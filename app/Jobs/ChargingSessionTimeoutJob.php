<?php

namespace App\Jobs;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\Ocpp\ChargingSessionStateMachine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChargingSessionTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $reservationId)
    {
    }

    public function handle(ChargingSessionStateMachine $stateMachine): void
    {
        $reservation = Reservation::find($reservationId);

        if (!$reservation) {
            return;
        }

        if ($reservation->status === ReservationStatus::RESERVED) {
            Log::warning('Session timeout - no StartTransaction received', [
                'reservation_id' => $reservation->id,
            ]);

            $stateMachine->handleStartTransactionTimeout($reservation);
        }
    }
}