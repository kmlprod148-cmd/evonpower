<?php

namespace App\Services;

use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class HierarchicalPermissionService
{
    /**
     * Apply hierarchical filtering to charging points query
     */
    public function filterChargingPointsForUser(Builder $query, User $user): Builder
    {
        // Admin: See everything
        if ($user->hasRole(['admin', 'super_admin'])) {
            Log::info('Admin charging points access - no filtering', ['user_id' => $user->id]);
            return $query;
        }

        // Integrator: ONLY their own charging points and those of their operators/partners
        if ($user->hasRole('integrator')) {
            Log::info('Integrator charging points filtering', [
                'user_id' => $user->id,
                'integrator_id' => $user->integrator_id
            ]);
            
            return $query->where(function ($q) use ($user) {
                // Charging points appartenant directement à cet intégrateur
                $q->where('integrator_id', $user->integrator_id)
                  // OR charging points créés/assignés aux opérateurs de cet intégrateur
                  ->orWhereHas('user', function ($userQuery) use ($user) {
                      $userQuery->where('integrator_id', $user->integrator_id)
                               ->where('role', 'operator');
                  })
                  // OR charging points assignés aux partenaires de cet intégrateur
                  ->orWhereHas('partner', function ($partnerQuery) use ($user) {
                      $partnerQuery->where('integrator_id', $user->integrator_id);
                  });
            });
        }

        // Operator: ONLY their own charging points (created by them)
        if ($user->hasRole('operator')) {
            Log::info('Operator charging points filtering', [
                'user_id' => $user->id,
                'integrator_id' => $user->integrator_id
            ]);
            
            return $query->where(function ($q) use ($user) {
                // Charging points created by this operator
                $q->where('created_by', $user->id)
                  ->orWhere('created_by_id', $user->id)
                  ->orWhere('user_id', $user->id);
            });
        }

        // Partner: Only charging points associated with their partner
        if ($user->hasRole('partner')) {
            Log::info('Partner charging points filtering', [
                'user_id' => $user->id,
                'partner_id' => $user->partner_id
            ]);
            
            return $query->where('partner_id', $user->partner_id);
        }

        // Default: No access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply hierarchical filtering to business profiles query
     */
    public function filterBusinessProfilesForUser(Builder $query, User $user): Builder
    {
        // Admin: See everything
        if ($user->hasRole(['admin', 'super_admin'])) {
            return $query;
        }

        // Integrator: Only their own business profiles
        if ($user->hasRole('integrator')) {
            Log::info('Integrator business profiles filtering', [
                'user_id' => $user->id,
                'integrator_id' => $user->integrator_id
            ]);
            
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('integrator_id', $user->integrator_id);
            });
        }

        // Operator: Read-only access to their inherited profile
        if ($user->hasRole('operator')) {
            Log::info('Operator business profiles filtering (read-only)', [
                'user_id' => $user->id
            ]);
            
            return $query->where(function ($q) use ($user) {
                // Their own profile or inherited from integrator
                $q->where('user_id', $user->id)
                  ->orWhere('integrator_id', $user->integrator_id);
            });
        }

        // Partner: Only their partner's business profile
        if ($user->hasRole('partner')) {
            return $query->where('partner_id', $user->partner_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply hierarchical filtering to pricing plans query
     */
    public function filterPricingPlansForUser(Builder $query, User $user): Builder
    {
        // Admin: See everything
        if ($user->hasRole(['admin', 'super_admin'])) {
            return $query;
        }

        // Integrator: Only their own pricing plans
        if ($user->hasRole('integrator')) {
            Log::info('Integrator pricing plans filtering', [
                'user_id' => $user->id,
                'integrator_id' => $user->integrator_id
            ]);
            
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('integrator_id', $user->integrator_id);
            });
        }

        // Operator: Read-only access to available plans
        if ($user->hasRole('operator')) {
            Log::info('Operator pricing plans filtering (read-only)', [
                'user_id' => $user->id
            ]);
            
            return $query->where(function ($q) use ($user) {
                // Plans from their integrator or public plans
                $q->where('integrator_id', $user->integrator_id)
                  ->orWhere('is_public', true);
            });
        }

        // Partner: Only their partner's pricing plans
        if ($user->hasRole('partner')) {
            return $query->where('partner_id', $user->partner_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply hierarchical filtering to reservations query
     */
    public function filterReservationsForUser(Builder $query, User $user): Builder
    {
        // Admin: See everything
        if ($user->hasRole(['admin', 'super_admin'])) {
            return $query;
        }

        // Integrator: All reservations EXCEPT those for charging points belonging to other integrators
        if ($user->hasRole('integrator')) {
            Log::info('Integrator reservations filtering', [
                'user_id' => $user->id,
                'integrator_id' => $user->integrator_id
            ]);
            
            return $query->where(function ($q) use ($user) {
                // Exclure uniquement les réservations pour les charging points d'autres intégrateurs
                $q->whereDoesntHave('chargingPoint', function ($cpQuery) use ($user) {
                    // Exclure les charging points appartenant à d'autres intégrateurs
                    $cpQuery->whereNotNull('integrator_id')
                           ->where('integrator_id', '!=', $user->integrator_id);
                })
                // OR reservations sans charging point associé (accessibles)
                ->orWhereNull('charging_point_id');
            });
        }

        // Operator: Their own reservations and reservations for their charging points
        if ($user->hasRole('operator')) {
            Log::info('Operator reservations filtering', [
                'user_id' => $user->id
            ]);
            
            return $query->where(function ($q) use ($user) {
                // Their own reservations
                $q->where('user_id', $user->id)
                  // OR reservations for charging points created by this operator
                  ->orWhereHas('chargingPoint', function ($cpQuery) use ($user) {
                      $cpQuery->where(function ($cpSubQuery) use ($user) {
                          $cpSubQuery->where('created_by', $user->id)
                                    ->orWhere('created_by_id', $user->id)
                                    ->orWhere('user_id', $user->id);
                      });
                  });
            });
        }

        // Partner: Only their own reservations
        if ($user->hasRole('partner')) {
            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply hierarchical filtering to users query (for user management)
     */
    public function filterUsersForUser(Builder $query, User $user): Builder
    {
        // Admin: See everyone
        if ($user->hasRole(['admin', 'super_admin'])) {
            return $query;
        }

        // Integrator: Only operators under their integrator
        if ($user->hasRole('integrator')) {
            Log::info('Integrator users filtering', [
                'user_id' => $user->id,
                'integrator_id' => $user->integrator_id
            ]);
            
            return $query->where(function ($q) use ($user) {
                $q->where('integrator_id', $user->integrator_id)
                  ->where('role', 'operator');
            });
        }

        // Operator: Cannot manage other users
        if ($user->hasRole('operator')) {
            return $query->where('id', $user->id); // Only themselves
        }

        // Partner: Cannot manage other users
        if ($user->hasRole('partner')) {
            return $query->where('id', $user->id); // Only themselves
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Check if user can access a specific charging point
     */
    public function canAccessChargingPoint(User $user, ChargingPoint $chargingPoint): bool
    {
        // Admin: Full access
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Integrator: Can access ONLY their own charging points and those of their operators/partners
        if ($user->hasRole('integrator')) {
            // Leur propre charging point (appartenant à leur intégrateur)
            if ($chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            
            // Charging points créés/assignés à leurs opérateurs
            if ($chargingPoint->user && 
                $chargingPoint->user->integrator_id === $user->integrator_id && 
                $chargingPoint->user->hasRole('operator')) {
                return true;
            }
            
            // Charging points assignés à leurs partenaires
            if ($chargingPoint->partner && 
                $chargingPoint->partner->integrator_id === $user->integrator_id) {
                return true;
            }
            
            return false;
        }

        // Operator: ONLY their own charging points (created by them)
        if ($user->hasRole('operator')) {
            $isCreatedByOperator = ($chargingPoint->created_by === $user->id) ||
                                  ($chargingPoint->created_by_id === $user->id) ||
                                  ($chargingPoint->user_id === $user->id);
            
            return $isCreatedByOperator;
        }

        // Partner: Only charging points associated with their partner
        if ($user->hasRole('partner')) {
            return $chargingPoint->partner_id === $user->partner_id;
        }

        return false;
    }

    /**
     * Check if user can access a specific business profile
     */
    public function canAccessBusinessProfile(User $user, BusinessProfile $businessProfile): bool
    {
        // Admin: Full access
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Integrator: Only their own business profiles
        if ($user->hasRole('integrator')) {
            return $businessProfile->user_id === $user->id ||
                   $businessProfile->integrator_id === $user->integrator_id;
        }

        // Operator: Read-only access to their inherited profile
        if ($user->hasRole('operator')) {
            return $businessProfile->user_id === $user->id ||
                   $businessProfile->integrator_id === $user->integrator_id;
        }

        // Partner: Only their partner's business profile
        if ($user->hasRole('partner')) {
            return $businessProfile->partner_id === $user->partner_id;
        }

        return false;
    }

    /**
     * Check if user can modify a specific business profile
     */
    public function canModifyBusinessProfile(User $user, BusinessProfile $businessProfile): bool
    {
        // Admin: Full access
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Integrator: Only their own business profiles
        if ($user->hasRole('integrator')) {
            return $businessProfile->user_id === $user->id ||
                   $businessProfile->integrator_id === $user->integrator_id;
        }

        // Operator: No modification rights
        if ($user->hasRole('operator')) {
            return false;
        }

        // Partner: Limited modification rights
        if ($user->hasRole('partner')) {
            return $businessProfile->partner_id === $user->partner_id;
        }

        return false;
    }

    /**
     * Check if user can access a specific reservation
     */
    public function canAccessReservation(User $user, Reservation $reservation): bool
    {
        // Admin: Full access
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Integrator: Can access all reservations EXCEPT those for charging points belonging to other integrators
        if ($user->hasRole('integrator')) {
            // Si la réservation est pour un charging point d'un autre intégrateur, refuser
            if ($reservation->chargingPoint && 
                $reservation->chargingPoint->integrator_id && 
                $user->integrator_id && 
                $reservation->chargingPoint->integrator_id != $user->integrator_id) {
                return false;
            }
            // Sinon, autoriser (comme un admin)
            return true;
        }

        // Operator: Their own reservations and reservations for their charging points
        if ($user->hasRole('operator')) {
            // Their own reservation
            if ($reservation->user_id === $user->id) {
                return true;
            }
            
            // Reservation for a charging point created by this operator
            if ($reservation->chargingPoint) {
                $chargingPoint = $reservation->chargingPoint;
                $isOwnChargingPoint = ($chargingPoint->created_by === $user->id) ||
                                     ($chargingPoint->created_by_id === $user->id) ||
                                     ($chargingPoint->user_id === $user->id);
                
                return $isOwnChargingPoint;
            }
            
            return false;
        }

        // Partner: Only their own reservations
        if ($user->hasRole('partner')) {
            return $reservation->user_id === $user->id;
        }

        return false;
    }

    /**
     * Get allowed actions for a user role
     */
    public function getAllowedActions(User $user): array
    {
        if ($user->hasRole(['admin', 'super_admin'])) {
            return [
                'charging_points' => ['view', 'create', 'edit', 'delete'],
                'business_profiles' => ['view', 'create', 'edit', 'delete'],
                'pricing_plans' => ['view', 'create', 'edit', 'delete'],
                'reservations' => ['view', 'create', 'edit', 'delete'],
                'users' => ['view', 'create', 'edit', 'delete'],
                'reports' => ['view', 'export'],
                'system' => ['configure', 'backup', 'restore']
            ];
        }

        if ($user->hasRole('integrator')) {
            return [
                'charging_points' => ['view', 'create', 'edit', 'delete'],
                'business_profiles' => ['view', 'create', 'edit'],
                'pricing_plans' => ['view', 'create', 'edit'],
                'reservations' => ['view'],
                'users' => ['view', 'create', 'edit'], // Only operators
                'reports' => ['view'],
                'ocpp_tags' => ['view', 'create', 'edit', 'delete'],
                'charging_control' => ['start', 'stop']
            ];
        }

        if ($user->hasRole('operator')) {
            return [
                'charging_points' => ['view', 'create', 'edit'],
                'business_profiles' => ['view'], // Read-only
                'pricing_plans' => ['view'], // Read-only
                'reservations' => ['view', 'create'],
                'users' => [], // No user management
                'reports' => ['view'], // Limited reports
                'ocpp_tags' => ['view', 'create'], // Limited OCPP tag management
                'charging_control' => ['start', 'stop'] // Can control their own charging points
            ];
        }

        if ($user->hasRole('partner')) {
            return [
                'charging_points' => ['view'],
                'business_profiles' => ['view', 'edit'], // Limited
                'pricing_plans' => ['view'],
                'reservations' => ['view', 'create'],
                'users' => [], // No user management
                'reports' => ['view'] // Limited reports
            ];
        }

        return [];
    }

    /**
     * Log access attempt for audit
     */
    public function logAccessAttempt(User $user, string $resource, string $action, bool $granted, ?int $resourceId = null): void
    {
        Log::info('Hierarchical access attempt', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'integrator_id' => $user->integrator_id,
            'partner_id' => $user->partner_id,
            'resource' => $resource,
            'action' => $action,
            'resource_id' => $resourceId,
            'access_granted' => $granted,
            'timestamp' => now()
        ]);
    }
}
