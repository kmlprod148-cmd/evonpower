<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixAmountInconsistencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:fix-amount-inconsistencies 
                            {--dry-run : Afficher les incohérences sans les corriger}
                            {--limit=100 : Limiter le nombre de transactions à traiter}
                            {--min-difference=0.01 : Différence minimale pour considérer une incohérence}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corriger les incohérences entre le montant de transaction et la somme des parts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $minDifference = (float) $this->option('min-difference');

        $this->info('🔍 Recherche des incohérences dans les montants des transactions...');
        
        if ($dryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé : aucune modification ne sera effectuée');
        }

        // Trouver les transactions avec TransactionDetail
        $transactionDetails = TransactionDetail::with('transaction')
            ->has('transaction')
            ->limit($limit)
            ->get();

        $inconsistencies = [];
        $fixed = 0;
        $total = $transactionDetails->count();

        $this->info("📊 Analyse de {$total} transactions...");
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($transactionDetails as $transactionDetail) {
            $validation = $transactionDetail->validateAmountConsistency();
            
            if (!$validation['is_consistent'] && $validation['difference'] >= $minDifference) {
                $inconsistencies[] = [
                    'transaction_id' => $transactionDetail->transaction_id,
                    'transaction_detail_id' => $transactionDetail->id,
                    'transaction_amount' => $validation['transaction_amount'],
                    'shares_total' => $validation['shares_total'],
                    'difference' => $validation['difference'],
                    'reservation_id' => $transactionDetail->transaction->reservation_id ?? null,
                ];

                if (!$dryRun && $validation['shares_total'] > 0) {
                    try {
                        DB::beginTransaction();
                        
                        // Corriger l'incohérence
                        $transactionDetail->transaction->update([
                            'amount' => $validation['shares_total']
                        ]);
                        
                        // Ajouter une note dans les métadonnées
                        $metadata = $transactionDetail->transaction->metadata ?? [];
                        $metadata['amount_fixed_at'] = now()->toDateTimeString();
                        $metadata['amount_fixed_from'] = $validation['transaction_amount'];
                        $metadata['amount_fixed_to'] = $validation['shares_total'];
                        $metadata['amount_fixed_difference'] = $validation['difference'];
                        
                        $transactionDetail->transaction->update(['metadata' => $metadata]);
                        
                        DB::commit();
                        $fixed++;
                        
                        Log::info('Incohérence corrigée par commande FixAmountInconsistencies', [
                            'transaction_id' => $transactionDetail->transaction_id,
                            'transaction_detail_id' => $transactionDetail->id,
                            'ancien_montant' => $validation['transaction_amount'],
                            'nouveau_montant' => $validation['shares_total'],
                            'difference' => $validation['difference']
                        ]);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->error("❌ Erreur lors de la correction de la transaction #{$transactionDetail->transaction_id}: " . $e->getMessage());
                        Log::error('Erreur lors de la correction d\'incohérence', [
                            'transaction_id' => $transactionDetail->transaction_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Afficher le résumé
        $this->info('📈 Résumé des résultats :');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Transactions analysées', $total],
                ['Incohérences détectées', count($inconsistencies)],
                ['Incohérences corrigées', $dryRun ? 0 : $fixed],
                ['Différence minimale', $minDifference . ' €'],
            ]
        );

        if (!empty($inconsistencies)) {
            $this->newLine();
            $this->warn('📋 Détails des incohérences détectées :');
            
            $tableData = array_map(function ($inc) {
                return [
                    $inc['transaction_id'],
                    $inc['reservation_id'] ?? 'N/A',
                    number_format($inc['transaction_amount'], 2) . ' €',
                    number_format($inc['shares_total'], 2) . ' €',
                    number_format($inc['difference'], 2) . ' €',
                ];
            }, array_slice($inconsistencies, 0, 20)); // Limiter à 20 pour l'affichage
            
            $this->table(
                ['Transaction ID', 'Réservation ID', 'Montant Transaction', 'Somme des Parts', 'Différence'],
                $tableData
            );

            if (count($inconsistencies) > 20) {
                $this->info('... et ' . (count($inconsistencies) - 20) . ' autres incohérences');
            }
        } else {
            $this->info('✅ Aucune incohérence détectée !');
        }

        if ($dryRun && !empty($inconsistencies)) {
            $this->newLine();
            $this->info('💡 Pour corriger ces incohérences, exécutez la commande sans l\'option --dry-run');
        }

        return Command::SUCCESS;
    }
}

