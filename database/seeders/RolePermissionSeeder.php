<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear existing roles and permissions
        Permission::query()->delete();
        Role::query()->delete();

        // Create permissions
        $permissions = [
            // User management
            'view_users', 'create_users', 'edit_users', 'delete_users',
            
            // Integrator management
            'view_integrators', 'create_integrators', 'edit_integrators', 'delete_integrators',
            'view_own_integrator', 'edit_own_integrator',
            
            // Partner management
            'view_partners', 'create_partners', 'edit_partners', 'delete_partners',
            'view_integrator_partners', 'view_own_partner', 'edit_own_partner',
            
            // Group management
            'view_groups', 'create_groups', 'edit_groups', 'delete_groups',
            'view_partner_groups', 'view_own_groups',
            
            // Charging Point management
            'view_charging_points', 'create_charging_points', 'edit_charging_points', 'delete_charging_points',
            'view_group_charging_points', 'view_own_charging_points',
            
            // Business Profile management
            'view_business_profiles', 'create_business_profiles', 'edit_business_profiles', 'delete_business_profiles',
            
            // Pricing Plan management
            'view_pricing_plans', 'create_pricing_plans', 'edit_pricing_plans', 'delete_pricing_plans',
            
            // Transaction management
            'view_transactions', 'create_transactions', 'view_own_transactions',
            
            // Station management
            'view_stations', 'create_stations', 'edit_stations', 'delete_stations',
            
            // Reports and analytics
            'view_reports', 'view_analytics',
            
            // System settings
            'manage_settings', 'view_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Admin Role - Full access
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Integrator Role
        $integratorRole = Role::create(['name' => 'integrator']);
        $integratorRole->givePermissionTo([
            'view_own_integrator', 'edit_own_integrator',
            'view_partners', 'create_partners', 'edit_partners', 'delete_partners',
            'view_integrator_partners',
            'view_groups', 'create_groups', 'edit_groups', 'delete_groups',
            'view_charging_points', 'create_charging_points', 'edit_charging_points', 'delete_charging_points',
            'view_business_profiles', 'create_business_profiles', 'edit_business_profiles',
            'view_pricing_plans', 'create_pricing_plans', 'edit_pricing_plans',
            'view_transactions', 'view_reports',
        ]);

        // Partner/Operator Role
        $partnerRole = Role::create(['name' => 'partner']);
        $partnerRole->givePermissionTo([
            'view_own_partner', 'edit_own_partner',
            'view_partner_groups', 'view_own_groups', 'create_groups', 'edit_groups',
            'view_group_charging_points', 'view_own_charging_points', 'create_charging_points', 'edit_charging_points',
            'view_pricing_plans',
            'view_transactions', 'view_own_transactions',
        ]);

        // User Role (End user)
        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo([
            'view_own_transactions',
        ]);

        // Create admin user
        $admin = User::create([
            'name' => 'Admin EVON',
            'email' => 'admin@evoncharge.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $admin->assignRole('admin');
    }
}