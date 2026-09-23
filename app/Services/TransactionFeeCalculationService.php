<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use App\Models\ChargingPoint;

class TransactionFeeCalculationService
{
    /**
     * Calculate all fees for a transaction
     */
    public function calculateAllFees(Transaction $transaction): array
    {
        $fees = [
            'activation_fee' => 0,
            'recharge_fee' => 0,
            'admin_fee' => 0,
            'integrator_fee' => 0,
            'partner_fee' => 0,
            'total_fees' => 0,
            'revenue_to_distribute' => 0,
            'breakdown' => [],
            'source' => ''
        ];

        // Get business profile
        $businessProfile = $this->getBusinessProfile($transaction);
        
        if ($businessProfile) {
            $fees = $this->calculateFeesFromBusinessProfile($transaction, $businessProfile);
        } else {
            $fees = $this->calculateFeesFromPricingPlan($transaction);
        }

        // Calculate revenue to distribute
        $fees['revenue_to_distribute'] = $transaction->price_total - $fees['total_fees'];
        
        return $fees;
    }

    /**
     * Get business profile for transaction
     */
    private function getBusinessProfile(Transaction $transaction): ?BusinessProfile
    {
        // Method 1: From transaction's business_profile_id
        if ($transaction->business_profile_id) {
            return BusinessProfile::find($transaction->business_profile_id);
        }

        // Method 2: From charging point's business profile
        if ($transaction->chargingPoint && $transaction->chargingPoint->business_profile_id) {
            return BusinessProfile::find($transaction->chargingPoint->business_profile_id);
        }

        // Method 3: From charging point's station owner's business profile
        if ($transaction->chargingPoint && 
            $transaction->chargingPoint->station && 
            $transaction->chargingPoint->station->owner && 
            $transaction->chargingPoint->station->owner->businessProfile) {
            return $transaction->chargingPoint->station->owner->businessProfile;
        }

        return null;
    }

    /**
     * Calculate fees from business profile
     */
    private function calculateFeesFromBusinessProfile(Transaction $transaction, BusinessProfile $businessProfile): array
    {
        $fees = [
            'activation_fee' => 0,
            'recharge_fee' => 0,
            'admin_fee' => 0,
            'integrator_fee' => 0,
            'partner_fee' => 0,
            'total_fees' => 0,
            'breakdown' => [],
            'source' => "business profile '{$businessProfile->name}'"
        ];

        // 1. Activation Fee (Frais d'activation)
        $fees['activation_fee'] = (float) ($businessProfile->base_fee_amount ?? 0);

        // 2. Recharge Fee (Frais de recharge)
        $chargeFeeConfig = is_string($businessProfile->charge_fee_config) 
            ? json_decode($businessProfile->charge_fee_config, true) 
            : $businessProfile->charge_fee_config;

        if ($chargeFeeConfig) {
            $rechargeFeeFixed = (float) ($chargeFeeConfig['fixed_amount'] ?? 0);
            $rechargeFeePercentage = (float) ($chargeFeeConfig['percentage'] ?? 0);

            $fees['recharge_fee'] = $rechargeFeeFixed;
            if ($rechargeFeePercentage > 0) {
                $fees['recharge_fee'] += ($transaction->price_total * $rechargeFeePercentage / 100);
            }
        }

        // 3. Admin Fees
        $adminFeeFixed = (float) ($businessProfile->admin_fee_fixed ?? 0);
        $adminFeePercentage = (float) ($businessProfile->admin_fee_percentage ?? 0);

        $fees['admin_fee'] = $adminFeeFixed;
        if ($adminFeePercentage > 0) {
            $fees['admin_fee'] += ($transaction->price_total * $adminFeePercentage / 100);
        }

        // 4. Integrator Fees
        $integratorFeeFixed = (float) ($businessProfile->integrator_fee_fixed ?? 0);
        $integratorFeePercentage = (float) ($businessProfile->integrator_fee_percentage ?? 0);

        $fees['integrator_fee'] = $integratorFeeFixed;
        if ($integratorFeePercentage > 0) {
            $fees['integrator_fee'] += ($transaction->price_total * $integratorFeePercentage / 100);
        }

        // 5. Partner Fees
        $partnerFeeFixed = (float) ($businessProfile->partner_fee_fixed ?? 0);
        $partnerFeePercentage = (float) ($businessProfile->partner_fee_percentage ?? 0);

        $fees['partner_fee'] = $partnerFeeFixed;
        if ($partnerFeePercentage > 0) {
            $fees['partner_fee'] += ($transaction->price_total * $partnerFeePercentage / 100);
        }

        // Calculate total fees
        $fees['total_fees'] = $fees['activation_fee'] + $fees['recharge_fee'] + 
                             $fees['admin_fee'] + $fees['integrator_fee'] + $fees['partner_fee'];

        // Create detailed breakdown
        $fees['breakdown'] = [
            'business_profile_name' => $businessProfile->name,
            'business_profile_id' => $businessProfile->id,
            'activation_fee' => [
                'amount' => $fees['activation_fee'],
                'source' => 'base_fee_amount'
            ],
            'recharge_fee' => [
                'fixed_amount' => $chargeFeeConfig['fixed_amount'] ?? 0,
                'percentage' => $chargeFeeConfig['percentage'] ?? 0,
                'total' => $fees['recharge_fee'],
                'source' => 'charge_fee_config'
            ],
            'admin_fee' => [
                'fixed_amount' => $adminFeeFixed,
                'percentage' => $adminFeePercentage,
                'total' => $fees['admin_fee']
            ],
            'integrator_fee' => [
                'fixed_amount' => $integratorFeeFixed,
                'percentage' => $integratorFeePercentage,
                'total' => $fees['integrator_fee']
            ],
            'partner_fee' => [
                'fixed_amount' => $partnerFeeFixed,
                'percentage' => $partnerFeePercentage,
                'total' => $fees['partner_fee']
            ],
            'commissions' => [
                'operator_commission' => $businessProfile->operator_commission ?? 0,
                'integrator_commission' => $businessProfile->integrator_commission ?? 0,
                'partner_commission' => $businessProfile->partner_commission ?? 0,
            ]
        ];

        return $fees;
    }

