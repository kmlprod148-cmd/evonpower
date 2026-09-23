<?php

namespace App\Services;

use App\Models\User;
use App\Models\TransactionHierarchy;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BalanceUpdateService
{
    /**
     * Mettre à jour automatiquement les soldes après une transaction hiérarchique
     */
    public function updateBalancesAfterTransaction(TransactionHierarchy $transaction): array
    {
        try {
            DB::beginTransaction();

            $payer = $transaction->payer;
            $payee = $transaction->payee;
            $amount = $transaction->net_amount;

            // Vérifier que le payeur a suffisamment de solde
            if (!$payer->hasSufficientBalance($amount)) {
                throw new \Exception("Solde insuffisant pour {$payer->name}. Solde actuel: {$payer->getFormattedBalance()}, Montant requis: {$amount} EUR");
            }

            // Mettre à jour les soldes
            $payer->subtractBalance($amount);
            $payee->addBalance($amount);

            // Enregistrer l'historique des mouvements
            $this->recordBalanceMovement($payer, $payee, $amount, $transaction);

            DB::commit();

            Log::info('Soldes mis à jour avec succès', [
                'transaction_id' => $transaction->id,
                'payer' => $payer->name,
                'payee' => $payee->name,
                'amount' => $amount,
                'payer_new_balance' => $payer->balance,
                'payee_new_balance' => $payee->balance,
            ]);

            return [
                'success' => true,
                'payer_balance' => $payer->balance,
                'payee_balance' => $payee->balance,
                'amount_transferred' => $amount,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la mise à jour des soldes', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enregistrer l'historique des mouvements de solde
     */
    private function recordBalanceMovement(User $payer, User $payee, float $amount, TransactionHierarchy $transaction): void
    {
        // Créer un enregistrement d'historique des mouvements
        DB::table('balance_movements')->insert([
            'payer_id' => $payer->id,
            'payee_id' => $payee->id,
            'transaction_hierarchy_id' => $transaction->id,
            'amount' => $amount,
            'payer_balance_before' => $payer->balance + $amount, // Solde avant la transaction
            'payer_balance_after' => $payer->balance,
            'payee_balance_before' => $payee->balance - $amount, // Solde avant la transaction
            'payee_balance_after' => $payee->balance,
            'movement_type' => $transaction->transaction_type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Obtenir l'historique des mouvements de solde pour un utilisateur
     */
    public function getUserBalanceHistory(User $user, int $limit = 50): array
    {
        $movements = DB::table('balance_movements')
            ->leftJoin('users as payer', 'balance_movements.payer_id', '=', 'payer.id')
            ->leftJoin('users as payee', 'balance_movements.payee_id', '=', 'payee.id')
            ->where(function ($query) use ($user) {
                $query->where('balance_movements.payer_id', $user->id)
                      ->orWhere('balance_movements.payee_id', $user->id);
            })
            ->select([
                'balance_movements.*',
                'payer.name as payer_name',
                'payee.name as payee_name',
            ])
            ->orderBy('balance_movements.created_at', 'desc')
            ->limit($limit)
            ->get();

        return $movements->map(function ($movement) use ($user) {
            $isOutgoing = $movement->payer_id === $user->id;
            
            return [
                'id' => $movement->id,
                'type' => $isOutgoing ? 'outgoing' : 'incoming',
                'amount' => $movement->amount,
                'counterparty' => $isOutgoing ? $movement->payee_name : $movement->payer_name,
                'balance_before' => $isOutgoing ? $movement->payer_balance_before : $movement->payee_balance_before,
                'balance_after' => $isOutgoing ? $movement->payer_balance_after : $movement->payee_balance_after,
                'movement_type' => $movement->movement_type,
                'created_at' => $movement->created_at,
            ];
        })->toArray();
    }

    /**
     * Obtenir les statistiques de solde pour un utilisateur
     */
    public function getUserBalanceStats(User $user): array
    {
        $stats = DB::table('balance_movements')
            ->where(function ($query) use ($user) {
                $query->where('payer_id', $user->id)
                      ->orWhere('payee_id', $user->id);
            })
            ->selectRaw('
                SUM(CASE WHEN payer_id = ? THEN amount ELSE 0 END) as total_outgoing,
                SUM(CASE WHEN payee_id = ? THEN amount ELSE 0 END) as total_incoming,
                COUNT(CASE WHEN payer_id = ? THEN 1 END) as outgoing_count,
                COUNT(CASE WHEN payee_id = ? THEN 1 END) as incoming_count
            ', [$user->id, $user->id, $user->id, $user->id])
            ->first();

        return [
            'current_balance' => $user->balance,
            'total_incoming' => $stats->total_incoming ?? 0,
            'total_outgoing' => $stats->total_outgoing ?? 0,
            'net_balance_change' => ($stats->total_incoming ?? 0) - ($stats->total_outgoing ?? 0),
            'incoming_count' => $stats->incoming_count ?? 0,
            'outgoing_count' => $stats->outgoing_count ?? 0,
            'total_transactions' => ($stats->incoming_count ?? 0) + ($stats->outgoing_count ?? 0),
        ];
    }

    /**
     * Synchroniser les soldes avec les transactions hiérarchiques
     */
    public function syncBalancesFromTransactions(): array
    {
        try {
            DB::beginTransaction();

            // Réinitialiser tous les soldes
            User::query()->update(['balance' => 0]);

            // Recalculer les soldes à partir des transactions hiérarchiques
            $transactions = TransactionHierarchy::where('status', 'completed')
                ->with(['payer', 'payee'])
                ->orderBy('created_at')
                ->get();

            $updatedUsers = [];

            foreach ($transactions as $transaction) {
                $payer = $transaction->payer;
                $payee = $transaction->payee;

                // Mettre à jour les soldes
                $payer->balance -= $transaction->net_amount;
                $payee->balance += $transaction->net_amount;

                $payer->save();
                $payee->save();

                $updatedUsers[$payer->id] = $payer;
                $updatedUsers[$payee->id] = $payee;
            }

            DB::commit();

            Log::info('Soldes synchronisés avec succès', [
                'transactions_processed' => $transactions->count(),
                'users_updated' => count($updatedUsers),
            ]);

            return [
                'success' => true,
                'transactions_processed' => $transactions->count(),
                'users_updated' => count($updatedUsers),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la synchronisation des soldes', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
