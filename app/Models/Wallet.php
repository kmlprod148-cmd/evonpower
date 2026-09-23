<?php

namespace App\Models;

use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'balance',
        'currency',
        'is_active',
        'name',
        'description',
        'min_balance',
        'max_balance',
        'auto_recharge',
        'auto_recharge_threshold',
        'auto_recharge_amount',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'min_balance' => 'decimal:2',
        'max_balance' => 'decimal:2',
        'auto_recharge_threshold' => 'decimal:2',
        'auto_recharge_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'auto_recharge' => 'boolean',
    ];

    /**
     * Get the owner of the wallet (polymorphic relationship)
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all transactions for this wallet
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get credit transactions
     */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->where('type', 'credit');
    }

    /**
     * Get debit transactions
     */
    public function debitTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->where('type', 'debit');
    }

    /**
     * Credit amount to the wallet
     */
    public function credit(float $amount, string $description = null, array $metadata = [], string $currency = MoneyService::DEFAULT_CURRENCY): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive');
        }

        // Convert to EUR for internal storage
        $eurAmount = MoneyService::toEur($amount, $currency);

        return DB::transaction(function () use ($eurAmount, $amount, $description, $metadata, $currency) {
            // Lock the wallet row for update
            $wallet = static::where('id', $this->id)->lockForUpdate()->first();
            
            if (!$wallet) {
                throw new \Exception('Wallet not found');
            }

            $balanceBefore = $wallet->balance;
            $newBalance = MoneyService::roundToPrecision($wallet->balance + $eurAmount);

            // Create transaction record
            $transaction = $wallet->transactions()->create([
                'type' => 'credit',
                'amount' => $eurAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $newBalance,
                'description' => $description,
                'metadata' => array_merge($metadata, [
                    'original_amount' => $amount,
                    'original_currency' => $currency,
                    'currency' => $currency,
                    'exchange_rate' => MoneyService::getExchangeRate($currency, MoneyService::DEFAULT_CURRENCY)
                ]),
                'status' => 'completed',
            ]);

            // Update wallet balance
            $wallet->update([
                'balance' => $newBalance
            ]);

            return $transaction;
        });
    }

    /**
     * Debit amount from the wallet
     */
    public function debit(float $amount, string $description = null, array $metadata = [], string $currency = MoneyService::DEFAULT_CURRENCY): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Debit amount must be positive');
        }

        // Convert to EUR for internal storage
        $eurAmount = MoneyService::toEur($amount, $currency);

        return DB::transaction(function () use ($eurAmount, $amount, $description, $metadata, $currency) {
            // Lock the wallet row for update
            $wallet = static::where('id', $this->id)->lockForUpdate()->first();
            
            if (!$wallet) {
                throw new \Exception('Wallet not found');
            }

            // Check if sufficient balance
            if ($wallet->balance < $eurAmount) {
                throw new \Exception('Insufficient balance. Available: ' . $wallet->balance . ', Required: ' . $eurAmount);
            }

            // Check minimum balance constraint
            $balanceBefore = $wallet->balance;
            $newBalance = MoneyService::roundToPrecision($wallet->balance - $eurAmount);
            if ($wallet->min_balance && $newBalance < $wallet->min_balance) {
                throw new \Exception('Transaction would violate minimum balance constraint');
            }

            // Create transaction record
            $transaction = $wallet->transactions()->create([
                'type' => 'debit',
                'amount' => $eurAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $newBalance,
                'description' => $description,
                'metadata' => array_merge($metadata, [
                    'original_amount' => $amount,
                    'original_currency' => $currency,
                    'currency' => $currency,
                    'exchange_rate' => MoneyService::getExchangeRate($currency, MoneyService::DEFAULT_CURRENCY)
                ]),
                'status' => 'completed',
            ]);

            // Update wallet balance
            $wallet->update([
                'balance' => $newBalance
            ]);

            return $transaction;
        });
    }

    /**
     * Transfer amount to another wallet
     */
    public function transferTo(Wallet $destinationWallet, float $amount, string $description = null, array $metadata = []): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive');
        }

        if ($this->id === $destinationWallet->id) {
            throw new \InvalidArgumentException('Cannot transfer to the same wallet');
        }

        return DB::transaction(function () use ($destinationWallet, $amount, $description, $metadata) {
            // Lock both wallets for update
            $sourceWallet = static::where('id', $this->id)->lockForUpdate()->first();
            $destWallet = static::where('id', $destinationWallet->id)->lockForUpdate()->first();

            if (!$sourceWallet || !$destWallet) {
                throw new \Exception('One or both wallets not found');
            }

            // Check if sufficient balance
            if ($sourceWallet->balance < $amount) {
                throw new \Exception('Insufficient balance for transfer');
            }

            // Check minimum balance constraint
            $newSourceBalance = $sourceWallet->balance - $amount;
            if ($sourceWallet->min_balance && $newSourceBalance < $sourceWallet->min_balance) {
                throw new \Exception('Transfer would violate minimum balance constraint');
            }

            // Create transfer transactions
            $sourceTransaction = $sourceWallet->transactions()->create([
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $sourceWallet->balance,
                'balance_after' => $newSourceBalance,
                'description' => $description ?: $this->generateTransferDescription($destinationWallet),
                'metadata' => array_merge($metadata, [
                    'transfer_to' => $destinationWallet->id,
                    'transfer_type' => 'outgoing'
                ]),
                'status' => 'completed',
            ]);

            $destTransaction = $destinationWallet->transactions()->create([
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $destWallet->balance,
                'balance_after' => $destWallet->balance + $amount,
                'description' => $description ?: $this->generateTransferFromDescription($sourceWallet),
                'metadata' => array_merge($metadata, [
                    'transfer_from' => $sourceWallet->id,
                    'transfer_type' => 'incoming'
                ]),
                'status' => 'completed',
            ]);

            // Update wallet balances
            $sourceWallet->update(['balance' => $newSourceBalance]);
            $destWallet->update(['balance' => $destWallet->balance + $amount]);

            return [
                'source_transaction' => $sourceTransaction,
                'destination_transaction' => $destTransaction,
            ];
        });
    }

    /**
     * Generates a descriptive string for a wallet transfer.
     *
     * @param \App\Models\Wallet $destinationWallet The destination wallet.
     * @return string
     */
    private function generateTransferDescription(Wallet $destinationWallet): string
    {
        $ownerName = $destinationWallet->owner->name ?? 'Wallet';
        return "Transfer to {$ownerName} (ID: {$destinationWallet->id})";
    }

    /**
     * Generates a descriptive string for a wallet transfer from a source wallet.
     *
     * @param \App\Models\Wallet $sourceWallet The source wallet.
     * @return string
     */
    private function generateTransferFromDescription(Wallet $sourceWallet): string
    {
        $ownerName = $sourceWallet->owner->name ?? 'Wallet';
        return "Transfer from {$ownerName} (ID: {$sourceWallet->id})";
    }

    /**
     * Check if wallet has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get available balance (considering minimum balance)
     */
    public function getAvailableBalance(): float
    {
        $available = $this->balance;
        if ($this->min_balance) {
            $available = max(0, $available - $this->min_balance);
        }
        return $available;
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalance(string $displayCurrency = null): string
    {
        $currency = $displayCurrency ?? $this->currency ?? MoneyService::DEFAULT_CURRENCY;
        $fresh = $this->fresh();
        $balance = $fresh ? $fresh->balance : $this->balance;
        return MoneyService::format($balance ?? 0, $currency);
    }

    /**
     * Get balance history for a date range
     */
    public function getBalanceHistory($startDate = null, $endDate = null)
    {
        $query = $this->transactions()->orderBy('created_at', 'desc');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Get total credits for a period
     */
    public function getTotalCredits($startDate = null, $endDate = null): float
    {
        $query = $this->creditTransactions();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->sum('amount');
    }

    /**
     * Get total debits for a period
     */
    public function getTotalDebits($startDate = null, $endDate = null): float
    {
        $query = $this->debitTransactions();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->sum('amount');
    }

    /**
     * Check if auto-recharge is needed
     */
    public function needsAutoRecharge(): bool
    {
        return $this->auto_recharge && 
               $this->auto_recharge_threshold && 
               $this->balance <= $this->auto_recharge_threshold;
    }

    /**
     * Perform auto-recharge if needed
     */
    public function performAutoRecharge(): ?WalletTransaction
    {
        if (!$this->needsAutoRecharge()) {
            return null;
        }

        return $this->credit(
            $this->auto_recharge_amount,
            'Auto-recharge triggered',
            ['auto_recharge' => true]
        );
    }

    /**
     * Scope for active wallets
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for wallets with positive balance
     */
    public function scopeWithBalance($query)
    {
        return $query->where('balance', '>', 0);
    }

    /**
     * Scope for wallets needing auto-recharge
     */
    public function scopeNeedingRecharge($query)
    {
        return $query->where('auto_recharge', true)
                    ->whereColumn('balance', '<=', 'auto_recharge_threshold');
    }

    /**
     * Get all wallets in an integrator's scope
     * Includes: integrator's own wallet, all operators they created, all partners they created
     */
    public static function getIntegratorScopeWallets(int $integratorId): \Illuminate\Database\Eloquent\Collection
    {
        $wallets = new \Illuminate\Database\Eloquent\Collection();

        // 1. Integrator's own wallet
        $integratorModel = \App\Models\Integrator::find($integratorId);
        if ($integratorModel && $integratorModel->wallet) {
            $wallets->push($integratorModel->wallet);
        }

        // 2. Integrator user's wallet (CRITICAL: Ensure wallet exists for integrator user)
        if ($integratorModel && $integratorModel->user_id) {
            $integratorUser = \App\Models\User::find($integratorModel->user_id);
            if ($integratorUser) {
                // Ensure wallet is created if it doesn't exist
                $userWallet = $integratorUser->getOrCreateWallet();
                if ($userWallet) {
                    $wallets->push($userWallet);
                }
            }
        }

        // 3. All users associated with this integrator (direct or indirect)
        $userIds = \App\Models\User::where('integrator_id', $integratorId)
            ->pluck('id');

        $userWallets = static::whereIn('owner_id', $userIds)
            ->where('owner_type', 'App\Models\User')
            ->get();
        $wallets = $wallets->merge($userWallets);

        // 4. All partners associated with this integrator
        $partnerIds = \App\Models\Partner::where('integrator_id', $integratorId)->pluck('id');
        $partnerWallets = static::whereIn('owner_id', $partnerIds)
            ->where('owner_type', 'App\Models\Partner')
            ->get();
        $wallets = $wallets->merge($partnerWallets);

        return $wallets->unique('id')->values();
    }

    /**
     * Get all wallet transactions in an integrator's scope
     */
    public static function getIntegratorScopeTransactions(int $integratorId): \Illuminate\Database\Eloquent\Collection
    {
        $wallets = static::getIntegratorScopeWallets($integratorId);
        $walletIds = $wallets->pluck('id');

        return WalletTransaction::whereIn('wallet_id', $walletIds)->latest()->get();
    }
}
