<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
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

        // Create permissions from config
        $permissions = config('permissions.roles');
        
        foreach ($permissions as $roleName => $roleData) {
            // Create role
            $role = Role::firstOrCreate(['name' => $roleName]);
            
            // Create and assign permissions
            if ($roleName === 'admin') {
                // Admin gets all permissions
                $role->givePermissionTo(Permission::all());
            } else {
                foreach ($roleData['permissions'] as $permission) {
                    $permissionModel = Permission::firstOrCreate(['name' => $permission]);
                    $role->givePermissionTo($permissionModel);
                }
            }
        }

        // Create permission groups
        $permissionGroups = config('permissions.permission_groups');
        
        foreach ($permissionGroups as $groupName => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
        }

        $this->command->info('Permissions and roles seeded successfully!');
    }
}