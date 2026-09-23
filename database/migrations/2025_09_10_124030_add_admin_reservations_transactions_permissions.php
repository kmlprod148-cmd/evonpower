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
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create admin reservations permissions
        $adminReservationsPermissions = [
            'view_admin_reservations',
            'edit_admin_reservations',
            'delete_admin_reservations',
            'view_admin_reservation_details',
            'confirm_admin_reservations',
            'reject_admin_reservations',
            'manage_admin_reservations',
        ];

        // Create admin transactions permissions
        $adminTransactionsPermissions = [
            'view_admin_transactions',
            'edit_admin_transactions',
            'delete_admin_transactions',
            'view_admin_transaction_details',
            'manage_admin_transactions',
            'export_admin_transactions',
        ];

        // Create all permissions
        $allPermissions = array_merge($adminReservationsPermissions, $adminTransactionsPermissions);

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to admin and super_admin roles
        $adminRoles = ['admin', 'super_admin'];
        
        foreach ($adminRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($allPermissions);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Remove permissions from roles first
        $adminRoles = ['admin', 'super_admin'];
        $allPermissions = [
            'view_admin_reservations',
            'edit_admin_reservations',
            'delete_admin_reservations',
            'view_admin_reservation_details',
            'confirm_admin_reservations',
            'reject_admin_reservations',
            'manage_admin_reservations',
            'view_admin_transactions',
            'edit_admin_transactions',
            'delete_admin_transactions',
            'view_admin_transaction_details',
            'manage_admin_transactions',
            'export_admin_transactions',
        ];

        foreach ($adminRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->revokePermissionTo($allPermissions);
            }
        }

        // Delete permissions
        foreach ($allPermissions as $permission) {
            Permission::where('name', $permission)->where('guard_name', 'web')->delete();
        }
    }
};
