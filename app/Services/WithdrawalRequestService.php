<?php

namespace App\Services;

use App\Models\WithdrawalRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Jobs\ProcessWithdrawal;
use App\Jobs\ValidateWithdrawalOffline;
use App\Enums\WithdrawalStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service de Demandes de Retrait
 * 
 * Fonctionnalités:
 * - Soumission de demandes de retrait
 * - Validation hors-ligne
 * - Workflow d'approbation multi-niveaux
 * - Validation des coordonnées bancaires
 * - Gestion des limites et frais
 * - Synchronisation offline via queue
 */
class WithdrawalRequestService
{
    /**
     * Montant minimum de retrait
     */
    public const MIN_WITHDRAWAL_AMOUNT = 10.00;

    /**
     * Montant maximum de retrait
     */
    public const MAX_WITHDRAWAL_AMOUNT = 10000.00;

    /**
     * Frais de retrait (pourcentage)
     */
    public const WITHDRAWAL_FEE_PERCENTAGE = 1.0;

    /**
     * Nombre maximum de tentatives de traitement
     */
    public const MAX_RETRY_ATTEMPTS = 3;

    /**
     * Délai entre les tentatives (minutes)
     */
    public const RETRY_DELAY_MINUTES = 5;

    public function __construct(
        protected MoneyService $moneyService
    ) {}

