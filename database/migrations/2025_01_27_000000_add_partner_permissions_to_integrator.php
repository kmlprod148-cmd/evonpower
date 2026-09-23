<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Permissions nécessaires pour les intégrateurs
        $integratorPartnerPermissions = [
            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            'show_partners',
            'activate_partners',
            'deactivate_partners',
        ];

        // Créer les permissions si elles n'existent pas
        foreach ($integratorPartnerPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assigner les permissions au rôle intégrateur
        $integratorRole = Role::where('name', 'integrator')->first();
        if ($integratorRole) {
            $integratorRole->givePermissionTo($integratorPartnerPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Récupérer le rôle intégrateur
        $integratorRole = Role::where('name', 'integrator')->first();
        if ($integratorRole) {
            $integratorRole->revokePermissionTo([
                'view_partners',
                'create_partners',
                'edit_partners',
                'delete_partners',
                'show_partners',
                'activate_partners',
                'deactivate_partners',
            ]);
        }
    }
};
