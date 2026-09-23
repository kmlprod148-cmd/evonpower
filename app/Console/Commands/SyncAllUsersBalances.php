<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BalanceSynchronizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Commande simple pour synchroniser automatiquement les balances de TOUS les utilisateurs
 * 
 * Usage:
 * php artisan balances:sync-all
 * php artisan balances:sync-all --force
 */
class SyncAllUsersBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances:sync-all 
                            {--force : Forcer la synchronisation même si déjà à jour}
                            {--chunk=100 : Nombre d\'utilisateurs à traiter par batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise automatiquement les balances de TOUS les utilisateurs';

    /**
     * Execute the console command.
     */
    public function handle(BalanceSynchronizationService $balanceSyncService): int
    {
        $this->info('🔄 Synchronisation automatique des balances pour TOUS les utilisateurs...');
        $this->newLine();

        $force = $this->option('force');
        $chunkSize = (int) $this->option('chunk');

        try {
            // Récupérer TOUS les utilisateurs
            $totalUsers = User::count();
            $this->info("📊 Total d'utilisateurs à synchroniser : {$totalUsers}");
            $this->newLine();

            if ($totalUsers === 0) {
                $this->warn('⚠️  Aucun utilisateur trouvé');
                return Command::SUCCESS;
            }

            $bar = $this->output->createProgressBar($totalUsers);
            $bar->start();

            $syncedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;
            $errors = [];

            // Traiter les utilisateurs par chunks pour optimiser la mémoire
            User::chunk($chunkSize, function ($users) use ($balanceSyncService, $force, &$syncedCount, &$skippedCount, &$errorCount, &$errors, $bar) {
                foreach ($users as $user) {
                    try {
                        $wallet = $user->getOrCreateWallet();
                        $balanceBefore = $wallet->balance;
                        $calculatedBalance = $balanceSyncService->calculateBalanceFromApprovedTransactions($user);
                        
                        $difference = abs($balanceBefore - $calculatedBalance);
                        
                        // Si la différence est négligeable et qu'on ne force pas, ignorer
                        if ($difference <= 0.01 && !$force) {
                            $skippedCount++;
                            $bar->advance();
                            continue;
                        }
                        
                        // Synchroniser
                        $result = $balanceSyncService->synchronizeUserBalance($user);
                        $wallet->refresh();
                        
                        if ($result['synchronized']) {
                            $syncedCount++;
                        } else {
                            $skippedCount++;
                        }
                        
                    } catch (\Exception $e) {
                        $errorCount++;
                        $errors[] = "Utilisateur #{$user->id} ({$user->name}): {$e->getMessage()}";
                        Log::error('Erreur lors de la synchronisation de balance', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine(2);

            // Afficher le résumé
            $this->info('📊 Résumé de la synchronisation automatique:');
            $this->table(
                ['Statut', 'Nombre'],
                [
                    ['✅ Synchronisés', $syncedCount],
                    ['⏭️  Ignorés (déjà à jour)', $skippedCount],
                    ['❌ Erreurs', $errorCount],
                    ['📊 Total', $totalUsers],
                ]
            );

            if (!empty($errors) && count($errors) <= 10) {
                $this->newLine();
                $this->warn('⚠️  Erreurs rencontrées (affichage des 10 premières):');
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->line("  - {$error}");
                }
                if (count($errors) > 10) {
                    $this->line("  ... et " . (count($errors) - 10) . " autres erreurs (voir les logs)");
                }
            } elseif (!empty($errors)) {
                $this->newLine();
                $this->warn("⚠️  {$errorCount} erreur(s) rencontrée(s) - Voir les logs pour plus de détails");
            }

            $this->newLine();
            if ($errorCount > 0) {
                $this->warn("⚠️  Synchronisation terminée avec {$errorCount} erreur(s)");
                return Command::FAILURE;
            } else {
                $this->info('✅ Synchronisation automatique terminée avec succès !');
                return Command::SUCCESS;
            }

        } catch (\Exception $e) {
            $this->error("❌ Erreur fatale: {$e->getMessage()}");
            Log::error('Erreur fatale lors de la synchronisation automatique des balances', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}

