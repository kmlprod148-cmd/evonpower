<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Service pour déterminer les clients liés à un intégrateur, opérateur ou partenaire.
 * Un client est lié s'il a fait une réservation sur un point de charge de l'entité.
 *
 * Règles par rôle :
 * - Intégrateur : clients ayant réservé sur les bornes (integrator_id, user_id opérateurs, partner_id partenaires)
 * - Opérateur : clients ayant réservé sur les bornes dont il est propriétaire (user_id)
 * - Partenaire : clients ayant réservé sur les bornes de son partenaire (partner_id)
 */
class CreditClientScopeService
{
    /**
     * Retourne les IDs des clients ayant réservé sur les points de charge de l'utilisateur.
     * Pour admin/super_admin : tous les clients.
     * Pour intégrateur : clients des bornes de son intégrateur (incl. opérateurs et partenaires).
     * Pour opérateur : clients des bornes dont il est opérateur (user_id).
     * Pour partenaire : clients des bornes de son partenaire (partner_id).
     */
    public function getRelatedClientIds(\Illuminate\Contracts\Auth\Authenticatable $user): ?array
    {
        if ($user->hasRole(['admin', 'super_admin'])) {
            return null; // null = tous les clients
        }

        $chargingPointIds = $this->getRelatedChargingPointIds($user);
        if ($chargingPointIds->isEmpty()) {
            return [];
        }

        return Reservation::whereIn('charging_point_id', $chargingPointIds->toArray())
            ->whereNotNull('user_id')
            ->select('user_id')
            ->distinct()
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * Rôles considérés comme "clients" (utilisateurs finaux de l'app).
     * 'user' = inscription depuis l'app, 'client' = créés par admin/intégrateur.
     */
    protected function getClientRoleNames(): array
    {
        return ['user', 'client'];
    }

    /**
     * Retourne la query des clients pour un utilisateur (intégrateur, opérateur, partenaire).
     */
    public function getRelatedClientsQuery(\Illuminate\Contracts\Auth\Authenticatable $user)
    {
        $clientIds = $this->getRelatedClientIds($user);
        $clientRoles = $this->getClientRoleNames();

        if ($clientIds === null) {
            return User::whereHas('roles', fn ($q) => $q->whereIn('name', $clientRoles))
                ->orderBy('name');
        }

        if (empty($clientIds)) {
            return User::whereHas('roles', fn ($q) => $q->whereIn('name', $clientRoles))
                ->whereIn('id', [])
                ->orderBy('name');
        }

        return User::whereHas('roles', fn ($q) => $q->whereIn('name', $clientRoles))
            ->whereIn('id', $clientIds)
            ->orderBy('name');
    }

    /**
     * Vérifie si un client est accessible par l'utilisateur.
     */
    public function canAccessClient(\Illuminate\Contracts\Auth\Authenticatable $manager, int $clientId): bool
    {
        if ($manager->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        $clientIds = $this->getRelatedClientIds($manager);
        return $clientIds !== null && in_array($clientId, $clientIds);
    }

    /**
     * Retourne les IDs des points de charge liés à l'utilisateur.
     */
    protected function getRelatedChargingPointIds(\Illuminate\Contracts\Auth\Authenticatable $user): Collection
    {
        $query = ChargingPoint::query();

        if ($user->hasRole('integrator')) {
            $integrator = \App\Models\Integrator::where('user_id', $user->id)->first();
            $integratorId = $integrator?->id ?? $user->integrator_id;

            if (!$integratorId) {
                return collect();
            }

            $operatorIds = User::where('integrator_id', $integratorId)->pluck('id');
            $partnerIds = \App\Models\Partner::where('integrator_id', $integratorId)->pluck('id');

            return ChargingPoint::where(function ($q) use ($integratorId, $operatorIds, $partnerIds) {
                $q->where('integrator_id', $integratorId)
                    ->orWhereIn('user_id', $operatorIds)
                    ->orWhereIn('partner_id', $partnerIds);
            })->pluck('id');
        }

        if ($user->hasRole('operator')) {
            return ChargingPoint::where('user_id', $user->id)->pluck('id');
        }

        if ($user->hasRole('partner') && $user->partner_id) {
            return ChargingPoint::where('partner_id', $user->partner_id)->pluck('id');
        }

        return collect();
    }
}
