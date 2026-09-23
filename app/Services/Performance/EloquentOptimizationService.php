<?php

namespace App\Services\Performance;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EloquentOptimizationService
{
    protected const CACHE_TTL = 300; // 5 minutes

    /**
     * Optimize User queries with eager loading
     */
    public function getOptimizedUsers(array $filters = [], int $perPage = 15)
    {
        $query = User::with([
            'roles',
            'permissions',
            'integrator',
            'partner',
            'wallet'
        ]);

        // Apply filters
        if (isset($filters['role'])) {
            $query->role($filters['role']);
        }

        if (isset($filters['active'])) {
            $query->where('is_active', $filters['active']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Optimize Transaction queries with eager loading
     */
    public function getOptimizedTransactions(array $filters = [], int $perPage = 15)
    {
        $query = Transaction::with([
            'chargingPoint',
            'user',
            'pricingPlan',
            'walletTransactions'
        ]);

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['charging_point_id'])) {
            $query->where('charging_point_id', $filters['charging_point_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        if (isset($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Optimize ChargingPoint queries with eager loading
     */
    public function getOptimizedChargingPoints(array $filters = [], int $perPage = 15)
    {
        $query = ChargingPoint::with([
            'integrator',
            'partner',
            'group',
            'pricingPlan',
            'businessProfile',
            'connectors'
        ]);

        // Apply filters
        if (isset($filters['integrator_id'])) {
            $query->where('integrator_id', $filters['integrator_id']);
        }

        if (isset($filters['partner_id'])) {
            $query->where('partner_id', $filters['partner_id']);
        }

        if (isset($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('serial_number', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['location'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('latitude', '>=', $filters['location']['lat_min'])
                  ->where('latitude', '<=', $filters['location']['lat_max'])
                  ->where('longitude', '>=', $filters['location']['lng_min'])
                  ->where('longitude', '<=', $filters['location']['lng_max']);
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Get optimized dashboard data
     */
    public function getOptimizedDashboardData(): array
    {
        $cacheKey = 'optimized_dashboard_data';
        
        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL), function () {
            return [
                'user_stats' => $this->getUserStats(),
                'transaction_stats' => $this->getTransactionStats(),
                'charging_point_stats' => $this->getChargingPointStats(),
                'revenue_stats' => $this->getRevenueStats(),
                'recent_activities' => $this->getRecentActivities()
            ];
        });
    }

    /**
     * Get user statistics with optimized queries
     */
    private function getUserStats(): array
    {
        try {
            $totalUsers = User::count();
            $activeUsers = User::where('last_activity_at', '>=', now()->subDays(7))->count();
            $newUsersToday = User::whereDate('created_at', today())->count();
            $newUsersWeek = User::where('created_at', '>=', now()->subWeek())->count();

            // Role distribution with single query
            $roleDistribution = User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->selectRaw('roles.name, COUNT(*) as count')
                ->groupBy('roles.name')
                ->get()
                ->pluck('count', 'name')
                ->toArray();

            return [
                'total_users' => $totalUsers,
                'active_users_7d' => $activeUsers,
                'new_users_today' => $newUsersToday,
                'new_users_week' => $newUsersWeek,
                'role_distribution' => $roleDistribution,
                'user_activity_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user stats', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve user statistics'];
        }
    }

    /**
     * Get transaction statistics with optimized queries
     */
    private function getTransactionStats(): array
    {
        try {
            $totalTransactions = Transaction::count();
            $completedTransactions = Transaction::where('status', 'completed')->count();
            $transactionsToday = Transaction::whereDate('created_at', today())->count();
            $transactionsWeek = Transaction::where('created_at', '>=', now()->subWeek())->count();

            // Status distribution with single query
            $statusDistribution = Transaction::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            // Revenue statistics
            $totalRevenue = Transaction::where('status', 'completed')->sum('amount');
            $revenueToday = Transaction::where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('amount');
            $revenueWeek = Transaction::where('status', 'completed')
                ->where('created_at', '>=', now()->subWeek())
                ->sum('amount');

            return [
                'total_transactions' => $totalTransactions,
                'completed_transactions' => $completedTransactions,
                'transactions_today' => $transactionsToday,
                'transactions_week' => $transactionsWeek,
                'status_distribution' => $statusDistribution,
                'completion_rate' => $totalTransactions > 0 ? round(($completedTransactions / $totalTransactions) * 100, 2) : 0,
                'total_revenue' => $totalRevenue,
                'revenue_today' => $revenueToday,
                'revenue_week' => $revenueWeek,
                'avg_transaction_value' => $completedTransactions > 0 ? round($totalRevenue / $completedTransactions, 2) : 0
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get transaction stats', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve transaction statistics'];
        }
    }

    /**
     * Get charging point statistics with optimized queries
     */
    private function getChargingPointStats(): array
    {
        try {
            $totalChargingPoints = ChargingPoint::count();
            
            // Status distribution with single query
            $statusDistribution = ChargingPoint::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            $availableChargingPoints = $statusDistribution['available'] ?? 0;
            $offlineChargingPoints = $statusDistribution['offline'] ?? 0;
            $faultedChargingPoints = $statusDistribution['faulted'] ?? 0;

            return [
                'total_charging_points' => $totalChargingPoints,
                'status_distribution' => $statusDistribution,
                'available_charging_points' => $availableChargingPoints,
                'offline_charging_points' => $offlineChargingPoints,
                'faulted_charging_points' => $faultedChargingPoints,
                'availability_rate' => $totalChargingPoints > 0 ? round(($availableChargingPoints / $totalChargingPoints) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get charging point stats', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve charging point statistics'];
        }
    }

    /**
     * Get revenue statistics with optimized queries
     */
    private function getRevenueStats(): array
    {
        try {
            $totalRevenue = Transaction::where('status', 'completed')->sum('amount');
            $revenueToday = Transaction::where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('amount');
            $revenueWeek = Transaction::where('status', 'completed')
                ->where('created_at', '>=', now()->subWeek())
                ->sum('amount');
            $revenueMonth = Transaction::where('status', 'completed')
                ->where('created_at', '>=', now()->subMonth())
                ->sum('amount');

            // Commission breakdown with single query
            $commissionStats = Transaction::where('status', 'completed')
                ->selectRaw('
                    SUM(admin_commission) as total_admin_commission,
                    SUM(integrator_commission) as total_integrator_commission,
                    SUM(partner_commission) as total_partner_commission,
                    SUM(price_tax) as total_taxes,
                    SUM(price_service) as total_service_fees
                ')
                ->first();

            return [
                'total_revenue' => $totalRevenue,
                'revenue_today' => $revenueToday,
                'revenue_week' => $revenueWeek,
                'revenue_month' => $revenueMonth,
                'commission_breakdown' => [
                    'admin_commission' => $commissionStats->total_admin_commission ?? 0,
                    'integrator_commission' => $commissionStats->total_integrator_commission ?? 0,
                    'partner_commission' => $commissionStats->total_partner_commission ?? 0,
                    'total_commissions' => ($commissionStats->total_admin_commission ?? 0) + 
                                         ($commissionStats->total_integrator_commission ?? 0) + 
                                         ($commissionStats->total_partner_commission ?? 0)
                ],
                'taxes_and_fees' => [
                    'total_taxes' => $commissionStats->total_taxes ?? 0,
                    'total_service_fees' => $commissionStats->total_service_fees ?? 0
                ],
                'net_revenue' => $totalRevenue - ($commissionStats->total_taxes ?? 0) - ($commissionStats->total_service_fees ?? 0)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get revenue stats', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve revenue statistics'];
        }
    }

    /**
     * Get recent activities with optimized queries
     */
    private function getRecentActivities(): array
    {
        try {
            $recentTransactions = Transaction::with(['user', 'chargingPoint'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => 'transaction',
                        'description' => "Transaction {$transaction->status} for {$transaction->amount}€",
                        'user' => $transaction->user->name ?? 'Unknown',
                        'charging_point' => $transaction->chargingPoint->name ?? 'Unknown',
                        'created_at' => $transaction->created_at->toISOString()
                    ];
                });

            $recentUsers = User::orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'type' => 'user',
                        'description' => "New user registered: {$user->name}",
                        'user' => $user->name,
                        'created_at' => $user->created_at->toISOString()
                    ];
                });

            return [
                'recent_transactions' => $recentTransactions,
                'recent_users' => $recentUsers,
                'last_updated' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get recent activities', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve recent activities'];
        }
    }

    /**
     * Optimize query by adding select specific columns
     */
    public function optimizeQuery(Builder $query, array $columns = []): Builder
    {
        if (!empty($columns)) {
            $query->select($columns);
        }

        return $query;
    }

    /**
     * Add query caching
     */
    public function cacheQuery(string $key, callable $callback, int $ttl = null): mixed
    {
        $ttl = $ttl ?? self::CACHE_TTL;
        $cacheKey = 'eloquent_optimization:' . $key;
        
        return Cache::remember($cacheKey, now()->addSeconds($ttl), $callback);
    }

    /**
     * Clear optimization cache
     */
    public function clearCache(): void
    {
        Cache::forget('optimized_dashboard_data');
        Log::info('Eloquent optimization cache cleared');
    }
}
