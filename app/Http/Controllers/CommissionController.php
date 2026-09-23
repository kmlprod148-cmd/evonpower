<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Integrator;
use App\Models\Partner;
use App\Services\CommissionService;
use App\Services\CommissionReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CommissionController extends Controller
{
    protected $commissionService;
    protected $reportService;

    public function __construct(CommissionService $commissionService, CommissionReportService $reportService)
    {
        $this->commissionService = $commissionService;
        $this->reportService = $reportService;
        $this->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_commission_settings');
        $this->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':manage_commissions', ['only' => ['markAsPaid', 'markMultipleAsPaid', 'recalculate']]);
    }

    /**
     * Affiche le tableau de bord des commissions.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function dashboard(Request $request)
    {
        // Période par défaut: mois en cours
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Générer le rapport pour la période
        $report = $this->reportService->generateReport($startDate, $endDate);
        
        // Récupérer les commissions impayées
        $unpaidReport = $this->reportService->generateUnpaidCommissionsReport('all');
        
        // Récupérer les dernières transactions
        $latestTransactions = Transaction::orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        // Déterminer si on est dans le contexte des paramètres admin
        $isAdminSettings = $request->route()->getName() === 'settings.commission-dashboard';
        
        if ($isAdminSettings) {
            // Vue pour les paramètres admin
            return view('settings.commission-dashboard', compact('report', 'unpaidReport', 'latestTransactions', 'startDate', 'endDate', 'isAdminSettings'));
        } else {
            // Vue standard
            return view('commissions.dashboard', compact('report', 'unpaidReport', 'latestTransactions', 'startDate', 'endDate', 'isAdminSettings'));
        }
    }

    /**
     * Affiche la liste des transactions avec leurs commissions.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Transaction::query();
        
        // Filtres
        if ($request->filled('start_date')) {
            $query->where('start_timestamp', '>=', $request->input('start_date') . ' 00:00:00');
        }
        
        if ($request->filled('end_date')) {
            $query->where('start_timestamp', '<=', $request->input('end_date') . ' 23:59:59');
        }
        
        if ($request->filled('integrator_id')) {
            $query->whereHas('chargingPoint', function ($q) use ($request) {
                $q->where('integrator_id', $request->input('integrator_id'));
            });
        }
        
        if ($request->filled('partner_id')) {
            $query->whereHas('chargingPoint', function ($q) use ($request) {
                $q->where('partner_id', $request->input('partner_id'));
            });
        }
        
        if ($request->filled('charging_point_id')) {
            $query->where('charging_point_id', $request->input('charging_point_id'));
        }
        
        if ($request->filled('commission_plan_id')) {
            $query->where('commission_plan_id', $request->input('commission_plan_id'));
        }
        
        if ($request->filled('commission_paid_status')) {
            $status = $request->input('commission_paid_status');
            $type = $request->input('commission_type', 'all');
            
            if ($type === 'admin' || $type === 'all') {
                $query->where('admin_commission_paid', $status === 'paid');
            }
            
            if ($type === 'integrator' || $type === 'all') {
                $query->where('integrator_commission_paid', $status === 'paid');
            }
            
            if ($type === 'partner' || $type === 'all') {
                $query->where('partner_commission_paid', $status === 'paid');
            }
        }
        
        // Tri
        $sortBy = $request->input('sort_by', 'start_timestamp');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $transactions = $query->paginate($perPage);
        
        // Récupérer les intégrateurs et partenaires pour les filtres
        $integrators = Integrator::orderBy('name')->get();
        $partners = Partner::orderBy('name')->get();
        
        return view('commissions.index', compact('transactions', 'integrators', 'partners'));
    }

    /**
     * Affiche les détails d'une transaction avec ses commissions.
     *
     * @param Transaction $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        return view('commissions.show', compact('transaction'));
    }

    /**
     * Marque une commission comme payée.
     *
     * @param Request $request
     * @param Transaction $transaction
     * @return \Illuminate\Http\Response
     */
    public function markAsPaid(Request $request, Transaction $transaction)
    {
        $validator = Validator::make($request->all(), [
            'commission_type' => ['required', Rule::in(['admin', 'integrator', 'partner'])],
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $type = $request->input('commission_type');
        
        if ($this->commissionService->markCommissionAsPaid($transaction, $type)) {
            return redirect()->back()
                ->with('success', "Commission {$type} marquée comme payée.");
        }
        
        return redirect()->back()
            ->with('error', "Impossible de marquer la commission comme payée.");
    }

    /**
     * Marque plusieurs commissions comme payées.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function markMultipleAsPaid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'integer|exists:transactions,id',
            'commission_type' => ['required', Rule::in(['admin', 'integrator', 'partner'])],
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $transactionIds = $request->input('transaction_ids');
        $type = $request->input('commission_type');
        
        $transactions = Transaction::whereIn('id', $transactionIds)->get();
        $count = $this->commissionService->markMultipleCommissionsAsPaid($transactions, $type);
        
        return redirect()->back()
            ->with('success', "{$count} commissions marquées comme payées.");
    }

    /**
     * Affiche la page des rapports de commission.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function reports(Request $request)
    {
        // Période par défaut: mois en cours
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Type de regroupement
        $groupBy = $request->input('group_by', 'day');
        
        // Filtres
        $filters = [
            'group_by' => $groupBy,
        ];
        
        if ($request->filled('integrator_id')) {
            $filters['integrator_id'] = $request->input('integrator_id');
        }
        
        if ($request->filled('partner_id')) {
            $filters['partner_id'] = $request->input('partner_id');
        }
        
        if ($request->filled('commission_plan_id')) {
            $filters['commission_plan_id'] = $request->input('commission_plan_id');
        }
        
        // Générer le rapport
        $report = $this->reportService->generateReport($startDate, $endDate, $filters);
        
        // Récupérer les intégrateurs et partenaires pour les filtres
        $integrators = Integrator::orderBy('name')->get();
        $partners = Partner::orderBy('name')->get();
        
        return view('commissions.reports', compact('report', 'startDate', 'endDate', 'groupBy', 'integrators', 'partners'));
    }

    /**
     * Exporte les données de commission au format CSV.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $query = Transaction::with(['chargingPoint', 'connector', 'user', 'commissionPlan', 'pricingPlan'])->whereBetween('start_timestamp', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
        
        // Appliquer les filtres
        if ($request->filled('integrator_id')) {
            $query->whereHas('chargingPoint', function ($q) use ($request) {
                $q->where('integrator_id', $request->input('integrator_id'));
            });
        }
        
        if ($request->filled('partner_id')) {
            $query->whereHas('chargingPoint', function ($q) use ($request) {
                $q->where('partner_id', $request->input('partner_id'));
            });
        }
        
        if ($request->filled('commission_plan_id')) {
            $query->where('commission_plan_id', $request->input('commission_plan_id'));
        }
        
        if ($request->filled('commission_paid_status')) {
            $status = $request->input('commission_paid_status');
            $type = $request->input('commission_type', 'all');
            
            if ($type === 'admin' || $type === 'all') {
                $query->where('admin_commission_paid', $status === 'paid');
            }
            
            if ($type === 'integrator' || $type === 'all') {
                $query->where('integrator_commission_paid', $status === 'paid');
            }
            
            if ($type === 'partner' || $type === 'all') {
                $query->where('partner_commission_paid', $status === 'paid');
            }
        }
        
        $transactions = $query->get();
        
        // Générer le CSV
        $csv = $this->reportService->exportToCsv($transactions);
        
        // Créer un nom de fichier
        $filename = 'commissions_' . $startDate . '_to_' . $endDate . '.csv';
        
        // Créer une réponse avec le contenu CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        return response($csv, 200, $headers);
    }

    /**
     * Recalcule les commissions pour une transaction.
     *
     * @param Transaction $transaction
     * @return \Illuminate\Http\Response
     */
    public function recalculate(Transaction $transaction)
    {
        if ($this->commissionService->calculateAndSaveCommissions($transaction)) {
            return redirect()->back()
                ->with('success', 'Commissions recalculées avec succès.');
        }
        
        return redirect()->back()
            ->with('error', 'Impossible de recalculer les commissions.');
    }
    /**
     * Show the form for creating a new commission plan.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('commission-plans.create');
    }

    /**
     * Store a newly created commission plan in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // For now, just redirect back with a success message.
        // Actual logic for storing a commission plan would go here.
        return redirect()->route('admin.commission-plans.index')->with('success', 'Commission plan created successfully.');
    }

    /**
     * Show the form for editing the specified commission plan.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // Placeholder for edit logic
        return view('commission-plans.edit', ['commissionPlan' => []]); // Replace with actual data
    }

    /**
     * Update the specified commission plan in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Placeholder for update logic
        return redirect()->route('admin.commission-plans.index')->with('success', 'Commission plan updated successfully.');
    }

    /**
     * Remove the specified commission plan from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Placeholder for destroy logic
        return redirect()->route('admin.commission-plans.index')->with('success', 'Commission plan deleted successfully.');
    }
}