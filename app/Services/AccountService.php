<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountService
{
    /**
     * Crée ou récupère un compte pour une entité
     */
    public function getOrCreateAccount($entity, string $currency = 'EUR'): Account
    {
        $accountableType = get_class($entity);
        $accountableId = $entity->id;

        return Account::firstOrCreate([
            'accountable_type' => $accountableType,
            'accountable_id' => $accountableId,
        ], [
            'balance' => 0,
            'currency' => $currency
        ]);
    }

    /**
     * Met à jour le solde d'un compte
     */
    public function updateBalance(Account $account, float $amount, string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            $oldBalance = $account->balance;
            $account->increment('balance', $amount);
            $account->refresh();

            Log::info('Solde de compte mis à jour', [
                'account_id' => $account->id,
                'accountable_type' => $account->accountable_type,
                'accountable_id' => $account->accountable_id,
                'old_balance' => $oldBalance,
                'new_balance' => $account->balance,
                'amount' => $amount,
                'reason' => $reason
            ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour du solde', [
                'account_id' => $account->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtient le solde d'un intégrateur
     */
    public function getIntegratorBalance(int $integratorId): float
    {
        $integrator = Integrator::find($integratorId);
        if (!$integrator) {
            return 0.0;
        }

        $account = $this->getOrCreateAccount($integrator);
        return (float) $account->balance;
    }

    /**
     * Obtient le solde d'un partenaire
     */
    public function getPartnerBalance(int $partnerId): float
    {
        $partner = Partner::find($partnerId);
        if (!$partner) {
            return 0.0;
        }

        $account = $this->getOrCreateAccount($partner);
        return (float) $account->balance;
    }

    /**
     * Obtient le solde d'un business profile
     */
    public function getBusinessProfileBalance(int $businessProfileId): float
    {
        $businessProfile = BusinessProfile::find($businessProfileId);
        if (!$businessProfile) {
            return 0.0;
        }

        $account = $this->getOrCreateAccount($businessProfile);
        return (float) $account->balance;
    }

    /**
     * Met à jour le solde d'un intégrateur
     */
    public function updateIntegratorBalance(int $integratorId, float $amount, string $reason = null): bool
    {
        $integrator = Integrator::find($integratorId);
        if (!$integrator) {
            return false;
        }

        $account = $this->getOrCreateAccount($integrator);
        return $this->updateBalance($account, $amount, $reason);
    }

    /**
     * Met à jour le solde d'un partenaire
     */
    public function updatePartnerBalance(int $partnerId, float $amount, string $reason = null): bool
    {
        $partner = Partner::find($partnerId);
        if (!$partner) {
            return false;
        }

        $account = $this->getOrCreateAccount($partner);
        return $this->updateBalance($account, $amount, $reason);
    }

    /**
     * Met à jour le solde d'un business profile
     */
    public function updateBusinessProfileBalance(int $businessProfileId, float $amount, string $reason = null): bool
    {
        $businessProfile = BusinessProfile::find($businessProfileId);
        if (!$businessProfile) {
            return false;
        }

        $account = $this->getOrCreateAccount($businessProfile);
        return $this->updateBalance($account, $amount, $reason);
    }

    /**
     * Obtient le résumé des soldes pour tous les types d'entités
     */
    public function getBalanceSummary(): array
    {
        $integrators = Integrator::with('account')->get();
        $partners = Partner::with('account')->get();
        $businessProfiles = BusinessProfile::with('account')->get();

        return [
            'integrators' => $integrators->map(function($integrator) {
                return [
                    'id' => $integrator->id,
                    'name' => $integrator->name,
                    'balance' => $integrator->account ? (float) $integrator->account->balance : 0.0,
                    'currency' => $integrator->account ? $integrator->account->currency : 'EUR'
                ];
            }),
            'partners' => $partners->map(function($partner) {
                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'balance' => $partner->account ? (float) $partner->account->balance : 0.0,
                    'currency' => $partner->account ? $partner->account->currency : 'EUR'
                ];
            }),
            'business_profiles' => $businessProfiles->map(function($profile) {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'balance' => $profile->account ? (float) $profile->account->balance : 0.0,
                    'currency' => $profile->account ? $profile->account->currency : 'EUR'
                ];
            })
        ];
    }

    /**
     * Effectue un transfert entre deux comptes
     */
    public function transfer(Account $fromAccount, Account $toAccount, float $amount, string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            // Débiter le compte source
            $this->updateBalance($fromAccount, -$amount, "Transfert sortant: " . ($reason ?? 'Transfert'));

            // Créditer le compte destination
            $this->updateBalance($toAccount, $amount, "Transfert entrant: " . ($reason ?? 'Transfert'));

            Log::info('Transfert effectué entre comptes', [
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'amount' => $amount,
                'reason' => $reason
            ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors du transfert entre comptes', [
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Vérifie si un compte a un solde suffisant
     */
    public function hasSufficientBalance(Account $account, float $amount): bool
    {
        return $account->balance >= $amount;
    }

    /**
     * Obtient l'historique des transactions d'un compte
     */
    public function getAccountHistory(Account $account, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $account->transactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
