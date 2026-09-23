<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChargingSession;
use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use App\Services\ChargingSessionCostService;
use App\Services\ChargingSessionValidationService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class ChargingSessionPrepaidPostpaidTest extends TestCase
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
    public function can_create_prepaid_session()
    {
        $user = User::factory()->create();
        $user->creditWallet(100, 'Initial credit');
        
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS123',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'mode' => 'prepaid',
            'estimated_cost' => 50.00,
            'status' => 'pending',
        ]);

        $this->assertTrue($session->isPrepaid());
        $this->assertFalse($session->isPostpaid());
        $this->assertEquals(50.00, $session->estimated_cost);
    }

    /** @test */
    public function can_create_postpaid_session()
    {
        $user = User::factory()->create();
        $user->creditWallet(100, 'Initial credit');
        
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS124',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'mode' => 'postpaid',
            'min_threshold' => 10.00,
            'status' => 'pending',
        ]);

        $this->assertTrue($session->isPostpaid());
        $this->assertFalse($session->isPrepaid());
        $this->assertEquals(10.00, $session->min_threshold);
    }

    /** @test */
    public function prepaid_session_validates_wallet_balance()
    {
        $user = User::factory()->create();
        $user->creditWallet(30, 'Initial credit');
        
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS125',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'mode' => 'prepaid',
            'estimated_cost' => 50.00,
            'status' => 'pending',
        ]);

        // Should fail validation - insufficient balance
        $this->assertFalse($session->validatePrepaidWallet());
        $this->assertFalse($session->wallet_validation_passed);
    }

    /** @test */
    public function prepaid_session_passes_validation_with_sufficient_balance()
    {
        $user = User::factory()->create();
        $user->creditWallet(100, 'Initial credit');
        
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS126',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'mode' => 'prepaid',
            'estimated_cost' => 50.00,
            'status' => 'pending',
        ]);

        // Should pass validation - sufficient balance
        $this->assertTrue($session->validatePrepaidWallet());
        $this->assertTrue($session->wallet_validation_passed);
    }

    /** @test */
    public function postpaid_session_validates_minimum_threshold()
    {
        $user = User::factory()->create();
        $user->creditWallet(5, 'Initial credit');
        
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS127',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'mode' => 'postpaid',
            'min_threshold' => 10.00,
            'status' => 'pending',
        ]);

        // Should fail validation - insufficient balance for threshold
        $this->assertFalse($session->validatePostpaidWallet());
        $this->assertFalse($session->wallet_validation_passed);
    }

    /** @test */
    public function postpaid_session_passes_validation_with_sufficient_threshold()
    {
        $user = User::factory()->create();
        $user->creditWallet(20, 'Initial credit');
        
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS128',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'mode' => 'postpaid',
            'min_threshold' => 10.00,
            'status' => 'pending',
        ]);

        // Should pass validation - sufficient balance for threshold
        $this->assertTrue($session->validatePostpaidWallet());
        $this->assertTrue($session->wallet_validation_passed);
    }

    /** @test */
    public function prepaid_session_processes_payment_correctly()
    {
        $user = User::factory()->create();
        $user->creditWallet(100, 'Initial credit');
        
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS129',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'mode' => 'prepaid',
            'estimated_cost' => 50.00,
            'status' => 'pending',
        ]);

        // Validate and process payment
        $this->assertTrue($session->validatePrepaidWallet());
        $this->assertTrue($session->processPrepaidPayment());
        
        // Check wallet balance
        $this->assertEquals(50, $user->getWalletBalance()); // 100 - 50 = 50
        
        // Check session status
        $this->assertEquals('paid', $session->fresh()->payment_status);
        $this->assertEquals(50.00, $session->fresh()->prepaid_amount);
    }

    /** @test */
    public function prepaid_session_processes_refund_correctly()
    {
        $user = User::factory()->create();
        $user->creditWallet(100, 'Initial credit');
        
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS130',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'mode' => 'prepaid',
            'estimated_cost' => 50.00,
            'prepaid_amount' => 50.00,
            'cost' => 30.00, // Actual cost less than estimated
            'status' => 'completed',
        ]);

        // Process refund
        $this->assertTrue($session->processPrepaidRefund());
        
        // Check wallet balance (should be 100 - 50 + 20 = 70)
        $this->assertEquals(70, $user->getWalletBalance());
        
        // Check session status
        $this->assertEquals('refunded', $session->fresh()->payment_status);
        $this->assertEquals(20.00, $session->fresh()->refund_amount);
    }

    /** @test */
    public function postpaid_session_processes_payment_at_end()
    {
        $user = User::factory()->create();
        $user->creditWallet(100, 'Initial credit');
        
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS131',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'mode' => 'postpaid',
            'min_threshold' => 10.00,
            'cost' => 25.00,
            'status' => 'completed',
        ]);

        // Process postpaid payment
        $this->assertTrue($session->processPostpaidPayment());
        
        // Check wallet balance
        $this->assertEquals(75, $user->getWalletBalance()); // 100 - 25 = 75
        
        // Check session status
        $this->assertEquals('paid', $session->fresh()->payment_status);
    }

    /** @test */
    public function session_cost_calculation_works_correctly()
    {
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS132',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => User::factory()->create()->id,
            'mode' => 'prepaid',
            'energy_consumed' => 15.5,
            'duration' => 30,
            'status' => 'completed',
        ]);

        // Update session costs
        $this->assertTrue(ChargingSessionCostService::updateSessionCosts($session));
        
        // Check that cost was calculated
        $this->assertGreaterThan(0, $session->fresh()->cost);
    }

    /** @test */
    public function session_validation_service_works_correctly()
    {
        $user = User::factory()->create();
        $user->creditWallet(100, 'Initial credit');
        
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS133',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'mode' => 'prepaid',
            'estimated_cost' => 50.00,
            'status' => 'pending',
        ]);

        // Validate session start
        $validation = ChargingSessionValidationService::validateSessionStart($session);
        
        $this->assertTrue($validation['can_start']);
        $this->assertEmpty($validation['errors']);
        $this->assertEquals(100, $validation['wallet_balance']);
        $this->assertEquals(50, $validation['required_amount']);
    }

    /** @test */
    public function session_validation_fails_with_insufficient_balance()
    {
        $user = User::factory()->create();
        $user->creditWallet(20, 'Initial credit');
        
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS134',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'mode' => 'prepaid',
            'estimated_cost' => 50.00,
            'status' => 'pending',
        ]);

        // Validate session start
        $validation = ChargingSessionValidationService::validateSessionStart($session);
        
        $this->assertFalse($validation['can_start']);
        $this->assertNotEmpty($validation['errors']);
        $this->assertStringContains('Insufficient balance', $validation['errors'][0]);
    }

    /** @test */
    public function session_can_be_started_and_ended()
    {
        $user = User::factory()->create();
        $user->creditWallet(100, 'Initial credit');
        
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS135',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'mode' => 'prepaid',
            'estimated_cost' => 50.00,
            'cost' => 30.00,
            'status' => 'pending',
        ]);

        // Start session
        $this->assertTrue($session->canBeStarted());
        $this->assertTrue($session->startSession());
        
        // Check session status
        $this->assertEquals('in_progress', $session->fresh()->status);
        $this->assertNotNull($session->fresh()->started_at);
        
        // End session
        $this->assertTrue($session->endSession());
        
        // Check session status
        $this->assertEquals('completed', $session->fresh()->status);
        $this->assertNotNull($session->fresh()->ended_at);
    }

    /** @test */
    public function cost_breakdown_calculation_works()
    {
        $chargingPoint = $this->createChargingPoint();
        
        $session = ChargingSession::create([
            'session_id' => 'SESS136',
            'charging_point_id' => $chargingPoint->id,
            'user_id' => User::factory()->create()->id,
            'mode' => 'prepaid',
            'energy_consumed' => 15.5,
            'duration' => 30,
            'cost' => 25.00,
            'status' => 'completed',
        ]);

        $breakdown = ChargingSessionCostService::getCostBreakdown($session);
        
        $this->assertArrayHasKey('base_cost', $breakdown);
        $this->assertArrayHasKey('energy_cost', $breakdown);
        $this->assertArrayHasKey('time_cost', $breakdown);
        $this->assertArrayHasKey('total_cost', $breakdown);
        $this->assertEquals(25.00, $breakdown['total_cost']);
    }

    /** @test */
    public function refund_amount_calculation_works()
    {
        $session = ChargingSession::create([
            'session_id' => 'SESS137',
            'charging_point_id' => 1,
            'user_id' => 1,
            'mode' => 'prepaid',
            'prepaid_amount' => 50.00,
            'cost' => 30.00,
            'status' => 'completed',
        ]);

        $refundAmount = ChargingSessionCostService::calculateRefundAmount($session);
        
        $this->assertEquals(20.00, $refundAmount);
    }

    /** @test */
    public function user_eligibility_check_works()
    {
        $user = User::factory()->create();
        $user->creditWallet(50, 'Initial credit');
        
        $eligibility = ChargingSessionValidationService::getUserSessionEligibility($user);
        
        $this->assertEquals($user->id, $eligibility['user_id']);
        $this->assertEquals(50, $eligibility['wallet_balance']);
        $this->assertTrue($eligibility['can_start_prepaid']);
        $this->assertTrue($eligibility['can_start_postpaid']);
    }

    /**
     * Create a charging point with business profile and pricing plan
     */
    protected function createChargingPoint(): ChargingPoint
    {
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

        $pricingPlan = PricingPlan::create([
            'name' => 'Test Pricing Plan',
            'energy_price_per_kwh' => 0.30,
            'time_price_per_minute' => 0.05,
            'session_price' => 1.00,
            'currency' => 'EUR',
        ]);

        $businessProfile = BusinessProfile::create([
            'name' => 'Test Business Profile',
            'integrator_id' => $integrator->id,
            'pricing_plan_id' => $pricingPlan->id,
            'admin_fee_percentage' => 5,
            'integrator_fee_percentage' => 10,
            'partner_fee_percentage' => 15,
        ]);

        return ChargingPoint::create([
            'name' => 'Test Charging Point',
            'serial_number' => 'CP001',
            'group_id' => $group->id,
            'business_profile_id' => $businessProfile->id,
            'status' => 'online',
        ]);
    }
}
