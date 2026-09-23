<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Services\AutomaticChargerConnectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class AutomaticChargerConnectionController extends Controller
{
    protected $connectionService;

    public function __construct(AutomaticChargerConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Connecter automatiquement une borne
     */
    public function connectCharger(Request $request, $chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            Log::info('AutomaticChargerConnectionController: Connecting charger', [
                'charging_point_id' => $chargingPointId,
                'user_id' => auth()->id()
            ]);

            $result = $this->connectionService->connectChargingPoint($chargingPoint);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'charging_point_id' => $chargingPointId,
                        'charger_id' => $result['charger_id'],
                        'connected' => $result['connected'],
                        'duration_ms' => $result['duration_ms'] ?? null
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? 'Unknown error'
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('AutomaticChargerConnectionController: Error connecting charger', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion de la borne',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnecter une borne
     */
    public function disconnectCharger(Request $request, $chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            Log::info('AutomaticChargerConnectionController: Disconnecting charger', [
                'charging_point_id' => $chargingPointId,
                'user_id' => auth()->id()
            ]);

            $result = $this->connectionService->disconnectChargingPoint($chargingPoint);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'charging_point_id' => $chargingPointId,
                    'charger_id' => $result['charger_id'],
                    'connected' => $result['connected']
                ]
            ]);

        } catch (Exception $e) {
            Log::error('AutomaticChargerConnectionController: Error disconnecting charger', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion de la borne',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut de connexion d'une borne
     */
    public function getChargerStatus($chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            $result = $this->connectionService->getChargerConnectionStatus($chargingPoint);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Statut récupéré avec succès' : 'Erreur lors de la récupération du statut',
                'data' => [
                    'charging_point_id' => $chargingPointId,
                    'charger_id' => $result['charger_id'],
                    'connected' => $result['connected'] ?? false,
                    'cached' => $result['cached'] ?? false,
                    'last_checked' => $result['last_checked'] ?? null,
                    'status' => $result['status'] ?? null
                ],
                'error' => $result['error'] ?? null
            ]);

        } catch (Exception $e) {
            Log::error('AutomaticChargerConnectionController: Error getting charger status', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut de la borne',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connecter toutes les bornes d'un groupe
     */
    public function connectAllChargersInGroup(Request $request, $groupId): JsonResponse
    {
        try {
            $request->validate([
                'group_id' => 'required|integer|exists:groups,id'
            ]);

            Log::info('AutomaticChargerConnectionController: Connecting all chargers in group', [
                'group_id' => $groupId,
                'user_id' => auth()->id()
            ]);

            $result = $this->connectionService->connectAllChargersInGroup($groupId);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'group_id' => $groupId,
                    'total_chargers' => $result['total_chargers'] ?? 0,
                    'successful_connections' => $result['successful_connections'] ?? 0,
                    'results' => $result['results'] ?? []
                ],
                'error' => $result['error'] ?? null
            ]);

        } catch (Exception $e) {
            Log::error('AutomaticChargerConnectionController: Error connecting all chargers in group', [
                'group_id' => $groupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion des bornes du groupe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de connexion
     */
    public function getConnectionStats(): JsonResponse
    {
        try {
            $result = $this->connectionService->getConnectionStats();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Statistiques récupérées avec succès' : 'Erreur lors de la récupération des statistiques',
                'data' => [
                    'total_chargers' => $result['total_chargers'] ?? 0,
                    'connected' => $result['connected'] ?? 0,
                    'disconnected' => $result['disconnected'] ?? 0,
                    'connection_rate' => $result['connection_rate'] ?? 0
                ],
                'error' => $result['error'] ?? null
            ]);

        } catch (Exception $e) {
            Log::error('AutomaticChargerConnectionController: Error getting connection stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques de connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Nettoyer le cache de connexion
     */
    public function clearConnectionCache(Request $request, $chargingPointId = null): JsonResponse
    {
        try {
            if ($chargingPointId) {
                $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
                $this->connectionService->clearConnectionCache($chargingPoint);
                
                $message = 'Cache de connexion nettoyé pour la borne';
            } else {
                $this->connectionService->clearConnectionCache();
                $message = 'Cache de connexion nettoyé pour toutes les bornes';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'charging_point_id' => $chargingPointId,
                    'cleared_at' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('AutomaticChargerConnectionController: Error clearing connection cache', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du nettoyage du cache de connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier la connectivité du service Steve
     */
    public function testConnectivity(): JsonResponse
    {
        try {
            $result = $this->connectionService->getConnectionStats();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Service de connexion automatique opérationnel',
                    'data' => [
                        'service_status' => 'operational',
                        'total_chargers' => $result['total_chargers'],
                        'connected_chargers' => $result['connected'],
                        'connection_rate' => $result['connection_rate']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Service de connexion automatique non disponible',
                    'error' => $result['error'] ?? 'Unknown error'
                ], 503);
            }

        } catch (Exception $e) {
            Log::error('AutomaticChargerConnectionController: Error testing connectivity', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de connectivité',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
