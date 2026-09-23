<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de dashboard et statistiques
 * 
 * Ce service fournit :
 * - Statistiques globales
 * - Métriques par rôle
 * - Graphiques de performance
 * - Rapports en temps réel
 */
class DashboardService
{
    protected array $cacheConfig = [
        'global_stats' => 300,      // 5 minutes
        'user_stats' => 600,        // 10 minutes
        'performance_stats' => 900, // 15 minutes
        'realtime_stats' => 60,     // 1 minute
    ];

    /**
     * Obtient les statistiques globales du dashboard
     * 
     * @param User $user
     * @return array
     */
    public function getGlobalStatistics(User $user): array
    {
        $cacheKey = "global_stats:user_{$user->id}";
        
        return Cache::tags(['dashboard', 'global_stats', "user_{$user->id}"])
            ->remember($cacheKey, $this->cacheConfig['global_stats'], function () use ($user) {
                return $this->computeGlobalStatistics($user);
            });
    }

    /**
     * Calcule les statistiques globales
     * 
     * @param User $user
     * @return array
     */
    private function computeGlobalStatistics(User $user): array
    {
        $stats = [
            'overview' => $this->getOverviewStats($user),
            'transactions' => $this->getTransactionStats($user),
            'users' => $this->getUserStats($user),
            'charging_points' => $this->getChargingPointStats($user),
            'revenue' => $this->getRevenueStats($user),
            'performance' => $this->getPerformanceStats($user),
            'generated_at' => now()->toISOString()
        ];

        return $stats;
    }

    /**
     * Obtient les statistiques d'aperçu
     * 
     * @param User $user
     * @return array
     */
    private function getOverviewStats(User $user): array
    {
        $baseQuery = $this->getBaseQuery($user);

        return [
            'total_transactions' => $baseQuery->count(),
            'total_revenue' => $baseQuery->sum('price_total'),
            'active_users' => $this->getActiveUsersCount($user),
            'active_charging_points' => $this->getActiveChargingPointsCount($user),
            'pending_reservations' => $this->getPendingReservationsCount($user),
            'today_transactions' => $this->getTodayTransactionsCount($user),
            'today_revenue' => $this->getTodayRevenue($user)
        ];
    }

    /**
     * Obtient les statistiques de transaction
     * 
     * @param User $user
     * @return array
     */
    private function getTransactionStats(User $user): array
    {
        $baseQuery = $this->getBaseQuery($user);

        return [
            'total_count' => $baseQuery->count(),
            'paid_count' => $baseQuery->where('payment_status', 'paid')->count(),
            'pending_count' => $baseQuery->where('payment_status', 'pending')->count(),
            'failed_count' => $baseQuery->where('payment_status', 'failed')->count(),
            'total_amount' => $baseQuery->sum('price_total'),
            'paid_amount' => $baseQuery->where('payment_status', 'paid')->sum('price_total'),
            'average_amount' => $baseQuery->avg('price_total') ?? 0,
            'last_7_days' => $this->getLast7DaysTransactions($user),
            'last_30_days' => $this->getLast30DaysTransactions($user)
        ];
    }

    /**
     * Obtient les statistiques utilisateur
     * 
     * @param User $user
     * @return array
     */
    private function getUserStats(User $user): array
    {
        $userQuery = User::query();
        
        // Appliquer les filtres selon le rôle
        if ($user->hasRole('integrator')) {
            $userQuery->where('integrator_id', $user->integrator_id);
        } elseif ($user->hasRole('operator')) {
            $userQuery->where('id', $user->id);
        }

        return [
            'total_users' => $userQuery->count(),
            'active_users' => $userQuery->where('is_active', true)->count(),
            'inactive_users' => $userQuery->where('is_active', false)->count(),
            'admin_count' => User::whereHas('roles', function ($q) {
                $q->where('name', 'admin');
            })->count(),
            'integrator_count' => User::whereHas('roles', function ($q) {
                $q->where('name', 'integrator');
            })->count(),
            'operator_count' => User::whereHas('roles', function ($q) {
                $q->where('name', 'operator');
            })->count(),
            'new_users_this_month' => $userQuery->where('created_at', '>=', now()->startOfMonth())->count()
        ];
    }

