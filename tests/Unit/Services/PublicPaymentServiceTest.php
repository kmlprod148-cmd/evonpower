<?php

namespace Tests\Unit\Services;

use App\Services\PublicPaymentService;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Exceptions\ChargingPoint\ChargingPointNotFoundException;
use App\Exceptions\InsufficientBalanceException;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Illuminate\Support\Facades\Log;

class PublicPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PublicPaymentService $service;
    protected $mockQRCodeService;
    protected $mockChargingService;
    protected $mockTransactionService;
    protected $mockMoneyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockQRCodeService = Mockery::mock(ChargingPointQRCodeService::class);
        $this->mockChargingService = Mockery::mock(ChargingService::class);
        $this->mockTransactionService = Mockery::mock(TransactionService::class);
        $this->mockMoneyService = Mockery::mock(MoneyService::class);

        $this->service = new PublicPaymentService(
            $this->mockQRCodeService,
            $this->mockChargingService,
            $this->mockTransactionService,
            $this->mockMoneyService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_scan_qr_code_and_return_charging_point_info()
    {
        // Arrange
        $chargingPoint = ChargingPoint::factory()->create([
            'name' => 'Test Charging Point',
            'status' => 'available',
            'is_active' => true,
            'latitude' => 33.5731,
            'longitude' => -7.5890,
        ]);

        $pricingPlan = PricingPlan::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'name' => 'Standard Plan',
            'is_active' => true,
            'is_public' => true,
            'price_per_kwh' => 0.50,
            'price_per_minute' => 0.10,
            'activation_fee' => 1.00,
        ]);

        $qrCodeData = json_encode([
            'charging_point_id' => $chargingPoint->id,
        ]);

        // Act
        $result = $this->service->scanQRCode($qrCodeData);

        // Assert
        $this->assertEquals(1, $result['step']);
        $this->assertEquals($chargingPoint->id, $result['charging_point']['id']);
        $this->assertEquals('Test Charging Point', $result['charging_point']['name']);
        $this->assertArrayHasKey('pricing_plans', $result);
        $this->assertArrayHasKey('payment_token', $result);
        $this->assertArrayHasKey('expires_at', $result);
    }

    /** @test */
    public function it_throws_exception_for_invalid_qr_code()
    {
        // Arrange
        $invalidQRCode = 'invalid_qr_code';

        // Expect
        $this->expectException(ChargingPointNotFoundException::class);

        // Act
        $this->service->scanQRCode($invalidQRCode);
    }

    /** @test */
    public function it_throws_exception_for_non_existent_charging_point()
    {
        // Arrange
        $qrCodeData = json_encode(['charging_point_id' => 99999]);

        // Expect
        $this->expectException(ChargingPointNotFoundException::class);

        // Act
        $this->service->scanQRCode($qrCodeData);
    }

    /** @test */
    public function it_can_extract_charging_point_id_from_numeric_qr()
    {
        // Arrange
        $chargingPoint = ChargingPoint::factory()->create([
            'status' => 'available',
            'is_active' => true,
        ]);

        // Act - Test avec un ID numérique
        $result = $this->service->scanQRCode((string) $chargingPoint->id);

        // Assert
        $this->assertEquals($chargingPoint->id, $result['charging_point']['id']);
    }

    /** @test */
    public function it_can_extract_charging_point_id_from_url()
    {
        // Arrange
        $chargingPoint = ChargingPoint::factory()->create([
            'status' => 'available',
            'is_active' => true,
        ]);

        // Act - Test avec une URL
        $url = "https://evon.example.com/charging-points/{$chargingPoint->id}";
        $result = $this->service->scanQRCode($url);

        // Assert
        $this->assertEquals($chargingPoint->id, $result['charging_point']['id']);
    }

    /** @test */
    public function it_checks_user_authorization_for_postpaid()
    {
        // Arrange
        $authorizedUser = User::factory()->create([
            'postpaid_status' => 'approved',
            'postpaid_credit_limit' => 500.00,
        ]);

        $nonAuthorizedUser = User::factory()->create([
            'postpaid_status' => 'not_authorized',
            'postpaid_credit_limit' => null,
        ]);

        // Act
        $authorizedResult = $this->service->isUserAuthorizedForPostpaid($authorizedUser);
        $nonAuthorizedResult = $this->service->isUserAuthorizedForPostpaid($nonAuthorizedUser);

        // Assert
        $this->assertTrue($authorizedResult);
        $this->assertFalse($nonAuthorizedResult);
    }

    /** @test */
    public function it_validates_payment_token_format()
    {
        // Test avec un token invalide
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Token de paiement invalide');

        // Access private method through reflection
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validatePaymentToken');
        $method->setAccessible(true);

        $method->invoke($this->service, 'INVALID_TOKEN');
    }

    /** @test */
    public function it_generates_valid_payment_token()
    {
        // Arrange
        $chargingPointId = 123;

        // Access private method through reflection
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generatePaymentToken');
        $method->setAccessible(true);

        // Act
        $token = $method->invoke($this->service, $chargingPointId);

        // Assert
        $this->assertStringStartsWith('PAY_123_', $token);
        $this->assertEquals(38, strlen($token)); // PAY_ + 3 + _ + 32 random
    }

    /** @test */
    public function it_checks_availability_of_charging_point()
    {
        // Arrange
        $availableChargingPoint = ChargingPoint::factory()->create([
            'status' => 'available',
            'is_active' => true,
        ]);

        $unavailableChargingPoint = ChargingPoint::factory()->create([
            'status' => 'offline',
            'is_active' => true,
        ]);

        $inactiveChargingPoint = ChargingPoint::factory()->create([
            'status' => 'available',
            'is_active' => false,
        ]);

        // Access private method through reflection
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('isChargingPointAvailable');
        $method->setAccessible(true);

        // Act
        $availableResult = $method->invoke($this->service, $availableChargingPoint);
        $unavailableResult = $method->invoke($this->service, $unavailableChargingPoint);
        $inactiveResult = $method->invoke($this->service, $inactiveChargingPoint);

        // Assert
        $this->assertTrue($availableResult);
        $this->assertFalse($unavailableResult);
        $this->assertFalse($inactiveResult);
    }
}
