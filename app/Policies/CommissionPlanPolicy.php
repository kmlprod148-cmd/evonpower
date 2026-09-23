<?php

namespace App\Policies;

use App\Models\CommissionPlan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommissionPlanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_commission_settings');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CommissionPlan  $commissionPlan
     * @return bool
     */
    public function view(User $user, CommissionPlan $commissionPlan): bool
    {
        return $user->can('view_commission_settings');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('manage_commissions');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CommissionPlan  $commissionPlan
     * @return bool
     */
    public function update(User $user, CommissionPlan $commissionPlan): bool
    {
        return $user->can('manage_commissions');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CommissionPlan  $commissionPlan
     * @return bool
     */
    public function delete(User $user, CommissionPlan $commissionPlan): bool
    {
        // Prevent deletion if the plan is associated with transactions
        if ($commissionPlan->transactions()->exists()) {
            return false;
        }
        return $user->can('manage_commissions');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CommissionPlan  $commissionPlan
     * @return bool
     */
    public function restore(User $user, CommissionPlan $commissionPlan): bool
    {
        return $user->can('manage_commissions');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CommissionPlan  $commissionPlan
     * @return bool
     */
    public function forceDelete(User $user, CommissionPlan $commissionPlan): bool
    {
        return $user->can('manage_commissions');
    }
}