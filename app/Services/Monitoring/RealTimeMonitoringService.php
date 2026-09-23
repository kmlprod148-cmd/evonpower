<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\AuditLog;

class RealTimeMonitoringService
{
    protected const CACHE_TTL = 60; // 1 minute
    protected const METRICS_PREFIX = 'monitoring';

    /**
     * Get real-time system metrics
     */
    public function getSystemMetrics(): array
    {
        $cacheKey = $this->getCacheKey('system_metrics');
        
        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL), function () {
            return [
                'timestamp' => now()->toISOString(),
                'system_health' => $this->getSystemHealth(),
                'user_metrics' => $this->getUserMetrics(),
                'transaction_metrics' => $this->getTransactionMetrics(),
                'charging_point_metrics' => $this->getChargingPointMetrics(),
                'performance_metrics' => $this->getPerformanceMetrics(),
                'security_metrics' => $this->getSecurityMetrics(),
                'database_metrics' => $this->getDatabaseMetrics()
            ];
        });
    }

    /**
     * Get system health status
     */
    public function getSystemHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'checks' => [],
            'overall_score' => 100
        ];

        // Database connectivity check
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = ['status' => 'ok', 'message' => 'Database connected'];
        } catch (\Exception $e) {
            $health['checks']['database'] = ['status' => 'error', 'message' => 'Database connection failed'];
            $health['status'] = 'unhealthy';
            $health['overall_score'] -= 30;
        }

        // Cache system check
        try {
            Cache::put('health_check', 'ok', 10);
            $cacheValue = Cache::get('health_check');
            if ($cacheValue === 'ok') {
                $health['checks']['cache'] = ['status' => 'ok', 'message' => 'Cache system working'];
            } else {
                $health['checks']['cache'] = ['status' => 'warning', 'message' => 'Cache system inconsistent'];
                $health['overall_score'] -= 10;
            }
        } catch (\Exception $e) {
            $health['checks']['cache'] = ['status' => 'error', 'message' => 'Cache system failed'];
            $health['overall_score'] -= 20;
        }

        // Memory usage check
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryPercent = ($memoryUsage / $this->parseMemoryLimit($memoryLimit)) * 100;
        
        if ($memoryPercent > 90) {
            $health['checks']['memory'] = ['status' => 'error', 'message' => 'High memory usage: ' . round($memoryPercent, 2) . '%'];
            $health['overall_score'] -= 25;
        } elseif ($memoryPercent > 75) {
            $health['checks']['memory'] = ['status' => 'warning', 'message' => 'Moderate memory usage: ' . round($memoryPercent, 2) . '%'];
            $health['overall_score'] -= 10;
        } else {
            $health['checks']['memory'] = ['status' => 'ok', 'message' => 'Memory usage normal: ' . round($memoryPercent, 2) . '%'];
        }

        // Update overall status based on score
        if ($health['overall_score'] < 50) {
            $health['status'] = 'critical';
        } elseif ($health['overall_score'] < 75) {
            $health['status'] = 'warning';
        }

        return $health;
    }

    /**
     * Get user-related metrics
     */
    public function getUserMetrics(): array
    {
        try {
            $totalUsers = User::count();
            $activeUsers = User::where('last_activity_at', '>=', now()->subDays(7))->count();
            $newUsersToday = User::whereDate('created_at', today())->count();
            $newUsersThisWeek = User::where('created_at', '>=', now()->subWeek())->count();

            return [
                'total_users' => $totalUsers,
                'active_users_7d' => $activeUsers,
                'new_users_today' => $newUsersToday,
                'new_users_week' => $newUsersThisWeek,
                'user_growth_rate' => $this->calculateGrowthRate($newUsersThisWeek, $totalUsers),
                'last_updated' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user metrics', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve user metrics'];
        }
    }

    /**
     * Get transaction-related metrics
     */
    public function getTransactionMetrics(): array
    {
        try {
            $totalTransactions = Transaction::count();
            $transactionsToday = Transaction::whereDate('created_at', today())->count();
            $transactionsThisWeek = Transaction::where('created_at', '>=', now()->subWeek())->count();
            $totalRevenue = Transaction::where('status', 'completed')->sum('amount');
            $revenueToday = Transaction::where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('amount');

            return [
                'total_transactions' => $totalTransactions,
                'transactions_today' => $transactionsToday,
                'transactions_week' => $transactionsThisWeek,
                'total_revenue' => $totalRevenue,
                'revenue_today' => $revenueToday,
                'avg_transaction_value' => $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0,
                'transaction_growth_rate' => $this->calculateGrowthRate($transactionsThisWeek, $totalTransactions),
                'last_updated' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get transaction metrics', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve transaction metrics'];
        }
    }

    /**
     * Get charging point metrics
     */
    public function getChargingPointMetrics(): array
    {
        try {
            $totalChargingPoints = ChargingPoint::count();
            $availableChargingPoints = ChargingPoint::where('status', 'available')->count();
            $offlineChargingPoints = ChargingPoint::where('status', 'offline')->count();
            $faultedChargingPoints = ChargingPoint::where('status', 'faulted')->count();

            return [
                'total_charging_points' => $totalChargingPoints,
                'available' => $availableChargingPoints,
                'offline' => $offlineChargingPoints,
                'faulted' => $faultedChargingPoints,
                'availability_rate' => $totalChargingPoints > 0 ? ($availableChargingPoints / $totalChargingPoints) * 100 : 0,
                'last_updated' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get charging point metrics', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve charging point metrics'];
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        try {
            $memoryUsage = memory_get_usage(true);
            $peakMemoryUsage = memory_get_peak_usage(true);
            $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
            
            return [
                'memory_usage_bytes' => $memoryUsage,
                'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'peak_memory_usage_bytes' => $peakMemoryUsage,
                'peak_memory_usage_mb' => round($peakMemoryUsage / 1024 / 1024, 2),
                'memory_limit_bytes' => $memoryLimit,
                'memory_limit_mb' => round($memoryLimit / 1024 / 1024, 2),
                'memory_usage_percent' => round(($memoryUsage / $memoryLimit) * 100, 2),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'uptime_seconds' => time() - $_SERVER['REQUEST_TIME_FLOAT'],
                'last_updated' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get performance metrics', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve performance metrics'];
        }
    }

    /**
     * Get security metrics
     */
    public function getSecurityMetrics(): array
    {
        try {
            $securityEventsToday = AuditLog::where('action', 'like', 'security.%')
                ->whereDate('created_at', today())
                ->count();
            
            $authFailuresToday = AuditLog::where('action', 'auth.login_failed')
                ->whereDate('created_at', today())
                ->count();
            
            $unauthorizedAccessToday = AuditLog::where('action', 'security.unauthorized_access')
                ->whereDate('created_at', today())
                ->count();

            return [
                'security_events_today' => $securityEventsToday,
                'auth_failures_today' => $authFailuresToday,
                'unauthorized_access_today' => $unauthorizedAccessToday,
                'security_score' => $this->calculateSecurityScore($securityEventsToday, $authFailuresToday, $unauthorizedAccessToday),
                'last_updated' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get security metrics', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to retrieve security metrics'];
        }
    }

    /**
     * Get database metrics
     */
    public function getDatabaseMetrics(): array
    {
        try {
            $connection = DB::connection();
            $config = $connection->getConfig();
            
            return [
                'driver' => $config['driver'] ?? 'unknown',
                'database' => $config['database'] ?? 'unknown',
                'host' => $config['host'] ?? 'unknown',
                'connection_status' => 'connected',
                'last_updated' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            return [
                'connection_status' => 'disconnected',
                'error' => $e->getMessage(),
                'last_updated' => now()->toISOString()
            ];
        }
    }

    /**
     * Get alerts based on metrics
     */
    public function getAlerts(): array
    {
        $alerts = [];
        $metrics = $this->getSystemMetrics();

        // Memory usage alert
        if (isset($metrics['performance_metrics']['memory_usage_percent'])) {
            $memoryPercent = $metrics['performance_metrics']['memory_usage_percent'];
            if ($memoryPercent > 90) {
                $alerts[] = [
                    'type' => 'critical',
                    'title' => 'High Memory Usage',
                    'message' => "Memory usage is at {$memoryPercent}%",
                    'timestamp' => now()->toISOString()
                ];
            } elseif ($memoryPercent > 75) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Moderate Memory Usage',
                    'message' => "Memory usage is at {$memoryPercent}%",
                    'timestamp' => now()->toISOString()
                ];
            }
        }

        // Security alerts
        if (isset($metrics['security_metrics']['auth_failures_today'])) {
            $authFailures = $metrics['security_metrics']['auth_failures_today'];
            if ($authFailures > 50) {
                $alerts[] = [
                    'type' => 'critical',
                    'title' => 'High Authentication Failures',
                    'message' => "{$authFailures} authentication failures today",
                    'timestamp' => now()->toISOString()
                ];
            } elseif ($authFailures > 20) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Elevated Authentication Failures',
                    'message' => "{$authFailures} authentication failures today",
                    'timestamp' => now()->toISOString()
                ];
            }
        }

        // Charging point availability alert
        if (isset($metrics['charging_point_metrics']['availability_rate'])) {
            $availabilityRate = $metrics['charging_point_metrics']['availability_rate'];
            if ($availabilityRate < 50) {
                $alerts[] = [
                    'type' => 'critical',
                    'title' => 'Low Charging Point Availability',
                    'message' => "Only {$availabilityRate}% of charging points are available",
                    'timestamp' => now()->toISOString()
                ];
            } elseif ($availabilityRate < 75) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Reduced Charging Point Availability',
                    'message' => "{$availabilityRate}% of charging points are available",
                    'timestamp' => now()->toISOString()
                ];
            }
        }

        return $alerts;
    }

    /**
     * Get historical metrics for charts
     */
    public function getHistoricalMetrics(string $metric, int $days = 7): array
    {
        $cacheKey = $this->getCacheKey("historical_{$metric}_{$days}");
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($metric, $days) {
            $data = [];
            $startDate = now()->subDays($days);
            
            for ($i = 0; $i < $days; $i++) {
                $date = $startDate->copy()->addDays($i);
                
                switch ($metric) {
                    case 'users':
                        $data[] = [
                            'date' => $date->toDateString(),
                            'value' => User::whereDate('created_at', $date)->count()
                        ];
                        break;
                    case 'transactions':
                        $data[] = [
                            'date' => $date->toDateString(),
                            'value' => Transaction::whereDate('created_at', $date)->count()
                        ];
                        break;
                    case 'revenue':
                        $data[] = [
                            'date' => $date->toDateString(),
                            'value' => Transaction::where('status', 'completed')
                                ->whereDate('created_at', $date)
                                ->sum('amount')
                        ];
                        break;
                }
            }
            
            return $data;
        });
    }

    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate(int $recent, int $total): float
    {
        if ($total == 0) return 0;
        return round(($recent / $total) * 100, 2);
    }

    /**
     * Calculate security score
     */
    private function calculateSecurityScore(int $securityEvents, int $authFailures, int $unauthorizedAccess): int
    {
        $score = 100;
        $score -= min($securityEvents * 2, 20);
        $score -= min($authFailures, 30);
        $score -= min($unauthorizedAccess * 10, 40);
        
        return max($score, 0);
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Get cache key
     */
    private function getCacheKey(string $key): string
    {
        return self::METRICS_PREFIX . ':' . $key;
    }

    /**
     * Clear monitoring cache
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey('system_metrics'));
        Log::info('Monitoring cache cleared');
    }
}
