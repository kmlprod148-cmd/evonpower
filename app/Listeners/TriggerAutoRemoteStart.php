<?php

namespace App\Listeners;

use App\Events\ReservationApproved;
use App\Events\ReservationCreated;
use App\Jobs\AutoStartTransactionJob;
use App\Services\AutoRemoteStartService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Listener pour déclencher le démarrage automatique des transactions
 * 
 * Ce listener réagit aux événements de réservation et décide si un
 * démarrage automatique doit être lancé immédiatement ou programmé.
 */
class TriggerAutoRemoteStart implements ShouldQueue
{
    use InteractsWithQueue;

    protected AutoRemoteStartService $autoStartService;

    /**
     * Créer une nouvelle instance du listener
     */
    public function __construct(AutoRemoteStartService $autoStartService)
    {
        $this->autoStartService = $autoStartService;
    }

    /**
     * Gérer l'événement ReservationApproved
     */
    public function handle($event)
    {
        if ($event instanceof ReservationApproved) {
            $this->handleReservationApproved($event);
        } elseif ($event instanceof ReservationCreated) {
            $this->handleReservationCreated($event);
        }
    }

    /**
     * Gérer une réservation approuvée
     */
    protected function handleReservationApproved(ReservationApproved $event)
    {
        $reservation = $event->reservation;

        Log::info('TriggerAutoRemoteStart: Réservation approuvée détectée', [
            'reservation_id' => $reservation->id,
            'approval_method' => $event->approvalMethod,
            'start_time' => $reservation->start_time?->toISOString()
        ]);

        // Vérifier si le démarrage automatique est activé
        if (!config('auto-remote-start.enabled')) {
            Log::debug('TriggerAutoRemoteStart: Démarrage automatique désactivé');
            return;
        }

        // Décider si on démarre immédiatement ou on programme
        $startMode = config('auto-remote-start.mode.start_mode', 'auto');
        $now = now();
        $startTime = $reservation->start_time ? Carbon::parse($reservation->start_time) : null;

        if (
            $startMode === 'scheduled'
            && $startTime
            && $startTime->isFuture()
            && $reservation->isApproved()
        ) {
            $gracePeriod = config('auto-remote-start.grace_period_minutes', 5);
            $scheduledTime = $startTime->copy()->subMinutes($gracePeriod);

            Log::info('TriggerAutoRemoteStart: Réservation future planifiée', [
                'reservation_id' => $reservation->id,
                'scheduled_for' => $scheduledTime->toISOString(),
            ]);

            AutoStartTransactionJob::dispatch($reservation->id)
                ->delay($scheduledTime);

            return;
        }

        // Vérifier si la réservation est éligible pour un démarrage immédiat
        $eligibility = $this->autoStartService->checkReservationEligibility($reservation);

        if (!$eligibility['eligible']) {
            if (
                $startMode === 'immediate'
                && ($eligibility['code'] ?? null) === 'INVALID_START_TIME'
                && $reservation->isApproved()
            ) {
                Log::info('TriggerAutoRemoteStart: Mode immédiat - bypass contrôle horaire', [
                    'reservation_id' => $reservation->id,
                ]);

                AutoStartTransactionJob::dispatch($reservation->id, true)
                    ->delay(now()->addSeconds(5));

                return;
            }

            Log::info('TriggerAutoRemoteStart: Réservation non éligible', [
                'reservation_id' => $reservation->id,
                'reason' => $eligibility['reason'],
                'code' => $eligibility['code']
            ]);
            return;
        }

        if ($startMode === 'immediate') {
            // Mode immédiat: démarrer dès approbation
            Log::info('TriggerAutoRemoteStart: Mode immédiat - Dispatch du job', [
                'reservation_id' => $reservation->id
            ]);
            
            AutoStartTransactionJob::dispatch($reservation->id, true)
                ->delay(now()->addSeconds(5)); // Léger délai pour éviter les conflits

        } elseif ($startMode === 'scheduled' && $startTime) {
            // Mode programmé: démarrer à l'heure exacte
            $gracePeriod = config('auto-remote-start.grace_period_minutes', 5);
            $scheduledTime = $startTime->copy()->subMinutes($gracePeriod);

            if ($scheduledTime->isFuture()) {
                Log::info('TriggerAutoRemoteStart: Mode programmé - Job schedulé', [
                    'reservation_id' => $reservation->id,
                    'scheduled_for' => $scheduledTime->toISOString()
                ]);
                
                AutoStartTransactionJob::dispatch($reservation->id)
                    ->delay($scheduledTime);
            } else {
                // L'heure est passée, démarrer maintenant
                Log::info('TriggerAutoRemoteStart: Heure passée - Dispatch immédiat', [
                    'reservation_id' => $reservation->id
                ]);
                
                AutoStartTransactionJob::dispatch($reservation->id)
                    ->delay(now()->addSeconds(5));
            }

        } else {
            // Mode auto: laisser le scheduler gérer
            Log::info('TriggerAutoRemoteStart: Mode auto - Géré par le scheduler', [
                'reservation_id' => $reservation->id
            ]);
        }
    }

    /**
     * Gérer une nouvelle réservation créée
     */
    protected function handleReservationCreated(ReservationCreated $event)
    {
        $reservation = $event->reservation;

        // Si la réservation est déjà approuvée et payée à la création
        if ($reservation->isApproved()) {
            Log::info('TriggerAutoRemoteStart: Nouvelle réservation déjà approuvée', [
                'reservation_id' => $reservation->id
            ]);

            // Créer un événement ReservationApproved synthétique
            $approvedEvent = new ReservationApproved(
                $reservation,
                'system',
                'automatic'
            );

            $this->handleReservationApproved($approvedEvent);
        }
    }

    /**
     * Gérer l'échec du listener
     */
    public function failed($event, $exception)
    {
        Log::error('TriggerAutoRemoteStart: Échec du listener', [
            'event' => get_class($event),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}

