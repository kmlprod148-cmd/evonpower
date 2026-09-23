<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TestAdminRole extends Command
{
    protected $signature = 'test:admin-role {email}';
    protected $description = 'Test admin role detection';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found");
            return 1;
        }

        $this->info("Testing admin role for user: {$user->name} ({$user->email})");
        
        // Test authentication
        Auth::login($user);
        $this->info("User authenticated: " . (Auth::check() ? 'Yes' : 'No'));
        
        if (!Auth::check()) {
            $this->error("Failed to authenticate user");
            return 1;
        }

        // Test role detection
        $this->info("Role detection tests:");
        $this->line("  - User role attribute: {$user->role}");
        $this->line("  - User roles: " . $user->getRoleNames()->implode(', '));
        $this->line("  - Has admin role: " . ($user->hasRole('admin') ? 'Yes' : 'No'));
        $this->line("  - Has any admin role: " . ($user->hasAnyRole(['admin', 'super_admin']) ? 'Yes' : 'No'));
        
        // Test middleware conditions
        $this->info("Middleware condition tests:");
        $this->line("  - AdminRoleMiddleware condition: " . ($user->hasRole(['admin', 'super_admin']) ? 'Pass' : 'Fail'));
        $this->line("  - Spatie role middleware condition: " . ($user->hasRole('admin') ? 'Pass' : 'Fail'));
        
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

        return 0;
    }
}
