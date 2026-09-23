<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class AddIntegratorChargingPointPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrator:add-charging-point-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ajoute toutes les permissions nécessaires pour créer et gérer les points de charge au rôle intégrateur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Ajout des permissions de points de charge aux intégrateurs...');

        // Réinitialiser le cache des permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Trouver ou créer le rôle intégrateur
        $integratorRole = Role::firstOrCreate(
            ['name' => 'integrator'],
            ['guard_name' => 'web']
        );

        $this->info("Rôle intégrateur trouvé/créé: {$integratorRole->name}");

        // Définir toutes les permissions nécessaires pour les points de charge
        $chargingPointPermissions = [
            // Permissions de base
            'view_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'show_charging_points',
            'manage_charging_points',
            
            // Permissions spécifiques aux intégrateurs
            'view_integrator_charging_points',
            'create_integrator_charging_points',
            'edit_integrator_charging_points',
            'delete_integrator_charging_points',
        ];

        // Créer les permissions si elles n'existent pas
        $createdPermissions = [];
        $existingPermissions = [];

        foreach ($chargingPointPermissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );

            if ($permission->wasRecentlyCreated) {
                $createdPermissions[] = $permissionName;
                $this->line("  ✓ Permission créée: {$permissionName}");
            } else {
                $existingPermissions[] = $permissionName;
            }
        }

        if (!empty($createdPermissions)) {
            $this->info('Permissions créées: ' . count($createdPermissions));
        }

        // Attribuer toutes les permissions au rôle intégrateur
        $missingPermissions = [];
        foreach ($chargingPointPermissions as $permissionName) {
            if (!$integratorRole->hasPermissionTo($permissionName)) {
                $missingPermissions[] = $permissionName;
            }
        }

        if (!empty($missingPermissions)) {
            $this->warn('Permissions manquantes détectées: ' . implode(', ', $missingPermissions));
            
            // Ajouter les permissions manquantes
            $integratorRole->givePermissionTo($missingPermissions);
            
            $this->info('✓ Permissions ajoutées au rôle intégrateur: ' . count($missingPermissions));
            
            Log::info('Integrator permissions added', [
                'role' => 'integrator',
                'permissions_added' => $missingPermissions
            ]);
        } else {
            $this->info('✓ Toutes les permissions sont déjà attribuées au rôle intégrateur');
        }

        // Afficher toutes les permissions actuelles du rôle
        $currentPermissions = $integratorRole->permissions->pluck('name')->toArray();
        $this->info("\nPermissions actuelles du rôle intégrateur (" . count($currentPermissions) . "):");
        
        foreach ($chargingPointPermissions as $permission) {
            $status = in_array($permission, $currentPermissions) ? '✓' : '✗';
            $this->line("  {$status} {$permission}");
        }

        // Réinitialiser le cache des permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info("\n✓ Cache des permissions réinitialisé");
        $this->info("\n✅ Opération terminée avec succès!");

        return Command::SUCCESS;
    }
}

