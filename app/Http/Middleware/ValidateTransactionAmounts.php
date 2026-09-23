<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Services\ReservationCostCalculationService;
use App\Services\RevenueDistributionCalculationService;
use Illuminate\Support\Facades\Log;

class ValidateTransactionAmounts
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Vérifier si la réponse contient des transactions
        if ($response->getContent() && strpos($response->getContent(), 'transaction') !== false) {
            $this->validateAndFixTransactionAmounts();
        }

        return $response;
    }

    /**
     * Valide et corrige les montants de transaction nuls
     */
    private function validateAndFixTransactionAmounts()
    {
        try {
            // Trouver les transactions avec des montants nuls
            $nullAmountTransactions = Transaction::where(function ($query) {
                $query->whereNull('amount')
                      ->orWhere('amount', '<=', 0)
                      ->orWhereNull('price_total')
                      ->orWhere('price_total', '<=', 0);
            })->whereNotNull('reservation_id')
              ->with(['reservation.pricingPlan', 'reservation.chargingPoint'])
              ->limit(10) // Traiter par petits lots
              ->get();

            if ($nullAmountTransactions->isEmpty()) {
                return;
            }

            Log::info("Validation des montants de transaction: {$nullAmountTransactions->count()} transactions avec montants nuls détectées");

            $costService = app(ReservationCostCalculationService::class);
            $revenueService = app(RevenueDistributionCalculationService::class);

            foreach ($nullAmountTransactions as $transaction) {
                try {
                    $this->fixTransactionAmount($transaction, $costService, $revenueService);
                } catch (\Exception $e) {
                    Log::error("Erreur lors de la correction de la transaction #{$transaction->id}: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error("Erreur dans ValidateTransactionAmounts middleware: " . $e->getMessage());
        }
    }

    /**
     * Corrige le montant d'une transaction spécifique
     */
    private function fixTransactionAmount(Transaction $transaction, ReservationCostCalculationService $costService, RevenueDistributionCalculationService $revenueService)
    {
        $reservation = $transaction->reservation;
        
        if (!$reservation) {
            Log::warning("Transaction #{$transaction->id} sans réservation associée");
            return;
        }

        // Calculer le coût correct
        $correctAmount = $costService->calculateReservationCost($reservation);
        
        if ($correctAmount <= 0) {
            $correctAmount = $costService->calculateCostWithDefaults($reservation, 1.0);
        }

        if ($correctAmount <= 0) {
            Log::error("Impossible de calculer un montant valide pour la transaction #{$transaction->id}");
            return;
        }

        // Calculer la répartition des revenus
        $repartitionBreakdown = $revenueService->calculateRevenueDistribution($reservation, $correctAmount);

        // Mettre à jour la transaction
        $transaction->update([
            'amount' => $correctAmount,
            'price_total' => $correctAmount,
            'repartition_breakdown' => $repartitionBreakdown,
            'business_profile_fee_breakdown' => $repartitionBreakdown['fees'],
            'admin_commission' => $repartitionBreakdown['distribution']['admin_commission'],
            'integrator_commission' => $repartitionBreakdown['distribution']['integrator_commission'],
            'partner_commission' => $repartitionBreakdown['distribution']['partner_commission'],
        ]);

        // Mettre à jour la réservation si nécessaire
        if ($reservation->estimated_cost <= 0) {
            $reservation->update([
                'estimated_cost' => $correctAmount,
                'actual_cost' => $correctAmount,
                'total_cost' => $correctAmount,
            ]);
        }

        Log::info("Transaction #{$transaction->id} corrigée: montant mis à jour de 0€ à {$correctAmount}€");
    }
}
