<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use App\Services\TransactionCalculator;

class CompleteFixTransaction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'complete:fix-transaction {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige complètement une transaction avec frais admin et intégrateur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🔧 Correction complète de la transaction ID {$transactionId}...");
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
            
            // Vérifier/créer le business profile
            $bp = $chargingPoint->businessProfile;
            if (!$bp) {
                $this->info("📝 Création d'un business profile...");
                
                $bp = BusinessProfile::create([
                    'name' => 'BP Test Transaction ' . $transactionId,
                    'is_active' => true,
                    'transaction_fee_amount' => 2.50,
                    'charge_fee_amount' => 1.00,
                    'terminal_fee_amount' => 0.50,
                    'base_fee_amount' => 0.00,
                    'admin_fee_fixed' => 0.00,
                    'admin_fee_percentage' => 0.00,
                    'integrator_fee_fixed' => 1.50,
                    'integrator_fee_percentage' => 8.00,
                    'operator_commission' => 0.00,
                    'integrator_commission' => 0.00,
                    'owner_commission' => 0.00,
                    'partner_id' => 1,
                    'integrator_id' => 1,
                ]);
                
                $chargingPoint->update(['business_profile_id' => $bp->id]);
                $this->info("✅ Business Profile créé avec ID: {$bp->id}");
            } else {
                $this->info("💼 Business Profile existant: {$bp->name} (ID: {$bp->id})");
                
                // Mettre à jour les frais s'ils sont à zéro
                $needsUpdate = false;
                $updates = [];
                
                if (($bp->transaction_fee_amount ?? 0) == 0) {
                    $updates['transaction_fee_amount'] = 2.50;
                    $needsUpdate = true;
                }
                if (($bp->charge_fee_amount ?? 0) == 0) {
                    $updates['charge_fee_amount'] = 1.00;
                    $needsUpdate = true;
                }
                if (($bp->terminal_fee_amount ?? 0) == 0) {
                    $updates['terminal_fee_amount'] = 0.50;
                    $needsUpdate = true;
                }
                if (($bp->integrator_fee_percentage ?? 0) == 0 && ($bp->integrator_fee_fixed ?? 0) == 0) {
                    $updates['integrator_fee_percentage'] = 8.00;
                    $updates['integrator_fee_fixed'] = 1.50;
                    $needsUpdate = true;
                }
                
                if ($needsUpdate) {
                    $bp->update($updates);
                    $this->info("✅ Frais mis à jour dans le business profile");
                } else {
                    $this->info("ℹ️ Le business profile a déjà des frais configurés");
                }
            }
            
            // Vérifier/créer l'intégrateur
            if (!$chargingPoint->integrator_id) {
                $this->info("📝 Création d'un intégrateur...");
                
                $integrator = Integrator::first();
                if (!$integrator) {
                    $integrator = Integrator::create([
                        'name' => 'Intégrateur Test',
                        'email' => 'test@integrator.com',
                        'is_active' => true,
                    ]);
                    $this->info("✅ Intégrateur créé avec ID: {$integrator->id}");
                } else {
                    $this->info("✅ Intégrateur existant: {$integrator->name} (ID: {$integrator->id})");
                }
                
                $chargingPoint->update(['integrator_id' => $integrator->id]);
                $this->info("✅ Intégrateur associé au point de charge");
            } else {
                $this->info("ℹ️ Le point de charge a déjà un intégrateur ID: {$chargingPoint->integrator_id}");
            }
            
            $this->newLine();
            
            // Tester le calcul final
            $this->info("🧮 Test du calcul final...");
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
            
            // Détails des frais
            $this->newLine();
            $this->info("📊 Détails des frais:");
            $detailedResult = $calculator->calculateDetailed($transaction);
            
            $adminBreakdown = $detailedResult['details']['admin_fees_breakdown'];
            $this->info("Frais Admin:");
            $this->info("  - Frais de transaction: " . number_format($adminBreakdown['transaction_fee'], 2) . " €");
            $this->info("  - Frais de recharge: " . number_format($adminBreakdown['recharge_fee'], 2) . " €");
            $this->info("  - Autres frais: " . number_format($adminBreakdown['other_fees'], 2) . " €");
            $this->info("  - Total frais fixes: " . number_format($adminBreakdown['total_fixed_fees'], 2) . " €");
            
            if ($detailedResult['details']['integrator_details']) {
                $integratorDetails = $detailedResult['details']['integrator_details'];
                $this->info("Frais Intégrateur:");
                $this->info("  - Pourcentage: " . $integratorDetails['integrator_fee_percentage'] . "%");
                $this->info("  - Frais fixes: " . number_format($integratorDetails['integrator_fixed_fee'], 2) . " €");
                $this->info("  - Montant brut: " . number_format($integratorDetails['gross_amount'], 2) . " €");
            }
            
            // Vérification finale
            if ($result['admin'] > 0 && $result['integrator'] > 0) {
                $this->newLine();
                $this->info("🎉 SUCCÈS COMPLET !");
                $this->info("✅ Frais admin configurés: {$result['admin']} €");
                $this->info("✅ Frais intégrateur configurés: {$result['integrator']} €");
                $this->info("✅ Frais opérateur calculés: {$result['operator']} €");
                $this->info("📱 Vous pouvez maintenant voir la répartition complète dans l'interface web.");
            } else {
                $this->newLine();
                $this->warn("⚠️ Certains frais sont encore à 0 €");
                if ($result['admin'] == 0) {
                    $this->error("❌ Frais admin = 0 €");
                }
                if ($result['integrator'] == 0) {
                    $this->error("❌ Frais intégrateur = 0 €");
                }
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
