<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BalanceSynchronizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeepBalanceVerification extends Command
{
    protected $signature = 'balances:deep-verify 
                            {--fix : Corriger automatiquement les incohérences détectées}
                            {--user= : Vérifier un utilisateur spécifique}';

    protected $description = 'Vérification approfondie de toutes les balances - Compare balances calculées vs stockées';

    public function handle(BalanceSynchronizationService $balanceSyncService): int
    {
        $this->info('🔍 Vérification approfondie de toutes les balances...');
        $this->newLine();

        $fix = $this->option('fix');
        $userId = $this->option('user');

        $query = User::query();
        if ($userId) {
            // Si un ID spécifique est fourni, chercher cet utilisateur uniquement
            $query->where('id', $userId);
        } else {
            // Sinon, tous les utilisateurs avec rôles pertinents OU avec wallet
            $query->where(function($q) {
                $q->whereHas('roles', function($roleQuery) {
                    $roleQuery->whereIn('name', ['admin', 'super_admin', 'integrator', 'operator', 'partner']);
                })
                ->orHas('wallet');
            });
        }

        $users = $query->with('wallet', 'roles')->get();
        
        // Si un ID spécifique est fourni mais non trouvé, essayer sans filtre
        if ($userId && $users->isEmpty()) {
            $user = User::with('wallet', 'roles')->find($userId);
            if ($user) {
                $users = collect([$user]);
            }
        }
        $totalUsers = $users->count();

        if ($totalUsers === 0) {
            $this->warn('⚠️  Aucun utilisateur trouvé');
            return Command::FAILURE;
        }

        $this->info("📊 Vérification de {$totalUsers} utilisateur(s)");
        $this->newLine();

        $issues = [];
        $fixed = [];
        $verified = [];

        foreach ($users as $user) {
            try {
                $wallet = $user->getOrCreateWallet();
                $storedBalance = (float) $wallet->balance;
                
                // Calculer la balance depuis TransactionDetails
                $calculatedBalance = $balanceSyncService->calculateBalanceFromApprovedTransactions($user);
                
                // Calculer les totaux
                $totals = $balanceSyncService->calculateTotalsFromApprovedTransactions($user);
                
                // Obtenir les WalletTransactions réelles
                $actualCredits = (float) $wallet->getTotalCredits();
                $actualDebits = (float) $wallet->getTotalDebits();
                $actualNet = $actualCredits - $actualDebits;
                
                // Vérifier les incohérences
                $balanceDiff = abs($storedBalance - $calculatedBalance);
                $netDiff = abs($storedBalance - $actualNet);
                $creditsDiff = abs($totals['total_credits'] - $actualCredits);
                $debitsDiff = abs($totals['total_debits'] - $actualDebits);
                
                $hasIssues = false;
                $issueDetails = [];
                
                // Vérification 1: Balance stockée vs calculée
                if ($balanceDiff > 0.01) {
                    $hasIssues = true;
                    $issueDetails[] = "Balance stockée ({$storedBalance}) ≠ Balance calculée ({$calculatedBalance}) - Diff: " . number_format($balanceDiff, 2);
                }
                
                // Vérification 2: Balance stockée vs WalletTransactions
                if ($netDiff > 0.01) {
                    $hasIssues = true;
                    $issueDetails[] = "Balance stockée ({$storedBalance}) ≠ Net WalletTransactions ({$actualNet}) - Diff: " . number_format($netDiff, 2);
                }
                
                // Vérification 3: Crédits attendus vs réels
                if ($creditsDiff > 0.01) {
                    $hasIssues = true;
                    $issueDetails[] = "Crédits attendus ({$totals['total_credits']}) ≠ Crédits réels ({$actualCredits}) - Diff: " . number_format($creditsDiff, 2);
                }
                
                // Vérification 4: Débits attendus vs réels
                if ($debitsDiff > 0.01) {
                    $hasIssues = true;
                    $issueDetails[] = "Débits attendus ({$totals['total_debits']}) ≠ Débits réels ({$actualDebits}) - Diff: " . number_format($debitsDiff, 2);
                }
                
                if ($hasIssues) {
                    $issues[] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'roles' => $user->roles->pluck('name')->join(', '),
                        'stored_balance' => $storedBalance,
                        'calculated_balance' => $calculatedBalance,
                        'actual_net' => $actualNet,
                        'expected_credits' => $totals['total_credits'],
                        'actual_credits' => $actualCredits,
                        'expected_debits' => $totals['total_debits'],
                        'actual_debits' => $actualDebits,
                        'issues' => $issueDetails,
                    ];
                    
                    if ($fix) {
                        $this->warn("🔧 Correction de l'utilisateur #{$user->id} ({$user->name})...");
                        
                        try {
                            DB::beginTransaction();
                            
                            // Recalculer les totaux pour être sûr d'avoir les bonnes valeurs
                            $totalsForFix = $balanceSyncService->calculateTotalsFromApprovedTransactions($user);
                            $expectedCreditsForFix = $totalsForFix['total_credits'];
                            $expectedDebitsForFix = $totalsForFix['total_debits'];
                            
                            // Recharger le wallet pour avoir les valeurs actuelles
                            $wallet->refresh();
                            $actualCreditsForFix = (float) $wallet->getTotalCredits();
                            $actualDebitsForFix = (float) $wallet->getTotalDebits();
                            
                            // 1. Corriger les débits en trop
                            if ($actualDebitsForFix > $expectedDebitsForFix + 0.01) {
                                $excessDebits = $actualDebitsForFix - $expectedDebitsForFix;
                                $this->line("   🔍 Débits en trop détectés: " . number_format($excessDebits, 2) . " €");
                                
                                // Trouver et supprimer les débits non liés à des TransactionDetails
                                $debitTransactions = $wallet->debitTransactions()
                                    ->where(function($q) {
                                        $q->whereNull('metadata')
                                          ->orWhere('metadata', 'not like', '%"source":"transaction_details"%')
                                          ->orWhere('metadata', 'not like', '%"transaction_detail_id"%')
                                          ->orWhere('metadata', 'not like', '%"source":"transaction_detail_retroactive"%');
                                    })
                                    ->orderBy('created_at', 'desc')
                                    ->get();
                                
                                $totalRemoved = 0;
                                foreach ($debitTransactions as $debit) {
                                    if ($totalRemoved >= $excessDebits - 0.01) break;
                                    
                                    $totalRemoved += $debit->amount;
                                    $debit->delete();
                                    $this->line("      • Supprimé débit: " . number_format($debit->amount, 2) . " €");
                                }
                                
                                if ($totalRemoved > 0.01) {
                                    $wallet->refresh();
                                    $newBalance = (float) $wallet->balance + $totalRemoved;
                                    $wallet->update(['balance' => $newBalance]);
                                    $this->line("   ✅ Supprimé " . number_format($totalRemoved, 2) . " € de débits en trop");
                                }
                            }
                            
                            // 2. Créer les débits manquants
                            if ($expectedDebitsForFix > $actualDebitsForFix + 0.01) {
                                $missingDebits = $expectedDebitsForFix - $actualDebitsForFix;
                                $this->line("   🔍 Débits manquants détectés: " . number_format($missingDebits, 2) . " €");
                                
                                // La synchronisation devrait créer les débits manquants
                                // via createMissingWalletTransactions
                            }
                            
                            // 3. Forcer la synchronisation complète
                            $syncResult = $balanceSyncService->synchronizeUserBalance($user);
                            $wallet->refresh();
                            
                            // 4. Ajuster la balance si nécessaire
                            $newBalance = (float) $wallet->balance;
                            $calculatedBalanceAfter = $balanceSyncService->calculateBalanceFromApprovedTransactions($user);
                            
                            if (abs($newBalance - $calculatedBalanceAfter) > 0.01) {
                                $wallet->update(['balance' => $calculatedBalanceAfter]);
                                $wallet->refresh();
                                $newBalance = $calculatedBalanceAfter;
                            }
                            
                            DB::commit();
                            
                            $newDiff = abs($newBalance - $calculatedBalance);
                            
                            if ($newDiff <= 0.01) {
                                $fixed[] = [
                                    'user_id' => $user->id,
                                    'user_name' => $user->name,
                                    'old_balance' => $storedBalance,
                                    'new_balance' => $newBalance,
                                    'calculated_balance' => $calculatedBalance,
                                ];
                                $this->info("   ✅ Corrigé: " . number_format($storedBalance, 2) . " → " . number_format($newBalance, 2) . " €");
                            } else {
                                $this->warn("   ⚠️  Partiellement corrigé: Différence restante " . number_format($newDiff, 2) . " €");
                            }
                            
                        } catch (\Exception $e) {
                            DB::rollBack();
                            $this->error("   ❌ Erreur lors de la correction: {$e->getMessage()}");
                            throw $e;
                        }
                    }
                } else {
                    $verified[] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'balance' => $storedBalance,
                    ];
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Erreur pour utilisateur #{$user->id}: {$e->getMessage()}");
                $issues[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Afficher le résumé
        $this->newLine();
        $this->info('📊 Résumé de la vérification:');
        $this->table(
            ['Statut', 'Nombre'],
            [
                ['✅ Vérifiés (OK)', count($verified)],
                ['⚠️  Incohérences détectées', count($issues)],
                ['🔧 Corrigés', count($fixed)],
                ['📊 Total', $totalUsers],
            ]
        );

        if (!empty($issues)) {
            $this->newLine();
            $this->warn('⚠️  Incohérences détectées:');
            $this->newLine();
            
            foreach ($issues as $issue) {
                $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->info("👤 Utilisateur #{$issue['user_id']}: {$issue['user_name']}");
                if (isset($issue['user_email'])) {
                    $this->line("   Email: {$issue['user_email']}");
                    $this->line("   Rôles: {$issue['roles']}");
                    $this->newLine();
                    $this->line("   💰 Balance stockée: " . number_format($issue['stored_balance'], 2) . " €");
                    $this->line("   📊 Balance calculée: " . number_format($issue['calculated_balance'], 2) . " €");
                    $this->line("   💳 Net WalletTransactions: " . number_format($issue['actual_net'], 2) . " €");
                    $this->newLine();
                    $this->line("   📈 Crédits:");
                    $this->line("      • Attendus: " . number_format($issue['expected_credits'], 2) . " €");
                    $this->line("      • Réels: " . number_format($issue['actual_credits'], 2) . " €");
                    $this->line("   📉 Débits:");
                    $this->line("      • Attendus: " . number_format($issue['expected_debits'], 2) . " €");
                    $this->line("      • Réels: " . number_format($issue['actual_debits'], 2) . " €");
                    $this->newLine();
                    $this->warn("   ⚠️  Problèmes:");
                    foreach ($issue['issues'] as $detail) {
                        $this->line("      • {$detail}");
                    }
                } else {
                    $this->error("   ❌ Erreur: {$issue['error']}");
                }
                $this->newLine();
            }
        }

        if (!empty($fixed)) {
            $this->newLine();
            $this->info('🔧 Corrections effectuées:');
            foreach ($fixed as $fix) {
                $this->line("   ✅ User #{$fix['user_id']} ({$fix['user_name']}): " . 
                    number_format($fix['old_balance'], 2) . " → " . number_format($fix['new_balance'], 2) . " €");
            }
        }

        if (empty($issues)) {
            $this->newLine();
            $this->info('✅ Toutes les balances sont correctement synchronisées !');
            return Command::SUCCESS;
        } else {
            $this->newLine();
            if ($fix) {
                $this->info('✅ Vérification terminée. ' . count($fixed) . ' correction(s) effectuée(s).');
            } else {
                $this->warn('⚠️  Des incohérences ont été détectées. Utilisez --fix pour les corriger automatiquement.');
            }
            return Command::FAILURE;
        }
    }
}

