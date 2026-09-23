<?php

namespace App\Services\Commission\Strategies;

use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Log;

/**
 * Stratégie de calcul de commission corrigée
 * 
 * Logique :
 * - Intégrateur: Commission sur le TOTAL de la transaction
 * - Admin: Commission sur la PART DE L'INTÉGRATEUR (pas sur le total)
 * - Opérateur: Le reste après déduction de la part Intégrateur
 * 
 * IMPORTANT: La commission Admin se DÉDUIT de la balance de l'Intégrateur
 */
class CorrectedCommissionStrategy implements CommissionCalculationStrategyInterface
{
    public function calculate(BusinessProfile $businessProfile, float $totalAmount, array $options = []): array
    {
        $this->validate($businessProfile, $totalAmount, $options);

        Log::info("Calcul des commissions (Frais) - Stratégie Corrigée", [
            "business_profile_id" => $businessProfile->id,
            "total_amount" => $totalAmount,
            "integrator_commission_rate" => $businessProfile->integrator_commission,
            "admin_commission_rate" => $businessProfile->owner_commission
        ]);

        // 1. PART INTÉGRATEUR : Commission calculée sur le TOTAL DE LA TRANSACTION
        $integratorCommission = round($totalAmount * ($businessProfile->integrator_commission / 100), 2);
        
        // 2. PART ADMIN : Commission calculée sur la PART DE L'INTÉGRATEUR (pas sur le total)
        $adminCommission = round($integratorCommission * ($businessProfile->owner_commission / 100), 2);
        
        // 3. PART OPÉRATEUR : Le reste après déduction de la part Intégrateur
        $operatorCommission = round($totalAmount - $integratorCommission, 2);

        // Calculer les pourcentages pour l'affichage
        $adminPercentage = $totalAmount > 0 ? round(($adminCommission / $totalAmount) * 100, 2) : 0;
        $integratorPercentage = $totalAmount > 0 ? round(($integratorCommission / $totalAmount) * 100, 2) : 0;
        $operatorPercentage = $totalAmount > 0 ? round(($operatorCommission / $totalAmount) * 100, 2) : 0;

        // Pourcentage de l'Admin sur la part de l'Intégrateur
        $adminOnIntegratorPercentage = $integratorCommission > 0 ? round(($adminCommission / $integratorCommission) * 100, 2) : 0;

        // Intégrateur Net (après déduction de la commission Admin)
        $integratorNet = round($integratorCommission - $adminCommission, 2);

        $result = [
            'total_amount' => $totalAmount,
            'admin_commission' => $adminCommission,
            'integrator_commission' => $integratorCommission,
            'operator_commission' => $operatorCommission,
            'admin_percentage' => $adminPercentage,
            'integrator_percentage' => $integratorPercentage,
            'operator_percentage' => $operatorPercentage,
            'admin_on_integrator_percentage' => $adminOnIntegratorPercentage,
            'integrator_net' => $integratorNet,
            'strategy' => $this->getName(),
            'calculation_breakdown' => [
                'step_1_integrator_commission' => "Commission Intégrateur: {$businessProfile->integrator_commission}% de {$totalAmount} = {$integratorCommission} EUR",
                'step_2_admin_commission' => "Commission Admin: {$businessProfile->owner_commission}% de {$integratorCommission} = {$adminCommission} EUR",
                'step_3_operator_remaining' => "Part Opérateur: {$totalAmount} - {$integratorCommission} = {$operatorCommission} EUR",
                'step_4_integrator_net' => "Intégrateur Net: {$integratorCommission} - {$adminCommission} = {$integratorNet} EUR"
            ]
        ];

        Log::info("Résultat du calcul des commissions (Frais) - Stratégie Corrigée", $result);

        return $result;
    }

    public function getName(): string
    {
        return 'corrected';
    }

    public function getDescription(): string
    {
        return 'Stratégie corrigée : Admin commission sur part intégrateur, Intégrateur commission sur total';
    }

    public function validate(BusinessProfile $businessProfile, float $totalAmount, array $options = []): bool
    {
        if (!$businessProfile) {
            throw new \InvalidArgumentException('Business profile requis');
        }

        if ($totalAmount <= 0) {
            throw new \InvalidArgumentException('Le montant total doit être positif');
        }

        if ($businessProfile->integrator_commission < 0 || $businessProfile->integrator_commission > 100) {
            throw new \InvalidArgumentException('Le taux de commission intégrateur doit être entre 0 et 100%');
        }

        if ($businessProfile->owner_commission < 0 || $businessProfile->owner_commission > 100) {
            throw new \InvalidArgumentException('Le taux de commission admin doit être entre 0 et 100%');
        }

        return true;
    }
}