    /**
     * Obtient les statistiques des bornes de charge
     * 
     * @param User $user
     * @return array
     */
    private function getChargingPointStats(User $user): array
    {
        $chargingPointQuery = ChargingPoint::query();
        
        // Appliquer les filtres selon le rôle
        if ($user->hasRole('integrator')) {
            $chargingPointQuery->where('integrator_id', $user->integrator_id);
        } elseif ($user->hasRole('operator')) {
            $chargingPointQuery->where('user_id', $user->id);
        }

        return [
            'total_points' => $chargingPointQuery->count(),
            'online_points' => $chargingPointQuery->where('status', 'online')->count(),
            'offline_points' => $chargingPointQuery->where('status', 'offline')->count(),
            'maintenance_points' => $chargingPointQuery->where('status', 'maintenance')->count(),
            'error_points' => $chargingPointQuery->where('status', 'error')->count(),
            'new_points_this_month' => $chargingPointQuery->where('created_at', '>=', now()->startOfMonth())->count()
        ];
    }

    /**
     * Obtient les statistiques de revenus
     * 
     * @param User $user
     * @return array
     */
    private function getRevenueStats(User $user): array
    {
        $baseQuery = $this->getBaseQuery($user);

        return [
            'total_revenue' => $baseQuery->sum('price_total'),
            'monthly_revenue' => $baseQuery->where('created_at', '>=', now()->startOfMonth())->sum('price_total'),
            'weekly_revenue' => $baseQuery->where('created_at', '>=', now()->startOfWeek())->sum('price_total'),
            'daily_revenue' => $baseQuery->where('created_at', '>=', now()->startOfDay())->sum('price_total'),
            'average_transaction' => $baseQuery->avg('price_total') ?? 0,
            'revenue_by_payment_method' => $this->getRevenueByPaymentMethod($user),
            'revenue_trend' => $this->getRevenueTrend($user)
        ];
    }

    /**
     * Obtient les statistiques de performance
     * 
     * @param User $user
     * @return array
     */
    private function getPerformanceStats(User $user): array
    {
        return [
            'commission_stats' => $this->getCommissionStats($user),
            'efficiency_metrics' => $this->getEfficiencyMetrics($user),
            'growth_metrics' => $this->getGrowthMetrics($user),
            'top_performers' => $this->getTopPerformers($user)
        ];
    }

    /**
     * Obtient les statistiques de commission
     * 
     * @param User $user
     * @return array
     */
    private function getCommissionStats(User $user): array
    {
        $baseQuery = $this->getBaseQuery($user);

        return [
            'total_commissions' => $baseQuery->selectRaw('SUM(admin_commission + integrator_commission + partner_commission) as total')
                ->value('total') ?? 0,
            'admin_commissions' => $baseQuery->sum('admin_commission'),
            'integrator_commissions' => $baseQuery->sum('integrator_commission'),
            'partner_commissions' => $baseQuery->sum('partner_commission'),
            'paid_commissions' => $baseQuery->where('admin_commission_paid', true)
                ->where('integrator_commission_paid', true)
                ->where('partner_commission_paid', true)
                ->selectRaw('SUM(admin_commission + integrator_commission + partner_commission) as total')
                ->value('total') ?? 0,
            'pending_commissions' => $baseQuery->where(function ($q) {
                $q->where('admin_commission_paid', false)
                  ->orWhere('integrator_commission_paid', false)
                  ->orWhere('partner_commission_paid', false);
            })->selectRaw('SUM(admin_commission + integrator_commission + partner_commission) as total')
            ->value('total') ?? 0
        ];
    }

    /**
     * Obtient les métriques d'efficacité
     * 
     * @param User $user
     * @return array
     */
    private function getEfficiencyMetrics(User $user): array
    {
        $baseQuery = $this->getBaseQuery($user);
        $totalTransactions = $baseQuery->count();
        $paidTransactions = $baseQuery->where('payment_status', 'paid')->count();

        return [
            'payment_success_rate' => $totalTransactions > 0 ? round(($paidTransactions / $totalTransactions) * 100, 2) : 0,
            'average_processing_time' => $this->getAverageProcessingTime($user),
            'system_uptime' => $this->getSystemUptime(),
            'error_rate' => $this->getErrorRate($user)
        ];
    }

    /**
     * Obtient les métriques de croissance
     * 
     * @param User $user
     * @return array
     */
    private function getGrowthMetrics(User $user): array
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $currentRevenue = $this->getBaseQuery($user)
            ->where('created_at', '>=', $currentMonth)
            ->sum('price_total');

