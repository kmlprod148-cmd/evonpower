<?php

namespace App\Services\Commission\Strategies;

use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Log;

/**
 * Stratégie de calcul de commission finale
 * 
 * Logique identique à la stratégie unifiée mais avec validation renforcée
 * et gestion des cas limites
 */
class FinalCommissionStrategy implements CommissionCalculationStrategyInterface
{
    public function calculate(BusinessProfile $businessProfile, float $totalAmount, array $options = []): array
    {
        $this->validate($businessProfile, $totalAmount, $options);

        Log::info("Calcul des commissions - Stratégie Finale", [
            "business_profile_id" => $businessProfile->id,
            "total_amount" => $totalAmount,
            "integrator_commission_rate" => $businessProfile->integrator_commission,
            "admin_commission_rate" => $businessProfile->owner_commission
        ]);

        // 1. PART ADMIN : Calculée sur le TOTAL DE LA TRANSACTION
        $adminCommission = round($totalAmount * ($businessProfile->owner_commission / 100), 2);
        
        // 2. PART INTÉGRATEUR : Calculée sur le TOTAL DE LA TRANSACTION
        $integratorCommission = round($totalAmount * ($businessProfile->integrator_commission / 100), 2);
        
        // 3. PART OPÉRATEUR : Le reste après déduction des commissions Admin et Intégrateur
        $operatorCommission = round($totalAmount - $adminCommission - $integratorCommission, 2);

        // Vérification des cas limites
        if ($operatorCommission < 0) {
            Log::warning("Commission opérateur négative détectée", [
                'total_amount' => $totalAmount,
                'admin_commission' => $adminCommission,
                'integrator_commission' => $integratorCommission,
                'operator_commission' => $operatorCommission
            ]);
            
            // Ajuster pour éviter les montants négatifs
            $operatorCommission = 0;
        }

        // Calculer les pourcentages pour l'affichage
        $adminPercentage = $totalAmount > 0 ? round(($adminCommission / $totalAmount) * 100, 2) : 0;
        $integratorPercentage = $totalAmount > 0 ? round(($integratorCommission / $totalAmount) * 100, 2) : 0;
        $operatorPercentage = $totalAmount > 0 ? round(($operatorCommission / $totalAmount) * 100, 2) : 0;

        // Intégrateur Net (après déduction de la commission Admin)
        $integratorNet = round($integratorCommission - $adminCommission, 2);

        // Vérification de cohérence
        $totalCalculated = $adminCommission + $integratorCommission + $operatorCommission;
        $difference = abs($totalAmount - $totalCalculated);

        $result = [
            'total_amount' => $totalAmount,
            'admin_commission' => $adminCommission,
            'integrator_commission' => $integratorCommission,
            'operator_commission' => $operatorCommission,
            'admin_percentage' => $adminPercentage,
            'integrator_percentage' => $integratorPercentage,
            'operator_percentage' => $operatorPercentage,
            'integrator_net' => $integratorNet,
            'strategy' => $this->getName(),
            'validation' => [
                'total_calculated' => $totalCalculated,
                'difference' => $difference,
                'is_balanced' => $difference < 0.01, // Tolérance de 1 centime
            ],
            'calculation_breakdown' => [
                'step_1_admin_commission' => "Commission Admin: {$businessProfile->owner_commission}% de {$totalAmount} = {$adminCommission} EUR",
                'step_2_integrator_commission' => "Commission Intégrateur: {$businessProfile->integrator_commission}% de {$totalAmount} = {$integratorCommission} EUR",
                'step_3_operator_remaining' => "Part Opérateur: {$totalAmount} - {$adminCommission} - {$integratorCommission} = {$operatorCommission} EUR",
                'step_4_integrator_net' => "Intégrateur Net: {$integratorCommission} - {$adminCommission} = {$integratorNet} EUR",
                'step_5_validation' => "Total calculé: {$totalCalculated} EUR (Différence: {$difference} EUR)"
            ]
        ];

        Log::info("Résultat du calcul des commissions - Stratégie Finale", $result);

        return $result;
    }

    public function getName(): string
    {
        return 'final';
    }

    public function getDescription(): string
    {
        return 'Stratégie finale : Validation renforcée et gestion des cas limites';
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

        // Vérification renforcée : la somme des commissions ne peut pas dépasser 100%
        $totalCommissionRate = $businessProfile->owner_commission + $businessProfile->integrator_commission;
        if ($totalCommissionRate > 100) {
            throw new \InvalidArgumentException(
                "La somme des taux de commission ({$totalCommissionRate}%) ne peut pas dépasser 100%"
            );
        }

        // Vérification des montants minimums
        if ($totalAmount < 0.01) {
            throw new \InvalidArgumentException('Le montant minimum est de 0.01 EUR');
        }

        return true;
    }
}
