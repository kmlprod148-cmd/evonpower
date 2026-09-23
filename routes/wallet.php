<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\WalletMiddleware;

/*
|--------------------------------------------------------------------------
| Wallet API Routes
|--------------------------------------------------------------------------
|
| Here are all the routes for the wallet system. These routes are
| loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::middleware(['auth:sanctum', WalletMiddleware::class])->group(function () {
    
    // Basic wallet operations
    Route::get('/wallets/{wallet}', [WalletController::class, 'show'])->name('wallet.show');
    Route::get('/wallets/{wallet}/balance', [WalletController::class, 'balance'])->name('wallet.balance');
    Route::get('/wallets/{wallet}/summary', [WalletController::class, 'summary'])->name('wallet.summary');
    Route::get('/wallets/{wallet}/analytics', [WalletController::class, 'analytics'])->name('wallet.analytics');
    
    // Wallet settings
    Route::get('/wallets/{wallet}/settings', [WalletController::class, 'settings'])->name('wallet.settings');
    Route::put('/wallets/{wallet}/settings', [WalletController::class, 'updateSettings'])->name('wallet.settings.update');
    
    // Transaction operations
    Route::post('/wallets/{wallet}/credit', [WalletController::class, 'credit'])->name('wallet.credit');
    Route::post('/wallets/{wallet}/debit', [WalletController::class, 'debit'])->name('wallet.debit');
    Route::post('/wallets/{wallet}/transfer', [WalletController::class, 'transfer'])->name('wallet.transfer');
    Route::get('/wallets/{wallet}/transactions', [WalletController::class, 'transactions'])->name('wallet.transactions');
    Route::get('/wallets/{wallet}/statistics', [WalletController::class, 'statistics'])->name('wallet.statistics');
    
    // Specialized operations
    Route::post('/wallets/{wallet}/charging-payment', [WalletController::class, 'processChargingPayment'])->name('wallet.charging-payment');
    Route::post('/wallets/{wallet}/commission', [WalletController::class, 'processCommission'])->name('wallet.commission');
    
    // Auto-recharge operations
    Route::post('/wallets/{wallet}/auto-recharge', [WalletController::class, 'processAutoRecharge'])->name('wallet.auto-recharge');
    
    // Bulk operations
    Route::post('/wallets/bulk-credit', [WalletController::class, 'bulkCredit'])->name('wallet.bulk-credit');
    
    // Admin operations
    Route::get('/wallets/attention', [WalletController::class, 'attention'])->name('wallet.attention');
    Route::post('/wallets/process-auto-recharges', [WalletController::class, 'processAllAutoRecharges'])->name('wallet.process-auto-recharges');
    
    // Available options
    Route::get('/wallets/{wallet}/available-wallets', [WalletController::class, 'availableWallets'])->name('wallet.available-wallets');
});

// Public wallet information (limited access)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/my-wallet', function () {
        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();
        
        return response()->json([
            'wallet' => [
                'id' => $wallet->id,
                'balance' => $wallet->balance,
                'formatted_balance' => $wallet->getFormattedBalance(),
                'currency' => $wallet->currency,
                'is_active' => $wallet->is_active,
            ],
            'statistics' => \App\Services\WalletService::getWalletStatistics($wallet),
        ]);
    })->name('wallet.my-wallet');
    
    Route::get('/my-wallet/transactions', function () {
        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();
        
        $perPage = request()->get('per_page', 20);
        $type = request()->get('type');
        $status = request()->get('status');
        
        $query = $wallet->transactions()->latest();
        
        if ($type) {
            $query->where('type', $type);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $transactions = $query->paginate($perPage);
        
        return response()->json([
            'transactions' => $transactions,
            'wallet' => [
                'id' => $wallet->id,
                'balance' => $wallet->balance,
                'formatted_balance' => $wallet->getFormattedBalance(),
                'currency' => $wallet->currency,
            ],
        ]);
    })->name('wallet.my-transactions');
    
    Route::post('/my-wallet/credit', function () {
        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();
        
        $validated = request()->validate([
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
    })->name('wallet.my-credit');
    
    Route::post('/my-wallet/debit', function () {
        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();
        
        $validated = request()->validate([
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
    })->name('wallet.my-debit');
});

// Webhook routes for external integrations
Route::middleware(['api'])->group(function () {
    Route::post('/webhooks/wallet/auto-recharge', function () {
        try {
            $processedCount = \App\Services\WalletService::processAutoRecharges();
            
            return response()->json([
                'message' => 'Auto-recharge processing completed',
                'processed_count' => $processedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Auto-recharge processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    })->name('webhook.wallet.auto-recharge');
    
    Route::post('/webhooks/wallet/balance-check', function () {
        try {
            $attention = \App\Services\WalletService::getWalletsNeedingAttention();
            
            return response()->json([
                'low_balance' => $attention['low_balance']->count(),
                'needing_recharge' => $attention['needing_recharge']->count(),
                'inactive' => $attention['inactive']->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Balance check failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    })->name('webhook.wallet.balance-check');
});

// Admin-only routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/wallets', function () {
        $wallets = \App\Models\Wallet::with('owner')->paginate(20);
        
        return response()->json([
            'wallets' => $wallets,
        ]);
    })->name('admin.wallets.index');
    
    Route::get('/admin/wallets/statistics', function () {
        $totalWallets = \App\Models\Wallet::count();
        $activeWallets = \App\Models\Wallet::where('is_active', true)->count();
        $totalBalance = \App\Models\Wallet::sum('balance');
        $attention = \App\Services\WalletService::getWalletsNeedingAttention();
        
        return response()->json([
            'total_wallets' => $totalWallets,
            'active_wallets' => $activeWallets,
            'inactive_wallets' => $totalWallets - $activeWallets,
            'total_balance' => $totalBalance,
            'low_balance_count' => $attention['low_balance']->count(),
            'needing_recharge_count' => $attention['needing_recharge']->count(),
            'inactive_count' => $attention['inactive']->count(),
        ]);
    })->name('admin.wallets.statistics');
    
    Route::post('/admin/wallets/bulk-operations', function () {
        $validated = request()->validate([
            'operation' => 'required|in:credit,debit,activate,deactivate',
            'wallet_ids' => 'required|array',
            'wallet_ids.*' => 'exists:wallets,id',
            'amount' => 'required_if:operation,credit,debit|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);
        
        $results = [];
        
        foreach ($validated['wallet_ids'] as $walletId) {
            try {
                $wallet = \App\Models\Wallet::findOrFail($walletId);
                
                switch ($validated['operation']) {
                    case 'credit':
                        $transaction = $wallet->credit(
                            $validated['amount'],
                            $validated['description'] ?? 'Bulk credit'
                        );
                        $results[$walletId] = ['success' => true, 'transaction' => $transaction];
                        break;
                        
                    case 'debit':
                        $transaction = $wallet->debit(
                            $validated['amount'],
                            $validated['description'] ?? 'Bulk debit'
                        );
                        $results[$walletId] = ['success' => true, 'transaction' => $transaction];
                        break;
                        
                    case 'activate':
                        $wallet->update(['is_active' => true]);
                        $results[$walletId] = ['success' => true, 'action' => 'activated'];
                        break;
                        
                    case 'deactivate':
                        $wallet->update(['is_active' => false]);
                        $results[$walletId] = ['success' => true, 'action' => 'deactivated'];
                        break;
                }
            } catch (\Exception $e) {
                $results[$walletId] = ['success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return response()->json([
            'message' => 'Bulk operation completed',
            'results' => $results,
        ]);
    })->name('admin.wallets.bulk-operations');
});
