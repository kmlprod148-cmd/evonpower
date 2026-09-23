<?php

namespace App\Services;

use App\Models\User;
use App\Models\TransactionDetail;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de validation de la cohérence des balances
 * 
 * Valide que les balances des wallets correspondent aux calculs
 * basés sur les TransactionDetail
 */
class BalanceValidationService
{
    protected BalanceSynchronizationService $balanceSyncService;

    public function __construct(BalanceSynchronizationService $balanceSyncService)
    {
        $this->balanceSyncService = $balanceSyncService;
    }

    /**
     * Valide la cohérence de la balance d'un utilisateur
     * 
     * @param User $user
     * @param float $tolerance Tolérance acceptée (défaut: 0.01)
     * @return array ['valid' => bool, 'wallet_balance' => float, 'calculated_balance' => float, 'difference' => float, 'issues' => array]
     */
    public function validateUserBalance(User $user, float $tolerance = 0.01): array
    {
        try {
            $wallet = $user->getOrCreateWallet();
            $walletBalance = (float) $wallet->balance;
            $calculatedBalance = $this->balanceSyncService->calculateBalanceFromApprovedTransactions($user);
            
            $difference = abs($walletBalance - $calculatedBalance);
            $isValid = $difference <= $tolerance;

            $issues = [];
            
            if (!$isValid) {
                // Vérifier les TransactionDetail
                $transactionDetails = $this->getRelevantTransactionDetails($user);
                $expectedFromDetails = $this->calculateExpectedBalanceFromDetails($user, $transactionDetails);
                
                if (abs($expectedFromDetails - $calculatedBalance) > $tolerance) {
                    $issues[] = [
                        'type' => 'calculation_mismatch',
                        'message' => 'Le calcul de balance ne correspond pas aux TransactionDetail',
                        'expected_from_details' => $expectedFromDetails,
                        'calculated_balance' => $calculatedBalance,
                    ];
                }

                // Vérifier les wallet transactions
                $walletTransactionsSum = $this->calculateWalletTransactionsSum($wallet);
                if (abs($walletTransactionsSum - $walletBalance) > $tolerance) {
                    $issues[] = [
                        'type' => 'wallet_transactions_mismatch',
                        'message' => 'La balance du wallet ne correspond pas à la somme des transactions',
                        'wallet_balance' => $walletBalance,
                        'transactions_sum' => $walletTransactionsSum,
                    ];
                }
            }

            return [
                'valid' => $isValid,
                'wallet_balance' => $walletBalance,
                'calculated_balance' => $calculatedBalance,
                'difference' => $difference,
                'tolerance' => $tolerance,
                'issues' => $issues,
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation de balance', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'wallet_balance' => 0,
                'calculated_balance' => 0,
                'difference' => 0,
                'tolerance' => $tolerance,
                'issues' => [
                    [
                        'type' => 'validation_error',
                        'message' => 'Erreur lors de la validation: ' . $e->getMessage(),
                    ]
                ],
            ];
        }
    }

    /**
     * Valide toutes les balances et retourne un rapport
     * 
     * @param array $userIds IDs d'utilisateurs spécifiques (optionnel)
     * @return array
     */
    public function validateAllBalances(array $userIds = []): array
    {
        $query = User::query();
        
        if (!empty($userIds)) {
            $query->whereIn('id', $userIds);
        } else {
            $query->whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'super_admin', 'integrator', 'operator', 'partner']);
            });
        }

        $users = $query->get();
        $results = [];
        $validCount = 0;
        $invalidCount = 0;

        foreach ($users as $user) {
            $validation = $this->validateUserBalance($user);
            $results[] = array_merge(['user_id' => $user->id, 'user_name' => $user->name], $validation);
            
            if ($validation['valid']) {
                $validCount++;
            } else {
                $invalidCount++;
            }
        }

        return [
            'total_users' => $users->count(),
            'valid_count' => $validCount,
            'invalid_count' => $invalidCount,
            'results' => $results,
        ];
    }

    /**
     * Récupère les TransactionDetail pertinents pour un utilisateur
     */
    protected function getRelevantTransactionDetails(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $isAdmin = $user->hasRole(['admin', 'super_admin']);
        $isIntegrator = $user->hasRole('integrator');
        $isOperator = $user->hasRole(['operator', 'partner']);

        if ($isAdmin) {
            return TransactionDetail::whereHas('transaction', function($q) {
                    $q->where(function($statusQuery) {
                        $statusQuery->whereIn('status', ['completed', 'confirmed'])
                            ->orWhereHas('reservation', function($resQuery) {
                                $resQuery->whereIn('status', ['confirmed', 'completed']);
                            });
                    });
                })
                ->where('admin_share_amount', '>', 0)
                ->get();
        }

        if ($isIntegrator) {
            $integratorModel = \App\Models\Integrator::where('user_id', $user->id)->first();
            $integratorModelId = $integratorModel ? $integratorModel->id : null;

            return TransactionDetail::whereHas('transaction', function($q) {
                    $q->where(function($statusQuery) {
                        $statusQuery->whereIn('status', ['completed', 'confirmed'])
                            ->orWhereHas('reservation', function($resQuery) {
                                $resQuery->whereIn('status', ['confirmed', 'completed']);
                            });
                    });
                })
                ->where(function($q) use ($user, $integratorModelId) {
                    $q->where('integrator_creator_id', $user->id);
                    if ($integratorModelId) {
                        $q->orWhere('integrator_creator_id', $integratorModelId);
                    }
                })
                ->get();
        }

        if ($isOperator) {
            return TransactionDetail::whereHas('transaction', function($q) {
                    $q->where(function($statusQuery) {
                        $statusQuery->whereIn('status', ['completed', 'confirmed'])
                            ->orWhereHas('reservation', function($resQuery) {
                                $resQuery->whereIn('status', ['confirmed', 'completed']);
                            });
                    });
                })
                ->where('operator_id', $user->id)
                ->where('operator_share_amount', '>', 0)
                ->get();
        }

        return collect();
    }

    /**
     * Calcule la balance attendue depuis les TransactionDetail
     */
    protected function calculateExpectedBalanceFromDetails(User $user, $transactionDetails): float
    {
        $isAdmin = $user->hasRole(['admin', 'super_admin']);
        $isIntegrator = $user->hasRole('integrator');
        $isOperator = $user->hasRole(['operator', 'partner']);

        if ($isAdmin) {
            return (float) $transactionDetails->sum('admin_share_amount');
        }

        if ($isIntegrator) {
            return (float) $transactionDetails->sum(function($detail) {
                return ($detail->integrator_share_amount ?? 0);
            });
        }

        if ($isOperator) {
            return (float) $transactionDetails->sum('operator_share_amount');
        }

        return 0.0;
    }

    /**
     * Calcule la somme des wallet transactions
     */
    protected function calculateWalletTransactionsSum(Wallet $wallet): float
    {
        $credits = (float) $wallet->creditTransactions()->sum('amount');
        $debits = (float) $wallet->debitTransactions()->sum('amount');
        
        return $credits - $debits;
    }
}

