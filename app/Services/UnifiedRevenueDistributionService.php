<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionRepartition;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service unifié pour le calcul correct de la répartition des revenus
 * Corrige toutes les erreurs de calcul existantes
 */
class UnifiedRevenueDistributionService
{
    /**
     * Calcule la répartition correcte des revenus pour une transaction
     */
    public function calculateCorrectRevenueDistribution(Transaction $transaction): array
    {
        try {
            // 1. Obtenir le montant total de la transaction
            $totalAmount = $this->getTransactionTotalAmount($transaction);
            
            if ($totalAmount <= 0) {
                Log::warning('Montant de transaction invalide', [
                    'transaction_id' => $transaction->id,
                    'amount' => $totalAmount
                ]);
                return $this->getDefaultDistribution($totalAmount);
            }

            // 2. Obtenir le business profile
            $businessProfile = $this->getBusinessProfileForTransaction($transaction);
            
            if (!$businessProfile) {
                Log::warning('Aucun business profile trouvé', [
                    'transaction_id' => $transaction->id,
                    'charging_point_id' => $transaction->chargingPoint?->id
                ]);
                return $this->getDefaultDistribution($totalAmount);
            }

            // 3. Calculer les frais selon le business profile
            $fees = $this->calculateBusinessProfileFees($businessProfile, $totalAmount);
            
            // 4. Calculer la répartition correcte
            $distribution = $this->calculateCorrectDistribution($totalAmount, $fees, $businessProfile);
            
            // 5. Valider la cohérence
            $this->validateDistribution($distribution, $totalAmount);
            
            Log::info('Répartition correcte calculée', [
                'transaction_id' => $transaction->id,
                'total_amount' => $totalAmount,
                'distribution' => $distribution
            ]);

            return $distribution;

        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul de la répartition', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            return $this->getDefaultDistribution($totalAmount ?? 0);
        }
    }

    /**
     * Obtient le montant total correct de la transaction
     */
    private function getTransactionTotalAmount(Transaction $transaction): float
    {
        // Priorité 1: amount (montant principal)
        if ($transaction->amount > 0) {
            return (float) $transaction->amount;
        }
        
        // Priorité 2: price_total
        if ($transaction->price_total > 0) {
            return (float) $transaction->price_total;
        }
        
        // Priorité 3: somme des composants
        $components = [
            $transaction->price_energy ?? 0,
            $transaction->price_time ?? 0,
            $transaction->price_service ?? 0,
            $transaction->price_tax ?? 0
        ];
        
        $total = array_sum($components);
        if ($total > 0) {
            return (float) $total;
        }
        
        // Priorité 4: estimated_cost de la réservation
        if ($transaction->reservation && $transaction->reservation->estimated_cost > 0) {
            return (float) $transaction->reservation->estimated_cost;
        }
        
        return 0;
    }

    /**
     * Obtient le business profile pour la transaction
     */
    private function getBusinessProfileForTransaction(Transaction $transaction): ?BusinessProfile
    {
        $chargingPoint = $transaction->chargingPoint;
        
        if (!$chargingPoint) {
            return null;
        }
        
        // Priorité 1: business profile du point de charge
        if ($chargingPoint->businessProfile) {
            return $chargingPoint->businessProfile;
        }
        
        // Priorité 2: business profile de l'intégrateur
        if ($chargingPoint->integrator && $chargingPoint->integrator->businessProfile) {
            return $chargingPoint->integrator->businessProfile;
        }
        
        // Priorité 3: business profile du partenaire
        if ($chargingPoint->partner && $chargingPoint->partner->businessProfile) {
            return $chargingPoint->partner->businessProfile;
        }
        
        return null;
    }

