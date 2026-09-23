<?php

declare(strict_types=1);

namespace App\Services\Reservations;

use App\DTO\OCPP\RemoteStartRequestDTO;
use App\DTO\OCPP\RemoteStartResponseDTO;
use App\DTO\OCPP\RemoteStopRequestDTO;
use App\DTO\OCPP\RemoteStopResponseDTO;
use App\Enums\ReservationStatus;
use App\Models\ChargingSession;
use App\Models\Reservation;
use App\Services\OcppOperationsService;
use App\Services\SteVe\OcppTagResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Domain service that drives a reservation through its charging lifecycle.
 *
 * Splits *what to do for a Reservation* from *how to talk OCPP to SteVe* —
 * the OCPP transport lives in OcppOperationsService and the HTTP layer below
 * it. This class is the single place reservation rows get mutated when a
 * remote-start / remote-stop is dispatched, so the Stripe/secure-payment flow
 * and the deprecated SteVe* shims converge on identical state transitions.
 *
 * Response shape is the legacy {success, message, transaction_id, method,
 * data, code} envelope so existing callers (SecurePaymentController,
 * SteVeRetryService) don't need to change.
 */
class ReservationChargingService
{
    public function __construct(
        private readonly OcppOperationsService $ocpp,
        private readonly OcppTagResolver $tagResolver,
    ) {
    }

    /**
     * Dispatch RemoteStart for a paid/confirmed reservation and, when SteVe
     * accepts, transition the row to ACTIVE.
     *
     * @param array<string, mixed> $opts Optional overrides:
     *   - connector_id : int  (defaults to reservation->connector_id, then 1)
     *   - ocpp_tag     : ?string  (otherwise resolved via OcppTagResolver)
     *   - idempotency_key : ?string
     * @return array<string, mixed>
     */
    public function start(Reservation $reservation, array $opts = []): array
    {
        $chargingPoint = $reservation->chargingPoint;
        if ($chargingPoint === null) {
            return $this->fail('charging_point_not_found', 'Point de charge non trouvé pour la réservation.');
        }

        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;
        if (empty($chargeBoxId)) {
            return $this->fail('charge_point_unconfigured', 'Point de charge non configuré pour SteVe (charge_box_id manquant).');
        }

        $connectorId = (int) ($opts['connector_id'] ?? $reservation->connector_id ?? 1);

        $ocppTag = $this->tagResolver->resolve(
            $opts['ocpp_tag'] ?? $opts['id_tag'] ?? $this->reservationTag($reservation)
        );
        if ($ocppTag === null) {
            return $this->fail('ocpp_tag_unavailable', 'Aucun tag OCPP disponible pour démarrer la session.');
        }

        $dto = new RemoteStartRequestDTO([
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
            'ocppTag'     => $ocppTag,
        ]);

        $response = $this->ocpp->remoteStart($dto, [
            'idempotencyKey' => $opts['idempotency_key'] ?? 'reservation:' . $reservation->id,
            'reservationId'  => $reservation->id,
        ]);

        if ($response->isAccepted()) {
            $this->markReservationActive($reservation, $response);

            return [
                'success'        => true,
                'message'        => $response->message,
                'method'         => 'ocpp',
                'transaction_id' => $response->getTransactionId(),
                'data'           => $response->toApiResponse(),
                'reservation'    => $reservation->fresh(),
            ];
        }

        return [
            'success' => false,
            'code'    => $response->errorCode ?? 'remote_start_rejected',
            'error'   => $response->message,
            'message' => $response->message,
            'data'    => $response->toApiResponse(),
        ];
    }

