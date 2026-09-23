<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Contrôleur Admin pour les rapports et exports
 */
class ReportController extends Controller
{
    public function __construct(
        protected ReportExportService $exportService
    ) {}

    /**
     * Tableau de bord des rapports
     */
    public function index()
    {
        return view('admin.reports.index');
    }

    /**
     * Exporter les transactions
     */
    public function exportTransactions(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'status', 'type', 'user_id']);
        $format = $request->get('format', 'pdf');

        if ($format === 'excel') {
            return $this->exportService->exportTransactionsExcel($filters);
        }

        return $this->exportService->exportTransactionsPdf($filters);
    }

    /**
     * Exporter les retraits
     */
    public function exportWithdrawals(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'status', 'user_id']);
        $format = $request->get('format', 'pdf');

        if ($format === 'excel') {
            return $this->exportService->exportWithdrawalsExcel($filters);
        }

        return $this->exportService->exportWithdrawalsPdf($filters);
    }

    /**
     * Exporter les sessions de recharge
     */
    public function exportSessions(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'status', 'user_id']);
        $format = $request->get('format', 'pdf');

        if ($format === 'excel') {
            return $this->exportService->exportSessionsExcel($filters);
        }

        return $this->exportService->exportSessionsPdf($filters);
    }

    /**
     * Exporter le rapport financier
     */
    public function exportFinancialReport(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'status']);
        $format = $request->get('format', 'pdf');

        return $this->exportService->exportFinancialReport($format, $filters);
    }

    /**
     * Exporter les statistiques globales
     */
    public function exportStatistics(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $format = $request->get('format', 'pdf');

        return $this->exportService->exportStatistics($format, $filters);
    }

    /**
     * Aperçu du rapport financier
     */
    public function financialPreview(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        
        $transactions = $this->exportService->exportTransactionsPdf($filters);
        $withdrawals = $this->exportService->exportWithdrawalsPdf($filters);
        $sessions = $this->exportService->exportSessionsPdf($filters);

        return view('admin.reports.financial-preview', [
            'filters' => $filters,
        ]);
    }
}
