<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommissionPlan;
use App\Models\BusinessProfile;
use App\Models\Partner;
use App\Models\Integrator;

class DemoCommissionPlanSeeder extends Seeder
{
    public function run()
    {
        // Get demo business profile, partner, and integrator
        $businessProfile = BusinessProfile::first();
        $partner = Partner::first();
        $integrator = Integrator::first();

        // Un seul plan de commission de démo
        $plans = [
            [
                'name' => 'Standard Partner Commission',
                'partner_percentage' => 5.0,
                'type' => 'percentage',
                'applies_to_type' => 'partner',
                'applies_to_id' => $partner ? $partner->id : null,
                'min_transaction_value' => 0.0,
                'max_transaction_value' => 10000.0,
                'description' => 'Plan de commission standard pour les partenaires',
            ],
        ];

        foreach ($plans as $plan) {
            CommissionPlan::updateOrCreate(
                ['name' => $plan['name']],
                [
                    'partner_percentage' => $plan['partner_percentage'] ?? 0,
                    'integrator_percentage' => $plan['integrator_percentage'] ?? 0,
                    'type' => $plan['type'],
                    'applies_to_type' => $plan['applies_to_type'],
                    'applies_to_id' => $plan['applies_to_id'],
                    'min_transaction_value' => $plan['min_transaction_value'],
                    'max_transaction_value' => $plan['max_transaction_value'],
                    'description' => $plan['description'],
                ]
            );
        }

        // Optionally, generate more demo commission plans
    }
}