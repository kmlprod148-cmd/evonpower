<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckOperatorPermissions extends Command
{
    protected $signature = 'check:operator-permissions {email}';
    protected $description = 'Check operator permissions';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found");
            return 1;
        }

        $this->info("Checking permissions for user: {$user->name} ({$user->email})");
        $this->info("User role: {$user->role}");
        $this->info("User roles: " . $user->getRoleNames()->implode(', '));
        
        $this->info("User permissions:");
        $permissions = $user->getAllPermissions();
        foreach ($permissions as $permission) {
            $this->line("  - {$permission->name}");
        }

        // Test specific permissions
        $testPermissions = [
            'view_own_charging_points',
            'create_charging_points',
            'edit_own_charging_points',
            'delete_own_charging_points',
            'manage_own_charging_points',
            'view_own_pricing_plans',
            'create_pricing_plans',
            'edit_own_pricing_plans',
            'delete_own_pricing_plans',
            'manage_own_pricing_plans',
            'view_operator_dashboard'
        ];

        $this->info("Permission tests:");
        foreach ($testPermissions as $permission) {
            $can = $user->can($permission);
            $this->line("  - {$permission}: " . ($can ? 'Yes' : 'No'));
        }

        return 0;
    }
}
