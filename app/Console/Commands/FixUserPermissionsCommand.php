<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class FixUserPermissionsCommand extends Command
{
    protected $signature = 'user:fix-permissions {user_id}';
    protected $description = 'Fix user permissions by ensuring they have the correct role permissions';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $this->info("=== Diagnostic des permissions utilisateur ID: {$userId} ===");
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("Utilisateur avec l'ID {$userId} non trouvé!");
            return 1;
        }
        
        $this->info("Utilisateur trouvé: {$user->name}");
        $this->info("Email: {$user->email}");
        $this->info("Rôles actuels: " . $user->getRoleNames()->implode(', '));
        
        // Vérifier si l'utilisateur a le rôle integrator
        if (!$user->hasRole('integrator')) {
            $this->warn("L'utilisateur n'a pas le rôle 'integrator'. Attribution du rôle...");
            $user->assignRole('integrator');
            $this->info("Rôle 'integrator' attribué avec succès.");
        }
        
        // Vérifier la permission spécifique
        $hasPermission = $user->can('view_integrator_charging_points');
        $this->info("A la permission 'view_integrator_charging_points': " . ($hasPermission ? 'OUI' : 'NON'));
        
        if (!$hasPermission) {
            $this->warn("L'utilisateur n'a pas la permission requise. Correction en cours...");
            
            // Vérifier que le rôle integrator a la permission
            $integratorRole = Role::findByName('integrator');
            if (!$integratorRole) {
                $this->error("Rôle 'integrator' non trouvé!");
                return 1;
            }
            
            // Vérifier que la permission existe
            $permission = Permission::where('name', 'view_integrator_charging_points')->first();
            if (!$permission) {
                $this->error("Permission 'view_integrator_charging_points' non trouvée!");
                return 1;
            }
            
            // S'assurer que le rôle a la permission
            if (!$integratorRole->hasPermissionTo('view_integrator_charging_points')) {
                $this->warn("Le rôle integrator n'a pas la permission. Attribution...");
                $integratorRole->givePermissionTo('view_integrator_charging_points');
            }
            
            // Rafraîchir le cache des permissions
            $this->call('permission:cache-reset');
            
            // Vérifier à nouveau
            $user->refresh();
            $hasPermission = $user->can('view_integrator_charging_points');
            $this->info("Après correction - A la permission 'view_integrator_charging_points': " . ($hasPermission ? 'OUI' : 'NON'));
        }
        
        // Afficher toutes les permissions du rôle integrator
        $this->info("\n=== Permissions du rôle integrator ===");
        $integratorPermissions = $integratorRole->permissions->pluck('name')->toArray();
        foreach ($integratorPermissions as $perm) {
            $this->line("- {$perm}");
        }
        
        $this->info("\n=== Test final ===");
        $this->info("L'utilisateur peut maintenant accéder aux charging-points: " . ($user->can('view_integrator_charging_points') ? 'OUI' : 'NON'));
        
        return 0;
    }
}
