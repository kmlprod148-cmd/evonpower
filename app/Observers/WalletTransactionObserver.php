<?php

namespace App\Observers;

use App\Events\ClientBalanceUpdated;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\ClientBalanceService;
use Illuminate\Support\Facades\Log;

/**
 * Observe les WalletTransaction pour dispatcher ClientBalanceUpdated
 * quand le solde d'un client (User) change.
 */
class WalletTransactionObserver
{
    public function created(WalletTransaction $transaction): void
    {
        if ($transaction->status !== 'completed') {
            return;
        }

        $wallet = $transaction->wallet;
        if (!$wallet) {
            return;
        }

        $owner = $wallet->owner;
        if (!$owner instanceof User) {
            return;
        }

        try {
            $balanceService = app(ClientBalanceService::class);
            $balance = $balanceService->getBalance($owner);
            $formatted = $balanceService->getFormatted($owner);
            $currency = $wallet->currency ?? 'EUR';

            event(new ClientBalanceUpdated($owner, $balance, $formatted, $currency));
        } catch (\Throwable $e) {
            Log::debug('WalletTransactionObserver: could not dispatch ClientBalanceUpdated', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
