<?php

namespace App\Http\Controllers;

use App\Models\Integrator;
use App\Services\HierarchicalTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class IntegratorFeeReportController extends Controller
{
    private HierarchicalTransactionService $hierarchicalService;

    public function __construct(HierarchicalTransactionService $hierarchicalService)
    {
        $this->hierarchicalService = $hierarchicalService;
        $this->middleware('auth');
    }

    /**
     * Afficher le rapport de frais pour l'intégrateur connecté
     */
    public function index(Request $request)
    {
        Gate::authorize('view_integrator_reports');
        
        $integrator = Auth::user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $report = $this->hierarchicalService->generateIntegratorFeeReport(
            $integrator, 
            $startDate, 
            $endDate
        );

        return view('integrator.fee-report', compact('report', 'startDate', 'endDate'));
    }

    /**
     * Exporter le rapport de frais en JSON
     */
    public function export(Request $request)
    {
        Gate::authorize('export_integrator_reports');
        
        $integrator = Auth::user()->integrator;
        if (!$integrator) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $report = $this->hierarchicalService->generateIntegratorFeeReport(
            $integrator, 
            $startDate, 
            $endDate
        );

        return response()->json($report);
    }

    /**
     * Afficher les détails d'une transaction spécifique
     */
    public function showTransaction($transactionId)
    {
        Gate::authorize('view_transaction_details');
        
        $integrator = Auth::user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        $transactionDetail = \App\Models\TransactionDetail::where('transaction_id', $transactionId)
            ->whereHas('transaction.chargingPoint', function($q) use ($integrator) {
                $q->where('integrator_id', $integrator->id);
            })
            ->with(['transaction.chargingPoint', 'transaction.user'])
            ->firstOrFail();

        $feeDistribution = $transactionDetail->calculation_details['fee_distribution_details'] ?? [];

        return view('integrator.transaction-detail', compact('transactionDetail', 'feeDistribution'));
    }
}
