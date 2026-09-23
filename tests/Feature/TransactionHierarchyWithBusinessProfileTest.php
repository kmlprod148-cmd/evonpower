<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use App\Models\Partner;
use App\Services\HierarchicalTransactionService;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class TransactionHierarchyWithBusinessProfileTest extends TestCase
{
    use RefreshDatabase;

    protected HierarchicalTransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->transactionService = new HierarchicalTransactionService();
        
        // Créer les rôles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'integrator', 'guard_name' => 'web']);
        Role::create(['name' => 'partner', 'guard_name' => 'web']);
    }

    /** @test */
    public function it_calculates_fees_based_on_business_profile()
    {
        // Créer un business profile avec des frais spécifiques
        $businessProfile = BusinessProfile::create([
            'name' => 'Test Business Profile',
            'description' => 'Business profile pour les tests',
            'is_active' => true,
            'transaction_fee_amount' => 2.00, // 2 DH fixe
            'transaction_fee_type' => 'fixed',
            'charge_fee_amount' => 3.00, // 3 DH de frais de recharge
            'base_fee_amount' => 1.00, // 1 DH de frais de base
            'admin_fee_percentage' => 12.0, // 12%
            'integrator_fee_percentage' => 8.0, // 8%
        ]);

        // Créer la hiérarchie des utilisateurs
        $admin = User::create([
            'name' => 'ALAA',
            'email' => 'alaa@admin.com',
            'password' => Hash::make('password'),
            'balance' => 0.00,
            'currency' => 'MAD',
        ]);
        $admin->assignRole('admin');

        $integrator = User::create([
            'name' => 'MEHDI',
            'email' => 'mehdi@integrator.com',
            'password' => Hash::make('password'),
            'balance' => 0.00,
            'currency' => 'MAD',
            'created_by' => $admin->id,
        ]);
        $integrator->assignRole('integrator');

        $operator = User::create([
            'name' => 'ANAS',
            'email' => 'anas@operator.com',
            'password' => Hash::make('password'),
            'balance' => 200.00,
            'currency' => 'MAD',
            'created_by' => $integrator->id,
        ]);
        $operator->assignRole('partner');

        // Créer une borne avec le business profile
        $chargingPoint = ChargingPoint::create([
            'name' => 'Borne Test',
            'user_id' => $operator->id,
            'business_profile_id' => $businessProfile->id,
            'status' => 'online',
            'max_power' => 22.0,
            'serial_number' => 'TEST-001',
            'latitude' => 33.5731,
            'longitude' => -7.5898,
        ]);

        // Créer une transaction
        $transaction = Transaction::create([
            'user_id' => $operator->id,
            'charging_point_id' => $chargingPoint->id,
            'amount' => 200.00,
            'price_total' => 200.00,
            'price_energy' => 180.00,
            'price_time' => 20.00,
            'price_service' => 0.00,
            'price_tax' => 0.00,
            'currency' => 'MAD',
            'status' => 'completed',
            'payment_status' => 'paid',
            'energy_delivered' => 10.0,
            'duration' => 30,
            'start_timestamp' => now()->subMinutes(30),
            'stop_timestamp' => now(),
        ]);

        // Traiter la transaction
        $result = $this->transactionService->processTransaction($transaction);

        // Vérifications
        $this->assertTrue($result['success']);
        
        $transactionDetail = $result['transaction_detail'];
        
        // Vérifier les frais calculés basés sur le business profile
        $expectedFees = 2.00 + 3.00 + 1.00; // 6 DH total
        $this->assertEquals($expectedFees, $transactionDetail->transaction_fee_total);
        
        // Vérifier les parts calculées
        $netAmount = 200.00 - $expectedFees; // 194 DH
        $expectedAdminShare = round($netAmount * 0.12, 2); // 12% de 194 = 23.28 DH
        $expectedIntegratorShare = round($netAmount * 0.08, 2); // 8% de 194 = 15.52 DH
        $expectedOperatorShare = $netAmount - $expectedAdminShare - $expectedIntegratorShare;
        
        $this->assertEquals($expectedAdminShare, $transactionDetail->admin_share_amount);
        $this->assertEquals($expectedIntegratorShare, $transactionDetail->integrator_share_amount);
        $this->assertEquals($expectedOperatorShare, $transactionDetail->operator_share_amount);
        
        // Vérifier que le business profile est enregistré dans les détails
        $this->assertEquals($businessProfile->id, $transactionDetail->calculation_details['business_profile_id']);
        $this->assertEquals($businessProfile->name, $transactionDetail->calculation_details['business_profile_name']);
        
        // Vérifier les transactions hiérarchiques
        $this->assertCount(2, $result['hierarchical_transactions']);
        
        $adminTransaction = $result['hierarchical_transactions'][0];
        $integratorTransaction = $result['hierarchical_transactions'][1];
        
        $this->assertEquals('admin_integrator', $adminTransaction->transaction_type);
        $this->assertEquals($expectedAdminShare, $adminTransaction->amount);
        
        $this->assertEquals('integrator_operator', $integratorTransaction->transaction_type);
        $this->assertEquals($expectedIntegratorShare, $integratorTransaction->amount);
    }

    /** @test */
    public function it_falls_back_to_integrator_business_profile_when_charging_point_has_none()
    {
        // Créer un intégrateur avec un business profile
        $integratorModel = Integrator::create([
            'name' => 'Intégrateur Test',
            'email' => 'integrator@test.com',
            'is_active' => true,
        ]);

        $businessProfile = BusinessProfile::create([
            'name' => 'Integrator Business Profile',
            'description' => 'Business profile de l\'intégrateur',
            'is_active' => true,
            'transaction_fee_amount' => 1.50,
            'transaction_fee_type' => 'fixed',
            'charge_fee_amount' => 2.50,
            'base_fee_amount' => 0.50,
            'admin_fee_percentage' => 10.0,
            'integrator_fee_percentage' => 5.0,
            'integrator_id' => $integratorModel->id,
        ]);

        // Créer les utilisateurs
        $admin = User::create([
            'name' => 'ALAA',
            'email' => 'alaa@admin.com',
            'password' => Hash::make('password'),
            'balance' => 0.00,
            'currency' => 'MAD',
        ]);
        $admin->assignRole('admin');

        $integrator = User::create([
            'name' => 'MEHDI',
            'email' => 'mehdi@integrator.com',
            'password' => Hash::make('password'),
            'balance' => 0.00,
            'currency' => 'MAD',
            'created_by' => $admin->id,
            'integrator_id' => $integratorModel->id,
        ]);
        $integrator->assignRole('integrator');

        $operator = User::create([
            'name' => 'ANAS',
            'email' => 'anas@operator.com',
            'password' => Hash::make('password'),
            'balance' => 200.00,
            'currency' => 'MAD',
            'created_by' => $integrator->id,
        ]);
        $operator->assignRole('partner');

        // Créer une borne SANS business profile direct
        $chargingPoint = ChargingPoint::create([
            'name' => 'Borne Test',
            'user_id' => $operator->id,
            'integrator_id' => $integratorModel->id, // Lier à l'intégrateur
            'status' => 'online',
            'max_power' => 22.0,
            'serial_number' => 'TEST-002',
            'latitude' => 33.5731,
            'longitude' => -7.5898,
        ]);

        // Créer une transaction
        $transaction = Transaction::create([
            'user_id' => $operator->id,
            'charging_point_id' => $chargingPoint->id,
            'amount' => 100.00,
            'price_total' => 100.00,
            'currency' => 'MAD',
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        // Traiter la transaction
        $result = $this->transactionService->processTransaction($transaction);

        // Vérifications
        $this->assertTrue($result['success']);
        
        $transactionDetail = $result['transaction_detail'];
        
        // Vérifier que le business profile de l'intégrateur est utilisé
        $this->assertEquals($businessProfile->id, $transactionDetail->calculation_details['business_profile_id']);
        
        // Vérifier les frais calculés
        $expectedFees = 1.50 + 2.50 + 0.50; // 4.5 DH
        $this->assertEquals($expectedFees, $transactionDetail->transaction_fee_total);
    }

    /** @test */
    public function it_throws_exception_when_no_business_profile_found()
    {
        // Créer les utilisateurs sans business profile
        $admin = User::create([
            'name' => 'ALAA',
            'email' => 'alaa@admin.com',
            'password' => Hash::make('password'),
            'balance' => 0.00,
            'currency' => 'MAD',
        ]);
        $admin->assignRole('admin');

        $integrator = User::create([
            'name' => 'MEHDI',
            'email' => 'mehdi@integrator.com',
            'password' => Hash::make('password'),
            'balance' => 0.00,
            'currency' => 'MAD',
            'created_by' => $admin->id,
        ]);
        $integrator->assignRole('integrator');

        $operator = User::create([
            'name' => 'ANAS',
            'email' => 'anas@operator.com',
            'password' => Hash::make('password'),
            'balance' => 200.00,
            'currency' => 'MAD',
            'created_by' => $integrator->id,
        ]);
        $operator->assignRole('partner');

        // Créer une borne SANS business profile
        $chargingPoint = ChargingPoint::create([
            'name' => 'Borne Test',
            'user_id' => $operator->id,
            'status' => 'online',
            'max_power' => 22.0,
            'serial_number' => 'TEST-003',
            'latitude' => 33.5731,
            'longitude' => -7.5898,
        ]);

        // Créer une transaction
        $transaction = Transaction::create([
            'user_id' => $operator->id,
            'charging_point_id' => $chargingPoint->id,
            'amount' => 100.00,
            'price_total' => 100.00,
            'currency' => 'MAD',
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        // Traiter la transaction - doit échouer
        $result = $this->transactionService->processTransaction($transaction);

        // Vérifications
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Aucun business profile trouvé', $result['error']);
    }
}
