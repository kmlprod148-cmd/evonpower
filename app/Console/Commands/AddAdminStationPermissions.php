<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddAdminStationPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:add-station-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add station management permissions to admin role and users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Adding station management permissions to admin...");

        // Station permissions for admin
        $stationPermissions = [
            'view_stations',
            'create_stations',
            'edit_stations',
            'delete_stations',
            'manage_stations',
        ];

        // Create permissions if they don't exist
        foreach ($stationPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
            $this->line("✅ Ensured permission exists: {$permission}");
        }

        // Find admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($stationPermissions);
            $this->info("✅ Station permissions assigned to admin role");
        } else {
            $this->error("❌ Admin role not found!");
            return 1;
        }

        // Find super-admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($stationPermissions);
            $this->info("✅ Station permissions assigned to super-admin role");
        }

        // Assign permissions to all admin users
        $adminUsers = User::role('admin')->get();
        foreach ($adminUsers as $adminUser) {
            $adminUser->givePermissionTo($stationPermissions);
            $this->line("✅ Station permissions assigned to admin user: {$adminUser->name}");
        }

        // Test permissions for admin users
        $this->info("Testing station permissions for admin users:");
        foreach ($adminUsers as $adminUser) {
            $this->line("  Testing permissions for: {$adminUser->name}");
            foreach ($stationPermissions as $permission) {
                $hasPermission = $adminUser->can($permission);
                $status = $hasPermission ? "✅ YES" : "❌ NO";
                $this->line("    - {$permission}: {$status}");
            }
        }

        $this->info("🎉 Station permissions setup completed successfully!");
        return 0;
    }
}
