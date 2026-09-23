<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChargingPoint;
use App\Services\SteVeApiService;
use App\Services\ChargingPointConnectionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ChargingPointActionsController extends Controller
{
    protected $steveService;
    protected $connectionService;

    public function __construct(SteVeApiService $steveService, ChargingPointConnectionService $connectionService)
    {
        $this->middleware('auth');
        $this->steveService = $steveService;
        $this->connectionService = $connectionService;
    }

    /**
     * Démarrer une session de charge
     */
    public function startCharging(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'connector_id' => 'required|integer|min:1',
            'id_tag' => 'required|string|max:20',
            'reservation_id' => 'nullable|string|max:50'
        ]);

        try {
            $result = $this->steveService->startCharging($chargingPoint, $request->all());
            
            Log::info('ChargingPointActionsController: Charging started', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'result' => $result
            ]);

            // Vérifier si c'est une simulation
            $isSimulated = isset($result['simulated']) && $result['simulated'];
            $message = $isSimulated 
                ? 'Session de charge simulée (SteVe non disponible)'
                : 'Session de charge démarrée avec succès';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result,
                'simulated' => $isSimulated
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to start charging', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du démarrage de la charge: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Arrêter une session de charge
     */
    public function stopCharging(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'session_id' => 'required|string|max:50'
        ]);

        try {
            $result = $this->steveService->stopCharging($chargingPoint, $request->session_id);
            
            Log::info('ChargingPointActionsController: Charging stopped', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'session_id' => $request->session_id
            ]);

            // Vérifier si c'est une simulation
            $isSimulated = isset($result['simulated']) && $result['simulated'];
            $message = $isSimulated 
                ? 'Arrêt de charge simulé (SteVe non disponible)'
                : 'Session de charge arrêtée avec succès';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result,
                'simulated' => $isSimulated
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to stop charging', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'arrêt de la charge: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une réservation
     */
    public function createReservation(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'connector_id' => 'required|integer|min:1',
            'id_tag' => 'required|string|max:20',
            'expiry_date' => 'required|date|after:now',
            'reservation_id' => 'nullable|string|max:50'
        ]);

        try {
            $result = $this->steveService->createReservation($chargingPoint, $request->all());
            
            Log::info('ChargingPointActionsController: Reservation created', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'reservation_id' => $result['reservation_id'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Réservation créée avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to create reservation', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la réservation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler une réservation
     */
    public function cancelReservation(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|string|max:50'
        ]);

        try {
            $result = $this->steveService->cancelReservation($request->reservation_id);
            
            Log::info('ChargingPointActionsController: Reservation canceled', [
                'user_id' => Auth::id(),
                'reservation_id' => $request->reservation_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Réservation annulée avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to cancel reservation', [
                'error' => $e->getMessage(),
                'reservation_id' => $request->reservation_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la réservation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut d'un point de charge
     */
    public function getStatus(ChargingPoint $chargingPoint)
    {
        try {
            $status = $this->steveService->getChargingPointStatus($chargingPoint);
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to get status', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les sessions actives
     */
    public function getActiveSessions(ChargingPoint $chargingPoint)
    {
        try {
            $sessions = $this->steveService->getActiveSessions($chargingPoint);
            
            return response()->json([
                'success' => true,
                'data' => $sessions
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to get active sessions', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tester la connexion SteVe
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
            Log::error('ChargingPointActionsController: Failed to test connection', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de connexion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connecter une borne par ID
     */
    public function connectById(Request $request, $id)
    {
        $request->validate([
            'iccid' => 'nullable|string|max:50',
            'imsi' => 'nullable|string|max:50',
            'meter_type' => 'nullable|string|max:50',
            'meter_serial' => 'nullable|string|max:50',
            'borne_id' => 'nullable|string|max:50'
        ]);

        try {
            $options = $request->all();
            $borneId = $request->input('borne_id', 'BORNE777');
            
            Log::info('ChargingPointActionsController: Connecting charging point by ID with borne ID', [
                'user_id' => Auth::id(),
                'charging_point_id' => $id,
                'borne_id' => $borneId,
                'options' => $options
            ]);

            $result = $this->connectionService->connectById($id, $options);
            
            // Ajouter l'ID de la borne dans la réponse
            if (isset($result['data'])) {
                $result['data']['borne_id'] = $borneId;
                $result['data']['websocket_url'] = 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/' . $borneId;
            }

            Log::info('ChargingPointActionsController: Charging point connected by ID', [
                'user_id' => Auth::id(),
                'charging_point_id' => $id,
                'borne_id' => $borneId,
                'result' => $result
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'] . ' (Borne: ' . $borneId . ')',
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to connect by ID', [
                'error' => $e->getMessage(),
                'charging_point_id' => $id,
                'borne_id' => $request->input('borne_id', 'BORNE777')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnecter un point de charge
     */
    public function disconnect(Request $request, ChargingPoint $chargingPoint)
    {
        try {
            $result = $this->connectionService->disconnect($chargingPoint);
            
            Log::info('ChargingPointActionsController: Charging point disconnected', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to disconnect', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut de connexion
     */
    public function getConnectionStatus(ChargingPoint $chargingPoint)
    {
        try {
            $status = $this->connectionService->getConnectionStatus($chargingPoint);
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to get connection status', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tester la connectivité
     */
    public function testConnectivity(ChargingPoint $chargingPoint)
    {
        try {
            $result = $this->connectionService->testConnectivity($chargingPoint);
            
            Log::info('ChargingPointActionsController: Connectivity test performed', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'result' => $result
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to test connectivity', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de connectivité: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les tags OCPP
     */
    public function getOcppTags(Request $request)
    {
        try {
            $filters = $request->only(['blocked', 'expired', 'idTag', 'inTransaction', 'ocppTagPk', 'parentIdTag']);
            $result = $this->steveServiceV2->getOcppTags($filters);
            
            Log::info('ChargingPointActionsController: OCPP tags retrieved', [
                'user_id' => Auth::id(),
                'filters' => $filters,
                'success' => $result['success']
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to get OCPP tags', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des tags OCPP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un tag OCPP
     */
    public function createOcppTag(Request $request)
    {
        $request->validate([
            'idTag' => 'required|string|max:20',
            'note' => 'nullable|string|max:255',
            'maxActiveTransactionCount' => 'nullable|integer|min:1',
            'parentIdTag' => 'nullable|string|max:20'
        ]);

        try {
            $tagData = $request->only(['idTag', 'note', 'maxActiveTransactionCount', 'parentIdTag']);
            $result = $this->steveServiceV2->createOcppTag($tagData);
            
            Log::info('ChargingPointActionsController: OCPP tag created', [
                'user_id' => Auth::id(),
                'tagData' => $tagData,
                'success' => $result['success']
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to create OCPP tag', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du tag OCPP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les transactions
     */
    public function getTransactions(Request $request)
    {
        try {
            $filters = $request->only(['chargeBoxId', 'from', 'ocppIdTag', 'periodType', 'to', 'transactionPk', 'type']);
            $result = $this->steveServiceV2->getTransactions($filters);
            
            Log::info('ChargingPointActionsController: Transactions retrieved', [
                'user_id' => Auth::id(),
                'filters' => $filters,
                'success' => $result['success']
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to get transactions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques d'un point de charge
     */
    public function getChargingPointStats(ChargingPoint $chargingPoint)
    {
        try {
            $chargeBoxId = $chargingPoint->charge_box_id ?? 'CP_' . $chargingPoint->id;
            $result = $this->steveServiceV2->getChargingPointStats($chargeBoxId);
            
            Log::info('ChargingPointActionsController: Charging point stats retrieved', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'chargeBoxId' => $chargeBoxId,
                'success' => $result['success']
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to get charging point stats', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tester la connexion SteVe
     */
    public function testSteVeConnection()
    {
        try {
            $result = $this->steveServiceV2->testConnection();
            
            Log::info('ChargingPointActionsController: SteVe connection test performed', [
                'user_id' => Auth::id(),
                'result' => $result
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to test SteVe connection', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de connexion SteVe: ' . $e->getMessage()
            ], 500);
        }
    }
}
