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
        // Créer toutes les permissions de partenaires si elles n'existent pas
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

        foreach ($partnerPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assigner toutes les permissions de partenaires au rôle admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($partnerPermissions);
        }

        // Assigner toutes les permissions de partenaires au rôle super-admin
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($partnerPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Récupérer le rôle admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->revokePermissionTo([
                'view_partners',
                'create_partners',
                'edit_partners',
                'delete_partners',
                'show_partners',
                'activate_partners',
                'deactivate_partners',
                'bulk_update_partners',
                'export_partners',
            ]);
        }

        // Récupérer le rôle super-admin
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->revokePermissionTo([
                'view_partners',
                'create_partners',
                'edit_partners',
                'delete_partners',
                'show_partners',
                'activate_partners',
                'deactivate_partners',
                'bulk_update_partners',
                'export_partners',
            ]);
        }
    }
};
