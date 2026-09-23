<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Schema;

class FixCommissionPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:commission-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix permission issues with commission system';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting commission permission fix...');

        // Vérifier si les tables nécessaires existent
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            $this->error('Required tables do not exist. Make sure to run migrations first.');
            return 1;
        }

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Ensure required permissions exist
        $this->createPermissions();
        
        // Ensure admin role exists
        $adminRole = $this->ensureAdminRole();
        
        // Assign permissions to admin role
        $this->assignPermissionsToAdmin($adminRole);
        
        // Check for any users without roles and assign them
        $this->fixUserRoles();
        
        $this->info('Commission permissions fixed successfully!');
        
        return 0;
    }
    
    /**
     * Create required permissions
     */
    private function createPermissions()
    {
        $this->info('Creating required permissions...');
        
        $permissions = [
            'view_commission_settings',
            'manage_commissions'
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
            $this->line("Permission '$permission' created or confirmed.");
        }
    }
    
    /**
     * Ensure admin role exists
     */
    private function ensureAdminRole()
    {
        $this->info('Ensuring admin role exists...');
        
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->line("Role 'admin' created or confirmed.");
        
        return $adminRole;
    }
    
    /**
     * Assign permissions to admin role
     */
    private function assignPermissionsToAdmin($adminRole)
    {
        $this->info('Assigning permissions to admin role...');
        
        $permissions = Permission::all();
        $adminRole->syncPermissions($permissions);
        
        $this->line("All permissions assigned to 'admin' role.");
    }
    
    /**
     * Fix user roles if needed
     */
    private function fixUserRoles()
    {
        $this->info('Checking user roles...');
        
        // Vérifier si la table users existe
        if (!Schema::hasTable('users')) {
            $this->warn('Users table does not exist yet.');
            return;
        }
        
        // This assumes you have a User model
        $userModel = config('auth.providers.users.model');
        if (!class_exists($userModel)) {
            $this->warn("User model $userModel does not exist.");
            return;
        }
        
        $users = $userModel::all();
        $adminRole = Role::where('name', 'admin')->first();
        if (!$adminRole) {
            $this->warn('Admin role not found.');
            return;
        }
        
        $fixedCount = 0;
        
        foreach ($users as $user) {
            if ($user->roles->isEmpty()) {
                // Assume the first user is an admin, others are regular users
                if ($user->id === 1) {
                    $user->assignRole($adminRole);
                    $fixedCount++;
                } else {
                    try {
                        $userRole = Role::firstOrCreate(['name' => 'user']);
                        $user->assignRole($userRole);
                        $fixedCount++;
                    } catch (\Exception $e) {
                        $this->error("Could not assign role to user {$user->id}: {$e->getMessage()}");
                    }
                }
            }
        }
        
        $this->line("Fixed roles for $fixedCount users.");
    }
}