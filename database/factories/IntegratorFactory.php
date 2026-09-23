<?php

namespace Database\Factories;

use App\Models\Integrator;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegratorFactory extends Factory
{
    protected $model = Integrator::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'city' => $this->faker->city,
            'address' => $this->faker->address,
            'postal_code' => $this->faker->postcode,
            'country' => $this->faker->country,
            'contact_name' => $this->faker->name,
            'website' => $this->faker->url,
            'logo' => null,
            'description' => $this->faker->sentence,
            'is_active' => true,
        ];
    }
} 