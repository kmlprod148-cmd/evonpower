<?php

namespace App\Services\Ocpp;

use App\Enums\ReservationStatus;
use App\Events\ChargingSessionCompleted;
use App\Events\ChargingSessionStarted;
use App\Models\ChargingSession;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChargingSessionStateMachine
{
    public function startSession(Reservation $reservation, array $transactionData): ChargingSession
    {
        return DB::transaction(function () use ($reservation, $transactionData) {
            $this->validateCanStartSession($reservation);

            $reservation->update([
                'status' => ReservationStatus::SESSION_INITIATED,
                'session_initiated_at' => now(),
                'session_initiation_status' => 'transaction_received',
            ]);

            $session = ChargingSession::create([
                'reservation_id' => $reservation->id,
                'charging_point_id' => $reservation->charging_point_id,
                'user_id' => $reservation->user_id,
                'steve_transaction_id' => $transactionData['transactionId'] ?? null,
                'connector_id' => $transactionData['connectorId'] ?? null,
                'ocpp_tag' => $transactionData['idTag'] ?? null,
                'status' => ChargingSession::STATUS_INITIATING,
                'started_at' => now(),
                'payment_mode' => $reservation->payment_mode,
                'prepaid_amount' => $reservation->isPrepaid() ? $reservation->prepaid_amount : null,
                'estimated_cost' => $reservation->estimated_cost,
                'estimated_energy' => $reservation->estimated_energy,
                'estimated_duration' => $reservation->estimated_duration,
            ]);

            $reservation->update([
                'charging_session_id' => $session->id,
                'transaction_id' => $transactionData['transactionId'] ?? null,
                'actual_start_time' => now(),
                'status' => ReservationStatus::ACTIVE,
            ]);

            $session->update(['status' => ChargingSession::STATUS_ACTIVE]);

            event(new ChargingSessionStarted($session));

            Log::info('Session started via state machine', [
                'session_id' => $session->id,
                'reservation_id' => $reservation->id,
            ]);

            return $session;
        });
    }

    public function stopSession(ChargingSession $session, string $reason = 'normal'): bool
    {
        return DB::transaction(function () use ($session, $reason) {
            $reservation = $session->reservation;

            $session->update([
                'status' => ChargingSession::STATUS_COMPLETED,
                'stopped_at' => now(),
                'stop_reason' => $reason,
            ]);

            $reservation->update([
                'status' => ReservationStatus::COMPLETED,
                'actual_end_time' => now(),
            ]);

            event(new ChargingSessionCompleted($session, $reason));

            Log::info('Session stopped via state machine', [
                'session_id' => $session->id,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    public function handleReservationExpired(ChargingSession $session, Reservation $reservation): bool
    {
        if (!$session->isActive()) {
            return false;
        }

        $reservation->update([
            'status' => ReservationStatus::EXPIRED,
        ]);

        return $this->stopSession($session, 'reservation_expired');
    }

    public function handleStartTransactionTimeout(Reservation $reservation): bool
    {
        $reservation->update([
            'status' => ReservationStatus::TIMEOUT_NO_TRANSACTION,
            'last_error' => 'No StartTransaction received within timeout period',
            'last_error_at' => now(),
        ]);

        Log::warning('StartTransaction timeout', [
            'reservation_id' => $reservation->id,
        ]);

        return false;
    }

    public function handleStartTransactionFailure(Reservation $reservation, string $error): bool
    {
        $reservation->update([
            'status' => ReservationStatus::FAILED,
            'last_error' => $error,
            'last_error_at' => now(),
        ]);

        Log::error('StartTransaction failure', [
            'reservation_id' => $reservation->id,
            'error' => $error,
        ]);

        return false;
    }

    protected function validateCanStartSession(Reservation $reservation): void
    {
        if (!in_array($reservation->status, [ReservationStatus::RESERVED, ReservationStatus::SESSION_INITIATED])) {
            throw new \InvalidArgumentException("Reservation status {$reservation->status->value} cannot start session");
        }
    }
}