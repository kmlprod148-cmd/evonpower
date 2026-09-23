<?php

namespace App\Jobs;

use App\Models\OcppCommandOutbox;
use App\Services\Ocpp\OcppCommandOutboxService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOcppCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public OcppCommandOutbox $command)
    {
    }

    public function handle(OcppCommandOutboxService $service): void
    {
        try {
            Log::channel('ocpp')->info('Sending OCPP command', [
                'command' => $this->command->command,
                'charge_box_id' => $this->command->charge_box_id,
                'correlation_id' => $this->command->correlation_id,
            ]);

            $response = $this->sendToCharger();

            $service->markSent($this->command->id, $response);
            $service->markResponded($this->command->id, $response);

        } catch (\Exception $e) {
            Log::channel('ocpp')->error('Failed to send OCPP command', [
                'command' => $this->command->command,
                'charge_box_id' => $this->command->charge_box_id,
                'error' => $e->getMessage(),
            ]);

            if ($this->command->canRetry()) {
                $service->incrementRetry($this->command->id);
                $this->release($this->backoff);
            } else {
                $service->markFailed($this->command->id, $e->getMessage());
            }
        }
    }

    protected function sendToCharger(): array
    {
        // This would call the SteVe/OCPP API
        // For now return a mock response
        return [
            'status' => 'Accepted',
            'timestamp' => now()->toISOString(),
        ];
    }
}