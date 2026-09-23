<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\ReservationTransactionService;
use App\Services\BalanceSynchronizationService;

class ForceSyncOperatorBalances extends Command
{
    protected $signature = 'balances:force-sync-operator {user_id?}';
    protected $description = 'Force la synchronisation des balances pour un opérateur et crée les TransactionDetails manquants';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        // Si aucun user_id fourni, lister les opérateurs disponibles
        if (!$userId) {
            $this->info("=== LISTE DES OPÉRATEURS DISPONIBLES ===");
            $operators = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['operator', 'partner']);
            })->get(['id', 'name', 'email']);
            
            if ($operators->isEmpty()) {
                $this->warn("Aucun opérateur trouvé dans la base de données.");
                return 1;
            }
            
            $this->table(['ID', 'Nom', 'Email'], $operators->map(function($op) {
                return [$op->id, $op->name, $op->email];
            })->toArray());
            
            $this->newLine();
            $userId = $this->ask('Entrez l\'ID de l\'opérateur à synchroniser');
        }
        
        if (!$userId) {
            $this->error("ID utilisateur requis!");
            return 1;
        }
        
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User not found with ID: {$userId}");
            $this->info("Utilisez 'php artisan balances:force-sync-operator' sans argument pour voir la liste des opérateurs.");
            return 1;
        }
        
        if (!$user->hasRole(['operator', 'partner'])) {
            $this->error("User is not an operator or partner! (ID: {$user->id}, Name: {$user->name})");
            $this->info("Roles de l'utilisateur: " . $user->roles->pluck('name')->implode(', '));
            return 1;
        }
        
        $this->info("=== FORCE SYNC OPERATOR BALANCES ===");
        $this->info("User: {$user->name} (ID: {$user->id})");
        $this->newLine();
        
        // 1. Trouver toutes les transactions approuvées (avec ET sans TransactionDetail)
        $this->info("1. Recherche de toutes les transactions approuvées liées à l'opérateur...");
        
        // D'abord, trouver toutes les transactions approuvées liées à cet opérateur
        $allApprovedTransactions = Transaction::where(function($statusQuery) {
                $statusQuery->whereIn('status', ['completed', 'confirmed'])
                    ->orWhereHas('reservation', function($resQuery) {
                        $resQuery->where('status', 'approved');
                    });
            })
            ->where('status', '!=', 'pending')
            ->where(function($q) use ($user) {
                $q->whereHas('chargingPoint', function($cpQuery) use ($user) {
                    $cpQuery->where(function($cpSubQuery) use ($user) {
                        $cpSubQuery->where('user_id', $user->id)
                                  ->orWhere('created_by_id', $user->id)
                                  ->orWhere('created_by', $user->id);
                    })
                    ->orWhereHas('group', function($groupQuery) use ($user) {
                        $groupQuery->where('user_id', $user->id);
                    });
                })
                ->orWhere('operator_id', $user->id);
            })
            ->with(['transactionDetail', 'reservation', 'chargingPoint'])
            ->get();
        
        $this->info("   Total transactions approuvées trouvées: {$allApprovedTransactions->count()}");
        
        // Séparer celles avec et sans TransactionDetail
        $transactionsWithoutDetails = $allApprovedTransactions->filter(function($t) {
            return !$t->transactionDetail;
        });
        
        $transactionsWithDetails = $allApprovedTransactions->filter(function($t) {
            return $t->transactionDetail;
        });
        
        $this->info("   - Avec TransactionDetail: {$transactionsWithDetails->count()}");
        $this->info("   - Sans TransactionDetail: {$transactionsWithoutDetails->count()}");
        
        if ($transactionsWithoutDetails->count() > 0) {
            $this->warn("   ⚠ ATTENTION: {$transactionsWithoutDetails->count()} transactions sans TransactionDetail!");
            $this->info("   IDs: " . $transactionsWithoutDetails->pluck('id')->implode(', '));
        }
        
        if ($transactionsWithoutDetails->count() > 0) {
            $this->info("   Création des TransactionDetails manquants...");
            $transactionService = app(ReservationTransactionService::class);
            $created = 0;
            $failed = 0;
            
            $bar = $this->output->createProgressBar($transactionsWithoutDetails->count());
            $bar->start();
            
            foreach ($transactionsWithoutDetails as $transaction) {
                try {
                    $result = $transactionService->createRetroactiveTransactionDetail($transaction);
                    if ($result['success']) {
                        $created++;
                        // Vérifier que le TransactionDetail a bien été créé avec operator_id correct
                        $detail = $result['transaction_detail'] ?? null;
                        if ($detail) {
                            // S'assurer que operator_id correspond à l'utilisateur
                            if ($detail->operator_id != $user->id) {
                                $detail->update(['operator_id' => $user->id]);
                                $this->newLine();
                                $this->info("   ✓ Transaction #{$transaction->id} - TransactionDetail créé et operator_id mis à jour");
                            } else {
                                $this->newLine();
                                $this->info("   ✓ Transaction #{$transaction->id} - TransactionDetail créé");
                            }
                        }
                    } else {
                        $failed++;
                        $this->newLine();
                        $this->warn("   ✗ Transaction #{$transaction->id} - Échec: " . ($result['error'] ?? 'Unknown'));
                        if (isset($result['errors']) && is_array($result['errors'])) {
                            $this->warn("      Erreurs: " . implode(', ', $result['errors']));
                        }
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
        }
        
        // Vérifier aussi les TransactionDetails existants qui ont un operator_id différent
        $this->newLine();
        $this->info("   Vérification des TransactionDetails avec operator_id incorrect...");
        $incorrectDetails = TransactionDetail::whereHas('transaction', function($q) use ($user) {
                $q->where(function($statusQuery) {
                    $statusQuery->whereIn('status', ['completed', 'confirmed'])
                        ->orWhereHas('reservation', function($resQuery) {
                            $resQuery->where('status', 'approved');
                        });
                })
                ->where('status', '!=', 'pending')
                ->where(function($tQuery) use ($user) {
                    $tQuery->whereHas('chargingPoint', function($cpQuery) use ($user) {
                        $cpQuery->where(function($cpSubQuery) use ($user) {
                            $cpSubQuery->where('user_id', $user->id)
                                      ->orWhere('created_by_id', $user->id)
                                      ->orWhere('created_by', $user->id);
                        });
                    })
                    ->orWhere('operator_id', $user->id);
                });
            })
            ->where('operator_id', '!=', $user->id)
            ->where('operator_share_amount', '>', 0)
            ->get();
        
        if ($incorrectDetails->count() > 0) {
            $this->warn("   ⚠ Trouvé {$incorrectDetails->count()} TransactionDetails avec operator_id incorrect!");
            $this->info("   Correction des operator_id...");
            $corrected = 0;
            foreach ($incorrectDetails as $detail) {
                $detail->update(['operator_id' => $user->id]);
                $corrected++;
            }
            $this->info("   ✓ {$corrected} TransactionDetails corrigés");
        }
        
        $this->newLine();
        
        // 2. Vérifier les TransactionDetails existants
        $this->info("2. Vérification des TransactionDetails existants...");
        
        // Méthode 1 : Par operator_id direct
        $detailsByOperatorId = TransactionDetail::whereHas('transaction', function($q) {
                $q->where(function($statusQuery) {
                    $statusQuery->whereIn('status', ['completed', 'confirmed'])
                        ->orWhereHas('reservation', function($resQuery) {
                            $resQuery->where('status', 'approved');
                        });
                })
                ->where('status', '!=', 'pending');
            })
            ->where('operator_id', $user->id)
            ->where('operator_share_amount', '>', 0)
            ->count();
        
        $this->info("   TransactionDetails avec operator_id={$user->id}: {$detailsByOperatorId}");
        
        // Méthode 2 : Via chargingPoint
        $detailsByChargingPoint = TransactionDetail::whereHas('transaction', function($q) use ($user) {
                $q->where(function($statusQuery) {
                    $statusQuery->whereIn('status', ['completed', 'confirmed'])
                        ->orWhereHas('reservation', function($resQuery) {
                            $resQuery->where('status', 'approved');
                        });
                })
                ->where('status', '!=', 'pending')
                ->whereHas('chargingPoint', function($cpQuery) use ($user) {
                    $cpQuery->where(function($cpSubQuery) use ($user) {
                        $cpSubQuery->where('user_id', $user->id)
                                  ->orWhere('created_by_id', $user->id)
                                  ->orWhere('created_by', $user->id);
                    })
                    ->orWhereHas('group', function($groupQuery) use ($user) {
                        $groupQuery->where('user_id', $user->id);
                    });
                });
            })
            ->where('operator_share_amount', '>', 0)
            ->count();
        
        $this->info("   TransactionDetails via chargingPoint: {$detailsByChargingPoint}");
        
        // Vérifier tous les TransactionDetails existants
        $allDetails = TransactionDetail::whereHas('transaction', function($q) {
                $q->where(function($statusQuery) {
                    $statusQuery->whereIn('status', ['completed', 'confirmed'])
                        ->orWhereHas('reservation', function($resQuery) {
                            $resQuery->where('status', 'approved');
                        });
                })
                ->where('status', '!=', 'pending');
            })
            ->where('operator_share_amount', '>', 0)
            ->get();
        
        $this->info("   Total TransactionDetails avec operator_share > 0: {$allDetails->count()}");
        
        // Lister les operator_id uniques dans les TransactionDetails
        $allOperatorIds = $allDetails->pluck('operator_id')->filter()->unique()->toArray();
        $this->info("   Operator IDs trouvés dans TransactionDetails: " . implode(', ', $allOperatorIds));
        
        // Vérifier les transactions liées à cet opérateur
        $userTransactions = Transaction::where(function($statusQuery) {
                $statusQuery->whereIn('status', ['completed', 'confirmed'])
                    ->orWhereHas('reservation', function($resQuery) {
                        $resQuery->where('status', 'approved');
                    });
            })
            ->where('status', '!=', 'pending')
            ->where(function($q) use ($user) {
                $q->whereHas('chargingPoint', function($cpQuery) use ($user) {
                    $cpQuery->where(function($cpSubQuery) use ($user) {
                        $cpSubQuery->where('user_id', $user->id)
                                  ->orWhere('created_by_id', $user->id)
                                  ->orWhere('created_by', $user->id);
                    })
                    ->orWhereHas('group', function($groupQuery) use ($user) {
                        $groupQuery->where('user_id', $user->id);
                    });
                })
                ->orWhere('operator_id', $user->id);
            })
            ->get();
        
        $this->info("   Transactions approuvées liées à l'opérateur: {$userTransactions->count()}");
        
        if ($userTransactions->count() > 0) {
            $withDetails = $userTransactions->filter(fn($t) => $t->transactionDetail)->count();
            $withoutDetails = $userTransactions->count() - $withDetails;
            $this->info("   - Avec TransactionDetail: {$withDetails}");
            $this->info("   - Sans TransactionDetail: {$withoutDetails}");
            
            if ($withoutDetails > 0) {
                $this->warn("   ⚠ ATTENTION: {$withoutDetails} transactions sans TransactionDetail trouvées!");
                $this->info("   IDs des transactions sans details: " . $userTransactions->filter(fn($t) => !$t->transactionDetail)->pluck('id')->implode(', '));
            }
        }
        
        // 3. Calculer le total
        $totalCredits = TransactionDetail::whereHas('transaction', function($q) {
                $q->where(function($statusQuery) {
                    $statusQuery->whereIn('status', ['completed', 'confirmed'])
                        ->orWhereHas('reservation', function($resQuery) {
                            $resQuery->where('status', 'approved');
                        });
                })
                ->where('status', '!=', 'pending');
            })
            ->where(function($detailQuery) use ($user) {
                $detailQuery->where('operator_id', $user->id)
                           ->orWhereHas('transaction.chargingPoint', function($cpQuery) use ($user) {
                               $cpQuery->where(function($cpSubQuery) use ($user) {
                                   $cpSubQuery->where('user_id', $user->id)
                                             ->orWhere('created_by_id', $user->id)
                                             ->orWhere('created_by', $user->id);
                               });
                           });
            })
            ->where('operator_share_amount', '>', 0)
            ->sum('operator_share_amount');
        
        $this->info("   Total Credits: " . number_format($totalCredits, 2) . " €");
        
        // 4. Synchroniser le balance
        $this->newLine();
        $this->info("3. Synchronisation du balance...");
        $balanceSyncService = app(BalanceSynchronizationService::class);
        $syncResult = $balanceSyncService->synchronizeUserBalance($user);
        
        $this->info("   Ancien balance: " . number_format($syncResult['old_balance'], 2) . " €");
        $this->info("   Nouveau balance: " . number_format($syncResult['new_balance'], 2) . " €");
        $createdCount = is_array($syncResult['created_transactions']) 
            ? count($syncResult['created_transactions']) 
            : $syncResult['created_transactions'];
        $this->info("   Transactions créées: " . $createdCount);
        
        // 5. Re-synchroniser après toutes les corrections
        $this->newLine();
        $this->info("4. Re-synchronisation finale du balance...");
        $syncResult2 = $balanceSyncService->synchronizeUserBalance($user);
        
        $this->info("   Ancien balance: " . number_format($syncResult2['old_balance'], 2) . " €");
        $this->info("   Nouveau balance: " . number_format($syncResult2['new_balance'], 2) . " €");
        $createdCount2 = is_array($syncResult2['created_transactions']) 
            ? count($syncResult2['created_transactions']) 
            : $syncResult2['created_transactions'];
        $this->info("   Transactions créées: " . $createdCount2);
        
        // 6. Afficher les totaux finaux
        $this->newLine();
        $this->info("5. Totaux finaux après synchronisation:");
        $totals = $balanceSyncService->calculateTotalsFromApprovedTransactions($user);
        $this->info("   Total Credits (Money in): " . number_format($totals['total_credits'], 2) . " €");
        $this->info("   Total Debits (Money out): " . number_format($totals['total_debits'], 2) . " €");
        $this->info("   Net Amount: " . number_format($totals['net_amount'], 2) . " €");
        
        // 7. Vérifier le wallet balance
        $user->refresh();
        $wallet = $user->getOrCreateWallet();
        $wallet->refresh();
        $this->newLine();
        $this->info("6. Wallet Balance:");
        $this->info("   Balance du wallet: " . number_format($wallet->balance, 2) . " €");
        $this->info("   Balance calculé: " . number_format($syncResult2['calculated_balance'], 2) . " €");
        
        if (abs($wallet->balance - $syncResult2['calculated_balance']) > 0.01) {
            $this->warn("   ⚠ ATTENTION: Le balance du wallet ne correspond pas au balance calculé!");
            $this->info("   Mise à jour du wallet balance...");
            $wallet->update(['balance' => $syncResult2['calculated_balance']]);
            $this->info("   ✓ Wallet balance mis à jour");
        } else {
            $this->info("   ✓ Wallet balance synchronisé");
        }
        
        $this->newLine();
        $this->info("=== SYNCHRONISATION TERMINÉE ===");
        $this->info("Le balance devrait maintenant être correct dans l'interface.");
        
        return 0;
    }
}

