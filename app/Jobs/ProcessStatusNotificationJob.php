<?php

namespace App\Jobs;

use App\Models\Connector;
use App\Models\OcppIncomingEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStatusNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public OcppIncomingEvent $event)
    {
    }

    public function handle(): void
    {
        try {
            $payload = $this->event->payload;
            $chargeBoxId = $this->event->charge_box_id;
            $connectorId = $payload['connectorId'] ?? 1;
            $status = $payload['status'] ?? 'Unknown';

            $connector = Connector::whereHas('chargingPoint', function ($q) use ($chargeBoxId) {
                $q->where('charge_box_id', $chargeBoxId);
            })
                ->where('connector_id', $connectorId)
                ->first();

            if ($connector) {
                $oldStatus = $connector->status;
                $connector->update(['status' => $status]);

                Log::info('Connector status updated via StatusNotification', [
                    'connector_id' => $connector->id,
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                ]);
            } else {
                Log::warning('No connector found for StatusNotification', [
                    'charge_box_id' => $chargeBoxId,
                    'connector_id' => $connectorId,
                    'status' => $status,
                ]);
            }

            $this->event->markProcessed();

        } catch (\Exception $e) {
            Log::error('Failed to process StatusNotification', [
                'charge_box_id' => $this->event->charge_box_id,
                'error' => $e->getMessage(),
            ]);
            $this->event->markFailed($e->getMessage());
        }
    }
}