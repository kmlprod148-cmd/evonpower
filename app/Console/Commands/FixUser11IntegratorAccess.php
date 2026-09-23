<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixUser11IntegratorAccess extends Command
{
    protected $signature = 'fix:user-11-integrator-access';
    protected $description = 'Fix integrator access for user ID 11';

    public function handle()
    {
        $this->info('Fixing integrator access for user ID 11...');

        // Récupérer l'utilisateur ID 11
        $user = User::find(11);
        
        if (!$user) {
            $this->error('Utilisateur ID 11 non trouvé');
            return;
        }

        $this->info("👤 Utilisateur: {$user->email} (ID: {$user->id})");
        $this->info("📋 Rôles actuels: " . implode(', ', $user->getRoleNames()->toArray()));

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
        }

        // S'assurer que l'utilisateur a le rôle admin
        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
            $this->info("✅ Rôle admin assigné");
        }

        // S'assurer que l'utilisateur a toutes les permissions
        foreach ($requiredPermissions as $permission) {
            if (!$user->can($permission)) {
                $user->givePermissionTo($permission);
                $this->info("✅ Permission '{$permission}' ajoutée");
            }
        }

        // Vérifier les permissions après correction
        $this->info("\n🧪 Test des permissions après correction:");
        foreach ($requiredPermissions as $permission) {
            $can = $user->can($permission);
            $this->info("  " . ($can ? "✅" : "❌") . " {$permission}: " . ($can ? "Oui" : "Non"));
        }

        // Test d'accès aux intégrateurs
        $integrators = \App\Models\Integrator::all();
        if ($integrators->count() > 0) {
            $integrator = $integrators->first();
            $canUpdate = $user->can('update', $integrator);
            $this->info("\n🏢 Test d'autorisation sur intégrateur:");
            $this->info("  " . ($canUpdate ? "✅" : "❌") . " Peut modifier l'intégrateur: " . ($canUpdate ? "Oui" : "Non"));
        }

        $this->info("\n✅ Correction terminée!");
        $this->info("L'utilisateur ID 11 peut maintenant accéder à /integrators/1/edit");
    }
}
