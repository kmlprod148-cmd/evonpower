<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Services\SteVeIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SteVeIntegrationController extends Controller
{
    protected SteVeIntegrationService $steveService;

    public function __construct(SteVeIntegrationService $steveService)
    {
        $this->steveService = $steveService;
        $this->middleware('auth');
        $this->middleware('can:manage_charging_points');
    }

    /**
     * Display the SteVe integration management page
     */
    public function index()
    {
        try {
            // Get all charging points
            $chargingPoints = ChargingPoint::all();
            
            // Get server status
            $serverStatus = $this->steveService->checkServerStatus();
            
            // Get connection statistics
            $statistics = $this->steveService->getConnectionStatistics();
            
            // Get configuration
            $configuration = $this->steveService->getConfiguration();

            return view('steve-integration.index', [
                'chargingPoints' => $chargingPoints,
                'serverStatus' => $serverStatus,
                'statistics' => $statistics,
                'configuration' => $configuration
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading SteVe integration page', [
                'error' => $e->getMessage()
            ]);
            
            return view('steve-integration.index', [
                'chargingPoints' => [],
                'serverStatus' => ['status' => 'error'],
                'statistics' => [],
                'configuration' => []
            ]);
        }
    }

    /**
     * Test SteVe connection
     */
    public function testConnection()
    {
        try {
            $result = $this->steveService->testConnection();
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Error testing SteVe connection', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error testing connection: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get server status
     */
    public function getServerStatus()
    {
        try {
            $status = $this->steveService->checkServerStatus();
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting server status', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error getting server status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get connection statistics
     */
    public function getStatistics()
    {
        try {
            $statistics = $this->steveService->getConnectionStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting statistics', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error getting statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connect a charging point to SteVe
     */
    public function connectChargingPoint(Request $request, ChargingPoint $chargingPoint)
    {
        try {
            $request->validate([
                'charge_box_id' => 'required|string|max:255',
                'steve_server_url' => 'nullable|string'
            ]);

            $result = $this->steveService->connectChargingPoint($chargingPoint);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error connecting charging point', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error connecting charging point: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disconnect a charging point from SteVe
     */
    public function disconnectChargingPoint(ChargingPoint $chargingPoint)
    {
        try {
            $result = $this->steveService->disconnectChargingPoint($chargingPoint);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error disconnecting charging point', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error disconnecting charging point: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get charging point SteVe status
     */
    public function getChargingPointStatus(ChargingPoint $chargingPoint)
    {
        try {
            $chargeBoxId = $this->steveService->getChargeBoxIdFromDevice($chargingPoint);
            $websocketUrl = $this->steveService->generateWebSocketUrl($chargingPoint);
            $serverStatus = $this->steveService->checkServerStatus();

            return response()->json([
                'success' => true,
                'data' => [
                    'charging_point_id' => $chargingPoint->id,
                    'charge_box_id' => $chargeBoxId,
                    'websocket_url' => $websocketUrl,
                    'status' => $chargingPoint->status,
                    'last_connection_attempt' => $chargingPoint->last_connection_attempt,
                    'server_status' => $serverStatus
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting charging point status', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error getting charging point status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get complete API status for a charging point (includes test connection, charger status, and info)
     */
    public function getCompleteApiStatus(ChargingPoint $chargingPoint)
    {
        try {
            // Vérifier les permissions explicitement pour retourner du JSON en cas d'erreur
            if (!auth()->user()->can('manage_charging_points')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Vous n\'avez pas la permission d\'accéder à cette ressource',
                    'timestamp' => now()->toISOString()
                ], 403);
            }

            // Get charge box ID (serial number or generated ID)
            $chargeBoxId = $chargingPoint->serial_number ?? $chargingPoint->charge_box_id ?? "BORNE_{$chargingPoint->id}";
            
            // Generate WebSocket URL
            $wsBaseUrl = config('steve.websocket_url', 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/');
            $websocketUrl = $wsBaseUrl . $chargeBoxId;

            // Get services - utiliser try-catch pour chaque appel indépendamment
            $steveApiService = app(\App\Services\SteVeApiService::class);

            $testConnection = ['success' => false, 'error' => 'Not attempted'];
            $chargerStatus = ['success' => false, 'error' => 'Not attempted'];
            $chargerInfo = ['success' => false, 'error' => 'Not attempted'];

            // Get test connection status
            try {
                $testConnection = $steveApiService->testConnection();
            } catch (\Exception $e) {
                Log::warning('Error getting test connection status', [
                    'error' => $e->getMessage()
                ]);
                $testConnection = ['success' => false, 'error' => $e->getMessage()];
            }

            // Get charger status
            try {
                $chargerStatus = $steveApiService->getChargerStatus($chargeBoxId);
            } catch (\Exception $e) {
                Log::warning('Error getting charger status', [
                    'charge_box_id' => $chargeBoxId,
                    'error' => $e->getMessage()
                ]);
                $chargerStatus = ['success' => false, 'error' => $e->getMessage()];
            }

            // Get charger info
            try {
                $chargerInfo = $steveApiService->getChargerInfo($chargeBoxId);
            } catch (\Exception $e) {
                Log::warning('Error getting charger info', [
                    'charge_box_id' => $chargeBoxId,
                    'error' => $e->getMessage()
                ]);
                $chargerInfo = ['success' => false, 'error' => $e->getMessage()];
            }

            return response()->json([
                'success' => true,
                'websocket_url' => $websocketUrl,
                'charge_box_id' => $chargeBoxId,
                'charging_point_id' => $chargingPoint->id,
                'timestamp' => now()->toISOString(),
                'test_connection' => $testConnection,
                'charger_status' => $chargerStatus,
                'charger_info' => $chargerInfo
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('Authorization error getting complete API status', [
                'charging_point_id' => $chargingPoint->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            $chargeBoxId = $chargingPoint->serial_number ?? $chargingPoint->charge_box_id ?? "BORNE_{$chargingPoint->id}";
            $wsBaseUrl = config('steve.websocket_url', 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/');
            $websocketUrl = $wsBaseUrl . $chargeBoxId;

            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Vous n\'avez pas la permission d\'accéder à cette ressource',
                'timestamp' => now()->toISOString(),
                'websocket_url' => $websocketUrl,
                'charge_box_id' => $chargeBoxId,
                'charging_point_id' => $chargingPoint->id,
                'test_connection' => ['success' => false, 'error' => 'Unauthorized'],
                'charger_status' => ['success' => false, 'error' => 'Unauthorized'],
                'charger_info' => ['success' => false, 'error' => 'Unauthorized']
            ], 403);

        } catch (\Exception $e) {
            Log::error('Error getting complete API status', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $chargeBoxId = $chargingPoint->serial_number ?? $chargingPoint->charge_box_id ?? "BORNE_{$chargingPoint->id}";
            $wsBaseUrl = config('steve.websocket_url', 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/');
            $websocketUrl = $wsBaseUrl . $chargeBoxId;

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la récupération du statut complet de l\'API Steve',
                'timestamp' => now()->toISOString(),
                'websocket_url' => $websocketUrl,
                'charge_box_id' => $chargeBoxId,
                'charging_point_id' => $chargingPoint->id,
                'test_connection' => ['success' => false, 'error' => $e->getMessage()],
                'charger_status' => ['success' => false, 'error' => $e->getMessage()],
                'charger_info' => ['success' => false, 'error' => $e->getMessage()]
            ], 500);
        }
    }
}

