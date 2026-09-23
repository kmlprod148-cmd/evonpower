<?php

namespace App\Http\Controllers;

use App\Models\EnhancedUser;
use App\Models\Reservation;
use App\Services\ReservationService;
use App\Events\ReservationCreated;
use App\Events\RechargeRequested;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ReservationTransactionController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    /**
     * Traiter une réservation et créer les transactions
     */
    public function processReservation(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0.01',
                'operator_id' => 'required|exists:enhanced_users,id',
                'charging_point_id' => 'nullable|exists:charging_points,id',
                'payment_status' => 'required|in:completed,pending,canceled,failed',
                'description' => 'nullable|string|max:255',
                'currency' => 'nullable|string|size:3',
                'session_duration' => 'nullable|integer|min:0',
                'energy_delivered' => 'nullable|numeric|min:0',
                'payment_method' => 'nullable|string|max:50',
                'payment_reference' => 'nullable|string|max:100',
                'external_reference' => 'nullable|string|max:100',
                'location' => 'nullable|array',
                'metadata' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reservationData = $request->all();
            $result = $this->reservationService->processReservation($reservationData);

            return response()->json([
                'success' => true,
                'message' => 'Réservation traitée avec succès',
                'data' => [
                    'reservation' => $result['reservation_data'],
                    'transactions' => $result['transactions']->map(function ($transaction) {
                        return [
                            'id' => $transaction->id,
                            'reference' => $transaction->transaction_reference,
                            'type' => $transaction->transaction_type,
                            'amount' => $transaction->amount,
                            'total_fees' => $transaction->getTotalFees(),
                            'total_amount' => $transaction->total_amount,
                            'status' => $transaction->status,
                            'created_at' => $transaction->created_at
                        ];
                    }),
                    'fee_calculations' => $result['fee_calculations']
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de la réservation', [
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement de la réservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Traiter une recharge
     */
    public function processRecharge(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0.01',
                'operator_id' => 'required|exists:enhanced_users,id',
                'payment_status' => 'required|in:completed,pending,canceled,failed',
                'description' => 'nullable|string|max:255',
                'currency' => 'nullable|string|size:3',
                'recharge_type' => 'nullable|string|in:manual,automatic',
                'location' => 'nullable|array',
                'charging_point_id' => 'nullable|exists:charging_points,id',
                'external_reference' => 'nullable|string|max:100',
                'metadata' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rechargeData = $request->all();
            $result = $this->reservationService->processRecharge($rechargeData);

            return response()->json([
                'success' => true,
                'message' => 'Recharge traitée avec succès',
                'data' => [
                    'recharge' => $result['recharge_data'],
                    'transaction' => [
                        'id' => $result['transaction']->id,
                        'reference' => $result['transaction']->transaction_reference,
                        'type' => $result['transaction']->transaction_type,
                        'amount' => $result['transaction']->amount,
                        'total_fees' => $result['transaction']->getTotalFees(),
                        'total_amount' => $result['transaction']->total_amount,
                        'status' => $result['transaction']->status,
                        'created_at' => $result['transaction']->created_at
                    ],
                    'fee_calculation' => $result['fee_calculation']
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de la recharge', [
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement de la recharge',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déclencher un événement de réservation créée
     */
    public function triggerReservationEvent(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reservation_id' => 'required|exists:reservations,id',
                'metadata' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reservation = Reservation::findOrFail($request->reservation_id);
            event(new ReservationCreated($reservation, $request->metadata ?? []));

            return response()->json([
                'success' => true,
                'message' => 'Événement de réservation déclenché avec succès',
                'data' => [
                    'reservation_id' => $reservation->id,
                    'event' => 'ReservationCreated'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du déclenchement de l\'événement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déclencher un événement de recharge demandée
     */
    public function triggerRechargeEvent(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'operator_id' => 'required|exists:enhanced_users,id',
                'recharge_data' => 'required|array',
                'recharge_data.amount' => 'required|numeric|min:0.01',
                'recharge_data.payment_status' => 'required|in:completed,pending,canceled,failed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $operator = EnhancedUser::findOrFail($request->operator_id);
            event(new RechargeRequested($operator, $request->recharge_data));

            return response()->json([
                'success' => true,
                'message' => 'Événement de recharge déclenché avec succès',
                'data' => [
                    'operator_id' => $operator->id,
                    'operator_name' => $operator->name,
                    'event' => 'RechargeRequested'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du déclenchement de l\'événement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler une réservation
     */
    public function cancelReservation(Request $request, int $reservationId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $this->reservationService->cancelReservation(
                $reservationId,
                $request->reason
            );

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Réservation annulée avec succès' : 'Échec de l\'annulation',
                'data' => [
                    'reservation_id' => $reservationId,
                    'canceled_at' => now()->toISOString(),
                    'reason' => $request->reason
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la réservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de réservation d'un utilisateur
     */
    public function getUserReservationStats(int $userId): JsonResponse
    {
        try {
            $stats = $this->reservationService->getUserReservationStats($userId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de recharge d'un utilisateur
     */
    public function getUserRechargeStats(int $userId): JsonResponse
    {
        try {
            $stats = $this->reservationService->getUserRechargeStats($userId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
