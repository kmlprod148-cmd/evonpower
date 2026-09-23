<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Services\HierarchicalTransactionService;
use App\Services\BalanceUpdateService;
use Illuminate\Support\Facades\DB;

class TestCompleteHierarchySystem extends Command
{
    protected $signature = 'test:hierarchy-system {--reset : Reset all data}';
    protected $description = 'Test the complete hierarchical transaction system';

    public function handle()
    {
        $this->info('🧪 Test du Système de Transactions Hiérarchiques');
        $this->line('');

        if ($this->option('reset')) {
            $this->resetData();
        }

        $this->createTestData();
        $this->testTransactionProcessing();
        $this->testBalanceSystem();
        $this->displayResults();

        $this->info('✅ Test terminé avec succès !');
    }

    private function resetData()
    {
        $this->info('🔄 Réinitialisation des données...');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('balance_movements')->truncate();
        DB::table('transaction_hierarchies')->truncate();
        DB::table('transaction_details')->truncate();
        DB::table('transactions')->truncate();
        DB::table('charging_points')->truncate();
        DB::table('business_profiles')->truncate();
        DB::table('users')->where('email', 'like', 'test_%')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info('✅ Données réinitialisées');
    }

    private function createTestData()
    {
        $this->info('👥 Création des utilisateurs de test...');

        // Créer Admin
        $admin = User::create([
            'name' => 'ALAA (Admin Test)',
            'email' => 'test_admin@example.com',
            'password' => bcrypt('password'),
            'phone' => '+212600000001',
            'address' => 'Casablanca, Maroc',
            'city' => 'Casablanca',
            'balance' => 1000.00,
            'currency' => 'EUR',
        ]);
        $admin->assignRole('admin');

        // Créer Intégrateur (créé par Admin)
        $integrator = User::create([
            'name' => 'MEHDI (Intégrateur Test)',
            'email' => 'test_integrator@example.com',
            'password' => bcrypt('password'),
            'phone' => '+212600000002',
            'address' => 'Rabat, Maroc',
            'city' => 'Rabat',
            'balance' => 500.00,
            'currency' => 'EUR',
            'created_by' => $admin->id,
        ]);
        $integrator->assignRole('integrator');

        // Créer Opérateur (créé par Intégrateur)
        $operator = User::create([
            'name' => 'ANAS (Opérateur Test)',
            'email' => 'test_operator@example.com',
            'password' => bcrypt('password'),
            'phone' => '+212600000003',
            'address' => 'Fès, Maroc',
            'city' => 'Fès',
            'balance' => 200.00,
            'currency' => 'EUR',
            'created_by' => $integrator->id,
        ]);
        $operator->assignRole('partner');

        $this->info('✅ Utilisateurs créés :');
        $this->line("   - Admin: {$admin->name} (Solde: {$admin->balance} EUR)");
        $this->line("   - Intégrateur: {$integrator->name} (Solde: {$integrator->balance} EUR)");
        $this->line("   - Opérateur: {$operator->name} (Solde: {$operator->balance} EUR)");

        // Créer Business Profile créé par l'Intégrateur
        $this->info('🏢 Création du business profile...');
        $businessProfile = BusinessProfile::create([
            'name' => 'Business Profile Test Intégrateur',
            'description' => 'Business profile créé par l\'intégrateur pour tester les frais séparés',
            'transaction_fee_amount' => 2.5, // 2.5% pour l'intégrateur
            'transaction_fee_type' => 'percentage',
            'charge_fee_amount' => 2.0, // 2 DH de frais de recharge
            'base_fee_amount' => 1.0, // 1 DH de frais de base
            'admin_fee_percentage' => 10.0,
            'integrator_fee_percentage' => 5.0,
            'creator_id' => $integrator->id,
        ]);

        // Créer Charging Point
        $chargingPoint = ChargingPoint::create([
            'name' => 'Station Test',
            'address' => 'Test Address',
            'city' => 'Test City',
            'latitude' => 33.5731,
            'longitude' => -7.5898,
            'business_profile_id' => $businessProfile->id,
            'creator_id' => $integrator->id,
        ]);

        $this->info('✅ Business Profile créé :');
        $this->line("   - Nom: {$businessProfile->name}");
        $this->line("   - Créateur: {$integrator->name}");
        $this->line("   - Frais transaction: {$businessProfile->transaction_fee_amount}%");
        $this->line("   - Frais recharge: {$businessProfile->charge_fee_amount} DH");
        $this->line("   - Frais base: {$businessProfile->base_fee_amount} DH");

        return compact('admin', 'integrator', 'operator', 'businessProfile', 'chargingPoint');
    }

