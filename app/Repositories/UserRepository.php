<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    /**
     * Récupère un utilisateur par son ID
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Récupère les rôles d'un utilisateur
     */
    public function getUserRoles(User $user): Collection
    {
        return $user->roles()->get();
    }

    /**
     * Vérifie si un utilisateur possède un rôle spécifique
     */
    public function hasRole(User $user, string $role): bool
    {
        return $user->hasRole($role);
    }
}