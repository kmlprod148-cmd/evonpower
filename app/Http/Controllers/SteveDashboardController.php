<?php

namespace App\Http\Controllers;

use App\Services\SteveDashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur pour le tableau de bord Steve API
 * 
 * Fournit les endpoints API pour toutes les métriques du dashboard
 */
class SteveDashboardController extends Controller
{
    protected SteveDashboardService $dashboardService;

    public function __construct(SteveDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Afficher la page principale du dashboard
     */
    public function index(Request $request)
    {
        $summary = $this->dashboardService->getDashboardSummary();
        $activeSessions = $this->dashboardService->getActiveSessions();
        $energy = $this->dashboardService->getEnergyMetrics();
        $stations = $this->dashboardService->getStationsStatus();
        $availability = $this->dashboardService->getAvailabilityStatus();
        $chargerTypes = $this->dashboardService->getChargerTypesBreakdown();
        $health = $this->dashboardService->getNetworkHealth();
        $performance = $this->dashboardService->getPerformanceAnalytics();
        
        return view('steve.dashboard.index', compact(
            'summary',
            'activeSessions',
            'energy',
            'stations',
            'availability',
            'chargerTypes',
            'health',
            'performance'
        ));
    }

    /**
     * Obtenir le résumé complet du dashboard
     * GET /api/steve/dashboard/summary
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getDashboardSummary();
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SteveDashboard API: Error fetching summary', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les sessions de recharge
     * GET /api/steve/dashboard/sessions
     */
    public function sessions(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 50);
            $data = $this->dashboardService->getChargingSessions($limit);
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SteveDashboard API: Error fetching sessions', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les sessions actives
     * GET /api/steve/dashboard/active-sessions
     */
    public function activeSessions(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getActiveSessions();
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SteveDashboard API: Error fetching active sessions', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les métriques d'énergie
     * GET /api/steve/dashboard/energy
     */
    public function energy(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getEnergyMetrics();
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SteveDashboard API: Error fetching energy', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir le statut des stations
     * GET /api/steve/dashboard/stations
     */
    public function stations(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getStationsStatus();
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SteveDashboard API: Error fetching stations', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les métriques par station
     * GET /api/steve/dashboard/station-metrics
     */
    public function stationMetrics(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getPerStationMetrics();
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SteveDashboard API: Error fetching station metrics', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir la disponibilité des stations
     * GET /api/steve/dashboard/availability
     */
    public function availability(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getAvailabilityStatus();
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SteveDashboard API: Error fetching availability', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir la santé du réseau
     * GET /api/steve/dashboard/health
     */
    public function health(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getNetworkHealth();
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SteveDashboard API: Error fetching health', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les analytiques de performance
     * GET /api/steve/dashboard/performance
     */
    public function performance(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getPerformanceAnalytics();
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SteveDashboard API: Error fetching performance', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les types de chargeurs
     * GET /api/steve/dashboard/charger-types
     */
    public function chargerTypes(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getChargerTypesBreakdown();
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SteveDashboard API: Error fetching charger types', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rafraîchir les données du cache
     * POST /api/steve/dashboard/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->refreshCache();
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SteveDashboard API: Error refreshing cache', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir toutes les données du dashboard en une requête
     * GET /api/steve/dashboard/all
     */
    public function all(Request $request): JsonResponse
    {
        try {
            $data = [
                'summary' => $this->dashboardService->getDashboardSummary(),
                'active_sessions' => $this->dashboardService->getActiveSessions(),
                'energy' => $this->dashboardService->getEnergyMetrics(),
                'stations' => $this->dashboardService->getStationsStatus(),
                'availability' => $this->dashboardService->getAvailabilityStatus(),
                'charger_types' => $this->dashboardService->getChargerTypesBreakdown(),
                'health' => $this->dashboardService->getNetworkHealth(),
                'performance' => $this->dashboardService->getPerformanceAnalytics(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SteveDashboard API: Error fetching all data', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
