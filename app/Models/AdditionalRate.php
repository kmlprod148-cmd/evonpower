<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalRate extends Model
{
    use HasFactory;

    const TYPE_FIXED = 'fixed';

    const TYPE_TIME = 'time';

    const TYPE_ENERGY = 'energy';

    const TYPE_PERCENTAGE = 'percentage';

    const CONDITION_ALL = 'all';

    const CONDITION_TIME = 'time';

    const CONDITION_DAY = 'day';

    const CONDITION_DURATION = 'duration';

    const CONDITION_POWER = 'power';

    const CONDITION_CUSTOM = 'custom';

    const CONDITION_WEEKEND = 'weekend';

    const CONDITION_NIGHT = 'night';

    const CONDITION_HOLIDAY = 'holiday';

    const CONDITION_CUSTOMER_SEGMENT = 'customer_segment';

    const CONDITION_LOCATION = 'location';

    const CONDITION_QUANTITY = 'quantity';

    const APPLY_ADD = 'add';

    const APPLY_MULTIPLY = 'multiply';

    const APPLY_REPLACE = 'replace';

    protected $fillable = [
        'pricing_plan_id',
        'name',
        'rate_type',
        'price',
        'vat_rate_id',
        'condition_type',
        'time_start',
        'time_end',
        'days',
        'is_active',
        'priority',
        'description',
        'custom_condition',
        'min_duration',
        'max_duration',
        'min_power',
        'max_power',
        'customer_segment',
        'location_zone',
        'quantity_min',
        'quantity_max',
        'is_percentage',
        'percentage_value',
        'apply_type',
        'applicable_dates',
        'excluded_dates',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'vat_rate_id' => 'integer',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'time_start' => 'string',
        'time_end' => 'string',
        'days' => 'array',
        'min_duration' => 'integer',
        'max_duration' => 'integer',
        'min_power' => 'decimal:2',
        'max_power' => 'decimal:2',
        'is_percentage' => 'boolean',
        'percentage_value' => 'decimal:2',
        'applicable_dates' => 'array',
        'excluded_dates' => 'array',
    ];

    // Relationships
    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class);
    }

    public function vatRate()
    {
        return $this->belongsTo(VatRate::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByConditionType($query, $type)
    {
        return $query->where('condition_type', $type);
    }

    public function scopeWeekend($query)
    {
        return $query->where('condition_type', self::CONDITION_WEEKEND);
    }

    public function scopeNight($query)
    {
        return $query->where('condition_type', self::CONDITION_NIGHT);
    }

    public function scopePriorityOrdered($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Scope to filter rates applicable at a specific datetime
     */
    public function scopeApplicableAt($query, \Carbon\Carbon $datetime)
    {
        // Filter by day of week
        $dayOfWeek = $datetime->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday
        $isoDay = $dayOfWeek === 0 ? 7 : $dayOfWeek;
        $query->where(function ($q) use ($isoDay) {
            $q->whereNull('days')
                ->orWhereJsonContains('days', $isoDay);
        });

        // Filter by time range if specified
        $time = $datetime->format('H:i:s');
        $query->where(function ($q) use ($time) {
            $q->where(function ($sub) use ($time) {
                // Cases where time_start is null OR time condition matches
                $sub->whereNull('time_start')
                    ->orWhereNull('time_end')
                    ->orWhere(function ($t) use ($time) {
                        // Simple case: start <= end
                        $t->whereRaw('TIME(?) >= time_start', [$time])
                            ->whereRaw('TIME(?) <= time_end', [$time]);
                    })
                    ->orWhere(function ($t) use ($time) {
                        // Overnight case: start > end (e.g., 22:00-06:00)
                        // time >= start OR time <= end
                        $t->whereRaw('TIME(?) >= time_start', [$time])
                            ->orWhereRaw('TIME(?) <= time_end', [$time]);
                    });
            });
        });

        return $query;
    }

    /**
     * Check if this rate is applicable at the given datetime
     */
    public function isApplicableAt(\Carbon\Carbon $datetime): bool
    {
        // Check date-based conditions
        if ($this->applicable_dates) {
            $dateStr = $datetime->format('Y-m-d');
            if (! in_array($dateStr, $this->applicable_dates)) {
                return false;
            }
        }

        if ($this->excluded_dates) {
            $dateStr = $datetime->format('Y-m-d');
            if (in_array($dateStr, $this->excluded_dates)) {
                return false;
            }
        }

        // Check day of week
        if ($this->days && is_array($this->days) && count($this->days) > 0) {
            $dayOfWeek = $datetime->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday
            // Convert to 1-7 (Monday=1, Sunday=7)
            $isoDay = $dayOfWeek === 0 ? 7 : $dayOfWeek;
            if (! in_array($isoDay, $this->days)) {
                return false;
            }
        }

        // Check time range
        if ($this->time_start || $this->time_end) {
            $time = $datetime->format('H:i:s');
            if ($this->time_start && $time < $this->time_start) {
                return false;
            }
            if ($this->time_end && $time > $this->time_end) {
                // Handle overnight ranges (e.g., 22:00 - 06:00)
                if ($this->time_end < $this->time_start) {
                    // Overnight: applies if time >= start OR time <= end
                    if ($time < $this->time_start && $time > $this->time_end) {
                        return false;
                    }
                } else {
                    if ($time > $this->time_end) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check if rate applies based on power (in kW)
     */
    public function isApplicableToPower(float $power): bool
    {
        if ($this->min_power !== null && $power < $this->min_power) {
            return false;
        }
        if ($this->max_power !== null && $power > $this->max_power) {
            return false;
        }

        return true;
    }

    /**
     * Check if rate applies based on duration (in minutes)
     */
    public function isApplicableToDuration(int $duration): bool
    {
        if ($this->min_duration !== null && $duration < $this->min_duration) {
            return false;
        }
        if ($this->max_duration !== null && $duration > $this->max_duration) {
            return false;
        }

        return true;
    }

    /**
     * Check if rate applies based on customer segment
     */
    public function isApplicableToCustomerSegment(?string $segment): bool
    {
        if (! $this->customer_segment) {
            return true; // No segment restriction
        }
        if (! $segment) {
            return false;
        }

        return $this->customer_segment === $segment;
    }

    /**
     * Check if rate applies based on location zone
     */
    public function isApplicableToLocation(?string $zone): bool
    {
        if (! $this->location_zone) {
            return true;
        }
        if (! $zone) {
            return false;
        }

        return $this->location_zone === $zone;
    }

    /**
     * Check if rate applies based on quantity
     */
    public function isApplicableToQuantity(int $quantity): bool
    {
        if ($this->quantity_min !== null && $quantity < $this->quantity_min) {
            return false;
        }
        if ($this->quantity_max !== null && $quantity > $this->quantity_max) {
            return false;
        }

        return true;
    }

    /**
     * Apply this rate to a base price
     *
     * @param  float  $basePrice  The original price
     * @param  array  $context  Additional context (power, duration, customer_segment, etc.)
     * @return float The adjusted price
     */
    public function apply(float $basePrice, array $context = []): float
    {
        $adjustedPrice = $basePrice;

        // Check all conditions
        if (isset($context['datetime']) && ! $this->isApplicableAt($context['datetime'])) {
            return $basePrice;
        }
        if (isset($context['power']) && ! $this->isApplicableToPower($context['power'])) {
            return $basePrice;
        }
        if (isset($context['duration']) && ! $this->isApplicableToDuration($context['duration'])) {
            return $basePrice;
        }
        if (isset($context['customer_segment']) && ! $this->isApplicableToCustomerSegment($context['customer_segment'])) {
            return $basePrice;
        }
        if (isset($context['location_zone']) && ! $this->isApplicableToLocation($context['location_zone'])) {
            return $basePrice;
        }
        if (isset($context['quantity']) && ! $this->isApplicableToQuantity($context['quantity'])) {
            return $basePrice;
        }

        // Apply based on rate_type and apply_type
        if ($this->is_percentage && $this->percentage_value !== null) {
            $adjustment = $basePrice * ($this->percentage_value / 100);
        } else {
            $adjustment = (float) $this->price;
        }

        switch ($this->apply_type) {
            case self::APPLY_MULTIPLY:
                if ($this->is_percentage) {
                    // For percentage, multiply means add/subtract percentage
                    $adjustedPrice = $basePrice * (1 + ($adjustment / 100));
                } else {
                    // For fixed amount, multiply means multiply
                    $adjustedPrice = $basePrice * $adjustment;
                }
                break;

            case self::APPLY_REPLACE:
                $adjustedPrice = $adjustment;
                break;

            case self::APPLY_ADD:
            default:
                $adjustedPrice = $basePrice + $adjustment;
                break;
        }

        return round($adjustedPrice, 4);
    }

    /**
     * Get the rate value considering its type
     */
    public function getEffectiveValue(?float $baseValue = null): float
    {
        if ($this->is_percentage && $this->percentage_value !== null) {
            return $this->percentage_value / 100; // Return as decimal multiplier
        }

        return (float) $this->price;
    }

    /**
     * Get display label for the rate
     */
    public function getDisplayLabel(): string
    {
        $label = $this->name;

        if ($this->is_percentage) {
            $value = $this->percentage_value ?? $this->price;
            $label .= " ({$value}%)";
        } else {
            $currency = $this->pricingPlan->currency ?? 'EUR';
            $label .= ' ('.number_format($this->price, 2)." {$currency})";
        }

        // Add conditions
        $conditions = [];
        if ($this->days && count($this->days)) {
            $dayNames = [];
            foreach ($this->days as $day) {
                $dayNames[] = $this->getDayName($day);
            }
            $conditions[] = implode(', ', $dayNames);
        }
        if ($this->time_start) {
            $conditions[] = $this->time_start.'-'.($this->time_end ?? '23:59');
        }
        if ($this->min_duration || $this->max_duration) {
            $dur = [];
            if ($this->min_duration) {
                $dur[] = "≥{$this->min_duration}min";
            }
            if ($this->max_duration) {
                $dur[] = "≤{$this->max_duration}min";
            }
            $conditions[] = implode(' ', $dur);
        }
        if ($this->customer_segment) {
            $conditions[] = ucfirst($this->customer_segment);
        }

        if ($conditions) {
            $label .= ' - '.implode(' | ', $conditions);
        }

        return $label;
    }

    /**
     * Get day name from ISO day number (1-7)
     */
    private function getDayName(int $dayNumber): string
    {
        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        return $days[$dayNumber] ?? "Jour $dayNumber";
    }

    /**
     * Check if this rate represents weekend pricing
     */
    public function isWeekendRate(): bool
    {
        return $this->condition_type === self::CONDITION_WEEKEND ||
               ($this->days && in_array(6, $this->days) && in_array(7, $this->days));
    }

    /**
     * Check if this rate represents night pricing
     */
    public function isNightRate(): bool
    {
        return $this->condition_type === self::CONDITION_NIGHT ||
               ($this->time_start && $this->time_end &&
                $this->time_start >= '20:00' || $this->time_start <= '06:00');
    }

    /**
     * Validate that required fields are present for the condition type
     */
    public function isValidConfiguration(): bool
    {
        switch ($this->condition_type) {
            case self::CONDITION_TIME:
            case self::CONDITION_NIGHT:
                return ! empty($this->time_start);

            case self::CONDITION_DAY:
            case self::CONDITION_WEEKEND:
                return ! empty($this->days) && is_array($this->days);

            case self::CONDITION_DURATION:
                return $this->min_duration !== null || $this->max_duration !== null;

            case self::CONDITION_POWER:
                return $this->min_power !== null || $this->max_power !== null;

            case self::CONDITION_CUSTOMER_SEGMENT:
                return ! empty($this->customer_segment);

            case self::CONDITION_LOCATION:
                return ! empty($this->location_zone);

            case self::CONDITION_QUANTITY:
                return $this->quantity_min !== null || $this->quantity_max !== null;

            case self::CONDITION_HOLIDAY:
                return ! empty($this->applicable_dates);

            default:
                return true; // 'all' condition type requires no specific fields
        }
    }
}
