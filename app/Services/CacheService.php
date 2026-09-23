<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\ChargingPoint;
use App\Models\User;
use App\Models\Transaction;

class CacheService
{
    /**
     * Obtenir les statistiques des points de charge avec cache
     */
    public function getChargingPointsStats(): array
    {
        return Cache::remember('charging_points_stats', 300, function () {
            return [
                'total' => ChargingPoint::count(),
                'online' => ChargingPoint::where('status', 'online')->count(),
                'offline' => ChargingPoint::where('status', 'offline')->count(),
                'charging' => ChargingPoint::where('status', 'charging')->count(),
                'maintenance' => ChargingPoint::where('status', 'maintenance')->count(),
                'error' => ChargingPoint::where('status', 'error')->count(),
            ];
        });
    }

    /**
     * Obtenir les statistiques des utilisateurs avec cache
     */
    public function getUsersStats(): array
    {
        return Cache::remember('users_stats', 600, function () {
            return [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
                'admins' => User::role('admin')->count(),
                'integrators' => User::role('integrator')->count(),
                'operators' => User::role('operator')->count(),
            ];
        });
    }

    /**
     * Obtenir les statistiques des transactions avec cache
     */
    public function getTransactionsStats(): array
    {
        return Cache::remember('transactions_stats', 300, function () {
            $today = now()->startOfDay();
            $thisMonth = now()->startOfMonth();
            
            return [
                'today' => [
                    'total' => Transaction::where('created_at', '>=', $today)->count(),
                    'completed' => Transaction::where('created_at', '>=', $today)
                        ->where('status', 'completed')->count(),
                    'revenue' => Transaction::where('created_at', '>=', $today)
                        ->where('status', 'completed')
                        ->sum('amount') ?? 0,
                ],
                'this_month' => [
                    'total' => Transaction::where('created_at', '>=', $thisMonth)->count(),
                    'completed' => Transaction::where('created_at', '>=', $thisMonth)
                        ->where('status', 'completed')->count(),
                    'revenue' => Transaction::where('created_at', '>=', $thisMonth)
                        ->where('status', 'completed')
                        ->sum('amount') ?? 0,
                ],
                'all_time' => [
                    'total' => Transaction::count(),
                    'completed' => Transaction::where('status', 'completed')->count(),
                    'revenue' => Transaction::where('status', 'completed')->sum('amount') ?? 0,
                ]
            ];
        });
    }

    /**
     * Obtenir les points de charge les plus performants avec cache
     */
    public function getTopChargingPoints(int $limit = 10): array
    {
        return Cache::remember("top_charging_points_{$limit}", 600, function () use ($limit) {
            $results = DB::table('charging_points')
                ->select([
                    'charging_points.id as charging_point_id',
                    'charging_points.name',
                    'charging_points.integrator_id',
                    'charging_points.partner_id',
                    DB::raw('COUNT(transactions.id) as transaction_count'),
                    DB::raw('SUM(transactions.amount) as total_revenue')
                ])
                ->leftJoin('transactions', 'charging_points.id', '=', 'transactions.charging_point_id')
                ->where('transactions.status', 'completed')
                ->whereNull('charging_points.deleted_at')
                ->groupBy([
                    'charging_points.id',
                    'charging_points.name',
                    'charging_points.integrator_id',
                    'charging_points.partner_id'
                ])
                ->orderBy('total_revenue', 'desc')
                ->limit($limit)
                ->get();
            
            // Convertir la Collection en array
            return $results->toArray();
        });
    }

    /**
     * Nettoyer le cache des statistiques
     */
    public function clearStatsCache(): void
    {
        Cache::forget('charging_points_stats');
        Cache::forget('users_stats');
        Cache::forget('transactions_stats');
        
        // Nettoyer le cache des top charging points
        for ($i = 5; $i <= 20; $i += 5) {
            Cache::forget("top_charging_points_{$i}");
        }
    }

    /**
     * Obtenir les données du dashboard avec cache
     */
    public function getDashboardData(): array
    {
        return Cache::remember('dashboard_data', 300, function () {
            return [
                'charging_points' => $this->getChargingPointsStats(),
                'users' => $this->getUsersStats(),
                'transactions' => $this->getTransactionsStats(),
                'top_charging_points' => $this->getTopChargingPoints(5),
            ];
        });
    }
}
