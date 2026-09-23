<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated Use UnifiedRevenueCalculationService instead. This class will be removed in a future version.
 */
class RevenueDistributionCalculationService
{
    /**
     * Calcule la répartition complète des revenus basée sur les frais du business profile
     */
    public function calculateRevenueDistribution(Reservation $reservation, float $totalAmount): array
    {
        $chargingPoint = $reservation->chargingPoint;
        $pricingPlan = $reservation->pricingPlan;
        
        if (!$chargingPoint) {
            Log::warning('Aucune borne de recharge trouvée pour la réservation', [
                'reservation_id' => $reservation->id
            ]);
            return $this->getDefaultDistribution($totalAmount);
        }

        // Charger les relations nécessaires
        $chargingPoint->load(['integrator.businessProfile', 'partner.businessProfile', 'businessProfile']);
        
        // Récupérer les business profiles selon la logique corrigée
        $integratorBusinessProfile = $this->getCreatorBusinessProfile($chargingPoint); // Pour les parts admin
        $operatorBusinessProfile = $this->getOperatorBusinessProfile($chargingPoint); // Pour les parts intégrateur
        
        Log::info('Business profiles récupérés', [
            'charging_point_id' => $chargingPoint->id,
            'integrator_business_profile' => $integratorBusinessProfile ? [
                'id' => $integratorBusinessProfile->id,
                'name' => $integratorBusinessProfile->name,
                'base_fee_amount' => $integratorBusinessProfile->base_fee_amount ?? 'N/A'
            ] : 'Aucun',
            'operator_business_profile' => $operatorBusinessProfile ? [
                'id' => $operatorBusinessProfile->id,
                'name' => $operatorBusinessProfile->name
            ] : 'Aucun'
        ]);
        
        if (!$integratorBusinessProfile) {
            Log::info('Aucun business profile intégrateur trouvé pour la borne, création d\'un profil par défaut', [
                'charging_point_id' => $chargingPoint->id,
                'reservation_id' => $reservation->id
            ]);
            
            // Créer un business profile par défaut temporaire
            $integratorBusinessProfile = $this->createDefaultBusinessProfile();
        }

        // Calculer les frais basés sur le business profile intégrateur (pour les parts admin)
        $fees = $this->calculateBusinessProfileFees($reservation, $integratorBusinessProfile, $totalAmount);
        
        // Calculer la répartition des revenus avec les deux business profiles
        $distribution = $this->calculateRevenueBreakdown($reservation, $integratorBusinessProfile, $operatorBusinessProfile, $fees, $totalAmount);

        Log::info('Répartition des revenus calculée', [
            'reservation_id' => $reservation->id,
            'charging_point_id' => $chargingPoint->id,
            'integrator_business_profile_id' => $integratorBusinessProfile->id,
            'integrator_business_profile_name' => $integratorBusinessProfile->name,
            'operator_business_profile_id' => $operatorBusinessProfile?->id,
            'operator_business_profile_name' => $operatorBusinessProfile?->name,
            'total_amount' => $totalAmount,
            'fees' => $fees,
            'distribution' => $distribution
        ]);

        return $distribution;
    }

    /**
     * Récupère le business profile pour le calcul des commissions selon la logique corrigée
     */
    private function getCreatorBusinessProfile(ChargingPoint $chargingPoint): ?BusinessProfile
    {
        // Priorité 1: Business profile direct de la borne
        if ($chargingPoint->business_profile_id) {
            return BusinessProfile::find($chargingPoint->business_profile_id);
        }

        // Priorité 2: Business profile de l'intégrateur créateur de la borne
        if ($chargingPoint->integrator_id) {
            $integrator = $chargingPoint->integrator;
            if ($integrator && $integrator->businessProfile) {
                return $integrator->businessProfile;
            }
        }

        // Priorité 3: Chercher les business profiles créés par des admins et appliqués à l'intégrateur
        if ($chargingPoint->integrator_id) {
            $adminUsers = \App\Models\User::whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })->get();

