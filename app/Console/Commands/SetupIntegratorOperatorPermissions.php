<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class SetupIntegratorOperatorPermissions extends Command
{
    protected $signature = 'integrator:setup-operator-permissions';
    protected $description = 'Setup integrator permissions for operator management';

    public function handle()
    {
        $this->info('Setting up integrator operator permissions...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define integrator operator permissions
        $integratorOperatorPermissions = [
            'view_own_operators',
            'view_integrator_operators',
            'edit_own_operators',
            'edit_integrator_operators',
            'delete_own_operators',
            'delete_integrator_operators',
            'manage_own_operators',
            'manage_integrator_operators',
            'view_operator_details',
            'edit_operator_details',
            'manage_operator_status',
        ];

        // Create permissions if they don't exist
        $this->info('Creating integrator operator permissions...');
        foreach ($integratorOperatorPermissions as $permission) {
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
            'view_own_operators',
            'view_integrator_operators',
            'edit_own_operators',
            'edit_integrator_operators',
            'delete_own_operators',
            'delete_integrator_operators',
            'manage_own_operators',
            'manage_integrator_operators',
            'view_operator_details',
            'edit_operator_details',
            'manage_operator_status',
        ]);
        $this->info("✅ Assigned operator permissions to integrator role");

        // Check if integrator users exist and verify their permissions
        $integratorUsers = User::role('integrator')->get();
        if ($integratorUsers->count() > 0) {
            $this->info("✅ Found {$integratorUsers->count()} integrator user(s)");
            
            // Test permissions for first integrator
            $testIntegrator = $integratorUsers->first();
            $this->info("Testing permissions for integrator: {$testIntegrator->email}");
            
            $permissions = [
                'view_own_operators',
                'edit_own_operators',
                'manage_own_operators',
                'view_operator_details',
                'edit_operator_details',
                'manage_operator_status'
            ];

            foreach ($permissions as $permission) {
                $can = $testIntegrator->can($permission);
                $status = $can ? '✅' : '❌';
                $this->line("  {$status} {$permission}: " . ($can ? 'Yes' : 'No'));
            }
        } else {
            $this->warn('⚠️  No integrator users found. You may need to create integrator users.');
        }

        $this->info('🎉 Integrator operator permissions setup completed!');
        
        return 0;
    }
}
