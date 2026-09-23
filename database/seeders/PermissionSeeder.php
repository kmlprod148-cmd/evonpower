<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        \Log::info('Admin role created or found: ' . $adminRole->name);
        $integratorRole = Role::firstOrCreate(['name' => 'integrator']);
        $operatorRole = Role::firstOrCreate(['name' => 'operator']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Define permissions from config
        $permissions = [
            // Charging Points Management
            'view_charging_points',
            'view_integrator_charging_points',
            'view_own_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'show_charging_points',
            'manage_qr_codes',
            'view_qr_codes',

            // Stations Management
            'view_stations',
            'create_stations',
            'edit_stations',
            'delete_stations',
            'show_stations',

            // Group permissions
            'view_groups',
            'view_integrator_groups',
            'view_own_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',
            'show_groups',

            // Partner permissions
            'view_partners',
            'view_all_partners',
            'view_integrator_partners',
            'view_own_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            'show_partners',
            'activate_partners',
            'deactivate_partners',

            // User/Operator permissions
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'show_users',
            'manage_users',
            'activate_users',
            'deactivate_users',

            // Integrator permissions
            'view_integrators',
            'create_integrators',
            'edit_integrators',
            'delete_integrators',

            // Business Profile permissions
            'view_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
            'show_business_profiles',

            // Transaction permissions
            'view_transactions',
            'view_integrator_transactions',
            'manage_transactions',

            // Commission permissions
            'view_commission_settings',
            'manage_commissions',
            'approve_withdrawal_requests',

            // Profile permissions
            'view_own_profile',
            'edit_own_profile',

            // Pricing plan permissions
            'view_pricing_plans',
            'manage_pricing_plans',

            // Dashboard and Reports
            'access_dashboard',
            'view_reports',
            'export_data',

            // Remote Control
            'manage_remote_control',

            // System Permissions
            'manage_system_permissions',
            'view_system_audit',

            // Public charging point permissions
            'view_public_charging_points',
        ];

        // Create permissions
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // Assign permissions to roles

        // Admin has all permissions
        $adminPermissions = Permission::all()->pluck('name')->toArray();

        \Log::info('Assigning ' . count($adminPermissions) . ' permissions to admin role.');
        $adminRole->syncPermissions($adminPermissions);
        \Log::info('Permissions assigned to admin role: ' . $adminRole->permissions->pluck('name')->join(', '));

        // Integrator permissions - from config/permissions.php
        $integratorPermissions = [
            // Charging Points Management
            'view_integrator_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'show_charging_points',

            // Partners/Operators Management
            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            'show_partners',
            'activate_partners',
            'deactivate_partners',

            // Users/Operators Management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'show_users',
            'activate_users',
            'deactivate_users',

            // Groups Management
            'view_integrator_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',
            'show_groups',

            // Business Profiles Management
            'view_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
            'show_business_profiles',

            // Financial Management
            'view_transactions',
            'view_integrator_transactions',
            'approve_withdrawal_requests',
            'manage_pricing_plans',

            // Dashboard and Reports
            'access_dashboard',
            'view_reports',
            'export_data',

            // Remote Control
            'manage_remote_control',

            // System Permissions
            'manage_system_permissions',
            'view_system_audit',
        ];
        $integratorRole->syncPermissions($integratorPermissions);

        // Operator permissions
        $operatorPermissions = [
            // Own Charging Points Management
            'view_charging_points',
            'view_own_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'show_charging_points',

            // Own Groups Management
            'view_own_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',
            'show_groups',

            // Own Transactions
            'view_transactions',
            'export_data',

            // Profile Management
            'view_own_profile',
            'edit_own_profile',
            'access_dashboard',

            // Limited Business Profile Access
            'view_business_profiles',
        ];
        $operatorRole->syncPermissions($operatorPermissions);

        // User permissions
        $userPermissions = [
            'view_public_charging_points',
            'view_own_profile',
            'edit_own_profile',
        ];
        $userRole->syncPermissions($userPermissions);
    }
}