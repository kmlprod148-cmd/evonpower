<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Service unifié pour le calcul des revenus et commissions
 * 
 * Ce service consolide :
 * - RevenueDistributionCalculationService (calcul complet des revenus)
 * - FinancialDistributionService (distribution financière basique)
 * - Méthodes de calcul de FinancialService
 * 
 * Fonctionnalités :
 * - Calcul des frais (activation, recharge, transaction)
 * - Répartition des revenus (admin, intégrateur, partenaire, opérateur)
 * - Calcul des commissions unifié avec Strategy Pattern
 */
class UnifiedRevenueCalculationService
{
    // ========================================================================
    // MAIN CALCULATION METHODS
    // ========================================================================

    /**
     * Calculer la répartition complète des revenus pour une réservation
     */
    public function calculateRevenueDistribution(Reservation $reservation, float $totalAmount): array
    {
        $chargingPoint = $reservation->chargingPoint;
        
        if (!$chargingPoint) {
            Log::warning('UnifiedRevenue: Aucune borne trouvée', [
                'reservation_id' => $reservation->id
            ]);
            return $this->getDefaultDistribution($totalAmount);
        }

        $chargingPoint->load(['integrator.businessProfile', 'partner.businessProfile', 'businessProfile']);
        
        $creatorProfile = $this->getCreatorBusinessProfile($chargingPoint);
        $operatorProfile = $this->getOperatorBusinessProfile($chargingPoint);
        
        Log::info('UnifiedRevenue: Business profiles récupérés', [
            'charging_point_id' => $chargingPoint->id,
            'creator_profile' => $creatorProfile?->name,
            'operator_profile' => $operatorProfile?->name
        ]);
        
        if (!$creatorProfile) {
            $creatorProfile = $this->createDefaultBusinessProfile();
        }

        $fees = $this->calculateBusinessProfileFees($reservation, $creatorProfile, $totalAmount);
        $distribution = $this->calculateRevenueBreakdown($reservation, $creatorProfile, $operatorProfile, $fees, $totalAmount);

        Log::info('UnifiedRevenue: Répartition calculée', [
            'reservation_id' => $reservation->id,
            'total_amount' => $totalAmount,
            'distribution' => $distribution
        ]);

        return $distribution;
    }

    /**
     * Calculer la distribution pour une transaction
     */
    public function calculateTransactionDistribution(Transaction $transaction): array
    {
        $businessProfile = $transaction->businessProfile;
        $amount = $transaction->amount;

        if (!$businessProfile) {
            return $this->getDefaultDistribution($amount);
        }

        $breakdown = [
            'operator' => round($amount * ($businessProfile->operator_commission ?? 0) / 100, 2),
            'integrator' => round($amount * ($businessProfile->integrator_commission ?? 0) / 100, 2),
            'owner' => round($amount * ($businessProfile->owner_commission ?? 0) / 100, 2),
        ];

        $transaction->repartition_breakdown = $breakdown;
        $transaction->save();

        return $breakdown;
    }

    // ========================================================================
    // FEE CALCULATIONS
    // ========================================================================

    /**
     * Calculer les frais basés sur le business profile
     */
    public function calculateBusinessProfileFees(Reservation $reservation, BusinessProfile $businessProfile, float $totalAmount): array
    {
        $fees = [
            'activation_fee' => 0,
            'recharge_fee' => 0,
            'transaction_fee' => 0,
            'total_fees' => 0
        ];

        // Frais d'activation (fixe)
        if ($businessProfile->activation_fee_enabled ?? false) {
            $fees['activation_fee'] = $businessProfile->activation_fee_amount ?? 0;
        }

        // Frais de recharge (pourcentage)
        if ($businessProfile->recharge_fee_enabled ?? false) {
            $rechargePercentage = $businessProfile->recharge_fee_percentage ?? 0;
            $fees['recharge_fee'] = round($totalAmount * $rechargePercentage / 100, 2);
        }

        // Frais de transaction (fixe par transaction)
        if ($businessProfile->transaction_fee_enabled ?? false) {
            $fees['transaction_fee'] = $businessProfile->transaction_fee_amount ?? 0;
        }

        $fees['total_fees'] = $fees['activation_fee'] + $fees['recharge_fee'] + $fees['transaction_fee'];

        return $fees;
    }

