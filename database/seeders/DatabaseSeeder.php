<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \Log::info('Starting Database Seeder');
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        $this->call([
            TariffPlanSeeder::class,
            // 1. Base permissions and roles
            DemoRolePermissionSeeder::class,
            RolesAndPermissionsSeeder::class,
            GrantOperatorCreateChargingPointsPermissionSeeder::class,
            IntegratorAutoPermissionsSeeder::class,

            // 2. Users with different roles
            DemoUserSeeder::class,
            // UpdateIntegratorPermissionsSeeder::class, // Removed - functionality handled by IntegratorAutoPermissionsSeeder

            // 3. Business profiles and VAT rates
            DemoVatRateSeeder::class,
            // DemoBusinessProfileSeeder::class,

            // 4. Integrators
            DemoIntegratorSeeder::class,

            // 5. Partners
            DemoPartnerSeeder::class,

            // 6. Groups and Stations
            DemoGroupSeeder::class,
            DemoStationSeeder::class,

            // 7. Pricing plans
            DemoPricingPlanSeeder::class,

            // 8. Charging points and connectors
            DemoChargingPointSeeder::class,

            // 9. Commission plans
            DemoCommissionPlanSeeder::class,

            // 10. Transactions and usage data
            // DemoTransactionSeeder::class,
            
            // 11. Business Profile Demo Transactions
            DemoBusinessProfileSeeder::class,

            // 11. Reports
            DemoReportSeeder::class,
        ]);
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // Création des rôles nécessaires pour les tests et l'application
        \Spatie\Permission\Models\Role::findOrCreate('admin');
        \Spatie\Permission\Models\Role::findOrCreate('integrator');
        \Spatie\Permission\Models\Role::findOrCreate('partner');
        \Spatie\Permission\Models\Role::findOrCreate('user');
        \Spatie\Permission\Models\Role::findOrCreate('operator');

        // Verify admin permissions after seeding
        $adminUser = \App\Models\User::where('email', 'admin@evon.com')->first();
        if ($adminUser) {
            $adminRoles = $adminUser->getRoleNames();
            $adminPermissions = $adminUser->getAllPermissions()->pluck('name');
            \Log::info('Admin User: ' . $adminUser->email);
            \Log::info('Admin Roles: ' . $adminRoles->join(', '));
            \Log::info('Admin Permissions: ' . $adminPermissions->join(', '));
        } else {
            \Log::warning('Admin user not found after seeding.');
        }

        // Fix integrator permissions for user ID 50
        $integratorUser = \App\Models\User::find(50);
        if ($integratorUser) {
            \Log::info('Fixing permissions for integrator user ID 50: ' . $integratorUser->email);
            
            // Ensure user has integrator role
            if (!$integratorUser->hasRole('integrator')) {
                $integratorUser->assignRole('integrator');
                \Log::info('Assigned integrator role to user 50');
            }
            
            // Clear permission cache
            \Spatie\Permission\PermissionRegistrar::forgetCachedPermissions();
            
            // Verify permissions
            $hasPermission = $integratorUser->can('view_integrator_charging_points');
            \Log::info('User 50 has view_integrator_charging_points permission: ' . ($hasPermission ? 'YES' : 'NO'));
            
            if (!$hasPermission) {
                \Log::warning('User 50 still does not have the required permission. Manual intervention needed.');
            }
        } else {
            \Log::warning('Integrator user with ID 50 not found.');
        }

        // Help/FAQ content
        $this->call([
            HelpSeeder::class,
        ]);

        \Log::info('Finished Database Seeder');
    }
}