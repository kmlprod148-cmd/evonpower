<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company . ' Group',
            'description' => $this->faker->sentence,
            'type' => $this->faker->randomElement(['public', 'private']),
            'city' => $this->faker->city,
            'user_id' => \App\Models\User::factory(),
            'integrator_id' => \App\Models\Integrator::factory(),
            'partner_id' => \App\Models\Partner::factory(),
        ];
    }
} 