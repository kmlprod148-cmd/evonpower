<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'charging_point_id',
        'amount',
        'status',
        'details',
        'payment_method',
        'payment_reference',
        'payment_status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(PricingPlan::class, 'plan_id');
    }

    public function chargingPoint()
    {
        return $this->belongsTo(ChargingPoint::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
