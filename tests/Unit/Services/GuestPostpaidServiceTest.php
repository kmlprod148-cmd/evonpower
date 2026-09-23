<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\GuestPostpaidService;
use App\Models\GuestPaymentMethod;
use App\Models\ChargingSession;
use App\Models\Reservation;
use App\Services\PaymentGatewayService;
use App\Services\Gateways\Contracts\GatewayInterface;
use Mockery;
use Exception;

/**
 * Unit tests for GuestPostpaidService
 * 
 * Tests the guest postpaid payment service that handles:
 * - Creating authorization holds on payment methods
 * - Capturing payments after charging sessions end
 * - Cancelling authorization holds
 * - Handling payment method storage for guests
 */
class GuestPostpaidServiceTest extends TestCase
{
    protected GuestPostpaidService $service;
    protected $mockGateway;
    protected $mockPaymentGatewayService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the payment gateway
        $this->mockGateway = Mockery::mock(GatewayInterface::class);
        
        // Mock the PaymentGatewayService
        $this->mockPaymentGatewayService = Mockery::mock(PaymentGatewayService::class);
        $this->mockPaymentGatewayService->shouldReceive('resolveGateway')
            ->andReturn($this->mockGateway);
        
        $this->service = new GuestPostpaidService($this->mockPaymentGatewayService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_authorization_hold_successfully()
    {
        // Arrange
        $reservation = Mockery::mock(Reservation::class);
        $reservation->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $reservation->shouldReceive('getAttribute')->with('charge_point_id')->andReturn(1);
        
        $paymentMethodId = 'pm_test_123';
        $amount = 500.00;
        $currency = 'MAD';
        $metadata = ['test' => 'data'];

        // Mock gateway authorization
        $this->mockGateway->shouldReceive('authorize')
            ->once()
            ->with($amount, $currency, Mockery::on(function($meta) use ($metadata) {
                return isset($meta['reservation_id']) && isset($meta['payment_method_id']);
            }))
            ->andReturn([
                'success' => true,
                'authorization_id' => 'auth_123456',
                'transaction_id' => 'ch_123456'
            ]);

        // Act
        $result = $this->service->createAuthorization(
            $reservation,
            $amount,
            $currency,
            $paymentMethodId,
            $metadata
        );

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('auth_123456', $result['authorization_id']);
    }

    /** @test */
    public function it_handles_authorization_failure()
    {
        // Arrange
        $reservation = Mockery::mock(Reservation::class);
        $reservation->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $reservation->shouldReceive('getAttribute')->with('charge_point_id')->andReturn(1);
        
        $paymentMethodId = 'pm_invalid';
        $amount = 500.00;
        $currency = 'MAD';
        $metadata = ['test' => 'data'];

        // Mock gateway authorization failure
        $this->mockGateway->shouldReceive('authorize')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Card declined'
            ]);

