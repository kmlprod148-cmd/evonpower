<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Integrator;
use Illuminate\Auth\Access\HandlesAuthorization;

class OperatorPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * Admin and super-admin can do anything.
     */
    public function before(User $user, $ability)
    {
        \Log::info('OperatorPolicy::before() - START', [
            'user_id' => $user->id,
            'ability' => $ability,
            'user_roles' => $user->getRoleNames()->toArray(),
        ]);

        // Admin and Super Admin can do anything
        // Check for both lowercase and capitalized versions
        $isAdmin = $user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin', 'Super Admin', 'Super-Admin']);
        \Log::info('OperatorPolicy::before() - isAdmin check', [
            'isAdmin' => $isAdmin,
            'user_roles' => $user->getRoleNames()->toArray(),
        ]);

        if ($isAdmin) {
            \Log::info('OperatorPolicy::before() - RETURNING TRUE');
            return true;
        }
        // Return null to defer to specific policy methods
        \Log::info('OperatorPolicy::before() - RETURNING NULL');
        return null;
    }

    /**
     * Determine whether the user can view any operators.
     */
    public function viewAny(User $user)
    {
        // Admin can view all operators
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator can view their own operators
        if ($user->hasRole('integrator')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the operator.
     */
    public function view(User $user, User $operator)
    {
        \Log::info('OperatorPolicy@view', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames()->toArray(),
            'operator_id' => $operator->id,
            'operator_created_by' => $operator->created_by,
            'operator_integrator_id' => $operator->integrator_id,
            'user_integrator_id' => $user->integrator?->id
        ]);

        // Admin and Super Admin can view all operators
        if ($user->hasRole(['admin', 'super_admin', 'super_admin'])) {
            \Log::info('OperatorPolicy@view: Admin access granted');
            return true;
        }

        // Integrator can view operators they created or that belong to their integrator
        if ($user->hasRole('integrator')) {
            // Check if operator was created by this integrator
            if ($operator->created_by === $user->id) {
                \Log::info('OperatorPolicy@view: Integrator access granted - created by integrator');
                return true;
            }
            
            // Check if operator belongs to this integrator via integrator_id directly on user
            if ($user->integrator_id && $operator->integrator_id && (int)$user->integrator_id === (int)$operator->integrator_id) {
                \Log::info('OperatorPolicy@view: Integrator access granted - integrator_id match');
                return true;
            }
            
            // Check if operator belongs to this integrator's integrator record
            if ($user->integrator && $operator->integrator_id && (int)$operator->integrator_id === (int)$user->integrator->id) {
                \Log::info('OperatorPolicy@view: Integrator access granted - belongs to integrator model');
                return true;
            }
            
            // Check via created_by_type and created_by_id (polymorphic)
            if ($operator->created_by_type === get_class($user) && $operator->created_by_id === $user->id) {
                \Log::info('OperatorPolicy@view: Integrator access granted - polymorphic created_by match');
                return true;
            }
        }

        // Operator can view themselves
        if ($user->hasRole('operator') && $user->id === $operator->id) {
            \Log::info('OperatorPolicy@view: Operator access granted - self access');
            return true;
        }

        \Log::info('OperatorPolicy@view: Access denied');
        return false;
    }

    /**
     * Determine whether the user can create operators.
     */
    public function create(User $user)
    {
        // Only admin and integrator can create operators
        return $user->hasRole('admin') || $user->hasRole('integrator');
    }

    /**
     * Determine whether the user can update the operator.
     */
    public function update(User $user, User $operator)
    {
        \Log::info('OperatorPolicy@update', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames()->toArray(),
            'operator_id' => $operator->id,
            'operator_created_by' => $operator->created_by,
            'operator_integrator_id' => $operator->integrator_id,
            'user_integrator_id' => $user->integrator?->id
        ]);

        // Admin can update all operators
        // Check for both lowercase and capitalized versions
        if ($user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin', 'Super Admin', 'Super-Admin'])) {
            \Log::info('OperatorPolicy@update: Admin access granted');
            return true;
        }

        // Integrator can update operators they created or that belong to their integrator
        if ($user->hasRole(['integrator', 'Integrator'])) {
            // Check if operator was created by this integrator
            if ($operator->created_by === $user->id) {
                \Log::info('OperatorPolicy@update: Integrator access granted - created by integrator');
                return true;
            }
            
            // Check if operator belongs to this integrator via integrator_id directly on user
            if ($user->integrator_id && $operator->integrator_id && (int)$user->integrator_id === (int)$operator->integrator_id) {
                \Log::info('OperatorPolicy@update: Integrator access granted - integrator_id match');
                return true;
            }
            
            // Check if operator belongs to this integrator's integrator record
            if ($user->integrator && $operator->integrator_id && (int)$operator->integrator_id === (int)$user->integrator->id) {
                \Log::info('OperatorPolicy@update: Integrator access granted - belongs to integrator model');
                return true;
            }
            
            // Check via created_by_type and created_by_id (polymorphic)
            if ($operator->created_by_type === get_class($user) && $operator->created_by_id === $user->id) {
                \Log::info('OperatorPolicy@update: Integrator access granted - polymorphic created_by match');
                return true;
            }
        }

        // Operator can update themselves
        if ($user->hasRole(['operator', 'Operator']) && $user->id === $operator->id) {
            \Log::info('OperatorPolicy@update: Operator access granted - self access');
            return true;
        }

        \Log::info('OperatorPolicy@update: Access denied');
        return false;
    }

    /**
     * Determine whether the user can delete the operator.
     */
    public function delete(User $user, User $operator)
    {
        \Log::info('OperatorPolicy@delete', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames()->toArray(),
            'operator_id' => $operator->id,
            'operator_created_by' => $operator->created_by,
            'operator_integrator_id' => $operator->integrator_id,
            'user_integrator_id' => $user->integrator_id,
            'user_integrator_model_id' => $user->integrator?->id
        ]);

        // Admin can delete all operators
        if ($user->hasRole(['admin', 'super_admin', 'super_admin'])) {
            \Log::info('OperatorPolicy@delete: Admin access granted');
            return true;
        }

        // Integrator can delete operators they created or that belong to their integrator
        if ($user->hasRole(['integrator', 'Integrator'])) {
            // Check if operator was created by this integrator
            if ($operator->created_by === $user->id) {
                \Log::info('OperatorPolicy@delete: Integrator access granted - created by integrator');
                return true;
            }
            
            // Check if operator belongs to this integrator via integrator_id directly on user
            if ($user->integrator_id && $operator->integrator_id && (int)$user->integrator_id === (int)$operator->integrator_id) {
                \Log::info('OperatorPolicy@delete: Integrator access granted - integrator_id match');
                return true;
            }
            
            // Check if operator belongs to this integrator's integrator record
            if ($user->integrator && $operator->integrator_id && (int)$operator->integrator_id === (int)$user->integrator->id) {
                \Log::info('OperatorPolicy@delete: Integrator access granted - belongs to integrator model');
                return true;
            }
            
            // Check via created_by_type and created_by_id (polymorphic)
            if ($operator->created_by_type === get_class($user) && $operator->created_by_id === $user->id) {
                \Log::info('OperatorPolicy@delete: Integrator access granted - polymorphic created_by match');
                return true;
            }
        }

        \Log::info('OperatorPolicy@delete: Access denied');
        return false;
    }

    /**
     * Determine whether the user can restore the operator.
     */
    public function restore(User $user, User $operator)
    {
        // Admin can restore all operators
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator can restore operators they created
        if ($user->hasRole('integrator') && $operator->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the operator.
     */
    public function forceDelete(User $user, User $operator)
    {
        // Only admin can permanently delete operators
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can assign roles to the operator.
     */
    public function assignRole(User $user, User $operator)
    {
        // Admin can assign roles to all operators
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator can assign roles to operators they created
        if ($user->hasRole('integrator') && $operator->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage the operator's charging points.
     */
    public function manageChargingPoints(User $user, User $operator)
    {
        // Admin can manage all operators' charging points
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator can manage operators' charging points they created
        if ($user->hasRole('integrator') && $operator->created_by === $user->id) {
            return true;
        }

        // Operator can manage their own charging points
        if ($user->hasRole('operator') && $user->id === $operator->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view QR codes for the charging point.
     */
    public function viewQrCode(User $user, ChargingPoint $chargingPoint): bool
    {
        // Super admins and admins can view QR codes for all charging points
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Integrators can view QR codes for charging points in their hierarchy
        if ($user->hasRole('integrator')) {
            // For now, allow integrators to view all QR codes
            return true;
        }

        // Partners can view QR codes for charging points in their hierarchy
        if ($user->hasRole('partner')) {
            // For now, allow partners to view all QR codes
            return true;
        }

        // Operators can view QR codes for their own charging points
        if ($user->hasRole('operator')) {
            return $chargingPoint->user_id === $user->id;
        }

        return false;
    }
}