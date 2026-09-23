<?php

namespace App\Models;

use App\Enums\SubscriptionPlanType;
use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'price',
        'vat_rate',
        'vat_rate_id',
        'duration_months',
        'max_sessions',
        'max_kwh',
        'max_duration_minutes',
        'max_charging_points',
        'terms_conditions',
        'features',
        'allow_renewal',
        'allow_upgrade',
        'allow_downgrade',
        'cancellation_fee',
        'min_contract_months',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'duration_months' => 'integer',
        'max_sessions' => 'integer',
        'max_kwh' => 'decimal:2',
        'max_duration_minutes' => 'integer',
        'max_charging_points' => 'integer',
        'allow_renewal' => 'boolean',
        'allow_upgrade' => 'boolean',
        'allow_downgrade' => 'boolean',
        'cancellation_fee' => 'decimal:2',
        'min_contract_months' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'features' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the VAT rate associated with the plan
     */
    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * Get the groups that can access this plan
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'subscription_plan_groups')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    /**
     * Get the charging points that can access this plan
     */
    public function chargingPoints(): BelongsToMany
    {
        return $this->belongsToMany(ChargingPoint::class, 'subscription_plan_charging_points')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    /**
     * Get the stations that can access this plan
     */
    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'subscription_plan_stations')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    /**
     * Get user subscriptions for this plan
     */
    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Get active user subscriptions count
     */
    public function getActiveSubscriptionsCount(): int
    {
        return $this->userSubscriptions()
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get the plan type enum
     */
    public function getPlanTypeAttribute(): SubscriptionPlanType
    {
        return SubscriptionPlanType::from($this->type);
    }

    /**
     * Get formatted price with VAT
     */
    public function getFormattedPriceWithVatAttribute(): string
    {
        $totalPrice = $this->price * (1 + ($this->vat_rate / 100));
        return MoneyService::format($totalPrice, $this->currency ?? 'EUR');
    }

    /**
     * Get formatted VAT amount
     */
    public function getFormattedVatAmountAttribute(): string
    {
        $vatAmount = $this->price * ($this->vat_rate / 100);
        return MoneyService::format($vatAmount, $this->currency ?? 'EUR');
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return MoneyService::format($this->price, $this->currency ?? 'EUR');
    }

    /**
     * Check if plan is quota-based
     */
    public function isQuotaBased(): bool
    {
        return $this->planType->isQuotaBased();
    }

    /**
     * Check if plan is cyclical (time-based)
     */
    public function isCyclical(): bool
    {
        return $this->planType->isCyclical();
    }

    /**
     * Get quota type for this plan
     */
    public function getQuotaType(): ?string
    {
        return $this->planType->getQuotaType();
    }

    /**
     * Get duration in months
     */
    public function getDurationInMonths(): int
    {
        if ($this->duration_months > 0) {
            return $this->duration_months;
        }
        return $this->planType->getDurationMonths();
    }

    /**
     * Check if user can access this plan through their group
     */
    public function isAccessibleByGroup(Group $group): bool
    {
        return $this->groups()
            ->where('groups.id', $group->id)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Check if charging point is accessible with this plan
     */
    public function isAccessibleAtChargingPoint(ChargingPoint $chargingPoint): bool
    {
        // If no specific charging points assigned, plan is accessible everywhere
        if ($this->chargingPoints()->count() === 0) {
            // Check if accessible through station
            if ($chargingPoint->station) {
                return $this->isAccessibleAtStation($chargingPoint->station);
            }
            return true;
        }

        return $this->chargingPoints()
            ->where('charging_points.id', $chargingPoint->id)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Check if station is accessible with this plan
     */
    public function isAccessibleAtStation(Station $station): bool
    {
        // If no specific stations assigned, plan is accessible everywhere
        if ($this->stations()->count() === 0) {
            return true;
        }

        return $this->stations()
            ->where('stations.id', $station->id)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured plans
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for quota-based plans
     */
    public function scopeQuotaBased($query)
    {
        return $query->whereIn('type', [
            'per_charge',
            'per_kwh',
            'per_time',
            'per_session',
        ]);
    }

    /**
     * Scope for time-based plans
     */
    public function scopeTimeBased($query)
    {
        return $query->whereIn('type', [
            'monthly',
            'quarterly',
            'semi_annual',
            'annual',
        ]);
    }

    /**
     * Scope ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Get the subscription type label
     */
    public function getTypeLabelAttribute(): string
    {
        try {
            return SubscriptionPlanType::from($this->type)->getLabel();
        } catch (\ValueError $e) {
            return $this->type;
        }
    }
}
