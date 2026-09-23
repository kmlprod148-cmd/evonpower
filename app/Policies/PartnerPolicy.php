<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Partner;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class PartnerPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        // Si admin ou super-admin (peu importe la casse/variant) -> accès complet
        if ($user->hasRole(['admin','Admin','super_admin','super_admin','Super Admin','Super-Admin'])) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        // Admin can view all partners
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Integrator can view their own partners
        if ($user->hasRole('integrator')) {
            return true;
        }

        return false;
    }

    public function view(User $user, Partner $partner)
    {
        // Si admin ou super-admin -> accès complet
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // L'intégrateur peut voir si il a créé le partenaire OU si le partenaire appartient à son intégrateur
        return $this->belongsToIntegrator($user, $partner);
    }

    public function create(User $user)
    {
        // Only admin and integrator can create partners
        return $user->hasRole(['admin', 'super_admin', 'integrator']);
    }

    public function update(User $user, Partner $partner)
    {
        // Si admin ou super-admin -> accès complet
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        return $this->belongsToIntegrator($user, $partner);
    }

    public function delete(User $user, Partner $partner)
    {
        // Si admin ou super-admin -> accès complet
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        return $this->belongsToIntegrator($user, $partner);
    }

    public function activate(User $user, Partner $partner)
    {
        return $this->belongsToIntegrator($user, $partner);
    }

    protected function belongsToIntegrator(User $user, Partner $partner)
    {
        if ($user->hasRole('integrator')) {
            Log::info('PartnerPolicy::belongsToIntegrator - Checking integrator access', [
                'user_id' => $user->id,
                'user_integrator_id' => $user->integrator_id,
                'partner_id' => $partner->id,
                'partner_integrator_id' => $partner->integrator_id,
                'partner_created_by' => $partner->created_by,
                'partner_created_by_id' => $partner->created_by_id,
                'partner_created_by_type' => $partner->created_by_type,
            ]);

            // Cas 1: partenaire créé par cet intégrateur (nouveau système polymorphique)
            if ($partner->created_by_type === get_class($user) && $partner->created_by_id === $user->id) {
                Log::info('PartnerPolicy::belongsToIntegrator - Access granted (Case 1: created by integrator)');
                return true;
            }

            // Cas 1bis: partenaire créé par cet intégrateur (ancien système)
            if ($partner->created_by == $user->id) {
                Log::info('PartnerPolicy::belongsToIntegrator - Access granted (Case 1bis: created by integrator old system)');
                return true;
            }

            // Cas 2: partenaire lié à l'intégrateur via integrator_id (comparing as integers)
            if ($partner->integrator_id && $user->integrator_id) {
                if ((int)$partner->integrator_id === (int)$user->integrator_id) {
                    Log::info('PartnerPolicy::belongsToIntegrator - Access granted (Case 2: integrator_id match)');
                    return true;
                }
            }

            // Cas 3: Vérifier si l'intégrateur a un modèle Integrator et si le partenaire y est lié
            if ($user->integrator && $partner->integrator_id) {
                if ((int)$partner->integrator_id === (int)$user->integrator->id) {
                    Log::info('PartnerPolicy::belongsToIntegrator - Access granted (Case 3: integrator model match)');
                    return true;
                }
            }

            // Cas 4: Vérifier si le partenaire a un integrator et si cet integrator a un user qui est cet intégrateur
            if ($partner->integrator && $user->integrator) {
                if ($partner->integrator->id === $user->integrator->id) {
                    Log::info('PartnerPolicy::belongsToIntegrator - Access granted (Case 4: integrator relationship match)');
                    return true;
                }
            }

            // Cas 5: Vérifier si l'intégrateur a la permission edit_partners
            if ($user->can('edit_partners')) {
                // Vérifier que le partenaire appartient bien à cet intégrateur
                if ((int)$partner->integrator_id === (int)$user->integrator_id ||
                    ($partner->created_by_type === get_class($user) && $partner->created_by_id === $user->id) ||
                    $partner->created_by == $user->id) {
                    Log::info('PartnerPolicy::belongsToIntegrator - Access granted (Case 5: has edit_partners permission)');
                    return true;
                }
            }

            // Cas 6: Fallback - If partner has no integrator_id but was created by this integrator, allow access
            if (!$partner->integrator_id && $partner->created_by == $user->id) {
                Log::info('PartnerPolicy::belongsToIntegrator - Access granted (Case 6: fallback - no integrator_id but created by user)');
                return true;
            }

            Log::info('PartnerPolicy::belongsToIntegrator - Access denied for integrator');
        }

        return false;
    }
}