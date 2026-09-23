<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\BusinessProfile;

class SetRealAdminFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:real-admin-fees {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure des frais admin réels pour une transaction';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🔧 Configuration des frais admin réels pour la transaction ID {$transactionId}...");
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
            
            // Récupérer ou créer le business profile
            $bp = $transaction->chargingPoint->businessProfile;
            
            if (!$bp) {
                $this->info("📝 Création d'un nouveau business profile...");
                $bp = BusinessProfile::create([
                    'name' => 'BP Transaction ' . $transactionId,
                    'description' => 'Business profile avec frais admin réels',
                    'is_active' => true,
                    'transaction_fee_amount' => 2.50,  // Frais de transaction
                    'charge_fee_amount' => 1.00,       // Frais de recharge
                    'terminal_fee_amount' => 0.50,     // Frais terminal
                    'base_fee_amount' => 0.25,         // Frais de base
                    'admin_fee_fixed' => 0.00,         // Pas de frais admin fixes spécifiques
                    'admin_fee_percentage' => 0.00,    // Pas de pourcentage admin
                    'integrator_fee_fixed' => 0.00,
                    'integrator_fee_percentage' => 0.00,
                    'operator_commission' => 0.00,
                    'integrator_commission' => 0.00,
                    'owner_commission' => 0.00,
                    'partner_id' => 1,
                ]);
                
                // Associer le business profile au point de charge
                $transaction->chargingPoint->update(['business_profile_id' => $bp->id]);
                
                $this->info("✅ Business profile créé avec ID: {$bp->id}");
            } else {
                $this->info("📝 Mise à jour du business profile existant (ID: {$bp->id})...");
                
                // Mettre à jour avec des frais réels
                $bp->update([
                    'transaction_fee_amount' => 2.50,  // Frais de transaction
                    'charge_fee_amount' => 1.00,       // Frais de recharge
                    'terminal_fee_amount' => 0.50,     // Frais terminal
                    'base_fee_amount' => 0.25,         // Frais de base
                    'admin_fee_fixed' => 0.00,         // Pas de frais admin fixes spécifiques
                    'admin_fee_percentage' => 0.00,    // Pas de pourcentage admin
                ]);
                
                $this->info("✅ Business profile mis à jour");
            }
            
            // Afficher les frais configurés
            $this->newLine();
            $this->info("💰 Frais admin configurés:");
            $this->info("  - Frais de transaction: " . number_format($bp->transaction_fee_amount, 2) . " €");
            $this->info("  - Frais de recharge: " . number_format($bp->charge_fee_amount, 2) . " €");
            $this->info("  - Frais terminal: " . number_format($bp->terminal_fee_amount, 2) . " €");
            $this->info("  - Frais de base: " . number_format($bp->base_fee_amount, 2) . " €");
            $this->info("  - Total frais admin: " . number_format($bp->transaction_fee_amount + $bp->charge_fee_amount + $bp->terminal_fee_amount + $bp->base_fee_amount, 2) . " €");
            
            // Tester le calcul
            $this->newLine();
            $this->info("🧮 Test du calcul...");
            
            $calculator = new \App\Services\TransactionCalculator();
            $result = $calculator->calculate($transaction);
            
            $this->info("📊 Résultats:");
            $this->table(
                ['Rôle', 'Montant', 'Pourcentage'],
                [
                    ['Admin', number_format($result['admin'], 2) . ' €', $this->getPercentage($result['admin'], $result['total'])],
                    ['Intégrateur', number_format($result['integrator'], 2) . ' €', $this->getPercentage($result['integrator'], $result['total'])],
                    ['Opérateur', number_format($result['operator'], 2) . ' €', $this->getPercentage($result['operator'], $result['total'])],
                    ['Total', number_format($result['total'], 2) . ' €', '100%'],
                ]
            );
            
            $this->newLine();
            $this->info("✅ Configuration terminée ! Les frais admin devraient maintenant s'afficher correctement.");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Calcule le pourcentage
     */
    protected function getPercentage(float $amount, float $total): string
    {
        if ($total == 0) {
            return '0%';
        }
        return number_format(($amount / $total) * 100, 1) . '%';
    }
}
