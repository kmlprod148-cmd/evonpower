<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Partner;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class IntegratorPartnerPolicy
{
    use HandlesAuthorization;

    /**
     * Détermine si l'utilisateur peut voir la liste des partenaires
     */
    public function viewAny(User $user): bool
    {
        // Les admins peuvent voir tous les partenaires
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent voir leurs propres partenaires
        if ($user->hasRole('integrator')) {
            return $user->can('view_partners');
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut voir un partenaire spécifique
     */
    public function view(User $user, Partner $partner): bool
    {
        // Les admins peuvent voir tous les partenaires
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent voir leurs propres partenaires
        if ($user->hasRole('integrator')) {
            $integrator = $user->integrator;
            if (!$integrator) {
                return false;
            }

            // Check if user has show_partners permission
            if (!$user->can('show_partners')) {
                return false;
            }

            // Case 1: Partner created by this integrator (polymorphic system)
            if ($partner->created_by_type === get_class($user) && $partner->created_by_id === $user->id) {
                return true;
            }

            // Case 2: Partner created by this integrator (old system)
            if ($partner->created_by == $user->id) {
                return true;
            }

            // Case 3: Partner's integrator_id matches user's integrator_id (with type casting)
            if ($partner->integrator_id && $user->integrator_id) {
                if ((int)$partner->integrator_id === (int)$integrator->id) {
                    return true;
                }
            }

            // Case 4: Partner has integrator relationship that matches user's integrator
            if ($partner->integrator && $user->integrator) {
                if ($partner->integrator->id === $user->integrator->id) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut créer des partenaires
     */
    public function create(User $user): bool
    {
        // Les admins peuvent créer des partenaires
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent créer des partenaires
        if ($user->hasRole('integrator')) {
            return $user->can('create_partners');
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut modifier un partenaire
     */
    public function update(User $user, Partner $partner): bool
    {
        // Les admins peuvent modifier tous les partenaires
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent modifier leurs propres partenaires
        if ($user->hasRole('integrator')) {
            $integrator = $user->integrator;

            Log::info('IntegratorPartnerPolicy::update - Checking integrator access', [
                'user_id' => $user->id,
                'user_integrator_id' => $user->integrator_id,
                'user_integrator' => $integrator ? $integrator->id : null,
                'partner_id' => $partner->id,
                'partner_integrator_id' => $partner->integrator_id,
                'partner_created_by' => $partner->created_by,
                'partner_created_by_type' => $partner->created_by_type,
                'partner_created_by_id' => $partner->created_by_id,
            ]);

            if (!$integrator) {
                Log::info('IntegratorPartnerPolicy::update - No integrator found for user');
                return false;
            }

            // Check if user has edit_partners permission
            if (!$user->can('edit_partners')) {
                Log::info('IntegratorPartnerPolicy::update - User does not have edit_partners permission');
                return false;
            }

            // Case 1: Partner created by this integrator (polymorphic system)
            if ($partner->created_by_type === get_class($user) && $partner->created_by_id === $user->id) {
                Log::info('IntegratorPartnerPolicy::update - Access granted (Case 1: created by integrator)');
                return true;
            }

            // Case 2: Partner created by this integrator (old system)
            if ($partner->created_by == $user->id) {
                Log::info('IntegratorPartnerPolicy::update - Access granted (Case 2: created by integrator old system)');
                return true;
            }

            // Case 3: Partner's integrator_id matches user's integrator_id (with type casting)
            if ($partner->integrator_id && $user->integrator_id) {
                if ((int)$partner->integrator_id === (int)$integrator->id) {
                    Log::info('IntegratorPartnerPolicy::update - Access granted (Case 3: integrator_id match)');
                    return true;
                }
            }

            // Case 4: Partner has integrator relationship that matches user's integrator
            if ($partner->integrator && $user->integrator) {
                if ($partner->integrator->id === $user->integrator->id) {
                    Log::info('IntegratorPartnerPolicy::update - Access granted (Case 4: integrator relationship match)');
                    return true;
                }
            }

            Log::info('IntegratorPartnerPolicy::update - Access denied for integrator');
            return false;
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut supprimer un partenaire
     */
    public function delete(User $user, Partner $partner): bool
    {
        // Les admins peuvent supprimer tous les partenaires
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent supprimer leurs propres partenaires
        if ($user->hasRole('integrator')) {
            $integrator = $user->integrator;
            if (!$integrator) {
                return false;
            }

            // Check if user has delete_partners permission
            if (!$user->can('delete_partners')) {
                return false;
            }

            // Case 1: Partner created by this integrator (polymorphic system)
            if ($partner->created_by_type === get_class($user) && $partner->created_by_id === $user->id) {
                return true;
            }

            // Case 2: Partner created by this integrator (old system)
            if ($partner->created_by == $user->id) {
                return true;
            }

            // Case 3: Partner's integrator_id matches user's integrator_id (with type casting)
            if ($partner->integrator_id && $user->integrator_id) {
                if ((int)$partner->integrator_id === (int)$integrator->id) {
                    return true;
                }
            }

            // Case 4: Partner has integrator relationship that matches user's integrator
            if ($partner->integrator && $user->integrator) {
                if ($partner->integrator->id === $user->integrator->id) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut activer/désactiver un partenaire
     */
    public function activate(User $user, Partner $partner): bool
    {
        // Les admins peuvent activer/désactiver tous les partenaires
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent activer/désactiver leurs propres partenaires
        if ($user->hasRole('integrator')) {
            $integrator = $user->integrator;
            if (!$integrator) {
                return false;
            }

            // Check if user has activate_partners permission
            if (!$user->can('activate_partners')) {
                return false;
            }

            // Case 1: Partner created by this integrator (polymorphic system)
            if ($partner->created_by_type === get_class($user) && $partner->created_by_id === $user->id) {
                return true;
            }

            // Case 2: Partner created by this integrator (old system)
            if ($partner->created_by == $user->id) {
                return true;
            }

            // Case 3: Partner's integrator_id matches user's integrator_id (with type casting)
            if ($partner->integrator_id && $user->integrator_id) {
                if ((int)$partner->integrator_id === (int)$integrator->id) {
                    return true;
                }
            }

            // Case 4: Partner has integrator relationship that matches user's integrator
            if ($partner->integrator && $user->integrator) {
                if ($partner->integrator->id === $user->integrator->id) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut gérer le business profile d'un partenaire
     */
    public function manageBusinessProfile(User $user, Partner $partner): bool
    {
        // Les admins peuvent gérer tous les business profiles
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent gérer les business profiles de leurs partenaires
        if ($user->hasRole('integrator')) {
            $integrator = $user->integrator;
            if (!$integrator) {
                return false;
            }

            // Check if user has edit_business_profiles permission
            if (!$user->can('edit_business_profiles')) {
                return false;
            }

            // Case 1: Partner created by this integrator (polymorphic system)
            if ($partner->created_by_type === get_class($user) && $partner->created_by_id === $user->id) {
                return true;
            }

            // Case 2: Partner created by this integrator (old system)
            if ($partner->created_by == $user->id) {
                return true;
            }

            // Case 3: Partner's integrator_id matches user's integrator_id (with type casting)
            if ($partner->integrator_id && $user->integrator_id) {
                if ((int)$partner->integrator_id === (int)$integrator->id) {
                    return true;
                }
            }

            // Case 4: Partner has integrator relationship that matches user's integrator
            if ($partner->integrator && $user->integrator) {
                if ($partner->integrator->id === $user->integrator->id) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
}
