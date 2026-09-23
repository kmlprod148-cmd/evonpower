<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Services\SteVeApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class AutomaticChargerConnectionService
{
    protected $steveApiService;
    protected $cachePrefix = 'charger_connection_';
    protected $cacheTtl = 300; // 5 minutes

    public function __construct(SteVeApiService $steveApiService)
    {
        $this->steveApiService = $steveApiService;
    }

    /**
     * Connecter automatiquement une borne
     */
    public function connectChargingPoint(ChargingPoint $chargingPoint): array
    {
        $chargerId = $this->getChargerId($chargingPoint);
        $cacheKey = $this->cachePrefix . $chargingPoint->id;

        try {
            Log::info('AutomaticChargerConnectionService: Starting automatic connection', [
                'charging_point_id' => $chargingPoint->id,
                'charger_id' => $chargerId
            ]);

            // Vérifier si la borne est déjà connectée
            if ($this->isChargerConnected($chargingPoint)) {
                Log::info('AutomaticChargerConnectionService: Charger already connected', [
                    'charging_point_id' => $chargingPoint->id,
                    'charger_id' => $chargerId
                ]);

                return [
                    'success' => true,
                    'message' => 'Borne déjà connectée',
                    'charger_id' => $chargerId,
                    'connected' => true,
                    'cached' => true
                ];
            }

            // Tenter la connexion via l'API Steve
            $result = $this->steveApiService->connectCharger($chargerId);

            if ($result['success']) {
                // Mettre en cache le statut de connexion
                Cache::put($cacheKey, [
                    'connected' => true,
                    'connected_at' => now()->toISOString(),
                    'charger_id' => $chargerId
                ], $this->cacheTtl);

                // Mettre à jour le statut de la borne dans la base de données
                $this->updateChargingPointStatus($chargingPoint, 'connected');

                Log::info('AutomaticChargerConnectionService: Charger connected successfully', [
                    'charging_point_id' => $chargingPoint->id,
                    'charger_id' => $chargerId,
                    'duration_ms' => $result['duration_ms'] ?? 0
                ]);

                return [
                    'success' => true,
                    'message' => 'Borne connectée avec succès',
                    'charger_id' => $chargerId,
                    'connected' => true,
                    'duration_ms' => $result['duration_ms'] ?? 0
                ];
            } else {
                Log::error('AutomaticChargerConnectionService: Failed to connect charger', [
                    'charging_point_id' => $chargingPoint->id,
                    'charger_id' => $chargerId,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);

                return [
                    'success' => false,
                    'message' => 'Échec de la connexion de la borne',
                    'charger_id' => $chargerId,
                    'connected' => false,
                    'error' => $result['error'] ?? 'Unknown error'
                ];
            }

        } catch (Exception $e) {
            Log::error('AutomaticChargerConnectionService: Exception during connection', [
                'charging_point_id' => $chargingPoint->id,
                'charger_id' => $chargerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la connexion de la borne',
                'charger_id' => $chargerId,
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier si une borne est connectée
     */
    public function isChargerConnected(ChargingPoint $chargingPoint): bool
    {
        $cacheKey = $this->cachePrefix . $chargingPoint->id;
        $cachedStatus = Cache::get($cacheKey);

        if ($cachedStatus && $cachedStatus['connected']) {
            return true;
        }

        // Vérifier via l'API Steve si pas en cache
        try {
            $chargerId = $this->getChargerId($chargingPoint);
            $statusResult = $this->steveApiService->getChargerStatus($chargerId);

            if ($statusResult['success'] && isset($statusResult['status'])) {
                $isConnected = $this->isStatusConnected($statusResult['status']);
                
                // Mettre en cache le résultat
                Cache::put($cacheKey, [
                    'connected' => $isConnected,
                    'checked_at' => now()->toISOString(),
                    'charger_id' => $chargerId
                ], $this->cacheTtl);

                return $isConnected;
            }

            return false;
        } catch (Exception $e) {
            Log::warning('AutomaticChargerConnectionService: Failed to check charger status', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Déconnecter une borne
     */
    public function disconnectChargingPoint(ChargingPoint $chargingPoint): array
    {
        $chargerId = $this->getChargerId($chargingPoint);
        $cacheKey = $this->cachePrefix . $chargingPoint->id;

        try {
            Log::info('AutomaticChargerConnectionService: Disconnecting charger', [
                'charging_point_id' => $chargingPoint->id,
                'charger_id' => $chargerId
            ]);

            // Supprimer du cache
            Cache::forget($cacheKey);

            // Mettre à jour le statut de la borne
            $this->updateChargingPointStatus($chargingPoint, 'disconnected');

            Log::info('AutomaticChargerConnectionService: Charger disconnected', [
                'charging_point_id' => $chargingPoint->id,
                'charger_id' => $chargerId
            ]);

            return [
                'success' => true,
                'message' => 'Borne déconnectée avec succès',
                'charger_id' => $chargerId,
                'connected' => false
            ];

        } catch (Exception $e) {
            Log::error('AutomaticChargerConnectionService: Exception during disconnection', [
                'charging_point_id' => $chargingPoint->id,
                'charger_id' => $chargerId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la déconnexion de la borne',
                'charger_id' => $chargerId,
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir le statut de connexion d'une borne
     */
    public function getChargerConnectionStatus(ChargingPoint $chargingPoint): array
    {
        $chargerId = $this->getChargerId($chargingPoint);
        $cacheKey = $this->cachePrefix . $chargingPoint->id;
        $cachedStatus = Cache::get($cacheKey);

        if ($cachedStatus) {
            return [
                'success' => true,
                'charger_id' => $chargerId,
                'connected' => $cachedStatus['connected'] ?? false,
                'cached' => true,
                'last_checked' => $cachedStatus['checked_at'] ?? $cachedStatus['connected_at'] ?? null
            ];
        }

        // Vérifier via l'API Steve
        try {
            $statusResult = $this->steveApiService->getChargerStatus($chargerId);

            if ($statusResult['success']) {
                $isConnected = $this->isStatusConnected($statusResult['status'] ?? []);

                // Mettre en cache le résultat
                Cache::put($cacheKey, [
                    'connected' => $isConnected,
                    'checked_at' => now()->toISOString(),
                    'charger_id' => $chargerId
                ], $this->cacheTtl);

                return [
                    'success' => true,
                    'charger_id' => $chargerId,
                    'connected' => $isConnected,
                    'cached' => false,
                    'status' => $statusResult['status'] ?? []
                ];
            } else {
                return [
                    'success' => false,
                    'charger_id' => $chargerId,
                    'connected' => false,
                    'error' => $statusResult['error'] ?? 'Failed to get status'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'charger_id' => $chargerId,
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Connecter automatiquement toutes les bornes d'un groupe
     */
    public function connectAllChargersInGroup($groupId): array
    {
        try {
            $chargingPoints = ChargingPoint::where('group_id', $groupId)->get();
            $results = [];

            foreach ($chargingPoints as $chargingPoint) {
                $result = $this->connectChargingPoint($chargingPoint);
                $results[] = [
                    'charging_point_id' => $chargingPoint->id,
                    'charger_id' => $this->getChargerId($chargingPoint),
                    'result' => $result
                ];
            }

            $successCount = collect($results)->where('result.success', true)->count();

            return [
                'success' => true,
                'message' => "Connexion de {$successCount}/" . count($chargingPoints) . " bornes réussie",
                'total_chargers' => count($chargingPoints),
                'successful_connections' => $successCount,
                'results' => $results
            ];

        } catch (Exception $e) {
            Log::error('AutomaticChargerConnectionService: Failed to connect all chargers in group', [
                'group_id' => $groupId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la connexion des bornes du groupe',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir l'ID de la borne
     */
    protected function getChargerId(ChargingPoint $chargingPoint): string
    {
        // Utiliser l'ID stocké ou générer automatiquement
        if ($chargingPoint->charge_box_id) {
            return $chargingPoint->charge_box_id;
        }

        return 'BORNE_' . $chargingPoint->id;
    }

    /**
     * Vérifier si un statut indique une connexion
     */
    protected function isStatusConnected($status): bool
    {
        if (is_array($status)) {
            $statusString = json_encode($status);
        } else {
            $statusString = (string) $status;
        }

        $connectedStatuses = ['available', 'connected', 'online', 'ready', 'idle'];
        $statusString = strtolower($statusString);

        foreach ($connectedStatuses as $connectedStatus) {
            if (strpos($statusString, $connectedStatus) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mettre à jour le statut de la borne dans la base de données
     */
    protected function updateChargingPointStatus(ChargingPoint $chargingPoint, string $status): void
    {
        try {
            // Mettre à jour le statut de connexion
            $chargingPoint->update([
                'connection_status' => $status,
                'last_connection_check' => now()
            ]);

            Log::debug('AutomaticChargerConnectionService: Updated charging point status', [
                'charging_point_id' => $chargingPoint->id,
                'status' => $status
            ]);
        } catch (Exception $e) {
            Log::warning('AutomaticChargerConnectionService: Failed to update charging point status', [
                'charging_point_id' => $chargingPoint->id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Nettoyer le cache des connexions
     */
    public function clearConnectionCache(ChargingPoint $chargingPoint = null): void
    {
        if ($chargingPoint) {
            $cacheKey = $this->cachePrefix . $chargingPoint->id;
            Cache::forget($cacheKey);
        } else {
            // Nettoyer tout le cache des connexions
            $pattern = $this->cachePrefix . '*';
            // Note: Laravel ne supporte pas les patterns de cache, donc on utilise une approche différente
            // En production, vous pourriez utiliser Redis avec des patterns
        }
    }

    /**
     * Obtenir les statistiques de connexion
     */
    public function getConnectionStats(): array
    {
        try {
            $totalChargingPoints = ChargingPoint::count();
            $connectedCount = 0;
            $disconnectedCount = 0;

            // Vérifier le statut de chaque borne
            $chargingPoints = ChargingPoint::all();
            foreach ($chargingPoints as $chargingPoint) {
                if ($this->isChargerConnected($chargingPoint)) {
                    $connectedCount++;
                } else {
                    $disconnectedCount++;
                }
            }

            return [
                'success' => true,
                'total_chargers' => $totalChargingPoints,
                'connected' => $connectedCount,
                'disconnected' => $disconnectedCount,
                'connection_rate' => $totalChargingPoints > 0 ? round(($connectedCount / $totalChargingPoints) * 100, 2) : 0
            ];

        } catch (Exception $e) {
            Log::error('AutomaticChargerConnectionService: Failed to get connection stats', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
