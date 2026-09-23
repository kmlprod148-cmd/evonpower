<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\CommissionPlan;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Log;

class ReservationFeeService
{
    /**
     * Calculate fees for a reservation based on commission plan and business profile
     */
    public function calculateFees(Reservation $reservation, float $actualCost = null): array
    {
        $cost = $actualCost ?? $reservation->estimated_cost;
        $chargingPoint = $reservation->chargingPoint;
        $commissionPlan = CommissionPlan::getDefault();

        $fees = [
            'admin' => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
            'integrator' => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
            'partner' => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
            'total_commission' => 0,
            'net_amount' => $cost
        ];

        // Get commission plan fees
        if ($commissionPlan) {
            $fees['admin']['percentage'] = $commissionPlan->admin_percentage;
            $fees['integrator']['percentage'] = $commissionPlan->integrator_percentage;
            $fees['partner']['percentage'] = $commissionPlan->partner_percentage;
        }

        // Get business profile specific fees if available
        if ($chargingPoint && $chargingPoint->owner) {
            $businessProfile = $chargingPoint->owner->businessProfile;
            if ($businessProfile) {
                // Override with business profile specific fees
                $fees['integrator']['fixed'] = $businessProfile->integrator_fee_fixed ?? 0;
                $fees['integrator']['percentage'] = $businessProfile->integrator_fee_percentage ?? $fees['integrator']['percentage'];
                $fees['partner']['fixed'] = $businessProfile->partner_fee_fixed ?? 0;
                $fees['partner']['percentage'] = $businessProfile->partner_fee_percentage ?? $fees['partner']['percentage'];
            }
        }

        // Calculate total fees
        foreach (['admin', 'integrator', 'partner'] as $type) {
            $percentageAmount = round($cost * ($fees[$type]['percentage'] / 100), 2);
            $fees[$type]['total'] = $fees[$type]['fixed'] + $percentageAmount;
            $fees['total_commission'] += $fees[$type]['total'];
        }

        $fees['net_amount'] = $cost - $fees['total_commission'];

        return $fees;
    }

    /**
     * Calculate fees with custom rates
     */
    public function calculateCustomFees(float $cost, array $customRates): array
    {
        $fees = [
            'admin' => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
            'integrator' => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
            'partner' => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
            'total_commission' => 0,
            'net_amount' => $cost
        ];

        // Apply custom rates
        foreach (['admin', 'integrator', 'partner'] as $type) {
            $fixed = $customRates[$type . '_fee_fixed'] ?? 0;
            $percentage = $customRates[$type . '_fee_percentage'] ?? 0;
            
            $fees[$type]['fixed'] = $fixed;
            $fees[$type]['percentage'] = $percentage;
            $fees[$type]['total'] = $fixed + round($cost * ($percentage / 100), 2);
            $fees['total_commission'] += $fees[$type]['total'];
        }

        $fees['net_amount'] = $cost - $fees['total_commission'];

        return $fees;
    }

    /**
     * Apply fees to a transaction
     */
    public function applyFeesToTransaction(Transaction $transaction, array $fees): void
    {
        $transaction->update([
            'admin_commission' => $fees['admin']['total'],
            'integrator_commission' => $fees['integrator']['total'],
            'partner_commission' => $fees['partner']['total'],
            'repartition_breakdown' => json_encode([
                'admin' => [
                    'fixed' => $fees['admin']['fixed'],
                    'percentage' => $fees['admin']['percentage'],
                    'amount' => $fees['admin']['total'],
                    'type' => $fees['admin']['fixed'] > 0 && $fees['admin']['percentage'] > 0 ? 'mixed' : 
                             ($fees['admin']['fixed'] > 0 ? 'fixed' : 'percentage')
                ],
                'integrator' => [
                    'fixed' => $fees['integrator']['fixed'],
                    'percentage' => $fees['integrator']['percentage'],
                    'amount' => $fees['integrator']['total'],
                    'type' => $fees['integrator']['fixed'] > 0 && $fees['integrator']['percentage'] > 0 ? 'mixed' : 
                             ($fees['integrator']['fixed'] > 0 ? 'fixed' : 'percentage')
                ],
                'partner' => [
                    'fixed' => $fees['partner']['fixed'],
                    'percentage' => $fees['partner']['percentage'],
                    'amount' => $fees['partner']['total'],
                    'type' => $fees['partner']['fixed'] > 0 && $fees['partner']['percentage'] > 0 ? 'mixed' : 
                             ($fees['partner']['fixed'] > 0 ? 'fixed' : 'percentage')
                ],
                'total_commission' => $fees['total_commission'],
                'net_amount' => $fees['net_amount']
            ])
        ]);

        Log::info('Fees applied to transaction', [
            'transaction_id' => $transaction->id,
            'fees' => $fees
        ]);
    }

