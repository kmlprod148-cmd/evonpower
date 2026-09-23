<?php

namespace App\Jobs;

use App\Events\OcppStartTransactionReceived;
use App\Models\OcppIncomingEvent;
use App\Services\Ocpp\ChargingSessionStateMachine;
use App\Services\Ocpp\StartTransactionCorrelator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStartTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public OcppIncomingEvent $event)
    {
    }

    public function handle(StartTransactionCorrelator $correlator, ChargingSessionStateMachine $stateMachine): void
    {
        try {
            $payload = $this->event->payload;

            $reservation = $correlator->findMatchingReservation($payload, $this->event->charge_box_id);

            if (!$reservation) {
                Log::warning('No matching reservation found for StartTransaction', [
                    'charge_box_id' => $this->event->charge_box_id,
                    'payload' => $payload,
                ]);
                $this->event->markFailed('No matching reservation found');
                return;
            }

            $session = $stateMachine->startSession($reservation, $payload);

            $correlator->correlateMatch($reservation, $payload);

            $this->event->markProcessed();

            event(new OcppStartTransactionReceived($session, $payload));

            Log::info('StartTransaction processed', [
                'session_id' => $session->id,
                'reservation_id' => $reservation->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process StartTransaction', [
                'charge_box_id' => $this->event->charge_box_id,
                'error' => $e->getMessage(),
            ]);
            $this->event->markFailed($e->getMessage());
        }
    }
}