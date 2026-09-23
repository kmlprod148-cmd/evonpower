<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Reservation;
use App\Enums\ReservationStatus;
use Illuminate\Auth\Access\Response;

class ReservationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin and super-admin can view all reservations
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        // Integrators can view their own and their operators' reservations
        if ($user->hasRole('integrator')) {
            return true;
        }
        
        // Operators can view their own reservations
        if ($user->hasRole('operator')) {
            return true;
        }
        
        // Partners can view their own reservations
        if ($user->hasRole('partner')) {
            return true;
        }
        
        // Clients can only view their own reservations (restricted)
        if ($user->hasRole('client')) {
            return true; // But will be filtered in controller to only show their own
        }
        
        // Regular users can view their own reservations
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        // Admin and super-admin can view all reservations
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        // Integrators: can view all reservations EXCEPT those for charging points belonging to other integrators
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
        
        // Operators can view their own reservations and reservations for their charging points
        if ($user->hasRole('operator')) {
            // Their own reservation
            if ($reservation->user_id === $user->id) {
                return true;
            }
            
            // Reservation for a charging point managed by this operator
            if ($reservation->chargingPoint) {
                $chargingPoint = $reservation->chargingPoint;
                // Check if operator is the manager of the charging point
                if ($chargingPoint->user_id === $user->id) {
                    return true;
                }
                // Check if charging point is in a group managed by this operator
                if ($chargingPoint->group && $chargingPoint->group->user_id === $user->id) {
                    return true;
                }
            }
        }
        
        // Partners can view reservations for their charging points
        if ($user->hasRole('partner') && $user->partner_id) {
            if ($reservation->chargingPoint) {
                $chargingPoint = $reservation->chargingPoint;
                // Check if charging point is directly linked to the partner
                if ($chargingPoint->partner_id === $user->partner_id) {
                    return true;
                }
                // Check if charging point is in a group belonging to the partner
                if ($chargingPoint->group && $chargingPoint->group->partner_id === $user->partner_id) {
                    return true;
                }
            }
        }
        
        // Clients can view their own reservations (user_id) OR guest reservations (guest_email/guest_phone)
        if ($user->hasRole(['client', 'user'])) {
            if ($user->id === $reservation->user_id) {
                return true;
            }
            // Réservations invitées : email ou téléphone correspondant
            if (!empty(trim($user->email ?? '')) && !empty(trim($reservation->guest_email ?? ''))) {
                if (strtolower(trim($user->email)) === strtolower(trim($reservation->guest_email))) {
                    return true;
                }
            }
            if (!empty(trim($user->phone ?? '')) && !empty(trim($reservation->guest_phone ?? ''))) {
                if (trim($user->phone) === trim($reservation->guest_phone)) {
                    return true;
                }
            }
            return false;
        }
        
        // Regular users can view their own reservations
        return $user->id === $reservation->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Clients can create reservations, but balance check will be done in service/controller
        return true; // Allow all authenticated users to create reservations
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Reservation $reservation): bool
    {
        // Admin and super-admin can update all reservations
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        // Integrators: can update all reservations EXCEPT those for charging points belonging to other integrators
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
        
        // Operators can update their own reservations and reservations for their charging points
        if ($user->hasRole('operator')) {
            // Their own reservation
            if ($reservation->user_id === $user->id) {
                return true;
            }
            
            // Reservation for a charging point managed by this operator
            if ($reservation->chargingPoint) {
                $chargingPoint = $reservation->chargingPoint;
                // Check if operator is the manager of the charging point
                if ($chargingPoint->user_id === $user->id) {
                    return true;
                }
                // Check if charging point is in a group managed by this operator
                if ($chargingPoint->group && $chargingPoint->group->user_id === $user->id) {
                    return true;
                }
            }
        }
        
        // Regular users can update their own reservations
        return $user->id === $reservation->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        // Admin and super-admin can delete all reservations
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        // Integrators: can delete all reservations EXCEPT those for charging points belonging to other integrators
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
        
        // Operators can delete their own reservations and reservations for their charging points
        if ($user->hasRole('operator')) {
            // Their own reservation
            if ($reservation->user_id === $user->id) {
                return true;
            }
            
            // Reservation for a charging point managed by this operator
            if ($reservation->chargingPoint) {
                $chargingPoint = $reservation->chargingPoint;
                // Check if operator is the manager of the charging point
                if ($chargingPoint->user_id === $user->id) {
                    return true;
                }
                // Check if charging point is in a group managed by this operator
                if ($chargingPoint->group && $chargingPoint->group->user_id === $user->id) {
                    return true;
                }
            }
        }
        
        // Regular users can delete their own reservations
        return $user->id === $reservation->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Reservation $reservation): bool
    {
        // Admin and super-admin can restore all reservations
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        return $user->id === $reservation->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Reservation $reservation): bool
    {
        // Admin and super-admin can permanently delete all reservations
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        return $user->id === $reservation->user_id;
    }

    /**
     * Determine whether the user can confirm the reservation.
     */
    public function confirm(User $user, Reservation $reservation): bool
    {
        // Admin and super-admin can confirm all reservations
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        // Integrators: can confirm all reservations EXCEPT those for charging points belonging to other integrators
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
        
        // Operators can confirm reservations for their own charging points
        // Utiliser la même logique que canAccessReservation() dans AdminReservationController
        if ($user->hasRole('operator')) {
            // Toujours autoriser les réservations personnelles de l'opérateur
            if ($reservation->user_id === $user->id) {
                return true;
            }
            
            $chargingPoint = $reservation->chargingPoint;
            
            if (!$chargingPoint) {
                return false;
            }
            
            // Vérifier si le point de charge est directement assigné à l'opérateur
            if ($chargingPoint->user_id === $user->id || 
                $chargingPoint->created_by === $user->id || 
                $chargingPoint->created_by_id === $user->id) {
                return true;
            }
            
            // Vérifier si le point de charge est dans un groupe géré par l'opérateur
            if ($chargingPoint->group_id) {
                $group = \App\Models\Group::find($chargingPoint->group_id);
                if ($group && $group->user_id === $user->id) {
                    return true;
                }
            }
            
            // Si l'opérateur a un intégrateur, vérifier si le point de charge appartient à cet intégrateur
            if ($user->integrator_id && $chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            
            return false;
        }
        
        return false;
    }

    /**
     * Determine whether the user can reject the reservation.
     */
    public function reject(User $user, Reservation $reservation): bool
    {
        // Admin and super-admin can reject all reservations
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        // Integrators: can reject all reservations EXCEPT those for charging points belonging to other integrators
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
        
        // Operators can reject reservations for their own charging points
        // Utiliser la même logique que confirm() pour garantir la cohérence
        if ($user->hasRole('operator')) {
            // Toujours autoriser les réservations personnelles de l'opérateur
            if ($reservation->user_id === $user->id) {
                return true;
            }
            
            $chargingPoint = $reservation->chargingPoint;
            
            if (!$chargingPoint) {
                return false;
            }
            
            // Vérifier si le point de charge est directement assigné à l'opérateur
            if ($chargingPoint->user_id === $user->id || 
                $chargingPoint->created_by === $user->id || 
                $chargingPoint->created_by_id === $user->id) {
                return true;
            }
            
            // Vérifier si le point de charge est dans un groupe géré par l'opérateur
            if ($chargingPoint->group_id) {
                $group = \App\Models\Group::find($chargingPoint->group_id);
                if ($group && $group->user_id === $user->id) {
                    return true;
                }
            }
            
            // Si l'opérateur a un intégrateur, vérifier si le point de charge appartient à cet intégrateur
            if ($user->integrator_id && $chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }
            
            return false;
        }
        
        return false;
    }

    /**
     * Determine whether the user can pay for the reservation.
     */
    public function pay(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id && $reservation->status === ReservationStatus::PENDING->value;
    }
}