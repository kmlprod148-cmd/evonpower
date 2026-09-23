<?php

namespace App\Services;

use App\Models\EnhancedUser;
use App\Models\EnhancedBusinessProfile;
use App\Exceptions\FeeCalculationException;
use Illuminate\Support\Facades\Log;

class FeeCalculationService
{
    /**
     * Calculer les frais pour une transaction
     *
     * @param float $amount Montant de la transaction
     * @param EnhancedUser $from Utilisateur source
     * @param EnhancedUser $to Utilisateur cible
     * @param string $type Type de transaction
     * @return array Résultat du calcul avec détails des frais
     * @throws FeeCalculationException
     */
    public function calculateFees(float $amount, EnhancedUser $from, EnhancedUser $to, string $type): array
    {
        try {
            // Valider les paramètres d'entrée
            $this->validateInput($amount, $from, $to, $type);

            // Obtenir le business profile approprié
            $businessProfile = $this->getBusinessProfile($from, $to, $type);

            // Calculer les frais selon la configuration
            $feeCalculation = $this->performFeeCalculation($amount, $businessProfile, $type);

            // Construire la réponse
            $result = $this->buildCalculationResult($amount, $feeCalculation, $businessProfile);

            Log::info('Calcul de frais effectué', [
                'amount' => $amount,
                'from_user' => $from->name,
                'to_user' => $to->name,
                'transaction_type' => $type,
                'business_profile' => $businessProfile->name,
                'total_fees' => $result['total_fees'],
                'net_amount' => $result['net_amount']
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul des frais', [
                'amount' => $amount,
                'from_user_id' => $from->id ?? null,
                'to_user_id' => $to->id ?? null,
                'transaction_type' => $type,
                'error' => $e->getMessage()
            ]);

            throw new FeeCalculationException(
                "Impossible de calculer les frais: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Valider les paramètres d'entrée
     */
    private function validateInput(float $amount, EnhancedUser $from, EnhancedUser $to, string $type): void
    {
        if ($amount <= 0) {
            throw new FeeCalculationException('Le montant doit être positif');
        }

        if (!$from || !$to) {
            throw new FeeCalculationException('Les utilisateurs source et cible sont requis');
        }

        if ($from->id === $to->id) {
            throw new FeeCalculationException('L\'utilisateur source et cible ne peuvent pas être identiques');
        }

        if (!$from->is_active || !$to->is_active) {
            throw new FeeCalculationException('Les utilisateurs doivent être actifs');
        }

        $validTypes = ['admin_to_integrator', 'integrator_to_operator', 'recharge', 'refund', 'commission'];
        if (!in_array($type, $validTypes)) {
            throw new FeeCalculationException('Type de transaction invalide: ' . $type);
        }
    }

    /**
     * Obtenir le business profile approprié pour la transaction
     */
    private function getBusinessProfile(EnhancedUser $from, EnhancedUser $to, string $type): EnhancedBusinessProfile
    {
        // Priorité 1: Business profile de l'utilisateur source
        if ($from->business_profile_id) {
            $businessProfile = EnhancedBusinessProfile::find($from->business_profile_id);
            if ($businessProfile && $businessProfile->is_active && $businessProfile->supportsTransactionType($type)) {
                return $businessProfile;
            }
        }

        // Priorité 2: Business profile de l'utilisateur cible
        if ($to->business_profile_id) {
            $businessProfile = EnhancedBusinessProfile::find($to->business_profile_id);
            if ($businessProfile && $businessProfile->is_active && $businessProfile->supportsTransactionType($type)) {
                return $businessProfile;
            }
        }

        // Priorité 3: Business profile par défaut du système
        $defaultProfile = EnhancedBusinessProfile::where('is_default', true)
            ->where('is_active', true)
            ->where("supports_{$type}", true)
            ->first();

        if ($defaultProfile) {
            return $defaultProfile;
        }

        // Priorité 4: Premier business profile actif qui supporte ce type
        $fallbackProfile = EnhancedBusinessProfile::where('is_active', true)
            ->where("supports_{$type}", true)
            ->first();

        if ($fallbackProfile) {
            return $fallbackProfile;
        }

        throw new FeeCalculationException(
            "Aucun business profile actif trouvé pour le type de transaction: {$type}"
        );
    }

    /**
     * Effectuer le calcul des frais selon la configuration
     */
    private function performFeeCalculation(float $amount, EnhancedBusinessProfile $businessProfile, string $type): array
    {
        // Vérifier les limites de montant
        if (!$businessProfile->isAmountWithinLimits($amount)) {
            throw new FeeCalculationException(
                "Le montant {$amount}€ est en dehors des limites autorisées " .
                "({$businessProfile->min_transaction_amount}€ - {$businessProfile->max_transaction_amount}€)"
            );
        }

        // Déterminer les frais applicables selon le type de transaction
        $applicableFees = $this->getApplicableFees($type);

        $calculation = [
            'base_amount' => $amount,
            'business_profile_id' => $businessProfile->id,
            'business_profile_name' => $businessProfile->name,
            'transaction_type' => $type,
            'fee_details' => [],
            'total_fees' => 0.00,
            'fee_configuration' => $businessProfile->getFeeConfiguration()
        ];

        foreach ($applicableFees as $feeCategory) {
            $feeDetail = $this->calculateCategoryFee($amount, $businessProfile, $feeCategory);
            $calculation['fee_details'][$feeCategory] = $feeDetail;
            $calculation['total_fees'] += $feeDetail['total_amount'];
        }

        return $calculation;
    }

    /**
     * Déterminer quels frais sont applicables selon le type de transaction
     */
    private function getApplicableFees(string $type): array
    {
        return match($type) {
            'admin_to_integrator' => ['admin', 'integrator'],
            'integrator_to_operator' => ['integrator', 'operator'],
            'recharge' => ['admin'],
            'refund' => ['admin'],
            'commission' => ['admin', 'integrator', 'operator'],
            default => []
        };
    }

    /**
     * Calculer les frais pour une catégorie spécifique
     */
    private function calculateCategoryFee(float $amount, EnhancedBusinessProfile $businessProfile, string $category): array
    {
        $fixedFee = $businessProfile->{$category . '_fixed_fee'};
        $percentageFee = $businessProfile->{$category . '_percentage_fee'};
        
        $feeDetail = [
            'category' => $category,
            'fixed_amount' => 0.00,
            'percentage_amount' => 0.00,
            'percentage_rate' => 0.00,
            'total_amount' => 0.00,
            'calculation_method' => 'none'
        ];

        // Calculer selon la configuration
        if ($businessProfile->use_fixed_fees && $businessProfile->use_percentage_fees && $businessProfile->combine_fees) {
            // Configuration: Frais fixes + pourcentage
            $feeDetail['fixed_amount'] = $fixedFee;
            $feeDetail['percentage_amount'] = ($amount * $percentageFee) / 100;
            $feeDetail['percentage_rate'] = $percentageFee;
            $feeDetail['total_amount'] = $feeDetail['fixed_amount'] + $feeDetail['percentage_amount'];
            $feeDetail['calculation_method'] = 'fixed_and_percentage';
            
        } elseif ($businessProfile->use_fixed_fees && !$businessProfile->use_percentage_fees) {
            // Configuration: Frais fixes uniquement
            $feeDetail['fixed_amount'] = $fixedFee;
            $feeDetail['total_amount'] = $feeDetail['fixed_amount'];
            $feeDetail['calculation_method'] = 'fixed_only';
            
        } elseif (!$businessProfile->use_fixed_fees && $businessProfile->use_percentage_fees) {
            // Configuration: Frais en pourcentage uniquement
            $feeDetail['percentage_amount'] = ($amount * $percentageFee) / 100;
            $feeDetail['percentage_rate'] = $percentageFee;
            $feeDetail['total_amount'] = $feeDetail['percentage_amount'];
            $feeDetail['calculation_method'] = 'percentage_only';
            
        } else {
            // Configuration: Aucun frais
            $feeDetail['calculation_method'] = 'no_fees';
        }

        return $feeDetail;
    }

    /**
     * Construire le résultat final du calcul
     */
    private function buildCalculationResult(float $amount, array $feeCalculation, EnhancedBusinessProfile $businessProfile): array
    {
        $totalFees = $feeCalculation['total_fees'];
        $netAmount = $amount - $totalFees; // Montant que recevra l'utilisateur cible
        $grossAmount = $amount; // Montant total débité de l'utilisateur source

        return [
            'success' => true,
            'base_amount' => $amount,
            'net_amount' => $netAmount,
            'gross_amount' => $grossAmount,
            'total_fees' => $totalFees,
            'currency' => 'EUR',
            'transaction_type' => $feeCalculation['transaction_type'],
            'business_profile' => [
                'id' => $businessProfile->id,
                'name' => $businessProfile->name,
                'owner' => $businessProfile->owner->name ?? 'N/A'
            ],
            'fee_breakdown' => $feeCalculation['fee_details'],
            'fee_summary' => $this->buildFeeSummary($feeCalculation['fee_details']),
            'calculation_timestamp' => now()->toISOString(),
            'metadata' => [
                'calculation_method' => $this->getOverallCalculationMethod($feeCalculation['fee_details']),
                'fee_configuration' => $feeCalculation['fee_configuration']
            ]
        ];
    }

    /**
     * Construire un résumé des frais
     */
    private function buildFeeSummary(array $feeDetails): array
    {
        $summary = [
            'by_category' => [],
            'by_type' => [
                'fixed_fees' => 0.00,
                'percentage_fees' => 0.00,
                'combined_fees' => 0.00
            ],
            'total_categories' => count($feeDetails)
        ];

        foreach ($feeDetails as $category => $detail) {
            $summary['by_category'][$category] = [
                'amount' => $detail['total_amount'],
                'method' => $detail['calculation_method']
            ];

            switch ($detail['calculation_method']) {
                case 'fixed_only':
                    $summary['by_type']['fixed_fees'] += $detail['total_amount'];
                    break;
                case 'percentage_only':
                    $summary['by_type']['percentage_fees'] += $detail['total_amount'];
                    break;
                case 'fixed_and_percentage':
                    $summary['by_type']['combined_fees'] += $detail['total_amount'];
                    break;
            }
        }

        return $summary;
    }

    /**
     * Obtenir la méthode de calcul globale
     */
    private function getOverallCalculationMethod(array $feeDetails): string
    {
        $methods = array_unique(array_column($feeDetails, 'calculation_method'));
        
        if (count($methods) === 1) {
            return $methods[0];
        }
        
        if (in_array('fixed_and_percentage', $methods)) {
            return 'mixed';
        }
        
        return 'varied';
    }

    /**
     * Obtenir les statistiques de frais pour un business profile
     */
    public function getBusinessProfileFeeStats(int $businessProfileId): array
    {
        $businessProfile = EnhancedBusinessProfile::findOrFail($businessProfileId);
        
        return [
            'business_profile' => [
                'id' => $businessProfile->id,
                'name' => $businessProfile->name,
                'owner' => $businessProfile->owner->name ?? 'N/A'
            ],
            'fee_configuration' => $businessProfile->getFeeConfiguration(),
            'supported_transactions' => [
                'admin_to_integrator' => $businessProfile->supports_admin_to_integrator,
                'integrator_to_operator' => $businessProfile->supports_integrator_to_operator,
                'recharge' => $businessProfile->supports_recharge
            ],
            'limits' => [
                'min_amount' => $businessProfile->min_transaction_amount,
                'max_amount' => $businessProfile->max_transaction_amount,
                'daily_limit' => $businessProfile->daily_limit,
                'monthly_limit' => $businessProfile->monthly_limit
            ]
        ];
    }

    /**
     * Simuler un calcul de frais sans créer de transaction
     */
    public function simulateFeeCalculation(float $amount, string $transactionType, int $businessProfileId = null): array
    {
        try {
            if ($businessProfileId) {
                $businessProfile = EnhancedBusinessProfile::findOrFail($businessProfileId);
            } else {
                $businessProfile = EnhancedBusinessProfile::where('is_default', true)
                    ->where('is_active', true)
                    ->first();
                
                if (!$businessProfile) {
                    throw new FeeCalculationException('Aucun business profile par défaut trouvé');
                }
            }

            $calculation = $this->performFeeCalculation($amount, $businessProfile, $transactionType);
            return $this->buildCalculationResult($amount, $calculation, $businessProfile);

        } catch (\Exception $e) {
            throw new FeeCalculationException(
                "Erreur lors de la simulation: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
