<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WalletMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only process authenticated requests
        if (!Auth::check()) {
            return $next($request);
        }

        // Auto-create wallet for authenticated user if needed
        $this->ensureUserWallet($request);

        // Process wallet-related requests
        $this->processWalletRequests($request);

        return $next($request);
    }

    /**
     * Ensure user has a wallet
     */
    protected function ensureUserWallet(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->wallet) {
            try {
                $wallet = WalletService::createWallet($user, [
                    'name' => $user->name . "'s Wallet",
                    'description' => 'Auto-created wallet for ' . $user->name,
                    'currency' => 'EUR',
                ]);
                
                Log::info('Auto-created wallet for user', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to auto-create wallet for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Process wallet-related requests
     */
    protected function processWalletRequests(Request $request)
    {
        $route = $request->route();
        $routeName = $route ? $route->getName() : '';
        
        // Handle specific wallet operations
        if (str_contains($routeName, 'wallet.credit') || str_contains($routeName, 'wallet.debit')) {
            $this->validateWalletOperation($request);
        }
        
        // Handle auto-recharge processing
        if (str_contains($routeName, 'wallet.auto-recharge')) {
            $this->processAutoRecharge($request);
        }
        
        // Handle bulk operations
        if (str_contains($routeName, 'wallet.bulk')) {
            $this->validateBulkOperation($request);
        }
    }

    /**
     * Validate wallet operation
     */
    protected function validateWalletOperation(Request $request)
    {
        $amount = $request->input('amount');
        
        if ($amount && $amount <= 0) {
            abort(400, 'Amount must be positive');
        }
        
        // Check for minimum amount
        if ($amount && $amount < 0.01) {
            abort(400, 'Amount must be at least 0.01');
        }
        
        // Check for maximum amount
        if ($amount && $amount > 1000000) {
            abort(400, 'Amount exceeds maximum limit');
        }
    }

    /**
     * Process auto-recharge
     */
    protected function processAutoRecharge(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->wallet;
        
        if ($wallet && $wallet->needsAutoRecharge()) {
            try {
                $transaction = $wallet->performAutoRecharge();
                
                Log::info('Auto-recharge processed via middleware', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $transaction->amount,
                ]);
            } catch (\Exception $e) {
                Log::error('Auto-recharge failed via middleware', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Validate bulk operation
     */
    protected function validateBulkOperation(Request $request)
    {
        $walletCredits = $request->input('wallet_credits', []);
        
        if (count($walletCredits) > 100) {
            abort(400, 'Bulk operation limited to 100 wallets');
        }
        
        $totalAmount = array_sum(array_column($walletCredits, 'amount'));
        
        if ($totalAmount > 1000000) {
            abort(400, 'Total bulk amount exceeds maximum limit');
        }
    }

    /**
     * Get wallet balance for user
     */
    public static function getUserWalletBalance(): float
    {
        $user = Auth::user();
        return $user ? $user->getWalletBalance() : 0;
    }

    /**
     * Check if user has sufficient balance
     */
    public static function hasSufficientBalance(float $amount): bool
    {
        $user = Auth::user();
        return $user ? $user->hasSufficientWalletBalance($amount) : false;
    }

    /**
     * Get wallet information for user
     */
    public static function getUserWalletInfo(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [
                'has_wallet' => false,
                'balance' => 0,
                'formatted_balance' => '0.00 EUR',
                'currency' => 'EUR',
            ];
        }
        
        $wallet = $user->wallet;
        
        if (!$wallet) {
            return [
                'has_wallet' => false,
                'balance' => 0,
                'formatted_balance' => '0.00 EUR',
                'currency' => 'EUR',
            ];
        }
        
        return [
            'has_wallet' => true,
            'wallet_id' => $wallet->id,
            'balance' => $wallet->balance,
            'formatted_balance' => $wallet->getFormattedBalance(),
            'currency' => $wallet->currency,
            'is_active' => $wallet->is_active,
            'available_balance' => $wallet->getAvailableBalance(),
        ];
    }

    /**
     * Process wallet creation for new users
     */
    public static function createWalletForUser($user, array $attributes = []): ?Wallet
    {
        try {
            return WalletService::createWallet($user, $attributes);
        } catch (\Exception $e) {
            Log::error('Failed to create wallet for user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Process wallet operations with logging
     */
    public static function processWalletOperation(callable $operation, string $description): mixed
    {
        try {
            $result = $operation();
            
            Log::info('Wallet operation completed', [
                'description' => $description,
                'user_id' => Auth::id(),
                'result' => $result,
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Wallet operation failed', [
                'description' => $description,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Get wallet statistics for user
     */
    public static function getUserWalletStatistics(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->wallet) {
            return [
                'current_balance' => 0,
                'total_credits' => 0,
                'total_debits' => 0,
                'net_change' => 0,
                'transaction_count' => 0,
            ];
        }
        
        return WalletService::getWalletStatistics($user->wallet);
    }

    /**
     * Process auto-recharge for all wallets
     */
    public static function processAllAutoRecharges(): int
    {
        try {
            return WalletService::processAutoRecharges();
        } catch (\Exception $e) {
            Log::error('Failed to process auto-recharges', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
