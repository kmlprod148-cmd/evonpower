<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PricingCalculationService;
use App\Models\PricingPlan;
use App\Models\PricingRuleCondition;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class PricingCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PricingCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PricingCalculationService();
        
        // Clear any cached data
        Cache::flush();
    }

    /**
     * Helper to create a pricing plan with minimal overrides
     */
    protected function createPricingPlan(array $overrides = []): PricingPlan
    {
        $defaults = [
            'name' => 'Test Plan',
            'description' => 'Test Description',
            'rate_type' => 'time',
            'base_rate' => 0,
            'price_per_kwh' => 0,
            'price_per_minute' => 0.10,
            'fixed_price' => 0,
            'activation_fee' => 0,
            'vat_rate_id' => null,
            'priority' => 1,
            'max_duration' => null,
            'max_energy' => null,
            'min_charge_duration' => null,
            'is_active' => true,
            'currency' => 'EUR',
            'billing_interval' => 'session',
            'min_charging_time' => 0,
            'max_charging_time' => 0,
            'has_weekend_pricing' => false,
            'has_night_pricing' => false,
            'weekend_price' => 0,
            'night_price' => 0,
            'night_start_time' => '22:00',
            'night_end_time' => '06:00',
        ];

        return PricingPlan::forceCreate(array_merge($defaults, $overrides));
    }

    /**
     * Helper to create a pricing rule condition
     */
    protected function createRuleCondition(int $planId, array $overrides = []): PricingRuleCondition
    {
        $defaults = [
            'pricing_plan_id' => $planId,
            'name' => 'Test Rule',
            'condition_type' => 'single',
            'conditions' => [
                ['field' => 'duration', 'operator' => 'gte', 'value' => 60]
            ],
            'rate_type' => 'percentage',
            'price_value' => 10,
            'is_percentage' => true,
            'apply_type' => 'add',
            'priority' => 1,
            'is_active' => true,
            'description' => null,
        ];

        return PricingRuleCondition::create(array_merge($defaults, $overrides));
    }

    /** @test */
    public function it_calculates_base_cost_for_time_based_plan()
    {
        $plan = $this->createPricingPlan([
            'rate_type' => 'time',
            'price_per_minute' => 0.10,
        ]);

        $sessionData = [
            'duration_minutes' => 60,
            'energy_kwh' => 0,
            'start_time' => now(),
        ];

        $result = $this->service->calculatePrice($plan, $sessionData);

        $this->assertTrue($result['success']);
        $this->assertEquals(6.00, $result['base_cost']); // 60 * 0.10
        $this->assertEquals(6.00, $result['subtotal']);
    }

    /** @test */
    public function it_calculates_base_cost_for_energy_based_plan()
    {
        $plan = $this->createPricingPlan([
            'rate_type' => 'energy',
            'price_per_kwh' => 0.50,
            'price_per_minute' => 0,
        ]);

        $sessionData = [
            'duration_minutes' => 60,
            'energy_kwh' => 20.5,
            'start_time' => now(),
        ];

        $result = $this->service->calculatePrice($plan, $sessionData);

        $this->assertTrue($result['success']);
        $this->assertEquals(10.25, $result['base_cost']); // 20.5 * 0.50
    }

    /** @test */
    public function it_calculates_base_cost_for_fixed_price_plan()
    {
        $plan = $this->createPricingPlan([
            'rate_type' => 'fixed',
            'fixed_price' => 15.00,
            'price_per_minute' => 0,
        ]);

        $sessionData = [
            'duration_minutes' => 60,
            'energy_kwh' => 20.5,
            'start_time' => now(),
        ];

        $result = $this->service->calculatePrice($plan, $sessionData);

        $this->assertTrue($result['success']);
        $this->assertEquals(15.00, $result['base_cost']);
        $this->assertEquals(15.00, $result['subtotal']);
    }

    /** @test */
    public function it_applies_weekend_pricing_on_saturday()
    {
        $plan = $this->createPricingPlan([
            'rate_type' => 'time',
            'price_per_minute' => 0.10,
            'has_weekend_pricing' => true,
            'weekend_price' => 0.05, // surcharge per minute
        ]);

        // Create a Saturday
        $saturday = Carbon::now()->next(Carbon::SATURDAY);

        $sessionData = [
            'duration_minutes' => 60,
            'energy_kwh' => 0,
            'start_time' => $saturday,
        ];

        $result = $this->service->calculatePrice($plan, $sessionData);

        $this->assertTrue($result['success']);
        $this->assertEquals(6.00, $result['base_cost']); // 60 * 0.10 (base rate)
        // Weekend surcharge: 60 * 0.05 = 3.00 (additional)
        $this->assertEquals(9.00, $result['subtotal']); // 6.00 + 3.00
        $this->assertTrue($result['context']['is_weekend']);
    }

    /** @test */
    public function it_applies_night_pricing_within_night_hours()
    {
        $plan = $this->createPricingPlan([
            'rate_type' => 'time',
            'price_per_minute' => 0.10,
            'has_night_pricing' => true,
            'night_price' => 0.05, // surcharge per minute
            'night_start_time' => '22:00',
            'night_end_time' => '06:00',
        ]);

        // Create a time during night hours (23:00)
        $nightTime = Carbon::today()->setTime(23, 0);

        $sessionData = [
            'duration_minutes' => 60,
            'energy_kwh' => 0,
            'start_time' => $nightTime,
        ];

        $result = $this->service->calculatePrice($plan, $sessionData);

        $this->assertTrue($result['success']);
        $this->assertEquals(6.00, $result['base_cost']); // 60 * 0.10 (base rate)
        // Night surcharge: 60 * 0.05 = 3.00
        $this->assertEquals(9.00, $result['subtotal']); // 6.00 + 3.00
    }

    /** @test */
    public function it_does_not_apply_night_pricing_during_day()
    {
        $plan = $this->createPricingPlan([
            'rate_type' => 'time',
            'price_per_minute' => 0.10,
            'has_night_pricing' => true,
            'night_price' => 0.05,
            'night_start_time' => '22:00',
            'night_end_time' => '06:00',
        ]);

        // Create a time during day (14:00)
        $dayTime = Carbon::today()->setTime(14, 0);

        $sessionData = [
            'duration_minutes' => 60,
            'energy_kwh' => 0,
            'start_time' => $dayTime,
        ];

        $result = $this->service->calculatePrice($plan, $sessionData);

        $this->assertTrue($result['success']);
        // No night surcharge during day
        $this->assertEquals(6.00, $result['subtotal']); // 60 * 0.10 (base rate only)
    }

    /** @test */
    public function it_applies_custom_rule_conditions()
    {
        $plan = $this->createPricingPlan([
            'rate_type' => 'time',
            'price_per_minute' => 0.10,
        ]);

        // Create a rule condition for premium customers
        $this->createRuleCondition($plan->id, [
            'name' => 'Premium Discount',
            'conditions' => [
                ['field' => 'customer_segment', 'operator' => 'eq', 'value' => 'premium']
            ],
            'price_value' => -5, // 5% discount
            'apply_type' => 'add',
        ]);

        $sessionData = [
            'duration_minutes' => 60,
            'energy_kwh' => 0,
            'start_time' => now(),
            'customer_segment' => 'premium',
        ];

        $result = $this->service->calculatePrice($plan, $sessionData);

        $this->assertTrue($result['success']);
        $this->assertEquals(6.00, $result['base_cost']); // 60 * 0.10
        // 5% discount applied: 6.00 - (6.00 * 0.05) = 5.70
        $this->assertEquals(5.70, $result['subtotal']);
    }

    /** @test */
    public function it_applies_multiple_rule_conditions_in_priority_order()
    {
        $plan = $this->createPricingPlan([
            'rate_type' => 'time',
            'price_per_minute' => 0.10,
        ]);

        // Create multiple conditions with different priorities
        $this->createRuleCondition($plan->id, [
            'name' => 'Flat Add',
            'conditions' => [
                ['field' => 'duration', 'operator' => 'gte', 'value' => 60]
            ],
            'rate_type' => 'fixed',
            'price_value' => 1.00,
            'is_percentage' => false,
            'apply_type' => 'add',
            'priority' => 1,
        ]);

        $this->createRuleCondition($plan->id, [
            'name' => 'Percentage Markup',
            'conditions' => [
                ['field' => 'duration', 'operator' => 'gte', 'value' => 60]
            ],
            'rate_type' => 'percentage',
            'price_value' => 10, // 10% markup
            'is_percentage' => true,
            'apply_type' => 'add',
            'priority' => 2,
        ]);

        $sessionData = [
            'duration_minutes' => 60,
            'energy_kwh' => 0,
            'start_time' => now(),
        ];

        $result = $this->service->calculatePrice($plan, $sessionData);

        $this->assertTrue($result['success']);
        $this->assertEquals(6.00, $result['base_cost']);
        // Verify that additional rates were applied
        $this->assertArrayHasKey('rule_conditions', $result['price_breakdown']);
        $this->assertGreaterThanOrEqual(2, count($result['price_breakdown']['rule_conditions']));
    }

    /** @test */
    public function it_includes_activation_fee()
    {
        $plan = $this->createPricingPlan([
            'rate_type' => 'time',
            'price_per_minute' => 0.10,
            'activation_fee' => 5.00,
        ]);

        $sessionData = [
            'duration_minutes' => 60,
            'energy_kwh' => 0,
            'start_time' => now(),
        ];

        $result = $this->service->calculatePrice($plan, $sessionData);

        $this->assertTrue($result['success']);
        $this->assertEquals(11.00, $result['base_cost']); // 6.00 + 5.00 (activation fee)
        $this->assertEquals(11.00, $result['subtotal']);
    }

    /** @test */
    public function it_returns_error_for_invalid_session_data()
    {
        $plan = $this->createPricingPlan([
            'rate_type' => 'time',
            'price_per_minute' => 0.10,
        ]);

        // Missing required fields
        $result = $this->service->calculatePrice($plan, []);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    /** @test */
    public function it_caches_calculation_results()
    {
        $plan = $this->createPricingPlan([
            'rate_type' => 'time',
            'price_per_minute' => 0.10,
        ]);

        $sessionData = [
            'duration_minutes' => 60,
            'energy_kwh' => 0,
            'start_time' => now(),
        ];

        // First call - should calculate
        $result1 = $this->service->calculatePrice($plan, $sessionData);
        
        // Second call - should use cache
        $result2 = $this->service->calculatePrice($plan, $sessionData);

        $this->assertEquals($result1['base_cost'], $result2['base_cost']);
    }

    /** @test */
    public function it_calculates_vat_correctly()
    {
        $plan = $this->createPricingPlan([
            'rate_type' => 'time',
            'price_per_minute' => 0.10,
        ]);

        $sessionData = [
            'duration_minutes' => 60,
            'energy_kwh' => 0,
            'start_time' => now(),
        ];

        $result = $this->service->calculatePrice($plan, $sessionData);

        $this->assertTrue($result['success']);
        $this->assertEquals(6.00, $result['subtotal']);
        // VAT should be applied based on the plan's vat_rate (default 20%)
        $this->assertEquals(1.20, $result['vat']); // 6.00 * 0.20
        $this->assertEquals(7.20, $result['total']);
    }

    /** @test */
    public function it_compares_multiple_plans()
    {
        $plan1 = $this->createPricingPlan([
            'rate_type' => 'time',
            'price_per_minute' => 0.10,
            'priority' => 1,
        ]);

        $plan2 = $this->createPricingPlan([
            'rate_type' => 'time',
            'price_per_minute' => 0.15,
            'priority' => 2,
        ]);

        $sessionData = [
            'duration_minutes' => 60,
            'energy_kwh' => 0,
            'start_time' => now(),
        ];

        $result1 = $this->service->calculatePrice($plan1, $sessionData);
        $result2 = $this->service->calculatePrice($plan2, $sessionData);

        $this->assertEquals(6.00, $result1['base_cost']);
        $this->assertEquals(9.00, $result2['base_cost']);
        
        // Clear all cache to avoid affecting other tests
        Cache::flush();
    }
}
