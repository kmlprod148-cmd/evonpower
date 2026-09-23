<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChargingStation extends Model
{
    use HasFactory;

    protected $table = 'charging_points';

    protected $fillable = [
        'operator_id',
        'pricing_plan_id',
        'location',
    ];

    public function pricingPlan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class, 'operator_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'charging_point_id');
    }
}
