<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class FixAdminChargingPointPermissions extends Command
{
    protected $signature = 'fix:admin-charging-point-permissions';
    protected $description = 'Fix admin charging point permissions';

    public function handle()
    {
        $this->info('Fixing admin charging point permissions...');

        // Get admin role
        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            $this->error('Admin role not found');
            return 1;
        }

        // Create missing permissions
        $permissions = [
            'view_all_charging_points',
            'view_charging_point_details',
            'edit_charging_point_details',
            'manage_charging_point_status',
            'assign_charging_points',
            'unassign_charging_points',
            'view_charging_point_statistics',
            'export_charging_points'
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
            
            if (!$adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
                $this->line("✅ Added permission: {$permissionName}");
            } else {
                $this->line("ℹ️  Permission already exists: {$permissionName}");
            }
        }

        $this->info('🎉 Admin charging point permissions fixed!');
        
        return 0;
    }
}
