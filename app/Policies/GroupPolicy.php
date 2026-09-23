<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        $userRoles = $user->getRoleNames()->map(fn($role) => strtolower($role))->toArray();
        if (in_array('admin', $userRoles) || in_array('super_admin', $userRoles)) {
            return true;
        }
        return null; // Continue to specific policy methods
    }

    public function viewAny(User $user)
    {
        return true; // Tous peuvent voir la liste
    }

    public function view(User $user, Group $group)
    {
        // Check for explicit permission first
        if ($user->hasPermissionTo('view_groups')) {
            return true;
        }

        // If the user is an integrator, check if they own the group
        if ($user->hasRole('integrator') && $user->integrator_id) {
            return $group->integrator_id === $user->integrator_id;
        }

        // If the user is a partner, check if they own the group
        if ($user->hasRole('partner') && $user->partner_id) {
            return $group->partner_id === $user->partner_id;
        }

        return false;
    }

    public function show(User $user, Group $group)
    {
        // Check for explicit permission first
        if ($user->hasPermissionTo('show_groups')) {
            return true;
        }

        // If the user is an integrator, check if they own the group
        if ($user->hasRole('integrator') && $user->integrator_id) {
            return $group->integrator_id === $user->integrator_id;
        }

        // If the user is a partner, check if they own the group
        if ($user->hasRole('partner') && $user->partner_id) {
            return $group->partner_id === $user->partner_id;
        }

        return false;
    }

    public function create(User $user)
    {
        return $user->hasAnyRole(['admin', 'integrator', 'partner']);
    }

    public function edit(User $user, Group $group)
    {
        return $this->update($user, $group);
    }

    public function update(User $user, Group $group)
    {
        // First, check for the explicit permission
        if ($user->hasPermissionTo('edit_groups')) {
            return true;
        }
    
        // If the user is an integrator, check if they own the group
        if ($user->hasRole('integrator') && $user->integrator_id) {
            return $group->integrator_id === $user->integrator_id;
        }
    
        // If the user is a partner, check if they own the group
        if ($user->hasRole('partner') && $user->partner_id) {
            return $group->partner_id === $user->partner_id;
        }
    
        return false;
    }

    public function delete(User $user, Group $group)
    {
        if ($user->hasPermissionTo('delete_groups')) {
            return true;
        }

        // Intégrateur peut supprimer ses groupes
        if ($user->hasRole('integrator') && $user->integrator_id) {
            return $group->integrator_id === $user->integrator_id;
        }

        // Partenaire peut supprimer ses groupes
        if ($user->hasRole('partner') && $user->partner_id) {
            return $group->partner_id === $user->partner_id;
        }

        return false;
    }
}