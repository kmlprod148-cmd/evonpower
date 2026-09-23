<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\User;
use App\Models\Integrator;
use App\Models\Partner;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Service de reporting pour les transactions
 * 
 * Fournit les données pour les tableaux de bord Admin, Intégrateur et Partenaire
 * ainsi que les fonctionnalités d'export CSV/Excel
 */
class TransactionReportingService
{
    protected TransactionAnalyticsService $analyticsService;

    public function __construct(TransactionAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Obtenir les données du tableau de bord Administrateur
     * 
     * @param array $period Période sélectionnée (from, to)
     * @return array
     */
    public function getAdminDashboardData(array $period = []): array
    {
        $from = $period['from'] ?? Carbon::now()->startOfMonth();
        $to = $period['to'] ?? Carbon::now()->endOfMonth();

        // Revenus globaux par type de transaction
        $revenueByType = $this->getRevenueByTransactionType($from, $to);
        
        // Nombre de bornes actives
        $activeChargingPoints = $this->getActiveChargingPointsCount($from, $to);
        
        // Volume de recharges en cours
        $rechargeVolume = $this->getRechargeVolume($from, $to);
        
        // Dernières transactions
        $lastTransactions = $this->getLastTransactions(5);
        
        // Données pour graphiques évolutifs
        $revenueEvolution = $this->getRevenueEvolution($from, $to);
        $activityEvolution = $this->getActivityEvolution($from, $to);

        return [
            'revenue' => [
                'total' => $revenueByType['total'],
                'by_type' => $revenueByType['by_type'],
                'by_category' => $revenueByType['by_category'],
            ],
            'active_charging_points' => $activeChargingPoints,
            'recharge_volume' => $rechargeVolume,
            'last_transactions' => $lastTransactions,
            'revenue_evolution' => $revenueEvolution,
            'activity_evolution' => $activityEvolution,
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    /**
     * Obtenir les données du tableau de bord Intégrateur/Partenaire
     * 
     * @param User|Integrator|Partner $user
     * @param array $period
     * @return array
     */
    public function getIntegratorPartnerDashboardData($user, array $period = []): array
    {
        $from = $period['from'] ?? Carbon::now()->startOfMonth();
        $to = $period['to'] ?? Carbon::now()->endOfMonth();

        // Chiffre d'affaires par borne et par période
        $revenueByChargingPoint = $this->getRevenueByChargingPoint($user, $from, $to);
        
        // Nombre de bornes actives
        $activeChargingPoints = $this->getUserActiveChargingPoints($user);
        
        // Statistiques de recharges
        $rechargeStats = $this->getRechargeStats($user, $from, $to);
        
        // Courbes d'évolution
        $dailyEvolution = $this->getDailyEvolution($user, $from, $to);
        $weeklyEvolution = $this->getWeeklyEvolution($user, $from, $to);
        $monthlyEvolution = $this->getMonthlyEvolution($user, $from, $to);
        
        // Comparatif de performance
        $performanceComparison = $this->getPerformanceComparison($user, $from, $to);

        return [
            'revenue' => [
                'total' => $revenueByChargingPoint['total'],
                'by_charging_point' => $revenueByChargingPoint['by_charging_point'],
                'by_period' => $revenueByChargingPoint['by_period'],
            ],
            'active_charging_points' => $activeChargingPoints,
            'recharge_stats' => $rechargeStats,
            'evolution' => [
                'daily' => $dailyEvolution,
                'weekly' => $weeklyEvolution,
                'monthly' => $monthlyEvolution,
            ],
            'performance_comparison' => $performanceComparison,
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    /**
     * Obtenir les revenus par type de transaction
     */
    protected function getRevenueByTransactionType($from, $to): array
    {
        $query = Transaction::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['completed', 'paid', 'approved']);

        $total = (float) $query->sum('price_total');

        $byType = (clone $query)
            ->selectRaw('transaction_type, SUM(price_total) as total, COUNT(*) as count')
            ->groupBy('transaction_type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->transaction_type,
                    'type_label' => TransactionType::getTypeLabel($item->transaction_type ?? ''),
                    'total' => (float) $item->total,
                    'count' => (int) $item->count,
                ];
            });

        $byCategory = (clone $query)
            ->selectRaw('transaction_category, SUM(price_total) as total, COUNT(*) as count')
            ->groupBy('transaction_category')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->transaction_category,
                    'category_label' => TransactionType::getCategoryLabel($item->transaction_category ?? ''),
                    'total' => (float) $item->total,
                    'count' => (int) $item->count,
                ];
            });

