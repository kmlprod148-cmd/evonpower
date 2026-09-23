<?php

namespace Database\Factories;

use App\Models\BusinessProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessProfileFactory extends Factory
{
    protected $model = BusinessProfile::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'description' => $this->faker->paragraph,
            'is_public' => $this->faker->boolean,
            'is_active' => $this->faker->boolean,
            'target_audience' => json_encode($this->faker->randomElements(['pro', 'collectivite', 'particulier'], $this->faker->numberBetween(1, 3))),
            'maintenance_fee_type' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
            'maintenance_fee_amount' => $this->faker->randomFloat(2, 0, 1000),
            'transaction_fee_config' => json_encode([
                'types' => $this->faker->randomElements(['fixed', 'percentage'], $this->faker->numberBetween(1, 2)),
                'fixed_amount' => $this->faker->randomFloat(2, 0, 10),
                'percentage' => $this->faker->randomFloat(2, 0, 20)
            ]),
            'charge_fee_config' => json_encode([
                'fixed_amount' => $this->faker->randomFloat(2, 0, 5),
                'percentage' => $this->faker->randomFloat(2, 0, 10)
            ]),
            'terminal_fee_amount' => $this->faker->randomFloat(2, 0, 100),
            'terminal_fee_period' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
            'base_fee_amount' => $this->faker->randomFloat(2, 0, 50),
            'operator_commission' => $this->faker->randomFloat(2, 0, 100),
            'integrator_commission' => $this->faker->randomFloat(2, 0, 100),
            'owner_commission' => $this->faker->randomFloat(2, 0, 100),
            // Ne créer les dépendances que si pas en mode test ou si explicitement demandé
            'partner_id' => app()->environment('testing') ? null : \App\Models\Partner::factory(),
            'integrator_id' => app()->environment('testing') ? null : \App\Models\Integrator::factory(),
        ];
    }

    /**
     * État pour créer avec des dépendances explicites
     */
    public function withDependencies()
    {
        return $this->state(function (array $attributes) {
            return [
                'partner_id' => \App\Models\Partner::factory(),
                'integrator_id' => \App\Models\Integrator::factory(),
            ];
        });
    }
}