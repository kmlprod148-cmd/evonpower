<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\TransactionRepartition;
use App\Services\TransactionCalculator;

class RecalculateRepartitions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recalculate:repartitions {--transaction-id=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcule les répartitions des transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔄 Recalcul des répartitions...");
        $this->newLine();

        $calculator = new TransactionCalculator();
        $processed = 0;
        $errors = 0;

        try {
            if ($this->option('transaction-id')) {
                // Recalculer une transaction spécifique
                $transactionId = $this->option('transaction-id');
                $transaction = Transaction::with(['chargingPoint', 'chargingPoint.businessProfile'])->find($transactionId);
                
                if (!$transaction) {
                    $this->error("❌ Transaction ID {$transactionId} non trouvée");
                    return 1;
                }
                
                $this->recalculateTransaction($transaction, $calculator);
                $processed = 1;
                
            } elseif ($this->option('all')) {
                // Recalculer toutes les transactions
                $this->info("📋 Recalcul de toutes les transactions...");
                
                $transactions = Transaction::with(['chargingPoint', 'chargingPoint.businessProfile'])
                    ->whereNotNull('price_total')
                    ->where('price_total', '>', 0)
                    ->get();
                
                $this->info("Trouvé {$transactions->count()} transactions à traiter...");
                $this->newLine();
                
                $bar = $this->output->createProgressBar($transactions->count());
                $bar->start();
                
                foreach ($transactions as $transaction) {
                    try {
                        $this->recalculateTransaction($transaction, $calculator, false);
                        $processed++;
                    } catch (\Exception $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("❌ Erreur transaction {$transaction->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
                
                $bar->finish();
                $this->newLine();
                
            } else {
                // Recalculer les répartitions incohérentes
                $this->info("🔍 Recherche des répartitions incohérentes...");
                
                $inconsistentRepartitions = TransactionRepartition::with('transaction')
                    ->get()
                    ->filter(function ($repartition) {
                        return !$repartition->isConsistent();
                    });
                
                if ($inconsistentRepartitions->isEmpty()) {
                    $this->info("✅ Toutes les répartitions sont cohérentes !");
                    return 0;
                }
                
                $this->info("Trouvé {$inconsistentRepartitions->count()} répartitions incohérentes...");
                $this->newLine();
                
                foreach ($inconsistentRepartitions as $repartition) {
                    try {
                        $transaction = $repartition->transaction;
                        $this->recalculateTransaction($transaction, $calculator);
                        $processed++;
                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("❌ Erreur transaction {$transaction->id}: " . $e->getMessage());
                    }
                }
            }
            
            $this->newLine();
            $this->info("📊 Résumé:");
            $this->info("  - Transactions traitées: {$processed}");
            $this->info("  - Erreurs: {$errors}");
            
            if ($errors == 0) {
                $this->info("🎉 Recalcul terminé avec succès !");
            } else {
                $this->warn("⚠️ Recalcul terminé avec {$errors} erreur(s)");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur générale: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Recalcule la répartition pour une transaction
     */
    private function recalculateTransaction(Transaction $transaction, TransactionCalculator $calculator, bool $verbose = true): void
    {
        if ($verbose) {
            $this->info("🔄 Transaction {$transaction->id}: {$transaction->price_total} {$transaction->currency}");
        }
        
        // Calculer la nouvelle répartition
        $calculation = $calculator->calculate($transaction);
        
        // Mettre à jour ou créer la répartition
        $repartition = TransactionRepartition::updateOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'admin_amount' => $calculation['admin'],
                'integrator_amount' => $calculation['integrator'],
                'operator_amount' => $calculation['operator'],
            ]
        );
        
        // Vérifier la cohérence
        $isConsistent = $repartition->isConsistent();
        
        if ($verbose) {
            if ($isConsistent) {
                $this->info("  ✅ Cohérente: Admin {$calculation['admin']}€, Intégrateur {$calculation['integrator']}€, Opérateur {$calculation['operator']}€");
            } else {
                $this->warn("  ⚠️ Incohérente: Total transaction {$transaction->price_total}€, Total répartition {$repartition->getTotalAmount()}€");
            }
        }
    }
}
