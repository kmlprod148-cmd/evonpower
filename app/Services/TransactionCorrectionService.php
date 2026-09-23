<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TransactionCorrectionService
{
    /**
     * Corrige les anciennes transactions avec les vrais détails selon la nouvelle logique
     */
    public function correctOldTransactions(): array
    {
        try {
            DB::beginTransaction();

            $results = [
                'total_processed' => 0,
                'corrected' => 0,
                'errors' => 0,
                'details' => []
            ];

            // Récupérer toutes les transactions avec des commissions existantes
            $transactions = Transaction::whereNotNull('admin_commission')
                ->orWhereNotNull('integrator_commission')
                ->orWhereNotNull('partner_commission')
                ->with(['businessProfile', 'chargingPoint'])
                ->get();

            Log::info('Début de la correction des anciennes transactions', [
                'total_transactions' => $transactions->count()
            ]);

            foreach ($transactions as $transaction) {
                $results['total_processed']++;

                try {
                    $correctionResult = $this->correctTransaction($transaction);
                    
                    if ($correctionResult['success']) {
                        $results['corrected']++;
                        $results['details'][] = [
                            'transaction_id' => $transaction->id,
                            'status' => 'corrected',
                            'old_commissions' => $correctionResult['old_commissions'],
                            'new_commissions' => $correctionResult['new_commissions']
                        ];
                    } else {
                        $results['errors']++;
                        $results['details'][] = [
                            'transaction_id' => $transaction->id,
                            'status' => 'error',
                            'error' => $correctionResult['error']
                        ];
                    }

                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'transaction_id' => $transaction->id,
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Erreur lors de la correction de la transaction', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            Log::info('Correction des anciennes transactions terminée', $results);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la correction des anciennes transactions', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Corrige une transaction spécifique
     */
    private function correctTransaction(Transaction $transaction): array
    {
        try {
            $totalAmount = $transaction->price_total ?? $transaction->amount ?? 0;
            $businessProfile = $transaction->businessProfile;

            if (!$businessProfile) {
                return [
                    'success' => false,
                    'error' => 'Aucun business profile associé'
                ];
            }

            // Sauvegarder les anciennes commissions
            $oldCommissions = [
                'admin_commission' => $transaction->admin_commission,
                'integrator_commission' => $transaction->integrator_commission,
                'partner_commission' => $transaction->partner_commission
            ];

            // Calculer les nouvelles commissions selon la logique corrigée
            $newCommissions = $this->calculateCorrectedCommissions($businessProfile, $totalAmount);

            // Mettre à jour la transaction
            $transaction->update([
                'admin_commission' => $newCommissions['admin_commission'],
                'integrator_commission' => $newCommissions['integrator_commission'],
                'partner_commission' => 0, // Plus utilisé dans la nouvelle logique
                'repartition_breakdown' => $newCommissions['breakdown'],
                'commission_notes' => 'Corrigé avec la nouvelle logique - Admin déduit de l\'Intégrateur'
            ]);

            return [
                'success' => true,
                'old_commissions' => $oldCommissions,
                'new_commissions' => $newCommissions
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calcule les commissions corrigées selon la nouvelle logique
     */
    private function calculateCorrectedCommissions(BusinessProfile $businessProfile, float $totalAmount): array
    {
        // 1. Commission Intégrateur selon Business Profile Intégrateur→Opérateur
        $integratorCommission = ($totalAmount * ($businessProfile->integrator_fee_percentage ?? 0)) / 100;
        $integratorFixed = $businessProfile->integrator_fee_fixed ?? 0;
        $integratorTotalCommission = $integratorCommission + $integratorFixed;

        // 2. Commission Admin déduite de la part de l'Intégrateur
        $adminCommission = ($integratorTotalCommission * ($businessProfile->admin_fee_percentage ?? 0)) / 100;
        $adminFixed = $businessProfile->admin_fee_fixed ?? 0;
        $adminTotalCommission = $adminCommission + $adminFixed;

        // 3. Montant net opérateur
        $operatorNetAmount = $totalAmount - $integratorTotalCommission;

        // Protection contre les montants négatifs
        if ($operatorNetAmount < 0) {
            $operatorNetAmount = 0;
        }

        return [
            'admin_commission' => $adminTotalCommission,
            'integrator_commission' => $integratorTotalCommission,
            'operator_net_amount' => $operatorNetAmount,
            'breakdown' => [
                'total_amount' => $totalAmount,
                'integrator_commission_percentage' => $businessProfile->integrator_fee_percentage ?? 0,
                'integrator_commission_fixed' => $businessProfile->integrator_fee_fixed ?? 0,
                'integrator_total' => $integratorTotalCommission,
                'admin_commission_percentage' => $businessProfile->admin_fee_percentage ?? 0,
                'admin_commission_fixed' => $businessProfile->admin_fee_fixed ?? 0,
                'admin_total' => $adminTotalCommission,
                'operator_net' => $operatorNetAmount,
                'calculation_method' => 'corrected_logic_admin_deducted_from_integrator'
            ]
        ];
    }

    /**
     * Corrige les transactions d'un business profile spécifique
     */
    public function correctTransactionsByBusinessProfile(int $businessProfileId): array
    {
        try {
            $businessProfile = BusinessProfile::find($businessProfileId);
            if (!$businessProfile) {
                return [
                    'success' => false,
                    'error' => 'Business Profile introuvable'
                ];
            }

            $transactions = Transaction::where('business_profile_id', $businessProfileId)
                ->where(function($query) {
                    $query->whereNotNull('admin_commission')
                          ->orWhereNotNull('integrator_commission')
                          ->orWhereNotNull('partner_commission');
                })
                ->get();

            $results = [
                'business_profile_id' => $businessProfileId,
                'business_profile_name' => $businessProfile->name,
                'total_transactions' => $transactions->count(),
                'corrected' => 0,
                'errors' => 0,
                'details' => []
            ];

            foreach ($transactions as $transaction) {
                try {
                    $correctionResult = $this->correctTransaction($transaction);
                    
                    if ($correctionResult['success']) {
                        $results['corrected']++;
                    } else {
                        $results['errors']++;
                    }

                    $results['details'][] = [
                        'transaction_id' => $transaction->id,
                        'success' => $correctionResult['success'],
                        'error' => $correctionResult['error'] ?? null
                    ];

                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'transaction_id' => $transaction->id,
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return $results;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Génère un rapport de correction
     */
    public function generateCorrectionReport(): array
    {
        $transactions = Transaction::whereNotNull('admin_commission')
            ->orWhereNotNull('integrator_commission')
            ->orWhereNotNull('partner_commission')
            ->with(['businessProfile'])
            ->get();

        $report = [
            'total_transactions' => $transactions->count(),
            'by_business_profile' => [],
            'commission_summary' => [
                'total_admin_commission' => $transactions->sum('admin_commission'),
                'total_integrator_commission' => $transactions->sum('integrator_commission'),
                'total_partner_commission' => $transactions->sum('partner_commission')
            ],
            'needs_correction' => []
        ];

        // Grouper par business profile
        $groupedTransactions = $transactions->groupBy('business_profile_id');

        foreach ($groupedTransactions as $businessProfileId => $profileTransactions) {
            $businessProfile = $profileTransactions->first()->businessProfile;
            
            $report['by_business_profile'][] = [
                'business_profile_id' => $businessProfileId,
                'business_profile_name' => $businessProfile ? $businessProfile->name : 'N/A',
                'transaction_count' => $profileTransactions->count(),
                'total_amount' => $profileTransactions->sum('price_total'),
                'admin_commission_total' => $profileTransactions->sum('admin_commission'),
                'integrator_commission_total' => $profileTransactions->sum('integrator_commission'),
                'partner_commission_total' => $profileTransactions->sum('partner_commission')
            ];
        }

        return $report;
    }
}
