<?php

namespace App\Services;

use App\Models\Group;
use App\Models\ChargingPoint;
use App\Models\Station;
use App\Models\PricingPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GroupService
{
    /**
     * Créer un nouveau groupe
     */
    public function createGroup(array $data): array
    {
        DB::beginTransaction();
        
        try {
            $group = Group::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'integrator_id' => $data['integrator_id'],
                'partner_id' => $data['partner_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'type' => $data['type'] ?? 'standard', // standard, premium, enterprise
                'max_charging_points' => $data['max_charging_points'] ?? null,
                'settings' => $data['settings'] ?? [],
            ]);

            // Associer les stations si fournies
            if (isset($data['station_ids'])) {
                $group->stations()->attach($data['station_ids']);
            }

            // Associer les plans tarifaires si fournis
            if (isset($data['pricing_plan_ids'])) {
                $group->pricingPlans()->attach($data['pricing_plan_ids']);
            }

            DB::commit();

            Log::info('Groupe créé avec succès', [
                'group_id' => $group->id,
                'name' => $group->name,
                'integrator_id' => $group->integrator_id
            ]);

            return [
                'success' => true,
                'group' => $group,
                'message' => 'Groupe créé avec succès'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la création du groupe', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la création du groupe: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mettre à jour un groupe
     */
    public function updateGroup(Group $group, array $data): array
    {
        DB::beginTransaction();
        
        try {
            $group->update([
                'name' => $data['name'] ?? $group->name,
                'description' => $data['description'] ?? $group->description,
                'is_active' => $data['is_active'] ?? $group->is_active,
                'type' => $data['type'] ?? $group->type,
                'max_charging_points' => $data['max_charging_points'] ?? $group->max_charging_points,
                'settings' => $data['settings'] ?? $group->settings,
            ]);

            // Mettre à jour les associations
            if (isset($data['station_ids'])) {
                $group->stations()->sync($data['station_ids']);
            }

            if (isset($data['pricing_plan_ids'])) {
                $group->pricingPlans()->sync($data['pricing_plan_ids']);
            }

            DB::commit();

            Log::info('Groupe mis à jour avec succès', [
                'group_id' => $group->id,
                'name' => $group->name
            ]);

            return [
                'success' => true,
                'group' => $group,
                'message' => 'Groupe mis à jour avec succès'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la mise à jour du groupe', [
                'error' => $e->getMessage(),
                'group_id' => $group->id
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du groupe: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Supprimer un groupe
     */
    public function deleteGroup(Group $group): array
    {
        try {
            // Vérifier s'il y a des bornes associées
            $chargingPointsCount = $group->chargingPoints()->count();
            if ($chargingPointsCount > 0) {
                return [
                    'success' => false,
                    'message' => "Impossible de supprimer le groupe : {$chargingPointsCount} borne(s) associée(s)"
                ];
            }

            $group->delete();

            Log::info('Groupe supprimé avec succès', [
                'group_id' => $group->id,
                'name' => $group->name
            ]);

            return [
                'success' => true,
                'message' => 'Groupe supprimé avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du groupe', [
                'error' => $e->getMessage(),
                'group_id' => $group->id
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression du groupe: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ajouter une borne à un groupe
     */
    public function addChargingPointToGroup(Group $group, ChargingPoint $chargingPoint): array
    {
        try {
            // Vérifier la limite de bornes
            if ($group->max_charging_points && $group->chargingPoints()->count() >= $group->max_charging_points) {
                return [
                    'success' => false,
                    'message' => 'Limite de bornes atteinte pour ce groupe'
                ];
            }

            $group->chargingPoints()->attach($chargingPoint->id);

            Log::info('Borne ajoutée au groupe', [
                'group_id' => $group->id,
                'charging_point_id' => $chargingPoint->id
            ]);

            return [
                'success' => true,
                'message' => 'Borne ajoutée au groupe avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'ajout de la borne au groupe', [
                'error' => $e->getMessage(),
                'group_id' => $group->id,
                'charging_point_id' => $chargingPoint->id
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de la borne au groupe: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retirer une borne d'un groupe
     */
    public function removeChargingPointFromGroup(Group $group, ChargingPoint $chargingPoint): array
    {
        try {
            $group->chargingPoints()->detach($chargingPoint->id);

            Log::info('Borne retirée du groupe', [
                'group_id' => $group->id,
                'charging_point_id' => $chargingPoint->id
            ]);

            return [
                'success' => true,
                'message' => 'Borne retirée du groupe avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors du retrait de la borne du groupe', [
                'error' => $e->getMessage(),
                'group_id' => $group->id,
                'charging_point_id' => $chargingPoint->id
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du retrait de la borne du groupe: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir les statistiques d'un groupe
     */
    public function getGroupStatistics(Group $group): array
    {
        $chargingPoints = $group->chargingPoints();
        
        $stats = [
            'total_charging_points' => $chargingPoints->count(),
            'active_charging_points' => $chargingPoints->where('status', 'online')->count(),
            'charging_charging_points' => $chargingPoints->where('status', 'charging')->count(),
            'offline_charging_points' => $chargingPoints->where('status', 'offline')->count(),
            'maintenance_charging_points' => $chargingPoints->where('status', 'maintenance')->count(),
            
            // Statistiques des transactions
            'total_transactions' => $group->transactions()->count(),
            'total_energy_delivered' => $group->transactions()->sum('energy_delivered'),
            'total_revenue' => $group->transactions()->sum('amount'),
            
            // Statistiques des sessions
            'active_sessions' => $group->chargingSessions()->where('status', 'active')->count(),
            'completed_sessions_today' => $group->chargingSessions()
                ->where('status', 'completed')
                ->whereDate('created_at', Carbon::today())
                ->count(),
        ];

        return $stats;
    }

    /**
     * Obtenir les groupes accessibles à un utilisateur
     */
    public function getAccessibleGroups(User $user)
    {
        $query = Group::query();

        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('integrator')) {
            return $query->where('integrator_id', $user->integrator_id);
        }

        if ($user->hasRole('partner')) {
            return $query->where('partner_id', $user->partner_id);
        }

        return $query->where('id', 0); // Aucun accès
    }

    /**
     * Rechercher des groupes
     */
    public function searchGroups(array $filters = [])
    {
        $query = Group::query();

        // Filtre par nom
        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        // Filtre par intégrateur
        if (isset($filters['integrator_id'])) {
            $query->where('integrator_id', $filters['integrator_id']);
        }

        // Filtre par partenaire
        if (isset($filters['partner_id'])) {
            $query->where('partner_id', $filters['partner_id']);
        }

        // Filtre par type
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filtre par statut
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query;
    }

    /**
     * Dupliquer un groupe
     */
    public function duplicateGroup(Group $group, array $data = []): array
    {
        DB::beginTransaction();
        
        try {
            $newGroup = $group->replicate();
            $newGroup->name = $data['name'] ?? $group->name . ' (Copie)';
            $newGroup->description = $data['description'] ?? $group->description;
            $newGroup->is_active = $data['is_active'] ?? false;
            $newGroup->save();

            // Copier les associations
            $group->stations()->get()->each(function ($station) use ($newGroup) {
                $newGroup->stations()->attach($station->id);
            });

            $group->pricingPlans()->get()->each(function ($plan) use ($newGroup) {
                $newGroup->pricingPlans()->attach($plan->id);
            });

            DB::commit();

            Log::info('Groupe dupliqué avec succès', [
                'original_group_id' => $group->id,
                'new_group_id' => $newGroup->id
            ]);

            return [
                'success' => true,
                'group' => $newGroup,
                'message' => 'Groupe dupliqué avec succès'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la duplication du groupe', [
                'error' => $e->getMessage(),
                'group_id' => $group->id
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la duplication du groupe: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir les plans tarifaires compatibles pour un groupe
     */
    public function getCompatiblePricingPlans(Group $group): array
    {
        $plans = PricingPlan::where('is_active', true)
            ->where(function ($query) use ($group) {
                $query->where('integrator_id', $group->integrator_id)
                      ->orWhereNull('integrator_id');
            })
            ->get();

        return $plans->toArray();
    }
}