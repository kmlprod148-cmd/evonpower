<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class GrantOperatorCreateChargingPointsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure cache is cleared so changes take effect immediately
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');
        $permissionName = 'create_charging_points';

        // Make sure permission exists (idempotent)
        Permission::findOrCreate($permissionName, $guard);

        // Make sure the operator role exists (idempotent)
        $operator = Role::findOrCreate('operator', $guard);

        // Grant the permission if not already granted (idempotent)
        if (!$operator->hasPermissionTo($permissionName)) {
            $operator->givePermissionTo($permissionName);
        }
    }
}

