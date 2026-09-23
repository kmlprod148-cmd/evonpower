<?php

namespace App\Listeners;

use App\Events\ChargingSessionCompleted;
use App\Jobs\ProcessHierarchicalTransactionsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ChargingSessionCompletedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ChargingSessionCompleted $event): void
    {
        try {
            $session = $event->session;
            
            Log::info('Session de charge terminée - Déclenchement de la transaction hiérarchique', [
                'session_id' => $session->id,
                'charging_point_id' => $session->charging_point_id,
                'status' => $session->status,
                'energy_delivered' => $session->energy_delivered,
                'duration' => $session->duration
            ]);

            // Dispatcher le job de traitement de la transaction hiérarchique
            ProcessHierarchicalTransactionsJob::dispatch($session->charging_point_id, $session->id)
                ->delay(now()->addSeconds(30)); // Attendre 30 secondes pour s'assurer que la session est bien terminée

        } catch (\Exception $e) {
            Log::error('Erreur dans le listener de session terminée', [
                'session_id' => $event->session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ChargingSessionCompleted $event, \Throwable $exception): void
    {
        Log::error('Échec du listener de session terminée', [
            'session_id' => $event->session->id,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
