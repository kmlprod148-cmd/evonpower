<?php

namespace App\Policies;

use App\Models\BusinessProfile;
use App\Models\Integrator;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;

class BusinessProfilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        Log::info('BusinessProfilePolicy viewAny method called.', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames()->toArray(),
            'user_permissions' => collect($user->getAllPermissions())->pluck('name')->toArray(),
        ]);

        // Permission explicite
        if ($user->can('view_business_profiles')) {
            Log::info('BusinessProfilePolicy viewAny: User has view_business_profiles permission, granting access.');
            return Response::allow();
        }

        // Admin: accès total
        if ($user->hasRole(['admin', 'super_admin', 'Admin', 'Super Admin', 'super admin'])) {
            Log::info('BusinessProfilePolicy viewAny: User is admin, granting access.');
            return Response::allow();
        }

        // Intégrateur: accès à la liste (filtrage appliqué côté requête)
        if ($user->hasRole(['integrator', 'Integrator'])) {
            Log::info('BusinessProfilePolicy viewAny: User is integrator, granting access.');
            return Response::allow();
        }

        // Autres rôles: refus
        Log::info('BusinessProfilePolicy viewAny: Denying access for non-admin/non-integrator without permission.');
        return Response::deny('Accès refusé.');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BusinessProfile  $businessProfile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, BusinessProfile $businessProfile)
    {
        // Super-admin: peut voir tous les profils
        if ($user->hasRole(['super_admin', 'Super Admin', 'super admin'])) {
            return Response::allow();
        }
        
        // Admin: ne peut voir que ses propres profils
        if ($user->hasRole('admin')) {
            $isOwn = ($businessProfile->created_by === $user->id) ||
                     ($businessProfile->created_by_id === $user->id) ||
                     ($businessProfile->created_by_type === get_class($user) && $businessProfile->created_by_id === $user->id);
            return $isOwn ? Response::allow() : Response::deny('Accès refusé.');
        }

        // Integrator: peut voir tous les profils SAUF ceux créés par d'autres intégrateurs
        if ($user->hasRole(['integrator', 'Integrator'])) {
            // Vérifier si le profil a été créé par un autre intégrateur
            if ($this->isCreatedByOtherIntegrator($businessProfile, $user)) {
                return Response::deny('Vous ne pouvez pas accéder aux business profiles d\'autres intégrateurs.');
            }
            // Sinon, accès autorisé (comme un admin)
            return Response::allow();
        }

        // Users with 'manage_all_business_profiles' permission can view any profile
        if ($user->can('manage_all_business_profiles')) {
            return Response::allow();
        }

        // Check if the user is the direct creator of the business profile
        if ($businessProfile->created_by_type === get_class($user) && $businessProfile->created_by_id === $user->id) {
            return Response::allow();
        }

        // If the profile is public, any authenticated user can view it
        if ($businessProfile->is_public) {
            return Response::allow();
        }

        return Response::deny('Accès refusé.');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        Log::info('BusinessProfilePolicy create method called.', [
            'user_id' => $user->id,
            'user_permissions' => collect($user->getAllPermissions())->pluck('name')->toArray(),
            'user_roles' => collect($user->getRoleNames())->toArray(),
        ]);

        // Check for specific permission first
        if ($user->can('create_business_profiles')) {
            Log::info('BusinessProfilePolicy create: User has create_business_profiles permission, granting access.');
            return Response::allow();
        }

        // Allow admin (any case), super-admin, integrators (any case) and partners with an integrator to create profiles
        $canCreate = $user->hasRole(['admin', 'Admin', 'super_admin', 'Super Admin', 'super admin']) ||
                     $user->hasRole(['integrator', 'Integrator']) ||
                     ($user->hasRole('partner') && $user->integrator_id !== null);

        Log::info('BusinessProfilePolicy create: User roles check result.', [
            'can_create' => $canCreate,
            'is_admin' => $user->hasRole(['admin', 'Admin', 'super_admin', 'Super Admin', 'super admin']),
            'is_integrator' => $user->hasRole(['integrator', 'Integrator']),
            'is_partner_with_integrator' => ($user->hasRole('partner') && $user->integrator_id !== null),
        ]);

        return $canCreate ? Response::allow() : Response::deny('Accès refusé.');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BusinessProfile  $businessProfile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, BusinessProfile $businessProfile)
    {
        Log::info('BusinessProfilePolicy update method called.', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames()->toArray(),
            'business_profile_id' => $businessProfile->id
        ]);

        // Check for specific permission first
        if ($user->can('edit_business_profiles') || $user->can('manage_all_business_profiles')) {
            Log::info('BusinessProfilePolicy update: User has edit_business_profiles or manage_all_business_profiles permission, granting access.');
            return Response::allow();
        }

        // Admin and super-admin can update any profile
        if ($user->hasRole(['admin', 'super_admin', 'Admin', 'Super Admin', 'super admin'])) {
            Log::info('BusinessProfilePolicy update: User is admin/super-admin, granting access.', ['user_id' => $user->id]);
            return Response::allow();
        }

        // Integrator: peut mettre à jour tous les profils SAUF ceux créés par d'autres intégrateurs
        if ($user->hasRole(['integrator', 'Integrator'])) {
            // Vérifier si le profil a été créé par un autre intégrateur
            if ($this->isCreatedByOtherIntegrator($businessProfile, $user)) {
                Log::info('BusinessProfilePolicy update: Integrator cannot update business profile from another integrator.');
                return Response::deny('Vous ne pouvez pas modifier les business profiles d\'autres intégrateurs.');
            }
            // Sinon, accès autorisé (comme un admin)
            Log::info('BusinessProfilePolicy update: Integrator can update this business profile.');
            return Response::allow();
        }

        // Partners cannot update profiles
        Log::info('BusinessProfilePolicy update: User has no permission to update.');
        return Response::deny('Accès refusé.');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BusinessProfile  $businessProfile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, BusinessProfile $businessProfile)
    {
        Log::info('BusinessProfilePolicy delete method called.', [
            'user_id' => $user->id,
            'business_profile_id' => $businessProfile->id,
            'user_permissions' => collect($user->getAllPermissions())->pluck('name')->toArray(), // Convert to array for better logging
            'user_roles' => collect($user->getRoleNames())->toArray(), // Convert to array for better logging
            'profile_created_by_type' => $businessProfile->created_by_type,
            'profile_created_by_id' => $businessProfile->created_by_id,
            'user_class' => get_class($user)
        ]);

        // Check for specific permission first - this is what your middleware expects
        if ($user->can('delete_business_profiles') || $user->can('manage_all_business_profiles')) {
            Log::info('BusinessProfilePolicy delete: User has delete_business_profiles or manage_all_business_profiles permission, granting access.');
            return Response::allow();
        }

        // Admin and super-admin can delete any profile
        if ($user->hasRole(['admin', 'super_admin', 'Admin', 'Super Admin', 'super admin'])) {
            Log::info('BusinessProfilePolicy delete: User is admin/super-admin, granting access.');
            return Response::allow();
        }

        // Integrator: peut supprimer tous les profils SAUF ceux créés par d'autres intégrateurs
        if ($user->hasRole(['integrator', 'Integrator'])) {
            // Vérifier si le profil a été créé par un autre intégrateur
            if ($this->isCreatedByOtherIntegrator($businessProfile, $user)) {
                Log::info('BusinessProfilePolicy delete: Integrator cannot delete business profile from another integrator.');
                return Response::deny('Vous ne pouvez pas supprimer les business profiles d\'autres intégrateurs.');
            }
            // Sinon, accès autorisé (comme un admin)
            Log::info('BusinessProfilePolicy delete: Integrator can delete this business profile.');
            return Response::allow();
        }

        // Partners cannot delete profiles
        Log::info('BusinessProfilePolicy delete: User is not authorized to delete, denying access.');
        return Response::deny('Accès refusé.');
    }

    /**
     * Determine whether the user can manage partner rates for the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BusinessProfile  $businessProfile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function managePartnerRates(User $user, BusinessProfile $businessProfile)
    {
        Log::info('BusinessProfilePolicy managePartnerRates method called.', [
            'user_id' => $user->id,
            'business_profile_id' => $businessProfile->id,
            'user_permissions' => collect($user->getAllPermissions())->pluck('name')->toArray(),
            'user_roles' => collect($user->getRoleNames())->toArray(),
        ]);

        // Specific permission overrides role logic
        if ($user->can('manage_partner_rates')) {
            Log::info('BusinessProfilePolicy managePartnerRates: User has manage_partner_rates permission, granting access.');
            return Response::allow();
        }

        // Admin and super-admin can manage partner rates for any profile
        if ($user->hasRole(['admin', 'super_admin', 'Admin', 'Super Admin', 'super admin'])) {
            Log::info('BusinessProfilePolicy managePartnerRates: User is admin/super-admin, granting access.');
            return Response::allow();
        }

        // Integrators can manage partner rates for their own profiles
        if ($user->hasRole(['integrator', 'Integrator'])) {
            $canManage = $businessProfile->created_by_type === get_class($user) &&
                         $businessProfile->created_by_id === $user->id;

            // Also allow managing rates for profiles created by THIS integrator's admin
            if (!$canManage && $user->integrator_id) {
                $integrator = Integrator::find($user->integrator_id);
                $adminId = $integrator && $integrator->admin ? $integrator->admin->id : null;
                if ($adminId) {
                    $canManage = $businessProfile->created_by === $adminId ||
                                 $businessProfile->created_by_id === $adminId ||
                                 ($businessProfile->created_by_type === User::class && $businessProfile->created_by_id === $adminId);
                }
            }
            Log::info('BusinessProfilePolicy managePartnerRates: User is integrator.', [
                'can_manage' => $canManage,
                'created_by_type' => $businessProfile->created_by_type,
                'created_by_id' => $businessProfile->created_by_id,
                'user_id' => $user->id
            ]);
            if ($canManage) {
                Log::info('BusinessProfilePolicy managePartnerRates: Integrator owns this business profile, granting access.');
                return Response::allow();
            }
            // If integrator doesn't own the profile, deny access (even if they have the permission)
            Log::info('BusinessProfilePolicy managePartnerRates: Integrator does not own this business profile, denying access.');
            return Response::deny('Vous ne pouvez gérer les taux que pour vos propres business profiles.');
        }

        // Check for specific permission (for other roles)
        if ($user->can('manage_partner_rates')) {
            Log::info('BusinessProfilePolicy managePartnerRates: User has manage_partner_rates permission, granting access.');
            return Response::allow();
        }

        // Partners can manage rates for business profiles they are associated with
        if ($user->hasRole('partner') && $user->partner_id && $businessProfile->partner_id === $user->partner_id) {
            Log::info('BusinessProfilePolicy managePartnerRates: User is a partner associated with this business profile, granting access.');
            return Response::allow();
        }

        Log::info('BusinessProfilePolicy managePartnerRates: User is not authorized, denying access.');
        return Response::deny('Accès refusé.');
    }

    /**
     * Vérifier si un business profile a été créé par un autre intégrateur
     *
     * @param BusinessProfile $businessProfile
     * @param User $currentIntegrator
     * @return bool
     */
    protected function isCreatedByOtherIntegrator(BusinessProfile $businessProfile, User $currentIntegrator): bool
    {
        // Si le profil n'a pas de créateur, ce n'est pas créé par un intégrateur
        if (!$businessProfile->created_by_type && !$businessProfile->created_by_id && !$businessProfile->created_by) {
            return false;
        }

        // Vérifier si le créateur est un utilisateur (User)
        if ($businessProfile->created_by_type === User::class || $businessProfile->created_by_type === get_class($currentIntegrator)) {
            $creatorId = $businessProfile->created_by_id ?? $businessProfile->created_by;
            
            if (!$creatorId) {
                return false;
            }

            // Si c'est le même utilisateur, ce n'est pas un autre intégrateur
            if ($creatorId == $currentIntegrator->id) {
                return false;
            }

            // Vérifier si le créateur est un intégrateur différent
            $creator = User::find($creatorId);
            if ($creator && $creator->hasRole(['integrator', 'Integrator'])) {
                // Si c'est un intégrateur différent (pas le même ID), c'est un autre intégrateur
                return $creator->id !== $currentIntegrator->id;
            }
        }

        // Vérifier aussi via created_by_role
        if ($businessProfile->created_by_role === 'integrator' && 
            ($businessProfile->created_by_id != $currentIntegrator->id || $businessProfile->created_by != $currentIntegrator->id)) {
            return true;
        }

        return false;
    }
}