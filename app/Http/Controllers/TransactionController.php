<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Services\TransactionQueryService;
use App\Services\TransactionBalanceService;
use App\Services\TransactionPresentationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * TransactionController - Handles transaction listing, viewing, and balance operations.
 * 
 * Responsibilities:
 * - Display user transactions (wallet + reservation based)
 * - Show transaction details
 * - Export transactions
 * - Balance operations via API
 * 
 * @property TransactionQueryService $queryService
 * @property TransactionBalanceService $balanceService
 * @property TransactionPresentationService $presentationService
 */
class TransactionController extends Controller
{
    /**
     * Default pagination per page
     */
    public const DEFAULT_PER_PAGE = 20;

    /**
     * Float comparison threshold
     */
    public const BALANCE_THRESHOLD = 0.01;

    /**
     * @var TransactionQueryService
     */
    protected $queryService;

    /**
     * @var TransactionBalanceService
     */
    protected $balanceService;

    /**
     * @var TransactionPresentationService
     */
    protected $presentationService;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        TransactionQueryService $queryService,
        TransactionBalanceService $balanceService,
        TransactionPresentationService $presentationService
    ) {
        $this->queryService = $queryService;
        $this->balanceService = $balanceService;
        $this->presentationService = $presentationService;
    }

    /**
     * Display a listing of the user's transactions.
     * Includes both wallet transactions and reservation-based transactions.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                Log::error('TransactionController: Unauthenticated user access attempt');
                return redirect()->route('login');
            }
            
            $wallet = $user->getOrCreateWallet();
            
            // Synchronize balance with approved transactions (only for admin users)
            if ($user instanceof User) {
                $this->synchronizeWalletBalance($user, $wallet);
            }

            // Get filter parameters
            $filters = $this->getFiltersFromRequest($request);

            // Retrieve transactions using the same hierarchy-aware scope for all data sets
            $reservationTransactions = $this->getReservationTransactions($user, $filters);
            $walletTransactions = $this->getWalletTransactions($wallet, $filters, $reservationTransactions);

            // Prepare transactions for display
            $walletTransactionsList = $this->presentationService->prepareWalletTransactions($walletTransactions);
            $reservationTransactionsList = $this->presentationService->prepareReservationTransactions($reservationTransactions);

            // Apply reservation ID filter if present
            if ($filters['reservation_id']) {
                $walletTransactionsList = $this->presentationService->filterByReservationId(
                    $walletTransactionsList, 
                    $filters['reservation_id']
                );
                $reservationTransactionsList = $this->presentationService->filterByReservationId(
                    $reservationTransactionsList, 
                    $filters['reservation_id']
                );
            }

            // Sort by date descending
            $walletTransactionsList = $this->presentationService->sortByDateDescending($walletTransactionsList);
            $reservationTransactionsList = $this->presentationService->sortByDateDescending($reservationTransactionsList);

            // Group by reservation
            $groupedData = $this->presentationService->groupByReservation(
                $walletTransactionsList, 
                $reservationTransactionsList
            );

            // Create pagination
            $currentPage = $request->get('page', 1);
            $walletTransactionsPaginated = $this->presentationService->paginateTransactions(
                $walletTransactionsList,
                $currentPage,
                self::DEFAULT_PER_PAGE,
                'wallet'
            );
            $reservationTransactionsPaginated = $this->presentationService->paginateTransactions(
                $reservationTransactionsList,
                $currentPage,
                self::DEFAULT_PER_PAGE,
                'reservation'
            );

            // Calculate statistics
            $stats = $this->calculateTransactionStats($user, $wallet, $walletTransactionsList, $reservationTransactionsList);

            // Get filter options
            $filterOptions = $this->getFilterOptions($user);

            // Log summary
            Log::info('Transactions loaded', [
                'user_id' => $user->id,
                'role' => $user->getRoleNames()->first(),
                'wallet_transactions_count' => $walletTransactionsList->count(),
                'reservation_transactions_count' => $reservationTransactionsList->count(),
                'wallet_balance' => $wallet->balance,
                'stats_current_balance' => $stats['current_balance'] ?? null,
            ]);

            if ($user->hasRole('integrator')) {
                $integratorId = $this->queryService->resolveIntegratorIdPublic($user);
                Log::info('Integrator transactions debug', [
                    'user_id' => $user->id,
                    'user_integrator_id_column' => $user->integrator_id,
                    'resolved_integrator_id' => $integratorId,
                    'wallet_transactions_count' => $walletTransactionsList->count(),
                    'reservation_transactions_count' => $reservationTransactionsList->count(),
                ]);
            }

            return view('transactions.index', [
                'walletTransactions' => $walletTransactionsList,
                'reservationTransactions' => $reservationTransactionsList,
                'walletTransactionsPaginated' => $walletTransactionsPaginated,
                'reservationTransactionsPaginated' => $reservationTransactionsPaginated,
                'transactionsByReservation' => $groupedData['grouped'],
                'standaloneWalletTransactions' => $groupedData['standalone_wallet'],
                'standaloneReservationTransactions' => $groupedData['standalone_reservation'],
                'transactionTypes' => $filterOptions['transactionTypes'],
                'filters' => $filters,
                'stats' => $stats,
                'wallet' => $wallet,
                'users' => $filterOptions['users'],
                'chargingPoints' => $filterOptions['chargingPoints'],
                'paymentMethods' => $filterOptions['paymentMethods'],
                'transactions' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1),
            ]);
            
        } catch (\Exception $e) {
            Log::error('TransactionController@index: Fatal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return back()->withErrors([
                'error' => 'Une erreur est survenue lors du chargement des transactions.'
            ])->withInput();
        }
    }

    /**
     * Synchronize wallet balance with approved transactions
     */
    protected function synchronizeWalletBalance(User $user, $wallet): void
    {
        // Skip synchronization for integrators: they have a separate Integrator model
        // wallet and a hierarchical scope that the personal-wallet sync doesn't cover.
        // Balance is read via getScopedWalletBalance() which aggregates all scope wallets.
        if ($user->hasRole('integrator')) {
            return;
        }

        try {
            // Ensure transaction details exist
            $this->balanceService->ensureTransactionDetailsExist($user);

            // Recalculate zero-share details
            $this->balanceService->recalculateZeroShareDetails($user);

            // Synchronize balance
            $syncResult = $this->balanceService->synchronizeBalance($user);

            $wallet->refresh();
        } catch (\Exception $e) {
            Log::warning('Balance synchronization failed in index', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get wallet transactions for user
     */
    protected function getWalletTransactions(
        $wallet,
        array $filters,
        ?\Illuminate\Support\Collection $reservationTransactions = null
    ): \Illuminate\Support\Collection
    {
        $user = Auth::user();

        if ($user->hasRole('integrator')) {
            return $this->getIntegratorWalletTransactions($user, $filters, $reservationTransactions);
        }

        $query = $wallet->transactions();

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['type'])) {
            if ($filters['type'] === 'credit') {
                $query->where('type', 'credit');
            } elseif ($filters['type'] === 'debit') {
                $query->where('type', 'debit');
            }
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('description', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['exclude_reservation_wallet_transactions'])) {
            $query->notRelatedToReservations();
        }

        return $query->latest()->get();
    }

    /**
     * Get wallet transactions for integrator scope
     * Includes: integrator's wallets, all operators they created, all partners they created
     */
    protected function getIntegratorWalletTransactions(
        \Illuminate\Contracts\Auth\Authenticatable $user,
        array $filters,
        ?\Illuminate\Support\Collection $reservationTransactions = null
    ): \Illuminate\Support\Collection
    {
        return $this->queryService->getIntegratorWalletTransactions($user, $filters, $reservationTransactions);
    }

    /**
     * Get reservation-based transactions
     */
    protected function getReservationTransactions(\Illuminate\Contracts\Auth\Authenticatable $user, array $filters): \Illuminate\Support\Collection
    {
        $query = $this->queryService->buildRoleBasedQuery($user);
        
        // Apply filters
        $query = $this->queryService->applyFilters($query, $filters);
        
        return $query->latest()->get();
    }

    /**
     * Calculate transaction statistics
     */
    protected function calculateTransactionStats(
        \Illuminate\Contracts\Auth\Authenticatable $user,
        $wallet,
        $walletTransactions,
        $reservationTransactions
    ): array {
        $walletCount      = $walletTransactions->count();
        $reservationCount = $reservationTransactions->count();
        $totalCount       = $walletCount + $reservationCount;

        $successfulReservationTransactions = $reservationTransactions->reject(function ($transaction) {
            $original = $transaction['transaction'] ?? $transaction;
            return in_array(strtolower((string) ($original->status ?? '')), ['failed', 'cancelled'], true);
        });

        $reservationRevenue = (float) $successfulReservationTransactions
            ->sum(function ($transaction) {
                $original = $transaction['transaction'] ?? $transaction;
                // Try price_total, then amount, then reservation.estimated_cost, then the prepared 'amount' key
                return (float) (
                    $original->price_total
                    ?? $original->amount
                    ?? $original->reservation?->estimated_cost
                    ?? $transaction['amount']
                    ?? 0
                );
            });

        // Wallet transactions are prepared arrays ('type' => 'wallet', amount > 0 for credits, < 0 for debits)
        $walletCredits = (float) $walletTransactions
            ->filter(fn ($transaction) => ($transaction['amount'] ?? 0) > 0)
            ->sum(fn ($transaction) => (float) ($transaction['amount'] ?? 0));

        $walletDebits = (float) $walletTransactions
            ->filter(fn ($transaction) => ($transaction['amount'] ?? 0) < 0)
            ->sum(fn ($transaction) => abs((float) ($transaction['amount'] ?? 0)));

        $usageKwh = (float) $successfulReservationTransactions->sum(function ($transaction) {
            $original = $transaction['transaction'] ?? $transaction;
            
            if (!empty($original->energy_consumed_wh)) {
                return ((float) $original->energy_consumed_wh) / 1000;
            }

            if (!is_null($original->start_value) && !is_null($original->stop_value)) {
                return max(0, ((float) $original->stop_value - (float) $original->start_value) / 1000);
            }

            if (!is_null($original->meter_start) && !is_null($original->meter_stop)) {
                return max(0, (float) $original->meter_stop - (float) $original->meter_start);
            }

            return (float) ($original->reservation?->actual_energy ?? 0);
        });

        $scopeWalletBalance = $user->hasRole('integrator')
            ? $this->queryService->getScopedWalletBalance($user)
            : (float) ($wallet->balance ?? 0);

        $totalCredits = $reservationRevenue + $walletCredits;
        $totalDebits = $walletDebits;

        return [
            'total_credits'                => round($totalCredits, 2),
            'total_debits'                 => round($totalDebits, 2),
            'net_amount'                   => round($totalCredits - $totalDebits, 2),
            'current_balance'              => $scopeWalletBalance,
            'calculated_balance'           => $scopeWalletBalance,
            'wallet_balance'               => $scopeWalletBalance,
            'total_transactions'           => $totalCount,
            'wallet_transactions_count'    => $walletCount,
            'reservation_transactions_count' => $reservationCount,
            'reservation_revenue'          => round($reservationRevenue, 2),
            'wallet_credits'               => round($walletCredits, 2),
            'wallet_debits'                => round($walletDebits, 2),
            'usage_kwh'                    => round($usageKwh, 2),
        ];
    }

    /**
     * Get filter options for dropdowns
     */
    protected function getFilterOptions(\Illuminate\Contracts\Auth\Authenticatable $user): array
    {
        $users = collect();
        $chargingPoints = collect();

        try {
            if ($user->hasRole(['integrator', 'partner', 'operator'])) {
                $users = $this->queryService->getScopedUsers($user);
                $chargingPoints = $this->queryService->getScopedChargingPoints($user)
                    ->select('charging_points.id', 'charging_points.name')
                    ->orderBy('charging_points.name')
                    ->get();
            } else {
                if ($user->can('view_users')) {
                    $users = User::select('id', 'name', 'email')->orderBy('name')->get();
                }

                if ($user->can('view_charging_points')) {
                    $chargingPoints = ChargingPoint::select('id', 'name')->orderBy('name')->get();
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to load filter options', ['error' => $e->getMessage()]);
        }

        return [
            'transactionTypes' => ['credit', 'debit'],
            'users' => $users,
            'chargingPoints' => $chargingPoints,
            'paymentMethods' => ['credit_card', 'debit_card', 'paypal', 'bank_transfer', 'cash', 'wallet'],
        ];
    }

    /**
     * Extract filters from request
     */
    protected function getFiltersFromRequest(Request $request): array
    {
        return [
            'type' => $request->get('type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
            'user_id' => $request->get('user_id'),
            'charging_point_id' => $request->get('charging_point_id'),
            'status' => $request->get('status'),
            'payment_method' => $request->get('payment_method'),
            'exclude_reservation_wallet_transactions' => $request->get('exclude_reservation_wallet_transactions', false),
            'reservation_id' => $request->get('reservation_id'),
        ];
    }

    /**
     * Show transaction fees details (AJAX)
     */
    public function feesDetails($id)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }
        
        $transaction = $this->loadFullTransaction($id);
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction non trouvée'
            ], 404);
        }
        
        // Ensure transaction detail exists
        $this->ensureTransactionDetail($transaction);

        $html = view('components.admin-transaction-full-breakdown', [
            'transaction' => $transaction,
            'transactionDetail' => $transaction->transactionDetail,
            'calculationDetails' => $transaction->transactionDetail 
                ? json_decode($transaction->transactionDetail->calculation_details ?? '{}', true) 
                : null,
        ])->render();
        
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }

    /**
     * Load full transaction with relations
     */
    protected function loadFullTransaction($id)
    {
        return Transaction::with([
            'reservation',
            'chargingPoint',
            'chargingPoint.group',
            'chargingPoint.group.partner',
            'chargingPoint.group.partner.integrator',
            'transactionDetail',
            'transactionDetail.adminCreator',
            'transactionDetail.integratorCreator',
            'transactionDetail.operator',
            'repartition',
            'repartitions',
            'businessProfile',
            'user'
        ])->find($id);
    }

    /**
     * Ensure transaction detail exists
     */
    protected function ensureTransactionDetail(Transaction $transaction): void
    {
        if ($transaction->transactionDetail) {
            return;
        }

        $canCreate = $transaction->status === 'completed' || 
            $transaction->status === 'confirmed' ||
            ($transaction->reservation && $this->isReservationApproved($transaction->reservation));

        if (!$canCreate) {
            return;
        }

        try {
            $transactionService = app(\App\Services\ReservationTransactionService::class);
            $result = $transactionService->createRetroactiveTransactionDetail($transaction);

            if ($result['success']) {
                $transaction->refresh();
                $transaction->load([
                    'transactionDetail',
                    'transactionDetail.adminCreator',
                    'transactionDetail.integratorCreator',
                    'transactionDetail.operator'
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to create transaction detail', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if reservation is approved
     */
    protected function isReservationApproved($reservation): bool
    {
        if (!$reservation) {
            return false;
        }

        $status = $reservation->status;
        
        if ($status instanceof \App\Enums\ReservationStatus) {
            return in_array($status->value, ['confirmed', 'completed']);
        }

        return in_array($status, ['confirmed', 'completed']);
    }

    /**
     * Show transaction details
     */
    public function show($id)
    {
        $user = Auth::user();
        $wallet = $user->getOrCreateWallet();

        // Try wallet transaction first, including integrator-visible scope wallets.
        if ($user->hasRole('integrator')) {
            $walletTransaction = $this->queryService
                ->getIntegratorWalletTransactions($user, [])
                ->firstWhere('id', (int) $id);
        } else {
            $walletTransaction = WalletTransaction::with('wallet.owner')
                ->where('id', $id)
                ->where('wallet_id', $wallet->id)
                ->first();
        }
        
        if ($walletTransaction) {
            return view('transactions.show', [
                'transaction' => $walletTransaction,
                'transaction_type' => 'wallet',
                'wallet' => $wallet
            ]);
        }
        
        // Fall back to reservation transaction
        $reservationTransaction = $this->loadFullTransaction($id);
        
        if (!$reservationTransaction) {
            abort(404, 'Transaction non trouvée.');
        }

        // Ensure detail exists and handle access
        $this->ensureTransactionDetail($reservationTransaction);
        $this->handleTransactionAccess($user, $reservationTransaction);

        return view('transactions.show', [
            'transaction' => $reservationTransaction,
            'transaction_type' => 'reservation',
            'wallet' => $wallet,
        ]);
    }

    /**
     * Handle transaction access control
     */
    protected function handleTransactionAccess(User $user, Transaction $transaction): void
    {
        if ($user->hasRole(['admin', 'super_admin'])) {
            return;
        }

        $hasAccess = $this->checkAccessByRole($user, $transaction);
        
        if (!$hasAccess) {
            abort(403, 'Vous n\'avez pas accès à cette transaction.');
        }
    }

    /**
     * Check access by user role — checks ALL applicable roles, grants if any passes.
     * A user may have multiple roles (e.g. integrator + operator).
     */
    protected function checkAccessByRole(User $user, Transaction $transaction): bool
    {
        if ($user->hasRole('integrator') && $this->checkIntegratorAccess($user, $transaction)) {
            return true;
        }

        if ($user->hasRole('partner') && $this->checkPartnerAccess($user, $transaction)) {
            return true;
        }

        if ($user->hasRole('operator') && $this->checkOperatorAccess($user, $transaction)) {
            return true;
        }

        // Regular user: own transaction or own reservation
        return $transaction->user_id == $user->id ||
            ($transaction->reservation && $transaction->reservation->user_id == $user->id);
    }

    /**
     * Check integrator access
     */
    protected function checkIntegratorAccess(User $user, Transaction $transaction): bool
    {
        $integratorId = $user->integrator_id ?? $user->id;
        $cp = $transaction->chargingPoint;

        if ($cp) {
            if ($cp->integrator_id == $integratorId) return true;
            if ($cp->group?->partner?->integrator_id == $integratorId) return true;
            if ($cp->group?->user?->integrator_id == $integratorId) return true;
        }

        if ($transaction->transactionDetail) {
            if ($transaction->transactionDetail->integrator_creator_id == $user->id &&
                ($transaction->transactionDetail->integrator_share_amount ?? 0) > 0) {
                return true;
            }
        }

        return $transaction->reservation?->user?->integrator_id == $integratorId;
    }

    /**
     * Check partner access
     */
    protected function checkPartnerAccess(User $user, Transaction $transaction): bool
    {
        $partnerId = $user->partner_id ?? $user->id;
        $cp = $transaction->chargingPoint;

        if ($cp) {
            if ($cp->partner_id == $partnerId) return true;
            if ($cp->group?->partner_id == $partnerId) return true;
        }

        if ($transaction->transactionDetail) {
            if ($transaction->transactionDetail->operator_id == $user->id &&
                ($transaction->transactionDetail->operator_share_amount ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check operator access
     */
    protected function checkOperatorAccess(User $user, Transaction $transaction): bool
    {
        $cp = $transaction->chargingPoint;

        if ($cp) {
            if (in_array($user->id, [$cp->user_id, $cp->created_by, $cp->created_by_id])) return true;
            if ($cp->group?->user_id == $user->id) return true;
        }

        if ($transaction->transactionDetail) {
            if ($transaction->transactionDetail->operator_id == $user->id &&
                ($transaction->transactionDetail->operator_share_amount ?? 0) > 0) {
                return true;
            }
        }

        return $transaction->reservation?->user_id == $user->id;
    }

    /**
     * Get wallet balance (API)
     */
    public function balance()
    {
        $user = Auth::user();
        
        try {
            $result = $this->balanceService->synchronizeBalance($user);
            $wallet = $result['wallet'];
            $calculatedBalance = $result['calculated_balance'];

            return response()->json([
                'balance' => $wallet->balance,
                'calculated_balance' => $calculatedBalance,
                'formatted_balance' => $wallet->getFormattedBalance(),
                'currency' => $wallet->currency,
                'is_active' => $wallet->is_active,
                'synchronized' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Balance retrieval failed', ['error' => $e->getMessage()]);
            
            $wallet = $user->getOrCreateWallet();
            
            return response()->json([
                'balance' => $wallet->balance,
                'calculated_balance' => $wallet->balance,
                'formatted_balance' => $wallet->getFormattedBalance(),
                'currency' => $wallet->currency,
                'is_active' => $wallet->is_active,
                'synchronized' => false,
            ]);
        }
    }

    /**
     * Get recent transactions for dashboard
     */
    public function recent()
    {
        $user = Auth::user();
        $wallet = $user->getOrCreateWallet();
        
        $recentTransactions = $wallet->transactions()
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'transactions' => $recentTransactions,
            'wallet' => [
                'balance' => $wallet->balance,
                'formatted_balance' => $wallet->getFormattedBalance(),
                'currency' => $wallet->currency,
            ],
        ]);
    }

    /**
     * Get transaction summary
     */
    public function summary()
    {
        try {
            $user = Auth::user();
            $wallet = $user->getOrCreateWallet();
            
            $summary = [
                'current_balance' => $wallet->balance,
                'total_credits' => $wallet->transactions()->where('type', 'credit')->sum('amount'),
                'total_debits' => $wallet->transactions()->where('type', 'debit')->sum('amount'),
                'total_transactions' => $wallet->transactions()->count(),
                'currency' => $wallet->currency ?? 'EUR'
            ];
            
            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du résumé',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transactions via API
     */
    public function api(Request $request)
    {
        try {
            $user = Auth::user();
            $wallet = $user->getOrCreateWallet();
            
            $query = $wallet->transactions()->latest();

            if ($request->get('type')) {
                $query->where('type', $request->get('type'));
            }
            if ($request->get('date_from')) {
                $query->whereDate('created_at', '>=', $request->get('date_from'));
            }
            if ($request->get('date_to')) {
                $query->whereDate('created_at', '<=', $request->get('date_to'));
            }
            if ($request->get('search')) {
                $query->where('description', 'like', '%' . $request->get('search') . '%');
            }

            $page = $request->get('page', 1);
            $perPage = 15;
            
            $transactions = $query->paginate($perPage, ['*'], 'page', $page);
            
            return response()->json([
                'success' => true,
                'data' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'total_pages' => $transactions->lastPage(),
                    'total_items' => $transactions->total(),
                    'per_page' => $transactions->perPage()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction details via API
     */
    public function getTransactionDetails($id)
    {
        try {
            $user = Auth::user();
            $wallet = $user->getOrCreateWallet();
            
            $transaction = $wallet->transactions()->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $transaction
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Export transactions to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->getOrCreateWallet();
        
        $filters = $this->getFiltersFromRequest($request);

        $reservationTransactions = $this->getReservationTransactions($user, $filters);
        $walletTransactions = $this->getWalletTransactions($wallet, $filters, $reservationTransactions);
        $context = $this->buildReservationTransactionContext($reservationTransactions);

        $filename = 'transactions_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($walletTransactions, $reservationTransactions, $context) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV headers aligned with the on-screen scope
            fputcsv($file, $this->getExportHeaders());
            /*
                'Statut', 'Réservation ID', 'Borne', 'Part Admin',
                'Part Intégrateur', 'Part Opérateur', 'Solde Actuel',
            ]);

            */
            $rows = $walletTransactions
                ->map(fn ($transaction) => $this->mapWalletTransactionForExport($transaction, $context))
                ->merge(
                    $reservationTransactions->map(fn ($transaction) => $this->mapReservationTransactionForExport($transaction))
                )
                ->sortByDesc('sort_at')
                ->values();

            foreach ($rows as $row) {
                fputcsv($file, $row['columns']);
            }

            /*
            // Wallet transactions
            foreach ($walletTransactions as $transaction) {
                fputcsv($file, [
                    'Wallet',
                    $transaction->id,
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->type === 'credit' ? $transaction->amount : -$transaction->amount,
                    $transaction->currency ?? 'EUR',
                    $transaction->description,
                    'completed',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $transaction->current_balance,
                ]);
            }
            
            // Reservation transactions
            foreach ($reservationTransactions as $transaction) {
                $detail = $transaction->transactionDetail;
                fputcsv($file, [
                    'Réservation',
                    $transaction->id,
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->amount ?? $transaction->price_total ?? 0,
                    $transaction->currency ?? 'EUR',
                    $transaction->description ?? "Paiement réservation #{$transaction->reservation_id}",
                    $transaction->status,
                    $transaction->reservation_id ?? '',
                    $transaction->chargingPoint->name ?? 'N/A',
                    $detail->admin_share_amount ?? 0,
                    $detail->integrator_share_amount ?? 0,
                    $detail->operator_share_amount ?? 0,
                    '',
                ]);
            }
            */

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function getExportHeaders(): array
    {
        return [
            'Record Type',
            'Source ID',
            'Created At',
            'Status',
            'Amount',
            'Currency',
            'Description',
            'Reference',
            'Wallet ID',
            'Wallet Owner Type',
            'Wallet Owner Name',
            'Wallet Owner Email',
            'Wallet Transaction Type',
            'Wallet Running Balance',
            'Transaction ID',
            'Reservation ID',
            'Reservation Status',
            'Reservation Payment Status',
            'Payment Method',
            'Charging Point ID',
            'Charging Point Name',
            'Charging Point Serial',
            'Charging Point City',
            'Integrator',
            'Partner',
            'Operator',
            'User Name',
            'User Email',
            'Admin Share',
            'Integrator Share',
            'Operator Share',
            'Session Start',
            'Session Stop',
        ];
    }

    protected function buildReservationTransactionContext(\Illuminate\Support\Collection $reservationTransactions): array
    {
        return [
            'transactions_by_id' => $reservationTransactions->keyBy('id'),
            'transactions_by_reservation_id' => $reservationTransactions
                ->filter(fn ($transaction) => !empty($transaction->reservation_id))
                ->keyBy('reservation_id'),
        ];
    }

    protected function mapWalletTransactionForExport(WalletTransaction $walletTransaction, array $context): array
    {
        $metadata = $walletTransaction->metadata ?? [];
        $relatedTransactionId = (int) ($metadata['transaction_id'] ?? 0);
        $relatedReservationId = $walletTransaction->getReservationId();

        $relatedTransaction = $relatedTransactionId > 0
            ? $context['transactions_by_id']->get($relatedTransactionId)
            : null;

        if (!$relatedTransaction && $relatedReservationId) {
            $relatedTransaction = $context['transactions_by_reservation_id']->get($relatedReservationId);
        }

        $reservation = $relatedTransaction?->reservation;
        $chargingPoint = $relatedTransaction?->chargingPoint ?? $reservation?->chargingPoint;
        $operator = $this->getChargingPointOperator($chargingPoint);
        $partner = $this->getChargingPointPartner($chargingPoint);
        $integrator = $this->getChargingPointIntegrator($chargingPoint);
        $walletOwner = $walletTransaction->wallet?->owner;
        $walletOwnerType = $walletTransaction->wallet?->owner_type
            ? class_basename($walletTransaction->wallet->owner_type)
            : ($walletOwner ? class_basename(get_class($walletOwner)) : '');

        return [
            'sort_at' => $walletTransaction->created_at?->timestamp ?? 0,
            'columns' => [
                'wallet',
                $walletTransaction->id,
                $this->formatExportDate($walletTransaction->created_at),
                $walletTransaction->status ?? 'completed',
                $walletTransaction->type === 'credit'
                    ? (float) ($walletTransaction->amount ?? 0)
                    : -(float) abs($walletTransaction->amount ?? 0),
                $walletTransaction->currency ?? ($metadata['currency'] ?? 'EUR'),
                $walletTransaction->description ?? '',
                $walletTransaction->reference ?? $walletTransaction->external_id ?? '',
                $walletTransaction->wallet_id ?? '',
                $walletOwnerType,
                $walletOwner->name ?? $walletOwner->email ?? '',
                $walletOwner->email ?? '',
                $walletTransaction->type ?? '',
                $walletTransaction->current_balance ?? '',
                $relatedTransaction?->id ?? ($relatedTransactionId ?: ''),
                $relatedReservationId ?? '',
                $this->normalizeStatusForExport($reservation?->status),
                $reservation?->payment_status ?? '',
                $metadata['payment_method'] ?? $relatedTransaction?->payment_method ?? $reservation?->payment_method ?? '',
                $chargingPoint?->id ?? '',
                $chargingPoint?->name ?? '',
                $chargingPoint?->serial_number ?? '',
                $chargingPoint?->city ?? '',
                $integrator?->name ?? '',
                $partner?->name ?? '',
                $operator?->name ?? '',
                $relatedTransaction?->user?->name ?? $reservation?->user?->name ?? '',
                $relatedTransaction?->user?->email ?? $reservation?->user?->email ?? '',
                $relatedTransaction?->transactionDetail?->admin_share_amount ?? '',
                $relatedTransaction?->transactionDetail?->integrator_share_amount ?? '',
                $relatedTransaction?->transactionDetail?->operator_share_amount ?? '',
                $this->formatExportDate($relatedTransaction?->start_timestamp),
                $this->formatExportDate($relatedTransaction?->stop_timestamp),
            ],
        ];
    }

    protected function mapReservationTransactionForExport(Transaction $transaction): array
    {
        $reservation = $transaction->reservation;
        $chargingPoint = $transaction->chargingPoint ?? $reservation?->chargingPoint;
        $detail = $transaction->transactionDetail;
        $operator = $this->getChargingPointOperator($chargingPoint);
        $partner = $this->getChargingPointPartner($chargingPoint);
        $integrator = $this->getChargingPointIntegrator($chargingPoint);

        return [
            'sort_at' => $transaction->created_at?->timestamp ?? 0,
            'columns' => [
                'reservation',
                $transaction->id,
                $this->formatExportDate($transaction->created_at),
                $transaction->status ?? '',
                (float) ($transaction->price_total ?? $transaction->amount ?? 0),
                $transaction->currency ?? 'EUR',
                $transaction->description ?? "Paiement réservation #{$transaction->reservation_id}",
                $transaction->transaction_id ?? '',
                '',
                '',
                '',
                '',
                '',
                '',
                $transaction->id,
                $transaction->reservation_id ?? '',
                $this->normalizeStatusForExport($reservation?->status),
                $reservation?->payment_status ?? '',
                $transaction->payment_method ?? $reservation?->payment_method ?? '',
                $chargingPoint?->id ?? '',
                $chargingPoint?->name ?? '',
                $chargingPoint?->serial_number ?? '',
                $chargingPoint?->city ?? '',
                $integrator?->name ?? '',
                $partner?->name ?? '',
                $operator?->name ?? '',
                $transaction->user?->name ?? $reservation?->user?->name ?? '',
                $transaction->user?->email ?? $reservation?->user?->email ?? '',
                $detail?->admin_share_amount ?? '',
                $detail?->integrator_share_amount ?? '',
                $detail?->operator_share_amount ?? '',
                $this->formatExportDate($transaction->start_timestamp),
                $this->formatExportDate($transaction->stop_timestamp),
            ],
        ];
    }

    protected function formatExportDate($value): string
    {
        if (!$value) {
            return '';
        }

        return $value instanceof \Carbon\CarbonInterface
            ? $value->format('Y-m-d H:i:s')
            : (string) $value;
    }

    protected function normalizeStatusForExport($status): string
    {
        if ($status instanceof \UnitEnum) {
            return (string) $status->value;
        }

        return (string) ($status ?? '');
    }

    protected function getChargingPointOperator($chargingPoint)
    {
        if (!$chargingPoint) {
            return null;
        }

        if (method_exists($chargingPoint, 'getOperator')) {
            return $chargingPoint->getOperator();
        }

        return $chargingPoint->operator ?? $chargingPoint->group?->operator ?? $chargingPoint->user ?? null;
    }

    protected function getChargingPointPartner($chargingPoint)
    {
        if (!$chargingPoint) {
            return null;
        }

        if (method_exists($chargingPoint, 'getPartner')) {
            return $chargingPoint->getPartner();
        }

        return $chargingPoint->partner ?? $chargingPoint->group?->partner ?? null;
    }

    protected function getChargingPointIntegrator($chargingPoint)
    {
        if (!$chargingPoint) {
            return null;
        }

        if ($chargingPoint->integrator) {
            return $chargingPoint->integrator;
        }

        if ($chargingPoint->partner?->integrator) {
            return $chargingPoint->partner->integrator;
        }

        if ($chargingPoint->group?->partner?->integrator) {
            return $chargingPoint->group->partner->integrator;
        }

        return $chargingPoint->group?->integrator;
    }

    /**
     * Admin transactions index (API)
     */
    public function adminIndex(Request $request)
    {
        $this->authorize('viewAny', Transaction::class);
        
        $filters = $this->getFiltersFromRequest($request);
        
        $transactions = Transaction::with(['user', 'chargingPoint'])
            ->when($filters['date_from'], fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'], fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($filters['status'], fn($q, $s) => $q->where('status', $s))
            ->when($filters['type'], fn($q, $t) => $q->where('transaction_type', $t))
            ->when($filters['payment_method'], fn($q, $m) => $q->where('payment_method', $m))
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return response()->json($transactions);
    }

    /**
     * Get transaction filters from request
     */
    private function getTransactionFilters(Request $request): array
    {
        return [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'status' => $request->get('status'),
            'type' => $request->get('type'),
            'payment_method' => $request->get('payment_method'),
        ];
    }
    
    /**
     * Get user type string
     */
    private function getUserType(User $user): string
    {
        if ($user->hasRole(['admin', 'super_admin'])) {
            return 'admin';
        }
        
        if ($user->hasRole('integrator')) {
            return 'integrator';
        }
        
        if ($user->hasRole('partner')) {
            return 'partner';
        }
        
        return 'admin';
    }
}
