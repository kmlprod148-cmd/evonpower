<?php

namespace Database\Factories;

use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Station::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->company . ' Station',
            'address' => $this->faker->streetAddress,
            'latitude' => $this->faker->latitude(33, 35), // Example range for Los Angeles
            'longitude' => $this->faker->longitude(-119, -117), // Example range for Los Angeles
            'city' => $this->faker->city,
            'postal_code' => $this->faker->postcode,
            'country' => $this->faker->countryCode,
            'group_id' => null, // Can be associated with a group later if needed
        ];
    }
}