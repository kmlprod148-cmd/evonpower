<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Log;

class ReservationFeeDisplayService
{
    /**
     * Calcule et retourne les détails complets des frais pour une réservation
     */
    public function calculateFeeDetails(Reservation $reservation, float $totalAmount): array
    {
        $chargingPoint = $reservation->chargingPoint;
        $businessProfile = $this->getBusinessProfile($chargingPoint);
        
        if (!$businessProfile) {
            return $this->getDefaultFeeDetails($totalAmount);
        }
        
        return $this->calculateBusinessProfileFeeDetails($reservation, $businessProfile, $totalAmount);
    }
    
    /**
     * Calcule et retourne les détails complets des frais pour une transaction
     */
    public function calculateTransactionFeeDetails(Transaction $transaction): array
    {
        $chargingPoint = $transaction->chargingPoint;
        $businessProfile = $this->getBusinessProfile($chargingPoint);
        
        if (!$businessProfile) {
            return $this->getDefaultFeeDetails($transaction->price_total);
        }
        
        return $this->calculateBusinessProfileFeeDetails(null, $businessProfile, $transaction->price_total, $transaction);
    }
    
    /**
     * Récupère le business profile associé à la borne
     */
    private function getBusinessProfile(ChargingPoint $chargingPoint): ?BusinessProfile
    {
        // Priorité 1: Business profile direct de la borne
        if ($chargingPoint->business_profile_id) {
            return BusinessProfile::find($chargingPoint->business_profile_id);
        }
        
        // Priorité 2: Business profile de l'intégrateur
        if ($chargingPoint->integrator && $chargingPoint->integrator->businessProfile) {
            return $chargingPoint->integrator->businessProfile;
        }
        
        // Priorité 3: Business profile du partenaire
        if ($chargingPoint->partner && $chargingPoint->partner->businessProfile) {
            return $chargingPoint->partner->businessProfile;
        }
        
        // Priorité 4: Business profile du groupe
        if ($chargingPoint->group) {
            if ($chargingPoint->group->integrator && $chargingPoint->group->integrator->businessProfile) {
                return $chargingPoint->group->integrator->businessProfile;
            }
            if ($chargingPoint->group->partner && $chargingPoint->group->partner->businessProfile) {
                return $chargingPoint->group->partner->businessProfile;
            }
        }
        
        return null;
    }
    
    /**
     * Calcule les détails des frais basés sur le business profile
     */
    private function calculateBusinessProfileFeeDetails(?Reservation $reservation, BusinessProfile $businessProfile, float $totalAmount, ?Transaction $transaction = null): array
    {
        $fees = [
            'business_profile' => [
                'id' => $businessProfile->id,
                'name' => $businessProfile->name,
                'is_active' => $businessProfile->is_active,
                'owner_type' => $this->getBusinessProfileOwnerType($businessProfile)
            ],
            'charging_fees' => $this->calculateChargingFees($reservation, $businessProfile, $totalAmount),
            'transaction_fees' => $this->calculateTransactionFees($businessProfile, $totalAmount),
            'activation_fees' => $this->calculateActivationFees($businessProfile),
            'revenue_distribution' => $this->calculateRevenueDistribution($businessProfile, $totalAmount),
            'total_fees' => 0,
            'net_revenue' => 0,
            'breakdown' => []
        ];
        
        // Calculer le total des frais
        $fees['total_fees'] = $fees['charging_fees']['total'] + $fees['transaction_fees']['total'] + $fees['activation_fees']['total'];
        $fees['net_revenue'] = max(0, $totalAmount - $fees['total_fees']);
        
        // Créer le breakdown détaillé
        $fees['breakdown'] = $this->createDetailedBreakdown($fees, $totalAmount);
        
        return $fees;
    }
    
