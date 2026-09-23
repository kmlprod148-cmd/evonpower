<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WalletTransaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteWalletTransaction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:delete-transaction {id : ID de la transaction wallet à supprimer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprime une transaction wallet spécifique et recalcule le balance du wallet';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🔍 Recherche de la transaction wallet #{$transactionId}...");
        
        // Chercher directement dans la base de données sans scopes
        $transactionData = DB::table('wallet_transactions')->where('id', $transactionId)->first();
        
        if (!$transactionData) {
            $this->error("❌ Transaction wallet #{$transactionId} introuvable dans la base de données.");
            $this->warn("💡 Vérification si l'ID correspond à une Transaction (pas WalletTransaction)...");
            
            $regularTransaction = DB::table('transactions')->where('id', $transactionId)->first();
            if ($regularTransaction) {
                $this->error("   L'ID #{$transactionId} correspond à une Transaction (pas WalletTransaction).");
                $this->info("   Utilisez une autre méthode pour supprimer cette transaction.");
            }
            return 1;
        }
        
        // Charger le modèle pour pouvoir le supprimer
        $transactionModel = WalletTransaction::withoutGlobalScopes()->find($transactionId);
        
        if (!$transactionModel) {
            // Si le modèle ne peut pas être chargé, supprimer directement via DB
            $this->warn("⚠️  Impossible de charger le modèle, suppression directe via DB...");
            
            $walletId = $transactionData->wallet_id;
            $transactionType = $transactionData->type;
            $transactionAmount = $transactionData->amount;
            $balanceAfter = $transactionData->balance_after;
            
            // Demander confirmation
            $this->info("📋 Détails de la transaction:");
            $this->table(
                ['Champ', 'Valeur'],
                [
                    ['ID', $transactionData->id],
                    ['Wallet ID', $walletId],
                    ['Type', $transactionType],
                    ['Amount', number_format($transactionAmount, 2) . ' EUR'],
                    ['Balance Before', number_format($transactionData->balance_before, 2) . ' EUR'],
                    ['Balance After', number_format($balanceAfter, 2) . ' EUR'],
                    ['Status', $transactionData->status],
                    ['Created At', $transactionData->created_at],
                ]
            );
            
            if (!$this->confirm('⚠️  Êtes-vous sûr de vouloir supprimer cette transaction ?', true)) {
                $this->info('Opération annulée.');
                return 0;
            }
            
            try {
                DB::beginTransaction();
                
                // Supprimer directement via DB
                DB::table('wallet_transactions')->where('id', $transactionId)->delete();
                $this->info("✅ Transaction supprimée avec succès.");
                
                // Recalculer le balance du wallet
                $this->info("🔄 Recalcul du balance du wallet...");
                $totalCredits = DB::table('wallet_transactions')
                    ->where('wallet_id', $walletId)
                    ->where('type', 'credit')
                    ->sum('amount');
                
                $totalDebits = DB::table('wallet_transactions')
                    ->where('wallet_id', $walletId)
                    ->where('type', 'debit')
                    ->sum('amount');
                
                $calculatedBalance = $totalCredits - $totalDebits;
                
                // Mettre à jour le balance du wallet
                DB::table('wallets')->where('id', $walletId)->update(['balance' => $calculatedBalance]);
                
                $this->info("✅ Balance du wallet recalculé:");
                $this->line("   Ancien balance: " . number_format($balanceAfter, 2) . " EUR");
                $this->line("   Nouveau balance: " . number_format($calculatedBalance, 2) . " EUR");
                $this->line("   Différence: " . number_format($calculatedBalance - $balanceAfter, 2) . " EUR");
                
                DB::commit();
                
                Log::info('Transaction wallet supprimée (via DB direct)', [
                    'transaction_id' => $transactionId,
                    'wallet_id' => $walletId,
                    'transaction_type' => $transactionType,
                    'transaction_amount' => $transactionAmount,
                    'old_balance' => $balanceAfter,
                    'new_balance' => $calculatedBalance,
                ]);
                
                $this->newLine();
                $this->info('✅ Opération terminée avec succès !');
                
                return 0;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error('❌ Erreur lors de la suppression: ' . $e->getMessage());
                return 1;
            }
        }
        
        // Afficher les détails de la transaction
        $this->info("📋 Détails de la transaction:");
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['ID', $transactionModel->id],
                ['Wallet ID', $transactionModel->wallet_id],
                ['Type', $transactionModel->type],
                ['Amount', number_format($transactionModel->amount, 2) . ' EUR'],
                ['Balance Before', number_format($transactionModel->balance_before, 2) . ' EUR'],
                ['Balance After', number_format($transactionModel->balance_after, 2) . ' EUR'],
                ['Status', $transactionModel->status],
                ['Created At', $transactionModel->created_at->format('Y-m-d H:i:s')],
            ]
        );
        
        // Demander confirmation
        if (!$this->confirm('⚠️  Êtes-vous sûr de vouloir supprimer cette transaction ?', true)) {
            $this->info('Opération annulée.');
            return 0;
        }
        
        try {
            DB::beginTransaction();
            
            $wallet = $transactionModel->wallet;
            $walletId = $transactionModel->wallet_id;
            $transactionType = $transactionModel->type;
            $transactionAmount = $transactionModel->amount;
            $balanceAfter = $transactionModel->balance_after;
            
            // Supprimer la transaction
            $this->info("🗑️  Suppression de la transaction...");
            $transactionModel->delete();
            $this->info("✅ Transaction supprimée avec succès.");
            
            // Recalculer le balance du wallet
            $this->info("🔄 Recalcul du balance du wallet...");
            $wallet->refresh();
            
            // Recalculer le balance depuis toutes les transactions restantes
            $totalCredits = WalletTransaction::where('wallet_id', $walletId)
                ->where('type', 'credit')
                ->sum('amount');
            
            $totalDebits = WalletTransaction::where('wallet_id', $walletId)
                ->where('type', 'debit')
                ->sum('amount');
            
            $calculatedBalance = $totalCredits - $totalDebits;
            
            // Mettre à jour le balance du wallet
            $wallet->update(['balance' => $calculatedBalance]);
            $wallet->refresh();
            
            $this->info("✅ Balance du wallet recalculé:");
            $this->line("   Ancien balance: " . number_format($balanceAfter, 2) . " EUR");
            $this->line("   Nouveau balance: " . number_format($calculatedBalance, 2) . " EUR");
            $this->line("   Différence: " . number_format($calculatedBalance - $balanceAfter, 2) . " EUR");
            
            DB::commit();
            
            // Logger l'opération
            Log::info('Transaction wallet supprimée', [
                'transaction_id' => $transactionId,
                'wallet_id' => $walletId,
                'transaction_type' => $transactionType,
                'transaction_amount' => $transactionAmount,
                'old_balance' => $balanceAfter,
                'new_balance' => $calculatedBalance,
                'executed_by' => get_current_user(),
            ]);
            
            $this->newLine();
            $this->info('✅ Opération terminée avec succès !');
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error('❌ Erreur lors de la suppression:');
            $this->error("   {$e->getMessage()}");
            $this->error("   Fichier: {$e->getFile()}:{$e->getLine()}");
            
            Log::error('Erreur lors de la suppression de transaction wallet', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }
}

