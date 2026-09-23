<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\Partner;
use App\Models\Integrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class HierarchicalTransactionService
{
    protected $transactionService;
    protected $moneyService;

    public function __construct(TransactionService $transactionService, MoneyService $moneyService)
    {
        $this->transactionService = $transactionService;
        $this->moneyService = $moneyService;
    }

    /**
     * Process hierarchical transaction for a charging session
     */
    public function processHierarchicalTransaction(ChargingSession $session): array
    {
        try {
            DB::beginTransaction();

            // 1️⃣ Récupérer la borne et identifier la hiérarchie
            $hierarchy = $this->identifyHierarchy($session->charging_point_id);
            
            if (!$hierarchy) {
                throw new Exception('Impossible d\'identifier la hiérarchie pour cette borne');
            }

            // 2️⃣ Vérifier les Business Profiles
            $businessProfiles = $this->getBusinessProfiles($hierarchy);
            
            if (!$businessProfiles['operator_profile'] || !$businessProfiles['integrator_profile']) {
                throw new Exception('Profils business manquants pour la transaction hiérarchique');
            }

            // 3️⃣ Calculer les montants selon les profils
            $amounts = $this->calculateHierarchicalAmounts($session, $businessProfiles);

            // 4️⃣ Effectuer les débits avec rollback
            $transactions = $this->performHierarchicalDebits($hierarchy, $amounts, $session);

            // 5️⃣ Créer les transactions de trace
            $traceTransactions = $this->createTraceTransactions($transactions, $session);

            DB::commit();

            Log::info('Transaction hiérarchique réussie', [
                'session_id' => $session->id,
                'charging_point_id' => $session->charging_point_id,
                'operator_amount' => $amounts['operator'],
                'integrator_amount' => $amounts['integrator'],
                'transactions' => $transactions
            ]);

            return [
                'success' => true,
                'transactions' => $transactions,
                'trace_transactions' => $traceTransactions,
                'amounts' => $amounts
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Échec de la transaction hiérarchique', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 1️⃣ Identifier la hiérarchie complète
     */
    protected function identifyHierarchy(int $chargingPointId): ?array
    {
        $chargingPoint = ChargingPoint::with(['group.partner.integrator', 'operator'])
            ->find($chargingPointId);

        if (!$chargingPoint) {
            return null;
        }

        $group = $chargingPoint->group;
        $partner = $group ? $group->partner : null;
        $integrator = $partner ? $partner->integrator : null;
        $operator = $chargingPoint->user;

        return [
            'charging_point' => $chargingPoint,
            'group' => $group,
            'partner' => $partner,
            'integrator' => $integrator,
            'operator' => $operator,
            'admin' => $this->getAdminUser()
        ];
    }

    /**
     * 2️⃣ Récupérer les Business Profiles
     *
     * a) Profil créé par l'Intégrateur et appliqué à l'Opérateur → débit sur Wallet Opérateur
     * b) Profil créé par l'Admin et appliqué à l'Intégrateur → débit sur Wallet Intégrateur
     */
    protected function getBusinessProfiles(array $hierarchy): array
    {
        $operatorProfile = null;
        $integratorProfile = null;

        // a) Profil créé par l'Intégrateur pour l'Opérateur
        if ($hierarchy['integrator'] && $hierarchy['operator']) {
            $operatorProfile = BusinessProfile::where('created_by_id', $hierarchy['integrator']->user_id)
                ->where('created_by_role', 'integrator')
                ->where('is_active', true)
                ->first();

            if (!$operatorProfile) {
                Log::warning('Operator profile not found', [
                    'integrator_id' => $hierarchy['integrator']->id,
                    'operator_id' => $hierarchy['operator']->id
                ]);
            }
        }

        // b) Profil créé par l'Admin pour l'Intégrateur
        if ($hierarchy['admin'] && $hierarchy['integrator']) {
            $integratorProfile = BusinessProfile::where('created_by_id', $hierarchy['admin']->id)
                ->where('created_by_role', 'admin')
                ->where('is_active', true)
                ->first();

            if (!$integratorProfile) {
                Log::warning('Integrator profile not found', [
                    'admin_id' => $hierarchy['admin']->id,
                    'integrator_id' => $hierarchy['integrator']->id
                ]);
            }
        }

        return [
            'operator_profile' => $operatorProfile,
            'integrator_profile' => $integratorProfile
        ];
    }

    /**
     * 3️⃣ Calculer les montants selon les profils
     */
    protected function calculateHierarchicalAmounts(ChargingSession $session, array $businessProfiles): array
    {
        $energyDelivered = $session->energy_delivered ?? 0;
        $duration = $session->duration ?? 0;

        // Calcul pour l'opérateur (profil intégrateur)
        $operatorAmount = $this->calculateAmountFromProfile(
            $businessProfiles['operator_profile'],
            $energyDelivered,
            $duration
        );

        // Calcul pour l'intégrateur (profil admin)
        $integratorAmount = $this->calculateAmountFromProfile(
            $businessProfiles['integrator_profile'],
            $energyDelivered,
            $duration
        );

        return [
            'operator' => $operatorAmount,
            'integrator' => $integratorAmount,
            'energy_delivered' => $energyDelivered,
            'duration' => $duration
        ];
    }

    /**
     * Calculer le montant selon un profil business
     */
    protected function calculateAmountFromProfile(?BusinessProfile $profile, float $energy, int $duration): float
    {
        if (!$profile) {
            return 0.0;
        }

        $amount = 0.0;

        // Prix par kWh
        if ($profile->price_per_kwh) {
            $amount += $energy * $profile->price_per_kwh;
        }

        // Prix par heure
        if ($profile->price_per_hour) {
            $amount += ($duration / 3600) * $profile->price_per_hour; // Convertir secondes en heures
        }

        // Prix fixe
        if ($profile->fixed_price) {
            $amount += $profile->fixed_price;
        }

        return $amount;
    }

    /**
     * 4️⃣ Effectuer les débits avec rollback
     *
     * Transaction double automatique :
     * - Opérateur débité → selon profil Intégrateur
     * - Intégrateur débité → selon profil Admin
     * - Part Admin se déduit de la part de Integrator
     */
    protected function performHierarchicalDebits(array $hierarchy, array $amounts, ChargingSession $session): array
    {
        $transactions = [];
        $debitedTransactions = [];

        try {
            // 1️⃣ Débit sur le wallet de l'opérateur (selon profil Intégrateur)
            if ($hierarchy['operator'] && $amounts['operator'] > 0) {
                $operatorWallet = $this->getOrCreateWallet($hierarchy['operator']);

                // Vérifier le solde
                if (!$operatorWallet->canDebit($amounts['operator'])) {
                    throw new Exception(
                        "Solde insuffisant sur le wallet de l'opérateur. " .
                        "Requis: {$amounts['operator']}, Disponible: {$operatorWallet->balance}"
                    );
                }

                $operatorTransaction = $operatorWallet->debit(
                    $amounts['operator'],
                    "Débit hiérarchique - Session {$session->id} - Profil Intégrateur",
                    [
                        'session_id' => $session->id,
                        'charging_point_id' => $session->charging_point_id,
                        'transaction_type' => 'hierarchical_operator_debit',
                        'hierarchy_level' => 'operator'
                    ]
                );

                $transactions['operator'] = $operatorTransaction;
                $debitedTransactions[] = $operatorTransaction;

                Log::info('Opérateur débité', [
                    'operator_id' => $hierarchy['operator']->id,
                    'amount' => $amounts['operator'],
                    'session_id' => $session->id
                ]);
            }

            // 2️⃣ Débit sur le wallet de l'intégrateur (selon profil Admin)
            if ($hierarchy['integrator'] && $amounts['integrator'] > 0) {
                $integratorUser = $hierarchy['integrator']->user;
                $integratorWallet = $this->getOrCreateWallet($integratorUser);

                // Vérifier le solde
                if (!$integratorWallet->canDebit($amounts['integrator'])) {
                    // Rollback du débit opérateur si intégrateur insuffisant
                    $this->rollbackDebits($debitedTransactions);

                    throw new Exception(
                        "Solde insuffisant sur le wallet de l'intégrateur. " .
                        "Requis: {$amounts['integrator']}, Disponible: {$integratorWallet->balance}"
                    );
                }

                $integratorTransaction = $integratorWallet->debit(
                    $amounts['integrator'],
                    "Débit hiérarchique - Session {$session->id} - Profil Admin",
                    [
                        'session_id' => $session->id,
                        'charging_point_id' => $session->charging_point_id,
                        'transaction_type' => 'hierarchical_integrator_debit',
                        'hierarchy_level' => 'integrator'
                    ]
                );

                $transactions['integrator'] = $integratorTransaction;
                $debitedTransactions[] = $integratorTransaction;

                Log::info('Intégrateur débité', [
                    'integrator_id' => $hierarchy['integrator']->id,
                    'amount' => $amounts['integrator'],
                    'session_id' => $session->id
                ]);
            }

            return $transactions;

        } catch (Exception $e) {
            // Rollback de tous les débits en cas d'erreur
            $this->rollbackDebits($debitedTransactions);
            throw $e;
        }
    }

    /**
     * 5️⃣ Créer les transactions de trace
     *
     * Génère 2 transactions distinctes avec trace (reference_id, charging_point_id, session_id)
     */
    protected function createTraceTransactions(array $transactions, ChargingSession $session): array
    {
        $traceTransactions = [];

        foreach ($transactions as $role => $transaction) {
            try {
                $traceTransaction = Transaction::create([
                    'reference_id' => uniqid('REF_'),
                    'charging_point_id' => $session->charging_point_id,
                    'session_id' => $session->id,
                    'user_id' => $transaction->wallet->owner_id,
                    'user_type' => $transaction->wallet->owner_type,
                    'amount' => $transaction->amount,
                    'currency' => 'EUR',
                    'type' => 'hierarchical_debit',
                    'status' => 'completed',
                    'description' => "Transaction hiérarchique - {$role} - Session {$session->id}",
                    'metadata' => [
                        'role' => $role,
                        'wallet_transaction_id' => $transaction->id,
                        'charging_session_id' => $session->id,
                        'energy_delivered' => $session->energy_delivered,
                        'duration' => $session->duration,
                        'hierarchy_level' => $role
                    ]
                ]);

                $traceTransactions[$role] = $traceTransaction;

                Log::info('Transaction de trace créée', [
                    'role' => $role,
                    'reference_id' => $traceTransaction->reference_id,
                    'session_id' => $session->id
                ]);

            } catch (Exception $e) {
                Log::error('Erreur lors de la création de la transaction de trace', [
                    'role' => $role,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $traceTransactions;
    }

    /**
     * Récupérer ou créer un wallet
     */
    protected function getOrCreateWallet($owner): Wallet
    {
        $wallet = Wallet::where('owner_type', get_class($owner))
            ->where('owner_id', $owner->id)
            ->first();

        if (!$wallet) {
            $wallet = Wallet::create([
                'owner_type' => get_class($owner),
                'owner_id' => $owner->id,
                'balance' => 0.00,
                'currency' => 'EUR',
                'is_active' => true
            ]);
        }

        return $wallet;
    }

    /**
     * Rollback d'une transaction
     */
    protected function rollbackTransaction(WalletTransaction $transaction): void
    {
        $wallet = $transaction->wallet;
        $wallet->credit(
            $transaction->amount,
            "Rollback - Transaction {$transaction->id}",
            [
                'rollback' => true,
                'original_transaction_id' => $transaction->id
            ]
        );

        Log::info('Transaction rollback effectué', [
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
            'wallet_id' => $wallet->id
        ]);
    }

    /**
     * Rollback de plusieurs transactions
     */
    protected function rollbackDebits(array $transactions): void
    {
        foreach ($transactions as $transaction) {
            try {
                $this->rollbackTransaction($transaction);
            } catch (Exception $e) {
                Log::error('Erreur lors du rollback', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Récupérer l'utilisateur admin
     */
    protected function getAdminUser(): ?User
    {
        return User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->first();
    }

    /**
     * Vérifier la cohérence des soldes après transaction
     */
    public function verifyBalances(array $hierarchy): array
    {
        $balances = [];

        if ($hierarchy['operator']) {
            $operatorWallet = $this->getOrCreateWallet($hierarchy['operator']);
            $balances['operator'] = [
                'balance' => $operatorWallet->balance,
                'formatted' => $this->moneyService->format($operatorWallet->balance)
            ];
        }

        if ($hierarchy['integrator']) {
            $integratorWallet = $this->getOrCreateWallet($hierarchy['integrator']->user);
            $balances['integrator'] = [
                'balance' => $integratorWallet->balance,
                'formatted' => $this->moneyService->format($integratorWallet->balance)
            ];
        }

        return $balances;
    }

    /**
     * Obtenir l'historique des transactions hiérarchiques
     */
    public function getHierarchicalTransactionHistory(int $chargingPointId, int $limit = 50): array
    {
        return Transaction::where('charging_point_id', $chargingPointId)
            ->where('type', 'hierarchical_debit')
            ->with(['user', 'chargingPoint'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}