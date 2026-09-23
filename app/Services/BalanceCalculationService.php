<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionHierarchy;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Cache\CacheService;

/**
 * Service de calcul de balance
 * 
 * Ce service gère le calcul et la mise à jour des balances
 * pour tous les types d'utilisateurs (Admin, Intégrateur, Opérateur)
 */
class BalanceCalculationService
{
    protected array $cacheConfig = [
        'user_balance' => 300,      // 5 minutes
        'transaction_balance' => 600, // 10 minutes
        'hierarchy_balance' => 900,   // 15 minutes
    ];

    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Calcule la balance complète d'un utilisateur
     * 
     * @param User $user
     * @param bool $forceRefresh
     * @return array
     */
    public function calculateUserBalance(User $user, bool $forceRefresh = false): array
    {
        $cacheKey = "user_balance:{$user->id}";
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return $this->cacheService->remember(
            $cacheKey, 
            $this->cacheConfig['user_balance'], 
            function () use ($user) {
                return $this->computeUserBalance($user);
            },
            ['user_balances', "user_{$user->id}"]
        );
    }

    /**
     * Calcule la balance d'un utilisateur sans cache
     * 
     * @param User $user
     * @return array
     */
    private function computeUserBalance(User $user): array
    {
        $balance = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->getRoleNames()->first(),
            'current_balance' => 0.0,
            'total_earnings' => 0.0,
            'total_expenses' => 0.0,
            'pending_commissions' => 0.0,
            'paid_commissions' => 0.0,
            'transaction_count' => 0,
            'last_transaction_date' => null,
            'balance_breakdown' => [],
            'calculated_at' => now()->toISOString()
        ];

        // Calculer selon le rôle
        if ($user->hasRole('admin')) {
            $balance = $this->calculateAdminBalance($user, $balance);
        } elseif ($user->hasRole('integrator')) {
            $balance = $this->calculateIntegratorBalance($user, $balance);
        } elseif ($user->hasRole('operator')) {
            $balance = $this->calculateOperatorBalance($user, $balance);
        }

        // Mettre à jour la balance dans la base de données
        $this->updateUserBalanceInDatabase($user, $balance);