    /**
     * Get suggested fees for a reservation
     */
    public function getSuggestedFees(Reservation $reservation): array
    {
        $chargingPoint = $reservation->chargingPoint;
        
        $suggestedFees = [
            'admin' => ['fixed' => 0, 'percentage' => 0],
            'integrator' => ['fixed' => 0, 'percentage' => 0],
            'partner' => ['fixed' => 0, 'percentage' => 0],
        ];

        try {
            $commissionPlan = CommissionPlan::getDefault();
            
            // Get commission plan defaults
            if ($commissionPlan) {
                $suggestedFees['admin']['percentage'] = $commissionPlan->admin_percentage ?? 0;
                $suggestedFees['integrator']['percentage'] = $commissionPlan->integrator_percentage ?? 0;
                $suggestedFees['partner']['percentage'] = $commissionPlan->partner_percentage ?? 0;
            } else {
                // Fallback to default percentages if no commission plan exists
                $suggestedFees['admin']['percentage'] = 5.0;
                $suggestedFees['integrator']['percentage'] = 3.0;
                $suggestedFees['partner']['percentage'] = 2.0;
            }
        } catch (\Exception $e) {
            Log::error('Error getting commission plan for suggested fees', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to default percentages
            $suggestedFees['admin']['percentage'] = 5.0;
            $suggestedFees['integrator']['percentage'] = 3.0;
            $suggestedFees['partner']['percentage'] = 2.0;
        }

        // Get business profile specific fees
        if ($chargingPoint && $chargingPoint->owner) {
            try {
                $businessProfile = $chargingPoint->owner->businessProfile;
                if ($businessProfile) {
                    $suggestedFees['integrator']['fixed'] = $businessProfile->integrator_fee_fixed ?? 0;
                    $suggestedFees['integrator']['percentage'] = $businessProfile->integrator_fee_percentage ?? $suggestedFees['integrator']['percentage'];
                    $suggestedFees['partner']['fixed'] = $businessProfile->partner_fee_fixed ?? 0;
                    $suggestedFees['partner']['percentage'] = $businessProfile->partner_fee_percentage ?? $suggestedFees['partner']['percentage'];
                }
            } catch (\Exception $e) {
                Log::error('Error getting business profile for suggested fees', [
                    'reservation_id' => $reservation->id,
                    'charging_point_id' => $chargingPoint->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $suggestedFees;
    }

    /**
     * Validate fee structure
     */
    public function validateFees(array $fees): array
    {
        $errors = [];

        foreach (['admin', 'integrator', 'partner'] as $type) {
            if (isset($fees[$type . '_fee_fixed']) && $fees[$type . '_fee_fixed'] < 0) {
                $errors[] = "Les frais fixes pour {$type} ne peuvent pas être négatifs.";
            }
            
            if (isset($fees[$type . '_fee_percentage']) && ($fees[$type . '_fee_percentage'] < 0 || $fees[$type . '_fee_percentage'] > 100)) {
                $errors[] = "Le pourcentage pour {$type} doit être entre 0 et 100.";
            }
        }

        return $errors;
    }

    /**
     * Calculate reservation cost based on type and value
     */
    public function calculateReservationCost(Reservation $reservation): float
    {
        $pricingPlan = $reservation->pricingPlan;
        $reservationType = $reservation->reservation_type;
        $reservationValue = $reservation->reservation_value;

        $cost = 0;

        if ($reservationType === 'kwh') {
            $cost = $reservationValue * ($pricingPlan->price_per_kwh ?? 0);
        } elseif ($reservationType === 'minute') {
            $cost = $reservationValue * ($pricingPlan->price_per_minute ?? 0);
        }

        // Add activation fee if applicable
        $cost += $pricingPlan->activation_fee ?? 0;

        return round($cost, 2);
    }

    /**
     * Get fee breakdown for display
     */
    public function getFeeBreakdown(array $fees): array
    {
        $breakdown = [];

        foreach (['admin', 'integrator', 'partner'] as $type) {
            $breakdown[$type] = [
                'label' => ucfirst($type),
                'fixed' => $fees[$type]['fixed'],
                'percentage' => $fees[$type]['percentage'],
                'total' => $fees[$type]['total'],
                'color' => $type === 'admin' ? 'secondary' : ($type === 'integrator' ? 'warning' : 'success')
            ];
        }

        return $breakdown;
    }
}
