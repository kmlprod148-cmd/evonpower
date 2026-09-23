<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Install the `access-admin-panel` Spatie permission and grant it to the
 * existing admin / super_admin roles so current administrators are NOT
 * locked out by the new gate. Further per-user grants happen via the
 * `admin:grant` artisan command or any user-edit UI that exposes
 * Spatie permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        $name = config('admin.permission', 'access-admin-panel');
        $guard = config('auth.defaults.guard', 'web');

        $permission = Permission::firstOrCreate(
            ['name' => $name, 'guard_name' => $guard],
        );

        foreach (['admin', 'super_admin'] as $roleName) {
            $role = Role::where('name', $roleName)
                ->where('guard_name', $guard)
                ->first();

            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $name = config('admin.permission', 'access-admin-panel');
        $guard = config('auth.defaults.guard', 'web');

        $permission = Permission::where('name', $name)
            ->where('guard_name', $guard)
            ->first();

        if ($permission) {
            DB::table(config('permission.table_names.model_has_permissions'))
                ->where('permission_id', $permission->getKey())
                ->delete();
            DB::table(config('permission.table_names.role_has_permissions'))
                ->where('permission_id', $permission->getKey())
                ->delete();
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
