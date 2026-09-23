<?php

namespace App\Console\Commands;

use App\Jobs\ProcessReservationExpirationJob;
use App\Jobs\ProcessCreditPaymentTimeoutJob;
use Illuminate\Console\Command;

class ProcessReservations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reservations:process 
        {--expired : Traiter les réservations expirées}
        {--timeout : Traiter les expirations de confirmation de paiement}
        {--all : Traiter toutes les tâches de réservation}
        {--batch-size= : Taille du batch (défaut: 100)}
        {--force : Forcer le déverrouillage même si occupation}';

    /**
     * The console command description.
     */
    protected $description = 'Traite les tâches de réservation (expirations, timeouts)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batchSize = (int) $this->option('batch-size') ?: 100;
        $force = $this->option('force') ?: false;

        if ($this->option('all') || $this->option('expired')) {
            $this->info('Traitement des réservations expirées...');
            
            ProcessReservationExpirationJob::dispatch($batchSize, $force)
                ->onQueue('reservations');

            $this->info('Job de traitement des expirations dispatché.');
        }

        if ($this->option('all') || $this->option('timeout')) {
            $this->info('Traitement des expirations de confirmation de paiement...');
            
            ProcessCreditPaymentTimeoutJob::dispatch($batchSize)
                ->onQueue('high');

            $this->info('Job de traitement des timeouts dispatché.');
        }

        if (!$this->option('all') && !$this->option('expired') && !$this->option('timeout')) {
            // Par défaut, tout traiter
            $this->info('Traitement complet des réservations...');
            
            ProcessReservationExpirationJob::dispatch($batchSize, $force)
                ->onQueue('reservations');

            ProcessCreditPaymentTimeoutJob::dispatch($batchSize)
                ->onQueue('high');

            $this->info('Jobs dispatchés.');
        }

        return Command::SUCCESS;
    }
}
