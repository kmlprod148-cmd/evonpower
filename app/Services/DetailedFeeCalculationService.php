<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Log;

class DetailedFeeCalculationService
{
    /**
     * Calculate detailed fees and parts for a charging point
     */
    public function calculateChargingPointFees(ChargingPoint $chargingPoint, float $amount): array
    {
        $businessProfile = $chargingPoint->businessProfile;
        $pricingPlan = $chargingPoint->pricingPlan;
        
        return $this->calculateDetailedFees($amount, $businessProfile, $pricingPlan, $chargingPoint);
    }

    /**
     * Calculate detailed fees and parts for a reservation using corrected logic
     */
    public function calculateReservationFees(Reservation $reservation, float $amount = null): array
    {
        $amount = $amount ?? $reservation->estimated_cost ?? 0;
        
        // Use the corrected RevenueDistributionCalculationService
        $revenueService = app(\App\Services\RevenueDistributionCalculationService::class);
        $correctedDistribution = $revenueService->calculateRevenueDistribution($reservation, $amount);
        
        // Convert to the format expected by the view
        return $this->convertToDetailedFormat($correctedDistribution, $amount);
    }

    /**
     * Convert RevenueDistributionCalculationService format to DetailedFeeCalculationService format
     */
    private function convertToDetailedFormat(array $revenueDistribution, float $amount): array
    {
        $distribution = $revenueDistribution['distribution'];
        $fees = $revenueDistribution['fees'];
        $availableRevenue = $revenueDistribution['available_revenue'] ?? 0;
        
        return [
            'total_amount' => $amount,
            'fees' => [
                'activation_fee' => $fees['activation_fee'] ?? 0,
                'recharge_fees' => $fees['recharge_fees'] ?? 0,
                'transaction_fees' => $fees['transaction_fees'] ?? 0,
                'total_fees' => $fees['total_fees'] ?? 0,
                'details' => $this->createFeeDetails($fees)
            ],
            'distribution' => [
                'admin_part' => $distribution['admin_share'] ?? 0,
                'integrator_part' => $distribution['integrator_share'] ?? 0,
                'partner_part' => 0, // Pas de partenaire dans la nouvelle logique
                'operator_part' => $distribution['operator_share'] ?? 0,
                'available_revenue' => $availableRevenue
            ],
            'percentages' => [
                'admin_percentage' => $amount > 0 ? (($distribution['admin_share'] ?? 0) / $amount) * 100 : 0,
                'integrator_percentage' => $availableRevenue > 0 ? (($distribution['integrator_share'] ?? 0) / $availableRevenue) * 100 : 0,
                'partner_percentage' => 0, // Pas de partenaire dans la nouvelle logique
                'operator_percentage' => $availableRevenue > 0 ? (($distribution['operator_share'] ?? 0) / $availableRevenue) * 100 : 0
            ],
            'breakdown' => $this->createDetailedBreakdownFromRevenue($revenueDistribution),
            'source' => 'corrected_calculation',
            'business_profile_info' => [
                'integrator_business_profile_id' => $revenueDistribution['integrator_business_profile_id'] ?? null,
                'integrator_business_profile_name' => $revenueDistribution['integrator_business_profile_name'] ?? null,
                'operator_business_profile_id' => $revenueDistribution['operator_business_profile_id'] ?? null,
                'operator_business_profile_name' => $revenueDistribution['operator_business_profile_name'] ?? null,
                'calculation_method' => $revenueDistribution['calculation_method'] ?? 'corrected_business_profile_fees'
            ]
        ];
    }

    /**
     * Create fee details from revenue distribution fees
     */
    private function createFeeDetails(array $fees): array
    {
        $details = [];
        
        if (($fees['activation_fee'] ?? 0) > 0) {
            $details[] = [
                'type' => 'activation_fee',
                'label' => 'Frais d\'activation',
                'amount' => $fees['activation_fee'],
                'source' => 'business_profile_integrator',
                'description' => 'Frais appliqués sur business profile intégrateur'
            ];
        }
        
        if (($fees['recharge_fees'] ?? 0) > 0) {
            $details[] = [
                'type' => 'recharge_fees',
                'label' => 'Frais de recharge',
                'amount' => $fees['recharge_fees'],
                'source' => 'business_profile_integrator',
                'description' => 'Frais appliqués sur business profile intégrateur'
            ];
        }
        
        if (($fees['transaction_fees'] ?? 0) > 0) {
            $details[] = [
                'type' => 'transaction_fees',
                'label' => 'Frais de transaction',
                'amount' => $fees['transaction_fees'],
                'source' => 'business_profile_integrator',
                'description' => 'Frais appliqués sur business profile intégrateur'
            ];
        }
        
        return $details;
    }

