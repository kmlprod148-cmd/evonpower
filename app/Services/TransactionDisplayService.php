<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionRepartition;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Service pour afficher les montants des transactions selon le rôle de l'utilisateur
 * Garantit que les montants affichés reflètent la logique réelle du calcul transactionnel
 * 
 * PRIORITÉ : TransactionDetail (calculé selon Business Profiles) > TransactionRepartition > Calcul direct
 */
class TransactionDisplayService
{
    protected $unifiedRevenueDistributionService;

    public function __construct(UnifiedRevenueDistributionService $unifiedRevenueDistributionService)
    {
        $this->unifiedRevenueDistributionService = $unifiedRevenueDistributionService;
    }

    /**
     * Obtient les montants à afficher pour un utilisateur selon son rôle
     * 
     * PRIORITÉ D'UTILISATION :
     * 1. TransactionDetail (montants calculés selon Business Profiles hiérarchiques)
     * 2. TransactionRepartition (fallback)
     * 3. Calcul direct depuis la transaction
     * 
     * @param Transaction $transaction
     * @param User|null $user Utilisateur qui consulte (null = visiteur)
     * @return array
     */
    public function getDisplayAmounts(Transaction $transaction, ?User $user = null): array
    {
        // PRIORITÉ 1 : TransactionDetail (montants calculés selon Business Profiles)
        $transactionDetail = $transaction->transactionDetail;
        
        if ($transactionDetail) {
            return $this->getAmountsFromTransactionDetail($transaction, $transactionDetail, $user);
        }
        
        // PRIORITÉ 2 : TransactionRepartition (fallback)
        $repartition = $transaction->repartition;
        
        if ($repartition) {
            return $this->getAmountsByRole($transaction, $repartition, $user);
        }
        
        // PRIORITÉ 3 : Si pas de répartition, essayer de créer une répartition si la transaction est finalisée
        if (in_array($transaction->status, ['completed', 'approved', 'paid', 'confirmed'])) {
            try {
                $finalizationService = app(\App\Services\TransactionFinalizationService::class);
                $repartition = $finalizationService->finalizeTransaction($transaction);
                
                if ($repartition) {
                    return $this->getAmountsByRole($transaction, $repartition, $user);
                }
            } catch (\Exception $e) {
                Log::warning('Impossible de créer la répartition pour l\'affichage', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // DERNIER RECOURS : Calculer à partir de la transaction
        return $this->calculateAmountsFromTransaction($transaction);
    }
    
    /**
     * Obtient les montants depuis TransactionDetail (source de vérité avec Business Profiles)
     * 
     * @param Transaction $transaction
     * @param TransactionDetail $transactionDetail
     * @param User|null $user
     * @return array
     */
    protected function getAmountsFromTransactionDetail(
        Transaction $transaction, 
        TransactionDetail $transactionDetail, 
        ?User $user
    ): array {
        $totalAmount = $transaction->price_total ?? $transaction->amount ?? 0;
        
        // Utiliser les montants réels de TransactionDetail (calculés selon Business Profiles)
        $adminShare = (float) ($transactionDetail->admin_share_amount ?? 0);
        $integratorShare = (float) ($transactionDetail->integrator_share_amount ?? 0);
        $operatorShare = (float) ($transactionDetail->operator_share_amount ?? 0);
        
        // Si pas d'utilisateur ou utilisateur sans rôle spécifique, afficher les montants de base
        if (!$user || !$user->hasAnyRole(['admin', 'integrator', 'operator', 'partner'])) {
            return [
                'total_amount' => $totalAmount,
                'your_amount' => $totalAmount,
                'fees_breakdown' => [],
                'repartition' => [
                    'admin_amount' => $adminShare,
                    'integrator_amount' => $integratorShare,
                    'operator_amount' => $operatorShare,
                ],
                'display_type' => 'customer',
                'source' => 'transaction_detail'
            ];
        }

        // Admin : voit tous les montants + sa part
        if ($user->hasRole('admin')) {
            return [
                'total_amount' => $totalAmount,
                'your_amount' => $adminShare,
                'fees_breakdown' => [
                    'admin_fee' => $adminShare,
                    'admin_amount' => $adminShare,
                    'integrator_amount' => $integratorShare,
                    'operator_amount' => $operatorShare,
                    'admin_percentage' => $transactionDetail->admin_share_percentage ?? 0,
                ],
                'repartition' => [
                    'admin_amount' => $adminShare,
                    'integrator_amount' => $integratorShare,
                    'operator_amount' => $operatorShare,
                ],
                'display_type' => 'admin',
                'admin_fee' => $adminShare,
                'admin_commission' => $adminShare,
                'source' => 'transaction_detail'
            ];
        }

        // Intégrateur : voit sa part (après déduction des frais admin)
        if ($user->hasRole('integrator')) {
            // La part intégrateur est déjà calculée selon Business Profile Intégrateur→Opérateur
            // L'admin prend sa part depuis l'intégrateur selon Business Profile Admin→Intégrateur
            // Donc la part intégrateur affichée = intégrateur_share (déjà net de la part admin)
            return [
                'total_amount' => $totalAmount,
                'your_amount' => $integratorShare,
                'fees_breakdown' => [
                    'integrator_fee' => $integratorShare,
                    'integrator_amount' => $integratorShare,
                    'integrator_percentage' => $transactionDetail->integrator_share_percentage ?? 0,
                    'admin_deduction' => $adminShare, // Ce qui est déduit pour l'admin
                    'remaining_for_operator' => $operatorShare,
                ],
                'repartition' => [
                    'admin_amount' => $adminShare,
                    'integrator_amount' => $integratorShare,
                    'operator_amount' => $operatorShare,
                ],
                'display_type' => 'integrator',
                'integrator_fee' => $integratorShare,
                'integrator_commission' => $integratorShare,
                'net_amount' => $integratorShare, // Montant net après déduction admin
                'source' => 'transaction_detail'
            ];
        }

        // Partenaire : similaire à l'intégrateur (peut être adapté selon votre logique)
        if ($user->hasRole('partner')) {
            return [
                'total_amount' => $totalAmount,
                'your_amount' => $operatorShare, // Le partenaire voit généralement la part opérateur
                'fees_breakdown' => [
                    'admin_deduction' => $adminShare,
                    'integrator_deduction' => $integratorShare,
                    'your_share' => $operatorShare,
                ],
                'repartition' => [
                    'admin_amount' => $adminShare,
                    'integrator_amount' => $integratorShare,
                    'operator_amount' => $operatorShare,
                ],
                'display_type' => 'partner',
                'net_amount' => $operatorShare,
                'source' => 'transaction_detail'
            ];
        }

        // Opérateur : voit sa part nette (après toutes les déductions)
        if ($user->hasRole('operator')) {
            return [
                'total_amount' => $totalAmount,
                'your_amount' => $operatorShare,
                'fees_breakdown' => [
                    'total_received' => $totalAmount,
                    'admin_deduction' => $adminShare,
                    'integrator_deduction' => $integratorShare,
                    'your_net_amount' => $operatorShare,
                ],
                'repartition' => [
                    'admin_amount' => $adminShare,
                    'integrator_amount' => $integratorShare,
                    'operator_amount' => $operatorShare,
                ],
                'display_type' => 'operator',
                'net_amount' => $operatorShare,
                'deductions' => [
                    'admin' => $adminShare,
                    'integrator' => $integratorShare,
                ],
                'source' => 'transaction_detail'
            ];
        }

        // Par défaut : afficher les montants de base
        return [
            'total_amount' => $totalAmount,
            'your_amount' => $totalAmount,
            'fees_breakdown' => [],
            'repartition' => [
                'admin_amount' => $adminShare,
                'integrator_amount' => $integratorShare,
                'operator_amount' => $operatorShare,
            ],
            'display_type' => 'default',
            'source' => 'transaction_detail'
        ];
    }

    /**
     * Obtient les montants selon le rôle de l'utilisateur
     * 
     * @param Transaction $transaction
     * @param TransactionRepartition $repartition
     * @param User|null $user
     * @return array
     */
    protected function getAmountsByRole(Transaction $transaction, TransactionRepartition $repartition, ?User $user): array
    {
        $totalAmount = $transaction->price_total ?? $transaction->amount ?? 0;

        // Si pas d'utilisateur ou utilisateur sans rôle spécifique, afficher les montants de base
        if (!$user || !$user->hasAnyRole(['admin', 'integrator', 'operator', 'partner'])) {
            return [
                'total_amount' => $totalAmount,
                'your_amount' => $totalAmount,
                'fees_breakdown' => [],
                'repartition' => [
                    'admin_amount' => $repartition->admin_amount,
                    'integrator_amount' => $repartition->integrator_amount,
                    'operator_amount' => $repartition->operator_amount,
                ],
                'display_type' => 'customer'
            ];
        }

        // Admin : voit tous les montants + sa part
        if ($user->hasRole('admin')) {
            return [
                'total_amount' => $totalAmount,
                'your_amount' => $repartition->admin_amount,
                'fees_breakdown' => [
                    'admin_fee' => $repartition->admin_fee ?? 0,
                    'admin_amount' => $repartition->admin_amount,
                    'integrator_amount' => $repartition->integrator_amount,
                    'operator_amount' => $repartition->operator_amount,
                ],
                'repartition' => [
                    'admin_amount' => $repartition->admin_amount,
                    'integrator_amount' => $repartition->integrator_amount,
                    'operator_amount' => $repartition->operator_amount,
                ],
                'display_type' => 'admin',
                'admin_fee' => $repartition->admin_fee ?? 0,
                'admin_commission' => $repartition->admin_amount
            ];
        }

        // Intégrateur : voit sa part (après déduction des frais admin)
        if ($user->hasRole('integrator')) {
            return [
                'total_amount' => $totalAmount,
                'your_amount' => $repartition->integrator_amount,
                'fees_breakdown' => [
                    'integrator_fee' => $repartition->integrator_fee ?? 0,
                    'integrator_amount' => $repartition->integrator_amount,
                    'admin_deduction' => $repartition->admin_amount, // Ce qui est déduit pour l'admin
                    'remaining_for_operator' => $repartition->operator_amount,
                ],
                'repartition' => [
                    'admin_amount' => $repartition->admin_amount,
                    'integrator_amount' => $repartition->integrator_amount,
                    'operator_amount' => $repartition->operator_amount,
                ],
                'display_type' => 'integrator',
                'integrator_fee' => $repartition->integrator_fee ?? 0,
                'integrator_commission' => $repartition->integrator_amount,
                'net_amount' => $repartition->integrator_amount // Montant net après déduction admin
            ];
        }

        // Partenaire : similaire à l'intégrateur (peut être adapté selon votre logique)
        if ($user->hasRole('partner')) {
            return [
                'total_amount' => $totalAmount,
                'your_amount' => $repartition->operator_amount, // Le partenaire voit généralement la part opérateur
                'fees_breakdown' => [
                    'admin_deduction' => $repartition->admin_amount,
                    'integrator_deduction' => $repartition->integrator_amount,
                    'your_share' => $repartition->operator_amount,
                ],
                'repartition' => [
                    'admin_amount' => $repartition->admin_amount,
                    'integrator_amount' => $repartition->integrator_amount,
                    'operator_amount' => $repartition->operator_amount,
                ],
                'display_type' => 'partner',
                'net_amount' => $repartition->operator_amount
            ];
        }

        // Opérateur : voit sa part nette (après toutes les déductions)
        if ($user->hasRole('operator')) {
            return [
                'total_amount' => $totalAmount,
                'your_amount' => $repartition->operator_amount,
                'fees_breakdown' => [
                    'total_received' => $totalAmount,
                    'admin_deduction' => $repartition->admin_amount,
                    'integrator_deduction' => $repartition->integrator_amount,
                    'your_net_amount' => $repartition->operator_amount,
                ],
                'repartition' => [
                    'admin_amount' => $repartition->admin_amount,
                    'integrator_amount' => $repartition->integrator_amount,
                    'operator_amount' => $repartition->operator_amount,
                ],
                'display_type' => 'operator',
                'net_amount' => $repartition->operator_amount,
                'deductions' => [
                    'admin' => $repartition->admin_amount,
                    'integrator' => $repartition->integrator_amount,
                ]
            ];
        }

        // Par défaut : afficher les montants de base
        return [
            'total_amount' => $totalAmount,
            'your_amount' => $totalAmount,
            'fees_breakdown' => [],
            'repartition' => [
                'admin_amount' => $repartition->admin_amount,
                'integrator_amount' => $repartition->integrator_amount,
                'operator_amount' => $repartition->operator_amount,
            ],
            'display_type' => 'default'
        ];
    }

    /**
     * Calcule les montants à partir de la transaction si pas de répartition
     * 
     * @param Transaction $transaction
     * @return array
     */
    protected function calculateAmountsFromTransaction(Transaction $transaction): array
    {
        $totalAmount = $transaction->price_total ?? $transaction->amount ?? 0;

        // Essayer de calculer la répartition
        try {
            $distribution = $this->unifiedRevenueDistributionService->calculateCorrectRevenueDistribution($transaction);
            
            return [
                'total_amount' => $totalAmount,
                'your_amount' => $totalAmount,
                'fees_breakdown' => $distribution['fees_breakdown'] ?? [],
                'repartition' => [
                    'admin_amount' => $distribution['admin'] ?? 0,
                    'integrator_amount' => $distribution['integrator'] ?? 0,
                    'operator_amount' => $distribution['operator'] ?? 0,
                ],
                'display_type' => 'calculated'
            ];
        } catch (\Exception $e) {
            Log::warning('Impossible de calculer la répartition pour l\'affichage', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            return [
                'total_amount' => $totalAmount,
                'your_amount' => $totalAmount,
                'fees_breakdown' => [],
                'repartition' => [
                    'admin_amount' => 0,
                    'integrator_amount' => 0,
                    'operator_amount' => 0,
                ],
                'display_type' => 'fallback'
            ];
        }
    }

    /**
     * Obtient les détails complets de répartition pour affichage
     * 
     * PRIORITÉ : TransactionDetail > TransactionRepartition
     * 
     * @param Transaction $transaction
     * @param User|null $user
     * @return array
     */
    public function getRepartitionDetails(Transaction $transaction, ?User $user = null): array
    {
        // PRIORITÉ 1 : TransactionDetail (montants calculés selon Business Profiles)
        $transactionDetail = $transaction->transactionDetail;
        
        if ($transactionDetail) {
            $totalAmount = $transaction->price_total ?? $transaction->amount ?? 0;
            $amounts = $this->getDisplayAmounts($transaction, $user);
            
            $adminShare = (float) ($transactionDetail->admin_share_amount ?? 0);
            $integratorShare = (float) ($transactionDetail->integrator_share_amount ?? 0);
            $operatorShare = (float) ($transactionDetail->operator_share_amount ?? 0);
            
            return [
                'has_repartition' => true,
                'source' => 'transaction_detail',
                'total_amount' => $totalAmount,
                'admin_amount' => $adminShare,
                'integrator_amount' => $integratorShare,
                'operator_amount' => $operatorShare,
                'total_repartitioned' => $adminShare + $integratorShare + $operatorShare,
                'is_balanced' => $transactionDetail->validateAmountConsistency()['is_consistent'],
                'user_amounts' => $amounts,
                'calculation_details' => [
                    'admin_fee' => $adminShare,
                    'integrator_fee' => $integratorShare,
                    'admin_percentage' => $transactionDetail->admin_share_percentage ?? 0,
                    'integrator_percentage' => $transactionDetail->integrator_share_percentage ?? 0,
                    'business_profiles' => $transactionDetail->calculation_details['business_profiles'] ?? [],
                    'calculation_method' => $transactionDetail->calculation_details['fees_calculation_method'] ?? 'unknown'
                ],
                'percentages' => $totalAmount > 0 ? [
                    'admin_percentage' => ($adminShare / $totalAmount) * 100,
                    'integrator_percentage' => ($integratorShare / $totalAmount) * 100,
                    'operator_percentage' => ($operatorShare / $totalAmount) * 100,
                ] : [
                    'admin_percentage' => 0,
                    'integrator_percentage' => 0,
                    'operator_percentage' => 0,
                ]
            ];
        }
        
        // PRIORITÉ 2 : TransactionRepartition (fallback)
        $repartition = $transaction->repartition;
        
        if ($repartition) {
            $totalAmount = $transaction->price_total ?? $transaction->amount ?? 0;
            $amounts = $this->getDisplayAmounts($transaction, $user);

            return [
                'has_repartition' => true,
                'source' => 'transaction_repartition',
                'total_amount' => $totalAmount,
                'admin_amount' => $repartition->admin_amount,
                'integrator_amount' => $repartition->integrator_amount,
                'operator_amount' => $repartition->operator_amount,
                'total_repartitioned' => $repartition->admin_amount + $repartition->integrator_amount + $repartition->operator_amount,
                'is_balanced' => $repartition->isBalanced(),
                'user_amounts' => $amounts,
                'calculation_details' => [
                    'admin_fee' => $repartition->admin_fee ?? 0,
                    'integrator_fee' => $repartition->integrator_fee ?? 0,
                ],
                'percentages' => $totalAmount > 0 ? [
                    'admin_percentage' => ($repartition->admin_amount / $totalAmount) * 100,
                    'integrator_percentage' => ($repartition->integrator_amount / $totalAmount) * 100,
                    'operator_percentage' => ($repartition->operator_amount / $totalAmount) * 100,
                ] : [
                    'admin_percentage' => 0,
                    'integrator_percentage' => 0,
                    'operator_percentage' => 0,
                ]
            ];
        }
        
        return [
            'has_repartition' => false,
            'source' => 'none',
            'message' => 'Aucune répartition disponible pour cette transaction'
        ];
    }

    /**
     * Filtre les transactions selon le rôle de l'utilisateur
     * Chaque utilisateur voit uniquement ses propres transactions/répartitions
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function filterTransactionsByUserRole($query, User $user)
    {
        // Admin voit tout
        if ($user->hasRole('admin')) {
            return $query;
        }

        // Intégrateur : voit ses transactions + celles de ses opérateurs/partenaires
        if ($user->hasRole('integrator')) {
            return $query->where(function($q) use ($user) {
                // Transactions sur les bornes de l'intégrateur
                $q->whereHas('chargingPoint', function($chargingPointQuery) use ($user) {
                    $chargingPointQuery->where('integrator_id', $user->integrator_id ?? $user->id);
                })
                // Ou transactions où l'intégrateur reçoit une part dans la répartition
                ->orWhereHas('repartition', function($repartitionQuery) use ($user) {
                    // Vérifier si l'intégrateur est lié à cette transaction via le charging point
                    $repartitionQuery->where('integrator_amount', '>', 0);
                });
            });
        }

        // Partenaire : voit ses transactions + celles de ses bornes
        if ($user->hasRole('partner')) {
            return $query->where(function($q) use ($user) {
                $q->whereHas('chargingPoint', function($chargingPointQuery) use ($user) {
                    $chargingPointQuery->where('partner_id', $user->partner_id ?? $user->id);
                })
                // Ou transactions où il reçoit une part
                ->orWhereHas('repartition', function($repartitionQuery) use ($user) {
                    $repartitionQuery->where('operator_amount', '>', 0);
                });
            });
        }

        // Opérateur : voit uniquement ses propres transactions
        if ($user->hasRole('operator')) {
            return $query->where('user_id', $user->id)
                ->orWhereHas('reservation', function($reservationQuery) use ($user) {
                    $reservationQuery->where('user_id', $user->id);
                });
        }

        // Utilisateur normal : voit uniquement ses transactions
        return $query->where('user_id', $user->id)
            ->orWhereHas('reservation', function($reservationQuery) use ($user) {
                $reservationQuery->where('user_id', $user->id);
            });
    }
}

