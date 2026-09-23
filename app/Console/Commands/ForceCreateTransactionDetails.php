<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Reservation;
use App\Services\ReservationTransactionService;
use Illuminate\Support\Facades\DB;

class ForceCreateTransactionDetails extends Command
{
    protected $signature = 'transactions:force-create-details 
                            {--operator-id= : Pour un opérateur spécifique uniquement}
                            {--all : Pour toutes les transactions}';
    
    protected $description = 'Force la création de TransactionDetails pour toutes les transactions approuvées';

    public function handle()
    {
        $this->info("=== FORCE CRÉATION DES TRANSACTION DETAILS ===");
        $this->newLine();
        
        $operatorId = $this->option('operator-id');
        $all = $this->option('all');
        
        // 1. Trouver toutes les transactions approuvées sans TransactionDetail
        $this->info("1. Recherche des transactions approuvées sans TransactionDetail...");
        
        $query = Transaction::where(function($statusQuery) {
                $statusQuery->whereIn('status', ['completed', 'confirmed'])
                    ->orWhereHas('reservation', function($resQuery) {
                        $resQuery->where('status', 'approved');
                    });
            })
            ->where('status', '!=', 'pending')
            ->whereDoesntHave('transactionDetail')
            ->with(['reservation', 'chargingPoint']);
        
        if ($operatorId && !$all) {
            $query->where(function($q) use ($operatorId) {
                $q->where('operator_id', $operatorId)
                  ->orWhereHas('chargingPoint', function($cpQuery) use ($operatorId) {
                      $cpQuery->where('user_id', $operatorId)
                             ->orWhere('created_by_id', $operatorId)
                             ->orWhere('created_by', $operatorId);
                  });
            });
        }
        
        $transactions = $query->get();
        
        $this->info("   Trouvé: {$transactions->count()} transactions sans TransactionDetail");
        
        if ($transactions->count() == 0) {
            $this->info("   ✓ Toutes les transactions ont déjà un TransactionDetail");
            return 0;
        }
        
        $this->newLine();
        $this->info("2. Création des TransactionDetails...");
        
        $transactionService = app(ReservationTransactionService::class);
        $created = 0;
        $failed = 0;
        $errors = [];
        
        $bar = $this->output->createProgressBar($transactions->count());
        $bar->start();
        
        foreach ($transactions as $transaction) {
            try {
                // Essayer avec la méthode normale
                $result = $transactionService->createRetroactiveTransactionDetail($transaction);
                
                if ($result['success']) {
                    $created++;
                } else {
                    // Si échec, créer un TransactionDetail minimal avec les données existantes
                    $failed++;
                    $error = $this->createMinimalTransactionDetail($transaction);
                    if ($error) {
                        $errors[] = "Transaction #{$transaction->id}: {$error}";
                    } else {
                        $created++;
                        $failed--;
                    }
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Transaction #{$transaction->id}: " . $e->getMessage();
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("   Créés: {$created}, Échoués: {$failed}");
        
        if (!empty($errors)) {
            $this->newLine();
            $this->warn("   Erreurs rencontrées:");
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->warn("     - {$error}");
            }
            if (count($errors) > 10) {
                $this->warn("     ... et " . (count($errors) - 10) . " autres erreurs");
            }
        }
        
        $this->newLine();
        $this->info("=== TERMINÉ ===");
        
        return 0;
    }
    
    protected function createMinimalTransactionDetail(Transaction $transaction): ?string
    {
        try {
            // Calculer les parts depuis les données existantes de la transaction
            $totalAmount = (float) ($transaction->amount ?? $transaction->price_total ?? 0);
            
            if ($totalAmount <= 0) {
                return "Montant total invalide";
            }
            
            // Utiliser les parts existantes si disponibles
            $adminShare = (float) ($transaction->admin_share_amount ?? 0);
            $integratorShare = (float) ($transaction->integrator_share_amount ?? 0);
            $operatorShare = (float) ($transaction->operator_share_amount ?? 0);
            
            // Si aucune part n'est définie, calculer basique (tout à l'opérateur)
            if ($adminShare == 0 && $integratorShare == 0 && $operatorShare == 0) {
                $operatorShare = $totalAmount;
            }
            
            // Vérifier que la somme correspond
            $sum = $adminShare + $integratorShare + $operatorShare;
            if (abs($sum - $totalAmount) > 0.01) {
                // Ajuster operator_share pour correspondre au total
                $operatorShare = $totalAmount - $adminShare - $integratorShare;
            }
            
            // Déterminer l'opérateur depuis chargingPoint ou transaction
            $operatorId = $transaction->operator_id;
            if (!$operatorId && $transaction->chargingPoint) {
                $cp = $transaction->chargingPoint;
                $operatorId = $cp->user_id ?? $cp->created_by_id ?? $cp->created_by;
            }
            
            if (!$operatorId) {
                return "Impossible de déterminer l'opérateur";
            }
            
            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'transaction_fee_percentage' => 0.0,
                'transaction_fee_fixed' => 0.0,
                'transaction_fee_total' => $adminShare + $integratorShare,
                'admin_share_amount' => $adminShare,
                'integrator_share_amount' => $integratorShare,
                'operator_share_amount' => max(0, $operatorShare),
                'admin_share_percentage' => $totalAmount > 0 ? round(($adminShare / $totalAmount) * 100, 2) : 0,
                'integrator_share_percentage' => $totalAmount > 0 ? round(($integratorShare / $totalAmount) * 100, 2) : 0,
                'admin_creator_id' => $transaction->admin_id,
                'integrator_creator_id' => $transaction->integrator_id,
                'operator_id' => $operatorId,
                'admin_paid' => false,
                'integrator_paid' => false,
                'operator_paid' => false,
                'calculation_details' => [
                    'created_retroactively' => true,
                    'source' => 'minimal_fallback',
                    'note' => 'TransactionDetail créé avec données minimales - nécessite vérification'
                ]
            ]);
            
            return null; // Succès
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}

