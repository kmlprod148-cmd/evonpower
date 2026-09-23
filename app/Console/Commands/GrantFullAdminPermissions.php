<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class GrantFullAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:grant-full-permissions 
                            {email=admin@evonpower.com : The email of the admin user}
                            {--create-missing : Create missing permissions from config}
                            {--force : Force operation even if user already has permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant ALL permissions to admin user for full control of the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $createMissing = $this->option('create-missing');
        $force = $this->option('force');

        $this->info("🔐 Granting FULL permissions to admin: {$email}");
        $this->info("================================================");

        // Find the admin user
        $admin = User::where('email', $email)->first();

        if (!$admin) {
            $this->error("❌ Admin user with email '{$email}' not found!");
            $this->warn("💡 Try running: php artisan admin:seed-credentials");
            return 1;
        }

        $this->info("✅ Found admin user: {$admin->name} (ID: {$admin->id})");

        // Ensure admin has admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
            $this->info("✅ Assigned 'admin' role to user");
        } else {
            $this->info("✅ User already has 'admin' role");
        }

        // Create missing permissions if requested
        if ($createMissing) {
            $this->info("📝 Creating missing permissions from config...");
            $this->createMissingPermissions();
        }

        // Get ALL permissions from database
        $allPermissions = Permission::all();

        if ($allPermissions->isEmpty()) {
            $this->error("❌ No permissions found in database!");
            $this->warn("💡 Run: php artisan db:seed --class=RolesAndPermissionsSeeder");
            return 1;
        }

        $this->info("📋 Found {$allPermissions->count()} permissions in database");

        // Clear permission cache first
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info("🧹 Cleared permission cache");

        // Get current permissions count
        $currentPermissions = $admin->getAllPermissions();
        $currentCount = $currentPermissions->count();

        if ($currentCount === $allPermissions->count() && !$force) {
            $this->warn("⚠️  User already has all {$currentCount} permissions!");
            if (!$this->confirm('Do you want to re-assign them anyway?')) {
                $this->info("Operation cancelled.");
                return 0;
            }
        }

        // Assign ALL permissions to admin user
        $this->info("🔧 Assigning ALL permissions to admin user...");
        $admin->syncPermissions($allPermissions);

        // Also assign all permissions to admin role (for future admins)
        $this->info("🔧 Assigning ALL permissions to 'admin' role...");
        $adminRole->syncPermissions($allPermissions);

        // Clear cache again
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Verify the assignment
        $admin->refresh();
        $finalPermissions = $admin->getAllPermissions();
        $finalCount = $finalPermissions->count();

        $this->info("");
        $this->info("✅ SUCCESS! Admin now has {$finalCount} permissions");
        $this->info("");

        // Show permission categories
        $this->displayPermissionSummary($finalPermissions);

        // Test critical permissions
        $this->testCriticalPermissions($admin);

        $this->info("");
        $this->info("🎉 Full admin permissions granted successfully!");
        $this->info("💡 Admin user '{$email}' now has COMPLETE CONTROL of the application");
        
        return 0;
    }

    /**
     * Create missing permissions from config
     */
    private function createMissingPermissions(): void
    {
        $permissionsConfig = config('permissions');
        
        if (!$permissionsConfig) {
            $this->warn("⚠️  No permissions config found");
            return;
        }

        $created = 0;
        
        // Get all permissions from config
        $allConfigPermissions = [];
        
        if (isset($permissionsConfig['roles'])) {
            foreach ($permissionsConfig['roles'] as $role => $data) {
                if (isset($data['permissions']) && is_array($data['permissions'])) {
                    foreach ($data['permissions'] as $permission) {
                        if ($permission !== '*') {
                            $allConfigPermissions[] = $permission;
                        }
                    }
                }
            }
        }
        
        if (isset($permissionsConfig['permission_groups'])) {
            foreach ($permissionsConfig['permission_groups'] as $group => $perms) {
                $allConfigPermissions = array_merge($allConfigPermissions, $perms);
            }
        }

        // Also get permissions from RolesAndPermissionsSeeder
        $seederPermissions = $this->getSeederPermissions();
        $allConfigPermissions = array_merge($allConfigPermissions, $seederPermissions);

        // Remove duplicates
        $allConfigPermissions = array_unique($allConfigPermissions);

        // Create missing permissions (database-agnostic to avoid SQLite schema issues)
        $driver = DB::getDriverName();
        
        foreach ($allConfigPermissions as $permissionName) {
            try {
                // Check if permission exists using raw SQL to avoid schema checks
                if ($driver === 'sqlite') {
                    $exists = DB::selectOne(
                        "SELECT COUNT(*) as count FROM permissions WHERE name = ?",
                        [$permissionName]
                    );
                    $exists = $exists && $exists->count > 0;
                } else {
                    $exists = DB::table('permissions')->where('name', $permissionName)->exists();
                }
                
                if (!$exists) {
                    // Use DB::table to avoid Eloquent schema checks that cause SQLite issues
                    DB::table('permissions')->insert([
                        'name' => $permissionName,
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $created++;
                }
            } catch (\Exception $e) {
                // If insert fails (e.g., duplicate or constraint violation), skip
                // This is expected if permission already exists
                if (strpos($e->getMessage(), 'UNIQUE constraint') === false && 
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    $this->warn("   ⚠️  Could not create permission: {$permissionName} - " . $e->getMessage());
                }
            }
        }

        if ($created > 0) {
            $this->info("✅ Created {$created} new permissions");
        } else {
            $this->info("✅ All permissions already exist");
        }
    }

    /**
     * Get permissions from RolesAndPermissionsSeeder
     */
    private function getSeederPermissions(): array
    {
        // This is a comprehensive list from the seeder
        return [
            // Authentication & Authorization
            'view_profile', 'update_profile', 'manage_roles', 'manage_permissions',
            'assign_roles', 'assign_permissions',
            
            // Dashboard
            'view_admin_dashboard', 'view_integrator_dashboard', 'view_partner_dashboard',
            'view_user_dashboard', 'view_dashboard_stats', 'view_dashboard_chart_data',
            
            // Integrators
            'view_integrators', 'create_integrators', 'edit_integrators', 'delete_integrators',
            'activate_integrators', 'deactivate_integrators', 'export_integrators',
            'view_integrator_charging_points', 'view_integrator_stats',
            
            // Partners
            'view_partners', 'create_partners', 'edit_partners', 'delete_partners',
            'bulk_update_partners', 'export_partners', 'view_partner_charging_points',
            'view_partner_stats',
            
            // Groups
            'view_groups', 'create_groups', 'edit_groups', 'delete_groups',
            'assign_charging_points_to_group', 'manage_group_location', 'bulk_update_groups',
            
            // Charging Points
            'view_charging_points', 'create_charging_points', 'edit_charging_points',
            'delete_charging_points', 'generate_qr_code', 'regenerate_qr_code',
            'download_qr_code', 'bulk_generate_qr_code', 'manage_charging_point_status',
            'manage_connectors', 'assign_pricing_plan_to_charging_point',
            'manage_geolocation', 'schedule_maintenance', 'simulate_remote_control',
            'view_public_offer_page', 'batch_assign_pricing_plan',
            
            // Offers
            'view_offers', 'create_offers', 'edit_offers', 'delete_offers',
            
            // Pricing Plans
            'view_pricing_plans', 'create_pricing_plans', 'edit_pricing_plans',
            'delete_pricing_plans', 'activate_pricing_plans', 'set_default_pricing_plan',
            'calculate_price', 'get_plan_for_charging_point', 'get_public_offers',
            
            // Business Profiles
            'view_business_profiles', 'create_business_profiles', 'edit_business_profiles',
            'delete_business_profiles', 'activate_business_profiles', 'deactivate_business_profiles',
            
            // Transactions
            'view_transactions', 'create_transactions', 'edit_transactions',
            'delete_transactions', 'export_transactions', 'view_integrator_transactions',
            'view_partner_transactions', 'view_operator_transactions',
            
            // Reservations
            'view_reservations', 'create_reservations', 'edit_reservations',
            'delete_reservations', 'approve_reservations', 'cancel_reservations',
            'manage_reservations',
            
            // Stations
            'view_stations', 'create_stations', 'edit_stations', 'delete_stations',
            
            // Users
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'activate_users', 'deactivate_users', 'export_users',
            
            // Reports
            'view_reports', 'create_reports', 'export_reports', 'generate_reports',
            
            // Settings
            'view_settings', 'edit_settings', 'manage_settings',
            
            // Wallet
            'view_wallet', 'manage_wallet', 'credit_wallet', 'debit_wallet',
            
            // System
            'manage_system_permissions', 'view_system_audit', 'access_dashboard',
            'export_data', 'view_analytics', 'view_statistics', 'export_analytics',
            
            // Remote Control
            'manage_remote_control',
            
            // Financial
            'approve_withdrawal_requests', 'issue_refunds', 'manage_pricing',
            'manage_pricing_plans', 'manage_financials',
        ];
    }

    /**
     * Display permission summary by category
     */
    private function displayPermissionSummary($permissions): void
    {
        $categories = [
            'Charging Points' => ['charging_point', 'connector', 'qr_code'],
            'Groups' => ['group'],
            'Partners' => ['partner'],
            'Integrators' => ['integrator'],
            'Users' => ['user', 'operator'],
            'Transactions' => ['transaction'],
            'Reservations' => ['reservation'],
            'Pricing Plans' => ['pricing', 'plan', 'offer'],
            'Business Profiles' => ['business_profile'],
            'Stations' => ['station'],
            'Reports' => ['report'],
            'Settings' => ['setting'],
            'Wallet' => ['wallet'],
            'Dashboard' => ['dashboard'],
            'System' => ['system', 'audit', 'permission', 'role'],
        ];

        $this->info("📊 Permission Summary by Category:");
        $this->info("");

        foreach ($categories as $category => $keywords) {
            $count = $permissions->filter(function ($permission) use ($keywords) {
                foreach ($keywords as $keyword) {
                    if (stripos($permission->name, $keyword) !== false) {
                        return true;
                    }
                }
                return false;
            })->count();

            if ($count > 0) {
                $this->line("   {$category}: {$count} permissions");
            }
        }

        $other = $permissions->count() - $permissions->filter(function ($permission) use ($categories) {
            foreach ($categories as $keywords) {
                foreach ($keywords as $keyword) {
                    if (stripos($permission->name, $keyword) !== false) {
                        return true;
                    }
                }
            }
            return false;
        })->count();

        if ($other > 0) {
            $this->line("   Other: {$other} permissions");
        }
    }

    /**
     * Test critical permissions
     */
    private function testCriticalPermissions(User $admin): void
    {
        $this->info("");
        $this->info("🧪 Testing Critical Permissions:");
        $this->info("");

        $criticalPermissions = [
            'view_admin_dashboard',
            'manage_roles',
            'manage_permissions',
            'view_integrators',
            'create_integrators',
            'edit_integrators',
            'delete_integrators',
            'view_partners',
            'create_partners',
            'view_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'view_transactions',
            'view_reservations',
            'manage_reservations',
            'view_pricing_plans',
            'create_pricing_plans',
            'view_users',
            'create_users',
            'view_reports',
            'manage_settings',
        ];

        $passed = 0;
        $failed = 0;

        foreach ($criticalPermissions as $permission) {
            $hasPermission = $admin->can($permission);
            if ($hasPermission) {
                $this->line("   ✅ {$permission}");
                $passed++;
            } else {
                $this->error("   ❌ {$permission}");
                $failed++;
            }
        }

        $this->info("");
        $this->info("📈 Test Results: {$passed} passed, {$failed} failed");
    }
}

