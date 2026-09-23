<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class SetupOperatorPermissions extends Command
{
    protected $signature = 'operator:setup-permissions';
    protected $description = 'Setup operator permissions for charging points and pricing plans';

    public function handle()
    {
        $this->info('Setting up operator permissions...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define operator permissions
        $operatorPermissions = [
            // Charging Point permissions
            'view_own_charging_points',
            'create_charging_points',
            'edit_own_charging_points',
            'delete_own_charging_points',
            'manage_own_charging_points',
            
            // Pricing Plan permissions
            'view_own_pricing_plans',
            'create_pricing_plans',
            'edit_own_pricing_plans',
            'delete_own_pricing_plans',
            'manage_own_pricing_plans',
            
            // General operator permissions
            'view_operator_dashboard',
            'manage_operator_settings',
        ];

        // Create permissions if they don't exist
        $this->info('Creating operator permissions...');
        foreach ($operatorPermissions as $permission) {
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

        // Find or create operator role
        $operatorRole = Role::firstOrCreate([
            'name' => 'operator',
            'guard_name' => 'web'
        ]);

        // Assign permissions to operator role
        $this->info('Assigning permissions to operator role...');
        $operatorRole->givePermissionTo([
            'view_own_charging_points',
            'create_charging_points',
            'edit_own_charging_points',
            'delete_own_charging_points',
            'manage_own_charging_points',
            'view_own_pricing_plans',
            'create_pricing_plans',
            'edit_own_pricing_plans',
            'delete_own_pricing_plans',
            'manage_own_pricing_plans',
            'view_operator_dashboard',
            'manage_operator_settings',
        ]);
        $this->info("✅ Assigned permissions to operator role");

        // Check if operator users exist and verify their permissions
        $operatorUsers = User::role('operator')->get();
        if ($operatorUsers->count() > 0) {
            $this->info("✅ Found {$operatorUsers->count()} operator user(s)");
            
            // Test permissions for first operator
            $testOperator = $operatorUsers->first();
            $this->info("Testing permissions for operator: {$testOperator->email}");
            
            $permissions = [
                'create_charging_points',
                'edit_own_charging_points',
                'view_own_charging_points',
                'create_pricing_plans',
                'edit_own_pricing_plans',
                'view_own_pricing_plans',
                'view_operator_dashboard'
            ];

            foreach ($permissions as $permission) {
                $can = $testOperator->can($permission);
                $status = $can ? '✅' : '❌';
                $this->line("  {$status} {$permission}: " . ($can ? 'Yes' : 'No'));
            }
        } else {
            $this->warn('⚠️  No operator users found. You may need to create operator users.');
        }

        $this->info('🎉 Operator permissions setup completed!');
        
        return 0;
    }
}
