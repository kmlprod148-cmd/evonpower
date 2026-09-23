<?php

namespace App\Models;

use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingPlan extends Model
{
    use HasCreator, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'rate_type',
        'base_rate',
        'base_price', // alias handled by mutator → maps to base_rate
        'price_per_kwh',
        'price_per_minute',
        'activation_fee',
        'fixed_price',
        'vat_rate_id',
        'priority',
        'max_duration',
        'max_energy',
        'min_charge_duration',
        'min_charging_time',
        'max_charging_time',
        'is_active',
        'is_public',
        'is_default',
        'currency',
        'billing_interval',
        'valid_from',
        'valid_until',
        'mobile_theme_color',
        // Weekend pricing fields
        'weekend_price',
        'has_weekend_pricing',
        // Night pricing fields
        'night_price',
        'has_night_pricing',
        'night_start_time',
        'night_end_time',
        'created_by', 'created_by_type', 'created_by_id', // Champs de traçabilité
    ];

    protected $casts = [
        'rate_type' => 'string',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'base_rate' => 'decimal:4',
        'price_per_kwh' => 'decimal:4',
        'price_per_minute' => 'decimal:4',
        'activation_fee' => 'decimal:2',
        'fixed_price' => 'decimal:2',
        'priority' => 'integer',
        'max_duration' => 'integer',
        'min_charging_time' => 'integer',
        'max_charging_time' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    public function chargingPoints(): HasMany
    {
        return $this->hasMany(ChargingPoint::class);
    }

    /**
     * Tarifs de base du plan (minute / kWh / fixe)
     */
    public function planRates(): HasMany
    {
        return $this->hasMany(PlanRate::class, 'plan_id');
    }

    /**
     * Tarifs supplémentaires (créneaux horaires, pick time, etc.)
     */
    public function additionalRates(): HasMany
    {
        return $this->hasMany(AdditionalRate::class);
    }

    /**
     * Complex pricing rule conditions
     */
    public function ruleConditions(): HasMany
    {
        return $this->hasMany(PricingRuleCondition::class);
    }

    /**
     * Groupes de bornes associés à ce plan.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_plan', 'plan_id', 'group_id');
    }

    /**
     * Partenaires associés à ce plan tarifaire.
     */
    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'partner_pricing_plan')
            ->withTimestamps();
    }

    /**
     * Scope: filter plans visible to the given user based on their role.
     * Admins see all. Partners see plans they created OR that are linked
     * to them (via partner_pricing_plan pivot or via their groups).
     */
    public function scopeForUser(Builder $query, \App\Models\User $user): Builder
    {
        if ($user->hasRole(['admin', 'super_admin'])) {
            return $query;
        }

        if ($user->hasRole('partner')) {
            $partnerId = $user->partner_id
                ?? optional($user->partner)->id
                ?? 0;

            return $query->whereHas('partners', fn (Builder $sq) => $sq->where('partners.id', $partnerId));
        }

        // Operator, integrator, or other: only plans they created
        return $query->where('created_by', $user->id);
    }

    /**
     * Type principal lisible (minute / kWh / fixe).
     */
    public function getMainTypeLabelAttribute(): string
    {
        return match ($this->rate_type) {
            'time', 'minute' => 'À la minute',
            'energy', 'kwh' => 'Par kWh',
            'fixed' => 'Fixe par recharge',
            default => 'Non défini',
        };
    }

    /**
     * Valeur principale selon le type (prix/kWh, prix/min, prix fixe).
     */
    public function getMainValueAttribute(): ?float
    {
        return match ($this->rate_type) {
            'time', 'minute' => $this->price_per_minute ?? null,
            'energy', 'kwh' => $this->price_per_kwh ?? null,
            'fixed' => $this->fixed_price ?? $this->base_rate,
            default => null,
        };
    }

    /**
     * Libellé formaté (ex: "1.50 MAD/kWh").
     */
    public function getFormattedMainValueAttribute(): ?string
    {
        $value = $this->main_value;
        if ($value === null) {
            return null;
        }

        $currency = $this->currency ?? 'EUR';

        return match ($this->rate_type) {
            'time', 'minute' => number_format($value, 2)." {$currency}/min",
            'energy', 'kwh' => number_format($value, 2)." {$currency}/kWh",
            'fixed' => number_format($value, 2)." {$currency}",
            default => number_format($value, 2)." {$currency}",
        };
    }

    /**
     * Accessor: $plan->base_price reads base_rate.
     * Many views and controllers use the "base_price" alias.
     */
    public function getBasePriceAttribute(): ?float
    {
        return isset($this->attributes['base_rate'])
            ? (float) $this->attributes['base_rate']
            : null;
    }

    /**
     * Mutator: $plan->base_price = x writes to base_rate column.
     */
    public function setBasePriceAttribute($value): void
    {
        $this->attributes['base_rate'] = $value;
    }
}
