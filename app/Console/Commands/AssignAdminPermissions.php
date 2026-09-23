<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class AssignAdminPermissions extends Command
{
    protected $signature = 'admin:assign-permissions';
    protected $description = 'Assign all admin permissions to admin and super-admin roles';

    public function handle()
    {
        $this->info('Assigning admin permissions...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Get admin permissions
        $adminPermissions = [
            // Admin Reservations Management
            'view_admin_reservations',
            'edit_admin_reservations',
            'delete_admin_reservations',
            'view_admin_reservation_details',
            'confirm_admin_reservations',
            'reject_admin_reservations',
            'manage_admin_reservations',
            
            // Admin Transactions Management
            'view_admin_transactions',
            'edit_admin_transactions',
            'delete_admin_transactions',
            'view_admin_transaction_details',
            'manage_admin_transactions',
            'export_admin_transactions',
            
            // Regular permissions that admins should have
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'confirm_reservations',
            'cancel_reservations',
            'manage_reservations',
            'view_transactions',
            'export_transactions',
            'manage_refunds',
        ];

        // Create permissions if they don't exist
        foreach ($adminPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Get admin roles
        $adminRole = Role::findByName('admin', 'web');
        $superAdminRole = Role::findByName('super_admin', 'web');

        if (!$adminRole) {
            $this->error('Admin role not found!');
            return 1;
        }

        if (!$superAdminRole) {
            $this->error('Super-admin role not found!');
            return 1;
        }

        // Assign permissions to admin role
        $adminRole->givePermissionTo($adminPermissions);
        $this->info('Assigned permissions to admin role');

        // Assign permissions to super-admin role
        $superAdminRole->givePermissionTo($adminPermissions);
        $this->info('Assigned permissions to super-admin role');

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info('Admin permissions assigned successfully!');
        
        // Show current admin users
        $adminUsers = User::role(['admin', 'super_admin'])->get();
        $this->info('Current admin users:');
        foreach ($adminUsers as $user) {
            $this->line("- {$user->name} ({$user->email}) - Roles: " . $user->getRoleNames()->implode(', '));
        }

        return 0;
    }
}