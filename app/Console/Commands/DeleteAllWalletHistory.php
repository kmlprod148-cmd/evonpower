<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteAllWalletHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:delete-all-wallet-history 
                            {--force : Force la suppression sans confirmation}
                            {--dry-run : Affiche seulement les statistiques sans supprimer}
                            {--reset-balances : Réinitialise les balances des wallets à 0 après suppression}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprime tout l\'historique des transactions de wallets (PRODUCTION)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->warn('⚠️  ATTENTION: Cette commande va supprimer TOUT l\'historique des wallets !');
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
            $confirmation = $this->ask('Tapez "SUPPRIMER" pour confirmer la suppression de tout l\'historique des wallets');
            
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

        // Compter les wallet transactions
        $walletTransactionsCount = WalletTransaction::count();
        $this->line("  Wallet Transactions: {$walletTransactionsCount}");

        // Compter les wallets
        $walletsCount = Wallet::count();
        $this->line("  Wallets: {$walletsCount}");

        // Statistiques supplémentaires
        $this->newLine();
        $this->info('📈 Détails supplémentaires:');
        
        // Transactions par type
        $transactionsByType = WalletTransaction::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
        
        if (!empty($transactionsByType)) {
            $this->line('  Transactions par type:');
            foreach ($transactionsByType as $type => $count) {
                $this->line("    - {$type}: {$count}");
            }
        }

        // Transactions par statut
        $transactionsByStatus = WalletTransaction::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        if (!empty($transactionsByStatus)) {
            $this->line('  Transactions par statut:');
            foreach ($transactionsByStatus as $status => $count) {
                $this->line("    - {$status}: {$count}");
            }
        }

        // Montants totaux
        $totalCredits = WalletTransaction::where('type', 'credit')->sum('amount');
        $totalDebits = WalletTransaction::where('type', 'debit')->sum('amount');
        $netAmount = $totalCredits - $totalDebits;

        $this->newLine();
        $this->line("  Total crédits: " . number_format($totalCredits, 2) . " €");
        $this->line("  Total débits: " . number_format($totalDebits, 2) . " €");
        $this->line("  Montant net: " . number_format($netAmount, 2) . " €");

        // Balance total des wallets
        $totalWalletBalance = Wallet::sum('balance');
        $this->newLine();
        $this->line("  Balance total des wallets: " . number_format($totalWalletBalance, 2) . " €");
        $this->line("  Nombre de wallets avec balance > 0: " . Wallet::where('balance', '>', 0)->count());

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

            // Étape 1: Supprimer toutes les wallet transactions
            $this->info('Étape 1/2: Suppression des Wallet Transactions...');
            $walletTransactionsDeleted = WalletTransaction::query()->delete();
            $this->info("  ✓ {$walletTransactionsDeleted} Wallet Transactions supprimées");

            // Étape 2: Optionnellement réinitialiser les balances
            $resetBalances = $this->option('reset-balances');
            if ($resetBalances) {
                $this->info('Étape 2/2: Réinitialisation des balances des wallets à 0...');
                $walletsUpdated = Wallet::query()->update(['balance' => 0]);
                $this->info("  ✓ {$walletsUpdated} Wallets réinitialisés (balance = 0)");
            } else {
                $this->info('Étape 2/2: Les balances des wallets ne sont PAS réinitialisées.');
                $this->warn('  ⚠️  Note: Les balances des wallets peuvent être incohérentes sans l\'historique.');
                $this->warn('  💡 Utilisez --reset-balances pour réinitialiser les balances à 0.');
            }

            DB::commit();

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->newLine();
            $this->info('✅ Suppression terminée avec succès !');
            $this->info("   Durée: {$duration} secondes");
            $this->newLine();
            $this->table(
                ['Type', 'Nombre'],
                [
                    ['Wallet Transactions supprimées', $walletTransactionsDeleted],
                    ['Wallets réinitialisés', $resetBalances ? Wallet::count() : 0],
                ]
            );

            // Logger l'opération
            Log::warning('Suppression massive de l\'historique des wallets effectuée', [
                'command' => 'DeleteAllWalletHistory',
                'wallet_transactions_deleted' => $walletTransactionsDeleted,
                'balances_reset' => $resetBalances,
                'wallets_count' => Wallet::count(),
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
            Log::error('Erreur lors de la suppression massive de l\'historique des wallets', [
                'command' => 'DeleteAllWalletHistory',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'environment' => config('app.env'),
            ]);

            return 1;
        }
    }
}

