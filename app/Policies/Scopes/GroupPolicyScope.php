<?php

namespace App\Policies\Scopes;

use App\Models\User;
use App\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\Response;

class GroupPolicyScope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function __invoke(User $user, Builder $query): void
    {
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            // Admins can view all groups
            return;
        }

        if ($user->hasRole('integrator') && $user->integrator_id) {
            // Integrators can view groups associated with their partners
            $partnerIds = $user->integrator->partners->pluck('id');
            $query->whereIn('partner_id', $partnerIds);
            return;
        }

        if ($user->hasRole('partner') && $user->partner_id) {
            // Partners can view their own groups
            $query->where('partner_id', $user->partner_id);
            return;
        }

        // Users can view groups they are a member of
        $query->whereHas('users', function (Builder $query) use ($user) {
            $query->where('users.id', $user->id);
        });
    }
}