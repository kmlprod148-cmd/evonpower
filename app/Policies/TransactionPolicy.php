<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Auth\Access\Response;

class TransactionPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return Response::allow();
        }
        return null;
    }

    public function viewAny(User $user)
    {
        // Admin and super-admin can view all transactions
        if ($user->hasRole(['admin', 'super_admin'])) {
            return Response::allow();
        }

        // Operators, integrators, and partners can view transactions
        if ($user->hasRole(['operator', 'integrator', 'partner'])) {
            return Response::allow();
        }

        // Clients (user role) can view their own transactions - controller filters by user_id
        if ($user->hasRole(['user', 'User', 'client'])) {
            return Response::allow();
        }

        // Allow if the user has the 'view_transactions' or 'view_own_transactions' permission
        if ($user->can('view_transactions') || $user->can('view_own_transactions') || $user->can('view_own_history')) {
            return Response::allow();
        }

        return Response::deny('Accès refusé.');
    }

    public function view(User $user, Transaction $transaction)
    {
        // Admin and super-admin can view all transactions
        if ($user->hasRole(['admin', 'super_admin'])) {
            return Response::allow();
        }
        
        // Allow if the user owns the transaction or has the 'view_transactions' permission
        if ($transaction->user_id === $user->id || $user->can('view_transactions')) {
            return Response::allow();
        }

        return Response::deny('Accès refusé.');
    }

    public function update(User $user, Transaction $transaction)
    {
        // Admin and super-admin can update all transactions
        if ($user->hasRole(['admin', 'super_admin'])) {
            return Response::allow();
        }
        
        return $transaction->user_id === $user->id
            ? Response::allow() : Response::deny('Accès refusé.');
    }

    public function delete(User $user, Transaction $transaction)
    {
        // Admin and super-admin can delete all transactions
        if ($user->hasRole(['admin', 'super_admin'])) {
            return Response::allow();
        }
        
        return $transaction->user_id === $user->id
            ? Response::allow() : Response::deny('Accès refusé.');
    }

    /**
     * View collect information for a transaction
     */
    public function viewCollectInfo(User $user, Transaction $transaction)
    {
        // Admin and super-admin can view all collect info
        if ($user->hasRole(['admin', 'super_admin'])) {
            return Response::allow();
        }

        // Integrator can view collect info for their transactions
        if ($user->hasRole('integrator')) {
            $integratorId = $user->integrator_id;
            if ($transaction->collect_user_id === $user->id && $transaction->collect_user_type === 'integrator') {
                return Response::allow();
            }
            // Check if transaction belongs to their integrator
            if ($transaction->chargingPoint && $transaction->chargingPoint->integrator_id === $integratorId) {
                return Response::allow();
            }
        }

        // Partner can view collect info for their transactions
        if ($user->hasRole('partner') && $user->partner_id) {
            if ($transaction->collect_user_id === $user->id && $transaction->collect_user_type === 'partner') {
                return Response::allow();
            }
            // Check if transaction belongs to their partner
            if ($transaction->chargingPoint && $transaction->chargingPoint->partner_id === $user->partner_id) {
                return Response::allow();
            }
        }

        return Response::deny('Accès refusé aux informations de collecte.');
    }

    /**
     * Request withdrawal for a partner/integrator
     */
    public function requestWithdrawal(User $user)
    {
        // Only partners and integrators can request withdrawal
        if ($user->hasRole(['partner', 'integrator'])) {
            return Response::allow();
        }

        return Response::deny('Seuls les partenaires et intégrateurs peuvent demander un retrait.');
    }

    /**
     * View withdrawal requests
     */
    public function viewWithdrawalRequests(User $user)
    {
        // Admin can view all withdrawal requests
        if ($user->hasRole(['admin', 'super_admin'])) {
            return Response::allow();
        }

        // Partners and integrators can view their own withdrawal requests
        if ($user->hasRole(['partner', 'integrator'])) {
            return Response::allow();
        }

        return Response::deny('Accès refusé aux demandes de retrait.');
    }

    /**
     * Approve or reject withdrawal requests
     */
    public function manageWithdrawalRequests(User $user)
    {
        // Only admin can approve/reject withdrawal requests
        if ($user->hasRole(['admin', 'super_admin'])) {
            return Response::allow();
        }

        return Response::deny('Seuls les administrateurs peuvent gérer les demandes de retrait.');
    }

    /**
     * View financial dashboard/balance
     */
    public function viewFinancialDashboard(User $user)
    {
        // Admin, partners and integrators can view financial dashboard
        if ($user->hasRole(['admin', 'super_admin', 'partner', 'integrator'])) {
            return Response::allow();
        }

        return Response::deny('Accès refusé au tableau de bord financier.');
    }

    /**
     * Process refund
     */
    public function refund(User $user, Transaction $transaction)
    {
        // Only admin can process refunds
        if ($user->hasRole(['admin', 'super_admin'])) {
            return Response::allow();
        }

        return Response::deny('Seuls les administrateurs peuvent traiter les remboursements.');
    }
}
