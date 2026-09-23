<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'start_date',
        'end_date',
        'status',
        'sessions_used',
        'kwh_used',
        'duration_minutes_used',
        'amount_paid',
        'vat_amount',
        'currency',
        'payment_method',
        'payment_reference',
        'transaction_id',
        'auto_renew',
        'renewal_date',
        'renewed_from_id',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'renewal_date' => 'date',
        'sessions_used' => 'integer',
        'kwh_used' => 'decimal:2',
        'duration_minutes_used' => 'integer',
        'amount_paid' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'auto_renew' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription plan
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Get the original transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the subscription this was renewed from
     */
    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'renewed_from_id');
    }

    /**
     * Get usage logs for this subscription
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(SubscriptionUsageLog::class, 'user_subscription_id');
    }

    /**
     * Get payment records for this subscription
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Get the completed payment (if any)
     */
    public function completedPayment()
    {
        return $this->payments()->where('status', 'completed')->latest()->first();
    }

    /**
     * Get the status enum
     */
    public function getStatusEnumAttribute(): SubscriptionStatus
    {
        return SubscriptionStatus::from($this->status);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if subscription has expired
     */
    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast() && $this->status === 'active';
    }

    /**
     * Check if subscription allows usage
     */
    public function allowsUsage(): bool
    {
        return $this->isActive() && !$this->isExpired();
    }

    /**
     * Get remaining sessions
     */
    public function getRemainingSessions(): ?int
    {
        if ($this->subscriptionPlan->max_sessions === null) {
            return null;
        }
        return max(0, $this->subscriptionPlan->max_sessions - $this->sessions_used);
    }

    /**
     * Get remaining kWh
     */
    public function getRemainingKwh(): ?float
    {
        if ($this->subscriptionPlan->max_kwh === null) {
            return null;
        }
        return max(0, $this->subscriptionPlan->max_kwh - $this->kwh_used);
    }

    /**
     * Get remaining duration in minutes
     */
    public function getRemainingDuration(): ?int
    {
        if ($this->subscriptionPlan->max_duration_minutes === null) {
            return null;
        }
        return max(0, $this->subscriptionPlan->max_duration_minutes - $this->duration_minutes_used);
    }

    /**
     * Get usage percentage
     */
    public function getUsagePercentage(): array
    {
        $usage = [];
        
        if ($this->subscriptionPlan->max_sessions !== null) {
            $usage['sessions'] = [
                'used' => $this->sessions_used,
                'max' => $this->subscriptionPlan->max_sessions,
                'percentage' => ($this->sessions_used / $this->subscriptionPlan->max_sessions) * 100,
            ];
        }
        
        if ($this->subscriptionPlan->max_kwh !== null) {
            $usage['kwh'] = [
                'used' => $this->kwh_used,
                'max' => $this->subscriptionPlan->max_kwh,
                'percentage' => ($this->kwh_used / $this->subscriptionPlan->max_kwh) * 100,
            ];
        }
        
        if ($this->subscriptionPlan->max_duration_minutes !== null) {
            $usage['duration'] = [
                'used' => $this->duration_minutes_used,
                'max' => $this->subscriptionPlan->max_duration_minutes,
                'percentage' => ($this->duration_minutes_used / $this->subscriptionPlan->max_duration_minutes) * 100,
            ];
        }
        
        return $usage;
    }

    /**
     * Check if can start a new session
     */
    public function canStartSession(): bool
    {
        if (!$this->allowsUsage()) {
            return false;
        }

        $plan = $this->subscriptionPlan;
        
        // Check session limit
        if ($plan->max_sessions !== null && $this->sessions_used >= $plan->max_sessions) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if can consume kWh
     */
    public function canConsumeKwh(float $kwh): bool
    {
        if (!$this->allowsUsage()) {
            return false;
        }

        $plan = $this->subscriptionPlan;
        
        // Check kWh limit
        if ($plan->max_kwh !== null && ($this->kwh_used + $kwh) > $plan->max_kwh) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if can use for duration
     */
    public function canUseForDuration(int $minutes): bool
    {
        if (!$this->allowsUsage()) {
            return false;
        }

        $plan = $this->subscriptionPlan;
        
        // Check duration limit
        if ($plan->max_duration_minutes !== null && 
            ($this->duration_minutes_used + $minutes) > $plan->max_duration_minutes) {
            return false;
        }
        
        return true;
    }

    /**
     * Increment session count
     */
    public function incrementSessions(int $count = 1): self
    {
        $this->increment('sessions_used', $count);
        return $this->fresh();
    }

    /**
     * Increment kWh used
     */
    public function incrementKwh(float $kwh): self
    {
        $this->increment('kwh_used', $kwh);
        return $this->fresh();
    }

    /**
     * Increment duration used
     */
    public function incrementDuration(int $minutes): self
    {
        $this->increment('duration_minutes_used', $minutes);
        return $this->fresh();
    }

    /**
     * Mark as expired
     */
    public function markAsExpired(): bool
    {
        return $this->update(['status' => 'expired']);
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }

    /**
     * Mark as active
     */
    public function markAsActive(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Get formatted amount paid
     */
    public function getFormattedAmountPaidAttribute(): string
    {
        return MoneyService::format($this->amount_paid, $this->currency ?? 'EUR');
    }

    /**
     * Get days remaining
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->end_date || $this->end_date->isPast()) {
            return 0;
        }
        return (int) now()->diffInDays($this->end_date);
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now())->where('status', 'active');
    }

    /**
     * Scope for expiring soon (within days)
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
