<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TariffPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price_per_minute',
        'price_per_kwh',
        'base_rate',
        'activation_fee',
        'max_minutes_per_reservation',
        'priority',
        'is_active',
        'currency',
        'vat_rate_id',
    ];

    protected $casts = [
        'price_per_minute' => 'decimal:4',
        'price_per_kwh' => 'decimal:4',
        'base_rate' => 'decimal:4',
        'activation_fee' => 'decimal:2',
        'max_minutes_per_reservation' => 'integer',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function vatRate()
    {
        return $this->belongsTo(VatRate::class);
    }

    public function chargingPoints()
    {
        return $this->hasMany(ChargingPoint::class, 'tariff_plan_id');
    }
}