<?php

namespace App\Listeners;

use App\Events\PostpaidSessionStarted;
use App\Jobs\UpdatePostpaidSessionConsumption;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdatePostpaidSessionConsumptionListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PostpaidSessionStarted $event): void
    {
        try {
            $session = $event->session;

            // Planifier la première mise à jour après 10 secondes
            UpdatePostpaidSessionConsumption::dispatch($session->id)
                ->delay(now()->addSeconds(10));

            // Note: Les mises à jour périodiques sont gérées par la commande planifiée
            // 'postpaid:update-all-sessions' qui s'exécute toutes les 30 secondes
            // Cela évite de créer trop de jobs en queue

            Log::info('Mise à jour initiale planifiée pour session postpayée', [
                'session_id' => $session->session_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans le listener de mise à jour de consommation', [
                'session_id' => $event->session->session_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

