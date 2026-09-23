<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Définir toutes les permissions d'intégrateur nécessaires
        $integratorPermissions = [
            'view_integrators',
            'create_integrators', 
            'edit_integrators',
            'delete_integrators',
            'view_integrator_profiles',
            'create_integrator_profiles',
            'edit_integrator_profiles',
            'delete_integrator_profiles',
        ];

        // Créer les permissions si elles n'existent pas
        foreach ($integratorPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }

        // Trouver ou créer le rôle admin
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);

        // Assigner toutes les permissions d'intégrateur au rôle admin
        foreach ($integratorPermissions as $permissionName) {
            if (!$adminRole->hasPermissionTo($permissionName)) {
                $adminRole->givePermissionTo($permissionName);
            }
        }

        // S'assurer que tous les utilisateurs admin ont les permissions
        $adminUsers = User::role('admin')->get();
        foreach ($adminUsers as $adminUser) {
            // S'assurer que l'utilisateur a le rôle admin
            if (!$adminUser->hasRole('admin')) {
                $adminUser->assignRole('admin');
            }
            
            // Vérifier les permissions
            foreach ($integratorPermissions as $permissionName) {
                if (!$adminUser->hasPermissionTo($permissionName)) {
                    $adminUser->givePermissionTo($permissionName);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionnel : supprimer les permissions si nécessaire
        // Note: En général, on ne supprime pas les permissions dans le down()
        // car cela pourrait casser d'autres parties de l'application
    }
}; 