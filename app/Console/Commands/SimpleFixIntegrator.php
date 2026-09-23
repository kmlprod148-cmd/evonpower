<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Models\BusinessProfile;
use App\Services\TransactionCalculator;

class SimpleFixIntegrator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simple:fix-integrator {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige simplement la part intégrateur en utilisant le business profile existant';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🔧 Correction simple de la part intégrateur pour la transaction ID {$transactionId}...");
        $this->newLine();

        try {
            // Récupérer la transaction
            $transaction = Transaction::with(['chargingPoint', 'chargingPoint.businessProfile'])->find($transactionId);
            
            if (!$transaction) {
                $this->error("❌ Transaction ID {$transactionId} non trouvée");
                return 1;
            }
            
            $chargingPoint = $transaction->chargingPoint;
            if (!$chargingPoint) {
                $this->error("❌ Point de charge non trouvé");
                return 1;
            }
            
            $this->info("📋 Transaction: {$transaction->price_total} {$transaction->currency}");
            $this->info("🔌 Point de charge: {$chargingPoint->name} (ID: {$chargingPoint->id})");
            
            // Vérifier le business profile
            $bp = $chargingPoint->businessProfile;
            if (!$bp) {
                $this->error("❌ Pas de business profile pour le point de charge");
                return 1;
            }
            
            $this->info("💼 Business Profile: {$bp->name} (ID: {$bp->id})");
            $this->info("  - Integrator Fee Percentage: " . ($bp->integrator_fee_percentage ?? 'null'));
            $this->info("  - Integrator Fee Fixed: " . ($bp->integrator_fee_fixed ?? 'null'));
            
            // Si le business profile n'a pas de frais intégrateur, les ajouter
            if (($bp->integrator_fee_percentage ?? 0) == 0 && ($bp->integrator_fee_fixed ?? 0) == 0) {
                $this->info("📝 Ajout de frais intégrateur au business profile...");
                
                $bp->update([
                    'integrator_fee_percentage' => 8.00,
                    'integrator_fee_fixed' => 1.50,
                ]);
                
                $this->info("✅ Frais intégrateur ajoutés: 8% ou 1.50€");
            } else {
                $this->info("ℹ️ Le business profile a déjà des frais intégrateur configurés");
            }
            
            // Si le point de charge n'a pas d'intégrateur, en créer un simple
            if (!$chargingPoint->integrator_id) {
                $this->info("📝 Création d'un intégrateur simple...");
                
                // Chercher un intégrateur existant ou en créer un
                $integrator = Integrator::first();
                if (!$integrator) {
                    $integrator = Integrator::create([
                        'name' => 'Intégrateur Test',
                        'email' => 'test@integrator.com',
                        'is_active' => true,
                    ]);
                    $this->info("✅ Intégrateur créé avec ID: {$integrator->id}");
                } else {
                    $this->info("✅ Intégrateur existant trouvé: {$integrator->name} (ID: {$integrator->id})");
                }
                
                // Associer l'intégrateur au point de charge
                $chargingPoint->update(['integrator_id' => $integrator->id]);
                $this->info("✅ Intégrateur associé au point de charge");
            } else {
                $this->info("ℹ️ Le point de charge a déjà un intégrateur ID: {$chargingPoint->integrator_id}");
            }
            
            $this->newLine();
            
            // Tester le calcul
            $this->info("🧮 Test du calcul avec la nouvelle configuration...");
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
            
            if ($result['integrator'] > 0) {
                $this->newLine();
                $this->info("🎉 SUCCÈS ! La part intégrateur est maintenant calculée: {$result['integrator']} €");
                $this->info("📱 Vous pouvez maintenant voir la répartition correcte dans l'interface web.");
            } else {
                $this->newLine();
                $this->error("❌ La part intégrateur est toujours à 0 €");
                $this->info("💡 Vérifiez la configuration du business profile.");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            $this->error("Stack trace:\n" . $e->getTraceAsString());
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
