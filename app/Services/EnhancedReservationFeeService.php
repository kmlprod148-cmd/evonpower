<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Service amélioré pour la gestion des frais de réservation
 * Récupère tous les frais appliqués dans les profils business liés aux transactions
 */
class EnhancedReservationFeeService
{
    /**
     * Récupère tous les frais appliqués pour une réservation
     */
    public function getAllAppliedFees(Reservation $reservation): array
    {
        try {
            $fees = [
                'reservation_fees' => $this->getReservationFees($reservation),
                'business_profile_fees' => $this->getBusinessProfileFees($reservation),
                'transaction_fees' => $this->getTransactionFees($reservation),
                'activation_fees' => $this->getActivationFees($reservation),
                'commission_fees' => $this->getCommissionFees($reservation),
                'total_fees' => 0,
                'net_amount' => 0
            ];

            // Calculer le total des frais
            $fees['total_fees'] = array_sum([
                $fees['reservation_fees']['total'],
                $fees['business_profile_fees']['total'],
                $fees['transaction_fees']['total'],
                $fees['activation_fees']['total'],
                $fees['commission_fees']['total']
            ]);

            // Calculer le montant net
            $fees['net_amount'] = $reservation->estimated_cost - $fees['total_fees'];

            return $fees;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des frais de réservation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->getDefaultFees($reservation);
        }
    }

    /**
     * Récupère les frais de réservation de base
     */
    private function getReservationFees(Reservation $reservation): array
    {
        $pricingPlan = $reservation->pricingPlan;
        $fees = [
            'base_rate' => 0,
            'activation_fee' => 0,
            'energy_fee' => 0,
            'time_fee' => 0,
            'vat_fee' => 0,
            'total' => 0
        ];

        if ($pricingPlan) {
            $fees['base_rate'] = $pricingPlan->base_rate ?? 0;
            $fees['activation_fee'] = $pricingPlan->activation_fee ?? 0;
            
            // Frais selon le type de réservation
            if ($reservation->reservation_type === 'kwh' && $pricingPlan->price_per_kwh) {
                $fees['energy_fee'] = $reservation->reservation_value * $pricingPlan->price_per_kwh;
            } elseif ($reservation->reservation_type === 'minute' && $pricingPlan->price_per_minute) {
                $fees['time_fee'] = $reservation->reservation_value * $pricingPlan->price_per_minute;
            }

            // TVA
            if ($pricingPlan->vatRate && $pricingPlan->vatRate->rate > 0) {
                $subtotal = $fees['base_rate'] + $fees['activation_fee'] + $fees['energy_fee'] + $fees['time_fee'];
                $fees['vat_fee'] = $subtotal * ($pricingPlan->vatRate->rate / 100);
            }

            $fees['total'] = $fees['base_rate'] + $fees['activation_fee'] + $fees['energy_fee'] + $fees['time_fee'] + $fees['vat_fee'];
        }

        return $fees;
    }

