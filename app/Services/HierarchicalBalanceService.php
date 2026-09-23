<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionHierarchy;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Cache\CacheService;
use Carbon\Carbon;

/**
 * Service de calcul de balances hiérarchiques
 * 
 * Gère le calcul des balances pour la hiérarchie Admin → Intégrateur → Opérateur
 * avec cache optimisé et recalcul automatique
 */
class HierarchicalBalanceService
{
    protected CacheService $cacheService;
    
    protected array $cacheConfig = [
        'user_balance' => 300,           // 5 minutes
        'hierarchy_balance' => 600,     // 10 minutes
        'global_stats' => 900,          // 15 minutes
        'commission_breakdown' => 1800, // 30 minutes
    ];

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    protected array $recalculationIntervals = [
        'immediate' => 0,    // Immédiat
        'hourly' => 3600,    // Toutes les heures
        'daily' => 86400,    // Quotidien
        'weekly' => 604800,  // Hebdomadaire
    ];

    /**
     * Calcule la balance complète d'un utilisateur dans la hiérarchie
     * 
     * @param User $user
     * @param bool $forceRefresh
     * @return array
     */
    public function calculateUserBalance(User $user, bool $forceRefresh = false): array
    {
        $cacheKey = "hierarchical_balance:user:{$user->id}";
        
        if ($forceRefresh) {
            $this->cacheService->forget($cacheKey);
        }

        return $this->cacheService->remember(
            $cacheKey, 
            $this->cacheConfig['user_balance'], 
            function () use ($user) {
                return $this->computeUserBalance($user);
            },
            ['hierarchical_balances', "user_{$user->id}"]
        );
    }

    /**
     * Calcule la balance hiérarchique complète (Admin → Intégrateur → Opérateur)
     * 
     * @param User $user
     * @return array
     */
    private function computeUserBalance(User $user): array
    {
        $role = $user->getRoleNames()->first();
        
        $balance = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $role,
            'current_balance' => 0.0,
            'total_earnings' => 0.0,
            'total_commissions' => 0.0,
            'pending_amount' => 0.0,
            'paid_amount' => 0.0,
            'transaction_count' => 0,
            'last_transaction_date' => null,
            'hierarchy_data' => [],
            'commission_breakdown' => [],
            'performance_metrics' => [],
            'calculated_at' => now()->toISOString()
        ];

        switch ($role) {
            case 'admin':
                $balance = $this->calculateAdminBalance($user, $balance);
                break;
            case 'integrator':
                $balance = $this->calculateIntegratorBalance($user, $balance);
                break;
            case 'operator':
                $balance = $this->calculateOperatorBalance($user, $balance);
                break;
        }

        // Mettre à jour la balance dans la base de données
        $this->updateUserBalanceInDatabase($user, $balance);

