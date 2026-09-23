<?php

namespace Tests\Unit\Services;

use App\Services\SubscriptionSessionService;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use App\Models\ChargingSession;
use App\Models\ChargingPoint;
use App\Enums\SubscriptionStatus;
use App\Enums\SubscriptionPlanType;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class SubscriptionSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionSessionService $service;
    protected $mockMoneyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockMoneyService = Mockery::mock(MoneyService::class);

        $this->service = new SubscriptionSessionService(
            $this->mockMoneyService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_check_if_user_can_start_session_with_active_subscription()
    {
        // Arrange
        $user = User::factory()->create();
        
        $plan = SubscriptionPlan::factory()->create([
            'type' => SubscriptionPlanType::PER_SESSION->value,
            'max_sessions' => 10,
            'max_kwh' => 100.00,
            'max_duration_minutes' => 600,
        ]);

        $subscription = UserSubscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'sessions_used' => 5,
            'end_date' => now()->addMonth(),
        ]);

        // Act
        $result = $this->service->canStartSession($user);

        // Assert
        $this->assertTrue($result['can_start']);
        $this->assertEquals('no_active_subscription', $result['reason'] ?? '');
    }

    /** @test */
    public function it_blocks_session_when_sessions_limit_reached()
    {
        // Arrange
        $user = User::factory()->create();
        
        $plan = SubscriptionPlan::factory()->create([
            'type' => SubscriptionPlanType::PER_SESSION->value,
            'max_sessions' => 5,
            'max_kwh' => 100.00,
            'max_duration_minutes' => 600,
        ]);

        $subscription = UserSubscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'sessions_used' => 5, // Already at limit
            'end_date' => now()->addMonth(),
        ]);

        // Act
        $result = $this->service->canStartSession($user);

        // Assert
        $this->assertFalse($result['can_start']);
        $this->assertEquals('session_limit_reached', $result['reason']);
    }

    /** @test */
    public function it_blocks_session_when_subscription_expired()
    {
        // Arrange
        $user = User::factory()->create();
        
        $plan = SubscriptionPlan::factory()->create([
            'type' => SubscriptionPlanType::PER_SESSION->value,
            'max_sessions' => 10,
        ]);

        $subscription = UserSubscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'sessions_used' => 3,
            'end_date' => now()->subDay(), // Expired yesterday
        ]);

        // Act
        $result = $this->service->canStartSession($user);

        // Assert
        $this->assertFalse($result['can_start']);
        $this->assertEquals('subscription_inactive', $result['reason']);
    }

    /** @test */
    public function it_returns_no_subscription_for_user_without_one()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->service->canStartSession($user);

        // Assert
        $this->assertFalse($result['can_start']);
        $this->assertEquals('no_active_subscription', $result['reason']);
    }

    /** @test */
    public function it_can_start_session_and_increment_counter()
    {
        // Arrange
        $user = User::factory()->create();
        
        $plan = SubscriptionPlan::factory()->create([
            'type' => SubscriptionPlanType::PER_SESSION->value,
            'max_sessions' => 10,
            'max_kwh' => 100.00,
            'max_duration_minutes' => 600,
        ]);

        $subscription = UserSubscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'sessions_used' => 2,
            'end_date' => now()->addMonth(),
        ]);

        $chargingPoint = ChargingPoint::factory()->create();
        $session = ChargingSession::factory()->create([
            'user_id' => $user->id,
            'charging_point_id' => $chargingPoint->id,
        ]);

        // Act
        $result = $this->service->startSessionWithSubscription($user, $session);

        // Assert
        $this->assertEquals($subscription->id, $result->user_subscription_id);
        $this->assertTrue($result->subscription_session_counted);
        
        $subscription->refresh();
        $this->assertEquals(3, $subscription->sessions_used);
    }

    /** @test */
    public function it_can_finalize_session_and_log_usage()
    {
        // Arrange
        $user = User::factory()->create();
        
        $plan = SubscriptionPlan::factory()->create([
            'type' => SubscriptionPlanType::PER_SESSION->value,
            'max_sessions' => 10,
            'max_kwh' => 100.00,
            'max_duration_minutes' => 600,
        ]);

        $subscription = UserSubscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'sessions_used' => 3,
            'kwh_used' => 20.00,
            'duration_minutes_used' => 120,
            'end_date' => now()->addMonth(),
        ]);

        $session = ChargingSession::factory()->create([
            'user_id' => $user->id,
            'user_subscription_id' => $subscription->id,
            'subscription_session_counted' => true,
        ]);

        // Act
        $result = $this->service->finalizeSession(
            $session,
            15.50, // energy kWh
            45     // duration minutes
        );

        // Assert
        $this->assertTrue($result['success']);
        
        $subscription->refresh();
        $this->assertEquals(35.50, $subscription->kwh_used); // 20 + 15.50
        $this->assertEquals(165, $subscription->duration_minutes_used); // 120 + 45
    }

    /** @test */
    public function it_handles_quota_exhaustion_correctly()
    {
        // Arrange
        $user = User::factory()->create();
        
        $plan = SubscriptionPlan::factory()->create([
            'type' => SubscriptionPlanType::PER_SESSION->value,
            'max_sessions' => 5,
            'max_kwh' => 100.00,
            'max_duration_minutes' => 600,
        ]);

        $subscription = UserSubscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'sessions_used' => 5, // At limit
            'kwh_used' => 100.00, // At limit
            'duration_minutes_used' => 600, // At limit
            'end_date' => now()->addMonth(),
        ]);

        $session = ChargingSession::factory()->create([
            'user_id' => $user->id,
            'user_subscription_id' => $subscription->id,
            'subscription_session_counted' => true,
        ]);

        // Act
        $result = $this->service->finalizeSession($session, 5.00, 30);

        // Assert
        $this->assertTrue($result['quota_exhausted']);
        $this->assertTrue($result['quota_status']['exhausted']);
        
        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::EXPIRED->value, $subscription->status);
    }

    /** @test */
    public function it_returns_unlimited_sessions_for_plan_without_limit()
    {
        // Arrange
        $user = User::factory()->create();
        
        $plan = SubscriptionPlan::factory()->create([
            'type' => SubscriptionPlanType::MONTHLY->value,
            'max_sessions' => null, // Unlimited
            'duration_months' => 1,
        ]);

        $subscription = UserSubscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'sessions_used' => 10,
            'end_date' => now()->addMonth(),
        ]);

        // Act
        $result = $this->service->canStartSession($user);

        // Assert
        $this->assertTrue($result['can_start']);
    }

    /** @test */
    public function it_can_get_remaining_sessions_count()
    {
        // Arrange
        $user = User::factory()->create();
        
        $plan = SubscriptionPlan::factory()->create([
            'max_sessions' => 10,
        ]);

        $subscription = UserSubscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'sessions_used' => 7,
            'end_date' => now()->addMonth(),
        ]);

        // Act
        $remaining = $this->service->getRemainingSessions($user);

        // Assert
        $this->assertEquals(3, $remaining);
    }

    /** @test */
    public function it_returns_zero_for_user_without_subscription()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $remaining = $this->service->getRemainingSessions($user);

        // Assert
        $this->assertEquals(0, $remaining);
    }

    /** @test */
    public function it_can_get_usage_summary()
    {
        // Arrange
        $user = User::factory()->create();
        
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Premium Plan',
            'type' => SubscriptionPlanType::PER_SESSION->value,
            'max_sessions' => 10,
            'max_kwh' => 100.00,
            'max_duration_minutes' => 600,
        ]);

        UserSubscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'sessions_used' => 3,
            'kwh_used' => 25.50,
            'duration_minutes_used' => 150,
            'end_date' => now()->addMonth(),
        ]);

        // Act
        $summary = $this->service->getUsageSummary($user);

        // Assert
        $this->assertCount(1, $summary);
        $this->assertEquals('Premium Plan', $summary[0]['plan_name']);
        $this->assertEquals(3, $summary[0]['sessions']['used']);
        $this->assertEquals(7, $summary[0]['sessions']['remaining']);
        $this->assertEquals(25.50, $summary[0]['kwh']['used']);
        $this->assertEquals(74.50, $summary[0]['kwh']['remaining']);
    }
}
