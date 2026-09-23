<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'user_id',
        'share_type',
        'share_value',
        'share_amount',
        'paid_amount',
        'status',
        'last_wallet_transaction_id',
        'last_refund_transaction_id',
        'metadata',
    ];

    protected $casts = [
        'share_value' => 'decimal:4',
        'share_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastWalletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'last_wallet_transaction_id');
    }

    public function lastRefundTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'last_refund_transaction_id');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->share_amount - (float) $this->paid_amount);
    }
}
