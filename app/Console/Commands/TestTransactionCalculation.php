<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Services\TransactionCalculator;

class TestTransactionCalculation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:transaction-calculation {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste le calcul de répartition pour une transaction';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🧮 Test du calcul de répartition pour la transaction ID {$transactionId}...");
        $this->newLine();

        try {
            // Récupérer la transaction
            $transaction = Transaction::with(['chargingPoint', 'chargingPoint.businessProfile'])->find($transactionId);
            
            if (!$transaction) {
                $this->error("❌ Transaction ID {$transactionId} non trouvée");
                return 1;
            }
            
            $this->info("📋 Transaction:");
            $this->info("  - Montant: {$transaction->price_total} {$transaction->currency}");
            $this->info("  - Status: {$transaction->status}");
            
            if ($transaction->chargingPoint && $transaction->chargingPoint->businessProfile) {
                $bp = $transaction->chargingPoint->businessProfile;
                $this->info("  - Business Profile: {$bp->name} (ID: {$bp->id})");
            } else {
                $this->warn("  - ⚠️ Pas de business profile configuré");
            }
            
            $this->newLine();
            
            // Tester le service
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
            
            // Vérifier si les montants sont corrects
            $this->newLine();
            if ($result['admin'] == 0 && $result['integrator'] == 0 && $result['operator'] == 0) {
                $this->error("❌ Tous les montants sont à zéro !");
                $this->info("Causes possibles:");
                $this->info("  1. Pas de business profile configuré");
                $this->info("  2. Business profile sans frais configurés");
                $this->info("  3. Montant de transaction invalide");
                $this->newLine();
                $this->info("💡 Solution: Exécutez 'php artisan fix:transaction-business-profile {$transactionId}'");
            } else {
                $this->info("✅ Calcul réussi !");
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
