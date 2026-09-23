<?php

namespace App\Policies\Scopes;

use App\Models\User;
use App\Models\ChargingPoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\Response;

class ChargingPointPolicyScope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function __invoke(User $user, Builder $query): void
    {
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            // Admins can view all charging points
            return;
        }

        if ($user->hasRole('integrator') && $user->integrator_id) {
            // Integrators can view charging points associated with their partners
            $partnerIds = $user->integrator->partners->pluck('id');
            $query->whereIn('partner_id', $partnerIds);
            return;
        }

        if ($user->hasRole('partner') && $user->partner_id) {
            // Partners can view their own charging points
            $query->where('partner_id', $user->partner_id);
            return;
        }

        // Users can view public charging points or those assigned to their group/account
        // Assuming a relationship between User and Group, and Group and ChargingPoint
        $groupIds = $user->groups->pluck('id');
        $query->where(function (Builder $query) use ($groupIds) {
            $query->where('public_access', true)
                  ->orWhereIn('group_id', $groupIds);
        });
    }
}