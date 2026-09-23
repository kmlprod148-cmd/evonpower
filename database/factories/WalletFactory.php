<?php

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\User;
use App\Models\Partner;
use App\Models\Integrator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $currencies = ['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'AUD', 'JPY'];
        $currency = $this->faker->randomElement($currencies);
        
        return [
            'owner_type' => $this->faker->randomElement([
                User::class,
                Partner::class,
                Integrator::class,
            ]),
            'owner_id' => function (array $attributes) {
                $ownerType = $attributes['owner_type'];
                
                if ($ownerType === User::class) {
                    return User::factory()->create()->id;
                } elseif ($ownerType === Partner::class) {
                    return Partner::factory()->create()->id;
                } elseif ($ownerType === Integrator::class) {
                    return Integrator::factory()->create()->id;
                }
                
                return 1;
            },
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'currency' => $currency,
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'name' => $this->faker->words(3, true) . ' Wallet',
            'description' => $this->faker->sentence(),
            'min_balance' => $this->faker->optional(0.3)->randomFloat(2, 0, 100),
            'max_balance' => $this->faker->optional(0.2)->randomFloat(2, 1000, 50000),
            'auto_recharge' => $this->faker->boolean(20), // 20% chance of auto-recharge
            'auto_recharge_threshold' => function (array $attributes) {
                return $attributes['auto_recharge'] ? 
                    $this->faker->randomFloat(2, 10, 100) : null;
            },
            'auto_recharge_amount' => function (array $attributes) {
                return $attributes['auto_recharge'] ? 
                    $this->faker->randomFloat(2, 50, 500) : null;
            },
        ];
    }

    /**
     * Indicate that the wallet is active.
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the wallet is inactive.
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Indicate that the wallet has a low balance.
     */
    public function lowBalance()
    {
        return $this->state(function (array $attributes) {
            return [
                'balance' => $this->faker->randomFloat(2, 0, 10),
            ];
        });
    }

    /**
     * Indicate that the wallet has a high balance.
     */
    public function highBalance()
    {
        return $this->state(function (array $attributes) {
            return [
                'balance' => $this->faker->randomFloat(2, 5000, 50000),
            ];
        });
    }

    /**
     * Indicate that the wallet has auto-recharge enabled.
     */
    public function withAutoRecharge()
    {
        return $this->state(function (array $attributes) {
            return [
                'auto_recharge' => true,
                'auto_recharge_threshold' => $this->faker->randomFloat(2, 10, 100),
                'auto_recharge_amount' => $this->faker->randomFloat(2, 50, 500),
            ];
        });
    }

    /**
     * Indicate that the wallet needs auto-recharge.
     */
    public function needingRecharge()
    {
        return $this->state(function (array $attributes) {
            $threshold = $this->faker->randomFloat(2, 10, 100);
            return [
                'auto_recharge' => true,
                'auto_recharge_threshold' => $threshold,
                'auto_recharge_amount' => $this->faker->randomFloat(2, 50, 500),
                'balance' => $this->faker->randomFloat(2, 0, $threshold),
            ];
        });
    }

    /**
     * Indicate that the wallet has minimum balance constraints.
     */
    public function withMinBalance()
    {
        return $this->state(function (array $attributes) {
            $minBalance = $this->faker->randomFloat(2, 10, 100);
            return [
                'min_balance' => $minBalance,
                'balance' => $this->faker->randomFloat(2, $minBalance, $minBalance + 1000),
            ];
        });
    }

    /**
     * Indicate that the wallet has maximum balance constraints.
     */
    public function withMaxBalance()
    {
        return $this->state(function (array $attributes) {
            $maxBalance = $this->faker->randomFloat(2, 1000, 10000);
            return [
                'max_balance' => $maxBalance,
                'balance' => $this->faker->randomFloat(2, 0, $maxBalance),
            ];
        });
    }

    /**
     * Create a wallet for a specific user.
     */
    public function forUser(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'owner_type' => User::class,
                'owner_id' => $user->id,
                'name' => $user->name . "'s Wallet",
                'description' => 'Main wallet for ' . $user->name,
            ];
        });
    }

    /**
     * Create a wallet for a specific partner.
     */
    public function forPartner(Partner $partner)
    {
        return $this->state(function (array $attributes) use ($partner) {
            return [
                'owner_type' => Partner::class,
                'owner_id' => $partner->id,
                'name' => $partner->name . ' Wallet',
                'description' => 'Business wallet for ' . $partner->name,
            ];
        });
    }

    /**
     * Create a wallet for a specific integrator.
     */
    public function forIntegrator(Integrator $integrator)
    {
        return $this->state(function (array $attributes) use ($integrator) {
            return [
                'owner_type' => Integrator::class,
                'owner_id' => $integrator->id,
                'name' => $integrator->name . ' Wallet',
                'description' => 'Business wallet for ' . $integrator->name,
            ];
        });
    }

    /**
     * Create a wallet with specific currency.
     */
    public function withCurrency(string $currency)
    {
        return $this->state(function (array $attributes) use ($currency) {
            return [
                'currency' => $currency,
            ];
        });
    }

    /**
     * Create a wallet with specific balance.
     */
    public function withBalance(float $balance)
    {
        return $this->state(function (array $attributes) use ($balance) {
            return [
                'balance' => $balance,
            ];
        });
    }
}
