<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\BusinessProfile;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'integrator']);
        Role::create(['name' => 'partner']);
        Role::create(['name' => 'operator']);
    }

    /** @test */
    public function can_identify_complete_hierarchy()
    {
        // Create hierarchy
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $group = Group::create([
            'name' => 'Test Group',
            'description' => 'Test Description',
            'partner_id' => $partner->id,
            'user_id' => $admin->id
        ]);

        $businessProfile = BusinessProfile::create([
            'name' => 'Test Business Profile',
            'integrator_id' => $integrator->id,
            'admin_fee_percentage' => 5,
            'integrator_fee_percentage' => 10,
            'partner_fee_percentage' => 15,
        ]);

        $chargingPoint = ChargingPoint::create([
            'name' => 'Test Charging Point',
            'serial_number' => 'CP001',
            'group_id' => $group->id,
            'business_profile_id' => $businessProfile->id,
            'status' => 'online',
        ]);

        $operator = User::create([
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id
        ]);
        $operator->assignRole('operator');

        $hierarchy = TransactionService::identifyHierarchy($chargingPoint->id);

        $this->assertInstanceOf(ChargingPoint::class, $hierarchy['charging_point']);
        $this->assertInstanceOf(Group::class, $hierarchy['group']);
        $this->assertInstanceOf(Partner::class, $hierarchy['partner']);
        $this->assertInstanceOf(Integrator::class, $hierarchy['integrator']);
        $this->assertInstanceOf(User::class, $hierarchy['operator']);
        $this->assertInstanceOf(BusinessProfile::class, $hierarchy['business_profile']);
    }

    /** @test */
    public function can_calculate_pricing_based_on_business_profile()
    {
        $businessProfile = BusinessProfile::create([
            'name' => 'Test Business Profile',
            'admin_fee_percentage' => 5,
            'admin_fee_fixed' => 1,
            'integrator_fee_percentage' => 10,
            'integrator_fee_fixed' => 2,
            'partner_fee_percentage' => 15,
            'partner_fee_fixed' => 3,
            'currency' => 'EUR',
        ]);

        $hierarchy = [
            'business_profile' => $businessProfile,
        ];

        $transactionData = [
            'amount' => 100,
            'energy_delivered' => 15.5,
            'duration_minutes' => 30,
        ];

        $pricing = TransactionService::calculatePricing($hierarchy, $transactionData);

        $this->assertEquals(100, $pricing['base_amount']);
        $this->assertEquals(15.5, $pricing['energy_delivered']);
        $this->assertEquals(30, $pricing['duration_minutes']);
        $this->assertEquals('EUR', $pricing['currency']);

        // Calculate expected fees
        $expectedAdminFee = 1 + (100 * 5 / 100); // 1 + 5 = 6
        $expectedIntegratorFee = 2 + (100 * 10 / 100); // 2 + 10 = 12
        $expectedPartnerFee = 3 + (100 * 15 / 100); // 3 + 15 = 18

        $this->assertEquals($expectedAdminFee, $pricing['admin_fee']);
        $this->assertEquals($expectedIntegratorFee, $pricing['integrator_fee']);
        $this->assertEquals($expectedPartnerFee, $pricing['partner_fee']);

        $expectedTotalFees = $expectedAdminFee + $expectedIntegratorFee + $expectedPartnerFee;
        $this->assertEquals($expectedTotalFees, $pricing['total_fees']);
        $this->assertEquals(100 - $expectedTotalFees, $pricing['user_amount']);
    }

    /** @test */
    public function can_process_complete_charging_transaction()
    {
        // Create hierarchy
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $group = Group::create([
            'name' => 'Test Group',
            'description' => 'Test Description',
            'partner_id' => $partner->id,
            'user_id' => $admin->id
        ]);

        $businessProfile = BusinessProfile::create([
            'name' => 'Test Business Profile',
            'integrator_id' => $integrator->id,
            'admin_fee_percentage' => 5,
            'integrator_fee_percentage' => 10,
            'partner_fee_percentage' => 15,
        ]);

        $chargingPoint = ChargingPoint::create([
            'name' => 'Test Charging Point',
            'serial_number' => 'CP001',
            'group_id' => $group->id,
            'business_profile_id' => $businessProfile->id,
            'status' => 'online',
        ]);

        $user = User::factory()->create();
        $user->creditWallet(200, 'Initial credit');

        $transactionData = [
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'amount' => 100,
            'session_id' => 'SESS123',
            'energy_delivered' => 15.5,
            'duration_minutes' => 30,
        ];

        $result = TransactionService::processChargingTransaction($transactionData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertArrayHasKey('hierarchy', $result);
        $this->assertArrayHasKey('pricing', $result);
        $this->assertArrayHasKey('debit_results', $result);

        // Check user wallet was debited
        $this->assertEquals(100, $user->getWalletBalance()); // 200 - 100 = 100

        // Check admin wallet was credited
        $adminWallet = $admin->getOrCreateWallet();
        $this->assertGreaterThan(0, $adminWallet->balance);

        // Check integrator wallet was credited
        $integratorWallet = $integrator->getOrCreateWallet();
        $this->assertGreaterThan(0, $integratorWallet->balance);

        // Check partner wallet was credited
        $partnerWallet = $partner->getOrCreateWallet();
        $this->assertGreaterThan(0, $partnerWallet->balance);
    }

    /** @test */
    public function transaction_fails_with_insufficient_balance()
    {
        // Create hierarchy
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $group = Group::create([
            'name' => 'Test Group',
            'description' => 'Test Description',
            'partner_id' => $partner->id,
            'user_id' => $admin->id
        ]);

        $businessProfile = BusinessProfile::create([
            'name' => 'Test Business Profile',
            'integrator_id' => $integrator->id,
            'admin_fee_percentage' => 5,
            'integrator_fee_percentage' => 10,
            'partner_fee_percentage' => 15,
        ]);

        $chargingPoint = ChargingPoint::create([
            'name' => 'Test Charging Point',
            'serial_number' => 'CP001',
            'group_id' => $group->id,
            'business_profile_id' => $businessProfile->id,
            'status' => 'online',
        ]);

        $user = User::factory()->create();
        // User has no wallet balance

        $transactionData = [
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'amount' => 100,
            'session_id' => 'SESS123',
        ];

        $result = TransactionService::processChargingTransaction($transactionData);

        $this->assertFalse($result['success']);
        $this->assertStringContains('Insufficient balance', $result['error']);
    }

    /** @test */
    public function can_validate_transaction_data()
    {
        // Valid data
        $validData = [
            'charging_point_id' => 1,
            'user_id' => 1,
            'amount' => 100,
        ];

        $errors = TransactionService::validateTransactionData($validData);
        $this->assertEmpty($errors);

        // Missing required fields
        $invalidData = [
            'charging_point_id' => 1,
            // Missing user_id and amount
        ];

        $errors = TransactionService::validateTransactionData($invalidData);
        $this->assertNotEmpty($errors);
        $this->assertContains("Field 'user_id' is required", $errors);
        $this->assertContains("Field 'amount' is required", $errors);

        // Invalid amount
        $invalidAmountData = [
            'charging_point_id' => 1,
            'user_id' => 1,
            'amount' => -50,
        ];

        $errors = TransactionService::validateTransactionData($invalidAmountData);
        $this->assertContains('Amount must be a positive number', $errors);
    }

    /** @test */
    public function can_get_user_transaction_history()
    {
        $user = User::factory()->create();
        $user->creditWallet(100, 'Test credit');
        $user->debitWallet(50, 'Test debit');

        $history = TransactionService::getUserTransactionHistory($user->id, 10);

        $this->assertIsArray($history);
        $this->assertCount(2, $history); // One credit, one debit

        $creditTransaction = collect($history)->firstWhere('type', 'credit');
        $debitTransaction = collect($history)->firstWhere('type', 'debit');

        $this->assertNotNull($creditTransaction);
        $this->assertNotNull($debitTransaction);
        $this->assertEquals(100, $creditTransaction['amount']);
        $this->assertEquals(50, $debitTransaction['amount']);
    }

    /** @test */
    public function can_get_hierarchy_statistics()
    {
        // Create hierarchy
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $group = Group::create([
            'name' => 'Test Group',
            'description' => 'Test Description',
            'partner_id' => $partner->id,
            'user_id' => $admin->id
        ]);

        $businessProfile = BusinessProfile::create([
            'name' => 'Test Business Profile',
            'integrator_id' => $integrator->id,
            'admin_fee_percentage' => 5,
            'integrator_fee_percentage' => 10,
            'partner_fee_percentage' => 15,
        ]);

        $chargingPoint = ChargingPoint::create([
            'name' => 'Test Charging Point',
            'serial_number' => 'CP001',
            'group_id' => $group->id,
            'business_profile_id' => $businessProfile->id,
            'status' => 'online',
        ]);

        $hierarchy = [
            'charging_point' => $chargingPoint,
            'group' => $group,
            'partner' => $partner,
            'integrator' => $integrator,
            'operator' => null,
            'business_profile' => $businessProfile,
        ];

        $stats = TransactionService::getHierarchyStatistics($hierarchy);

        $this->assertArrayHasKey('integrator', $stats);
        $this->assertArrayHasKey('partner', $stats);
        $this->assertArrayHasKey('operator', $stats);

        $this->assertArrayHasKey('current_balance', $stats['integrator']);
        $this->assertArrayHasKey('current_balance', $stats['partner']);
    }

    /** @test */
    public function rollback_works_on_transaction_failure()
    {
        // Create hierarchy
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $group = Group::create([
            'name' => 'Test Group',
            'description' => 'Test Description',
            'partner_id' => $partner->id,
            'user_id' => $admin->id
        ]);

        $businessProfile = BusinessProfile::create([
            'name' => 'Test Business Profile',
            'integrator_id' => $integrator->id,
            'admin_fee_percentage' => 5,
            'integrator_fee_percentage' => 10,
            'partner_fee_percentage' => 15,
        ]);

        $chargingPoint = ChargingPoint::create([
            'name' => 'Test Charging Point',
            'serial_number' => 'CP001',
            'group_id' => $group->id,
            'business_profile_id' => $businessProfile->id,
            'status' => 'online',
        ]);

        $user = User::factory()->create();
        $user->creditWallet(200, 'Initial credit');

        // Mock a failure scenario by creating a charging point without business profile
        $chargingPointWithoutProfile = ChargingPoint::create([
            'name' => 'Test Charging Point 2',
            'serial_number' => 'CP002',
            'group_id' => $group->id,
            'business_profile_id' => null, // No business profile
            'status' => 'online',
        ]);

        $transactionData = [
            'charging_point_id' => $chargingPointWithoutProfile->id,
            'user_id' => $user->id,
            'amount' => 100,
            'session_id' => 'SESS123',
        ];

        $result = TransactionService::processChargingTransaction($transactionData);

        $this->assertFalse($result['success']);
        $this->assertStringContains('No business profile found', $result['error']);

        // User balance should remain unchanged due to rollback
        $this->assertEquals(200, $user->getWalletBalance());
    }

    /** @test */
    public function transaction_model_works_correctly()
    {
        $user = User::factory()->create();
        $chargingPoint = ChargingPoint::factory()->create();

        $transaction = Transaction::create([
            'transaction_id' => 'TXN123',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'session_id' => 'SESS123',
            'amount' => 100,
            'currency' => 'EUR',
            'status' => 'completed',
            'hierarchy_data' => [
                'charging_point' => $chargingPoint->id,
                'user' => $user->id,
            ],
            'pricing_data' => [
                'base_amount' => 100,
                'admin_fee' => 5,
                'integrator_fee' => 10,
                'partner_fee' => 15,
                'total_fees' => 30,
                'user_amount' => 70,
            ],
            'debit_results' => [
                'user' => ['success' => true, 'amount' => 100],
                'admin' => ['success' => true, 'amount' => 5],
                'integrator' => ['success' => true, 'amount' => 10],
                'partner' => ['success' => true, 'amount' => 15],
            ],
        ]);

        $this->assertTrue($transaction->isCompleted());
        $this->assertFalse($transaction->isFailed());
        $this->assertFalse($transaction->isPending());

        $this->assertEquals('100.00 EUR', $transaction->formatted_amount);
        $this->assertEquals('green', $transaction->status_badge_color);

        $this->assertEquals(30, $transaction->total_fees);
        $this->assertEquals(70, $transaction->user_amount);

        $this->assertStringContains('TXN123', $transaction->summary);
    }
}
