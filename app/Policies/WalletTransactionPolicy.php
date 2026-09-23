<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class WalletTransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any wallet transactions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the wallet transaction.
     */
    public function view(User $user, WalletTransaction $walletTransaction): bool
    {
        // Admin can view all transactions
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can view their own transactions
        if ($walletTransaction->wallet && $walletTransaction->wallet->owner_type === User::class) {
            return $walletTransaction->wallet->owner_id === $user->id;
        }

        // Integrators can view transactions for their operators
        if ($user->hasRole('integrator')) {
            if ($walletTransaction->wallet && $walletTransaction->wallet->owner_type === User::class) {
                $owner = $walletTransaction->wallet->owner;
                return $owner && $owner->integrator_id === $user->integrator_id;
            }
        }

        // Partners can view transactions for their users
        if ($user->hasRole('partner')) {
            if ($walletTransaction->wallet && $walletTransaction->wallet->owner_type === User::class) {
                $owner = $walletTransaction->wallet->owner;
                return $owner && $owner->partner_id === $user->partner_id;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create wallet transactions.
     */
    public function create(User $user): bool
    {
        // Only admin can create transactions directly
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the wallet transaction.
     */
    public function update(User $user, WalletTransaction $walletTransaction): bool
    {
        // Only admin can update transactions
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the wallet transaction.
     */
    public function delete(User $user, WalletTransaction $walletTransaction): bool
    {
        // Only admin can delete transactions
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the wallet transaction.
     */
    public function restore(User $user, WalletTransaction $walletTransaction): bool
    {
        // Only admin can restore transactions
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the wallet transaction.
     */
    public function forceDelete(User $user, WalletTransaction $walletTransaction): bool
    {
        // Only admin can force delete transactions
        return $user->hasRole('admin');
    }
}
