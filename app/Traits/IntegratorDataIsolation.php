<?php

namespace App\Traits;

use App\Services\IntegratorDataValidationService;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\Transaction;
use Illuminate\Http\Request;

trait IntegratorDataIsolation
{
    protected IntegratorDataValidationService $integratorValidationService;

    public function __construct()
    {
        $this->integratorValidationService = app(IntegratorDataValidationService::class);
    }

    /**
     * Vérifier l'accès à un utilisateur pour un intégrateur
     */
    protected function canIntegratorAccessUser(User $targetUser): bool
    {
        return $this->integratorValidationService->canIntegratorAccessUser(auth()->user(), $targetUser);
    }

    /**
     * Vérifier l'accès à un point de charge pour un intégrateur
     */
    protected function canIntegratorAccessChargingPoint(ChargingPoint $chargingPoint): bool
    {
        return $this->integratorValidationService->canIntegratorAccessChargingPoint(auth()->user(), $chargingPoint);
    }

    /**
     * Vérifier l'accès à une réservation pour un intégrateur
     */
    protected function canIntegratorAccessReservation(Reservation $reservation): bool
    {
        return $this->integratorValidationService->canIntegratorAccessReservation(auth()->user(), $reservation);
    }

    /**
     * Vérifier l'accès à une transaction pour un intégrateur
     */
    protected function canIntegratorAccessTransaction(Transaction $transaction): bool
    {
        return $this->integratorValidationService->canIntegratorAccessTransaction(auth()->user(), $transaction);
    }

    /**
     * Appliquer le filtrage des utilisateurs pour un intégrateur
     */
    protected function applyIntegratorUserFilter($query)
    {
        return $this->integratorValidationService->filterUsersForIntegrator(auth()->user(), $query);
    }

    /**
     * Appliquer le filtrage des points de charge pour un intégrateur
     */
    protected function applyIntegratorChargingPointFilter($query)
    {
        return $this->integratorValidationService->filterChargingPointsForIntegrator(auth()->user(), $query);
    }

    /**
     * Appliquer le filtrage des réservations pour un intégrateur
     */
    protected function applyIntegratorReservationFilter($query)
    {
        return $this->integratorValidationService->filterReservationsForIntegrator(auth()->user(), $query);
    }

    /**
     * Appliquer le filtrage des transactions pour un intégrateur
     */
    protected function applyIntegratorTransactionFilter($query)
    {
        return $this->integratorValidationService->filterTransactionsForIntegrator(auth()->user(), $query);
    }

    /**
     * Vérifier et rediriger si l'accès n'est pas autorisé
     */
    protected function ensureIntegratorAccess($resource, string $resourceType, string $action = 'access')
    {
        $user = auth()->user();
        
        if (!$user->hasRole('integrator')) {
            return true; // Pas un intégrateur, pas de restriction
        }

        $hasAccess = false;
        
        switch ($resourceType) {
            case 'user':
                $hasAccess = $this->canIntegratorAccessUser($resource);
                break;
            case 'charging_point':
                $hasAccess = $this->canIntegratorAccessChargingPoint($resource);
                break;
            case 'reservation':
                $hasAccess = $this->canIntegratorAccessReservation($resource);
                break;
            case 'transaction':
                $hasAccess = $this->canIntegratorAccessTransaction($resource);
                break;
        }

        if (!$hasAccess) {
            $this->integratorValidationService->logUnauthorizedAccess(
                $user, 
                $resourceType, 
                $resource->id ?? 'unknown', 
                $action
            );
            
            return false;
        }

        return true;
    }

    /**
     * Middleware helper pour vérifier l'accès
     */
    protected function checkIntegratorAccess($resource, string $resourceType, string $action = 'access')
    {
        if (!$this->ensureIntegratorAccess($resource, $resourceType, $action)) {
            abort(403, 'Accès non autorisé. Vous ne pouvez pas accéder à cette ressource.');
        }
    }
}
