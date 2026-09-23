<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionViewController extends Controller
{
    public function __construct(
        protected TransactionQueryService $queryService
    ) {}

    /**
     * Display a listing of transactions with filtering and statistics
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $filters = [
            'type'               => $request->get('type'),
            'date_from'          => $request->get('date_from'),
            'date_to'            => $request->get('date_to'),
            'search'             => $request->get('search'),
            'user_id'            => $request->get('user_id'),
            'charging_point_id'  => $request->get('charging_point_id'),
            'status'             => $request->get('status'),
            'payment_method'     => $request->get('payment_method'),
        ];

        // Build role-scoped query with eager loading + apply UI filters
        $query = $this->queryService->buildRoleBasedQuery($user);
        $query = $this->queryService->applyFilters($query, $filters);
        $query->latest('created_at');

        // Get paginated transactions
        $transactions = $query->paginate(20)->appends($filters);

        // Statistics on the same scoped+filtered base
        $statsQuery = $this->queryService->buildRoleBasedQuery($user);
        $statsQuery = $this->queryService->applyFilters($statsQuery, $filters);
        $stats = $this->calculateStatistics($statsQuery->toBase());

        // Filter options scoped to the current user's role
        $transactionTypes = Transaction::distinct('status')
            ->pluck('status')
            ->filter()
            ->toArray();

        $users          = $this->queryService->getScopedUsers($user);
        $chargingPoints = $this->queryService->getScopedChargingPoints($user)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $paymentMethods = Transaction::whereNotNull('metadata->payment_method')
            ->distinct()
            ->pluck('metadata->payment_method')
            ->filter()
            ->toArray();

        // Get wallet data if available (refresh pour solde synchronisé)
        $wallet = null;
        if (method_exists($user, 'getOrCreateWallet')) {
            $wallet = $user->getOrCreateWallet();
            $wallet->refresh();
        }

        return view('transactions.index', compact(
            'transactions',
            'filters',
            'stats',
            'transactionTypes',
            'users',
            'chargingPoints',
            'paymentMethods',
            'wallet'
        ));
    }

    /**
     * Export transactions to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        $filters = [
            'type'              => $request->get('type'),
            'date_from'         => $request->get('date_from'),
            'date_to'           => $request->get('date_to'),
            'search'            => $request->get('search'),
            'user_id'           => $request->get('user_id'),
            'charging_point_id' => $request->get('charging_point_id'),
            'status'            => $request->get('status'),
            'payment_method'    => $request->get('payment_method'),
        ];

        $query = $this->queryService->buildRoleBasedQuery($user)->with([
            'chargingPoint',
            'chargingPoint.businessProfile',
            'user',
        ]);
        $query = $this->queryService->applyFilters($query, $filters);
        $query->latest('created_at');

        $transactions = $query->get();

        $filename = 'transactions_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'External ID',
                'Date',
                'User',
                'User Email',
                'Transaction Type',
                'Charging Point',
                'Charging Point ID',
                'Amount HT',
                'Amount TTC',
                'Fees',
                'Status',
                'Payment Method',
                'Session ID',
                'Duration (min)',
                'Energy Delivered (kWh)',
                'Admin Fees',
                'Integrator Fees',
                'Partner Fees',
                'Total Fees',
                'Created At',
                'Updated At',
            ]);

            $calculator  = new \App\Services\TransactionAmountCalculator();
            $httcService = new \App\Services\TransactionHTTTCService();

            foreach ($transactions as $transaction) {
                $httcAmounts     = $httcService->calculateHTTTC($transaction);
                $transactionType = $calculator->getTransactionType($transaction);
                $paymentMethod   = $calculator->getPaymentMethod($transaction);

                fputcsv($file, [
                    $transaction->id,
                    $transaction->transaction_id ?? 'N/A',
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->user?->name ?? 'N/A',
                    $transaction->user?->email ?? 'N/A',
                    $transactionType,
                    $transaction->chargingPoint?->name ?? 'N/A',
                    $transaction->chargingPoint?->id ?? 'N/A',
                    $httcAmounts['ht'],
                    $httcAmounts['ttc'],
                    $httcAmounts['vat_amount'],
                    $transaction->status,
                    $paymentMethod,
                    $transaction->session_id ?? 'N/A',
                    $transaction->duration ?? 'N/A',
                    $transaction->energy_delivered ?? 'N/A',
                    $transaction->pricing_data['admin_fee'] ?? 0,
                    $transaction->pricing_data['integrator_fee'] ?? 0,
                    $transaction->pricing_data['partner_fee'] ?? 0,
                    $transaction->pricing_data['total_fees'] ?? 0,
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get transactions via API (role-scoped)
     */
    public function api(Request $request)
    {
        try {
            $user = Auth::user();

            $filters = [
                'type'              => $request->get('type'),
                'date_from'         => $request->get('date_from'),
                'date_to'           => $request->get('date_to'),
                'search'            => $request->get('search'),
                'user_id'           => $request->get('user_id'),
                'charging_point_id' => $request->get('charging_point_id'),
                'status'            => $request->get('status'),
                'payment_method'    => $request->get('payment_method'),
            ];

            $query = $this->queryService->buildRoleBasedQuery($user)->with([
                'chargingPoint',
                'chargingPoint.businessProfile',
                'user',
            ]);
            $query = $this->queryService->applyFilters($query, $filters);
            $query->latest('created_at');

            $page    = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);

            $transactions = $query->paginate($perPage, ['*'], 'page', $page);

            $calculator  = new \App\Services\TransactionAmountCalculator();
            $httcService = new \App\Services\TransactionHTTTCService();

            $transformedTransactions = $transactions->getCollection()->map(function ($transaction) use ($calculator, $httcService) {
                $httcAmounts     = $httcService->calculateHTTTC($transaction);
                $transactionType = $calculator->getTransactionType($transaction);
                $paymentMethod   = $calculator->getPaymentMethod($transaction);

                return [
                    'id'          => $transaction->id,
                    'external_id' => $transaction->transaction_id,
                    'user'        => $transaction->user ? [
                        'id'    => $transaction->user->id,
                        'name'  => $transaction->user->name,
                        'email' => $transaction->user->email,
                    ] : null,
                    'charging_point' => $transaction->chargingPoint ? [
                        'id'   => $transaction->chargingPoint->id,
                        'name' => $transaction->chargingPoint->name,
                    ] : null,
                    'amount_ht'       => $httcAmounts['ht'],
                    'amount_ttc'      => $httcAmounts['ttc'],
                    'fees'            => $httcAmounts['vat_amount'],
                    'type'            => $transactionType,
                    'payment_method'  => $paymentMethod,
                    'status'          => $transaction->status,
                    'session_id'      => $transaction->session_id,
                    'duration'        => $transaction->duration,
                    'energy_delivered' => $transaction->energy_delivered,
                    'pricing_data'    => $transaction->pricing_data,
                    'hierarchy_data'  => $transaction->hierarchy_data,
                    'metadata'        => $transaction->metadata,
                    'created_at'      => $transaction->created_at,
                    'updated_at'      => $transaction->updated_at,
                ];
            });

            return response()->json([
                'success'    => true,
                'data'       => $transformedTransactions,
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'total_pages'  => $transactions->lastPage(),
                    'total_items'  => $transactions->total(),
                    'per_page'     => $transactions->perPage(),
                    'has_more'     => $transactions->hasMorePages(),
                ],
                'filters'    => $filters,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error retrieving transactions'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transaction details via API
     */
    public function apiShow($id)
    {
        try {
            $user        = Auth::user();
            $baseQuery   = $this->queryService->buildRoleBasedQuery($user);
            $transaction = $baseQuery->with([
                'chargingPoint',
                'user',
                'chargingPoint.businessProfile',
            ])->findOrFail($id);

            $calculator      = new \App\Services\TransactionAmountCalculator();
            $httcService     = new \App\Services\TransactionHTTTCService();
            $httcAmounts     = $httcService->calculateHTTTC($transaction);
            $transactionType = $calculator->getTransactionType($transaction);
            $paymentMethod   = $calculator->getPaymentMethod($transaction);
            $summary         = $calculator->getTransactionSummary($transaction);

            return response()->json([
                'success' => true,
                'data'    => [
                    'transaction'      => $transaction,
                    'amounts'          => [
                        'ht'       => $httcAmounts['ht'],
                        'ttc'      => $httcAmounts['ttc'],
                        'fees'     => $httcAmounts['vat_amount'],
                        'vat_rate' => $httcAmounts['vat_rate'],
                    ],
                    'transaction_type' => $transactionType,
                    'payment_method'   => $paymentMethod,
                    'summary'          => $summary,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Transaction not found'),
                'error'   => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get transaction statistics via API (role-scoped)
     */
    public function apiStats(Request $request)
    {
        try {
            $user = Auth::user();

            $filters = [
                'type'              => $request->get('type'),
                'date_from'         => $request->get('date_from'),
                'date_to'           => $request->get('date_to'),
                'search'            => $request->get('search'),
                'user_id'           => $request->get('user_id'),
                'charging_point_id' => $request->get('charging_point_id'),
                'status'            => $request->get('status'),
                'payment_method'    => $request->get('payment_method'),
            ];

            $query = $this->queryService->buildRoleBasedQuery($user);
            $query = $this->queryService->applyFilters($query, $filters);
            $query->latest('created_at');

            $stats = $this->calculateStatistics($query->toBase());

            return response()->json([
                'success' => true,
                'data'    => $stats,
                'filters' => $filters,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error retrieving statistics'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate transaction statistics from a base (unscoped Eloquent\Builder converted to Query\Builder)
     */
    private function calculateStatistics($query)
    {
        $baseQuery = clone $query;

        $totalTransactions = $baseQuery->count();

        $completedQuery = clone $baseQuery;
        $failedQuery    = clone $baseQuery;
        $pendingQuery   = clone $baseQuery;

        $totalCredits = $completedQuery->where('status', 'completed')->sum('amount');
        $totalDebits  = $failedQuery->where('status', 'failed')->sum('amount');
        $netAmount    = $totalCredits - $totalDebits;

        return [
            'total_transactions' => $totalTransactions,
            'total_credits'      => $totalCredits,
            'total_debits'       => $totalDebits,
            'net_amount'         => $netAmount,
            'completed_count'    => $completedQuery->count(),
            'failed_count'       => $failedQuery->count(),
            'pending_count'      => $pendingQuery->where('status', 'pending')->count(),
        ];
    }

    /**
     * Display a single transaction
     */
    public function show($id)
    {
        $user        = Auth::user();
        $transaction = $this->queryService->buildRoleBasedQuery($user)->with([
            'chargingPoint',
            'chargingPoint.group',
            'chargingPoint.group.partner',
            'chargingPoint.group.partner.integrator',
            'user',
            'chargingPoint.businessProfile',
            'transactionDetail',
            'transactionDetail.adminCreator',
            'transactionDetail.integratorCreator',
            'transactionDetail.operator',
            'reservation',
            'reservation.user',
        ])->findOrFail($id);

        $calculator      = new \App\Services\TransactionAmountCalculator();
        $amounts         = $calculator->calculateAmounts($transaction);
        $transactionType = $calculator->getTransactionType($transaction);
        $paymentMethod   = $calculator->getPaymentMethod($transaction);
        $summary         = $calculator->getTransactionSummary($transaction);

        return view('transactions.show', compact(
            'transaction',
            'amounts',
            'transactionType',
            'paymentMethod',
            'summary'
        ));
    }
}
