<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UnifiedRevenueDistributionService;
use App\Models\Transaction;
use App\Models\TransactionRepartition;
use Illuminate\Support\Facades\Log;

/**
 * Commande pour corriger toutes les erreurs de calcul dans la répartition des revenus
 */
class FixRevenueDistributionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'revenue:fix-distribution 
                            {--validate-only : Valider sans corriger}
                            {--force : Forcer la correction même si des répartitions sont cohérentes}
                            {--transaction-id= : Corriger une transaction spécifique}
                            {--batch-size=100 : Taille des lots pour le traitement}';

    /**
     * The console command description.
     */
    protected $description = 'Corrige toutes les erreurs de calcul dans la répartition des revenus';

    protected $unifiedService;

    public function __construct(UnifiedRevenueDistributionService $unifiedService)
    {
        parent::__construct();
        $this->unifiedService = $unifiedService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Correction des erreurs de calcul dans la répartition des revenus...');
        $this->info('================================================================');

        try {
            // Validation uniquement
            if ($this->option('validate-only')) {
                return $this->validateRepartitions();
            }

            // Transaction spécifique
            if ($transactionId = $this->option('transaction-id')) {
                return $this->fixSpecificTransaction($transactionId);
            }

            // Correction complète
            return $this->fixAllRepartitions();

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la correction: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Valide toutes les répartitions
     */
    private function validateRepartitions(): int
    {
        $this->info('🔍 Validation des répartitions existantes...');
        
        $results = $this->unifiedService->validateAllRepartitions();
        
        $this->info("📊 Résultats de validation:");
        $this->info("   Total des répartitions: {$results['total']}");
        $this->info("   Répartitions cohérentes: {$results['consistent']}");
        $this->info("   Répartitions incohérentes: {$results['inconsistent']}");
        
        if ($results['inconsistent'] > 0) {
            $this->warn("⚠️  {$results['inconsistent']} répartitions incohérentes détectées:");
            
            foreach ($results['inconsistent_details'] as $detail) {
                $this->warn("   Transaction #{$detail['transaction_id']}:");
                $this->warn("     - Montant transaction: {$detail['transaction_amount']} EUR");
                $this->warn("     - Total répartition: {$detail['repartition_total']} EUR");
                $this->warn("     - Différence: {$detail['difference']} EUR");
            }
            
            $this->info("\n💡 Utilisez --force pour corriger ces incohérences");
        } else {
            $this->info('✅ Toutes les répartitions sont cohérentes!');
        }

        return 0;
    }

    /**
     * Corrige une transaction spécifique
     */
    private function fixSpecificTransaction(int $transactionId): int
    {
        $this->info("🔧 Correction de la transaction #{$transactionId}...");
        
        try {
            $transaction = Transaction::with(['repartitions', 'chargingPoint.businessProfile'])->find($transactionId);
            
            if (!$transaction) {
                $this->error("❌ Transaction #{$transactionId} non trouvée");
                return 1;
            }

            $repartition = $this->unifiedService->createOrUpdateRepartition($transaction);
            
            if ($repartition->isConsistent()) {
                $this->info("✅ Transaction #{$transactionId} corrigée avec succès");
                $this->displayRepartitionDetails($repartition);
            } else {
                $this->error("❌ Transaction #{$transactionId} toujours incohérente après correction");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la correction de la transaction #{$transactionId}: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Corrige toutes les répartitions
     */
    private function fixAllRepartitions(): int
    {
        $this->info('🔧 Correction de toutes les répartitions...');
        
        // D'abord valider
        $validationResults = $this->unifiedService->validateAllRepartitions();
        
        if ($validationResults['inconsistent'] == 0 && !$this->option('force')) {
            $this->info('✅ Toutes les répartitions sont déjà cohérentes!');
            return 0;
        }

        if ($validationResults['inconsistent'] > 0) {
            $this->warn("⚠️  {$validationResults['inconsistent']} répartitions incohérentes détectées");
            
            if (!$this->confirm('Voulez-vous corriger ces incohérences?')) {
                $this->info('❌ Correction annulée par l\'utilisateur');
                return 0;
            }
        }

        // Afficher une barre de progression
        $totalTransactions = Transaction::count();
        $progressBar = $this->output->createProgressBar($totalTransactions);
        $progressBar->start();

        $results = [
            'total_processed' => 0,
            'fixed' => 0,
            'errors' => 0,
            'details' => []
        ];

        try {
            // Traitement par lots
            $batchSize = (int) $this->option('batch-size');
            $transactions = Transaction::with(['repartitions', 'chargingPoint.businessProfile'])
                ->chunk($batchSize, function ($transactionBatch) use (&$results, $progressBar) {
                    foreach ($transactionBatch as $transaction) {
                        $results['total_processed']++;
                        
                        try {
                            $repartition = $this->unifiedService->createOrUpdateRepartition($transaction);
                            
                            if ($repartition->isConsistent()) {
                                $results['fixed']++;
                            } else {
                                $results['errors']++;
                                $results['details'][] = [
                                    'transaction_id' => $transaction->id,
                                    'status' => 'still_inconsistent',
                                    'error' => 'Répartition toujours incohérente après correction'
                                ];
                            }
                            
                        } catch (\Exception $e) {
                            $results['errors']++;
                            $results['details'][] = [
                                'transaction_id' => $transaction->id,
                                'status' => 'error',
                                'error' => $e->getMessage()
                            ];
                        }
                        
                        $progressBar->advance();
                    }
                });

            $progressBar->finish();
            $this->newLine();

            // Afficher les résultats
            $this->displayResults($results);

            return 0;

        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine();
            $this->error('❌ Erreur lors de la correction: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Affiche les détails d'une répartition
     */
    private function displayRepartitionDetails(TransactionRepartition $repartition): void
    {
        $this->info("📊 Détails de la répartition:");
        $this->info("   - Admin: {$repartition->admin_amount} EUR");
        $this->info("   - Intégrateur: {$repartition->integrator_amount} EUR");
        $this->info("   - Opérateur: {$repartition->operator_amount} EUR");
        $this->info("   - Total: {$repartition->getTotalAmount()} EUR");
        $this->info("   - Cohérent: " . ($repartition->isConsistent() ? '✅ OUI' : '❌ NON'));
    }

    /**
     * Affiche les résultats de la correction
     */
    private function displayResults(array $results): void
    {
        $this->info("\n📊 Résultats de la correction:");
        $this->info("   Total traité: {$results['total_processed']}");
        $this->info("   Corrigées: {$results['fixed']}");
        $this->info("   Erreurs: {$results['errors']}");

        if ($results['errors'] > 0) {
            $this->warn("\n⚠️  Détails des erreurs:");
            foreach ($results['details'] as $detail) {
                if ($detail['status'] === 'error') {
                    $this->warn("   Transaction #{$detail['transaction_id']}: {$detail['error']}");
                } elseif ($detail['status'] === 'still_inconsistent') {
                    $this->warn("   Transaction #{$detail['transaction_id']}: {$detail['error']}");
                }
            }
        }

        if ($results['fixed'] > 0) {
            $this->info("\n✅ {$results['fixed']} répartitions corrigées avec succès!");
        }

        // Validation finale
        $this->info("\n🔍 Validation finale...");
        $finalValidation = $this->unifiedService->validateAllRepartitions();
        
        $this->info("📊 État final:");
        $this->info("   Répartitions cohérentes: {$finalValidation['consistent']}");
        $this->info("   Répartitions incohérentes: {$finalValidation['inconsistent']}");

        if ($finalValidation['inconsistent'] == 0) {
            $this->info("\n🎉 Toutes les répartitions sont maintenant cohérentes!");
        } else {
            $this->warn("\n⚠️  {$finalValidation['inconsistent']} répartitions restent incohérentes");
        }
    }
}
