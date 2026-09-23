<?php

namespace App\Services;

use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Service unifié pour la synchronisation des données SteVe
 * 
 * Ce service consolide :
 * - SteveTransactionService (gestion des transactions OCPP)
 * - ChargingPointSteveSyncService (synchronisation des bornes)
 * 
 * Fonctionnalités :
 * - Synchronisation bidirectionnelle des bornes de charge
 * - Récupération et analyse des transactions OCPP
 * - Mise en cache des données fréquemment consultées
 */
class SteVeDataSyncService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('steve.api_url', '');
        $this->username = config('steve.username', '');
        $this->password = config('steve.password', '');
        $this->timeout = config('steve.timeout', 30);
    }

    // ========================================================================
    // CHARGING POINT SYNCHRONIZATION
    // ========================================================================

    /**
     * Synchroniser la création d'une borne vers SteVe
     */
    public function syncChargingPointCreate(ChargingPoint $chargingPoint, bool $autoSync = true): array
    {
        if (!$autoSync) {
            return ['success' => true, 'synced' => false, 'reason' => 'auto_sync_disabled'];
        }

        try {
            Log::info('SteVeDataSync: Syncing new charging point', [
                'charging_point_id' => $chargingPoint->id,
                'name' => $chargingPoint->name
            ]);

            $steveData = $this->mapChargingPointToSteveFormat($chargingPoint);
            $result = $this->makeRequest('POST', '/api/v1/charge-points', $steveData);

            if ($result['success']) {
                $chargingPoint->update([
                    'steve_charging_point_id' => $result['data']['chargeBoxId'] ?? $steveData['chargeBoxId'],
                    'steve_charge_box_pk' => $result['data']['chargeBoxPk'] ?? null,
                    'steve_synced_at' => now(),
                    'steve_sync_status' => 'synced'
                ]);

                Log::info('SteVeDataSync: Successfully synced to Steve', [
                    'charging_point_id' => $chargingPoint->id
                ]);

                return [
                    'success' => true,
                    'synced' => true,
                    'steve_data' => $result['data'],
                    'message' => 'Borne synchronisée avec Steve'
                ];
            }

            $chargingPoint->update([
                'steve_sync_status' => 'failed',
                'steve_sync_error' => $result['error'] ?? 'Unknown error'
            ]);

            return [
                'success' => false,
                'synced' => false,
                'error' => $result['error'] ?? 'Unknown error'
            ];

        } catch (Exception $e) {
            Log::error('SteVeDataSync: Exception during sync', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            $chargingPoint->update([
                'steve_sync_status' => 'failed',
                'steve_sync_error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Synchroniser la mise à jour d'une borne vers SteVe
     */
    public function syncChargingPointUpdate(ChargingPoint $chargingPoint): array
    {
        if (empty($chargingPoint->steve_charge_box_pk) && empty($chargingPoint->steve_charging_point_id)) {
            return ['success' => true, 'synced' => false, 'reason' => 'not_synced_with_steve'];
        }

        try {
            $steveData = $this->mapChargingPointToSteveFormat($chargingPoint);
            $steveId = $chargingPoint->steve_charge_box_pk ?? $chargingPoint->steve_charging_point_id;
            
            $result = $this->makeRequest('PUT', "/api/v1/charge-points/{$steveId}", $steveData);

            if ($result['success']) {
                $chargingPoint->update([
                    'steve_synced_at' => now(),
                    'steve_sync_status' => 'synced',
                    'steve_sync_error' => null
                ]);

                return [
                    'success' => true,
                    'synced' => true,
                    'message' => 'Mise à jour synchronisée avec Steve'
                ];
            }

            // Handle 404/410 - try to recreate
            $status = $result['status'] ?? null;
            if (in_array($status, [404, 410])) {
                return $this->syncChargingPointCreate($chargingPoint, true);
            }

            $chargingPoint->update([
                'steve_sync_status' => 'out_of_sync',
                'steve_sync_error' => $result['error'] ?? 'Unknown error'
            ]);

            return [
                'success' => false,
                'error' => $result['error'] ?? 'Unknown error'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Synchroniser la suppression d'une borne vers SteVe
     */
    public function syncChargingPointDelete(ChargingPoint $chargingPoint): array
    {
        if (empty($chargingPoint->steve_charge_box_pk)) {
            return ['success' => true, 'synced' => false, 'reason' => 'not_synced'];
        }

        try {
            $steveId = $chargingPoint->steve_charge_box_pk;
            $result = $this->makeRequest('DELETE', "/api/v1/charge-points/{$steveId}");

            if ($result['success']) {
                Log::info('SteVeDataSync: Deleted from Steve', [
                    'charging_point_id' => $chargingPoint->id
                ]);

                return [
                    'success' => true,
                    'synced' => true,
                    'message' => 'Borne supprimée de Steve'
                ];
            }

            return [
                'success' => false,
                'error' => $result['error'] ?? 'Unknown error'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Synchronisation complète de toutes les bornes
     */
    public function syncAllChargingPoints(): array
    {
        $results = [
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => []
        ];

        $chargingPoints = ChargingPoint::all();
        $results['total'] = $chargingPoints->count();

        foreach ($chargingPoints as $point) {
            if (empty($point->steve_charge_box_pk)) {
                $syncResult = $this->syncChargingPointCreate($point);
            } else {
                $syncResult = $this->syncChargingPointUpdate($point);
            }

            if ($syncResult['success'] && $syncResult['synced']) {
                $results['synced']++;
            } elseif (!$syncResult['success']) {
                $results['failed']++;
            } else {
                $results['skipped']++;
            }

            $results['details'][] = [
                'id' => $point->id,
                'name' => $point->name,
                'result' => $syncResult
            ];
        }

        return $results;
    }

    // ========================================================================
    // TRANSACTION MANAGEMENT
    // ========================================================================

    /**
     * Récupérer les transactions avec filtres
     */
    public function getTransactions(array $filters = []): array
    {
        try {
            $cleanedFilters = $this->cleanTransactionFilters($filters);
            $queryString = http_build_query($cleanedFilters);
            $endpoint = '/api/v1/transactions' . ($queryString ? '?' . $queryString : '');

            Log::info('SteVeDataSync: Getting transactions', ['filters' => $cleanedFilters]);

            $result = $this->makeRequest('GET', $endpoint);

            if ($result['success']) {
                $enrichedData = $this->enrichTransactions($result['data'] ?? []);

                return [
                    'success' => true,
                    'data' => $enrichedData,
                    'count' => count($enrichedData),
                    'filters' => $cleanedFilters
                ];
            }

            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer une transaction par PK
     */
    public function getTransactionByPk(int $transactionPk): array
    {
        return $this->getTransactions(['transactionPk' => $transactionPk]);
    }

    /**
     * Récupérer les transactions actives
     */
    public function getActiveTransactions(?string $chargeBoxId = null): array
    {
        $filters = ['type' => 'ACTIVE'];
        if ($chargeBoxId) {
            $filters['chargeBoxId'] = $chargeBoxId;
        }
        return $this->getTransactions($filters);
    }

    /**
     * Récupérer les transactions arrêtées
     */
    public function getStoppedTransactions(?string $chargeBoxId = null): array
    {
        $filters = ['type' => 'STOPPED'];
        if ($chargeBoxId) {
            $filters['chargeBoxId'] = $chargeBoxId;
        }
        return $this->getTransactions($filters);
    }

    /**
     * Récupérer les transactions d'aujourd'hui
     */
    public function getTodayTransactions(?string $chargeBoxId = null): array
    {
        $filters = ['periodType' => 'TODAY'];
        if ($chargeBoxId) {
            $filters['chargeBoxId'] = $chargeBoxId;
        }
        return $this->getTransactions($filters);
    }

    /**
     * Récupérer les transactions par période
     */
    public function getTransactionsByPeriod(string $from, string $to, ?string $chargeBoxId = null): array
    {
        $filters = [
            'periodType' => 'FROM_TO',
            'from' => $from,
            'to' => $to
        ];
        if ($chargeBoxId) {
            $filters['chargeBoxId'] = $chargeBoxId;
        }
        return $this->getTransactions($filters);
    }

    /**
     * Récupérer les transactions par borne
     */
    public function getTransactionsByChargeBox(string $chargeBoxId, ?string $type = null): array
    {
        $filters = ['chargeBoxId' => $chargeBoxId];
        if ($type) {
            $filters['type'] = $type;
        }
        return $this->getTransactions($filters);
    }

    /**
     * Obtenir les statistiques de transactions
     */
    public function getTransactionStats(?string $chargeBoxId = null): array
    {
        try {
            $todayTransactions = $this->getTodayTransactions($chargeBoxId);
            $activeTransactions = $this->getActiveTransactions($chargeBoxId);

            $stats = [
                'today_count' => $todayTransactions['count'] ?? 0,
                'active_count' => $activeTransactions['count'] ?? 0,
                'today_energy' => 0,
                'today_duration' => 0
            ];

            if ($todayTransactions['success'] && !empty($todayTransactions['data'])) {
                foreach ($todayTransactions['data'] as $tx) {
                    $stats['today_energy'] += $tx['energy_consumed'] ?? 0;
                    $stats['today_duration'] += $tx['duration_minutes'] ?? 0;
                }
            }

            return [
                'success' => true,
                'data' => $stats
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ========================================================================
    // METER VALUES
    // ========================================================================

    /**
     * Récupérer les valeurs de compteur pour une transaction
     */
    public function getMeterValues(int $transactionPk): array
    {
        return $this->makeRequest('GET', "/api/v1/transactions/{$transactionPk}/meter-values");
    }

    // ========================================================================
    // HTTP CLIENT
    // ========================================================================

    /**
     * Effectuer une requête HTTP vers SteVe
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;
            
            $httpClient = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]);

            $response = match (strtoupper($method)) {
                'GET' => $httpClient->get($url),
                'POST' => $httpClient->post($url, $data),
                'PUT' => $httpClient->put($url, $data),
                'DELETE' => $httpClient->delete($url),
                default => throw new Exception("Méthode HTTP non supportée: {$method}"),
            };

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json() ?? [],
                    'status' => $response->status()
                ];
            }

            return [
                'success' => false,
                'error' => "HTTP {$response->status()}",
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Mapper une borne locale au format SteVe
     */
    protected function mapChargingPointToSteveFormat(ChargingPoint $chargingPoint): array
    {
        return [
            'chargeBoxId' => $chargingPoint->charge_box_id ?? 'CP_' . $chargingPoint->id,
            'description' => $chargingPoint->name ?? 'Point de charge #' . $chargingPoint->id,
            'locationLatitude' => $chargingPoint->latitude,
            'locationLongitude' => $chargingPoint->longitude,
            'note' => $chargingPoint->description ?? '',
            'registrationStatus' => $chargingPoint->status === 'active' ? 'ACCEPTED' : 'PENDING',
            'insertConnectorStatusAfterTransactionMsg' => true
        ];
    }

    /**
     * Nettoyer les filtres de transaction
     */
    protected function cleanTransactionFilters(array $filters): array
    {
        $validKeys = [
            'transactionPk', 'type', 'periodType', 'chargeBoxId',
            'ocppIdTag', 'userId', 'from', 'to'
        ];

        $cleaned = [];
        foreach ($filters as $key => $value) {
            if (in_array($key, $validKeys) && !empty($value)) {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }

    /**
     * Enrichir les données de transaction
     */
    protected function enrichTransactions(array $transactions): array
    {
        return array_map(function ($tx) {
            // Calculer la durée en minutes si possible
            if (isset($tx['startTimestamp']) && isset($tx['stopTimestamp'])) {
                $start = Carbon::parse($tx['startTimestamp']);
                $stop = Carbon::parse($tx['stopTimestamp']);
                $tx['duration_minutes'] = $start->diffInMinutes($stop);
            }

            // Calculer l'énergie consommée
            if (isset($tx['stopValue']) && isset($tx['startValue'])) {
                $tx['energy_consumed'] = ($tx['stopValue'] - $tx['startValue']) / 1000; // Wh to kWh
            }

            // Ajouter le statut lisible
            $tx['status_label'] = match ($tx['stopTimestamp'] ?? null) {
                null => 'En cours',
                default => 'Terminée'
            };

            return $tx;
        }, $transactions);
    }

    /**
     * Vider le cache de synchronisation
     */
    public function clearSyncCache(): void
    {
        Cache::forget('steve_sync_status');
        Cache::forget('steve_transactions_cache');
    }

    /**
     * Obtenir le statut de synchronisation
     */
    public function getSyncStatus(): array
    {
        $totalPoints = ChargingPoint::count();
        $syncedPoints = ChargingPoint::where('steve_sync_status', 'synced')->count();
        $failedPoints = ChargingPoint::where('steve_sync_status', 'failed')->count();
        $outOfSyncPoints = ChargingPoint::where('steve_sync_status', 'out_of_sync')->count();

        return [
            'total_charging_points' => $totalPoints,
            'synced' => $syncedPoints,
            'failed' => $failedPoints,
            'out_of_sync' => $outOfSyncPoints,
            'not_synced' => $totalPoints - $syncedPoints - $failedPoints - $outOfSyncPoints,
            'sync_percentage' => $totalPoints > 0 ? round(($syncedPoints / $totalPoints) * 100, 1) : 0
        ];
    }
}
