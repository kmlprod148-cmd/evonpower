<?php

namespace Tests\Unit\Services;

use App\Models\ChargePoint;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Models\User;
use App\Services\GuestCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Unit Tests for GuestCheckoutService
 * 
 * Tests the guest checkout flow business logic including:
 * - Price calculation
 * - GDPR consent validation
 * - Session creation
 * - Checkout validation
 * 
 * @package Tests\Unit\Services
 */
class GuestCheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GuestCheckoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GuestCheckoutService();
    }

    /**
     * Test price calculation with per-minute billing
     */
    public function test_calculate_price_per_minute(): void
    {
        // Create a pricing plan with per-minute billing
        $pricingPlan = PricingPlan::create([
            'name' => 'Test Plan',
            'price_per_minute' => 0.50,
            'price_per_kwh' => 0,
            'activation_fee' => 1.00,
            'vat_rate' => 20,
            'currency' => 'MAD',
        ]);

        // Create a charge point with the pricing plan
        $chargePoint = ChargePoint::create([
            'name' => 'Test Charge Point',
            'external_id' => 'TEST-001',
            'status' => 'online',
            'is_active' => true,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Add a connector
        $chargePoint->connectors()->create([
            'connector_id' => 1,
            'connector_type' => 'Type 2',
            'max_power' => 22,
            'status' => 'Available',
        ]);

        // Calculate price for 60 minutes
        $result = $this->service->calculatePrice($chargePoint, 60);

        // Verify results
        $this->assertEquals(60, $result['duration_minutes']);
        $this->assertEquals(31.00, $result['estimated_kwh']); // 22kW * 60min / 60
        $this->assertEquals(31.00, $result['price_excl_vat']); // 60 * 0.50 + 1.00
        $this->assertEquals(6.20, $result['vat_amount']); // 31.00 * 20%
        $this->assertEquals(37.20, $result['total_price']); // 31.00 + 6.20
        $this->assertEquals('per_minute', $result['billing_type']);
        $this->assertEquals('MAD', $result['currency']);
    }

    /**
     * Test price calculation with per-kWh billing
     */
    public function test_calculate_price_per_kwh(): void
    {
        // Create a pricing plan with per-kWh billing
        $pricingPlan = PricingPlan::create([
            'name' => 'Per kWh Plan',
            'price_per_minute' => 0,
            'price_per_kwh' => 1.50,
            'activation_fee' => 2.00,
            'vat_rate' => 20,
            'currency' => 'MAD',
        ]);

        // Create a charge point with the pricing plan
        $chargePoint = ChargePoint::create([
            'name' => 'Test Charge Point',
            'external_id' => 'TEST-002',
            'status' => 'online',
            'is_active' => true,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Add a connector
        $chargePoint->connectors()->create([
            'connector_id' => 1,
            'connector_type' => 'Type 2',
            'max_power' => 22,
            'status' => 'Available',
        ]);

        // Calculate price for 60 minutes
        $result = $this->service->calculatePrice($chargePoint, 60);

        // Verify results
        $this->assertEquals(60, $result['duration_minutes']);
        $this->assertEquals(31.00, $result['estimated_kwh']); // 22kW * 60min / 60
        $this->assertEquals(48.50, $result['price_excl_vat']); // 31 * 1.50 + 2.00
        $this->assertEquals(9.70, $result['vat_amount']); // 48.50 * 20%
        $this->assertEquals(58.20, $result['total_price']);
        $this->assertEquals('per_kwh', $result['billing_type']);
    }

    /**
     * Test price calculation with flat rate
     */
    public function test_calculate_price_flat_rate(): void
    {
        // Create a pricing plan with flat rate
        $pricingPlan = PricingPlan::create([
            'name' => 'Flat Rate Plan',
            'price_per_minute' => 0,
            'price_per_kwh' => 0,
            'base_rate' => 25.00,
            'vat_rate' => 20,
            'currency' => 'MAD',
        ]);

        // Create a charge point with the pricing plan
        $chargePoint = ChargePoint::create([
            'name' => 'Test Charge Point',
            'external_id' => 'TEST-003',
            'status' => 'online',
            'is_active' => true,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Calculate price for 60 minutes
        $result = $this->service->calculatePrice($chargePoint, 60);

        // Verify results
        $this->assertEquals(25.00, $result['price_excl_vat']);
        $this->assertEquals(5.00, $result['vat_amount']);
        $this->assertEquals(30.00, $result['total_price']);
    }

    /**
     * Test price calculation throws exception when no pricing plan
     */
    public function test_calculate_price_throws_exception_when_no_pricing_plan(): void
    {
        // Create a charge point without pricing plan
        $chargePoint = ChargePoint::create([
            'name' => 'Test Charge Point',
            'external_id' => 'TEST-004',
            'status' => 'online',
            'is_active' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No pricing plan configured for this charge point');

        $this->service->calculatePrice($chargePoint, 60);
    }

    /**
     * Test validate guest info with valid data
     */
    public function test_validate_guest_info_valid(): void
    {
        $guestInfo = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'gdpr_consent' => true,
            'password' => 'password123',
            'create_account' => true,
        ];

        $errors = $this->service->validateGuestInfo($guestInfo);

        $this->assertEmpty($errors);
    }

    /**
     * Test validate guest info with missing required fields
     */
    public function test_validate_guest_info_missing_fields(): void
    {
        $guestInfo = [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'gdpr_consent' => false,
        ];

        $errors = $this->service->validateGuestInfo($guestInfo);

        $this->assertArrayHasKey('first_name', $errors);
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('gdpr_consent', $errors);
    }

    /**
     * Test validate guest info with invalid email
     */
    public function test_validate_guest_info_invalid_email(): void
    {
        $guestInfo = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'gdpr_consent' => true,
        ];

        $errors = $this->service->validateGuestInfo($guestInfo);

        $this->assertArrayHasKey('email', $errors);
    }

    /**
     * Test validate guest info with short password
     */
    public function test_validate_guest_info_short_password(): void
    {
        $guestInfo = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'gdpr_consent' => true,
            'create_account' => true,
            'password' => 'short',
        ];

        $errors = $this->service->validateGuestInfo($guestInfo);

        $this->assertArrayHasKey('password', $errors);
    }

    /**
     * Test get charge point info
     */
    public function test_get_charge_point_info(): void
    {
        $pricingPlan = PricingPlan::create([
            'name' => 'Test Plan',
            'price_per_minute' => 0.50,
            'price_per_kwh' => 0,
            'activation_fee' => 1.00,
            'vat_rate' => 20,
            'currency' => 'MAD',
        ]);

        $chargePoint = ChargePoint::create([
            'name' => 'Test Charge Point',
            'external_id' => 'TEST-005',
            'address' => '123 Test Street',
            'city' => 'Test City',
            'status' => 'online',
            'is_active' => true,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        $result = $this->service->getChargePointInfo($chargePoint);

        $this->assertEquals($chargePoint->id, $result['id']);
        $this->assertEquals('Test Charge Point', $result['name']);
        $this->assertEquals('123 Test Street', $result['address']);
        $this->assertTrue($result['is_available']);
        $this->assertEquals(22, $result['max_power']);
    }

    /**
     * Test get charge point info with unavailable charge point
     */
    public function test_get_charge_point_info_unavailable(): void
    {
        $chargePoint = ChargePoint::create([
            'name' => 'Offline Charge Point',
            'external_id' => 'TEST-006',
            'status' => 'offline',
            'is_active' => false,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This charge point is currently unavailable');

        $this->service->getChargePointInfo($chargePoint);
    }

    /**
     * Test get duration options
     */
    public function test_get_duration_options(): void
    {
        $options = $this->service->getDurationOptions();

        $this->assertIsArray($options);
        $this->assertContains(10, $options);
        $this->assertContains(60, $options);
        $this->assertContains(120, $options);
    }

    /**
     * Test create checkout session as guest
     */
    public function test_create_checkout_session_as_guest(): void
    {
        // Create pricing plan and charge point
        $pricingPlan = PricingPlan::create([
            'name' => 'Test Plan',
            'price_per_minute' => 0.50,
            'price_per_kwh' => 0,
            'activation_fee' => 1.00,
            'vat_rate' => 20,
            'currency' => 'MAD',
        ]);

        $chargePoint = ChargePoint::create([
            'name' => 'Test Charge Point',
            'external_id' => 'TEST-007',
            'status' => 'online',
            'is_active' => true,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        $checkoutData = [
            'duration_minutes' => 60,
            'estimated_kwh' => 22.00,
            'total_price' => 37.20,
            'currency' => 'MAD',
        ];

        $guestInfo = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+212612345678',
            'gdpr_consent' => true,
            'marketing_consent' => false,
        ];

        $reservation = $this->service->createCheckoutSession(
            $chargePoint,
            $checkoutData,
            $guestInfo,
            false
        );

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertTrue($reservation->is_guest);
        $this->assertEquals('john@example.com', $reservation->guest_email);
        $this->assertEquals('+212612345678', $reservation->guest_phone);
        $this->assertEquals('pending', $reservation->status);
        $this->assertEquals(60, $reservation->duration_minutes);
        $this->assertEquals(37.20, $reservation->estimated_cost);
        $this->assertNotNull($reservation->metadata['session_token']);
        $this->assertTrue($reservation->metadata['gdpr_consent']['consented']);
    }

    /**
     * Test create checkout session with account creation
     */
    public function test_create_checkout_session_with_account_creation(): void
    {
        // Create pricing plan and charge point
        $pricingPlan = PricingPlan::create([
            'name' => 'Test Plan',
            'price_per_minute' => 0.50,
            'price_per_kwh' => 0,
            'activation_fee' => 1.00,
            'vat_rate' => 20,
            'currency' => 'MAD',
        ]);

        $chargePoint = ChargePoint::create([
            'name' => 'Test Charge Point',
            'external_id' => 'TEST-008',
            'status' => 'online',
            'is_active' => true,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        $checkoutData = [
            'duration_minutes' => 60,
            'estimated_kwh' => 22.00,
            'total_price' => 37.20,
            'currency' => 'MAD',
        ];

        $guestInfo = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '+212698765432',
            'gdpr_consent' => true,
            'marketing_consent' => true,
        ];

        $reservation = $this->service->createCheckoutSession(
            $chargePoint,
            $checkoutData,
            $guestInfo,
            true,
            'securepassword123'
        );

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertFalse($reservation->is_guest);
        $this->assertNotNull($reservation->user_id);
        
        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Smith',
        ]);
    }

    /**
     * Test create checkout session throws exception for duplicate email
     */
    public function test_create_checkout_session_duplicate_email_throws_exception(): void
    {
        // Create existing user
        User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create pricing plan and charge point
        $pricingPlan = PricingPlan::create([
            'name' => 'Test Plan',
            'price_per_minute' => 0.50,
            'price_per_kwh' => 0,
            'activation_fee' => 1.00,
            'vat_rate' => 20,
            'currency' => 'MAD',
        ]);

        $chargePoint = ChargePoint::create([
            'name' => 'Test Charge Point',
            'external_id' => 'TEST-009',
            'status' => 'online',
            'is_active' => true,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        $checkoutData = [
            'duration_minutes' => 60,
            'estimated_kwh' => 22.00,
            'total_price' => 37.20,
            'currency' => 'MAD',
        ];

        $guestInfo = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com', // Duplicate email
            'gdpr_consent' => true,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An account with this email already exists');

        $this->service->createCheckoutSession(
            $chargePoint,
            $checkoutData,
            $guestInfo,
            true, // Try to create account with existing email
            'password123'
        );
    }

    /**
     * Test validate checkout session
     */
    public function test_validate_checkout_session(): void
    {
        // Create a pending reservation
        $reservation = Reservation::create([
            'status' => 'pending',
            'duration_minutes' => 60,
            'estimated_energy' => 22.00,
            'estimated_cost' => 37.20,
        ]);

        // Mock the chargePoint relationship
        $reservation->setRelation('chargePoint', null);

        $errors = $this->service->validateCheckoutSession($reservation);

        $this->assertArrayHasKey('charge_point', $errors);
    }

    /**
     * Test validate expired checkout session
     */
    public function test_validate_checkout_session_expired(): void
    {
        // Create a pending reservation with old timestamp
        $reservation = Reservation::create([
            'status' => 'pending',
            'duration_minutes' => 60,
            'estimated_energy' => 22.00,
            'estimated_cost' => 37.20,
            'created_at' => now()->subMinutes(35), // More than 30 minutes ago
        ]);

        $errors = $this->service->validateCheckoutSession($reservation);

        $this->assertArrayHasKey('expired', $errors);
    }

    /**
     * Test cancel checkout session
     */
    public function test_cancel_checkout_session(): void
    {
        $reservation = Reservation::create([
            'status' => 'pending',
            'duration_minutes' => 60,
            'estimated_energy' => 22.00,
            'estimated_cost' => 37.20,
        ]);

        $result = $this->service->cancelCheckoutSession($reservation);

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $reservation->fresh()->status);
    }

    /**
     * Test cancel already cancelled session returns false
     */
    public function test_cancel_already_cancelled_session_returns_false(): void
    {
        $reservation = Reservation::create([
            'status' => 'cancelled',
            'duration_minutes' => 60,
            'estimated_energy' => 22.00,
            'estimated_cost' => 37.20,
        ]);

        $result = $this->service->cancelCheckoutSession($reservation);

        $this->assertFalse($result);
    }

    /**
     * Test get checkout summary
     */
    public function test_get_checkout_summary(): void
    {
        $chargePoint = ChargePoint::create([
            'name' => 'Test Charge Point',
            'external_id' => 'TEST-010',
            'address' => '123 Test Street',
            'status' => 'online',
            'is_active' => true,
        ]);

        $reservation = Reservation::create([
            'status' => 'pending',
            'duration_minutes' => 60,
            'estimated_energy' => 22.00,
            'estimated_cost' => 37.20,
            'charge_point_id' => $chargePoint->id,
            'guest_email' => 'test@example.com',
            'guest_phone' => '+212612345678',
            'guest_info' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
            ],
            'metadata' => [
                'currency' => 'MAD',
            ],
        ]);

        $summary = $this->service->getCheckoutSummary($reservation);

        $this->assertEquals($reservation->id, $summary['reservation_id']);
        $this->assertEquals('Test Charge Point', $summary['charge_point']['name']);
        $this->assertEquals(60, $summary['duration_minutes']);
        $this->assertEquals(37.20, $summary['total_price']);
        $this->assertEquals('John Doe', $summary['guest_info']['name']);
    }

    /**
     * Test price calculation respects min/max duration
     */
    public function test_calculate_price_respects_duration_limits(): void
    {
        $pricingPlan = PricingPlan::create([
            'name' => 'Test Plan',
            'price_per_minute' => 0.50,
            'price_per_kwh' => 0,
            'activation_fee' => 1.00,
            'vat_rate' => 20,
            'currency' => 'MAD',
        ]);

        $chargePoint = ChargePoint::create([
            'name' => 'Test Charge Point',
            'external_id' => 'TEST-011',
            'status' => 'online',
            'is_active' => true,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Test with duration less than minimum (1 minute)
        $result = $this->service->calculatePrice($chargePoint, 0);
        $this->assertEquals(1, $result['duration_minutes']);

        // Test with duration more than maximum (300 minutes)
        $result = $this->service->calculatePrice($chargePoint, 500);
        $this->assertEquals(300, $result['duration_minutes']);
    }
}
