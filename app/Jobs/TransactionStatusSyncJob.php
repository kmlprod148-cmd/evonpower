<?php

namespace App\Jobs;

use App\Services\TransactionStatusSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job planifié : corrige automatiquement les transactions "pending" dont la réservation est payée.
 * Prépayé et postpayé - aucune intervention du propriétaire de la borne requise.
 */
class TransactionStatusSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 120;

    public function handle(TransactionStatusSyncService $syncService): void
    {
        $count = $syncService->syncAllPendingTransactionsForPaidReservations();

        if ($count > 0) {
            Log::info('TransactionStatusSyncJob: Transactions corrigées', ['count' => $count]);
        }
    }
}
