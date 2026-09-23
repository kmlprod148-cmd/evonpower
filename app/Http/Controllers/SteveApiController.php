<?php

namespace App\Http\Controllers;

use App\Services\SteVeApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class SteveApiController extends Controller
{
    protected $steveApiService;

    public function __construct(SteVeApiService $steveApiService)
    {
        $this->steveApiService = $steveApiService;
    }

    /**
     * Connecter une borne au serveur Steve
     */
    public function connectCharger(Request $request, $chargingPointId): JsonResponse
    {
        try {
            $request->validate([
                'charger_id' => 'required|string',
                'websocket_url' => 'required|string'
            ]);

            $chargerId = $request->input('charger_id');
            
            Log::info('SteveApiController: Connecting charger', [
                'charging_point_id' => $chargingPointId,
                'charger_id' => $chargerId,
                'websocket_url' => $request->input('websocket_url')
            ]);

            $result = $this->steveApiService->connectCharger($chargerId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Borne connectée avec succès',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la connexion de la borne',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('SteveApiController: Error connecting charger', [
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
     * Démarrer une session de charge
     */
    public function startCharge(Request $request, $chargingPointId): JsonResponse
    {
        try {
            $request->validate([
                'session_id' => 'required|string',
                'charger_id' => 'required|string'
            ]);

            $sessionId = $request->input('session_id');
            $chargerId = $request->input('charger_id');

            Log::info('SteveApiController: Starting charge', [
                'charging_point_id' => $chargingPointId,
                'session_id' => $sessionId,
                'charger_id' => $chargerId
            ]);

            $result = $this->steveApiService->startCharge($chargerId, $sessionId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Session de charge démarrée avec succès',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec du démarrage de la session de charge',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('SteveApiController: Error starting charge', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du démarrage de la session de charge',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Arrêter une session de charge
     */
    public function stopCharge(Request $request, $chargingPointId): JsonResponse
    {
        try {
            $request->validate([
                'session_id' => 'required|string',
                'charger_id' => 'required|string'
            ]);

            $sessionId = $request->input('session_id');
            $chargerId = $request->input('charger_id');

            Log::info('SteveApiController: Stopping charge', [
                'charging_point_id' => $chargingPointId,
                'session_id' => $sessionId,
                'charger_id' => $chargerId
            ]);

            $result = $this->steveApiService->stopCharge($chargerId, $sessionId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Session de charge arrêtée avec succès',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de l\'arrêt de la session de charge',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('SteveApiController: Error stopping charge', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'arrêt de la session de charge',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut d'une borne
     */
    public function getChargerStatus($chargingPointId): JsonResponse
    {
        try {
            $chargerId = "BORNE_{$chargingPointId}";

            Log::info('SteveApiController: Getting charger status', [
                'charging_point_id' => $chargingPointId,
                'charger_id' => $chargerId
            ]);

            $result = $this->steveApiService->getChargerStatus($chargerId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Statut de la borne récupéré avec succès',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la récupération du statut de la borne',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('SteveApiController: Error getting charger status', [
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
     * Obtenir le statut d'une session de charge
     */
    public function getSessionStatus(Request $request, $chargingPointId): JsonResponse
    {
        try {
            $request->validate([
                'session_id' => 'required|string'
            ]);

            $sessionId = $request->input('session_id');
            $chargerId = "BORNE_{$chargingPointId}";

            Log::info('SteveApiController: Getting session status', [
                'charging_point_id' => $chargingPointId,
                'session_id' => $sessionId,
                'charger_id' => $chargerId
            ]);

            $result = $this->steveApiService->getSessionStatus($chargerId, $sessionId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Statut de la session récupéré avec succès',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la récupération du statut de la session',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('SteveApiController: Error getting session status', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut de la session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tester la connexion au serveur Steve
     */
    public function testConnection(): JsonResponse
    {
        try {
            Log::info('SteveApiController: Testing connection to Steve API');

            $result = $this->steveApiService->testConnection();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connexion au serveur Steve réussie',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la connexion au serveur Steve',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('SteveApiController: Error testing connection', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les informations d'une borne
     */
    public function getChargerInfo($chargingPointId): JsonResponse
    {
        try {
            $chargerId = "BORNE_{$chargingPointId}";

            Log::info('SteveApiController: Getting charger info', [
                'charging_point_id' => $chargingPointId,
                'charger_id' => $chargerId
            ]);

            $result = $this->steveApiService->getChargerInfo($chargerId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Informations de la borne récupérées avec succès',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la récupération des informations de la borne',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('SteveApiController: Error getting charger info', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations de la borne',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister toutes les bornes
     */
    public function listChargers(): JsonResponse
    {
        try {
            Log::info('SteveApiController: Listing chargers');

            $result = $this->steveApiService->listChargers();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Liste des bornes récupérée avec succès',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la récupération de la liste des bornes',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('SteveApiController: Error listing chargers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la liste des bornes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de charge
     */
    public function getChargingStats($chargingPointId = null): JsonResponse
    {
        try {
            $chargerId = $chargingPointId ? "BORNE_{$chargingPointId}" : null;

            Log::info('SteveApiController: Getting charging stats', [
                'charging_point_id' => $chargingPointId,
                'charger_id' => $chargerId
            ]);

            $result = $this->steveApiService->getChargingStats($chargerId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Statistiques de charge récupérées avec succès',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la récupération des statistiques de charge',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('SteveApiController: Error getting charging stats', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques de charge',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer l'URL WebSocket pour une borne
     */
    public function generateWebSocketUrl($chargingPointId): JsonResponse
    {
        try {
            $chargerId = "BORNE_{$chargingPointId}";
            $websocketUrl = $this->steveApiService->generateWebSocketUrl($chargerId);

            Log::info('SteveApiController: Generated WebSocket URL', [
                'charging_point_id' => $chargingPointId,
                'charger_id' => $chargerId,
                'websocket_url' => $websocketUrl
            ]);

            return response()->json([
                'success' => true,
                'message' => 'URL WebSocket générée avec succès',
                'data' => [
                    'charger_id' => $chargerId,
                    'websocket_url' => $websocketUrl
                ]
            ]);

        } catch (Exception $e) {
            Log::error('SteveApiController: Error generating WebSocket URL', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de l\'URL WebSocket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier la disponibilité du service Steve
     */
    public function checkAvailability(): JsonResponse
    {
        try {
            $isAvailable = $this->steveApiService->isAvailable();

            return response()->json([
                'success' => true,
                'message' => 'Disponibilité du service Steve vérifiée',
                'data' => [
                    'available' => $isAvailable,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('SteveApiController: Error checking availability', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification de la disponibilité',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la configuration du service Steve
     */
    public function getConfig(): JsonResponse
    {
        try {
            $config = $this->steveApiService->getConfig();

            return response()->json([
                'success' => true,
                'message' => 'Configuration du service Steve récupérée',
                'data' => $config
            ]);

        } catch (Exception $e) {
            Log::error('SteveApiController: Error getting config', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
