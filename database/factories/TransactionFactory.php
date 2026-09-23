<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\\Models\\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start_timestamp = $this->faker->dateTimeBetween('-1 year', 'now');
        $stop_timestamp = $this->faker->dateTimeBetween($start_timestamp, 'now');
        $energy_delivered = $this->faker->randomFloat(2, 1, 100);
        $amount = $this->faker->randomFloat(2, 0.5, 50);

        return [
            'transaction_id' => $this->faker->uuid(),
            'charging_point_id' => \App\Models\ChargingPoint::factory(), // Or use an existing ID
            'connector_id' => \App\Models\Connector::factory(), // Or use an existing ID
            'user_id' => \App\Models\User::factory(), // Or use an existing ID, nullable
            'pricing_plan_id' => \App\Models\PricingPlan::factory(), // Or use an existing ID, nullable
            'commission_plan_id' => null, // nullable
            'start_timestamp' => \Illuminate\Support\Carbon::parse($start_timestamp),
            'stop_timestamp' => \Illuminate\Support\Carbon::parse($stop_timestamp),
            'duration' => $stop_timestamp ? \Illuminate\Support\Carbon::parse($start_timestamp)->diffInMinutes(\Illuminate\Support\Carbon::parse($stop_timestamp)) : null,
            'status' => $this->faker->randomElement(['in_progress', 'completed', 'failed', 'cancelled']),
            'meter_start' => $this->faker->randomFloat(4, 0, 1000),
            'meter_stop' => $stop_timestamp ? $this->faker->randomFloat(4, 1000, 2000) : null,
            'energy_delivered' => $energy_delivered,
            'price_energy' => $this->faker->randomFloat(4, 0.1, 1.0),
            'price_time' => $this->faker->randomFloat(4, 0.01, 0.1),
            'price_service' => $this->faker->randomFloat(4, 0.0, 0.5),
            'price_tax' => $this->faker->randomFloat(4, 0.0, 0.2),
            'price_total' => $this->faker->randomFloat(4, 1.0, 100.0),
            'currency' => $this->faker->currencyCode(),
            'stop_reason' => $this->faker->randomElement(['completed', 'user_stopped', 'error', 'timeout', 'payment_issue', null]),
            'error_code' => $this->faker->word(), // nullable
            'auth_id' => $this->faker->uuid(), // nullable
            'auth_method' => $this->faker->randomElement(['rfid', 'app', 'credit_card', 'qr_code', null]),
            'admin_commission' => $this->faker->randomFloat(4, 0.0, 5.0), // nullable
            'integrator_commission' => $this->faker->randomFloat(4, 0.0, 5.0), // nullable
            'partner_commission' => $this->faker->randomFloat(4, 0.0, 5.0), // nullable
            'admin_commission_paid' => $this->faker->boolean(),
            'integrator_commission_paid' => $this->faker->boolean(),
            'partner_commission_paid' => $this->faker->boolean(),
            'amount' => $amount, // Added amount field
        ];
    }
}