    /**
     * Créer une demande de retrait
     */
    public function createWithdrawalRequest(
        User $user,
        float $amount,
        array $bankDetails,
        ?string $notes = null
    ): WithdrawalRequest {
        return DB::transaction(function () use ($user, $amount, $bankDetails, $notes) {
            // Valider les détails bancaires
            $this->validateBankDetails($bankDetails);

            // Vérifier les limites
            $this->validateAmountLimits($amount, $user);

            // Obtenir le wallet
            $wallet = $user->getOrCreateWallet();

            // Vérifier le solde
            if (!$wallet->hasSufficientBalance($amount)) {
                throw new \Exception('Solde insuffisant pour ce retrait');
            }

            // Calculer les frais
            $fee = $this->calculateFee($amount);
            $netAmount = $amount - $fee;

            // Créer la demande
            $withdrawal = WithdrawalRequest::create([
                'owner_type' => User::class,
                'owner_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'currency' => $wallet->currency ?? 'EUR',
                'fee' => $fee,
                'net_amount' => $netAmount,
                'status' => WithdrawalStatus::PENDING,
                'withdrawal_method' => $bankDetails['method'] ?? 'bank_transfer',
                'bank_name' => $bankDetails['bank_name'] ?? null,
                'bank_account' => $bankDetails['bank_account'] ?? null,
                'bank_code' => $bankDetails['bank_code'] ?? null,
                'card_last4' => $bankDetails['card_last4'] ?? null,
                'card_brand' => $bankDetails['card_brand'] ?? null,
                'notes' => $notes,
                'metadata' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'offline_validation_pending' => true,
                    'created_offline' => false,
                ],
            ]);

            // Réserver le montant (provisionner)
            $this->reserveAmount($wallet, $amount, $withdrawal);

            // Programmer la validation offline
            $this->scheduleOfflineValidation($withdrawal);

            Log::info('WithdrawalRequestService: Demande créée', [
                'withdrawal_id' => $withdrawal->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Valider les coordonnées bancaires
     */
    public function validateBankDetails(array $bankDetails): bool
    {
        $method = $bankDetails['method'] ?? 'bank_transfer';

        return match ($method) {
            'bank_transfer' => $this->validateBankTransferDetails($bankDetails),
            'card' => $this->validateCardDetails($bankDetails),
            default => throw new \Exception('Méthode de retrait non supportée'),
        };
    }

    /**
     * Valider les détails de virement bancaire
     */
    protected function validateBankTransferDetails(array $details): bool
    {
        // Validation IBAN
        if (isset($details['iban'])) {
            if (!$this->validateIBAN($details['iban'])) {
                throw new \Exception('IBAN invalide');
            }
        } elseif (isset($details['bank_account'])) {
            // Validation basique du numéro de compte
            if (strlen($details['bank_account']) < 8) {
                throw new \Exception('Numéro de compte bancaire invalide');
            }
        } else {
            throw new \Exception('Coordonnées bancaires manquantes');
        }

        // Validation du nom de la banque
        if (empty($details['bank_name'])) {
            throw new \Exception('Nom de la banque requis');
        }

        return true;
    }

    /**
     * Valider les détails de carte
     */
    protected function validateCardDetails(array $details): bool
    {
        if (empty($details['card_last4']) || strlen($details['card_last4']) !== 4) {
            throw new \Exception('Les 4 derniers chiffres de la carte sont requis');
        }

        if (empty($details['card_brand'])) {
            throw new \Exception('Type de carte requis');
        }

        return true;
    }

    /**
     * Valider un IBAN
     */
    protected function validateIBAN(string $iban): bool
    {
        // Supprimer les espaces et mettre en majuscules
        $iban = strtoupper(str_replace(' ', '', $iban));

        // Vérifier la structure de base
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4,30}$/', $iban)) {
            return false;
        }

        // Vérifier la somme de contrôle
        $numeric = '';
        for ($i = 0; $i < strlen($iban); $i++) {
            $char = ord($iban[$i]);
            if ($char >= 65 && $char <= 90) {
                $numeric .= $char - 55;
            } else {
                $numeric .= $iban[$i];
            }
        }

        // Vérifier le reste modulo 97
        return (bool) preg_match('/^[0-9]+$/', $numeric) && bcmod($numeric, '97') == 1;
    }

    /**
     * Valider les limites de montant
     */
    protected function validateAmountLimits(float $amount, User $user): void
    {
        if ($amount < self::MIN_WITHDRAWAL_AMOUNT) {
            throw new \Exception(
                'Le montant minimum de retrait est de ' . 
                number_format(self::MIN_WITHDRAWAL_AMOUNT, 2) . ' EUR'
            );
        }

        if ($amount > self::MAX_WITHDRAWAL_AMOUNT) {
            throw new \Exception(
                'Le montant maximum de retrait est de ' . 
                number_format(self::MAX_WITHDRAWAL_AMOUNT, 2) . ' EUR'
            );
        }

        // Vérifier la limite quotidienne
        $todayWithdrawals = $this->getTodayWithdrawalTotal($user);
        $dailyLimit = $this->getDailyLimit($user);

        if ($todayWithdrawals + $amount > $dailyLimit) {
            throw new \Exception(
                'Limite quotidienne de retrait atteinte. Maximum: ' . 
                number_format($dailyLimit - $todayWithdrawals, 2) . ' EUR'
            );
        }
    }

    /**
     * Calculer les frais de retrait
     */
    public function calculateFee(float $amount): float
    {
        return round($amount * (self::WITHDRAWAL_FEE_PERCENTAGE / 100), 2);
    }

    /**
     * Réserver le montant (provisionner)
     */
    protected function reserveAmount(Wallet $wallet, float $amount, WithdrawalRequest $withdrawal): void
    {
        // Créer une transaction de réserve
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => $amount,
            'balance_before' => $wallet->balance,
            'balance_after' => $wallet->balance - $amount,
            'description' => "Réserve retrait - {$withdrawal->id}",
            'status' => 'pending',
            'metadata' => [
                'withdrawal_id' => $withdrawal->id,
                'type' => 'withdrawal_reserve',
            ],
        ]);

        // Débiter le wallet
        $wallet->debit(
            $amount,
            "Demande de retrait #{$withdrawal->id}",
            [
                'withdrawal_id' => $withdrawal->id,
                'type' => 'withdrawal',
            ]
        );
    }

    /**
     * Programmer la validation offline
     */
    protected function scheduleOfflineValidation(WithdrawalRequest $withdrawal): void
    {
        // Dispatch job pour validation asynchrone
        ValidateWithdrawalOffline::dispatch($withdrawal)
            ->delay(now()->addSeconds(10));
    }

