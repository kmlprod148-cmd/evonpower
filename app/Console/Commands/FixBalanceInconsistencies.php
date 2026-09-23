<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\BalanceSynchronizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixBalanceInconsistencies extends Command
{
    protected $signature = 'balances:fix-inconsistencies 
                            {--user= : Corriger un utilisateur spécifique}
                            {--dry-run : Afficher ce qui serait fait sans modifier}';

    protected $description = 'Corrige les incohérences entre WalletTransactions et TransactionDetails (débits en trop ou manquants)';

    public function handle(BalanceSynchronizationService $balanceSyncService): int
    {
        $this->info('🔧 Correction des incohérences de balances...');
        $this->newLine();

        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé - Aucune modification ne sera effectuée');
            $this->newLine();
        }

        $query = User::query();
        if ($userId) {
            $query->where('id', $userId);
        } else {
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

        $this->info("📊 Analyse de {$totalUsers} utilisateur(s)");
        $this->newLine();

        $fixed = [];
        $errors = [];

        foreach ($users as $user) {
            try {
                $wallet = $user->getOrCreateWallet();
                $storedBalance = (float) $wallet->balance;
                
                // Calculer les totaux depuis TransactionDetails
                $totals = $balanceSyncService->calculateTotalsFromApprovedTransactions($user);
                
                // Obtenir les WalletTransactions réelles
                $actualCredits = (float) $wallet->getTotalCredits();
                $actualDebits = (float) $wallet->getTotalDebits();
                
                $expectedCredits = $totals['total_credits'];
                $expectedDebits = $totals['total_debits'];
                
                $creditsDiff = abs($expectedCredits - $actualCredits);
                $debitsDiff = abs($expectedDebits - $actualDebits);
                
                // Vérifier les incohérences
                $hasIssues = false;
                $actions = [];
                
                // Problème 1: Débits en trop
                if ($actualDebits > $expectedDebits + 0.01) {
                    $hasIssues = true;
                    $excessDebits = $actualDebits - $expectedDebits;
                    $actions[] = "Supprimer {$excessDebits} € de débits en trop";
                    
                    if (!$dryRun) {
                        // Trouver les débits qui ne sont pas liés à des TransactionDetails
                        $debitTransactions = $wallet->debitTransactions()
                            ->whereDoesntHave('metadata', function($q) {
                                $q->whereJsonContains('metadata->source', 'transaction_details');
                            })
                            ->orderBy('created_at', 'desc')
                            ->get();
                        
                        $totalToRemove = 0;
                        $removed = [];
                        
                        foreach ($debitTransactions as $debit) {
                            if ($totalToRemove >= $excessDebits - 0.01) break;
                            
                            $totalToRemove += $debit->amount;
                            $removed[] = $debit;
                        }
                        
                        if ($totalToRemove > 0.01) {
                            // Supprimer les débits en trop
                            foreach ($removed as $debit) {
                                $debit->delete();
                            }
                            
                            // Recalculer la balance
                            $wallet->refresh();
                            $newBalance = $storedBalance + $totalToRemove;
                            $wallet->update(['balance' => $newBalance]);
                            $wallet->refresh();
                            
                            $actions[] = "✅ Supprimé " . number_format($totalToRemove, 2) . " € de débits";
                        }
                    }
                }
                
                // Problème 2: Débits manquants
                if ($expectedDebits > $actualDebits + 0.01) {
                    $hasIssues = true;
                    $missingDebits = $expectedDebits - $actualDebits;
                    $actions[] = "Créer {$missingDebits} € de débits manquants";
                    
                    if (!$dryRun) {
                        // Créer les débits manquants depuis TransactionDetails
                        // Cette logique devrait être dans createMissingWalletTransactions
                        // Pour l'instant, on synchronise simplement
                        $balanceSyncService->synchronizeUserBalance($user);
                        $wallet->refresh();
                        
                        $actions[] = "✅ Créé les débits manquants";
                    }
                }
                
                // Problème 3: Balance stockée vs Net WalletTransactions
                $actualNet = $actualCredits - $actualDebits;
                $netDiff = abs($storedBalance - $actualNet);
                
                if ($netDiff > 0.01) {
                    $hasIssues = true;
                    $actions[] = "Ajuster balance stockée: {$storedBalance} → {$actualNet}";
                    
                    if (!$dryRun) {
                        $wallet->update(['balance' => $actualNet]);
                        $wallet->refresh();
                        $actions[] = "✅ Balance ajustée";
                    }
                }
                
                if ($hasIssues) {
                    $fixed[] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'actions' => $actions,
                    ];
                }
                
            } catch (\Exception $e) {
                $errors[] = "User #{$user->id}: {$e->getMessage()}";
                $this->error("❌ Erreur pour utilisateur #{$user->id}: {$e->getMessage()}");
            }
        }

        // Afficher le résumé
        $this->newLine();
        $this->info('📊 Résumé de la correction:');
        $this->table(
            ['Statut', 'Nombre'],
            [
                ['🔧 Corrigés', count($fixed)],
                ['❌ Erreurs', count($errors)],
                ['📊 Total', $totalUsers],
            ]
        );

        if (!empty($fixed)) {
            $this->newLine();
            $this->info('🔧 Corrections effectuées:');
            foreach ($fixed as $fix) {
                $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->info("👤 Utilisateur #{$fix['user_id']}: {$fix['user_name']}");
                $this->line("   Email: {$fix['user_email']}");
                $this->newLine();
                foreach ($fix['actions'] as $action) {
                    $this->line("   • {$action}");
                }
                $this->newLine();
            }
        }

        if (!empty($errors)) {
            $this->newLine();
            $this->error('❌ Erreurs rencontrées:');
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info('✅ Analyse terminée (mode DRY-RUN)');
        } else {
            $this->info('✅ Correction terminée !');
        }
        
        return Command::SUCCESS;
    }
}

