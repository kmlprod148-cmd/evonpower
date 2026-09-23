<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessProfileApplication extends Model
{
    use HasFactory;

    protected $table = 'business_profile_applications';

    protected $fillable = [
        'business_profile_id',
        'operator_id',
        'integrator_id',
        'status',
        'applied_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    /**
     * Get the business profile associated with the application.
     */
    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the operator (user) associated with the application.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Get the integrator (user) associated with the application.
     */
    public function integrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'integrator_id');
    }
}