<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReservationRevenueDistributionService
{
    /**
     * Applique la répartition des revenus à une transaction de réservation
     */
    public function applyRevenueDistributionToTransaction(Transaction $transaction): void
    {
        try {
            DB::beginTransaction();

            // Récupérer la réservation associée
            $reservation = $transaction->reservation;
            if (!$reservation) {
                Log::warning('Transaction sans réservation associée', [
                    'transaction_id' => $transaction->id
                ]);
                return;
            }

            // Vérifier que le montant total n'est pas null
            if ($transaction->price_total === null || $transaction->price_total <= 0) {
                Log::warning('Transaction sans montant valide, répartition des revenus ignorée', [
                    'transaction_id' => $transaction->id,
                    'price_total' => $transaction->price_total
                ]);
                return;
            }

            // Calculer la répartition des revenus
            $revenueService = app(\App\Services\RevenueDistributionCalculationService::class);
            $distribution = $revenueService->calculateRevenueDistribution($reservation, $transaction->price_total);

            // Appliquer la répartition à la transaction
            $this->updateTransactionWithDistribution($transaction, $distribution);

            // Créer les enregistrements de répartition des revenus
            $this->createRevenueShareRecords($transaction, $distribution);

            // Mettre à jour les comptes financiers
            $this->updateFinancialAccounts($transaction, $distribution);

            DB::commit();

            Log::info('Répartition des revenus appliquée avec succès', [
                'transaction_id' => $transaction->id,
                'reservation_id' => $reservation->id,
                'total_amount' => $transaction->price_total,
                'distribution' => $distribution
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'application de la répartition des revenus', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Ne pas relancer : la réservation et la transaction doivent être conservées
            // L'TransactionObserver gère déjà les erreurs, mais on évite toute propagation
        }
    }

    /**
     * Met à jour la transaction avec la répartition des revenus
     */
    private function updateTransactionWithDistribution(Transaction $transaction, array $distribution): void
    {
        // Temporarily disable the observer to prevent infinite loops
        $transaction->unsetEventDispatcher();

        $dist = $distribution['distribution'] ?? [];
        $fees = $distribution['fees'] ?? [];
        $percentages = $distribution['percentages'] ?? [];

        $transaction->repartition_breakdown = [
            'distribution' => $dist,
            'fees' => $fees,
            'percentages' => $percentages,
            'calculated_at' => now()->toISOString(),
            'version' => '2.0'
        ];

        $transaction->save();
        
        // Re-enable the observer
        $transaction->setEventDispatcher(app('events'));
    }

    /**
     * Crée les enregistrements de répartition des revenus
     */
    private function createRevenueShareRecords(Transaction $transaction, array $distribution): void
    {
        $reservation = $transaction->reservation;
        $chargingPoint = $reservation->chargingPoint;
        
        if (!$chargingPoint) {
            Log::warning('Aucun point de charge trouvé pour la transaction', [
                'transaction_id' => $transaction->id,
                'reservation_id' => $reservation->id
            ]);
            return;
        }
        
        $businessProfile = $this->getBusinessProfile($chargingPoint);

        if (!$businessProfile) {
            Log::warning('Aucun business profile trouvé pour la répartition des revenus', [
                'transaction_id' => $transaction->id,
                'charging_point_id' => $chargingPoint->id ?? null
            ]);
            return;
        }

        // Supprimer les anciens enregistrements
        \App\Models\RevenueShare::where('transaction_id', $transaction->id)->delete();

        // Compatibilité avec les deux formats de distribution :
        // - Format "commission" : admin_commission, integrator_commission, partner_commission, operator_revenue
        // - Format "share" (RevenueDistributionCalculationService) : admin_share, integrator_share, operator_share
        $dist = $distribution['distribution'] ?? [];
        $adminAmount = (float) ($dist['admin_commission'] ?? $dist['admin_share'] ?? 0);
        $integratorAmount = (float) ($dist['integrator_commission'] ?? $dist['integrator_share'] ?? 0);
        $partnerAmount = (float) ($dist['partner_commission'] ?? 0);
        $operatorAmount = (float) ($dist['operator_revenue'] ?? $dist['operator_share'] ?? 0);

        // Créer les nouveaux enregistrements
        $revenueShares = [];

        // Part Admin (frais totaux)
        if ($adminAmount > 0) {
            $revenueShares[] = [
                'transaction_id' => $transaction->id,
                'business_profile_id' => $businessProfile->id,
                'integrator_id' => null,
                'partner_id' => null,
                'amount' => $adminAmount,
                'type' => 'admin_commission',
                'commission_plan_id' => null,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Part Intégrateur
        if ($integratorAmount > 0) {
            $revenueShares[] = [
                'transaction_id' => $transaction->id,
                'business_profile_id' => $businessProfile->id,
                'integrator_id' => $chargingPoint->integrator_id ?? null,
                'partner_id' => null,
                'amount' => $integratorAmount,
                'type' => 'integrator_commission',
                'commission_plan_id' => null,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Part Partenaire
        if ($partnerAmount > 0) {
            $revenueShares[] = [
                'transaction_id' => $transaction->id,
                'business_profile_id' => $businessProfile->id,
                'integrator_id' => null,
                'partner_id' => $chargingPoint->partner_id ?? null,
                'amount' => $partnerAmount,
                'type' => 'partner_commission',
                'commission_plan_id' => null,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Part Opérateur
        if ($operatorAmount > 0) {
            $revenueShares[] = [
                'transaction_id' => $transaction->id,
                'business_profile_id' => $businessProfile->id,
                'integrator_id' => null,
                'partner_id' => null,
                'amount' => $operatorAmount,
                'type' => 'operator_revenue',
                'commission_plan_id' => null,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Insérer tous les enregistrements
        if (!empty($revenueShares)) {
            \App\Models\RevenueShare::insert($revenueShares);
        }
    }

    /**
     * Met à jour les comptes financiers
     */
    private function updateFinancialAccounts(Transaction $transaction, array $distribution): void
    {
        $reservation = $transaction->reservation;
        $chargingPoint = $reservation->chargingPoint;
        
        if (!$chargingPoint) {
            Log::warning('Aucun point de charge trouvé pour la mise à jour des comptes financiers', [
                'transaction_id' => $transaction->id,
                'reservation_id' => $reservation->id
            ]);
            return;
        }
        
        $businessProfile = $this->getBusinessProfile($chargingPoint);

        if (!$businessProfile) {
            return;
        }

        // Créer ou récupérer le compte du business profile
        $businessProfileAccount = $businessProfile->account()->firstOrCreate([
            'accountable_type' => BusinessProfile::class,
            'accountable_id' => $businessProfile->id,
        ], [
            'balance' => 0,
            'currency' => 'EUR'
        ]);

        // Créer les transactions financières
        $this->createFinancialTransactions($transaction, $distribution, $businessProfileAccount);
    }

    /**
     * Crée les transactions financières
     */
    private function createFinancialTransactions(Transaction $transaction, array $distribution, $businessProfileAccount): void
    {
        $reservation = $transaction->reservation;
        $chargingPoint = $reservation->chargingPoint;

        // Obtenir ou créer un compte système pour le client
        $clientAccount = $this->getOrCreateSystemAccount('client', 'Compte Client Système');

        // Transaction principale - dépôt du montant total
        \App\Models\FinancialTransaction::create([
            'transaction_id' => $transaction->id,
            'payer_account_id' => $clientAccount->id,
            'payee_account_id' => $businessProfileAccount->id,
            'amount' => $transaction->price_total,
            'type' => 'revenue_deposit',
            'description' => "Dépôt initial pour réservation #{$reservation->id}",
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Mettre à jour le solde du compte
        $businessProfileAccount->increment('balance', $transaction->price_total);

        // Créer les transactions de répartition si nécessaire
        $this->createDistributionTransactions($transaction, $distribution, $businessProfileAccount);
    }

    /**
     * Crée les transactions de répartition
     */
    private function createDistributionTransactions(Transaction $transaction, array $distribution, $businessProfileAccount): void
    {
        $reservation = $transaction->reservation;
        $chargingPoint = $reservation->chargingPoint;

        // Compatibilité avec les deux formats de distribution (commission vs share)
        $dist = $distribution['distribution'] ?? [];
        $integratorAmount = (float) ($dist['integrator_commission'] ?? $dist['integrator_share'] ?? 0);
        $partnerAmount = (float) ($dist['partner_commission'] ?? 0);

        // Part Intégrateur
        if ($integratorAmount > 0 && $chargingPoint->integrator) {
            $integratorAccount = $chargingPoint->integrator->account()->firstOrCreate([
                'accountable_type' => \App\Models\Integrator::class,
                'accountable_id' => $chargingPoint->integrator->id,
            ], [
                'balance' => 0,
                'currency' => 'EUR'
            ]);

            \App\Models\FinancialTransaction::create([
                'transaction_id' => $transaction->id,
                'payer_account_id' => $businessProfileAccount->id,
                'payee_account_id' => $integratorAccount->id,
                'amount' => $integratorAmount,
                'type' => 'commission_payout',
                'description' => "Commission intégrateur pour réservation #{$reservation->id}",
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $businessProfileAccount->decrement('balance', $integratorAmount);
            $integratorAccount->increment('balance', $integratorAmount);
        }

        // Part Partenaire
        if ($partnerAmount > 0 && $chargingPoint->partner) {
            $partnerAccount = $chargingPoint->partner->account()->firstOrCreate([
                'accountable_type' => \App\Models\Partner::class,
                'accountable_id' => $chargingPoint->partner->id,
            ], [
                'balance' => 0,
                'currency' => 'EUR'
            ]);

            \App\Models\FinancialTransaction::create([
                'transaction_id' => $transaction->id,
                'payer_account_id' => $businessProfileAccount->id,
                'payee_account_id' => $partnerAccount->id,
                'amount' => $partnerAmount,
                'type' => 'commission_payout',
                'description' => "Commission partenaire pour réservation #{$reservation->id}",
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $businessProfileAccount->decrement('balance', $partnerAmount);
            $partnerAccount->increment('balance', $partnerAmount);
        }
    }

    /**
     * Récupère le business profile pour un point de charge
     */
    private function getBusinessProfile(ChargingPoint $chargingPoint): ?BusinessProfile
    {
        // Priorité 1: Business profile direct de la borne
        if ($chargingPoint->business_profile_id) {
            return BusinessProfile::find($chargingPoint->business_profile_id);
        }

        // Priorité 2: Business profile de l'intégrateur
        if ($chargingPoint->integrator && $chargingPoint->integrator->businessProfile) {
            return $chargingPoint->integrator->businessProfile;
        }

        // Priorité 3: Business profile du partenaire
        if ($chargingPoint->partner && $chargingPoint->partner->businessProfile) {
            return $chargingPoint->partner->businessProfile;
        }

        // Priorité 4: Business profile du groupe
        if ($chargingPoint->group_id) {
            $group = $chargingPoint->group;
            if ($group) {
                if ($group->integrator_id && $group->integrator && $group->integrator->businessProfile) {
                    return $group->integrator->businessProfile;
                }
                if ($group->partner_id && $group->partner && $group->partner->businessProfile) {
                    return $group->partner->businessProfile;
                }
            }
        }

        return null;
    }

    /**
     * Applique la répartition des revenus à toutes les transactions d'une réservation
     */
    public function applyToReservation(Reservation $reservation): void
    {
        $transactions = $reservation->transactions;
        
        foreach ($transactions as $transaction) {
            $this->applyRevenueDistributionToTransaction($transaction);
        }

        Log::info('Répartition des revenus appliquée à toutes les transactions de la réservation', [
            'reservation_id' => $reservation->id,
            'transactions_count' => $transactions->count()
        ]);
    }

    /**
     * Recalcule et met à jour la répartition des revenus pour une transaction existante
     */
    public function recalculateTransactionDistribution(Transaction $transaction): void
    {
        Log::info('Recalcul de la répartition des revenus', [
            'transaction_id' => $transaction->id
        ]);

        $this->applyRevenueDistributionToTransaction($transaction);
    }

    /**
     * Obtient ou crée un compte système
     */
    private function getOrCreateSystemAccount(string $type, string $description): \App\Models\Account
    {
        try {
            // Créer un utilisateur système temporaire
            $systemUser = \App\Models\User::firstOrCreate(
                ['email' => 'system@evonpower.com'],
                [
                    'name' => 'System User',
                    'password' => bcrypt('system_password'),
                    'email_verified_at' => now(),
                ]
            );

            // Chercher le compte associé à cet utilisateur
            $account = \App\Models\Account::where('accountable_type', \App\Models\User::class)
                ->where('accountable_id', $systemUser->id)
                ->first();

            if (!$account) {
                // Créer le compte associé
                $account = \App\Models\Account::create([
                    'accountable_type' => \App\Models\User::class,
                    'accountable_id' => $systemUser->id,
                    'balance' => 0,
                    'currency' => 'EUR'
                ]);
            }

            return $account;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du compte système', [
                'type' => $type,
                'description' => $description,
                'error' => $e->getMessage()
            ]);
            
            // En cas d'erreur, utiliser le premier compte disponible comme fallback
            $fallbackAccount = \App\Models\Account::where('accountable_type', '!=', 'SystemAccount')->first();
            if (!$fallbackAccount) {
                throw new \Exception('Aucun compte système disponible et impossible de créer un compte de fallback');
            }
            
            return $fallbackAccount;
        }
    }
}