    /**
     * Create detailed breakdown from revenue distribution
     */
    private function createDetailedBreakdownFromRevenue(array $revenueDistribution): array
    {
        $distribution = $revenueDistribution['distribution'];
        $fees = $revenueDistribution['fees'];
        
        return [
            'scenario_1_admin' => [
                'description' => 'Parts Admin = frais appliqués sur business profile attaché à l\'intégrateur créateur de la borne',
                'business_profile' => $revenueDistribution['integrator_business_profile_name'] ?? 'N/A',
                'total_fees' => $fees['total_fees'] ?? 0,
                'breakdown' => [
                    'activation_fee' => $fees['activation_fee'] ?? 0,
                    'recharge_fees' => $fees['recharge_fees'] ?? 0,
                    'transaction_fees' => $fees['transaction_fees'] ?? 0
                ]
            ],
            'scenario_2_integrator' => [
                'description' => 'Parts Intégrateur = frais appliqués sur business profile attaché à l\'opérateur (créateur de la borne)',
                'business_profile' => $revenueDistribution['operator_business_profile_name'] ?? 'N/A',
                'integrator_commission' => $distribution['integrator_share'] ?? 0,
                'partner_commission' => 0 // Pas de partenaire dans la nouvelle logique
            ],
            'operator_revenue' => [
                'description' => 'Revenu opérateur après déduction des commissions',
                'amount' => $distribution['operator_share'] ?? 0
            ]
        ];
    }

    /**
     * Calculate detailed fees and parts for a transaction
     */
    public function calculateTransactionFees(Transaction $transaction): array
    {
        $amount = $transaction->price_total ?? 0;
        $chargingPoint = $transaction->chargingPoint;
        $businessProfile = $chargingPoint->businessProfile;
        $pricingPlan = $transaction->tariffPlan;
        
        return $this->calculateDetailedFees($amount, $businessProfile, $pricingPlan, $chargingPoint);
    }

    /**
     * Main calculation method
     */
    private function calculateDetailedFees(float $amount, ?BusinessProfile $businessProfile, ?PricingPlan $pricingPlan, ChargingPoint $chargingPoint): array
    {
        $result = [
            'total_amount' => $amount,
            'fees' => [
                'activation_fee' => 0,
                'recharge_fees' => 0,
                'transaction_fees' => 0,
                'total_fees' => 0,
                'details' => []
            ],
            'distribution' => [
                'admin_part' => 0,
                'integrator_part' => 0,
                'partner_part' => 0,
                'operator_part' => 0,
                'available_revenue' => 0
            ],
            'percentages' => [
                'admin_percentage' => 0,
                'integrator_percentage' => 0,
                'partner_percentage' => 0,
                'operator_percentage' => 0
            ],
            'breakdown' => [],
            'source' => 'calculation'
        ];

        // Calculate activation fee from pricing plan
        if ($pricingPlan) {
            $result['fees']['activation_fee'] = (float) ($pricingPlan->activation_fee ?? 0);
            $result['fees']['details'][] = [
                'type' => 'activation_fee',
                'label' => 'Frais d\'activation',
                'amount' => $result['fees']['activation_fee'],
                'source' => 'pricing_plan',
                'description' => "Plan tarifaire: {$pricingPlan->name}"
            ];
        }

        // Calculate business profile fees
        if ($businessProfile) {
            $result = $this->calculateBusinessProfileFees($result, $businessProfile, $amount);
        } else {
            // Default fees if no business profile
            $result = $this->calculateDefaultFees($result, $amount);
        }

        // Calculate total fees
        $result['fees']['total_fees'] = $result['fees']['activation_fee'] + 
                                     $result['fees']['recharge_fees'] + 
                                     $result['fees']['transaction_fees'];

        // Calculate available revenue
        $result['distribution']['available_revenue'] = max(0, $amount - $result['fees']['total_fees']);

        // Calculate distribution parts
        $result = $this->calculateDistributionParts($result, $businessProfile);

        // Calculate percentages
        $result = $this->calculatePercentages($result);

        // Create detailed breakdown
        $result['breakdown'] = $this->createDetailedBreakdown($result);

        return $result;
    }

