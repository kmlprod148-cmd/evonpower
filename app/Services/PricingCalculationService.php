<?php

namespace App\Services;

use App\Models\AdditionalRate;
use App\Models\Plan;
use App\Models\PricingPlan;
use App\Models\PricingRuleCondition;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PricingCalculationService
{
    /**
     * Cache key prefix for price calculations
     */
    const CACHE_PREFIX = 'pricing_calc:';

    const CACHE_TTL = 3600; // 1 hour

    /**
     * Calculate the total price for a charging session
     *
     * @param  array  $sessionData
     *                              Required keys: duration_minutes (int), energy_kwh (float), start_time (datetime|Carbon)
     *                              Optional keys: power (float kW), customer_segment (string), location_zone (string), quantity (int)
     */
    public function calculatePrice(PricingPlan|Plan $pricingPlan, array $sessionData): array
    {
        $cacheKey = $this->buildCacheKey($pricingPlan->id, $sessionData);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $startTime = $sessionData['start_time'] instanceof Carbon
                ? $sessionData['start_time']
                : Carbon::parse($sessionData['start_time']);

            $duration = (int) ($sessionData['duration_minutes'] ?? 0);
            $energy = (float) ($sessionData['energy_kwh'] ?? 0);
            $power = $sessionData['power'] ?? null;
            $customerSegment = $sessionData['customer_segment'] ?? null;
            $locationZone = $sessionData['location_zone'] ?? null;
            $quantity = $sessionData['quantity'] ?? 1;

            // Step 1: Calculate base cost
            $baseCost = $this->calculateBaseCost($pricingPlan, $duration, $energy);

            // Step 2: Apply activation fee
            if ($pricingPlan->activation_fee) {
                $baseCost += (float) $pricingPlan->activation_fee;
            }

            // Step 3: Build calculation context
            $context = [
                'datetime' => $startTime,
                'duration' => $duration,
                'energy' => $energy,
                'power' => $power,
                'customer_segment' => $customerSegment,
                'location_zone' => $locationZone,
                'quantity' => $quantity,
                'day_of_week' => $startTime->dayOfWeek,
                'is_weekend' => $startTime->dayOfWeek === 0 || $startTime->dayOfWeek === 6,
            ];

            // Step 4: Apply predefined weekend/night pricing (from plan fields)
            $totalPrice = $baseCost;
            $priceBreakdown = [
                'base_cost' => [
                    'description' => $this->getBaseCostDescription($pricingPlan),
                    'amount' => $baseCost,
                ],
            ];

            // Weekend pricing
            if ($pricingPlan->has_weekend_pricing && $context['is_weekend']) {
                $weekendAmount = $this->calculateWeekendNightSurcharge($pricingPlan, $context, 'weekend');
                if ($weekendAmount > 0) {
                    $totalPrice += $weekendAmount;
                    $priceBreakdown['additional_rates'][] = [
                        'name' => 'Weekend Rate',
                        'description' => $this->getWeekendNightDescription($pricingPlan, 'weekend'),
                        'rate_type' => 'markup',
                        'apply_type' => 'add',
                        'value' => $pricingPlan->weekend_price,
                        'is_percentage' => false,
                        'before' => $totalPrice - $weekendAmount,
                        'after' => $totalPrice,
                        'adjustment' => $weekendAmount,
                    ];
                }
            }

            // Night pricing
            if ($pricingPlan->has_night_pricing && $this->isNightTime($pricingPlan, $context['datetime'])) {
                $nightAmount = $this->calculateWeekendNightSurcharge($pricingPlan, $context, 'night');
                if ($nightAmount > 0) {
                    $totalPrice += $nightAmount;
                    $priceBreakdown['additional_rates'][] = [
                        'name' => 'Night Rate',
                        'description' => $this->getWeekendNightDescription($pricingPlan, 'night'),
                        'rate_type' => 'markup',
                        'apply_type' => 'add',
                        'value' => $pricingPlan->night_price,
                        'is_percentage' => false,
                        'before' => $totalPrice - $nightAmount,
                        'after' => $totalPrice,
                        'adjustment' => $nightAmount,
                    ];
                }
            }

            // Step 5: Get all applicable additional rates (custom) and rule conditions
            $applicableRates = $this->getApplicableAdditionalRates($pricingPlan, $context);
            $applicableRules = $this->getApplicableRuleConditions($pricingPlan, $context);

            // Step 6: Apply additional custom rates (priority order: highest first)
            foreach ($applicableRates as $rate) {
                $before = $totalPrice;
                $totalPrice = $rate->apply($totalPrice, $context);

                if ($totalPrice != $before) {
                    $priceBreakdown['additional_rates'][] = [
                        'name' => $rate->name,
                        'description' => $rate->description ?? $this->getRateDescription($rate),
                        'rate_type' => $rate->rate_type,
                        'apply_type' => $rate->apply_type,
                        'value' => $rate->price,
                        'is_percentage' => $rate->is_percentage,
                        'before' => $before,
                        'after' => $totalPrice,
                        'adjustment' => $totalPrice - $before,
                    ];
                }
            }

            // Step 7: Apply rule conditions (highest priority first)
            foreach ($applicableRules as $rule) {
                $before = $totalPrice;
                $totalPrice = $rule->apply($totalPrice, $context);

                if ($totalPrice != $before) {
                    $priceBreakdown['rule_conditions'][] = [
                        'name' => $rule->name,
                        'description' => $rule->getFormattedDescription(),
                        'apply_type' => $rule->apply_type,
                        'price_value' => $rule->price_value,
                        'is_percentage' => $rule->is_percentage,
                        'before' => $before,
                        'after' => $totalPrice,
                        'adjustment' => $totalPrice - $before,
                    ];
                }
            }

            // Step 8: Calculate VAT
            $vatBreakdown = $this->calculateVatBreakdown($pricingPlan, $totalPrice, $priceBreakdown);
            $totalVat = array_sum(array_column($vatBreakdown, 'amount'));
            $totalWithVat = $totalPrice + $totalVat;

            $result = [
                'success' => true,
                'pricing_plan_id' => $pricingPlan->id,
                'pricing_plan_name' => $pricingPlan->name,
                'currency' => $pricingPlan->currency ?? 'EUR',
                'base_cost' => $baseCost,
                'subtotal' => $totalPrice,
                'vat_rate' => $pricingPlan->vatRate?->rate ?? ($pricingPlan->vat_rate ?? 20),
                'vat' => round($totalVat, 2),
                'total' => round($totalWithVat, 2),
                'price_breakdown' => $priceBreakdown,
                'vat_breakdown' => $vatBreakdown,
                'context' => [
                    'datetime' => $startTime->toIso8601String(),
                    'duration_minutes' => $duration,
                    'energy_kwh' => $energy,
                    'power_kw' => $power,
                    'customer_segment' => $customerSegment,
                    'location_zone' => $locationZone,
                    'quantity' => $quantity,
                    'is_weekend' => $context['is_weekend'],
                    'day_of_week' => $startTime->dayOfWeek,
                ],
                'applied_rates_count' => count($priceBreakdown['additional_rates'] ?? []),
            ];

            // Cache the result
            Cache::put($cacheKey, $result, self::CACHE_TTL);

            return $result;

        } catch (\Exception $e) {
            Log::error('Pricing calculation error', [
                'pricing_plan_id' => $pricingPlan->id,
                'session_data' => $sessionData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Pricing calculation failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Calculate base cost from pricing plan (energy or time based)
     */
    private function calculateBaseCost(PricingPlan|Plan $plan, int $durationMinutes, float $energyKwh): float
    {
        $cost = 0.0;

        switch ($plan->rate_type) {
            case 'energy':
            case 'kwh':
                $cost = $energyKwh * ($plan->price_per_kwh ?? 0);
                break;

            case 'time':
            case 'minute':
                $cost = $durationMinutes * ($plan->price_per_minute ?? 0);
                break;

            case 'fixed':
                $cost = $plan->fixed_price ?? $plan->base_rate ?? 0;
                break;

            default:
                $cost = $plan->base_rate ?? 0;
        }

        return round($cost, 4);
    }

    /**
     * Get description for base cost
     */
    private function getBaseCostDescription(PricingPlan|Plan $plan): string
    {
        return match ($plan->rate_type) {
            'energy', 'kwh' => 'Tarif par kWh ('.number_format($plan->price_per_kwh ?? 0, 2).' '.($plan->currency ?? 'EUR').'/kWh)',
            'time', 'minute' => 'Tarif par minute ('.number_format($plan->price_per_minute ?? 0, 2).' '.($plan->currency ?? 'EUR').'/min)',
            'fixed' => 'Forfait fixe ('.number_format($plan->fixed_price ?? $plan->base_rate ?? 0, 2).' '.($plan->currency ?? 'EUR').')',
            default => 'Tarif de base',
        };
    }

    /**
     * Get human-readable description for a rate
     */
    private function getRateDescription(AdditionalRate $rate): string
    {
        $desc = [];
        if ($rate->days) {
            $dayNames = array_map(fn ($d) => $this->getDayName($d), $rate->days);
            $desc[] = 'Jours: '.implode(', ', $dayNames);
        }
        if ($rate->time_start) {
            $desc[] = "Heures: {$rate->time_start}".($rate->time_end ? "-{$rate->time_end}" : '');
        }
        if ($rate->min_duration) {
            $desc[] = "≥{$rate->min_duration}min";
        }
        if ($rate->max_duration) {
            $desc[] = "≤{$rate->max_duration}min";
        }
        if ($rate->customer_segment) {
            $desc[] = ucfirst($rate->customer_segment);
        }

        return implode(' | ', $desc);
    }

    private function getDayName(int $dayNum): string
    {
        $days = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

        return $days[$dayNum] ?? "Jour $dayNum";
    }

    /**
     * Get all applicable additional rates for the given context
     *
     * @return \Illuminate\Support\Collection|AdditionalRate[]
     */
    private function getApplicableAdditionalRates(PricingPlan|Plan $plan, array $context): \Illuminate\Support\Collection
    {
        $rates = $plan->additionalRates()
            ->active()
            ->with(['vatRate'])
            ->orderBy('priority', 'desc')
            ->orderBy('id')
            ->get();

        return $rates->filter(function (AdditionalRate $rate) use ($context) {
            // Check basic datetime applicability (day, time)
            if (! $rate->isApplicableAt($context['datetime'])) {
                return false;
            }

            // Check other conditions
            if (isset($context['power']) && ! $rate->isApplicableToPower($context['power'])) {
                return false;
            }
            if (isset($context['duration']) && ! $rate->isApplicableToDuration($context['duration'])) {
                return false;
            }
            if (isset($context['customer_segment']) && ! $rate->isApplicableToCustomerSegment($context['customer_segment'])) {
                return false;
            }
            if (isset($context['location_zone']) && ! $rate->isApplicableToLocation($context['location_zone'])) {
                return false;
            }
            if (isset($context['quantity']) && ! $rate->isApplicableToQuantity($context['quantity'])) {
                return false;
            }

            return true;
        })->values();
    }

    /**
     * Get applicable pricing rule conditions
     *
     * @return \Illuminate\Support\Collection|PricingRuleCondition[]
     */
    private function getApplicableRuleConditions(PricingPlan|Plan $plan, array $context): \Illuminate\Support\Collection
    {
        $rules = $plan->ruleConditions()
            ->activeOrdered()
            ->get();

        return $rules->filter(function (PricingRuleCondition $rule) use ($context) {
            return $rule->matches($context);
        })->values();
    }

    /**
     * Build a unique cache key for the calculation
     */
    private function buildCacheKey(int $planId, array $sessionData): string
    {
        $keyParts = [
            'plan' => $planId,
            'dur' => $sessionData['duration_minutes'] ?? 0,
            'ene' => $sessionData['energy_kwh'] ?? 0,
            'start' => $sessionData['start_time'] ?? now()->toIso8601String(),
        ];
        if (isset($sessionData['power'])) {
            $keyParts['pow'] = $sessionData['power'];
        }
        if (isset($sessionData['customer_segment'])) {
            $keyParts['seg'] = $sessionData['customer_segment'];
        }
        if (isset($sessionData['location_zone'])) {
            $keyParts['loc'] = $sessionData['location_zone'];
        }
        if (isset($sessionData['quantity'])) {
            $keyParts['qty'] = $sessionData['quantity'];
        }

        return self::CACHE_PREFIX.md5(implode('_', $keyParts));
    }

    /**
     * Clear cache for a specific pricing plan
     */
    public function clearPlanCache(int $planId): void
    {
        $pattern = self::CACHE_PREFIX."*plan{$planId}*";
        try {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);
            if ($keys) {
                Cache::forgetMultiple($keys);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear pricing cache', ['plan_id' => $planId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Get price estimate for quick preview (cached)
     */
    public function getEstimate(PricingPlan|Plan $plan, string $type, float $value, array $options = []): array
    {
        $sessionData = [
            'duration_minutes' => $type === 'duration' ? (int) $value : 60,
            'energy_kwh' => $type === 'energy' ? $value : 0,
            'start_time' => $options['start_time'] ?? now(),
        ];

        if (isset($options['power'])) {
            $sessionData['power'] = $options['power'];
        }
        if (isset($options['customer_segment'])) {
            $sessionData['customer_segment'] = $options['customer_segment'];
        }
        if (isset($options['location_zone'])) {
            $sessionData['location_zone'] = $options['location_zone'];
        }
        if (isset($options['quantity'])) {
            $sessionData['quantity'] = $options['quantity'];
        }

        return $this->calculatePrice($plan, $sessionData);
    }

    /**
     * Compare pricing plans for a given session
     *
     * @return array List of plans with their prices sorted by total ascending
     */
    public function comparePlans(array $planIds, array $sessionData): array
    {
        $results = [];
        foreach ($planIds as $planId) {
            $plan = PricingPlan::with(['vatRate', 'planRates', 'additionalRates', 'ruleConditions'])->find($planId);
            if (! $plan || ! $plan->is_active) {
                continue;
            }
            $calc = $this->calculatePrice($plan, $sessionData);
            if ($calc['success'] ?? false) {
                $results[] = [
                    'plan' => $plan,
                    'calculation' => $calc,
                ];
            }
        }

        // Sort by total price ascending
        usort($results, fn ($a, $b) => ($a['calculation']['total'] ?? 0) <=> ($b['calculation']['total'] ?? 0));

        return $results;
    }

    /**
     * Calculate weekend or night surcharge amount
     */
    private function calculateWeekendNightSurcharge(PricingPlan|Plan $plan, array $context, string $type): float
    {
        $price = $type === 'weekend' ? $plan->weekend_price : $plan->night_price;
        if ($price <= 0) {
            return 0.0;
        }

        return match ($plan->rate_type) {
            'energy', 'kwh' => $price * $context['energy'],
            'time', 'minute' => $price * $context['duration'],
            'fixed' => $price,
            default => $price * ($context['duration'] ?: 1),
        };
    }

    /**
     * Get description for weekend/night surcharge
     */
    private function getWeekendNightDescription(PricingPlan|Plan $plan, string $type): string
    {
        $price = $type === 'weekend' ? $plan->weekend_price : $plan->night_price;
        $label = $type === 'weekend' ? 'Weekend' : 'Nuit';
        $unit = match ($plan->rate_type) {
            'energy', 'kwh' => '/kWh',
            'time', 'minute' => '/min',
            default => '',
        };

        return "Supplément $label (".number_format($price, 2).' '.($plan->currency ?? 'EUR')."$unit)";
    }

    /**
     * Check if given datetime falls within night hours
     */
    private function isNightTime(PricingPlan|Plan $plan, Carbon $datetime): bool
    {
        $currentTime = $datetime->format('H:i:s');
        $nightStart = $plan->night_start_time ?? '22:00:00';
        $nightEnd = $plan->night_end_time ?? '06:00:00';

        // Handle overnight period (e.g., 22:00 to 06:00)
        if ($nightStart > $nightEnd) {
            return $currentTime >= $nightStart || $currentTime <= $nightEnd;
        } else {
            return $currentTime >= $nightStart && $currentTime <= $nightEnd;
        }
    }

    /**
     * Get rate conditions for debugging/logging
     */
    private function getRateConditions(AdditionalRate $rate): array
    {
        $conditions = [];
        if ($rate->days) {
            $conditions['days'] = $rate->days;
        }
        if ($rate->time_start) {
            $conditions['time_range'] = [$rate->time_start, $rate->time_end];
        }
        if ($rate->min_duration) {
            $conditions['min_duration'] = $rate->min_duration;
        }
        if ($rate->max_duration) {
            $conditions['max_duration'] = $rate->max_duration;
        }
        if ($rate->min_power) {
            $conditions['min_power'] = $rate->min_power;
        }
        if ($rate->max_power) {
            $conditions['max_power'] = $rate->max_power;
        }
        if ($rate->customer_segment) {
            $conditions['customer_segment'] = $rate->customer_segment;
        }
        if ($rate->location_zone) {
            $conditions['location_zone'] = $rate->location_zone;
        }
        if ($rate->quantity_min) {
            $conditions['quantity_min'] = $rate->quantity_min;
        }
        if ($rate->quantity_max) {
            $conditions['quantity_max'] = $rate->quantity_max;
        }

        return $conditions;
    }

    /**
     * Calculate VAT breakdown
     */
    private function calculateVatBreakdown(PricingPlan|Plan $plan, float $subtotal, array &$priceBreakdown): array
    {
        $vatBreakdown = [];
        $defaultVatRate = $plan->vatRate?->rate ?? ($plan->vat_rate ?? 20);
        $vatAmount = $subtotal * ($defaultVatRate / 100);

        return [
            [
                'rate' => $defaultVatRate,
                'rate_name' => $plan->vatRate?->name ?? 'TVA Standard',
                'amount' => round($vatAmount, 2),
                'base_amount' => $subtotal,
            ],
        ];
    }
}
