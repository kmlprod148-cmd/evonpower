<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BalanceSynchronizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances:sync 
                            {--user= : ID de l\'utilisateur spécifique à synchroniser}
                            {--role= : Rôle spécifique à synchroniser (admin, integrator, operator)}
                            {--all : Synchroniser TOUS les utilisateurs (y compris sans rôles spécifiques)}
                            {--force : Forcer la synchronisation même si déjà à jour}
                            {--dry-run : Afficher ce qui serait fait sans modifier les données}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les balances de tous les utilisateurs depuis les TransactionDetail';

    /**
     * Execute the console command.
     */
    public function handle(BalanceSynchronizationService $balanceSyncService): int
    {
        $this->info('🔄 Synchronisation des balances...');
        $this->newLine();

        $userId = $this->option('user');
        $role = $this->option('role');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé - Aucune modification ne sera effectuée');
            $this->newLine();
        }

        try {
            // Si un utilisateur spécifique est fourni
            if ($userId) {
                $user = User::find($userId);
                if (!$user) {
                    $this->error("❌ Utilisateur #{$userId} non trouvé");
                    return Command::FAILURE;
                }
                return $this->syncUser($user, $balanceSyncService, $force, $dryRun);
            }

            // Construire la requête selon les options
            $query = User::query();
            $syncAll = $this->option('all');
            
            if ($role) {
                $query->whereHas('roles', function($q) use ($role) {
                    $q->where('name', $role);
                });
            } elseif (!$syncAll) {
                // Par défaut, synchroniser tous les utilisateurs avec rôles pertinents
                $query->whereHas('roles', function($q) {
                    $q->whereIn('name', ['admin', 'super_admin', 'integrator', 'operator', 'partner']);
                });
            }
            // Si --all est spécifié, synchroniser TOUS les utilisateurs (pas de filtre)

            $users = $query->get();
            $totalUsers = $users->count();

            if ($totalUsers === 0) {
                $this->warn('⚠️  Aucun utilisateur trouvé à synchroniser');
                return Command::SUCCESS;
            }

            $this->info("📊 {$totalUsers} utilisateur(s) à synchroniser");
            $this->newLine();

            $bar = $this->output->createProgressBar($totalUsers);
            $bar->start();

            $syncedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($users as $user) {
                try {
                    $result = $this->syncUser($user, $balanceSyncService, $force, $dryRun, false);
                    
                    if ($result === Command::SUCCESS) {
                        $syncedCount++;
                    } elseif ($result === Command::INVALID) {
                        $skippedCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "Utilisateur #{$user->id} ({$user->name})";
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Utilisateur #{$user->id} ({$user->name}): {$e->getMessage()}";
                    Log::error('Erreur lors de la synchronisation de balance', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Afficher le résumé
            $this->info('📊 Résumé de la synchronisation:');
            $this->table(
                ['Statut', 'Nombre'],
                [
                    ['✅ Synchronisés', $syncedCount],
                    ['⏭️  Ignorés (déjà à jour)', $skippedCount],
                    ['❌ Erreurs', $errorCount],
                    ['📊 Total', $totalUsers],
                ]
            );

            if (!empty($errors)) {
                $this->newLine();
                $this->error('❌ Erreurs rencontrées:');
                foreach ($errors as $error) {
                    $this->line("  - {$error}");
                }
            }

            if ($errorCount > 0) {
                return Command::FAILURE;
            }

            $this->newLine();
            $this->info('✅ Synchronisation terminée avec succès');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erreur fatale: {$e->getMessage()}");
            Log::error('Erreur fatale lors de la synchronisation des balances', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Synchroniser un utilisateur spécifique
     */
    protected function syncUser(
        User $user, 
        BalanceSynchronizationService $balanceSyncService, 
        bool $force = false,
        bool $dryRun = false,
        bool $verbose = true
    ): int {
        try {
            $wallet = $user->getOrCreateWallet();
            $balanceBefore = $wallet->balance;
            $calculatedBalance = $balanceSyncService->calculateBalanceFromApprovedTransactions($user);

            if ($verbose) {
                $this->info("🔄 Synchronisation de {$user->name} (ID: {$user->id})");
                $this->line("   Rôles: " . $user->roles->pluck('name')->join(', '));
                $this->line("   Balance actuelle: " . number_format($balanceBefore, 2) . " EUR");
                $this->line("   Balance calculée: " . number_format($calculatedBalance, 2) . " EUR");
            }

            $difference = abs($balanceBefore - $calculatedBalance);

            if ($difference <= 0.01 && !$force) {
                if ($verbose) {
                    $this->line("   ✅ Balance déjà synchronisée");
                }
                return Command::INVALID; // INVALID est utilisé ici pour indiquer "ignoré"
            }

            if ($dryRun) {
                if ($verbose) {
                    $this->warn("   🔄 Balance serait mise à jour de " . number_format($balanceBefore, 2) . " à " . number_format($calculatedBalance, 2) . " EUR");
                }
                return Command::SUCCESS;
            }

            // Synchroniser réellement
            $result = $balanceSyncService->synchronizeUserBalance($user);
            $wallet->refresh();

            if ($verbose) {
                $this->line("   ✅ Balance mise à jour: " . number_format($wallet->balance, 2) . " EUR");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            if ($verbose) {
                $this->error("   ❌ Erreur: {$e->getMessage()}");
            }
            throw $e;
        }
    }
}

