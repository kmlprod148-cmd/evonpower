<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PlanPolicy
{
    use HandlesAuthorization;

    /**
     * Autorise tout pour les admins avant toute vérification spécifique.
     */
    public function before(User $user, $ability)
    {
        \Log::info('Policy before', ['user_id' => $user->id, 'roles' => $user->roles]);
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    /**
     * Voir la liste des plans.
     */
    public function viewAny(User $user)
    {
        return null; // Laisse la méthode before gérer
    }

    /**
     * Voir un plan spécifique.
     */
    public function view(User $user, Plan $plan)
    {
        // Autoriser tous les utilisateurs connectés
        return true;
    }

    /**
     * Créer un plan.
     */
    public function create(User $user)
    {
        return null; // Laisse la méthode before gérer
    }

    /**
     * Mettre à jour un plan.
     */
    public function update(User $user, Plan $plan)
    {
        \Log::info('PlanPolicy update', [
            'user_id' => $user->id,
            'roles' => $user->getRoleNames() // ou $user->roles selon ton modèle
        ]);
        return $user->hasRole(['admin', 'Admin']);
    }

    /**
     * Supprimer un plan.
     */
    public function delete(User $user, Plan $plan)
    {
        return null; // Laisse la méthode before gérer
    }
}
