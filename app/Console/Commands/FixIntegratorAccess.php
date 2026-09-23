<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixIntegratorAccess extends Command
{
    protected $signature = 'fix:integrator-access {user_id=11}';
    protected $description = 'Fix integrator access for a specific user';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $this->info("Fixing integrator access for user ID {$userId}...");

        // Récupérer l'utilisateur
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Utilisateur ID {$userId} non trouvé");
            return;
        }

        $this->info("👤 Utilisateur: {$user->email} (ID: {$user->id})");
        $this->info("📋 Rôles actuels: " . implode(', ', $user->getRoleNames()->toArray()));

        // 1. Créer toutes les permissions nécessaires
        $this->info("\n1. Création des permissions...");
        $permissions = [
            'view_integrators',
            'edit_integrators',
            'create_integrators',
            'delete_integrators',
            'admin_access',
            'manage_all'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
            $this->info("   ✅ Permission '{$permission}' créée/vérifiée");
        }

        // 2. S'assurer que le rôle admin existe et a toutes les permissions
        $this->info("\n2. Configuration du rôle admin...");
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo($permissions);
        $this->info("   ✅ Rôle admin configuré avec toutes les permissions");

        // 3. Assigner le rôle admin à l'utilisateur
        $this->info("\n3. Configuration de l'utilisateur...");
        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
            $this->info("   ✅ Rôle admin assigné");
        } else {
            $this->info("   ✅ Rôle admin déjà assigné");
        }

        // 4. Donner toutes les permissions directement à l'utilisateur
        foreach ($permissions as $permission) {
            if (!$user->can($permission)) {
                $user->givePermissionTo($permission);
                $this->info("   ✅ Permission '{$permission}' ajoutée directement");
            }
        }

        // 5. Vérifier les permissions après correction
        $this->info("\n4. Vérification des permissions:");
        foreach ($permissions as $permission) {
            $can = $user->can($permission);
            $this->info("   " . ($can ? "✅" : "❌") . " {$permission}: " . ($can ? "Oui" : "Non"));
        }

        // 6. Test d'accès aux intégrateurs
        $this->info("\n5. Test d'accès aux intégrateurs:");
        $integrators = \App\Models\Integrator::all();
        $this->info("   📊 Nombre d'intégrateurs: " . $integrators->count());
        
        if ($integrators->count() > 0) {
            $integrator = $integrators->first();
            $this->info("   🏢 Test sur intégrateur: {$integrator->name} (ID: {$integrator->id})");
            
            try {
                $canUpdate = $user->can('update', $integrator);
                $this->info("   " . ($canUpdate ? "✅" : "❌") . " Peut modifier l'intégrateur: " . ($canUpdate ? "Oui" : "Non"));
            } catch (Exception $e) {
                $this->error("   ❌ Erreur test autorisation: " . $e->getMessage());
            }
        }

        // 7. Nettoyer le cache des permissions
        $this->info("\n6. Nettoyage du cache...");
        $this->call('permission:cache-reset');
        $this->info("   ✅ Cache des permissions nettoyé");

        $this->info("\n✅ Correction terminée!");
        $this->info("L'utilisateur devrait maintenant pouvoir accéder à /integrators/1/edit");
    }
}
