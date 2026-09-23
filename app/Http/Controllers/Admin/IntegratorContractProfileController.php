<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integrator;
use App\Models\IntegratorContractProfile;
use App\Models\IntegratorBillingInvoice;
use App\Services\IntegratorBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Admin Controller for managing Integrator Contract Profiles
 * 
 * Handles CRUD operations for integrator billing contracts including:
 * - Creating and editing contract profiles
 * - Managing billing configurations (maintenance, terminal, commission fees)
 * - Generating and managing invoices
 * - Viewing billing summaries
 */
class IntegratorContractProfileController extends Controller
{
    /**
     * @var IntegratorBillingService
     */
    protected IntegratorBillingService $billingService;

    /**
     * Constructor
     */
    public function __construct(IntegratorBillingService $billingService)
    {
        $this->billingService = $billingService;
        
        $this->middleware('permission:view_integrator_contracts', ['only' => ['index', 'show']]);
        $this->middleware('permission:create_integrator_contracts', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit_integrator_contracts', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete_integrator_contracts', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of contract profiles.
     */
    public function index(Request $request)
    {
        $query = IntegratorContractProfile::with(['integrator', 'creator']);

        // Filter by integrator
        if ($request->has('integrator_id') && $request->integrator_id) {
            $query->where('integrator_id', $request->integrator_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by maintenance fee enabled
        if ($request->has('maintenance_fee') && $request->maintenance_fee) {
            $query->where('maintenance_fee_enabled', true);
        }

        // Filter by terminal fee enabled
        if ($request->has('terminal_fee') && $request->terminal_fee) {
            $query->where('terminal_fee_enabled', true);
        }

        // Filter by transaction commission enabled
        if ($request->has('commission') && $request->commission) {
            $query->where('transaction_commission_enabled', true);
        }

        // Search by contract number or name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $contracts = $query->orderBy('created_at', 'desc')
            ->paginate(config('app.pagination_limit', 15));

        $integrators = Integrator::active()->orderBy('name')->get();

        return view('admin.integrator-contracts.index', compact('contracts', 'integrators'));
    }

    /**
     * Show the form for creating a new contract profile.
     */
    public function create()
    {
        $integrators = Integrator::active()->orderBy('name')->get();
        
        return view('admin.integrator-contracts.create', compact('integrators'));
    }

    /**
     * Store a newly created contract profile.
     */
    public function store(Request $request)
    {
        $validator = $this->validateContractRequest($request);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $contract = IntegratorContractProfile::create([
                'integrator_id' => $request->integrator_id,
                'contract_number' => IntegratorContractProfile::generateContractNumber(),
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? 'draft',
                'currency' => $request->currency ?? 'EUR',
                
                // Maintenance Fee
                'maintenance_fee_enabled' => $request->has('maintenance_fee_enabled'),
                'maintenance_fee_amount' => $request->maintenance_fee_amount ?? 0,
                'maintenance_fee_period' => $request->maintenance_fee_period,
                'maintenance_fee_start_date' => $request->maintenance_fee_start_date,
                
                // Terminal Fee
                'terminal_fee_enabled' => $request->has('terminal_fee_enabled'),
                'terminal_fee_amount' => $request->terminal_fee_amount ?? 0,
                'terminal_fee_period' => $request->terminal_fee_period,
                'terminal_fee_minimum' => $request->terminal_fee_minimum ?? 0,
                'terminal_fee_free_count' => $request->terminal_fee_free_count ?? 0,
                
                // Transaction Commission
                'transaction_commission_enabled' => $request->has('transaction_commission_enabled'),
                'transaction_commission_type' => $request->transaction_commission_type,
                'transaction_commission_percentage' => $request->transaction_commission_percentage ?? 0,
                'transaction_commission_fixed_amount' => $request->transaction_commission_fixed_amount ?? 0,
                'transaction_commission_min_amount' => $request->transaction_commission_min_amount ?? 0,
                'transaction_commission_max_amount' => $request->transaction_commission_max_amount,
                
                // Contract dates
                'contract_start_date' => $request->contract_start_date,
                'contract_end_date' => $request->contract_end_date,
                'auto_renewal' => $request->has('auto_renewal'),
                
                // Metadata
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // Set next maintenance due date if enabled
            if ($contract->maintenance_fee_enabled && $contract->maintenance_fee_start_date) {
                $nextDueDate = $contract->calculateNextMaintenanceDueDate();
                if ($nextDueDate) {
                    $contract->update(['maintenance_fee_next_due_date' => $nextDueDate]);
                }
            }

            Log::info('Integrator contract created', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'integrator_id' => $contract->integrator_id,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.integrator-contracts.show', $contract)
                ->with('success', 'Contrat créé avec succès.');
        } catch (\Exception $e) {
            Log::error('Error creating integrator contract', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la création du contrat: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified contract profile.
     */
    public function show(IntegratorContractProfile $contract)
    {
        $contract->load(['integrator', 'creator', 'updater']);
        
        // Get recent invoices
        $invoices = $contract->invoices()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get billing summary
        $billingSummary = $this->billingService->getBillingSummary(
            $contract->integrator,
            now()->year,
            now()->month
        );

        // Get active terminal count
        $activeTerminalCount = $this->billingService->getActiveTerminalCount($contract->integrator_id);

        return view('admin.integrator-contracts.show', compact(
            'contract',
            'invoices',
            'billingSummary',
            'activeTerminalCount'
        ));
    }

    /**
     * Show the form for editing the specified contract.
     */
    public function edit(IntegratorContractProfile $contract)
    {
        $contract->load(['integrator']);
        $integrators = Integrator::active()->orderBy('name')->get();

        return view('admin.integrator-contracts.edit', compact('contract', 'integrators'));
    }

    /**
     * Update the specified contract profile.
     */
    public function update(Request $request, IntegratorContractProfile $contract)
    {
        $validator = $this->validateContractRequest($request, $contract->id);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $contract->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
                'currency' => $request->currency ?? 'EUR',
                
                // Maintenance Fee
                'maintenance_fee_enabled' => $request->has('maintenance_fee_enabled'),
                'maintenance_fee_amount' => $request->maintenance_fee_amount ?? 0,
                'maintenance_fee_period' => $request->maintenance_fee_period,
                'maintenance_fee_start_date' => $request->maintenance_fee_start_date,
                
                // Terminal Fee
                'terminal_fee_enabled' => $request->has('terminal_fee_enabled'),
                'terminal_fee_amount' => $request->terminal_fee_amount ?? 0,
                'terminal_fee_period' => $request->terminal_fee_period,
                'terminal_fee_minimum' => $request->terminal_fee_minimum ?? 0,
                'terminal_fee_free_count' => $request->terminal_fee_free_count ?? 0,
                
                // Transaction Commission
                'transaction_commission_enabled' => $request->has('transaction_commission_enabled'),
                'transaction_commission_type' => $request->transaction_commission_type,
                'transaction_commission_percentage' => $request->transaction_commission_percentage ?? 0,
                'transaction_commission_fixed_amount' => $request->transaction_commission_fixed_amount ?? 0,
                'transaction_commission_min_amount' => $request->transaction_commission_min_amount ?? 0,
                'transaction_commission_max_amount' => $request->transaction_commission_max_amount,
                
                // Contract dates
                'contract_start_date' => $request->contract_start_date,
                'contract_end_date' => $request->contract_end_date,
                'auto_renewal' => $request->has('auto_renewal'),
                
                // Metadata
                'notes' => $request->notes,
                'updated_by' => auth()->id(),
            ]);

            Log::info('Integrator contract updated', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('admin.integrator-contracts.show', $contract)
                ->with('success', 'Contrat mis à jour avec succès.');
        } catch (\Exception $e) {
            Log::error('Error updating integrator contract', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour du contrat: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified contract profile.
     */
    public function destroy(IntegratorContractProfile $contract)
    {
        try {
            // Check if there are associated invoices
            if ($contract->invoices()->exists()) {
                return redirect()->back()
                    ->with('error', 'Impossible de supprimer ce contrat car il possède des factures associées.');
            }

            $contractNumber = $contract->contract_number;
            $contract->delete();

            Log::info('Integrator contract deleted', [
                'contract_number' => $contractNumber,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.integrator-contracts.index')
                ->with('success', 'Contrat supprimé avec succès.');
        } catch (\Exception $e) {
            Log::error('Error deleting integrator contract', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression du contrat: ' . $e->getMessage());
        }
    }

    /**
     * Process billing for a contract.
     */
    public function processBilling(Request $request, IntegratorContractProfile $contract)
    {
        $request->validate([
            'billing_type' => 'required|in:maintenance,terminal,all',
        ]);

        try {
            $results = [];
            
            if ($request->billing_type === 'maintenance' || $request->billing_type === 'all') {
                $results['maintenance'] = $this->billingService->processMaintenanceFee($contract);
            }
            
            if ($request->billing_type === 'terminal' || $request->billing_type === 'all') {
                $results['terminal'] = $this->billingService->processTerminalFee($contract);
            }

            return redirect()->back()
                ->with('success', 'Facturation traitée avec succès.');
        } catch (\Exception $e) {
            Log::error('Error processing billing', [
                'contract_id' => $contract->id,
                'billing_type' => $request->billing_type,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors du traitement de la facturation: ' . $e->getMessage());
        }
    }

    /**
     * Generate invoice for a contract.
     */
    public function generateInvoice(Request $request, IntegratorContractProfile $contract)
    {
        $request->validate([
            'invoice_type' => 'required|in:maintenance,terminal,commission,consolidated',
            'line_item_ids' => 'required_if:invoice_type,maintenance,terminal,commission|array',
            'billing_period_start' => 'nullable|date',
            'billing_period_end' => 'nullable|date',
        ]);

        try {
            $lineItemIds = $request->line_item_ids ?? [];
            $billingPeriodStart = $request->billing_period_start ? \Carbon\Carbon::parse($request->billing_period_start) : null;
            $billingPeriodEnd = $request->billing_period_end ? \Carbon\Carbon::parse($request->billing_period_end) : null;

            if ($request->invoice_type === 'consolidated') {
                $invoice = $this->billingService->generateConsolidatedInvoice(
                    $contract,
                    $billingPeriodStart,
                    $billingPeriodEnd
                );
            } else {
                $invoice = $this->billingService->generateInvoice(
                    $contract,
                    $lineItemIds,
                    $request->invoice_type,
                    $billingPeriodStart,
                    $billingPeriodEnd
                );
            }

            Log::info('Invoice generated', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'contract_id' => $contract->id,
            ]);

            return redirect()->route('admin.integrator-invoices.show', $invoice)
                ->with('success', 'Facture générée avec succès.');
        } catch (\Exception $e) {
            Log::error('Error generating invoice', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la génération de la facture: ' . $e->getMessage());
        }
    }

    /**
     * Validate contract request.
     */
    protected function validateContractRequest(Request $request, ?int $contractId = null): \Illuminate\Validation\Validator
    {
        $rules = [
            'integrator_id' => 'required|exists:integrators,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,suspended,terminated',
            'currency' => 'required|string|size:3',
            
            // Maintenance Fee
            'maintenance_fee_enabled' => 'nullable',
            'maintenance_fee_amount' => 'required_if:maintenance_fee_enabled,1|numeric|min:0',
            'maintenance_fee_period' => 'required_if:maintenance_fee_enabled,1|in:monthly,quarterly,yearly',
            'maintenance_fee_start_date' => 'nullable|date',
            
            // Terminal Fee
            'terminal_fee_enabled' => 'nullable',
            'terminal_fee_amount' => 'required_if:terminal_fee_enabled,1|numeric|min:0',
            'terminal_fee_period' => 'required_if:terminal_fee_enabled,1|in:monthly,quarterly,yearly',
            'terminal_fee_minimum' => 'required_if:terminal_fee_enabled,1|integer|min:0',
            'terminal_fee_free_count' => 'required_if:terminal_fee_enabled,1|integer|min:0',
            
            // Transaction Commission
            'transaction_commission_enabled' => 'nullable',
            'transaction_commission_type' => 'required_if:transaction_commission_enabled,1|in:percentage,fixed,combined',
            'transaction_commission_percentage' => 'required_if:transaction_commission_type,percentage,combined|numeric|min:0|max:100',
            'transaction_commission_fixed_amount' => 'required_if:transaction_commission_type,fixed,combined|numeric|min:0',
            'transaction_commission_min_amount' => 'required_if:transaction_commission_enabled,1|numeric|min:0',
            'transaction_commission_max_amount' => 'nullable|numeric|min:0',
            
            // Contract dates
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
            'auto_renewal' => 'nullable',
            
            // Metadata
            'notes' => 'nullable|string',
        ];

        return Validator::make($request->all(), $rules);
    }
}