    /**
     * Calcule les frais de recharge
     */
    private function calculateChargingFees(?Reservation $reservation, BusinessProfile $businessProfile, float $totalAmount): array
    {
        $chargeFeeConfig = is_string($businessProfile->charge_fee_config) 
            ? json_decode($businessProfile->charge_fee_config, true) 
            : $businessProfile->charge_fee_config;
        
        $fees = [
            'fixed_fee' => 0,
            'percentage_fee' => 0,
            'per_kwh_fee' => 0,
            'per_minute_fee' => 0,
            'total' => 0
        ];
        
        if ($chargeFeeConfig) {
            // Frais fixes
            $fees['fixed_fee'] = (float) ($chargeFeeConfig['fixed_amount'] ?? 0);
            
            // Frais en pourcentage
            $percentage = (float) ($chargeFeeConfig['percentage'] ?? 0);
            if ($percentage > 0) {
                $fees['percentage_fee'] = $totalAmount * $percentage / 100;
            }
            
            // Frais spécifiques selon le type de réservation
            if ($reservation) {
                if ($reservation->reservation_type === 'kwh') {
                    $perKwhFee = (float) ($chargeFeeConfig['per_kwh_fee'] ?? 0);
                    if ($perKwhFee > 0) {
                        $fees['per_kwh_fee'] = $reservation->reservation_value * $perKwhFee;
                    }
                } elseif ($reservation->reservation_type === 'minute') {
                    $perMinuteFee = (float) ($chargeFeeConfig['per_minute_fee'] ?? 0);
                    if ($perMinuteFee > 0) {
                        $fees['per_minute_fee'] = $reservation->reservation_value * $perMinuteFee;
                    }
                }
            }
        }
        
        $fees['total'] = $fees['fixed_fee'] + $fees['percentage_fee'] + $fees['per_kwh_fee'] + $fees['per_minute_fee'];
        
        return $fees;
    }
    
    /**
     * Calcule les frais de transaction
     */
    private function calculateTransactionFees(BusinessProfile $businessProfile, float $totalAmount): array
    {
        $transactionFeeConfig = is_string($businessProfile->transaction_fee_config) 
            ? json_decode($businessProfile->transaction_fee_config, true) 
            : $businessProfile->transaction_fee_config;
        
        $fees = [
            'fixed_fee' => 0,
            'percentage_fee' => 0,
            'minimum_fee' => 0,
            'maximum_fee' => 0,
            'total' => 0
        ];
        
        if ($transactionFeeConfig) {
            // Frais fixes
            $fees['fixed_fee'] = (float) ($transactionFeeConfig['fixed_amount'] ?? 0);
            
            // Frais en pourcentage
            $percentage = (float) ($transactionFeeConfig['percentage'] ?? 0);
            if ($percentage > 0) {
                $fees['percentage_fee'] = $totalAmount * $percentage / 100;
            }
            
            // Frais minimum et maximum
            $fees['minimum_fee'] = (float) ($transactionFeeConfig['minimum_fee'] ?? 0);
            $fees['maximum_fee'] = (float) ($transactionFeeConfig['maximum_fee'] ?? 0);
        }
        
        $fees['total'] = $fees['fixed_fee'] + $fees['percentage_fee'];
        
        // Appliquer les limites
        if ($fees['minimum_fee'] > 0 && $fees['total'] < $fees['minimum_fee']) {
            $fees['total'] = $fees['minimum_fee'];
        }
        if ($fees['maximum_fee'] > 0 && $fees['total'] > $fees['maximum_fee']) {
            $fees['total'] = $fees['maximum_fee'];
        }
        
        return $fees;
    }
    
    /**
     * Calcule les frais d'activation
     */
    private function calculateActivationFees(BusinessProfile $businessProfile): array
    {
        return [
            'base_fee' => (float) ($businessProfile->base_fee_amount ?? 0),
            'one_time_fee' => 0, // À implémenter si nécessaire
            'setup_fee' => 0, // À implémenter si nécessaire
            'total' => (float) ($businessProfile->base_fee_amount ?? 0)
        ];
    }
    
