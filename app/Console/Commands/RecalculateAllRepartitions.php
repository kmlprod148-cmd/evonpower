<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\TransactionRepartition;
use App\Services\TransactionCalculator;
use Illuminate\Console\Command;

class RecalculateAllRepartitions extends Command
{
    protected $signature = 'transactions:recalculate-repartitions';
    protected $description = 'Recalcule toutes les répartitions de transactions';

    public function handle()
    {
        $this->info('🔄 Recalcul de toutes les répartitions...');
        
        $calculator = new TransactionCalculator();
        $transactions = Transaction::all();
        
        $this->info("📊 Transactions trouvées: {$transactions->count()}");
        
        $updated = 0;
        $created = 0;
        
        foreach ($transactions as $transaction) {
            try {
                $calculation = $calculator->calculate($transaction);
                
                $repartition = TransactionRepartition::updateOrCreate(
                    ['transaction_id' => $transaction->id],
                    [
                        'admin_amount' => $calculation['admin'],
                        'integrator_amount' => $calculation['integrator'],
                        'operator_amount' => $calculation['operator'],
                    ]
                );
                
                if ($repartition->wasRecentlyCreated) {
                    $created++;
                    $this->line("✅ Répartition créée pour transaction #{$transaction->id}");
                } else {
                    $updated++;
                    $this->line("🔄 Répartition mise à jour pour transaction #{$transaction->id}");
                }
                
                $this->line("   - Admin: {$repartition->admin_amount} EUR");
                $this->line("   - Intégrateur: {$repartition->integrator_amount} EUR");
                $this->line("   - Opérateur: {$repartition->operator_amount} EUR");
                $this->line("   - Total: {$repartition->getTotalAmount()} EUR");
                $this->line("");
                
            } catch (\Exception $e) {
                $this->error("❌ Erreur pour transaction #{$transaction->id}: " . $e->getMessage());
            }
        }
        
        $this->info("🎯 Recalcul terminé !");
        $this->info("   - Répartitions créées: {$created}");
        $this->info("   - Répartitions mises à jour: {$updated}");
        
        return 0;
    }
}