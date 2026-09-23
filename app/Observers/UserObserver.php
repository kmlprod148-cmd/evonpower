<?php

namespace App\Observers;

use App\Models\User;
use App\Services\IntegratorPermissionService;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->ensureIntegratorRoleAndPermissions($user, 'created');
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Si integrator_id a été modifié ou ajouté
        if ($user->wasChanged('integrator_id') || $user->integrator_id) {
            $this->ensureIntegratorRoleAndPermissions($user, 'updated');
        }
    }

    /**
     * Ensure user has integrator role and all permissions if they have integrator_id
     */
    protected function ensureIntegratorRoleAndPermissions(User $user, string $event): void
    {
        // Si l'utilisateur a un integrator_id, il doit être intégrateur
        if ($user->integrator_id) {
            try {
                // Trouver ou créer le rôle intégrateur
                $integratorRole = Role::whereRaw('LOWER(name) = ?', ['integrator'])->first();
                
                if (!$integratorRole) {
                    Log::info("Creating integrator role for user {$user->id}");
                    IntegratorPermissionService::createIntegratorRole();
                    $integratorRole = Role::whereRaw('LOWER(name) = ?', ['integrator'])->first();
                }

                if ($integratorRole) {
                    // Vérifier si l'utilisateur a déjà le rôle intégrateur (case-insensitive)
                    $userRoles = $user->getRoleNames()->map(fn($role) => strtolower($role))->toArray();
                    
                    if (!in_array('integrator', $userRoles)) {
                        Log::info("Assigning integrator role to user {$user->id} (event: {$event})");
                        $user->assignRole($integratorRole);
                    }

                    // Assigner toutes les permissions nécessaires
                    Log::info("Ensuring permissions for integrator user {$user->id}");
                    IntegratorPermissionService::assignIntegratorPermissions($user);
                    
                    Log::info("Integrator role and permissions ensured for user {$user->id} (event: {$event})");
                } else {
                    Log::error("Could not find or create integrator role for user {$user->id}");
                }
            } catch (\Exception $e) {
                Log::error("Error ensuring integrator role and permissions for user {$user->id}: " . $e->getMessage(), [
                    'exception' => $e,
                    'event' => $event
                ]);
            }
        }
    }
}

