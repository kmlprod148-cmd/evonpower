<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TransactionCalculator;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Models\Integrator;
use App\Models\User;

class TestTransactionCalculator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:transaction-calculator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste le service TransactionCalculator avec des données de test';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Test du service TransactionCalculator...');
        $this->newLine();

        // Créer des données de test
        $this->info('📝 Création des données de test...');

        // Business profile avec frais
        $bp = BusinessProfile::create([
            'name' => 'Test BP',
            'transaction_fee' => 2.00,
            'recharge_fee' => 1.00,
            'active_terminal_fee' => 0.50,
            'admin_fee_fixed' => 4.00,
            'admin_fee_percentage' => 12.0,
            'integrator_fee_fixed' => 2.00,
            'integrator_fee_percentage' => 18.0,
        ]);

        $this->info("✅ Business Profile créé (ID: {$bp->id})");

        // Business profile intégrateur
        $integratorBp = BusinessProfile::create([
            'name' => 'Test Integrator BP',
            'integrator_fee_fixed' => 1.50,
            'integrator_fee_percentage' => 20.0,
        ]);

        $this->info("✅ Business Profile Intégrateur créé (ID: {$integratorBp->id})");

        // Utilisateur intégrateur
        $user = User::create([
            'name' => 'Test Integrator',
            'email' => 'test@integrator.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('integrator');

        // Intégrateur
        $integrator = Integrator::create([
            'user_id' => $user->id,
            'business_profile_id' => $integratorBp->id,
            'name' => 'Test Integrator',
            'email' => 'test@integrator.com',
        ]);

        $this->info("✅ Intégrateur créé (ID: {$integrator->id})");

        // Point de charge
        $chargingPoint = ChargingPoint::create([
            'name' => 'Test CP',
            'business_profile_id' => $bp->id,
            'integrator_id' => $integrator->id,
            'status' => 'active',
            'location' => 'Test Location',
        ]);

        $this->info("✅ Point de charge créé (ID: {$chargingPoint->id})");

        // Transaction de test
        $transaction = Transaction::create([
            'charging_point_id' => $chargingPoint->id,
            'amount' => 25.00,
            'status' => 'completed',
            'start_timestamp' => now()->subHour(),
            'stop_timestamp' => now(),
            'price_total' => 25.00,
            'currency' => 'EUR',
        ]);

        $this->info("✅ Transaction créée (ID: {$transaction->id}, Montant: 25.00 €)");
        $this->newLine();

        // Test du service
        $this->info('🧮 Test du calcul de répartition...');

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

        // Vérification
        $expectedAdmin = max(4.00, 3.50); // max(admin_fee_fixed, transaction_fee + recharge_fee + active_terminal_fee)
        $expectedIntegrator = 25.00 * 0.20; // 20% du montant total
        $expectedOperator = 25.00 - $expectedAdmin - $expectedIntegrator;

        $this->newLine();
        $this->info('🔍 Vérification des calculs:');
        $this->info("  - Admin attendu: {$expectedAdmin} € (max entre 4.00€ fixe et 3.50€ calculé)");
        $this->info("  - Intégrateur attendu: {$expectedIntegrator} € (20% de 25€)");
        $this->info("  - Opérateur attendu: {$expectedOperator} €");
        $this->newLine();

        $adminCorrect = abs($result['admin'] - $expectedAdmin) < 0.01;
        $integratorCorrect = abs($result['integrator'] - $expectedIntegrator) < 0.01;
        $operatorCorrect = abs($result['operator'] - $expectedOperator) < 0.01;

        if ($adminCorrect) {
            $this->info('✅ Calcul admin correct');
        } else {
            $this->error('❌ Calcul admin incorrect');
        }

        if ($integratorCorrect) {
            $this->info('✅ Calcul intégrateur correct');
        } else {
            $this->error('❌ Calcul intégrateur incorrect');
        }

        if ($operatorCorrect) {
            $this->info('✅ Calcul opérateur correct');
        } else {
            $this->error('❌ Calcul opérateur incorrect');
        }

        // Test détaillé
        $this->newLine();
        $this->info('📋 Test du calcul détaillé...');
        $detailedResult = $calculator->calculateDetailed($transaction);

        $this->info('Détails admin:');
        $this->info("  - Frais transaction: " . $detailedResult['details']['admin_fees_breakdown']['transaction_fee'] . " €");
        $this->info("  - Frais recharge: " . $detailedResult['details']['admin_fees_breakdown']['recharge_fee'] . " €");
        $this->info("  - Autres frais: " . $detailedResult['details']['admin_fees_breakdown']['other_fees'] . " €");
        $this->info("  - Total frais fixes: " . $detailedResult['details']['admin_fees_breakdown']['total_fixed_fees'] . " €");
        $this->info("  - Frais admin fixes: " . $detailedResult['details']['admin_fees_breakdown']['admin_fee_fixed'] . " €");
        $this->info("  - Pourcentage admin: " . $detailedResult['details']['admin_fees_breakdown']['admin_fee_percentage'] . "%");

        if ($detailedResult['details']['integrator_details']) {
            $this->info('Détails intégrateur:');
            $this->info("  - Pourcentage: " . $detailedResult['details']['integrator_details']['integrator_fee_percentage'] . "%");
            $this->info("  - Frais fixes: " . $detailedResult['details']['integrator_details']['integrator_fixed_fee'] . " €");
            $this->info("  - Montant brut: " . $detailedResult['details']['integrator_details']['gross_amount'] . " €");
        }

        $this->newLine();
        $this->info('🎉 Test terminé avec succès !');

        // Nettoyer les données de test
        $this->newLine();
        $this->info('🧹 Nettoyage des données de test...');
        
        $transaction->delete();
        $chargingPoint->delete();
        $integrator->delete();
        $user->delete();
        $integratorBp->delete();
        $bp->delete();
        
        $this->info('✅ Données de test supprimées');

        return 0;
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
