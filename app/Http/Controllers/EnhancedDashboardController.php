<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class EnhancedDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche le dashboard amélioré avec données réelles
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Redirect simple clients (role 'user' only) to their reservations
        if ($user) {
            $userRoles = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
            $isClientOnly = count($userRoles) === 1 && in_array('user', $userRoles);
            
            if ($isClientOnly) {
                return redirect()->route('reservations.index');
            }
        }

        // Récupérer les données réelles
        $stats = $this->getRealTimeStats($user);
        $chargingPoints = $this->getActiveChargingPoints($user);
        $recentTransactions = $this->getRecentTransactions($user);
        $chartData = $this->getChartData($user);
        $topStations = $this->getTopPerformingStations($user);
        $revenueStats = $this->getRevenueStats($user);

        return view('dashboard-enhanced', compact(
            'stats',
            'chargingPoints',
            'recentTransactions',
            'chartData',
            'topStations',
            'revenueStats',
            'user'
        ));
    }

    /**
     * Récupère les statistiques en temps réel
     */
    private function getRealTimeStats($user)
    {
        $query = $this->applyUserRoleFilter(ChargingPoint::query(), $user);
        
        $totalPoints = $query->count();
        $onlinePoints = (clone $query)->where('status', 'online')->count();
        $maintenancePoints = (clone $query)->where('status', 'maintenance')->count();

        // Transactions en cours
        $transactionQuery = $this->applyUserRoleFilter(Transaction::query(), $user, 'charging_point_id');
        $activeTransactions = (clone $transactionQuery)
            ->where('status', 'in_progress')
            ->count();

        // Réservations actives
        $reservationQuery = $this->applyUserRoleFilter(Reservation::query(), $user, 'charging_point_id');
        $activeReservations = (clone $reservationQuery)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->count();

        // Transactions complétées aujourd'hui
        $todayTransactions = (clone $transactionQuery)
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'completed')
            ->count();

        // Revenus du jour
        $todayRevenue = (clone $transactionQuery)
            ->whereDate('processed_at', Carbon::today())
            ->where('status', 'completed')
            ->sum('amount');

        // Revenus du mois
        $monthRevenue = (clone $transactionQuery)
            ->whereMonth('processed_at', Carbon::now()->month)
            ->whereYear('processed_at', Carbon::now()->year)
            ->where('status', 'completed')
            ->sum('amount');

        // Énergie distribuée aujourd'hui (en kWh)
        $todayEnergy = (clone $transactionQuery)
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'completed')
            ->sum(DB::raw('COALESCE(energy_consumed_wh, 0) / 1000'));

        // Taux de disponibilité
        $availabilityRate = $totalPoints > 0 
            ? round(($onlinePoints / $totalPoints) * 100, 1) 
            : 0;

        return [
            'totalPoints' => $totalPoints,
            'onlinePoints' => $onlinePoints,
            'maintenancePoints' => $maintenancePoints,
            'activeTransactions' => $activeTransactions,
            'activeReservations' => $activeReservations,
            'todayTransactions' => $todayTransactions,
            'todayRevenue' => $todayRevenue,
            'monthRevenue' => $monthRevenue,
            'todayEnergy' => round($todayEnergy, 2),
            'availabilityRate' => $availabilityRate,
        ];
    }

    /**
     * Récupère les points de charge actifs
     */
    private function getActiveChargingPoints($user)
    {
        $query = $this->applyUserRoleFilter(ChargingPoint::query(), $user);
        
        return $query->with(['transactions' => function($q) {
                $q->where('status', 'in_progress')->latest();
            }])
            ->where('status', 'online')
            ->whereHas('transactions', function($q) {
                $q->where('status', 'in_progress');
            })
            ->take(8)
            ->get()
            ->map(function($cp) {
                $transaction = $cp->transactions->first();
                return [
                    'id' => $cp->id,
                    'name' => $cp->name,
                    'location' => $cp->city ?? 'N/A',
                    'energy' => $transaction ? round($transaction->energy_consumed_wh / 1000, 2) : 0,
                    'duration' => $transaction && $transaction->start_timestamp 
                        ? Carbon::parse($transaction->start_timestamp)->diffInMinutes(Carbon::now()) 
                        : 0,
                    'status' => 'charging',
                ];
            });
    }

    /**
     * Récupère les transactions récentes
     */
    private function getRecentTransactions($user)
    {
        $query = $this->applyUserRoleFilter(Transaction::query(), $user, 'charging_point_id');
        
        return $query->with(['chargingPoint', 'user'])
            ->where('status', 'completed')
            ->latest('processed_at')
            ->take(10)
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'charging_point' => $transaction->chargingPoint->name ?? 'N/A',
                    'user_name' => $transaction->user->name ?? 'N/A',
                    'amount' => $transaction->amount,
                    'energy' => round($transaction->energy_consumed_wh / 1000, 2),
                    'duration' => $transaction->duration_minutes,
                    'date' => $transaction->processed_at?->format('d/m/Y H:i') ?? $transaction->created_at->format('d/m/Y H:i'),
                ];
            });
    }

    /**
     * Données pour les graphiques
     */
    private function getChartData($user)
    {
        $query = $this->applyUserRoleFilter(Transaction::query(), $user, 'charging_point_id');
        
        // Sessions des 24 dernières heures par tranche de 3h
        $hourlyData = [];
        $hourlyLabels = [];
        
        for ($i = 0; $i < 8; $i++) {
            $start = Carbon::now()->subHours(24 - ($i * 3));
            $end = Carbon::now()->subHours(24 - (($i + 1) * 3));
            
            $count = (clone $query)
                ->whereBetween('created_at', [$end, $start])
                ->count();
            
            $hourlyData[] = $count;
            $hourlyLabels[] = $start->format('H') . 'h';
        }

        // Sessions par jour de la semaine
        $weeklyData = [];
        $weeklyLabels = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            $count = (clone $query)
                ->whereDate('created_at', $date)
                ->where('status', 'completed')
                ->count();
            
            $weeklyData[] = $count;
            $weeklyLabels[] = $date->format('d/m');
        }

        // Revenus par mois (12 derniers mois)
        $monthlyRevenue = [];
        $monthlyLabels = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            
            $revenue = (clone $query)
                ->whereMonth('processed_at', $date->month)
                ->whereYear('processed_at', $date->year)
                ->where('status', 'completed')
                ->sum('amount');
            
            $monthlyRevenue[] = round($revenue, 2);
            $monthlyLabels[] = $date->format('M');
        }

        return [
            'hourly' => [
                'labels' => $hourlyLabels,
                'data' => $hourlyData,
            ],
            'weekly' => [
                'labels' => $weeklyLabels,
                'data' => $weeklyData,
            ],
            'monthly' => [
                'labels' => $monthlyLabels,
                'data' => $monthlyRevenue,
            ],
        ];
    }

    /**
     * Récupère les meilleures stations
     */
    private function getTopPerformingStations($user)
    {
        $query = $this->applyUserRoleFilter(ChargingPoint::query(), $user);
        
        return $query->withCount(['transactions as completed_transactions' => function($q) {
                $q->where('status', 'completed')
                  ->whereDate('created_at', '>=', Carbon::now()->subDays(7));
            }])
            ->withSum(['transactions as total_energy' => function($q) {
                $q->where('status', 'completed')
                  ->whereDate('created_at', '>=', Carbon::now()->subDays(7));
            }], DB::raw('COALESCE(energy_consumed_wh, 0) / 1000'))
            ->having('completed_transactions', '>', 0)
            ->orderByDesc('completed_transactions')
            ->take(5)
            ->get()
            ->map(function($cp) {
                return [
                    'name' => $cp->name,
                    'sessions' => $cp->completed_transactions,
                    'energy' => round($cp->total_energy ?? 0, 2),
                    'city' => $cp->city ?? 'N/A',
                ];
            });
    }

    /**
     * Statistiques des revenus
     */
    private function getRevenueStats($user)
    {
        $query = $this->applyUserRoleFilter(Transaction::query(), $user, 'charging_point_id');
        
        // Revenus par période
        $today = (clone $query)
            ->whereDate('processed_at', Carbon::today())
            ->where('status', 'completed')
            ->sum('amount');

        $yesterday = (clone $query)
            ->whereDate('processed_at', Carbon::yesterday())
            ->where('status', 'completed')
            ->sum('amount');

        $thisWeek = (clone $query)
            ->whereBetween('processed_at', [Carbon::now()->startOfWeek(), Carbon::now()])
            ->where('status', 'completed')
            ->sum('amount');

        $lastWeek = (clone $query)
            ->whereBetween('processed_at', [
                Carbon::now()->subWeek()->startOfWeek(), 
                Carbon::now()->subWeek()->endOfWeek()
            ])
            ->where('status', 'completed')
            ->sum('amount');

        // Calcul des tendances
        $todayTrend = $yesterday > 0 
            ? round((($today - $yesterday) / $yesterday) * 100, 1) 
            : ($today > 0 ? 100 : 0);

        $weekTrend = $lastWeek > 0 
            ? round((($thisWeek - $lastWeek) / $lastWeek) * 100, 1) 
            : ($thisWeek > 0 ? 100 : 0);

        return [
            'today' => round($today, 2),
            'yesterday' => round($yesterday, 2),
            'thisWeek' => round($thisWeek, 2),
            'lastWeek' => round($lastWeek, 2),
            'todayTrend' => $todayTrend,
            'weekTrend' => $weekTrend,
        ];
    }

    /**
     * API endpoint pour les données temps réel
     */
    public function realtimeData(Request $request)
    {
        $user = auth()->user();
        
        $stats = $this->getRealTimeStats($user);
        $chargingPoints = $this->getActiveChargingPoints($user);
        
        return response()->json([
            'stats' => $stats,
            'activeChargingPoints' => $chargingPoints,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Applique les filtres selon le rôle utilisateur
     */
    private function applyUserRoleFilter($query, $user, $relationColumn = null)
    {
        if (!$user) {
            return $query;
        }

        $userRoles = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();

        if (in_array('admin', $userRoles)) {
            // Admin voit tout
            return $query;
        }

        if (in_array('integrator', $userRoles) && $user->integrator_id) {
            if ($relationColumn) {
                return $query->whereHas('chargingPoint', function($q) use ($user) {
                    $q->where('integrator_id', $user->integrator_id);
                });
            }
            return $query->where('integrator_id', $user->integrator_id);
        }

        if (in_array('operator', $userRoles)) {
            if ($relationColumn) {
                return $query->whereHas('chargingPoint', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
            return $query->where('user_id', $user->id);
        }

        if (in_array('partner', $userRoles) && $user->partner_id) {
            if ($relationColumn) {
                return $query->whereHas('chargingPoint', function($q) use ($user) {
                    $q->where('partner_id', $user->partner_id);
                });
            }
            return $query->where('partner_id', $user->partner_id);
        }

        // Par défaut, filtrer par utilisateur
        return $query->where('user_id', $user->id);
    }
}

