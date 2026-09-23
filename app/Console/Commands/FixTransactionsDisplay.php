<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Transaction;
use App\Models\WalletTransaction;
use App\Models\TransactionDetail;
use App\Services\BalanceSynchronizationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FixTransactionsDisplay extends Command
{
    protected $signature = 'transactions:fix-display {user_id?}';
    protected $description = 'Diagnostiquer et corriger l\'affichage des transactions';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $users = User::where('id', $userId)->get();
        } else {
            // Récupérer tous les utilisateurs avec des rôles pertinents
            $users = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'super_admin', 'integrator', 'operator', 'partner']);
            })->get();
        }
        
        foreach ($users as $user) {
            $this->info("=== Traitement de l'utilisateur #{$user->id} ({$user->name}) ===");
            
            $wallet = $user->getOrCreateWallet();
            $this->info("Wallet ID: {$wallet->id}, Balance: {$wallet->balance}");
            
            // 1. Vérifier les TransactionDetails
            $transactionDetailsCount = TransactionDetail::whereHas('transaction', function($q) {
                $q->where(function($statusQuery) {
                    $statusQuery->whereIn('status', ['completed', 'confirmed'])
                        ->orWhereHas('reservation', function($resQuery) {
                            $resQuery->whereIn('status', ['confirmed', 'completed']);
                        });
                })
                ->where('status', '!=', 'pending');
            })->count();
            
            $this->info("TransactionDetails approuvées: {$transactionDetailsCount}");
            
            // 2. Vérifier les WalletTransactions
            $walletTransactionsCount = $wallet->transactions()->count();
            $this->info("WalletTransactions existantes: {$walletTransactionsCount}");
            
            // 3. Vérifier les Transactions
            $transactionsCount = Transaction::where(function($statusQuery) {
                $statusQuery->whereIn('status', ['completed', 'confirmed'])
                    ->orWhereHas('reservation', function($resQuery) {
                        $resQuery->where('status', 'approved');
                    });
            })
            ->where('status', '!=', 'pending')
            ->count();
            
            $this->info("Transactions approuvées: {$transactionsCount}");
            
            // 4. Synchroniser le balance
            $this->info("Synchronisation du balance...");
            try {
                $balanceSyncService = app(BalanceSynchronizationService::class);
                $syncResult = $balanceSyncService->synchronizeUserBalance($user);
                
                $this->info("Synchronisation terminée:");
                $this->info("  - Synchronisé: " . ($syncResult['synchronized'] ? 'Oui' : 'Non'));
                $this->info("  - Balance calculée: " . $syncResult['calculated_balance']);
                $this->info("  - Crédits attendus: " . $syncResult['total_credits']);
                $this->info("  - Transactions créées: " . count($syncResult['created_transactions'] ?? []));
                
                $wallet->refresh();
                $this->info("  - Balance wallet après sync: " . $wallet->balance);
                
                // 5. Vérifier les WalletTransactions après synchronisation
                $walletTransactionsAfter = $wallet->transactions()->count();
                $this->info("WalletTransactions après synchronisation: {$walletTransactionsAfter}");
                
            } catch (\Exception $e) {
                $this->error("Erreur lors de la synchronisation: " . $e->getMessage());
            }
            
            $this->newLine();
        }
        
        $this->info("Diagnostic terminé!");
        return 0;
    }
}