    /**
     * Calcule la répartition des revenus
     */
    private function calculateRevenueDistribution(BusinessProfile $businessProfile, float $totalAmount): array
    {
        // Calculer le revenu disponible après frais
        $totalFees = $this->calculateTotalFees($businessProfile, $totalAmount);
        $revenueAfterFees = max(0, $totalAmount - $totalFees);
        
        // Calculer les parts
        $adminPart = $totalFees; // L'admin reçoit tous les frais
        $integratorPart = $this->calculateCommission($revenueAfterFees, $businessProfile->integrator_fee_percentage ?? 0, $businessProfile->integrator_fee_fixed ?? 0);
        $partnerPart = $this->calculateCommission($revenueAfterFees, $businessProfile->partner_fee_percentage ?? 0, $businessProfile->partner_fee_fixed ?? 0);
        $operatorPart = max(0, $revenueAfterFees - $integratorPart - $partnerPart);
        
        return [
            'admin' => [
                'amount' => $adminPart,
                'percentage' => $totalAmount > 0 ? ($adminPart / $totalAmount) * 100 : 0,
                'description' => 'Tous les frais de recharge et transaction'
            ],
            'integrator' => [
                'amount' => $integratorPart,
                'percentage' => $revenueAfterFees > 0 ? ($integratorPart / $revenueAfterFees) * 100 : 0,
                'description' => 'Calculé sur le revenu après frais admin'
            ],
            'partner' => [
                'amount' => $partnerPart,
                'percentage' => $revenueAfterFees > 0 ? ($partnerPart / $revenueAfterFees) * 100 : 0,
                'description' => 'Calculé sur le revenu après frais admin'
            ],
            'operator' => [
                'amount' => $operatorPart,
                'percentage' => $revenueAfterFees > 0 ? ($operatorPart / $revenueAfterFees) * 100 : 0,
                'description' => 'Reçoit le reste après toutes les commissions'
            ]
        ];
    }
    
    /**
     * Calcule le total des frais
     */
    private function calculateTotalFees(BusinessProfile $businessProfile, float $totalAmount): float
    {
        $chargingFees = $this->calculateChargingFees(null, $businessProfile, $totalAmount);
        $transactionFees = $this->calculateTransactionFees($businessProfile, $totalAmount);
        $activationFees = $this->calculateActivationFees($businessProfile);
        
        return $chargingFees['total'] + $transactionFees['total'] + $activationFees['total'];
    }
    
    /**
     * Calcule une commission
     */
    private function calculateCommission(float $amount, float $percentage, float $fixed): float
    {
        return ($amount * $percentage / 100) + $fixed;
    }
    
    /**
     * Crée un breakdown détaillé
     */
    private function createDetailedBreakdown(array $fees, float $totalAmount): array
    {
        return [
            'total_amount' => $totalAmount,
            'fees_summary' => [
                'charging_fees' => $fees['charging_fees']['total'],
                'transaction_fees' => $fees['transaction_fees']['total'],
                'activation_fees' => $fees['activation_fees']['total'],
                'total_fees' => $fees['total_fees']
            ],
            'revenue_summary' => [
                'gross_revenue' => $totalAmount,
                'net_revenue' => $fees['net_revenue'],
                'admin_share' => $fees['revenue_distribution']['admin']['amount'],
                'integrator_share' => $fees['revenue_distribution']['integrator']['amount'],
                'partner_share' => $fees['revenue_distribution']['partner']['amount'],
                'operator_share' => $fees['revenue_distribution']['operator']['amount']
            ]
        ];
    }
    
    /**
     * Retourne les détails par défaut si aucun business profile
     */
    private function getDefaultFeeDetails(float $totalAmount): array
    {
        return [
            'business_profile' => null,
            'charging_fees' => ['total' => 0],
            'transaction_fees' => ['total' => 0],
            'activation_fees' => ['total' => 0],
            'revenue_distribution' => [
                'admin' => ['amount' => 0, 'percentage' => 0, 'description' => 'Aucun business profile configuré'],
                'integrator' => ['amount' => 0, 'percentage' => 0, 'description' => 'Aucun business profile configuré'],
                'partner' => ['amount' => 0, 'percentage' => 0, 'description' => 'Aucun business profile configuré'],
                'operator' => ['amount' => $totalAmount, 'percentage' => 100, 'description' => 'Reçoit tout le montant']
            ],
            'total_fees' => 0,
            'net_revenue' => $totalAmount,
            'breakdown' => [
                'total_amount' => $totalAmount,
                'fees_summary' => ['total_fees' => 0],
                'revenue_summary' => [
                    'gross_revenue' => $totalAmount,
                    'net_revenue' => $totalAmount,
                    'operator_share' => $totalAmount
                ]
            ]
        ];
    }
    
    /**
     * Détermine le type de propriétaire du business profile
     */
    private function getBusinessProfileOwnerType(BusinessProfile $businessProfile): string
    {
        if ($businessProfile->integrator_id) {
            return 'Integrator';
        }
        if ($businessProfile->partner_id) {
            return 'Partner';
        }
        return 'Direct';
    }
}
