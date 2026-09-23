<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApiMonitoringService;
use App\Services\HealthMonitoringService;
use App\Models\ChargingPoint;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class MonitoringController extends Controller
{
    protected $monitoringService;
    protected $healthMonitoringService;

    public function __construct(ApiMonitoringService $monitoringService, HealthMonitoringService $healthMonitoringService)
    {
        $this->monitoringService = $monitoringService;
        $this->healthMonitoringService = $healthMonitoringService;
    }

    /**
     * Afficher le tableau de bord de monitoring
     */
    public function index(Request $request)
    {
        try {
            $apiName = $request->get('api', null);
            $minutes = $request->get('minutes', 60);
            
            $dashboardData = $this->monitoringService->getDashboardData($apiName, $minutes);
            $healthStatus = $this->monitoringService->getApiHealthStatus();
            $chargerStatus = $this->healthMonitoringService->getChargerStatus();
            $overallHealth = $this->healthMonitoringService->getOverallHealthStatus();
            
            return view('admin.monitoring.index', compact('dashboardData', 'healthStatus', 'chargerStatus', 'overallHealth', 'apiName', 'minutes'));
        } catch (Exception $e) {
            Log::error('MonitoringController: Error in index method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Retourner des données vides si la table n'existe pas
            $dashboardData = [
                'stats' => [
                    'total_requests' => 0,
                    'successful_requests' => 0,
                    'failed_requests' => 0,
                    'success_rate' => 0,
                    'average_response_time' => 0
                ],
                'top_endpoints' => collect([]),
                'status_codes' => collect([]),
                'performance_data' => [],
                'alerts' => []
            ];
            $healthStatus = [];
            $chargerStatus = [];
            $overallHealth = ['status' => 'unknown'];
            $apiName = $request->get('api', null);
            $minutes = $request->get('minutes', 60);
            
            return view('admin.monitoring.index', compact('dashboardData', 'healthStatus', 'chargerStatus', 'overallHealth', 'apiName', 'minutes'))
                ->with('warning', 'La table de monitoring API n\'est pas disponible. Veuillez exécuter la migration pour activer cette fonctionnalité.');
        }
    }

    /**
     * Obtenir les données du tableau de bord via AJAX
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        try {
            $apiName = $request->get('api', null);
            $minutes = $request->get('minutes', 60);
            
            $dashboardData = $this->monitoringService->getDashboardData($apiName, $minutes);
            $healthStatus = $this->monitoringService->getApiHealthStatus();
            $recentRequests = $this->monitoringService->getRecentRequests($apiName, 20);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'dashboard' => $dashboardData,
                    'health_status' => $healthStatus,
                    'recent_requests' => $recentRequests,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error getting dashboard data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données de monitoring',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les métriques en temps réel
     */
    public function getRealTimeMetrics(Request $request): JsonResponse
    {
        try {
            $apiName = $request->get('api', null);
            $metrics = $this->monitoringService->getRealTimeMetrics($apiName);
            
            return response()->json([
                'success' => true,
                'data' => $metrics,
                'timestamp' => now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error getting real-time metrics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des métriques temps réel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les requêtes récentes
     */
    public function getRecentRequests(Request $request): JsonResponse
    {
        try {
            $apiName = $request->get('api', null);
            $limit = $request->get('limit', 50);
            
            $requests = $this->monitoringService->getRecentRequests($apiName, $limit);
            
            return response()->json([
                'success' => true,
                'data' => $requests,
                'count' => $requests->count()
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error getting recent requests', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des requêtes récentes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques détaillées
     */
    public function getDetailedStats(Request $request): JsonResponse
    {
        try {
            $apiName = $request->get('api', null);
            $minutes = $request->get('minutes', 60);
            
            $stats = $this->monitoringService->getMonitoringStats($apiName, $minutes);
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error getting detailed stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques détaillées',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les données de santé des API
     */
    public function getApiHealth(Request $request): JsonResponse
    {
        try {
            $healthStatus = $this->monitoringService->getApiHealthStatus();
            
            return response()->json([
                'success' => true,
                'data' => $healthStatus,
                'timestamp' => now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error getting API health', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut de santé des API',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter les données de monitoring
     */
    public function export(Request $request)
    {
        try {
            $apiName = $request->get('api', null);
            $startDate = $request->get('start_date', null);
            $endDate = $request->get('end_date', null);
            $format = $request->get('format', 'json');
            
            $data = $this->monitoringService->exportData($apiName, $startDate, $endDate, $format);
            
            if ($format === 'csv') {
                $filename = 'api_monitoring_' . ($apiName ?? 'all') . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
                
                return response($data)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => count($data)
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error exporting data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exportation des données',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Nettoyer les anciennes données
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            $deleted = $this->monitoringService->cleanupOldData();
            
            return response()->json([
                'success' => true,
                'message' => "Nettoyage terminé: {$deleted} enregistrements supprimés",
                'deleted_count' => $deleted
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error cleaning up data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du nettoyage des données',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les alertes de performance
     */
    public function getAlerts(Request $request): JsonResponse
    {
        try {
            $apiName = $request->get('api', null);
            $minutes = $request->get('minutes', 60);
            
            $dashboardData = $this->monitoringService->getDashboardData($apiName, $minutes);
            $alerts = $dashboardData['alerts'] ?? [];
            
            return response()->json([
                'success' => true,
                'data' => $alerts,
                'count' => count($alerts)
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error getting alerts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des alertes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les données pour les graphiques
     */
    public function getChartData(Request $request): JsonResponse
    {
        try {
            $apiName = $request->get('api', null);
            $minutes = $request->get('minutes', 60);
            $chartType = $request->get('chart_type', 'performance');
            
            $dashboardData = $this->monitoringService->getDashboardData($apiName, $minutes);
            
            $chartData = match($chartType) {
                'performance' => $dashboardData['performance_data'] ?? [],
                'status_codes' => $dashboardData['status_codes'] ?? [],
                'top_endpoints' => $dashboardData['top_endpoints'] ?? [],
                default => []
            };
            
            return response()->json([
                'success' => true,
                'data' => $chartData,
                'chart_type' => $chartType
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error getting chart data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données de graphique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut des chargeurs
     */
    public function getChargerStatus(): JsonResponse
    {
        try {
            $chargerStatus = $this->healthMonitoringService->getChargerStatus();
            
            return response()->json([
                'success' => true,
                'data' => $chargerStatus,
                'timestamp' => now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error getting charger status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut des chargeurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut de santé global
     */
    public function getOverallHealth(): JsonResponse
    {
        try {
            $overallHealth = $this->healthMonitoringService->getOverallHealthStatus();
            
            return response()->json([
                'success' => true,
                'data' => $overallHealth,
                'timestamp' => now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error getting overall health', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut de santé global',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier manuellement la santé d'un chargeur
     */
    public function checkChargerHealth(Request $request, $chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            
            $result = $this->healthMonitoringService->checkChargerHealth($chargingPoint);
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result,
                'message' => $result['message']
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error checking charger health', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification de la santé du chargeur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier manuellement la santé d'une API
     */
    public function checkApiHealth(Request $request, $apiName): JsonResponse
    {
        try {
            $result = $this->healthMonitoringService->checkApiHealth($apiName);
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result,
                'message' => $result['message']
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error checking API health', [
                'api_name' => $apiName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification de la santé de l\'API',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'historique d'une entité
     */
    public function getEntityHistory(Request $request, $entityType, $entityId): JsonResponse
    {
        try {
            $hours = $request->get('hours', 24);
            
            $history = $this->healthMonitoringService->getEntityHistory($entityType, $entityId, $hours);
            
            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error getting entity history', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique de l\'entité',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Nettoyer le cache de monitoring
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->healthMonitoringService->clearCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Cache de monitoring nettoyé avec succès'
            ]);

        } catch (Exception $e) {
            Log::error('MonitoringController: Error clearing cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du nettoyage du cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
