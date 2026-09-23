<?php

namespace App\Console\Commands;

use App\Jobs\SyncSteVePostpaidTransactionsJob;
use Illuminate\Console\Command;

class SyncStevePostpaidCommand extends Command
{
    protected $signature = 'postpaid:sync-steve
                            {--limit=20 : Nombre max de sessions à vérifier}
                            {--max-age=60 : Âge max des sessions en minutes}
                            {--queue : Exécuter via la queue}';

    protected $description = 'Synchronise les sessions postpayées stoppées côté Steve sans paiement EVON';

    public function handle(): int
    {
        if (!config('steve.postpaid_sync_enabled', true)) {
            $this->warn('Sync postpayé Steve désactivée (steve.postpaid_sync_enabled=false)');
            return 1;
        }

        $limit = (int) $this->option('limit');
        $maxAge = (int) $this->option('max-age');

        $this->info("Synchronisation des sessions postpayées Steve (limit={$limit}, max_age={$maxAge}min)...");

        if ($this->option('queue')) {
            SyncSteVePostpaidTransactionsJob::dispatch($limit, $maxAge);
            $this->info('Job dispatché dans la queue.');
            return 0;
        }

        $job = new SyncSteVePostpaidTransactionsJob($limit, $maxAge);
        $job->handle(
            app(\App\Services\SteVeApiEndpointService::class),
            app(\App\Services\StevePostpaidPaymentService::class)
        );

        $this->info('Synchronisation terminée.');
        return 0;
    }
}
