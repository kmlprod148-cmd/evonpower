<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CreditRecharge;
use App\Services\CashPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Contrôleur pour la gestion des paiements en espèces
 * 
 * Permet aux opérateurs et administrateurs d'enregistrer les paiements
 * en espèces effectués par les clients et d'ajouter des crédits à leur wallet.
 */
class CashPaymentController extends Controller
{
    protected CashPaymentService $cashPaymentService;

    public function __construct(CashPaymentService $cashPaymentService)
    {
        $this->cashPaymentService = $cashPaymentService;
        $this->middleware('auth');
        $this->middleware('can:manage_wallet');
    }

    /**
     * Affiche la liste des paiements en espèces
     * 
     * GET /admin/cash-payments
     */
    public function index(Request $request)
    {
        $query = CreditRecharge::where('payment_method', CashPaymentService::PAYMENT_METHOD)
            ->with(['user', 'wallet', 'processor']);

        // Filtres
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $cashPayments = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        // Statistiques
        $stats = $this->cashPaymentService->getCashPaymentStatistics(
            null,
            $request->date_from,
            $request->date_to
        );

        return view('admin.cash-payments.index', compact('cashPayments', 'stats'));
    }

    /**
     * Affiche le formulaire de création
     * 
     * GET /admin/cash-payments/create
     */
    public function create(Request $request)
    {
        $client = null;
        
        if ($request->has('client_id')) {
            $client = User::find($request->client_id);
        }

        $suggestedAmounts = CashPaymentService::SUGGESTED_AMOUNTS;

        return view('admin.cash-payments.create', compact('client', 'suggestedAmounts'));
    }

    /**
     * Enregistre un nouveau paiement en espèces
     * 
     * POST /admin/cash-payments
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1|max:10000',
            'currency' => 'required|in:MAD,EUR,USD',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $client = User::findOrFail($request->client_id);
            
            $recharge = $this->cashPaymentService->createCashPayment(
                $client,
                (float) $request->amount,
                $request->currency,
                auth()->user(),
                [
                    'description' => $request->description,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            // Générer le reçu
            $receipt = $this->cashPaymentService->generateReceipt($recharge);

            Log::info('CashPayment created via controller', [
                'recharge_id' => $recharge->id,
                'client_id' => $client->id,
                'amount' => $request->amount,
                'currency' => $request->currency,
            ]);

            return redirect()
                ->route('admin.cash-payments.show', $recharge->id)
                ->with('success', 'Paiement en espèces enregistré avec succès!')
                ->with('receipt', $receipt);

        } catch (\Exception $e) {
            Log::error('CashPayment creation failed', [
                'error' => $e->getMessage(),
                'client_id' => $request->client_id,
                'amount' => $request->amount,
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'enregistrement: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'un paiement
     * 
     * GET /admin/cash-payments/{id}
     */
    public function show(int $id)
    {
        $cashPayment = CreditRecharge::where('payment_method', CashPaymentService::PAYMENT_METHOD)
            ->with(['user', 'wallet', 'processor'])
            ->findOrFail($id);

        $receipt = $this->cashPaymentService->generateReceipt($cashPayment);

        return view('admin.cash-payments.show', compact('cashPayment', 'receipt'));
    }

    /**
     * Annule un paiement en espèces
     * 
     * POST /admin/cash-payments/{id}/cancel
     */
    public function cancel(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:10|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator);
        }

        try {
            $cashPayment = CreditRecharge::where('payment_method', CashPaymentService::PAYMENT_METHOD)
                ->findOrFail($id);

            $this->cashPaymentService->cancelCashPayment(
                $cashPayment,
                $request->reason,
                auth()->user()
            );

            return redirect()
                ->route('admin.cash-payments.show', $id)
                ->with('success', 'Paiement annulé avec succès. Les crédits ont été déduits du wallet.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'annulation: ' . $e->getMessage());
        }
    }

    /**
     * API: Recherche un client
     * 
     * GET /api/admin/cash-payments/search-clients
     */
    public function searchClients(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['clients' => []]);
        }

        $clients = User::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone', 'balance']);

        return response()->json(['clients' => $clients]);
    }

    /**
     * API: Vérifie un paiement avant enregistrement
     * 
     * POST /api/admin/cash-payments/validate
     */
    public function validatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|in:MAD,EUR,USD',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validation = $this->cashPaymentService->validateCashPayment(
            (float) $request->amount,
            $request->currency
        );

        return response()->json($validation);
    }

    /**
     * API: Retourne les statistiques des paiements
     * 
     * GET /api/admin/cash-payments/statistics
     */
    public function statistics(Request $request)
    {
        $stats = $this->cashPaymentService->getCashPaymentStatistics(
            null,
            $request->get('date_from'),
            $request->get('date_to')
        );

        return response()->json($stats);
    }

    /**
     * Télécharge le reçu au format PDF
     * 
     * GET /admin/cash-payments/{id}/receipt
     */
    public function downloadReceipt(int $id)
    {
        $cashPayment = CreditRecharge::where('payment_method', CashPaymentService::PAYMENT_METHOD)
            ->with(['user', 'wallet', 'processor'])
            ->findOrFail($id);

        $receipt = $this->cashPaymentService->generateReceipt($cashPayment);

        // Ici, vous pourriez générer un PDF avec something comme dompdf
        // Pour l'instant, on retourne JSON
        return response()->json([
            'receipt' => $receipt,
            'printable' => true,
        ]);
    }

    /**
     * Liste les paiements en attente
     * 
     * GET /admin/cash-payments/pending
     */
    public function pending()
    {
        $pendingPayments = $this->cashPaymentService->getPendingCashPayments();

        return view('admin.cash-payments.pending', compact('pendingPayments'));
    }

    /**
     * Traite un paiement en attente
     * 
     * POST /admin/cash-payments/{id}/process
     */
    public function process(int $id)
    {
        try {
            $cashPayment = CreditRecharge::where('payment_method', CashPaymentService::PAYMENT_METHOD)
                ->where('status', CashPaymentService::STATUS_PENDING)
                ->findOrFail($id);

            // Le paiement est déjà traité lors de la création
            // Cette méthode pourrait être utilisée pour des validations supplémentaires
            
            return redirect()
                ->route('admin.cash-payments.show', $id)
                ->with('success', 'Paiement traité avec succès');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
