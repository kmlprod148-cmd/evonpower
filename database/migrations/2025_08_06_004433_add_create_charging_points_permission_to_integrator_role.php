<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find the permission for creating charging points
        $permission = Permission::firstOrCreate(['name' => 'create_charging_points', 'guard_name' => 'web']);

        // Find the integrator role
        $role = Role::firstOrCreate(['name' => 'integrator', 'guard_name' => 'web']);

        // Assign the permission to the role
        if ($role && $permission) {
            $role->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Find the permission
        $permission = Permission::where('name', 'create_charging_points')->where('guard_name', 'web')->first();

        // Find the integrator role
        $role = Role::where('name', 'integrator')->where('guard_name', 'web')->first();

        // Revoke the permission from the role
        if ($role && $permission) {
            $role->revokePermissionTo($permission);
        }
    }
};
