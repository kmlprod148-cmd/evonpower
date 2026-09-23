<?php

namespace App\Services;

use App\Models\User;
use App\Models\BusinessProfile;

class BusinessProfileDisplayService
{
    /**
     * Obtenir les business profiles visibles pour un utilisateur intégrateur
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVisibleBusinessProfilesForIntegrator(User $user)
    {
        if (!$user->hasRole(['integrator', 'Integrator'])) {
            // Si ce n'est pas un intégrateur, retourner tous les business profiles actifs
            return BusinessProfile::where('is_active', true)->get();
        }

        // Obtenir l'admin qui a créé cet intégrateur (robuste: relation OU via integrator_id)
        $adminId = null;
        $integrator = $user->integrator;
        if ($integrator && $integrator->admin) {
            $adminId = $integrator->admin->id;
        } elseif ($user->integrator_id) {
            // Fallback si la relation n'est pas chargée
            $integratorModel = \App\Models\Integrator::with('admin')->find($user->integrator_id);
            if ($integratorModel && $integratorModel->admin) {
                $adminId = $integratorModel->admin->id;
            }
        } else {
            // Si l'utilisateur n'a pas d'intégrateur associé, retourner seulement ses propres business profiles
            return $this->getIntegratorOwnBusinessProfiles($user);
        }

        // Pour les intégrateurs : leurs propres business profiles + ceux créés par LEUR admin spécifique
        return BusinessProfile::where('is_active', true)
            ->where(function($query) use ($user, $adminId) {
                $query->where(function($subQuery) use ($user) {
                    // A. Business profiles créés par l'intégrateur lui-même
                    $subQuery->where(function($q) use ($user) {
                        // Nouveau système polymorphique
                        $q->where('created_by_type', get_class($user))
                          ->where('created_by_id', $user->id);
                    })
                    // Ancien système created_by
                    ->orWhere('created_by', $user->id);
                });
                
                // B. Business profiles créés par LEUR admin spécifique (pas tous les admins)
                if ($adminId) {
                    $query->orWhere(function($adminQuery) use ($adminId) {
                        // Profils créés par l'admin spécifique UNIQUEMENT s'ils sont publics ET target_audience contient "operators"
                        $adminQuery->where(function($q) use ($adminId) {
                            // Ancien champ simplifié
                            $q->where('created_by', $adminId)
                              // Nouveau système polymorphique: type User ET id de cet admin
                              ->orWhere(function($qq) use ($adminId) {
                                  $qq->where('created_by_type', 'App\\Models\\User')
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
            })
            ->get();
    }

    /**
     * Obtenir UNIQUEMENT les business profiles de l'intégrateur connecté
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getIntegratorOwnBusinessProfiles(User $user)
    {
        if (!$user->hasRole(['integrator', 'Integrator'])) {
            // Si ce n'est pas un intégrateur, retourner tous les business profiles actifs
            return BusinessProfile::where('is_active', true)->get();
        }

        // Pour les intégrateurs : UNIQUEMENT leurs propres business profiles
        return BusinessProfile::where('is_active', true)
            ->where(function($query) use ($user) {
                $query->where(function($subQuery) use ($user) {
                    // A. Business profiles créés par l'intégrateur lui-même
                    $subQuery->where(function($q) use ($user) {
                        // Nouveau système polymorphique
                        $q->where('created_by_type', get_class($user))
                          ->where('created_by_id', $user->id);
                    })
                    // Ancien système created_by
                    ->orWhere('created_by', $user->id);
                });
            })
            ->get();
    }

    /**
     * Obtenir les business profiles pour un select/dropdown
     *
     * @param User $user
     * @return array
     */
    public function getBusinessProfilesForSelect(User $user)
    {
        $businessProfiles = $this->getVisibleBusinessProfilesForIntegrator($user);
        
        return $businessProfiles->mapWithKeys(function ($businessProfile) {
            $creatorInfo = $this->getCreatorInfo($businessProfile);
            $label = $businessProfile->name . ' — créé par ' . $creatorInfo;
            return [$businessProfile->id => $label];
        })->toArray();
    }

    /**
     * Obtenir UNIQUEMENT les business profiles de l'intégrateur pour un select/dropdown
     *
     * @param User $user
     * @return array
     */
    public function getIntegratorOwnBusinessProfilesForSelect(User $user)
    {
        $businessProfiles = $this->getIntegratorOwnBusinessProfiles($user);
        
        return $businessProfiles->mapWithKeys(function ($businessProfile) {
            $creatorInfo = $this->getCreatorInfo($businessProfile);
            $label = $businessProfile->name . ' — créé par ' . $creatorInfo;
            return [$businessProfile->id => $label];
        })->toArray();
    }

