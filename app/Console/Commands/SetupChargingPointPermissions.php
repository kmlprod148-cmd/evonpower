<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class SetupChargingPointPermissions extends Command
{
    protected $signature = 'admin:setup-charging-point-permissions';
    protected $description = 'Setup admin permissions for charging points access';

    public function handle()
    {
        $this->info('Setting up admin permissions for charging points...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define charging point permissions
        $chargingPointPermissions = [
            'view_charging_points',
            'view_any_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'edit_any_charging_points',
            'delete_charging_points',
            'delete_any_charging_points',
            'manage_charging_points',
            'view_integrator_charging_points',
            'view_own_charging_points',
        ];

        // Create permissions if they don't exist
        $this->info('Creating charging point permissions...');
        foreach ($chargingPointPermissions as $permission) {
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

        // Assign all charging point permissions to admin role
        $this->info('Assigning permissions to admin role...');
        $adminRole->givePermissionTo($chargingPointPermissions);
        $this->info("✅ Assigned " . count($chargingPointPermissions) . " permissions to admin role");

        // Check if admin users exist and assign role if needed
        $adminUsers = User::role('admin')->get();
        if ($adminUsers->count() > 0) {
            $this->info("✅ Found {$adminUsers->count()} admin user(s) with proper role");
        } else {
            $this->warn('⚠️  No admin users found. You may need to create an admin user.');
        }

        // Verify permissions are working
        $this->info('Verifying permissions...');
        $testUser = User::role('admin')->first();
        if ($testUser) {
            $canView = $testUser->can('view_charging_points');
            $canCreate = $testUser->can('create_charging_points');
            $canEdit = $testUser->can('edit_charging_points');
            
            $this->info("✅ Admin user permissions verified:");
            $this->line("  - Can view charging points: " . ($canView ? 'Yes' : 'No'));
            $this->line("  - Can create charging points: " . ($canCreate ? 'Yes' : 'No'));
            $this->line("  - Can edit charging points: " . ($canEdit ? 'Yes' : 'No'));
        }

        $this->info('🎉 Admin permissions for charging points setup completed!');
        
        return 0;
    }
}
