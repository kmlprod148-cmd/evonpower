<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Integrator;

class IntegratorAutoPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->command->info('Setting up automatic integrator permissions...');

        // Define all permissions that integrators should have
        $integratorPermissions = [
            // Charging Points
            'view_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'view_integrator_charging_points',
            'create_integrator_charging_points',
            'edit_integrator_charging_points',
            'delete_integrator_charging_points',
            
            // Partners
            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            'view_integrator_partners',
            'create_integrator_partners',
            'edit_integrator_partners',
            'delete_integrator_partners',
            
            // Groups
            'view_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',
            'view_integrator_groups',
            'create_integrator_groups',
            'edit_integrator_groups',
            'delete_integrator_groups',
            
            // Business Profiles
            'view_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
            
            // Pricing Plans
            'view_pricing_plans',
            'create_pricing_plans',
            'edit_pricing_plans',
            'delete_pricing_plans',
            
            // Transactions
            'view_transactions',
            'view_integrator_transactions',
            'view_reports',
            
            // Dashboard
            'access_dashboard',
            'view_integrator_dashboard',
            
            // Operators
            'view_operators',
            'create_operators',
            'edit_operators',
            'delete_operators',
            'view_integrator_operators',
            'create_integrator_operators',
            'edit_integrator_operators',
            'delete_integrator_operators',
        ];

        // Create permissions if they don't exist
        foreach ($integratorPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Get or create integrator role
        $integratorRole = Role::firstOrCreate(['name' => 'integrator']);
        
        // Assign all permissions to integrator role
        $integratorRole->syncPermissions($integratorPermissions);

        $this->command->info('Integrator role permissions updated successfully.');

        // Fix existing integrators
        $this->fixExistingIntegrators();

        $this->command->info('Integrator auto-permissions setup completed.');
    }

    /**
     * Fix permissions for existing integrators
     */
    private function fixExistingIntegrators()
    {
        $this->command->info('Fixing permissions for existing integrators...');

        $integrators = User::role('integrator')->get();
        
        foreach ($integrators as $integrator) {
            $this->command->info("Fixing permissions for integrator: {$integrator->name} (ID: {$integrator->id})");
            
            // Ensure user has integrator role
            if (!$integrator->hasRole('integrator')) {
                $integrator->assignRole('integrator');
                $this->command->info("  - Assigned integrator role");
            }
            
            // Clear permission cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            // Verify key permissions
            $keyPermissions = [
                'view_charging_points',
                'view_integrator_charging_points',
                'view_partners',
                'view_integrator_partners',
                'access_dashboard'
            ];
            
            foreach ($keyPermissions as $permission) {
                $hasPermission = $integrator->can($permission);
                $this->command->info("  - {$permission}: " . ($hasPermission ? 'YES' : 'NO'));
            }
        }

        $this->command->info('Existing integrators fixed.');
    }
}