        // Act
        $result = $this->service->createAuthorization(
            $reservation,
            $amount,
            $currency,
            $paymentMethodId,
            $metadata
        );

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Card declined', $result['error']);
    }

    /** @test */
    public function it_validates_invalid_amount_for_authorization()
    {
        // Arrange
        $reservation = Mockery::mock(Reservation::class);
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        
        // Act
        $this->service->createAuthorization(
            $reservation,
            -100, // Invalid negative amount
            'MAD',
            'pm_test',
            []
        );
    }

    /** @test */
    public function it_validates_zero_amount_for_authorization()
    {
        // Arrange
        $reservation = Mockery::mock(Reservation::class);
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        
        // Act
        $this->service->createAuthorization(
            $reservation,
            0, // Invalid zero amount
            'MAD',
            'pm_test',
            []
        );
    }

    /** @test */
    public function it_captures_payment_successfully()
    {
        // Arrange
        $session = Mockery::mock(ChargingSession::class);
        $session->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $session->shouldReceive('getAttribute')->with('authorization_hold_id')->andReturn('auth_123456');
        $session->shouldReceive('getAttribute')->with('guest_payment_method_id')->andReturn(1);
        
        $amount = 350.00;

        // Mock gateway capture
        $this->mockGateway->shouldReceive('capture')
            ->once()
            ->with('auth_123456', $amount)
            ->andReturn([
                'success' => true,
                'transaction_id' => 'ch_capture_123',
                'captured_amount' => $amount
            ]);

        // Act
        $result = $this->service->capturePayment($session, $amount);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('ch_capture_123', $result['transaction_id']);
    }

    /** @test */
    public function it_handles_capture_failure()
    {
        // Arrange
        $session = Mockery::mock(ChargingSession::class);
        $session->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $session->shouldReceive('getAttribute')->with('authorization_hold_id')->andReturn('auth_123456');
        
        $amount = 350.00;

        // Mock gateway capture failure
        $this->mockGateway->shouldReceive('capture')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Authorization expired'
            ]);

        // Act
        $result = $this->service->capturePayment($session, $amount);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Authorization expired', $result['error']);
    }

    /** @test */
    public function it_validates_missing_authorization_for_capture()
    {
        // Arrange
        $session = Mockery::mock(ChargingSession::class);
        $session->shouldReceive('getAttribute')->with('authorization_hold_id')->andReturn(null);
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        
        // Act
        $this->service->capturePayment($session, 100.00);
    }

    /** @test */
    public function it_cancels_authorization_hold_successfully()
    {
        // Arrange
        $authorizationId = 'auth_123456';

        // Mock gateway cancel
        $this->mockGateway->shouldReceive('cancelAuthorization')
            ->once()
            ->with($authorizationId)
            ->andReturn([
                'success' => true,
                'cancelled_authorization_id' => $authorizationId
            ]);

        // Act
        $result = $this->service->cancelAuthorization($authorizationId);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_handles_cancel_failure()
    {
        // Arrange
        $authorizationId = 'auth_expired';

        // Mock gateway cancel failure
        $this->mockGateway->shouldReceive('cancelAuthorization')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Authorization already captured'
            ]);

        // Act
        $result = $this->service->cancelAuthorization($authorizationId);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Authorization already captured', $result['error']);
    }

    /** @test */
    public function it_stores_payment_method_for_guest()
    {
        // Arrange
        $guestEmail = 'guest@example.com';
        $guestPhone = '+212612345678';
        $paymentMethodToken = 'pm_stripe_token';
        $cardLast4 = '4242';
        $cardBrand = 'visa';
        $expiresAt = now()->addYear();

        // Mock the gateway to return payment method info
        $this->mockGateway->shouldReceive('createPaymentMethod')
            ->once()
            ->with($paymentMethodToken, $guestEmail)
            ->andReturn([
                'success' => true,
                'payment_method_id' => 'pm_stripe_123',
                'card_last4' => $cardLast4,
                'card_brand' => $cardBrand,
                'expires_at' => $expiresAt
            ]);

        // Act
        $result = $this->service->storePaymentMethod(
            $guestEmail,
            $guestPhone,
            $paymentMethodToken
        );

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('pm_stripe_123', $result['payment_method_id']);
    }

    /** @test */
    public function it_retries_failed_capture_with_backoff()
    {
        // Arrange
        $session = Mockery::mock(ChargingSession::class);
        $session->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $session->shouldReceive('getAttribute')->with('authorization_hold_id')->andReturn('auth_123456');
        $session->shouldReceive('getAttribute')->with('guest_payment_method_id')->andReturn(1);
        
        $amount = 350.00;
        $attemptCount = 0;

        // First two attempts fail, third succeeds
        $this->mockGateway->shouldReceive('capture')
            ->times(3)
            ->andReturnUsing(function($authId, $amt) use (&$attemptCount) {
                $attemptCount++;
                if ($attemptCount < 3) {
                    return ['success' => false, 'error' => 'Temporary error'];
                }
                return ['success' => true, 'transaction_id' => 'ch_retry_123'];
            });

        // Act
        $result = $this->service->capturePaymentWithRetry($session, $amount, 3);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $attemptCount);
    }

    /** @test */
    public function it_fails_after_max_retries_exceeded()
    {
        // Arrange
        $session = Mockery::mock(ChargingSession::class);
        $session->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $session->shouldReceive('getAttribute')->with('authorization_hold_id')->andReturn('auth_123456');
        
        $amount = 350.00;

        // All attempts fail
        $this->mockGateway->shouldReceive('capture')
            ->times(3)
            ->andReturn(['success' => false, 'error' => 'Persistent error']);

        // Act
        $result = $this->service->capturePaymentWithRetry($session, $amount, 3);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Persistent error', $result['error']);
    }
}
