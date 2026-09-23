<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FinancialCollectionService;
use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Contrôleur Admin pour la Collecte Financière
 */
class FinancialCollectionController extends Controller
{
    public function __construct(
        protected FinancialCollectionService $collectionService
    ) {}

    /**
     * Tableau de bord analytique
     * 
     * GET /api/admin/financial/dashboard
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $startDate = $request->has('start_date') 
            ? Carbon::parse($request->start_date) 
            : null;
        $endDate = $request->has('end_date') 
            ? Carbon::parse($request->end_date) 
            : null;

        try {
            $dashboard = $this->collectionService->getAnalyticsDashboard($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $dashboard,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['message' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Liste des factures
     * 
     * GET /api/admin/financial/invoices
     */
    public function getInvoices(Request $request): JsonResponse
    {
        $query = BillingInvoice::with(['billable', 'payments']);

        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('billable', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Détails d'une facture
     * 
     * GET /api/admin/financial/invoices/{id}
     */
    public function getInvoiceDetails(int $id): JsonResponse
    {
        $invoice = BillingInvoice::with(['billable', 'payments', 'creator'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * Créer une facture
     * 
     * POST /api/admin/financial/invoices
     */
    public function createInvoice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::findOrFail($request->user_id);
            
            $invoice = $this->collectionService->createInvoice($user, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Facture créée avec succès',
                'data' => $invoice,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['message' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Paiements d'une facture
     * 
     * POST /api/admin/financial/invoices/{id}/pay
     */
    public function processPayment(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:stripe,card,bank_transfer,wallet',
            'reference' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $invoice = BillingInvoice::findOrFail($id);
            
            $payment = $this->collectionService->processPayment(
                $invoice,
                $request->payment_method,
                $request->all()
            );

            return response()->json([
                'success' => $payment->isCompleted(),
                'message' => $payment->isCompleted() ? 'Paiement traité avec succès' : 'Paiement échoué',
                'data' => [
                    'payment' => $payment,
                    'invoice' => $invoice->fresh(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['message' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Envoyer un rappel
     * 
     * POST /api/admin/financial/invoices/{id}/remind
     */
    public function sendReminder(int $id): JsonResponse
    {
        try {
            $invoice = BillingInvoice::findOrFail($id);
            
            $this->collectionService->sendPaymentReminder($invoice);

            return response()->json([
                'success' => true,
                'message' => 'Rappel envoyé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['message' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Liste des paiements
     * 
     * GET /api/admin/financial/payments
     */
    public function getPayments(Request $request): JsonResponse
    {
        $query = BillingPayment::with(['billingInvoice', 'creator']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Générer un rapport financier
     * 
     * GET /api/admin/financial/reports
     */
    public function generateReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'group_by' => 'nullable|string|in:day,week,month,year',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $report = $this->collectionService->generateFinancialReport(
                Carbon::parse($request->start_date),
                Carbon::parse($request->end_date),
                $request->group_by ?? 'day'
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['message' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Traiter les rappels automatiques
     * 
     * POST /api/admin/financial/process-reminders
     */
    public function processReminders(): JsonResponse
    {
        try {
            $count = $this->collectionService->processAutomaticReminders();

            return response()->json([
                'success' => true,
                'message' => "{$count} rappels traités",
                'data' => ['count' => $count],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['message' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Marquer les factures en retard
     * 
     * POST /api/admin/financial/mark-overdue
     */
    public function markOverdue(): JsonResponse
    {
        try {
            $count = $this->collectionService->markOverdueInvoices();

            return response()->json([
                'success' => true,
                'message' => "{$count} factures marquées en retard",
                'data' => ['count' => $count],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['message' => $e->getMessage()],
            ], 500);
        }
    }
}
