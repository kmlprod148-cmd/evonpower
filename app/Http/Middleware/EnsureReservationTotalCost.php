<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Services\ReservationCostCalculationService;
use Illuminate\Support\Facades\Log;

/**
 * Middleware pour s'assurer que le total_cost des réservations est correctement calculé
 */
class EnsureReservationTotalCost
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Vérifier si la réponse contient une réservation
        if ($response->getData() && isset($response->getData()->reservation)) {
            $reservationId = $response->getData()->reservation->id ?? null;
            
            if ($reservationId) {
                $this->ensureReservationTotalCost($reservationId);
            }
        }
        
        return $response;
    }
    
    /**
     * S'assurer que le total_cost de la réservation est correct
     */
    private function ensureReservationTotalCost($reservationId)
    {
        try {
            $reservation = Reservation::find($reservationId);
            
            if (!$reservation) {
                return;
            }
            
            // Vérifier si le total_cost est manquant ou incorrect
            if (!$reservation->total_cost || 
                $reservation->total_cost <= 0 || 
                $reservation->total_cost != $reservation->estimated_cost) {
                
                $costService = app(ReservationCostCalculationService::class);
                $correctCost = $costService->calculateReservationCost($reservation);
                
                if ($correctCost <= 0) {
                    $correctCost = $costService->calculateCostWithDefaults($reservation, 1.0);
                }
                
                if ($correctCost > 0) {
                    $reservation->update([
                        'estimated_cost' => $correctCost,
                        'actual_cost' => $correctCost,
                        'total_cost' => $correctCost
                    ]);
                    
                    Log::info("Total cost corrigé pour la réservation #{$reservationId}", [
                        'reservation_id' => $reservationId,
                        'corrected_cost' => $correctCost
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors de la correction du total_cost pour la réservation #{$reservationId}: " . $e->getMessage());
        }
    }
}