    /**
     * Calculate fees from pricing plan (fallback)
     */
    private function calculateFeesFromPricingPlan(Transaction $transaction): array
    {
        $fees = [
            'activation_fee' => 0,
            'recharge_fee' => 0,
            'admin_fee' => 0,
            'integrator_fee' => 0,
            'partner_fee' => 0,
            'total_fees' => 0,
            'breakdown' => [],
            'source' => 'pricing plan'
        ];

        if ($transaction->tariffPlan) {
            $fees['activation_fee'] = (float) ($transaction->tariffPlan->activation_fee ?? 0);
            $fees['total_fees'] = $fees['activation_fee'];
            $fees['source'] = "pricing plan '{$transaction->tariffPlan->name}'";

            $fees['breakdown'] = [
                'pricing_plan_name' => $transaction->tariffPlan->name,
                'pricing_plan_id' => $transaction->tariffPlan->id,
                'activation_fee' => [
                    'amount' => $fees['activation_fee'],
                    'source' => 'pricing_plan'
                ]
            ];
        }

        return $fees;
    }

    /**
     * Apply fees to transaction data for new transactions
     */
    public function applyFeesToTransactionData(array $data, ChargingPoint $chargingPoint, ?PricingPlan $pricingPlan = null): array
    {
        // Get business profile
        $businessProfile = $chargingPoint->business_profile_id ? BusinessProfile::find($chargingPoint->business_profile_id) : null;

        if ($businessProfile) {
            $data = $this->applyBusinessProfileFees($data, $businessProfile);
        } elseif ($pricingPlan) {
            $data = $this->applyPricingPlanFees($data, $pricingPlan);
        }

        return $data;
    }

    /**
     * Apply business profile fees to transaction data
     */
    private function applyBusinessProfileFees(array $data, BusinessProfile $businessProfile): array
    {
        // Set activation fee
        $data['activation_fee'] = (float) ($businessProfile->base_fee_amount ?? 0);
        $data['activation_fee_type'] = 'business_profile';
        $data['activation_fee_business_profile_id'] = $businessProfile->id;

        // Calculate recharge fees
        $chargeFeeConfig = is_string($businessProfile->charge_fee_config) 
            ? json_decode($businessProfile->charge_fee_config, true) 
            : $businessProfile->charge_fee_config;

        $rechargeFeeAmount = 0;
        if ($chargeFeeConfig) {
            $rechargeFeeFixed = (float) ($chargeFeeConfig['fixed_amount'] ?? 0);
            $rechargeFeePercentage = (float) ($chargeFeeConfig['percentage'] ?? 0);

            $rechargeFeeAmount = $rechargeFeeFixed;
            if ($rechargeFeePercentage > 0) {
                $rechargeFeeAmount += ($data['price_total'] * $rechargeFeePercentage / 100);
            }
        }

        // Add fees to total price
        $data['price_total'] += $data['activation_fee'] + $rechargeFeeAmount;

        // Store fee breakdown
        $data['business_profile_fee_breakdown'] = [
            'business_profile_name' => $businessProfile->name,
            'business_profile_id' => $businessProfile->id,
            'activation_fee' => $data['activation_fee'],
            'recharge_fee_fixed' => $chargeFeeConfig['fixed_amount'] ?? 0,
            'recharge_fee_percentage' => $chargeFeeConfig['percentage'] ?? 0,
            'recharge_fee_total' => $rechargeFeeAmount,
            'admin_fee_fixed' => $businessProfile->admin_fee_fixed ?? 0,
            'admin_fee_percentage' => $businessProfile->admin_fee_percentage ?? 0,
            'integrator_fee_fixed' => $businessProfile->integrator_fee_fixed ?? 0,
            'integrator_fee_percentage' => $businessProfile->integrator_fee_percentage ?? 0,
            'partner_fee_fixed' => $businessProfile->partner_fee_fixed ?? 0,
            'partner_fee_percentage' => $businessProfile->partner_fee_percentage ?? 0,
            'operator_commission' => $businessProfile->operator_commission ?? 0,
            'integrator_commission' => $businessProfile->integrator_commission ?? 0,
            'partner_commission' => $businessProfile->partner_commission ?? 0,
        ];

        return $data;
    }

    /**
     * Apply pricing plan fees to transaction data
     */
    private function applyPricingPlanFees(array $data, PricingPlan $pricingPlan): array
    {
        $data['activation_fee'] = (float) ($pricingPlan->activation_fee ?? 0);
        $data['activation_fee_type'] = 'fixed';
        $data['price_total'] += $data['activation_fee'];

        return $data;
    }
}

