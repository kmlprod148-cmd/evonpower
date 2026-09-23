<?php

namespace Database\Factories;

use App\Models\VatRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class VatRateFactory extends Factory
{
    protected $model = VatRate::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word . ' VAT',
            'rate' => $this->faker->randomFloat(2, 5, 25),
            'is_active' => $this->faker->boolean,
        ];
    }
}