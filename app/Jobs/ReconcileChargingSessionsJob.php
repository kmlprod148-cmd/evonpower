<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ReservationStatus;
use App\Models\ChargingSession;
use App\Models\Reservation;
use App\Services\OcppOperationsService;
use App\Services\StevePostpaidPaymentService;
use App\Services\SteVeHttpClientService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Polls SteVe for transaction state of every non-terminal ChargingSession with
 * a steve_transaction_id, and reconciles three things when SteVe reports the
 * transaction has stopped:
 *
 *   1. Flip ChargingSession to STOPPED via OcppOperationsService (canonical
 *      state-machine writes — stopped_at, stop_value, stop_reason,
 *      steve_stop_response).
 *   2. Cascade the linked Reservation (if any) to COMPLETED with
 *      actual_end_time = stopTimestamp.
 *   3. If payment_mode = postpaid AND the reservation isn't already PAID,
 *      dispatch StevePostpaidPaymentService::finalizeFromSteve (wallet debit).
 *
 * Slice C: replaces the postpaid-scoped SyncSteVePostpaidTransactionsJob with
 * a generic reconciler covering both prepaid and postpaid flows, plus
 * operator-initiated stops (cable unplug, time/energy limit, SteVe web UI).
 * Runs every minute (matches the OCPP enforcement cadence).
 */
class ReconcileChargingSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 1;

    public function __construct(
        protected int $limit = 50,
        protected int $maxAgeMinutes = 240,
    ) {
    }

    public function handle(
        SteVeHttpClientService $steve,
        OcppOperationsService $ocppOps,
        StevePostpaidPaymentService $postpaidService,
    ): void {
        if (!config('steve.reconcile_enabled', true)) {
            Log::debug('ReconcileChargingSessionsJob: reconciliation désactivée par config');
            return;
        }

        $cutoff = now()->subMinutes($this->maxAgeMinutes);

        $sessions = ChargingSession::with(['reservation', 'chargingPoint'])
            ->whereIn('status', [
                ChargingSession::STATUS_ACTIVE,
                ChargingSession::STATUS_IN_PROGRESS,
                ChargingSession::STATUS_INITIATING,
                ChargingSession::STATUS_PENDING,
            ])
            ->whereNotNull('steve_transaction_id')
            ->where('started_at', '>=', $cutoff)
            ->orderBy('started_at', 'desc')
            ->limit($this->limit)
            ->get();

        if ($sessions->isEmpty()) {
            return;
        }

        $reconciled = 0;
        $stillActive = 0;
        $errors = 0;
        $postpaidFinalized = 0;

        foreach ($sessions as $session) {
            try {
                $outcome = $this->reconcileOne($session, $steve, $ocppOps, $postpaidService);
                match ($outcome) {
                    'stopped'    => $reconciled++,
                    'finalized'  => ++$reconciled && $postpaidFinalized++,
                    'active'     => $stillActive++,
                    'error'      => $errors++,
                    default      => null,
                };
            } catch (Throwable $e) {
                $errors++;
                Log::error('ReconcileChargingSessionsJob: exception per-session', [
                    'session_id' => $session->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        if ($reconciled > 0 || $errors > 0) {
            Log::info('ReconcileChargingSessionsJob: tour terminé', [
                'scanned'            => $sessions->count(),
                'reconciled'         => $reconciled,
                'still_active'       => $stillActive,
                'postpaid_finalized' => $postpaidFinalized,
                'errors'             => $errors,
            ]);
        }
    }

    /**
     * @return 'stopped'|'finalized'|'active'|'error'
     */
    private function reconcileOne(
        ChargingSession $session,
        SteVeHttpClientService $steve,
        OcppOperationsService $ocppOps,
        StevePostpaidPaymentService $postpaidService,
    ): string {
        $txId = (string) $session->steve_transaction_id;
        $result = $steve->getTransaction($txId);

        if (($result['success'] ?? false) !== true) {
            // 4xx (transaction not found on SteVe) vs 5xx (transient): we don't
            // mark the session FAILED here — that's a separate concern (the
            // "broadest" scope option). Just count it as an error so the
            // operator sees noise if SteVe is down.
            Log::debug('ReconcileChargingSessionsJob: SteVe getTransaction failed', [
                'session_id'     => $session->id,
                'steve_txn_id'   => $txId,
                'upstream_error' => $result['error']   ?? null,
            ]);
            return 'error';
        }

        $data = $result['data'] ?? [];
        if (!is_array($data) || empty($data)) {
            return 'error';
        }

        // SteVe writes stopTimestamp when the charger has sent StopTransaction.
        // No stopTimestamp → still active on SteVe's side, nothing to do.
        $stopTimestamp = $data['stopTimestamp'] ?? null;
        if ($stopTimestamp === null || $stopTimestamp === '') {
            return 'active';
        }

        // (1) Flip local ChargingSession to STOPPED via the canonical layer.
        $ocppOps->markSessionStoppedFromSteve($session, $data);

        // (2) Cascade the linked Reservation to COMPLETED.
        $this->cascadeReservation($session, $stopTimestamp);

        // (3) Postpaid wallet debit, only when payment isn't already settled.
        if ($this->shouldFinalizePostpaid($session)) {
            $finalize = $postpaidService->finalizeFromSteve($session);
            if (!($finalize['success'] ?? false)) {
                Log::error('ReconcileChargingSessionsJob: échec finalisation postpayé', [
                    'session_id' => $session->id,
                    'error'      => $finalize['error'] ?? 'unknown',
                ]);
                return 'error';
            }
            return 'finalized';
        }

        return 'stopped';
    }

    private function cascadeReservation(ChargingSession $session, ?string $stopTimestamp): void
    {
        $reservation = $session->reservation;
        if ($reservation === null) {
            return;
        }

        // Idempotent: only flip if still ACTIVE. Don't disturb terminal states.
        $currentStatus = $reservation->status instanceof ReservationStatus
            ? $reservation->status
            : ReservationStatus::tryFrom((string) $reservation->status);
        if ($currentStatus !== ReservationStatus::ACTIVE) {
            return;
        }

        try {
            DB::transaction(function () use ($reservation, $stopTimestamp) {
                $fresh = Reservation::lockForUpdate()->find($reservation->id);
                if ($fresh === null) {
                    return;
                }
                $fresh->update([
                    'status'          => ReservationStatus::COMPLETED,
                    'actual_end_time' => $fresh->actual_end_time
                        ?: ($stopTimestamp ? Carbon::parse($stopTimestamp) : now()),
                ]);
            });
        } catch (Throwable $e) {
            Log::warning('ReconcileChargingSessionsJob: failed to cascade reservation to COMPLETED', [
                'reservation_id' => $reservation->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    private function shouldFinalizePostpaid(ChargingSession $session): bool
    {
        if (($session->payment_mode ?? null) !== ChargingSession::PAYMENT_MODE_POSTPAID) {
            return false;
        }

        $reservation = $session->reservation;
        if ($reservation === null) {
            return true;
        }

        $paymentStatus = strtoupper((string) ($reservation->payment_status ?? ''));
        return !in_array($paymentStatus, ['PAID', 'PAYE'], true);
    }
}
