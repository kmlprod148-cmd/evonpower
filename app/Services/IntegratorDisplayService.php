<?php

namespace App\Services;

use App\Models\User;
use App\Models\Integrator;
use App\Models\BusinessProfile;
use App\Models\Partner;

class IntegratorDisplayService
{
    /**
     * Obtenir les intégrateurs visibles pour un utilisateur donné
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVisibleIntegratorsForUser(User $user)
    {
        if ($user->hasRole('integrator')) {
            return $this->getVisibleIntegratorsForIntegrator($user);
        } elseif ($user->hasRole(['operator', 'partner'])) {
            return $this->getVisibleIntegratorsForOperatorPartner($user);
        }
        
        // Pour les admins/super-admins, retourner tous les intégrateurs (actifs et inactifs)
        if ($user->hasRole(['admin', 'super_admin'])) {
            return Integrator::all();
        }
        
        // Par défaut, retourner tous les intégrateurs actifs
        return Integrator::where('is_active', true)->get();
    }
    
    /**
     * Obtenir les intégrateurs visibles pour un utilisateur intégrateur
     *
     * @param User $integratorUser
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVisibleIntegratorsForIntegrator(User $integratorUser)
    {
        // Les intégrateurs ne peuvent voir QUE leur propre intégrateur
        // Ils ne peuvent pas voir d'autres intégrateurs
        return Integrator::where('user_id', $integratorUser->id)->get();
    }
    
    /**
     * Obtenir les intégrateurs visibles pour un opérateur/partenaire créé par un intégrateur
     *
     * @param User $operatorPartnerUser
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVisibleIntegratorsForOperatorPartner(User $operatorPartnerUser)
    {
        $visibleIntegrators = collect();
        
        // A. L'intégrateur qui a créé cet opérateur/partenaire
        if ($operatorPartnerUser->integrator_id) {
            $creatorIntegrator = Integrator::find($operatorPartnerUser->integrator_id);
            if ($creatorIntegrator) {
                $visibleIntegrators->push($creatorIntegrator);
            }
        }
        
        // B. Intégrateurs liés aux business profiles de l'opérateur/partenaire
        if ($operatorPartnerUser->partner_id) {
            $partner = Partner::find($operatorPartnerUser->partner_id);
            if ($partner && $partner->integrator_id) {
                $integrator = Integrator::find($partner->integrator_id);
                if ($integrator) {
                    $visibleIntegrators->push($integrator);
                }
            }
        }
        
        // C. Intégrateurs créés par des admins (toujours visibles)
        $adminCreatedIntegrators = Integrator::whereHas('user', function($query) {
            $query->whereHas('roles', function($roleQuery) {
                $roleQuery->where('name', 'admin');
            });
        })->get();
        
        $visibleIntegrators = $visibleIntegrators->merge($adminCreatedIntegrators);
        
        return $visibleIntegrators->unique('id');
    }
    
    /**
     * Obtenir les intégrateurs pour un select/dropdown
     *
     * @param User $user
     * @return array
     */
    public function getIntegratorsForSelect(User $user)
    {
        $integrators = $this->getVisibleIntegratorsForUser($user);
        
        return $integrators->mapWithKeys(function ($integrator) {
            return [$integrator->id => $integrator->name];
        })->toArray();
    }
    
    /**
     * Obtenir les intégrateurs avec leurs informations détaillées
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getIntegratorsWithDetails(User $user)
    {
        $integrators = $this->getVisibleIntegratorsForUser($user);
        
        return $integrators->map(function ($integrator) {
            return [
                'id' => $integrator->id,
                'name' => $integrator->name,
                'email' => $integrator->email,
                'is_active' => $integrator->is_active,
                'created_at' => $integrator->created_at,
                'user' => $integrator->user ? [
                    'id' => $integrator->user->id,
                    'email' => $integrator->user->email,
                    'roles' => $integrator->user->getRoleNames()->toArray()
                ] : null
            ];
        });
    }
}