        return $balance;
    }

    /**
     * Calcule la balance d'un admin
     * 
     * @param User $admin
     * @param array $balance
     * @return array
     */
    private function calculateAdminBalance(User $admin, array $balance): array
    {
        // IMPORTANT: Utiliser TransactionDetail au lieu de admin_commission dans transactions
        // Car les réservations approuvées utilisent TransactionDetail avec admin_share_amount
        // Cela garantit que toutes les transactions approuvées sont incluses dans le calcul
        
        // Pour l'admin, on inclut TOUTES les transactions avec admin_share_amount > 0
        // ou avec calculation_details contenant admin_fees_amount > 0
        // Peu importe le admin_creator_id (car il n'y a généralement qu'un seul admin)
        // Les frais admin sont calculés selon Business Profiles et stockés dans admin_share_amount
        
        // Requête optimisée : filtrer directement sur admin_share_amount > 0 au niveau SQL
        // puis filtrer en PHP pour vérifier calculation_details si nécessaire
        $adminDetails = TransactionDetail::where(function($query) {
                // Inclure si admin_share_amount > 0
                $query->where('admin_share_amount', '>', 0)
                    // OU si calculation_details existe (on vérifiera en PHP)
                    ->orWhereNotNull('calculation_details');
            })
            ->whereHas('transaction', function($q) {
                $q->where(function($statusQuery) {
                    // Transactions avec statut completed/confirmed
                    $statusQuery->whereIn('status', ['completed', 'confirmed'])
                        // OU transactions avec réservation approuvée (peu importe le statut de la transaction)
                        // Ces transactions doivent être incluses car la réservation est approuvée
                        ->orWhereHas('reservation', function($resQuery) {
                            $resQuery->whereIn('status', ['confirmed', 'completed']);
                        })
                        // OU transactions pending (elles sont déjà filtrées par la requête principale
                        // qui vérifie admin_share_amount > 0 ou calculation_details non null)
                        // Cela signifie qu'elles ont été traitées et que les parts ont été calculées
                        ->orWhere('status', 'pending');
                });
            })
            ->with(['transaction.reservation'])
            ->get()
            ->filter(function($detail) {
                // Inclure si admin_share_amount > 0
                if (($detail->admin_share_amount ?? 0) > 0) {
                    return true;
                }
                // OU si calculation_details contient admin_fees_amount > 0
                $adminFeesFromDetails = $this->extractAdminFeesFromCalculationDetails($detail->calculation_details);
                return $adminFeesFromDetails > 0;
            });
        
        $totalEarnings = 0;
        $paidCommissions = 0;
        $pendingCommissions = 0;
        $transactionCount = 0;
        $lastTransactionDate = null;
        
        foreach ($adminDetails as $detail) {
            // Calculer les frais admin en utilisant la méthode helper
            $adminFees = $this->calculateAdminFeesFromDetail($detail);
            
            if ($adminFees <= 0) {
                continue; // Ignorer les transactions sans frais admin
            }
            
            $totalEarnings += $adminFees;
            
            // Déterminer si la commission est payée
            $isPaid = $this->isAdminCommissionPaid($detail);
            
            if ($isPaid) {
                $paidCommissions += $adminFees;
            } else {
                $pendingCommissions += $adminFees;
            }
            
            $transactionCount++;
            
            // Mettre à jour la dernière date de transaction
            $transaction = $detail->transaction;
            if ($transaction && $transaction->created_at) {
                if (!$lastTransactionDate || $transaction->created_at->gt($lastTransactionDate)) {
                    $lastTransactionDate = $transaction->created_at;
                }
            }
        }

        $balance['total_earnings'] = (float) $totalEarnings;
        $balance['paid_commissions'] = (float) $paidCommissions;
        $balance['pending_commissions'] = (float) $pendingCommissions;
        $balance['current_balance'] = $balance['paid_commissions'];
        $balance['transaction_count'] = (int) $transactionCount;
        $balance['last_transaction_date'] = $lastTransactionDate;

        $balance['balance_breakdown'] = [
            'admin_commissions_received' => $balance['total_earnings'],
            'admin_commissions_paid' => $balance['paid_commissions'],
            'admin_commissions_pending' => $balance['pending_commissions']
        ];

        Log::info('Balance admin calculée depuis TransactionDetail', [
            'admin_id' => $admin->id,
            'transaction_details_count' => $adminDetails->count(),
            'total_earnings' => $balance['total_earnings'],
            'paid_commissions' => $balance['paid_commissions'],
            'pending_commissions' => $balance['pending_commissions']
        ]);

        return $balance;
    }

    /**
     * Extrait les frais admin depuis calculation_details
     * 
     * @param mixed $calculationDetails
     * @return float
     */
    private function extractAdminFeesFromCalculationDetails($calculationDetails): float
    {
        if (!$calculationDetails) {
            return 0.0;
        }
        
        $adminFees = 0.0;
        
        if (is_array($calculationDetails)) {
            $adminFees = (float) ($calculationDetails['hierarchical_logic']['admin_fees_amount'] ?? 0);
        } elseif (is_string($calculationDetails)) {
            $decoded = json_decode($calculationDetails, true);
            if (is_array($decoded)) {
                $adminFees = (float) ($decoded['hierarchical_logic']['admin_fees_amount'] ?? 0);
            }
        }
        
        return $adminFees;
    }

    /**
     * Calcule les frais admin depuis un TransactionDetail
     * 
     * @param TransactionDetail $detail
     * @return float
     */
    private function calculateAdminFeesFromDetail(TransactionDetail $detail): float
    {
        // PRIORITÉ 1 : Utiliser admin_share_amount comme source de vérité
        // car c'est la valeur réelle stockée dans TransactionDetail
        $adminFees = (float) ($detail->admin_share_amount ?? 0);
        
        // PRIORITÉ 2 : Vérifier si admin_fees_amount dans calculation_details est différent
        // et utiliser la valeur la plus élevée (car admin_fees_amount devrait être >= admin_share_amount)
        $adminFeesFromDetails = $this->extractAdminFeesFromCalculationDetails($detail->calculation_details);
        
        // Utiliser la valeur la plus élevée (normalement elles devraient être égales)
        return max($adminFees, $adminFeesFromDetails);
    }

    /**
     * Détermine si la commission admin est payée
     * 
     * @param TransactionDetail $detail
     * @return bool
     */
    private function isAdminCommissionPaid(TransactionDetail $detail): bool
    {
        // Vérifier d'abord le flag admin_paid
        if ($detail->admin_paid) {
            return true;
        }
        
        $transaction = $detail->transaction;
        if (!$transaction) {
            return false;
        }
        
        // PRIORITÉ 1 : Les réservations approuvées sont considérées comme payées
        // même si le statut de la transaction est "pending"
        // car cela signifie que la réservation a été approuvée et que les parts ont été calculées
        $statusValue = $transaction->reservation->status instanceof \App\Enums\ReservationStatus 
            ? $transaction->reservation->status->value 
            : $transaction->reservation->status;
        if ($transaction->reservation && in_array($statusValue, ['confirmed', 'completed'])) {
            return true;
        }
        
        // PRIORITÉ 2 : Transactions avec statut completed/confirmed sont considérées comme payées
        if (in_array($transaction->status, ['completed', 'confirmed'])) {
            return true;
        }
        
        // PRIORITÉ 3 : Si la transaction a un TransactionDetail avec admin_share_amount > 0
        // et que le statut est pending, cela peut signifier que la transaction a été traitée
        // mais pas encore finalisée. On considère cela comme payé si admin_share_amount > 0
        // car cela signifie que les parts ont été calculées et enregistrées
        if ($transaction->status === 'pending' && ($detail->admin_share_amount ?? 0) > 0) {
            // Vérifier si calculation_details existe (signe que la transaction a été traitée)
            if ($detail->calculation_details) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Calcule la balance d'un intégrateur
     * 
     * @param User $integrator
     * @param array $balance
     * @return array
     */
    private function calculateIntegratorBalance(User $integrator, array $balance): array
    {
        // Commissions intégrateur reçues
        $integratorCommissions = Transaction::where('integrator_commission', '>', 0)
            ->whereHas('chargingPoint', function ($query) use ($integrator) {
                $query->where('integrator_id', $integrator->integrator_id);
            })
            ->selectRaw('
                SUM(integrator_commission) as total_earnings,
                SUM(CASE WHEN integrator_commission_paid = 1 THEN integrator_commission ELSE 0 END) as paid_commissions,
                SUM(CASE WHEN integrator_commission_paid = 0 THEN integrator_commission ELSE 0 END) as pending_commissions,
                COUNT(*) as transaction_count,
                MAX(created_at) as last_transaction_date
            ')
            ->first();

        // Commissions admin déduites (sur la part intégrateur)
        $adminDeductions = Transaction::where('admin_commission', '>', 0)
            ->whereHas('chargingPoint', function ($query) use ($integrator) {
                $query->where('integrator_id', $integrator->integrator_id);
            })
            ->selectRaw('SUM(admin_commission) as total_deductions')
            ->first();

        $balance['total_earnings'] = (float) ($integratorCommissions->total_earnings ?? 0);
        $balance['paid_commissions'] = (float) ($integratorCommissions->paid_commissions ?? 0);
        $balance['pending_commissions'] = (float) ($integratorCommissions->pending_commissions ?? 0);
        $balance['total_expenses'] = (float) ($adminDeductions->total_deductions ?? 0);
        $balance['current_balance'] = $balance['paid_commissions'] - $balance['total_expenses'];
        $balance['transaction_count'] = (int) ($integratorCommissions->transaction_count ?? 0);
        $balance['last_transaction_date'] = $integratorCommissions->last_transaction_date;

        $balance['balance_breakdown'] = [
            'integrator_commissions_received' => $balance['total_earnings'],
            'integrator_commissions_paid' => $balance['paid_commissions'],
            'integrator_commissions_pending' => $balance['pending_commissions'],
            'admin_deductions' => $balance['total_expenses'],
            'net_balance' => $balance['current_balance']
        ];

        return $balance;
    }

    /**
     * Calcule la balance d'un opérateur
     * 
     * @param User $operator
     * @param array $balance
     * @return array
     */
    private function calculateOperatorBalance(User $operator, array $balance): array
    {
        // Transactions de l'opérateur
        $operatorTransactions = Transaction::where('user_id', $operator->id)
            ->selectRaw('
                SUM(price_total) as total_revenue,
                SUM(price_total - admin_commission - integrator_commission) as operator_earnings,
                COUNT(*) as transaction_count,
                MAX(created_at) as last_transaction_date
            ')
            ->first();

        $balance['total_earnings'] = (float) ($operatorTransactions->operator_earnings ?? 0);
        $balance['current_balance'] = $balance['total_earnings'];
        $balance['transaction_count'] = (int) ($operatorTransactions->transaction_count ?? 0);
        $balance['last_transaction_date'] = $operatorTransactions->last_transaction_date;

        $balance['balance_breakdown'] = [
            'total_revenue' => (float) ($operatorTransactions->total_revenue ?? 0),
            'operator_earnings' => $balance['total_earnings'],
            'transaction_count' => $balance['transaction_count']
        ];

        return $balance;
    }

    /**
     * Met à jour la balance dans la base de données
     * 
     * @param User $user
     * @param array $balance
     */
    private function updateUserBalanceInDatabase(User $user, array $balance): void
    {
        $user->update([
            'balance' => $balance['current_balance'],
            'last_balance_calculation' => now()
        ]);

        Log::info('Balance utilisateur mise à jour', [
            'user_id' => $user->id,
            'balance' => $balance['current_balance'],
            'role' => $user->getRoleNames()->first()
        ]);
    }

    /**
     * Calcule la balance hiérarchique complète
     * 
     * @param User $user
     * @return array
     */
    public function calculateHierarchicalBalance(User $user): array
    {
        $cacheKey = "hierarchical_balance:{$user->id}";
        
        return $this->cacheService->remember(
            $cacheKey, 
            $this->cacheConfig['hierarchy_balance'], 
            function () use ($user) {
                return $this->computeHierarchicalBalance($user);
            },
            ['hierarchical_balances', "user_{$user->id}"]
        );
    }

    /**
     * Calcule la balance hiérarchique sans cache
     * 
     * @param User $user
     * @return array
     */
    private function computeHierarchicalBalance(User $user): array
    {
        $hierarchy = [
            'user_id' => $user->id,
            'user_role' => $user->getRoleNames()->first(),
            'hierarchy_balance' => 0.0,
            'subordinates' => [],
            'total_hierarchy_earnings' => 0.0,
            'calculated_at' => now()->toISOString()
        ];

        if ($user->hasRole('admin')) {
            $hierarchy = $this->calculateAdminHierarchyBalance($user, $hierarchy);
        } elseif ($user->hasRole('integrator')) {
            $hierarchy = $this->calculateIntegratorHierarchyBalance($user, $hierarchy);
        }

        return $hierarchy;
    }

    /**
     * Calcule la balance hiérarchique d'un admin
     * 
     * @param User $admin
     * @param array $hierarchy
     * @return array
     */
    private function calculateAdminHierarchyBalance(User $admin, array $hierarchy): array
    {
        // Récupérer tous les intégrateurs sous cet admin
        $integrators = User::whereHas('roles', function ($query) {
            $query->where('name', 'integrator');
        })->where('created_by', $admin->id)->get();

        $totalHierarchyEarnings = 0.0;

        foreach ($integrators as $integrator) {
            $integratorBalance = $this->calculateUserBalance($integrator);
            $hierarchy['subordinates'][] = [
                'user_id' => $integrator->id,
                'user_name' => $integrator->name,
                'role' => 'integrator',
                'balance' => $integratorBalance['current_balance'],
                'total_earnings' => $integratorBalance['total_earnings']
            ];
            $totalHierarchyEarnings += $integratorBalance['total_earnings'];
        }

        $hierarchy['total_hierarchy_earnings'] = $totalHierarchyEarnings;
        $hierarchy['hierarchy_balance'] = $totalHierarchyEarnings;

        return $hierarchy;
    }

    /**
     * Calcule la balance hiérarchique d'un intégrateur
     * 
     * @param User $integrator
     * @param array $hierarchy
     * @return array
     */
    private function calculateIntegratorHierarchyBalance(User $integrator, array $hierarchy): array
    {
        // Récupérer tous les opérateurs sous cet intégrateur
        $operators = User::whereHas('roles', function ($query) {
            $query->where('name', 'operator');
        })->where('integrator_id', $integrator->integrator_id)->get();

        $totalHierarchyEarnings = 0.0;

        foreach ($operators as $operator) {
            $operatorBalance = $this->calculateUserBalance($operator);
            $hierarchy['subordinates'][] = [
                'user_id' => $operator->id,
                'user_name' => $operator->name,
                'role' => 'operator',
                'balance' => $operatorBalance['current_balance'],
                'total_earnings' => $operatorBalance['total_earnings']
            ];
            $totalHierarchyEarnings += $operatorBalance['total_earnings'];
        }

        $hierarchy['total_hierarchy_earnings'] = $totalHierarchyEarnings;
        $hierarchy['hierarchy_balance'] = $totalHierarchyEarnings;

        return $hierarchy;
    }

    /**
     * Recalcule toutes les balances (pour les crons)
     * 
     * @return array
     */
    public function recalculateAllBalances(): array
    {
        $results = [
            'admin_balances' => 0,
            'integrator_balances' => 0,
            'operator_balances' => 0,
            'errors' => []
        ];

        try {
            // Recalculer les balances des admins
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            foreach ($admins as $admin) {
                try {
                    $this->calculateUserBalance($admin, true);
                    $results['admin_balances']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Admin {$admin->id}: " . $e->getMessage();
                }
            }

            // Recalculer les balances des intégrateurs
            $integrators = User::whereHas('roles', function ($query) {
                $query->where('name', 'integrator');
            })->get();

            foreach ($integrators as $integrator) {
                try {
                    $this->calculateUserBalance($integrator, true);
                    $results['integrator_balances']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Intégrateur {$integrator->id}: " . $e->getMessage();
                }
            }

            // Recalculer les balances des opérateurs
            $operators = User::whereHas('roles', function ($query) {
                $query->where('name', 'operator');
            })->get();

            foreach ($operators as $operator) {
                try {
                    $this->calculateUserBalance($operator, true);
                    $results['operator_balances']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Opérateur {$operator->id}: " . $e->getMessage();
                }
            }

            // Nettoyer le cache
            $this->cacheService->forgetByTags(['user_balances', 'hierarchical_balances']);

            Log::info('Recalcul de toutes les balances terminé', $results);

        } catch (\Exception $e) {
            Log::error('Erreur lors du recalcul des balances', [
                'error' => $e->getMessage(),
                'results' => $results
            ]);
        }

        return $results;
    }

    /**
     * Obtient les statistiques de balance globales
     * 
     * @return array
     */
    public function getGlobalBalanceStatistics(): array
    {
        $cacheKey = 'global_balance_statistics';
        
        return $this->cacheService->remember($cacheKey, 600, function () {
            $stats = [
                'total_users' => User::count(),
                'total_balance' => User::sum('balance'),
                'admin_balance' => User::whereHas('roles', function ($q) {
                    $q->where('name', 'admin');
                })->sum('balance'),
                'integrator_balance' => User::whereHas('roles', function ($q) {
                    $q->where('name', 'integrator');
                })->sum('balance'),
                'operator_balance' => User::whereHas('roles', function ($q) {
                    $q->where('name', 'operator');
                })->sum('balance'),
                'calculated_at' => now()->toISOString()
            ];

            return $stats;
        });
    }
}
