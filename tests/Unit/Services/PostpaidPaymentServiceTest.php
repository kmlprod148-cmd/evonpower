<?php

namespace Tests\Unit\Services;

use App\Services\PostpaidPaymentService;
use App\Models\User;
use App\Models\Reservation;
use App\Models\ChargingSession;
use App\Models\PricingPlan;
use App\Models\ChargingPoint;
use App\Models\BillingInvoice;
use App\Enums\PostpaidStatus;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class PostpaidPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PostpaidPaymentService $service;
    protected $mockTransactionService;
    protected $mockMoneyService;
    protected $mockBillingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockTransactionService = Mockery::mock(TransactionService::class);
        $this->mockMoneyService = Mockery::mock(MoneyService::class);
        $this->mockBillingService = Mockery::mock(BillingService::class);

        $this->service = new PostpaidPaymentService(
            $this->mockTransactionService,
            $this->mockMoneyService,
            $this->mockBillingService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_authorize_user_for_postpaid()
    {
        // Arrange
        $user = User::factory()->create([
            'postpaid_status' => 'not_authorized',
            'postpaid_credit_limit' => null,
        ]);

        $creditLimit = 500.00;

        // Act
        $result = $this->service->authorizeUser($user, $creditLimit);

        // Assert
        $this->assertEquals(PostpaidStatus::APPROVED->value, $result->postpaid_status);
        $this->assertEquals($creditLimit, $result->postpaid_credit_limit);
        $this->assertNotNull($result->postpaid_approved_at);
    }

    /** @test */
    public function it_can_check_if_user_is_authorized()
    {
        // Arrange
        $authorizedUser = User::factory()->create([
            'postpaid_status' => PostpaidStatus::APPROVED->value,
        ]);

        $nonAuthorizedUser = User::factory()->create([
            'postpaid_status' => PostpaidStatus::NOT_AUTHORIZED->value,
        ]);

        $pendingUser = User::factory()->create([
            'postpaid_status' => PostpaidStatus::PENDING->value,
        ]);

        // Act
        $authorizedResult = $this->service->isUserAuthorized($authorizedUser);
        $nonAuthorizedResult = $this->service->isUserAuthorized($nonAuthorizedUser);
        $pendingResult = $this->service->isUserAuthorized($pendingUser);

        // Assert
        $this->assertTrue($authorizedResult);
        $this->assertFalse($nonAuthorizedResult);
        $this->assertFalse($pendingResult);
    }

    /** @test */
    public function it_can_revoke_user_postpaid_authorization()
    {
        // Arrange
        $user = User::factory()->create([
            'postpaid_status' => PostpaidStatus::APPROVED->value,
            'postpaid_credit_limit' => 500.00,
        ]);

        $reason = 'Non-paiement des factures';

        // Act
        $result = $this->service->revokeUser($user, $reason);

        // Assert
        $this->assertEquals(PostpaidStatus::REVOKED->value, $result->postpaid_status);
        $this->assertNotNull($result->postpaid_revoked_at);
        $this->assertEquals($reason, $result->postpaid_revocation_reason);
    }

    /** @test */
    public function it_can_suspend_user_postpaid()
    {
        // Arrange
        $user = User::factory()->create([
            'postpaid_status' => PostpaidStatus::APPROVED->value,
        ]);

        $reason = 'Suspicion de fraude';

        // Act
        $result = $this->service->suspendUser($user, $reason);

        // Assert
        $this->assertEquals(PostpaidStatus::SUSPENDED->value, $result->postpaid_status);
        $this->assertNotNull($result->postpaid_suspended_at);
        $this->assertEquals($reason, $result->postpaid_suspension_reason);
    }

    /** @test */
    public function it_can_reactivate_suspended_user()
    {
        // Arrange
        $user = User::factory()->create([
            'postpaid_status' => PostpaidStatus::SUSPENDED->value,
            'postpaid_suspended_at' => now(),
            'postpaid_suspension_reason' => 'Test suspension',
        ]);

        // Act
        $result = $this->service->reactivateUser($user);

        // Assert
        $this->assertEquals(PostpaidStatus::APPROVED->value, $result->postpaid_status);
        $this->assertNull($result->postpaid_suspended_at);
        $this->assertNull($result->postpaid_suspension_reason);
    }

    /** @test */
    public function it_calculates_available_credit_correctly()
    {
        // Arrange
        $user = User::factory()->create([
            'postpaid_status' => PostpaidStatus::APPROVED->value,
            'postpaid_credit_limit' => 500.00,
        ]);

        // Create unpaid invoices
        BillingInvoice::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 100.00,
        ]);

        BillingInvoice::factory()->create([
            'user_id' => $user->id,
            'status' => 'overdue',
            'total_amount' => 50.00,
        ]);

        // Act
        $availableCredit = $this->service->getAvailableCredit($user);
        $currentUsage = $this->service->getCurrentUsage($user);

        // Assert
        // Available = (500 - 150) * 0.8 = 280
        $this->assertEquals(280.00, $availableCredit);
        $this->assertEquals(150.00, $currentUsage);
    }

    /** @test */
    public function it_checks_can_start_session()
    {
        // Arrange
        $user = User::factory()->create([
            'postpaid_status' => PostpaidStatus::APPROVED->value,
            'postpaid_credit_limit' => 100.00,
        ]);

        // Create unpaid invoices totaling 60
        BillingInvoice::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 60.00,
        ]);

        // With 80% safety factor: available = (100 - 60) * 0.8 = 32

        // Act - Test with estimated cost within limit
        $canStartLowCost = $this->service->canStartSession($user, 30.00);

        // Act - Test with estimated cost exceeding limit
        $canStartHighCost = $this->service->canStartSession($user, 50.00);

        // Assert
        $this->assertTrue($canStartLowCost['can_start']);
        $this->assertFalse($canStartHighCost['can_start']);
    }

    /** @test */
    public function it_throws_exception_for_unauthorized_user_starting_session()
    {
        // Arrange
        $user = User::factory()->create([
            'postpaid_status' => PostpaidStatus::NOT_AUTHORIZED->value,
        ]);

        $reservation = Reservation::factory()->create();
        $session = ChargingSession::factory()->create();

        // Expect
        $this->expectException(\App\Exceptions\PostpaidCharging\PostpaidChargingException::class);
        $this->expectExceptionMessage('Utilisateur non autorisé pour le mode postpayé');

        // Act
        $this->service->startPostpaidSession($user, $reservation, $session);
    }

    /** @test */
    public function it_throws_exception_for_insufficient_credit()
    {
        // Arrange
        $user = User::factory()->create([
            'postpaid_status' => PostpaidStatus::APPROVED->value,
            'postpaid_credit_limit' => 50.00,
        ]);

        // Create unpaid invoices totaling 40 (available = (50-40) * 0.8 = 8)
        BillingInvoice::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 40.00,
        ]);

        $reservation = Reservation::factory()->create([
            'estimated_cost' => 30.00, // More than available
        ]);

        $session = ChargingSession::factory()->create();

        // Expect
        $this->expectException(\App\Exceptions\PostpaidCharging\InsufficientBalanceException::class);

        // Act
        $this->service->startPostpaidSession($user, $reservation, $session);
    }

    /** @test */
    public function it_can_get_usage_history()
    {
        // Arrange
        $user = User::factory()->create();
        
        $session1 = ChargingSession::factory()->create([
            'user_id' => $user->id,
            'payment_method' => 'postpaid',
            'actual_cost' => 25.00,
            'actual_energy' => 15.5,
            'actual_duration' => 45,
        ]);

        $session2 = ChargingSession::factory()->create([
            'user_id' => $user->id,
            'payment_method' => 'postpaid',
            'actual_cost' => 30.00,
            'actual_energy' => 18.0,
            'actual_duration' => 60,
        ]);

        // Act
        $history = $this->service->getUsageHistory($user);

        // Assert
        $this->assertEquals(2, $history['total_sessions']);
        $this->assertEquals(33.5, $history['total_energy_kwh']);
        $this->assertEquals(55.00, $history['total_cost']);
    }

    /** @test */
    public function it_can_filter_usage_history_by_date()
    {
        // Arrange
        $user = User::factory()->create();
        
        // Session this month
        ChargingSession::factory()->create([
            'user_id' => $user->id,
            'payment_method' => 'postpaid',
            'actual_cost' => 25.00,
            'start_timestamp' => now(),
        ]);

        // Session last month
        ChargingSession::factory()->create([
            'user_id' => $user->id,
            'payment_method' => 'postpaid',
            'actual_cost' => 30.00,
            'start_timestamp' => now()->subMonth(),
        ]);

        // Act - Get this month's history only
        $history = $this->service->getUsageHistory(
            $user, 
            now()->startOfMonth(), 
            now()->endOfMonth()
        );

        // Assert
        $this->assertEquals(1, $history['total_sessions']);
        $this->assertEquals(25.00, $history['total_cost']);
    }
}
