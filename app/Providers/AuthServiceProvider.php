<?php

namespace App\Providers;

use App\Models\BusinessProfile;
use App\Models\User;
use App\Policies\BusinessProfilePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan;
use App\Policies\PlanPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        BusinessProfile::class => BusinessProfilePolicy::class,
        \App\Models\ChargingPoint::class => \App\Policies\ChargingPointPolicy::class,
        \App\Models\Group::class => \App\Policies\GroupPolicy::class,
        \App\Models\Partner::class => \App\Policies\PartnerPolicy::class,
        \App\Models\Integrator::class => \App\Policies\IntegratorPolicy::class,
        \App\Models\Transaction::class => \App\Policies\TransactionPolicy::class,
        \App\Models\Reservation::class => \App\Policies\ReservationPolicy::class,
        \App\Models\CommissionPlan::class => \App\Policies\CommissionPlanPolicy::class,
        \App\Models\User::class => \App\Policies\OperatorPolicy::class,
        \App\Models\WalletTransaction::class => \App\Policies\WalletTransactionPolicy::class,
        \App\Models\IntegratorProfile::class => \App\Policies\IntegratorProfilePolicy::class,
        // Autres modèles...
        Plan::class => PlanPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        // Short-circuit all Gates for non-admin users (e.g. ClientUser).
        // Gate closures below are typed as (User $user); passing a ClientUser
        // causes a fatal TypeError. Returning false here prevents that.
        Gate::before(function ($user, $ability) {
            if (!$user instanceof User) {
                return false;
            }
        });

        // Enregistrer le provider personnalisé pour gérer les mots de passe non hachés

        // Définir les gates supplémentaires
        Gate::define('manage-partner-rates', function (User $user, BusinessProfile $profile) {
            return $user->isAdmin() || 
                   ($user->isIntegrator() && $profile->parent_profile_id === $user->business_profile_id);
        });

        // Hierarchical access gates
        Gate::define('manage-integrator-operators', function (User $user, $integratorId) {
            if ($user->hasRole('admin')) {
                return true;
            }
            if ($user->hasRole('integrator') && $user->integrator_id == $integratorId) {
                return true;
            }
            return false;
        });

        Gate::define('manage-partner-groups', function (User $user, $partnerId) {
            if ($user->hasRole('admin')) {
                return true;
            }
            if ($user->hasRole('integrator')) {
                $partner = \App\Models\Partner::find($partnerId);
                return $partner && $partner->integrator_id == $user->integrator_id;
            }
            if ($user->hasRole('partner') && $user->partner_id == $partnerId) {
                return true;
            }
            return false;
        });

        Gate::define('manage-group-charging-points', function (User $user, $groupId) {
            if ($user->hasRole('admin')) {
                return true;
            }
            $group = \App\Models\Group::find($groupId);
            if (!$group) {
                return false;
            }
            if ($user->hasRole('integrator') && $group->partner && $group->partner->integrator_id == $user->integrator_id) {
                return true;
            }
            if ($user->hasRole('partner') && $group->partner_id == $user->partner_id) {
                return true;
            }
            if ($user->hasRole('operator') && $group->partner && $group->partner->integrator_id == $user->integrator_id) {
                return true;
            }
            return false;
        });

        // Admin Reservations Gates - Autoriser les intégrateurs et opérateurs
        Gate::define('view_admin_reservations', function (User $user) {
            if ($user->hasRole(['admin', 'super_admin'])) {
                return true;
            }
            // Les intégrateurs peuvent voir les réservations admin (pour leurs propres ressources et celles de leurs opérateurs)
            if ($user->hasRole('integrator')) {
                return true;
            }
            // Les opérateurs peuvent voir leurs propres réservations et celles de leurs charging points
            if ($user->hasRole('operator')) {
                return true;
            }
            // Vérifier directement via Spatie Permission (sans passer par la gate pour éviter la récursion)
            return $user->hasPermissionTo('view_admin_reservations', 'web');
        });

        Gate::define('view_admin_reservation_details', function (User $user) {
            if ($user->hasRole(['admin', 'super_admin'])) {
                return true;
            }
            // Les intégrateurs peuvent voir les détails des réservations admin
            if ($user->hasRole('integrator')) {
                return true;
            }
            // Les opérateurs peuvent voir les détails de leurs propres réservations et celles de leurs charging points
            if ($user->hasRole('operator')) {
                return true;
            }
            // Vérifier directement via Spatie Permission
            return $user->hasPermissionTo('view_admin_reservation_details', 'web');
        });

        // Admin Transactions Gates - Autoriser les intégrateurs et opérateurs
        Gate::define('view_admin_transactions', function (User $user) {
            if ($user->hasRole(['admin', 'super_admin'])) {
                return true;
            }
            // Les intégrateurs peuvent voir les transactions admin (pour leurs propres ressources et celles de leurs opérateurs)
            if ($user->hasRole('integrator')) {
                return true;
            }
            // Les opérateurs peuvent voir leurs propres transactions et celles de leurs charging points
            if ($user->hasRole('operator')) {
                return true;
            }
            // Vérifier directement via Spatie Permission
            return $user->hasPermissionTo('view_admin_transactions', 'web');
        });

        Gate::define('view_admin_transaction_details', function (User $user) {
            if ($user->hasRole(['admin', 'super_admin'])) {
                return true;
            }
            // Les intégrateurs peuvent voir les détails des transactions admin
            if ($user->hasRole('integrator')) {
                return true;
            }
            // Les opérateurs peuvent voir les détails de leurs propres transactions et celles de leurs charging points
            if ($user->hasRole('operator')) {
                return true;
            }
            // Vérifier directement via Spatie Permission
            return $user->hasPermissionTo('view_admin_transaction_details', 'web');
        });

        // Charging Points Gates - Autoriser les opérateurs et partenaires
        Gate::define('create_charging_points', function (User $user) {
            // Utiliser directement la politique ChargingPointPolicy::create qui autorise admin, integrator, partner, et operator
            $policy = app(\App\Policies\ChargingPointPolicy::class);
            return $policy->create($user);
        });

        // Approbation des recharges offline - intégrateurs, opérateurs, partenaires (leurs clients uniquement)
        Gate::define('approve_offline_credits', function (User $user) {
            return $user->hasRole(['integrator', 'operator', 'partner']);
        });
    }
}