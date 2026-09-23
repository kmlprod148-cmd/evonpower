<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service centralisé pour synchroniser le statut des transactions avec les réservations.
 *
 * Règles automatiques (sans intervention du propriétaire de la borne) :
 * - PRÉPAYÉ : reservation.payment_status = PAID → transaction.status = completed
 * - POSTPAYÉ : reservation.payment_status = PAID (après débit fin de session) → transaction.status = completed
 *
 * Ce service est la source unique de vérité pour cette logique.
 */
class TransactionStatusSyncService
{
    /**
     * Statuts considérés comme "payé" pour une réservation (comparaison en majuscules).
     */
    protected array $paidStatuses = ['PAID', 'PAYE'];

    /**
     * Vérifie si une réservation est considérée comme payée.
     */
    public function isReservationPaid(?Reservation $reservation): bool
    {
        if (!$reservation) {
            return false;
        }
        $status = $reservation->payment_status ?? null;
        return in_array(strtoupper((string) $status), $this->paidStatuses);
    }

    /**
     * Vérifie si une réservation est considérée comme payée (via raw DB).
     */
    public function isReservationPaidById(int $reservationId): bool
    {
        $row = DB::table('reservations')
            ->where('id', $reservationId)
            ->whereRaw('UPPER(COALESCE(payment_status, \'\')) IN (?, ?)', ['PAID', 'PAYE'])
            ->exists();
        return $row;
    }

    /**
     * Met à jour une transaction en "completed" si la réservation associée est payée.
     * Prépayé ET postpayé : même règle (payment_status = PAID).
     *
     * @return bool True si la transaction a été mise à jour
     */
    public function syncTransactionStatusIfPaid(Transaction $transaction): bool
    {
        if (!$transaction->reservation_id) {
            return false;
        }
        // Déjà completed → rien à faire. confirmed → on passe à completed (spécification flux client)
        // Pour paiement par solde (prepaid/postpaid) : toujours completed = approuvé automatiquement
        $statusLower = strtolower((string) ($transaction->status ?? ''));
        if (in_array($statusLower, ['completed', 'confirmed'])) {
            // Si confirmed, passer à completed pour cohérence (réservation payée = terminée)
            if ($statusLower === 'confirmed') {
                $transaction->status = 'completed';
                $transaction->saveQuietly();
                Log::info('TransactionStatusSyncService: confirmed → completed (réservation payée)', [
                    'transaction_id' => $transaction->id,
                    'reservation_id' => $transaction->reservation_id,
                ]);
                return true;
            }
            return false;
        }

        $reservation = $transaction->reservation;
        if (!$reservation) {
            $reservation = Reservation::find($transaction->reservation_id);
        }
        if (!$this->isReservationPaid($reservation)) {
            return false;
        }

        $transaction->status = 'completed';
        $transaction->saveQuietly();

        Log::info('TransactionStatusSyncService: Transaction mise à jour automatiquement', [
            'transaction_id' => $transaction->id,
            'reservation_id' => $transaction->reservation_id,
            'payment_mode' => $reservation->payment_mode ?? 'unknown',
        ]);

        return true;
    }

    /**
     * IDs des réservations payées (PAID, prepaid_amount>0, payment_method prepaid_credit, ou prepaid+credit+estimated_cost>0).
     */
    public function getPaidReservationIds(): \Illuminate\Support\Collection
    {
        return DB::table('reservations')
            ->where(function ($q) {
                $q->whereRaw('UPPER(COALESCE(payment_status, \'\')) IN (?, ?)', ['PAID', 'PAYE'])
                    ->orWhere(function ($q2) {
                        $q2->where('payment_mode', 'prepaid')
                            ->whereRaw('COALESCE(prepaid_amount, 0) > 0');
                    })
                    ->orWhere(function ($q2) {
                        $q2->where('payment_mode', 'prepaid')
                            ->whereRaw('LOWER(COALESCE(payment_method, \'\')) = ?', ['credit'])
                            ->whereRaw('COALESCE(estimated_cost, 0) > 0');
                    })
                    ->orWhereRaw('LOWER(COALESCE(payment_method, \'\')) IN (?, ?)', ['prepaid_credit', 'postpaid_credit']);
            })
            ->pluck('id');
    }