    /**
     * Calculer la répartition détaillée des revenus
     */
    protected function calculateRevenueBreakdown(
        Reservation $reservation, 
        BusinessProfile $creatorProfile, 
        ?BusinessProfile $operatorProfile, 
        array $fees, 
        float $totalAmount
    ): array {
        $netAmount = $totalAmount - $fees['total_fees'];

        // Pourcentages par défaut
        $adminPercentage = $creatorProfile->admin_percentage ?? 5;
        $integratorPercentage = $creatorProfile->integrator_percentage ?? 10;
        $partnerPercentage = $operatorProfile->partner_percentage ?? $creatorProfile->partner_percentage ?? 15;
        $operatorPercentage = 100 - $adminPercentage - $integratorPercentage - $partnerPercentage;

        $distribution = [
            'total_amount' => $totalAmount,
            'fees' => $fees,
            'net_amount' => $netAmount,
            'breakdown' => [
                'admin' => [
                    'percentage' => $adminPercentage,
                    'amount' => round($netAmount * $adminPercentage / 100, 2),
                    'label' => 'Part Administrateur'
                ],
                'integrator' => [
                    'percentage' => $integratorPercentage,
                    'amount' => round($netAmount * $integratorPercentage / 100, 2),
                    'label' => 'Part Intégrateur'
                ],
                'partner' => [
                    'percentage' => $partnerPercentage,
                    'amount' => round($netAmount * $partnerPercentage / 100, 2),
                    'label' => 'Part Partenaire'
                ],
                'operator' => [
                    'percentage' => $operatorPercentage,
                    'amount' => round($netAmount * $operatorPercentage / 100, 2),
                    'label' => 'Part Opérateur'
                ]
            ],
            'business_profiles' => [
                'creator' => [
                    'id' => $creatorProfile->id,
                    'name' => $creatorProfile->name
                ],
                'operator' => $operatorProfile ? [
                    'id' => $operatorProfile->id,
                    'name' => $operatorProfile->name
                ] : null
            ]
        ];

        return $distribution;
    }

    // ========================================================================
    // BUSINESS PROFILE RESOLUTION
    // ========================================================================

    /**
     * Récupérer le business profile créateur (pour les parts admin)
     */
    public function getCreatorBusinessProfile(ChargingPoint $chargingPoint): ?BusinessProfile
    {
        // Priorité 1: Business profile direct de la borne
        if ($chargingPoint->business_profile_id) {
            return BusinessProfile::find($chargingPoint->business_profile_id);
        }

        // Priorité 2: Business profile de l'intégrateur
        if ($chargingPoint->integrator_id) {
            $integrator = $chargingPoint->integrator;
            if ($integrator?->businessProfile) {
                return $integrator->businessProfile;
            }
        }

        // Priorité 3: Business profiles admin appliqués à l'intégrateur
        if ($chargingPoint->integrator_id) {
            $adminProfile = $this->findAdminAppliedProfile($chargingPoint->integrator_id);
            if ($adminProfile) {
                return $adminProfile;
            }
        }

        // Priorité 4: Business profile du partenaire
        if ($chargingPoint->partner_id) {
            $partner = $chargingPoint->partner;
            if ($partner?->businessProfile) {
                return $partner->businessProfile;
            }
        }

        // Priorité 5: Business profile du groupe
        if ($chargingPoint->group_id) {
            $groupProfile = $this->getGroupBusinessProfile($chargingPoint);
            if ($groupProfile) {
                return $groupProfile;
            }
        }

        return null;
    }

    /**
     * Récupérer le business profile opérateur
     */
    public function getOperatorBusinessProfile(ChargingPoint $chargingPoint): ?BusinessProfile
    {
        // Priorité 1: Business profile direct
        if ($chargingPoint->business_profile_id) {
            return BusinessProfile::find($chargingPoint->business_profile_id);
        }

        // Priorité 2: Business profile de l'intégrateur
        if ($chargingPoint->integrator_id) {
            $integrator = $chargingPoint->integrator;
            if ($integrator?->businessProfile) {
                return $integrator->businessProfile;
            }
        }

        // Priorité 3: Business profile intégrateur appliqué au partenaire
        if ($chargingPoint->integrator_id && $chargingPoint->partner_id) {
            $integratorProfile = $this->findIntegratorAppliedProfile(
                $chargingPoint->integrator_id, 
                $chargingPoint->partner_id
            );
            if ($integratorProfile) {
                return $integratorProfile;
            }
        }

        // Priorité 4: Business profile du partenaire
        if ($chargingPoint->partner_id) {
            $partner = $chargingPoint->partner;
            if ($partner?->businessProfile) {
                return $partner->businessProfile;
            }
        }

        return null;
    }

