<?php

namespace App\Services\Ocpp;

use App\Jobs\ProcessStartTransactionJob;
use App\Jobs\ProcessStopTransactionJob;
use App\Jobs\ProcessStatusNotificationJob;
use App\Models\OcppIncomingEvent;
use Illuminate\Support\Facades\Log;

class OcppEventIngestionService
{
    public function ingest(string $messageType, string $chargeBoxId, array $payload, ?string $action = null): OcppIncomingEvent
    {
        $event = OcppIncomingEvent::create([
            'message_type' => $messageType,
            'charge_box_id' => $chargeBoxId,
            'connector_id' => $payload['connectorId'] ?? null,
            'action' => $action,
            'payload' => $payload,
            'received_at' => now(),
            'processed' => false,
        ]);

        $this->dispatchToProcessor($event);

        return $event;
    }

    protected function dispatchToProcessor(OcppIncomingEvent $event): void
    {
        match ($event->message_type) {
            'StartTransaction' => ProcessStartTransactionJob::dispatch($event),
            'StopTransaction' => ProcessStopTransactionJob::dispatch($event),
            'StatusNotification' => ProcessStatusNotificationJob::dispatch($event),
            default => Log::info("Unhandled OCPP message type: {$event->message_type}"),
        };
    }

    public function ingestStartTransaction(string $chargeBoxId, array $payload): OcppIncomingEvent
    {
        return $this->ingest('StartTransaction', $chargeBoxId, $payload, 'StartTransaction');
    }

    public function ingestStopTransaction(string $chargeBoxId, array $payload): OcppIncomingEvent
    {
        return $this->ingest('StopTransaction', $chargeBoxId, $payload, 'StopTransaction');
    }

    public function ingestStatusNotification(string $chargeBoxId, array $payload): OcppIncomingEvent
    {
        return $this->ingest('StatusNotification', $chargeBoxId, $payload, 'StatusNotification');
    }
}