<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixApprovedReservationsTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:fix-approved-reservations 
                            {--dry-run : Run without making changes}
                            {--force : Force update all transactions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corriger les transactions en pending pour les réservations approuvées';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Recherche des transactions à corriger...');

        // Trouver toutes les transactions de réservations approuvées qui sont en pending
        $transactions = Transaction::whereHas('reservation', function($query) {
            $query->where('status', 'approved');
        })
        ->where('status', 'pending')
        ->with('reservation')
        ->get();

        if ($transactions->isEmpty()) {
            $this->info('✅ Aucune transaction à corriger.');
            return 0;
        }

        $this->warn("⚠️  Trouvé {$transactions->count()} transaction(s) en pending pour des réservations approuvées.");

        if ($this->option('dry-run')) {
            $this->info('🔍 Mode dry-run - Aucune modification ne sera effectuée.');
            foreach ($transactions as $transaction) {
                $this->line("  - Transaction #{$transaction->id} (Réservation #{$transaction->reservation_id}): pending → completed");
            }
            return 0;
        }

        $updated = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            foreach ($transactions as $transaction) {
                try {
                    $transaction->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);

                    $updated++;
                    $this->info("✅ Transaction #{$transaction->id} mise à jour (Réservation #{$transaction->reservation_id})");

                    Log::info('Transaction corrigée automatiquement', [
                        'transaction_id' => $transaction->id,
                        'reservation_id' => $transaction->reservation_id,
                        'old_status' => 'pending',
                        'new_status' => 'completed',
                    ]);
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("❌ Erreur lors de la mise à jour de la transaction #{$transaction->id}: {$e->getMessage()}");
                    Log::error('Erreur lors de la correction de transaction', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();
            $this->info("✅ {$updated} transaction(s) corrigée(s) avec succès.");
            
            if ($errors > 0) {
                $this->warn("⚠️  {$errors} erreur(s) rencontrée(s).");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erreur critique: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}

