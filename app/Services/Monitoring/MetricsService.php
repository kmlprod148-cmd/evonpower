<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\AuditLog;

class MetricsService
{
    protected const CACHE_TTL = 300; // 5 minutes
    protected const METRICS_PREFIX = 'metrics';

    /**
     * Get comprehensive business metrics
     */
    public function getBusinessMetrics(): array
    {
        $cacheKey = $this->getCacheKey('business_metrics');
        
        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL), function () {
            return [
                'revenue_metrics' => $this->getRevenueMetrics(),
                'user_metrics' => $this->getDetailedUserMetrics(),
                'charging_metrics' => $this->getChargingMetrics(),
                'operational_metrics' => $this->getOperationalMetrics(),
                'financial_metrics' => $this->getFinancialMetrics(),
                'growth_metrics' => $this->getGrowthMetrics(),
                'efficiency_metrics' => $this->getEfficiencyMetrics(),
                'last_updated' => now()->toISOString()
            ];
        });
    }

    /**
     * Get revenue metrics
     */
    public function getRevenueMetrics(): array
    {
        try {
            $totalRevenue = Transaction::where('status', 'completed')->sum('amount');
            $revenueToday = Transaction::where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('amount');
            $revenueThisWeek = Transaction::where('status', 'completed')
                ->where('created_at', '>=', now()->subWeek())
                ->sum('amount');
            $revenueThisMonth = Transaction::where('status', 'completed')
                ->where('created_at', '>=', now()->subMonth())
                ->sum('amount');

            $avgTransactionValue = Transaction::where('status', 'completed')->avg('amount');
            $totalTransactions = Transaction::where('status', 'completed')->count();

            return [
                'total_revenue' => $totalRevenue,
                'revenue_today' => $revenueToday,
                'revenue_week' => $revenueThisWeek,
                'revenue_month' => $revenueThisMonth,
                'avg_transaction_value' => round($avgTransactionValue, 2),
                'total_transactions' => $totalTransactions,
                'revenue_per_transaction' => $totalTransactions > 0 ? round($totalRevenue / $totalTransactions, 2) : 0
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get revenue metrics', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve revenue metrics'];
        }
    }

    /**
     * Get detailed user metrics
     */
    public function getDetailedUserMetrics(): array
    {
        try {
            $totalUsers = User::count();
            $activeUsers7d = User::where('last_activity_at', '>=', now()->subDays(7))->count();
            $activeUsers30d = User::where('last_activity_at', '>=', now()->subDays(30))->count();
            $newUsersToday = User::whereDate('created_at', today())->count();
            $newUsersWeek = User::where('created_at', '>=', now()->subWeek())->count();
            $newUsersMonth = User::where('created_at', '>=', now()->subMonth())->count();

            // User role distribution
            $roleDistribution = User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->selectRaw('roles.name, COUNT(*) as count')
                ->groupBy('roles.name')
                ->get()
                ->pluck('count', 'name')
                ->toArray();

            return [
                'total_users' => $totalUsers,
                'active_users_7d' => $activeUsers7d,
                'active_users_30d' => $activeUsers30d,
                'new_users_today' => $newUsersToday,
                'new_users_week' => $newUsersWeek,
                'new_users_month' => $newUsersMonth,
                'user_retention_rate' => $totalUsers > 0 ? round(($activeUsers30d / $totalUsers) * 100, 2) : 0,
                'role_distribution' => $roleDistribution,
                'user_activity_rate' => $totalUsers > 0 ? round(($activeUsers7d / $totalUsers) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user metrics', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve user metrics'];
        }
    }

    /**
     * Get charging metrics
     */
    public function getChargingMetrics(): array
    {
        try {
            $totalChargingPoints = ChargingPoint::count();
            $availableChargingPoints = ChargingPoint::where('status', 'available')->count();
            $offlineChargingPoints = ChargingPoint::where('status', 'offline')->count();
            $faultedChargingPoints = ChargingPoint::where('status', 'faulted')->count();

            // Charging sessions metrics
            $totalSessions = Transaction::where('status', 'completed')->count();
            $sessionsToday = Transaction::where('status', 'completed')
                ->whereDate('created_at', today())
                ->count();
            $sessionsThisWeek = Transaction::where('status', 'completed')
                ->where('created_at', '>=', now()->subWeek())
                ->count();

            // Energy delivered metrics
            $totalEnergyDelivered = Transaction::where('status', 'completed')->sum('energy_delivered');
            $energyToday = Transaction::where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('energy_delivered');
            $energyThisWeek = Transaction::where('status', 'completed')
                ->where('created_at', '>=', now()->subWeek())
                ->sum('energy_delivered');

            return [
                'total_charging_points' => $totalChargingPoints,
                'available_charging_points' => $availableChargingPoints,
                'offline_charging_points' => $offlineChargingPoints,
                'faulted_charging_points' => $faultedChargingPoints,
                'availability_rate' => $totalChargingPoints > 0 ? round(($availableChargingPoints / $totalChargingPoints) * 100, 2) : 0,
                'total_sessions' => $totalSessions,
                'sessions_today' => $sessionsToday,
                'sessions_week' => $sessionsThisWeek,
                'total_energy_delivered' => round($totalEnergyDelivered, 2),
                'energy_today' => round($energyToday, 2),
                'energy_week' => round($energyThisWeek, 2),
                'avg_energy_per_session' => $totalSessions > 0 ? round($totalEnergyDelivered / $totalSessions, 2) : 0
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get charging metrics', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve charging metrics'];
        }
    }

    /**
     * Get operational metrics
     */
    public function getOperationalMetrics(): array
    {
        try {
            // Transaction status distribution
            $statusDistribution = Transaction::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            // Average session duration
            $avgSessionDuration = Transaction::where('status', 'completed')
                ->whereNotNull('duration')
                ->avg('duration');

            // Peak usage hours (simplified)
            $peakHours = Transaction::where('status', 'completed')
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('count', 'desc')
                ->limit(3)
                ->get()
                ->pluck('count', 'hour')
                ->toArray();

            return [
                'transaction_status_distribution' => $statusDistribution,
                'avg_session_duration_minutes' => round($avgSessionDuration, 2),
                'peak_usage_hours' => $peakHours,
                'completion_rate' => $this->calculateCompletionRate($statusDistribution),
                'last_updated' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get operational metrics', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve operational metrics'];
        }
    }

    /**
     * Get financial metrics
     */
    public function getFinancialMetrics(): array
    {
        try {
            $totalRevenue = Transaction::where('status', 'completed')->sum('amount');
            $totalTaxes = Transaction::where('status', 'completed')->sum('price_tax');
            $totalServiceFees = Transaction::where('status', 'completed')->sum('price_service');
            $totalEnergyCosts = Transaction::where('status', 'completed')->sum('price_energy');
            $totalTimeCosts = Transaction::where('status', 'completed')->sum('price_time');

            // Commission breakdown
            $totalAdminCommission = Transaction::where('status', 'completed')->sum('admin_commission');
            $totalIntegratorCommission = Transaction::where('status', 'completed')->sum('integrator_commission');
            $totalPartnerCommission = Transaction::where('status', 'completed')->sum('partner_commission');

            return [
                'total_revenue' => $totalRevenue,
                'total_taxes' => $totalTaxes,
                'total_service_fees' => $totalServiceFees,
                'total_energy_costs' => $totalEnergyCosts,
                'total_time_costs' => $totalTimeCosts,
                'net_revenue' => $totalRevenue - $totalTaxes - $totalServiceFees,
                'commission_breakdown' => [
                    'admin_commission' => $totalAdminCommission,
                    'integrator_commission' => $totalIntegratorCommission,
                    'partner_commission' => $totalPartnerCommission,
                    'total_commissions' => $totalAdminCommission + $totalIntegratorCommission + $totalPartnerCommission
                ],
                'commission_rate' => $totalRevenue > 0 ? round((($totalAdminCommission + $totalIntegratorCommission + $totalPartnerCommission) / $totalRevenue) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get financial metrics', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve financial metrics'];
        }
    }

    /**
     * Get growth metrics
     */
    public function getGrowthMetrics(): array
    {
        try {
            // User growth
            $usersThisMonth = User::where('created_at', '>=', now()->subMonth())->count();
            $usersLastMonth = User::whereBetween('created_at', [now()->subMonths(2), now()->subMonth()])->count();
            $userGrowthRate = $usersLastMonth > 0 ? round((($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100, 2) : 0;

            // Revenue growth
            $revenueThisMonth = Transaction::where('status', 'completed')
                ->where('created_at', '>=', now()->subMonth())
                ->sum('amount');
            $revenueLastMonth = Transaction::where('status', 'completed')
                ->whereBetween('created_at', [now()->subMonths(2), now()->subMonth()])
                ->sum('amount');
            $revenueGrowthRate = $revenueLastMonth > 0 ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 2) : 0;

            // Transaction growth
            $transactionsThisMonth = Transaction::where('created_at', '>=', now()->subMonth())->count();
            $transactionsLastMonth = Transaction::whereBetween('created_at', [now()->subMonths(2), now()->subMonth()])->count();
            $transactionGrowthRate = $transactionsLastMonth > 0 ? round((($transactionsThisMonth - $transactionsLastMonth) / $transactionsLastMonth) * 100, 2) : 0;

            return [
                'user_growth_rate' => $userGrowthRate,
                'revenue_growth_rate' => $revenueGrowthRate,
                'transaction_growth_rate' => $transactionGrowthRate,
                'users_this_month' => $usersThisMonth,
                'users_last_month' => $usersLastMonth,
                'revenue_this_month' => $revenueThisMonth,
                'revenue_last_month' => $revenueLastMonth,
                'transactions_this_month' => $transactionsThisMonth,
                'transactions_last_month' => $transactionsLastMonth
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get growth metrics', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve growth metrics'];
        }
    }

    /**
     * Get efficiency metrics
     */
    public function getEfficiencyMetrics(): array
    {
        try {
            $totalChargingPoints = ChargingPoint::count();
            $totalSessions = Transaction::where('status', 'completed')->count();
            $totalRevenue = Transaction::where('status', 'completed')->sum('amount');

            // Revenue per charging point
            $revenuePerChargingPoint = $totalChargingPoints > 0 ? round($totalRevenue / $totalChargingPoints, 2) : 0;

            // Sessions per charging point
            $sessionsPerChargingPoint = $totalChargingPoints > 0 ? round($totalSessions / $totalChargingPoints, 2) : 0;

            // Average revenue per session
            $avgRevenuePerSession = $totalSessions > 0 ? round($totalRevenue / $totalSessions, 2) : 0;

            return [
                'revenue_per_charging_point' => $revenuePerChargingPoint,
                'sessions_per_charging_point' => $sessionsPerChargingPoint,
                'avg_revenue_per_session' => $avgRevenuePerSession,
                'charging_point_utilization' => $this->calculateChargingPointUtilization(),
                'last_updated' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get efficiency metrics', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve efficiency metrics'];
        }
    }

    /**
     * Get metrics for specific time range
     */
    public function getMetricsForPeriod(string $startDate, string $endDate): array
    {
        $cacheKey = $this->getCacheKey("period_metrics_{$startDate}_{$endDate}");
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($startDate, $endDate) {
            try {
                $start = \Carbon\Carbon::parse($startDate);
                $end = \Carbon\Carbon::parse($endDate);

                $revenue = Transaction::where('status', 'completed')
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('amount');

                $transactions = Transaction::whereBetween('created_at', [$start, $end])->count();
                $users = User::whereBetween('created_at', [$start, $end])->count();

                return [
                    'period' => [
                        'start' => $start->toISOString(),
                        'end' => $end->toISOString(),
                        'days' => $start->diffInDays($end)
                    ],
                    'revenue' => $revenue,
                    'transactions' => $transactions,
                    'new_users' => $users,
                    'avg_daily_revenue' => $start->diffInDays($end) > 0 ? round($revenue / $start->diffInDays($end), 2) : 0,
                    'avg_daily_transactions' => $start->diffInDays($end) > 0 ? round($transactions / $start->diffInDays($end), 2) : 0
                ];
            } catch (\Exception $e) {
                Log::error('Failed to get period metrics', ['error' => $e->getMessage()]);
                return ['error' => 'Failed to retrieve period metrics'];
            }
        });
    }

    /**
     * Calculate completion rate
     */
    private function calculateCompletionRate(array $statusDistribution): float
    {
        $total = array_sum($statusDistribution);
        $completed = $statusDistribution['completed'] ?? 0;
        
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    /**
     * Calculate charging point utilization
     */
    private function calculateChargingPointUtilization(): float
    {
        try {
            $totalChargingPoints = ChargingPoint::count();
            $activeChargingPoints = ChargingPoint::where('status', 'available')->count();
            
            return $totalChargingPoints > 0 ? round(($activeChargingPoints / $totalChargingPoints) * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get cache key
     */
    private function getCacheKey(string $key): string
    {
        return self::METRICS_PREFIX . ':' . $key;
    }

    /**
     * Clear metrics cache
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey('business_metrics'));
        Log::info('Metrics cache cleared');
    }
}
