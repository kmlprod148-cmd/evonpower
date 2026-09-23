<?php

namespace App\Services;

use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\Station;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    /**
     * Vérifier les permissions d'accès aux bornes
     */
    public function canAccessChargingPoint(User $user, ChargingPoint $chargingPoint): bool
    {
        // Admin a accès à tout
        if ($user->hasRole('admin')) {
            return true;
        }

        // Intégrateur peut accéder à ses bornes
        if ($user->hasRole('integrator') && $user->integrator_id === $chargingPoint->integrator_id) {
            return true;
        }

        // Partenaire peut accéder à ses bornes
        if ($user->hasRole('partner') && $user->partner_id === $chargingPoint->partner_id) {
            return true;
        }

        // Client peut voir les bornes publiques
        if ($user->hasRole('client') && $chargingPoint->public_access) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier les permissions d'accès aux groupes
     */
    public function canAccessGroup(User $user, Group $group): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('integrator') && $group->integrator_id === $user->integrator_id) {
            return true;
        }

        if ($user->hasRole('partner') && $group->partner_id === $user->partner_id) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier les permissions d'accès aux stations
     */
    public function canAccessStation(User $user, Station $station): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('integrator') && $station->integrator_id === $user->integrator_id) {
            return true;
        }

        if ($user->hasRole('partner') && $station->partner_id === $user->partner_id) {
            return true;
        }

        return false;
    }

    /**
     * Obtenir les bornes accessibles à un utilisateur
     */
    public function getAccessibleChargingPoints(User $user)
    {
        $query = ChargingPoint::query();

        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('integrator')) {
            return $query->where('integrator_id', $user->integrator_id);
        }

        if ($user->hasRole('partner')) {
            return $query->where('partner_id', $user->partner_id);
        }

        if ($user->hasRole('client')) {
            return $query->where('public_access', true);
        }

        return $query->where('id', 0); // Aucun accès
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
     * Créer les rôles et permissions par défaut
     */
    public function createDefaultRolesAndPermissions(): void
    {
        // Créer les rôles
        $roles = [
            'admin' => 'Administrateur système',
            'integrator' => 'Intégrateur',
            'partner' => 'Partenaire',
            'operator' => 'Opérateur',
            'client' => 'Client'
        ];

        foreach ($roles as $roleName => $roleDescription) {
            Role::firstOrCreate(['name' => $roleName], [
                'guard_name' => 'web',
                'description' => $roleDescription
            ]);
        }

        // Créer les permissions
        $permissions = [
            // Gestion des bornes
            'charging-points.view' => 'Voir les bornes',
            'charging-points.create' => 'Créer des bornes',
            'charging-points.edit' => 'Modifier les bornes',
            'charging-points.delete' => 'Supprimer les bornes',
            
            // Gestion des groupes
            'groups.view' => 'Voir les groupes',
            'groups.create' => 'Créer des groupes',
            'groups.edit' => 'Modifier les groupes',
            'groups.delete' => 'Supprimer les groupes',
            
            // Gestion des stations
            'stations.view' => 'Voir les stations',
            'stations.create' => 'Créer des stations',
            'stations.edit' => 'Modifier les stations',
            'stations.delete' => 'Supprimer les stations',
            
            // Gestion des plans tarifaires
            'pricing-plans.view' => 'Voir les plans tarifaires',
            'pricing-plans.create' => 'Créer des plans tarifaires',
            'pricing-plans.edit' => 'Modifier les plans tarifaires',
            'pricing-plans.delete' => 'Supprimer les plans tarifaires',
            
            // Gestion des transactions
            'transactions.view' => 'Voir les transactions',
            'transactions.create' => 'Créer des transactions',
            'transactions.edit' => 'Modifier les transactions',
            'transactions.delete' => 'Supprimer les transactions',
            
            // Gestion des abonnements
            'subscriptions.view' => 'Voir les abonnements',
            'subscriptions.create' => 'Créer des abonnements',
            'subscriptions.edit' => 'Modifier les abonnements',
            'subscriptions.delete' => 'Supprimer les abonnements',
            
            // Gestion des utilisateurs
            'users.view' => 'Voir les utilisateurs',
            'users.create' => 'Créer des utilisateurs',
            'users.edit' => 'Modifier les utilisateurs',
            'users.delete' => 'Supprimer les utilisateurs',
            
            // Rapports et analytics
            'reports.view' => 'Voir les rapports',
            'reports.export' => 'Exporter les rapports',
            
            // Configuration système
            'settings.view' => 'Voir les paramètres',
            'settings.edit' => 'Modifier les paramètres',
        ];

        foreach ($permissions as $permissionName => $permissionDescription) {
            Permission::firstOrCreate(['name' => $permissionName], [
                'guard_name' => 'web',
                'description' => $permissionDescription
            ]);
        }

        // Assigner les permissions aux rôles
        $this->assignPermissionsToRoles();
    }

    /**
     * Assigner les permissions aux rôles
     */
    private function assignPermissionsToRoles(): void
    {
        $adminRole = Role::findByName('admin');
        $integratorRole = Role::findByName('integrator');
        $partnerRole = Role::findByName('partner');
        $operatorRole = Role::findByName('operator');
        $clientRole = Role::findByName('client');

        // Admin - Toutes les permissions
        $adminRole->givePermissionTo(Permission::all());

        // Intégrateur - Permissions limitées à ses entités
        $integratorPermissions = [
            'charging-points.view', 'charging-points.create', 'charging-points.edit',
            'groups.view', 'groups.create', 'groups.edit',
            'stations.view', 'stations.create', 'stations.edit',
            'pricing-plans.view', 'pricing-plans.create', 'pricing-plans.edit',
            'transactions.view', 'transactions.create',
            'subscriptions.view', 'subscriptions.create', 'subscriptions.edit',
            'users.view', 'users.create', 'users.edit',
            'reports.view', 'reports.export',
            'settings.view'
        ];
        $integratorRole->givePermissionTo($integratorPermissions);

        // Partenaire - Permissions limitées
        $partnerPermissions = [
            'charging-points.view', 'charging-points.edit',
            'groups.view',
            'stations.view',
            'pricing-plans.view',
            'transactions.view',
            'subscriptions.view',
            'users.view', 'users.create',
            'reports.view'
        ];
        $partnerRole->givePermissionTo($partnerPermissions);

        // Opérateur - Permissions de base
        $operatorPermissions = [
            'charging-points.view',
            'transactions.view', 'transactions.create',
            'reports.view'
        ];
        $operatorRole->givePermissionTo($operatorPermissions);

        // Client - Permissions minimales
        $clientPermissions = [
            'charging-points.view',
            'transactions.view'
        ];
        $clientRole->givePermissionTo($clientPermissions);
    }

    /**
     * Vérifier si un utilisateur peut effectuer une action
     */
    public function can(User $user, string $permission, $model = null): bool
    {
        // Vérifier la permission de base
        if (!$user->hasPermissionTo($permission)) {
            return false;
        }

        // Si un modèle est fourni, vérifier l'accès spécifique
        if ($model) {
            if ($model instanceof ChargingPoint) {
                return $this->canAccessChargingPoint($user, $model);
            }
            
            if ($model instanceof Group) {
                return $this->canAccessGroup($user, $model);
            }
            
            if ($model instanceof Station) {
                return $this->canAccessStation($user, $model);
            }
        }

        return true;
    }
}