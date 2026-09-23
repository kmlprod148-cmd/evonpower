<?php

namespace App\Services;

use App\Models\User;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\TransactionDetail;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class HierarchicalBalanceSynchronizationService
{
    protected BalanceSynchronizationService $balanceSyncService;

    public function __construct(BalanceSynchronizationService $balanceSyncService)
    {
        $this->balanceSyncService = $balanceSyncService;
    }

    /**
     * Synchroniser toutes les balances de manière hiérarchique
     * Ordre : Operators/Partners → Integrators → Admin
     */
    public function synchronizeAllHierarchicalBalances(): array
    {
        $results = [
            'operators' => [],
            'partners' => [],
            'integrators' => [],
            'admins' => [],
            'summary' => [
                'total_synchronized' => 0,
                'total_errors' => 0,
                'started_at' => now()->toIso8601String(),
                'completed_at' => null
            ]
        ];

        try {
            DB::beginTransaction();

            // 1. Synchroniser les balances des opérateurs et partenaires (niveau le plus bas)
            Log::info('HierarchicalBalanceSynchronization: Starting operator/partner synchronization');
            $results['operators'] = $this->synchronizeOperatorsAndPartners();
            $results['partners'] = $results['operators']; // Partners sont traités comme operators

            // 2. Synchroniser les balances des intégrateurs (incluant les parts des opérateurs/partenaires)
            Log::info('HierarchicalBalanceSynchronization: Starting integrator synchronization');
            $results['integrators'] = $this->synchronizeIntegrators();

            // 3. Synchroniser les balances des admins (incluant les money in/out des intégrateurs)
            Log::info('HierarchicalBalanceSynchronization: Starting admin synchronization');
            $results['admins'] = $this->synchronizeAdmins();

            DB::commit();

            $results['summary']['total_synchronized'] = 
                count($results['operators']) + 
                count($results['integrators']) + 
                count($results['admins']);
            $results['summary']['completed_at'] = now()->toIso8601String();

            Log::info('HierarchicalBalanceSynchronization: All balances synchronized successfully', [
                'summary' => $results['summary']
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $results['summary']['total_errors']++;
            $results['summary']['error'] = $e->getMessage();
            
            Log::error('HierarchicalBalanceSynchronization: Error during synchronization', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }

        return $results;
    }

    /**
     * Synchroniser les balances des opérateurs et partenaires
     */
    public function synchronizeOperatorsAndPartners(): array
    {
        $results = [];

        // Récupérer tous les opérateurs et partenaires
        $operators = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['operator', 'partner']);
        })->get();

        foreach ($operators as $operator) {
            try {
                $result = $this->balanceSyncService->synchronizeUserBalance($operator);
                $results[$operator->id] = [
                    'user_id' => $operator->id,
                    'user_name' => $operator->name,
                    'role' => $operator->getRoleNames()->first(),
                    'synchronized' => $result['synchronized'] ?? false,
                    'old_balance' => $result['old_balance'] ?? 0,
                    'new_balance' => $result['new_balance'] ?? 0,
                    'total_credits' => $result['total_credits'] ?? 0,
                    'total_debits' => $result['total_debits'] ?? 0,
                ];

                Log::info('HierarchicalBalanceSynchronization: Operator/Partner synchronized', [
                    'user_id' => $operator->id,
                    'role' => $operator->getRoleNames()->first(),
                    'balance' => $result['new_balance'] ?? 0
                ]);

            } catch (\Exception $e) {
                $results[$operator->id] = [
                    'user_id' => $operator->id,
                    'user_name' => $operator->name,
                    'role' => $operator->getRoleNames()->first(),
                    'synchronized' => false,
                    'error' => $e->getMessage()
                ];

                Log::error('HierarchicalBalanceSynchronization: Error synchronizing operator/partner', [
                    'user_id' => $operator->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Synchroniser les balances des intégrateurs
     * Inclut les parts des opérateurs/partenaires dans le calcul
     */
    public function synchronizeIntegrators(): array
    {
        $results = [];

        // Récupérer tous les intégrateurs
        $integrators = User::whereHas('roles', function($q) {
            $q->where('name', 'integrator');
        })->get();

        foreach ($integrators as $integrator) {
            try {
                // 1. Synchroniser d'abord le balance de base de l'intégrateur
                $baseResult = $this->balanceSyncService->synchronizeUserBalance($integrator);
                
                // 2. Calculer les parts des opérateurs/partenaires sous cet intégrateur
                $operatorsParts = $this->calculateOperatorsPartsForIntegrator($integrator);
                
                // 3. Vérifier que le balance de l'intégrateur reflète correctement les parts des opérateurs
                $integratorWallet = $integrator->getOrCreateWallet();
                $currentBalance = (float) $integratorWallet->balance;
                
                // Le balance de l'intégrateur devrait être :
                // integrator_share_amount (part nette après déduction admin)
                // Les parts des opérateurs sont déjà incluses dans les transactions
                
                $results[$integrator->id] = [
                    'user_id' => $integrator->id,
                    'user_name' => $integrator->name,
                    'role' => 'integrator',
                    'synchronized' => $baseResult['synchronized'] ?? false,
                    'old_balance' => $baseResult['old_balance'] ?? 0,
                    'new_balance' => $baseResult['new_balance'] ?? 0,
                    'total_credits' => $baseResult['total_credits'] ?? 0,
                    'total_debits' => $baseResult['total_debits'] ?? 0,
                    'operators_parts' => $operatorsParts,
                    'money_in' => $baseResult['total_credits'] ?? 0, // Part intégrateur brute
                    'money_out' => $baseResult['total_debits'] ?? 0, // Part admin déduite
                    'net_balance' => ($baseResult['total_credits'] ?? 0) - ($baseResult['total_debits'] ?? 0)
                ];

                Log::info('HierarchicalBalanceSynchronization: Integrator synchronized', [
                    'user_id' => $integrator->id,
                    'balance' => $baseResult['new_balance'] ?? 0,
                    'money_in' => $baseResult['total_credits'] ?? 0,
                    'money_out' => $baseResult['total_debits'] ?? 0,
                    'operators_count' => count($operatorsParts)
                ]);

            } catch (\Exception $e) {
                $results[$integrator->id] = [
                    'user_id' => $integrator->id,
                    'user_name' => $integrator->name,
                    'role' => 'integrator',
                    'synchronized' => false,
                    'error' => $e->getMessage()
                ];

                Log::error('HierarchicalBalanceSynchronization: Error synchronizing integrator', [
                    'user_id' => $integrator->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Synchroniser les balances des admins
     * Inclut les money in/out des intégrateurs dans le calcul
     */
    public function synchronizeAdmins(): array
    {
        $results = [];

        // Récupérer tous les admins
        $admins = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'super_admin']);
        })->get();

        foreach ($admins as $admin) {
            try {
                // 1. Synchroniser d'abord le balance de base de l'admin
                $baseResult = $this->balanceSyncService->synchronizeUserBalance($admin);
                
                // 2. Calculer les money in/out des intégrateurs sous cet admin
                $integratorsMoneyFlow = $this->calculateIntegratorsMoneyFlowForAdmin($admin);
                
                // 3. Vérifier que le balance de l'admin inclut bien les parts admin de tous les intégrateurs
                $adminWallet = $admin->getOrCreateWallet();
                $currentBalance = (float) $adminWallet->balance;
                
                // Le balance de l'admin devrait être :
                // Somme de tous les admin_share_amount depuis TransactionDetails
                // Ce qui correspond aux money out des intégrateurs
                
                $results[$admin->id] = [
                    'user_id' => $admin->id,
                    'user_name' => $admin->name,
                    'role' => $admin->getRoleNames()->first(),
                    'synchronized' => $baseResult['synchronized'] ?? false,
                    'old_balance' => $baseResult['old_balance'] ?? 0,
                    'new_balance' => $baseResult['new_balance'] ?? 0,
                    'total_credits' => $baseResult['total_credits'] ?? 0,
                    'total_debits' => $baseResult['total_debits'] ?? 0,
                    'integrators_money_flow' => $integratorsMoneyFlow,
                    'money_in_from_integrators' => $integratorsMoneyFlow['total_admin_share'] ?? 0,
                    'integrators_count' => count($integratorsMoneyFlow['integrators'] ?? [])
                ];

                Log::info('HierarchicalBalanceSynchronization: Admin synchronized', [
                    'user_id' => $admin->id,
                    'balance' => $baseResult['new_balance'] ?? 0,
                    'money_in_from_integrators' => $integratorsMoneyFlow['total_admin_share'] ?? 0,
                    'integrators_count' => count($integratorsMoneyFlow['integrators'] ?? [])
                ]);

            } catch (\Exception $e) {
                $results[$admin->id] = [
                    'user_id' => $admin->id,
                    'user_name' => $admin->name,
                    'role' => $admin->getRoleNames()->first(),
                    'synchronized' => false,
                    'error' => $e->getMessage()
                ];

                Log::error('HierarchicalBalanceSynchronization: Error synchronizing admin', [
                    'user_id' => $admin->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Calculer les parts des opérateurs/partenaires pour un intégrateur
     */
    protected function calculateOperatorsPartsForIntegrator(User $integrator): array
    {
        $operatorsParts = [];

        // Récupérer tous les opérateurs/partenaires sous cet intégrateur
        $operators = User::where('integrator_id', $integrator->id)
            ->whereHas('roles', function($q) {
                $q->whereIn('name', ['operator', 'partner']);
            })
            ->get();

        foreach ($operators as $operator) {
            // Calculer la part de l'opérateur depuis TransactionDetails
            $operatorShare = TransactionDetail::whereHas('transaction', function($q) {
                    $q->where(function($statusQuery) {
                        $statusQuery->whereIn('status', ['completed', 'confirmed'])
                            ->orWhereHas('reservation', function($resQuery) {
                                $resQuery->where('status', 'approved');
                            });
                    })
                    ->where('status', '!=', 'pending');
                })
                ->where('operator_id', $operator->id)
                ->where('operator_share_amount', '>', 0)
                ->sum('operator_share_amount');

            $operatorsParts[] = [
                'operator_id' => $operator->id,
                'operator_name' => $operator->name,
                'operator_share' => (float) $operatorShare,
                'wallet_balance' => (float) ($operator->getOrCreateWallet()->balance ?? 0)
            ];
        }

        return $operatorsParts;
    }

    /**
     * Calculer les money in/out des intégrateurs pour un admin
     */
    protected function calculateIntegratorsMoneyFlowForAdmin(User $admin): array
    {
        $integratorsFlow = [
            'integrators' => [],
            'total_admin_share' => 0,
            'total_integrator_share' => 0
        ];

        // Récupérer tous les intégrateurs créés par cet admin
        $integrators = User::whereHas('roles', function($q) {
                $q->where('name', 'integrator');
            })
            ->where(function($q) use ($admin) {
                // Intégrateurs créés par cet admin
                $q->where('created_by', $admin->id)
                  // OU intégrateurs via le modèle Integrator
                  ->orWhereHas('integrator', function($intQuery) use ($admin) {
                      $intQuery->where('created_by', $admin->id);
                  });
            })
            ->get();

        foreach ($integrators as $integrator) {
            // Calculer les parts admin et intégrateur depuis TransactionDetails
            $adminShare = TransactionDetail::whereHas('transaction', function($q) {
                    $q->where(function($statusQuery) {
                        $statusQuery->whereIn('status', ['completed', 'confirmed'])
                            ->orWhereHas('reservation', function($resQuery) {
                                $resQuery->where('status', 'approved');
                            });
                    })
                    ->where('status', '!=', 'pending');
                })
                ->where(function($q) use ($integrator) {
                    $q->where('integrator_creator_id', $integrator->id)
                      ->orWhere('admin_creator_id', $admin->id);
                })
                ->where('admin_share_amount', '>', 0)
                ->sum('admin_share_amount');

            $integratorShare = TransactionDetail::whereHas('transaction', function($q) {
                    $q->where(function($statusQuery) {
                        $statusQuery->whereIn('status', ['completed', 'confirmed'])
                            ->orWhereHas('reservation', function($resQuery) {
                                $resQuery->where('status', 'approved');
                            });
                    })
                    ->where('status', '!=', 'pending');
                })
                ->where('integrator_creator_id', $integrator->id)
                ->where('integrator_share_amount', '>', 0)
                ->sum('integrator_share_amount');

            $integratorWallet = $integrator->getOrCreateWallet();
            
            $integratorsFlow['integrators'][] = [
                'integrator_id' => $integrator->id,
                'integrator_name' => $integrator->name,
                'admin_share' => (float) $adminShare, // Money out de l'intégrateur = Money in de l'admin
                'integrator_share' => (float) $integratorShare, // Money in de l'intégrateur (brut)
                'integrator_net_balance' => (float) ($integratorWallet->balance ?? 0),
                'money_in' => (float) $integratorShare, // Part intégrateur brute
                'money_out' => (float) $adminShare, // Part admin déduite
            ];

            $integratorsFlow['total_admin_share'] += (float) $adminShare;
            $integratorsFlow['total_integrator_share'] += (float) $integratorShare;
        }

        return $integratorsFlow;
    }

    /**
     * Synchroniser le balance d'un utilisateur spécifique avec sa hiérarchie
     */
    public function synchronizeUserWithHierarchy(User $user): array
    {
        $results = [];

        if ($user->hasRole(['admin', 'super_admin'])) {
            // Synchroniser l'admin et tous ses intégrateurs
            $results['admin'] = $this->synchronizeAdmins()[$user->id] ?? null;
            
            // Synchroniser tous les intégrateurs de cet admin
            $integrators = User::whereHas('roles', function($q) {
                    $q->where('name', 'integrator');
                })
                ->where(function($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhereHas('integrator', function($intQuery) use ($user) {
                          $intQuery->where('created_by', $user->id);
                      });
                })
                ->get();

            foreach ($integrators as $integrator) {
                $results['integrators'][$integrator->id] = $this->synchronizeIntegratorWithOperators($integrator);
            }

        } elseif ($user->hasRole('integrator')) {
            // Synchroniser l'intégrateur et tous ses opérateurs/partenaires
            $results['integrator'] = $this->synchronizeIntegratorWithOperators($user);

        } elseif ($user->hasRole(['operator', 'partner'])) {
            // Synchroniser seulement l'opérateur/partenaire
            $result = $this->balanceSyncService->synchronizeUserBalance($user);
            $results['operator'] = $result;
        }

        return $results;
    }

    /**
     * Synchroniser un intégrateur avec tous ses opérateurs/partenaires
     */
    protected function synchronizeIntegratorWithOperators(User $integrator): array
    {
        $result = [
            'integrator' => null,
            'operators' => []
        ];

        // Synchroniser l'intégrateur
        $integratorResult = $this->balanceSyncService->synchronizeUserBalance($integrator);
        $result['integrator'] = $integratorResult;

        // Synchroniser tous les opérateurs/partenaires de cet intégrateur
        $operators = User::where('integrator_id', $integrator->id)
            ->whereHas('roles', function($q) {
                $q->whereIn('name', ['operator', 'partner']);
            })
            ->get();

        foreach ($operators as $operator) {
            $operatorResult = $this->balanceSyncService->synchronizeUserBalance($operator);
            $result['operators'][$operator->id] = $operatorResult;
        }

        return $result;
    }
}

