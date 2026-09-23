<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Services\TransactionCalculator;

class TestAdminDeduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:admin-deduction {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste la nouvelle logique de déduction admin de la part intégrateur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🧮 Test de la déduction admin de la part intégrateur pour la transaction ID {$transactionId}...");
        $this->newLine();

        try {
            // Récupérer la transaction
            $transaction = Transaction::with(['chargingPoint', 'chargingPoint.businessProfile'])->find($transactionId);
            
            if (!$transaction) {
                $this->error("❌ Transaction ID {$transactionId} non trouvée");
                return 1;
            }
            
            $this->info("📋 Transaction: {$transaction->price_total} {$transaction->currency}");
            
            // Tester le calcul
            $calculator = new TransactionCalculator();
            $result = $calculator->calculate($transaction);
            $detailedResult = $calculator->calculateDetailed($transaction);
            
            $this->info("📊 Résultats du calcul:");
            $this->table(
                ['Rôle', 'Montant', 'Pourcentage'],
                [
                    ['Admin', number_format($result['admin'], 2) . ' €', $this->getPercentage($result['admin'], $result['total'])],
                    ['Intégrateur', number_format($result['integrator'], 2) . ' €', $this->getPercentage($result['integrator'], $result['total'])],
                    ['Opérateur', number_format($result['operator'], 2) . ' €', $this->getPercentage($result['operator'], $result['total'])],
                    ['Total', number_format($result['total'], 2) . ' €', '100%'],
                ]
            );
            
            // Détails de la déduction
            $this->newLine();
            $this->info("🔍 Détails de la déduction admin:");
            
            $adminBreakdown = $detailedResult['details']['admin_fees_breakdown'];
            $this->info("Frais Admin calculés:");
            $this->info("  - Frais de transaction: " . number_format($adminBreakdown['transaction_fee'], 2) . " €");
            $this->info("  - Frais de recharge: " . number_format($adminBreakdown['recharge_fee'], 2) . " €");
            $this->info("  - Autres frais: " . number_format($adminBreakdown['other_fees'], 2) . " €");
            $this->info("  - Total frais admin: " . number_format($result['admin'], 2) . " €");
            
            if ($detailedResult['details']['integrator_details']) {
                $integratorDetails = $detailedResult['details']['integrator_details'];
                $this->newLine();
                $this->info("Part Intégrateur:");
                $this->info("  - Montant brut (avant déduction): " . number_format($integratorDetails['gross_amount'], 2) . " €");
                $this->info("  - Déduction admin: -" . number_format($integratorDetails['admin_deduction'], 2) . " €");
                $this->info("  - Montant net (après déduction): " . number_format($integratorDetails['net_amount'], 2) . " €");
                
                // Vérification
                $expectedNet = $integratorDetails['gross_amount'] - $integratorDetails['admin_deduction'];
                $this->newLine();
                $this->info("✅ Vérification:");
                $this->info("  - Montant brut: " . number_format($integratorDetails['gross_amount'], 2) . " €");
                $this->info("  - Moins déduction admin: " . number_format($integratorDetails['admin_deduction'], 2) . " €");
                $this->info("  - Égal montant net: " . number_format($expectedNet, 2) . " €");
                
                if (abs($expectedNet - $integratorDetails['net_amount']) < 0.01) {
                    $this->info("  ✅ Calcul correct !");
                } else {
                    $this->error("  ❌ Erreur de calcul !");
                }
            }
            
            // Résumé de la logique
            $this->newLine();
            $this->info("📋 Logique appliquée:");
            $this->info("  1. Calcul de la part intégrateur brute");
            $this->info("  2. Calcul de la part admin");
            $this->info("  3. Déduction de la part admin de la part intégrateur");
            $this->info("  4. Calcul de la part opérateur (reste)");
            
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
