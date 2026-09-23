<?php

namespace App\Services;

use App\Models\ChargingSession;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Log;

class ChargingSessionCostService
{
    /**
     * Calculate estimated cost for a charging session
     */
    public static function calculateEstimatedCost(ChargingSession $session): float
    {
        $chargingPoint = $session->chargingPoint;
        $businessProfile = $chargingPoint->businessProfile;
        
        if (!$businessProfile) {
            throw new \Exception('No business profile found for charging point');
        }

        // Get pricing plan
        $pricingPlan = $businessProfile->pricingPlan;
        if (!$pricingPlan) {
            throw new \Exception('No pricing plan found for business profile');
        }

        // Estimate energy consumption based on session parameters
        $estimatedEnergy = self::estimateEnergyConsumption($session);
        
        // Calculate base cost
        $baseCost = self::calculateBaseCost($pricingPlan, $estimatedEnergy);
        
        // Apply business profile fees
        $totalCost = self::applyBusinessProfileFees($businessProfile, $baseCost);
        
        return round($totalCost, 2);
    }

    /**
     * Calculate actual cost for a completed charging session
     */
    public static function calculateActualCost(ChargingSession $session): float
    {
        $chargingPoint = $session->chargingPoint;
        $businessProfile = $chargingPoint->businessProfile;
        
        if (!$businessProfile) {
            throw new \Exception('No business profile found for charging point');
        }

        // Get pricing plan
        $pricingPlan = $businessProfile->pricingPlan;
        if (!$pricingPlan) {
            throw new \Exception('No pricing plan found for business profile');
        }

        // Calculate base cost using actual energy consumption
        $baseCost = self::calculateBaseCost($pricingPlan, $session->energy_consumed);
        
        // Apply business profile fees
        $totalCost = self::applyBusinessProfileFees($businessProfile, $baseCost);
        
        return round($totalCost, 2);
    }

    /**
     * Estimate energy consumption for a session
     */
    protected static function estimateEnergyConsumption(ChargingSession $session): float
    {
        // Default estimation based on session parameters
        $estimatedDuration = $session->metadata['estimated_duration'] ?? 30; // minutes
        $estimatedPower = $session->metadata['estimated_power'] ?? 22; // kW
        
        // Convert duration to hours and calculate energy
        $durationHours = $estimatedDuration / 60;
        $estimatedEnergy = $estimatedPower * $durationHours;
        
        return round($estimatedEnergy, 2);
    }

    /**
     * Calculate base cost using pricing plan
     */
    protected static function calculateBaseCost(PricingPlan $pricingPlan, float $energyConsumed): float
    {
        $baseCost = 0;
        
        // Energy cost
        if ($pricingPlan->energy_price_per_kwh) {
            $baseCost += $energyConsumed * $pricingPlan->energy_price_per_kwh;
        }
        
        // Time cost (if applicable)
        if ($pricingPlan->time_price_per_minute) {
            $duration = $pricingPlan->time_price_per_minute;
            $baseCost += $duration * $pricingPlan->time_price_per_minute;
        }
        
        // Session cost (if applicable)
        if ($pricingPlan->session_price) {
            $baseCost += $pricingPlan->session_price;
        }
        
        return $baseCost;
    }

