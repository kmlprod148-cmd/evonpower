<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Grants view_transactions and view_own_transactions to all non-user roles.
 * Uses case-insensitive role lookup to handle mixed-case role names (e.g. 'Integrator').
 */
class GrantTransactionViewPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure permissions exist
        $permissionsToCreate = [
            'view_transactions',
            'view_own_transactions',
            'view_own_history',
        ];

        foreach ($permissionsToCreate as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Role names that should have transaction view access (case-insensitive)
        $rolePatterns = ['admin', 'super_admin', 'super-admin', 'integrator', 'operator', 'partner'];

        $allRoles = Role::all();

        foreach ($allRoles as $role) {
            $roleLower = strtolower($role->name);

            foreach ($rolePatterns as $pattern) {
                if ($roleLower === strtolower($pattern)) {
                    if (!$role->hasPermissionTo('view_transactions')) {
                        $role->givePermissionTo('view_transactions');
                        $this->command->info("✅ view_transactions → {$role->name}");
                    }
                    if (!$role->hasPermissionTo('view_own_transactions')) {
                        $role->givePermissionTo('view_own_transactions');
                        $this->command->info("✅ view_own_transactions → {$role->name}");
                    }
                    break;
                }
            }
        }

        // Also grant directly to the specific user (id=7) if roles are still not matching
        // This ensures the user can always access their own transactions
        $userId = 7;
        $user = \App\Models\User::find($userId);
        if ($user) {
            if (!$user->hasPermissionTo('view_transactions')) {
                $user->givePermissionTo('view_transactions');
                $this->command->info("✅ view_transactions granted directly to user #{$userId}");
            }
            if (!$user->hasPermissionTo('view_own_transactions')) {
                $user->givePermissionTo('view_own_transactions');
                $this->command->info("✅ view_own_transactions granted directly to user #{$userId}");
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('🎉 Transaction view permissions granted.');
    }
}
