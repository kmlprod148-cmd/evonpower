<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Log;

class ClarifiedRevenueDistributionService
{
    /**
     * Calcule la répartition des revenus selon la logique clarifiée :
     * - Admin : Tous les frais (frais de recharge + frais de transaction)
     * - Intégrateur : Commission calculée sur le revenu après frais admin
     * - Opérateur : Le reste (total - frais admin - commission intégrateur)
     */
    public function calculateClarifiedRevenueDistribution(Reservation $reservation, float $totalAmount): array
    {
        $chargingPoint = $reservation->chargingPoint;
        
        if (!$chargingPoint) {
            Log::warning('Aucune borne de recharge trouvée pour la réservation', [
                'reservation_id' => $reservation->id
            ]);
            return $this->getDefaultDistribution($totalAmount);
        }

        // Récupérer le business profile du créateur de la borne
        $businessProfile = $this->getCreatorBusinessProfile($chargingPoint);
        
        if (!$businessProfile) {
            Log::warning('Aucun business profile trouvé pour le créateur de la borne', [
                'charging_point_id' => $chargingPoint->id,
                'reservation_id' => $reservation->id
            ]);
            return $this->getDefaultDistribution($totalAmount);
        }

        // Calculer les frais basés sur le business profile
        $fees = $this->calculateBusinessProfileFees($reservation, $businessProfile, $totalAmount);
        
        // Calculer la répartition selon la logique clarifiée
        $distribution = $this->calculateClarifiedRevenueBreakdown($reservation, $businessProfile, $fees, $totalAmount);

        Log::info('Répartition clarifiée des revenus calculée', [
            'reservation_id' => $reservation->id,
            'charging_point_id' => $chargingPoint->id,
            'business_profile_id' => $businessProfile->id,
            'total_amount' => $totalAmount,
            'fees' => $fees,
            'distribution' => $distribution
        ]);

        return $distribution;
    }