    /**
     * Obtenir les business profiles avec leurs informations détaillées
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBusinessProfilesWithDetails(User $user)
    {
        $businessProfiles = $this->getVisibleBusinessProfilesForIntegrator($user);
        
        return $businessProfiles->map(function ($businessProfile) {
            return [
                'id' => $businessProfile->id,
                'name' => $businessProfile->name,
                'description' => $businessProfile->description,
                'is_active' => $businessProfile->is_active,
                'created_at' => $businessProfile->created_at,
                'creator_info' => $this->getCreatorInfo($businessProfile),
                'creator' => $businessProfile->creator ? [
                    'id' => $businessProfile->creator->id,
                    'email' => $businessProfile->creator->email,
                    'roles' => $businessProfile->creator->getRoleNames()->toArray()
                ] : null
            ];
        });
    }

    /**
     * Obtenir les informations sur le créateur d'un business profile
     *
     * @param BusinessProfile $businessProfile
     * @return string
     */
    public function getCreatorInfo(BusinessProfile $businessProfile)
    {
        if ($businessProfile->creator) {
            $roles = $businessProfile->creator->getRoleNames()->toArray();
            
            if (in_array('admin', $roles)) {
                return 'Admin ' . $businessProfile->creator->name;
            } elseif (in_array('integrator', $roles)) {
                return 'Intégrateur ' . $businessProfile->creator->name;
            } else {
                return $businessProfile->creator->name;
            }
        }

        // Fallback pour les business profiles sans créateur associé
        if ($businessProfile->created_by_role) {
            switch ($businessProfile->created_by_role) {
                case 'admin':
                    return 'Admin EVON';
                case 'integrator':
                    return 'Intégrateur';
                case 'system':
                    return 'Système';
                default:
                    return 'Utilisateur';
            }
        }

        return 'Système';
    }

    /**
     * Obtenir tous les business profiles disponibles pour un intégrateur pour créer des partenaires
     * Inclut:
     * - Business profiles créés par l'intégrateur lui-même
     * - Business profiles créés par son admin, type "operators" ET publics uniquement
     * - Business profiles publics créés par n'importe quel admin
     * - Business profiles avec target_audience "operators" créés par des admins
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableBusinessProfilesForIntegrator(User $user)
    {
        if (!$user->hasRole(['integrator', 'Integrator'])) {
            // Si ce n'est pas un intégrateur, retourner tous les business profiles actifs
            return BusinessProfile::where('is_active', true)->get();
        }

        // Obtenir l'admin qui a créé cet intégrateur
        $adminId = null;
        $integrator = $user->integrator;
        if ($integrator && $integrator->admin) {
            $adminId = $integrator->admin->id;
        } elseif ($user->integrator_id) {
            // Fallback si la relation n'est pas chargée
            $integratorModel = \App\Models\Integrator::with('admin')->find($user->integrator_id);
            if ($integratorModel && $integratorModel->admin) {
                $adminId = $integratorModel->admin->id;
            }
        }

        // Construire la requête pour récupérer tous les business profiles accessibles
        return BusinessProfile::where('is_active', true)
            ->where(function($query) use ($user, $adminId) {
                // A. Business profiles créés par l'intégrateur lui-même
                $query->where(function($subQuery) use ($user) {
                    $subQuery->where(function($q) use ($user) {
                        // Nouveau système polymorphique
                        $q->where('created_by_type', get_class($user))
                          ->where('created_by_id', $user->id);
                    })
                    // Ancien système created_by
                    ->orWhere('created_by', $user->id);
                });
                
                // B. Business profiles créés par SON admin spécifique, type "operators" ET publics uniquement
                if ($adminId) {
                    $query->orWhere(function($adminQuery) use ($adminId) {
                        $adminQuery->where(function($q) use ($adminId) {
                            // Ancien champ simplifié
                            $q->where('created_by', $adminId)
                              // Nouveau système polymorphique: type User ET id de cet admin
                              ->orWhere(function($qq) use ($adminId) {
                                  $qq->where('created_by_type', 'App\\Models\\User')
                                     ->where('created_by_id', $adminId);
                              });
                        })
                        // Doit être public
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

                // C. Business profiles publics créés par n'importe quel admin
                $query->orWhere(function($publicAdminQuery) {
                    $publicAdminQuery->where('is_public', true)
                                   ->where(function($q) {
                                       // Créés par un admin via created_by_role
                                       $q->where('created_by_role', 'admin')
                                         // OU créés par un utilisateur avec rôle admin
                                         ->orWhereHas('creator', function($creatorQuery) {
                                             $creatorQuery->whereHas('roles', function($roleQuery) {
                                                 $roleQuery->where('name', 'admin');
                                             });
                                         });
                                   });
                });

                // D. Business profiles avec target_audience contenant "operators" créés par des admins
                $query->orWhere(function($operatorsAudienceQuery) {
                    $operatorsAudienceQuery->where(function($q) {
                                           // Créés par un admin
                                           $q->where('created_by_role', 'admin')
                                             ->orWhereHas('creator', function($creatorQuery) {
                                                 $creatorQuery->whereHas('roles', function($roleQuery) {
                                                     $roleQuery->where('name', 'admin');
                                                 });
                                             });
                                       })
                                       ->where(function($targetQ) {
                                           // target_audience contient "operators" (gestion JSON robuste)
                                           // whereJsonContains pour MySQL/PostgreSQL avec support JSON natif
                                           $targetQ->whereJsonContains('target_audience', 'operators')
                                                  // Fallback pour les cas où JSON n'est pas supporté nativement
                                                  ->orWhere('target_audience', 'like', '%"operators"%')
                                                  ->orWhere('target_audience', 'like', '%operators%');
                                       });
                });
            })
            ->get();
    }

    /**
     * Vérifier si un business profile est visible pour un utilisateur
     *
     * @param User $user
     * @param int $businessProfileId
     * @return bool
     */
    public function isBusinessProfileVisible(User $user, int $businessProfileId)
    {
        $visibleBusinessProfiles = $this->getVisibleBusinessProfilesForIntegrator($user);
        
        return $visibleBusinessProfiles->contains('id', $businessProfileId);
    }
}