    /**
     * Dispatch RemoteStop for an active reservation and, when SteVe accepts,
     * transition the row to COMPLETED.
     *
     * @return array<string, mixed>
     */
    public function stop(Reservation $reservation, string $reason = 'manual', array $opts = []): array
    {
        $chargingPoint = $reservation->chargingPoint;
        if ($chargingPoint === null) {
            return $this->fail('charging_point_not_found', 'Point de charge non trouvé pour la réservation.');
        }

        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;
        if (empty($chargeBoxId)) {
            return $this->fail('charge_point_unconfigured', 'Point de charge non configuré pour SteVe (charge_box_id manquant).');
        }

        $transactionId = (int) ($opts['transaction_id'] ?? $this->resolveTransactionId($reservation));
        if ($transactionId <= 0) {
            return $this->fail('transaction_id_unknown', 'Aucune transaction OCPP active associée à cette réservation.');
        }

        $dto = new RemoteStopRequestDTO([
            'chargeBoxId'   => $chargeBoxId,
            'transactionId' => $transactionId,
        ]);

        $response = $this->ocpp->remoteStop($dto, [
            'idempotencyKey' => $opts['idempotency_key'] ?? 'reservation-stop:' . $reservation->id,
            'reservationId'  => $reservation->id,
            'reason'         => $reason,
        ]);

        if ($response->isAccepted()) {
            $this->markReservationCompleted($reservation);

            return [
                'success'        => true,
                'message'        => $response->message,
                'method'         => 'ocpp',
                'transaction_id' => $response->transactionId,
                'reason'         => $reason,
                'data'           => $response->toApiResponse(),
                'reservation'    => $reservation->fresh(),
            ];
        }

        return [
            'success' => false,
            'code'    => $response->errorCode ?? 'remote_stop_rejected',
            'error'   => $response->message,
            'message' => $response->message,
            'data'    => $response->toApiResponse(),
        ];
    }

    /**
     * The OCPP id-tag to use for a given reservation when the caller didn't
     * supply one. Resolution order:
     *   1. reservation.ocpp_tag_id → OcppTag.ocpp_tag (column name; SteVe wire name is idTag)
     *   2. reservation.user.ocpp_tag (legacy column on User if present)
     *   3. null (resolver will fall back to STEVE_DEFAULT_ID_TAG / first usable)
     */
    private function reservationTag(Reservation $reservation): ?string
    {
        if ($reservation->ocpp_tag_id !== null && method_exists($reservation, 'ocppTag')) {
            $tag = $reservation->ocppTag()->first();
            if ($tag !== null && !empty($tag->ocpp_tag)) {
                return (string) $tag->ocpp_tag;
            }
        }

        $user = $reservation->user;
        if ($user !== null && !empty($user->ocpp_tag ?? null)) {
            return (string) $user->ocpp_tag;
        }

        return null;
    }

    /**
     * Resolve the SteVe transactionId for a stop dispatch. Prefers the
     * ChargingSession row that OcppOperationsService::remoteStart already
     * promoted to ACTIVE (steve_transaction_id), then falls back to the
     * reservation's transaction relation.
     */
    private function resolveTransactionId(Reservation $reservation): int
    {
        if ($reservation->charging_session_id !== null) {
            $session = ChargingSession::find($reservation->charging_session_id);
            if ($session !== null && !empty($session->steve_transaction_id)) {
                return (int) $session->steve_transaction_id;
            }
        }
        return 0;
    }

    /**
     * Persist the post-accepted-start state. Wrapped so a model write failure
     * never breaks the response — the operator already saw SteVe ACCEPTED.
     */
    private function markReservationActive(Reservation $reservation, RemoteStartResponseDTO $response): void
    {
        try {
            DB::transaction(function () use ($reservation, $response) {
                $fresh = Reservation::lockForUpdate()->find($reservation->id);
                if ($fresh === null) {
                    return;
                }

                $updates = [
                    'status'           => ReservationStatus::ACTIVE,
                    'actual_start_time' => $fresh->actual_start_time ?: now(),
                ];

                $txnId = $response->getTransactionId();
                if ($txnId !== null && $fresh->charging_session_id === null) {
                    $session = ChargingSession::where('steve_transaction_id', (string) $txnId)
                        ->orderByDesc('id')
                        ->first();
                    if ($session !== null) {
                        $updates['charging_session_id'] = $session->id;
                    }
                }

                $fresh->update($updates);
            });
        } catch (Throwable $e) {
            Log::warning('ReservationChargingService: failed to mark reservation ACTIVE', [
                'reservation_id' => $reservation->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    private function markReservationCompleted(Reservation $reservation): void
    {
        try {
            DB::transaction(function () use ($reservation) {
                $fresh = Reservation::lockForUpdate()->find($reservation->id);
                if ($fresh === null) {
                    return;
                }
                $fresh->update([
                    'status'         => ReservationStatus::COMPLETED,
                    'actual_end_time' => $fresh->actual_end_time ?: now(),
                ]);
            });
        } catch (Throwable $e) {
            Log::warning('ReservationChargingService: failed to mark reservation COMPLETED', [
                'reservation_id' => $reservation->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    private function fail(string $code, string $message): array
    {
        return [
            'success' => false,
            'code'    => $code,
            'error'   => $message,
            'message' => $message,
        ];
    }
}
