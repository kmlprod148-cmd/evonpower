<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class CheckUserRoles extends Command
{
    protected $signature = 'check:user-roles';
    protected $description = 'Check users and their roles';

    public function handle()
    {
        $this->info('Checking users and roles...');

        // Check all roles
        $this->info('Available roles:');
        $roles = Role::all(['id', 'name']);
        foreach ($roles as $role) {
            $this->line("  - {$role->id}: {$role->name}");
        }

        $this->newLine();

        // Check admin users
        $this->info('Admin users:');
        $adminUsers = User::role('admin')->get(['id', 'name', 'email']);
        if ($adminUsers->count() > 0) {
            foreach ($adminUsers as $user) {
                $this->line("  - {$user->id}: {$user->name} ({$user->email})");
            }
        } else {
            $this->warn('  No admin users found');
        }

        $this->newLine();

        // Check all users with their roles
        $this->info('All users with roles:');
        $users = User::with('roles')->get(['id', 'name', 'email']);
        foreach ($users as $user) {
            $roles = $user->getRoleNames()->implode(', ');
            $this->line("  - {$user->id}: {$user->name} ({$user->email}) - Roles: {$roles}");
        }

        return 0;
    }
}
