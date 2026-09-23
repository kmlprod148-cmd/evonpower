<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\BalanceSynchronizationService;
use Illuminate\Support\Facades\Log;

class DiagnoseAdminBalance extends Command
{
    protected $signature = 'admin:diagnose-balance {user_id?}';
    protected $description = 'Diagnostiquer le balance de l\'admin et vérifier la synchronisation';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        // Trouver l'admin
        if ($userId) {
            $admin = User::find($userId);
        } else {
            $admin = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'super_admin']);
            })->first();
        }
        
        if (!$admin) {
            $this->error('Aucun admin trouvé');
            return 1;
        }
        
        $this->info("Diagnostic du balance pour l'admin: {$admin->name} (ID: {$admin->id})");
        $this->newLine();
        
        // 1. Vérifier les réservations approuvées
        $this->info("1. Réservations approuvées:");
        $approvedReservations = \App\Models\Reservation::whereIn('status', ['confirmed', 'completed'])->get();
        $this->info("   Total: {$approvedReservations->count()}");
        
        if ($approvedReservations->count() > 0) {
            $reservationsWithTransactions = $approvedReservations->filter(function($res) {
                return $res->transaction !== null;
            });
            $this->info("   Avec transaction: {$reservationsWithTransactions->count()}");
            $this->info("   Sans transaction: " . ($approvedReservations->count() - $reservationsWithTransactions->count()));
        }
        
        // 2. Vérifier les transactions approuvées
        $this->info("2. Transactions approuvées:");
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
            ->get();
        
        $this->info("   Total: {$approvedTransactions->count()}");
        
        if ($approvedTransactions->count() > 0) {
            $transactionsWithDetails = $approvedTransactions->filter(function($t) {
                return $t->transactionDetail !== null;
            });
            $this->info("   Avec TransactionDetail: {$transactionsWithDetails->count()}");
            $this->info("   Sans TransactionDetail: " . ($approvedTransactions->count() - $transactionsWithDetails->count()));
        }
        
        // 3. Vérifier les TransactionDetail
        $this->info("3. TransactionDetail:");
        $totalDetails = TransactionDetail::count();
        $this->info("   Total dans la base: {$totalDetails}");
        
        $adminDetails = TransactionDetail::whereHas('transaction', function($q) {
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
                });
            })
            ->get()
            ->filter(function($detail) {
                if (($detail->admin_share_amount ?? 0) > 0) {
                    return true;
                }
                $calculationDetails = $detail->calculation_details;
                if ($calculationDetails) {
                    $adminFeesFromDetails = 0;
                    if (is_array($calculationDetails) && isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                        $adminFeesFromDetails = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                    } elseif (is_string($calculationDetails)) {
                        $decoded = json_decode($calculationDetails, true);
                        if (is_array($decoded) && isset($decoded['hierarchical_logic']['admin_fees_amount'])) {
                            $adminFeesFromDetails = (float) $decoded['hierarchical_logic']['admin_fees_amount'];
                        }
                    }
                    return $adminFeesFromDetails > 0;
                }
                return false;
            });
        
        $this->info("   TransactionDetail avec admin_share > 0 ou admin_fees dans calculation_details: {$adminDetails->count()}");
        
        // Afficher les détails du TransactionDetail existant
        if ($totalDetails > 0) {
            $allDetails = TransactionDetail::with('transaction.reservation')->get();
            $this->info("   Détails des TransactionDetail existants:");
            foreach ($allDetails as $detail) {
                $transaction = $detail->transaction;
                $adminShare = (float) ($detail->admin_share_amount ?? 0);
                $calculationDetails = $detail->calculation_details;
                $adminFeesFromDetails = 0;
                
                if ($calculationDetails) {
                    if (is_array($calculationDetails) && isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                        $adminFeesFromDetails = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                    } elseif (is_string($calculationDetails)) {
                        $decoded = json_decode($calculationDetails, true);
                        if (is_array($decoded) && isset($decoded['hierarchical_logic']['admin_fees_amount'])) {
                            $adminFeesFromDetails = (float) $decoded['hierarchical_logic']['admin_fees_amount'];
                        }
                    }
                }
                
                $this->info("     TransactionDetail #{$detail->id} (Transaction #{$transaction->id}):");
                $this->info("       - admin_share_amount: " . number_format($adminShare, 2) . " €");
                $this->info("       - admin_fees_from_details: " . number_format($adminFeesFromDetails, 2) . " €");
                $this->info("       - transaction status: " . ($transaction->status ?? 'N/A'));
                $this->info("       - reservation status: " . ($transaction->reservation->status ?? 'N/A'));
                $this->info("       - transaction amount: " . number_format($transaction->amount ?? $transaction->price_total ?? 0, 2) . " €");
            }
        }
        
        // 4. Calculer les totaux
        $this->info("4. Calcul des totaux:");
        $balanceSyncService = app(BalanceSynchronizationService::class);
        $totals = $balanceSyncService->calculateTotalsFromApprovedTransactions($admin);
        $calculatedBalance = $balanceSyncService->calculateBalanceFromApprovedTransactions($admin);
        
        $this->info("   Total Credits: " . number_format($totals['total_credits'], 2) . " €");
        $this->info("   Total Debits: " . number_format($totals['total_debits'], 2) . " €");
        $this->info("   Net Amount: " . number_format($totals['net_amount'], 2) . " €");
        $this->info("   Calculated Balance: " . number_format($calculatedBalance, 2) . " €");
        
        // 5. Vérifier le wallet
        $this->info("5. Wallet:");
        $wallet = $admin->getOrCreateWallet();
        $this->info("   Wallet Balance: " . number_format($wallet->balance, 2) . " €");
        $this->info("   Wallet Total Credits: " . number_format($wallet->getTotalCredits(), 2) . " €");
        $this->info("   Wallet Total Debits: " . number_format($wallet->getTotalDebits(), 2) . " €");
        
        // 6. Vérifier la synchronisation
        $this->info("6. Synchronisation:");
        $balanceDiff = abs($wallet->balance - $calculatedBalance);
        $creditsDiff = abs($wallet->getTotalCredits() - $totals['total_credits']);
        $debitsDiff = abs($wallet->getTotalDebits() - $totals['total_debits']);
        
        if ($balanceDiff <= 0.01 && $creditsDiff <= 0.01 && $debitsDiff <= 0.01) {
            $this->info("   ✓ Synchronisé");
        } else {
            $this->warn("   ✗ Non synchronisé:");
            if ($balanceDiff > 0.01) {
                $this->warn("     - Balance diff: " . number_format($balanceDiff, 2) . " €");
            }
            if ($creditsDiff > 0.01) {
                $this->warn("     - Credits diff: " . number_format($creditsDiff, 2) . " €");
            }
            if ($debitsDiff > 0.01) {
                $this->warn("     - Debits diff: " . number_format($debitsDiff, 2) . " €");
            }
            
            // Proposer de synchroniser
            if ($this->confirm('Voulez-vous synchroniser maintenant?', true)) {
                $this->info("Synchronisation en cours...");
                $syncResult = $balanceSyncService->synchronizeUserBalance($admin);
                $wallet->refresh();
                
                $this->info("   Nouveau Balance: " . number_format($wallet->balance, 2) . " €");
                $this->info("   Nouveau Total Credits: " . number_format($wallet->getTotalCredits(), 2) . " €");
                $this->info("   Nouveau Total Debits: " . number_format($wallet->getTotalDebits(), 2) . " €");
            }
        }
        
        // 7. Actions correctives possibles
        $this->newLine();
        $this->info("7. Actions correctives:");
        
        if ($approvedReservations->count() > 0) {
            $reservationsWithoutTransactions = $approvedReservations->filter(function($res) {
                return $res->transaction === null;
            });
            
            if ($reservationsWithoutTransactions->count() > 0) {
                $this->warn("   - {$reservationsWithoutTransactions->count()} réservation(s) approuvée(s) sans transaction");
                if ($this->confirm('   Créer les transactions manquantes?', false)) {
                    $this->info("   Création des transactions en cours...");
                    $transactionService = app(\App\Services\ReservationTransactionService::class);
                    $created = 0;
                    foreach ($reservationsWithoutTransactions as $reservation) {
                        try {
                            $result = $transactionService->processReservationTransaction($reservation);
                            if ($result['success']) {
                                $created++;
                            }
                        } catch (\Exception $e) {
                            $this->error("   Erreur pour réservation #{$reservation->id}: " . $e->getMessage());
                        }
                    }
                    $this->info("   {$created} transaction(s) créée(s)");
                }
            }
        }
        
        if ($approvedTransactions->count() > 0) {
            $transactionsWithoutDetails = $approvedTransactions->filter(function($t) {
                return $t->transactionDetail === null;
            });
            
            if ($transactionsWithoutDetails->count() > 0) {
                $this->warn("   - {$transactionsWithoutDetails->count()} transaction(s) sans TransactionDetail");
                if ($this->confirm('   Créer les TransactionDetail manquants?', false)) {
                    $this->info("   Création des TransactionDetail en cours...");
                    $transactionService = app(\App\Services\ReservationTransactionService::class);
                    $created = 0;
                    foreach ($transactionsWithoutDetails as $transaction) {
                        try {
                            $result = $transactionService->createRetroactiveTransactionDetail($transaction);
                            if ($result['success']) {
                                $created++;
                            }
                        } catch (\Exception $e) {
                            $this->error("   Erreur pour transaction #{$transaction->id}: " . $e->getMessage());
                        }
                    }
                    $this->info("   {$created} TransactionDetail créé(s)");
                }
            }
        }
        
        // 8. Détails des TransactionDetail (verbose)
        if ($this->option('verbose')) {
            $this->newLine();
            $this->info("8. Détails des TransactionDetail:");
            foreach ($adminDetails->take(10) as $detail) {
                $transaction = $detail->transaction;
                $adminShare = (float) ($detail->admin_share_amount ?? 0);
                $calculationDetails = $detail->calculation_details;
                $adminFeesFromDetails = 0;
                
                if ($calculationDetails) {
                    if (is_array($calculationDetails) && isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                        $adminFeesFromDetails = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                    } elseif (is_string($calculationDetails)) {
                        $decoded = json_decode($calculationDetails, true);
                        if (is_array($decoded) && isset($decoded['hierarchical_logic']['admin_fees_amount'])) {
                            $adminFeesFromDetails = (float) $decoded['hierarchical_logic']['admin_fees_amount'];
                        }
                    }
                }
                
                $finalAmount = max($adminShare, $adminFeesFromDetails);
                
                $this->info("   Transaction #{$transaction->id}:");
                $this->info("     - admin_share_amount: " . number_format($adminShare, 2) . " €");
                $this->info("     - admin_fees_from_details: " . number_format($adminFeesFromDetails, 2) . " €");
                $this->info("     - final_amount: " . number_format($finalAmount, 2) . " €");
            }
        }
        
        return 0;
    }
}

