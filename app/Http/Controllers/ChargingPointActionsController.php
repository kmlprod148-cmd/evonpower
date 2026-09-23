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
            'id_tag' => 'required|string|max:20'
        ]);

        try {
            $result = $this->steveService->startCharging($chargingPoint, $request->all());
            
            Log::info('ChargingPointActionsController: Charging started', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'result' => $result
            ]);

            return response()->json($result);

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
            'session_id' => 'required|string'
        ]);

        try {
            $result = $this->steveService->stopCharging($chargingPoint, $request->all());
            
            Log::info('ChargingPointActionsController: Charging stopped', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'result' => $result
            ]);

            return response()->json($result);

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
     * Créer une réservation pour un point de charge
     */
    public function createReservation(Request $request, ChargingPoint $chargingPoint)
    {
        try {
            // Validation des données de la requête
            $validated = $request->validate([
                'pricing_plan_id' => 'required|exists:pricing_plans,id',
                'reservation_type' => 'required|string|in:kwh,minute',
                'reservation_value' => 'required|numeric|min:0.01',
                'payment_type' => 'required|string|in:cmi,stripe,offline,credit,prepaid_credit,postpaid_credit',
                'start_time' => 'nullable|string',
                'guest_email' => 'nullable|email',
                'guest_phone' => 'nullable|string',
            ], [
                'pricing_plan_id.required' => 'L\'identifiant du plan tarifaire est requis.',
                'pricing_plan_id.exists' => 'Le plan tarifaire sélectionné n\'existe pas.',
                'reservation_type.required' => 'Le type de réservation est requis.',
                'reservation_type.in' => 'Le type de réservation doit être "kwh" ou "minute".',
                'reservation_value.required' => 'La valeur de réservation est requise.',
                'reservation_value.numeric' => 'La valeur de réservation doit être un nombre.',
                'reservation_value.min' => 'La valeur de réservation doit être supérieure à 0.',
                'payment_type.required' => 'Le type de paiement est requis.',
                'payment_type.in' => 'Le type de paiement doit être "cmi", "stripe", "offline", "credit", "prepaid_credit" ou "postpaid_credit".',
                'guest_email.email' => 'L\'adresse email invité doit être valide.',
            ]);

            // Ajouter l'ID du point de charge aux données validées
            $validated['charging_point_id'] = $chargingPoint->id;
            // Alias payment_method pour ReservationService (attend payment_method)
            $validated['payment_method'] = $validated['payment_type'];

            // Utiliser le service de réservation
            $reservationService = app(\App\Services\ReservationService::class);
            $result = $reservationService->createReservation($validated, $chargingPoint);

            if ($result['status'] === 'success') {
                Log::info('ChargingPointActionsController: Reservation created successfully', [
                    'user_id' => Auth::id(),
                    'charging_point_id' => $chargingPoint->id,
                    'reservation_id' => $result['reservation']->id ?? null
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'reservation' => $result['reservation'] ?? null,
                    'order' => $result['order'] ?? null
                ], 201);
            } else {
                Log::error('ChargingPointActionsController: Reservation creation failed', [
                    'user_id' => Auth::id(),
                    'charging_point_id' => $chargingPoint->id,
                    'error' => $result['message']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? 'unknown_error'
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('ChargingPointActionsController: Validation error in createReservation', [
                'charging_point_id' => $chargingPoint->id,
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des données',
                'errors' => $e->errors(),
                'error' => 'validation_error'
            ], 422);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Unexpected error in createReservation', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue lors de la création de la réservation.',
                'error' => 'unexpected_error',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler une réservation
     */
    public function cancelReservation(Request $request, $reservationId)
    {
        try {
            $reservation = \App\Models\Reservation::findOrFail($reservationId);
            
            // Vérifier que l'utilisateur peut annuler cette réservation.
            // La condition originale (Auth::id() && ...) permettait aux utilisateurs non authentifiés
            // de contourner la vérification (null && anything = false). Correction : exiger l'auth.
            if (!Auth::id() || $reservation->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à annuler cette réservation.'
                ], 403);
            }

            // Annuler la réservation — utiliser la valeur exacte de l'enum (canceled, pas cancelled)
            $reservation->update(['status' => \App\Enums\ReservationStatus::CANCELED]);

            Log::info('ChargingPointActionsController: Reservation cancelled', [
                'user_id' => Auth::id(),
                'reservation_id' => $reservationId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Réservation annulée avec succès.',
                'reservation' => $reservation
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to cancel reservation', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la réservation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les sessions actives d'un point de charge
     */
    public function getActiveSessions(ChargingPoint $chargingPoint)
    {
        try {
            $sessions = $this->steveService->getActiveSessions($chargingPoint);
            
            return response()->json([
                'success' => true,
                'sessions' => $sessions
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to get active sessions', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut d'un point de charge
     */
    public function getStatus(ChargingPoint $chargingPoint)
    {
        try {
            $status = $this->steveService->getStatus($chargingPoint);
            
            return response()->json([
                'success' => true,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('ChargingPointActionsController: Failed to get status', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tester la connexion SteVe globale
     */
    public function testConnection()
    {
        try {
            $result = $this->steveService->testConnection();
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result
            ]);

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
