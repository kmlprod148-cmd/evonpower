<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixTransactionPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer la permission view_transactions si elle n'existe pas
        $viewTransactionsPermission = Permission::firstOrCreate(
            ['name' => 'view_transactions'],
            ['guard_name' => 'web']
        );

        // Créer d'autres permissions liées aux transactions si elles n'existent pas
        $transactionPermissions = [
            'view_own_transactions',
            'view_own_history',
            'create_transactions',
            'edit_transactions',
            'delete_transactions',
            'view_admin_transactions'
        ];

        foreach ($transactionPermissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName],
                ['guard_name' => 'web']
            );
        }

        // Attribuer les permissions aux rôles
        $roles = [
            'admin' => ['view_transactions', 'view_own_transactions', 'create_transactions', 'edit_transactions', 'delete_transactions', 'view_admin_transactions'],
            'super-admin' => ['view_transactions', 'view_own_transactions', 'create_transactions', 'edit_transactions', 'delete_transactions', 'view_admin_transactions'],
            'integrator' => ['view_transactions', 'view_own_transactions', 'create_transactions', 'edit_transactions'],
            'operator' => ['view_transactions', 'view_own_transactions'],
            'partner' => ['view_transactions', 'view_own_transactions'],
            'user' => ['view_own_transactions', 'view_own_history'],
            'client' => ['view_own_transactions', 'view_own_history'],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                foreach ($permissions as $permissionName) {
                    $permission = Permission::where('name', $permissionName)->first();
                    if ($permission && !$role->hasPermissionTo($permissionName)) {
                        $role->givePermissionTo($permissionName);
                        $this->command->info("✅ Permission '$permissionName' attribuée au rôle '$roleName'");
                    }
                }
            } else {
                $this->command->warn("⚠️  Rôle '$roleName' n'existe pas");
            }
        }

        $this->command->info('🎉 Permissions Transactions configurées avec succès!');
    }
}