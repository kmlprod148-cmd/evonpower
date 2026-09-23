<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ChargingPointFeeCalculationService
{
    /**
     * Calcule tous les frais pour un créateur de borne de recharge
     */
    public function calculateChargingPointCreatorFees(ChargingPoint $chargingPoint, float $baseAmount = 0): array
    {
        try {
            DB::beginTransaction();

            // Identifier le créateur de la borne
            $creator = $this->identifyChargingPointCreator($chargingPoint);
            
            // Récupérer le business profile associé
            $businessProfile = $this->getBusinessProfileForCreator($creator);
            
            if (!$businessProfile) {
                Log::warning('Aucun business profile trouvé pour le créateur de la borne', [
                    'charging_point_id' => $chargingPoint->id,
                    'creator_type' => $creator ? get_class($creator) : 'null',
                    'creator_id' => $creator ? $creator->id : 'null'
                ]);
                
                DB::rollBack();
                return $this->getDefaultFeeBreakdown();
            }

            // Calculer les différents types de frais
            $feeBreakdown = [
                'creator_info' => $this->getCreatorInfo($creator),
                'business_profile_info' => $this->getBusinessProfileInfo($businessProfile),
                'charging_fees' => $this->calculateChargingFees($businessProfile, $baseAmount),
                'transaction_fees' => $this->calculateTransactionFees($businessProfile, $baseAmount),
                'activation_fees' => $this->calculateActivationFees($businessProfile, $chargingPoint),
                'admin_fees' => $this->calculateAdminFees($businessProfile, $baseAmount),
                'total_breakdown' => [],
                'summary' => []
            ];

            // Calculer le total et créer le résumé
            $feeBreakdown['total_breakdown'] = $this->calculateTotalBreakdown($feeBreakdown);
            $feeBreakdown['summary'] = $this->createSummary($feeBreakdown);

            // Logging détaillé
            Log::info('Calcul des frais du créateur de borne terminé', [
                'charging_point_id' => $chargingPoint->id,
                'creator_type' => get_class($creator),
                'creator_id' => $creator->id,
                'business_profile_id' => $businessProfile->id,
                'total_fees' => $feeBreakdown['summary']['total_fees'],
                'base_amount' => $baseAmount
            ]);

            DB::commit();
            return $feeBreakdown;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors du calcul des frais du créateur de borne', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getDefaultFeeBreakdown();
        }
    }

    /**
     * Identifie le créateur de la borne de recharge
     */
    private function identifyChargingPointCreator(ChargingPoint $chargingPoint)
    {
        // Priorité 1: Intégrateur direct
        if ($chargingPoint->integrator_id) {
            return Integrator::find($chargingPoint->integrator_id);
        }

        // Priorité 2: Partenaire
        if ($chargingPoint->partner_id) {
            return Partner::find($chargingPoint->partner_id);
        }

        // Priorité 3: Groupe (si applicable)
        if ($chargingPoint->group_id) {
            $group = $chargingPoint->group;
            if ($group) {
                if ($group->integrator_id) {
                    return Integrator::find($group->integrator_id);
                }
                if ($group->partner_id) {
                    return Partner::find($group->partner_id);
                }
            }
        }

        return null;
    }

    /**
     * Récupère le business profile associé au créateur
     */
    private function getBusinessProfileForCreator($creator)
    {
        if (!$creator) {
            return null;
        }

        // Si c'est un intégrateur
        if ($creator instanceof Integrator) {
            return $creator->businessProfile;
        }

        // Si c'est un partenaire
        if ($creator instanceof Partner) {
            return $creator->businessProfile;
        }

        return null;
    }

    /**
     * Calcule les frais de recharge
     */
    private function calculateChargingFees(BusinessProfile $businessProfile, float $baseAmount): array
    {
        $chargingConfig = $businessProfile->charge_fee_config ?? [];
        
        $fees = [
            'fixed_fee' => 0,
            'percentage_fee' => 0,
            'per_kwh_fee' => 0,
            'per_minute_fee' => 0,
            'total' => 0
        ];

        // Frais fixes
        if (isset($chargingConfig['fixed_fee'])) {
            $fees['fixed_fee'] = (float) $chargingConfig['fixed_fee'];
        }

        // Frais en pourcentage
        if (isset($chargingConfig['percentage_fee']) && $baseAmount > 0) {
            $fees['percentage_fee'] = ($baseAmount * (float) $chargingConfig['percentage_fee']) / 100;
        }

        // Frais par kWh (si applicable)
        if (isset($chargingConfig['per_kwh_fee'])) {
            $fees['per_kwh_fee'] = (float) $chargingConfig['per_kwh_fee'];
        }

        // Frais par minute (si applicable)
        if (isset($chargingConfig['per_minute_fee'])) {
            $fees['per_minute_fee'] = (float) $chargingConfig['per_minute_fee'];
        }

        $fees['total'] = $fees['fixed_fee'] + $fees['percentage_fee'] + $fees['per_kwh_fee'] + $fees['per_minute_fee'];

        return $fees;
    }

    /**
     * Calcule les frais de transaction
     */
    private function calculateTransactionFees(BusinessProfile $businessProfile, float $baseAmount): array
    {
        $transactionConfig = $businessProfile->transaction_fee_config ?? [];
        
        $fees = [
            'fixed_fee' => 0,
            'percentage_fee' => 0,
            'minimum_fee' => 0,
            'maximum_fee' => 0,
            'total' => 0
        ];

        // Frais fixes
        if (isset($transactionConfig['fixed_fee'])) {
            $fees['fixed_fee'] = (float) $transactionConfig['fixed_fee'];
        }

        // Frais en pourcentage
        if (isset($transactionConfig['percentage_fee']) && $baseAmount > 0) {
            $fees['percentage_fee'] = ($baseAmount * (float) $transactionConfig['percentage_fee']) / 100;
        }

        // Frais minimum
        if (isset($transactionConfig['minimum_fee'])) {
            $fees['minimum_fee'] = (float) $transactionConfig['minimum_fee'];
        }

        // Frais maximum
        if (isset($transactionConfig['maximum_fee'])) {
            $fees['maximum_fee'] = (float) $transactionConfig['maximum_fee'];
        }

        // Calcul du total avec minimum et maximum
        $total = $fees['fixed_fee'] + $fees['percentage_fee'];
        
        if ($fees['minimum_fee'] > 0 && $total < $fees['minimum_fee']) {
            $total = $fees['minimum_fee'];
        }
        
        if ($fees['maximum_fee'] > 0 && $total > $fees['maximum_fee']) {
            $total = $fees['maximum_fee'];
        }

        $fees['total'] = $total;

        return $fees;
    }

    /**
     * Calcule les frais d'activation
     */
    private function calculateActivationFees(BusinessProfile $businessProfile, ChargingPoint $chargingPoint): array
    {
        $fees = [
            'base_fee' => 0,
            'one_time_fee' => 0,
            'setup_fee' => 0,
            'total' => 0
        ];

        // Frais de base
        if ($businessProfile->base_fee_amount > 0) {
            $fees['base_fee'] = (float) $businessProfile->base_fee_amount;
        }

        // Frais uniques (si c'est la première activation)
        $existingTransactions = Transaction::where('charging_point_id', $chargingPoint->id)
            ->where('transaction_type', 'activation_fee')
            ->count();

        if ($existingTransactions === 0) {
            // Frais d'installation/configuration
            if ($businessProfile->terminal_fee_amount > 0) {
                $fees['setup_fee'] = (float) $businessProfile->terminal_fee_amount;
            }
        }

        $fees['total'] = $fees['base_fee'] + $fees['one_time_fee'] + $fees['setup_fee'];

        return $fees;
    }

    /**
     * Calcule les frais administratifs
     */
    private function calculateAdminFees(BusinessProfile $businessProfile, float $baseAmount): array
    {
        $fees = [
            'fixed_fee' => 0,
            'percentage_fee' => 0,
            'total' => 0
        ];

        // Frais fixes admin
        if ($businessProfile->admin_fee_fixed > 0) {
            $fees['fixed_fee'] = (float) $businessProfile->admin_fee_fixed;
        }

        // Frais en pourcentage admin
        if ($businessProfile->admin_fee_percentage > 0 && $baseAmount > 0) {
            $fees['percentage_fee'] = ($baseAmount * (float) $businessProfile->admin_fee_percentage) / 100;
        }

        $fees['total'] = $fees['fixed_fee'] + $fees['percentage_fee'];

        return $fees;
    }

    /**
     * Calcule le total de tous les frais
     */
    private function calculateTotalBreakdown(array $feeBreakdown): array
    {
        $chargingFees = $feeBreakdown['charging_fees']['total'] ?? 0;
        $transactionFees = $feeBreakdown['transaction_fees']['total'] ?? 0;
        $activationFees = $feeBreakdown['activation_fees']['total'] ?? 0;
        $adminFees = $feeBreakdown['admin_fees']['total'] ?? 0;

        return [
            'charging_fees_total' => $chargingFees,
            'transaction_fees_total' => $transactionFees,
            'activation_fees_total' => $activationFees,
            'admin_fees_total' => $adminFees,
            'total_fees' => $chargingFees + $transactionFees + $activationFees + $adminFees
        ];
    }

    /**
     * Crée un résumé des frais
     */
    private function createSummary(array $feeBreakdown): array
    {
        $totalBreakdown = $feeBreakdown['total_breakdown'];
        
        return [
            'total_fees' => $totalBreakdown['total_fees'],
            'fee_breakdown' => [
                'Charging Fees' => $totalBreakdown['charging_fees_total'],
                'Transaction Fees' => $totalBreakdown['transaction_fees_total'],
                'Activation Fees' => $totalBreakdown['activation_fees_total'],
                'Admin Fees' => $totalBreakdown['admin_fees_total']
            ],
            'has_fees' => $totalBreakdown['total_fees'] > 0,
            'fee_types_count' => array_filter($totalBreakdown, function($value, $key) {
                return $key !== 'total_fees' && $value > 0;
            }, ARRAY_FILTER_USE_BOTH)
        ];
    }

    /**
     * Récupère les informations du créateur
     */
    private function getCreatorInfo($creator): array
    {
        if (!$creator) {
            return [
                'type' => 'unknown',
                'id' => null,
                'name' => 'Unknown',
                'email' => null
            ];
        }

        return [
            'type' => class_basename($creator),
            'id' => $creator->id,
            'name' => $creator->name,
            'email' => $creator->email ?? null
        ];
    }

    /**
     * Récupère les informations du business profile
     */
    private function getBusinessProfileInfo(BusinessProfile $businessProfile): array
    {
        return [
            'id' => $businessProfile->id,
            'name' => $businessProfile->name,
            'description' => $businessProfile->description,
            'is_active' => $businessProfile->is_active,
            'is_public' => $businessProfile->is_public,
            'commission_rates' => [
                'operator' => $businessProfile->operator_commission,
                'integrator' => $businessProfile->integrator_commission,
                'owner' => $businessProfile->owner_commission,
                'partner' => $businessProfile->partner_commission
            ]
        ];
    }

    /**
     * Retourne un breakdown par défaut si aucun business profile n'est trouvé
     */
    private function getDefaultFeeBreakdown(): array
    {
        return [
            'creator_info' => ['type' => 'unknown', 'id' => null, 'name' => 'Unknown', 'email' => null],
            'business_profile_info' => null,
            'charging_fees' => ['fixed_fee' => 0, 'percentage_fee' => 0, 'per_kwh_fee' => 0, 'per_minute_fee' => 0, 'total' => 0],
            'transaction_fees' => ['fixed_fee' => 0, 'percentage_fee' => 0, 'minimum_fee' => 0, 'maximum_fee' => 0, 'total' => 0],
            'activation_fees' => ['base_fee' => 0, 'one_time_fee' => 0, 'setup_fee' => 0, 'total' => 0],
            'admin_fees' => ['fixed_fee' => 0, 'percentage_fee' => 0, 'total' => 0],
            'total_breakdown' => [
                'charging_fees_total' => 0,
                'transaction_fees_total' => 0,
                'activation_fees_total' => 0,
                'admin_fees_total' => 0,
                'total_fees' => 0
            ],
            'summary' => [
                'total_fees' => 0,
                'fee_breakdown' => [],
                'has_fees' => false,
                'fee_types_count' => []
            ]
        ];
    }

    /**
     * Applique les frais à une transaction existante
     */
    public function applyFeesToTransaction(Transaction $transaction): bool
    {
        try {
            $chargingPoint = $transaction->chargingPoint;
            if (!$chargingPoint) {
                return false;
            }

            $feeBreakdown = $this->calculateChargingPointCreatorFees($chargingPoint, $transaction->price_total ?? 0);
            
            // Mettre à jour la transaction avec les frais calculés
            $transaction->update([
                'business_profile_fee_breakdown' => $feeBreakdown,
                'activation_fee' => $feeBreakdown['activation_fees']['total'],
                'activation_fee_amount' => $feeBreakdown['activation_fees']['total'],
                'activation_fee_type' => 'business_profile'
            ]);

            Log::info('Frais appliqués à la transaction', [
                'transaction_id' => $transaction->id,
                'charging_point_id' => $chargingPoint->id,
                'total_fees' => $feeBreakdown['summary']['total_fees']
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'application des frais à la transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtient un rapport détaillé des frais pour une période donnée
     */
    public function getFeeReportForPeriod($creatorId, $creatorType, $startDate, $endDate): array
    {
        $creator = null;
        
        if ($creatorType === 'integrator') {
            $creator = Integrator::find($creatorId);
        } elseif ($creatorType === 'partner') {
            $creator = Partner::find($creatorId);
        }

        if (!$creator) {
            return [];
        }

        $businessProfile = $this->getBusinessProfileForCreator($creator);
        if (!$businessProfile) {
            return [];
        }

        // Récupérer toutes les transactions pour cette période
        $transactions = Transaction::whereHas('chargingPoint', function($query) use ($creator, $creatorType) {
            if ($creatorType === 'integrator') {
                $query->where('integrator_id', $creator->id);
            } elseif ($creatorType === 'partner') {
                $query->where('partner_id', $creator->id);
            }
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();

        $report = [
            'creator_info' => $this->getCreatorInfo($creator),
            'business_profile_info' => $this->getBusinessProfileInfo($businessProfile),
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'transactions_count' => $transactions->count(),
            'total_revenue' => $transactions->sum('price_total'),
            'fee_summary' => [
                'total_charging_fees' => 0,
                'total_transaction_fees' => 0,
                'total_activation_fees' => 0,
                'total_admin_fees' => 0,
                'total_fees' => 0
            ],
            'transactions' => []
        ];

        foreach ($transactions as $transaction) {
            $feeBreakdown = $this->calculateChargingPointCreatorFees($transaction->chargingPoint, $transaction->price_total);
            
            $report['fee_summary']['total_charging_fees'] += $feeBreakdown['charging_fees']['total'];
            $report['fee_summary']['total_transaction_fees'] += $feeBreakdown['transaction_fees']['total'];
            $report['fee_summary']['total_activation_fees'] += $feeBreakdown['activation_fees']['total'];
            $report['fee_summary']['total_admin_fees'] += $feeBreakdown['admin_fees']['total'];
            $report['fee_summary']['total_fees'] += $feeBreakdown['summary']['total_fees'];

            $report['transactions'][] = [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->price_total,
                'fees' => $feeBreakdown['summary']
            ];
        }

        return $report;
    }
}
