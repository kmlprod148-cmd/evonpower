<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixAdminIntegratorPermissions extends Command
{
    protected $signature = 'fix:admin-integrator-permissions';
    protected $description = 'Fix admin permissions for integrator editing';

    public function handle()
    {
        $this->info('Fixing admin permissions for integrator editing...');

        // Vérifier les permissions nécessaires
        $requiredPermissions = [
            'view_integrators',
            'edit_integrators',
            'create_integrators',
            'delete_integrators'
        ];

        // Créer les permissions si elles n'existent pas
        foreach ($requiredPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
            $this->info("✅ Permission '{$permission}' créée/vérifiée");
        }

        // Trouver ou créer le rôle admin
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->info("✅ Rôle admin trouvé/créé");

        // Assigner toutes les permissions au rôle admin
        $adminRole->givePermissionTo($requiredPermissions);
        $this->info("✅ Permissions assignées au rôle admin");

        // Trouver tous les utilisateurs admin
        $adminUsers = User::role('admin')->get();
        $this->info("📊 Nombre d'utilisateurs admin: " . $adminUsers->count());

        foreach ($adminUsers as $admin) {
            $this->info("👤 Admin: {$admin->email} (ID: {$admin->id})");
            
            // S'assurer que l'utilisateur a le rôle admin
            if (!$admin->hasRole('admin')) {
                $admin->assignRole('admin');
                $this->info("  ✅ Rôle admin assigné");
            }

            // S'assurer que l'utilisateur a toutes les permissions
            foreach ($requiredPermissions as $permission) {
                if (!$admin->can($permission)) {
                    $admin->givePermissionTo($permission);
                    $this->info("  ✅ Permission '{$permission}' ajoutée");
                }
            }
        }

        // Tester l'accès
        $adminUser = User::role('admin')->first();
        if ($adminUser) {
            $this->info("\n🧪 Test des permissions:");
            foreach ($requiredPermissions as $permission) {
                $can = $adminUser->can($permission);
                $this->info("  " . ($can ? "✅" : "❌") . " {$permission}: " . ($can ? "Oui" : "Non"));
            }
        }

        $this->info("\n✅ Correction terminée!");
        $this->info("Les admins peuvent maintenant éditer les intégrateurs.");
    }
}
