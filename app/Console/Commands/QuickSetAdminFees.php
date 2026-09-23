<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Services\TransactionCalculator;

class QuickSetAdminFees extends Command
{
    protected $signature = 'quick:set-admin-fees {id=1}';
    protected $description = 'Configure rapidement des frais admin réels';

    public function handle()
    {
        $transactionId = $this->argument('id');
        
        try {
            $transaction = Transaction::with(['chargingPoint', 'chargingPoint.businessProfile'])->find($transactionId);
            
            if (!$transaction) {
                $this->error("Transaction ID {$transactionId} non trouvée");
                return 1;
            }
            
            $bp = $transaction->chargingPoint->businessProfile;
            
            if (!$bp) {
                $bp = BusinessProfile::create([
                    'name' => 'BP Transaction ' . $transactionId,
                    'is_active' => true,
                    'transaction_fee_amount' => 2.50,
                    'charge_fee_amount' => 1.00,
                    'terminal_fee_amount' => 0.50,
                    'base_fee_amount' => 0.25,
                    'admin_fee_fixed' => 0.00,
                    'admin_fee_percentage' => 0.00,
                    'integrator_fee_fixed' => 0.00,
                    'integrator_fee_percentage' => 0.00,
                    'operator_commission' => 0.00,
                    'integrator_commission' => 0.00,
                    'owner_commission' => 0.00,
                    'partner_id' => 1,
                ]);
                
                $transaction->chargingPoint->update(['business_profile_id' => $bp->id]);
                $this->info("Business profile créé avec ID: {$bp->id}");
            } else {
                $bp->update([
                    'transaction_fee_amount' => 2.50,
                    'charge_fee_amount' => 1.00,
                    'terminal_fee_amount' => 0.50,
                    'base_fee_amount' => 0.25,
                    'admin_fee_fixed' => 0.00,
                    'admin_fee_percentage' => 0.00,
                ]);
                $this->info("Business profile mis à jour");
            }
            
            $calculator = new TransactionCalculator();
            $result = $calculator->calculate($transaction);
            
            $this->info("Frais admin configurés:");
            $this->info("  - Frais de transaction: 2.50 €");
            $this->info("  - Frais de recharge: 1.00 €");
            $this->info("  - Frais terminal: 0.50 €");
            $this->info("  - Frais de base: 0.25 €");
            $this->info("  - Total frais admin: 4.25 €");
            
            $this->info("Résultats du calcul:");
            $this->info("  - Admin: " . number_format($result['admin'], 2) . " €");
            $this->info("  - Intégrateur: " . number_format($result['integrator'], 2) . " €");
            $this->info("  - Opérateur: " . number_format($result['operator'], 2) . " €");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Erreur: " . $e->getMessage());
            return 1;
        }
    }
}
