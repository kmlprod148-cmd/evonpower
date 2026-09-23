<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\WalletTransaction;
use App\Services\ReservationTransactionService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for preparing and formatting transactions for display.
 * Handles transformation of transaction models into view-friendly formats.
 */
class TransactionPresentationService
{
    /**
     * Default pagination per page
     */
    public const DEFAULT_PER_PAGE = 20;

    /**
     * @var ReservationTransactionService
     */
    protected $transactionService;

    public function __construct(ReservationTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Prepare wallet transactions for display
     */
    public function prepareWalletTransactions(Collection $walletTransactions): Collection
    {
        return $walletTransactions->map(function ($wt) {
            try {
                $reservationId = $wt->getReservationId();
                $relatedTransaction = $wt->relationLoaded('scopedTransaction')
                    ? $wt->getRelation('scopedTransaction')
                    : null;

                if (!$relatedTransaction) {
                    $transactionId = (int) (($wt->metadata ?? [])['transaction_id'] ?? 0);

                    if ($transactionId > 0) {
                        $relatedTransaction = \App\Models\Transaction::with([
                            'user',
                            'reservation.user',
                            'reservation.chargingPoint',
                            'chargingPoint',
                        ])->find($transactionId);
                    } elseif ($reservationId) {
                        $relatedTransaction = \App\Models\Transaction::with([
                            'user',
                            'reservation.user',
                            'reservation.chargingPoint',
                            'chargingPoint',
                        ])->where('reservation_id', $reservationId)->latest('id')->first();
                    }
                }

                $relatedReservation = $relatedTransaction?->reservation;
                $chargingPoint = $relatedTransaction?->chargingPoint ?? $relatedReservation?->chargingPoint;
                $displayUser = $relatedTransaction?->user ?? $relatedReservation?->user ?? $wt->wallet?->owner;

                return [
                    'id' => $wt->id,
                    'type' => 'wallet',
                    'wallet_transaction' => $wt,
                    'amount' => $wt->type === 'credit' ? $wt->amount : -$wt->amount,
                    'currency' => $wt->currency ?? 'EUR',
                    'description' => $wt->description ?? 'Transaction wallet',
                    'status' => 'completed',
                    'created_at' => $wt->created_at,
                    'reservation_id' => $reservationId,
                    'is_reservation_related' => $reservationId !== null,
                    'owner_name' => $displayUser->name ?? ($wt->wallet?->owner?->name ?? 'N/A'),
                    'owner_email' => $displayUser->email ?? ($wt->wallet?->owner?->email ?? 'N/A'),
                    'charging_point_name' => $chargingPoint?->name ?? 'N/A',
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to prepare wallet transaction', [
                    'transaction_id' => $wt->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        })->filter()->values();
    }

    /**
     * Prepare reservation transactions for display
     */
    public function prepareReservationTransactions(Collection $transactions): Collection
    {
        return $transactions->map(function ($rt) {
            try {
                // Auto-create transaction detail if needed
                $this->ensureTransactionDetail($rt);

                // Resolve the best available amount (same priority chain as TransactionHTTTCService)
                $resolvedAmount = (float) (
                    (isset($rt->price_total) && $rt->price_total > 0 ? $rt->price_total : null)
                    ?? (isset($rt->total_amount) && $rt->total_amount > 0 ? $rt->total_amount : null)
                    ?? ($rt->reservation && isset($rt->reservation->estimated_cost) && $rt->reservation->estimated_cost > 0 ? $rt->reservation->estimated_cost : null)
                    ?? ($rt->reservation && isset($rt->reservation->actual_cost) && $rt->reservation->actual_cost > 0 ? $rt->reservation->actual_cost : null)
                    ?? (isset($rt->amount) && abs((float) $rt->amount) > 0 ? abs((float) $rt->amount) : null)
                    ?? 0
                );

                return [
                    'id' => $rt->id,
                    'type' => 'reservation',
                    'transaction' => $rt,
                    'amount' => $resolvedAmount,
                    'currency' => 'EUR',
                    'description' => $rt->description ?? "Paiement réservation #{$rt->reservation_id}",
                    'status' => $rt->status ?? 'unknown',
                    'created_at' => $rt->created_at ?? now(),
                    'transaction_detail' => $rt->transactionDetail ?? null,
                    'reservation' => $rt->reservation ?? null,
                    'reservation_id' => $rt->reservation_id ?? null,
                    'charging_point' => $rt->chargingPoint ?? null,
                    'charging_point_name' => $this->getChargingPointName($rt->chargingPoint),
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to prepare reservation transaction', [
                    'transaction_id' => $rt->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        })->filter()->values();
    }

    /**
     * Ensure transaction has a detail record
     */
    protected function ensureTransactionDetail(Transaction $transaction): void
    {
        if ($transaction->transactionDetail) {
            return;
        }

        $canCreateDetail = $transaction->status === 'completed' || 
            $transaction->status === 'confirmed' ||
            ($transaction->reservation && $this->isReservationApproved($transaction->reservation));

        if (!$canCreateDetail) {
            return;
        }

        try {
            $result = $this->transactionService->createRetroactiveTransactionDetail($transaction);

            if ($result['success']) {
                $transaction->refresh();
                $transaction->load('transactionDetail');
            }
        } catch (\Exception $e) {
            Log::warning('Failed to auto-create transaction detail', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if reservation is approved
     */
    protected function isReservationApproved($reservation): bool
    {
        if (!$reservation) {
            return false;
        }

        $status = $reservation->status;
        
        if ($status instanceof \App\Enums\ReservationStatus) {
            return in_array($status->value, ['confirmed', 'completed']);
        }

        return in_array($status, ['confirmed', 'completed']);
    }

    /**
     * Get charging point name safely
     */
    protected function getChargingPointName($chargingPoint): string
    {
        if (!$chargingPoint || !isset($chargingPoint->name)) {
            return 'N/A';
        }

        return $chargingPoint->name;
    }

    /**
     * Group transactions by reservation ID
     */
    public function groupByReservation(Collection $walletTransactions, Collection $reservationTransactions): array
    {
        $walletByReservation = $walletTransactions
            ->filter(fn($wt) => $wt['reservation_id'] !== null)
            ->groupBy('reservation_id');

        $reservationByReservation = $reservationTransactions
            ->filter(fn($rt) => $rt['reservation_id'] !== null)
            ->groupBy('reservation_id');

        $allReservationIds = $walletByReservation->keys()
            ->merge($reservationByReservation->keys())
            ->unique()
            ->sortDesc();

        $grouped = [];
        foreach ($allReservationIds as $reservationId) {
            $grouped[] = [
                'reservation_id' => $reservationId,
                'wallet_transactions' => $walletByReservation->get($reservationId, collect()),
                'reservation_transactions' => $reservationByReservation->get($reservationId, collect()),
                'total_wallet_count' => $walletByReservation->get($reservationId, collect())->count(),
                'total_reservation_count' => $reservationByReservation->get($reservationId, collect())->count(),
                'total_count' => $walletByReservation->get($reservationId, collect())->count() + 
                                $reservationByReservation->get($reservationId, collect())->count(),
            ];
        }

        return [
            'grouped' => collect($grouped),
            'standalone_wallet' => $walletTransactions->filter(fn($wt) => $wt['reservation_id'] === null),
            'standalone_reservation' => $reservationTransactions->filter(fn($rt) => $rt['reservation_id'] === null),
        ];
    }

    /**
     * Create paginated results
     */
    public function paginateTransactions(
        Collection $transactions,
        int $currentPage = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?string $tableType = 'wallet'
    ): LengthAwarePaginator {
        $items = $transactions->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $transactions->count(),
            $perPage,
            $currentPage
        );
    }

    /**
     * Sort transactions by date descending
     */
    public function sortByDateDescending(Collection $transactions): Collection
    {
        return $transactions->sortByDesc('created_at')->values();
    }

    /**
     * Filter transactions by reservation ID
     */
    public function filterByReservationId(Collection $transactions, ?int $reservationId): Collection
    {
        if (!$reservationId) {
            return $transactions;
        }

        return $transactions->filter(fn($t) => $t['reservation_id'] == $reservationId)->values();
    }

    /**
     * Merge and sort transactions
     */
    public function mergeAndSort(Collection $walletTransactions, Collection $reservationTransactions): Collection
    {
        return $walletTransactions->concat($reservationTransactions)
            ->sortByDesc('created_at')
            ->values();
    }
}
