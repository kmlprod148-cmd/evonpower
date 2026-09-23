<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create reservation permissions
        $permissions = [
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'confirm_reservations',
            'cancel_reservations',
            'manage_reservations',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'confirm_reservations',
            'cancel_reservations',
            'manage_reservations',
        ];

        foreach ($permissions as $permission) {
            Permission::where('name', $permission)->delete();
        }
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles(): void
    {
        $roles = [
            'admin' => [
                'view_reservations',
                'create_reservations',
                'edit_reservations',
                'delete_reservations',
                'confirm_reservations',
                'cancel_reservations',
                'manage_reservations',
            ],
            'super-admin' => [
                'view_reservations',
                'create_reservations',
                'edit_reservations',
                'delete_reservations',
                'confirm_reservations',
                'cancel_reservations',
                'manage_reservations',
            ],
            'integrator' => [
                'view_reservations',
                'manage_reservations',
            ],
            'partner' => [
                'view_reservations',
                'manage_reservations',
            ],
            'user' => [
                'view_reservations',
                'create_reservations',
                'edit_reservations',
                'cancel_reservations',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }
    }
};