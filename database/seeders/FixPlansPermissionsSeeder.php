<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixPlansPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer la permission view_plans si elle n'existe pas
        $viewPlansPermission = Permission::firstOrCreate(
            ['name' => 'view_plans'],
            ['guard_name' => 'web']
        );

        // Créer d'autres permissions liées aux plans si elles n'existent pas
        $planPermissions = [
            'create_plans',
            'edit_plans', 
            'delete_plans',
            'view_pricing_plans',
            'create_pricing_plans',
            'edit_pricing_plans',
            'delete_pricing_plans'
        ];

        foreach ($planPermissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName],
                ['guard_name' => 'web']
            );
        }

        // Attribuer les permissions aux rôles
        $roles = [
            'admin' => ['view_plans', 'create_plans', 'edit_plans', 'delete_plans', 'view_pricing_plans', 'create_pricing_plans', 'edit_pricing_plans', 'delete_pricing_plans'],
            'integrator' => ['view_plans', 'create_plans', 'edit_plans', 'view_pricing_plans', 'create_pricing_plans', 'edit_pricing_plans'],
            'operator' => ['view_plans', 'view_pricing_plans'],
            'partner' => ['view_plans', 'view_pricing_plans']
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

        $this->command->info('🎉 Permissions Plans configurées avec succès!');
    }
}
