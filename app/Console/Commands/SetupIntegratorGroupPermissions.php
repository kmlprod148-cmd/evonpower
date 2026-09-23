<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class SetupIntegratorGroupPermissions extends Command
{
    protected $signature = 'integrator:setup-group-permissions';
    protected $description = 'Setup integrator permissions for group management';

    public function handle()
    {
        $this->info('Setting up integrator group permissions...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define integrator group permissions
        $integratorGroupPermissions = [
            'view_own_groups',
            'create_groups',
            'edit_own_groups',
            'delete_own_groups',
            'manage_own_groups',
            'view_integrator_groups',
            'manage_integrator_groups',
        ];

        // Create permissions if they don't exist
        $this->info('Creating integrator group permissions...');
        foreach ($integratorGroupPermissions as $permission) {
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

        // Find or create integrator role
        $integratorRole = Role::firstOrCreate([
            'name' => 'integrator',
            'guard_name' => 'web'
        ]);

        // Assign permissions to integrator role
        $this->info('Assigning permissions to integrator role...');
        $integratorRole->givePermissionTo([
            'view_own_groups',
            'create_groups',
            'edit_own_groups',
            'delete_own_groups',
            'manage_own_groups',
            'view_integrator_groups',
            'manage_integrator_groups',
        ]);
        $this->info("✅ Assigned group permissions to integrator role");

        // Check if integrator users exist and verify their permissions
        $integratorUsers = User::role('integrator')->get();
        if ($integratorUsers->count() > 0) {
            $this->info("✅ Found {$integratorUsers->count()} integrator user(s)");
            
            // Test permissions for first integrator
            $testIntegrator = $integratorUsers->first();
            $this->info("Testing permissions for integrator: {$testIntegrator->email}");
            
            $permissions = [
                'create_groups',
                'edit_own_groups',
                'view_own_groups',
                'manage_own_groups',
                'manage_integrator_groups'
            ];

            foreach ($permissions as $permission) {
                $can = $testIntegrator->can($permission);
                $status = $can ? '✅' : '❌';
                $this->line("  {$status} {$permission}: " . ($can ? 'Yes' : 'No'));
            }
        } else {
            $this->warn('⚠️  No integrator users found. You may need to create integrator users.');
        }

        $this->info('🎉 Integrator group permissions setup completed!');
        
        return 0;
    }
}
