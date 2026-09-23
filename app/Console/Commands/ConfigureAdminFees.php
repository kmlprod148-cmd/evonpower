<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Services\TransactionCalculator;

class ConfigureAdminFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configure:admin-fees {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure les frais admin dans le business profile';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🔧 Configuration des frais admin pour la transaction ID {$transactionId}...");
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
            $this->newLine();
            
            // Afficher l'état actuel des frais
            $this->info("📊 État actuel des frais admin:");
            $this->info("  - Transaction Fee Amount: " . ($bp->transaction_fee_amount ?? 'null'));
            $this->info("  - Charge Fee Amount: " . ($bp->charge_fee_amount ?? 'null'));
            $this->info("  - Terminal Fee Amount: " . ($bp->terminal_fee_amount ?? 'null'));
            $this->info("  - Base Fee Amount: " . ($bp->base_fee_amount ?? 'null'));
            $this->info("  - Admin Fee Fixed: " . ($bp->admin_fee_fixed ?? 'null'));
            $this->info("  - Admin Fee Percentage: " . ($bp->admin_fee_percentage ?? 'null'));
            
            $this->newLine();
            
            // Configurer les frais admin
            $this->info("📝 Configuration des frais admin...");
            
            $bp->update([
                'transaction_fee_amount' => 2.50,
                'charge_fee_amount' => 1.00,
                'terminal_fee_amount' => 0.50,
                'base_fee_amount' => 0.00,
                'admin_fee_fixed' => 0.00, // Pas de frais fixes spécifiques
                'admin_fee_percentage' => 0.00, // Pas de pourcentage, utiliser les frais fixes
            ]);
            
            $this->info("✅ Frais admin configurés:");
            $this->info("  - Frais de transaction: 2.50 €");
            $this->info("  - Frais de recharge: 1.00 €");
            $this->info("  - Frais de terminal: 0.50 €");
            $this->info("  - Frais de base: 0.00 €");
            $this->info("  - Total frais fixes: 4.00 €");
            
            $this->newLine();
            
            // Tester le calcul
            $this->info("🧮 Test du calcul avec les nouveaux frais...");
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
            $this->info("📊 Détails des frais admin:");
            $detailedResult = $calculator->calculateDetailed($transaction);
            
            $adminBreakdown = $detailedResult['details']['admin_fees_breakdown'];
            $this->info("  - Frais de transaction: " . number_format($adminBreakdown['transaction_fee'], 2) . " €");
            $this->info("  - Frais de recharge: " . number_format($adminBreakdown['recharge_fee'], 2) . " €");
            $this->info("  - Autres frais: " . number_format($adminBreakdown['other_fees'], 2) . " €");
            $this->info("  - Total frais fixes: " . number_format($adminBreakdown['total_fixed_fees'], 2) . " €");
            
            if ($result['admin'] > 0) {
                $this->newLine();
                $this->info("🎉 SUCCÈS ! Les frais admin sont maintenant configurés: {$result['admin']} €");
                $this->info("📱 Vous pouvez maintenant voir les frais admin corrects dans l'interface web.");
            } else {
                $this->newLine();
                $this->error("❌ Les frais admin sont toujours à 0 €");
                $this->info("💡 Vérifiez la configuration du business profile.");
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