    /**
     * Normalise les réservations payées par solde : met payment_status=PAID.
     * Cas 1: prepaid_amount > 0
     * Cas 2: payment_mode=prepaid + payment_method=credit + estimated_cost > 0 (prepaid_amount manquant)
     * Retourne le nombre de réservations mises à jour.
     */
    public function normalizePaidReservations(): int
    {
        $total = 0;

        // Cas 1: prepaid_amount déjà renseigné
        $count1 = DB::table('reservations')
            ->where('payment_mode', 'prepaid')
            ->whereRaw('COALESCE(prepaid_amount, 0) > 0')
            ->whereRaw('UPPER(COALESCE(payment_status, \'\')) NOT IN (?, ?)', ['PAID', 'PAYE'])
            ->update([
                'payment_status' => 'PAID',
                'payment_method' => 'prepaid_credit',
                'status' => 'confirmed',
            ]);
        $total += $count1;

        // Cas 2: prepaid_amount manquant mais payment_mode=prepaid + payment_method=credit + estimated_cost > 0
        $count2 = DB::table('reservations')
            ->where('payment_mode', 'prepaid')
            ->whereRaw('LOWER(COALESCE(payment_method, \'\')) = ?', ['credit'])
            ->whereRaw('COALESCE(estimated_cost, 0) > 0')
            ->whereRaw('UPPER(COALESCE(payment_status, \'\')) NOT IN (?, ?)', ['PAID', 'PAYE'])
            ->update([
                'payment_status' => 'PAID',
                'payment_method' => 'prepaid_credit',
                'status' => 'confirmed',
                'prepaid_amount' => DB::raw('estimated_cost'),
            ]);
        $total += $count2;

        return $total;
    }

    /**
     * Compte les transactions à corriger (sans les modifier).
     */
    public function countPendingTransactionsForPaidReservations(): int
    {
        $paidReservationIds = $this->getPaidReservationIds();

        if ($paidReservationIds->isEmpty()) {
            return 0;
        }

        return Transaction::query()
            ->whereNotNull('reservation_id')
            ->whereIn('reservation_id', $paidReservationIds)
            ->whereRaw('LOWER(COALESCE(status, \'\')) IN (?, ?)', ['pending', 'confirmed'])
            ->count();
    }

    /**
     * Synchronise toutes les transactions en attente dont la réservation est payée.
     * Utilisé par le job planifié et la commande artisan.
     *
     * @return int Nombre de transactions corrigées
     */
    public function syncAllPendingTransactionsForPaidReservations(): int
    {
        $paidReservationIds = $this->getPaidReservationIds();

        if ($paidReservationIds->isEmpty()) {
            return 0;
        }

        // Mettre à jour pending ET confirmed → completed (réservations payées = approuvées automatiquement)
        $updated = Transaction::query()
            ->whereNotNull('reservation_id')
            ->whereIn('reservation_id', $paidReservationIds)
            ->whereRaw('LOWER(COALESCE(status, \'\')) IN (?, ?)', ['pending', 'confirmed'])
            ->update(['status' => 'completed']);

        if ($updated > 0) {
            Log::info('TransactionStatusSyncService: Synchronisation batch effectuée', [
                'count' => $updated,
            ]);
        }

        return $updated;
    }

    /**
     * Appelé depuis un Observer ou un Job : synchronise la transaction si la réservation est payée.
     * Utilisé par ReservationObserver quand payment_status devient PAID.
     */
    public function syncForReservation(Reservation $reservation): bool
    {
        if (!$this->isReservationPaid($reservation)) {
            return false;
        }

        $transaction = $reservation->transaction;
        if (!$transaction) {
            return false;
        }

        return $this->syncTransactionStatusIfPaid($transaction);
    }
}
