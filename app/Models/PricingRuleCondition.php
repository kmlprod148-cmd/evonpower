<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRuleCondition extends Model
{
    use HasFactory;

    const CONDITION_AND = 'and';

    const CONDITION_OR = 'or';

    const CONDITION_SINGLE = 'single';

    const APPLY_ADD = 'add';

    const APPLY_MULTIPLY = 'multiply';

    const APPLY_REPLACE = 'replace';

    protected $table = 'pricing_rule_conditions';

    protected $fillable = [
        'pricing_plan_id',
        'name',
        'condition_type', // 'and', 'or', 'single'
        'conditions', // JSON array of condition objects
        'rate_type', // 'fixed', 'time', 'energy', 'percentage'
        'price_value',
        'is_percentage',
        'apply_type', // 'add', 'multiply', 'replace'
        'priority',
        'is_active',
        'description',
        'metadata',
    ];

    protected $casts = [
        'conditions' => 'array',
        'price_value' => 'decimal:4',
        'is_percentage' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the pricing plan that owns this rule condition.
     */
    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class);
    }

    /**
     * Scope to get active rules ordered by priority
     */
    public function scopeActiveOrdered($query)
    {
        return $query->where('is_active', true)->orderBy('priority', 'desc');
    }

    /**
     * Check if this rule matches the given context
     *
     * @param  array  $context  Contains keys like: datetime, duration, power, customer_segment, location_zone, quantity, service_type, etc.
     */
    public function matches(array $context): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $conditions = $this->conditions ?? [];
        if (empty($conditions)) {
            return true;
        }

        $results = [];
        foreach ($conditions as $condition) {
            $result = $this->evaluateCondition($condition, $context);
            $results[] = $result;
        }

        if ($this->condition_type === self::CONDITION_AND) {
            return ! in_array(false, $results, true);
        } elseif ($this->condition_type === self::CONDITION_OR) {
            return in_array(true, $results, true);
        }

        // Single condition - return first result
        return $results[0] ?? false;
    }

    /**
     * Evaluate a single condition against context
     */
    private function evaluateCondition(array $condition, array $context): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'eq';
        $value = $condition['value'] ?? null;

        if (! $field || $value === null) {
            return false;
        }

        $actualValue = $context[$field] ?? null;
        if ($actualValue === null) {
            return false;
        }

        switch ($operator) {
            case 'eq':
                return $actualValue == $value;
            case 'ne':
                return $actualValue != $value;
            case 'gt':
                return $actualValue > $value;
            case 'gte':
                return $actualValue >= $value;
            case 'lt':
                return $actualValue < $value;
            case 'lte':
                return $actualValue <= $value;
            case 'in':
                return in_array($actualValue, (array) $value);
            case 'not_in':
                return ! in_array($actualValue, (array) $value);
            case 'contains':
                return stripos($actualValue, $value) !== false;
            case 'between':
                return $actualValue >= $value[0] && $actualValue <= $value[1];
            default:
                return false;
        }
    }

    /**
     * Apply this rule to a price
     */
    public function apply(float $basePrice, array $context = []): float
    {
        if (! $this->matches($context)) {
            return $basePrice;
        }

        $adjustedPrice = $basePrice;

        if ($this->is_percentage) {
            $adjustment = $basePrice * ($this->price_value / 100);
        } else {
            $adjustment = (float) $this->price_value;
        }

        switch ($this->apply_type) {
            case self::APPLY_MULTIPLY:
                if ($this->is_percentage) {
                    $adjustedPrice = $basePrice * (1 + ($adjustment / 100));
                } else {
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
     * Get formatted description of this rule
     */
    public function getFormattedDescription(): string
    {
        $desc = $this->description ?? $this->name;

        // Build condition string
        $parts = [];
        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? 'eq';
            $value = $condition['value'] ?? '';

            $fieldLabel = $this->formatFieldLabel($field);
            $operatorLabel = $this->formatOperatorLabel($operator);
            $valueLabel = $this->formatValueLabel($field, $value);

            $parts[] = "$fieldLabel $operatorLabel $valueLabel";
        }

        if ($parts) {
            $desc .= ' - '.implode(' AND ', $parts);
        }

        // Addadjustment
        if ($this->is_percentage) {
            $adjust = ($this->apply_type === self::APPLY_MULTIPLY) ? '+' : '';
            $desc .= " → {$adjust}{$this->price_value}%";
        } else {
            $currency = $this->pricingPlan->currency ?? 'EUR';
            $adjust = ($this->apply_type === self::APPLY_ADD) ? '+' : '';
            $desc .= " → {$adjust}".number_format($this->price_value, 2)." {$currency}";
        }

        return $desc;
    }

    private function formatFieldLabel(string $field): string
    {
        return match ($field) {
            'datetime' => 'Date/heure',
            'duration' => 'Durée',
            'power' => 'Puissance',
            'customer_segment' => 'Segment client',
            'location_zone' => 'Zone',
            'quantity' => 'Quantité',
            'service_type' => 'Type de service',
            'day_of_week' => 'Jour',
            'hour' => 'Heure',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }

    private function formatOperatorLabel(string $operator): string
    {
        return match ($operator) {
            'eq' => '=',
            'ne' => '≠',
            'gt' => '>',
            'gte' => '≥',
            'lt' => '<',
            'lte' => '≤',
            'in' => 'dans',
            'not_in' => 'pas dans',
            'contains' => 'contient',
            'between' => 'entre',
            default => $operator,
        };
    }

    private function formatValueLabel(string $field, $value): string
    {
        if (is_array($value)) {
            return implode(', ', $value);
        }

        return (string) $value;
    }
}
