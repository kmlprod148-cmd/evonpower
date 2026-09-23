<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BusinessProfile;

class TestBusinessProfileEdit extends Command
{
    protected $signature = 'test:business-profile-edit {id=1}';
    protected $description = 'Teste l\'édition d\'un business profile';

    public function handle()
    {
        $id = $this->argument('id');
        
        try {
            $bp = BusinessProfile::find($id);
            
            if (!$bp) {
                $this->error("Business Profile ID {$id} non trouvé");
                return 1;
            }
            
            $this->info("Business Profile trouvé: {$bp->name}");
            $this->info("ID: {$bp->id}");
            
            // Tester l'accès aux champs JSON
            $this->info("\nTest des champs JSON:");
            $this->info("transaction_fee_config: " . json_encode($bp->transaction_fee_config));
            $this->info("charge_fee_config: " . json_encode($bp->charge_fee_config));
            $this->info("target_audience: " . json_encode($bp->target_audience));
            
            // Tester l'accès aux propriétés
            $this->info("\nTest des propriétés:");
            $this->info("transaction_fee_config->fixed_amount: " . ($bp->transaction_fee_config?->fixed_amount ?? 'NULL'));
            $this->info("transaction_fee_config->percentage: " . ($bp->transaction_fee_config?->percentage ?? 'NULL'));
            $this->info("charge_fee_config->fixed_amount: " . ($bp->charge_fee_config?->fixed_amount ?? 'NULL'));
            $this->info("charge_fee_config->percentage: " . ($bp->charge_fee_config?->percentage ?? 'NULL'));
            
            // Tester la mise à jour
            $this->info("\nTest de mise à jour...");
            $bp->update([
                'transaction_fee_config' => json_encode([
                    'types' => ['fixed'],
                    'fixed_amount' => 2.50,
                    'percentage' => 0.00
                ]),
                'charge_fee_config' => json_encode([
                    'fixed_amount' => 1.00,
                    'percentage' => 0.00
                ])
            ]);
            
            $this->info("✅ Mise à jour réussie");
            
            // Vérifier après mise à jour
            $bp->refresh();
            $this->info("\nAprès mise à jour:");
            $this->info("transaction_fee_config: " . json_encode($bp->transaction_fee_config));
            $this->info("charge_fee_config: " . json_encode($bp->charge_fee_config));
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Erreur: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
