<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create default permissions
        $permissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage charging points',
            'view reports',
            'view_commission_settings',
            'manage_commissions',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // Assign all permissions to admin role
        $adminRole->syncPermissions($permissions);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@evon.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'language' => 'en',
                'timezone' => 'UTC',
                'theme' => 'dark',
                'two_factor_enabled' => false,
            ]
        );
        $admin->assignRole('admin');

        // Create regular user
        $user = User::firstOrCreate(
            ['email' => 'user@evon.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'language' => 'en',
                'timezone' => 'Europe/Paris',
                'theme' => 'light',
                'two_factor_enabled' => false,
            ]
        );
        $user->assignRole('user');

        // Optional: Create some additional test users
        if (app()->environment(['local', 'testing'])) {
            User::factory()->count(10)->create()->each(function ($user) {
                $user->assignRole('user');
            });
        }

        User::factory()->count(10)->create(); // Crée 10 utilisateurs
    }
}