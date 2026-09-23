<?php

namespace App\Console\Commands;

use App\Services\ReservationTransactionTestService;
use Illuminate\Console\Command;

class TestReservationTransactionLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:reservation-transaction-logic {--cleanup : Nettoyer les données de test après le test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester la logique de répartition des parts de réservation selon les Business Profiles';

    protected ReservationTransactionTestService $testService;

    public function __construct(ReservationTransactionTestService $testService)
    {
        parent::__construct();
        $this->testService = $testService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Démarrage des tests de logique de répartition des parts...');
        $this->newLine();

        try {
            // Exécuter la suite de tests
            $results = $this->testService->runFullTestSuite();

            // Afficher les résultats
            $this->displayResults($results);

            // Nettoyer si demandé
            if ($this->option('cleanup')) {
                $this->info('🧹 Nettoyage des données de test...');
                $cleanup = $this->testService->cleanupTestData();
                if ($cleanup['success']) {
                    $this->info('✅ Données de test nettoyées');
                } else {
                    $this->error('❌ Erreur lors du nettoyage: ' . $cleanup['error']);
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors des tests: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function displayResults(array $results): void
    {
        // Test 1: Création du scénario
        $this->info('📋 Test 1: Création du scénario de test');
        if ($results['scenario_creation']['success']) {
            $this->info('✅ Scénario créé avec succès');
            $scenario = $results['scenario_creation']['scenario'];
            $this->line("   - Admin: {$scenario['admin']->name} (Balance: {$scenario['admin']->balance}€)");
            $this->line("   - Intégrateur: {$scenario['integrator']->name}");
            $this->line("   - Opérateur: {$scenario['operator']->name} (Balance: {$scenario['operator']->balance}€)");
            $this->line("   - Réservation: {$scenario['reservation']->total_amount}€");
        } else {
            $this->error('❌ Échec de la création du scénario: ' . $results['scenario_creation']['error']);
            return;
        }

        $this->newLine();

        // Test 2: Calcul des parts
        $this->info('💰 Test 2: Calcul des parts selon les Business Profiles');
        if ($results['share_calculation']['success']) {
            $testResults = $results['share_calculation']['test_results'];
            $walletResults = $results['share_calculation']['wallet_results'];

            $this->info('✅ Calcul des parts effectué');
            $this->line("   - Montant total: {$testResults['total_amount']}€");
            $this->line("   - Part Admin: {$testResults['admin_share']}€ (Attendu: {$testResults['expected_admin_share']}€)");
            $this->line("   - Part Intégrateur: {$testResults['integrator_share']}€ (Attendu: {$testResults['expected_integrator_share']}€)");
            $this->line("   - Part Opérateur: {$testResults['operator_share']}€ (Attendu: {$testResults['expected_operator_share']}€)");

            $this->newLine();
            $this->info('🔍 Vérifications des calculs:');
            $this->line("   - Calcul Admin correct: " . ($testResults['admin_calculation_correct'] ? '✅' : '❌'));
            $this->line("   - Calcul Intégrateur correct: " . ($testResults['integrator_calculation_correct'] ? '✅' : '❌'));
            $this->line("   - Calcul Opérateur correct: " . ($testResults['operator_calculation_correct'] ? '✅' : '❌'));
            $this->line("   - Total correct: " . ($testResults['total_calculation_correct'] ? '✅' : '❌'));

            $this->newLine();
            $this->info('💳 Vérifications des wallets:');
            $this->line("   - Balance Admin: {$walletResults['admin_wallet_balance']}€");
            $this->line("   - Balance Intégrateur: {$walletResults['integrator_wallet_balance']}€");
            $this->line("   - Balance Opérateur: {$walletResults['operator_wallet_balance']}€");
            $this->line("   - Crédit Admin correct: " . ($walletResults['admin_credit_correct'] ? '✅' : '❌'));
            $this->line("   - Crédit Intégrateur correct: " . ($walletResults['integrator_credit_correct'] ? '✅' : '❌'));
            $this->line("   - Débit Opérateur correct: " . ($walletResults['operator_debit_correct'] ? '✅' : '❌'));

        } else {
            $this->error('❌ Échec du calcul des parts: ' . $results['share_calculation']['error']);
        }

        $this->newLine();

        // Test 3: Nettoyage
        $this->info('🧹 Test 3: Nettoyage des données');
        if ($results['cleanup']['success']) {
            $this->info('✅ Données nettoyées avec succès');
        } else {
            $this->error('❌ Échec du nettoyage: ' . $results['cleanup']['error']);
        }

        $this->newLine();
        $this->info('🎉 Tests terminés!');
    }
}
