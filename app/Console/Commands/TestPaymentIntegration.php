<?php

namespace App\Console\Commands;

use App\Services\PaymentIntegrationService;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Commande de test pour l'intégration des paiements
 */
class TestPaymentIntegration extends Command
{
    protected $signature = 'payment:test-integration';
    protected $description = 'Teste l\'intégration des paiements CMI et Stripe';

    protected PaymentIntegrationService $paymentService;

    public function __construct(PaymentIntegrationService $paymentService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
    }

    public function handle()
    {
        $this->info('🧪 Test d\'intégration des paiements EVON');
        $this->info('==========================================');
        $this->newLine();

        // Test 1: Méthodes de paiement disponibles
        $this->testPaymentMethods();

        // Test 2: Statistiques de paiement
        $this->testPaymentStatistics();

        // Test 3: Configuration
        $this->testConfiguration();

        // Test 4: Simulation CMI
        $this->testCmiPayment();

        // Test 5: Simulation Stripe
        $this->testStripePayment();

        $this->newLine();
        $this->info('🎉 Tests d\'intégration des paiements terminés!');
    }

    protected function testPaymentMethods()
    {
        $this->info('1️⃣ Test des méthodes de paiement disponibles');
        $this->line('--------------------------------------------');

        try {
            $methods = $this->paymentService->getAvailablePaymentMethods();
            
            $this->info('✅ Méthodes de paiement récupérées:');
            foreach ($methods as $key => $method) {
                $status = $method['enabled'] ? '✅ Activé' : '❌ Désactivé';
                $this->line("   - {$method['name']}: {$status}");
                $this->line("     Devise: {$method['currency']}");
                $this->line("     Mode test: " . ($method['test_mode'] ? 'Oui' : 'Non'));
                $this->newLine();
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
        }
    }

    protected function testPaymentStatistics()
    {
        $this->info('2️⃣ Test des statistiques de paiement');
        $this->line('------------------------------------');

        try {
            $stats = $this->paymentService->getPaymentStatistics();
            
            $this->info('✅ Statistiques de paiement:');
            $this->line("   - Total transactions: {$stats['total_transactions']}");
            $this->line("   - Transactions payées: {$stats['paid_transactions']}");
            $this->line("   - Transactions en attente: {$stats['pending_transactions']}");
            $this->line("   - Transactions échouées: {$stats['failed_transactions']}");
            $this->line("   - Revenus totaux: {$stats['total_revenue']} EUR");
            $this->line("   - Paiements CMI: {$stats['cmi_payments']}");
            $this->line("   - Paiements Stripe: {$stats['stripe_payments']}");
            $this->line("   - Virements bancaires: {$stats['bank_transfer_payments']}");
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
        }
    }

    protected function testConfiguration()
    {
        $this->newLine();
        $this->info('3️⃣ Test de la configuration des paiements');
        $this->line('----------------------------------------');

        try {
            $config = config('payments');
            
            if ($config) {
                $this->info('✅ Configuration des paiements chargée');
                $this->line("   - Méthode par défaut: {$config['default_method']}");
                $this->line("   - Méthodes supportées: " . implode(', ', $config['supported_methods']));
                $this->line("   - CMI configuré: " . (!empty($config['cmi']['store_key']) ? 'Oui' : 'Non'));
                $this->line("   - Stripe configuré: " . (!empty($config['stripe']['secret_key']) ? 'Oui' : 'Non'));
            } else {
                $this->error('❌ Configuration des paiements non trouvée');
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
        }
    }

    protected function testCmiPayment()
    {
        $this->newLine();
        $this->info('4️⃣ Test de simulation paiement CMI');
        $this->line('----------------------------------');

        try {
            $user = User::first();
            if (!$user) {
                $this->error('❌ Aucun utilisateur trouvé pour le test');
                return;
            }

            $this->info("✅ Utilisateur de test: {$user->name} (ID: {$user->id})");
            
            // Créer une transaction de test
            $transaction = new Transaction();
            $transaction->id = 999999; // ID temporaire pour le test
            $transaction->price_total = 50.00;
            $transaction->currency = 'EUR';
            $transaction->user_id = $user->id;
            $transaction->user = $user;
            
            $this->info("✅ Transaction de test créée: {$transaction->id} - {$transaction->price_total} {$transaction->currency}");
            
            // Tester l'initialisation du paiement CMI
            $result = $this->paymentService->initiateCmiPayment($transaction, ['lang' => 'fr']);
            
            if ($result['success']) {
                $this->info('✅ Paiement CMI initialisé avec succès');
                $this->line("   - URL de paiement: {$result['payment_url']}");
                $this->line("   - Méthode: {$result['payment_method']}");
                $this->line("   - Transaction ID: {$result['transaction_id']}");
            } else {
                $this->error("❌ Échec initialisation paiement CMI: {$result['message']}");
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
        }
    }

    protected function testStripePayment()
    {
        $this->newLine();
        $this->info('5️⃣ Test de simulation paiement Stripe');
        $this->line('------------------------------------');

        try {
            $user = User::first();
            if (!$user) {
                $this->error('❌ Aucun utilisateur trouvé pour le test');
                return;
            }

            // Créer une transaction de test
            $transaction = new Transaction();
            $transaction->id = 999998; // ID temporaire pour le test
            $transaction->price_total = 25.00;
            $transaction->currency = 'EUR';
            $transaction->user_id = $user->id;
            $transaction->user = $user;
            
            $this->info("✅ Transaction de test créée: {$transaction->id} - {$transaction->price_total} {$transaction->currency}");
            
            // Tester l'initialisation du paiement Stripe
            $result = $this->paymentService->initiateStripePayment($transaction);
            
            if ($result['success']) {
                $this->info('✅ Paiement Stripe initialisé avec succès');
                $this->line("   - Session ID: {$result['session_id']}");
                $this->line("   - URL de paiement: {$result['payment_url']}");
                $this->line("   - Méthode: {$result['payment_method']}");
                $this->line("   - Transaction ID: {$result['transaction_id']}");
            } else {
                $this->error("❌ Échec initialisation paiement Stripe: {$result['message']}");
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
        }
    }
}
