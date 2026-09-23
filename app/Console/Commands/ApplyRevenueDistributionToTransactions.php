<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\ReservationRevenueDistributionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ApplyRevenueDistributionToTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:apply-revenue-distribution 
                            {--dry-run : Afficher les calculs sans les appliquer}
                            {--transaction-id= : Appliquer à une transaction spécifique}
                            {--limit=100 : Limite du nombre de transactions à traiter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Applique la répartition des revenus aux transactions de réservation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $transactionId = $this->option('transaction-id');
        $limit = (int) $this->option('limit');

        $this->info('🚀 Début de l\'application de la répartition des revenus...');
        
        if ($isDryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé - Aucune modification ne sera appliquée');
        }

        try {
            $revenueService = app(ReservationRevenueDistributionService::class);
            $processedCount = 0;
            $successCount = 0;
            $errorCount = 0;

            // Récupérer les transactions à traiter
            $query = Transaction::with(['reservation.chargingPoint.businessProfile', 'reservation.chargingPoint.integrator', 'reservation.chargingPoint.partner'])
                ->whereNotNull('price_total')
                ->where('price_total', '>', 0);

            if ($transactionId) {
                $query->where('id', $transactionId);
                $this->info("🎯 Traitement de la transaction spécifique #{$transactionId}");
            } else {
                $this->info("📊 Traitement de toutes les transactions (limite: {$limit})");
            }

            $transactions = $query->limit($limit)->get();

            if ($transactions->isEmpty()) {
                $this->warn('❌ Aucune transaction trouvée à traiter');
                return;
            }

            $this->info("📋 {$transactions->count()} transaction(s) trouvée(s)");

            // Barre de progression
            $progressBar = $this->output->createProgressBar($transactions->count());
            $progressBar->start();

            foreach ($transactions as $transaction) {
                $processedCount++;

                try {
                    if ($isDryRun) {
                        // Mode dry-run : afficher les calculs sans les appliquer
                        $this->displayTransactionCalculation($transaction, $revenueService);
                    } else {
                        // Mode normal : appliquer la répartition
                        $revenueService->applyRevenueDistributionToTransaction($transaction);
                        $successCount++;
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Erreur lors du traitement de la transaction', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);

                    if (!$isDryRun) {
                        $this->error("❌ Erreur transaction #{$transaction->id}: {$e->getMessage()}");
                    }
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Résumé
            $this->displaySummary($processedCount, $successCount, $errorCount, $isDryRun);

        } catch (\Exception $e) {
            $this->error("💥 Erreur fatale: {$e->getMessage()}");
            Log::error('Erreur fatale dans la commande de répartition des revenus', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Affiche le calcul pour une transaction en mode dry-run
     */
    private function displayTransactionCalculation(Transaction $transaction, ReservationRevenueDistributionService $revenueService): void
    {
        $reservation = $transaction->reservation;
        if (!$reservation) {
            $this->warn("⚠️  Transaction #{$transaction->id} sans réservation");
            return;
        }

        $chargingPoint = $reservation->chargingPoint;
        $businessProfile = $chargingPoint->businessProfile;

        $this->line("📊 Transaction #{$transaction->id}");
        $this->line("   Montant: {$transaction->price_total} EUR");
        $this->line("   Réservation: #{$reservation->id}");
        $this->line("   Point de charge: {$chargingPoint->name}");

        if ($businessProfile) {
            $this->line("   Business Profile: {$businessProfile->name}");
            
            // Calculer la répartition
            $revenueCalculationService = app(\App\Services\RevenueDistributionCalculationService::class);
            $distribution = $revenueCalculationService->calculateRevenueDistribution($reservation, $transaction->price_total);

            $this->line("   📈 Répartition calculée:");
            $this->line("      Admin (frais): {$distribution['distribution']['admin_part']} EUR");
            $this->line("      Intégrateur: {$distribution['distribution']['integrator_part']} EUR");
            $this->line("      Partenaire: {$distribution['distribution']['partner_part']} EUR");
            $this->line("      Opérateur: {$distribution['distribution']['operator_part']} EUR");
        } else {
            $this->line("   ⚠️  Aucun business profile configuré");
        }

        $this->line("");
    }

    /**
     * Affiche le résumé du traitement
     */
    private function displaySummary(int $processedCount, int $successCount, int $errorCount, bool $isDryRun): void
    {
        $this->info('📊 Résumé du traitement:');
        $this->line("   Transactions traitées: {$processedCount}");
        
        if ($isDryRun) {
            $this->line("   Mode: DRY-RUN (aucune modification appliquée)");
        } else {
            $this->line("   Succès: {$successCount}");
            $this->line("   Erreurs: {$errorCount}");
        }

        if ($errorCount > 0) {
            $this->warn("⚠️  {$errorCount} erreur(s) rencontrée(s) - vérifiez les logs");
        } else {
            $this->info("✅ Traitement terminé avec succès!");
        }
    }
}
