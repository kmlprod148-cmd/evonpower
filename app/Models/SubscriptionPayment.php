<?php

namespace App\Models;

use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SubscriptionPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_subscription_id',
        'user_id',
        'amount',
        'vat_amount',
        'currency',
        'payment_method',
        'status',
        'external_id',
        'reference',
        'gateway_data',
        'paid_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'vat_amount'  => 'decimal:2',
        'gateway_data' => 'array',
        'paid_at'     => 'datetime',
        'failed_at'   => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->reference)) {
                $model->reference = 'SUBPAY-' . strtoupper(Str::random(10));
            }
        });
    }

    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function markAsCompleted(string $externalId = null): bool
    {
        return $this->update([
            'status'      => 'completed',
            'external_id' => $externalId ?? $this->external_id,
            'paid_at'     => now(),
        ]);
    }

    public function markAsFailed(string $reason = ''): bool
    {
        return $this->update([
            'status'         => 'failed',
            'failed_at'      => now(),
            'failure_reason' => $reason,
        ]);
    }

    public function getFormattedAmountAttribute(): string
    {
        return MoneyService::format($this->amount, $this->currency ?? 'EUR');
    }
}
