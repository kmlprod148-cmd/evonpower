<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\HierarchicalBalanceService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur pour le dashboard des balances hiérarchiques
 * 
 * Fournit une interface web pour :
 * - Visualisation des balances en temps réel
 * - Statistiques globales détaillées
 * - Gestion des recalculs
 * - Monitoring des performances
 */
class BalanceDashboardController extends Controller
{
    protected HierarchicalBalanceService $balanceService;

    public function __construct(HierarchicalBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
        $this->middleware('auth');
    }

    /**
     * Affiche le dashboard principal des balances
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $userRole = $user->getRoleNames()->first();

        try {
            // Récupérer les données selon le rôle
            $dashboardData = $this->getDashboardData($user, $userRole);

            return view('dashboard.balances.index', [
                'user' => $user,
                'userRole' => $userRole,
                'dashboardData' => $dashboardData,
                'pageTitle' => 'Dashboard des Balances Hiérarchiques'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du chargement du dashboard des balances', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return view('dashboard.balances.index', [
                'user' => $user,
                'userRole' => $userRole,
                'dashboardData' => null,
                'error' => 'Erreur lors du chargement des données du dashboard',
                'pageTitle' => 'Dashboard des Balances Hiérarchiques'
            ]);
        }
    }

    /**
     * Récupère les données du dashboard selon le rôle
     * 
     * @param User $user
     * @param string $userRole
     * @return array
     */
    private function getDashboardData(User $user, string $userRole): array
    {
        $data = [
            'user_balance' => null,
            'hierarchy_data' => null,
            'global_statistics' => null,
            'performance_metrics' => null,
            'recent_activity' => null
        ];

        // Balance de l'utilisateur
        $data['user_balance'] = $this->balanceService->calculateUserBalance($user);

        // Données hiérarchiques
        $data['hierarchy_data'] = $this->getHierarchyData($user, $userRole);

        // Statistiques globales (admin seulement)
        if ($userRole === 'admin') {
            $data['global_statistics'] = $this->balanceService->calculateGlobalStatistics();
        }

        // Métriques de performance
        $data['performance_metrics'] = $this->getPerformanceMetrics($user, $userRole);

        // Activité récente
        $data['recent_activity'] = $this->getRecentActivity($user, $userRole);

        return $data;
    }

    /**
     * Récupère les données hiérarchiques selon le rôle
     * 
     * @param User $user
     * @param string $userRole
     * @return array
     */
    private function getHierarchyData(User $user, string $userRole): array
    {
        switch ($userRole) {
            case 'admin':
                return $this->getAdminHierarchyData();
            case 'integrator':
                return $this->getIntegratorHierarchyData($user);
            case 'operator':
                return $this->getOperatorHierarchyData($user);
            default:
                return [];
        }
    }