    /**
     * Valider une demande en mode offline
     */
    public function validateOffline(WithdrawalRequest $withdrawal): array
    {
        try {
            // Vérifier les coordonnées bancaires
            $bankValidation = $this->validateBankDetails([
                'method' => $withdrawal->withdrawal_method,
                'bank_name' => $withdrawal->bank_name,
                'bank_account' => $withdrawal->bank_account,
                'bank_code' => $withdrawal->bank_code,
                'card_last4' => $withdrawal->card_last4,
                'card_brand' => $withdrawal->card_brand,
            ]);

            // Mettre à jour le statut de validation
            $withdrawal->update([
                'metadata' => array_merge($withdrawal->metadata ?? [], [
                    'offline_validation_completed' => true,
                    'offline_validation_at' => now()->toISOString(),
                    'offline_validation_result' => 'valid',
                ]),
            ]);

            return [
                'success' => true,
                'valid' => true,
                'message' => 'Validation offline réussie',
            ];

        } catch (\Exception $e) {
            Log::error('WithdrawalRequestService: Validation offline échouée', [
                'withdrawal_id' => $withdrawal->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'valid' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Approuver une demande
     */
    public function approve(WithdrawalRequest $withdrawal, User $approver, ?string $notes = null): bool
    {
        if (!$withdrawal->canApprove()) {
            throw new \Exception('Cette demande ne peut pas être approuvée');
        }

        $withdrawal->approve($approver, $notes);

        // Programmer le traitement
        ProcessWithdrawal::dispatch($withdrawal)
            ->delay(now()->addMinutes(self::RETRY_DELAY_MINUTES));

        Log::info('WithdrawalRequestService: Demande approuvée', [
            'withdrawal_id' => $withdrawal->id,
            'approved_by' => $approver->id,
        ]);

        return true;
    }

    /**
     * Rejeter une demande
     */
    public function reject(WithdrawalRequest $withdrawal, User $rejecter, string $reason): bool
    {
        if (!$withdrawal->canReject()) {
            throw new \Exception('Cette demande ne peut pas être rejetée');
        }

        return DB::transaction(function () use ($withdrawal, $rejecter, $reason) {
            // Annuler la réservation
            $this->cancelReservation($withdrawal);

            $withdrawal->reject($rejecter, $reason);

            Log::info('WithdrawalRequestService: Demande rejetée', [
                'withdrawal_id' => $withdrawal->id,
                'rejected_by' => $rejecter->id,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Traiter une demande approuvée
     */
    public function process(WithdrawalRequest $withdrawal): array
    {
        if (!$withdrawal->canProcess()) {
            return ['success' => false, 'error' => 'Demande non accessible au traitement'];
        }

        return DB::transaction(function () use ($withdrawal) {
            $withdrawal->markAsProcessing(auth()->user());

            try {
                // Effectuer le transfert
                $result = $this->executeWithdrawal($withdrawal);

                if ($result['success']) {
                    $withdrawal->markAsCompleted(
                        auth()->user(),
                        $result['external_id'] ?? null,
                        $result['notes'] ?? null
                    );

                    Log::info('WithdrawalRequestService: Retrait traité', [
                        'withdrawal_id' => $withdrawal->id,
                        'external_id' => $result['external_id'] ?? null,
                    ]);

                    return [
                        'success' => true,
                        'external_id' => $result['external_id'] ?? null,
                    ];
                } else {
                    // Réessayer plus tard
                    $this->scheduleRetry($withdrawal, $result['error'] ?? 'Erreur inconnue');

                    return [
                        'success' => false,
                        'error' => $result['error'],
                        'retry_scheduled' => true,
                    ];
                }

            } catch (\Exception $e) {
                $this->scheduleRetry($withdrawal, $e->getMessage());

                throw $e;
            }
        });
    }

    /**
     * Exécuter le retrait (intégration externe)
     */
    protected function executeWithdrawal(WithdrawalRequest $withdrawal): array
    {
        // Simulation - en production, intégration avec plateforme de paiement
        // Pour Stripe, PayPal, etc.

        return [
            'success' => true,
            'external_id' => 'EXT_' . Str::random(16),
            'notes' => 'Retrait traité avec succès',
        ];
    }

    /**
     * Planifier une nouvelle tentative
     */
    protected function scheduleRetry(WithdrawalRequest $withdrawal, string $error): void
    {
        $attempts = ($withdrawal->metadata['retry_attempts'] ?? 0) + 1;

        if ($attempts >= self::MAX_RETRY_ATTEMPTS) {
            // Marquer comme échoué après trop de tentatives
            $withdrawal->markAsFailed(auth()->user(), "Échec après {$attempts} tentatives: {$error}");

            // Rembourser le montant
            $this->refundAmount($withdrawal);

            Log::error('WithdrawalRequestService: Trop de tentatives', [
                'withdrawal_id' => $withdrawal->id,
                'attempts' => $attempts,
            ]);
        } else {
            // Programmer une nouvelle tentative
            $withdrawal->update([
                'metadata' => array_merge($withdrawal->metadata ?? [], [
                    'retry_attempts' => $attempts,
                    'last_retry_error' => $error,
                    'next_retry_at' => now()->addMinutes(self::RETRY_DELAY_MINUTES * $attempts)->toISOString(),
                ]),
            ]);

            ProcessWithdrawal::dispatch($withdrawal)
                ->delay(now()->addMinutes(self::RETRY_DELAY_MINUTES * $attempts));
        }
    }

    /**
     * Annuler la réservation
     */
    protected function cancelReservation(WithdrawalRequest $withdrawal): void
    {
        $wallet = $withdrawal->wallet;
        
        if ($wallet) {
            // Créditer le montant réservé
            $wallet->credit(
                $withdrawal->amount,
                "Annulation retrait #{$withdrawal->id}",
                [
                    'withdrawal_id' => $withdrawal->id,
                    'type' => 'withdrawal_refund',
                ]
            );
        }

        // Mettre à jour les transactions
        WalletTransaction::where('metadata->withdrawal_id', $withdrawal->id)
            ->where('metadata->type', 'withdrawal_reserve')
            ->update([
                'status' => 'cancelled',
            ]);
    }

    /**
     * Rembourser le montant
     */
    protected function refundAmount(WithdrawalRequest $withdrawal): void
    {
        $wallet = $withdrawal->wallet;
        
        if ($wallet) {
            $wallet->credit(
                $withdrawal->amount,
                "Remboursement retrait #{$withdrawal->id}",
                [
                    'withdrawal_id' => $withdrawal->id,
                    'type' => 'withdrawal_refund',
                ]
            );
        }
    }

    /**
     * Obtenir le total des retraits du jour
     */
    protected function getTodayWithdrawalTotal(User $user): float
    {
        return WithdrawalRequest::where('owner_id', $user->id)
            ->where('owner_type', User::class)
            ->whereDate('created_at', now()->toDateString())
            ->whereIn('status', ['pending', 'approved', 'processing'])
            ->sum('amount');
    }

    /**
     * Obtenir la limite quotidienne
     */
    protected function getDailyLimit(User $user): float
    {
        // Limite par défaut, peut être personnalisée par rôle
        return match ($user->roles()->first()?->name) {
            'admin' => 50000.00,
            'integrator' => 20000.00,
            'partner' => 10000.00,
            default => 5000.00,
        };
    }

    /**
     * Obtenir l'historique des retraits d'un utilisateur
     */
    public function getUserHistory(User $user, int $limit = 20): array
    {
        $withdrawals = WithdrawalRequest::where('owner_id', $user->id)
            ->where('owner_type', User::class)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $withdrawals->map(function ($w) {
            return [
                'id' => $w->id,
                'amount' => $w->amount,
                'fee' => $w->fee,
                'net_amount' => $w->net_amount,
                'currency' => $w->currency,
                'status' => $w->status,
                'status_label' => $w->statusEnum->getLabel(),
                'bank_name' => $w->bank_name,
                'masked_account' => $w->masked_bank_account,
                'created_at' => $w->created_at,
                'processed_at' => $w->processed_at,
            ];
        })->toArray();
    }
}
