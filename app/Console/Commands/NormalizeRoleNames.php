<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use App\Models\User;

class NormalizeRoleNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:normalize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalise les noms de rôles en minuscules pour éviter les problèmes de casse';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Normalisation des noms de rôles ===');

        try {
            // Récupérer tous les rôles
            $roles = Role::all();
            $this->info("Trouvé {$roles->count()} rôle(s)");

            foreach ($roles as $role) {
                $originalName = $role->name;
                $normalizedName = strtolower($originalName);

                if ($originalName !== $normalizedName) {
                    $this->info("Normalisation du rôle '{$originalName}' vers '{$normalizedName}'");

                    // Vérifier si le rôle normalisé existe déjà
                    $existingRole = Role::where('name', $normalizedName)->first();
                    if ($existingRole && $existingRole->id !== $role->id) {
                        $this->warn("Le rôle '{$normalizedName}' existe déjà. Fusion des permissions...");
                        
                        // Transférer les permissions du rôle original vers le rôle normalisé
                        $originalPermissions = $role->permissions;
                        foreach ($originalPermissions as $permission) {
                            if (!$existingRole->hasPermissionTo($permission)) {
                                $existingRole->givePermissionTo($permission);
                            }
                        }

                        // Transférer les utilisateurs du rôle original vers le rôle normalisé
                        $usersWithOriginalRole = User::role($originalName)->get();
                        foreach ($usersWithOriginalRole as $user) {
                            $user->removeRole($originalName);
                            $user->assignRole($normalizedName);
                        }

                        // Supprimer le rôle original
                        $role->delete();
                        $this->info("✓ Rôle '{$originalName}' fusionné avec '{$normalizedName}'");
                    } else {
                        // Mettre à jour le nom du rôle
                        $role->update(['name' => $normalizedName]);
                        $this->info("✓ Rôle '{$originalName}' renommé en '{$normalizedName}'");
                    }
                } else {
                    $this->info("✓ Rôle '{$originalName}' déjà normalisé");
                }
            }

            // Vérification finale
            $this->info("\n=== Vérification finale ===");
            $finalRoles = Role::all();
            foreach ($finalRoles as $role) {
                $this->info("- {$role->name}");
            }

            $this->info("\n✓ Normalisation terminée avec succès!");

        } catch (\Exception $e) {
            $this->error("✗ Erreur: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
} 