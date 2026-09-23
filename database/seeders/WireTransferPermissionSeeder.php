<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WireTransferPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les permissions pour les virements
        $permissions = [
            'view_wire_transfers',
            'create_wire_transfers',
            'process_wire_transfers',
            'export_wire_transfers',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assigner les permissions aux rôles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $integratorRole = Role::firstOrCreate(['name' => 'integrator']);
        $operatorRole = Role::firstOrCreate(['name' => 'operator']);

        // Admin : toutes les permissions
        $adminRole->givePermissionTo($permissions);

        // Intégrateur : peut voir et créer des virements, mais pas les traiter
        $integratorRole->givePermissionTo([
            'view_wire_transfers',
            'create_wire_transfers',
            'export_wire_transfers',
        ]);

        // Opérateur : peut seulement voir les virements
        $operatorRole->givePermissionTo([
            'view_wire_transfers',
        ]);

        $this->command->info('Permissions pour les virements créées avec succès');
    }
}