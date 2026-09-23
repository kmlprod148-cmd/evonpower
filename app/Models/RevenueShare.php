<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueShare extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_id',
        'business_profile_id',
        'integrator_id',
        'partner_id',
        'amount',
        'type',
        'commission_plan_id',
    ];

    /**
     * Get the transaction that owns the RevenueShare.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the business profile that owns the RevenueShare.
     */
    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the integrator that owns the RevenueShare.
     */
    public function integrator(): BelongsTo
    {
        return $this->belongsTo(Integrator::class);
    }

    /**
     * Get the partner that owns the RevenueShare.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the commission plan that owns the RevenueShare.
     */
    public function commissionPlan(): BelongsTo
    {
        return $this->belongsTo(CommissionPlan::class);
    }
}