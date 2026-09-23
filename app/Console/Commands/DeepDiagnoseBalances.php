<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class DeepDiagnoseBalances extends Command
{
    protected $signature = 'balances:deep-diagnose {user_id}';
    protected $description = 'Diagnostic approfondi des balances - vérifie directement la base de données';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User not found!");
            return 1;
        }
        
        $this->info("=== DIAGNOSTIC APPROFONDI DES BALANCES ===");
        $this->info("User: {$user->name} (ID: {$user->id})");
        $this->info("Roles: " . $user->roles->pluck('name')->implode(', '));
        $this->newLine();
        
        // 1. Vérifier les réservations approuvées
        $this->info("1. RÉSERVATIONS APPROUVÉES:");
        $approvedReservations = Reservation::where('status', 'approved')->get();
        $this->info("   Total réservations approuvées: {$approvedReservations->count()}");
        
        // Réservations liées à cet opérateur
        $userReservations = $approvedReservations->filter(function($r) use ($user) {
            if (!$r->charging_point_id) return false;
            $cp = $r->chargingPoint;
            if (!$cp) return false;
            return ($cp->user_id == $user->id) 
                || ($cp->created_by_id == $user->id)
                || ($cp->created_by == $user->id)
                || ($cp->group && $cp->group->user_id == $user->id);
        });
        
        $this->info("   Réservations liées à l'utilisateur: {$userReservations->count()}");
        
        if ($userReservations->count() > 0) {
            $withTransaction = $userReservations->filter(fn($r) => $r->transaction_id)->count();
            $withoutTransaction = $userReservations->count() - $withTransaction;
            $this->info("   - Avec transaction_id: {$withTransaction}");
            $this->info("   - Sans transaction_id: {$withoutTransaction}");
            
            if ($withoutTransaction > 0) {
                $this->warn("   ⚠ ATTENTION: {$withoutTransaction} réservations approuvées sans transaction!");
                $this->info("   IDs: " . $userReservations->filter(fn($r) => !$r->transaction_id)->pluck('id')->implode(', '));
            }
        }
        $this->newLine();
        
        // 2. Vérifier les transactions directement en SQL
        $this->info("2. TRANSACTIONS (Requête SQL directe):");
        
        // Transactions avec statut completed/confirmed
        $sql1 = "SELECT COUNT(*) as count, SUM(amount) as total 
                 FROM transactions 
                 WHERE status IN ('completed', 'confirmed') 
                 AND status != 'pending'";
        $result1 = DB::select($sql1)[0];
        $this->info("   Transactions completed/confirmed: {$result1->count}, Total: " . number_format($result1->total ?? 0, 2) . " €");
        
        // Transactions avec réservation approuvée
        $sql2 = "SELECT COUNT(*) as count 
                 FROM transactions t
                 INNER JOIN reservations r ON t.reservation_id = r.id
                 WHERE r.status = 'approved'
                 AND t.status != 'pending'";
        $result2 = DB::select($sql2)[0];
        $this->info("   Transactions avec réservation approuvée: {$result2->count}");
        
        // Transactions liées à cet opérateur
        $sql3 = "SELECT COUNT(*) as count, SUM(t.amount) as total
                 FROM transactions t
                 LEFT JOIN charging_points cp ON t.charging_point_id = cp.id
                 LEFT JOIN groups g ON cp.group_id = g.id
                 WHERE (t.status IN ('completed', 'confirmed') 
                    OR EXISTS (SELECT 1 FROM reservations r WHERE r.id = t.reservation_id AND r.status = 'approved'))
                 AND t.status != 'pending'
                 AND (t.operator_id = ? 
                    OR cp.user_id = ? 
                    OR cp.created_by_id = ? 
                    OR cp.created_by = ?
                    OR g.user_id = ?)";
        
        $result3 = DB::select($sql3, [$user->id, $user->id, $user->id, $user->id, $user->id])[0];
        $this->info("   Transactions liées à l'utilisateur: {$result3->count}, Total: " . number_format($result3->total ?? 0, 2) . " €");
        $this->newLine();
        
        // 3. Vérifier les TransactionDetails directement en SQL
        $this->info("3. TRANSACTION DETAILS (Requête SQL directe):");
        
        $sql4 = "SELECT COUNT(*) as count, SUM(operator_share_amount) as total
                 FROM transaction_details td
                 INNER JOIN transactions t ON td.transaction_id = t.id
                 WHERE td.operator_id = ?
                 AND td.operator_share_amount > 0
                 AND (t.status IN ('completed', 'confirmed') 
                    OR EXISTS (SELECT 1 FROM reservations r WHERE r.id = t.reservation_id AND r.status = 'approved'))
                 AND t.status != 'pending'";
        
        $result4 = DB::select($sql4, [$user->id])[0];
        $this->info("   TransactionDetails avec operator_id={$user->id}: {$result4->count}");
        $this->info("   Total operator_share_amount: " . number_format($result4->total ?? 0, 2) . " €");
        
        // TransactionDetails via chargingPoint
        $sql5 = "SELECT COUNT(*) as count, SUM(td.operator_share_amount) as total
                 FROM transaction_details td
                 INNER JOIN transactions t ON td.transaction_id = t.id
                 LEFT JOIN charging_points cp ON t.charging_point_id = cp.id
                 LEFT JOIN groups g ON cp.group_id = g.id
                 WHERE (cp.user_id = ? OR cp.created_by_id = ? OR cp.created_by = ? OR g.user_id = ?)
                 AND td.operator_share_amount > 0
                 AND (t.status IN ('completed', 'confirmed') 
                    OR EXISTS (SELECT 1 FROM reservations r WHERE r.id = t.reservation_id AND r.status = 'approved'))
                 AND t.status != 'pending'";
        
        $result5 = DB::select($sql5, [$user->id, $user->id, $user->id, $user->id])[0];
        $this->info("   TransactionDetails via chargingPoint: {$result5->count}");
        $this->info("   Total operator_share_amount: " . number_format($result5->total ?? 0, 2) . " €");
        
        // Tous les TransactionDetails avec operator_share > 0
        $sql6 = "SELECT COUNT(*) as count, 
                        GROUP_CONCAT(DISTINCT operator_id) as operator_ids
                 FROM transaction_details td
                 INNER JOIN transactions t ON td.transaction_id = t.id
                 WHERE td.operator_share_amount > 0
                 AND (t.status IN ('completed', 'confirmed') 
                    OR EXISTS (SELECT 1 FROM reservations r WHERE r.id = t.reservation_id AND r.status = 'approved'))
                 AND t.status != 'pending'";
        
        $result6 = DB::select($sql6)[0];
        $this->info("   Total TransactionDetails avec operator_share > 0: {$result6->count}");
        $this->info("   Operator IDs trouvés: " . ($result6->operator_ids ?? 'Aucun'));
        $this->newLine();
        
        // 4. Vérifier les WalletTransactions
        $this->info("4. WALLET TRANSACTIONS:");
        $wallet = $user->getOrCreateWallet();
        
        $walletCredits = DB::table('wallet_transactions')
            ->where('wallet_id', $wallet->id)
            ->where('type', 'credit')
            ->sum('amount');
        
        $walletDebits = DB::table('wallet_transactions')
            ->where('wallet_id', $wallet->id)
            ->where('type', 'debit')
            ->sum('amount');
        
        $this->info("   Wallet ID: {$wallet->id}");
        $this->info("   Wallet Balance (stocké): " . number_format($wallet->balance, 2) . " €");
        $this->info("   Total Credits (wallet_transactions): " . number_format($walletCredits, 2) . " €");
        $this->info("   Total Debits (wallet_transactions): " . number_format($walletDebits, 2) . " €");
        $this->info("   Calculé (Credits - Debits): " . number_format($walletCredits - $walletDebits, 2) . " €");
        $this->newLine();
        
        // 5. Afficher quelques exemples de TransactionDetails
        $this->info("5. EXEMPLES DE TRANSACTION DETAILS:");
        $examples = DB::select("
            SELECT td.id, td.transaction_id, td.operator_id, td.operator_share_amount,
                   t.status as transaction_status, t.amount as transaction_amount,
                   r.status as reservation_status, r.id as reservation_id,
                   cp.user_id as cp_user_id, cp.created_by_id as cp_created_by_id
            FROM transaction_details td
            INNER JOIN transactions t ON td.transaction_id = t.id
            LEFT JOIN reservations r ON t.reservation_id = r.id
            LEFT JOIN charging_points cp ON t.charging_point_id = cp.id
            WHERE td.operator_share_amount > 0
            LIMIT 10
        ");
        
        if (count($examples) > 0) {
            $this->table(
                ['TD ID', 'T ID', 'Operator ID', 'Share', 'T Status', 'R Status', 'CP User', 'CP Created By'],
                array_map(function($ex) {
                    return [
                        $ex->id,
                        $ex->transaction_id,
                        $ex->operator_id ?? 'NULL',
                        number_format($ex->operator_share_amount, 2) . ' €',
                        $ex->transaction_status ?? 'N/A',
                        $ex->reservation_status ?? 'N/A',
                        $ex->cp_user_id ?? 'NULL',
                        $ex->cp_created_by_id ?? 'NULL'
                    ];
                }, $examples)
            );
        } else {
            $this->warn("   Aucun TransactionDetail avec operator_share > 0 trouvé!");
        }
        
        $this->newLine();
        $this->info("=== DIAGNOSTIC TERMINÉ ===");
        
        return 0;
    }
}

