<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Integrator;
use Illuminate\Auth\Access\HandlesAuthorization;

class IntegratorPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * Admin and super-admin can do anything.
     */
    public function before(User $user, $ability)
    {
        \Log::info('IntegratorPolicy::before() - START', [
            'user_id' => $user->id,
            'ability' => $ability,
            'user_roles' => $user->getRoleNames()->toArray(),
        ]);

        // Admin and Super Admin can do anything
        // Check for both lowercase and capitalized versions
        $isAdmin = $user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin', 'Super Admin', 'Super-Admin']);
        \Log::info('IntegratorPolicy::before() - isAdmin check', [
            'isAdmin' => $isAdmin,
            'user_roles' => $user->getRoleNames()->toArray(),
        ]);

        if ($isAdmin) {
            \Log::info('IntegratorPolicy::before() - RETURNING TRUE');
            return true;
        }
        // Return null to defer to specific policy methods
        \Log::info('IntegratorPolicy::before() - RETURNING NULL');
        return null;
    }

    /**
     * Determine whether the user can view any integrators.
     */
    public function viewAny(User $user)
    {
        // Admin can view all integrators
        if ($user->hasRole(['admin', 'super_admin', 'super_admin'])) {
            return true;
        }

        // Integrator can view themselves only
        if ($user->hasRole('integrator')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the integrator.
     */
    public function view(User $user, Integrator $integrator)
    {
        \Log::info('IntegratorPolicy@view', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames()->toArray(),
            'integrator_id' => $integrator->id,
            'integrator_user_id' => $integrator->user_id,
            'user_integrator_id' => $user->integrator_id,
            'user_partner_integrator_id' => $user->partner?->integrator_id
        ]);

        // Admin and Super Admin can view all integrators
        if ($user->hasRole(['admin', 'super_admin', 'super_admin'])) {
            \Log::info('IntegratorPolicy@view: Admin access granted');
            return true;
        }

        // Integrator can view themselves
        if ($user->hasRole('integrator') && $user->id === $integrator->user_id) {
            \Log::info('IntegratorPolicy@view: Integrator access granted - self access');
            return true;
        }

        // Partner can view their integrator
        if ($user->hasRole('partner') && $user->partner && $user->partner->integrator_id === $integrator->id) {
            \Log::info('IntegratorPolicy@view: Partner access granted - their integrator');
            return true;
        }

        // Operator can view their integrator
        if ($user->hasRole('operator') && $user->integrator_id === $integrator->id) {
            \Log::info('IntegratorPolicy@view: Operator access granted - their integrator');
            return true;
        }

        \Log::info('IntegratorPolicy@view: Access denied');
        return false;
    }

    /**
     * Determine whether the user can create integrators.
     */
    public function create(User $user)
    {
        // Only admin can create integrators
        // Integrators cannot create other integrators
        return $user->hasRole(['admin', 'super_admin', 'super_admin']);
    }

    /**
     * Determine whether the user can update the integrator.
     */
    public function update(User $user, Integrator $integrator)
    {
        \Log::info('IntegratorPolicy@update', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames()->toArray(),
            'integrator_id' => $integrator->id,
            'integrator_user_id' => $integrator->user_id
        ]);

        // Admin can update all integrators
        // Check for both lowercase and capitalized versions
        if ($user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin', 'Super Admin', 'Super-Admin'])) {
            \Log::info('IntegratorPolicy@update: Admin access granted', [
                'user_roles' => $user->getRoleNames()->toArray(),
                'has_admin' => $user->hasRole('admin'),
                'has_super_admin' => $user->hasRole(['super_admin', 'super_admin'])
            ]);
            return true;
        }

        // Integrator can update themselves
        if ($user->hasRole(['integrator', 'Integrator']) && $user->id === $integrator->user_id) {
            \Log::info('IntegratorPolicy@update: Integrator access granted - self access');
            return true;
        }

        \Log::info('IntegratorPolicy@update: Access denied');
        return false;
    }

    /**
     * Determine whether the user can delete the integrator.
     */
    public function delete(User $user, Integrator $integrator)
    {
        // Only admin can delete integrators
        // Integrators cannot delete integrators (including themselves)
        return $user->hasRole(['admin', 'super_admin', 'super_admin']);
    }

    /**
     * Determine whether the user can restore the integrator.
     */
    public function restore(User $user, Integrator $integrator)
    {
        // Only admin can restore integrators
        // Integrators cannot restore integrators
        return $user->hasRole(['admin', 'super_admin', 'super_admin']);
    }

    /**
     * Determine whether the user can permanently delete the integrator.
     */
    public function forceDelete(User $user, Integrator $integrator)
    {
        // Only admin can permanently delete integrators
        // Integrators cannot permanently delete integrators
        return $user->hasRole(['admin', 'super_admin', 'super_admin']);
    }

    /**
     * Determine whether the user can manage the integrator's partners.
     */
    public function managePartners(User $user, Integrator $integrator)
    {
        // Admin can manage all integrators' partners
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator can manage their own partners
        if ($user->hasRole('integrator') && $user->id === $integrator->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage the integrator's operators.
     */
    public function manageOperators(User $user, Integrator $integrator)
    {
        // Admin can manage all integrators' operators
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator can manage their own operators
        if ($user->hasRole('integrator') && $user->id === $integrator->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage the integrator's charging points.
     */
    public function manageChargingPoints(User $user, Integrator $integrator)
    {
        // Admin can manage all integrators' charging points
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator can manage their own charging points
        if ($user->hasRole('integrator') && $user->id === $integrator->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the integrator's statistics.
     */
    public function viewStatistics(User $user, Integrator $integrator)
    {
        // Admin can view all integrators' statistics
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator can view their own statistics
        if ($user->hasRole('integrator') && $user->id === $integrator->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage the integrator's business profile.
     */
    public function manageBusinessProfile(User $user, Integrator $integrator)
    {
        // Admin can manage all integrators' business profiles
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator can manage their own business profile
        if ($user->hasRole('integrator') && $user->id === $integrator->user_id) {
            return true;
        }

        return false;
    }
}