<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Events\ReservationApproved;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\ReservationPaymentApprovalService;
use App\Services\ReservationTransactionService;
use App\Services\TransactionFinalizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CreditPaymentService
{
    protected $reservationTransactionService;
    protected ReservationParticipantService $participantService;
    protected TransactionFinalizationService $finalizationService;
    protected ReservationPaymentApprovalService $approvalService;

    public function __construct(
        ReservationTransactionService $reservationTransactionService,
        ReservationParticipantService $participantService,
        TransactionFinalizationService $finalizationService,
        ReservationPaymentApprovalService $approvalService
    )
    {
        $this->reservationTransactionService = $reservationTransactionService;
        $this->participantService = $participantService;
        $this->approvalService = $approvalService;
        $this->finalizationService = $finalizationService;
    }

    /**
     * Traiter un paiement prépayé
     */
    public function processPrepaidPayment(Reservation $reservation): array
    {
        try {
            // Idempotence : déjà payé (tous modes) → succès sans re-débit
            if ($reservation->isPaid()) {
                $wallet = $reservation->user?->getOrCreateWallet();
            $remaining = $wallet ? (float) $wallet->fresh()->balance : 0;
            return [
                'success' => true,
                'message' => 'Réservation déjà payée',
                'reservation_id' => $reservation->id,
                'amount_paid' => (float) ($reservation->prepaid_amount ?? $reservation->estimated_cost ?? 0),
                'remaining_balance' => $remaining,
                'formatted_balance' => $wallet ? $wallet->getFormattedBalance() : \App\Services\MoneyService::format($remaining, 'EUR'),
                'already_paid' => true,
            ];
            }

            DB::beginTransaction();

            $user = $reservation->user;
            if (!$user) {
                throw new Exception('Réservation sans utilisateur associé (user_id requis pour paiement par crédit)');
            }

            $wallet = $user->getOrCreateWallet();
            $wallet->refresh();

            // Montant TTC à débiter (priorité: transaction.price_total, puis reservation.estimated_cost)
            $mainTx = \App\Models\Transaction::where('reservation_id', $reservation->id)->first();
            $amountToDebit = (float) ($mainTx->price_total ?? $mainTx->amount ?? $reservation->estimated_cost ?? $reservation->amount ?? 0);

            if ($amountToDebit <= 0) {
                throw new Exception("Montant invalide pour la réservation #{$reservation->id}");
            }

            // Synchroniser les participants et débiter chaque part
            $this->participantService->syncParticipants($reservation, null, $amountToDebit);
            $chargeResult = $this->participantService->applyWalletCharges($reservation, 'prepaid');

            // Mettre à jour la réservation : confirmée, approuvée automatiquement par le propriétaire de la borne
            $reservation->update([
                'status' => ReservationStatus::CONFIRMED,
                'confirmed_at' => now(),
                'approved_at' => now(),
                // Set to the charging-point operator (owner), not the paying client
                'approved_by' => $this->approvalService->resolveChargingPointOperatorId($reservation),
                'payment_status' => 'PAID',
                'payment_method' => 'prepaid_credit',
                'payment_mode' => 'prepaid',
                'prepaid_amount' => $amountToDebit,
                'wallet_validation_passed' => true,
                'wallet_validated_at' => now()
            ]);

            // IMMÉDIAT : Forcer transaction + réservation en base (avant processReservationTransaction)
            DB::table('transactions')
                ->where('reservation_id', $reservation->id)
                ->update(['status' => 'completed']);
            Log::info('Transaction mise à completed immédiatement après paiement prépayé', [
                'reservation_id' => $reservation->id,
            ]);

            // Traiter la transaction de réservation pour la répartition des parts (AVANT mise à jour status)
            // processReservationTransaction peut écraser le statut → on force completed APRÈS
            try {
                $transactionResult = $this->reservationTransactionService->processReservationTransaction($reservation);
                if (!($transactionResult['success'] ?? true)) {
                    Log::warning('processReservationTransaction a échoué (non bloquant)', [
                        'reservation_id' => $reservation->id,
                        'error' => $transactionResult['error'] ?? 'unknown'
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('processReservationTransaction a échoué - réservation et paiement sauvegardés', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Ne pas relancer : la réservation et le débit wallet sont prioritaires
            }

            // Forcer status=completed APRÈS processReservationTransaction (évite écrasement par "confirmed")
            $reservation->unsetRelation('transaction');
            $mainTransaction = \App\Models\Transaction::where('reservation_id', $reservation->id)->first();
            if ($mainTransaction && strtolower((string) $mainTransaction->status) !== 'completed') {
                $mainTransaction->status = 'completed';
                $mainTransaction->saveQuietly();
                Log::info('Transaction forcée à completed après paiement prépayé', [
                    'transaction_id' => $mainTransaction->id,
                    'reservation_id' => $reservation->id,
                ]);
            }
            
            // Finaliser la transaction via TransactionFinalizationService
            try {
                $mainTransaction = \App\Models\Transaction::where('reservation_id', $reservation->id)->first();
                if ($mainTransaction) {
                    $this->finalizationService->finalizeTransaction($mainTransaction, ['source' => 'CreditPaymentService_prepaid']);
                }
            } catch (\Throwable $e) {
                Log::warning('TransactionFinalizationService a échoué - transaction sauvegardée', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage()
                ]);
            }
            // Fallback : mise à jour directe en base (insensible à la casse)
            $updated = DB::table('transactions')
                ->where('reservation_id', $reservation->id)
                ->whereRaw('LOWER(COALESCE(status, \'\')) != ?', ['completed'])
                ->update(['status' => 'completed']);
            if ($updated > 0 && !$mainTransaction) {
                Log::info('Transaction mise à completed via fallback DB', ['reservation_id' => $reservation->id]);
            }

            DB::commit();

            // Invalider le cache wallet pour que getWalletBalance() retourne le solde à jour
            $user->unsetRelation('wallet');

            // Émettre l'événement d'approbation automatique (déclenche auto Remote Start si configuré)
            event(new ReservationApproved($reservation, (string) $user->id, 'prepaid_balance'));

            Log::info('Paiement prépayé traité avec succès', [
                'reservation_id' => $reservation->id,
                'user_id' => $user->id,
                'amount' => $amountToDebit,
                'wallet_transactions' => $chargeResult['results'] ?? []
            ]);

            $remaining = $wallet->fresh()->balance;
            return [
                'success' => true,
                'message' => 'Paiement prépayé effectué avec succès',
                'reservation_id' => $reservation->id,
                'amount_paid' => $amountToDebit,
                'remaining_balance' => $remaining,
                'formatted_balance' => $wallet->getFormattedBalance(),
                'transaction_ids' => collect($chargeResult['results'] ?? [])
                    ->pluck('wallet_transaction_id')
                    ->filter()
                    ->values()
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur paiement prépayé', [
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Traiter un paiement postpayé
     */
    public function processPostpaidPayment(Reservation $reservation): array
    {
        try {
            DB::beginTransaction();

            $user = $reservation->user;
            if (!$user) {
                throw new Exception('Réservation sans utilisateur associé (user_id requis pour paiement postpayé)');
            }
            $wallet = $user->getOrCreateWallet();
            $wallet->refresh();
            $minThreshold = config('charging.postpaid_min_threshold', 10.00);

            // Vérifier le seuil minimum
            if (!$wallet->hasSufficientBalance($minThreshold)) {
                throw new Exception("Solde insuffisant pour le paiement postpayé. Requis: {$minThreshold}, Disponible: {$wallet->balance}");
            }

            // Mettre à jour la réservation : confirmée et approuvée automatiquement par le propriétaire de la borne
            $reservation->update([
                'status' => ReservationStatus::CONFIRMED,
                'confirmed_at' => now(),
                'approved_at' => now(),
                // Set to the charging-point operator (owner), not the paying client
                'approved_by' => $this->approvalService->resolveChargingPointOperatorId($reservation),
                'payment_status' => 'PENDING', // Débit à la fin de la session de recharge
                'payment_method' => 'postpaid_credit',
                'payment_mode' => 'postpaid',
                'min_threshold' => $minThreshold,
                'wallet_validation_passed' => true,
                'wallet_validated_at' => now()
            ]);

            DB::commit();

            $user->unsetRelation('wallet');

            // Émettre l'événement d'approbation automatique pour déclencher AutoRemoteStart
            event(new ReservationApproved($reservation, (string) $user->id, 'postpaid_balance'));

            Log::info('Paiement postpayé configuré - réservation confirmée et approuvée', [
                'reservation_id' => $reservation->id,
                'user_id' => $user->id,
                'min_threshold' => $minThreshold,
                'wallet_balance' => $wallet->balance
            ]);

            return [
                'success' => true,
                'message' => 'Réservation postpayée confirmée',
                'reservation_id' => $reservation->id,
                'min_threshold' => $minThreshold,
                'current_balance' => $wallet->balance,
                'remaining_balance' => $wallet->fresh()->balance
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur paiement postpayé', [
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Finaliser un paiement postpayé après la session de recharge
     */
    public function finalizePostpaidPayment(Reservation $reservation, float $actualCost): array
    {
        try {
            DB::beginTransaction();

            if (!$reservation->isPostpaid()) {
                throw new Exception('Cette réservation n\'est pas en mode postpayé');
            }

            $user = $reservation->user;
            if (!$user) {
                throw new Exception('Réservation sans utilisateur associé (user_id requis pour finalisation postpayée)');
            }
            $wallet = $user->getOrCreateWallet();
            $wallet->refresh();

            // Synchroniser les participants et débiter chaque part
            $this->participantService->syncParticipants($reservation, null, $actualCost);
            $chargeResult = $this->participantService->applyWalletCharges($reservation, 'postpaid');

            // Mettre à jour la réservation (payment_status = PAID déclenche ReservationObserver → sync transaction)
            $reservation->update([
                'actual_cost' => $actualCost,
                'payment_status' => 'PAID',
                'status' => 'completed'
            ]);

            // Synchronisation explicite du statut transaction (sécurité si observer échoue)
            $mainTransaction = $reservation->transaction;
            if ($mainTransaction && in_array($mainTransaction->status, ['pending'])) {
                app(TransactionStatusSyncService::class)->syncTransactionStatusIfPaid($mainTransaction);
            }

            // Traiter la transaction de réservation pour la répartition des parts
            $transactionResult = $this->reservationTransactionService->processReservationTransaction($reservation);
            
            // Finaliser la transaction via TransactionFinalizationService
            try {
                $mainTransaction = $reservation->fresh()->transaction;
                if ($mainTransaction) {
                    $this->finalizationService->finalizeTransaction($mainTransaction, ['source' => 'CreditPaymentService_postpaid']);
                }
            } catch (\Throwable $e) {
                Log::warning('TransactionFinalizationService a échoué - transaction sauvegardée', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            $user->unsetRelation('wallet');

            Log::info('Paiement postpayé finalisé', [
                'reservation_id' => $reservation->id,
                'user_id' => $user->id,
                'actual_cost' => $actualCost,
                'wallet_transactions' => $chargeResult['results'] ?? []
            ]);

            return [
                'success' => true,
                'message' => 'Paiement postpayé finalisé avec succès',
                'reservation_id' => $reservation->id,
                'amount_paid' => $actualCost,
                'remaining_balance' => $wallet->fresh()->balance,
                'transaction_ids' => collect($chargeResult['results'] ?? [])
                    ->pluck('wallet_transaction_id')
                    ->filter()
                    ->values()
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur finalisation paiement postpayé', [
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'actual_cost' => $actualCost,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Traiter un remboursement pour une réservation prépayée
     */
    public function processPrepaidRefund(Reservation $reservation): array
    {
        try {
            DB::beginTransaction();

            if (!$reservation->isPrepaid()) {
                throw new Exception('Cette réservation n\'est pas en mode prépayé');
            }

            $refundAmount = $reservation->calculateRefundAmount();
            
            if ($refundAmount <= 0) {
                return [
                    'success' => true,
                    'message' => 'Aucun remboursement nécessaire',
                    'refund_amount' => 0
                ];
            }

            $user = $reservation->user;
            $wallet = $user->getOrCreateWallet();

            // Ajuster les parts des participants selon le coût réel (génère les crédits nécessaires)
            $this->participantService->recalculateShares($reservation, (float) ($reservation->actual_cost ?? 0));

            // Mettre à jour la réservation
            $reservation->update([
                'refund_amount' => $refundAmount,
                'payment_status' => 'refunded'
            ]);

            DB::commit();

            $user->unsetRelation('wallet');

            Log::info('Remboursement prépayé traité', [
                'reservation_id' => $reservation->id,
                'user_id' => $user->id,
                'refund_amount' => $refundAmount
            ]);

            return [
                'success' => true,
                'message' => 'Remboursement effectué avec succès',
                'reservation_id' => $reservation->id,
                'refund_amount' => $refundAmount,
                'new_balance' => $wallet->fresh()->balance
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur remboursement prépayé', [
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier si un utilisateur peut effectuer un paiement par crédit
     */
    public function canMakeCreditPayment(User $user, float $amount, string $mode = 'prepaid'): array
    {
        $wallet = $user->getOrCreateWallet();
        $wallet->refresh();
        
        if ($mode === 'prepaid') {
            $hasSufficientBalance = $wallet->hasSufficientBalance($amount);
            $requiredBalance = $amount;
        } else {
            $minThreshold = config('charging.postpaid_min_threshold', 10.00);
            $hasSufficientBalance = $wallet->hasSufficientBalance($minThreshold);
            $requiredBalance = $minThreshold;
        }

        return [
            'can_pay' => $hasSufficientBalance,
            'current_balance' => $wallet->balance,
            'required_balance' => $requiredBalance,
            'shortfall' => max(0, $requiredBalance - $wallet->balance)
        ];
    }
}
