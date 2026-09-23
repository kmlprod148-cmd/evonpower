<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Services\HierarchicalTransactionService;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class TestTransactionHierarchy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:hierarchy-test {--amount=200} {--create-data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste le système de transactions hiérarchiques avec business profiles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Test du système de transactions hiérarchiques avec business profiles');
        $this->newLine();

        $amount = (float) $this->option('amount');
        $createData = $this->option('create-data');

        if ($createData) {
            $this->createTestData();
        }

        $this->testTransactionProcessing($amount);
    }

    private function createTestData()
    {
        $this->info('📝 Création des données de test...');

        // Créer les rôles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'integrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'partner', 'guard_name' => 'web']);

        // Créer un business profile
        $businessProfile = BusinessProfile::firstOrCreate(
            ['name' => 'Business Profile Test Command'],
            [
                'description' => 'Business profile créé par la commande de test',
                'is_active' => true,
                'transaction_fee_amount' => 1.00,
                'transaction_fee_type' => 'fixed',
                'charge_fee_amount' => 2.00,
                'base_fee_amount' => 1.00,
                'admin_fee_percentage' => 10.0,
                'integrator_fee_percentage' => 5.0,
            ]
        );

        // Créer les utilisateurs
        $admin = User::firstOrCreate(
            ['email' => 'test-admin@example.com'],
            [
                'name' => 'ALAA TEST',
                'password' => Hash::make('password'),
                'balance' => 0.00,
                'currency' => 'EUR',
            ]
        );
        $admin->assignRole('admin');

        $integrator = User::firstOrCreate(
            ['email' => 'test-integrator@example.com'],
            [
                'name' => 'MEHDI TEST',
                'password' => Hash::make('password'),
                'balance' => 0.00,
                'currency' => 'EUR',
                'created_by' => $admin->id,
            ]
        );
        $integrator->assignRole('integrator');

        $operator = User::firstOrCreate(
            ['email' => 'test-operator@example.com'],
            [
                'name' => 'ANAS TEST',
                'password' => Hash::make('password'),
                'balance' => 500.00,
                'currency' => 'EUR',
                'created_by' => $integrator->id,
            ]
        );
        $operator->assignRole('partner');

        // Créer une borne
        $chargingPoint = ChargingPoint::firstOrCreate(
            ['name' => 'Borne Test Command'],
            [
                'user_id' => $operator->id,
                'business_profile_id' => $businessProfile->id,
                'status' => 'online',
                'max_power' => 22.0,
                'serial_number' => 'TEST-CMD-001',
                'latitude' => 33.5731,
                'longitude' => -7.5898,
            ]
        );

        $this->info('✅ Données de test créées avec succès');
        $this->newLine();
    }

    private function testTransactionProcessing($amount)
    {
        $this->info("💰 Test de traitement d'une transaction de {$amount} DH");
        $this->newLine();

        // Trouver les données de test
        $operator = User::where('email', 'test-operator@example.com')->first();
        $chargingPoint = ChargingPoint::where('name', 'Borne Test Command')->first();

        if (!$operator || !$chargingPoint) {
            $this->error('❌ Données de test non trouvées. Utilisez --create-data pour les créer.');
            return;
        }

        // Créer une transaction
        $transaction = Transaction::create([
            'user_id' => $operator->id,
            'charging_point_id' => $chargingPoint->id,
            'amount' => $amount,
            'price_total' => $amount,
            'price_energy' => $amount * 0.9,
            'price_time' => $amount * 0.1,
            'price_service' => 0.00,
            'price_tax' => 0.00,
            'currency' => 'EUR',
            'status' => 'completed',
            'payment_status' => 'paid',
            'energy_delivered' => 10.0,
            'duration' => 30,
            'start_timestamp' => now()->subMinutes(30),
            'stop_timestamp' => now(),
        ]);

        $this->info("📋 Transaction créée avec l'ID: {$transaction->id}");

        // Traiter la transaction
        $service = new HierarchicalTransactionService();
        $result = $service->processTransaction($transaction);

        if ($result['success']) {
            $this->info('✅ Transaction traitée avec succès !');
            $this->newLine();

            $transactionDetail = $result['transaction_detail'];
            $businessProfile = $transactionDetail->getBusinessProfile();

            // Afficher les détails
            $this->displayTransactionDetails($transactionDetail, $businessProfile);
            $this->displayHierarchicalTransactions($result['hierarchical_transactions']);
            $this->displayUserBalances($result['hierarchy']);

        } else {
            $this->error('❌ Erreur lors du traitement: ' . $result['error']);
        }
    }

    private function displayTransactionDetails($transactionDetail, $businessProfile)
    {
        $this->info('📊 Détails de la transaction:');
        $this->table(
            ['Élément', 'Valeur'],
            [
                ['Business Profile', $businessProfile ? $businessProfile->name : 'N/A'],
                ['Montant original', $transactionDetail->transaction->amount . ' EUR'],
                ['Frais totaux', $transactionDetail->transaction_fee_total . ' EUR'],
                ['Part Admin', $transactionDetail->admin_share_amount . ' EUR (' . $transactionDetail->admin_share_percentage . '%)'],
                ['Part Intégrateur', $transactionDetail->integrator_share_amount . ' EUR (' . $transactionDetail->integrator_share_percentage . '%)'],
                ['Part Opérateur', $transactionDetail->operator_share_amount . ' EUR'],
            ]
        );
        $this->newLine();
    }

    private function displayHierarchicalTransactions($hierarchicalTransactions)
    {
        $this->info('🔄 Transactions hiérarchiques:');
        $this->table(
            ['Type', 'Payeur', 'Bénéficiaire', 'Montant', 'Statut'],
            array_map(function ($ht) {
                return [
                    $ht->getTransactionTypeLabel(),
                    $ht->payer->name,
                    $ht->payee->name,
                    $ht->amount . ' EUR',
                    $ht->getStatusLabel(),
                ];
            }, $hierarchicalTransactions)
        );
        $this->newLine();
    }

    private function displayUserBalances($hierarchy)
    {
        $this->info('💰 Soldes des utilisateurs après transaction:');
        $this->table(
            ['Utilisateur', 'Rôle', 'Solde'],
            [
                [$hierarchy['admin']->name, 'Admin', $hierarchy['admin']->getFormattedBalance()],
                [$hierarchy['integrator']->name, 'Intégrateur', $hierarchy['integrator']->getFormattedBalance()],
                [$hierarchy['operator']->name, 'Opérateur', $hierarchy['operator']->getFormattedBalance()],
            ]
        );
        $this->newLine();
    }
}
