<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\WithdrawalRequest;
use App\Models\ChargingSession;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Exports\TransactionExport;
use App\Exports\WithdrawalExport;
use App\Exports\ChargingSessionExport;
use App\Exports\FinancialReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

/**
 * Service d'exportation de rapports PDF et Excel
 */
class ReportExportService
{
    /**
     * Exporter les transactions en Excel
     */
    public function exportTransactionsExcel(
        array $filters = [],
        ?string $fileName = null
    ): \Maatwebsite\Excel\Excel
    {
        $transactions = $this->getFilteredTransactions($filters);
        
        return Excel::download(
            new TransactionExport($transactions),
            $fileName ?? 'transactions_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Exporter les transactions en PDF
     */
    public function exportTransactionsPdf(
        array $filters = [],
        ?string $fileName = null
    ): \Illuminate\Http\Response
    {
        $transactions = $this->getFilteredTransactions($filters);
        $summary = $this->getTransactionSummary($filters);

        $pdf = Pdf::loadView('reports.transactions-pdf', [
            'transactions' => $transactions,
            'filters' => $filters,
            'summary' => $summary,
            'generatedAt' => now(),
        ]);

        return $pdf->download($fileName ?? 'transactions_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Exporter les demandes de retrait en Excel
     */
    public function exportWithdrawalsExcel(
        array $filters = [],
        ?string $fileName = null
    ): \Maatwebsite\Excel\Excel
    {
        $withdrawals = $this->getFilteredWithdrawals($filters);

        return Excel::download(
            new WithdrawalExport($withdrawals),
            $fileName ?? 'retraits_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Exporter les demandes de retrait en PDF
     */
    public function exportWithdrawalsPdf(
        array $filters = [],
        ?string $fileName = null
    ): \Illuminate\Http\Response
    {
        $withdrawals = $this->getFilteredWithdrawals($filters);
        $summary = $this->getWithdrawalSummary($filters);

        $pdf = Pdf::loadView('reports.withdrawals-pdf', [
            'withdrawals' => $withdrawals,
            'filters' => $filters,
            'summary' => $summary,
            'generatedAt' => now(),
        ]);

        return $pdf->download($fileName ?? 'retraits_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Exporter les sessions de recharge en Excel
     */
    public function exportSessionsExcel(
        array $filters = [],
        ?string $fileName = null
    ): \Maatwebsite\Excel\Excel
    {
        $sessions = $this->getFilteredSessions($filters);

        return Excel::download(
            new ChargingSessionExport($sessions),
            $fileName ?? 'sessions_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Exporter les sessions de recharge en PDF
     */
    public function exportSessionsPdf(
        array $filters = [],
        ?string $fileName = null
    ): \Illuminate\Http\Response
    {
        $sessions = $this->getFilteredSessions($filters);
        $summary = $this->getSessionSummary($filters);

        $pdf = Pdf::loadView('reports.sessions-pdf', [
            'sessions' => $sessions,
            'filters' => $filters,
            'summary' => $summary,
            'generatedAt' => now(),
        ]);

        return $pdf->download($fileName ?? 'sessions_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Exporter le rapport financier complet
     */
    public function exportFinancialReport(
        string $format = 'pdf',
        array $filters = [],
        ?string $fileName = null
    ): mixed
    {
        $transactions = $this->getFilteredTransactions($filters);
        $withdrawals = $this->getFilteredWithdrawals($filters);
        $sessions = $this->getFilteredSessions($filters);
        
        $financialSummary = $this->getFinancialSummary($filters);

        if ($format === 'excel') {
            return Excel::download(
                new FinancialReportExport($transactions, $withdrawals, $sessions, $financialSummary),
                $fileName ?? 'rapport_financier_' . date('Y-m-d') . '.xlsx'
            );
        }

        $pdf = Pdf::loadView('reports.financial-pdf', [
            'transactions' => $transactions,
            'withdrawals' => $withdrawals,
            'sessions' => $sessions,
            'summary' => $financialSummary,
            'filters' => $filters,
            'generatedAt' => now(),
        ]);

        return $pdf->download($fileName ?? 'rapport_financier_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Exporter les statistiques globales
     */
    public function exportStatistics(
        string $format = 'pdf',
        array $filters = [],
        ?string $fileName = null
    ): mixed
    {
        $stats = $this->getGlobalStatistics($filters);

        if ($format === 'excel') {
            return Excel::download(
                new \App\Exports\StatisticsExport($stats),
                $fileName ?? 'statistiques_' . date('Y-m-d') . '.xlsx'
            );
        }

        $pdf = Pdf::loadView('reports.statistics-pdf', [
            'stats' => $stats,
            'filters' => $filters,
            'generatedAt' => now(),
        ]);

        return $pdf->download($fileName ?? 'statistiques_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Obtenir les transactions filtrées
     */
    protected function getFilteredTransactions(array $filters): Collection
    {
        $query = Transaction::with(['user', 'chargingPoint']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtenir les retraits filtrés
     */
    protected function getFilteredWithdrawals(array $filters): Collection
    {
        $query = WithdrawalRequest::with(['owner', 'wallet']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('owner_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtenir les sessions filtrées
     */
    protected function getFilteredSessions(array $filters): Collection
    {
        $query = ChargingSession::with(['user', 'chargingPoint']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtenir le résumé des transactions
     */
    protected function getTransactionSummary(array $filters): array
    {
        $query = Transaction::query();

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_count' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'total_fees' => $query->sum('fee_amount'),
        ];
    }

    /**
     * Obtenir le résumé des retraits
     */
    protected function getWithdrawalSummary(array $filters): array
    {
        $query = WithdrawalRequest::query();

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_count' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'total_fees' => $query->sum('fee'),
            'completed_count' => $query->where('status', 'completed')->count(),
            'pending_count' => $query->where('status', 'pending')->count(),
        ];
    }

    /**
     * Obtenir le résumé des sessions
     */
    protected function getSessionSummary(array $filters): array
    {
        $query = ChargingSession::query();

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_count' => $query->count(),
            'total_energy' => $query->sum('energy_delivered'),
            'total_duration' => $query->sum('duration'),
            'average_session_cost' => $query->avg('actual_cost'),
        ];
    }

    /**
     * Obtenir le résumé financier complet
     */
    protected function getFinancialSummary(array $filters): array
    {
        $transactionsQuery = Transaction::query();
        $withdrawalsQuery = WithdrawalRequest::query();
        $sessionsQuery = ChargingSession::query();
        $walletQuery = WalletTransaction::query();

        foreach ([$transactionsQuery, $withdrawalsQuery, $sessionsQuery, $walletQuery] as $query) {
            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }
        }

        return [
            'revenus' => [
                'transactions' => $transactionsQuery->sum('amount'),
                'sessions' => $sessionsQuery->sum('actual_cost'),
            ],
            'depenses' => [
                'retraits' => $withdrawalsQuery->where('status', 'completed')->sum('net_amount'),
                'frais_retraits' => $withdrawalsQuery->sum('fee'),
            ],
            'wallets' => [
                'total_credits' => $walletQuery->where('type', 'credit')->sum('amount'),
                'total_debits' => $walletQuery->where('type', 'debit')->sum('amount'),
            ],
            'totals' => [
                'solde_net' => ($transactionsQuery->sum('amount') + $sessionsQuery->sum('actual_cost')) 
                    - $withdrawalsQuery->where('status', 'completed')->sum('net_amount'),
            ],
        ];
    }

    /**
     * Obtenir les statistiques globales
     */
    protected function getGlobalStatistics(array $filters): array
    {
        $usersQuery = User::query();
        $transactionsQuery = Transaction::query();
        $sessionsQuery = ChargingSession::query();
        $withdrawalsQuery = WithdrawalRequest::query();

        foreach ([$usersQuery, $transactionsQuery, $sessionsQuery, $withdrawalsQuery] as $query) {
            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }
        }

        return [
            'utilisateurs' => [
                'total' => User::count(),
                'nouveaux' => $usersQuery->count(),
            ],
            'transactions' => [
                'total' => $transactionsQuery->count(),
                'montant_total' => $transactionsQuery->sum('amount'),
            ],
            'sessions' => [
                'total' => $sessionsQuery->count(),
                'energie_totale' => $sessionsQuery->sum('energy_delivered'),
            ],
            'retraits' => [
                'total' => $withdrawalsQuery->count(),
                'montant_total' => $withdrawalsQuery->sum('amount'),
            ],
        ];
    }
}
