<?php

namespace App\Services\Commission\Strategies;

use App\Models\BusinessProfile;

/**
 * Interface pour les stratégies de calcul de commission
 */
interface CommissionCalculationStrategyInterface
{
    /**
     * Calcule les commissions selon la stratégie spécifique
     * 
     * @param BusinessProfile $businessProfile
     * @param float $totalAmount
     * @param array $options
     * @return array
     */
    public function calculate(BusinessProfile $businessProfile, float $totalAmount, array $options = []): array;

    /**
     * Retourne le nom de la stratégie
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Retourne la description de la stratégie
     * 
     * @return string
     */
    public function getDescription(): string;

    /**
     * Valide les paramètres spécifiques à la stratégie
     * 
     * @param BusinessProfile $businessProfile
     * @param float $totalAmount
     * @param array $options
     * @return bool
     */
    public function validate(BusinessProfile $businessProfile, float $totalAmount, array $options = []): bool;
}
