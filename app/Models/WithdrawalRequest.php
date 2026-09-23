<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'wallet_id',
        'amount',
        'currency',
        'fee',
        'net_amount',
        'status',
        'withdrawal_method',
        'bank_name',
        'bank_account',
        'bank_code',
        'card_last4',
        'card_brand',
        'destination_wallet_id',
        'external_reference',
        'external_id',
        'external_response',
        'approved_by',
        'approved_at',
        'approval_notes',
        'processed_by',
        'processed_at',
        'processing_notes',
        'status_history',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'status_history' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the owner (Integrator, Partner, etc.)
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the wallet
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the user who approved this request
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who processed this request
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the destination wallet (if applicable)
     */
    public function destinationWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'destination_wallet_id');
    }

    /**
     * Get status enum
     */
    public function getStatusEnumAttribute(): WithdrawalStatus
    {
        return WithdrawalStatus::from($this->status);
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if request can be approved
     */
    public function canApprove(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request can be rejected
     */
    public function canReject(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    /**
     * Check if request can be processed
     */
    public function canProcess(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Approve the request
     */
    public function approve(User $approvedBy, string $notes = null): bool
    {
        $history = $this->status_history ?? [];
        $history[] = [
            'status' => 'approved',
            'changed_at' => now()->toISOString(),
            'changed_by' => $approvedBy->id,
            'notes' => $notes,
        ];

        return $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
            'status_history' => $history,
        ]);
    }

    /**
     * Reject the request
     */
    public function reject(User $rejectedBy, string $notes = null): bool
    {
        $history = $this->status_history ?? [];
        $history[] = [
            'status' => 'rejected',
            'changed_at' => now()->toISOString(),
            'changed_by' => $rejectedBy->id,
            'notes' => $notes,
        ];

        return $this->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
            'status_history' => $history,
        ]);
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(User $processedBy, string $notes = null): bool
    {
        $history = $this->status_history ?? [];
        $history[] = [
            'status' => 'processing',
            'changed_at' => now()->toISOString(),
            'changed_by' => $processedBy->id,
            'notes' => $notes,
        ];

        return $this->update([
            'status' => 'processing',
            'processed_by' => $processedBy->id,
            'processing_notes' => $notes,
            'status_history' => $history,
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(User $processedBy, string $externalId = null, string $notes = null): bool
    {
        $history = $this->status_history ?? [];
        $history[] = [
            'status' => 'completed',
            'changed_at' => now()->toISOString(),
            'changed_by' => $processedBy->id,
            'notes' => $notes,
        ];

        return $this->update([
            'status' => 'completed',
            'processed_by' => $processedBy->id,
            'processed_at' => now(),
            'external_id' => $externalId,
            'processing_notes' => $notes,
            'status_history' => $history,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(User $processedBy, string $reason): bool
    {
        $history = $this->status_history ?? [];
        $history[] = [
            'status' => 'failed',
            'changed_at' => now()->toISOString(),
            'changed_by' => $processedBy->id,
            'notes' => $reason,
        ];

        return $this->update([
            'status' => 'failed',
            'processed_by' => $processedBy->id,
            'processed_at' => now(),
            'processing_notes' => $reason,
            'status_history' => $history,
        ]);
    }

    /**
     * Cancel the request
     */
    public function cancel(string $notes = null): bool
    {
        $history = $this->status_history ?? [];
        $history[] = [
            'status' => 'cancelled',
            'changed_at' => now()->toISOString(),
            'notes' => $notes,
        ];

        return $this->update([
            'status' => 'cancelled',
            'processing_notes' => $notes,
            'status_history' => $history,
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
     * Get formatted net amount
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return MoneyService::format($this->net_amount, $this->currency ?? 'EUR');
    }

    /**
     * Get formatted fee
     */
    public function getFormattedFeeAttribute(): string
    {
        return MoneyService::format($this->fee, $this->currency ?? 'EUR');
    }

    /**
     * Get masked bank account
     */
    public function getMaskedBankAccountAttribute(): string
    {
        if (!$this->bank_account) {
            return 'N/A';
        }
        $length = strlen($this->bank_account);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        return str_repeat('*', $length - 4) . substr($this->bank_account, -4);
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for processing requests
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for a specific owner
     */
    public function scopeForOwner($query, $owner)
    {
        return $query->where('owner_type', get_class($owner))
            ->where('owner_id', $owner->id);
    }

    /**
     * Scope for a specific wallet
     */
    public function scopeForWallet($query, int $walletId)
    {
        return $query->where('wallet_id', $walletId);
    }
}
