<?php

namespace App\Models;

use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasCreator, HasFactory;

    protected $table = 'pricing_plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'rate_type',
        'base_rate',
        'price_per_kwh',
        'price_per_minute',
        'activation_fee',
        'fixed_price',
        'weekend_price',
        'night_price',
        'has_weekend_pricing',
        'has_night_pricing',
        'night_start_time',
        'night_end_time',
        'vat_rate_id',
        'priority',
        'max_duration',
        'is_active',
        'currency',
        'billing_interval',
        'valid_from',
        'valid_until',
        'mobile_theme_color',
        'created_by',
        'created_by_role',
        'created_by_type',
        'created_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'base_rate' => 'decimal:4',
        'price_per_kwh' => 'decimal:4',
        'price_per_minute' => 'decimal:4',
        'activation_fee' => 'decimal:2',
        'fixed_price' => 'decimal:2',
        'weekend_price' => 'decimal:4',
        'night_price' => 'decimal:4',
        'has_weekend_pricing' => 'boolean',
        'has_night_pricing' => 'boolean',
        'night_start_time' => 'datetime:H:i:s',
        'night_end_time' => 'datetime:H:i:s',
        'priority' => 'integer',
        'max_duration' => 'integer',
        'is_active' => 'boolean',
        'billing_interval' => 'string',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    /**
     * Get the VAT rate for this plan
     */
    public function vatRate()
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * Get additional rates for this plan
     */
    public function additionalRates()
    {
        return $this->hasMany(AdditionalRate::class, 'pricing_plan_id');
    }

    /**
     * Get complex pricing rule conditions for this plan.
     */
    public function ruleConditions()
    {
        return $this->hasMany(PricingRuleCondition::class);
    }

    /**
     * Get charging points using this plan
     */
    public function chargingPoints()
    {
        return $this->hasMany(ChargingPoint::class, 'pricing_plan_id');
    }

    /**
     * Get the users subscribed to this plan.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'pricing_plan_user');
    }

    /**
     * Get the business profile that owns the plan.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the business profiles associated with the plan.
     */
    public function businessProfiles()
    {
        return $this->belongsToMany(BusinessProfile::class, 'business_profile_pricing_plan', 'pricing_plan_id', 'business_profile_id')
            ->withTimestamps();
    }

    /**
     * Get the groups associated with the plan.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_plan', 'plan_id', 'group_id');
    }

    /**
     * Get the partners associated with the plan.
     */
    public function partners()
    {
        return $this->belongsToMany(Partner::class, 'partner_pricing_plan')
            ->withTimestamps();
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: filter plans visible to the given user based on their role.
     * Partners see plans they created OR linked to them (pivot or groups).
     */
    public function scopeForUser(Builder $query, User $user): Builder
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

        return $query->where('created_by', $user->id);
    }

    /**
     * Get the effective price based on rate type
     */
    public function getEffectivePriceAttribute()
    {
        switch ($this->rate_type) {
            case 'energy':
            case 'kwh':
                return $this->price_per_kwh;
            case 'time':
            case 'minute':
                return $this->price_per_minute;
            case 'fixed':
            default:
                return $this->fixed_price ?? $this->base_rate;
        }
    }

    /**
     * Get formatted price string
     */
    public function getFormattedPriceAttribute()
    {
        $currency = $this->currency ?? 'EUR';
        $price = $this->getEffectivePriceAttribute();

        switch ($this->rate_type) {
            case 'energy':
            case 'kwh':
                return number_format($price, 2)." {$currency}/kWh";
            case 'time':
            case 'minute':
                return number_format($price, 2)." {$currency}/min";
            case 'fixed':
            default:
                return number_format($price, 2)." {$currency}";
        }
    }

    /**
     * Human readable rate type label.
     */
    public function getTypeNameAttribute()
    {
        return match ($this->rate_type) {
            'time', 'minute' => 'A la minute',
            'energy', 'kwh' => 'Par kWh',
            'fixed' => 'Tarif fixe',
            default => 'Non defini',
        };
    }

    /**
     * Calculate price for given parameters with additional rates.
     * Delegates to PricingCalculationService for consistency.
     *
     * @param  float  $energyKwh  Energy consumed in kWh
     * @param  int  $durationMinutes  Duration in minutes
     * @param  \Carbon\Carbon|null  $datetime  DateTime of charging session
     * @return array Calculation result with base_price, additional_rates, additional_price, vat_amount, total_price, currency.
     */
    public function calculatePrice($energyKwh = 0, $durationMinutes = 0, $datetime = null)
    {
        $datetime = $datetime ?? now();

        $sessionData = [
            'duration_minutes' => $durationMinutes,
            'energy_kwh' => $energyKwh,
            'start_time' => $datetime,
        ];

        $result = app(\App\Services\PricingCalculationService::class)->calculatePrice($this, $sessionData);

        // Map new result structure to legacy format for backward compatibility
        $additionalRates = $result['price_breakdown']['additional_rates'] ?? [];

        // Extract weekend and night amounts if they exist
        $weekendAmount = 0;
        $nightAmount = 0;
        foreach ($additionalRates as $rate) {
            if (($rate['name'] ?? '') === 'Weekend Rate') {
                $weekendAmount = $rate['adjustment'] ?? 0;
            } elseif (($rate['name'] ?? '') === 'Night Rate') {
                $nightAmount = $rate['adjustment'] ?? 0;
            }
        }

        return [
            'base_price' => $result['base_cost'] ?? 0,
            'additional_rates' => [
                'weekend' => $weekendAmount,
                'night' => $nightAmount,
                'total' => $weekendAmount + $nightAmount,
            ],
            'additional_price' => ($result['subtotal'] ?? 0) - ($result['base_cost'] ?? 0),
            'vat_amount' => $result['vat'] ?? 0,
            'total_price' => $result['total'] ?? 0,
            'currency' => $result['currency'] ?? $this->currency ?? 'EUR',
        ];
    }

    /**
     * Calculate additional rates (weekend and night)
     */
    public function calculateAdditionalRates($energyKwh = 0, $durationMinutes = 0, $datetime = null)
    {
        $datetime = $datetime ?? now();
        $additionalRates = [
            'weekend' => 0,
            'night' => 0,
            'total' => 0,
        ];

        // Weekend pricing
        if ($this->has_weekend_pricing && $this->isWeekend($datetime)) {
            switch ($this->rate_type) {
                case 'energy':
                case 'kwh':
                    $additionalRates['weekend'] = $this->weekend_price * $energyKwh;
                    break;
                case 'time':
                case 'minute':
                    $additionalRates['weekend'] = $this->weekend_price * $durationMinutes;
                    break;
                case 'fixed':
                    $additionalRates['weekend'] = $this->weekend_price;
                    break;
            }
        }

        // Night pricing
        if ($this->has_night_pricing && $this->isNightTime($datetime)) {
            switch ($this->rate_type) {
                case 'energy':
                case 'kwh':
                    $additionalRates['night'] = $this->night_price * $energyKwh;
                    break;
                case 'time':
                case 'minute':
                    $additionalRates['night'] = $this->night_price * $durationMinutes;
                    break;
                case 'fixed':
                    $additionalRates['night'] = $this->night_price;
                    break;
            }
        }

        $additionalRates['total'] = $additionalRates['weekend'] + $additionalRates['night'];

        return $additionalRates;
    }

    /**
     * Check if the given datetime is during weekend
     */
    public function isWeekend($datetime)
    {
        $dayOfWeek = $datetime->dayOfWeek;

        return $dayOfWeek == 0 || $dayOfWeek == 6; // Sunday = 0, Saturday = 6
    }

    /**
     * Check if the given datetime is during night time
     */
    public function isNightTime($datetime)
    {
        $currentTime = $datetime->format('H:i:s');
        $nightStart = $this->night_start_time ?? '22:00:00';
        $nightEnd = $this->night_end_time ?? '06:00:00';

        // Handle overnight period (e.g., 22:00 to 06:00)
        if ($nightStart > $nightEnd) {
            return $currentTime >= $nightStart || $currentTime <= $nightEnd;
        } else {
            return $currentTime >= $nightStart && $currentTime <= $nightEnd;
        }
    }
}
