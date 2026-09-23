<?php

namespace App\Services\Ocpp;

use App\Models\OcppCommandOutbox;
use App\Models\Reservation;
use App\Models\ChargingSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OcppCommandOutboxService
{
    public function enqueue(string $command, string $chargeBoxId, array $payload, ?int $reservationId = null, ?int $sessionId = null): OcppCommandOutbox
    {
        return OcppCommandOutbox::create([
            'command' => $command,
            'charge_box_id' => $chargeBoxId,
            'payload' => $payload,
            'status' => 'pending',
            'reservation_id' => $reservationId,
            'charging_session_id' => $sessionId,
            'correlation_id' => $this->generateCorrelationId($command, $chargeBoxId, $payload),
        ]);
    }

    public function enqueueRemoteStart(string $chargeBoxId, int $connectorId, string $idTag, ?int $reservationId = null): OcppCommandOutbox
    {
        return $this->enqueue(
            'RemoteStartTransaction',
            $chargeBoxId,
            [
                'connectorId' => $connectorId,
                'idTag' => $idTag,
            ],
            $reservationId
        );
    }

    public function enqueueReserveNow(string $chargeBoxId, int $connectorId, int $reservationId, string $expiryDate, string $idTag): OcppCommandOutbox
    {
        return $this->enqueue(
            'ReserveNow',
            $chargeBoxId,
            [
                'connectorId' => $connectorId,
                'reservationId' => $reservationId,
                'expiryDate' => $expiryDate,
                'idTag' => $idTag,
            ],
            $reservationId
        );
    }

    public function enqueueStopTransaction(string $chargeBoxId, int $transactionId, ?int $sessionId = null): OcppCommandOutbox
    {
        return $this->enqueue(
            'StopTransaction',
            $chargeBoxId,
            [
                'transactionId' => $transactionId,
            ],
            null,
            $sessionId
        );
    }

    public function processPending(): int
    {
        $count = 0;
        $commands = OcppCommandOutbox::where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->limit(100)
            ->get();

        foreach ($commands as $command) {
            dispatch(new SendOcppCommandJob($command));
            $count++;
        }

        return $count;
    }

    public function markSent(int $commandId, array $response = null): void
    {
        $command = OcppCommandOutbox::find($commandId);
        if ($command) {
            $command->markSent($response);
        }
    }

    public function markResponded(int $commandId, array $response): void
    {
        $command = OcppCommandOutbox::find($commandId);
        if ($command) {
            $command->markResponded($response);
        }
    }

    public function markFailed(int $commandId, string $error): void
    {
        $command = OcppCommandOutbox::find($commandId);
        if ($command) {
            $command->markFailed($error);
        }
    }

    public function incrementRetry(int $commandId): void
    {
        $command = OcppCommandOutbox::find($commandId);
        if ($command) {
            $command->incrementRetry();
        }
    }

    private function generateCorrelationId(string $command, string $chargeBoxId, array $payload): string
    {
        return Str::md5($command . $chargeBoxId . json_encode($payload));
    }
}