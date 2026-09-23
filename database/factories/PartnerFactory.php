<?php

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'contact_name' => $this->faker->name,
            'type' => $this->faker->randomElement(['Exploitant', 'Propriétaire', 'Intégrateur']),
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'city' => $this->faker->city,
            'address' => $this->faker->address,
            'postal_code' => $this->faker->postcode,
            'country' => $this->faker->country,
            'website' => $this->faker->url,
            'logo' => null,
            'description' => $this->faker->sentence,
            'is_active' => true,
            'integrator_id' => \App\Models\Integrator::factory(),
            'business_profile_id' => \App\Models\BusinessProfile::factory(),
        ];
    }
} 