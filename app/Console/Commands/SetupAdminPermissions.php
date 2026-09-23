<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SetupAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:admin-permissions {user_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup admin role and permissions, assign to user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Setting up admin permissions...');
        $this->line('');

        // Step 1: Create admin role if it doesn't exist
        $this->info('Step 1: Creating admin role...');
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);
        $this->info("✅ Admin role ready (ID: {$adminRole->id})");
        $this->line('');

        // Step 1.1: Create operator role if it doesn't exist
        $this->info('Step 1.1: Creating operator role...');
        $operatorRole = Role::firstOrCreate([
            'name' => 'operator',
            'guard_name' => 'web'
        ]);
        $this->info("✅ Operator role ready (ID: {$operatorRole->id})");
        $this->line('');

        // Step 2: Create essential permissions
        $this->info('Step 2: Creating essential permissions...');
        $permissions = [
            // Charging Points
            'view_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            
            // Groups
            'view_groups',
            'view_all_groups',
            'view_integrator_groups', 
            'view_own_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',
            
            // Integrators
            'view_integrators',
            'create_integrators',
            'edit_integrators',
            'delete_integrators',
            
            // Partners
            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            
            // Transactions
            'view_transactions',
            'create_transactions',
            'edit_transactions',
            'delete_transactions',
            
            // Users
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            
            // Admin specific
            'admin_access',
            'manage_all',
            'super_admin',
            'manage_reservations', // Add this permission
            
            // Commission settings (from CommissionController)
            'view_commission_settings',
            'manage_commissions',
        ];

        $createdCount = 0;
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
            
            if ($permission->wasRecentlyCreated) {
                $createdCount++;
            }
        }
        
        $this->info("✅ Permissions ready ({$createdCount} new permissions created)");
        $this->line('');

        // Step 3: Assign all permissions to admin role
        $this->info('Step 3: Assigning permissions to admin role...');
        $allPermissions = Permission::where('guard_name', 'web')->get();
        $adminRole->syncPermissions($allPermissions);
        $this->info("✅ Admin role now has {$allPermissions->count()} permissions");
        $this->line('');

        // Step 3.1: Assign specific permissions to operator role
        $this->info('Step 3.1: Assigning permissions to operator role...');
        $operatorPermissions = [
            'view_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'view_transactions',
        ];
        $operatorRole->syncPermissions($operatorPermissions);
        $this->info("✅ Operator role now has " . count($operatorPermissions) . " permissions");
        $this->line('');

        // Step 4: Handle user assignment
        $userId = $this->argument('user_id');
        
        if (!$userId) {
            // Try to find first user or ask for input
            $firstUser = User::first();
            if ($firstUser) {
                if ($this->confirm("Assign admin role to user '{$firstUser->name}' (ID: {$firstUser->id})?")) {
                    $userId = $firstUser->id;
                }
            } else {
                $this->warn('No users found in database. Please create a user first.');
                return;
            }
        }

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found!");
                return;
            }

            $this->info("Step 4: Assigning admin role to user...");
            
            // Remove any existing roles first (optional)
            if ($this->confirm("Remove existing roles from user '{$user->name}' before assigning admin?", false)) {
                $user->roles()->detach();
                $this->info('Existing roles removed');
            }
            
            // Assign admin role
            $user->assignRole($adminRole);
            $this->info("✅ User '{$user->name}' now has admin role");
            
            // Verification
            $this->line('');
            $this->info('🔍 Verification:');
            $this->line("User roles: " . $user->getRoleNames()->implode(', '));
            $this->line("User permissions: " . $user->getAllPermissions()->count() . " permissions");
            $this->line("Can view charging points: " . ($user->can('view_charging_points') ? 'YES' : 'NO'));
            $this->line("Has admin role: " . ($user->hasRole('admin') ? 'YES' : 'NO'));
        }

        $this->line('');
        $this->info('🎉 Admin setup complete!');
        $this->line('');
        $this->info('💡 Next steps:');
        $this->line('1. Test permissions: php artisan test:admin-permissions ' . ($userId ?: '1'));
        $this->line('2. Clear cache: php artisan cache:clear');
        $this->line('3. Try accessing the application as admin user');
        
        if (!$userId) {
            $this->line('');
            $this->warn('⚠️  No user was assigned admin role. Run this command again with a user ID:');
            $this->line('php artisan setup:admin-permissions <user_id>');
        }
    }
}