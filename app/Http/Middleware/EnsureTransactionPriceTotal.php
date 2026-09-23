<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Services\ReservationCostCalculationService;
use Illuminate\Support\Facades\Log;

/**
 * Middleware pour s'assurer que le price_total des transactions est valide
 */
class EnsureTransactionPriceTotal
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Vérifier si la réponse contient une transaction
        if ($response->getData() && isset($response->getData()->transaction)) {
            $transactionId = $response->getData()->transaction->id ?? null;
            
            if ($transactionId) {
                $this->ensureTransactionPriceTotal($transactionId);
            }
        }
        
        return $response;
    }
    
    /**
     * S'assurer que le price_total de la transaction est valide
     */
    private function ensureTransactionPriceTotal($transactionId)
    {
        try {
            $transaction = Transaction::find($transactionId);
            
            if (!$transaction) {
                return;
            }
            
            // Vérifier si le price_total est nul ou zéro
            if (!$transaction->price_total || $transaction->price_total <= 0) {
                $correctPriceTotal = $this->calculateCorrectPriceTotal($transaction);
                
                if ($correctPriceTotal > 0) {
                    $transaction->update([
                        'price_total' => $correctPriceTotal,
                        'amount' => $correctPriceTotal
                    ]);
                    
                    Log::info("Price total corrigé pour la transaction #{$transactionId}", [
                        'transaction_id' => $transactionId,
                        'corrected_price_total' => $correctPriceTotal
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors de la correction du price_total pour la transaction #{$transactionId}: " . $e->getMessage());
        }
    }
    
    /**
     * Calcule le price_total correct pour une transaction
     */
    private function calculateCorrectPriceTotal(Transaction $transaction): float
    {
        // Essayer de récupérer le prix depuis la réservation
        if ($transaction->reservation) {
            $reservation = $transaction->reservation;
            
            // Utiliser le coût calculé de la réservation
            if ($reservation->total_cost && $reservation->total_cost > 0) {
                return $reservation->total_cost;
            } elseif ($reservation->estimated_cost && $reservation->estimated_cost > 0) {
                return $reservation->estimated_cost;
            } elseif ($reservation->actual_cost && $reservation->actual_cost > 0) {
                return $reservation->actual_cost;
            } else {
                // Calculer le coût avec le service
                $costService = app(ReservationCostCalculationService::class);
                $calculatedCost = $costService->calculateReservationCost($reservation);
                
                if ($calculatedCost > 0) {
                    return $calculatedCost;
                }
                
                $calculatedCost = $costService->calculateCostWithDefaults($reservation, 1.0);
                if ($calculatedCost > 0) {
                    return $calculatedCost;
                }
            }
        }
        
        // Utiliser le montant de la transaction
        if ($transaction->amount && $transaction->amount > 0) {
            return $transaction->amount;
        }
        
        // Valeur par défaut
        return 1.0;
    }
}
