<?php

namespace App\Services;

use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class IntegratorBusinessProfilePermissionService
{
    /**
     * Appliquer le filtrage hiérarchique pour les profils d'entreprise des intégrateurs
     * 
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    public function applyIntegratorFiltering(Builder $query, User $user): Builder
    {
        // Admin : ne voir que ses propres profils
        if ($user->hasRole(['admin', 'super_admin'])) {
            Log::info('Admin filtered to own business profiles', ['user_id' => $user->id]);
            return $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('created_by_id', $user->id)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('created_by_type', get_class($user))
                         ->where('created_by_id', $user->id);
                  });
            });
        }

        // Intégrateur : voir uniquement ses profils + ceux de son admin (insensible à la casse)
        if ($user->hasRole(['integrator', 'Integrator'])) {
            return $this->filterForIntegrator($query, $user);
        }

        // Autres rôles : pas d'accès
        Log::warning('User without integrator role trying to access business profiles', [
            'user_id' => $user->id,
            'roles' => $user->getRoleNames()->toArray()
        ]);
        
        return $query->whereRaw('1 = 0');
    }

    /**
     * Filtrer les profils d'entreprise pour un intégrateur
     * 
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    protected function filterForIntegrator(Builder $query, User $user): Builder
    {
        Log::info('Applying integrator business profile filtering', [
            'user_id' => $user->id,
            'integrator_id' => $user->integrator_id
        ]);

        return $query->where(function ($q) use ($user) {
            // 1. Profils créés par l'intégrateur lui-même
            $q->where(function ($subQuery) use ($user) {
                $subQuery->where(function ($creatorQuery) use ($user) {
                    // Système polymorphique
                    $creatorQuery->where('created_by_type', get_class($user))
                                ->where('created_by_id', $user->id);
                })
                // Système legacy
                ->orWhere('created_by', $user->id);
            });

            // 2. Profils créés par SON admin spécifique UNIQUEMENT si is_public = true ET target_audience contient "operators"
            $adminId = $this->getIntegratorAdminId($user);
            if ($adminId) {
                $q->orWhere(function ($adminQuery) use ($adminId) {
                    $adminQuery->where(function ($q) use ($adminId) {
                        $q->where('created_by', $adminId)
                          ->orWhere('created_by_id', $adminId)
                          ->orWhere(function ($adminTypeQuery) use ($adminId) {
                              $adminTypeQuery->where('created_by_type', 'App\Models\User')
                                           ->where('created_by_id', $adminId);
                          });
                    })
                    ->where('is_public', true)
                    // target_audience doit contenir "operators"
                    ->where(function($targetQ) {
                        // target_audience contient "operators" (gestion JSON robuste)
                        $targetQ->whereJsonContains('target_audience', 'operators')
                               // Fallback pour les cas où JSON n'est pas supporté nativement
                               ->orWhere('target_audience', 'like', '%"operators"%')
                               ->orWhere('target_audience', 'like', '%operators%');
                    });
                });
            }
        });
    }

    /**
     * Obtenir l'ID de l'admin qui a créé cet intégrateur
     * 
     * @param User $user
     * @return int|null
     */
    protected function getIntegratorAdminId(User $user): ?int
    {
        if (!$user->integrator_id) {
            return null;
        }

        $integrator = Integrator::find($user->integrator_id);
        if (!$integrator || !$integrator->admin) {
            return null;
        }

        return $integrator->admin->id;
    }

    /**
     * Vérifier si un intégrateur peut créer un profil d'entreprise
     * 
     * @param User $user
     * @return bool
     */
    public function canCreateBusinessProfile(User $user): bool
    {
        // Autoriser immédiatement les admins/super-admins
        if ($user->hasRole(['admin', 'Admin', 'super_admin', 'Super Admin', 'super admin'])) {
            return true;
        }

        if (!$user->hasRole(['integrator', 'Integrator'])) {
            return false;
        }

        // Vérifier les permissions spécifiques
        $hasSpecificPermission = $user->can('create_integrator_business_profiles') || 
                                $user->can('create_business_profiles');

        if ($hasSpecificPermission) {
            Log::info('Integrator has business profile creation permission', [
                'user_id' => $user->id,
                'integrator_id' => $user->integrator_id
            ]);
            return true;
        }

        // Fallback via policy: autoriser si la policy BusinessProfilePolicy::create permet
        try {
            if (Gate::allows('create', BusinessProfile::class)) {
                Log::info('Integrator allowed to create business profiles via policy fallback', [
                    'user_id' => $user->id,
                ]);
                return true;
            }
        } catch (\Throwable $e) {
            Log::warning('Policy fallback failed in canCreateBusinessProfile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::warning('Integrator lacks business profile creation permission', [
            'user_id' => $user->id,
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray()
        ]);

        return false;
    }

    /**
     * Vérifier si un intégrateur peut modifier un profil d'entreprise
     * 
     * @param User $user
     * @param BusinessProfile $businessProfile
     * @return bool
     */
    public function canEditBusinessProfile(User $user, BusinessProfile $businessProfile): bool
    {
        if (!$user->hasRole(['integrator', 'Integrator'])) {
            return false;
        }

        // Vérifier les permissions spécifiques
        $hasPermission = $user->can('edit_integrator_business_profiles') || 
                        $user->can('edit_business_profiles');

        if (!$hasPermission) {
            return false;
        }

        // Vérifier si c'est son propre profil
        $isOwnProfile = $this->isOwnBusinessProfile($user, $businessProfile);
        
        // Vérifier si c'est un profil de son admin
        $isAdminProfile = $this->isAdminBusinessProfile($user, $businessProfile);

        $canEdit = $isOwnProfile || $isAdminProfile;

        Log::info('Business profile edit permission check', [
            'user_id' => $user->id,
            'business_profile_id' => $businessProfile->id,
            'is_own_profile' => $isOwnProfile,
            'is_admin_profile' => $isAdminProfile,
            'can_edit' => $canEdit
        ]);

        return $canEdit;
    }

    /**
     * Vérifier si un intégrateur peut supprimer un profil d'entreprise
     * 
     * @param User $user
     * @param BusinessProfile $businessProfile
     * @return bool
     */
    public function canDeleteBusinessProfile(User $user, BusinessProfile $businessProfile): bool
    {
        if (!$user->hasRole(['integrator', 'Integrator'])) {
            return false;
        }

        // Vérifier les permissions spécifiques
        $hasPermission = $user->can('delete_integrator_business_profiles') || 
                        $user->can('delete_business_profiles');

        if (!$hasPermission) {
            return false;
        }

        // Un intégrateur ne peut supprimer que SES propres profils
        return $this->isOwnBusinessProfile($user, $businessProfile);
    }

    /**
     * Vérifier si un profil d'entreprise appartient à l'intégrateur
     * 
     * @param User $user
     * @param BusinessProfile $businessProfile
     * @return bool
     */
    protected function isOwnBusinessProfile(User $user, BusinessProfile $businessProfile): bool
    {
        return ($businessProfile->created_by_type === get_class($user) && 
                $businessProfile->created_by_id === $user->id) ||
               $businessProfile->created_by === $user->id;
    }

    /**
     * Vérifier si un profil d'entreprise appartient à l'admin de l'intégrateur
     * 
     * @param User $user
     * @param BusinessProfile $businessProfile
     * @return bool
     */
    protected function isAdminBusinessProfile(User $user, BusinessProfile $businessProfile): bool
    {
        $adminId = $this->getIntegratorAdminId($user);
        
        if (!$adminId) {
            return false;
        }

        return $businessProfile->created_by === $adminId ||
               ($businessProfile->created_by_type === 'App\Models\User' && 
                $businessProfile->created_by_id === $adminId);
    }

    /**
     * Obtenir les profils d'entreprise visibles pour un intégrateur
     * 
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVisibleBusinessProfiles(User $user)
    {
        // Admins et super-admins: uniquement leurs propres profils
        if ($user->hasRole(['admin', 'Admin', 'super_admin', 'Super Admin', 'super admin'])) {
            return BusinessProfile::where('is_active', true)
                ->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhere('created_by_id', $user->id)
                      ->orWhere(function ($q2) use ($user) {
                          $q2->where('created_by_type', get_class($user))
                             ->where('created_by_id', $user->id);
                      });
                })
                ->get();
        }

        // Intégrateurs: uniquement leurs propres profils
        if ($user->hasRole(['integrator', 'Integrator'])) {
            return $this->getOwnBusinessProfiles($user);
        }

        // Autres rôles: rien par défaut
        return collect();
    }

    /**
     * Obtenir uniquement les profils d'entreprise créés par l'intégrateur
     * 
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOwnBusinessProfiles(User $user)
    {
        if (!$user->hasRole(['integrator', 'Integrator'])) {
            return collect();
        }

        return BusinessProfile::where('is_active', true)
            ->where(function ($query) use ($user) {
                $query->where(function ($subQuery) use ($user) {
                    $subQuery->where('created_by_type', get_class($user))
                            ->where('created_by_id', $user->id);
                })
                ->orWhere('created_by', $user->id);
            })
            ->get();
    }

    /**
     * Obtenir les profils d'entreprise créés par l'admin de l'intégrateur
     * 
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAdminBusinessProfiles(User $user)
    {
        if (!$user->hasRole(['integrator', 'Integrator'])) {
            return collect();
        }

        $adminId = $this->getIntegratorAdminId($user);
        if (!$adminId) {
            return collect();
        }

        return BusinessProfile::where('is_active', true)
            ->where(function ($query) use ($adminId) {
                $query->where('created_by', $adminId)
                      ->orWhere('created_by_id', $adminId)
                      ->orWhere(function ($adminTypeQuery) use ($adminId) {
                          $adminTypeQuery->where('created_by_type', 'App\Models\User')
                                       ->where('created_by_id', $adminId);
                      });
            })
            ->get();
    }
}
