<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BalanceSynchronizationService;
use App\Services\ReservationTransactionService;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixAndSyncAllBalances extends Command
{
    protected $signature = 'balances:fix-and-sync 
                            {--dry-run : Afficher ce qui sera fait sans exécuter}
                            {--transaction-id= : Traiter une transaction spécifique}
                            {--user-id= : Synchroniser seulement un utilisateur spécifique}';
    
    protected $description = 'Corriger et synchroniser tous les balances basés sur les TransactionDetails des réservations approuvées';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $transactionId = $this->option('transaction-id');
        $userId = $this->option('user-id');

        $this->info('=== CORRECTION ET SYNCHRONISATION DES BALANCES ===');
        if ($dryRun) {
            $this->warn('MODE DRY-RUN - Aucune modification ne sera effectuée');
        }
        $this->newLine();

        $balanceSyncService = app(BalanceSynchronizationService::class);
        $reservationTransactionService = app(ReservationTransactionService::class);

        try {
            // ÉTAPE 1 : Trouver toutes les transactions avec réservations approuvées
            $this->info('ÉTAPE 1 : Recherche des transactions avec réservations approuvées...');
            
            $transactionsQuery = Transaction::whereHas('reservation', function($q) {
                    $q->where('status', 'approved');
                })
                ->with(['reservation', 'transactionDetail', 'chargingPoint']);
            
            if ($transactionId) {
                $transactionsQuery->where('id', $transactionId);
            }
            
            $transactions = $transactionsQuery->get();
            
            $this->info("   Total transactions trouvées: {$transactions->count()}");
            $this->newLine();

            // ÉTAPE 2 : Vérifier/créer les TransactionDetails
            $this->info('ÉTAPE 2 : Vérification des TransactionDetails...');
            $detailsCreated = 0;
            $detailsFixed = 0;
            $detailsErrors = 0;
            $partsInconsistencies = 0;

            foreach ($transactions as $transaction) {
                $this->line("   Transaction #{$transaction->id}...");
                
                // Vérifier si TransactionDetail existe
                if (!$transaction->transactionDetail) {
                    $this->warn("      ⚠ TransactionDetail manquant - Création...");
                    
                    if (!$dryRun) {
                        try {
                            $result = $reservationTransactionService->createRetroactiveTransactionDetail($transaction);
                            
                            if ($result['success']) {
                                $detailsCreated++;
                                $this->info("      ✓ TransactionDetail créé");
                            } else {
                                $detailsErrors++;
                                $this->error("      ✗ Erreur: " . implode(', ', $result['errors']));
                            }
                        } catch (\Exception $e) {
                            $detailsErrors++;
                            $this->error("      ✗ Exception: " . $e->getMessage());
                        }
                    } else {
                        $detailsCreated++;
                        $this->info("      [DRY-RUN] TransactionDetail serait créé");
                    }
                } else {
                    // Vérifier la cohérence des parts
                    $detail = $transaction->transactionDetail;
                    $totalAmount = (float) ($transaction->price_total ?? $transaction->amount ?? 0);
                    $adminShare = (float) ($detail->admin_share_amount ?? 0);
                    $integratorShare = (float) ($detail->integrator_share_amount ?? 0);
                    $operatorShare = (float) ($detail->operator_share_amount ?? 0);
                    $sumOfShares = $adminShare + $integratorShare + $operatorShare;
                    
                    // Tolérance de 0.01 pour les arrondis
                    $difference = abs($sumOfShares - $totalAmount);
                    
                    if ($difference > 0.01 && $totalAmount > 0) {
                        $partsInconsistencies++;
                        $this->warn("      ⚠ Incohérence des parts détectée:");
                        $this->line("         Total: " . number_format($totalAmount, 2) . " €");
                        $this->line("         Somme des parts: " . number_format($sumOfShares, 2) . " €");
                        $this->line("         Différence: " . number_format($difference, 2) . " €");
                        $this->line("         Admin: " . number_format($adminShare, 2) . " €");
                        $this->line("         Intégrateur: " . number_format($integratorShare, 2) . " €");
                        $this->line("         Opérateur: " . number_format($operatorShare, 2) . " €");
                        
                        if (!$dryRun) {
                            $this->info("      → Recalcul des parts...");
                            try {
                                $result = $reservationTransactionService->recalculateTransactionShares($transaction, true);
                                
                                if ($result['success']) {
                                    $detailsFixed++;
                                    $this->info("      ✓ Parts corrigées");
                                } else {
                                    $detailsErrors++;
                                    $this->error("      ✗ Erreur lors du recalcul: " . implode(', ', $result['errors']));
                                }
                            } catch (\Exception $e) {
                                $detailsErrors++;
                                $this->error("      ✗ Exception: " . $e->getMessage());
                            }
                        } else {
                            $detailsFixed++;
                            $this->info("      [DRY-RUN] Parts seraient recalculées");
                        }
                    } else {
                        $this->line("      ✓ TransactionDetail OK");
                    }
                }
            }

            $this->newLine();
            $this->info("   Résumé TransactionDetails:");
            $this->line("      Créés: {$detailsCreated}");
            $this->line("      Corrigés: {$detailsFixed}");
            $this->line("      Incohérences détectées: {$partsInconsistencies}");
            $this->line("      Erreurs: {$detailsErrors}");
            $this->newLine();

            // ÉTAPE 3 : Synchroniser tous les balances
            $this->info('ÉTAPE 3 : Synchronisation des balances...');
            
            if ($userId) {
                $user = User::find($userId);
                if (!$user) {
                    $this->error("Utilisateur non trouvé (ID: {$userId})");
                    return 1;
                }
                
                $this->synchronizeUser($user, $balanceSyncService, $dryRun);
            } else {
                // Synchroniser tous les utilisateurs avec rôles pertinents
                $users = User::whereHas('roles', function($q) {
                    $q->whereIn('name', ['admin', 'super_admin', 'integrator', 'operator', 'partner']);
                })->get();
                
                $this->info("   Synchronisation de {$users->count()} utilisateurs...");
                $this->newLine();
                
                $synchronizedCount = 0;
                $upToDateCount = 0;
                $errorCount = 0;
                
                foreach ($users as $user) {
                    $result = $this->synchronizeUser($user, $balanceSyncService, $dryRun);
                    
                    if ($result === 'synchronized') {
                        $synchronizedCount++;
                    } elseif ($result === 'up_to_date') {
                        $upToDateCount++;
                    } else {
                        $errorCount++;
                    }
                }
                
                $this->newLine();
                $this->info("   Résumé synchronisation:");
                $this->line("      Synchronisés: {$synchronizedCount}");
                $this->line("      Déjà à jour: {$upToDateCount}");
                $this->line("      Erreurs: {$errorCount}");
            }

            $this->newLine();
            $this->info('=== TERMINÉ ===');
            
            if ($dryRun) {
                $this->warn('Mode DRY-RUN - Aucune modification effectuée');
                $this->info('Exécutez sans --dry-run pour appliquer les modifications');
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Erreur: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    protected function synchronizeUser(User $user, BalanceSynchronizationService $service, bool $dryRun): string
    {
        $role = $user->roles->first()?->name ?? 'unknown';
        $this->line("   Utilisateur #{$user->id} ({$user->name}) - Rôle: {$role}");
        
        if ($dryRun) {
            // En mode dry-run, calculer seulement le balance attendu
            try {
                $calculatedBalance = $service->calculateBalanceFromApprovedTransactions($user);
                $wallet = $user->getOrCreateWallet();
                $currentBalance = (float) $wallet->balance;
                $difference = abs($calculatedBalance - $currentBalance);
                
                if ($difference > 0.01) {
                    $this->warn("      ⚠ Balance désynchronisé:");
                    $this->line("         Actuel: " . number_format($currentBalance, 2) . " €");
                    $this->line("         Calculé: " . number_format($calculatedBalance, 2) . " €");
                    $this->line("         Différence: " . number_format($difference, 2) . " €");
                    $this->info("      [DRY-RUN] Balance serait synchronisé");
                    return 'would_sync';
                } else {
                    $this->line("      ✓ Balance OK");
                    return 'up_to_date';
                }
            } catch (\Exception $e) {
                $this->error("      ✗ Erreur: " . $e->getMessage());
                return 'error';
            }
        } else {
            try {
                $result = $service->synchronizeUserBalance($user);
                
                if ($result['synchronized']) {
                    $this->info("      ✓ Balance synchronisé");
                    $this->line("         Ancien: " . number_format($result['old_balance'], 2) . " €");
                    $this->line("         Nouveau: " . number_format($result['new_balance'], 2) . " €");
                    return 'synchronized';
                } else {
                    $this->line("      ✓ Balance déjà à jour");
                    return 'up_to_date';
                }
            } catch (\Exception $e) {
                $this->error("      ✗ Erreur: " . $e->getMessage());
                return 'error';
            }
        }
    }
}

