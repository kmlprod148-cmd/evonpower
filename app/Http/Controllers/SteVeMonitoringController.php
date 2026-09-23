<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Services\SteVeMonitoringService;
use App\Services\OCPPCommandService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Contrôleur pour le monitoring et la gestion des connexions SteVe
 * 
 * Ce contrôleur gère les tests de connectivité, les commandes OCPP en temps réel
 * et le monitoring des bornes de charge
 */
class SteVeMonitoringController extends Controller
{
    protected SteVeMonitoringService $monitoringService;
    protected OCPPCommandService $ocppService;

    public function __construct(
        SteVeMonitoringService $monitoringService,
        OCPPCommandService $ocppService
    ) {
        $this->monitoringService = $monitoringService;
        $this->ocppService = $ocppService;
    }

    /**
     * Afficher le tableau de bord de monitoring
     */
    public function dashboard()
    {
        return view('steve.monitoring.dashboard');
    }

    /**
     * Interface de monitoring en temps réel (écran noir avec texte vert)
     */
    public function realTimeMonitor()
    {
        return view('steve.monitoring.realtime', [
            'title' => 'Monitoring SteVe - Temps Réel'
        ]);
    }

    /**
     * Test complet de connectivité SteVe
     */
    public function testConnectivity(): JsonResponse
    {
        try {
            $results = $this->monitoringService->testFullConnectivity();
            
            Log::info('Test de connectivité SteVe effectué', [
                'overall_status' => $results['overall_status'],
                'response_time' => $results['response_time_ms']
            ]);

            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du test de connectivité SteVe', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de connectivité: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut en temps réel
     */
    public function getRealTimeStatus(): JsonResponse
    {
        try {
            $status = $this->monitoringService->getRealTimeStatus();
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du statut temps réel', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la liste des points de charge avec leur statut
     */
    public function getChargingPointsStatus(): JsonResponse
    {
        try {
            $chargingPoints = ChargingPoint::with(['businessProfile', 'integrator'])
                ->select([
                    'id', 'name', 'charge_box_id', 'status', 
                    'last_connected_at', 'last_disconnected_at',
                    'business_profile_id', 'integrator_id'
                ])
                ->get()
                ->map(function ($point) {
                    return [
                        'id' => $point->id,
                        'name' => $point->name,
                        'charge_box_id' => $point->charge_box_id,
                        'status' => $point->status,
                        'last_connected' => $point->last_connected_at?->format('Y-m-d H:i:s'),
                        'last_disconnected' => $point->last_disconnected_at?->format('Y-m-d H:i:s'),
                        'business_profile' => $point->businessProfile?->name ?? 'N/A',
                        'integrator' => $point->integrator?->name ?? 'N/A',
                        'is_online' => $point->status === 'online'
                    ];
                });

            $summary = [
                'total' => $chargingPoints->count(),
                'online' => $chargingPoints->where('is_online', true)->count(),
                'offline' => $chargingPoints->where('is_online', false)->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'charging_points' => $chargingPoints
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du statut des points de charge', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer une commande OCPP en temps réel
     */
    public function sendOcppCommand(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $request->validate([
            'command' => 'required|string|in:StartTransaction,StopTransaction,Reset,UnlockConnector,ChangeConfiguration,GetConfiguration,GetDiagnostics,ClearCache,ChangeAvailability,RemoteStartTransaction,RemoteStopTransaction',
            'parameters' => 'array'
        ]);

        try {
            $result = $this->monitoringService->sendRealTimeCommand(
                $chargingPoint,
                $request->command,
                $request->parameters ?? []
            );

            if ($result['success']) {
                Log::info('Commande OCPP envoyée avec succès', [
                    'charging_point' => $chargingPoint->name,
                    'command' => $request->command,
                    'parameters' => $request->parameters
                ]);
            } else {
                Log::warning('Échec de l\'envoi de commande OCPP', [
                    'charging_point' => $chargingPoint->name,
                    'command' => $request->command,
                    'error' => $result['message']
                ]);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de commande OCPP', [
                'charging_point' => $chargingPoint->name,
                'command' => $request->command,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la commande: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Démarrer une session de charge
     */
    public function startCharging(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $request->validate([
            'connector_id' => 'required|integer|min:1',
            'id_tag' => 'required|string',
            'meter_start' => 'integer|min:0',
            'reservation_id' => 'nullable|string'
        ]);

        try {
            $params = [
                'connector_id' => $request->connector_id,
                'id_tag' => $request->id_tag,
                'meter_start' => $request->meter_start ?? 0,
                'reservation_id' => $request->reservation_id
            ];

            $result = $this->ocppService->startCharging($chargingPoint, $params);

            Log::info('Démarrage de session de charge', [
                'charging_point' => $chargingPoint->name,
                'params' => $params,
                'result' => $result
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Erreur lors du démarrage de la session de charge', [
                'charging_point' => $chargingPoint->name,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du démarrage de la session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Arrêter une session de charge
     */
    public function stopCharging(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer',
            'reason' => 'nullable|string'
        ]);

        try {
            $params = [
                'transaction_id' => $request->transaction_id,
                'reason' => $request->reason ?? 'Remote'
            ];

            $result = $this->ocppService->stopCharging($chargingPoint, $params);

            Log::info('Arrêt de session de charge', [
                'charging_point' => $chargingPoint->name,
                'params' => $params,
                'result' => $result
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'arrêt de la session de charge', [
                'charging_point' => $chargingPoint->name,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'arrêt de la session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Débloquer un connecteur
     */
    public function unlockConnector(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $request->validate([
            'connector_id' => 'required|integer|min:1'
        ]);

        try {
            $result = $this->ocppService->unlockConnector($chargingPoint, [
                'connector_id' => $request->connector_id
            ]);

            Log::info('Déblocage de connecteur', [
                'charging_point' => $chargingPoint->name,
                'connector_id' => $request->connector_id,
                'result' => $result
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Erreur lors du déblocage du connecteur', [
                'charging_point' => $chargingPoint->name,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du déblocage: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réinitialiser un point de charge
     */
    public function resetChargingPoint(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:Hard,Soft'
        ]);

        try {
            $result = $this->ocppService->resetChargingPoint($chargingPoint, [
                'type' => $request->type
            ]);

            Log::info('Réinitialisation du point de charge', [
                'charging_point' => $chargingPoint->name,
                'type' => $request->type,
                'result' => $result
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la réinitialisation', [
                'charging_point' => $chargingPoint->name,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les logs d'action d'un point de charge
     */
    public function getActionLog(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $cacheKey = "action_log_{$chargingPoint->id}";
            $logs = Cache::get($cacheKey, []);

            return response()->json([
                'success' => true,
                'data' => [
                    'charging_point' => $chargingPoint->name,
                    'logs' => $logs,
                    'total_logs' => count($logs)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des logs', [
                'charging_point' => $chargingPoint->name,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier le statut de connexion d'un point de charge
     */
    public function checkConnectionStatus(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $result = $this->ocppService->checkConnectionStatus($chargingPoint);

            return response()->json([
                'success' => true,
                'data' => [
                    'charging_point' => $chargingPoint->name,
                    'status' => $result
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du statut', [
                'charging_point' => $chargingPoint->name,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de monitoring
     */
    public function getMonitoringStats(): JsonResponse
    {
        try {
            $stats = [
                'total_charging_points' => ChargingPoint::count(),
                'online_points' => ChargingPoint::where('status', 'online')->count(),
                'offline_points' => ChargingPoint::where('status', 'offline')->count(),
                'last_24h_connections' => ChargingPoint::where('last_connected_at', '>=', now()->subDay())->count(),
                'system_uptime' => $this->getSystemUptime(),
                'memory_usage' => memory_get_usage(true),
                'cache_status' => Cache::getStore() ? 'active' : 'inactive'
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des statistiques', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le temps de fonctionnement du système
     */
    protected function getSystemUptime(): string
    {
        try {
            $uptime = shell_exec('uptime');
            return trim($uptime) ?: 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}
