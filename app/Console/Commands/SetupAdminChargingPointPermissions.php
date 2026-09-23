<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class SetupAdminChargingPointPermissions extends Command
{
    protected $signature = 'admin:setup-charging-point-permissions';
    protected $description = 'Setup admin permissions for charging point management';

    public function handle()
    {
        $this->info('Setting up admin charging point permissions...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define admin charging point permissions
        $adminChargingPointPermissions = [
            'view_all_charging_points',
            'view_admin_charging_points',
            'create_admin_charging_points',
            'edit_all_charging_points',
            'edit_admin_charging_points',
            'delete_all_charging_points',
            'delete_admin_charging_points',
            'manage_all_charging_points',
            'manage_admin_charging_points',
            'view_charging_point_details',
            'edit_charging_point_details',
            'manage_charging_point_status',
            'assign_charging_points',
            'unassign_charging_points',
            'view_charging_point_statistics',
            'export_charging_points',
        ];

        // Create permissions if they don't exist
        $this->info('Creating admin charging point permissions...');
        foreach ($adminChargingPointPermissions as $permission) {
            $permissionModel = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
            
            if ($permissionModel->wasRecentlyCreated) {
                $this->line("✅ Created permission: {$permission}");
            } else {
                $this->line("ℹ️  Permission already exists: {$permission}");
            }
        }

        // Find or create admin role
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);

        // Assign permissions to admin role
        $this->info('Assigning permissions to admin role...');
        $adminRole->givePermissionTo([
            'view_all_charging_points',
            'view_admin_charging_points',
            'create_admin_charging_points',
            'edit_all_charging_points',
            'edit_admin_charging_points',
            'delete_all_charging_points',
            'delete_admin_charging_points',
            'manage_all_charging_points',
            'manage_admin_charging_points',
            'view_charging_point_details',
            'edit_charging_point_details',
            'manage_charging_point_status',
            'assign_charging_points',
            'unassign_charging_points',
            'view_charging_point_statistics',
            'export_charging_points',
        ]);
        $this->info("✅ Assigned charging point permissions to admin role");

        // Check if admin users exist and verify their permissions
        $adminUsers = User::role('admin')->get();
        if ($adminUsers->count() > 0) {
            $this->info("✅ Found {$adminUsers->count()} admin user(s)");
            
            // Test permissions for first admin
            $testAdmin = $adminUsers->first();
            $this->info("Testing permissions for admin: {$testAdmin->email}");
            
            $permissions = [
                'view_all_charging_points',
                'create_admin_charging_points',
                'edit_all_charging_points',
                'delete_all_charging_points',
                'manage_all_charging_points',
                'view_charging_point_details',
                'edit_charging_point_details',
                'manage_charging_point_status',
                'view_charging_point_statistics',
                'export_charging_points'
            ];

            foreach ($permissions as $permission) {
                $can = $testAdmin->can($permission);
                $status = $can ? '✅' : '❌';
                $this->line("  {$status} {$permission}: " . ($can ? 'Yes' : 'No'));
            }
        } else {
            $this->warn('⚠️  No admin users found. You may need to create admin users.');
        }

        $this->info('🎉 Admin charging point permissions setup completed!');
        
        return 0;
    }
}
