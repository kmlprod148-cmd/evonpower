<?php

namespace Database\Factories;

use App\Models\ChargingPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Connector>
 */
class ConnectorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $connectorTypes = ['Type2', 'CCS', 'CHAdeMO', 'Tesla'];
        $statuses = ['Available', 'Occupied', 'Unavailable', 'Faulted'];
        $formats = ['IEC_62196_T2', 'IEC_62196_T2_COMBO', 'IEC_62196_T3A', 'IEC_62196_T3C'];
        
        return [
            'charging_point_id' => ChargingPoint::factory(),
            'connector_id' => $this->faker->numberBetween(1, 4),
            'type' => $this->faker->randomElement($connectorTypes),
            'status' => $this->faker->randomElement($statuses),
            'power' => $this->faker->randomFloat(2, 3.7, 350), // Puissance entre 3.7kW et 350kW
            'format' => $this->faker->randomElement($formats),
            'tariff_id' => $this->faker->optional()->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate that the connector is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Available',
        ]);
    }

    /**
     * Indicate that the connector is occupied.
     */
    public function occupied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Occupied',
        ]);
    }

    /**
     * Indicate that the connector is unavailable.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Unavailable',
        ]);
    }

    /**
     * Indicate that the connector is faulted.
     */
    public function faulted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Faulted',
        ]);
    }

    /**
     * Create a Type2 connector.
     */
    public function type2(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Type2',
            'format' => 'IEC_62196_T2',
            'power' => $this->faker->randomFloat(2, 3.7, 22),
        ]);
    }

    /**
     * Create a CCS connector.
     */
    public function ccs(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'CCS',
            'format' => 'IEC_62196_T2_COMBO',
            'power' => $this->faker->randomFloat(2, 50, 350),
        ]);
    }

    /**
     * Create a CHAdeMO connector.
     */
    public function chademo(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'CHAdeMO',
            'format' => 'IEC_62196_T3A',
            'power' => $this->faker->randomFloat(2, 50, 200),
        ]);
    }
}