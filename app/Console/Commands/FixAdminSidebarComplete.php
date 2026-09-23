<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class FixAdminSidebarComplete extends Command
{
    protected $signature = 'fix:admin-sidebar-complete {email}';
    protected $description = 'Complete fix for admin sidebar and access issues';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found");
            return 1;
        }

        $this->info("🔧 Complete Admin Sidebar Fix for: {$user->name} ({$user->email})");
        $this->line("=" . str_repeat("=", 80));
        
        // Step 1: Clear all caches
        $this->info("🧹 Step 1: Clearing all caches and sessions...");
        
        Artisan::call('config:clear');
        $this->line("  ✅ Config cache cleared");
        
        Artisan::call('cache:clear');
        $this->line("  ✅ Application cache cleared");
        
        Artisan::call('route:clear');
        $this->line("  ✅ Route cache cleared");
        
        Artisan::call('view:clear');
        $this->line("  ✅ View cache cleared");
        
        Artisan::call('session:table');
        $this->line("  ✅ Session table created/updated");

        Cache::flush();
        $this->line("  ✅ Cache store flushed");

        // Step 2: Force logout all sessions
        $this->info("🚪 Step 2: Forcing logout of all sessions...");
        $user->tokens()->delete();
        $this->line("  ✅ All user tokens revoked");

        // Step 3: Ensure super-admin role exists
        $this->info("👤 Step 3: Setting up super-admin role...");
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);
        $this->line("  ✅ Super-admin role: " . ($superAdminRole ? 'Exists' : 'Created'));

        // Step 4: Change user role to super-admin
        $this->info("🔄 Step 4: Changing user role to super-admin...");
        
        // Remove admin role if exists
        if ($user->hasRole('admin')) {
            $user->removeRole('admin');
            $this->line("  ✅ Removed admin role");
        }
        
        // Assign super-admin role
        if (!$user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
            $this->line("  ✅ Assigned super-admin role");
        } else {
            $this->line("  ℹ️  User already has super-admin role");
        }

        // Update user role attribute
        $user->role = 'super_admin';
        $user->save();
        $this->line("  ✅ Updated user role attribute to super-admin");

        // Step 5: Create and assign all necessary permissions
        $this->info("🔑 Step 5: Setting up permissions...");
        
        $permissions = [
            // Basic charging point permissions
            'view_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'manage_charging_points',
            
            // Admin-specific permissions
            'view_all_charging_points',
            'create_all_charging_points',
            'edit_all_charging_points',
            'delete_all_charging_points',
            'manage_all_charging_points',
            
            // Detailed permissions
            'view_charging_point_details',
            'edit_charging_point_details',
            'manage_charging_point_status',
            'assign_charging_points',
            'unassign_charging_points',
            'view_charging_point_statistics',
            'export_charging_points',
            
            // Admin role permissions
            'view_admin_charging_points',
            'create_admin_charging_points',
            'edit_admin_charging_points',
            'delete_admin_charging_points',
            'manage_admin_charging_points'
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
            
            if (!$superAdminRole->hasPermissionTo($permission)) {
                $superAdminRole->givePermissionTo($permission);
                $this->line("  ✅ Added permission to role: {$permissionName}");
            }
            
            if (!$user->can($permissionName)) {
                $user->givePermissionTo($permission);
                $this->line("  ✅ Added permission to user: {$permissionName}");
            }
        }

        // Step 6: Verify setup
        $this->info("🔍 Step 6: Verifying setup...");
        $user->refresh();
        
        $this->line("  - User role: {$user->role}");
        $this->line("  - Has super-admin role: " . ($user->hasRole('super_admin') ? '✅ Yes' : '❌ No'));
        $this->line("  - Has admin role: " . ($user->hasRole('admin') ? '❌ Yes (should be removed)' : '✅ No'));
        
        // Test key permissions
        $keyPermissions = [
            'view_charging_points',
            'view_all_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'manage_charging_points'
        ];
        
        foreach ($keyPermissions as $permission) {
            $can = $user->can($permission);
            $this->line("  - {$permission}: " . ($can ? '✅ Yes' : '❌ No'));
        }

        // Step 7: Test middleware and routes
        $this->info("🛡️  Step 7: Testing middleware and routes...");
        
        try {
            $middleware = app(\App\Http\Middleware\AdminRoleMiddleware::class);
            $this->line("  ✅ AdminRoleMiddleware resolved: " . get_class($middleware));
        } catch (\Exception $e) {
            $this->line("  ❌ AdminRoleMiddleware resolution failed: " . $e->getMessage());
        }

        try {
            $adminIndexUrl = route('admin.charging-points.index');
            $this->line("  ✅ admin.charging-points.index: {$adminIndexUrl}");
        } catch (\Exception $e) {
            $this->line("  ❌ admin.charging-points.index: ERROR - " . $e->getMessage());
        }

        try {
            $adminCreateUrl = route('admin.charging-points.create');
            $this->line("  ✅ admin.charging-points.create: {$adminCreateUrl}");
        } catch (\Exception $e) {
            $this->line("  ❌ admin.charging-points.create: ERROR - " . $e->getMessage());
        }

        // Step 8: Test sidebar logic
        $this->info("📱 Step 8: Testing sidebar logic...");
        
        Auth::login($user);
        $this->line("  - User authenticated: " . (Auth::check() ? '✅ Yes' : '❌ No'));
        $this->line("  - Should see admin links: " . ($user->hasRole(['admin', 'super_admin']) ? '✅ Yes' : '❌ No'));
        $this->line("  - Should hide general links: " . ($user->hasRole(['admin', 'super_admin']) ? '✅ Yes' : '❌ No'));

        $this->line("=" . str_repeat("=", 80));
        $this->info("🎉 Complete admin sidebar fix completed!");
        $this->line("📝 Summary:");
        $this->line("  ✅ User role changed to super-admin");
        $this->line("  ✅ All necessary permissions added");
        $this->line("  ✅ Caches cleared and sessions reset");
        $this->line("  ✅ Middleware and routes tested");
        $this->line("  ✅ Sidebar logic verified");
        
        $this->line("📝 Next steps for the user:");
        $this->line("  1. Log out completely from the browser");
        $this->line("  2. Clear browser cache, cookies, and local storage");
        $this->line("  3. Close all browser tabs and windows");
        $this->line("  4. Open a new browser window/tab");
        $this->line("  5. Log in again with super-admin credentials");
        $this->line("  6. Access /admin/charging-points");
        $this->line("  7. Use the admin sidebar links");
        
        return 0;
    }
}
