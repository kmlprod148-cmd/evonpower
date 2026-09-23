<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Services\TransactionCalculator;

class FixIntegratorShare extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:integrator-share {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige la part intégrateur en associant un intégrateur au point de charge';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🔧 Correction de la part intégrateur pour la transaction ID {$transactionId}...");
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
            
            // Vérifier si le point de charge a déjà un intégrateur
            if ($chargingPoint->integrator_id) {
                $this->info("ℹ️ Le point de charge a déjà un intégrateur ID: {$chargingPoint->integrator_id}");
                
                // Vérifier si l'intégrateur existe et a un business profile
                $integrator = Integrator::with('businessProfile')->find($chargingPoint->integrator_id);
                if ($integrator) {
                    $this->info("✅ Intégrateur trouvé: {$integrator->name}");
                    
                    if ($integrator->businessProfile) {
                        $this->info("✅ Business profile de l'intégrateur: {$integrator->businessProfile->name}");
                        $this->info("  - Integrator Fee Percentage: " . ($integrator->businessProfile->integrator_fee_percentage ?? 'null'));
                        $this->info("  - Integrator Fee Fixed: " . ($integrator->businessProfile->integrator_fee_fixed ?? 'null'));
                    } else {
                        $this->warn("⚠️ L'intégrateur n'a pas de business profile");
                        
                        // Créer un business profile pour l'intégrateur
                        $integratorBp = $integrator->businessProfile()->create([
                            'name' => 'BP Intégrateur ' . $integrator->name,
                            'is_active' => true,
                            'integrator_fee_percentage' => 8.00,
                            'integrator_fee_fixed' => 1.50,
                            'admin_fee_fixed' => 0.00,
                            'admin_fee_percentage' => 0.00,
                            'operator_commission' => 0.00,
                            'integrator_commission' => 0.00,
                            'owner_commission' => 0.00,
                            'partner_id' => 1, // Utiliser un partner par défaut
                            'integrator_id' => $integrator->id,
                        ]);
                        
                        $this->info("✅ Business profile créé pour l'intégrateur avec ID: {$integratorBp->id}");
                    }
                } else {
                    $this->warn("⚠️ Intégrateur non trouvé, créons-en un nouveau");
                    
                    // Créer un nouvel intégrateur
                    $integrator = Integrator::create([
                        'name' => 'Intégrateur Test Transaction ' . $transactionId,
                        'email' => 'integrator' . $transactionId . '@test.com',
                        'is_active' => true,
                    ]);
                    
                    // Créer un business profile pour l'intégrateur
                    $integratorBp = $integrator->businessProfile()->create([
                        'name' => 'BP Intégrateur ' . $integrator->name,
                        'is_active' => true,
                        'integrator_fee_percentage' => 8.00,
                        'integrator_fee_fixed' => 1.50,
                        'admin_fee_fixed' => 0.00,
                        'admin_fee_percentage' => 0.00,
                        'operator_commission' => 0.00,
                        'integrator_commission' => 0.00,
                        'owner_commission' => 0.00,
                        'partner_id' => 1,
                        'integrator_id' => $integrator->id,
                    ]);
                    
                    // Associer l'intégrateur au point de charge
                    $chargingPoint->update(['integrator_id' => $integrator->id]);
                    
                    $this->info("✅ Intégrateur créé avec ID: {$integrator->id}");
                    $this->info("✅ Business profile créé avec ID: {$integratorBp->id}");
                    $this->info("✅ Point de charge mis à jour");
                }
            } else {
                $this->info("📝 Le point de charge n'a pas d'intégrateur, créons-en un...");
                
                // Créer un nouvel intégrateur
                $integrator = Integrator::create([
                    'name' => 'Intégrateur Test Transaction ' . $transactionId,
                    'email' => 'integrator' . $transactionId . '@test.com',
                    'is_active' => true,
                ]);
                
                // Créer un business profile pour l'intégrateur
                $integratorBp = $integrator->businessProfile()->create([
                    'name' => 'BP Intégrateur ' . $integrator->name,
                    'is_active' => true,
                    'integrator_fee_percentage' => 8.00,
                    'integrator_fee_fixed' => 1.50,
                    'admin_fee_fixed' => 0.00,
                    'admin_fee_percentage' => 0.00,
                    'operator_commission' => 0.00,
                    'integrator_commission' => 0.00,
                    'owner_commission' => 0.00,
                    'partner_id' => 1,
                    'integrator_id' => $integrator->id,
                ]);
                
                // Associer l'intégrateur au point de charge
                $chargingPoint->update(['integrator_id' => $integrator->id]);
                
                $this->info("✅ Intégrateur créé avec ID: {$integrator->id}");
                $this->info("✅ Business profile créé avec ID: {$integratorBp->id}");
                $this->info("✅ Point de charge mis à jour");
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
                $this->info("💡 Vérifiez la configuration du business profile de l'intégrateur.");
            }
            
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
