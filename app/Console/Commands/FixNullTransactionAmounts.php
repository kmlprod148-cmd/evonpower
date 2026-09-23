<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixNullTransactionAmounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:fix-null-amounts {--dry-run : Mode simulation sans modification} {--limit=100 : Nombre maximum de transactions à traiter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige les transactions avec des montants nuls en recalculant les montants basés sur les réservations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Début de la correction des transactions avec montants nuls...');

        $isDryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        if ($isDryRun) {
            $this->warn('⚠️  Mode simulation activé - Aucune modification ne sera effectuée');
        }

        // Récupérer les transactions avec des montants nuls ou très faibles
        $transactions = Transaction::where(function ($query) {
            $query->whereNull('amount')
                  ->orWhere('amount', '<=', 0)
                  ->orWhere('price_total', '<=', 0);
        })
        ->whereNotNull('reservation_id')
        ->with(['reservation.pricingPlan', 'chargingPoint'])
        ->limit($limit)
        ->get();

        if ($transactions->isEmpty()) {
            $this->info('✅ Aucune transaction avec montant nul trouvée.');
            return 0;
        }

        $this->info("📊 {$transactions->count()} transactions à traiter...");

        $fixedCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        $progressBar = $this->output->createProgressBar($transactions->count());
        $progressBar->start();

        foreach ($transactions as $transaction) {
            try {
                $result = $this->fixTransactionAmount($transaction, $isDryRun);
                
                if ($result === 'fixed') {
                    $fixedCount++;
                } elseif ($result === 'skipped') {
                    $skippedCount++;
                } else {
                    $errorCount++;
                }

            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Erreur lors de la correction de la transaction', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Afficher le résumé
        $this->info('📈 Résumé de la correction :');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Transactions traitées', $transactions->count()],
                ['Transactions corrigées', $fixedCount],
                ['Transactions ignorées', $skippedCount],
                ['Erreurs', $errorCount],
            ]
        );

        if ($isDryRun) {
            $this->warn('💡 Pour appliquer les corrections, relancez la commande sans l\'option --dry-run');
        } else {
            $this->info('✅ Correction terminée avec succès !');
        }

        return 0;
    }

    /**
     * Corrige le montant d'une transaction spécifique
     */
    private function fixTransactionAmount(Transaction $transaction, bool $isDryRun): string
    {
        // Vérifier que la transaction a une réservation
        if (!$transaction->reservation) {
            $this->warn("Transaction {$transaction->id}: Aucune réservation associée");
            return 'skipped';
        }

        $reservation = $transaction->reservation;

        // Calculer le nouveau montant basé sur la réservation
        $newAmount = $this->calculateReservationAmount($reservation);

        if ($newAmount <= 0) {
            $this->warn("Transaction {$transaction->id}: Impossible de calculer un montant valide");
            return 'skipped';
        }

        // Vérifier si le montant a changé
        $currentAmount = $transaction->amount ?? 0;
        $currentPriceTotal = $transaction->price_total ?? 0;

        if ($currentAmount == $newAmount && $currentPriceTotal == $newAmount) {
            return 'skipped';
        }

        if (!$isDryRun) {
            // Mettre à jour la transaction
            DB::transaction(function () use ($transaction, $newAmount, $reservation) {
                $transaction->update([
                    'amount' => $newAmount,
                    'price_total' => $newAmount,
                    'updated_at' => now()
                ]);

                // Mettre à jour la réservation si nécessaire
                if ($reservation->estimated_cost <= 0) {
                    $reservation->update([
                        'estimated_cost' => $newAmount,
                        'actual_cost' => $newAmount
                    ]);
                }

                Log::info('Transaction corrigée avec succès', [
                    'transaction_id' => $transaction->id,
                    'reservation_id' => $reservation->id,
                    'old_amount' => $currentAmount,
                    'new_amount' => $newAmount
                ]);
            });
        }

        $this->line("Transaction {$transaction->id}: {$currentAmount} → {$newAmount}");
        return 'fixed';
    }

    /**
     * Calcule le montant de la réservation basé sur le plan tarifaire
     */
    private function calculateReservationAmount(Reservation $reservation): float
    {
        $costService = app(\App\Services\ReservationCostCalculationService::class);
        return $costService->calculateReservationCost($reservation);
    }
}
