<?php

namespace App\Livewire;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TransactionDashboard extends Component
{
    public $user;
    public $period = 'month';
    public $dateFrom = '';
    public $dateTo = '';
    
    public $dashboardData = [];
    public $chartData = [];

    protected $listeners = ['periodChanged' => 'updatePeriod'];

    public function mount()
    {
        $this->user = auth()->user();
        $this->loadDashboardData();
    }

    public function updatePeriod($period)
    {
        $this->period = $period;
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $dates = $this->calculatePeriodDates();
        
        $query = Transaction::whereBetween('created_at', [$dates['from'], $dates['to']]);

        $this->dashboardData = $this->generateDashboardData($query);
        $this->chartData = $this->generateChartData($query);
    }

    private function calculatePeriodDates(): array
    {
        if ($this->dateFrom && $this->dateTo) {
            return [
                'from' => $this->dateFrom,
                'to' => $this->dateTo
            ];
        }

        switch ($this->period) {
            case 'today':
                return [
                    'from' => now()->startOfDay(),
                    'to' => now()->endOfDay()
                ];
            case 'week':
                return [
                    'from' => now()->startOfWeek(),
                    'to' => now()->endOfWeek()
                ];
            case 'month':
                return [
                    'from' => now()->startOfMonth(),
                    'to' => now()->endOfMonth()
                ];
            case 'quarter':
                return [
                    'from' => now()->startOfQuarter(),
                    'to' => now()->endOfQuarter()
                ];
            case 'year':
                return [
                    'from' => now()->startOfYear(),
                    'to' => now()->endOfYear()
                ];
            case 'all':
            default:
                return [
                    'from' => '1900-01-01',
                    'to' => '2100-12-31'
                ];
        }
    }

    private function generateDashboardData($query): array
    {
        $baseQuery = clone $query;
        
        // Récupérer les IDs des transactions pour calculer depuis TransactionDetail
        $transactionIds = (clone $baseQuery)->pluck('id');
        
        // Calculer les parts réelles depuis TransactionDetail
        $totalAdminShare = DB::table('transaction_details')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->whereIn('transactions.id', $transactionIds)
            ->sum('transaction_details.admin_share_amount');
            
        $totalIntegratorShare = DB::table('transaction_details')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->whereIn('transactions.id', $transactionIds)
            ->sum('transaction_details.integrator_share_amount');
            
        $totalOperatorShare = DB::table('transaction_details')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->whereIn('transactions.id', $transactionIds)
            ->sum('transaction_details.operator_share_amount');
        
        // Statistiques générales
        $stats = [
            'total_transactions' => $baseQuery->count(),
            'total_amount' => $baseQuery->sum('amount'),
            // Utiliser les parts réelles depuis TransactionDetail
            'total_admin_share' => $totalAdminShare ?? 0,
            'total_integrator_share' => $totalIntegratorShare ?? 0,
            'total_operator_share' => $totalOperatorShare ?? 0,
            'total_fees' => ($totalAdminShare ?? 0) + ($totalIntegratorShare ?? 0),
            'average_transaction_amount' => $baseQuery->avg('amount'),
            'completed_transactions' => $baseQuery->where('status', 'completed')->count(),
            'pending_transactions' => $baseQuery->where('status', 'pending')->count(),
            'canceled_transactions' => $baseQuery->where('status', 'canceled')->count(),
            'failed_transactions' => $baseQuery->where('status', 'failed')->count()
        ];

        // Statistiques par type de transaction
        $byType = $baseQuery->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total_amount, AVG(amount) as avg_amount')
            ->groupBy('transaction_type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $this->getTransactionTypeLabel($item->transaction_type),
                    'count' => $item->count,
                    'total_amount' => $item->total_amount,
                    'avg_amount' => $item->avg_amount
                ];
            });

        // Statistiques par statut
        $byStatus = $baseQuery->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $this->getStatusLabel($item->status),
                    'count' => $item->count,
                    'total_amount' => $item->total_amount
                ];
            });

        // Statistiques spécifiques au rôle
        $roleSpecificStats = $this->getRoleSpecificStats($baseQuery);

        return [
            'general_stats' => $stats,
            'by_type' => $byType,
            'by_status' => $byStatus,
            'role_specific_stats' => $roleSpecificStats,
            'period' => $this->period
        ];
    }

    private function generateChartData($query): array
    {
        $baseQuery = clone $query;
        
        // Tendances quotidiennes
        $dailyTrends = $baseQuery->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Répartition par type
        $typeDistribution = $baseQuery->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('transaction_type')
            ->get();

        // Répartition par statut
        $statusDistribution = $baseQuery->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('status')
            ->get();

        return [
            'daily_trends' => $dailyTrends,
            'type_distribution' => $typeDistribution,
            'status_distribution' => $statusDistribution
        ];
    }

    private function getRoleSpecificStats($query): array
    {
        switch ($this->user->role) {
            case 'admin':
                return [
                    'total_revenue' => $query->sum('amount'),
                    'total_fees_collected' => $query->sum('admin_commission') + $query->sum('integrator_commission') + $query->sum('partner_commission'),
                    'admin_fees' => $query->sum('admin_commission'),
                    'integrator_fees' => $query->sum('integrator_commission'),
                    'operator_fees' => $query->sum('partner_commission'),
                    'top_integrators' => $query->selectRaw('user_id, COUNT(*) as transaction_count, SUM(amount) as total_amount')
                      ->groupBy('user_id')
                      ->orderBy('total_amount', 'desc')
                      ->limit(5)
                      ->get()
                ];

            case 'integrator':
                return [
                    'total_revenue' => $query->where('user_id', $this->user->id)->sum('amount'),
                    'total_fees_collected' => $query->sum('integrator_commission') + $query->sum('partner_commission'),
                    'integrator_fees' => $query->sum('integrator_commission'),
                    'operator_fees' => $query->sum('partner_commission'),
                    'top_operators' => $query->selectRaw('user_id, COUNT(*) as transaction_count, SUM(amount) as total_amount')
                      ->groupBy('user_id')
                      ->orderBy('total_amount', 'desc')
                      ->limit(5)
                      ->get()
                ];

            case 'operator':
                return [
                    'total_revenue' => $query->where('user_id', $this->user->id)->sum('amount'),
                    'total_fees_paid' => $query->where('user_id', $this->user->id)->sum('partner_commission'),
                    'average_transaction_amount' => $query->where('user_id', $this->user->id)->avg('amount'),
                    'most_common_transaction_type' => $query->where('user_id', $this->user->id)
                        ->selectRaw('transaction_type, COUNT(*) as count')
                        ->groupBy('transaction_type')
                        ->orderBy('count', 'desc')
                        ->first()
                ];

            default:
                return [];
        }
    }

    private function getTransactionTypeLabel(string $type): string
    {
        return match($type) {
            'admin_to_integrator' => 'Admin → Integrator',
            'integrator_to_operator' => 'Integrator → Operator',
            'recharge' => 'Recharge',
            'refund' => 'Remboursement',
            'commission' => 'Commission',
            default => ucfirst(str_replace('_', ' ', $type))
        };
    }

    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'completed' => 'Terminé',
            'pending' => 'En attente',
            'canceled' => 'Annulé',
            'failed' => 'Échoué',
            default => ucfirst($status)
        };
    }

    public function render()
    {
        return view('livewire.transaction-dashboard');
    }
}
