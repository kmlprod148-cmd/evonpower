<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_subscription_id',
        'transaction_id',
        'reservation_id',
        'charging_session_id',
        'usage_type',
        'sessions_consumed',
        'kwh_consumed',
        'duration_minutes_consumed',
        'sessions_before',
        'sessions_after',
        'kwh_before',
        'kwh_after',
        'duration_before',
        'duration_after',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'sessions_consumed' => 'integer',
        'kwh_consumed' => 'decimal:2',
        'duration_minutes_consumed' => 'integer',
        'sessions_before' => 'integer',
        'sessions_after' => 'integer',
        'kwh_before' => 'decimal:2',
        'kwh_after' => 'decimal:2',
        'duration_before' => 'integer',
        'duration_after' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the user subscription
     */
    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
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
     * Get the related charging session
     */
    public function chargingSession(): BelongsTo
    {
        return $this->belongsTo(ChargingSession::class, 'charging_session_id');
    }

    /**
     * Get the subscription plan
     */
    public function getSubscriptionPlan()
    {
        return $this->userSubscription?->subscriptionPlan;
    }

    /**
     * Get formatted usage summary
     */
    public function getUsageSummaryAttribute(): string
    {
        $parts = [];
        
        if ($this->sessions_consumed > 0) {
            $parts[] = "{$this->sessions_consumed} session(s)";
        }
        
        if ($this->kwh_consumed > 0) {
            $parts[] = number_format($this->kwh_consumed, 2) . ' kWh';
        }
        
        if ($this->duration_minutes_consumed > 0) {
            $hours = floor($this->duration_minutes_consumed / 60);
            $minutes = $this->duration_minutes_consumed % 60;
            $parts[] = $hours > 0 ? "{$hours}h {$minutes}min" : "{$minutes} min";
        }
        
        return implode(', ', $parts) ?: 'Aucune consommation';
    }

    /**
     * Scope for session usage
     */
    public function scopeSessionUsage($query)
    {
        return $query->where('usage_type', 'session');
    }

    /**
     * Scope for kWh usage
     */
    public function scopeKwhUsage($query)
    {
        return $query->where('usage_type', 'kwh');
    }

    /**
     * Scope for duration usage
     */
    public function scopeDurationUsage($query)
    {
        return $query->where('usage_type', 'duration');
    }

    /**
     * Scope for a specific subscription
     */
    public function scopeForSubscription($query, int $subscriptionId)
    {
        return $query->where('user_subscription_id', $subscriptionId);
    }

    /**
     * Scope for a specific transaction
     */
    public function scopeForTransaction($query, int $transactionId)
    {
        return $query->where('transaction_id', $transactionId);
    }
}
