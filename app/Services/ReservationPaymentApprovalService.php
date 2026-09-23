<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Events\ReservationApproved;
use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Log;

class ReservationPaymentApprovalService
{
    /**
     * Payment methods that should never wait for manual approval after capture.
     */
    private const FORCED_AUTO_APPROVE_METHODS = [
        'stripe',
        'cmi',
        'credit',
        'prepaid_credit',
        'postpaid_credit',
        'wallet',
        'balance',
    ];

    /**
     * Mark a reservation as paid and auto-approve it.
     *
     * Sets payment_status = PAID, status = confirmed, approved_at = now(),
     * and resolves the charging-point operator as the approved_by actor so that
     * the reservation is recorded as "approved by owner".
     */
    public function applyPaymentSuccess(
        Reservation $reservation,
        ?string $paymentMethod = null,
        string $source = 'payment_gateway'
    ): array {
        $autoApprove = $this->shouldAutoApprove($reservation, $paymentMethod, $source);
        $now = now();
        $statusValue = $reservation->status instanceof ReservationStatus
            ? $reservation->status->value
            : (string) $reservation->status;

        $update = [
            'payment_status' => 'PAID',
            'payment_confirmed_at' => $reservation->payment_confirmed_at ?? $now,
        ];

        if ($paymentMethod) {
            $update['payment_method'] = $paymentMethod;
        }

        if ($autoApprove) {
            if (in_array($statusValue, [
                ReservationStatus::PENDING->value,
                ReservationStatus::PENDING_CONFIRMATION->value,
                '',
            ], true)) {
                $update['status'] = ReservationStatus::CONFIRMED;
            }

            $update['confirmed_at'] = $reservation->confirmed_at ?? $now;
            $update['approved_at'] = $reservation->approved_at ?? $now;

            if (!$reservation->approved_by) {
                // Resolve the charging-point owner (operator) as the approver so that
                // the reservation is marked "approved by owner" automatically.
                $update['approved_by'] = $this->resolveChargingPointOperatorId($reservation);
            }
        }

        $reservation->update($update);
        $reservation = $reservation->fresh();

        $this->logPaymentAudit($reservation, $autoApprove, $source);

        return [
            'reservation'  => $reservation,
            'auto_approved' => $autoApprove,
        ];
    }

    public function dispatchApprovalEvent(Reservation $reservation, string $approvalMethod = 'payment_gateway'): void
    {
        event(new ReservationApproved($reservation, 'system', $approvalMethod));
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    protected function shouldAutoApprove(
        Reservation $reservation,
        ?string $paymentMethod = null,
        string $source = 'payment_gateway'
    ): bool {
        $normalizedMethod = strtolower(trim((string) ($paymentMethod ?: $reservation->payment_method)));
        $normalizedSource = strtolower(trim($source));

        if ($reservation->isPaidByBalance()) {
            return true;
        }

        if (in_array($normalizedMethod, self::FORCED_AUTO_APPROVE_METHODS, true)) {
            return true;
        }

        foreach (['stripe', 'cmi', 'credit', 'wallet', 'balance'] as $keyword) {
            if (str_contains($normalizedSource, $keyword)) {
                return true;
            }
        }

        return (bool) config('auto-remote-start.auto_approve_on_payment', true);
    }

    /**
     * Resolve the user ID of the charging point's owner/operator.
     *
     * Priority: ChargingPoint::getOperator() → created_by_id → created_by → user_id
     *
     * Returns null if the charging point cannot be resolved (so that the column
     * stays NULL rather than pointing at a wrong user).
     */
    public function resolveChargingPointOperatorId(Reservation $reservation): ?int
    {
        if (!$reservation->charging_point_id) {
            return null;
        }

        try {
            /** @var ChargingPoint|null $cp */
            $cp = $reservation->chargingPoint
                ?? ChargingPoint::find($reservation->charging_point_id);

            if (!$cp) {
                return null;
            }

            // Primary: use the model's own operator-resolution logic
            $operator = $cp->getOperator();
            if ($operator) {
                return $operator->id;
            }

            // Fallback chain on the charging point record itself
            return $cp->created_by_id
                ?? $cp->created_by
                ?? $cp->user_id
                ?? null;

        } catch (\Throwable $e) {
            Log::warning('ReservationPaymentApprovalService: could not resolve operator id', [
                'reservation_id'    => $reservation->id,
                'charging_point_id' => $reservation->charging_point_id,
                'error'             => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function logPaymentAudit(Reservation $reservation, bool $autoApproved, string $source): void
    {
        try {
            $audit = app(AuditService::class);
            $audit->log(
                $autoApproved ? 'reservation.payment_auto_approved' : 'reservation.payment_confirmed',
                $autoApproved
                    ? 'Reservation payment confirmed and auto-approved by owner'
                    : 'Reservation payment confirmed',
                $reservation,
                [
                    'reservation_id' => $reservation->id,
                    'payment_status' => $reservation->payment_status,
                    'payment_method' => $reservation->payment_method,
                    'approved_by'    => $reservation->approved_by,
                    'source'         => $source,
                    'auto_approved'  => $autoApproved,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('ReservationPaymentApprovalService: audit log failed', [
                'reservation_id' => $reservation->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
