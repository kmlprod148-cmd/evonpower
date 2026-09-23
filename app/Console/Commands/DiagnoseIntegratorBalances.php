<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\TransactionDetail;
use App\Models\Transaction;
use App\Models\Reservation;
use App\Services\BalanceSynchronizationService;
use Illuminate\Support\Facades\DB;

class DiagnoseIntegratorBalances extends Command
{
    protected $signature = 'balances:diagnose-integrator {user_id?}';
    protected $description = 'Diagnostic approfondi des balances intégrateur - vérifie toutes les transactions approuvées';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        // Si aucun ID n'est fourni, lister tous les intégrateurs
        if (!$userId) {
            $this->info("=== LISTE DES INTÉGRATEURS ===");
            $integrators = User::whereHas('roles', function($q) {
                $q->where('name', 'integrator');
            })->get();
            
            if ($integrators->isEmpty()) {
                $this->warn("Aucun intégrateur trouvé dans le système.");
                return 1;
            }
            
            $this->table(
                ['ID', 'Nom', 'Email', 'TransactionDetails'],
                $integrators->map(function($user) {
                    $detailsCount = TransactionDetail::where('integrator_creator_id', $user->id)->count();
                    return [
                        $user->id,
                        $user->name,
                        $user->email,
                        $detailsCount
                    ];
                })->toArray()
            );
            
            $this->newLine();
            $this->info("Utilisez: php artisan balances:diagnose-integrator {id}");
            $this->info("Exemple: php artisan balances:diagnose-integrator " . $integrators->first()->id);
            return 0;
        }
        
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User not found! ID: {$userId}");
            $this->newLine();
            $this->info("Intégrateurs disponibles:");
            
            $integrators = User::whereHas('roles', function($q) {
                $q->where('name', 'integrator');
            })->get(['id', 'name', 'email']);
            
