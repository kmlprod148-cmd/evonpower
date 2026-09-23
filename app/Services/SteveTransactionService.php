<?php

namespace App\Services;

use App\Exceptions\SteVeConfigurationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

/**
 * Service pour gérer les transactions OCPP depuis l'API Steve
 * 
 * @deprecated Use SteVeDataSyncService instead. This class will be removed in a future version.
 * 
 * Ce service permet de récupérer, filtrer et analyser les transactions
 * enregistrées dans le serveur Steve selon le protocole OCPP
 */
class SteveTransactionService
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

    /**
     * Récupérer les transactions depuis Steve avec filtres
     * 
     * @param array $filters Filtres possibles:
     *   - transactionPk: int - Clé primaire de la transaction
     *   - type: string - ALL|ACTIVE|STOPPED
     *   - periodType: string - ALL|TODAY|LAST_10|LAST_30|LAST_90|FROM_TO
     *   - chargeBoxId: string - ID de la borne
     *   - ocppIdTag: string - ID du tag OCPP
     *   - userId: int - ID de l'utilisateur
     *   - from: string - Date début (ISO 8601)
     *   - to: string - Date fin (ISO 8601)
     * @return array
     */
    public function getTransactions(array $filters = []): array
    {
        try {
            // Nettoyer et valider les filtres
            $cleanedFilters = $this->cleanFilters($filters);

            // Construire la query string
            $queryString = $this->buildQueryString($cleanedFilters);
            $endpoint = $this->apiPath('/transactions') . ($queryString ? '?' . $queryString : '');

            Log::info('SteveTransactionService: Récupération des transactions', [
                'filters' => $cleanedFilters,
                'endpoint' => $endpoint
            ]);

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->get(rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/'));

            if ($response->successful()) {
                $data = $response->json();
                
                // Enrichir les données
                $enrichedData = $this->enrichTransactions($data);

                Log::info('SteveTransactionService: Transactions récupérées avec succès', [
                    'count' => count($enrichedData),
                    'filters_applied' => $cleanedFilters
                ]);

                return [
                    'success' => true,
                    'data' => $enrichedData,
                    'count' => count($enrichedData),
                    'filters' => $cleanedFilters,
                    'message' => 'Transactions récupérées avec succès'
                ];
            }

            Log::error('SteveTransactionService: Échec récupération transactions', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Échec de la récupération des transactions',
                'status_code' => $response->status(),
                'error' => $response->json()['message'] ?? $response->body()
            ];

        } catch (Exception $e) {
            Log::error('SteveTransactionService: Exception getTransactions', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la récupération des transactions'
            ];
        }
    }

    /**
     * Récupérer une transaction spécifique par sa clé primaire
     * 
     * @param int $transactionPk
     * @return array
     */
    public function getTransactionByPk(int $transactionPk): array
    {
        return $this->getTransactions(['transactionPk' => $transactionPk]);
    }

    /**
     * Récupérer les transactions actives
     * 
     * @param string|null $chargeBoxId Filtrer par borne (optionnel)
     * @param string|null $ocppIdTag Filtrer par tag (optionnel)
     * @return array
     */
    public function getActiveTransactions(?string $chargeBoxId = null, ?string $ocppIdTag = null): array
    {
        $filters = ['type' => 'ACTIVE'];
        
        if ($chargeBoxId) {
            $filters['chargeBoxId'] = $chargeBoxId;
        }
        
        if ($ocppIdTag) {
            $filters['ocppIdTag'] = $ocppIdTag;
        }

        return $this->getTransactions($filters);
    }

    /**
     * Récupérer les transactions arrêtées
     * 
     * @param string|null $chargeBoxId Filtrer par borne (optionnel)
     * @return array
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
     * 
     * @param string|null $chargeBoxId Filtrer par borne (optionnel)
     * @return array
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
     * Récupérer les transactions des 10 derniers jours
     * 
     * @param string|null $chargeBoxId
     * @return array
     */
    public function getLast10DaysTransactions(?string $chargeBoxId = null): array
    {
        $filters = ['periodType' => 'LAST_10'];
        
        if ($chargeBoxId) {
            $filters['chargeBoxId'] = $chargeBoxId;
        }

        return $this->getTransactions($filters);
    }

    /**
     * Récupérer les transactions des 30 derniers jours
     * 
     * @param string|null $chargeBoxId
     * @return array
     */
    public function getLast30DaysTransactions(?string $chargeBoxId = null): array
    {
        $filters = ['periodType' => 'LAST_30'];
        
        if ($chargeBoxId) {
            $filters['chargeBoxId'] = $chargeBoxId;
        }

        return $this->getTransactions($filters);
    }

    /**
     * Récupérer les transactions des 90 derniers jours
     * 
     * @param string|null $chargeBoxId
     * @return array
     */
    public function getLast90DaysTransactions(?string $chargeBoxId = null): array
    {
        $filters = ['periodType' => 'LAST_90'];
        
        if ($chargeBoxId) {
            $filters['chargeBoxId'] = $chargeBoxId;
        }

        return $this->getTransactions($filters);
    }

    /**
     * Récupérer les transactions dans une période donnée
     * 
     * @param Carbon|string $from Date de début
     * @param Carbon|string $to Date de fin
     * @param string|null $chargeBoxId
     * @return array
     */
    public function getTransactionsByPeriod($from, $to, ?string $chargeBoxId = null): array
    {
        // Convertir en Carbon si nécessaire
        $fromCarbon = $from instanceof Carbon ? $from : Carbon::parse($from);
        $toCarbon = $to instanceof Carbon ? $to : Carbon::parse($to);

        $filters = [
            'periodType' => 'FROM_TO',
            'from' => $fromCarbon->toISOString(),
            'to' => $toCarbon->toISOString()
        ];
        
        if ($chargeBoxId) {
            $filters['chargeBoxId'] = $chargeBoxId;
        }

        return $this->getTransactions($filters);
    }

    /**
     * Récupérer les transactions par tag OCPP
     * 
     * @param string $ocppIdTag
     * @param string $type ALL|ACTIVE|STOPPED
     * @return array
     */
    public function getTransactionsByTag(string $ocppIdTag, string $type = 'ALL'): array
    {
        return $this->getTransactions([
            'ocppIdTag' => $ocppIdTag,
            'type' => $type
        ]);
    }

    /**
     * Récupérer les transactions par utilisateur Steve
     * 
     * @param int $userId
     * @param string $type ALL|ACTIVE|STOPPED
     * @return array
     */
    public function getTransactionsByUser(int $userId, string $type = 'ALL'): array
    {
        return $this->getTransactions([
            'userId' => $userId,
            'type' => $type
        ]);
    }

    /**
     * Obtenir les statistiques des transactions
     * 
     * @param array $filters
     * @return array
     */
    public function getTransactionStatistics(array $filters = []): array
    {
        try {
            $result = $this->getTransactions($filters);

            if (!$result['success']) {
                return $result;
            }

            $transactions = $result['data'];

            // Calculer les statistiques
            $stats = [
                'total_count' => count($transactions),
                'active_count' => 0,
                'stopped_count' => 0,
                'total_energy_wh' => 0,
                'average_energy_wh' => 0,
                'total_duration_minutes' => 0,
                'average_duration_minutes' => 0,
                'by_charge_box' => [],
                'by_tag' => [],
                'by_connector' => [],
            ];

            foreach ($transactions as $transaction) {
                // Compter actives vs arrêtées
                if (empty($transaction['stopTimestamp'])) {
                    $stats['active_count']++;
                } else {
                    $stats['stopped_count']++;
                }

                // Calculer l'énergie consommée
                if (!empty($transaction['stopValue']) && !empty($transaction['startValue'])) {
                    $energy = (float) $transaction['stopValue'] - (float) $transaction['startValue'];
                    $stats['total_energy_wh'] += $energy;
                    $transaction['energy_consumed_wh'] = $energy;
                }

                // Calculer la durée
                if (!empty($transaction['startTimestamp']) && !empty($transaction['stopTimestamp'])) {
                    $start = Carbon::parse($transaction['startTimestamp']);
                    $stop = Carbon::parse($transaction['stopTimestamp']);
                    $duration = $stop->diffInMinutes($start);
                    $stats['total_duration_minutes'] += $duration;
                    $transaction['duration_minutes'] = $duration;
                }

                // Statistiques par borne
                $chargeBoxId = $transaction['chargeBoxId'] ?? 'unknown';
                if (!isset($stats['by_charge_box'][$chargeBoxId])) {
                    $stats['by_charge_box'][$chargeBoxId] = ['count' => 0, 'energy_wh' => 0];
                }
                $stats['by_charge_box'][$chargeBoxId]['count']++;
                if (isset($transaction['energy_consumed_wh'])) {
                    $stats['by_charge_box'][$chargeBoxId]['energy_wh'] += $transaction['energy_consumed_wh'];
                }

                // Statistiques par tag
                $tag = $transaction['ocppIdTag'] ?? 'unknown';
                if (!isset($stats['by_tag'][$tag])) {
                    $stats['by_tag'][$tag] = ['count' => 0, 'energy_wh' => 0];
                }
                $stats['by_tag'][$tag]['count']++;
                if (isset($transaction['energy_consumed_wh'])) {
                    $stats['by_tag'][$tag]['energy_wh'] += $transaction['energy_consumed_wh'];
                }

                // Statistiques par connecteur
                $connectorId = $transaction['connectorId'] ?? 0;
                if (!isset($stats['by_connector'][$connectorId])) {
                    $stats['by_connector'][$connectorId] = ['count' => 0];
                }
                $stats['by_connector'][$connectorId]['count']++;
            }

            // Calculer les moyennes
            if ($stats['stopped_count'] > 0) {
                $stats['average_energy_wh'] = round($stats['total_energy_wh'] / $stats['stopped_count'], 2);
                $stats['average_duration_minutes'] = round($stats['total_duration_minutes'] / $stats['stopped_count'], 2);
            }

            // Convertir l'énergie en kWh pour faciliter la lecture
            $stats['total_energy_kwh'] = round($stats['total_energy_wh'] / 1000, 2);
            $stats['average_energy_kwh'] = round($stats['average_energy_wh'] / 1000, 2);

            return [
                'success' => true,
                'statistics' => $stats,
                'filters_applied' => $filters
            ];

        } catch (Exception $e) {
            Log::error('SteveTransactionService: Exception getTransactionStatistics', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors du calcul des statistiques'
            ];
        }
    }

    /**
     * Obtenir le résumé des transactions pour un point de charge
     * 
     * @param string $chargeBoxId
     * @return array
     */
    public function getChargeBoxSummary(string $chargeBoxId): array
    {
        try {
            // Récupérer toutes les transactions de cette borne
            $allTransactions = $this->getTransactions(['chargeBoxId' => $chargeBoxId]);

            // Récupérer les transactions actives
            $activeTransactions = $this->getActiveTransactions($chargeBoxId);

            // Récupérer les transactions d'aujourd'hui
            $todayTransactions = $this->getTodayTransactions($chargeBoxId);

            // Récupérer les transactions des 30 derniers jours
            $last30Days = $this->getLast30DaysTransactions($chargeBoxId);

            // Statistiques globales
            $stats = $this->getTransactionStatistics(['chargeBoxId' => $chargeBoxId]);

            // Bug #6 (full pass): if ANY child call failed, propagate the failure.
            // Without this, a SteVe outage produced `success:true` summaries with
            // zero counts — indistinguishable from a quiet charger.
            foreach ([$allTransactions, $activeTransactions, $todayTransactions, $last30Days, $stats] as $child) {
                if (is_array($child) && ($child['success'] ?? true) === false) {
                    return [
                        'success' => false,
                        'error'   => $child['error'] ?? 'Erreur upstream SteVe',
                        'message' => 'Impossible de récupérer le résumé : appel SteVe en échec',
                    ];
                }
            }

            return [
                'success' => true,
                'charge_box_id' => $chargeBoxId,
                'summary' => [
                    'total_transactions' => $allTransactions['count'] ?? 0,
                    'active_transactions' => $activeTransactions['count'] ?? 0,
                    'today_transactions' => $todayTransactions['count'] ?? 0,
                    'last_30_days_transactions' => $last30Days['count'] ?? 0,
                    'statistics' => $stats['statistics'] ?? null
                ],
                'active_transactions_data' => $activeTransactions['data'] ?? [],
                'today_transactions_data' => $todayTransactions['data'] ?? [],
            ];

        } catch (Exception $e) {
            Log::error('SteveTransactionService: Exception getChargeBoxSummary', [
                'charge_box_id' => $chargeBoxId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la récupération du résumé'
            ];
        }
    }

    /**
     * Obtenir le résumé des transactions pour un tag OCPP
     * 
     * @param string $ocppIdTag
     * @return array
     */
    public function getTagSummary(string $ocppIdTag): array
    {
        try {
            // Récupérer toutes les transactions de ce tag
            $allTransactions = $this->getTransactionsByTag($ocppIdTag, 'ALL');

            // Récupérer les transactions actives
            $activeTransactions = $this->getTransactionsByTag($ocppIdTag, 'ACTIVE');

            // Statistiques
            $stats = $this->getTransactionStatistics(['ocppIdTag' => $ocppIdTag]);

            // Bug #6 (full pass): same propagation as getChargeBoxSummary.
            foreach ([$allTransactions, $activeTransactions, $stats] as $child) {
                if (is_array($child) && ($child['success'] ?? true) === false) {
                    return [
                        'success' => false,
                        'error'   => $child['error'] ?? 'Erreur upstream SteVe',
                        'message' => 'Impossible de récupérer le résumé du tag : appel SteVe en échec',
                    ];
                }
            }

            return [
                'success' => true,
                'ocpp_id_tag' => $ocppIdTag,
                'summary' => [
                    'total_transactions' => $allTransactions['count'] ?? 0,
                    'active_transactions' => $activeTransactions['count'] ?? 0,
                    'statistics' => $stats['statistics'] ?? null
                ],
                'active_transactions_data' => $activeTransactions['data'] ?? [],
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la récupération du résumé du tag'
            ];
        }
    }

    /**
     * Nettoyer et valider les filtres
     * 
     * @param array $filters
     * @return array
     */
    protected function cleanFilters(array $filters): array
    {
        $cleaned = [];
        
        // Filtres acceptés par l'API Steve
        $allowedFilters = [
            'transactionPk', 'type', 'periodType', 'chargeBoxId',
            'ocppIdTag', 'connectorId', 'userId', 'from', 'to'
        ];

        foreach ($allowedFilters as $filter) {
            if (isset($filters[$filter]) && $filters[$filter] !== '' && $filters[$filter] !== null) {
                $cleaned[$filter] = $filters[$filter];
            }
        }

        // Valider les types énumérés
        if (isset($cleaned['type'])) {
            $cleaned['type'] = strtoupper((string) $cleaned['type']);
            $cleaned['type'] = match ($cleaned['type']) {
                'COMPLETED' => 'STOPPED',
                'EVERYTHING' => 'ALL',
                default => $cleaned['type'],
            };
        }

        if (isset($cleaned['type']) && !in_array($cleaned['type'], ['ALL', 'ACTIVE', 'STOPPED'], true)) {
            unset($cleaned['type']);
        }

        if (isset($cleaned['periodType'])) {
            $cleaned['periodType'] = strtoupper((string) $cleaned['periodType']);
            $cleaned['periodType'] = match ($cleaned['periodType']) {
                'LAST_7' => 'LAST_10',
                default => $cleaned['periodType'],
            };
        }

        if (isset($cleaned['periodType']) && !in_array($cleaned['periodType'], ['ALL', 'TODAY', 'LAST_10', 'LAST_30', 'LAST_90', 'FROM_TO'], true)) {
            unset($cleaned['periodType']);
        }

        // Valider FROM_TO
        if (isset($cleaned['periodType']) && $cleaned['periodType'] === 'FROM_TO') {
            if (empty($cleaned['from']) || empty($cleaned['to'])) {
                unset($cleaned['periodType']);
                Log::warning('SteveTransactionService: FROM_TO requiert from et to');
            }
        }

        return $cleaned;
    }

    /**
     * Build an endpoint relative to the configured SteVe base URL.
     *
     * Aligned to SteVe 3.9.0-SNAPSHOT: the management REST API lives under
     * `/manager/api/v1/*`. Mirrors `SteVeHttpClientService::apiPath()` so both
     * clients honour the same set of accepted base-URL shapes:
     *   - `…/manager/api/v1`  → already canonical, no prefix
     *   - `…/manager`         → append `/api/v1`
     *   - `…/api/v1` (legacy) → no prefix (still hits SteVe's legacy mount)
     *   - anything else       → prepend `/manager/api/v1`
     */
    protected function apiPath(string $path): string
    {
        if (trim($this->baseUrl) === '') {
            throw new SteVeConfigurationException(
                'SteVe base URL is not configured. Set STEVE_API_URL in .env.'
            );
        }

        $basePath = rtrim(parse_url($this->baseUrl, PHP_URL_PATH) ?: '', '/');

        $prefix = match (true) {
            str_ends_with($basePath, '/manager/api/v1') => '',
            str_ends_with($basePath, '/manager')        => '/api/v1',
            str_ends_with($basePath, '/api/v1')         => '',
            default                                     => '/api/v1',
        };

        return $prefix . '/' . ltrim($path, '/');
    }

    protected function buildQueryString(array $filters): string
    {
        $parts = [];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            foreach ((array) $value as $item) {
                if ($item === null || $item === '') {
                    continue;
                }

                $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $item);
            }
        }

        return implode('&', $parts);
    }

    /**
     * Enrichir les transactions avec des données calculées
     * 
     * @param array $transactions
     * @return array
     */
    protected function enrichTransactions(array $transactions): array
    {
        return array_map(function($transaction) {
            // Calculer l'énergie consommée
            if (!empty($transaction['stopValue']) && !empty($transaction['startValue'])) {
                $startValue = (float) str_replace(',', '.', $transaction['startValue']);
                $stopValue = (float) str_replace(',', '.', $transaction['stopValue']);
                $transaction['energy_consumed_wh'] = $stopValue - $startValue;
                $transaction['energy_consumed_kwh'] = round($transaction['energy_consumed_wh'] / 1000, 2);
            } else {
                $transaction['energy_consumed_wh'] = null;
                $transaction['energy_consumed_kwh'] = null;
            }

            // Calculer la durée
            if (!empty($transaction['startTimestamp']) && !empty($transaction['stopTimestamp'])) {
                $start = Carbon::parse($transaction['startTimestamp']);
                $stop = Carbon::parse($transaction['stopTimestamp']);
                $transaction['duration_minutes'] = $stop->diffInMinutes($start);
                $transaction['duration_hours'] = round($transaction['duration_minutes'] / 60, 2);
                $transaction['duration_formatted'] = $this->formatDuration($transaction['duration_minutes']);
            } else {
                $transaction['duration_minutes'] = null;
                $transaction['duration_hours'] = null;
                $transaction['duration_formatted'] = null;
            }

            // Statut enrichi
            $transaction['is_active'] = empty($transaction['stopTimestamp']);
            $transaction['status'] = $transaction['is_active'] ? 'active' : 'stopped';

            // Formater les dates
            if (!empty($transaction['startTimestamp'])) {
                $transaction['start_formatted'] = Carbon::parse($transaction['startTimestamp'])->format('d/m/Y H:i:s');
            }
            if (!empty($transaction['stopTimestamp'])) {
                $transaction['stop_formatted'] = Carbon::parse($transaction['stopTimestamp'])->format('d/m/Y H:i:s');
            }

            return $transaction;
        }, $transactions);
    }

    /**
     * Formater une durée en minutes en format lisible
     * 
     * @param int $minutes
     * @return string
     */
    protected function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$mins}min";
        }

        return "{$mins}min";
    }

    /**
     * Exporter les transactions en CSV
     * 
     * @param array $filters
     * @return array
     */
    public function exportTransactionsToCsv(array $filters = []): array
    {
        $result = $this->getTransactions($filters);

        if (!$result['success']) {
            return $result;
        }

        $transactions = $result['data'];

        $csv = [];
        $csv[] = [
            'Transaction ID',
            'ChargeBox ID',
            'Connector ID',
            'OCPP Tag',
            'Début',
            'Fin',
            'Durée (min)',
            'Énergie (kWh)',
            'Valeur Début (Wh)',
            'Valeur Fin (Wh)',
            'Raison Arrêt',
            'Acteur Arrêt',
            'Statut'
        ];

        foreach ($transactions as $transaction) {
            $csv[] = [
                $transaction['id'] ?? '',
                $transaction['chargeBoxId'] ?? '',
                $transaction['connectorId'] ?? '',
                $transaction['ocppIdTag'] ?? '',
                $transaction['start_formatted'] ?? '',
                $transaction['stop_formatted'] ?? '',
                $transaction['duration_minutes'] ?? '',
                $transaction['energy_consumed_kwh'] ?? '',
                $transaction['startValue'] ?? '',
                $transaction['stopValue'] ?? '',
                $transaction['stopReason'] ?? '',
                $transaction['stopEventActor'] ?? '',
                $transaction['status'] ?? '',
            ];
        }

        return [
            'success' => true,
            'csv_data' => $csv,
            'count' => count($transactions),
            'filename' => 'steve_transactions_' . date('Y-m-d_His') . '.csv'
        ];
    }

    /**
     * Synchroniser les transactions Steve avec la base de données locale
     * 
     * @param string|null $chargeBoxId
     * @return array
     */
    public function syncTransactionsToLocal(?string $chargeBoxId = null): array
    {
        try {
            $filters = [];
            if ($chargeBoxId) {
                $filters['chargeBoxId'] = $chargeBoxId;
            }

            $result = $this->getTransactions($filters);

            if (!$result['success']) {
                return $result;
            }

            $steveTransactions = $result['data'];
            $syncedCount = 0;
            $updatedCount = 0;
            $errors = [];

            // Importer dans la table locale Transaction
            $transactionModel = new \App\Models\Transaction();

            foreach ($steveTransactions as $steveTransaction) {
                try {
                    // Chercher le charging point local
                    $chargingPoint = \App\Models\ChargingPoint::where(function($q) use ($steveTransaction) {
                        $q->where('charge_box_id', $steveTransaction['chargeBoxId'])
                          ->orWhere('steve_charging_point_id', $steveTransaction['chargeBoxId']);
                    })->first();

                    if (!$chargingPoint) {
                        $errors[] = "ChargingPoint non trouvé pour ChargeBoxId: {$steveTransaction['chargeBoxId']}";
                        continue;
                    }

                    // Chercher ou créer la transaction locale
                    $localTransaction = $transactionModel->firstOrNew([
                        'steve_transaction_id' => $steveTransaction['id']
                    ]);

                    $isNew = !$localTransaction->exists;

                    // Mapper les données
                    $localTransaction->charging_point_id = $chargingPoint->id;
                    $localTransaction->ocpp_id_tag = $steveTransaction['ocppIdTag'] ?? null;
                    $localTransaction->connector_id = $steveTransaction['connectorId'] ?? null;
                    $localTransaction->start_timestamp = $steveTransaction['startTimestamp'] ?? null;
                    $localTransaction->stop_timestamp = $steveTransaction['stopTimestamp'] ?? null;
                    $localTransaction->start_value = $steveTransaction['startValue'] ?? null;
                    $localTransaction->stop_value = $steveTransaction['stopValue'] ?? null;
                    $localTransaction->stop_reason = $steveTransaction['stopReason'] ?? null;
                    $localTransaction->stop_event_actor = $steveTransaction['stopEventActor'] ?? null;
                    $localTransaction->energy_consumed_wh = $steveTransaction['energy_consumed_wh'] ?? null;
                    $localTransaction->duration_minutes = $steveTransaction['duration_minutes'] ?? null;
                    $localTransaction->status = $steveTransaction['is_active'] ? 'active' : 'completed';

                    $localTransaction->save();

                    if ($isNew) {
                        $syncedCount++;
                    } else {
                        $updatedCount++;
                    }

                } catch (Exception $e) {
                    $errors[] = "Transaction ID {$steveTransaction['id']}: {$e->getMessage()}";
                    Log::warning('SteveTransactionService: Erreur sync transaction', [
                        'steve_transaction_id' => $steveTransaction['id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return [
                'success' => true,
                'synced_count' => $syncedCount,
                'updated_count' => $updatedCount,
                'total_processed' => count($steveTransactions),
                'errors' => $errors,
                'message' => "Synchronisation terminée: {$syncedCount} nouvelles, {$updatedCount} mises à jour"
            ];

        } catch (Exception $e) {
            Log::error('SteveTransactionService: Exception syncTransactionsToLocal', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la synchronisation'
            ];
        }
    }

    /**
     * Obtenir les transactions avec cache
     * 
     * @param array $filters
     * @param int $cacheDuration Durée du cache en secondes (défaut: 5 minutes)
     * @return array
     */
    public function getTransactionsCached(array $filters = [], int $cacheDuration = 300): array
    {
        $cacheKey = 'steve_transactions_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, $cacheDuration, function() use ($filters) {
            return $this->getTransactions($filters);
        });
    }

    /**
     * Vider le cache des transactions
     */
    public function clearTransactionsCache(): void
    {
        // Supprimer tous les caches commençant par 'steve_transactions_'
        $cacheKeys = Cache::get('steve_transactions_keys', []);
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        Cache::forget('steve_transactions_keys');
    }

    /**
     * Obtenir la configuration du service
     * 
     * @return array
     */
    public function getConfig(): array
    {
        $endpoint = '/api/v1/transactions';
        if (trim($this->baseUrl) !== '') {
            try {
                $endpoint = $this->apiPath('/transactions');
            } catch (SteVeConfigurationException) {
                // base url empty — keep documented default
            }
        }

        return [
            'base_url'     => $this->baseUrl,
            'timeout'      => $this->timeout,
            'username'     => $this->username,
            'api_endpoint' => $endpoint,
        ];
    }
}