        $lastRevenue = $this->getBaseQuery($user)
            ->where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $currentMonth)
            ->sum('price_total');

        $revenueGrowth = $lastRevenue > 0 ? round((($currentRevenue - $lastRevenue) / $lastRevenue) * 100, 2) : 0;

        return [
            'revenue_growth' => $revenueGrowth,
            'transaction_growth' => $this->getTransactionGrowth($user),
            'user_growth' => $this->getUserGrowth($user),
            'charging_point_growth' => $this->getChargingPointGrowth($user)
        ];
    }

    /**
     * Obtient les top performers
     * 
     * @param User $user
     * @return array
     */
    private function getTopPerformers(User $user): array
    {
        $baseQuery = $this->getBaseQuery($user);

        return [
            'top_charging_points' => $baseQuery->join('charging_points', 'transactions.charging_point_id', '=', 'charging_points.id')
                ->select('charging_points.name', 'charging_points.id', DB::raw('SUM(transactions.price_total) as revenue'), DB::raw('COUNT(*) as transaction_count'))
                ->groupBy('charging_points.id', 'charging_points.name')
                ->orderBy('revenue', 'desc')
                ->limit(5)
                ->get()
                ->toArray(),
            'top_users' => $baseQuery->join('users', 'transactions.user_id', '=', 'users.id')
                ->select('users.name', 'users.id', DB::raw('SUM(transactions.price_total) as revenue'), DB::raw('COUNT(*) as transaction_count'))
                ->groupBy('users.id', 'users.name')
                ->orderBy('revenue', 'desc')
                ->limit(5)
                ->get()
                ->toArray()
        ];
    }

    /**
     * Obtient les statistiques en temps réel
     * 
     * @param User $user
     * @return array
     */
    public function getRealtimeStatistics(User $user): array
    {
        $cacheKey = "realtime_stats:user_{$user->id}";
        
        return Cache::tags(['dashboard', 'realtime_stats', "user_{$user->id}"])
            ->remember($cacheKey, $this->cacheConfig['realtime_stats'], function () use ($user) {
                return [
                    'active_sessions' => $this->getActiveSessionsCount(),
                    'current_transactions' => $this->getCurrentTransactionsCount($user),
                    'system_status' => $this->getSystemStatus(),
                    'last_updated' => now()->toISOString()
                ];
            });
    }

    /**
     * Obtient la requête de base selon le rôle de l'utilisateur
     * 
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getBaseQuery(User $user)
    {
        $query = Transaction::query();

        if ($user->hasRole('integrator')) {
            $query->whereHas('chargingPoint', function ($q) use ($user) {
                $q->where('integrator_id', $user->integrator_id);
            });
        } elseif ($user->hasRole('operator')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    /**
     * Obtient le nombre d'utilisateurs actifs
     * 
     * @param User $user
     * @return int
     */
    private function getActiveUsersCount(User $user): int
    {
        $query = User::where('is_active', true);
        
        if ($user->hasRole('integrator')) {
            $query->where('integrator_id', $user->integrator_id);
        } elseif ($user->hasRole('operator')) {
            $query->where('id', $user->id);
        }

        return $query->count();
    }

    /**
     * Obtient le nombre de bornes actives
     * 
     * @param User $user
     * @return int
     */
    private function getActiveChargingPointsCount(User $user): int
    {
        $query = ChargingPoint::where('status', 'online');
        
        if ($user->hasRole('integrator')) {
            $query->where('integrator_id', $user->integrator_id);
        } elseif ($user->hasRole('operator')) {
            $query->where('user_id', $user->id);
        }

        return $query->count();
    }

    /**
     * Obtient le nombre de réservations en attente
     * 
     * @param User $user
     * @return int
     */
    private function getPendingReservationsCount(User $user): int
    {
        $query = Reservation::where('status', 'pending');
        
        if ($user->hasRole('integrator')) {
            $query->whereHas('chargingPoint', function ($q) use ($user) {
                $q->where('integrator_id', $user->integrator_id);
            });
        } elseif ($user->hasRole('operator')) {
            $query->whereHas('chargingPoint', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query->count();
    }

    /**
     * Obtient le nombre de transactions d'aujourd'hui
     * 
     * @param User $user
     * @return int
     */
    private function getTodayTransactionsCount(User $user): int
    {
        return $this->getBaseQuery($user)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    /**
     * Obtient le revenu d'aujourd'hui
     * 
     * @param User $user
     * @return float
     */
    private function getTodayRevenue(User $user): float
    {
        return $this->getBaseQuery($user)
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('price_total') ?? 0;
    }

    /**
     * Obtient les transactions des 7 derniers jours
     * 
     * @param User $user
     * @return array
     */
    private function getLast7DaysTransactions(User $user): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = $this->getBaseQuery($user)
                ->where('created_at', '>=', $date)
                ->where('created_at', '<', $date->copy()->endOfDay())
                ->count();
            
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'transactions' => $count
            ];
        }
        
        return $data;
    }

    /**
     * Obtient les transactions des 30 derniers jours
     * 
     * @param User $user
     * @return array
     */
    private function getLast30DaysTransactions(User $user): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = $this->getBaseQuery($user)
                ->where('created_at', '>=', $date)
                ->where('created_at', '<', $date->copy()->endOfDay())
                ->count();
            
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'transactions' => $count
            ];
        }
        
        return $data;
    }

    /**
     * Obtient les revenus par méthode de paiement
     * 
     * @param User $user
     * @return array
     */
    private function getRevenueByPaymentMethod(User $user): array
    {
        return $this->getBaseQuery($user)
            ->where('payment_status', 'paid')
            ->select('payment_method', DB::raw('SUM(price_total) as revenue'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->toArray();
    }

    /**
     * Obtient la tendance des revenus
     * 
     * @param User $user
     * @return array
     */
    private function getRevenueTrend(User $user): array
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $revenue = $this->getBaseQuery($user)
                ->where('created_at', '>=', $date)
                ->where('created_at', '<', $date->copy()->endOfMonth())
                ->sum('price_total');
            
            $data[] = [
                'month' => $date->format('Y-m'),
                'revenue' => $revenue ?? 0
            ];
        }
        
        return $data;
    }

    /**
     * Obtient le temps de traitement moyen
     * 
     * @param User $user
     * @return float
     */
    private function getAverageProcessingTime(User $user): float
    {
        // Logique pour calculer le temps de traitement moyen
        // Pour l'instant, retourner une valeur simulée
        return 2.5; // minutes
    }

    /**
     * Obtient l'uptime du système
     * 
     * @return float
     */
    private function getSystemUptime(): float
    {
        // Logique pour calculer l'uptime
        // Pour l'instant, retourner une valeur simulée
        return 99.9; // pourcentage
    }

    /**
     * Obtient le taux d'erreur
     * 
     * @param User $user
     * @return float
     */
    private function getErrorRate(User $user): float
    {
        $totalTransactions = $this->getBaseQuery($user)->count();
        $failedTransactions = $this->getBaseQuery($user)->where('payment_status', 'failed')->count();
        
        return $totalTransactions > 0 ? round(($failedTransactions / $totalTransactions) * 100, 2) : 0;
    }

    /**
     * Obtient la croissance des transactions
     * 
     * @param User $user
     * @return float
     */
    private function getTransactionGrowth(User $user): float
    {
        $currentMonth = $this->getBaseQuery($user)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $lastMonth = $this->getBaseQuery($user)
            ->where('created_at', '>=', now()->subMonth()->startOfMonth())
            ->where('created_at', '<', now()->startOfMonth())
            ->count();

        return $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100, 2) : 0;
    }

    /**
     * Obtient la croissance des utilisateurs
     * 
     * @param User $user
     * @return float
     */
    private function getUserGrowth(User $user): float
    {
        $currentMonth = User::where('created_at', '>=', now()->startOfMonth())->count();
        $lastMonth = User::where('created_at', '>=', now()->subMonth()->startOfMonth())
            ->where('created_at', '<', now()->startOfMonth())
            ->count();

        return $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100, 2) : 0;
    }

    /**
     * Obtient la croissance des bornes de charge
     * 
     * @param User $user
     * @return float
     */
    private function getChargingPointGrowth(User $user): float
    {
        $query = ChargingPoint::query();
        
        if ($user->hasRole('integrator')) {
            $query->where('integrator_id', $user->integrator_id);
        } elseif ($user->hasRole('operator')) {
            $query->where('user_id', $user->id);
        }

        $currentMonth = $query->where('created_at', '>=', now()->startOfMonth())->count();
        $lastMonth = $query->where('created_at', '>=', now()->subMonth()->startOfMonth())
            ->where('created_at', '<', now()->startOfMonth())
            ->count();

        return $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100, 2) : 0;
    }

    /**
     * Obtient le nombre de sessions actives
     * 
     * @return int
     */
    private function getActiveSessionsCount(): int
    {
        // Logique pour compter les sessions actives
        // Pour l'instant, retourner une valeur simulée
        return 15;
    }

    /**
     * Obtient le nombre de transactions en cours
     * 
     * @param User $user
     * @return int
     */
    private function getCurrentTransactionsCount(User $user): int
    {
        return $this->getBaseQuery($user)
            ->where('status', 'in_progress')
            ->count();
    }

    /**
     * Obtient le statut du système
     * 
     * @return array
     */
    private function getSystemStatus(): array
    {
        return [
            'database' => 'online',
            'cache' => 'online',
            'queue' => 'online',
            'storage' => 'online',
            'last_check' => now()->toISOString()
        ];
    }
}
