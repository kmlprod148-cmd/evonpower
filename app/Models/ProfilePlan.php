<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfilePlan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'integrator_id',
        'owner_type', // 'admin', 'integrator'
        'profile_type', // 'integrator', 'partner'
        'settings',
        'features',
        'limitations',
        'pricing_plan_id', // Lien vers le plan tarifaire associé
        'is_active'
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array
     */
    protected $casts = [
        'settings' => 'json',
        'features' => 'json',
        'limitations' => 'json',
        'is_active' => 'boolean',
    ];

    /**
     * Obtenir l'intégrateur associé à ce profil.
     */
    public function integrator()
    {
        return $this->belongsTo(Integrator::class);
    }

    /**
     * Obtenir le plan tarifaire associé à ce profil.
     */
    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class);
    }

    /**
     * Obtenir les intégrateurs qui utilisent ce profil.
     */
    public function integrators()
    {
        return $this->hasMany(Integrator::class, 'profile_plan_id');
    }

    /**
     * Obtenir les partenaires qui utilisent ce profil.
     */
    public function partners()
    {
        return $this->hasMany(Partner::class, 'profile_plan_id');
    }

    /**
     * Déterminer si le profil est applicable à un intégrateur spécifique.
     * 
     * @param int $integratorId
     * @return bool
     */
    public function isApplicableToIntegrator($integratorId)
    {
        // Si c'est un profil créé par l'admin, il est applicable à tous les intégrateurs
        if ($this->owner_type === 'admin') {
            return true;
        }
        
        // Si c'est un profil créé par un intégrateur, il n'est applicable qu'à cet intégrateur
        return $this->integrator_id === $integratorId;
    }

    /**
     * Déterminer si le profil est applicable à un partenaire spécifique.
     * 
     * @param int $partnerId
     * @return bool
     */
    public function isApplicableToPartner($partnerId)
    {
        // Si c'est un profil créé par l'admin, il est applicable à tous les partenaires
        if ($this->owner_type === 'admin') {
            return true;
        }
        
        // Si c'est un profil créé par un intégrateur, vérifier si le partenaire appartient à cet intégrateur
        $partner = Partner::find($partnerId);
        return $partner && $partner->integrator_id === $this->integrator_id;
    }
}