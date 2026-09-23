<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Models\Partner;
use App\Models\Integrator;
use App\Services\TransactionCalculator;

class FixTransactionRepartition extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:transaction-repartition {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige complètement la répartition d\'une transaction';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🔧 Correction complète de la répartition pour la transaction ID {$transactionId}...");
        $this->newLine();

        try {
            // Étape 1: Vérifier la transaction
            $transaction = Transaction::with(['chargingPoint'])->find($transactionId);
            
            if (!$transaction) {
                $this->error("❌ Transaction ID {$transactionId} non trouvée");
                return 1;
            }
            
            $this->info("✅ Transaction trouvée: {$transaction->price_total} {$transaction->currency}");
            
            // Étape 2: Vérifier/créer les données de base
            $this->info("📋 Vérification des données de base...");
            
            $partner = Partner::first();
            if (!$partner) {
                $partner = Partner::create([
                    'name' => 'Partenaire Test',
                    'email' => 'test@partner.com',
                    'is_active' => true,
                ]);
                $this->info("✅ Partenaire créé avec ID: {$partner->id}");
            } else {
                $this->info("✅ Partenaire existant: {$partner->name} (ID: {$partner->id})");
            }
            
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
            
            // Étape 3: Vérifier/créer le business profile
            $chargingPoint = $transaction->chargingPoint;
            $businessProfile = null;
            
            if ($chargingPoint && $chargingPoint->business_profile_id) {
                $businessProfile = BusinessProfile::find($chargingPoint->business_profile_id);
                if ($businessProfile) {
                    $this->info("✅ Business Profile existant: {$businessProfile->name} (ID: {$businessProfile->id})");
                }
            }
            
            if (!$businessProfile) {
                $this->info("📝 Création d'un nouveau business profile...");
                
                $businessProfile = BusinessProfile::create([
                    'name' => 'BP Test Transaction ' . $transactionId,
                    'is_active' => true,
                    'admin_fee_fixed' => 3.00,
                    'admin_fee_percentage' => 5.00,
                    'integrator_fee_fixed' => 1.00,
                    'integrator_fee_percentage' => 8.00,
                    'operator_commission' => 0.00,
                    'integrator_commission' => 0.00,
                    'owner_commission' => 0.00,
                    'partner_id' => $partner->id,
                    'integrator_id' => $integrator->id,
                ]);
                
                $this->info("✅ Business Profile créé avec ID: {$businessProfile->id}");
                
                // Mettre à jour le point de charge
                if ($chargingPoint) {
                    $chargingPoint->update(['business_profile_id' => $businessProfile->id]);
                    $this->info("✅ Point de charge mis à jour");
                }
            }
            
            // Étape 4: Tester le calcul
            $this->newLine();
            $this->info("🧮 Test du calcul de répartition...");
            
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
            
            // Étape 5: Vérifier le résultat
            if ($result['admin'] > 0 || $result['integrator'] > 0 || $result['operator'] > 0) {
                $this->newLine();
                $this->info("🎉 SUCCÈS ! La répartition fonctionne maintenant.");
                $this->info("📱 Vous pouvez maintenant voir les détails de la transaction dans l'interface web.");
                $this->info("🔗 URL: /transactions/{$transactionId}");
            } else {
                $this->newLine();
                $this->error("❌ Le calcul retourne encore des zéros.");
                $this->info("💡 Vérifiez que le business profile a des frais configurés.");
            }
            
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
