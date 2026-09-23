<?php

namespace App\Jobs;

use App\Events\OcppStopTransactionReceived;
use App\Models\ChargingSession;
use App\Models\OcppIncomingEvent;
use App\Services\Ocpp\ChargingSessionStateMachine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStopTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public OcppIncomingEvent $event)
    {
    }

    public function handle(ChargingSessionStateMachine $stateMachine): void
    {
        try {
            $payload = $this->event->payload;

            $session = ChargingSession::where('steve_transaction_id', $payload['transactionId'] ?? null)
                ->orWhere('ocpp_tag', $payload['idTag'] ?? null)
                ->where('status', 'ACTIVE')
                ->first();

            if (!$session) {
                Log::warning('No matching session found for StopTransaction', [
                    'transaction_id' => $payload['transactionId'] ?? null,
                    'id_tag' => $payload['idTag'] ?? null,
                ]);
                $this->event->markProcessed();
                return;
            }

            $reason = $this->determineStopReason($payload);

            $stateMachine->stopSession($session, $reason);

            $this->event->markProcessed();

            event(new OcppStopTransactionReceived($session, $payload));

            Log::info('StopTransaction processed', [
                'session_id' => $session->id,
                'reason' => $reason,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process StopTransaction', [
                'charge_box_id' => $this->event->charge_box_id,
                'error' => $e->getMessage(),
            ]);
            $this->event->markFailed($e->getMessage());
        }
    }

    protected function determineStopReason(array $payload): string
    {
        if (!empty($payload['reason'])) {
            return strtolower((string) $payload['reason']);
        }

        if (!empty($payload['meterStop'])) {
            return 'normal';
        }

        return 'charger_initiated';
    }
}