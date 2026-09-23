<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:add-permissions {user_id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all permissions to admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $this->info("Adding permissions to admin user ID={$userId}...");

        // Find the admin user
        $admin = User::find($userId);

        if (!$admin) {
            $this->error("Admin user with ID={$userId} not found!");
            return 1;
        }

        $this->info("Found admin user: {$admin->name} (ID: {$admin->id})");

        // Get all permissions
        $allPermissions = Permission::all();

        if ($allPermissions->isEmpty()) {
            $this->error("No permissions found in database!");
            $this->info("Please run the migration first: php artisan migrate");
            return 1;
        }

        $this->info("Found {$allPermissions->count()} permissions in database");

        // Assign all permissions to admin
        $admin->givePermissionTo($allPermissions);

        $this->info("All permissions assigned to admin user");

        // Verify the assignment
        $adminPermissions = $admin->getAllPermissions();
        $this->info("Admin now has {$adminPermissions->count()} permissions");

        // Show reservation permissions specifically
        $reservationPermissions = $adminPermissions->filter(function($permission) {
            return strpos($permission->name, 'reservation') !== false;
        });

        $this->info("Reservation permissions:");
        foreach ($reservationPermissions as $permission) {
            $this->line("  - {$permission->name}");
        }

        // Check roles
        $roles = $admin->getRoleNames();
        $this->info("Admin roles: " . $roles->implode(', '));

        // Test specific permissions
        $testPermissions = [
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'manage_reservations',
            'view_stations',
            'create_stations',
            'edit_stations',
            'delete_stations'
        ];

        $this->info("Testing specific permissions:");
        foreach ($testPermissions as $permission) {
            $hasPermission = $admin->can($permission);
            $status = $hasPermission ? "✅ YES" : "❌ NO";
            $this->line("  - {$permission}: {$status}");
        }

        $this->info("Admin permissions setup completed successfully!");
        return 0;
    }
}