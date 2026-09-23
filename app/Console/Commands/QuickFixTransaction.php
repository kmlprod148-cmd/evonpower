<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Services\TransactionCalculator;

class QuickFixTransaction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quick:fix-transaction {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige rapidement une transaction en créant un business profile simple';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🚀 Correction rapide de la transaction ID {$transactionId}...");
        $this->newLine();

        try {
            // Récupérer la transaction
            $transaction = Transaction::with(['chargingPoint'])->find($transactionId);
            
            if (!$transaction) {
                $this->error("❌ Transaction ID {$transactionId} non trouvée");
                return 1;
            }
            
            $chargingPoint = $transaction->chargingPoint;
            if (!$chargingPoint) {
                $this->error("❌ Point de charge non trouvé");
                return 1;
            }
            
            $this->info("📋 Transaction trouvée:");
            $this->info("  - Montant: {$transaction->price_total} {$transaction->currency}");
            $this->info("  - Point de charge: {$chargingPoint->name} (ID: {$chargingPoint->id})");
            
            // Créer un business profile très simple
            $businessProfile = BusinessProfile::create([
                'name' => 'Test BP ' . $transactionId,
                'is_active' => true,
                'admin_fee_fixed' => 3.00,
                'admin_fee_percentage' => 5.00,
                'integrator_fee_fixed' => 1.00,
                'integrator_fee_percentage' => 8.00,
                'operator_commission' => 0.00,
                'integrator_commission' => 0.00,
                'owner_commission' => 0.00,
                'partner_id' => 1, // Utiliser un partner_id par défaut
                'integrator_id' => 1, // Utiliser un integrator_id par défaut
            ]);
            
            // Mettre à jour le point de charge
            $chargingPoint->update(['business_profile_id' => $businessProfile->id]);
            
            $this->info("✅ Business Profile créé avec ID: {$businessProfile->id}");
            $this->newLine();
            
            // Tester le calcul
            $this->info("🧮 Test du calcul...");
            $calculator = new TransactionCalculator();
            $result = $calculator->calculate($transaction);
            
            $this->table(
                ['Rôle', 'Montant', 'Pourcentage'],
                [
                    ['Admin', number_format($result['admin'], 2) . ' €', $this->getPercentage($result['admin'], $result['total'])],
                    ['Intégrateur', number_format($result['integrator'], 2) . ' €', $this->getPercentage($result['integrator'], $result['total'])],
                    ['Opérateur', number_format($result['operator'], 2) . ' €', $this->getPercentage($result['operator'], $result['total'])],
                    ['Total', number_format($result['total'], 2) . ' €', '100%'],
                ]
            );
            
            if ($result['admin'] > 0 || $result['integrator'] > 0 || $result['operator'] > 0) {
                $this->info("🎉 Succès ! La répartition fonctionne maintenant.");
                $this->info("Vous pouvez maintenant voir les détails de la transaction dans l'interface web.");
            } else {
                $this->error("❌ Le calcul retourne encore des zéros.");
            }
            
            return 0;
            
        } catch (Exception $e) {
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