        return [
            'total' => $total,
            'by_type' => $byType,
            'by_category' => $byCategory,
        ];
    }

    /**
     * Obtenir le nombre de bornes actives
     */
    protected function getActiveChargingPointsCount($from, $to): int
    {
        return ChargingPoint::where('is_available', true)
            ->whereHas('transactions', function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [$from, $to]);
            })
            ->count();
    }

    /**
     * Obtenir le volume de recharges en cours
     */
    protected function getRechargeVolume($from, $to): array
    {
        $pendingRecharges = Transaction::whereBetween('created_at', [$from, $to])
            ->whereIn('transaction_category', [TransactionType::CATEGORY_RECHARGE, TransactionType::CATEGORY_RECHARGE_WALLET])
            ->whereIn('status', ['pending', 'processing'])
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'amount' => (float) ($tx->price_total ?? $tx->amount ?? 0),
                    'status' => $tx->status,
                    'created_at' => $tx->created_at,
                ];
            });

        return [
            'count' => $pendingRecharges->count(),
            'total_amount' => $pendingRecharges->sum('amount'),
            'transactions' => $pendingRecharges,
        ];
    }

    /**
     * Obtenir les dernières transactions
     */
    protected function getLastTransactions(int $limit = 5): array
    {
        return Transaction::with(['user:id,name,email', 'chargingPoint:id,name,charge_point_id'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'type' => $tx->transaction_type,
                    'type_label' => TransactionType::getTypeLabel($tx->transaction_type ?? ''),
                    'amount' => (float) ($tx->price_total ?? $tx->amount ?? 0),
                    'user' => $tx->user ? [
                        'id' => $tx->user->id,
                        'name' => $tx->user->name,
                    ] : null,
                    'charging_point' => $tx->chargingPoint ? [
                        'id' => $tx->chargingPoint->id,
                        'name' => $tx->chargingPoint->name,
                    ] : null,
                    'status' => $tx->status,
                    'created_at' => $tx->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    /**
     * Obtenir l'évolution des revenus
     */
    protected function getRevenueEvolution($from, $to): array
    {
        $daily = Transaction::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['completed', 'paid', 'approved'])
            ->selectRaw('DATE(created_at) as date, SUM(price_total) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'total' => (float) $item->total,
                ];
            });

        return [
            'daily' => $daily,
        ];
    }

    /**
     * Obtenir l'évolution de l'activité
     */
    protected function getActivityEvolution($from, $to): array
    {
        $daily = Transaction::whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => (int) $item->count,
                ];
            });

        return [
            'daily' => $daily,
        ];
    }

    /**
     * Obtenir les revenus par borne pour un intégrateur/partenaire
     */
    protected function getRevenueByChargingPoint($user, $from, $to): array
    {
        $query = Transaction::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['completed', 'paid', 'approved']);

        // Filtrer par intégrateur ou partenaire
        if ($user instanceof Integrator) {
            $query->where('integrator_id', $user->id);
        } elseif ($user instanceof Partner) {
            $query->whereHas('chargingPoint', function ($q) use ($user) {
                $q->where('partner_id', $user->id);
            });
        } elseif ($user instanceof User && $user->isIntegrator()) {
            $query->where('integrator_id', $user->id);
        } elseif ($user instanceof User && $user->isPartner()) {
            $query->whereHas('chargingPoint', function ($q) use ($user) {
                $q->where('partner_id', $user->partner_id);
            });
        }

        $total = (float) $query->sum('price_total');

        $byChargingPoint = (clone $query)
            ->selectRaw('charging_point_id, SUM(price_total) as total, COUNT(*) as count')
            ->groupBy('charging_point_id')
            ->with('chargingPoint:id,name,charge_point_id')
            ->get()
            ->map(function ($item) {
                return [
                    'charging_point_id' => $item->charging_point_id,
                    'charging_point_name' => $item->chargingPoint?->name ?? 'Inconnu',
                    'total' => (float) $item->total,
                    'count' => (int) $item->count,
                ];
            });

        return [
            'total' => $total,
            'by_charging_point' => $byChargingPoint,
            'by_period' => [],
        ];
    }

    /**
     * Obtenir les bornes actives d'un utilisateur
     */
    protected function getUserActiveChargingPoints($user): int
    {
        $query = ChargingPoint::where('is_available', true);

        if ($user instanceof Integrator) {
            $query->where('integrator_id', $user->id);
        } elseif ($user instanceof Partner) {
            $query->where('partner_id', $user->id);
        } elseif ($user instanceof User) {
            if ($user->isIntegrator()) {
                $query->where('integrator_id', $user->id);
            } elseif ($user->isPartner()) {
                $query->where('partner_id', $user->partner_id);
            }
        }

        return $query->count();
    }

    /**
     * Obtenir les statistiques de recharges
     */
    protected function getRechargeStats($user, $from, $to): array
    {
        $query = Transaction::whereBetween('created_at', [$from, $to])
            ->whereIn('transaction_category', [TransactionType::CATEGORY_RECHARGE, TransactionType::CATEGORY_RECHARGE_WALLET])
            ->whereIn('status', ['completed', 'paid', 'approved']);

        if ($user instanceof User && $user->isIntegrator()) {
            $query->where('integrator_id', $user->id);
        }

        return [
            'count' => $query->count(),
            'total_amount' => (float) $query->sum('price_total'),
        ];
    }

    /**
     * Obtenir l'évolution journalière
     */
    protected function getDailyEvolution($user, $from, $to): array
    {
        $query = Transaction::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['completed', 'paid', 'approved']);

        if ($user instanceof Integrator) {
            $query->where('integrator_id', $user->id);
        } elseif ($user instanceof User && $user->isIntegrator()) {
            $query->where('integrator_id', $user->id);
        }

        return $query->selectRaw('DATE(created_at) as date, SUM(price_total) as total, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'total' => (float) $item->total,
                'count' => (int) $item->count,
            ])
            ->toArray();
    }

    /**
     * Obtenir l'évolution hebdomadaire
     */
    protected function getWeeklyEvolution($user, $from, $to): array
    {
        $query = Transaction::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['completed', 'paid', 'approved']);

        if ($user instanceof Integrator) {
            $query->where('integrator_id', $user->id);
        } elseif ($user instanceof User && $user->isIntegrator()) {
            $query->where('integrator_id', $user->id);
        }

        return $query->selectRaw('YEARWEEK(created_at) as week, SUM(price_total) as total, COUNT(*) as count')
            ->groupBy(DB::raw('YEARWEEK(created_at)'))
            ->orderBy('week')
            ->get()
            ->map(fn($item) => [
                'week' => $item->week,
                'total' => (float) $item->total,
                'count' => (int) $item->count,
            ])
            ->toArray();
    }

    /**
     * Obtenir l'évolution mensuelle
     */
    protected function getMonthlyEvolution($user, $from, $to): array
    {
        $query = Transaction::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['completed', 'paid', 'approved']);

        if ($user instanceof Integrator) {
            $query->where('integrator_id', $user->id);
        } elseif ($user instanceof User && $user->isIntegrator()) {
            $query->where('integrator_id', $user->id);
        }

        return $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(price_total) as total, COUNT(*) as count')
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
            ->orderBy('month')
            ->get()
            ->map(fn($item) => [
                'month' => $item->month,
                'total' => (float) $item->total,
                'count' => (int) $item->count,
            ])
            ->toArray();
    }

    /**
     * Obtenir le comparatif de performance
     */
    protected function getPerformanceComparison($user, $from, $to): array
    {
        // Période actuelle
        $currentPeriod = clone $from;
        $currentTotal = $this->calculatePeriodTotal($user, $from, $to);

        // Période précédente (même durée)
        $periodLength = $from->diffInDays($to);
        $previousFrom = $from->copy()->subDays($periodLength + 1);
        $previousTo = $from->copy()->subDay();
        $previousTotal = $this->calculatePeriodTotal($user, $previousFrom, $previousTo);

        // Calcul de l'évolution
        $evolution = $previousTotal > 0 
            ? (($currentTotal - $previousTotal) / $previousTotal) * 100 
            : 0;

        return [
            'current_period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'total' => $currentTotal,
            ],
            'previous_period' => [
                'from' => $previousFrom->format('Y-m-d'),
                'to' => $previousTo->format('Y-m-d'),
                'total' => $previousTotal,
            ],
            'evolution_percentage' => round($evolution, 2),
            'trend' => $evolution > 0 ? 'up' : ($evolution < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Calculer le total des transactions pour une période
     */
    protected function calculatePeriodTotal($user, $from, $to): float
    {
        $query = Transaction::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['completed', 'paid', 'approved']);

        if ($user instanceof Integrator) {
            $query->where('integrator_id', $user->id);
        } elseif ($user instanceof User && $user->isIntegrator()) {
            $query->where('integrator_id', $user->id);
        }

        return (float) $query->sum('price_total');
    }

    /**
     * Obtenir les transactions avec filtres pour l'export
     * 
     * @param array $filters Filtres: type, status, collect_status, integrator_id, partner_id
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFilteredTransactions(array $filters = [])
    {
        $query = Transaction::with([
            'user:id,name,email',
            'chargingPoint:id,name,charge_point_id',
            'collectUser'
        ]);

        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (!empty($filters['transaction_category'])) {
            $query->where('transaction_category', $filters['transaction_category']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['collect_status'])) {
            $query->where('collect_status', $filters['collect_status']);
        }

        if (!empty($filters['integrator_id'])) {
            $query->where('integrator_id', $filters['integrator_id']);
        }

        if (!empty($filters['partner_id'])) {
            $query->whereHas('chargingPoint', function ($q) use ($filters) {
                $q->where('partner_id', $filters['partner_id']);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Exporter les transactions en CSV
     * 
     * @param array $filters
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportTransactionsCsv(array $filters = [])
    {
        $transactions = $this->getFilteredTransactions($filters)->get();

        $headers = [
            'ID',
            'Type',
            'Catégorie',
            'Montant',
            'Statut',
            'Utilisateur',
            'Borne',
            'Date',
            'Collecteur',
            'Statut collecte',
            'Date collecte',
            'Référence collecte',
        ];

        $callback = function () use ($transactions, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers, ';');

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx->id,
                    TransactionType::getTypeLabel($tx->transaction_type ?? ''),
                    TransactionType::getCategoryLabel($tx->transaction_category ?? ''),
                    $tx->price_total ?? $tx->amount ?? 0,
                    $tx->status,
                    $tx->user?->name ?? 'N/A',
                    $tx->chargingPoint?->name ?? 'N/A',
                    $tx->created_at->format('Y-m-d H:i:s'),
                    $tx->collectUser?->name ?? 'N/A',
                    $tx->collect_status ?? 'N/A',
                    $tx->collect_date?->format('Y-m-d H:i:s') ?? 'N/A',
                    $tx->collect_reference ?? 'N/A',
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="transactions_' . date('Ymd_His') . '.csv"',
        ]);
    }

    /**
     * Exporter les transactions en Excel
     * 
     * @param array $filters
     * @return \Maatwebsite\Excel\BinaryFileResponse
     */
    public function exportTransactionsExcel(array $filters = [])
    {
        $transactions = $this->getFilteredTransactions($filters)->get();

        return Excel::download(new class($transactions) implements \Maatwebsite\Excel\Concerns\FromCollection {
            protected $transactions;

            public function __construct($transactions)
            {
                $this->transactions = $transactions;
            }

            public function collection()
            {
                return $this->transactions->map(function ($tx) {
                    return [
                        'ID' => $tx->id,
                        'Type' => TransactionType::getTypeLabel($tx->transaction_type ?? ''),
                        'Catégorie' => TransactionType::getCategoryLabel($tx->transaction_category ?? ''),
                        'Montant' => $tx->price_total ?? $tx->amount ?? 0,
                        'Statut' => $tx->status,
                        'Utilisateur' => $tx->user?->name ?? 'N/A',
                        'Borne' => $tx->chargingPoint?->name ?? 'N/A',
                        'Date' => $tx->created_at->format('Y-m-d H:i:s'),
                        'Collecteur' => $tx->collectUser?->name ?? 'N/A',
                        'Statut collecte' => $tx->collect_status ?? 'N/A',
                        'Date collecte' => $tx->collect_date?->format('Y-m-d H:i:s') ?? 'N/A',
                        'Référence collecte' => $tx->collect_reference ?? 'N/A',
                    ];
                });
            }
        }, 'transactions_' . date('Ymd_His') . '.xlsx');
    }
}
