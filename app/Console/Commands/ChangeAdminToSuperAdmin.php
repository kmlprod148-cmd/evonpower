<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class ChangeAdminToSuperAdmin extends Command
{
    protected $signature = 'change:admin-to-super-admin {email}';
    protected $description = 'Change admin role to super-admin for the specified user';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found");
            return 1;
        }

        $this->info("🔧 Changing role to super-admin for: {$user->name} ({$user->email})");
        $this->line("=" . str_repeat("=", 60));
        
        // Check current role
        $this->info("👤 Current user status:");
        $this->line("  - User ID: {$user->id}");
        $this->line("  - User name: {$user->name}");
        $this->line("  - User email: {$user->email}");
        $this->line("  - Current role: {$user->role}");
        $this->line("  - Current roles: " . $user->getRoleNames()->implode(', '));

        // Create super-admin role if it doesn't exist
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);
        $this->line("  ✅ Super-admin role: " . ($superAdminRole ? 'Exists' : 'Created'));

        // Remove admin role and assign super-admin role
        $this->info("🔄 Changing roles...");
        
        // Remove admin role
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

        // Verify the change
        $this->info("🔍 Verifying role change...");
        $user->refresh();
        $this->line("  - New role attribute: {$user->role}");
        $this->line("  - New roles: " . $user->getRoleNames()->implode(', '));
        $this->line("  - Has super-admin role: " . ($user->hasRole('super_admin') ? '✅ Yes' : '❌ No'));
        $this->line("  - Has admin role: " . ($user->hasRole('admin') ? '❌ Yes (should be removed)' : '✅ No'));

        // Test permissions
        $this->info("🔑 Testing permissions...");
        $this->line("  - Has view_charging_points: " . ($user->can('view_charging_points') ? '✅ Yes' : '❌ No'));
        $this->line("  - Has create_charging_points: " . ($user->can('create_charging_points') ? '✅ Yes' : '❌ No'));
        $this->line("  - Has view_all_charging_points: " . ($user->can('view_all_charging_points') ? '✅ Yes' : '❌ No'));

        $this->line("=" . str_repeat("=", 60));
        $this->info("🎉 Role change completed!");
        $this->line("📝 User now has super-admin role");
        $this->line("📝 User should now be able to access /admin/charging-points");
        
        return 0;
    }
}