            if ($integrators->isEmpty()) {
                $this->warn("Aucun intégrateur trouvé dans le système.");
            } else {
                $this->table(
                    ['ID', 'Nom', 'Email'],
                    $integrators->map(function($u) {
                        return [$u->id, $u->name, $u->email];
                    })->toArray()
                );
            }
            return 1;
        }
        
        if (!$user->hasRole('integrator')) {
            $this->error("User is not an integrator! ID: {$userId}, Roles: " . $user->roles->pluck('name')->implode(', '));
            $this->newLine();
            $this->info("Intégrateurs disponibles:");
            
            $integrators = User::whereHas('roles', function($q) {
                $q->where('name', 'integrator');
            })->get(['id', 'name', 'email']);
            
            if ($integrators->isEmpty()) {
                $this->warn("Aucun intégrateur trouvé dans le système.");
            } else {
                $this->table(
                    ['ID', 'Nom', 'Email'],
                    $integrators->map(function($u) {
                        return [$u->id, $u->name, $u->email];
                    })->toArray()
                );
            }
            return 1;
        }
        
        $this->info("=== DIAGNOSTIC BALANCES INTÉGRATEUR ===");
        $this->info("User: {$user->name} (ID: {$user->id})");
        $this->newLine();
        
        // 1. Vérifier les réservations approuvées liées à cet intégrateur
        $this->info("1. RÉSERVATIONS APPROUVÉES:");
        $approvedReservations = Reservation::where('status', 'approved')->get();
        $this->info("   Total réservations approuvées: {$approvedReservations->count()}");
        
        $reservationsWithTransaction = $approvedReservations->filter(fn($r) => $r->transaction_id);
        $this->info("   Avec transaction_id: {$reservationsWithTransaction->count()}");
        $this->newLine();
        
        // 2. Vérifier les TransactionDetails directement en SQL
        $this->info("2. TRANSACTION DETAILS (Requête SQL directe):");
        
        // TransactionDetails avec transactions approuvées
        $sql1 = "SELECT COUNT(*) as count
                 FROM transaction_details td
                 INNER JOIN transactions t ON td.transaction_id = t.id
                 LEFT JOIN reservations r ON t.reservation_id = r.id
                 WHERE td.integrator_creator_id = ?
                 AND (
                     t.status IN ('completed', 'confirmed')
                     OR r.status = 'approved'
                 )
                 AND t.status != 'pending'";
        
        $result1 = DB::select($sql1, [$user->id])[0];
        $this->info("   TransactionDetails avec transactions approuvées: {$result1->count}");
        
        // TransactionDetails avec integrator_share_amount > 0
        $sql2 = "SELECT COUNT(*) as count, SUM(td.integrator_share_amount) as total_net
                 FROM transaction_details td
                 INNER JOIN transactions t ON td.transaction_id = t.id
                 LEFT JOIN reservations r ON t.reservation_id = r.id
                 WHERE td.integrator_creator_id = ?
                 AND td.integrator_share_amount > 0
                 AND (
                     t.status IN ('completed', 'confirmed')
                     OR r.status = 'approved'
                 )
                 AND t.status != 'pending'";
        
        $result2 = DB::select($sql2, [$user->id])[0];
        $this->info("   TransactionDetails avec integrator_share_amount > 0: {$result2->count}");
        $this->info("   Total integrator_share_amount (net): " . number_format($result2->total_net ?? 0, 2) . " €");
        
        // TransactionDetails avec admin_share_amount > 0
        $sql3 = "SELECT COUNT(*) as count, SUM(td.admin_share_amount) as total_admin
                 FROM transaction_details td
                 INNER JOIN transactions t ON td.transaction_id = t.id
                 LEFT JOIN reservations r ON t.reservation_id = r.id
                 WHERE td.integrator_creator_id = ?
                 AND td.admin_share_amount > 0
                 AND (
                     t.status IN ('completed', 'confirmed')
                     OR r.status = 'approved'
                 )
                 AND t.status != 'pending'";
        
        $result3 = DB::select($sql3, [$user->id])[0];
        $this->info("   TransactionDetails avec admin_share_amount > 0: {$result3->count}");
        $this->info("   Total admin_share_amount (déduit): " . number_format($result3->total_admin ?? 0, 2) . " €");
        $this->newLine();
        
        // 3. Vérifier les calculation_details
        $this->info("3. VÉRIFICATION DES CALCULATION_DETAILS:");
        
        $integratorDetails = TransactionDetail::whereHas('transaction', function($q) {
                $q->where(function($statusQuery) {
                    $statusQuery->whereIn('status', ['completed', 'confirmed'])
                        ->orWhereHas('reservation', function($resQuery) {
                            $resQuery->where('status', 'approved');
                        });
                })
                ->where('status', '!=', 'pending');
            })
            ->where('integrator_creator_id', $user->id)
            ->get();
        
        $this->info("   TransactionDetails trouvés: {$integratorDetails->count()}");
        
        $withCalculationDetails = 0;
        $withIntegratorFeeBrut = 0;
        $withAdminFees = 0;
        $totalIntegratorFeeBrut = 0;
        $totalAdminFees = 0;
        $missingCalculationDetails = [];
        
        foreach ($integratorDetails as $detail) {
            $calculationDetails = $detail->calculation_details;
            $hasCalculationDetails = false;
            $hasIntegratorFeeBrut = false;
            $hasAdminFees = false;
            $integratorFeeBrut = 0;
            $adminFees = 0;
            
            if ($calculationDetails) {
                $hasCalculationDetails = true;
                $withCalculationDetails++;
                
                if (is_string($calculationDetails)) {
                    $calculationDetails = json_decode($calculationDetails, true);
                }
                
                if (is_array($calculationDetails)) {
                    if (isset($calculationDetails['hierarchical_logic']['integrator_fees_amount'])) {
                        $hasIntegratorFeeBrut = true;
                        $integratorFeeBrut = (float) $calculationDetails['hierarchical_logic']['integrator_fees_amount'];
                        $totalIntegratorFeeBrut += $integratorFeeBrut;
                    }
                    
                    if (isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                        $hasAdminFees = true;
                        $adminFees = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                        $totalAdminFees += $adminFees;
                    }
                }
            }
            
            if ($hasCalculationDetails) {
                if ($hasIntegratorFeeBrut) {
                    $withIntegratorFeeBrut++;
                }
                if ($hasAdminFees) {
                    $withAdminFees++;
                }
            } else {
                $missingCalculationDetails[] = $detail->id;
            }
        }
        
        $this->info("   Avec calculation_details: {$withCalculationDetails}");
        $this->info("   Avec integrator_fees_amount: {$withIntegratorFeeBrut}");
        $this->info("   Avec admin_fees_amount: {$withAdminFees}");
        $this->info("   Total integrator_fees_amount (brut): " . number_format($totalIntegratorFeeBrut, 2) . " €");
        $this->info("   Total admin_fees_amount (déduit): " . number_format($totalAdminFees, 2) . " €");
        
        if (!empty($missingCalculationDetails)) {
            $this->warn("   ⚠ TransactionDetails sans calculation_details: " . count($missingCalculationDetails));
            $this->info("   IDs: " . implode(', ', array_slice($missingCalculationDetails, 0, 10)));
            if (count($missingCalculationDetails) > 10) {
                $this->info("   ... et " . (count($missingCalculationDetails) - 10) . " autres");
            }
        }
        $this->newLine();
        
        // 4. Calculer avec BalanceSynchronizationService
        $this->info("4. CALCUL AVEC BalanceSynchronizationService:");
        
        $balanceSyncService = app(BalanceSynchronizationService::class);
        $totals = $balanceSyncService->calculateTotalsFromApprovedTransactions($user);
        $balance = $balanceSyncService->calculateBalanceFromApprovedTransactions($user);
        
        $this->info("   Total Credits (Money in): " . number_format($totals['total_credits'], 2) . " €");
        $this->info("   Total Debits (Money out): " . number_format($totals['total_debits'], 2) . " €");
        $this->info("   Net Amount: " . number_format($totals['net_amount'], 2) . " €");
        $this->info("   Current Balance: " . number_format($balance, 2) . " €");
        $this->newLine();
        
        // 5. Vérification de cohérence
        $this->info("5. VÉRIFICATION DE COHÉRENCE:");
        
        $expectedCredits = $totalIntegratorFeeBrut;
        $expectedDebits = $totalAdminFees;
        
        if ($withIntegratorFeeBrut > 0) {
            // Si on a des calculation_details, utiliser ces valeurs
            $this->info("   Total intégrateur brut (depuis calculation_details): " . number_format($expectedCredits, 2) . " €");
            $this->info("   Total admin déduit (depuis calculation_details): " . number_format($expectedDebits, 2) . " €");
        } else {
            // Sinon, utiliser fallback
            $expectedCredits = (float) ($result2->total_net ?? 0) + (float) ($result3->total_admin ?? 0);
            $expectedDebits = (float) ($result3->total_admin ?? 0);
            $this->info("   Total intégrateur brut (fallback: net + admin): " . number_format($expectedCredits, 2) . " €");
            $this->info("   Total admin déduit (fallback: admin_share_amount): " . number_format($expectedDebits, 2) . " €");
        }
        
        $diffCredits = abs($totals['total_credits'] - $expectedCredits);
        $diffDebits = abs($totals['total_debits'] - $expectedDebits);
        
        if ($diffCredits < 0.01 && $diffDebits < 0.01) {
            $this->info("   ✓ Les calculs sont cohérents!");
        } else {
            $this->warn("   ⚠ Différences détectées:");
            if ($diffCredits >= 0.01) {
                $this->warn("     - Credits: différence de " . number_format($diffCredits, 2) . " €");
            }
            if ($diffDebits >= 0.01) {
                $this->warn("     - Debits: différence de " . number_format($diffDebits, 2) . " €");
            }
        }
        $this->newLine();
        
        // 6. Afficher quelques exemples
        $this->info("6. EXEMPLES DE TRANSACTION DETAILS:");
        $examples = $integratorDetails->take(5);
        
        if ($examples->count() > 0) {
            $tableData = [];
            foreach ($examples as $detail) {
                $calculationDetails = $detail->calculation_details;
                $integratorFeeBrut = 0;
                $adminFees = 0;
                
                if ($calculationDetails) {
                    if (is_string($calculationDetails)) {
                        $calculationDetails = json_decode($calculationDetails, true);
                    }
                    if (is_array($calculationDetails)) {
                        $integratorFeeBrut = (float) ($calculationDetails['hierarchical_logic']['integrator_fees_amount'] ?? 0);
                        $adminFees = (float) ($calculationDetails['hierarchical_logic']['admin_fees_amount'] ?? 0);
                    }
                }
                
                if ($integratorFeeBrut <= 0.01) {
                    $integratorFeeBrut = (float) ($detail->integrator_share_amount ?? 0) + (float) ($detail->admin_share_amount ?? 0);
                }
                
                if ($adminFees <= 0.01) {
                    $adminFees = (float) ($detail->admin_share_amount ?? 0);
                }
                
                $tableData[] = [
                    'TD ID' => $detail->id,
                    'T ID' => $detail->transaction_id,
                    'Integrator Brut' => number_format($integratorFeeBrut, 2) . ' €',
                    'Admin Déduit' => number_format($adminFees, 2) . ' €',
                    'Integrator Net' => number_format($detail->integrator_share_amount ?? 0, 2) . ' €',
                    'Has Calc Details' => $calculationDetails ? 'Oui' : 'Non'
                ];
            }
            
            $this->table(
                ['TD ID', 'T ID', 'Integrator Brut', 'Admin Déduit', 'Integrator Net', 'Has Calc Details'],
                $tableData
            );
        }
        
        $this->newLine();
        $this->info("=== DIAGNOSTIC TERMINÉ ===");
        
        return 0;
    }
}

