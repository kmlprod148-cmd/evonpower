<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class AssignPartnerPermissionsToAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:assign-partner-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign all partner permissions to admin and super-admin roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Assigning partner permissions to admin roles...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all partner permissions
        $partnerPermissions = [
            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            'show_partners',
            'activate_partners',
            'deactivate_partners',
            'bulk_update_partners',
            'export_partners',
        ];

        // Create permissions if they don't exist
        foreach ($partnerPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->line("✓ Permission '{$permission}' created/verified");
        }

        // Get admin roles
        $adminRole = Role::where('name', 'admin')->first();
        $superAdminRole = Role::where('name', 'super_admin')->first();

        if ($adminRole) {
            $adminRole->givePermissionTo($partnerPermissions);
            $this->info("✓ Partner permissions assigned to 'admin' role");
        } else {
            $this->warn("⚠ 'admin' role not found");
        }

        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($partnerPermissions);
            $this->info("✓ Partner permissions assigned to 'super_admin' role");
        } else {
            $this->warn("⚠ 'super_admin' role not found");
        }

        // Assign permissions to existing admin users
        $adminUsers = User::role(['admin', 'super_admin'])->get();
        foreach ($adminUsers as $user) {
            $user->givePermissionTo($partnerPermissions);
            $this->line("✓ Partner permissions assigned to user: {$user->email}");
        }

        $this->info('Partner permissions successfully assigned to admin roles and users!');
    }
}
