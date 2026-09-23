<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CashPaymentService;
use App\Services\MoneyService;
use App\Models\User;
use App\Models\Wallet;
use App\Models\CreditRecharge;
use Mockery;
use Exception;

/**
 * Unit tests for CashPaymentService
 * 
 * Tests the cash payment service that handles
 * client cash payments and credit management.
 */
class CashPaymentServiceTest extends TestCase
{
    protected CashPaymentService $service;
    protected $mockMoneyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockMoneyService = Mockery::mock(MoneyService::class);
        $this->service = new CashPaymentService($this->mockMoneyService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_validates_invalid_amount()
    {
        // Arrange
        $amount = -100;
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        
        // Act - create a mock user to test
        $mockUser = Mockery::mock(User::class);
        $this->service->createCashPayment($mockUser, $amount);
    }

    /** @test */
    public function it_validates_zero_amount()
    {
        // Arrange
        $amount = 0;
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        
        // Act
        $mockUser = Mockery::mock(User::class);
        $this->service->createCashPayment($mockUser, $amount);
    }

    /** @test */
    public function it_returns_correct_payment_method()
    {
        // Assert
        $this->assertEquals('espece_client', CashPaymentService::PAYMENT_METHOD);
        $this->assertEquals('Espèces Client', CashPaymentService::PAYMENT_METHOD_LABEL);
    }

    /** @test */
    public function it_has_correct_status_constants()
    {
        // Assert
        $this->assertEquals('pending', CashPaymentService::STATUS_PENDING);
        $this->assertEquals('completed', CashPaymentService::STATUS_COMPLETED);
        $this->assertEquals('failed', CashPaymentService::STATUS_FAILED);
        $this->assertEquals('cancelled', CashPaymentService::STATUS_CANCELLED);
    }

    /** @test */
    public function it_has_suggested_amounts()
    {
        // Assert
        $expectedAmounts = [50, 100, 200, 500, 1000];
        $this->assertEquals($expectedAmounts, CashPaymentService::SUGGESTED_AMOUNTS);
    }

    /** @test */
    public function it_validates_cash_payment_amount()
    {
        // Act
        $result = $this->service->validateCashPayment(100, 'MAD');
        
        // Assert
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_validates_negative_amount()
    {
        // Act
        $result = $this->service->validateCashPayment(-50, 'MAD');
        
        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Le montant doit être supérieur à 0', $result['errors']);
    }

    /** @test */
    public function it_warns_for_large_amounts()
    {
        // Act
        $result = $this->service->validateCashPayment(15000, 'MAD');
        
        // Assert
        $this->assertTrue($result['valid']);
        $this->assertNotEmpty($result['warnings']);
    }

    /** @test */
    public function it_rejects_invalid_currency()
    {
        // Act
        $result = $this->service->validateCashPayment(100, 'GBP');
        
        // Assert
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Devise non supportée', $result['errors'][0]);
    }

    /** @test */
    public function it_accepts_supported_currencies()
    {
        // Test MAD
        $result = $this->service->validateCashPayment(100, 'MAD');
        $this->assertTrue($result['valid']);
        
        // Test EUR
        $result = $this->service->validateCashPayment(100, 'EUR');
        $this->assertTrue($result['valid']);
        
        // Test USD
        $result = $this->service->validateCashPayment(100, 'USD');
        $this->assertTrue($result['valid']);
    }

    // =========================================================================
    // INTEGRATION NOTE
    // =========================================================================

    /*
     * Note: Full integration tests for createCashPayment, cancelCashPayment,
     * and other database-dependent methods require:
     * 
     * 1. A test database with proper schema
     * 2. Mocked User and Wallet models with proper relationships
     * 3. Transaction mocking for DB::transaction
     * 
     * These tests verify the service's logic and validation methods.
     * For full integration testing, consider using Laravel's RefreshDatabase
     * trait or a dedicated test database.
     */
}
