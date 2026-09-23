<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RefundService
{
    /**
     * Create and process a refund to wallet
     */
    public function refundToWallet(
        Wallet $wallet,
        float $amount,
        ?WalletTransaction $originalTransaction = null,
        ?int $transactionId = null,
        ?int $reservationId = null,
        string $reason = null,
        ?User $requestedBy = null
    ): Refund {
        return DB::transaction(function () use ($wallet, $amount, $originalTransaction, $transactionId, $reservationId, $reason, $requestedBy) {
            // Create refund record
            $refund = Refund::create([
                'wallet_transaction_id' => $originalTransaction?->id,
                'transaction_id' => $transactionId,
                'reservation_id' => $reservationId,
                'refund_wallet_id' => $wallet->id,
                'amount' => $amount,
                'currency' => $wallet->currency ?? 'EUR',
                'refund_type' => 'wallet',
                'status' => 'pending',
                'reason' => $reason,
                'requested_by' => $requestedBy?->id,
            ]);

            // Credit the wallet
            $wallet->credit(
                $amount,
                "Refund for " . ($originalTransaction ? "transaction #{$originalTransaction->id}" : "reservation #{$reservationId}"),
                [
                    'refund_id' => $refund->id,
                    'original_transaction_id' => $originalTransaction?->id,
                    'refund_type' => 'wallet',
                ]
            );

            // Mark refund as completed
            $refund->markAsCompleted();

            return $refund;
        });
    }

    /**
     * Create and process a refund to bank card via CMI
     */
    public function refundToCardViaCmi(
        float $amount,
        string $paymentReference,
        ?WalletTransaction $originalTransaction = null,
        ?int $transactionId = null,
        ?int $reservationId = null,
        string $reason = null,
        ?User $requestedBy = null
    ): Refund {
        return DB::transaction(function () use ($amount, $paymentReference, $originalTransaction, $transactionId, $reservationId, $reason, $requestedBy) {
            // Create refund record
            $refund = Refund::create([
                'wallet_transaction_id' => $originalTransaction?->id,
                'transaction_id' => $transactionId,
                'reservation_id' => $reservationId,
                'amount' => $amount,
                'currency' => 'EUR',
                'refund_type' => 'bank_card',
                'status' => 'processing',
                'payment_gateway' => 'cmi',
                'original_payment_reference' => $paymentReference,
                'reason' => $reason,
                'requested_by' => $requestedBy?->id,
            ]);

            // Process CMI refund
            try {
                $cmiResponse = $this->processCmiRefund($paymentReference, $amount, $refund->id);
                
                $refund->update([
                    'external_refund_id' => $cmiResponse['refund_id'] ?? null,
                    'gateway_response' => $cmiResponse,
                    'status' => $cmiResponse['success'] ? 'completed' : 'failed',
                    'processed_at' => now(),
                ]);
            } catch (\Exception $e) {
                $refund->update([
                    'status' => 'failed',
                    'gateway_response' => ['error' => $e->getMessage()],
                    'processed_at' => now(),
                    'notes' => $e->getMessage(),
                ]);
            }

            return $refund->fresh();
        });
    }

    /**
     * Create and process a refund to bank card via Stripe
     */
    public function refundToCardViaStripe(
        float $amount,
        string $paymentIntentId,
        ?WalletTransaction $originalTransaction = null,
        ?int $transactionId = null,
        ?int $reservationId = null,
        string $reason = null,
        ?User $requestedBy = null
    ): Refund {
        return DB::transaction(function () use ($amount, $paymentIntentId, $originalTransaction, $transactionId, $reservationId, $reason, $requestedBy) {
            // Create refund record
            $refund = Refund::create([
                'wallet_transaction_id' => $originalTransaction?->id,
                'transaction_id' => $transactionId,
                'reservation_id' => $reservationId,
                'amount' => $amount,
                'currency' => 'EUR',
                'refund_type' => 'bank_card',
                'status' => 'processing',
                'payment_gateway' => 'stripe',
                'original_payment_reference' => $paymentIntentId,
                'reason' => $reason,
                'requested_by' => $requestedBy?->id,
            ]);

            // Process Stripe refund
            try {
                $stripeResponse = $this->processStripeRefund($paymentIntentId, $amount, $refund->id);
                
                $refund->update([
                    'external_refund_id' => $stripeResponse['id'] ?? null,
                    'gateway_response' => $stripeResponse,
                    'status' => $stripeResponse['status'] === 'succeeded' ? 'completed' : 'failed',
                    'processed_at' => now(),
                ]);
            } catch (\Exception $e) {
                $refund->update([
                    'status' => 'failed',
                    'gateway_response' => ['error' => $e->getMessage()],
                    'processed_at' => now(),
                    'notes' => $e->getMessage(),
                ]);
            }

            return $refund->fresh();
        });
    }

    /**
     * Process a refund based on type
     */
    public function processRefund(
        string $refundType,
        array $data,
        ?WalletTransaction $originalTransaction = null,
        ?int $transactionId = null,
        ?int $reservationId = null,
        string $reason = null,
        ?User $requestedBy = null
    ): Refund {
        return match ($refundType) {
            'wallet' => $this->refundToWallet(
                $data['wallet'],
                $data['amount'],
                $originalTransaction,
                $transactionId,
                $reservationId,
                $reason,
                $requestedBy
            ),
            'bank_card' => $this->refundToCardViaStripe(
                $data['amount'],
                $data['payment_reference'],
                $originalTransaction,
                $transactionId,
                $reservationId,
                $reason,
                $requestedBy
            ),
            default => throw new \InvalidArgumentException("Invalid refund type: {$refundType}"),
        };
    }

    /**
     * Reverse a completed refund
     */
    public function reverseRefund(int $refundId, string $reason, ?User $processedBy = null): ?Refund
    {
        $refund = Refund::find($refundId);
        if (!$refund || !$refund->isCompleted()) {
            return null;
        }

        $refund->markAsReversed($reason);

        // If it was a wallet refund, debit the wallet back
        if ($refund->isToWallet() && $refund->refund_wallet_id) {
            $wallet = Wallet::find($refund->refund_wallet_id);
            if ($wallet) {
                try {
                    $wallet->debit(
                        $refund->amount,
                        "Refund reversal for refund #{$refund->id}",
                        ['refund_id' => $refund->id, 'reversal' => true]
                    );
                } catch (\Exception $e) {
                    // Log error but don't fail the reversal
                }
            }
        }

        return $refund->fresh();
    }

    /**
     * Get all refunds with filters
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Refund::query()
            ->with(['walletTransaction', 'transaction', 'reservation', 'refundWallet', 'requester', 'processor']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['refund_type'])) {
            $query->where('refund_type', $filters['refund_type']);
        }

        if (!empty($filters['payment_gateway'])) {
            $query->where('payment_gateway', $filters['payment_gateway']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';

        return $query->orderBy($orderBy, $orderDir)->paginate($perPage);
    }

    /**
     * Process CMI refund API call
     */
    private function processCmiRefund(string $paymentReference, float $amount, int $refundId): array
    {
        // CMI API integration would go here
        // This is a placeholder implementation
        $cmiEndpoint = config('services.cmi.endpoint', 'https://cmi.com/refund');
        $cmiMerchantId = config('services.cmi.merchant_id');
        
        // Simulated response for now
        return [
            'success' => true,
            'refund_id' => 'CMI-' . $refundId . '-' . time(),
            'amount' => $amount,
            'reference' => $paymentReference,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Process Stripe refund API call
     */
    private function processStripeRefund(string $paymentIntentId, float $amount, int $refundId): array
    {
        // Stripe API integration would go here
        // This is a placeholder implementation
        $stripeKey = config('services.stripe.secret');
        
        // In production, you would call Stripe API:
        // \Stripe\Refund::create([
        //     'payment_intent' => $paymentIntentId,
        //     'amount' => $amount * 100, // Stripe uses cents
        // ]);

        // Simulated response for now
        return [
            'id' => 're_' . $refundId . '_' . time(),
            'amount' => $amount * 100,
            'currency' => 'eur',
            'payment_intent' => $paymentIntentId,
            'status' => 'succeeded',
            'created' => time(),
        ];
    }

    /**
     * Get refund statistics
     */
    public function getStats(array $filters = []): array
    {
        $query = Refund::query();

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_refunds' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'wallet_refunds' => (clone $query)->where('refund_type', 'wallet')->count(),
            'wallet_amount' => (clone $query)->where('refund_type', 'wallet')->sum('amount'),
            'card_refunds' => (clone $query)->where('refund_type', 'bank_card')->count(),
            'card_amount' => (clone $query)->where('refund_type', 'bank_card')->sum('amount'),
            'pending_count' => (clone $query)->where('status', 'pending')->count(),
            'completed_count' => (clone $query)->where('status', 'completed')->count(),
            'failed_count' => (clone $query)->where('status', 'failed')->count(),
        ];
    }
}
