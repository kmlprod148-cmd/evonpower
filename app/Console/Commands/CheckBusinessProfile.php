<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\BusinessProfile;

class CheckBusinessProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:business-profile {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie les données du business profile d\'une transaction';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🔍 Vérification du business profile pour la transaction ID {$transactionId}...");
        $this->newLine();

        try {
            // Récupérer la transaction
            $transaction = Transaction::with(['chargingPoint', 'chargingPoint.businessProfile'])->find($transactionId);
            
            if (!$transaction) {
                $this->error("❌ Transaction ID {$transactionId} non trouvée");
                return 1;
            }
            
            $this->info("📋 Transaction: {$transaction->price_total} {$transaction->currency}");
            $this->info("🔌 Point de charge: {$transaction->chargingPoint->name} (ID: {$transaction->chargingPoint->id})");
            
            $bp = $transaction->chargingPoint->businessProfile;
            
            if (!$bp) {
                $this->error("❌ Aucun business profile associé au point de charge");
                $this->info("💡 Utilisez: php artisan set:real-admin-fees {$transactionId}");
                return 1;
            }
            
            $this->info("📝 Business Profile: {$bp->name} (ID: {$bp->id})");
            $this->newLine();
            
            // Afficher tous les champs de frais
            $this->info("💰 Frais configurés dans le business profile:");
            $this->table(
                ['Champ', 'Valeur', 'Type'],
                [
                    ['transaction_fee_amount', $bp->transaction_fee_amount ?? 'NULL', gettype($bp->transaction_fee_amount)],
                    ['charge_fee_amount', $bp->charge_fee_amount ?? 'NULL', gettype($bp->charge_fee_amount)],
                    ['terminal_fee_amount', $bp->terminal_fee_amount ?? 'NULL', gettype($bp->terminal_fee_amount)],
                    ['base_fee_amount', $bp->base_fee_amount ?? 'NULL', gettype($bp->base_fee_amount)],
                    ['admin_fee_fixed', $bp->admin_fee_fixed ?? 'NULL', gettype($bp->admin_fee_fixed)],
                    ['admin_fee_percentage', $bp->admin_fee_percentage ?? 'NULL', gettype($bp->admin_fee_percentage)],
                    ['integrator_fee_fixed', $bp->integrator_fee_fixed ?? 'NULL', gettype($bp->integrator_fee_fixed)],
                    ['integrator_fee_percentage', $bp->integrator_fee_percentage ?? 'NULL', gettype($bp->integrator_fee_percentage)],
                ]
            );
            
            // Calculer le total des frais admin
            $totalAdminFees = ($bp->transaction_fee_amount ?? 0) + 
                             ($bp->charge_fee_amount ?? 0) + 
                             ($bp->terminal_fee_amount ?? 0) + 
                             ($bp->base_fee_amount ?? 0);
            
            $this->newLine();
            $this->info("🧮 Calcul des frais admin:");
            $this->info("  - Frais de transaction: " . number_format($bp->transaction_fee_amount ?? 0, 2) . " €");
            $this->info("  - Frais de recharge: " . number_format($bp->charge_fee_amount ?? 0, 2) . " €");
            $this->info("  - Frais terminal: " . number_format($bp->terminal_fee_amount ?? 0, 2) . " €");
            $this->info("  - Frais de base: " . number_format($bp->base_fee_amount ?? 0, 2) . " €");
            $this->info("  - Total frais admin: " . number_format($totalAdminFees, 2) . " €");
            
            if ($totalAdminFees == 0) {
                $this->newLine();
                $this->warn("⚠️  Tous les frais admin sont à 0 !");
                $this->info("💡 Utilisez: php artisan set:real-admin-fees {$transactionId}");
            } else {
                $this->newLine();
                $this->info("✅ Des frais admin sont configurés");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            return 1;
        }
    }
}
