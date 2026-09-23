<?php

namespace App\Policies;

use App\Models\User;
use App\Models\IntegratorProfile;
use Illuminate\Auth\Access\HandlesAuthorization;

class IntegratorProfilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any integrator profiles.
     */
    public function viewAny(User $user)
    {
        // Admin can view all integrator profiles
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator can view their own profile
        if ($user->hasRole('integrator')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the integrator profile.
     */
    public function view(User $user, IntegratorProfile $integratorProfile)
    {
        // Admin can view all integrator profiles
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator can view their own profile
        if ($user->hasRole('integrator') && $user->id === $integratorProfile->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create integrator profiles.
     */
    public function create(User $user)
    {
        // Only admin and integrator can create integrator profiles
        return $user->hasRole('admin') || $user->hasRole('integrator');
    }

    /**
     * Determine whether the user can update the integrator profile.
     */
    public function update(User $user, IntegratorProfile $integratorProfile)
    {
        // Admin can update all integrator profiles
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator can update their own profile
        if ($user->hasRole('integrator') && $user->id === $integratorProfile->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the integrator profile.
     */
    public function delete(User $user, IntegratorProfile $integratorProfile)
    {
        // Only admin can delete integrator profiles
        return $user->hasRole('admin');
    }
}
