<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BalanceSynchronizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DiagnoseUserBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balance:diagnose 
                            {user? : ID ou email de l\'utilisateur à diagnostiquer}
                            {--sync : Synchroniser la balance après le diagnostic}
                            {--auto-sync : Synchroniser automatiquement si une différence est détectée (sans confirmation)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnostique et affiche les détails de la balance d\'un utilisateur';

    /**
     * Execute the console command.
     */
    public function handle(BalanceSynchronizationService $balanceSyncService): int
    {
        $userIdentifier = $this->argument('user');
        $shouldSync = $this->option('sync');

        if (!$userIdentifier) {
            $userIdentifier = $this->ask('Entrez l\'ID ou l\'email de l\'utilisateur');
        }

        // Trouver l'utilisateur
        $user = is_numeric($userIdentifier) 
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (!$user) {
            $this->error("❌ Utilisateur non trouvé : {$userIdentifier}");
            return Command::FAILURE;
        }

        $this->info("🔍 Diagnostic de la balance pour : {$user->name} (ID: {$user->id})");
        $this->info("📧 Email : {$user->email}");
        $this->newLine();

        // Afficher les rôles
        $roles = $user->roles->pluck('name')->toArray();
        $this->info("👤 Rôles : " . implode(', ', $roles));
        $this->newLine();

        // Obtenir le wallet
        $wallet = $user->getOrCreateWallet();
        
        // Balance actuelle du wallet
        $this->info("💰 Balance actuelle du wallet : " . number_format($wallet->balance, 2) . " €");
        $this->info("🕐 Dernière mise à jour : " . ($wallet->updated_at ? $wallet->updated_at->format('M d, Y H:i') : 'Jamais'));
        $this->newLine();

        // Calculer la balance depuis TransactionDetails
        $this->info("📊 Calcul de la balance depuis TransactionDetails...");
        $calculatedBalance = $balanceSyncService->calculateBalanceFromApprovedTransactions($user);
        $this->info("✅ Balance calculée : " . number_format($calculatedBalance, 2) . " €");
        
        $difference = abs($wallet->balance - $calculatedBalance);
        if ($difference > 0.01) {
            $this->warn("⚠️  Différence détectée : " . number_format($difference, 2) . " €");
        } else {
            $this->info("✅ Balance synchronisée (différence < 0.01 €)");
        }
        $this->newLine();

        // Calculer les totaux
        $totals = $balanceSyncService->calculateTotalsFromApprovedTransactions($user);
        $this->info("📈 Détails des totaux :");
        $this->line("   • Money In (Total Credits) : " . number_format($totals['total_credits'], 2) . " €");
        $this->line("   • Money Out (Total Debits) : " . number_format($totals['total_debits'], 2) . " €");
        $this->line("   • Balance Nette : " . number_format($totals['net_amount'], 2) . " €");
        $this->newLine();

        // WalletTransactions existantes
        $actualCredits = (float) $wallet->getTotalCredits();
        $actualDebits = (float) $wallet->getTotalDebits();
        $this->info("💳 WalletTransactions existantes :");
        $this->line("   • Crédits réels : " . number_format($actualCredits, 2) . " €");
        $this->line("   • Débits réels : " . number_format($actualDebits, 2) . " €");
        $this->newLine();

        // Différences
        $missingCredits = max(0, $totals['total_credits'] - $actualCredits);
        $missingDebits = max(0, $totals['total_debits'] - $actualDebits);
        
        if ($missingCredits > 0.01 || $missingDebits > 0.01) {
            $this->warn("⚠️  WalletTransactions manquantes :");
            if ($missingCredits > 0.01) {
                $this->line("   • Crédits manquants : " . number_format($missingCredits, 2) . " €");
            }
            if ($missingDebits > 0.01) {
                $this->line("   • Débits manquants : " . number_format($missingDebits, 2) . " €");
            }
            $this->newLine();
        }

        // Compter les TransactionDetails
        $isAdmin = $user->hasRole(['admin', 'super_admin']);
        $isIntegrator = $user->hasRole('integrator');
        $isOperator = $user->hasRole(['operator', 'partner']);

        if ($isAdmin) {
            $detailsCount = \App\Models\TransactionDetail::whereHas('transaction')
                ->get()
                ->filter(function($detail) {
                    return ($detail->admin_share_amount ?? 0) > 0;
                })
                ->count();
            $this->info("📋 TransactionDetails avec parts admin > 0 : {$detailsCount}");
        } elseif ($isIntegrator) {
            $integratorModel = \App\Models\Integrator::where('user_id', $user->id)->first();
            $integratorModelId = $integratorModel ? $integratorModel->id : null;
            $detailsCount = \App\Models\TransactionDetail::whereHas('transaction')
                ->where(function($q) use ($user, $integratorModelId) {
                    $q->where('integrator_creator_id', $user->id);
                    if ($integratorModelId) {
                        $q->orWhere('integrator_creator_id', $integratorModelId);
                    }
                })
                ->get()
                ->filter(function($detail) {
                    return ($detail->integrator_share_amount ?? 0) > 0;
                })
                ->count();
            $this->info("📋 TransactionDetails avec parts intégrateur > 0 : {$detailsCount}");
        } elseif ($isOperator) {
            $detailsCount = \App\Models\TransactionDetail::whereHas('transaction')
                ->where('operator_id', $user->id)
                ->where('operator_share_amount', '>', 0)
                ->count();
            $this->info("📋 TransactionDetails avec parts opérateur > 0 : {$detailsCount}");
        }

        $this->newLine();

        // Synchroniser si demandé
        $autoSync = $this->option('auto-sync');
        $shouldSyncNow = $shouldSync || $autoSync;
        
        if ($shouldSyncNow || ($difference > 0.01 && !$autoSync && $this->confirm('Voulez-vous synchroniser la balance maintenant ?', true))) {
            $this->info("🔄 Synchronisation de la balance...");
            try {
                $syncResult = $balanceSyncService->synchronizeUserBalance($user);
                $wallet->refresh();
                
                $this->info("✅ Synchronisation terminée !");
                $this->line("   • Balance avant : " . number_format($calculatedBalance, 2) . " €");
                $this->line("   • Balance après : " . number_format($wallet->balance, 2) . " €");
                $this->line("   • WalletTransactions créées : " . count($syncResult['created_transactions'] ?? []));
                
                if ($syncResult['synchronized']) {
                    $this->info("✅ Balance synchronisée avec succès !");
                } else {
                    $this->warn("⚠️  Balance déjà synchronisée");
                }
            } catch (\Exception $e) {
                $this->error("❌ Erreur lors de la synchronisation : " . $e->getMessage());
                Log::error('Erreur synchronisation balance', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}

