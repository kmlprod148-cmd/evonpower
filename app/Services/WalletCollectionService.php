<?php

namespace App\Services;

use App\Enums\WalletCollectionMode;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletCollectionService
{
    /**
     * Update wallet collection settings
     */
    public function updateCollectionSettings(Wallet $wallet, array $settings): Wallet
    {
        $wallet->update([
            'collection_mode' => $settings['collection_mode'] ?? $wallet->collection_mode,
            'collection_threshold' => $settings['collection_threshold'] ?? null,
            'collection_target' => $settings['collection_target'] ?? null,
            'collection_metadata' => array_merge($wallet->collection_metadata ?? [], [
                'updated_at' => now()->toISOString(),
                'updated_by' => $settings['updated_by'] ?? null,
            ]),
        ]);

        return $wallet->fresh();
    }

    /**
     * Initiate a collection for a wallet (mark balance as "to be collected")
     */
    public function initiateCollection(Wallet $wallet, float $amount, string $description = null): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $description) {
            // Lock the wallet
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            // Check if sufficient balance
            if ($wallet->balance < $amount) {
                throw new \Exception('Insufficient balance for collection');
            }

            // Update balance_to_collect
            $newToCollect = $wallet->balance_to_collect + $amount;
            $wallet->update(['balance_to_collect' => $newToCollect]);

            // Create a transaction record for the collection initiation
            $transaction = $wallet->transactions()->create([
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance - $amount,
                'description' => $description ?? 'Collection initiated',
                'metadata' => [
                    'collection_initiated' => true,
                    'collection_amount' => $amount,
                    'balance_to_collect_before' => $wallet->balance_to_collect - $amount,
                    'balance_to_collect_after' => $newToCollect,
                ],
                'status' => 'completed',
            ]);

            // Optionally reduce available balance
            // This depends on business logic - whether "to be collected" reduces available balance

            return $transaction;
        });
    }

    /**
     * Complete a collection (actually transfer the funds)
     */
    public function completeCollection(Wallet $wallet, float $amount, array $bankDetails = []): bool
    {
        return DB::transaction(function () use ($wallet, $amount, $bankDetails) {
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            // Check if there's enough to collect
            if ($wallet->balance_to_collect < $amount) {
                throw new \Exception('Insufficient balance to collect');
            }

            // Update balance_to_collect
            $wallet->update([
                'balance_to_collect' => $wallet->balance_to_collect - $amount,
                'last_collected_amount' => $amount,
                'last_collected_at' => now(),
            ]);

            // Create a transaction for the collection completion
            $wallet->transactions()->create([
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance - $amount,
                'description' => 'Collection completed - ' . ($bankDetails['bank_name'] ?? 'Bank transfer'),
                'metadata' => [
                    'collection_completed' => true,
                    'amount_collected' => $amount,
                    'bank_name' => $bankDetails['bank_name'] ?? null,
                    'bank_reference' => $bankDetails['reference'] ?? null,
                ],
                'status' => 'completed',
            ]);

            // Update actual balance
            $wallet->update([
                'balance' => $wallet->balance - $amount,
            ]);

            return true;
        });
    }

    /**
     * Cancel a collection (return funds to available balance)
     */
    public function cancelCollection(Wallet $wallet, float $amount): bool
    {
        return DB::transaction(function () use ($wallet, $amount) {
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            if ($wallet->balance_to_collect < $amount) {
                throw new \Exception('Insufficient balance to cancel collection');
            }

            // Update balance_to_collect
            $wallet->update([
                'balance_to_collect' => $wallet->balance_to_collect - $amount,
            ]);

            // Create a transaction for the collection cancellation
            $wallet->transactions()->create([
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance + $amount,
                'description' => 'Collection cancelled',
                'metadata' => [
                    'collection_cancelled' => true,
                    'amount_returned' => $amount,
                ],
                'status' => 'completed',
            ]);

            return true;
        });
    }

    /**
     * Check if auto-collection should be triggered
     */
    public function shouldAutoCollect(Wallet $wallet): bool
    {
        if ($wallet->collection_mode !== WalletCollectionMode::AUTO) {
            return false;
        }

        if (!$wallet->collection_threshold) {
            return false;
        }

        return $wallet->balance >= $wallet->collection_threshold;
    }

    /**
     * Perform auto-collection for a wallet
     */
    public function performAutoCollection(Wallet $wallet): ?array
    {
        if (!$this->shouldAutoCollect($wallet)) {
            return null;
        }

        $collectionAmount = $wallet->collection_target ?? $wallet->balance;

        try {
            $transaction = $this->initiateCollection(
                $wallet,
                $collectionAmount,
                'Auto-collection triggered'
            );

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'amount' => $collectionAmount,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get collection summary for a wallet
     */
    public function getCollectionSummary(Wallet $wallet): array
    {
        return [
            'wallet_id' => $wallet->id,
            'balance' => $wallet->balance,
            'balance_to_collect' => $wallet->balance_to_collect,
            'collection_mode' => $wallet->collection_mode,
            'collection_threshold' => $wallet->collection_threshold,
            'collection_target' => $wallet->collection_target,
            'last_collected_amount' => $wallet->last_collected_amount,
            'last_collected_at' => $wallet->last_collected_at,
            'available_for_collection' => $wallet->balance - $wallet->balance_to_collect,
            'should_auto_collect' => $this->shouldAutoCollect($wallet),
        ];
    }

    /**
     * Get all wallets that need auto-collection
     */
    public function getWalletsNeedingAutoCollection(): \Illuminate\Database\Eloquent\Collection
    {
        return Wallet::where('collection_mode', WalletCollectionMode::AUTO)
            ->whereNotNull('collection_threshold')
            ->whereColumn('balance', '>=', 'collection_threshold')
            ->get();
    }

    /**
     * Process auto-collections for all eligible wallets
     */
    public function processAutoCollections(): array
    {
        $wallets = $this->getWalletsNeedingAutoCollection();
        $results = [];

        foreach ($wallets as $wallet) {
            $result = $this->performAutoCollection($wallet);
            $results[] = [
                'wallet_id' => $wallet->id,
                'result' => $result,
            ];
        }

        return $results;
    }

    /**
     * Get collection history for a wallet
     */
    public function getCollectionHistory(Wallet $wallet, int $limit = 50)
    {
        return $wallet->transactions()
            ->where(function ($query) {
                $query->where('metadata->collection_initiated', true)
                    ->orWhere('metadata->collection_completed', true)
                    ->orWhere('metadata->collection_cancelled', true);
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