    /**
     * Récupère les frais du profil business
     */
    private function getBusinessProfileFees(Reservation $reservation): array
    {
        $chargingPoint = $reservation->chargingPoint;
        $fees = [
            'transaction_fees' => 0,
            'charge_fees' => 0,
            'maintenance_fees' => 0,
            'terminal_fees' => 0,
            'base_fees' => 0,
            'total' => 0
        ];

        if ($chargingPoint && $chargingPoint->businessProfile) {
            $businessProfile = $chargingPoint->businessProfile;
            
            Log::info('Business Profile trouvé', [
                'business_profile_id' => $businessProfile->id,
                'name' => $businessProfile->name,
                'transaction_fee_config' => $businessProfile->transaction_fee_config,
                'charge_fee_config' => $businessProfile->charge_fee_config,
                'maintenance_fee_type' => $businessProfile->maintenance_fee_type,
                'maintenance_fee_amount' => $businessProfile->maintenance_fee_amount,
                'terminal_fee_amount' => $businessProfile->terminal_fee_amount,
                'base_fee_amount' => $businessProfile->base_fee_amount
            ]);
            
            // Frais de transaction - utiliser les champs directs si config JSON vide
            if ($businessProfile->transaction_fee_config) {
                $config = is_string($businessProfile->transaction_fee_config) 
                    ? json_decode($businessProfile->transaction_fee_config, true) 
                    : $businessProfile->transaction_fee_config;
                
                if (isset($config['fixed'])) {
                    $fees['transaction_fees'] += $config['fixed'];
                }
                if (isset($config['percentage'])) {
                    $fees['transaction_fees'] += $reservation->estimated_cost * ($config['percentage'] / 100);
                }
            } else {
                // Utiliser les frais fixes directs du business profile
                $fees['transaction_fees'] = $businessProfile->admin_fee_fixed ?? 0;
                if ($businessProfile->admin_fee_percentage) {
                    $fees['transaction_fees'] += $reservation->estimated_cost * ($businessProfile->admin_fee_percentage / 100);
                }
            }

            // Frais de charge - utiliser les champs directs si config JSON vide
            if ($businessProfile->charge_fee_config) {
                $config = is_string($businessProfile->charge_fee_config) 
                    ? json_decode($businessProfile->charge_fee_config, true) 
                    : $businessProfile->charge_fee_config;
                
                if (isset($config['fixed'])) {
                    $fees['charge_fees'] += $config['fixed'];
                }
                if (isset($config['percentage'])) {
                    $fees['charge_fees'] += $reservation->estimated_cost * ($config['percentage'] / 100);
                }
            } else {
                // Utiliser les frais de recharge directs
                $fees['charge_fees'] = $businessProfile->integrator_fee_fixed ?? 0;
                if ($businessProfile->integrator_fee_percentage) {
                    $fees['charge_fees'] += $reservation->estimated_cost * ($businessProfile->integrator_fee_percentage / 100);
                }
            }

            // Frais de maintenance
            if ($businessProfile->maintenance_fee_type === 'fixed') {
                $fees['maintenance_fees'] = $businessProfile->maintenance_fee_amount ?? 0;
            } elseif ($businessProfile->maintenance_fee_type === 'percentage') {
                $fees['maintenance_fees'] = $reservation->estimated_cost * (($businessProfile->maintenance_fee_amount ?? 0) / 100);
            } else {
                // Utiliser les frais de maintenance par défaut
                $fees['maintenance_fees'] = $businessProfile->partner_fee_fixed ?? 0;
                if ($businessProfile->partner_fee_percentage) {
                    $fees['maintenance_fees'] += $reservation->estimated_cost * ($businessProfile->partner_fee_percentage / 100);
                }
            }

            // Frais de terminal
            $fees['terminal_fees'] = $businessProfile->terminal_fee_amount ?? 0;

            // Frais de base
            $fees['base_fees'] = $businessProfile->base_fee_amount ?? 0;

            $fees['total'] = $fees['transaction_fees'] + $fees['charge_fees'] + $fees['maintenance_fees'] + $fees['terminal_fees'] + $fees['base_fees'];
            
            Log::info('Frais business profile calculés', [
                'transaction_fees' => $fees['transaction_fees'],
                'charge_fees' => $fees['charge_fees'],
                'maintenance_fees' => $fees['maintenance_fees'],
                'terminal_fees' => $fees['terminal_fees'],
                'base_fees' => $fees['base_fees'],
                'total' => $fees['total']
            ]);
        } else {
            Log::warning('Aucun business profile trouvé pour le point de charge', [
                'charging_point_id' => $chargingPoint->id ?? 'N/A',
                'charging_point_name' => $chargingPoint->name ?? 'N/A'
            ]);
        }

        return $fees;
    }

    /**
     * Récupère les frais de transaction
     */
    private function getTransactionFees(Reservation $reservation): array
    {
        $transaction = $reservation->transaction;
        $fees = [
            'admin_fees' => 0,
            'integrator_fees' => 0,
            'partner_fees' => 0,
            'operator_fees' => 0,
            'total' => 0
        ];

        if ($transaction) {
            $fees['admin_fees'] = $transaction->admin_commission ?? 0;
            $fees['integrator_fees'] = $transaction->integrator_commission ?? 0;
            $fees['partner_fees'] = $transaction->partner_commission ?? 0;
            
            // Frais opérateur (reste après déduction des autres frais)
            $totalCommissions = $fees['admin_fees'] + $fees['integrator_fees'] + $fees['partner_fees'];
            $fees['operator_fees'] = max(0, $transaction->price_total - $totalCommissions);
            
            $fees['total'] = $fees['admin_fees'] + $fees['integrator_fees'] + $fees['partner_fees'] + $fees['operator_fees'];
            
            Log::info('Frais de transaction récupérés', [
                'transaction_id' => $transaction->id,
                'admin_fees' => $fees['admin_fees'],
                'integrator_fees' => $fees['integrator_fees'],
                'partner_fees' => $fees['partner_fees'],
                'operator_fees' => $fees['operator_fees'],
                'total' => $fees['total']
            ]);
        } else {
            // Si pas de transaction, calculer les frais basés sur le business profile
            $chargingPoint = $reservation->chargingPoint;
            if ($chargingPoint && $chargingPoint->businessProfile) {
                $businessProfile = $chargingPoint->businessProfile;
                
                // Utiliser les pourcentages du business profile
                $fees['admin_fees'] = $reservation->estimated_cost * (($businessProfile->admin_fee_percentage ?? 0) / 100);
                $fees['integrator_fees'] = $reservation->estimated_cost * (($businessProfile->integrator_fee_percentage ?? 0) / 100);
                $fees['partner_fees'] = $reservation->estimated_cost * (($businessProfile->partner_fee_percentage ?? 0) / 100);
                
                // Frais opérateur (reste)
                $totalCommissions = $fees['admin_fees'] + $fees['integrator_fees'] + $fees['partner_fees'];
                $fees['operator_fees'] = max(0, $reservation->estimated_cost - $totalCommissions);
                
                $fees['total'] = $fees['admin_fees'] + $fees['integrator_fees'] + $fees['partner_fees'] + $fees['operator_fees'];
                
                Log::info('Frais calculés depuis business profile (pas de transaction)', [
                    'reservation_id' => $reservation->id,
                    'estimated_cost' => $reservation->estimated_cost,
                    'admin_fees' => $fees['admin_fees'],
                    'integrator_fees' => $fees['integrator_fees'],
                    'partner_fees' => $fees['partner_fees'],
                    'operator_fees' => $fees['operator_fees'],
                    'total' => $fees['total']
                ]);
            }
        }

        return $fees;
    }