    /**
     * Calculer le coût détaillé avec tous les tarifs (prix/min, prix/kWh, prix fixe, TVA)
     * 
     * @param ChargingPoint $chargingPoint
     * @param float $energyConsumed kWh consommés
     * @param float $durationMinutes Durée en minutes
     * @return array Détail complet du coût
     */
    public static function calculateDetailedCost(ChargingPoint $chargingPoint, float $energyConsumed, float $durationMinutes): array
    {
        $businessProfile = $chargingPoint->businessProfile;
        if (!$businessProfile) {
            throw new \Exception('No business profile found for charging point');
        }

        $pricingPlan = $businessProfile->pricingPlan;
        if (!$pricingPlan) {
            throw new \Exception('No pricing plan found for business profile');
        }

        $breakdown = [
            'activation_fee' => 0,
            'energy_cost' => 0,
            'time_cost' => 0,
            'fixed_cost' => 0,
            'subtotal' => 0,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total_with_vat' => 0,
            'currency' => $pricingPlan->currency ?? 'EUR',
        ];

        // 1. Frais d'activation (prix fixe par session)
        if ($pricingPlan->activation_fee) {
            $breakdown['activation_fee'] = (float) $pricingPlan->activation_fee;
        }

        // 2. Coût par kWh
        if ($pricingPlan->price_per_kwh && $energyConsumed > 0) {
            $breakdown['energy_cost'] = $energyConsumed * (float) $pricingPlan->price_per_kwh;
        }

        // 3. Coût par minute
        if ($pricingPlan->price_per_minute && $durationMinutes > 0) {
            $breakdown['time_cost'] = $durationMinutes * (float) $pricingPlan->price_per_minute;
        }

        // 4. Prix fixe (si applicable)
        if ($pricingPlan->fixed_price) {
            $breakdown['fixed_cost'] = (float) $pricingPlan->fixed_price;
        }

        // 5. Sous-total HT
        $breakdown['subtotal'] = $breakdown['activation_fee'] 
            + $breakdown['energy_cost'] 
            + $breakdown['time_cost'] 
            + $breakdown['fixed_cost'];

        // 6. Appliquer la TVA
        $vatRate = $pricingPlan->vatRate;
        if ($vatRate && $vatRate->rate > 0) {
            $breakdown['vat_rate'] = (float) $vatRate->rate;
            $breakdown['vat_amount'] = $breakdown['subtotal'] * ($vatRate->rate / 100);
            $breakdown['total_with_vat'] = $breakdown['subtotal'] + $breakdown['vat_amount'];
        } else {
            $breakdown['total_with_vat'] = $breakdown['subtotal'];
        }

        // Arrondir tous les montants à 2 décimales
        foreach ($breakdown as $key => $value) {
            if (is_float($value) || is_numeric($value)) {
                $breakdown[$key] = round((float) $value, 2);
            }
        }

        // Ajouter aussi un total simple pour compatibilité
        $breakdown['total'] = $breakdown['total_with_vat'];

        return $breakdown;
    }

    /**
     * Apply business profile fees to base cost
     */
    protected static function applyBusinessProfileFees(BusinessProfile $businessProfile, float $baseCost): float
    {
        $totalCost = $baseCost;
        
        // Admin fee
        if ($businessProfile->admin_fee_percentage) {
            $totalCost += $baseCost * ($businessProfile->admin_fee_percentage / 100);
        }
        if ($businessProfile->admin_fee_fixed) {
            $totalCost += $businessProfile->admin_fee_fixed;
        }
        
        // Integrator fee
        if ($businessProfile->integrator_fee_percentage) {
            $totalCost += $baseCost * ($businessProfile->integrator_fee_percentage / 100);
        }
        if ($businessProfile->integrator_fee_fixed) {
            $totalCost += $businessProfile->integrator_fee_fixed;
        }
        
        // Partner fee
        if ($businessProfile->partner_fee_percentage) {
            $totalCost += $baseCost * ($businessProfile->partner_fee_percentage / 100);
        }
        if ($businessProfile->partner_fee_fixed) {
            $totalCost += $businessProfile->partner_fee_fixed;
        }
        
        return $totalCost;
    }

