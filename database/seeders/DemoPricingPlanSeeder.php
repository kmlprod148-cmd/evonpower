<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PricingPlan;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\DB;

class DemoPricingPlanSeeder extends Seeder
{
    public function run()
    {
        // Get a business profile and VAT rate
        $businessProfile = BusinessProfile::first();
        $vatRate = DB::table('vat_rates')->where('name', 'Standard VAT')->first();

        // Un seul plan de démo
        $plans = [
            [
                'name' => 'Plan Fixe Standard',
                'description' => 'Un plan tarifaire fixe pour les sessions de recharge.',
                'rate_type' => 'fixed',
                'base_rate' => 10.00,
                'price_per_kwh' => 0.00,
                'price_per_minute' => null,
                'activation_fee' => 1.00,
                'vat_rate_id' => $vatRate ? $vatRate->id : null,
                'priority' => 10,
                'max_duration' => null,
                'is_active' => true,
                'currency' => 'EUR',
                'billing_interval' => 'session',
                'min_charging_time' => 0,
                'max_charging_time' => 0,
            ],
        ];

        foreach ($plans as $plan) {
            PricingPlan::updateOrCreate(
                ['name' => $plan['name']],
                [
                    'description' => $plan['description'],
                    'rate_type' => $plan['rate_type'],
                    'base_rate' => $plan['base_rate'],
                    'price_per_kwh' => $plan['price_per_kwh'],
                    'price_per_minute' => $plan['price_per_minute'],
                    'activation_fee' => $plan['activation_fee'],
                    'vat_rate_id' => $plan['vat_rate_id'],
                    'priority' => $plan['priority'],
                    'max_duration' => $plan['max_duration'],
                    'is_active' => $plan['is_active'],
                    'currency' => $plan['currency'],
                    'billing_interval' => $plan['billing_interval'],
                    'min_charging_time' => $plan['min_charging_time'],
                    'max_charging_time' => $plan['max_charging_time'],
                ]
            );
        }
    }
}