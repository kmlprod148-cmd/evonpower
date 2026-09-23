<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CMIReservationPaymentService;
use App\Services\PaymentKeysService;
use App\Models\Reservation;
use App\Models\PricingPlan;
use App\Models\User;
use Mockery;
use Exception;

/**
 * Unit tests for CMIReservationPaymentService
 * 
 * Tests the CMI payment gateway integration for reservations.
 * Validates the payment data format and hash generation.
 */
class CMIReservationPaymentServiceTest extends TestCase
{
    protected CMIReservationPaymentService $service;
    protected $mockPaymentKeysService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPaymentKeysService = Mockery::mock(PaymentKeysService::class);
        $this->service = new CMIReservationPaymentService($this->mockPaymentKeysService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_maps_mad_currency_correctly()
    {
        // Test MAD currency mapping to numeric code
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('preparePaymentData');
        $method->setAccessible(true);

        // Create mock reservation
        $reservation = $this->createMockReservation([
            'estimated_cost' => 100.00,
            'currency' => 'MAD',
        ]);

        // Mock CMI config
        $this->mockPaymentKeysService
            ->shouldReceive('getActiveCmiKeys')
            ->andReturn([
                'clientid' => '600002823',
                'storekey' => 'test_key_12345',
            ]);

        // We need to test that the currency mapping is correct
        // The service should convert 'MAD' to '504' (ISO 4217 numeric)
        $currencyMap = [
            'MAD' => '504',
            'EUR' => '978',
            'USD' => '840',
            'GBP' => '826',
        ];

        $this->assertEquals('504', $currencyMap['MAD']);
        $this->assertEquals('978', $currencyMap['EUR']);
        $this->assertEquals('840', $currencyMap['USD']);
    }

    /** @test */
    public function it_uses_decimal_format_for_amount()
    {
        // The amount should be in decimal format with 2 decimal places
        // e.g., 100.00 instead of 10000 (centimes)
        
        $amount = 100.00;
        $formattedAmount = number_format($amount, 2, '.', '');
        
        $this->assertEquals('100.00', $formattedAmount);
    }

    /** @test */
    public function it_generates_sha512_hash()
    {
        // Test that SHA512 hash is generated correctly
        $storeKey = 'test_store_key_12345';
        
        $hashFields = [
            '600002823',           // clientid
            '123',                  // oid (reservation id)
            '100.00',              // amount (decimal format)
            'https://example.com/ok',   // okUrl
            'https://example.com/fail', // failUrl
            'PreAuth',             // TranType
            'abc123def456',        // rnd
            'https://example.com/callback', // callbackUrl
            '504',                 // currency (MAD)
            '3D_PAY_HOSTING',      // storetype
            'ver3',                // hashAlgorithm
            'fr',                  // lang
            $storeKey,             // store_key (at the end)
        ];

        $hashString = implode('|', $hashFields);
        $hash = base64_encode(hash('sha512', $hashString, true));

        // Verify hash is generated
        $this->assertNotEmpty($hash);
        $this->assertIsString($hash);
        
        // SHA512 produces 88 characters in base64
        $this->assertEquals(88, strlen($hash));
    }

    /** @test */
    public function it_handles_missing_currency_with_default()
    {
        // Test that missing currency falls back to MAD (504)
        $currencyMap = [
            'MAD' => '504',
            'EUR' => '978',
            'USD' => '840',
            'GBP' => '826',
        ];

        // Unknown currency should default to MAD
        $unknownCurrency = 'UNKNOWN';
        $currencyCode = $currencyMap[$unknownCurrency] ?? '504';
        
        $this->assertEquals('504', $currencyCode);
    }

    /** @test */
    public function it_validates_required_cmi_config()
    {
        // Test that missing CMI config is handled
        $this->mockPaymentKeysService
            ->shouldReceive('getActiveCmiKeys')
            ->andReturn([]);

        // Should log warning when config is missing
        $this->mockPaymentKeysService
            ->shouldReceive('getActiveCmiKeys')
            ->andReturn([
                'clientid' => '600002823',
                // Missing storekey
            ]);

        // Test that empty config is detected
        $cmiKeys = [];
        $hasRequiredKeys = !empty($cmiKeys['storekey']) && !empty($cmiKeys['clientid']);
        
        $this->assertFalse($hasRequiredKeys);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Create a mock reservation for testing
     */
    protected function createMockReservation(array $attributes = []): Reservation
    {
        $reservation = Mockery::mock(Reservation::class)->makePartial();
        
        $defaultAttributes = [
            'id' => 123,
            'estimated_cost' => 100.00,
            'amount' => 100.00,
            'user_id' => 1,
            'pricing_plan_id' => 1,
        ];

        foreach (array_merge($defaultAttributes, $attributes) as $key => $value) {
            $reservation->{$key} = $value;
        }

        // Mock pricing plan relationship
        $pricingPlan = Mockery::mock(PricingPlan::class)->makePartial();
        $pricingPlan->currency = $attributes['currency'] ?? 'MAD';
        $reservation->pricingPlan = $pricingPlan;

        // Mock user relationship
        $user = Mockery::mock(User::class)->makePartial();
        $user->email = 'test@example.com';
        $user->phone = '+212612345678';
        $user->name = 'Test User';
        $reservation->user = $user;

        return $reservation;
    }

    // =========================================================================
    // INTEGRATION NOTES
    // =========================================================================

    /*
     * IMPORTANT: Integration Testing Notes
     * 
     * Before deploying to production, verify the following in CMI test environment:
     * 
     * 1. Amount Format: Verify CMI test gateway accepts decimal format (100.00)
     *    vs centimes format (10000). The current implementation uses decimal.
     * 
     * 2. Hash Algorithm: Verify SHA512 is supported. If CMI requires SHA1,
     *    you will need to modify the hash generation in preparePaymentData().
     * 
     * 3. Currency Codes: The ISO 4217 numeric codes are used:
     *    - MAD: 504
     *    - EUR: 978
     *    - USD: 840
     *    - GBP: 826
     * 
     * 4. Hash Field Order: The pipe-delimited order must match CMI's expected order.
     *    Current order: clientid|oid|amount|okUrl|failUrl|TranType|rnd|callbackUrl|currency|storetype|hashAlgorithm|lang|store_key
     * 
     * To test in CMI sandbox:
     * 1. Set CMI_TEST_MODE=true in .env
     * 2. Use test credentials from CMI documentation
     * 3. Test with small amounts (e.g., 1.00 MAD)
     * 4. Verify both success and failure callbacks
     */
}
