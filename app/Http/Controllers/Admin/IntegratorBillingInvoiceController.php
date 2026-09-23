<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integrator;
use App\Models\IntegratorBillingInvoice;
use App\Models\IntegratorBillingLineItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin Controller for managing Integrator Billing Invoices
 */
class IntegratorBillingInvoiceController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('permission:view_integrator_invoices', ['only' => ['index', 'show']]);
        $this->middleware('permission:create_integrator_invoices', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit_integrator_invoices', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete_integrator_invoices', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        $query = IntegratorBillingInvoice::with(['integrator', 'contractProfile']);

        // Filter by integrator
        if ($request->has('integrator_id') && $request->integrator_id) {
            $query->where('integrator_id', $request->integrator_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by invoice type
        if ($request->has('invoice_type') && $request->invoice_type) {
            $query->where('invoice_type', $request->invoice_type);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('issue_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        // Search by invoice number
        if ($request->has('search') && $request->search) {
            $query->where('invoice_number', 'like', '%' . $request->search . '%');
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate(config('app.pagination_limit', 15));

        $integrators = Integrator::active()->orderBy('name')->get();

        return view('admin.integrator-invoices.index', compact('invoices', 'integrators'));
    }

    /**
     * Display the specified invoice.
     */
    public function show(IntegratorBillingInvoice $invoice)
    {
        $invoice->load(['integrator', 'contractProfile', 'creator', 'payer', 'lineItems']);
        
        return view('admin.integrator-invoices.show', compact('invoice'));
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Request $request, IntegratorBillingInvoice $invoice)
    {
        $request->validate([
            'payment_method' => 'nullable|string|max:255',
            'payment_reference' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string',
        ]);

        try {
            $invoice->markAsPaid(
                $request->payment_method,
                $request->payment_reference,
                auth()->id()
            );

            if ($request->payment_notes) {
                $invoice->update(['payment_notes' => $request->payment_notes]);
            }

            Log::info('Invoice marked as paid', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'paid_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', 'Facture marquée comme payée.');
        } catch (\Exception $e) {
            Log::error('Error marking invoice as paid', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Cancel invoice.
     */
    public function cancel(Request $request, IntegratorBillingInvoice $invoice)
    {
        $request->validate([
            'notes' => 'nullable|string',
        ]);

        try {
            if (in_array($invoice->status, ['paid', 'refunded'])) {
                return redirect()->back()
                    ->with('error', 'Impossible d\'annuler une facture payée ou remboursée.');
            }

            $invoice->markAsCancelled($request->notes);

            Log::info('Invoice cancelled', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'cancelled_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', 'Facture annulée.');
        } catch (\Exception $e) {
            Log::error('Error cancelling invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de l\'annulation: ' . $e->getMessage());
        }
    }

    /**
     * Get line items for a contract (AJAX).
     */
    public function getLineItems(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:integrator_contract_profiles,id',
            'type' => 'nullable|in:maintenance,terminal,commission',
            'status' => 'nullable|in:pending,calculated',
        ]);

        $query = IntegratorBillingLineItem::where('integrator_contract_profile_id', $request->contract_id);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['pending', 'calculated']);
        }

        $lineItems = $query->orderBy('billing_period_start', 'desc')->get();

        return response()->json([
            'line_items' => $lineItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'type_label' => $item->type_label,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'tax_rate' => $item->tax_rate,
                    'tax_amount' => $item->tax_amount,
                    'total_amount' => $item->total_amount,
                    'currency' => $item->currency,
                    'status' => $item->status,
                    'billing_period' => $item->formatted_period,
                ];
            }),
        ]);
    }
}