    /**
     * Récupère les données hiérarchiques pour un admin
     * 
     * @return array
     */
    private function getAdminHierarchyData(): array
    {
        $integrators = \DB::table('users as u')
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
            ->orderBy('total_commissions', 'desc')
            ->get();

        return [
            'type' => 'admin',
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
            'total_commissions' => $integrators->sum('total_commissions')
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
        $operators = \DB::table('users as u')
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
            ->orderBy('total_earnings', 'desc')
            ->get();

        return [
            'type' => 'integrator',
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
            'total_earnings' => $operators->sum('total_earnings')
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
        $integrator = \DB::table('users as u')
            ->where('u.id', $user->integrator_id)
            ->select('id', 'name', 'email')
            ->first();

        // Récupérer les points de charge
        $chargingPoints = \DB::table('charging_points as cp')
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
            ->orderBy('total_revenue', 'desc')
            ->get();

        return [
            'type' => 'operator',
            'integrator' => $integrator ? [
                'id' => $integrator->id,
                'name' => $integrator->name,
                'email' => $integrator->email,
            ] : null,
            'charging_points' => $chargingPoints->map(function($point) {
                return [
                    'id' => $point->id,
                    'name' => $point->name,
                    'status' => $point->status,
                    'transaction_count' => (int) $point->transaction_count,
                    'total_revenue' => (float) $point->total_revenue,
                ];
            })->toArray()
        ];
    }

    /**
     * Récupère les métriques de performance
     * 
     * @param User $user
     * @param string $userRole
     * @return array
     */
    private function getPerformanceMetrics(User $user, string $userRole): array
    {
        $metrics = [];

        // Métriques générales
        $generalMetrics = \DB::table('transaction_hierarchies as th')
            ->where('th.payee_id', $user->id)
            ->where('th.status', 'completed')
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(th.net_amount) as total_amount,
                AVG(th.net_amount) as average_amount,
                MIN(th.net_amount) as min_amount,
                MAX(th.net_amount) as max_amount,
                COUNT(DISTINCT DATE(th.created_at)) as active_days
            ')
            ->first();

        $metrics['general'] = [
            'total_transactions' => (int) ($generalMetrics->total_transactions ?? 0),
            'total_amount' => (float) ($generalMetrics->total_amount ?? 0),
            'average_amount' => (float) ($generalMetrics->average_amount ?? 0),
            'min_amount' => (float) ($generalMetrics->min_amount ?? 0),
            'max_amount' => (float) ($generalMetrics->max_amount ?? 0),
            'active_days' => (int) ($generalMetrics->active_days ?? 0),
        ];

        // Métriques par période
        $metrics['periods'] = $this->getPeriodMetrics($user);

        // Métriques de tendance
        $metrics['trends'] = $this->getTrendMetrics($user);

        return $metrics;
    }

    /**
     * Récupère les métriques par période
     * 
     * @param User $user
     * @return array
     */
    private function getPeriodMetrics(User $user): array
    {
        $periods = [
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear()
        ];

        $metrics = [];

        foreach ($periods as $period => $startDate) {
            $periodData = \DB::table('transaction_hierarchies as th')
                ->where('th.payee_id', $user->id)
                ->where('th.status', 'completed')
                ->where('th.created_at', '>=', $startDate)
                ->selectRaw('
                    COUNT(*) as transaction_count,
                    SUM(th.net_amount) as total_amount,
                    AVG(th.net_amount) as average_amount
                ')
                ->first();

            $metrics[$period] = [
                'transaction_count' => (int) ($periodData->transaction_count ?? 0),
                'total_amount' => (float) ($periodData->total_amount ?? 0),
                'average_amount' => (float) ($periodData->average_amount ?? 0),
            ];
        }

        return $metrics;
    }

    /**
     * Récupère les métriques de tendance
     * 
     * @param User $user
     * @return array
     */
    private function getTrendMetrics(User $user): array
    {
        // Données des 30 derniers jours
        $dailyData = \DB::table('transaction_hierarchies as th')
            ->where('th.payee_id', $user->id)
            ->where('th.status', 'completed')
            ->where('th.created_at', '>=', now()->subDays(30))
            ->selectRaw('
                DATE(th.created_at) as date,
                COUNT(*) as transaction_count,
                SUM(th.net_amount) as total_amount
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $trends = [
            'daily_data' => $dailyData->map(function($day) {
                return [
                    'date' => $day->date,
                    'transaction_count' => (int) $day->transaction_count,
                    'total_amount' => (float) $day->total_amount,
                ];
            })->toArray(),
            'growth_rate' => $this->calculateGrowthRate($dailyData),
            'average_daily' => $dailyData->avg('total_amount'),
            'best_day' => $dailyData->max('total_amount'),
            'worst_day' => $dailyData->min('total_amount')
        ];

        return $trends;
    }

    /**
     * Calcule le taux de croissance
     * 
     * @param \Illuminate\Support\Collection $dailyData
     * @return float
     */
    private function calculateGrowthRate($dailyData): float
    {
        if ($dailyData->count() < 2) {
            return 0;
        }

        $firstHalf = $dailyData->take(15)->sum('total_amount');
        $secondHalf = $dailyData->skip(15)->sum('total_amount');

        if ($firstHalf == 0) {
            return 0;
        }

        return round((($secondHalf - $firstHalf) / $firstHalf) * 100, 2);
    }

    /**
     * Récupère l'activité récente
     * 
     * @param User $user
     * @param string $userRole
     * @return array
     */
    private function getRecentActivity(User $user, string $userRole): array
    {
        $query = \DB::table('transaction_hierarchies as th')
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
            ->limit(10);

        // Filtrer selon le rôle
        if ($userRole === 'integrator') {
            $query->where('u.integrator_id', $user->id);
        } elseif ($userRole === 'operator') {
            $query->where('th.payee_id', $user->id);
        }

        $recentTransactions = $query->get();

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
     * Affiche la page de recalcul des balances
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function recalculate(Request $request)
    {
        $user = $request->user();
        
        // Vérifier les permissions
        if (!$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        return view('dashboard.balances.recalculate', [
            'user' => $user,
            'pageTitle' => 'Recalcul des Balances'
        ]);
    }

    /**
     * Traite le recalcul des balances
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processRecalculation(Request $request)
    {
        $user = $request->user();
        
        // Vérifier les permissions
        if (!$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'role' => 'nullable|string|in:admin,integrator,operator',
            'force' => 'boolean',
            'clear_cache' => 'boolean'
        ]);

        try {
            $userId = $request->input('user_id');
            $role = $request->input('role');
            $force = $request->boolean('force', false);
            $clearCache = $request->boolean('clear_cache', false);

            // Nettoyer le cache si demandé
            if ($clearCache) {
                $this->balanceService->clearBalanceCache($userId);
            }

            $results = [];

            if ($userId) {
                // Recalcul pour un utilisateur spécifique
                $targetUser = User::findOrFail($userId);
                $balance = $this->balanceService->calculateUserBalance($targetUser, $force);
                $results = [
                    'success' => true,
                    'processed_users' => 1,
                    'message' => "Balance recalculée pour {$targetUser->name}"
                ];
            } elseif ($role) {
                // Recalcul pour un rôle spécifique
                $users = User::role($role)->get();
                $processed = 0;
                $errors = 0;

                foreach ($users as $user) {
                    try {
                        $this->balanceService->calculateUserBalance($user, $force);
                        $processed++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('Erreur lors du recalcul de balance', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $results = [
                    'success' => $errors === 0,
                    'processed_users' => $processed,
                    'errors' => $errors,
                    'message' => "Recalcul terminé pour {$processed} utilisateurs du rôle {$role}"
                ];
            } else {
                // Recalcul pour tous les utilisateurs
                $results = $this->balanceService->recalculateAllBalances($force);
            }

            return redirect()->route('dashboard.balances.index')
                ->with('success', $results['message'])
                ->with('results', $results);

        } catch (\Exception $e) {
            Log::error('Erreur lors du recalcul des balances', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('dashboard.balances.recalculate')
                ->with('error', 'Erreur lors du recalcul: ' . $e->getMessage());
        }
    }

    /**
     * Affiche les statistiques détaillées
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        // Vérifier les permissions
        if (!$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        try {
            $statistics = $this->balanceService->calculateGlobalStatistics();

            return view('dashboard.balances.statistics', [
                'user' => $user,
                'statistics' => $statistics,
                'pageTitle' => 'Statistiques Globales des Balances'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du chargement des statistiques', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return view('dashboard.balances.statistics', [
                'user' => $user,
                'statistics' => null,
                'error' => 'Erreur lors du chargement des statistiques',
                'pageTitle' => 'Statistiques Globales des Balances'
            ]);
        }
    }
}
