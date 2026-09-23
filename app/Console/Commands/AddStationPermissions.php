<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddStationPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:add-stations {user_id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add station permissions to admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $this->info("Adding station permissions to user ID={$userId}...");

        // Find the user
        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID={$userId} not found!");
            return 1;
        }

        $this->info("Found user: {$user->name} (ID: {$user->id})");

        // Station permissions
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
            $this->line("Ensured permission exists: {$permission}");
        }

        // Assign permissions to user
        $user->givePermissionTo($stationPermissions);

        $this->info("Station permissions assigned to user");

        // Test the permissions
        $this->info("Testing station permissions:");
        foreach ($stationPermissions as $permission) {
            $hasPermission = $user->can($permission);
            $status = $hasPermission ? "✅ YES" : "❌ NO";
            $this->line("  - {$permission}: {$status}");
        }

        // Also assign to admin role if user has it
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($stationPermissions);
            $this->info("Station permissions also assigned to admin role");
        }

        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($stationPermissions);
            $this->info("Station permissions also assigned to super-admin role");
        }

        $this->info("Station permissions setup completed successfully!");
        return 0;
    }
}