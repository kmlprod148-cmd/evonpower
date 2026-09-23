<?php

namespace App\Services;

use App\Models\PricingPlan;

class FinancialService
{
    public function calculateAndDistributeCommissions(PricingPlan $plan, array $validatedData): array
    {
        $pricePerMinute = $plan->price_per_minute ?? 0;
        $pricePerKwh = $plan->price_per_kwh ?? 0;
        $activationFee = $plan->activation_fee ?? 0;

        $costByTime = 0;
        $costByEnergy = 0;
        $subTotal = 0;

        // Calculate costs based on reservation type and value
        $reservationType = $validatedData['reservation_type'] ?? null;
        $reservationValue = $validatedData['reservation_value'] ?? 0;

        if ($plan->rate_type === 'fixed') {
            // Utiliser fixed_price ou base_rate comme fallback
            $subTotal = (float) ($plan->fixed_price ?? $plan->base_rate ?? 0);
        } else {
            if ($reservationType === 'minute' && $reservationValue > 0) {
                $costByTime = $reservationValue * $pricePerMinute;
            } elseif ($reservationType === 'kwh' && $reservationValue > 0) {
                $costByEnergy = $reservationValue * $pricePerKwh;
            }

            // For mixed plans, use the appropriate calculation based on what was provided
            if ($costByTime > 0 && $costByEnergy > 0) {
                // Both time and energy provided - use the higher cost or sum depending on plan logic
                $subTotal = max($costByTime, $costByEnergy);
            } else {
                // Use whichever cost is available
                $subTotal = $costByTime + $costByEnergy;
            }
        }

        $subTotal += $activationFee;
        
        // Appliquer la TVA si configurée dans le pricing plan
        $vatRate = 0;
        if ($plan->vatRate && $plan->vatRate->rate > 0) {
            $vatRate = $plan->vatRate->rate / 100;
        } elseif (isset($plan->vat_rate) && $plan->vat_rate > 0) {
            $vatRate = $plan->vat_rate / 100;
        }
        
        // Calculer le total TTC (HT + TVA)
        $totalPrice = $vatRate > 0 
            ? $subTotal * (1 + $vatRate)
            : $subTotal;
        
        // Commission structure (calculée sur le HT, pas le TTC)
        $adminCommissionRate = 0.10; // 10%
        $integratorCommissionRate = 0.05; // 5%
        $partnerCommissionRate = 0.05; // 5%

        $adminCommission = $subTotal * $adminCommissionRate;
        $integratorCommission = $subTotal * $integratorCommissionRate;
        $partnerCommission = $subTotal * $partnerCommissionRate;

        return [
            'total_price' => round($totalPrice, 2), // TTC (avec TVA)
            'subtotal_ht' => round($subTotal, 2), // HT (sans TVA)
            'vat_rate' => $vatRate * 100, // Taux de TVA en pourcentage
            'vat_amount' => round($totalPrice - $subTotal, 2), // Montant de la TVA
            'admin_commission' => round($adminCommission, 2),
            'integrator_commission' => round($integratorCommission, 2),
            'partner_commission' => round($partnerCommission, 2),
            'price_details' => [
                'cost_by_time' => round($costByTime, 2),
                'cost_by_energy' => round($costByEnergy, 2),
                'activation_fee' => round($activationFee, 2),
                'subtotal_ht' => round($subTotal, 2),
                'vat_rate' => $vatRate * 100,
                'vat_amount' => round($totalPrice - $subTotal, 2),
                'total_ttc' => round($totalPrice, 2),
            ],
            'currency' => $plan->currency ?? 'EUR',
        ];
    }
}