    private function testTransactionProcessing()
    {
        $this->info('💳 Test du traitement des transactions...');

        // Créer une transaction
        $transaction = Transaction::create([
            'user_id' => User::where('email', 'test_operator@example.com')->first()->id,
            'charging_point_id' => ChargingPoint::first()->id,
            'amount' => 200.00,
            'status' => 'completed',
            'transaction_type' => 'charging',
        ]);

        $this->line("   - Transaction créée: {$transaction->amount} EUR");

        // Traiter la transaction avec le service hiérarchique
        $service = new HierarchicalTransactionService();
        $result = $service->processTransaction($transaction);

        if ($result['success']) {
            $this->info('✅ Transaction traitée avec succès !');
            $this->line("   - Transaction ID: {$result['transaction_id']}");
            $this->line("   - Transactions hiérarchiques créées: {$result['hierarchical_transactions_count']}");
        } else {
            $this->error('❌ Erreur lors du traitement: ' . $result['error']);
        }
    }

    private function testBalanceSystem()
    {
        $this->info('💰 Test du système de soldes...');

        $balanceService = new BalanceUpdateService();
        
        // Obtenir les statistiques de solde pour chaque utilisateur
        $users = User::where('email', 'like', 'test_%')->get();
        
        foreach ($users as $user) {
            $stats = $balanceService->getUserBalanceStats($user);
            $this->line("   - {$user->name}:");
            $this->line("     * Solde actuel: {$stats['current_balance']} EUR");
            $this->line("     * Total entrées: {$stats['total_incoming']} EUR");
            $this->line("     * Total sorties: {$stats['total_outgoing']} EUR");
            $this->line("     * Net: {$stats['net_balance_change']} EUR");
        }
    }

    private function displayResults()
    {
        $this->info('📊 Résultats du test...');
        $this->line('');

        // Afficher les transactions hiérarchiques
        $hierarchicalTransactions = DB::table('transaction_hierarchies')
            ->join('users as payer', 'transaction_hierarchies.payer_id', '=', 'payer.id')
            ->join('users as payee', 'transaction_hierarchies.payee_id', '=', 'payee.id')
            ->select([
                'transaction_hierarchies.*',
                'payer.name as payer_name',
                'payee.name as payee_name'
            ])
            ->get();

        $this->info('🔄 Transactions Hiérarchiques:');
        foreach ($hierarchicalTransactions as $tx) {
            $this->line("   - {$tx->payer_name} → {$tx->payee_name}");
            $this->line("     * Type: {$tx->transaction_type}");
            $this->line("     * Montant: {$tx->amount} EUR");
            $this->line("     * Frais: {$tx->fees_amount} EUR");
            $this->line("     * Net: {$tx->net_amount} EUR");
            $this->line("     * Statut: {$tx->status}");
            $this->line('');
        }

        // Afficher les détails des transactions
        $transactionDetails = DB::table('transaction_details')
            ->join('users as admin', 'transaction_details.admin_creator_id', '=', 'admin.id')
            ->join('users as integrator', 'transaction_details.integrator_creator_id', '=', 'integrator.id')
            ->join('users as operator', 'transaction_details.operator_id', '=', 'operator.id')
            ->select([
                'transaction_details.*',
                'admin.name as admin_name',
                'integrator.name as integrator_name',
                'operator.name as operator_name'
            ])
            ->get();

        $this->info('📋 Détails des Transactions:');
        foreach ($transactionDetails as $detail) {
            $this->line("   - Transaction ID: {$detail->transaction_id}");
            $this->line("     * Admin ({$detail->admin_name}): {$detail->admin_share_amount} EUR");
            $this->line("     * Intégrateur ({$detail->integrator_name}): {$detail->integrator_share_amount} EUR");
            $this->line("     * Opérateur ({$detail->operator_name}): {$detail->operator_share_amount} EUR");
            $this->line("     * Frais totaux: {$detail->transaction_fee_total} EUR");
            $this->line('');
        }

        // Afficher les mouvements de solde
        $balanceMovements = DB::table('balance_movements')
            ->join('users as payer', 'balance_movements.payer_id', '=', 'payer.id')
            ->join('users as payee', 'balance_movements.payee_id', '=', 'payee.id')
            ->select([
                'balance_movements.*',
                'payer.name as payer_name',
                'payee.name as payee_name'
            ])
            ->get();

        $this->info('💰 Mouvements de Solde:');
        foreach ($balanceMovements as $movement) {
            $this->line("   - {$movement->payer_name} → {$movement->payee_name}");
            $this->line("     * Montant: {$movement->amount} EUR");
            $this->line("     * Type: {$movement->movement_type}");
            $this->line("     * Solde payeur avant: {$movement->payer_balance_before} EUR");
            $this->line("     * Solde payeur après: {$movement->payer_balance_after} EUR");
            $this->line('');
        }
    }
}
