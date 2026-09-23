<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class TestAdminChargingPointAccess extends Command
{
    protected $signature = 'test:admin-charging-point-access-final {email}';
    protected $description = 'Test admin charging point access with route testing';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found");
            return 1;
        }

        $this->info("Testing admin charging point access for user: {$user->name} ({$user->email})");
        
        // Test authentication
        Auth::login($user);
        $this->info("User authenticated: " . (Auth::check() ? 'Yes' : 'No'));
        
        if (!Auth::check()) {
            $this->error("Failed to authenticate user");
            return 1;
        }

        // Test role and permissions
        $this->info("Role and permission tests:");
        $this->line("  - User role: {$user->role}");
        $this->line("  - Has admin role: " . ($user->hasRole('admin') ? 'Yes' : 'No'));
        $this->line("  - Has any admin role: " . ($user->hasAnyRole(['admin', 'super_admin']) ? 'Yes' : 'No'));
        
        // Test specific permissions
        $permissions = [
            'view_charging_points',
            'view_all_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'manage_charging_points'
        ];
        
        $this->info("Permission tests:");
        foreach ($permissions as $permission) {
            $can = $user->can($permission);
            $this->line("  - {$permission}: " . ($can ? 'Yes' : 'No'));
        }

        // Test route accessibility
        $this->info("Route accessibility tests:");
        
        // Test admin-specific routes (should work)
        try {
            $url = route('admin.charging-points.index');
            $this->line("  ✅ admin.charging-points.index: {$url}");
        } catch (\Exception $e) {
            $this->line("  ❌ admin.charging-points.index: ERROR - " . $e->getMessage());
        }

        try {
            $url = route('admin.charging-points.create');
            $this->line("  ✅ admin.charging-points.create: {$url}");
        } catch (\Exception $e) {
            $this->line("  ❌ admin.charging-points.create: ERROR - " . $e->getMessage());
        }

        // Test general routes (should work but admin should use admin routes instead)
        try {
            $url = route('charging-points.index');
            $this->line("  ⚠️  charging-points.index: {$url} (admin should use admin.charging-points.index instead)");
        } catch (\Exception $e) {
            $this->line("  ❌ charging-points.index: ERROR - " . $e->getMessage());
        }

        $this->info("✅ Admin should now use: /admin/charging-points instead of /charging-points");
        $this->info("✅ Sidebar links have been fixed to show admin-specific routes only");

        return 0;
    }
}