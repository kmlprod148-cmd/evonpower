<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;

class FixTransactionBusinessProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:transaction-business-profile {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crée un business profile de test pour une transaction';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🔧 Création d'un business profile de test pour la transaction ID {$transactionId}...");
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
            
            // Vérifier si le point de charge a déjà un business profile
            if ($chargingPoint->business_profile_id) {
                $this->info("ℹ️ Le point de charge a déjà un business profile ID: {$chargingPoint->business_profile_id}");
                
                $businessProfile = BusinessProfile::find($chargingPoint->business_profile_id);
                if ($businessProfile) {
                    $this->info("📋 Business Profile existant:");
                    $this->info("  - Nom: {$businessProfile->name}");
                    $this->info("  - Actif: " . ($businessProfile->is_active ? 'Oui' : 'Non'));
                    $this->info("  - Admin Fee Fixed: " . ($businessProfile->admin_fee_fixed ?? 'null'));
                    $this->info("  - Admin Fee Percentage: " . ($businessProfile->admin_fee_percentage ?? 'null'));
                    
                    // Mettre à jour avec des valeurs de test si elles sont nulles
                    $updated = false;
                    if (!$businessProfile->admin_fee_fixed && !$businessProfile->admin_fee_percentage) {
                        $businessProfile->update([
                            'admin_fee_fixed' => 5.00,
                            'admin_fee_percentage' => 8.00,
                            'integrator_fee_fixed' => 2.00,
                            'integrator_fee_percentage' => 10.00,
                        ]);
                        $updated = true;
                    }
                    
                    if (!$businessProfile->transaction_fee_config) {
                        $businessProfile->update([
                            'transaction_fee_config' => json_encode(['fixed' => 2.00]),
                        ]);
                        $updated = true;
                    }
                    
                    if (!$businessProfile->charge_fee_config) {
                        $businessProfile->update([
                            'charge_fee_config' => json_encode(['fixed_amount' => 1.00]),
                        ]);
                        $updated = true;
                    }
                    
                    if ($updated) {
                        $this->info("✅ Business Profile mis à jour avec des valeurs de test");
                    } else {
                        $this->info("ℹ️ Business Profile déjà configuré");
                    }
                }
            } else {
                // Créer un nouveau business profile
                $businessProfile = BusinessProfile::create([
                    'name' => 'Business Profile Test - Transaction ' . $transactionId,
                    'description' => 'Business profile créé automatiquement pour tester les calculs de répartition',
                    'is_active' => true,
                    'transaction_fee_config' => json_encode(['fixed' => 2.00]),
                    'charge_fee_config' => json_encode(['fixed_amount' => 1.00]),
                    'terminal_fee_amount' => 0.50,
                    'base_fee_amount' => 0.00,
                    'admin_fee_fixed' => 5.00,
                    'admin_fee_percentage' => 8.00,
                    'integrator_fee_fixed' => 2.00,
                    'integrator_fee_percentage' => 10.00,
                ]);
                
                // Mettre à jour le point de charge
                $chargingPoint->update(['business_profile_id' => $businessProfile->id]);
                
                $this->info("✅ Business Profile créé avec ID: {$businessProfile->id}");
            }
            
            $this->newLine();
            $this->info("🎉 Configuration terminée !");
            $this->info("Vous pouvez maintenant tester la répartition de la transaction.");
            
            return 0;
            
        } catch (Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            return 1;
        }
    }
}
