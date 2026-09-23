<?php

namespace App\Services;

use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class IntegratorDataValidationService
{
    /**
     * Vérifier qu'un utilisateur appartient à l'intégrateur
     */
    public function canIntegratorAccessUser(User $integrator, User $targetUser): bool
    {
        if (!$integrator->hasRole('integrator') || !$integrator->integrator_id) {
            return false;
        }

        // L'intégrateur peut accéder à ses propres opérateurs
        if ($targetUser->integrator_id === $integrator->integrator_id && $targetUser->hasRole('operator')) {
            return true;
        }

        // L'intégrateur peut accéder à son propre compte
        if ($targetUser->id === $integrator->id) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier qu'un point de charge appartient à l'intégrateur
     */
    public function canIntegratorAccessChargingPoint(User $integrator, ChargingPoint $chargingPoint): bool
    {
        if (!$integrator->hasRole('integrator') || !$integrator->integrator_id) {
            return false;
        }

        // Le point de charge appartient directement à l'intégrateur
        if ($chargingPoint->integrator_id === $integrator->integrator_id) {
            return true;
        }

        // Le point de charge appartient à un opérateur de l'intégrateur
        if ($chargingPoint->user && 
            $chargingPoint->user->integrator_id === $integrator->integrator_id && 
            $chargingPoint->user->hasRole('operator')) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier qu'une réservation appartient à l'intégrateur
     */
    public function canIntegratorAccessReservation(User $integrator, Reservation $reservation): bool
    {
        if (!$integrator->hasRole('integrator') || !$integrator->integrator_id) {
            return false;
        }

        // La réservation appartient à l'intégrateur
        if ($reservation->user_id === $integrator->id) {
            return true;
        }

        // La réservation appartient à un opérateur de l'intégrateur
        if ($reservation->user && 
            $reservation->user->integrator_id === $integrator->integrator_id && 
            $reservation->user->hasRole('operator')) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier qu'une transaction appartient à l'intégrateur
     */
    public function canIntegratorAccessTransaction(User $integrator, Transaction $transaction): bool
    {
        if (!$integrator->hasRole('integrator') || !$integrator->integrator_id) {
            return false;
        }

        // La transaction implique directement l'intégrateur
        if ($transaction->user_id === $integrator->id) {
            return true;
        }

        // La transaction implique un opérateur de l'intégrateur
        if ($transaction->user && 
            $transaction->user->integrator_id === $integrator->integrator_id && 
            $transaction->user->hasRole('operator')) {
            return true;
        }

        // La transaction implique un point de charge de l'intégrateur
        if ($transaction->chargingPoint && 
            $this->canIntegratorAccessChargingPoint($integrator, $transaction->chargingPoint)) {
            return true;
        }

        return false;
    }

    /**
     * Filtrer les utilisateurs pour un intégrateur
     */
    public function filterUsersForIntegrator(User $integrator, $query)
    {
        if (!$integrator->hasRole('integrator') || !$integrator->integrator_id) {
            return $query->whereRaw('1 = 0'); // Aucun résultat
        }

        return $query->where(function($q) use ($integrator) {
            $q->where('id', $integrator->id) // L'intégrateur lui-même
              ->orWhere(function($subQ) use ($integrator) {
                  $subQ->where('integrator_id', $integrator->integrator_id)
                       ->whereHas('roles', function($roleQuery) {
                           $roleQuery->where('name', 'operator');
                       });
              });
        });
    }

    /**
     * Filtrer les points de charge pour un intégrateur
     */
    public function filterChargingPointsForIntegrator(User $integrator, $query)
    {
        if (!$integrator->hasRole('integrator') || !$integrator->integrator_id) {
            return $query->whereRaw('1 = 0'); // Aucun résultat
        }

        return $query->where(function($q) use ($integrator) {
            $q->where('integrator_id', $integrator->integrator_id)
              ->orWhereHas('user', function($userQuery) use ($integrator) {
                  $userQuery->where('integrator_id', $integrator->integrator_id)
                           ->whereHas('roles', function($roleQuery) {
                               $roleQuery->where('name', 'operator');
                           });
              });
        });
    }

    /**
     * Filtrer les réservations pour un intégrateur
     */
    public function filterReservationsForIntegrator(User $integrator, $query)
    {
        if (!$integrator->hasRole('integrator') || !$integrator->integrator_id) {
            return $query->whereRaw('1 = 0'); // Aucun résultat
        }

        return $query->where(function($q) use ($integrator) {
            $q->where('user_id', $integrator->id)
              ->orWhereHas('user', function($userQuery) use ($integrator) {
                  $userQuery->where('integrator_id', $integrator->integrator_id)
                           ->whereHas('roles', function($roleQuery) {
                               $roleQuery->where('name', 'operator');
                           });
              });
        });
    }

    /**
     * Filtrer les transactions pour un intégrateur
     */
    public function filterTransactionsForIntegrator(User $integrator, $query)
    {
        if (!$integrator->hasRole('integrator') || !$integrator->integrator_id) {
            return $query->whereRaw('1 = 0'); // Aucun résultat
        }

        return $query->where(function($q) use ($integrator) {
            $q->where('user_id', $integrator->id)
              ->orWhereHas('user', function($userQuery) use ($integrator) {
                  $userQuery->where('integrator_id', $integrator->integrator_id)
                           ->whereHas('roles', function($roleQuery) {
                               $roleQuery->where('name', 'operator');
                           });
              })
              ->orWhereHas('chargingPoint', function($cpQuery) use ($integrator) {
                  $cpQuery->where('integrator_id', $integrator->integrator_id)
                         ->orWhereHas('user', function($userQuery) use ($integrator) {
                             $userQuery->where('integrator_id', $integrator->integrator_id)
                                      ->whereHas('roles', function($roleQuery) {
                                          $roleQuery->where('name', 'operator');
                                      });
                         });
              });
        });
    }

    /**
     * Log des tentatives d'accès non autorisées
     */
    public function logUnauthorizedAccess(User $integrator, string $resourceType, $resourceId, string $action = 'access')
    {
        Log::warning('Unauthorized integrator access attempt', [
            'integrator_id' => $integrator->id,
            'integrator_name' => $integrator->name,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'action' => $action,
            'timestamp' => now()
        ]);
    }
}
