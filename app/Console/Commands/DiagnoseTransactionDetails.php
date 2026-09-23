<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;

class DiagnoseTransactionDetails extends Command
{
    protected $signature = 'transactions:diagnose {user_id?}';
    protected $description = 'Diagnose TransactionDetails and calculate totals';

    public function handle()
    {
        $userId = $this->argument('user_id') ?? $this->ask('User ID');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User not found!");
            return 1;
        }
        
        $this->info("=== DIAGNOSTIC TRANSACTION DETAILS ===");
        $this->info("User: {$user->name} (ID: {$user->id})");
        $this->info("Roles: " . $user->roles->pluck('name')->implode(', '));
        $this->newLine();
        
        // 1. Vérifier les transactions approuvées
        $this->info("1. Transactions approuvées:");
        $approvedTransactions = Transaction::where(function($statusQuery) {
                $statusQuery->whereIn('status', ['completed', 'confirmed'])
                    ->orWhereHas('reservation', function($resQuery) {
                        $resQuery->where('status', 'approved');
                    });
            })
            ->where('status', '!=', 'pending')
            ->get();
        
        $this->info("   Total: {$approvedTransactions->count()}");
        
        $withDetails = $approvedTransactions->filter(fn($t) => $t->transactionDetail)->count();
        $withoutDetails = $approvedTransactions->count() - $withDetails;
        
        $this->info("   Avec TransactionDetail: {$withDetails}");
        $this->info("   Sans TransactionDetail: {$withoutDetails}");
        $this->newLine();
        
        // 2. Vérifier les TransactionDetails
        $this->info("2. TransactionDetails:");
        $totalDetails = TransactionDetail::count();
        $this->info("   Total dans la base: {$totalDetails}");
        
        $adminDetails = TransactionDetail::where('admin_share_amount', '>', 0)->count();
        $integratorDetails = TransactionDetail::where('integrator_share_amount', '>', 0)->count();
        $operatorDetails = TransactionDetail::where('operator_share_amount', '>', 0)->count();
        
        $this->info("   Avec admin_share > 0: {$adminDetails}");
        $this->info("   Avec integrator_share > 0: {$integratorDetails}");
        $this->info("   Avec operator_share > 0: {$operatorDetails}");
        $this->newLine();
        
        // 3. Calculer les totaux selon le rôle
        if ($user->hasRole(['admin', 'super_admin'])) {
            $this->info("3. Calcul pour ADMIN:");
            $total = TransactionDetail::whereHas('transaction', function($q) {
                    $q->where(function($statusQuery) {
                        $statusQuery->whereIn('status', ['completed', 'confirmed'])
                            ->orWhereHas('reservation', function($resQuery) {
                                $resQuery->where('status', 'approved');
                            });
                    })
                    ->where('status', '!=', 'pending');
                })
                ->where('admin_share_amount', '>', 0)
                ->sum('admin_share_amount');
            
            $this->info("   Total Admin Share: " . number_format($total, 2) . " €");
            
        } elseif ($user->hasRole('integrator')) {
            $this->info("3. Calcul pour INTEGRATOR:");
            $total = TransactionDetail::whereHas('transaction', function($q) {
                    $q->where(function($statusQuery) {
                        $statusQuery->whereIn('status', ['completed', 'confirmed'])
                            ->orWhereHas('reservation', function($resQuery) {
                                $resQuery->where('status', 'approved');
                            });
                    })
                    ->where('status', '!=', 'pending');
                })
                ->where('integrator_creator_id', $user->id)
                ->where('integrator_share_amount', '>', 0)
                ->sum('integrator_share_amount');
            
            $this->info("   Total Integrator Share: " . number_format($total, 2) . " €");
            
        } elseif ($user->hasRole(['operator', 'partner'])) {
            $this->info("3. Calcul pour OPERATOR:");
            
            // Méthode 1 : Direct par operator_id
            $total1 = TransactionDetail::whereHas('transaction', function($q) {
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
                ->sum('operator_share_amount');
            
            $this->info("   Méthode 1 (operator_id direct): " . number_format($total1, 2) . " €");
            
            // Méthode 2 : Via chargingPoint
            $total2 = TransactionDetail::whereHas('transaction', function($q) {
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
            
            $this->info("   Méthode 2 (via chargingPoint): " . number_format($total2, 2) . " €");
            
            // Compter les TransactionDetails
            $count1 = TransactionDetail::where('operator_id', $user->id)
                ->where('operator_share_amount', '>', 0)
                ->count();
            
            $this->info("   TransactionDetails avec operator_id={$user->id}: {$count1}");
            
            // Lister tous les operator_id dans TransactionDetails
            $allOperatorIds = TransactionDetail::where('operator_share_amount', '>', 0)
                ->distinct()
                ->pluck('operator_id')
                ->filter()
                ->toArray();
            
            $this->info("   Tous les operator_id dans TransactionDetails: " . implode(', ', $allOperatorIds));
            
            // Vérifier les transactions avec chargingPoint
            $transactionsWithCp = \App\Models\Transaction::whereHas('chargingPoint', function($cpQuery) use ($user) {
                $cpQuery->where(function($cpSubQuery) use ($user) {
                    $cpSubQuery->where('user_id', $user->id)
                              ->orWhere('created_by_id', $user->id)
                              ->orWhere('created_by', $user->id);
                });
            })
            ->where(function($statusQuery) {
                $statusQuery->whereIn('status', ['completed', 'confirmed'])
                    ->orWhereHas('reservation', function($resQuery) {
                        $resQuery->where('status', 'approved');
                    });
            })
            ->where('status', '!=', 'pending')
            ->count();
            
            $this->info("   Transactions avec chargingPoint appartenant à l'utilisateur: {$transactionsWithCp}");
        }
        
        $this->newLine();
        
        // 4. Afficher quelques exemples
        $this->info("4. Exemples de TransactionDetails:");
        $examples = TransactionDetail::with('transaction')
            ->whereHas('transaction', function($q) {
                $q->where(function($statusQuery) {
                    $statusQuery->whereIn('status', ['completed', 'confirmed'])
                        ->orWhereHas('reservation', function($resQuery) {
                            $resQuery->where('status', 'approved');
                        });
                });
            })
            ->limit(5)
            ->get();
        
        foreach ($examples as $detail) {
            $this->info("   Transaction #{$detail->transaction_id}:");
            $this->info("      Admin: " . number_format($detail->admin_share_amount, 2) . " €");
            $this->info("      Integrator: " . number_format($detail->integrator_share_amount, 2) . " €");
            $this->info("      Operator: " . number_format($detail->operator_share_amount, 2) . " €");
            $this->info("      Transaction Status: " . ($detail->transaction->status ?? 'N/A'));
            $this->info("      Reservation Status: " . ($detail->transaction->reservation->status ?? 'N/A'));
            $this->newLine();
        }
        
        return 0;
    }
}

