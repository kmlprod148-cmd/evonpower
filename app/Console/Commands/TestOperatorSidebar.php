<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TestOperatorSidebar extends Command
{
    protected $signature = 'test:operator-sidebar {email}';
    protected $description = 'Test operator sidebar links';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found");
            return 1;
        }

        $this->info("Testing sidebar for user: {$user->name} ({$user->email})");
        
        // Test authentication
        Auth::login($user);
        $this->info("User authenticated: " . (Auth::check() ? 'Yes' : 'No'));
        
        if (!Auth::check()) {
            $this->error("Failed to authenticate user");
            return 1;
        }

        // Test role
        $this->info("User role: {$user->role}");
        $this->info("Has operator role: " . ($user->hasRole('operator') ? 'Yes' : 'No'));
        
        // Test sidebar conditions
        $this->info("Sidebar link conditions:");
        $this->line("  - Dashboard: " . ($user->hasRole('operator') ? 'Should show' : 'Should not show'));
        $this->line("  - Charging Points: " . ($user->hasRole('operator') ? 'Should show' : 'Should not show'));
        $this->line("  - Pricing Plans: " . ($user->hasRole('operator') ? 'Should show' : 'Should not show'));
        
        // Test route accessibility
        $this->info("Route accessibility:");
        $routes = [
            'operator.dashboard',
            'operator.charging-points.index',
            'operator.charging-points.create',
            'operator.pricing-plans.index',
            'operator.pricing-plans.create'
        ];
        
        foreach ($routes as $route) {
            try {
                $url = route($route);
                $this->line("  - {$route}: {$url}");
            } catch (\Exception $e) {
                $this->line("  - {$route}: ERROR - " . $e->getMessage());
            }
        }

        return 0;
    }
}
