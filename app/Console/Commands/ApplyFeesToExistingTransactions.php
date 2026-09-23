<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Services\TransactionFeeCalculationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplyFeesToExistingTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:apply-fees 
                            {--recalculate : Recalculer les frais pour toutes les transactions}
                            {--dry-run : Mode simulation sans modification}
                            {--limit=100 : Nombre maximum de transactions à traiter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Applique les frais aux transactions existantes qui n\'en ont pas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Application des frais aux transactions existantes...');
        
        $recalculate = $this->option('recalculate');
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        
        if ($dryRun) {
            $this->warn('⚠️  Mode simulation activé - Aucune modification ne sera effectuée');
        }
        
        // Construire la requête
        $query = Transaction::with(['chargingPoint.businessProfile', 'businessProfile'])
            ->where('transaction_type', 'client');
            
        if ($recalculate) {
            $this->info('📊 Recalcul des frais pour toutes les transactions...');
        } else {
            $query->where(function($q) {
                $q->where('activation_fee', 0)
                  ->orWhereNull('activation_fee');
            });
        }
        
        $transactions = $query->limit($limit)->get();
        
        if ($transactions->isEmpty()) {
            $this->info('✅ Aucune transaction à traiter');
            return;
        }
        
        $this->info("📊 Traitement de {$transactions->count()} transactions...");
        
        $feeService = app(TransactionFeeCalculationService::class);
        $processed = 0;
        $updated = 0;
        $errors = 0;
        
        $progressBar = $this->output->createProgressBar($transactions->count());
        $progressBar->start();
        
        foreach ($transactions as $transaction) {
            try {
                $processed++;
                
                // Calculer les frais
                $fees = $feeService->calculateAllFees($transaction);
                
                // Vérifier si des frais ont été calculés
                if ($fees['total_fees'] > 0 || $recalculate) {
                    if (!$dryRun) {
                        // Mettre à jour la transaction
                        $transaction->update([
                            'activation_fee' => $fees['activation_fee'],
                            'price_total' => $transaction->price_total + $fees['total_fees'],
                            'business_profile_fee_breakdown' => json_encode($fees['breakdown']),
                            'updated_at' => now()
                        ]);
                    }
                    $updated++;
                }
                
                $progressBar->advance();
                
            } catch (\Exception $e) {
                $errors++;
                Log::error('Erreur lors de l\'application des frais à la transaction ' . $transaction->id, [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
                $this->error("❌ Erreur transaction {$transaction->id}: " . $e->getMessage());
            }
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Résumé
        $this->info('📊 Résumé du traitement :');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Transactions traitées', $processed],
                ['Transactions mises à jour', $updated],
                ['Erreurs', $errors],
                ['Mode simulation', $dryRun ? 'Oui' : 'Non'],
                ['Recalcul complet', $recalculate ? 'Oui' : 'Non']
            ]
        );
        
        if ($updated > 0 && !$dryRun) {
            $this->info('✅ Frais appliqués avec succès !');
            $this->info('💡 Les nouvelles transactions incluront automatiquement les frais.');
        } elseif ($dryRun) {
            $this->info('💡 Exécutez la commande sans --dry-run pour appliquer les modifications.');
        }
    }
}
