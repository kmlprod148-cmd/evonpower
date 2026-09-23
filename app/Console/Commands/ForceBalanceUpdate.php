<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BalanceSynchronizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ForceBalanceUpdate extends Command
{
    protected $signature = 'balances:force-update 
                            {--user= : Mettre à jour un utilisateur spécifique}
                            {--all : Mettre à jour tous les utilisateurs}';

    protected $description = 'Force la mise à jour de toutes les balances et met à jour le timestamp updated_at';

    public function handle(BalanceSynchronizationService $balanceSyncService): int
    {
        $this->info('🔄 Mise à jour forcée de toutes les balances...');
        $this->newLine();

        $userId = $this->option('user');
        $all = $this->option('all');

        $query = User::query();
        if ($userId) {
            $query->where('id', $userId);
        } elseif (!$all) {
            // Par défaut, seulement les utilisateurs avec rôles pertinents
            $query->whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'super_admin', 'integrator', 'operator', 'partner']);
            });
        }

        $users = $query->with('wallet', 'roles')->get();
        $totalUsers = $users->count();

        if ($totalUsers === 0) {
            $this->warn('⚠️  Aucun utilisateur trouvé');
            return Command::FAILURE;
        }

        $this->info("📊 Mise à jour de {$totalUsers} utilisateur(s)");
        $this->newLine();

        $bar = $this->output->createProgressBar($totalUsers);
        $bar->start();

        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                $wallet = $user->getOrCreateWallet();
                $oldBalance = (float) $wallet->balance;
                $oldUpdatedAt = $wallet->updated_at;
                
                // Calculer la balance depuis TransactionDetails
                $calculatedBalance = $balanceSyncService->calculateBalanceFromApprovedTransactions($user);
                
                // Synchroniser (crée les WalletTransactions manquantes si nécessaire)
                $syncResult = $balanceSyncService->synchronizeUserBalance($user);
                $wallet->refresh();
                
                // Forcer la mise à jour du timestamp même si la balance n'a pas changé
                DB::table('wallets')
                    ->where('id', $wallet->id)
                    ->update([
                        'updated_at' => now()
                    ]);
                
                $wallet->refresh();
                $newBalance = (float) $wallet->balance;
                $newUpdatedAt = $wallet->updated_at;
                
                if ($oldBalance != $newBalance || $oldUpdatedAt != $newUpdatedAt) {
                    $updated++;
                } else {
                    $skipped++;
                }
                
            } catch (\Exception $e) {
                $errors[] = "User #{$user->id}: {$e->getMessage()}";
                $this->error("❌ Erreur pour utilisateur #{$user->id}: {$e->getMessage()}");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Afficher le résumé
        $this->info('📊 Résumé de la mise à jour:');
        $this->table(
            ['Statut', 'Nombre'],
            [
                ['✅ Mis à jour', $updated],
                ['⏭️  Ignorés (déjà à jour)', $skipped],
                ['❌ Erreurs', count($errors)],
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

        $this->newLine();
        $this->info('✅ Mise à jour terminée !');
        $this->info('🕐 Tous les timestamps updated_at ont été mis à jour à : ' . now()->format('M d, Y H:i'));
        
        return Command::SUCCESS;
    }
}

