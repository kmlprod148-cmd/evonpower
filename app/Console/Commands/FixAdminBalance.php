<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\ReservationTransactionService;
use App\Services\BalanceSynchronizationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FixAdminBalance extends Command
{
    protected $signature = 'admin:fix-balance {--force : Forcer la création même si TransactionDetail existe}';
    protected $description = 'Créer les TransactionDetail manquants pour les réservations approuvées et synchroniser le balance admin';

    public function handle()
    {
        $this->info("Correction du balance admin...");
        $this->newLine();
        
        // Trouver l'admin
        $admin = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'super_admin']);
        })->first();
        
        if (!$admin) {
            $this->error('Aucun admin trouvé');
            return 1;
        }
        
        $this->info("Admin: {$admin->name} (ID: {$admin->id})");
        $this->newLine();
        
        // 1. Trouver les réservations approuvées sans transaction
        $this->info("1. Vérification des réservations approuvées...");
        $approvedReservations = Reservation::where('status', 'approved')->get();
        $this->info("   Total réservations approuvées: {$approvedReservations->count()}");
        
        $reservationsWithoutTransactions = $approvedReservations->filter(function($res) {
            return $res->transaction === null;
        });
        
        $this->info("   Réservations sans transaction: {$reservationsWithoutTransactions->count()}");
        
        // Créer les transactions manquantes
        if ($reservationsWithoutTransactions->count() > 0) {
            $this->info("   Création des transactions manquantes...");
            $transactionService = app(ReservationTransactionService::class);
            $created = 0;
            $errors = 0;
            
            foreach ($reservationsWithoutTransactions as $reservation) {
                try {
                    $result = $transactionService->processReservationTransaction($reservation);
                    if ($result['success']) {
                        $created++;
                        $this->info("     ✓ Transaction créée pour réservation #{$reservation->id}");
                    } else {
                        $errors++;
                        $this->warn("     ✗ Erreur pour réservation #{$reservation->id}: " . ($result['error'] ?? 'Erreur inconnue'));
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("     ✗ Exception pour réservation #{$reservation->id}: " . $e->getMessage());
                }
            }
            
            $this->info("   {$created} transaction(s) créée(s), {$errors} erreur(s)");
        }
        
        // 2. Trouver les transactions approuvées sans TransactionDetail
        $this->newLine();
        $this->info("2. Vérification des transactions approuvées...");
        $approvedTransactions = Transaction::where(function($statusQuery) {
                $statusQuery->whereIn('status', ['completed', 'confirmed'])
                    ->orWhereHas('reservation', function($resQuery) {
                        $resQuery->where('status', 'approved');
                    });
            })
            ->where(function($notPendingQuery) {
                $notPendingQuery->where('status', '!=', 'pending')
                    ->orWhereHas('reservation', function($resQuery) {
                        $resQuery->where('status', 'approved');
                    });
            })
            ->where(function($amountQuery) {
                $amountQuery->where('amount', '>', 0)
                           ->orWhere('price_total', '>', 0);
            })
            ->get();
        
        $this->info("   Total transactions approuvées: {$approvedTransactions->count()}");
        
        $transactionsWithoutDetails = $approvedTransactions->filter(function($t) {
            return $t->transactionDetail === null;
        });
        
        $this->info("   Transactions sans TransactionDetail: {$transactionsWithoutDetails->count()}");
        
        // Créer les TransactionDetail manquants
        if ($transactionsWithoutDetails->count() > 0) {
            $this->info("   Création des TransactionDetail manquants...");
            $transactionService = app(ReservationTransactionService::class);
            $created = 0;
            $errors = 0;
            
            foreach ($transactionsWithoutDetails as $transaction) {
                try {
                    $result = $transactionService->createRetroactiveTransactionDetail($transaction);
                    if ($result['success']) {
                        $created++;
                        $adminShare = $result['transaction_detail']->admin_share_amount ?? 0;
                        $this->info("     ✓ TransactionDetail créé pour transaction #{$transaction->id} (admin_share: " . number_format($adminShare, 2) . " €)");
                    } else {
                        $errors++;
                        $this->warn("     ✗ Erreur pour transaction #{$transaction->id}: " . implode(', ', $result['errors'] ?? ['Erreur inconnue']));
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("     ✗ Exception pour transaction #{$transaction->id}: " . $e->getMessage());
                }
            }
            
            $this->info("   {$created} TransactionDetail créé(s), {$errors} erreur(s)");
        }
        
        // 3. Recalculer les TransactionDetail avec parts à 0
        $this->newLine();
        $this->info("3. Vérification des TransactionDetail avec parts à 0...");
        
        $zeroShareDetails = TransactionDetail::whereHas('transaction', function($q) {
                $q->where(function($statusQuery) {
                    $statusQuery->whereIn('status', ['completed', 'confirmed'])
                        ->orWhereHas('reservation', function($resQuery) {
                            $resQuery->where('status', 'approved');
                        });
                })
                ->where(function($notPendingQuery) {
                    $notPendingQuery->where('status', '!=', 'pending')
                        ->orWhereHas('reservation', function($resQuery) {
                            $resQuery->where('status', 'approved');
                        });
                })
                ->where(function($amountQuery) {
                    $amountQuery->where('amount', '>', 0)
                               ->orWhere('price_total', '>', 0);
                });
            })
            ->where('admin_share_amount', '<=', 0)
            ->get();
        
        $this->info("   TransactionDetail avec admin_share_amount <= 0: {$zeroShareDetails->count()}");
        
        if ($zeroShareDetails->count() > 0 && ($this->option('force') || $this->confirm('   Recalculer les parts pour ces TransactionDetail?', true))) {
            $this->info("   Recalcul des parts...");
            $transactionService = app(ReservationTransactionService::class);
            $recalculated = 0;
            $errors = 0;
            
            foreach ($zeroShareDetails as $detail) {
                try {
                    $transaction = $detail->transaction;
                    if ($transaction) {
                        $result = $transactionService->recalculateTransactionShares($transaction, true);
                        if ($result['success']) {
                            $recalculated++;
                            $newAdminShare = $result['transaction_detail']->admin_share_amount ?? 0;
                            $this->info("     ✓ Parts recalculées pour transaction #{$transaction->id} (nouveau admin_share: " . number_format($newAdminShare, 2) . " €)");
                        } else {
                            $errors++;
                            $this->warn("     ✗ Erreur pour transaction #{$transaction->id}: " . implode(', ', $result['errors'] ?? ['Erreur inconnue']));
                        }
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("     ✗ Exception pour transaction #{$detail->transaction_id}: " . $e->getMessage());
                }
            }
            
            $this->info("   {$recalculated} TransactionDetail recalculé(s), {$errors} erreur(s)");
        }
        
        // 4. Synchroniser le balance
        $this->newLine();
        $this->info("4. Synchronisation du balance...");
        
        try {
            $balanceSyncService = app(BalanceSynchronizationService::class);
            $syncResult = $balanceSyncService->synchronizeUserBalance($admin);
            
            $wallet = $admin->getOrCreateWallet();
            $wallet->refresh();
            
            $totals = $balanceSyncService->calculateTotalsFromApprovedTransactions($admin);
            $calculatedBalance = $balanceSyncService->calculateBalanceFromApprovedTransactions($admin);
            
            $this->info("   ✓ Synchronisation terminée");
            $this->info("   Balance: " . number_format($wallet->balance, 2) . " €");
            $this->info("   Total Credits: " . number_format($totals['total_credits'], 2) . " €");
            $this->info("   Total Debits: " . number_format($totals['total_debits'], 2) . " €");
            $this->info("   Net Amount: " . number_format($totals['net_amount'], 2) . " €");
            
        } catch (\Exception $e) {
            $this->error("   ✗ Erreur lors de la synchronisation: " . $e->getMessage());
            return 1;
        }
        
        $this->newLine();
        $this->info("=== TERMINÉ ===");
        
        return 0;
    }
}

