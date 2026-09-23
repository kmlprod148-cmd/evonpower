<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanRate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plan_rates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plan_id',
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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:4',
        'vat_rate_id' => 'integer',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'time_start' => 'string',
        'time_end' => 'string',
        'days' => 'array',
    ];

    /**
     * Get the plan that owns the rate.
     */
    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class, 'plan_id');
    }

    /**
     * Get the VAT rate associated with the plan rate.
     */
    public function vatRate()
    {
        return $this->belongsTo(VatRate::class);
    }
}