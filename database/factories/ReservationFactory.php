<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'charging_point_id' => ChargingPoint::factory(),
            'pricing_plan_id' => PricingPlan::factory(),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'active', 'completed', 'cancelled']),
            'reservation_type' => 'kwh',
            'reservation_value' => $this->faker->numberBetween(10, 100),
            'start_time' => now(),
            'end_time' => now()->addHours(1),
        ];
    }
}