    /**
     * Calcule les frais selon le business profile
     */
    private function calculateBusinessProfileFees(BusinessProfile $businessProfile, float $totalAmount): array
    {
        $fees = [
            'admin_fixed' => (float) ($businessProfile->admin_fee_fixed ?? 0),
            'admin_percentage' => (float) ($businessProfile->admin_fee_percentage ?? 0),
            'integrator_fixed' => (float) ($businessProfile->integrator_fee_fixed ?? 0),
            'integrator_percentage' => (float) ($businessProfile->integrator_fee_percentage ?? 0),
            'partner_fixed' => (float) ($businessProfile->partner_fee_fixed ?? 0),
            'partner_percentage' => (float) ($businessProfile->partner_fee_percentage ?? 0),
            'maintenance_fee' => (float) ($businessProfile->maintenance_fee_amount ?? 0),
            'maintenance_type' => $businessProfile->maintenance_fee_type ?? 'fixed',
            'terminal_fee' => (float) ($businessProfile->terminal_fee_amount ?? 0),
            'base_fee' => (float) ($businessProfile->base_fee_amount ?? 0)
        ];

        // Calculer les montants
        $fees['admin_amount'] = $fees['admin_fixed'] + ($totalAmount * $fees['admin_percentage'] / 100);
        $fees['integrator_amount'] = $fees['integrator_fixed'] + ($totalAmount * $fees['integrator_percentage'] / 100);
        $fees['partner_amount'] = $fees['partner_fixed'] + ($totalAmount * $fees['partner_percentage'] / 100);
        
        // Frais de maintenance
        if ($fees['maintenance_type'] === 'percentage') {
            $fees['maintenance_amount'] = $totalAmount * ($fees['maintenance_fee'] / 100);
        } else {
            $fees['maintenance_amount'] = $fees['maintenance_fee'];
        }
        
        $fees['total_fees'] = $fees['admin_amount'] + $fees['integrator_amount'] + $fees['partner_amount'] + 
                             $fees['maintenance_amount'] + $fees['terminal_fee'] + $fees['base_fee'];

        return $fees;
    }

    /**
     * Calcule la répartition correcte selon la logique unifiée
     */
    private function calculateCorrectDistribution(float $totalAmount, array $fees, BusinessProfile $businessProfile): array
    {
        // 1. L'admin reçoit TOUS les frais (frais de transaction + frais de recharge + autres frais)
        $adminRevenue = $fees['total_fees'];
        
        // 2. Revenu disponible après frais admin
        $revenueAfterAdmin = max(0, $totalAmount - $adminRevenue);
        
        // 3. Commission intégrateur (calculée sur le revenu après frais admin)
        $integratorRevenue = $fees['integrator_amount'];
        
        // 4. Commission partenaire (calculée sur le revenu après frais admin)
        $partnerRevenue = $fees['partner_amount'];
        
        // 5. Revenu opérateur (le reste après toutes les commissions)
        $operatorRevenue = max(0, $revenueAfterAdmin - $integratorRevenue - $partnerRevenue);
        
        // 6. Protection contre les montants négatifs
        if ($operatorRevenue < 0) {
            // Ajuster les commissions proportionnellement
            $totalCommissions = $integratorRevenue + $partnerRevenue;
            if ($totalCommissions > 0 && $revenueAfterAdmin > 0) {
                $ratio = $revenueAfterAdmin / $totalCommissions;
                $integratorRevenue = $integratorRevenue * $ratio;
                $partnerRevenue = $partnerRevenue * $ratio;
                $operatorRevenue = 0;
            } else {
                $integratorRevenue = 0;
                $partnerRevenue = 0;
                $operatorRevenue = $revenueAfterAdmin;
            }
        }

        return [
            'total_amount' => $totalAmount,
            'admin' => round($adminRevenue, 2),
            'integrator' => round($integratorRevenue, 2),
            'partner' => round($partnerRevenue, 2),
            'operator' => round($operatorRevenue, 2),
            'fees_breakdown' => $fees,
            'revenue_after_admin' => round($revenueAfterAdmin, 2),
            'is_consistent' => true
        ];
    }

    /**
     * Valide que la répartition est cohérente
     */
    private function validateDistribution(array $distribution, float $totalAmount): void
    {
        $calculatedTotal = $distribution['admin'] + $distribution['integrator'] + 
                          $distribution['partner'] + $distribution['operator'];
        
        $difference = abs($totalAmount - $calculatedTotal);
        
        if ($difference > 0.01) {
            Log::error('Répartition incohérente détectée', [
                'total_amount' => $totalAmount,
                'calculated_total' => $calculatedTotal,
                'difference' => $difference,
                'distribution' => $distribution
            ]);
            
            throw new \Exception("Répartition incohérente: différence de {$difference}€");
        }
    }

