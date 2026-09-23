<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    /**
     * Display the specified wallet
     */
    public function show(Wallet $wallet)
    {
        $this->authorize('view', $wallet);
        
        $wallet->load(['owner', 'transactions' => function ($query) {
            $query->latest()->limit(10);
        }]);
        
        $statistics = WalletService::getWalletStatistics($wallet);
        
        if (request()->expectsJson()) {
            return response()->json([
                'wallet' => $wallet,
                'statistics' => $statistics,
            ]);
        }
        
        return view('wallets.show', compact('wallet', 'statistics'));
    }

    /**
     * Credit amount to wallet
     */
    public function credit(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);
        
        try {
            $transaction = $wallet->credit(
                $validated['amount'],
                $validated['description'],
                $validated['metadata'] ?? []
            );
            
            return response()->json([
                'message' => 'Amount credited successfully',
                'transaction' => $transaction,
                'new_balance' => $wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Credit failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Debit amount from wallet
     */
    public function debit(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);
        
        try {
            $transaction = $wallet->debit(
                $validated['amount'],
                $validated['description'],
                $validated['metadata'] ?? []
            );
            
            return response()->json([
                'message' => 'Amount debited successfully',
                'transaction' => $transaction,
                'new_balance' => $wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Debit failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Transfer amount to another wallet
     */
    public function transfer(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);
        
        $validated = $request->validate([
            'destination_wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);
        
        $destinationWallet = Wallet::findOrFail($validated['destination_wallet_id']);
        
        try {
            $result = $wallet->transferTo(
                $destinationWallet,
                $validated['amount'],
                $validated['description'],
                $validated['metadata'] ?? []
            );
            
            return response()->json([
                'message' => 'Transfer completed successfully',
                'source_transaction' => $result['source_transaction'],
                'destination_transaction' => $result['destination_transaction'],
                'new_balance' => $wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Transfer failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get wallet transactions
     */
    public function transactions(Request $request, Wallet $wallet)
    {
        $this->authorize('view', $wallet);
        
        $perPage = $request->get('per_page', 20);
        $type = $request->get('type'); // 'credit' or 'debit'
        $status = $request->get('status'); // 'completed', 'pending', 'failed'
        
        $query = $wallet->transactions()->latest();
        
        if ($type) {
            $query->where('type', $type);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $transactions = $query->paginate($perPage);
        
        if (request()->expectsJson()) {
            return response()->json([
                'transactions' => $transactions,
                'wallet' => $wallet,
            ]);
        }
        
        return view('wallets.transactions', compact('wallet', 'transactions'));
    }

    /**
     * Get wallet statistics
     */
    public function statistics(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);
        
        $startDate = $request->get('start_date') ? 
            \Carbon\Carbon::parse($request->get('start_date')) : 
            now()->subDays(30);
            
        $endDate = $request->get('end_date') ? 
            \Carbon\Carbon::parse($request->get('end_date')) : 
            now();
        
        $statistics = WalletService::getWalletStatistics($wallet, $startDate, $endDate);
        
        return response()->json([
            'statistics' => $statistics,
            'wallet' => $wallet,
        ]);
    }

    /**
     * Get available wallets for transfer
     */
    public function availableWallets(Request $request, Wallet $wallet)
    {
        $this->authorize('view', $wallet);
        
        $user = auth()->user();
        
        // Get user's accessible wallets
        $wallets = Wallet::whereHasMorph('owner', [User::class], function ($query) use ($user) {
            if ($user->hasRole('admin')) {
                return $query; // Admin can see all wallets
            }
            
            // Other users can only see their own wallets
            return $query->where('id', $user->id);
        })->where('id', '!=', $wallet->id)->get();
        
        return response()->json([
            'wallets' => $wallets->map(function ($wallet) {
                return [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'owner' => $wallet->owner->name ?? 'Unknown',
                    'balance' => $wallet->balance,
                    'currency' => $wallet->currency,
                ];
            }),
        ]);
    }

    /**
     * Process charging session payment
     */
    public function processChargingPayment(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'session_id' => 'required|string',
            'charging_point_id' => 'required|integer',
            'energy_delivered' => 'nullable|numeric',
        ]);
        
        try {
            $sessionData = [
                'session_id' => $validated['session_id'],
                'charging_point_id' => $validated['charging_point_id'],
                'energy_delivered' => $validated['energy_delivered'] ?? 0,
            ];
            
            $transaction = WalletService::processChargingPayment(
                $wallet,
                $validated['amount'],
                $sessionData
            );
            
            return response()->json([
                'message' => 'Charging payment processed successfully',
                'transaction' => $transaction,
                'new_balance' => $wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Payment processing failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Process commission payment
     */
    public function processCommission(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'commission_type' => 'nullable|string',
            'rate' => 'nullable|numeric',
        ]);
        
        try {
            $commissionData = [
                'commission_type' => $validated['commission_type'] ?? 'general',
                'rate' => $validated['rate'] ?? 0,
            ];
            
            $transaction = WalletService::processCommissionPayment(
                $wallet,
                $validated['amount'],
                $validated['description'],
                $commissionData
            );
            
            return response()->json([
                'message' => 'Commission payment processed successfully',
                'transaction' => $transaction,
                'new_balance' => $wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Commission processing failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get wallet balance
     */
    public function balance(Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);
        
        return response()->json([
            'wallet_id' => $wallet->id,
            'balance' => $wallet->balance,
            'formatted_balance' => $wallet->getFormattedBalance(),
            'currency' => $wallet->currency,
            'is_active' => $wallet->is_active,
            'available_balance' => $wallet->getAvailableBalance(),
        ]);
    }

    /**
     * Get wallet settings
     */
    public function settings(Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);
        
        return response()->json([
            'wallet' => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'description' => $wallet->description,
                'currency' => $wallet->currency,
                'is_active' => $wallet->is_active,
                'min_balance' => $wallet->min_balance,
                'max_balance' => $wallet->max_balance,
                'auto_recharge' => $wallet->auto_recharge,
                'auto_recharge_threshold' => $wallet->auto_recharge_threshold,
                'auto_recharge_amount' => $wallet->auto_recharge_amount,
            ],
        ]);
    }

    /**
     * Update wallet settings
     */
    public function updateSettings(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);
        
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'min_balance' => 'nullable|numeric|min:0',
            'max_balance' => 'nullable|numeric|min:0',
            'auto_recharge' => 'nullable|boolean',
            'auto_recharge_threshold' => 'nullable|numeric|min:0',
            'auto_recharge_amount' => 'nullable|numeric|min:0',
        ]);
        
        try {
            $wallet->update($validated);
            
            return response()->json([
                'message' => 'Wallet settings updated successfully',
                'wallet' => $wallet->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update wallet settings',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get wallet summary
     */
    public function summary(Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);
        
        $statistics = WalletService::getWalletStatistics($wallet);
        $recentTransactions = $wallet->transactions()->latest()->limit(5)->get();
        
        return response()->json([
            'wallet' => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'balance' => $wallet->balance,
                'formatted_balance' => $wallet->getFormattedBalance(),
                'currency' => $wallet->currency,
                'is_active' => $wallet->is_active,
            ],
            'statistics' => $statistics,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    /**
     * Process auto-recharge
     */
    public function processAutoRecharge(Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);
        
        try {
            if (!$wallet->needsAutoRecharge()) {
                return response()->json([
                    'message' => 'Auto-recharge not needed',
                    'needs_recharge' => false,
                ]);
            }
            
            $transaction = $wallet->performAutoRecharge();
            
            return response()->json([
                'message' => 'Auto-recharge processed successfully',
                'transaction' => $transaction,
                'new_balance' => $wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Auto-recharge failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get wallet analytics
     */
    public function analytics(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);
        
        $startDate = $request->get('start_date') ? 
            \Carbon\Carbon::parse($request->get('start_date')) : 
            now()->subDays(30);
            
        $endDate = $request->get('end_date') ? 
            \Carbon\Carbon::parse($request->get('end_date')) : 
            now();
        
        $statistics = WalletService::getWalletStatistics($wallet, $startDate, $endDate);
        $balanceHistory = $wallet->getBalanceHistory($startDate, $endDate);
        
        return response()->json([
            'wallet_id' => $wallet->id,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'statistics' => $statistics,
            'balance_history' => $balanceHistory,
        ]);
    }

    /**
     * Bulk operations
     */
    public function bulkCredit(Request $request): JsonResponse
    {
        $this->authorize('create', Wallet::class);
        
        $validated = $request->validate([
            'wallet_credits' => 'required|array',
            'wallet_credits.*.wallet_id' => 'required|exists:wallets,id',
            'wallet_credits.*.amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);
        
        try {
            $walletCredits = [];
            foreach ($validated['wallet_credits'] as $credit) {
                $walletCredits[$credit['wallet_id']] = $credit['amount'];
            }
            
            $results = WalletService::bulkCredit(
                $walletCredits,
                $validated['description'] ?? 'Bulk credit',
                $validated['metadata'] ?? []
            );
            
            return response()->json([
                'message' => 'Bulk credit processed',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Bulk credit failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get wallets needing attention
     */
    public function attention(): JsonResponse
    {
        $this->authorize('viewAny', Wallet::class);
        
        $attention = WalletService::getWalletsNeedingAttention();
        
        return response()->json([
            'low_balance' => $attention['low_balance'],
            'needing_recharge' => $attention['needing_recharge'],
            'inactive' => $attention['inactive'],
        ]);
    }

    /**
     * Process all auto-recharges
     */
    public function processAllAutoRecharges(): JsonResponse
    {
        $this->authorize('update', Wallet::class);
        
        try {
            $processedCount = WalletService::processAutoRecharges();
            
            return response()->json([
                'message' => 'Auto-recharge processing completed',
                'processed_count' => $processedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Auto-recharge processing failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
