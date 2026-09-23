<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\PublicPaymentService;
use App\Services\ChargingSessionService;
use App\Models\Reservation;
use App\Models\ChargingSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Contrôleur API pour le flux de paiement public en 3 étapes
 * 
 * Endpoints:
 * - POST /api/public/payment/scan - Scanner QR Code (Étape 1)
 * - POST /api/public/payment/initiate - Initier paiement (Étape 2)
 * - POST /api/public/payment/confirm - Confirmer et démarrer (Étape 3)
 * - GET /api/public/payment/validate - Validation temps réel
 * - GET /api/public/payment/{reservationId}/status - Statut paiement
 */
class PublicPaymentController extends Controller
{
    public function __construct(
        protected PublicPaymentService $paymentService,
        protected ChargingSessionService $sessionService
    ) {}

    /**
     * ÉTAPE 1: Scanner le QR Code
     * 
     * POST /api/public/payment/scan
     * 
     * @bodyParam qr_code string required Les données du QR Code
     * @bodyParam station_id integer optional ID de la station sélectionnée
     * 
     * @response 200 {
     *   "step": 1,
     *   "charging_point": {...},
     *   "pricing_plans": [...],
     *   "payment_token": "PAY_123_xxx",
     *   "expires_at": "2026-03-18T12:00:00+00:00"
     * }
     */
    public function scanQRCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'qr_code' => 'required|string|min:1',
            'station_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->paymentService->scanQRCode($request->input('qr_code'));

            // Ajouter la station_id si fournie
            if ($request->has('station_id')) {
                $result['selected_station_id'] = $request->input('station_id');
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\App\Exceptions\ChargingPoint\ChargingPointNotFoundException $e) {
            Log::warning('PublicPaymentController: Borne non trouvée', [
                'qr_code' => $request->input('qr_code'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CHARGING_POINT_NOT_FOUND',
                    'message' => $e->getMessage(),
                ],
            ], 404);

        } catch (\Exception $e) {
            Log::error('PublicPaymentController: Erreur scan QR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SCAN_FAILED',
                    'message' => 'Erreur lors du scan du QR Code',
                ],
            ], 500);
        }
    }

    /**
     * ÉTAPE 2: Initier le paiement
     * 
     * POST /api/public/payment/initiate
     * 
     * @bodyParam payment_token string required Token de session paiement
     * @bodyParam pricing_plan_id integer required ID du plan tarifaire
     * @bodyParam payment_method string required Méthode de paiement (wallet, qr_code, postpaid)
     * 
     * @response 200 {
     *   "step": 2,
     *   "reservation_id": 123,
     *   "payment_token": "PAY_123_xxx",
     *   "payment_method": "wallet",
     *   "pricing_plan": {...},
     *   "wallet_balance": 50.00,
     *   "estimated_cost": 25.00,
     *   "can_start": true,
     *   "expires_at": "2026-03-18T12:00:00+00:00"
     * }
     */
    public function initiatePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_token' => 'required|string|min:10',
            'pricing_plan_id' => 'required|integer|exists:pricing_plans,id',
            'payment_method' => 'required|string|in:wallet,qr_code,postpaid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();

            $result = $this->paymentService->initiatePayment(
                $request->input('payment_token'),
                $request->input('pricing_plan_id'),
                $request->input('payment_method'),
                $user
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\App\Exceptions\InsufficientBalanceException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INSUFFICIENT_BALANCE',
                    'message' => $e->getMessage(),
                ],
            ], 402);

        } catch (\Exception $e) {
            Log::error('PublicPaymentController: Erreur initiation paiement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INITIATE_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * ÉTAPE 3: Confirmer le paiement et démarrer la session
     * 
     * POST /api/public/payment/confirm
     * 
     * @bodyParam reservation_id integer required ID de la réservation
     * @bodyParam payment_token string required Token de session paiement
     * @bodyParam payment_method string required Méthode de paiement utilisée
     * 
     * @response 200 {
     *   "step": 3,
     *   "success": true,
     *   "session": {...},
     *   "payment": {...},
     *   "monitoring_url": "..."
     * }
     */
    public function confirmPaymentAndStartSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|integer|exists:reservations,id',
            'payment_token' => 'required|string|min:10',
            'payment_method' => 'required|string|in:wallet,qr_code,postpaid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();

            $result = $this->paymentService->confirmPaymentAndStartSession(
                $request->input('reservation_id'),
                $request->input('payment_token'),
                $request->input('payment_method'),
                $user
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('PublicPaymentController: Erreur confirmation paiement', [
                'reservation_id' => $request->input('reservation_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CONFIRM_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Validation en temps réel du paiement
     * 
     * GET /api/public/payment/validate
     * 
     * @queryParam reservation_id integer required ID de la réservation
     * 
     * @response 200 {
     *   "valid": true,
     *   "payment_status": "confirmed",
     *   "is_expired": false,
     *   "reservation_status": "confirmed",
     *   "checked_at": "2026-03-18T12:00:00+00:00"
     * }
     */
    public function validatePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|integer|exists:reservations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->paymentService->validatePaymentInRealTime(
                $request->input('reservation_id')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATE_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Obtenir le statut du paiement
     * 
     * GET /api/public/payment/{reservationId}/status
     * 
     * @response 200 {
     *   "reservation_id": 123,
     *   "payment_status": "confirmed",
     *   "payment_method": "wallet",
     *   "session_status": "active",
     *   "session_id": 456
     * }
     */
    public function getPaymentStatus(int $reservationId): JsonResponse
    {
        try {
            $reservation = Reservation::with(['chargingPoint', 'pricingPlan'])
                ->findOrFail($reservationId);

            $session = ChargingSession::where('reservation_id', $reservationId)
                ->latest()
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'reservation_id' => $reservation->id,
                    'payment_status' => $reservation->payment_status,
                    'payment_method' => $reservation->payment_method,
                    'reservation_status' => $reservation->status,
                    'estimated_cost' => $reservation->estimated_cost,
                    'actual_cost' => $reservation->actual_cost,
                    'session' => $session ? [
                        'id' => $session->id,
                        'status' => $session->status,
                        'start_timestamp' => $session->start_timestamp,
                        'stopped_at' => $session->stopped_at,
                        'energy_consumed_kwh' => $session->energy_consumed_kwh,
                        'duration_minutes' => $session->duration_minutes,
                        'total_cost' => $session->total_cost,
                    ] : null,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STATUS_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
