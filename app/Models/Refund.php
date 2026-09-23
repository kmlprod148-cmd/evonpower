<?php

namespace App\Models;

use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_transaction_id',
        'transaction_id',
        'reservation_id',
        'refund_wallet_id',
        'amount',
        'currency',
        'refund_type',
        'status',
        'payment_gateway',
        'original_payment_reference',
        'refund_reference',
        'external_refund_id',
        'gateway_response',
        'reason',
        'notes',
        'requested_by',
        'processed_by',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'gateway_response' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the original wallet transaction
     */
    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }

    /**
     * Get the related transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the related reservation
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Get the refund wallet
     */
    public function refundWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'refund_wallet_id');
    }

    /**
     * Get the user who requested the refund
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who processed the refund
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Check if refund is to wallet
     */
    public function isToWallet(): bool
    {
        return $this->refund_type === 'wallet';
    }

    /**
     * Check if refund is to bank card
     */
    public function isToBankCard(): bool
    {
        return $this->refund_type === 'bank_card';
    }

    /**
     * Check if refund is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if refund is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if refund is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): bool
    {
        return $this->update(['status' => 'processing']);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(string $externalRefundId = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'external_refund_id' => $externalRefundId,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $reason): bool
    {
        return $this->update([
            'status' => 'failed',
            'processed_at' => now(),
            'notes' => $reason,
        ]);
    }

    /**
     * Mark as reversed
     */
    public function markAsReversed(string $reason = null): bool
    {
        return $this->update([
            'status' => 'reversed',
            'processed_at' => now(),
            'notes' => $reason ?? $this->notes,
        ]);
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return MoneyService::format($this->amount, $this->currency ?? 'EUR');
    }

    /**
     * Get gateway label
     */
    public function getGatewayLabelAttribute(): string
    {
        return match($this->payment_gateway) {
            'cmi' => 'CMI',
            'stripe' => 'Stripe',
            default => 'N/A',
        };
    }

    /**
     * Get refund type label
     */
    public function getRefundTypeLabelAttribute(): string
    {
        return match($this->refund_type) {
            'wallet' => 'Vers le wallet',
            'bank_card' => 'Vers carte bancaire',
            default => 'N/A',
        };
    }

    /**
     * Scope for pending refunds
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed refunds
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for wallet refunds
     */
    public function scopeWalletRefunds($query)
    {
        return $query->where('refund_type', 'wallet');
    }

    /**
     * Scope for bank card refunds
     */
    public function scopeBankCardRefunds($query)
    {
        return $query->where('refund_type', 'bank_card');
    }

    /**
     * Scope for a specific transaction
     */
    public function scopeForTransaction($query, int $transactionId)
    {
        return $query->where('transaction_id', $transactionId);
    }

    /**
     * Scope for a specific reservation
     */
    public function scopeForReservation($query, int $reservationId)
    {
        return $query->where('reservation_id', $reservationId);
    }
}
