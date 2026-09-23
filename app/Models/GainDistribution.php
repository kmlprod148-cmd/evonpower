<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GainDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'total_amount',
        'admin_fees_total',
        'revenue_after_admin',
        'integrator_commission_amount',
        'integrator_commission_percent',
        'integrator_commission_fixed',
        'partner_commission_amount',
        'partner_commission_percent',
        'partner_commission_fixed',
        'operator_revenue',
        'currency',
        'details',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'admin_fees_total' => 'decimal:2',
        'revenue_after_admin' => 'decimal:2',
        'integrator_commission_amount' => 'decimal:2',
        'integrator_commission_percent' => 'decimal:2',
        'integrator_commission_fixed' => 'decimal:2',
        'partner_commission_amount' => 'decimal:2',
        'partner_commission_percent' => 'decimal:2',
        'partner_commission_fixed' => 'decimal:2',
        'operator_revenue' => 'decimal:2',
        'details' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}


