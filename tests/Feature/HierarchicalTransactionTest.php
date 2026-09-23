<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Models\Group;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\BusinessProfile;
use App\Models\Wallet;
use App\Services\HierarchicalTransactionService;
use App\Services\MoneyService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class HierarchicalTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected $hierarchicalTransactionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'integrator']);
        Role::create(['name' => 'partner']);
        Role::create(['name' => 'operator']);

        // Initialize services
        $this->hierarchicalTransactionService = new HierarchicalTransactionService(
            new TransactionService(),
            new MoneyService()
        );
    }

    /** @test */
    public function hierarchical_transaction_processes_correctly()
    {
        // Create hierarchy
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);

        $partner = Partner::factory()->create(['integrator_id' => $integratorProfile->id]);
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'operator_id' => $operator->id
        ]);

        // Create business profiles
        $operatorProfile = BusinessProfile::factory()->create([
            'name' => 'Profil Opérateur',
            'created_by' => $integrator->id,
            'applies_to' => 'operator',
            'price_per_kwh' => 0.15,
            'price_per_hour' => 2.00,
            'is_active' => true
        ]);

        $integratorProfile = BusinessProfile::factory()->create([
            'name' => 'Profil Intégrateur',
            'created_by' => $admin->id,
            'applies_to' => 'integrator',
            'price_per_kwh' => 0.20,
            'price_per_hour' => 3.00,
            'is_active' => true
        ]);

        // Create wallets with sufficient balance
        $operatorWallet = Wallet::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'balance' => 100.00
        ]);

        $integratorWallet = Wallet::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $integrator->id,
            'balance' => 200.00
        ]);

        // Create charging session
        $session = ChargingSession::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'energy_delivered' => 10.0, // 10 kWh
            'duration' => 3600, // 1 hour
            'status' => 'completed'
        ]);

        // Process hierarchical transaction
        $result = $this->hierarchicalTransactionService->processHierarchicalTransaction($session);

        // Assertions
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('transactions', $result);
        $this->assertArrayHasKey('trace_transactions', $result);
        $this->assertArrayHasKey('amounts', $result);

        // Verify operator debit
        $this->assertArrayHasKey('operator', $result['transactions']);
        $operatorTransaction = $result['transactions']['operator'];
        $this->assertEquals('debit', $operatorTransaction->type);
        $this->assertGreaterThan(0, $operatorTransaction->amount);

        // Verify integrator debit
        $this->assertArrayHasKey('integrator', $result['transactions']);
        $integratorTransaction = $result['transactions']['integrator'];
        $this->assertEquals('debit', $integratorTransaction->type);
        $this->assertGreaterThan(0, $integratorTransaction->amount);

        // Verify amounts are different (different profiles)
        $this->assertNotEquals(
            $result['amounts']['operator'],
            $result['amounts']['integrator']
        );

        // Verify trace transactions
        $this->assertArrayHasKey('operator', $result['trace_transactions']);
        $this->assertArrayHasKey('integrator', $result['trace_transactions']);

        $operatorTrace = $result['trace_transactions']['operator'];
        $integratorTrace = $result['trace_transactions']['integrator'];

        $this->assertEquals($session->id, $operatorTrace->session_id);
        $this->assertEquals($session->id, $integratorTrace->session_id);
        $this->assertEquals($chargingPoint->id, $operatorTrace->charging_point_id);
        $this->assertEquals($chargingPoint->id, $integratorTrace->charging_point_id);
    }

    /** @test */
    public function hierarchical_transaction_fails_with_insufficient_operator_balance()
    {
        // Create hierarchy
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);

        $partner = Partner::factory()->create(['integrator_id' => $integratorProfile->id]);
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'operator_id' => $operator->id
        ]);

        // Create business profiles
        $operatorProfile = BusinessProfile::factory()->create([
            'name' => 'Profil Opérateur',
            'created_by' => $integrator->id,
            'applies_to' => 'operator',
            'price_per_kwh' => 0.15,
            'is_active' => true
        ]);

        $integratorProfile = BusinessProfile::factory()->create([
            'name' => 'Profil Intégrateur',
            'created_by' => $admin->id,
            'applies_to' => 'integrator',
            'price_per_kwh' => 0.20,
            'is_active' => true
        ]);

        // Create wallets - operator with insufficient balance
        $operatorWallet = Wallet::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'balance' => 0.50 // Insufficient
        ]);

        $integratorWallet = Wallet::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $integrator->id,
            'balance' => 200.00
        ]);

        // Create charging session
        $session = ChargingSession::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'energy_delivered' => 10.0, // 10 kWh
            'duration' => 3600,
            'status' => 'completed'
        ]);

        // Process hierarchical transaction
        $result = $this->hierarchicalTransactionService->processHierarchicalTransaction($session);

        // Should fail
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Solde insuffisant', $result['error']);
    }

    /** @test */
    public function hierarchical_transaction_fails_with_insufficient_integrator_balance()
    {
        // Create hierarchy
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);

        $partner = Partner::factory()->create(['integrator_id' => $integratorProfile->id]);
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'operator_id' => $operator->id
        ]);

        // Create business profiles
        $operatorProfile = BusinessProfile::factory()->create([
            'name' => 'Profil Opérateur',
            'created_by' => $integrator->id,
            'applies_to' => 'operator',
            'price_per_kwh' => 0.15,
            'is_active' => true
        ]);

        $integratorProfile = BusinessProfile::factory()->create([
            'name' => 'Profil Intégrateur',
            'created_by' => $admin->id,
            'applies_to' => 'integrator',
            'price_per_kwh' => 0.20,
            'is_active' => true
        ]);

        // Create wallets - integrator with insufficient balance
        $operatorWallet = Wallet::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'balance' => 100.00
        ]);

        $integratorWallet = Wallet::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $integrator->id,
            'balance' => 0.50 // Insufficient
        ]);

        // Create charging session
        $session = ChargingSession::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'energy_delivered' => 10.0, // 10 kWh
            'duration' => 3600,
            'status' => 'completed'
        ]);

        // Process hierarchical transaction
        $result = $this->hierarchicalTransactionService->processHierarchicalTransaction($session);

        // Should fail and rollback operator transaction
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Solde insuffisant', $result['error']);
    }

    /** @test */
    public function hierarchical_transaction_creates_correct_trace_transactions()
    {
        // Create hierarchy
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);

        $partner = Partner::factory()->create(['integrator_id' => $integratorProfile->id]);
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'operator_id' => $operator->id
        ]);

        // Create business profiles
        $operatorProfile = BusinessProfile::factory()->create([
            'name' => 'Profil Opérateur',
            'created_by' => $integrator->id,
            'applies_to' => 'operator',
            'price_per_kwh' => 0.15,
            'is_active' => true
        ]);

        $integratorProfile = BusinessProfile::factory()->create([
            'name' => 'Profil Intégrateur',
            'created_by' => $admin->id,
            'applies_to' => 'integrator',
            'price_per_kwh' => 0.20,
            'is_active' => true
        ]);

        // Create wallets
        $operatorWallet = Wallet::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'balance' => 100.00
        ]);

        $integratorWallet = Wallet::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $integrator->id,
            'balance' => 200.00
        ]);

        // Create charging session
        $session = ChargingSession::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'energy_delivered' => 10.0,
            'duration' => 3600,
            'status' => 'completed'
        ]);

        // Process hierarchical transaction
        $result = $this->hierarchicalTransactionService->processHierarchicalTransaction($session);

        $this->assertTrue($result['success']);

        // Verify trace transactions have correct metadata
        $operatorTrace = $result['trace_transactions']['operator'];
        $integratorTrace = $result['trace_transactions']['integrator'];

        $this->assertEquals('hierarchical_debit', $operatorTrace->type);
        $this->assertEquals('hierarchical_debit', $integratorTrace->type);
        $this->assertEquals('completed', $operatorTrace->status);
        $this->assertEquals('completed', $integratorTrace->status);
        $this->assertEquals($session->id, $operatorTrace->session_id);
        $this->assertEquals($session->id, $integratorTrace->session_id);
        $this->assertEquals($chargingPoint->id, $operatorTrace->charging_point_id);
        $this->assertEquals($chargingPoint->id, $integratorTrace->charging_point_id);

        // Verify metadata
        $this->assertEquals('operator', $operatorTrace->metadata['role']);
        $this->assertEquals('integrator', $integratorTrace->metadata['role']);
        $this->assertEquals($session->id, $operatorTrace->metadata['charging_session_id']);
        $this->assertEquals($session->id, $integratorTrace->metadata['charging_session_id']);
    }
}
