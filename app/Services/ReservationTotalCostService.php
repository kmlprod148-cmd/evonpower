<?php

namespace App\Services;

use App\Models\Reservation;
use App\Services\ReservationCostCalculationService;
use Illuminate\Support\Facades\Log;

/**
 * Service pour gérer le total_cost des réservations
 * S'assure que le total_cost est toujours correctement calculé et mis à jour
 */
class ReservationTotalCostService
{
    protected $costService;
    
    public function __construct(ReservationCostCalculationService $costService)
    {
        $this->costService = $costService;
    }
    
    /**
     * Met à jour le total_cost d'une réservation
     */
    public function updateReservationTotalCost(Reservation $reservation, float $cost = null): bool
    {
        try {
            // Si aucun coût n'est fourni, le calculer
            if ($cost === null) {
                $cost = $this->costService->calculateReservationCost($reservation);
                
                if ($cost <= 0) {
                    $cost = $this->costService->calculateCostWithDefaults($reservation, 1.0);
                }
            }
            
            if ($cost <= 0) {
                Log::warning("Impossible de calculer un coût valide pour la réservation #{$reservation->id}");
                return false;
            }
            
            // Mettre à jour tous les champs de coût
            $reservation->update([
                'estimated_cost' => $cost,
                'actual_cost' => $cost,
                'total_cost' => $cost
            ]);
            
            Log::info("Total cost mis à jour pour la réservation #{$reservation->id}", [
                'reservation_id' => $reservation->id,
                'total_cost' => $cost
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour du total_cost pour la réservation #{$reservation->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifie et corrige le total_cost d'une réservation si nécessaire
     */
    public function validateAndFixReservationTotalCost(Reservation $reservation): bool
    {
        // Vérifier si le total_cost est correct
        if ($reservation->total_cost && 
            $reservation->total_cost > 0 && 
            $reservation->total_cost == $reservation->estimated_cost) {
            return true; // Déjà correct
        }
        
        // Corriger le total_cost
        return $this->updateReservationTotalCost($reservation);
    }
    
    /**
     * Corrige toutes les réservations avec des problèmes de total_cost
     */
    public function fixAllReservationsWithInvalidTotalCost(): array
    {
        $results = [
            'total_checked' => 0,
            'fixed' => 0,
            'errors' => 0,
            'error_details' => []
        ];
        
        try {
            // Récupérer toutes les réservations avec des problèmes
            $reservations = Reservation::where(function($query) {
                $query->whereNull('total_cost')
                      ->orWhere('total_cost', 0)
                      ->orWhere('total_cost', '!=', \DB::raw('estimated_cost'));
            })->get();
            
            $results['total_checked'] = $reservations->count();
            
            foreach ($reservations as $reservation) {
                try {
                    if ($this->updateReservationTotalCost($reservation)) {
                        $results['fixed']++;
                    } else {
                        $results['errors']++;
                        $results['error_details'][] = "Réservation #{$reservation->id}: Impossible de calculer un coût valide";
                    }
                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['error_details'][] = "Réservation #{$reservation->id}: " . $e->getMessage();
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la correction des réservations: " . $e->getMessage());
            $results['errors']++;
            $results['error_details'][] = "Erreur générale: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Obtient le coût total correct d'une réservation
     */
    public function getCorrectTotalCost(Reservation $reservation): float
    {
        $cost = $this->costService->calculateReservationCost($reservation);
        
        if ($cost <= 0) {
            $cost = $this->costService->calculateCostWithDefaults($reservation, 1.0);
        }
        
        return max(0, $cost);
    }
}
