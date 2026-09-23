<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Services\ImmediateStartService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ImmediateStartController extends Controller
{
    protected $immediateStartService;

    public function __construct(ImmediateStartService $immediateStartService)
    {
        $this->immediateStartService = $immediateStartService;
    }

    /**
     * Get immediate start status for a charging point
     */
    public function getStatus(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $status = $this->immediateStartService->getImmediateStartStatus($chargingPoint);
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get immediate start status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start charging immediately
     */
    public function startCharging(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'connector_id' => 'nullable|integer|min:1',
            'id_tag' => 'nullable|string|max:255',
            'reservation_id' => 'nullable|integer|exists:reservations,id',
            'estimated_cost' => 'nullable|numeric|min:0',
            'prepaid_amount' => 'nullable|numeric|min:0',
            'min_threshold' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sessionData = $validator->validated();
            $result = $this->immediateStartService->startChargingImmediately($chargingPoint, $sessionData);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'session' => $result['session'],
                        'steve_response' => $result['steve_response']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start charging immediately',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process payment and start charging immediately
     */
    public function processPaymentAndStart(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'connector_id' => 'nullable|integer|min:1',
            'id_tag' => 'nullable|string|max:255',
            'reservation_id' => 'nullable|integer|exists:reservations,id',
            'estimated_cost' => 'nullable|numeric|min:0',
            'prepaid_amount' => 'nullable|numeric|min:0',
            'min_threshold' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            $paymentData = [
                'amount' => $validatedData['amount'],
                'charging_point_id' => $chargingPoint->id,
            ];
            
            $sessionData = array_filter($validatedData, function($key) {
                return !in_array($key, ['amount']);
            }, ARRAY_FILTER_USE_KEY);

            $result = $this->immediateStartService->processPaymentAndStart($chargingPoint, $paymentData, $sessionData);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'payment' => $result['payment'],
                        'charging' => $result['charging']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment and start charging',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop charging session
     */
    public function stopCharging(ChargingSession $session): JsonResponse
    {
        try {
            // Check if user can stop this session
            if (Auth::id() !== $session->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to stop this charging session'
                ], 403);
            }

            $result = $this->immediateStartService->stopChargingSession($session);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop charging session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get charging session status
     */
    public function getSessionStatus(ChargingSession $session): JsonResponse
    {
        try {
            // Check if user can view this session
            if (Auth::id() !== $session->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this charging session'
                ], 403);
            }

            $status = $this->immediateStartService->getChargingSessionStatus($session);
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get charging session status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's active charging sessions
     */
    public function getActiveSessions(): JsonResponse
    {
        try {
            $user = Auth::user();
            $activeSessions = ChargingSession::where('user_id', $user->id)
                ->whereIn('status', ['charging', 'starting'])
                ->with(['chargingPoint', 'user'])
                ->get();

            $sessions = $activeSessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'charging_point' => [
                        'id' => $session->chargingPoint->id,
                        'name' => $session->chargingPoint->name,
                        'location' => $session->chargingPoint->address,
                    ],
                    'status' => $session->status,
                    'started_at' => $session->started_at,
                    'duration' => $session->started_at ? now()->diffInMinutes($session->started_at) : 0,
                    'connector_id' => $session->connector_id,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $sessions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get active charging sessions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check wallet balance for immediate start
     */
    public function checkWalletBalance(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required to check balance.',
                ], 401);
            }

            $wallet = $user->getOrCreateWallet();
            $wallet->refresh();
            
            // Get estimated cost for immediate start
            $estimatedCost = $this->getEstimatedCost($chargingPoint);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'current_balance' => $wallet->balance,
                    'estimated_cost' => $estimatedCost,
                    'sufficient_balance' => $wallet->balance >= $estimatedCost,
                    'formatted_balance' => $wallet->getFormattedBalance(),
                    'formatted_estimated_cost' => \App\Services\MoneyService::format($estimatedCost),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check wallet balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get estimated cost for immediate start
     */
    protected function getEstimatedCost(ChargingPoint $chargingPoint): float
    {
        // This is a simplified estimation
        // In a real implementation, you would calculate based on:
        // - Charging point power output
        // - Current electricity rates
        // - Expected charging duration
        // - Business profile pricing
        
        $baseRate = 0.25; // €0.25 per kWh
        $estimatedEnergy = 10; // 10 kWh as default estimation
        
        return $baseRate * $estimatedEnergy;
    }
}
