<?php

namespace Database\Factories;

use App\Models\PricingPlan;
use App\Models\VatRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PricingPlanFactory extends Factory
{
    protected $model = PricingPlan::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word . ' Plan',
            'description' => $this->faker->sentence,
            'rate_type' => $this->faker->randomElement(['fixed', 'time', 'energy']),
            'base_rate' => $this->faker->randomFloat(4, 5, 50),
            'price_per_kwh' => $this->faker->randomFloat(4, 1, 5),
            'price_per_minute' => $this->faker->randomFloat(4, 0.1, 1.0),
            'activation_fee' => $this->faker->randomFloat(2, 0, 10),
            'vat_rate_id' => VatRate::factory(),
            'priority' => $this->faker->numberBetween(0, 10),
            'max_duration' => $this->faker->boolean(70) ? $this->faker->numberBetween(60, 360) : null,
            'is_active' => $this->faker->boolean(90),
            'currency' => 'EUR',
            'billing_interval' => $this->faker->randomElement(['session', 'hourly', 'daily', 'monthly']),
            'min_charging_time' => $this->faker->numberBetween(0, 10),
            'max_charging_time' => $this->faker->boolean(70) ? $this->faker->numberBetween(60, 360) : 0,
        ];
    }

    /**
     * Indicate that the pricing plan is active.
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the pricing plan is inactive.
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Create a fixed rate pricing plan.
     */
    public function fixedRate()
    {
        return $this->state(function (array $attributes) {
            return [
                'rate_type' => 'fixed',
                'base_rate' => $this->faker->randomFloat(4, 10, 30),
                'price_per_kwh' => null,
                'price_per_minute' => null,
            ];
        });
    }

    /**
     * Create a time-based pricing plan.
     */
    public function timeBased()
    {
        return $this->state(function (array $attributes) {
            return [
                'rate_type' => 'time',
                'base_rate' => null,
                'price_per_kwh' => null,
                'price_per_minute' => $this->faker->randomFloat(4, 0.2, 2.0),
            ];
        });
    }

    /**
     * Create an energy-based pricing plan.
     */
    public function energyBased()
    {
        return $this->state(function (array $attributes) {
            return [
                'rate_type' => 'energy',
                'base_rate' => null,
                'price_per_kwh' => $this->faker->randomFloat(4, 1.5, 4.0),
                'price_per_minute' => null,
            ];
        });
    }
}