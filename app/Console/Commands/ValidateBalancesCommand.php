<?php

namespace App\Console\Commands;

use App\Models\TransactionDetail;
use App\Models\User;
use App\Services\BalanceSynchronizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances:validate 
                            {--fix : Corriger automatiquement les incohérences détectées}
                            {--user= : ID de l\'utilisateur spécifique à valider}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Valide la cohérence des balances entre TransactionDetail et wallets';

    /**
     * Execute the console command.
     */
    public function handle(BalanceSynchronizationService $balanceSyncService): int
    {
        $this->info('🔍 Validation de la cohérence des balances...');
        $this->newLine();

        $fix = $this->option('fix');
        $userId = $this->option('user');

        try {
            $issues = [];
            $validatedCount = 0;

            // Construire la requête
            $query = User::query();
            if ($userId) {
                $query->where('id', $userId);
            } else {
                $query->whereHas('roles', function($q) {
                    $q->whereIn('name', ['admin', 'super_admin', 'integrator', 'operator', 'partner']);
                });
            }

            $users = $query->get();

            $this->info("📊 Validation de {$users->count()} utilisateur(s)");
            $this->newLine();

            $bar = $this->output->createProgressBar($users->count());
            $bar->start();

            foreach ($users as $user) {
                $wallet = $user->getOrCreateWallet();
                $walletBalance = $wallet->balance;
                $calculatedBalance = $balanceSyncService->calculateBalanceFromApprovedTransactions($user);
                
                $difference = abs($walletBalance - $calculatedBalance);

                if ($difference > 0.01) {
                    $issues[] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'wallet_balance' => $walletBalance,
                        'calculated_balance' => $calculatedBalance,
                        'difference' => $difference,
                    ];

                    if ($fix) {
                        $balanceSyncService->synchronizeUserBalance($user);
                        $wallet->refresh();
                    }
                } else {
                    $validatedCount++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Afficher les résultats
            if (empty($issues)) {
                $this->info("✅ Toutes les balances sont cohérentes ({$validatedCount} utilisateur(s))");
                return Command::SUCCESS;
            }

            $this->warn("⚠️  {$issues->count()} incohérence(s) détectée(s)");
            $this->newLine();

            $this->table(
                ['User ID', 'Nom', 'Balance Wallet', 'Balance Calculée', 'Différence'],
                collect($issues)->map(function($issue) {
                    return [
                        $issue['user_id'],
                        $issue['user_name'],
                        number_format($issue['wallet_balance'], 2) . ' EUR',
                        number_format($issue['calculated_balance'], 2) . ' EUR',
                        number_format($issue['difference'], 2) . ' EUR',
                    ];
                })->toArray()
            );

            if ($fix) {
                $this->newLine();
                $this->info('✅ Corrections appliquées');
                return Command::SUCCESS;
            } else {
                $this->newLine();
                $this->comment('💡 Utilisez --fix pour corriger automatiquement les incohérences');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("❌ Erreur fatale: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}

