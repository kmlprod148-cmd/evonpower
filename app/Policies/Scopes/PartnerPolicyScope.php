<?php

namespace App\Policies\Scopes;

use App\Models\User;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\Response;

class PartnerPolicyScope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function __invoke(User $user, Builder $query): void
    {
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            // Admins can view all partners
            return;
        }

        if ($user->hasRole('integrator') && $user->integrator_id) {
            // Integrators can view their own partners
            $query->where('integrator_id', $user->integrator_id);
            return;
        }

        // Partners and Users typically cannot view a list of all partners,
        // so no scope is applied for these roles in viewAny.
    }
}