    /**
     * Récupère le business profile du créateur de la borne
     */
    private function getCreatorBusinessProfile(ChargingPoint $chargingPoint): ?BusinessProfile
    {
        // Priorité 1: Business profile direct de la borne
        if ($chargingPoint->business_profile_id) {
            return BusinessProfile::find($chargingPoint->business_profile_id);
        }

        // Priorité 2: Business profile de l'intégrateur
        if ($chargingPoint->integrator_id) {
            $integrator = $chargingPoint->integrator;
            if ($integrator && $integrator->businessProfile) {
                return $integrator->businessProfile;
            }
        }

        // Priorité 3: Business profile du partenaire
        if ($chargingPoint->partner_id) {
            $partner = $chargingPoint->partner;
            if ($partner && $partner->businessProfile) {
                return $partner->businessProfile;
            }
        }

        // Priorité 4: Business profile du groupe
        if ($chargingPoint->group_id) {
            $group = $chargingPoint->group;
            if ($group) {
                if ($group->integrator && $group->integrator->businessProfile) {
                    return $group->integrator->businessProfile;
                }
                if ($group->partner && $group->partner->businessProfile) {
                    return $group->partner->businessProfile;
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
            'charging_fees' => 0,
            'transaction_fees' => 0,
            'total_fees' => 0
        ];

        // 1. Frais d'activation
        $fees['activation_fee'] = (float) ($businessProfile->base_fee_amount ?? 0);

        // 2. Frais de recharge
        $chargeConfig = is_string($businessProfile->charge_fee_config) 
            ? json_decode($businessProfile->charge_fee_config, true) 
            : $businessProfile->charge_fee_config;

        if ($chargeConfig) {
            $fixedFee = (float) ($chargeConfig['fixed_amount'] ?? 0);
            $percentageFee = (float) ($chargeConfig['percentage'] ?? 0);
            $perKwhFee = (float) ($chargeConfig['per_kwh_fee'] ?? 0);
            $perMinuteFee = (float) ($chargeConfig['per_minute_fee'] ?? 0);

            $fees['charging_fees'] = $fixedFee + ($totalAmount * $percentageFee / 100);

            if ($reservation->reservation_type === 'kwh') {
                $fees['charging_fees'] += ($reservation->reservation_value * $perKwhFee);
            } elseif ($reservation->reservation_type === 'minute') {
                $fees['charging_fees'] += ($reservation->reservation_value * $perMinuteFee);
            }
        }

        // 3. Frais de transaction
        $transactionConfig = is_string($businessProfile->transaction_fee_config) 
            ? json_decode($businessProfile->transaction_fee_config, true) 
            : $businessProfile->transaction_fee_config;

        if ($transactionConfig) {
            $fixedFee = (float) ($transactionConfig['fixed_amount'] ?? 0);
            $percentageFee = (float) ($transactionConfig['percentage'] ?? 0);
            $minimumFee = (float) ($transactionConfig['minimum_fee'] ?? 0);
            $maximumFee = (float) ($transactionConfig['maximum_fee'] ?? 999999);

            $fees['transaction_fees'] = $fixedFee + ($totalAmount * $percentageFee / 100);
            $fees['transaction_fees'] = max($minimumFee, min($maximumFee, $fees['transaction_fees']));
        }

        // 4. Total des frais
        $fees['total_fees'] = $fees['activation_fee'] + $fees['charging_fees'] + $fees['transaction_fees'];

        return $fees;
    }

    /**
     * Calcule la répartition clarifiée des revenus
     * LOGIQUE CLARIFIÉE :
     * - Admin : Tous les frais (frais de recharge + frais de transaction)
     * - Intégrateur : Commission calculée sur le revenu après frais admin
     * - Opérateur : Le reste (total - frais admin - commission intégrateur)
     */
    private function calculateClarifiedRevenueBreakdown(Reservation $reservation, BusinessProfile $businessProfile, array $fees, float $totalAmount): array
    {
        // 1. L'admin reçoit TOUS les frais
        $adminRevenue = $fees['total_fees'];
        
        // 2. Revenu disponible après frais admin
        $revenueAfterAdmin = max(0, $totalAmount - $adminRevenue);

        // 3. Commission intégrateur (calculée sur le revenu après frais admin)
        $integratorPercentage = (float) ($businessProfile->integrator_fee_percentage ?? 0);
        $integratorFixed = (float) ($businessProfile->integrator_fee_fixed ?? 0);
        $integratorRevenue = ($revenueAfterAdmin * $integratorPercentage / 100) + $integratorFixed;

        // 4. Revenu opérateur (le reste)
        $operatorRevenue = max(0, $revenueAfterAdmin - $integratorRevenue);

        // 5. Protection contre les montants négatifs
        if ($operatorRevenue < 0) {
            // Ajuster la commission intégrateur proportionnellement
            if ($revenueAfterAdmin > 0) {
                $integratorRevenue = $revenueAfterAdmin;
                $operatorRevenue = 0;
            } else {
                $integratorRevenue = 0;
                $operatorRevenue = 0;
            }
        }

        return [
            'total_amount' => $totalAmount,
            'fees' => $fees,
            'revenue_after_admin' => round($revenueAfterAdmin, 2),
            'distribution' => [
                'admin' => [
                    'amount' => round($adminRevenue, 2),
                    'percentage' => $totalAmount > 0 ? round(($adminRevenue / $totalAmount) * 100, 2) : 0,
                    'description' => 'Tous les frais (activation + recharge + transaction)'
                ],
                'integrator' => [
                    'amount' => round($integratorRevenue, 2),
                    'percentage' => $revenueAfterAdmin > 0 ? round(($integratorRevenue / $revenueAfterAdmin) * 100, 2) : 0,
                    'description' => 'Commission calculée sur le revenu après frais admin'
                ],
                'operator' => [
                    'amount' => round($operatorRevenue, 2),
                    'percentage' => $revenueAfterAdmin > 0 ? round(($operatorRevenue / $revenueAfterAdmin) * 100, 2) : 0,
                    'description' => 'Le reste après frais admin et commission intégrateur'
                ]
            ],
            'summary' => [
                'admin_gets_fees' => $adminRevenue,
                'integrator_gets_commission_on_remaining' => $integratorRevenue,
                'operator_gets_rest' => $operatorRevenue,
                'total_distributed' => $adminRevenue + $integratorRevenue + $operatorRevenue
            ]
        ];
    }

    /**
     * Distribution par défaut si aucun business profile n'est trouvé
     */
    private function getDefaultDistribution(float $totalAmount): array
    {
        return [
            'total_amount' => $totalAmount,
            'fees' => [
                'activation_fee' => 0,
                'charging_fees' => 0,
                'transaction_fees' => 0,
                'total_fees' => 0
            ],
            'revenue_after_admin' => $totalAmount,
            'distribution' => [
                'admin' => [
                    'amount' => 0,
                    'percentage' => 0,
                    'description' => 'Aucun business profile configuré'
                ],
                'integrator' => [
                    'amount' => 0,
                    'percentage' => 0,
                    'description' => 'Aucun business profile configuré'
                ],
                'operator' => [
                    'amount' => $totalAmount,
                    'percentage' => 100,
                    'description' => 'Montant total (aucun business profile)'
                ]
            ],
            'summary' => [
                'admin_gets_fees' => 0,
                'integrator_gets_commission_on_remaining' => 0,
                'operator_gets_rest' => $totalAmount,
                'total_distributed' => $totalAmount
            ]
        ];
    }

    /**
     * Applique la répartition clarifiée à une transaction
     */
    public function applyClarifiedDistributionToTransaction(Transaction $transaction): bool
    {
        try {
            $reservation = $transaction->reservation;
            if (!$reservation) {
                Log::warning('Aucune réservation trouvée pour la transaction', [
                    'transaction_id' => $transaction->id
                ]);
                return false;
            }

            $distribution = $this->calculateClarifiedRevenueDistribution($reservation, $transaction->price_total);

            // Mettre à jour la transaction avec la répartition
            $transaction->update([
                'admin_revenue' => $distribution['distribution']['admin']['amount'],
                'integrator_revenue' => $distribution['distribution']['integrator']['amount'],
                'operator_revenue' => $distribution['distribution']['operator']['amount'],
                'total_fees' => $distribution['fees']['total_fees'],
                'revenue_distribution_data' => json_encode($distribution)
            ]);

            Log::info('Répartition clarifiée appliquée à la transaction', [
                'transaction_id' => $transaction->id,
                'distribution' => $distribution['distribution']
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Erreur lors de l\'application de la répartition clarifiée', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
