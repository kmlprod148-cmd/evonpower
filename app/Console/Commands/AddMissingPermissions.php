<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddMissingPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:add-missing-permissions {user_id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all missing permissions to admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $this->info("Adding missing permissions to admin user ID={$userId}...");

        // Find the admin user
        $admin = User::find($userId);

        if (!$admin) {
            $this->error("Admin user with ID={$userId} not found!");
            return 1;
        }

        $this->info("Found admin user: {$admin->name} (ID: {$admin->id})");

        // Define all permissions that should exist
        $allRequiredPermissions = [
            // Station permissions
            'view_stations',
            'create_stations',
            'edit_stations',
            'delete_stations',
            'manage_stations',
            
            // Reservation permissions
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'confirm_reservations',
            'cancel_reservations',
            'manage_reservations',
            
            // Charging point permissions
            'view_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'manage_charging_points',
            
            // Group permissions
            'view_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',
            'manage_groups',
            
            // Partner permissions
            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            'manage_partners',
            
            // Integrator permissions
            'view_integrators',
            'create_integrators',
            'edit_integrators',
            'delete_integrators',
            'manage_integrators',
            
            // Transaction permissions
            'view_transactions',
            'create_transactions',
            'edit_transactions',
            'delete_transactions',
            'manage_transactions',
            
            // User permissions
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_users',
            
            // Dashboard permissions
            'view_admin_dashboard',
            'view_dashboard_stats',
            'view_dashboard_chart_data',
            
            // Report permissions
            'view_reports',
            'create_reports',
            'edit_reports',
            'delete_reports',
            'export_reports',
            
            // Business profile permissions
            'view_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
            'manage_business_profiles',
            
            // Pricing plan permissions
            'view_pricing_plans',
            'create_pricing_plans',
            'edit_pricing_plans',
            'delete_pricing_plans',
            'manage_pricing_plans',
            
            // Commission permissions
            'view_commission_plans',
            'create_commission_plans',
            'edit_commission_plans',
            'delete_commission_plans',
            'manage_commission_plans',
            
            // Notification permissions
            'view_admin_notifications',
            'manage_notifications',
            
            // Financial transaction permissions
            'view_financial_transactions',
            'create_financial_transactions',
            'edit_financial_transactions',
            'delete_financial_transactions',
            'manage_financial_transactions',
            
            // Account permissions
            'view_accounts',
            'manage_accounts',
        ];

        // Create missing permissions
        $createdCount = 0;
        foreach ($allRequiredPermissions as $permission) {
            $perm = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
            
            if ($perm->wasRecentlyCreated) {
                $createdCount++;
                $this->line("Created permission: {$permission}");
            }
        }

        if ($createdCount > 0) {
            $this->info("Created {$createdCount} new permissions");
        } else {
            $this->info("All permissions already exist");
        }

        // Get all permissions from database
        $allPermissions = Permission::all();
        $this->info("Total permissions in database: {$allPermissions->count()}");

        // Assign all permissions to admin
        $admin->givePermissionTo($allPermissions);

        $this->info("All permissions assigned to admin user");

        // Verify the assignment
        $adminPermissions = $admin->getAllPermissions();
        $this->info("Admin now has {$adminPermissions->count()} permissions");

        // Test specific permissions
        $testPermissions = [
            'view_stations',
            'create_stations',
            'edit_stations',
            'delete_stations',
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'manage_reservations',
            'view_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
        ];

        $this->info("Testing specific permissions:");
        foreach ($testPermissions as $permission) {
            $hasPermission = $admin->can($permission);
            $status = $hasPermission ? "✅ YES" : "❌ NO";
            $this->line("  - {$permission}: {$status}");
        }

        // Check roles
        $roles = $admin->getRoleNames();
        $this->info("Admin roles: " . $roles->implode(', '));

        $this->info("Missing permissions setup completed successfully!");
        return 0;
    }
}