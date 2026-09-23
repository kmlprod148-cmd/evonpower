<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ChargingPoint;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChargingPointPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * This method is called before any other policy method.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Admins and super-admins can do everything
        // Use case-insensitive role checking
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        if (in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower)) {
            return true;
        }

        // Return null to continue to the specific policy method
        return null;
    }

    /**
     * Determine whether the user can view any charging points.
     */
    public function viewAny(User $user)
    {
        // Use case-insensitive role checking
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        
        // Admin can view all charging points
        if (in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower)) {
            return true;
        }

        // Integrator can view their charging points
        if (in_array('integrator', $userRolesLower)) {
            return true;
        }

        // Partner can view their charging points
        if (in_array('partner', $userRolesLower)) {
            return true;
        }

        // Operator can view their charging points
        if (in_array('operator', $userRolesLower)) {
            return true;
        }

        // Clients (user role) avec view_public_charging_points peuvent parcourir les bornes pour réserver
        if ($user->can('view_public_charging_points')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the charging point.
     */
    public function view(User $user, ChargingPoint $chargingPoint)
    {
        // Use case-insensitive role checking
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        
        // Admin can view all charging points
        if (in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower)) {
            return true;
        }

        // Integrator: can view their charging points and those of their operators/partners/groups
        if (in_array('integrator', $userRolesLower)) {
            $integratorId = $user->integrator_id ?? \App\Models\Integrator::where('user_id', $user->id)->value('id');
            if (!$integratorId) {
                return false;
            }
            
            // 1. Direct integrator_id match
            if ($chargingPoint->integrator_id === $integratorId) {
                return true;
            }
            
            // 2. Created by an operator of this integrator (via user_id)
            if ($chargingPoint->user_id && $chargingPoint->user && 
                $chargingPoint->user->integrator_id === $integratorId && 
                $chargingPoint->user->hasRole('operator')) {
                return true;
            }
            
            // 3. Assigned to a partner of this integrator
            if ($chargingPoint->partner_id && $chargingPoint->partner && 
                $chargingPoint->partner->integrator_id === $integratorId) {
                return true;
            }
            
            // 4. In a group belonging to a partner of this integrator
            if ($chargingPoint->group_id && $chargingPoint->group) {
                if ($chargingPoint->group->integrator_id === $integratorId) {
                    return true;
                }
                if ($chargingPoint->group->partner_id && $chargingPoint->group->partner && 
                    $chargingPoint->group->partner->integrator_id === $integratorId) {
                    return true;
                }
            }
            
            // 5. Created by field pointing to an operator of this integrator
            if ($chargingPoint->created_by || $chargingPoint->created_by_id) {
                $createdById = $chargingPoint->created_by_id ?? $chargingPoint->created_by;
                $creatorUser = \App\Models\User::find($createdById);
                if ($creatorUser && $creatorUser->integrator_id === $integratorId && 
                    $creatorUser->hasRole('operator')) {
                    return true;
                }
            }
            
            return false;
        }

        // Partner can view their charging points (direct or via group)
        if (in_array('partner', $userRolesLower) && $user->partner_id) {
            if ($chargingPoint->partner_id === $user->partner_id) {
                return true;
            }
            if ($chargingPoint->group && $chargingPoint->group->partner_id === $user->partner_id) {
                return true;
            }
            return false;
        }

        // Operator can view their charging points (created by them or in groups they manage or all integrator's points)
        if (in_array('operator', $userRolesLower)) {
            // Direct ownership
            $isCreatedByOperator = ($chargingPoint->created_by === $user->id) ||
                                  ($chargingPoint->created_by_id === $user->id) ||
                                  ($chargingPoint->user_id === $user->id);
            if ($isCreatedByOperator) {
                return true;
            }
            
            // In groups managed by this operator
            if ($chargingPoint->group_id && \App\Models\Group::where('id', $chargingPoint->group_id)->where('user_id', $user->id)->exists()) {
                return true;
            }
            
            // Operators attached to an integrator can see all charging points of that integrator
            if ($user->integrator_id && $chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            
            return false;
        }

        // Clients avec view_public_charging_points : peuvent voir les bornes pour réserver
        if ($user->can('view_public_charging_points')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create charging points.
     */
    public function create(User $user)
    {
        // Use case-insensitive role checking
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        
        // Admin can create charging points
        if (in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower)) {
            return true;
        }

        // Integrator can create charging points
        if (in_array('integrator', $userRolesLower)) {
            return true;
        }

        // Partner can create charging points
        if (in_array('partner', $userRolesLower)) {
            return true;
        }

        // Operator can create charging points
        if (in_array('operator', $userRolesLower)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the charging point.
     */
    public function update(User $user, ChargingPoint $chargingPoint)
    {
        // Use case-insensitive role checking
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        
        // Admin can update all charging points
        if (in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower)) {
            return true;
        }

        // Integrator: can update ONLY their own charging points and those of their operators/partners
        if (in_array('integrator', $userRolesLower)) {
            // Leur propre charging point
            if ($chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            // Charging points de leurs opérateurs
            if ($chargingPoint->user && 
                $chargingPoint->user->integrator_id === $user->integrator_id && 
                $chargingPoint->user->hasRole('operator')) {
                return true;
            }
            // Charging points de leurs partenaires
            if ($chargingPoint->partner && 
                $chargingPoint->partner->integrator_id === $user->integrator_id) {
                return true;
            }
            return false;
        }

        // Partner can update their charging points
        if (in_array('partner', $userRolesLower) && $chargingPoint->partner_id === $user->partner_id) {
            return true;
        }

        // Operator can update ONLY their own charging points (created by them)
        if (in_array('operator', $userRolesLower)) {
            // Check if charging point was created by this operator
            $isCreatedByOperator = ($chargingPoint->created_by === $user->id) ||
                                  ($chargingPoint->created_by_id === $user->id) ||
                                  ($chargingPoint->user_id === $user->id);
            
            return $isCreatedByOperator;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the charging point.
     */
    public function delete(User $user, ChargingPoint $chargingPoint)
    {
        // Use case-insensitive role checking
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        
        // Admin can delete all charging points
        if (in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower)) {
            return true;
        }

        // Integrator: can delete ONLY their own charging points and those of their operators/partners
        if (in_array('integrator', $userRolesLower)) {
            if ($chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            if ($chargingPoint->user && 
                $chargingPoint->user->integrator_id === $user->integrator_id && 
                $chargingPoint->user->hasRole('operator')) {
                return true;
            }
            if ($chargingPoint->partner && 
                $chargingPoint->partner->integrator_id === $user->integrator_id) {
                return true;
            }
            return false;
        }

        // Partner can delete their charging points
        if (in_array('partner', $userRolesLower) && $chargingPoint->partner_id === $user->partner_id) {
            return true;
        }

        // Operator can delete ONLY their own charging points (created by them)
        if (in_array('operator', $userRolesLower)) {
            // Check if charging point was created by this operator
            $isCreatedByOperator = ($chargingPoint->created_by === $user->id) ||
                                  ($chargingPoint->created_by_id === $user->id) ||
                                  ($chargingPoint->user_id === $user->id);
            
            return $isCreatedByOperator;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the charging point.
     */
    public function restore(User $user, ChargingPoint $chargingPoint)
    {
        // Use case-insensitive role checking
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        
        // Admin can restore all charging points
        if (in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower)) {
            return true;
        }

        // Integrator: can restore ONLY their own charging points and those of their operators/partners
        if (in_array('integrator', $userRolesLower)) {
            if ($chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            if ($chargingPoint->user && 
                $chargingPoint->user->integrator_id === $user->integrator_id && 
                $chargingPoint->user->hasRole('operator')) {
                return true;
            }
            if ($chargingPoint->partner && 
                $chargingPoint->partner->integrator_id === $user->integrator_id) {
                return true;
            }
            return false;
        }

        // Partner can restore their charging points
        if (in_array('partner', $userRolesLower) && $chargingPoint->partner_id === $user->partner_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the charging point.
     */
    public function forceDelete(User $user, ChargingPoint $chargingPoint)
    {
        // Use case-insensitive role checking
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        // Only admin can permanently delete charging points
        return in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower);
    }

    /**
     * Determine whether the user can manage the charging point's sessions.
     */
    public function manageSessions(User $user, ChargingPoint $chargingPoint)
    {
        // Use case-insensitive role checking
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        
        // Admin can manage all charging points' sessions
        if (in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower)) {
            return true;
        }

        // Integrator: can manage sessions ONLY for their own charging points and those of their operators/partners
        if (in_array('integrator', $userRolesLower)) {
            if ($chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            if ($chargingPoint->user && 
                $chargingPoint->user->integrator_id === $user->integrator_id && 
                $chargingPoint->user->hasRole('operator')) {
                return true;
            }
            if ($chargingPoint->partner && 
                $chargingPoint->partner->integrator_id === $user->integrator_id) {
                return true;
            }
            return false;
        }

        // Partner can manage their charging points' sessions
        if (in_array('partner', $userRolesLower) && $chargingPoint->partner_id === $user->partner_id) {
            return true;
        }

        // Operator can manage their charging points' sessions
        if (in_array('operator', $userRolesLower) && $chargingPoint->integrator_id === $user->integrator_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage the charging point's reservations.
     */
    public function manageReservations(User $user, ChargingPoint $chargingPoint)
    {
        // Admin can manage all charging points' reservations
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator: can manage reservations ONLY for their own charging points and those of their operators/partners
        if ($user->hasRole('integrator')) {
            if ($chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            if ($chargingPoint->user && 
                $chargingPoint->user->integrator_id === $user->integrator_id && 
                $chargingPoint->user->hasRole('operator')) {
                return true;
            }
            if ($chargingPoint->partner && 
                $chargingPoint->partner->integrator_id === $user->integrator_id) {
                return true;
            }
            return false;
        }

        // Partner can manage their charging points' reservations
        if ($user->hasRole('partner') && $chargingPoint->partner_id === $user->partner_id) {
            return true;
        }

        // Operator can manage their charging points' reservations
        if ($user->hasRole('operator') && $chargingPoint->integrator_id === $user->integrator_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the charging point's statistics.
     */
    public function viewStatistics(User $user, ChargingPoint $chargingPoint)
    {
        // Admin can view all charging points' statistics
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator: can view statistics ONLY for their own charging points and those of their operators/partners
        if ($user->hasRole('integrator')) {
            if ($chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            if ($chargingPoint->user && 
                $chargingPoint->user->integrator_id === $user->integrator_id && 
                $chargingPoint->user->hasRole('operator')) {
                return true;
            }
            if ($chargingPoint->partner && 
                $chargingPoint->partner->integrator_id === $user->integrator_id) {
                return true;
            }
            return false;
        }

        // Partner can view their charging points' statistics
        if ($user->hasRole('partner') && $chargingPoint->partner_id === $user->partner_id) {
            return true;
        }

        // Operator can view their charging points' statistics
        if ($user->hasRole('operator') && $chargingPoint->integrator_id === $user->integrator_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can control the charging point (start/stop charging).
     */
    public function control(User $user, ChargingPoint $chargingPoint)
    {
        // Admin can control all charging points
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator: can control ONLY their own charging points and those of their operators/partners
        if ($user->hasRole('integrator')) {
            if ($chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            if ($chargingPoint->user && 
                $chargingPoint->user->integrator_id === $user->integrator_id && 
                $chargingPoint->user->hasRole('operator')) {
                return true;
            }
            if ($chargingPoint->partner && 
                $chargingPoint->partner->integrator_id === $user->integrator_id) {
                return true;
            }
            return false;
        }

        // Partner can control their charging points
        if ($user->hasRole('partner') && $chargingPoint->partner_id === $user->partner_id) {
            return true;
        }

        // Operator can control ONLY their own charging points (created by them)
        if ($user->hasRole('operator')) {
            $isCreatedByOperator = ($chargingPoint->created_by === $user->id) ||
                                  ($chargingPoint->created_by_id === $user->id) ||
                                  ($chargingPoint->user_id === $user->id);
            
            return $isCreatedByOperator;
        }

        return false;
    }

    /**
     * Determine whether the user can configure the charging point.
     */
    public function configure(User $user, ChargingPoint $chargingPoint)
    {
        // Admin can configure all charging points
        if ($user->hasRole('admin')) {
            return true;
        }

        // Integrator: can configure ONLY their own charging points and those of their operators/partners
        if ($user->hasRole('integrator')) {
            if ($chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            if ($chargingPoint->user && 
                $chargingPoint->user->integrator_id === $user->integrator_id && 
                $chargingPoint->user->hasRole('operator')) {
                return true;
            }
            if ($chargingPoint->partner && 
                $chargingPoint->partner->integrator_id === $user->integrator_id) {
                return true;
            }
            return false;
        }

        // Partner can configure their charging points
        if ($user->hasRole('partner') && $chargingPoint->partner_id === $user->partner_id) {
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

        // Integrators: can view QR codes ONLY for their own charging points and those of their operators/partners
        if ($user->hasRole('integrator')) {
            if ($chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            if ($chargingPoint->user && 
                $chargingPoint->user->integrator_id === $user->integrator_id && 
                $chargingPoint->user->hasRole('operator')) {
                return true;
            }
            if ($chargingPoint->partner && 
                $chargingPoint->partner->integrator_id === $user->integrator_id) {
                return true;
            }
            return false;
        }

        // Partners can view QR codes for charging points in their hierarchy
        if ($user->hasRole('partner')) {
            // Check if the charging point belongs to this partner
            return $chargingPoint->partner_id === $user->partner_id;
        }

        // Operators can view QR codes for ONLY their own charging points (created by them)
        if ($user->hasRole('operator')) {
            $isCreatedByOperator = ($chargingPoint->created_by === $user->id) ||
                                  ($chargingPoint->created_by_id === $user->id) ||
                                  ($chargingPoint->user_id === $user->id);
            
            return $isCreatedByOperator;
        }

        return false;
    }
}