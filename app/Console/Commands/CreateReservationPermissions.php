<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateReservationPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:create-reservation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create reservation permissions and assign them to roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Creating reservation permissions...");

        // Create permissions
        $permissions = [
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'confirm_reservations',
            'cancel_reservations',
            'manage_reservations',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->line("Created permission: {$permission}");
        }

        // Assign permissions to roles
        $roles = [
            'admin' => $permissions,
            'super_admin' => $permissions,
            'integrator' => ['view_reservations', 'manage_reservations'],
            'partner' => ['view_reservations', 'manage_reservations'],
            'user' => ['view_reservations', 'create_reservations', 'edit_reservations', 'cancel_reservations'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($rolePermissions);
                $this->info("Assigned permissions to role: {$roleName}");
            } else {
                $this->warn("Role not found: {$roleName}");
            }
        }

        $this->info("Reservation permissions created and assigned successfully!");
        return 0;
    }
}