    /**
     * Update session with calculated costs
     */
    public static function updateSessionCosts(ChargingSession $session): bool
    {
        try {
            // Calculate estimated cost if not set
            if (!$session->estimated_cost && $session->isPrepaid()) {
                $estimatedCost = self::calculateEstimatedCost($session);
                $session->update(['estimated_cost' => $estimatedCost]);
            }
            
            // Calculate actual cost
            $actualCost = self::calculateActualCost($session);
            $session->update(['cost' => $actualCost]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update session costs', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get cost breakdown for a session
     */
    public static function getCostBreakdown(ChargingSession $session): array
    {
        $chargingPoint = $session->chargingPoint;
        $businessProfile = $chargingPoint->businessProfile;
        $pricingPlan = $businessProfile->pricingPlan;
        
        $breakdown = [
            'base_cost' => 0,
            'energy_cost' => 0,
            'time_cost' => 0,
            'session_cost' => 0,
            'admin_fee' => 0,
            'integrator_fee' => 0,
            'partner_fee' => 0,
            'total_cost' => $session->cost,
        ];
        
        if (!$pricingPlan) {
            return $breakdown;
        }
        
        // Calculate base costs
        if ($pricingPlan->energy_price_per_kwh) {
            $breakdown['energy_cost'] = $session->energy_consumed * $pricingPlan->energy_price_per_kwh;
        }
        
        if ($pricingPlan->time_price_per_minute) {
            $breakdown['time_cost'] = $session->duration * $pricingPlan->time_price_per_minute;
        }
        
        if ($pricingPlan->session_price) {
            $breakdown['session_cost'] = $pricingPlan->session_price;
        }
        
        $breakdown['base_cost'] = $breakdown['energy_cost'] + $breakdown['time_cost'] + $breakdown['session_cost'];
        
        // Calculate fees
        if ($businessProfile->admin_fee_percentage) {
            $breakdown['admin_fee'] += $breakdown['base_cost'] * ($businessProfile->admin_fee_percentage / 100);
        }
        if ($businessProfile->admin_fee_fixed) {
            $breakdown['admin_fee'] += $businessProfile->admin_fee_fixed;
        }
        
        if ($businessProfile->integrator_fee_percentage) {
            $breakdown['integrator_fee'] += $breakdown['base_cost'] * ($businessProfile->integrator_fee_percentage / 100);
        }
        if ($businessProfile->integrator_fee_fixed) {
            $breakdown['integrator_fee'] += $businessProfile->integrator_fee_fixed;
        }
        
        if ($businessProfile->partner_fee_percentage) {
            $breakdown['partner_fee'] += $breakdown['base_cost'] * ($businessProfile->partner_fee_percentage / 100);
        }
        if ($businessProfile->partner_fee_fixed) {
            $breakdown['partner_fee'] += $businessProfile->partner_fee_fixed;
        }
        
        return $breakdown;
    }

    /**
     * Calculate refund amount for prepaid session
     */
    public static function calculateRefundAmount(ChargingSession $session): float
    {
        if (!$session->isPrepaid() || !$session->prepaid_amount) {
            return 0;
        }
        
        $refundAmount = $session->prepaid_amount - $session->cost;
        return max(0, $refundAmount); // Ensure non-negative refund
    }

    /**
     * Get pricing information for a charging point
     */
    public static function getPricingInfo(ChargingPoint $chargingPoint): array
    {
        $businessProfile = $chargingPoint->businessProfile;
        $pricingPlan = $businessProfile->pricingPlan;
        
        if (!$pricingPlan) {
            return [
                'error' => 'No pricing plan found',
            ];
        }
        
        return [
            'energy_price_per_kwh' => $pricingPlan->energy_price_per_kwh,
            'time_price_per_minute' => $pricingPlan->time_price_per_minute,
            'session_price' => $pricingPlan->session_price,
            'currency' => $pricingPlan->currency ?? 'EUR',
            'fees' => [
                'admin_fee_percentage' => $businessProfile->admin_fee_percentage,
                'admin_fee_fixed' => $businessProfile->admin_fee_fixed,
                'integrator_fee_percentage' => $businessProfile->integrator_fee_percentage,
                'integrator_fee_fixed' => $businessProfile->integrator_fee_fixed,
                'partner_fee_percentage' => $businessProfile->partner_fee_percentage,
                'partner_fee_fixed' => $businessProfile->partner_fee_fixed,
            ],
        ];
    }

    /**
     * Estimate cost for a potential session
     */
    public static function estimateSessionCost(ChargingPoint $chargingPoint, array $parameters): float
    {
        $businessProfile = $chargingPoint->businessProfile;
        $pricingPlan = $businessProfile->pricingPlan;
        
        if (!$pricingPlan) {
            throw new \Exception('No pricing plan found for charging point');
        }
        
        $estimatedEnergy = $parameters['estimated_energy'] ?? 0;
        $estimatedDuration = $parameters['estimated_duration'] ?? 0;
        
        // Calculate base cost
        $baseCost = 0;
        
        if ($pricingPlan->energy_price_per_kwh) {
            $baseCost += $estimatedEnergy * $pricingPlan->energy_price_per_kwh;
        }
        
        if ($pricingPlan->time_price_per_minute) {
            $baseCost += $estimatedDuration * $pricingPlan->time_price_per_minute;
        }
        
        if ($pricingPlan->session_price) {
            $baseCost += $pricingPlan->session_price;
        }
        
        // Apply business profile fees
        $totalCost = self::applyBusinessProfileFees($businessProfile, $baseCost);
        
        return round($totalCost, 2);
    }
}
