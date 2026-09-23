<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommissionPlan;

class CommissionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if plans already exist
        if (CommissionPlan::count() > 0) {
            $this->command->info('Commission plans already exist. Skipping seeder.');
            return;
        }
        
        // Create default commission plan
        CommissionPlan::create([
            'name' => 'Plan Standard',
            'description' => 'Plan de commission standard pour toutes les transactions',
            'admin_percentage' => 5.0,
            'integrator_percentage' => 3.0,
            'partner_percentage' => 2.0,
            'is_default' => true,
            'is_active' => true,
            'applies_to_type' => 'global',
            'priority' => 0,
            'created_by_type' => 'admin',
            'created_by_id' => 1, // Admin user ID
        ]);

        // Create a plan for high-value transactions
        CommissionPlan::create([
            'name' => 'Plan Transactions Élevées',
            'description' => 'Plan de commission pour les transactions de valeur élevée',
            'admin_percentage' => 4.0,
            'integrator_percentage' => 2.5,
            'partner_percentage' => 1.5,
            'min_transaction_value' => 50.0,
            'is_default' => false,
            'is_active' => true,
            'applies_to_type' => 'global',
            'priority' => 10,
            'created_by_type' => 'admin',
            'created_by_id' => 1,
        ]);

        // Create a plan for low-value transactions
        CommissionPlan::create([
            'name' => 'Plan Transactions Faibles',
            'description' => 'Plan de commission pour les transactions de faible valeur',
            'admin_percentage' => 6.0,
            'integrator_percentage' => 3.5,
            'partner_percentage' => 2.5,
            'max_transaction_value' => 10.0,
            'is_default' => false,
            'is_active' => true,
            'applies_to_type' => 'global',
            'priority' => 5,
            'created_by_type' => 'admin',
            'created_by_id' => 1,
        ]);
        
        $this->command->info('Commission plans created successfully.');
    }
}