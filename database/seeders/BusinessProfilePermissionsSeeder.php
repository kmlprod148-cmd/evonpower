<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BusinessProfilePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define business profile permissions
        $permissions = [
            'view_all_business_profiles',
            'view_integrator_business_profiles',
            'view_own_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
        ];

        // Create permissions
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $integratorRole = Role::firstOrCreate(['name' => 'integrator']);
        $operatorRole = Role::firstOrCreate(['name' => 'operator']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Admin has all business profile permissions
        $adminRole->givePermissionTo([
            'view_all_business_profiles',
            'view_integrator_business_profiles',
            'view_own_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
        ]);

        // Integrator permissions
        $integratorRole->givePermissionTo([
            'view_integrator_business_profiles',
            'view_own_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
        ]);

        // Operator permissions
        $operatorRole->givePermissionTo([
            'view_own_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
        ]);

        // User permissions
        $userRole->givePermissionTo([
            'view_own_business_profiles',
        ]);
    }
}