    /**
     * Trouver un business profile admin appliqué
     */
    protected function findAdminAppliedProfile(int $integratorId): ?BusinessProfile
    {
        $adminUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->get();

        foreach ($adminUsers as $admin) {
            $profile = BusinessProfile::where('creator_id', $admin->id)
                ->whereExists(function($query) use ($integratorId) {
                    $query->select(DB::raw(1))
                        ->from('business_profile_applications')
                        ->whereColumn('business_profile_applications.business_profile_id', 'business_profiles.id')
                        ->where('integrator_id', $integratorId)
                        ->where('status', 'applied');
                })
                ->first();

            if ($profile) {
                return $profile;
            }
        }

        return null;
    }

    /**
     * Trouver un business profile intégrateur appliqué au partenaire
     */
    protected function findIntegratorAppliedProfile(int $integratorId, int $partnerId): ?BusinessProfile
    {
        $integrator = \App\Models\Integrator::find($integratorId);
        
        if (!$integrator) {
            return null;
        }

        return BusinessProfile::where('creator_id', $integrator->user_id)
            ->whereExists(function($query) use ($partnerId) {
                $query->select(DB::raw(1))
                    ->from('business_profile_applications')
                    ->whereColumn('business_profile_applications.business_profile_id', 'business_profiles.id')
                    ->where('partner_id', $partnerId)
                    ->where('status', 'applied');
            })
            ->first();
    }

    /**
     * Récupérer le business profile du groupe
     */
    protected function getGroupBusinessProfile(ChargingPoint $chargingPoint): ?BusinessProfile
    {
        $group = $chargingPoint->group;
        
        if (!$group) {
            return null;
        }

        if ($group->integrator_id) {
            $integrator = $group->integrator;
            if ($integrator?->businessProfile) {
                return $integrator->businessProfile;
            }
        }

        if ($group->partner_id) {
            $partner = $group->partner;
            if ($partner?->businessProfile) {
                return $partner->businessProfile;
            }
        }

        return null;
    }

    // ========================================================================
    // DEFAULT VALUES
    // ========================================================================

    /**
     * Créer un business profile par défaut
     */
    protected function createDefaultBusinessProfile(): BusinessProfile
    {
        return new BusinessProfile([
            'id' => 0,
            'name' => 'Profil par défaut',
            'admin_percentage' => 5,
            'integrator_percentage' => 10,
            'partner_percentage' => 15,
            'operator_percentage' => 70,
            'activation_fee_enabled' => false,
            'recharge_fee_enabled' => false,
            'transaction_fee_enabled' => false
        ]);
    }

    /**
     * Obtenir une distribution par défaut
     */
    protected function getDefaultDistribution(float $totalAmount): array
    {
        $netAmount = $totalAmount;

        return [
            'total_amount' => $totalAmount,
            'fees' => [
                'activation_fee' => 0,
                'recharge_fee' => 0,
                'transaction_fee' => 0,
                'total_fees' => 0
            ],
            'net_amount' => $netAmount,
            'breakdown' => [
                'admin' => [
                    'percentage' => 5,
                    'amount' => round($netAmount * 0.05, 2),
                    'label' => 'Part Administrateur'
                ],
                'integrator' => [
                    'percentage' => 10,
                    'amount' => round($netAmount * 0.10, 2),
                    'label' => 'Part Intégrateur'
                ],
                'partner' => [
                    'percentage' => 15,
                    'amount' => round($netAmount * 0.15, 2),
                    'label' => 'Part Partenaire'
                ],
                'operator' => [
                    'percentage' => 70,
                    'amount' => round($netAmount * 0.70, 2),
                    'label' => 'Part Opérateur'
                ]
            ],
            'business_profiles' => [
                'creator' => null,
                'operator' => null
            ],
            'is_default' => true
        ];
    }

    // ========================================================================
    // COMMISSION CALCULATIONS
    // ========================================================================

    /**
     * Calculer les commissions pour un montant donné
     */
    public function calculateCommissions(float $amount, BusinessProfile $businessProfile): array
    {
        return [
            'operator' => round($amount * ($businessProfile->operator_commission ?? 70) / 100, 2),
            'integrator' => round($amount * ($businessProfile->integrator_commission ?? 10) / 100, 2),
            'partner' => round($amount * ($businessProfile->partner_commission ?? 15) / 100, 2),
            'admin' => round($amount * ($businessProfile->admin_commission ?? 5) / 100, 2)
        ];
    }

    /**
     * Calculer et sauvegarder les commissions pour une transaction
     */
    public function calculateAndSaveCommissions(Transaction $transaction): array
    {
        $distribution = $this->calculateTransactionDistribution($transaction);
        
        $transaction->update([
            'repartition_breakdown' => $distribution,
            'commissions_calculated_at' => now()
        ]);

        return $distribution;
    }
}
