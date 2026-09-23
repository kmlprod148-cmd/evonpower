<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Services\PostpaidChargingService;
use App\Support\AppCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PostpaidChargingController extends Controller
{
    protected $postpaidService;

    public function __construct(PostpaidChargingService $postpaidService)
    {
        $this->postpaidService = $postpaidService;
    }

    /**
     * Vérifier le wallet avant de démarrer une session
     * 
     * GET /api/postpaid-charging/check-wallet/{chargingPointId}
     */
    public function checkWallet(Request $request, $chargingPointId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié',
                ], 401);
            }

            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            
            $minThreshold = $request->input('min_threshold') ?? config('charging.postpaid_min_threshold', 10.00);
            
            $wallet = $user->getOrCreateWallet();
            $currentBalance = (float) $wallet->balance;
            $hasSufficientBalance = $wallet->hasSufficientBalance($minThreshold);
            
            $missingBalance = max(0, $minThreshold - $currentBalance);
            $minimumRecharge = $hasSufficientBalance ? 0 : max($missingBalance, 5.00);
            $recommendedRecharge = max($minThreshold * 2, 20.00);
            $currency = AppCurrency::code();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'wallet' => [
                        'id' => $wallet->id,
                        'balance' => $currentBalance,
                        'formatted_balance' => number_format($currentBalance, 2) . ' ' . $currency,
                        'status' => $wallet->status ?? 'active',
                    ],
                    'validation' => [
                        'can_start' => $hasSufficientBalance,
                        'required_balance' => $minThreshold,
                        'missing_balance' => $missingBalance,
                        'has_sufficient_balance' => $hasSufficientBalance,
                    ],
                    'recharge_info' => $hasSufficientBalance ? null : [
                        'needs_recharge' => true,
                        'minimum_recharge' => round($minimumRecharge, 2),
                        'recommended_recharge' => round($recommendedRecharge, 2),
                        'message' => sprintf(
                            'Solde insuffisant. Veuillez recharger votre wallet d\'au moins %.2f %s (recommandé: %.2f %s)',
                            $minimumRecharge,
                            $currency,
                            $recommendedRecharge,
                            $currency
                        ),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du wallet', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Démarrer une session de recharge postpayée
     * 
     * POST /api/postpaid-charging/start/{chargingPointId}
     */
    public function startSession(Request $request, $chargingPointId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'connector_id' => 'nullable|integer|min:1',
                'min_threshold' => 'nullable|numeric|min:0',
                'id_tag' => 'nullable|string|max:255',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié',
                ], 401);
            }

            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            $result = $this->postpaidService->startPostpaidSession(
                $chargingPoint,
                $user,
                [
                    'connector_id' => $request->input('connector_id', 1),
                    'min_threshold' => $request->input('min_threshold'),
                    'id_tag' => $request->input('id_tag'),
                    'metadata' => $request->input('metadata', []),
                ]
            );

            if (!$result['success']) {
                // Améliorer le message d'erreur pour le solde insuffisant
                if (isset($result['error']) && $result['error'] === 'insufficient_balance') {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'error' => 'insufficient_balance',
                        'wallet' => $result['wallet'] ?? null,
                        'recharge_info' => $result['recharge_info'] ?? null,
                    ], 402); // 402 Payment Required
                }
                
                return response()->json($result, 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Session postpayée démarrée avec succès',
                'data' => [
                    'session_id' => $result['session']->session_id,
                    'status' => $result['session']->status,
                    'started_at' => $result['session']->started_at,
                    'monitoring_token' => $result['monitoring_token'] ?? null,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur lors du démarrage de la session postpayée', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du démarrage: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir le statut et la consommation en temps réel
     * 
     * GET /api/postpaid-charging/{sessionId}/status
     */
    public function getStatus($sessionId)
    {
        try {
            $session = ChargingSession::where('session_id', $sessionId)
                ->orWhere('id', $sessionId)
                ->firstOrFail();

            // Vérifier que l'utilisateur a le droit d'accéder à cette session
            $user = Auth::user();
            if ($session->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé à cette session',
                ], 403);
            }

            // Mettre à jour la consommation en temps réel
            $updateResult = $this->postpaidService->updateRealTimeConsumption($session);
            $session->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $session->session_id,
                    'status' => $session->status,
                    'started_at' => $session->started_at,
                    'ended_at' => $session->ended_at,
                    'duration_minutes' => $session->duration ?? 0,
                    'energy_consumed_kwh' => $session->energy_consumed ?? 0,
                    'current_cost' => $session->cost ?? 0,
                    'payment_status' => $session->payment_status,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du statut', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Arrêter une session postpayée
     * 
     * POST /api/postpaid-charging/{sessionId}/stop
     */
    public function stopSession(Request $request, $sessionId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'stop_reason' => 'nullable|string|max:255',
                'meter_end' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $session = ChargingSession::where('session_id', $sessionId)
                ->orWhere('id', $sessionId)
                ->firstOrFail();

            // Vérifier que l'utilisateur a le droit d'accéder à cette session
            $user = Auth::user();
            if ($session->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé à cette session',
                ], 403);
            }

            $result = $this->postpaidService->endPostpaidSession($session, [
                'stop_reason' => $request->input('stop_reason', 'user_stop'),
                'meter_end' => $request->input('meter_end'),
            ]);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Session terminée avec succès',
                'data' => [
                    'session' => [
                        'session_id' => $result['session']->session_id,
                        'status' => $result['session']->status,
                        'ended_at' => $result['session']->ended_at,
                        'energy_consumed_kwh' => $result['summary']['energy_consumed_kwh'],
                        'duration_minutes' => $result['summary']['duration_minutes'],
                        'final_cost' => $result['summary']['final_cost'],
                    ],
                    'invoice' => $result['invoice'] ?? null,
                    'payment' => $result['payment'] ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'arrêt de la session postpayée', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'arrêt: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir la facture d'une session terminée
     * 
     * GET /api/postpaid-charging/{sessionId}/invoice
     */
    public function getInvoice($sessionId)
    {
        try {
            $session = ChargingSession::where('session_id', $sessionId)
                ->orWhere('id', $sessionId)
                ->firstOrFail();

            // Vérifier que l'utilisateur a le droit d'accéder à cette session
            $user = Auth::user();
            if ($session->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé à cette session',
                ], 403);
            }

            if ($session->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'La session n\'est pas encore terminée',
                ], 400);
            }

            $invoiceService = app(\App\Services\PostpaidInvoiceService::class);
            $invoice = $invoiceService->getInvoice($session);

            if (!$invoice) {
                // Générer la facture si elle n'existe pas
                $invoice = $invoiceService->generateInvoice($session);
            }

            return response()->json([
                'success' => true,
                'data' => $invoice,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la facture', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la facture: ' . $e->getMessage(),
            ], 500);
        }
    }
}
