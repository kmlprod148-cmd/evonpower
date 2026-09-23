<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Group;
use App\Models\Station;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ReportingService
{
    /**
     * Obtenir les statistiques en temps réel
     */
    public function getRealTimeStatistics(): array
    {
        $cacheKey = 'realtime_stats';
        $cacheDuration = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheDuration, function () {
            $now = Carbon::now();
            $today = Carbon::today();

            return [
                'charging_points' => [
                    'total' => ChargingPoint::count(),
                    'online' => ChargingPoint::where('status', 'online')->count(),
                    'charging' => ChargingPoint::where('status', 'charging')->count(),
                    'offline' => ChargingPoint::where('status', 'offline')->count(),
                    'maintenance' => ChargingPoint::where('status', 'maintenance')->count(),
                ],
                'sessions' => [
                    'active' => Transaction::where('status', 'charging')->count(),
                    'completed_today' => Transaction::where('status', 'completed')
                        ->whereDate('created_at', $today)
                        ->count(),
                    'total_today' => Transaction::whereDate('created_at', $today)->count(),
                ],
                'energy' => [
                    'delivered_today' => Transaction::where('status', 'completed')
                        ->whereDate('created_at', $today)
                        ->sum('energy_delivered'),
                    'total_delivered' => Transaction::where('status', 'completed')
                        ->sum('energy_delivered'),
                ],
                'revenue' => [
                    'today' => Transaction::where('status', 'completed')
                        ->whereDate('created_at', $today)
                        ->sum('amount'),
                    'this_month' => Transaction::where('status', 'completed')
                        ->whereMonth('created_at', $now->month)
                        ->whereYear('created_at', $now->year)
                        ->sum('amount'),
                    'total' => Transaction::where('status', 'completed')->sum('amount'),
                ],
                'users' => [
                    'total' => User::count(),
                    'active_today' => User::whereHas('transactions', function ($query) use ($today) {
                        $query->whereDate('created_at', $today);
                    })->count(),
                ],
            ];
        });
    }

    /**
     * Obtenir les statistiques par période
     */
    public function getPeriodStatistics(string $period = 'month', Carbon $startDate = null, Carbon $endDate = null): array
    {
        if (!$startDate) {
            $startDate = Carbon::now()->startOfMonth();
        }
        if (!$endDate) {
            $endDate = Carbon::now()->endOfMonth();
        }

        $cacheKey = "period_stats_{$period}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        $cacheDuration = 3600; // 1 heure

        return Cache::remember($cacheKey, $cacheDuration, function () use ($startDate, $endDate) {
            $transactions = Transaction::whereBetween('created_at', [$startDate, $endDate]);

            return [
                'transactions' => [
                    'total' => $transactions->count(),
                    'completed' => $transactions->where('status', 'completed')->count(),
                    'cancelled' => $transactions->where('status', 'cancelled')->count(),
                    'failed' => $transactions->where('status', 'failed')->count(),
                ],
                'energy' => [
                    'total_delivered' => $transactions->where('status', 'completed')->sum('energy_delivered'),
                    'average_per_session' => $transactions->where('status', 'completed')->avg('energy_delivered'),
                ],
                'revenue' => [
                    'total' => $transactions->where('status', 'completed')->sum('amount'),
                    'average_per_session' => $transactions->where('status', 'completed')->avg('amount'),
                    'by_payment_method' => $transactions->where('status', 'completed')
                        ->select('payment_method', DB::raw('SUM(amount) as total'))
                        ->groupBy('payment_method')
                        ->get(),
                ],
                'duration' => [
                    'total_minutes' => $transactions->where('status', 'completed')->sum('duration_minutes'),
                    'average_minutes' => $transactions->where('status', 'completed')->avg('duration_minutes'),
                ],
            ];
        });
    }

    /**
     * Obtenir les top 10 des bornes
     */
    public function getTopChargingPoints(string $metric = 'revenue', int $limit = 10, array $filters = []): array
    {
        $query = ChargingPoint::query();

        // Appliquer les filtres
        if (isset($filters['integrator_id'])) {
            $query->where('integrator_id', $filters['integrator_id']);
        }
        if (isset($filters['partner_id'])) {
            $query->where('partner_id', $filters['partner_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        switch ($metric) {
            case 'revenue':
                $query->withSum(['transactions as total_revenue' => function ($q) {
                    $q->where('status', 'completed');
                }], 'amount')
                ->orderByDesc('total_revenue');
                break;

            case 'sessions':
                $query->withCount(['transactions as total_sessions' => function ($q) {
                    $q->where('status', 'completed');
                }])
                ->orderByDesc('total_sessions');
                break;

            case 'energy':
                $query->withSum(['transactions as total_energy' => function ($q) {
                    $q->where('status', 'completed');
                }], 'energy_delivered')
                ->orderByDesc('total_energy');
                break;

            case 'uptime':
                $query->withCount(['activeChargingPointsHistory as uptime_count' => function ($q) {
                    $q->where('status', 'online');
                }])
                ->orderByDesc('uptime_count');
                break;
        }

        return $query->limit($limit)->get()->toArray();
    }

    /**
     * Obtenir les top 10 des clients
     */
    public function getTopUsers(string $metric = 'revenue', int $limit = 10, array $filters = []): array
    {
        $query = User::query();

        // Appliquer les filtres
        if (isset($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        switch ($metric) {
            case 'revenue':
                $query->withSum(['transactions as total_revenue' => function ($q) {
                    $q->where('status', 'completed');
                }], 'amount')
                ->orderByDesc('total_revenue');
                break;

            case 'sessions':
                $query->withCount(['transactions as total_sessions' => function ($q) {
                    $q->where('status', 'completed');
                }])
                ->orderByDesc('total_sessions');
                break;

            case 'energy':
                $query->withSum(['transactions as total_energy' => function ($q) {
                    $q->where('status', 'completed');
                }], 'energy_delivered')
                ->orderByDesc('total_energy');
                break;
        }

        return $query->limit($limit)->get()->toArray();
    }

    /**
     * Obtenir les statistiques par groupe
     */
    public function getGroupStatistics(int $groupId = null, array $filters = []): array
    {
        $query = Group::query();

        if ($groupId) {
            $query->where('id', $groupId);
        }

        // Appliquer les filtres
        if (isset($filters['integrator_id'])) {
            $query->where('integrator_id', $filters['integrator_id']);
        }
        if (isset($filters['partner_id'])) {
            $query->where('partner_id', $filters['partner_id']);
        }

        $groups = $query->with(['chargingPoints.transactions' => function ($q) {
            $q->where('status', 'completed');
        }])->get();

        $statistics = [];
        foreach ($groups as $group) {
            $totalRevenue = $group->chargingPoints->sum(function ($cp) {
                return $cp->transactions->sum('amount');
            });

            $totalSessions = $group->chargingPoints->sum(function ($cp) {
                return $cp->transactions->count();
            });

            $totalEnergy = $group->chargingPoints->sum(function ($cp) {
                return $cp->transactions->sum('energy_delivered');
            });

            $statistics[] = [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'charging_points_count' => $group->chargingPoints->count(),
                'total_revenue' => $totalRevenue,
                'total_sessions' => $totalSessions,
                'total_energy' => $totalEnergy,
                'average_revenue_per_cp' => $group->chargingPoints->count() > 0 ? $totalRevenue / $group->chargingPoints->count() : 0,
            ];
        }

        return $statistics;
    }

    /**
     * Obtenir les statistiques par station
     */
    public function getStationStatistics(int $stationId = null, array $filters = []): array
    {
        $query = Station::query();

        if ($stationId) {
            $query->where('id', $stationId);
        }

        // Appliquer les filtres
        if (isset($filters['partner_id'])) {
            $query->where('partner_id', $filters['partner_id']);
        }

        $stations = $query->with(['chargingPoints.transactions' => function ($q) {
            $q->where('status', 'completed');
        }])->get();

        $statistics = [];
        foreach ($stations as $station) {
            $totalRevenue = $station->chargingPoints->sum(function ($cp) {
                return $cp->transactions->sum('amount');
            });

            $totalSessions = $station->chargingPoints->sum(function ($cp) {
                return $cp->transactions->count();
            });

            $statistics[] = [
                'station_id' => $station->id,
                'station_name' => $station->name,
                'charging_points_count' => $station->chargingPoints->count(),
                'total_revenue' => $totalRevenue,
                'total_sessions' => $totalSessions,
                'average_revenue_per_cp' => $station->chargingPoints->count() > 0 ? $totalRevenue / $station->chargingPoints->count() : 0,
            ];
        }

        return $statistics;
    }

    /**
     * Obtenir les tendances de croissance
     */
    public function getGrowthTrends(string $period = 'month', int $months = 12): array
    {
        $trends = [];
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $transactions = Transaction::whereBetween('created_at', [$monthStart, $monthEnd]);

            $trends[] = [
                'period' => $monthStart->format('Y-m'),
                'revenue' => $transactions->where('status', 'completed')->sum('amount'),
                'sessions' => $transactions->where('status', 'completed')->count(),
                'energy' => $transactions->where('status', 'completed')->sum('energy_delivered'),
                'new_users' => User::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            ];
        }

        return $trends;
    }

    /**
     * Obtenir les statistiques d'abonnements
     */
    public function getSubscriptionStatistics(array $filters = []): array
    {
        $query = DB::table('user_subscriptions')
            ->join('subscriptions', 'user_subscriptions.subscription_id', '=', 'subscriptions.id');

        // Appliquer les filtres
        if (isset($filters['date_from'])) {
            $query->where('user_subscriptions.created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('user_subscriptions.created_at', '<=', $filters['date_to']);
        }

        $stats = [
            'total_subscriptions' => $query->count(),
            'active_subscriptions' => $query->where('user_subscriptions.status', 'active')->count(),
            'expired_subscriptions' => $query->where('user_subscriptions.status', 'expired')->count(),
            'cancelled_subscriptions' => $query->where('user_subscriptions.status', 'cancelled')->count(),
            'revenue' => $query->sum('subscriptions.price'),
            'by_type' => $query->select('subscriptions.type', DB::raw('COUNT(*) as count'))
                ->groupBy('subscriptions.type')
                ->get(),
        ];

        return $stats;
    }

    /**
     * Générer un rapport complet
     */
    public function generateCompleteReport(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->startOfMonth();
        $endDate = $filters['end_date'] ?? Carbon::now()->endOfMonth();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'real_time' => $this->getRealTimeStatistics(),
            'period_stats' => $this->getPeriodStatistics('custom', $startDate, $endDate),
            'top_charging_points' => $this->getTopChargingPoints('revenue', 10, $filters),
            'top_users' => $this->getTopUsers('revenue', 10, $filters),
            'group_statistics' => $this->getGroupStatistics(null, $filters),
            'station_statistics' => $this->getStationStatistics(null, $filters),
            'growth_trends' => $this->getGrowthTrends('month', 6),
            'subscription_statistics' => $this->getSubscriptionStatistics($filters),
        ];
    }

    /**
     * Exporter les données en CSV
     */
    public function exportToCsv(array $data, string $filename = null): string
    {
        if (!$filename) {
            $filename = 'report_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
        }

        $handle = fopen('php://temp', 'r+');
        
        // Écrire les en-têtes
        if (!empty($data)) {
            fputcsv($handle, array_keys($data[0]));
            
            // Écrire les données
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Nettoyer le cache des rapports
     */
    public function clearReportCache(): void
    {
        Cache::forget('realtime_stats');
        
        // Nettoyer les caches de périodes
        $keys = Cache::get('report_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('report_cache_keys');
    }
}
