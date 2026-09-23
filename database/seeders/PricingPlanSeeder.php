<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PricingPlan;
use App\Models\VatRate;

class PricingPlanSeeder extends Seeder
{
    public function run()
    {
        // Get or create a default VAT rate
        $vatRate = VatRate::first() ?? VatRate::create([
            'name' => 'TVA Standard',
            'rate' => 20.0,
            'is_active' => true,
            'is_default' => true
        ]);

        // Check if any pricing plans exist
        if (PricingPlan::count() === 0) {
            PricingPlan::create([
                'name' => 'Plan Standard',
                'description' => 'Plan tarifaire par défaut - basé sur le temps',
                'rate_type' => 'time',
                'price_per_minute' => 0.50,
                'price_per_kwh' => 0,
                'vat_rate_id' => $vatRate->id,
                'is_active' => true,
                'is_default' => true,
                'priority' => 1,
                'billing_interval' => 'session',
                'max_duration' => 120, // 2 hours max
                'activation_fee' => 1.00,
                'currency' => 'EUR',
            ]);

            PricingPlan::create([
                'name' => 'Plan Énergie',
                'description' => 'Tarification basée sur l\'énergie consommée',
                'rate_type' => 'energy',
                'price_per_minute' => 0,
                'price_per_kwh' => 0.25,
                'vat_rate_id' => $vatRate->id,
                'is_active' => true,
                'is_default' => false,
                'priority' => 2,
                'billing_interval' => 'session',
                'max_duration' => 180, // 3 hours max (will be used to calculate max energy)
                'activation_fee' => 1.50,
                'currency' => 'EUR',
            ]);

            PricingPlan::create([
                'name' => 'Plan Mixte',
                'description' => 'Plan tarifaire flexible - temps et énergie',
                'rate_type' => 'mixed',
                'price_per_minute' => 0.30,
                'price_per_kwh' => 0.20,
                'vat_rate_id' => $vatRate->id,
                'is_active' => true,
                'is_default' => false,
                'priority' => 3,
                'billing_interval' => 'session',
                'max_duration' => 240, // 4 hours max
                'activation_fee' => 2.00,
                'currency' => 'EUR',
            ]);
        }
    }
}