    /**
     * Récupère les frais d'activation
     */
    private function getActivationFees(Reservation $reservation): array
    {
        $transaction = $reservation->transaction;
        $fees = [
            'activation_fee' => 0,
            'activation_fee_type' => null,
            'activation_fee_amount' => 0,
            'activation_fee_percentage' => 0,
            'total' => 0
        ];

        if ($transaction) {
            $fees['activation_fee'] = $transaction->activation_fee ?? 0;
            $fees['activation_fee_type'] = $transaction->activation_fee_type;
            $fees['activation_fee_amount'] = $transaction->activation_fee_amount ?? 0;
            $fees['activation_fee_percentage'] = $transaction->activation_fee_percentage ?? 0;
            $fees['total'] = $fees['activation_fee'];
        }

        return $fees;
    }

    /**
     * Récupère les frais de commission
     */
    private function getCommissionFees(Reservation $reservation): array
    {
        $transaction = $reservation->transaction;
        $fees = [
            'creator_charging_fees' => 0,
            'creator_transaction_fees' => 0,
            'creator_activation_fees' => 0,
            'creator_admin_fees' => 0,
            'creator_fees_total' => 0,
            'total' => 0
        ];

        if ($transaction) {
            $fees['creator_charging_fees'] = $transaction->creator_charging_fees ?? 0;
            $fees['creator_transaction_fees'] = $transaction->creator_transaction_fees ?? 0;
            $fees['creator_activation_fees'] = $transaction->creator_activation_fees ?? 0;
            $fees['creator_admin_fees'] = $transaction->creator_admin_fees ?? 0;
            $fees['creator_fees_total'] = $transaction->creator_fees_total ?? 0;
            $fees['total'] = $fees['creator_fees_total'];
        }

        return $fees;
    }

    /**
     * Récupère les frais par défaut en cas d'erreur
     */
    private function getDefaultFees(Reservation $reservation): array
    {
        return [
            'reservation_fees' => ['total' => 0],
            'business_profile_fees' => ['total' => 0],
            'transaction_fees' => ['total' => 0],
            'activation_fees' => ['total' => 0],
            'commission_fees' => ['total' => 0],
            'total_fees' => 0,
            'net_amount' => $reservation->estimated_cost
        ];
    }

    /**
     * Récupère le détail complet des frais pour l'affichage
     */
    public function getDetailedFeesBreakdown(Reservation $reservation): array
    {
        $allFees = $this->getAllAppliedFees($reservation);
        
        return [
            'reservation' => [
                'id' => $reservation->id,
                'estimated_cost' => $reservation->estimated_cost,
                'actual_cost' => $reservation->actual_cost,
                'reservation_type' => $reservation->reservation_type,
                'reservation_value' => $reservation->reservation_value
            ],
            'charging_point' => [
                'id' => $reservation->chargingPoint->id ?? null,
                'name' => $reservation->chargingPoint->name ?? null,
                'business_profile' => $reservation->chargingPoint->businessProfile->name ?? null
            ],
            'pricing_plan' => [
                'id' => $reservation->pricingPlan->id ?? null,
                'name' => $reservation->pricingPlan->name ?? null,
                'rate_type' => $reservation->pricingPlan->rate_type ?? null
            ],
            'fees_breakdown' => $allFees,
            'summary' => [
                'total_cost' => $reservation->estimated_cost,
                'total_fees' => $allFees['total_fees'],
                'net_amount' => $allFees['net_amount'],
                'fee_percentage' => $reservation->estimated_cost > 0 ? 
                    round(($allFees['total_fees'] / $reservation->estimated_cost) * 100, 2) : 0
            ]
        ];
    }
}
