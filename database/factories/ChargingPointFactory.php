<?php

namespace Database\Factories;

use App\Models\ChargingPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChargingPointFactory extends Factory
{
    protected $model = ChargingPoint::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word . ' Charging Point',
            'station_id' => \App\Models\Station::factory(),
            'pricing_plan_id' => \App\Models\PricingPlan::factory(),
            'serial_number' => $this->faker->unique()->uuid,
            'connection_type' => $this->faker->randomElement(['AC', 'DC']),
            'max_power' => $this->faker->randomFloat(2, 7, 150),
            'latitude' => $this->faker->randomFloat(7, -89.9999999, 89.9999999), // Reduced precision to avoid boundary issues
            'longitude' => $this->faker->randomFloat(7, -179.9999999, 179.9999999), // Reduced precision to avoid boundary issues
            'status' => $this->faker->randomElement(['online', 'offline', 'maintenance', 'error']),
        ];
    }
}