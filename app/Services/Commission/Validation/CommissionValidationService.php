<?php

namespace App\Services\Commission\Validation;

use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Service de validation avancé pour les calculs de commission
 * 
 * Fournit une validation robuste des paramètres avec messages d'erreur détaillés
 * et validation des contraintes métier
 */
class CommissionValidationService
{
    /**
     * Règles de validation pour les paramètres de calcul
     */
    private array $validationRules = [
        'business_profile' => 'required|exists:business_profiles,id',
        'total_amount' => 'required|numeric|min:0.01|max:999999.99',
        'strategy' => 'required|string|in:corrected,unified,final',
        'options' => 'array',
        'options.cache_enabled' => 'boolean',
        'options.force_recalculation' => 'boolean',
        'options.include_breakdown' => 'boolean',
    ];

    /**
     * Messages d'erreur personnalisés
     */
    private array $customMessages = [
        'business_profile.required' => 'Le profil d\'entreprise est requis',
        'business_profile.exists' => 'Le profil d\'entreprise spécifié n\'existe pas',
        'total_amount.required' => 'Le montant total est requis',
        'total_amount.numeric' => 'Le montant total doit être un nombre',
        'total_amount.min' => 'Le montant minimum est de 0.01 EUR',
        'total_amount.max' => 'Le montant maximum est de 999,999.99 EUR',
        'strategy.required' => 'La stratégie de calcul est requise',
        'strategy.in' => 'La stratégie doit être : corrected, unified ou final',
    ];

    /**
     * Valide les paramètres de calcul de commission
     * 
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateCalculationParameters(array $data): array
    {
        $validator = Validator::make($data, $this->validationRules, $this->customMessages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Valide un BusinessProfile pour les calculs de commission
     * 
     * @param BusinessProfile $businessProfile
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateBusinessProfile(BusinessProfile $businessProfile): bool
    {
        if (!$businessProfile) {
            throw new \InvalidArgumentException('Le profil d\'entreprise est requis');
        }

        if (!$businessProfile->is_active) {
            throw new \InvalidArgumentException('Le profil d\'entreprise doit être actif');
        }

        // Validation des taux de commission
        $this->validateCommissionRates($businessProfile);

        // Validation de la cohérence des données
        $this->validateBusinessProfileConsistency($businessProfile);

        return true;
    }

    /**
     * Valide les taux de commission
     * 
     * @param BusinessProfile $businessProfile
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateCommissionRates(BusinessProfile $businessProfile): bool
    {
        // Validation du taux de commission intégrateur
        if ($businessProfile->integrator_commission < 0 || $businessProfile->integrator_commission > 100) {
            throw new \InvalidArgumentException(
                "Le taux de commission intégrateur ({$businessProfile->integrator_commission}%) doit être entre 0 et 100%"
            );
        }

        // Validation du taux de commission admin
        if ($businessProfile->owner_commission < 0 || $businessProfile->owner_commission > 100) {
            throw new \InvalidArgumentException(
                "Le taux de commission admin ({$businessProfile->owner_commission}%) doit être entre 0 et 100%"
            );
        }

        // Validation de la cohérence des taux
        $totalRate = $businessProfile->integrator_commission + $businessProfile->owner_commission;
        if ($totalRate > 100) {
            throw new \InvalidArgumentException(
                "La somme des taux de commission ({$totalRate}%) ne peut pas dépasser 100%"
            );
        }

        return true;
    }

    /**
     * Valide la cohérence du BusinessProfile
     * 
     * @param BusinessProfile $businessProfile
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateBusinessProfileConsistency(BusinessProfile $businessProfile): bool
    {
        // Vérifier que le profil a un propriétaire
        if (!$businessProfile->owner_id) {
            throw new \InvalidArgumentException('Le profil d\'entreprise doit avoir un propriétaire');
        }

        // Vérifier que le profil a un intégrateur
        if (!$businessProfile->integrator_id) {
            throw new \InvalidArgumentException('Le profil d\'entreprise doit avoir un intégrateur');
        }

        return true;
    }

    /**
     * Valide le montant total
     * 
     * @param float $totalAmount
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateTotalAmount(float $totalAmount): bool
    {
        if ($totalAmount <= 0) {
            throw new \InvalidArgumentException('Le montant total doit être positif');
        }

        if ($totalAmount < 0.01) {
            throw new \InvalidArgumentException('Le montant minimum est de 0.01 EUR');
        }

        if ($totalAmount > 999999.99) {
            throw new \InvalidArgumentException('Le montant maximum est de 999,999.99 EUR');
        }

        return true;
    }

    /**
     * Valide la stratégie de calcul
     * 
     * @param string $strategy
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateStrategy(string $strategy): bool
    {
        $validStrategies = ['corrected', 'unified', 'final'];
        
        if (!in_array($strategy, $validStrategies)) {
            throw new \InvalidArgumentException(
                "Stratégie '{$strategy}' non valide. Stratégies disponibles : " . implode(', ', $validStrategies)
            );
        }

        return true;
    }

    /**
     * Valide les options de calcul
     * 
     * @param array $options
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateOptions(array $options): bool
    {
        $allowedOptions = ['cache_enabled', 'force_recalculation', 'include_breakdown', 'debug_mode'];
        
        foreach (array_keys($options) as $option) {
            if (!in_array($option, $allowedOptions)) {
                throw new \InvalidArgumentException(
                    "Option '{$option}' non autorisée. Options disponibles : " . implode(', ', $allowedOptions)
                );
            }
        }

        return true;
    }

    /**
     * Valide un calcul de commission complet
     * 
     * @param BusinessProfile $businessProfile
     * @param float $totalAmount
     * @param string $strategy
     * @param array $options
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateCompleteCalculation(
        BusinessProfile $businessProfile,
        float $totalAmount,
        string $strategy,
        array $options = []
    ): bool {
        $this->validateBusinessProfile($businessProfile);
        $this->validateTotalAmount($totalAmount);
        $this->validateStrategy($strategy);
        $this->validateOptions($options);

        return true;
    }

    /**
     * Valide les résultats d'un calcul de commission
     * 
     * @param array $calculationResult
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateCalculationResult(array $calculationResult): bool
    {
        $requiredFields = [
            'total_amount',
            'admin_commission',
            'integrator_commission',
            'operator_commission',
            'strategy'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($calculationResult[$field])) {
                throw new \InvalidArgumentException("Le champ '{$field}' est requis dans le résultat");
            }
        }

        // Vérifier la cohérence des montants
        $totalCalculated = $calculationResult['admin_commission'] + 
                          $calculationResult['integrator_commission'] + 
                          $calculationResult['operator_commission'];

        $difference = abs($calculationResult['total_amount'] - $totalCalculated);
        
        if ($difference > 0.01) { // Tolérance de 1 centime
            throw new \InvalidArgumentException(
                "Incohérence dans le calcul : total={$calculationResult['total_amount']}, " .
                "calculé={$totalCalculated}, différence={$difference}"
            );
        }

        return true;
    }

    /**
     * Obtient les règles de validation
     * 
     * @return array
     */
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    /**
     * Obtient les messages d'erreur personnalisés
     * 
     * @return array
     */
    public function getCustomMessages(): array
    {
        return $this->customMessages;
    }
}
