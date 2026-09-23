<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'integrator_id',
        'commission_rate',
        'max_operators',
        'auto_approve_operators',
        'notification_email',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:4',
        'auto_approve_operators' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Relation avec l'intégrateur
     */
    public function integrator()
    {
        return $this->belongsTo(Integrator::class);
    }

    /**
     * Scope pour les configurations actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour un intégrateur spécifique
     */
    public function scopeForIntegrator($query, $integratorId)
    {
        return $query->where('integrator_id', $integratorId);
    }

    /**
     * Accessor pour le taux de commission en pourcentage
     */
    public function getCommissionRatePercentageAttribute()
    {
        return $this->commission_rate * 100;
    }

    /**
     * Mutator pour le taux de commission depuis un pourcentage
     */
    public function setCommissionRateFromPercentage($percentage)
    {
        $this->commission_rate = $percentage / 100;
    }
}
