<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Models\User;
use App\Models\Wallet;
use App\Services\SteVeApiService;
use App\Services\SteveService;
use App\Services\TransactionService;
use App\Services\MoneyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ImmediateStartService
{
    protected $steveApiService;
    protected $steveService;
    protected $transactionService;

    public function __construct(
        SteVeApiService $steveApiService,
        SteveService $steveService,
        TransactionService $transactionService
    ) {
        $this->steveApiService = $steveApiService;
        $this->steveService = $steveService;
        $this->transactionService = $transactionService;
    }

    /**
     * Start charging immediately after successful payment
     */
    public function startChargingImmediately(ChargingPoint $chargingPoint, array $sessionData = []): array
    {
        return DB::transaction(function () use ($chargingPoint, $sessionData) {
            try {
                // Validate charging point is available
                if (!$this->isChargingPointAvailable($chargingPoint)) {
                    throw new \Exception('Charging point is not available for immediate start');
                }

                // Create charging session
                $session = $this->createChargingSession($chargingPoint, $sessionData);

                // Start charging via Steve API
                $startResult = $this->startChargingViaSteve($chargingPoint, $session, $sessionData);

                if (!$startResult['success']) {
                    throw new \Exception('Failed to start charging via Steve API: ' . $startResult['message']);
                }

                // Update session with Steve response
                $session->update([
                    'status' => 'charging',
                    'steve_session_id' => $startResult['session_id'] ?? null,
                    'started_at' => now(),
                ]);

                Log::info('Charging started immediately', [
                    'charging_point_id' => $chargingPoint->id,
                    'session_id' => $session->id,
                    'steve_session_id' => $startResult['session_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'session' => $session,
                    'steve_response' => $startResult,
                    'message' => 'Charging started successfully'
                ];

            } catch (\Exception $e) {
                Log::error('Failed to start charging immediately', [
                    'charging_point_id' => $chargingPoint->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Process payment and start charging immediately
     */
    public function processPaymentAndStart(ChargingPoint $chargingPoint, array $paymentData, array $sessionData = []): array
    {
        return DB::transaction(function () use ($chargingPoint, $paymentData, $sessionData) {
            try {
                $user = Auth::user();
                if (!$user) {
                    throw new \Exception('User must be authenticated for immediate start');
                }

                // Check wallet balance (refresh pour solde à jour)
                $wallet = $user->getOrCreateWallet();
                $wallet->refresh();
                $requiredAmount = $paymentData['amount'] ?? 0;

                if ($wallet->balance < $requiredAmount) {
                    throw new \Exception('Insufficient wallet balance for immediate start');
                }

                // Process payment
                $paymentResult = $this->processPayment($wallet, $requiredAmount, $paymentData);

                if (!$paymentResult['success']) {
                    throw new \Exception('Payment failed: ' . $paymentResult['message']);
                }

                // Start charging immediately
                $startResult = $this->startChargingImmediately($chargingPoint, $sessionData);

                if (!$startResult['success']) {
                    // Rollback payment if charging fails
                    $this->rollbackPayment($wallet, $requiredAmount, $paymentResult['transaction_id'] ?? null);
                    throw new \Exception('Failed to start charging: ' . $startResult['message']);
                }

                return [
                    'success' => true,
                    'payment' => $paymentResult,
                    'charging' => $startResult,
                    'message' => 'Payment processed and charging started successfully'
                ];

            } catch (\Exception $e) {
                Log::error('Failed to process payment and start charging', [
                    'charging_point_id' => $chargingPoint->id,
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Check if charging point is available for immediate start
     */
    protected function isChargingPointAvailable(ChargingPoint $chargingPoint): bool
    {
        // Check if charging point is online
        if ($chargingPoint->status !== 'online') {
            return false;
        }

        // Check if there's already an active session
        $activeSession = ChargingSession::where('charging_point_id', $chargingPoint->id)
            ->whereIn('status', ['charging', 'starting'])
            ->first();

        if ($activeSession) {
            return false;
        }

        // Check Steve API connection
        try {
            $connectionStatus = $this->steveService->status($chargingPoint);
            return $connectionStatus['success'] ?? false;
        } catch (\Exception $e) {
            Log::warning('Failed to check Steve API connection', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create charging session
     */
    protected function createChargingSession(ChargingPoint $chargingPoint, array $sessionData): ChargingSession
    {
        $user = Auth::user();
        
        $session = ChargingSession::create([
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'status' => 'starting',
            'mode' => 'immediate',
            'estimated_cost' => $sessionData['estimated_cost'] ?? 0,
            'prepaid_amount' => $sessionData['prepaid_amount'] ?? 0,
            'min_threshold' => $sessionData['min_threshold'] ?? 0,
            'wallet_validation_passed' => true,
            'wallet_validated_at' => now(),
            'started_at' => now(),
            'connector_id' => $sessionData['connector_id'] ?? 1,
            'id_tag' => $sessionData['id_tag'] ?? $user->id,
            'reservation_id' => $sessionData['reservation_id'] ?? null,
        ]);

        return $session;
    }

    /**
     * Start charging via Steve API
     */
    protected function startChargingViaSteve(ChargingPoint $chargingPoint, ChargingSession $session, array $sessionData): array
    {
        try {
            // Try SteVe API first
            $steveData = [
                'connector_id' => $session->connector_id,
                'id_tag' => $session->id_tag,
                'reservation_id' => $session->reservation_id,
                'session_id' => $session->id,
            ];

            $result = $this->steveApiService->startCharging($chargingPoint, $steveData);

            if ($result['success'] ?? false) {
                return [
                    'success' => true,
                    'session_id' => $result['session_id'] ?? $session->id,
                    'message' => 'Charging started via SteVe API'
                ];
            }

            // Fallback to SteveService
            $fallbackResult = $this->steveService->start($chargingPoint, $steveData);

            if ($fallbackResult['success'] ?? false) {
                return [
                    'success' => true,
                    'session_id' => $fallbackResult['session_id'] ?? $session->id,
                    'message' => 'Charging started via SteveService fallback'
                ];
            }

            throw new \Exception('Both SteVe API and SteveService failed to start charging');

        } catch (\Exception $e) {
            Log::error('Failed to start charging via Steve API', [
                'charging_point_id' => $chargingPoint->id,
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process payment
     */
    protected function processPayment(Wallet $wallet, float $amount, array $paymentData): array
    {
        try {
            $transaction = $wallet->debit(
                $amount,
                'Immediate charging session payment',
                [
                    'payment_type' => 'immediate_start',
                    'charging_point_id' => $paymentData['charging_point_id'] ?? null,
                    'session_id' => $paymentData['session_id'] ?? null,
                ]
            );

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'balance_after' => $wallet->fresh()->balance,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Rollback payment
     */
    protected function rollbackPayment(Wallet $wallet, float $amount, ?int $transactionId): void
    {
        try {
            $wallet->credit(
                $amount,
                'Payment rollback for failed immediate start',
                [
                    'rollback_for_transaction' => $transactionId,
                    'payment_type' => 'rollback',
                ]
            );

            Log::info('Payment rolled back successfully', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'original_transaction_id' => $transactionId
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to rollback payment', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'original_transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get immediate start status for a charging point
     */
    public function getImmediateStartStatus(ChargingPoint $chargingPoint): array
    {
        $isAvailable = $this->isChargingPointAvailable($chargingPoint);
        
        $activeSession = ChargingSession::where('charging_point_id', $chargingPoint->id)
            ->whereIn('status', ['charging', 'starting'])
            ->first();

        return [
            'available' => $isAvailable,
            'status' => $chargingPoint->status,
            'active_session' => $activeSession ? [
                'id' => $activeSession->id,
                'status' => $activeSession->status,
                'started_at' => $activeSession->started_at,
            ] : null,
            'steve_connection' => $this->getSteveConnectionStatus($chargingPoint),
        ];
    }

    /**
     * Get Steve connection status
     */
    protected function getSteveConnectionStatus(ChargingPoint $chargingPoint): array
    {
        try {
            $status = $this->steveService->status($chargingPoint);
            return [
                'connected' => $status['success'] ?? false,
                'message' => $status['message'] ?? 'Unknown status',
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Stop charging session
     */
    public function stopChargingSession(ChargingSession $session): array
    {
        try {
            $chargingPoint = $session->chargingPoint;
            
            // Stop via Steve API
            $stopResult = $this->steveApiService->stopCharging($chargingPoint, [
                'session_id' => $session->id,
                'steve_session_id' => $session->steve_session_id,
            ]);

            if ($stopResult['success'] ?? false) {
                $session->update([
                    'status' => 'completed',
                    'ended_at' => now(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Charging session stopped successfully'
                ];
            }

            throw new \Exception('Failed to stop charging session');

        } catch (\Exception $e) {
            Log::error('Failed to stop charging session', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get charging session status
     */
    public function getChargingSessionStatus(ChargingSession $session): array
    {
        try {
            $chargingPoint = $session->chargingPoint;
            
            // Get status from Steve API
            $status = $this->steveService->status($chargingPoint);
            
            return [
                'session_id' => $session->id,
                'status' => $session->status,
                'steve_status' => $status,
                'started_at' => $session->started_at,
                'ended_at' => $session->ended_at,
                'duration' => $session->started_at ? now()->diffInMinutes($session->started_at) : 0,
            ];

        } catch (\Exception $e) {
            return [
                'session_id' => $session->id,
                'status' => $session->status,
                'error' => $e->getMessage(),
            ];
        }
    }
}
