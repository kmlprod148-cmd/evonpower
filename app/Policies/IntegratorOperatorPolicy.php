<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Integrator;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class IntegratorOperatorPolicy
{
    use HandlesAuthorization;

    /**
     * Détermine si l'utilisateur peut voir la liste des opérateurs
     */
    public function viewAny(User $user): bool
    {
        // Les admins peuvent voir tous les opérateurs
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent voir leurs propres opérateurs
        if ($user->hasRole('integrator')) {
            return $user->can('view_users');
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut voir un opérateur spécifique
     */
    public function view(User $user, User $operator): bool
    {
        // Les admins peuvent voir tous les opérateurs
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent voir leurs propres opérateurs
        if ($user->hasRole('integrator')) {
            $integrator = $user->integrator;
            if (!$integrator) {
                return false;
            }

            // Vérifier que l'opérateur appartient à cet intégrateur
            return $operator->integrator_id === $integrator->id && 
                   $operator->hasRole('operator') &&
                   $user->can('show_users');
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut créer des opérateurs
     */
    public function create(User $user): bool
    {
        // Les admins peuvent créer des opérateurs
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent créer des opérateurs
        if ($user->hasRole('integrator')) {
            return $user->can('create_users');
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut modifier un opérateur
     */
    public function update(User $user, User $operator): bool
    {
        // Les admins peuvent modifier tous les opérateurs
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent modifier leurs propres opérateurs
        if ($user->hasRole('integrator')) {
            $integrator = $user->integrator;
            if (!$integrator) {
                return false;
            }

            // Vérifier que l'opérateur appartient à cet intégrateur
            return $operator->integrator_id === $integrator->id && 
                   $operator->hasRole('operator') &&
                   $user->can('edit_users');
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut supprimer un opérateur
     */
    public function delete(User $user, User $operator): bool
    {
        // Les admins peuvent supprimer tous les opérateurs
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent supprimer leurs propres opérateurs
        if ($user->hasRole('integrator')) {
            $integrator = $user->integrator;
            if (!$integrator) {
                return false;
            }

            // Vérifier que l'opérateur appartient à cet intégrateur
            return $operator->integrator_id === $integrator->id && 
                   $operator->hasRole('operator') &&
                   $user->can('delete_users');
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut activer/désactiver un opérateur
     */
    public function activate(User $user, User $operator): bool
    {
        // Les admins peuvent activer/désactiver tous les opérateurs
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent activer/désactiver leurs propres opérateurs
        if ($user->hasRole('integrator')) {
            $integrator = $user->integrator;
            if (!$integrator) {
                return false;
            }

            // Vérifier que l'opérateur appartient à cet intégrateur
            return $operator->integrator_id === $integrator->id && 
                   $operator->hasRole('operator') &&
                   $user->can('activate_users');
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut gérer le business profile d'un opérateur
     */
    public function manageBusinessProfile(User $user, User $operator): bool
    {
        // Les admins peuvent gérer tous les business profiles
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Les intégrateurs peuvent gérer les business profiles de leurs opérateurs
        if ($user->hasRole('integrator')) {
            $integrator = $user->integrator;
            if (!$integrator) {
                return false;
            }

            // Vérifier que l'opérateur appartient à cet intégrateur
            return $operator->integrator_id === $integrator->id && 
                   $operator->hasRole('operator') &&
                   $user->can('edit_business_profiles');
        }

        return false;
    }
}