    /**
     * Calculate business profile specific fees
     */
    private function calculateBusinessProfileFees(array $result, BusinessProfile $businessProfile, float $amount): array
    {
        // Charging fees from JSON config
        $chargeFeeConfig = $businessProfile->charge_fee_config ?? [];
        $chargingFeeFixed = (float) ($chargeFeeConfig['fixed_amount'] ?? 0);
        $chargingFeePercentage = (float) ($chargeFeeConfig['percentage'] ?? 0);
        $chargingFeeAmount = $chargingFeeFixed + ($amount * $chargingFeePercentage / 100);
        
        $result['fees']['recharge_fees'] = $chargingFeeAmount;
        $result['fees']['details'][] = [
            'type' => 'charging_fees',
            'label' => 'Frais de recharge',
            'amount' => $chargingFeeAmount,
            'source' => 'business_profile',
            'description' => "Frais fixes: {$chargingFeeFixed} EUR + {$chargingFeePercentage}%"
        ];

        // Transaction fees from JSON config
        $transactionFeeConfig = $businessProfile->transaction_fee_config ?? [];
        $transactionFeeFixed = (float) ($transactionFeeConfig['fixed_amount'] ?? 0);
        $transactionFeePercentage = (float) ($transactionFeeConfig['percentage'] ?? 0);
        $transactionFeeAmount = $transactionFeeFixed + ($amount * $transactionFeePercentage / 100);
        
        $result['fees']['transaction_fees'] = $transactionFeeAmount;
        $result['fees']['details'][] = [
            'type' => 'transaction_fees',
            'label' => 'Frais de transaction',
            'amount' => $transactionFeeAmount,
            'source' => 'business_profile',
            'description' => "Frais fixes: {$transactionFeeFixed} EUR + {$transactionFeePercentage}%"
        ];

        return $result;
    }

    /**
     * Calculate default fees when no business profile exists
     */
    private function calculateDefaultFees(array $result, float $amount): array
    {
        // Default 10% transaction fee
        $defaultTransactionFee = $amount * 0.10;
        $result['fees']['transaction_fees'] = $defaultTransactionFee;
        $result['fees']['details'][] = [
            'type' => 'transaction_fees',
            'label' => 'Frais de transaction',
            'amount' => $defaultTransactionFee,
            'source' => 'default',
            'description' => 'Frais par défaut: 10%'
        ];

        return $result;
    }

    /**
     * Calculate distribution parts based on business profile
     */
    private function calculateDistributionParts(array $result, ?BusinessProfile $businessProfile): array
    {
        $availableRevenue = $result['distribution']['available_revenue'];

        if ($businessProfile) {
            // Integrator part
            $integratorFeeFixed = (float) ($businessProfile->integrator_fee_fixed ?? 0);
            $integratorFeePercentage = (float) ($businessProfile->integrator_fee_percentage ?? 0);
            $result['distribution']['integrator_part'] = $integratorFeeFixed + ($availableRevenue * $integratorFeePercentage / 100);

            // Partner part
            $partnerFeeFixed = (float) ($businessProfile->partner_fee_fixed ?? 0);
            $partnerFeePercentage = (float) ($businessProfile->partner_fee_percentage ?? 0);
            $result['distribution']['partner_part'] = $partnerFeeFixed + ($availableRevenue * $partnerFeePercentage / 100);

            // Operator part (remaining)
            $result['distribution']['operator_part'] = max(0, $availableRevenue - 
                $result['distribution']['integrator_part'] - 
                $result['distribution']['partner_part']);
        } else {
            // Default distribution: 20% integrator, 20% partner, 60% operator
            $result['distribution']['integrator_part'] = $availableRevenue * 0.20;
            $result['distribution']['partner_part'] = $availableRevenue * 0.20;
            $result['distribution']['operator_part'] = $availableRevenue * 0.60;
        }

        // Admin part is the total fees
        $result['distribution']['admin_part'] = $result['fees']['total_fees'];

        return $result;
    }

