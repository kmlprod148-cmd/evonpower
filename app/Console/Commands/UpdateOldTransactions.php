<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use App\Services\ReservationTransactionService;
use App\Services\BalanceSynchronizationService;

class UpdateOldTransactions extends Command
{
    protected $signature = 'transactions:update-old 
                            {--force : Force la mise à jour même si TransactionDetail existe}
                            {--operator-id= : Mettre à jour uniquement pour un opérateur spécifique}';
    
    protected $description = 'Met à jour toutes les anciennes transactions approuvées et synchronise les balances';

    public function handle()
    {
        $this->info("=== MISE À JOUR DES ANCIENNES TRANSACTIONS ===");
        $this->newLine();
        
        $force = $this->option('force');
        $operatorId = $this->option('operator-id');
        
        // 1. Trouver toutes les transactions approuvées
        $this->info("1. Recherche des transactions approuvées...");
        
        $query = Transaction::where(function($statusQuery) {
                $statusQuery->whereIn('status', ['completed', 'confirmed'])
                    ->orWhereHas('reservation', function($resQuery) {
                        $resQuery->where('status', 'approved');
                    });
            })
            // Inclure les transactions avec réservations approuvées même si status = 'pending'
            ->with(['transactionDetail', 'reservation', 'chargingPoint']);
        
        if ($operatorId) {
            $query->where(function($q) use ($operatorId) {
                $q->where('operator_id', $operatorId)
                  ->orWhereHas('chargingPoint', function($cpQuery) use ($operatorId) {
                      $cpQuery->where('user_id', $operatorId)
                             ->orWhere('created_by_id', $operatorId)
                             ->orWhere('created_by', $operatorId);
                  });
            });
        }
        
        $allTransactions = $query->get();
        
        $this->info("   Total transactions approuvées trouvées: {$allTransactions->count()}");
        
        // Séparer celles avec et sans TransactionDetail
        $transactionsWithoutDetails = $allTransactions->filter(function($t) {
            return !$t->transactionDetail;
        });
        
        $transactionsWithDetails = $allTransactions->filter(function($t) {
            return $t->transactionDetail;
        });
        
        $this->info("   - Avec TransactionDetail: {$transactionsWithDetails->count()}");
        $this->info("   - Sans TransactionDetail: {$transactionsWithoutDetails->count()}");
        $this->newLine();
        
        $transactionService = app(ReservationTransactionService::class);
        $created = 0;
        $failed = 0;
        $updated = 0;
        
        // 2. Créer les TransactionDetails manquants
        if ($transactionsWithoutDetails->count() > 0) {
            $this->info("2. Création des TransactionDetails manquants...");
            $bar = $this->output->createProgressBar($transactionsWithoutDetails->count());
            $bar->start();
            
            foreach ($transactionsWithoutDetails as $transaction) {
                try {
                    $result = $transactionService->createRetroactiveTransactionDetail($transaction);
                    if ($result['success']) {
                        $created++;
                    } else {
                        $failed++;
                        $this->newLine();
                        $this->warn("   ✗ Transaction #{$transaction->id} - Échec: " . ($result['error'] ?? 'Unknown'));
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $this->newLine();
                    $this->error("   ✗ Transaction #{$transaction->id} - Exception: " . $e->getMessage());
                }
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("   Créés: {$created}, Échoués: {$failed}");
            $this->newLine();
        }
        
        // 3. Recalculer les TransactionDetails existants si --force
        if ($force && $transactionsWithDetails->count() > 0) {
            $this->info("3. Recalcul des TransactionDetails existants (--force)...");
            $bar = $this->output->createProgressBar($transactionsWithDetails->count());
            $bar->start();
            
            foreach ($transactionsWithDetails as $transaction) {
                try {
                    $result = $transactionService->recalculateTransactionShares($transaction, true);
                    if ($result['success']) {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->error("   ✗ Transaction #{$transaction->id} - Exception: " . $e->getMessage());
                }
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("   Mis à jour: {$updated}");
            $this->newLine();
        }
        
        // 4. Corriger les operator_id incorrects dans les TransactionDetails
        $this->info("4. Correction des operator_id incorrects...");
        
        $allDetails = TransactionDetail::whereHas('transaction', function($q) {
                $q->where(function($statusQuery) {
                    $statusQuery->whereIn('status', ['completed', 'confirmed'])
                        ->orWhereHas('reservation', function($resQuery) {
                            $resQuery->where('status', 'approved');
                        });
                    });
                // Inclure les transactions avec réservations approuvées même si status = 'pending'
            })
            ->with('transaction.chargingPoint')
            ->get();
        
        $corrected = 0;
        foreach ($allDetails as $detail) {
            $transaction = $detail->transaction;
            if (!$transaction || !$transaction->chargingPoint) continue;
            
            $cp = $transaction->chargingPoint;
            $correctOperatorId = $cp->user_id ?? $cp->created_by_id ?? $cp->created_by ?? $transaction->operator_id;
            
            if ($correctOperatorId && $detail->operator_id != $correctOperatorId) {
                $detail->update(['operator_id' => $correctOperatorId]);
                $corrected++;
            }
        }
        
        $this->info("   TransactionDetails corrigés: {$corrected}");
        $this->newLine();
        
        // 5. Synchroniser les balances pour tous les utilisateurs
        $this->info("5. Synchronisation des balances pour tous les utilisateurs...");
        
        $balanceSyncService = app(BalanceSynchronizationService::class);
        
        // Récupérer tous les opérateurs, intégrateurs et admins
        $operators = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['operator', 'partner']);
        })->get();
        
        $integrators = User::whereHas('roles', function($q) {
            $q->where('name', 'integrator');
        })->get();
        
        $admins = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'super_admin']);
        })->get();
        
        $totalUsers = $operators->count() + $integrators->count() + $admins->count();
        $this->info("   Total utilisateurs à synchroniser: {$totalUsers}");
        
        if ($operatorId) {
            $operators = $operators->filter(fn($u) => $u->id == $operatorId);
            $totalUsers = $operators->count();
            $this->info("   Synchronisation uniquement pour l'opérateur ID: {$operatorId}");
        }
        
        $bar = $this->output->createProgressBar($totalUsers);
        $bar->start();
        
        $synced = 0;
        foreach ($operators as $user) {
            try {
                $balanceSyncService->synchronizeUserBalance($user);
                $synced++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("   ✗ Erreur pour user {$user->id}: " . $e->getMessage());
            }
            $bar->advance();
        }
        
        foreach ($integrators as $user) {
            try {
                $balanceSyncService->synchronizeUserBalance($user);
                $synced++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("   ✗ Erreur pour user {$user->id}: " . $e->getMessage());
            }
            $bar->advance();
        }
        
        foreach ($admins as $user) {
            try {
                $balanceSyncService->synchronizeUserBalance($user);
                $synced++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("   ✗ Erreur pour user {$user->id}: " . $e->getMessage());
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("   Utilisateurs synchronisés: {$synced}");
        $this->newLine();
        
        // 6. Résumé final
        $this->info("=== RÉSUMÉ ===");
        $this->info("TransactionDetails créés: {$created}");
        if ($force) {
            $this->info("TransactionDetails mis à jour: {$updated}");
        }
        $this->info("Operator IDs corrigés: {$corrected}");
        $this->info("Balances synchronisés: {$synced}");
        $this->newLine();
        $this->info("✓ Mise à jour terminée!");
        
        return 0;
    }
}

