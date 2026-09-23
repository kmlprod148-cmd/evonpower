<?php

namespace App\Services;

use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use App\Models\Partner;
use Illuminate\Support\Facades\Log;

class AutomaticBusinessProfileService
{
    /**
     * Appliquer automatiquement le Business Profile Admin → Intégrateur (PAR DÉFAUT) lors de la création d'un intégrateur
     */
    public function applyDefaultBusinessProfileToIntegrator(Integrator $integrator): Integrator
    {
        $defaultProfile = $this->getDefaultAdminIntegratorProfile();
        
        if ($defaultProfile) {
            $integrator->business_profile_id = $defaultProfile->id;
            $integrator->save();
            
            Log::info('Business Profile Admin -> Integrateur (PAR DEFAUT) applique automatiquement', [
                'integrator_id' => $integrator->id,
                'integrator_name' => $integrator->name,
                'business_profile_id' => $defaultProfile->id,
                'business_profile_name' => $defaultProfile->name
            ]);
        }
        
        return $integrator;
    }

    /**
     * Appliquer automatiquement le Business Profile approprié lors de la création d'un opérateur
     */
    public function applyDefaultBusinessProfileToOperator(User $operator, ?User $creator = null): User
    {
        $defaultProfile = $this->getDefaultBusinessProfileForOperator($creator);
        
        if ($defaultProfile && $operator->partner_id) {
            $partner = Partner::find($operator->partner_id);
            if ($partner) {
                $partner->business_profile_id = $defaultProfile->id;
                $partner->save();
                
                Log::info('Business Profile applique automatiquement a l\'operateur', [
                    'operator_id' => $operator->id,
                    'operator_name' => $operator->name,
                    'creator_id' => $creator ? $creator->id : null,
                    'creator_name' => $creator ? $creator->name : 'Admin',
                    'business_profile_id' => $defaultProfile->id,
                    'business_profile_name' => $defaultProfile->name
                ]);
            }
        }
        
        return $operator;
    }

    /**
     * Obtenir le Business Profile par défaut Admin → Intégrateur
     */
    private function getDefaultAdminIntegratorProfile(): ?BusinessProfile
    {
        return BusinessProfile::where('name', 'Business Profile Standard Admin → Intégrateur')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Obtenir le Business Profile par défaut Intégrateur → Opérateur
     */
    private function getDefaultIntegratorOperatorProfile(): ?BusinessProfile
    {
        return BusinessProfile::where('name', 'Business Profile Standard Intégrateur → Opérateur')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Déterminer le Business Profile par défaut selon le créateur de l'opérateur
     */
    private function getDefaultBusinessProfileForOperator(?User $creator): ?BusinessProfile
    {
        if ($creator && $creator->hasRole('integrator')) {
            // Opérateur créé par intégrateur → Business Profile Intégrateur → Opérateur (PAR DÉFAUT)
            return $this->getDefaultIntegratorOperatorProfile();
        } else {
            // Opérateur créé par admin ou créateur non identifié → Business Profile Admin → Intégrateur (PAR DÉFAUT)
            return $this->getDefaultAdminIntegratorProfile();
        }
    }

    /**
     * Appliquer automatiquement le Business Profile aux charging points créés par un opérateur
     */
    public function applyDefaultBusinessProfileToChargingPoint($chargingPoint, User $operator): void
    {
        if ($operator->partner_id) {
            $partner = Partner::find($operator->partner_id);
            if ($partner && $partner->business_profile_id) {
                $chargingPoint->business_profile_id = $partner->business_profile_id;
                $chargingPoint->save();
                
                Log::info('Business Profile applique automatiquement au charging point', [
                    'charging_point_id' => $chargingPoint->id,
                    'charging_point_name' => $chargingPoint->name,
                    'operator_id' => $operator->id,
                    'operator_name' => $operator->name,
                    'business_profile_id' => $partner->business_profile_id
                ]);
            }
        }
    }
}