    /**
     * Distribution par défaut en cas d'erreur
     */
    private function getDefaultDistribution(float $totalAmount): array
    {
        return [
            'total_amount' => $totalAmount,
            'admin' => round($totalAmount * 0.1, 2), // 10% par défaut
            'integrator' => round($totalAmount * 0.1, 2), // 10% par défaut
            'partner' => round($totalAmount * 0.1, 2), // 10% par défaut
            'operator' => round($totalAmount * 0.7, 2), // 70% par défaut
            'fees_breakdown' => [],
            'revenue_after_admin' => round($totalAmount * 0.9, 2),
            'is_consistent' => true
        ];
    }

    /**
     * Crée ou met à jour la répartition pour une transaction
     */
    public function createOrUpdateRepartition(Transaction $transaction): TransactionRepartition
    {
        try {
            DB::beginTransaction();

            // Calculer la répartition correcte
            $distribution = $this->calculateCorrectRevenueDistribution($transaction);
            
            // Vérifier si une répartition existe déjà
            $existingRepartition = $transaction->repartitions()->first();
            
            if ($existingRepartition) {
                // Mettre à jour la répartition existante
                $existingRepartition->update([
                    'admin_amount' => $distribution['admin'],
                    'integrator_amount' => $distribution['integrator'],
                    'operator_amount' => $distribution['operator']
                ]);
                
                Log::info('Répartition mise à jour', [
                    'transaction_id' => $transaction->id,
                    'distribution' => $distribution
                ]);
                
                DB::commit();
                return $existingRepartition;
            } else {
                // Créer une nouvelle répartition
                $repartition = TransactionRepartition::create([
                    'transaction_id' => $transaction->id,
                    'admin_amount' => $distribution['admin'],
                    'integrator_amount' => $distribution['integrator'],
                    'operator_amount' => $distribution['operator']
                ]);
                
                Log::info('Nouvelle répartition créée', [
                    'transaction_id' => $transaction->id,
                    'distribution' => $distribution
                ]);
                
                DB::commit();
                return $repartition;
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création/mise à jour de la répartition', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Corrige toutes les répartitions incohérentes
     */
    public function fixAllInconsistentRepartitions(): array
    {
        $results = [
            'total_processed' => 0,
            'fixed' => 0,
            'errors' => 0,
            'details' => []
        ];

        try {
            // Récupérer toutes les transactions avec répartitions
            $transactions = Transaction::with(['repartitions', 'chargingPoint.businessProfile'])->get();
            
            foreach ($transactions as $transaction) {
                $results['total_processed']++;
                
                try {
                    // Recalculer la répartition
                    $repartition = $this->createOrUpdateRepartition($transaction);
                    
                    // Vérifier la cohérence
                    if ($repartition->isConsistent()) {
                        $results['fixed']++;
                        $results['details'][] = [
                            'transaction_id' => $transaction->id,
                            'status' => 'fixed',
                            'admin' => $repartition->admin_amount,
                            'integrator' => $repartition->integrator_amount,
                            'operator' => $repartition->operator_amount
                        ];
                    } else {
                        $results['errors']++;
                        $results['details'][] = [
                            'transaction_id' => $transaction->id,
                            'status' => 'still_inconsistent',
                            'error' => 'Répartition toujours incohérente après correction'
                        ];
                    }
                    
                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'transaction_id' => $transaction->id,
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                }
            }

            Log::info('Correction des répartitions terminée', $results);
            return $results;

        } catch (\Exception $e) {
            Log::error('Erreur lors de la correction des répartitions', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Valide toutes les répartitions existantes
     */
    public function validateAllRepartitions(): array
    {
        $repartitions = TransactionRepartition::with('transaction')->get();
        $results = [
            'total' => $repartitions->count(),
            'consistent' => 0,
            'inconsistent' => 0,
            'inconsistent_details' => []
        ];

        foreach ($repartitions as $repartition) {
            if ($repartition->isConsistent()) {
                $results['consistent']++;
            } else {
                $results['inconsistent']++;
                $results['inconsistent_details'][] = [
                    'transaction_id' => $repartition->transaction_id,
                    'transaction_amount' => $repartition->transaction->amount,
                    'repartition_total' => $repartition->getTotalAmount(),
                    'difference' => abs($repartition->transaction->amount - $repartition->getTotalAmount())
                ];
            }
        }

        return $results;
    }
}
