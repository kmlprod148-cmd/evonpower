<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\GainDistribution;
use Illuminate\Support\Facades\Log;

class RevenueShareService
{
    public function __construct(
        private readonly RevenueDistributionCalculationService $calculationService
    ) {}

    /**
     * Calcule et persiste la répartition des gains pour une transaction donnée.
     */
    public function calculateAndStoreDistribution(Transaction $transaction): ?GainDistribution
    {
        try {
            $transaction->loadMissing(['reservation', 'pricingPlan', 'chargingPoint.station.owner.businessProfile']);

            // Déterminer le montant total
            $totalAmount = (float)($transaction->amount ?? $transaction->price_total ?? 0);

            // Fallback si aucune réservation n'est liée: créer une distribution par défaut à partir des montants transaction
            if (!$transaction->relationLoaded('reservation') || !$transaction->reservation) {
                return $this->storeSimpleDistribution($transaction, $totalAmount);
            }

            $distribution = $this->calculationService->calculateRevenueDistribution($transaction->reservation, $totalAmount);

            return $this->storeDistributionArray($transaction, $distribution);
        } catch (\Throwable $e) {
            Log::error('RevenueShareService: erreur lors du calcul de la répartition', [
                'transaction_id' => $transaction->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function storeSimpleDistribution(Transaction $transaction, float $totalAmount): GainDistribution
    {
        return GainDistribution::create([
            'transaction_id' => $transaction->id,
            'total_amount' => $totalAmount,
            'admin_fees_total' => 0,
            'revenue_after_admin' => $totalAmount,
            'integrator_commission_amount' => 0,
            'integrator_commission_percent' => 0,
            'integrator_commission_fixed' => 0,
            'partner_commission_amount' => 0,
            'partner_commission_percent' => 0,
            'partner_commission_fixed' => 0,
            'operator_revenue' => $totalAmount,
            'currency' => $transaction->currency ?? 'EUR',
            'details' => json_encode(['note' => 'Simple distribution without reservation context']),
        ]);
    }

    private function storeDistributionArray(Transaction $transaction, array $distribution): GainDistribution
    {
        // On s'attend à des clés conformes au service de calcul
        $adminFees = $distribution['fees']['total_fees'] ?? 0;
        $revenueAfterAdmin = max(0, ($distribution['revenue_after_admin'] ?? ($distribution['total_amount'] ?? 0) - $adminFees));
        $integratorAmount = $distribution['commissions']['integrator']['amount'] ?? 0;
        $integratorPercent = $distribution['commissions']['integrator']['percent'] ?? 0;
        $integratorFixed = $distribution['commissions']['integrator']['fixed'] ?? 0;
        $partnerAmount = $distribution['commissions']['partner']['amount'] ?? 0;
        $partnerPercent = $distribution['commissions']['partner']['percent'] ?? 0;
        $partnerFixed = $distribution['commissions']['partner']['fixed'] ?? 0;
        $operatorRevenue = $distribution['operator_revenue'] ?? max(0, $revenueAfterAdmin - ($integratorAmount + $partnerAmount));

        return GainDistribution::create([
            'transaction_id' => $transaction->id,
            'total_amount' => $distribution['total_amount'] ?? ($transaction->amount ?? 0),
            'admin_fees_total' => $adminFees,
            'revenue_after_admin' => $revenueAfterAdmin,
            'integrator_commission_amount' => $integratorAmount,
            'integrator_commission_percent' => $integratorPercent,
            'integrator_commission_fixed' => $integratorFixed,
            'partner_commission_amount' => $partnerAmount,
            'partner_commission_percent' => $partnerPercent,
            'partner_commission_fixed' => $partnerFixed,
            'operator_revenue' => $operatorRevenue,
            'currency' => $transaction->currency ?? 'EUR',
            'details' => json_encode($distribution),
        ]);
    }
}


