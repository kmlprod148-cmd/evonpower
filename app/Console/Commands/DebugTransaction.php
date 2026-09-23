<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Services\TransactionCalculator;

class DebugTransaction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:transaction {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Débogue une transaction pour voir les données disponibles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🔍 Débogage de la transaction ID {$transactionId}...");
        $this->newLine();

        try {
            // Récupérer la transaction
            $transaction = Transaction::with(['chargingPoint', 'chargingPoint.businessProfile'])->find($transactionId);
            
            if (!$transaction) {
                $this->error("❌ Transaction ID {$transactionId} non trouvée");
                return 1;
            }
            
            $this->info("📋 Informations de la transaction:");
            $this->info("  - ID: {$transaction->id}");
            $this->info("  - Montant: {$transaction->price_total} {$transaction->currency}");
            $this->info("  - Status: {$transaction->status}");
            $this->info("  - Charging Point ID: {$transaction->charging_point_id}");
            $this->newLine();
            
            // Vérifier le point de charge
            $chargingPoint = $transaction->chargingPoint;
            if (!$chargingPoint) {
                $this->error("❌ Point de charge non trouvé");
                return 1;
            }
            
            $this->info("🔌 Informations du point de charge:");
            $this->info("  - ID: {$chargingPoint->id}");
            $this->info("  - Nom: {$chargingPoint->name}");
            $this->info("  - Business Profile ID: {$chargingPoint->business_profile_id}");
            $this->info("  - Integrator ID: {$chargingPoint->integrator_id}");
            $this->newLine();
            
            // Vérifier le business profile
            $businessProfile = $chargingPoint->businessProfile;
            if (!$businessProfile) {
                $this->warn("⚠️ Business Profile non trouvé");
                $this->info("Créons un business profile de test...");
                
                $businessProfile = BusinessProfile::create([
                    'name' => 'Business Profile Test',
                    'description' => 'Business profile pour tester les calculs',
                    'is_active' => true,
                    'transaction_fee_config' => json_encode(['fixed' => 2.00]),
                    'charge_fee_config' => json_encode(['fixed_amount' => 1.00]),
                    'terminal_fee_amount' => 0.50,
                    'base_fee_amount' => 0.00,
                    'admin_fee_fixed' => 4.00,
                    'admin_fee_percentage' => 10.0,
                    'integrator_fee_fixed' => 2.00,
                    'integrator_fee_percentage' => 15.0,
                ]);
                
                // Mettre à jour le point de charge
                $chargingPoint->update(['business_profile_id' => $businessProfile->id]);
                
                $this->info("✅ Business Profile créé avec ID: {$businessProfile->id}");
            }
            
            $this->info("💼 Informations du Business Profile:");
            $this->info("  - ID: {$businessProfile->id}");
            $this->info("  - Nom: {$businessProfile->name}");
            $this->info("  - Actif: " . ($businessProfile->is_active ? 'Oui' : 'Non'));
            $this->info("  - Transaction Fee Config: " . ($businessProfile->transaction_fee_config ?? 'null'));
            $this->info("  - Charge Fee Config: " . ($businessProfile->charge_fee_config ?? 'null'));
            $this->info("  - Terminal Fee Amount: " . ($businessProfile->terminal_fee_amount ?? 'null'));
            $this->info("  - Base Fee Amount: " . ($businessProfile->base_fee_amount ?? 'null'));
            $this->info("  - Admin Fee Fixed: " . ($businessProfile->admin_fee_fixed ?? 'null'));
            $this->info("  - Admin Fee Percentage: " . ($businessProfile->admin_fee_percentage ?? 'null'));
            $this->info("  - Integrator Fee Fixed: " . ($businessProfile->integrator_fee_fixed ?? 'null'));
            $this->info("  - Integrator Fee Percentage: " . ($businessProfile->integrator_fee_percentage ?? 'null'));
            $this->newLine();
            
            // Tester le service
            $this->info("🧮 Test du service TransactionCalculator...");
            
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
            
            // Test détaillé
            $this->newLine();
            $this->info("📊 Détails du calcul:");
            $detailedResult = $calculator->calculateDetailed($transaction);
            
            $this->info("Frais Admin:");
            $this->info("  - Frais transaction: " . $detailedResult['details']['admin_fees_breakdown']['transaction_fee'] . " €");
            $this->info("  - Frais recharge: " . $detailedResult['details']['admin_fees_breakdown']['recharge_fee'] . " €");
            $this->info("  - Autres frais: " . $detailedResult['details']['admin_fees_breakdown']['other_fees'] . " €");
            $this->info("  - Total frais fixes: " . $detailedResult['details']['admin_fees_breakdown']['total_fixed_fees'] . " €");
            $this->info("  - Frais admin fixes: " . $detailedResult['details']['admin_fees_breakdown']['admin_fee_fixed'] . " €");
            $this->info("  - Pourcentage admin: " . $detailedResult['details']['admin_fees_breakdown']['admin_fee_percentage'] . "%");
            
            if ($detailedResult['details']['integrator_details']) {
                $this->info("Frais Intégrateur:");
                $this->info("  - Pourcentage: " . $detailedResult['details']['integrator_details']['integrator_fee_percentage'] . "%");
                $this->info("  - Frais fixes: " . $detailedResult['details']['integrator_details']['integrator_fixed_fee'] . " €");
                $this->info("  - Montant brut: " . $detailedResult['details']['integrator_details']['gross_amount'] . " €");
            } else {
                $this->info("Frais Intégrateur: Aucun");
            }
            
            $this->newLine();
            $this->info("🎉 Débogage terminé !");
            
            return 0;
            
        } catch (Exception $e) {
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
