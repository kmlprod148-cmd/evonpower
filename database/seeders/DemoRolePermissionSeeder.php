<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DemoRolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear existing roles and permissions
        Permission::query()->delete();
        Role::query()->delete();

        // Define permissions
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

            // Commission Plan management
            'view_commission_plans', 'create_commission_plans', 'edit_commission_plans', 'delete_commission_plans',

            // Reports
            'view_reports', 'generate_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create demo roles
        $roles = [
            'Admin' => $permissions,
            'Integrator' => [
                'view_integrators', 'edit_own_integrator',
                'view_partners', 'view_integrator_partners',
                'view_groups', 'view_partner_groups',
                'view_charging_points', 'view_group_charging_points',
                'view_business_profiles',
                'view_pricing_plans',
                'view_transactions', 'view_own_transactions',
                'view_commission_plans',
                'view_reports', 'generate_reports',
            ],
            'Partner' => [
                'view_partners', 'edit_own_partner',
                'view_groups', 'view_own_groups',
                'view_charging_points', 'view_own_charging_points',
                'view_business_profiles',
                'view_pricing_plans',
                'view_transactions', 'view_own_transactions',
                'view_commission_plans',
                'view_reports',
            ],
            'User' => [
                'view_charging_points',
                'view_pricing_plans',
                'view_transactions', 'view_own_transactions',
                'view_reports',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::create(['name' => $roleName]);
            $role->givePermissionTo($rolePermissions);
        }
    }
}