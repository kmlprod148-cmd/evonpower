<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\ChargingSession;
use App\Models\Reservation;
use App\Services\PaymentGatewayService;
use App\Services\Gateways\Contracts\RefundResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Checkout Refund Service
 * 
 * Handles refunds for checkout sessions:
 * - Refund excess from prepaid session (always to wallet)
 * - Full refund to original payment method
 * - Refund to wallet for store credits
 */
class CheckoutRefundService
{
    protected PaymentGatewayService $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Refund excess from prepaid session (always to wallet, never back to card)
     * 
     * This is called after a charging session ends to refund any unused prepaid amount.
     * 
     * @param ChargingSession|Reservation $session
     * @return void
     */
    public function refundExcessToWallet(ChargingSession|Reservation $session): void
    {
        DB::transaction(function () use ($session) {
            // Get the original transaction
            $transaction = null;
            
            if ($session instanceof ChargingSession) {
                $transaction = $session->transaction;
            } elseif ($session instanceof Reservation) {
                $transaction = Transaction::find($session->transaction_id);
            }

            if (!$transaction) {
                Log::error('RefundExcess: No transaction found', [
                    'session_type' => get_class($session),
                    'session_id' => $session->id,
                ]);
                return;
            }

            // Get prepaid amount
            $prepaidAmount = (float) ($session->metadata['estimated_price'] ?? $transaction->amount);
            
            // Calculate actual consumption
            $actualAmount = $this->calculateActualAmount($session);
            
            // Calculate difference
            $difference = round($prepaidAmount - $actualAmount, 2);

            // Only refund if there's a positive difference
            if ($difference <= 0) {
                Log::info('RefundExcess: No excess to refund', [
                    'session_id' => $session->id,
                    'prepaid_amount' => $prepaidAmount,
                    'actual_amount' => $actualAmount,
                    'difference' => $difference,
                ]);
                return;
            }

            // Get user
            $user = null;
            if ($session instanceof ChargingSession) {
                $user = $session->user;
            } elseif ($session instanceof Reservation) {
                $user = $session->user ?? User::find($session->metadata['guest_user_id'] ?? null);
            }

            if (!$user) {
                Log::error('RefundExcess: No user found', [
                    'session_id' => $session->id,
                ]);
                return;
            }

            // Credit to user's wallet
            $wallet = $user->wallet;
            if (!$wallet) {
                $wallet = Wallet::create([
                    'owner_type' => User::class,
                    'owner_id' => $user->id,
                    'balance' => 0,
                    'currency' => $transaction->currency ?? 'MAD',
                ]);
            }

            // Create wallet transaction
            $walletTransaction = $wallet->credit(
                $difference,
                "Refund: Excess from prepaid charging session",
                [
                    'type' => 'refund',
                    'original_transaction_id' => $transaction->id,
                    'session_id' => $session->id,
                    'prepaid_amount' => $prepaidAmount,
                    'actual_amount' => $actualAmount,
                ]
            );

            // Update session with refund info
            if ($session instanceof ChargingSession) {
                $session->update([
                    'metadata' => array_merge($session->metadata ?? [], [
                        'refund_amount' => $difference,
                        'refund_type' => 'excess_to_wallet',
                        'refunded_at' => now()->toIso8601String(),
                        'wallet_transaction_id' => $walletTransaction->id,
                    ]),
                ]);
            } elseif ($session instanceof Reservation) {
                $session->update([
                    'metadata' => array_merge($session->metadata ?? [], [
                        'refund_amount' => $difference,
                        'refund_type' => 'excess_to_wallet',
                        'refunded_at' => now()->toIso8601String(),
                        'wallet_transaction_id' => $walletTransaction->id,
                    ]),
                ]);
            }

            Log::info('RefundExcess: Processed successfully', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'prepaid_amount' => $prepaidAmount,
                'actual_amount' => $actualAmount,
                'refund_amount' => $difference,
                'wallet_transaction_id' => $walletTransaction->id,
            ]);

            // TODO: Send refund confirmation email
        });
    }

    /**
     * Calculate actual amount consumed
     * 
     * @param ChargingSession|Reservation $session
     * @return float
     */
    protected function calculateActualAmount(ChargingSession|Reservation $session): float
    {
        // Get tariff plan
        $chargePoint = $session->chargePoint;
        $tariffPlan = $chargePoint->tariffPlan ?? $chargePoint->pricingPlan;

        if (!$tariffPlan) {
            // Fallback: use estimated amount
            return (float) ($session->metadata['estimated_price'] ?? 0);
        }

        // Get consumption details
        if ($session instanceof ChargingSession) {
            $energyUsed = (float) ($session->energy_delivered ?? 0);
            $durationMinutes = (int) ($session->duration_minutes ?? 0);
        } else {
            $energyUsed = (float) ($session->actual_energy ?? $session->estimated_energy ?? 0);
            $durationMinutes = (int) ($session->duration_minutes ?? 0);
        }

        // Calculate based on billing type
        $actualAmount = 0;

        if ($tariffPlan->price_per_minute > 0) {
            // Per minute billing
            $basePrice = $tariffPlan->price_per_minute * $durationMinutes;
            $activationFee = (float) ($tariffPlan->activation_fee ?? 0);
            $actualAmount = $basePrice + $activationFee;
        } elseif ($tariffPlan->price_per_kwh > 0) {
            // Per kWh billing
            $basePrice = $tariffPlan->price_per_kwh * $energyUsed;
            $activationFee = (float) ($tariffPlan->activation_fee ?? 0);
            $actualAmount = $basePrice + $activationFee;
        } else {
            // Flat rate - use prepaid amount
            $actualAmount = (float) ($session->metadata['estimated_price'] ?? 0);
        }

        // Apply VAT
        $vatRate = (float) ($tariffPlan->vat_rate ?? 20);
        $vatAmount = round($actualAmount * ($vatRate / 100), 2);
        $actualAmount = round($actualAmount + $vatAmount, 2);

        return $actualAmount;
    }

    /**
     * Full refund to original payment method
     * 
     * @param Transaction $transaction
     * @param float $amount
     * @param string $reason
     * @return RefundResult
     */
    public function refundToCard(Transaction $transaction, float $amount, string $reason = ''): RefundResult
    {
        // Get the gateway based on transaction's gateway type
        $gatewayType = $transaction->gateway_type;
        
        if (!$gatewayType) {
            return new RefundResult([
                'success' => false,
                'status' => 'failed',
                'amount' => $amount,
                'error_message' => 'No gateway type found for transaction',
            ]);
        }

        // Resolve gateway
        $gateway = $this->paymentGatewayService->createGateway($gatewayType, $transaction->chargePoint ?? null);

        // Process refund
        $result = $gateway->refundPayment($transaction->gateway_transaction_id, $amount);

        // Create refund record in database
        DB::transaction(function () use ($transaction, $amount, $result, $reason) {
            // Create refund transaction
            $refundTransaction = Transaction::create([
                'user_id' => $transaction->user_id,
                'charge_point_id' => $transaction->charge_point_id,
                'partner_id' => $transaction->partner_id,
                'amount' => -$amount,
                'currency' => $transaction->currency,
                'type' => 'refund',
                'status' => $result->success ? 'completed' : 'failed',
                'gateway_type' => $transaction->gateway_type,
                'gateway_transaction_id' => $result->refundId,
                'parent_transaction_id' => $transaction->id,
                'payment_data' => [
                    'original_transaction_id' => $transaction->id,
                    'refund_reason' => $reason,
                    'gateway_response' => $result->rawResponse,
                ],
            ]);

            // Update original transaction
            $transaction->update([
                'status' => $result->success ? 'refunded' : 'refund_failed',
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'refund_amount' => $amount,
                    'refund_id' => $refundTransaction->id,
                    'refunded_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::info('RefundToCard: Processed', [
                'original_transaction_id' => $transaction->id,
                'refund_transaction_id' => $refundTransaction->id,
                'amount' => $amount,
                'success' => $result->success,
            ]);
        });

        return $result;
    }

    /**
     * Refund to wallet (store credits)
     * 
     * @param User $user
     * @param float $amount
     * @param string $reason
     * @param int|null $originalTransactionId
     * @return WalletTransaction
     */
    public function refundToWallet(
        User $user, 
        float $amount, 
        string $reason = '', 
        ?int $originalTransactionId = null
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $amount, $reason, $originalTransactionId) {
            // Get or create wallet
            $wallet = $user->wallet;
            if (!$wallet) {
                $wallet = Wallet::create([
                    'owner_type' => User::class,
                    'owner_id' => $user->id,
                    'balance' => 0,
                    'currency' => 'MAD',
                ]);
            }

            // Create wallet transaction
            $walletTransaction = $wallet->credit(
                $amount,
                $reason ?: 'Refund to wallet',
                [
                    'type' => 'refund',
                    'original_transaction_id' => $originalTransactionId,
                ]
            );

            Log::info('RefundToWallet: Processed', [
                'user_id' => $user->id,
                'wallet_transaction_id' => $walletTransaction->id,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return $walletTransaction;
        });
    }

    /**
     * Process batch refunds for multiple sessions
     * 
     * @param array $sessions
     * @return array
     */
    public function processBatchRefunds(array $sessions): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($sessions as $session) {
            try {
                $this->refundExcessToWallet($session);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
