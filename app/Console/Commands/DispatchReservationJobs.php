<?php

namespace App\Console\Commands;

use App\Jobs\ProcessReservationExpirationJob;
use App\Jobs\ProcessCreditPaymentTimeoutJob;
use Illuminate\Console\Command;

class DispatchReservationJobs extends Command
{
    protected $signature = 'reservations:dispatch-jobs 
        {--expired= : Traiter les expirations}
        {--timeout= : Traiter les timeouts}
        {--batch-size=100 : Taille du batch}
        {--force : Forcer le déverrouillage}';

    protected $description = 'Dispatch les jobs de traitement des réservations';

    public function handle(): int
    {
        $processExpired = filter_var($this->option('expired') ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $processTimeout = filter_var($this->option('timeout') ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $batchSize = (int) $this->option('batch-size') ?: 100;
        $force = $this->option('force') ?: false;

        $this->info("Dispatch des jobs de réservation (batch: {$batchSize})");

        if ($processExpired) {
            $this->line('  - Expirations...');
            ProcessReservationExpirationJob::dispatch($batchSize, $force)
                ->onQueue('reservations');
        }

        if ($processTimeout) {
            $this->line('  - Timeouts...');
            ProcessCreditPaymentTimeoutJob::dispatch($batchSize)
                ->onQueue('high');
        }

        $this->info('Jobs dispatchés avec succès.');

        return Command::SUCCESS;
    }
}
