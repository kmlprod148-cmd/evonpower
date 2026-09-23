<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditRecharge;
use App\Services\CreditRechargeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminCreditRechargeController extends Controller
{
    protected CreditRechargeService $creditRechargeService;

    public function __construct(CreditRechargeService $creditRechargeService)
    {
        $this->middleware('auth');
        $this->middleware('role:admin|super_admin');
        $this->creditRechargeService = $creditRechargeService;
    }

    /**
     * Affiche la page principale de gestion des crédits (hub)
     */
    public function dashboard(Request $request)
    {
        // Filtres de date
        $dateFrom = $request->input('date_from', \Carbon\Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', \Carbon\Carbon::now()->format('Y-m-d'));
        
        $dateFromCarbon = \Carbon\Carbon::parse($dateFrom);
        $dateToCarbon = \Carbon\Carbon::parse($dateTo)->endOfDay();

        // Statistiques rapides
        $stats = [
            'total' => CreditRecharge::count(),
            'pending' => CreditRecharge::where('status', 'pending')
                ->where('payment_method', 'offline')
                ->count(),
            'completed' => CreditRecharge::where('status', 'completed')->count(),
            'failed' => CreditRecharge::where('status', 'failed')->count(),
            'total_amount' => CreditRecharge::where('status', 'completed')->sum('amount'),
            'pending_amount' => CreditRecharge::where('status', 'pending')
                ->where('payment_method', 'offline')
                ->sum('amount'),
        ];

        // Statistiques détaillées pour la période
        $detailedStats = $this->getDetailedStatistics($dateFromCarbon, $dateToCarbon);

        // Dernières recharges
        $recentRecharges = CreditRecharge::with(['user', 'creditPack'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Top clients par montant rechargé
        $topClients = CreditRecharge::where('status', 'completed')
            ->whereBetween('created_at', [$dateFromCarbon, $dateToCarbon])
            ->select('user_id', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total_amount'), \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->with('user:id,name,email')
            ->get();

        // Statistiques par méthode de paiement
        $byPaymentMethod = CreditRecharge::whereBetween('created_at', [$dateFromCarbon, $dateToCarbon])
            ->where('status', 'completed')
            ->select('payment_method', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'), \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Évolution quotidienne
        $dailyEvolution = CreditRecharge::whereBetween('created_at', [$dateFromCarbon, $dateToCarbon])
            ->where('status', 'completed')
            ->select(\Illuminate\Support\Facades\DB::raw('DATE(created_at) as date'), \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'), \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => (int) $item->count,
                    'total' => (float) $item->total
                ];
            });

        // Statistiques par pack
        $byPack = CreditRecharge::whereBetween('created_at', [$dateFromCarbon, $dateToCarbon])
            ->where('status', 'completed')
            ->whereNotNull('credit_pack_id')
            ->select('credit_pack_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'), \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->groupBy('credit_pack_id')
            ->with('creditPack:id,name,amount')
            ->orderByDesc('total')
            ->get();

        return view('admin.credit-recharges.dashboard', compact(
            'stats', 
            'recentRecharges', 
            'detailedStats',
            'topClients',
            'byPaymentMethod',
            'dailyEvolution',
            'byPack',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Obtient les statistiques détaillées pour une période
     */
    private function getDetailedStatistics($dateFrom, $dateTo): array
    {
        $query = CreditRecharge::whereBetween('created_at', [$dateFrom, $dateTo]);

        return [
            'total' => $query->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'cancelled' => (clone $query)->where('status', 'canceled')->count(),
            'total_amount' => (float) (clone $query)->where('status', 'completed')->sum('amount'),
            'pending_amount' => (float) (clone $query)->where('status', 'pending')->sum('amount'),
            'average_amount' => (clone $query)->where('status', 'completed')->avg('amount') ?? 0,
            'unique_clients' => (clone $query)->distinct('user_id')->count('user_id'),
        ];
    }

    /**
     * Affiche toutes les recharges de crédit
     */
    public function index(Request $request)
    {
        $query = CreditRecharge::with(['user', 'wallet', 'processor', 'creditPack'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $recharges = $query->paginate(20);

        // Statistiques pour le dashboard
        $stats = [
            'total' => CreditRecharge::count(),
            'pending' => CreditRecharge::where('status', 'pending')
                ->where('payment_method', 'offline')
                ->count(),
            'completed' => CreditRecharge::where('status', 'completed')->count(),
            'failed' => CreditRecharge::where('status', 'failed')->count(),
            'total_amount' => CreditRecharge::where('status', 'completed')->sum('amount'),
            'pending_amount' => CreditRecharge::where('status', 'pending')
                ->where('payment_method', 'offline')
                ->sum('amount'),
        ];

        return view('admin.credit-recharges.index', compact('recharges', 'stats'));
    }

    /**
     * Affiche les recharges en attente d'approbation
     */
    public function pending(Request $request)
    {
        $query = CreditRecharge::with(['user', 'wallet', 'creditPack'])
            ->where('status', 'pending')
            ->where('payment_method', 'offline')
            ->orderBy('created_at', 'desc');

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $recharges = $query->paginate(20);

        // Statistiques
        $stats = [
            'total_pending' => CreditRecharge::where('status', 'pending')
                ->where('payment_method', 'offline')
                ->count(),
            'total_amount' => CreditRecharge::where('status', 'pending')
                ->where('payment_method', 'offline')
                ->sum('amount'),
        ];

        return view('admin.credit-recharges.pending', compact('recharges', 'stats'));
    }

    /**
     * Affiche les détails d'une recharge
     */
    public function show(CreditRecharge $creditRecharge)
    {
        $creditRecharge->load(['user', 'wallet', 'processor', 'creditPack']);

        return view('admin.credit-recharges.show', compact('creditRecharge'));
    }

    /**
     * Confirme une recharge offline
     */
    public function confirm(CreditRecharge $creditRecharge, Request $request)
    {
        $admin = Auth::user();

        try {
            $result = $this->creditRechargeService->confirmOfflineRecharge(
                $creditRecharge,
                $admin,
                $request->only(['notes'])
            );

            if ($result['success']) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $result['message'],
                        'recharge' => $result['recharge'] ?? null
                    ]);
                }

                return redirect()->route('admin.credit-recharges.pending')
                    ->with('success', $result['message']);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return redirect()->back()
                ->with('error', $result['message']);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la confirmation de la recharge', [
                'recharge_id' => $creditRecharge->id,
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la confirmation: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erreur lors de la confirmation de la recharge');
        }
    }

    /**
     * Rejette une recharge offline
     */
    public function reject(CreditRecharge $creditRecharge, Request $request)
    {
        $admin = Auth::user();

        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            $result = $this->creditRechargeService->rejectOfflineRecharge(
                $creditRecharge,
                $admin,
                $request->reason
            );

            if ($result['success']) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $result['message']
                    ]);
                }

                return redirect()->route('admin.credit-recharges.pending')
                    ->with('success', $result['message']);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return redirect()->back()
                ->with('error', $result['message']);

        } catch (\Exception $e) {
            Log::error('Erreur lors du rejet de la recharge', [
                'recharge_id' => $creditRecharge->id,
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors du rejet: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erreur lors du rejet de la recharge');
        }
    }

    /**
     * Affiche les détails complets de la balance crédit d'un client
     */
    public function clientBalanceDetails($userId)
    {
        $user = \App\Models\User::with(['wallet', 'roles'])->findOrFail($userId);
        
        // Vérifier que l'utilisateur a un wallet
        if (!$user->wallet) {
            return redirect()->back()
                ->with('error', 'Ce client n\'a pas de wallet');
        }
        
        $wallet = $user->wallet;
        
        // Statistiques globales
        $stats = [
            'total_recharged' => CreditRecharge::where('user_id', $userId)
                ->where('status', 'completed')
                ->sum('amount'),
            'recharge_count' => CreditRecharge::where('user_id', $userId)
                ->where('status', 'completed')
                ->count(),
            'total_spent' => \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'debit')
                ->where('status', 'completed')
                ->sum('amount'),
            'transaction_count' => \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'debit')
                ->where('status', 'completed')
                ->count(),
            'pending_amount' => CreditRecharge::where('user_id', $userId)
                ->where('status', 'pending')
                ->sum('amount'),
            'pending_count' => CreditRecharge::where('user_id', $userId)
                ->where('status', 'pending')
                ->count(),
        ];
        
        // Statistiques mensuelles
        $monthlyStats = [
            'recharged' => CreditRecharge::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'spent' => \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'debit')
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];
        
        // Historique des recharges
        $recharges = CreditRecharge::where('user_id', $userId)
            ->with(['creditPack', 'processor'])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'recharges_page');
        
        // Historique des transactions wallet
        $transactions = \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'transactions_page');
        
        return view('admin.credit-recharges.client-balance-details', compact(
            'user',
            'wallet',
            'stats',
            'monthlyStats',
            'recharges',
            'transactions'
        ));
    }
}
