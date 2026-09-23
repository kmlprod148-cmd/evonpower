<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionHierarchy;
use App\Models\TransactionRepartition;
use App\Models\RevenueShare;
use App\Models\FinancialTransaction;
use App\Models\GainDistribution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteAllReservationsTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:delete-all-reservations-transactions 
                            {--force : Force la suppression sans confirmation}
                            {--dry-run : Affiche seulement les statistiques sans supprimer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprime toutes les réservations et transactions de la base de données (PRODUCTION)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->warn('⚠️  ATTENTION: Cette commande va supprimer TOUTES les réservations et transactions !');
        $this->newLine();

        // Vérifier l'environnement
        $environment = config('app.env');
        $this->info("Environnement actuel: {$environment}");
        
        if ($environment === 'production' && !$this->option('force')) {
            $this->error('❌ Vous êtes en PRODUCTION !');
            $this->error('Utilisez --force pour confirmer la suppression en production.');
            return 1;
        }

        // Afficher les statistiques
        $this->displayStatistics();

        // Si dry-run, arrêter ici
        if ($this->option('dry-run')) {
            $this->info('Mode dry-run activé - Aucune suppression effectuée.');
            return 0;
        }

        // Demander confirmation
        if (!$this->option('force')) {
            $this->newLine();
            $this->warn('⚠️  Cette opération est IRRÉVERSIBLE !');
            $confirmation = $this->ask('Tapez "SUPPRIMER" pour confirmer la suppression de toutes les données');
            
            if ($confirmation !== 'SUPPRIMER') {
                $this->info('Opération annulée.');
                return 0;
            }
        }

        // Confirmation finale
        $this->newLine();
        $this->error('⚠️  DERNIÈRE CONFIRMATION REQUISE ⚠️');
        $finalConfirmation = $this->ask('Êtes-vous ABSOLUMENT SÛR ? Tapez "OUI JE SUIS SÛR" pour continuer');
        
        if ($finalConfirmation !== 'OUI JE SUIS SÛR') {
            $this->info('Opération annulée.');
            return 0;
        }

        // Effectuer la suppression
        return $this->performDeletion();
    }

    /**
     * Affiche les statistiques avant suppression
     */
    protected function displayStatistics()
    {
        $this->info('📊 Statistiques actuelles:');
        $this->newLine();

        // Compter toutes les tables liées
        $financialTransactionsCount = FinancialTransaction::count();
        $this->line("  Financial Transactions: {$financialTransactionsCount}");

        $revenueSharesCount = RevenueShare::count();
        $this->line("  Revenue Shares: {$revenueSharesCount}");

        $transactionHierarchiesCount = TransactionHierarchy::count();
        $this->line("  Transaction Hierarchies: {$transactionHierarchiesCount}");

        $gainDistributionsCount = GainDistribution::count();
        $this->line("  Gain Distributions: {$gainDistributionsCount}");

        $transactionRepartitionsCount = TransactionRepartition::count();
        $this->line("  Transaction Repartitions: {$transactionRepartitionsCount}");

        $transactionDetailsCount = TransactionDetail::count();
        $this->line("  Transaction Details: {$transactionDetailsCount}");

        // Compter les transactions
        $transactionsCount = Transaction::count();
        $this->line("  Transactions: {$transactionsCount}");

        // Compter les réservations
        $reservationsCount = Reservation::count();
        $this->line("  Réservations: {$reservationsCount}");

        // Statistiques supplémentaires
        $this->newLine();
        $this->info('📈 Détails supplémentaires:');
        
        $transactionsByStatus = Transaction::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        if (!empty($transactionsByStatus)) {
            $this->line('  Transactions par statut:');
            foreach ($transactionsByStatus as $status => $count) {
                $this->line("    - {$status}: {$count}");
            }
        }

        $reservationsByStatus = Reservation::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        if (!empty($reservationsByStatus)) {
            $this->line('  Réservations par statut:');
            foreach ($reservationsByStatus as $status => $count) {
                $this->line("    - {$status}: {$count}");
            }
        }

        // Montant total des transactions
        $totalAmount = Transaction::sum('amount');
        $this->newLine();
        $this->line("  Montant total des transactions: " . number_format($totalAmount, 2) . " €");

        $this->newLine();
    }

    /**
     * Effectue la suppression des données
     */
    protected function performDeletion()
    {
        $this->newLine();
        $this->info('🗑️  Début de la suppression...');
        $this->newLine();

        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            // Étape 1: Supprimer les Financial Transactions (dépend de RevenueShare)
            $this->info('Étape 1/8: Suppression des Financial Transactions...');
            $financialTransactionsDeleted = FinancialTransaction::query()->delete();
            $this->info("  ✓ {$financialTransactionsDeleted} Financial Transactions supprimées");

            // Étape 2: Supprimer les Revenue Shares (dépend de Transaction)
            $this->info('Étape 2/8: Suppression des Revenue Shares...');
            $revenueSharesDeleted = RevenueShare::query()->delete();
            $this->info("  ✓ {$revenueSharesDeleted} Revenue Shares supprimés");

            // Étape 3: Supprimer les Transaction Hierarchies (dépend de Transaction)
            $this->info('Étape 3/8: Suppression des Transaction Hierarchies...');
            $transactionHierarchiesDeleted = TransactionHierarchy::query()->delete();
            $this->info("  ✓ {$transactionHierarchiesDeleted} Transaction Hierarchies supprimées");

            // Étape 4: Supprimer les Gain Distributions (dépend de Transaction)
            $this->info('Étape 4/8: Suppression des Gain Distributions...');
            $gainDistributionsDeleted = GainDistribution::query()->delete();
            $this->info("  ✓ {$gainDistributionsDeleted} Gain Distributions supprimées");

            // Étape 5: Supprimer les Transaction Repartitions (dépend de Transaction)
            $this->info('Étape 5/8: Suppression des Transaction Repartitions...');
            $transactionRepartitionsDeleted = TransactionRepartition::query()->delete();
            $this->info("  ✓ {$transactionRepartitionsDeleted} Transaction Repartitions supprimées");

            // Étape 6: Supprimer les transaction_details (dépend de Transaction)
            $this->info('Étape 6/8: Suppression des Transaction Details...');
            $transactionDetailsDeleted = TransactionDetail::query()->delete();
            $this->info("  ✓ {$transactionDetailsDeleted} Transaction Details supprimés");

            // Étape 7: Supprimer les transactions
            $this->info('Étape 7/8: Suppression des Transactions...');
            $transactionsDeleted = Transaction::query()->delete();
            $this->info("  ✓ {$transactionsDeleted} Transactions supprimées");

            // Étape 8: Supprimer les réservations
            $this->info('Étape 8/8: Suppression des Réservations...');
            $reservationsDeleted = Reservation::query()->delete();
            $this->info("  ✓ {$reservationsDeleted} Réservations supprimées");

            DB::commit();

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->newLine();
            $this->info('✅ Suppression terminée avec succès !');
            $this->info("   Durée: {$duration} secondes");
            $this->newLine();
            $this->table(
                ['Type', 'Nombre supprimé'],
                [
                    ['Financial Transactions', $financialTransactionsDeleted],
                    ['Revenue Shares', $revenueSharesDeleted],
                    ['Transaction Hierarchies', $transactionHierarchiesDeleted],
                    ['Gain Distributions', $gainDistributionsDeleted],
                    ['Transaction Repartitions', $transactionRepartitionsDeleted],
                    ['Transaction Details', $transactionDetailsDeleted],
                    ['Transactions', $transactionsDeleted],
                    ['Réservations', $reservationsDeleted],
                ]
            );

            // Logger l'opération
            Log::warning('Suppression massive de données effectuée', [
                'command' => 'DeleteAllReservationsTransactions',
                'financial_transactions_deleted' => $financialTransactionsDeleted,
                'revenue_shares_deleted' => $revenueSharesDeleted,
                'transaction_hierarchies_deleted' => $transactionHierarchiesDeleted,
                'gain_distributions_deleted' => $gainDistributionsDeleted,
                'transaction_repartitions_deleted' => $transactionRepartitionsDeleted,
                'transaction_details_deleted' => $transactionDetailsDeleted,
                'transactions_deleted' => $transactionsDeleted,
                'reservations_deleted' => $reservationsDeleted,
                'duration_seconds' => $duration,
                'environment' => config('app.env'),
                'executed_by' => get_current_user(),
            ]);

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->newLine();
            $this->error('❌ Erreur lors de la suppression !');
            $this->error("   Message: {$e->getMessage()}");
            $this->error("   Fichier: {$e->getFile()}:{$e->getLine()}");
            
            // Logger l'erreur
            Log::error('Erreur lors de la suppression massive de données', [
                'command' => 'DeleteAllReservationsTransactions',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'environment' => config('app.env'),
            ]);

            return 1;
        }
    }
}

