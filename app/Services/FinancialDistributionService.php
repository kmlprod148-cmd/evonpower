<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\PricingPlan;
use App\Models\BusinessProfile;

/**
 * @deprecated Use UnifiedRevenueCalculationService instead. This class will be removed in a future version.
 */
class FinancialDistributionService
{
    public function calculateDistribution(Transaction $transaction)
    {
        $pricingPlan = $transaction->pricingPlan;
        $businessProfile = $transaction->businessProfile;

        $amount = $transaction->amount;

        $operatorPart = round($amount * $businessProfile->operator_commission / 100, 2);
        $integratorPart = round($amount * $businessProfile->integrator_commission / 100, 2);
        $ownerPart = round($amount * $businessProfile->owner_commission / 100, 2);

        $breakdown = [
            'operator' => $operatorPart,
            'integrator' => $integratorPart,
            'owner' => $ownerPart,
        ];

        $transaction->repartition_breakdown = $breakdown;
        $transaction->save();

        return $breakdown;
    }
} 