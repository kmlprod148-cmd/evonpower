<?php

namespace App\Services;

use App\Models\User;
use App\Models\CreditRecharge;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Service de statistiques clients
 * 
 * Fournit des statistiques détaillées sur les clients :
 * - Statistiques de recharges
 * - Statistiques de réservations
 * - Statistiques financières
 * - Activité des clients
 */
class ClientStatisticsService
{
    protected array $cacheConfig = [
        'client_stats' => 300,      // 5 minutes
        'recharge_stats' => 300,    // 5 minutes
        'reservation_stats' => 300, // 5 minutes
    ];

    /**
     * Obtient les statistiques globales des clients
     * 
     * @param array $filters
     * @return array
     */
    public function getGlobalClientStatistics(array $filters = []): array
    {
        $cacheKey = 'global_client_stats:' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, $this->cacheConfig['client_stats'], function () use ($filters) {
            return $this->computeGlobalClientStatistics($filters);
        });
    }

    /**
     * Calcule les statistiques globales des clients
     * 
     * @param array $filters
     * @return array
     */
    private function computeGlobalClientStatistics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(30);
        $dateTo = $filters['date_to'] ?? Carbon::now();

        // Clients actifs (avec réservations)
        $activeClients = User::whereHas('reservations', function ($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        })->count();

        // Total clients
        $totalClients = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['client', 'user']);
        })->count();

        // Clients avec recharges
        $clientsWithRecharges = User::whereHas('reservations')
            ->whereHas('wallet.transactions', function ($query) use ($dateFrom, $dateTo) {
                $query->where('type', 'credit')
                    ->whereBetween('created_at', [$dateFrom, $dateTo]);
            })->count();

        return [
            'overview' => [
                'total_clients' => $totalClients,
                'active_clients' => $activeClients,
                'clients_with_recharges' => $clientsWithRecharges,
                'inactive_clients' => $totalClients - $activeClients,
            ],
            'recharges' => $this->getRechargeStatistics($filters),
            'reservations' => $this->getReservationStatistics($filters),
            'financial' => $this->getFinancialStatistics($filters),
            'activity' => $this->getActivityStatistics($filters),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Obtient les statistiques de recharges
     * 
     * @param array $filters
     * @return array
     */
    public function getRechargeStatistics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(30);
        $dateTo = $filters['date_to'] ?? Carbon::now();

        $query = CreditRecharge::whereBetween('created_at', [$dateFrom, $dateTo]);

        $total = $query->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $failed = (clone $query)->where('status', 'failed')->count();

        $totalAmount = (clone $query)->where('status', 'completed')->sum('amount');
        $pendingAmount = (clone $query)->where('status', 'pending')->sum('amount');

        // Par méthode de paiement
        $byPaymentMethod = (clone $query)
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->payment_method => [
                    'count' => $item->count,
                    'total' => (float) $item->total
                ]];
            })
            ->toArray();

        // Par statut
        $byStatus = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            })
            ->toArray();

        // Évolution sur les 30 derniers jours
        $dailyEvolution = (clone $query)
            ->where('status', 'completed')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                    'total' => (float) $item->total
                ];
            })
            ->toArray();

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'failed' => $failed,
            'total_amount' => (float) $totalAmount,
            'pending_amount' => (float) $pendingAmount,
            'average_amount' => $completed > 0 ? (float) $totalAmount / $completed : 0,
            'by_payment_method' => $byPaymentMethod,
            'by_status' => $byStatus,
            'daily_evolution' => $dailyEvolution,
        ];
    }

    /**
     * Obtient les statistiques de réservations
     * 
     * @param array $filters
     * @return array
     */
    public function getReservationStatistics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(30);
        $dateTo = $filters['date_to'] ?? Carbon::now();

        $query = Reservation::whereBetween('created_at', [$dateFrom, $dateTo]);

        $total = $query->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $cancelled = (clone $query)->where('status', 'cancelled')->count();

        $totalEnergy = (clone $query)->whereNotNull('actual_energy')->sum('actual_energy');
        $totalRevenue = (clone $query)->whereNotNull('actual_cost')->sum('actual_cost');

        // Top clients par nombre de réservations
        $topClientsByReservations = (clone $query)
            ->select('user_id', DB::raw('count(*) as count'))
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('user:id,name,email')
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->user_id,
                    'user_name' => $item->user->name ?? 'N/A',
                    'user_email' => $item->user->email ?? 'N/A',
                    'reservations_count' => $item->count
                ];
            })
            ->toArray();

        return [
            'total' => $total,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'total_energy_kwh' => (float) $totalEnergy,
            'total_revenue' => (float) $totalRevenue,
            'average_energy_per_reservation' => $completed > 0 ? (float) $totalEnergy / $completed : 0,
            'average_revenue_per_reservation' => $completed > 0 ? (float) $totalRevenue / $completed : 0,
            'top_clients' => $topClientsByReservations,
        ];
    }

    /**
     * Obtient les statistiques financières
     * 
     * @param array $filters
     * @return array
     */
    public function getFinancialStatistics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(30);
        $dateTo = $filters['date_to'] ?? Carbon::now();

        // Total crédité via recharges
        $totalCredited = CreditRecharge::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('amount');

        // Total dépensé via réservations
        $totalSpent = Reservation::where('status', 'completed')
            ->whereNotNull('actual_cost')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('actual_cost');

        // Solde total des wallets
        $totalWalletBalance = Wallet::sum('balance');

        // Clients avec solde positif
        $clientsWithBalance = Wallet::where('balance', '>', 0)->count();

        return [
            'total_credited' => (float) $totalCredited,
            'total_spent' => (float) $totalSpent,
            'net_flow' => (float) $totalCredited - $totalSpent,
            'total_wallet_balance' => (float) $totalWalletBalance,
            'clients_with_balance' => $clientsWithBalance,
            'average_wallet_balance' => $clientsWithBalance > 0 ? (float) $totalWalletBalance / $clientsWithBalance : 0,
        ];
    }

    /**
     * Obtient les statistiques d'activité
     * 
     * @param array $filters
     * @return array
     */
    public function getActivityStatistics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(30);
        $dateTo = $filters['date_to'] ?? Carbon::now();

        // Nouveaux clients
        $newClients = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['client', 'user']);
        })
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->count();

        // Clients actifs aujourd'hui
        $activeToday = User::whereHas('reservations', function ($query) {
            $query->whereDate('created_at', Carbon::today());
        })->count();

        // Clients actifs cette semaine
        $activeThisWeek = User::whereHas('reservations', function ($query) {
            $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        })->count();

        // Clients actifs ce mois
        $activeThisMonth = User::whereHas('reservations', function ($query) {
            $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        })->count();

        return [
            'new_clients' => $newClients,
            'active_today' => $activeToday,
            'active_this_week' => $activeThisWeek,
            'active_this_month' => $activeThisMonth,
        ];
    }

    /**
     * Obtient les statistiques d'un client spécifique
     * 
     * @param User $client
     * @return array
     */
    public function getClientStatistics(User $client): array
    {
        $cacheKey = "client_stats:{$client->id}";
        
        return Cache::remember($cacheKey, $this->cacheConfig['client_stats'], function () use ($client) {
            return [
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'created_at' => $client->created_at,
                ],
                'recharges' => [
                    'total' => $client->wallet ? CreditRecharge::where('user_id', $client->id)->count() : 0,
                    'total_amount' => (float) ($client->wallet ? CreditRecharge::where('user_id', $client->id)
                        ->where('status', 'completed')
                        ->sum('amount') : 0),
                    'pending' => CreditRecharge::where('user_id', $client->id)
                        ->where('status', 'pending')
                        ->count(),
                ],
                'reservations' => [
                    'total' => $client->reservations()->count(),
                    'completed' => $client->reservations()->where('status', 'completed')->count(),
                    'total_energy' => (float) $client->reservations()
                        ->where('status', 'completed')
                        ->whereNotNull('actual_energy')
                        ->sum('actual_energy'),
                    'total_spent' => (float) $client->reservations()
                        ->where('status', 'completed')
                        ->whereNotNull('actual_cost')
                        ->sum('actual_cost'),
                ],
                'wallet' => [
                    'balance' => (float) ($client->wallet ? $client->wallet->balance : 0),
                    'currency' => $client->wallet ? $client->wallet->currency : 'EUR',
                ],
            ];
        });
    }
}