        return $balance;
    }

    /**
     * Calcule la balance pour un administrateur
     * 
     * @param User $user
     * @param array $balance
     * @return array
     */
    private function calculateAdminBalance(User $user, array $balance): array
    {
        // Récupérer toutes les commissions admin
        $adminCommissions = DB::table('transaction_hierarchies as th')
            ->join('business_profiles as bp', 'th.business_profile_id', '=', 'bp.id')
            ->where('th.payee_id', $user->id)
            ->where('th.status', 'completed')
            ->selectRaw('
                SUM(th.net_amount) as total_earnings,
                COUNT(th.id) as transaction_count,
                MAX(th.created_at) as last_transaction_date,
                AVG(th.net_amount) as average_commission
            ')
            ->first();

        $balance['total_earnings'] = (float) ($adminCommissions->total_earnings ?? 0);
        $balance['current_balance'] = $balance['total_earnings'];
        $balance['transaction_count'] = (int) ($adminCommissions->transaction_count ?? 0);
        $balance['last_transaction_date'] = $adminCommissions->last_transaction_date;

        // Données hiérarchiques
        $balance['hierarchy_data'] = $this->getAdminHierarchyData($user);
        
        // Breakdown des commissions
        $balance['commission_breakdown'] = $this->getAdminCommissionBreakdown($user);
        
        // Métriques de performance
        $balance['performance_metrics'] = $this->getAdminPerformanceMetrics($user);

        return $balance;
    }

    /**
     * Calcule la balance pour un intégrateur
     * 
     * @param User $user
     * @param array $balance
     * @return array
     */
    private function calculateIntegratorBalance(User $user, array $balance): array
    {
        // Récupérer les commissions intégrateur
        $integratorCommissions = DB::table('transaction_hierarchies as th')
            ->join('business_profiles as bp', 'th.business_profile_id', '=', 'bp.id')
            ->where('th.payee_id', $user->id)
            ->where('th.status', 'completed')
            ->selectRaw('
                SUM(th.net_amount) as total_earnings,
                COUNT(th.id) as transaction_count,
                MAX(th.created_at) as last_transaction_date
            ')
            ->first();

        $balance['total_earnings'] = (float) ($integratorCommissions->total_earnings ?? 0);
        $balance['current_balance'] = $balance['total_earnings'];
        $balance['transaction_count'] = (int) ($integratorCommissions->transaction_count ?? 0);
        $balance['last_transaction_date'] = $integratorCommissions->last_transaction_date;

        // Données hiérarchiques (opérateurs sous cet intégrateur)
        $balance['hierarchy_data'] = $this->getIntegratorHierarchyData($user);
        
        // Breakdown des commissions
        $balance['commission_breakdown'] = $this->getIntegratorCommissionBreakdown($user);
        
        // Métriques de performance
        $balance['performance_metrics'] = $this->getIntegratorPerformanceMetrics($user);

        return $balance;
    }

    /**
     * Calcule la balance pour un opérateur
     * 
     * @param User $user
     * @param array $balance
     * @return array
     */
    private function calculateOperatorBalance(User $user, array $balance): array
    {
        // Récupérer les revenus de l'opérateur
        $operatorEarnings = DB::table('transaction_hierarchies as th')
            ->join('business_profiles as bp', 'th.business_profile_id', '=', 'bp.id')
            ->where('th.payee_id', $user->id)
            ->where('th.status', 'completed')
            ->selectRaw('
                SUM(th.net_amount) as total_earnings,
                COUNT(th.id) as transaction_count,
                MAX(th.created_at) as last_transaction_date
            ')
            ->first();

        $balance['total_earnings'] = (float) ($operatorEarnings->total_earnings ?? 0);
        $balance['current_balance'] = $balance['total_earnings'];
        $balance['transaction_count'] = (int) ($operatorEarnings->transaction_count ?? 0);
        $balance['last_transaction_date'] = $operatorEarnings->last_transaction_date;

        // Données hiérarchiques
        $balance['hierarchy_data'] = $this->getOperatorHierarchyData($user);
        
        // Breakdown des revenus
        $balance['commission_breakdown'] = $this->getOperatorRevenueBreakdown($user);
        
        // Métriques de performance
        $balance['performance_metrics'] = $this->getOperatorPerformanceMetrics($user);

        return $balance;
    }

    /**
     * Récupère les données hiérarchiques pour un admin
     * 
     * @param User $user
     * @return array
     */
    private function getAdminHierarchyData(User $user): array
    {
        // Récupérer tous les intégrateurs et leurs performances
        $integrators = DB::table('users as u')
            ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
            ->join('roles as r', 'mhr.role_id', '=', 'r.id')
            ->where('r.name', 'integrator')
            ->leftJoin('transaction_hierarchies as th', function($join) {
                $join->on('th.payee_id', '=', 'u.id')
                     ->where('th.status', '=', 'completed');
            })
            ->selectRaw('
                u.id,
                u.name,
                u.email,
                COUNT(th.id) as transaction_count,
                COALESCE(SUM(th.net_amount), 0) as total_commissions,
                MAX(th.created_at) as last_activity
            ')
            ->groupBy('u.id', 'u.name', 'u.email')
            ->get();

        return [
            'integrators' => $integrators->map(function($integrator) {
                return [
                    'id' => $integrator->id,
                    'name' => $integrator->name,
                    'email' => $integrator->email,
                    'transaction_count' => (int) $integrator->transaction_count,
                    'total_commissions' => (float) $integrator->total_commissions,
                    'last_activity' => $integrator->last_activity,
                ];
            })->toArray(),
            'total_integrators' => $integrators->count(),
            'total_commissions_from_integrators' => $integrators->sum('total_commissions')
        ];
    }

    /**
     * Récupère les données hiérarchiques pour un intégrateur
     * 
     * @param User $user
     * @return array
     */
    private function getIntegratorHierarchyData(User $user): array
    {
        // Récupérer tous les opérateurs sous cet intégrateur
        $operators = DB::table('users as u')
            ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
            ->join('roles as r', 'mhr.role_id', '=', 'r.id')
            ->where('r.name', 'operator')
            ->where('u.integrator_id', $user->id)
            ->leftJoin('transaction_hierarchies as th', function($join) {
                $join->on('th.payee_id', '=', 'u.id')
                     ->where('th.status', '=', 'completed');
            })
            ->selectRaw('
                u.id,
                u.name,
                u.email,
                COUNT(th.id) as transaction_count,
                COALESCE(SUM(th.net_amount), 0) as total_earnings,
                MAX(th.created_at) as last_activity
            ')
            ->groupBy('u.id', 'u.name', 'u.email')
            ->get();

        return [
            'operators' => $operators->map(function($operator) {
                return [
                    'id' => $operator->id,
                    'name' => $operator->name,
                    'email' => $operator->email,
                    'transaction_count' => (int) $operator->transaction_count,
                    'total_earnings' => (float) $operator->total_earnings,
                    'last_activity' => $operator->last_activity,
                ];
            })->toArray(),
            'total_operators' => $operators->count(),
            'total_earnings_from_operators' => $operators->sum('total_earnings')
        ];
    }

    /**
     * Récupère les données hiérarchiques pour un opérateur
     * 
     * @param User $user
     * @return array
     */
    private function getOperatorHierarchyData(User $user): array
    {
        // Récupérer l'intégrateur parent
        $integrator = DB::table('users as u')
            ->where('u.id', $user->integrator_id)
            ->select('id', 'name', 'email')
            ->first();

        return [
            'integrator' => $integrator ? [
                'id' => $integrator->id,
                'name' => $integrator->name,
                'email' => $integrator->email,
            ] : null,
            'charging_points' => $this->getOperatorChargingPoints($user)
        ];
    }

    /**
     * Récupère les points de charge d'un opérateur
     * 
     * @param User $user
     * @return array
     */
    private function getOperatorChargingPoints(User $user): array
    {
        return DB::table('charging_points as cp')
            ->where('cp.operator_id', $user->id)
            ->selectRaw('
                cp.id,
                cp.name,
                cp.status,
                COUNT(t.id) as transaction_count,
                COALESCE(SUM(t.amount), 0) as total_revenue
            ')
            ->leftJoin('transactions as t', function($join) {
                $join->on('t.charging_point_id', '=', 'cp.id')
                     ->where('t.status', '=', 'completed');
            })
            ->groupBy('cp.id', 'cp.name', 'cp.status')
            ->get()
            ->map(function($point) {
                return [
                    'id' => $point->id,
                    'name' => $point->name,
                    'status' => $point->status,
                    'transaction_count' => (int) $point->transaction_count,
                    'total_revenue' => (float) $point->total_revenue,
                ];
            })->toArray();
    }

    /**
     * Récupère le breakdown des commissions pour un admin
     * 
     * @param User $user
     * @return array
     */
    private function getAdminCommissionBreakdown(User $user): array
    {
        $breakdown = DB::table('transaction_hierarchies as th')
            ->join('business_profiles as bp', 'th.business_profile_id', '=', 'bp.id')
            ->where('th.payee_id', $user->id)
            ->where('th.status', 'completed')
            ->selectRaw('
                bp.name as business_profile_name,
                COUNT(th.id) as transaction_count,
                SUM(th.net_amount) as total_commission,
                AVG(th.net_amount) as average_commission,
                MAX(th.created_at) as last_transaction_date
            ')
            ->groupBy('bp.id', 'bp.name')
            ->get();

        return $breakdown->map(function($item) {
            return [
                'business_profile_name' => $item->business_profile_name,
                'transaction_count' => (int) $item->transaction_count,
                'total_commission' => (float) $item->total_commission,
                'average_commission' => (float) $item->average_commission,
                'last_transaction_date' => $item->last_transaction_date,
            ];
        })->toArray();
    }

    /**
     * Récupère le breakdown des commissions pour un intégrateur
     * 
     * @param User $user
     * @return array
     */
    private function getIntegratorCommissionBreakdown(User $user): array
    {
        $breakdown = DB::table('transaction_hierarchies as th')
            ->join('business_profiles as bp', 'th.business_profile_id', '=', 'bp.id')
            ->where('th.payee_id', $user->id)
            ->where('th.status', 'completed')
            ->selectRaw('
                bp.name as business_profile_name,
                COUNT(th.id) as transaction_count,
                SUM(th.net_amount) as total_commission,
                AVG(th.net_amount) as average_commission
            ')
            ->groupBy('bp.id', 'bp.name')
            ->get();

        return $breakdown->map(function($item) {
            return [
                'business_profile_name' => $item->business_profile_name,
                'transaction_count' => (int) $item->transaction_count,
                'total_commission' => (float) $item->total_commission,
                'average_commission' => (float) $item->average_commission,
            ];
        })->toArray();
    }

    /**
     * Récupère le breakdown des revenus pour un opérateur
     * 
     * @param User $user
     * @return array
     */
    private function getOperatorRevenueBreakdown(User $user): array
    {
        $breakdown = DB::table('transaction_hierarchies as th')
            ->join('business_profiles as bp', 'th.business_profile_id', '=', 'bp.id')
            ->where('th.payee_id', $user->id)
            ->where('th.status', 'completed')
            ->selectRaw('
                bp.name as business_profile_name,
                COUNT(th.id) as transaction_count,
                SUM(th.net_amount) as total_revenue,
                AVG(th.net_amount) as average_revenue
            ')
            ->groupBy('bp.id', 'bp.name')
            ->get();

        return $breakdown->map(function($item) {
            return [
                'business_profile_name' => $item->business_profile_name,
                'transaction_count' => (int) $item->transaction_count,
                'total_revenue' => (float) $item->total_revenue,
                'average_revenue' => (float) $item->average_revenue,
            ];
        })->toArray();
    }

    /**
     * Récupère les métriques de performance pour un admin
     * 
     * @param User $user
     * @return array
     */
    private function getAdminPerformanceMetrics(User $user): array
    {
        $metrics = DB::table('transaction_hierarchies as th')
            ->where('th.payee_id', $user->id)
            ->where('th.status', 'completed')
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(th.net_amount) as total_commissions,
                AVG(th.net_amount) as average_commission,
                MIN(th.net_amount) as min_commission,
                MAX(th.net_amount) as max_commission,
                COUNT(DISTINCT DATE(th.created_at)) as active_days
            ')
            ->first();

        return [
            'total_transactions' => (int) ($metrics->total_transactions ?? 0),
            'total_commissions' => (float) ($metrics->total_commissions ?? 0),
            'average_commission' => (float) ($metrics->average_commission ?? 0),
            'min_commission' => (float) ($metrics->min_commission ?? 0),
            'max_commission' => (float) ($metrics->max_commission ?? 0),
            'active_days' => (int) ($metrics->active_days ?? 0),
        ];
    }

    /**
     * Récupère les métriques de performance pour un intégrateur
     * 
     * @param User $user
     * @return array
     */
    private function getIntegratorPerformanceMetrics(User $user): array
    {
        $metrics = DB::table('transaction_hierarchies as th')
            ->where('th.payee_id', $user->id)
            ->where('th.status', 'completed')
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(th.net_amount) as total_commissions,
                AVG(th.net_amount) as average_commission,
                COUNT(DISTINCT DATE(th.created_at)) as active_days
            ')
            ->first();

        return [
            'total_transactions' => (int) ($metrics->total_transactions ?? 0),
            'total_commissions' => (float) ($metrics->total_commissions ?? 0),
            'average_commission' => (float) ($metrics->average_commission ?? 0),
            'active_days' => (int) ($metrics->active_days ?? 0),
        ];
    }

    /**
     * Récupère les métriques de performance pour un opérateur
     * 
     * @param User $user
     * @return array
     */
    private function getOperatorPerformanceMetrics(User $user): array
    {
        $metrics = DB::table('transaction_hierarchies as th')
            ->where('th.payee_id', $user->id)
            ->where('th.status', 'completed')
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(th.net_amount) as total_revenue,
                AVG(th.net_amount) as average_revenue,
                COUNT(DISTINCT DATE(th.created_at)) as active_days
            ')
            ->first();

        return [
            'total_transactions' => (int) ($metrics->total_transactions ?? 0),
            'total_revenue' => (float) ($metrics->total_revenue ?? 0),
            'average_revenue' => (float) ($metrics->average_revenue ?? 0),
            'active_days' => (int) ($metrics->active_days ?? 0),
        ];
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

        Log::info('Balance hiérarchique mise à jour', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role' => $user->getRoleNames()->first(),
            'balance' => $balance['current_balance'],
            'total_earnings' => $balance['total_earnings']
        ]);
    }

    /**
     * Calcule les statistiques globales du système
     * 
     * @param bool $forceRefresh
     * @return array
     */
    public function calculateGlobalStatistics(bool $forceRefresh = false): array
    {
        $cacheKey = 'global_hierarchical_statistics';
        
        if ($forceRefresh) {
            $this->cacheService->forget($cacheKey);
        }

        return $this->cacheService->remember(
            $cacheKey, 
            $this->cacheConfig['global_stats'], 
            function () {
                return $this->computeGlobalStatistics();
            },
            ['global_statistics']
        );
    }

    /**
     * Calcule les statistiques globales sans cache
     * 
     * @return array
     */
    private function computeGlobalStatistics(): array
    {
        $stats = [
            'calculated_at' => now()->toISOString(),
            'total_users' => 0,
            'total_transactions' => 0,
            'total_revenue' => 0.0,
            'hierarchy_breakdown' => [],
            'performance_metrics' => [],
            'recent_activity' => []
        ];

        // Statistiques par rôle
        $roleStats = DB::table('users as u')
            ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
            ->join('roles as r', 'mhr.role_id', '=', 'r.id')
            ->leftJoin('transaction_hierarchies as th', function($join) {
                $join->on('th.payee_id', '=', 'u.id')
                     ->where('th.status', '=', 'completed');
            })
            ->selectRaw('
                r.name as role,
                COUNT(DISTINCT u.id) as user_count,
                COUNT(th.id) as transaction_count,
                COALESCE(SUM(th.net_amount), 0) as total_amount
            ')
            ->groupBy('r.name')
            ->get();

        $stats['hierarchy_breakdown'] = $roleStats->map(function($role) {
            return [
                'role' => $role->role,
                'user_count' => (int) $role->user_count,
                'transaction_count' => (int) $role->transaction_count,
                'total_amount' => (float) $role->total_amount,
            ];
        })->toArray();

        $stats['total_users'] = $roleStats->sum('user_count');
        $stats['total_transactions'] = $roleStats->sum('transaction_count');
        $stats['total_revenue'] = $roleStats->sum('total_amount');

        // Métriques de performance
        $stats['performance_metrics'] = $this->getGlobalPerformanceMetrics();

        // Activité récente
        $stats['recent_activity'] = $this->getRecentActivity();

        return $stats;
    }

    /**
     * Récupère les métriques de performance globales
     * 
     * @return array
     */
    private function getGlobalPerformanceMetrics(): array
    {
        $metrics = DB::table('transaction_hierarchies as th')
            ->where('th.status', 'completed')
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(th.net_amount) as total_amount,
                AVG(th.net_amount) as average_amount,
                MIN(th.net_amount) as min_amount,
                MAX(th.net_amount) as max_amount,
                COUNT(DISTINCT DATE(th.created_at)) as active_days,
                COUNT(DISTINCT th.payee_id) as active_users
            ')
            ->first();

        return [
            'total_transactions' => (int) ($metrics->total_transactions ?? 0),
            'total_amount' => (float) ($metrics->total_amount ?? 0),
            'average_amount' => (float) ($metrics->average_amount ?? 0),
            'min_amount' => (float) ($metrics->min_amount ?? 0),
            'max_amount' => (float) ($metrics->max_amount ?? 0),
            'active_days' => (int) ($metrics->active_days ?? 0),
            'active_users' => (int) ($metrics->active_users ?? 0),
        ];
    }

    /**
     * Récupère l'activité récente
     * 
     * @return array
     */
    private function getRecentActivity(): array
    {
        $recentTransactions = DB::table('transaction_hierarchies as th')
            ->join('users as u', 'th.payee_id', '=', 'u.id')
            ->where('th.status', 'completed')
            ->where('th.created_at', '>=', now()->subDays(7))
            ->selectRaw('
                th.id,
                th.net_amount,
                th.created_at,
                u.name as user_name,
                u.email as user_email
            ')
            ->orderBy('th.created_at', 'desc')
            ->limit(10)
            ->get();

        return $recentTransactions->map(function($transaction) {
            return [
                'id' => $transaction->id,
                'amount' => (float) $transaction->net_amount,
                'created_at' => $transaction->created_at,
                'user_name' => $transaction->user_name,
                'user_email' => $transaction->user_email,
            ];
        })->toArray();
    }

    /**
     * Recalcule toutes les balances du système
     * 
     * @param bool $forceRefresh
     * @return array
     */
    public function recalculateAllBalances(bool $forceRefresh = true): array
    {
        $results = [
            'started_at' => now()->toISOString(),
            'processed_users' => 0,
            'errors' => [],
            'success' => true
        ];

        try {
            // Récupérer tous les utilisateurs avec des rôles
            $users = User::with('roles')->get();

            foreach ($users as $user) {
                try {
                    $this->calculateUserBalance($user, $forceRefresh);
                    $results['processed_users']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'error' => $e->getMessage()
                    ];
                    Log::error('Erreur lors du recalcul de balance', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Nettoyer le cache global
            $this->cacheService->forgetByTags(['global_statistics']);

            $results['completed_at'] = now()->toISOString();
            $results['success'] = count($results['errors']) === 0;

            Log::info('Recalcul de toutes les balances terminé', $results);

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = [
                'error' => $e->getMessage()
            ];
            Log::error('Erreur lors du recalcul global des balances', [
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Nettoie le cache des balances
     * 
     * @param string|null $userId
     * @return bool
     */
    public function clearBalanceCache(?string $userId = null): bool
    {
        try {
            if ($userId) {
                $this->cacheService->forgetByTags(['hierarchical_balances', "user_{$userId}"]);
            } else {
                $this->cacheService->forgetByTags(['hierarchical_balances', 'global_statistics']);
            }
            
            Log::info('Cache des balances nettoyé', ['user_id' => $userId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors du nettoyage du cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