            foreach ($adminUsers as $adminUser) {
                $adminBusinessProfiles = \App\Models\BusinessProfile::where('created_by_id', $adminUser->id)->get();
                
                foreach ($adminBusinessProfiles as $businessProfile) {
                    $isUsedByIntegrator = \DB::table('business_profile_applications')
                        ->where('business_profile_id', $businessProfile->id)
                        ->where('integrator_id', $chargingPoint->integrator_id)
                        ->where('status', 'applied')
                        ->exists();

                    if ($isUsedByIntegrator) {
                        return $businessProfile;
                    }
                }
            }
        }

        // Priorité 4: Business profile du partenaire
        if ($chargingPoint->partner_id) {
            $partner = $chargingPoint->partner;
            if ($partner && $partner->businessProfile) {
                return $partner->businessProfile;
            }
        }

        // Priorité 5: Business profile du groupe
        if ($chargingPoint->group_id) {
            $group = $chargingPoint->group;
            if ($group) {
                if ($group->integrator_id) {
                    $integrator = $group->integrator;
                    if ($integrator && $integrator->businessProfile) {
                        return $integrator->businessProfile;
                    }
                }
                if ($group->partner_id) {
                    $partner = $group->partner;
                    if ($partner && $partner->businessProfile) {
                        return $partner->businessProfile;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Récupère le business profile de l'opérateur (créateur de la borne) pour les parts intégrateur
     * CORRECTION: Inclure les business profiles des intégrateurs pour les opérateurs attachés
     */
    private function getOperatorBusinessProfile(ChargingPoint $chargingPoint): ?BusinessProfile
    {
        // Priorité 1: Business profile direct de la borne
        if ($chargingPoint->business_profile_id) {
            $businessProfile = BusinessProfile::find($chargingPoint->business_profile_id);
            if ($businessProfile) {
                return $businessProfile;
            }
        }

        // Priorité 2: Business profile de l'intégrateur (si l'opérateur est attaché à un intégrateur)
        if ($chargingPoint->integrator_id) {
            $integrator = $chargingPoint->integrator;
            if ($integrator && $integrator->businessProfile) {
                return $integrator->businessProfile;
            }
        }

        // Priorité 3: Chercher les business profiles créés par l'intégrateur et appliqués à ses opérateurs
        if ($chargingPoint->integrator_id && $chargingPoint->partner_id) {
            $integrator = $chargingPoint->integrator;
            
            if (!$integrator) {
                Log::warning('Intégrateur non trouvé pour le point de charge', [
                    'charging_point_id' => $chargingPoint->id,
                    'integrator_id' => $chargingPoint->integrator_id
                ]);
                return null;
            }
            
            $integratorBusinessProfiles = \App\Models\BusinessProfile::where('created_by_id', $integrator->user_id)->get();
            
            foreach ($integratorBusinessProfiles as $businessProfile) {
                // Vérifier si ce business profile est appliqué au partenaire/opérateur
                $isAppliedToPartner = \DB::table('business_profile_applications')
                    ->where('business_profile_id', $businessProfile->id)
                    ->where('partner_id', $chargingPoint->partner_id)
                    ->where('status', 'applied')
                    ->exists();

                if ($isAppliedToPartner) {
                    return $businessProfile;
                }
            }
        }

        // Priorité 4: Business profile du partenaire (opérateur)
        if ($chargingPoint->partner_id) {
            $partner = $chargingPoint->partner;
            if ($partner && $partner->businessProfile) {
                return $partner->businessProfile;
            }
        }

        // Priorité 5: Chercher un business profile attaché à l'opérateur via le groupe
        if ($chargingPoint->group_id) {
            $group = $chargingPoint->group;
            if ($group) {
                // Chercher le business profile de l'intégrateur du groupe
                if ($group->integrator_id) {
                    $integrator = $group->integrator;
                    if ($integrator && $integrator->businessProfile) {
                        return $integrator->businessProfile;
                    }
                }
                // Chercher le business profile du partenaire du groupe
                if ($group->partner_id) {
                    $partner = $group->partner;
                    if ($partner && $partner->businessProfile) {
                        return $partner->businessProfile;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Calcule les frais basés sur le business profile
     */
    private function calculateBusinessProfileFees(Reservation $reservation, BusinessProfile $businessProfile, float $totalAmount): array
    {
        $fees = [
            'activation_fee' => 0,
            'recharge_fees' => 0,
            'transaction_fees' => 0,
            'total_fees' => 0,
            'breakdown' => []
        ];

        // Log des données du business profile pour diagnostic
        Log::info('Calcul des frais du business profile', [
            'business_profile_id' => $businessProfile->id,
            'business_profile_name' => $businessProfile->name,
            'base_fee_amount' => $businessProfile->base_fee_amount,
            'charge_fee_config_raw' => $businessProfile->charge_fee_config,
            'transaction_fee_config_raw' => $businessProfile->transaction_fee_config,
            'reservation_id' => $reservation->id,
            'total_amount' => $totalAmount
        ]);

        // 1. Frais d'activation (base_fee_amount)
        $fees['activation_fee'] = (float) ($businessProfile->base_fee_amount ?? 0);
        
        // Si les frais d'activation sont à 0, utiliser une valeur par défaut
        if ($fees['activation_fee'] == 0) {
            $fees['activation_fee'] = 1.00; // Frais d'activation par défaut
            Log::info('Frais d\'activation à 0, utilisation de la valeur par défaut: 1.00 EUR', [
                'business_profile_id' => $businessProfile->id,
                'original_value' => $businessProfile->base_fee_amount
            ]);
        }

        // 2. Frais de recharge (charge_fee_config)
        $chargeFeeConfig = $this->parseJsonConfig($businessProfile->charge_fee_config);
        
        // Si la configuration est vide, utiliser des valeurs par défaut
        if (empty($chargeFeeConfig)) {
            $chargeFeeConfig = $this->getDefaultChargeFeeConfig();
            Log::warning('Configuration des frais de recharge vide, utilisation des valeurs par défaut', [
                'business_profile_id' => $businessProfile->id,
                'default_config' => $chargeFeeConfig
            ]);
        }
        
        $fees['recharge_fees'] = $this->calculateRechargeFees($reservation, $chargeFeeConfig, $totalAmount);
        
        // Si les frais de recharge sont à 0, forcer une valeur minimale
        if ($fees['recharge_fees'] == 0) {
            $fees['recharge_fees'] = 0.50; // Frais de recharge par défaut
            Log::info('Frais de recharge à 0, utilisation de la valeur par défaut: 0.50 EUR', [
                'business_profile_id' => $businessProfile->id,
                'charge_fee_config' => $chargeFeeConfig
            ]);
        }

        // 3. Frais de transaction (transaction_fee_config)
        $transactionFeeConfig = $this->parseJsonConfig($businessProfile->transaction_fee_config);
        
        // Si la configuration est vide, utiliser des valeurs par défaut
        if (empty($transactionFeeConfig)) {
            $transactionFeeConfig = $this->getDefaultTransactionFeeConfig();
            Log::warning('Configuration des frais de transaction vide, utilisation des valeurs par défaut', [
                'business_profile_id' => $businessProfile->id,
                'default_config' => $transactionFeeConfig
            ]);
        }
        
        $fees['transaction_fees'] = $this->calculateTransactionFees($transactionFeeConfig, $totalAmount);
        
        // Si les frais de transaction sont à 0, forcer une valeur minimale
        if ($fees['transaction_fees'] == 0) {
            $fees['transaction_fees'] = 0.25; // Frais de transaction par défaut
            Log::info('Frais de transaction à 0, utilisation de la valeur par défaut: 0.25 EUR', [
                'business_profile_id' => $businessProfile->id,
                'transaction_fee_config' => $transactionFeeConfig
            ]);
        }

        // 4. Total des frais
        $fees['total_fees'] = $fees['activation_fee'] + $fees['recharge_fees'] + $fees['transaction_fees'];

        // Log du calcul des frais
        Log::info('Frais calculés', [
            'activation_fee' => $fees['activation_fee'],
            'recharge_fees' => $fees['recharge_fees'],
            'transaction_fees' => $fees['transaction_fees'],
            'total_fees' => $fees['total_fees'],
            'charge_fee_config' => $chargeFeeConfig,
            'transaction_fee_config' => $transactionFeeConfig
        ]);

        // 5. Détail du calcul
        $fees['breakdown'] = [
            'activation_fee' => [
                'amount' => $fees['activation_fee'],
                'source' => 'base_fee_amount',
                'description' => 'Frais d\'activation du business profile'
            ],
            'recharge_fees' => [
                'amount' => $fees['recharge_fees'],
                'source' => 'charge_fee_config',
                'description' => 'Frais de recharge appliqués sur kWh/minutes',
                'config' => $chargeFeeConfig
            ],
            'transaction_fees' => [
                'amount' => $fees['transaction_fees'],
                'source' => 'transaction_fee_config',
                'description' => 'Frais de transaction appliqués sur le total',
                'config' => $transactionFeeConfig
            ]
        ];

        return $fees;
    }

    /**
     * Calcule les frais de recharge basés sur le type de réservation (kWh ou minutes)
     */
    private function calculateRechargeFees(Reservation $reservation, array $chargeFeeConfig, float $totalAmount): float
    {
        $rechargeFees = 0;

        // Frais fixes
        $fixedAmount = (float) ($chargeFeeConfig['fixed_amount'] ?? 0);
        $rechargeFees += $fixedAmount;

        // Frais en pourcentage
        $percentage = (float) ($chargeFeeConfig['percentage'] ?? 0);
        if ($percentage > 0) {
            $rechargeFees += ($totalAmount * $percentage / 100);
        }

        // Frais spécifiques selon le type de réservation
        if ($reservation->reservation_type === 'kwh') {
            // Frais par kWh si configurés
            $perKwhFee = (float) ($chargeFeeConfig['per_kwh_fee'] ?? 0);
            if ($perKwhFee > 0) {
                $rechargeFees += ($reservation->reservation_value * $perKwhFee);
            }
        } elseif ($reservation->reservation_type === 'minute') {
            // Frais par minute si configurés
            $perMinuteFee = (float) ($chargeFeeConfig['per_minute_fee'] ?? 0);
            if ($perMinuteFee > 0) {
                $rechargeFees += ($reservation->reservation_value * $perMinuteFee);
            }
        }

        return round($rechargeFees, 2);
    }

    /**
     * Calcule les frais de transaction appliqués sur le total
     */
    private function calculateTransactionFees(array $transactionFeeConfig, float $totalAmount): float
    {
        $transactionFees = 0;

        // Frais fixes
        $fixedAmount = (float) ($transactionFeeConfig['fixed_amount'] ?? 0);
        $transactionFees += $fixedAmount;

        // Frais en pourcentage
        $percentage = (float) ($transactionFeeConfig['percentage'] ?? 0);
        if ($percentage > 0) {
            $transactionFees += ($totalAmount * $percentage / 100);
        }

        // Appliquer les limites min/max si configurées
        $minimumFee = (float) ($transactionFeeConfig['minimum_fee'] ?? 0);
        $maximumFee = (float) ($transactionFeeConfig['maximum_fee'] ?? 0);

        if ($minimumFee > 0 && $transactionFees < $minimumFee) {
            $transactionFees = $minimumFee;
        }

        if ($maximumFee > 0 && $transactionFees > $maximumFee) {
            $transactionFees = $maximumFee;
        }

        return round($transactionFees, 2);
    }

    /**
     * Calcule la répartition des revenus selon la logique corrigée avec distinction importante
     * DISTINCTION IMPORTANTE :
     * - PART ADMIN : Calculée sur le TOTAL DE LA TRANSACTION
     * - PART INTÉGRATEUR : Calculée sur le TOTAL DE LA TRANSACTION
     * - PART OPÉRATEUR : Le reste après déduction des parts admin et intégrateur
     */
    private function calculateRevenueBreakdown(Reservation $reservation, BusinessProfile $integratorBusinessProfile, ?BusinessProfile $operatorBusinessProfile, array $fees, float $totalAmount): array
    {
        // Déterminer le business profile principal à utiliser
        $primaryBusinessProfile = $operatorBusinessProfile ?? $integratorBusinessProfile;
        
        // LOGIQUE CORRIGÉE AVEC DISTINCTION IMPORTANTE :
        
        // 1. PART ADMIN : Calculée sur le TOTAL DE LA TRANSACTION
        $adminPercentage = (float) ($primaryBusinessProfile->admin_fee_percentage ?? 10.0);
        $adminShare = round($totalAmount * ($adminPercentage / 100), 2);
        
        // 2. PART INTÉGRATEUR : Calculée sur le TOTAL DE LA TRANSACTION
        $integratorPercentage = (float) ($primaryBusinessProfile->integrator_fee_percentage ?? 0.0);
        $integratorShare = round($totalAmount * ($integratorPercentage / 100), 2);
        
        // 3. PART OPÉRATEUR : Le reste après déduction des parts admin et intégrateur
        $operatorShare = $totalAmount - $adminShare - $integratorShare;
        $operatorShare = round($operatorShare, 2);
        
        // S'assurer que l'opérateur ne reçoit pas un montant négatif
        if ($operatorShare < 0) {
            // Ajuster les parts proportionnellement
            $totalParts = $adminShare + $integratorShare;
            if ($totalParts > 0) {
                $adjustmentFactor = $totalAmount / $totalParts;
                $adminShare *= $adjustmentFactor;
                $integratorShare *= $adjustmentFactor;
                $operatorShare = 0;
            }
        }

        $availableRevenue = $totalAmount - $fees['total_fees'];
        
        return [
            'total_amount' => $totalAmount,
            'fees' => $fees,
            'available_revenue' => $availableRevenue,
            'distribution' => [
                'admin_share' => round($adminShare, 2),
                'integrator_share' => round($integratorShare, 2),
                'operator_share' => round($operatorShare, 2)
            ],
            'percentages' => [
                'admin_percentage' => round(($adminShare / $totalAmount) * 100, 2),
                'integrator_percentage' => round(($integratorShare / $totalAmount) * 100, 2),
                'operator_percentage' => round(($operatorShare / $totalAmount) * 100, 2)
            ],
            'integrator_business_profile_id' => $integratorBusinessProfile->id,
            'integrator_business_profile_name' => $integratorBusinessProfile->name,
            'operator_business_profile_id' => $operatorBusinessProfile?->id,
            'operator_business_profile_name' => $operatorBusinessProfile?->name,
            'calculation_method' => 'corrected_business_profile_fees'
        ];
    }

    /**
     * Calcule une commission (pourcentage + montant fixe)
     */
    private function calculateCommission(float $amount, float $percentage, float $fixedAmount): float
    {
        $commission = $fixedAmount;
        if ($percentage > 0) {
            $commission += ($amount * $percentage / 100);
        }
        return $commission;
    }

    /**
     * Parse une configuration JSON
     */
    private function parseJsonConfig($config): array
    {
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Erreur JSON lors du parsing de la configuration', [
                    'config' => $config,
                    'error' => json_last_error_msg()
                ]);
                return [];
            }
            return $decoded ?? [];
        }
        return is_array($config) ? $config : [];
    }

    /**
     * Retourne la configuration par défaut pour les frais de recharge
     */
    private function getDefaultChargeFeeConfig(): array
    {
        return [
            'fixed_amount' => 0.50,
            'percentage' => 2.0,
            'per_kwh_fee' => 0.15,
            'per_minute_fee' => 0.08
        ];
    }

    /**
     * Retourne la configuration par défaut pour les frais de transaction
     */
    private function getDefaultTransactionFeeConfig(): array
    {
        return [
            'fixed_amount' => 0.25,
            'percentage' => 1.5,
            'minimum_fee' => 0.15,
            'maximum_fee' => 3.00
        ];
    }

    /**
     * Crée un business profile par défaut temporaire avec des valeurs réalistes
     */
    private function createDefaultBusinessProfile(): BusinessProfile
    {
        $defaultProfile = new BusinessProfile();
        $defaultProfile->id = 'default-' . time();
        $defaultProfile->name = 'Business Profile Standard (Défaut)';
        $defaultProfile->base_fee_amount = 1.00;
        $defaultProfile->charge_fee_config = json_encode([
            'fixed_amount' => 0.50,
            'percentage' => 2.0,
            'per_kwh_fee' => 0.15,
            'per_minute_fee' => 0.08
        ]);
        $defaultProfile->transaction_fee_config = json_encode([
            'fixed_amount' => 0.25,
            'percentage' => 1.5,
            'minimum_fee' => 0.15,
            'maximum_fee' => 3.00
        ]);
        $defaultProfile->admin_fee_percentage = 10.0;
        $defaultProfile->integrator_fee_percentage = 0.0;
        $defaultProfile->is_public = true;
        
        Log::info('Business profile par défaut créé', [
            'profile_id' => $defaultProfile->id,
            'profile_name' => $defaultProfile->name,
            'base_fee_amount' => $defaultProfile->base_fee_amount,
            'admin_fee_percentage' => $defaultProfile->admin_fee_percentage
        ]);
        
        return $defaultProfile;
    }

    /**
     * Retourne une répartition par défaut si aucun business profile n'est trouvé
     */
    private function getDefaultDistribution(float $totalAmount): array
    {
        // Calculer des frais par défaut réalistes
        $defaultFees = [
            'activation_fee' => 1.00,
            'recharge_fees' => round($totalAmount * 0.02, 2), // 2% du montant total
            'transaction_fees' => round($totalAmount * 0.015, 2), // 1.5% du montant total
            'total_fees' => 0,
            'breakdown' => []
        ];
        
        $defaultFees['total_fees'] = $defaultFees['activation_fee'] + $defaultFees['recharge_fees'] + $defaultFees['transaction_fees'];
        $availableRevenue = $totalAmount - $defaultFees['total_fees'];
        
        Log::info('Utilisation de la répartition par défaut', [
            'total_amount' => $totalAmount,
            'default_fees' => $defaultFees,
            'available_revenue' => $availableRevenue
        ]);
        
        return [
            'total_amount' => $totalAmount,
            'fees' => $defaultFees,
            'available_revenue' => $availableRevenue,
            'distribution' => [
                'admin_commission' => round($totalAmount * 0.10, 2), // 10% du total
                'integrator_commission' => 0,
                'partner_commission' => 0,
                'operator_revenue' => round($availableRevenue * 0.90, 2) // 90% du revenu net
            ],
            'integrator_business_profile_id' => null,
            'integrator_business_profile_name' => 'Default Business Profile',
            'operator_business_profile_id' => null,
            'operator_business_profile_name' => 'Default Business Profile',
            'calculation_method' => 'default_with_fees'
        ];
    }

    /**
     * Applique la répartition des revenus à une transaction
     */
    public function applyRevenueDistributionToTransaction(Transaction $transaction, array $distribution): bool
    {
        try {
            $transaction->update([
                'repartition_breakdown' => $distribution,
                'admin_commission' => $distribution['distribution']['admin_commission'] ?? 0,
                'integrator_commission' => $distribution['distribution']['integrator_commission'] ?? 0,
                'partner_commission' => $distribution['distribution']['partner_commission'] ?? 0,
                'business_profile_fee_breakdown' => $distribution['fees'] ?? null
            ]);

            Log::info('Répartition des revenus appliquée à la transaction', [
                'transaction_id' => $transaction->id,
                'distribution' => $distribution
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'application de la répartition des revenus', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