    /**
     * Calculate percentages for display
     */
    private function calculatePercentages(array $result): array
    {
        $totalAmount = $result['total_amount'];
        $availableRevenue = $result['distribution']['available_revenue'];

        if ($totalAmount > 0) {
            $result['percentages']['admin_percentage'] = ($result['distribution']['admin_part'] / $totalAmount) * 100;
        }

        if ($availableRevenue > 0) {
            $result['percentages']['integrator_percentage'] = ($result['distribution']['integrator_part'] / $availableRevenue) * 100;
            $result['percentages']['partner_percentage'] = ($result['distribution']['partner_part'] / $availableRevenue) * 100;
            $result['percentages']['operator_percentage'] = ($result['distribution']['operator_part'] / $availableRevenue) * 100;
        }

        return $result;
    }

    /**
     * Create detailed breakdown for display
     */
    private function createDetailedBreakdown(array $result): array
    {
        return [
            'summary' => [
                'total_amount' => $result['total_amount'],
                'total_fees' => $result['fees']['total_fees'],
                'available_revenue' => $result['distribution']['available_revenue'],
                'currency' => 'EUR'
            ],
            'fees_breakdown' => $result['fees']['details'],
            'distribution_breakdown' => [
                [
                    'type' => 'admin',
                    'label' => 'Admin',
                    'amount' => $result['distribution']['admin_part'],
                    'percentage' => $result['percentages']['admin_percentage'],
                    'color' => 'red',
                    'icon' => 'user'
                ],
                [
                    'type' => 'integrator',
                    'label' => 'Intégrateur',
                    'amount' => $result['distribution']['integrator_part'],
                    'percentage' => $result['percentages']['integrator_percentage'],
                    'color' => 'blue',
                    'icon' => 'building'
                ],
                [
                    'type' => 'partner',
                    'label' => 'Partenaire',
                    'amount' => $result['distribution']['partner_part'],
                    'percentage' => $result['percentages']['partner_percentage'],
                    'color' => 'green',
                    'icon' => 'users'
                ],
                [
                    'type' => 'operator',
                    'label' => 'Opérateur',
                    'amount' => $result['distribution']['operator_part'],
                    'percentage' => $result['percentages']['operator_percentage'],
                    'color' => 'purple',
                    'icon' => 'bolt'
                ]
            ]
        ];
    }

    /**
     * Get fee display data for charging point
     */
    public function getChargingPointFeeDisplay(ChargingPoint $chargingPoint): array
    {
        $businessProfile = $chargingPoint->businessProfile;
        $pricingPlan = $chargingPoint->pricingPlan;

        return [
            'has_business_profile' => !is_null($businessProfile),
            'has_pricing_plan' => !is_null($pricingPlan),
            'business_profile_name' => $businessProfile ? $businessProfile->name : null,
            'pricing_plan_name' => $pricingPlan ? $pricingPlan->name : null,
            'activation_fee' => $pricingPlan ? (float) ($pricingPlan->activation_fee ?? 0) : 0,
            'charging_fees' => $businessProfile ? [
                'fixed' => (float) (($businessProfile->charge_fee_config['fixed_amount'] ?? 0)),
                'percentage' => (float) (($businessProfile->charge_fee_config['percentage'] ?? 0))
            ] : null,
            'transaction_fees' => $businessProfile ? [
                'fixed' => (float) (($businessProfile->transaction_fee_config['fixed_amount'] ?? 0)),
                'percentage' => (float) (($businessProfile->transaction_fee_config['percentage'] ?? 0))
            ] : null,
            'distribution' => $businessProfile ? [
                'integrator' => [
                    'fixed' => (float) ($businessProfile->integrator_fee_fixed ?? 0),
                    'percentage' => (float) ($businessProfile->integrator_fee_percentage ?? 0)
                ],
                'partner' => [
                    'fixed' => (float) ($businessProfile->partner_fee_fixed ?? 0),
                    'percentage' => (float) ($businessProfile->partner_fee_percentage ?? 0)
                ]
            ] : null
        ];
    }
}
