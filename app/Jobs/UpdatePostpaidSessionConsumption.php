<?php

namespace App\Jobs;

use App\Models\ChargingSession;
use App\Services\PostpaidChargingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdatePostpaidSessionConsumption implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sessionId;

    /**
     * Create a new job instance.
     */
    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Execute the job.
     */
    public function handle(PostpaidChargingService $postpaidService): void
    {
        try {
            $session = ChargingSession::find($this->sessionId);

            if (!$session) {
                Log::warning('Session introuvable pour mise à jour de consommation', [
                    'session_id' => $this->sessionId,
                ]);
                return;
            }

            // Ne mettre à jour que les sessions en cours
            if ($session->status !== 'in_progress') {
                return;
            }

            // Mettre à jour la consommation
            $result = $postpaidService->updateRealTimeConsumption($session);

            if (!$result['success']) {
                Log::warning('Échec de la mise à jour de la consommation', [
                    'session_id' => $this->sessionId,
                    'error' => $result['message'] ?? 'Erreur inconnue',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur dans le job de mise à jour de consommation', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Relancer le job en cas d'erreur temporaire
            throw $e;
        }
    }
}

