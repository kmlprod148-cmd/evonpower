<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Enums\ReservationStatus;
use App\Services\ReservationLockingService;
use App\Services\ReservationSessionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Job pour traiter les réservations expirées
 * 
 * Ce job est exécuté périodiquement (via scheduler) pour :
 * - Détecter les réservations expirées
 * - Déverrouiller les connecteurs
 * - Annuler les sessions en attente
 * - Libérer les ressources
 * 
 * FLUX:
 * 1. Récupérer toutes les réservations expirées non traitées
 * 2. Pour chaque réservation:
 *    - Vérifier qu'elle n'a pas de session active
 *    - Déverrouiller le connecteur
 *    - Mettre à jour le statut
 *    - Logger l'opération
 */
class ProcessReservationExpirationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes max
    public $tries = 3;
    public $backoff = [30, 60, 120];

    protected int $batchSize;
    protected bool $forceUnlock;

    public function __construct(int $batchSize = 100, bool $forceUnlock = false)
    {
        $this->batchSize = $batchSize;
        $this->forceUnlock = $forceUnlock;
        
        // Utiliser une queue dédiée pour les opérations de maintenance
        $this->onQueue('reservations');
    }

    /**
     * Execute the job.
     */
    public function handle(
        ReservationLockingService $lockingService,
        ReservationSessionService $sessionService
    ): void
    {
        Log::info('ProcessReservationExpirationJob: Début du traitement', [
            'batch_size' => $this->batchSize,
            'force_unlock' => $this->forceUnlock,
        ]);

        $startTime = microtime(true);
        $stats = [
            'processed' => 0,
            'unlocked' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        // Récupérer les réservations expirées
        $expiredReservations = $this->getExpiredReservations();

        foreach ($expiredReservations as $reservation) {
            try {
                $result = $this->processExpiredReservation($reservation, $lockingService, $sessionService);
                
                if ($result['success']) {
                    $stats['processed']++;
                    if ($result['unlocked']) {
                        $stats['unlocked']++;
                    }
                } else {
                    $stats['skipped']++;
                }
            } catch (Exception $e) {
                $stats['errors']++;
                Log::error('ProcessReservationExpirationJob: Erreur traitement réservation', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Limiter le nombre de traitées par exécution
            if ($stats['processed'] >= $this->batchSize) {
                Log::info('ProcessReservationExpirationJob: Limite de batch atteinte', [
                    'processed' => $stats['processed'],
                ]);
                break;
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        Log::info('ProcessReservationExpirationJob: Fin du traitement', [
            'duration_seconds' => $duration,
            'stats' => $stats,
        ]);
    }

    /**
     * Récupère les réservations expirées
     */
    protected function getExpiredReservations(): \Illuminate\Database\Eloquent\Collection
    {
        return Reservation::whereIn('status', [
            ReservationStatus::CONFIRMED,
            ReservationStatus::PENDING_CONFIRMATION,
            ReservationStatus::ACTIVE,
        ])
        ->where(function ($query) {
            $query->where('expires_at', '<', now())
                ->orWhere(function ($subQuery) {
                    $subQuery->whereNotNull('end_time')
                        ->where('end_time', '<', now());
                });
        })
        ->where(function ($query) {
            $query->whereNull('expiration_processed_at')
                ->orWhere('expiration_processed_at', '<', now()->subHour());
        })
        ->where('connector_locked', true)
        ->with(['chargingPoint', 'connector', 'user', 'chargingSessions'])
        ->limit($this->batchSize)
        ->get();
    }

    /**
     * Traite une réservation expirée
     */
    protected function processExpiredReservation(
        Reservation $reservation,
        ReservationLockingService $lockingService,
        ReservationSessionService $sessionService
    ): array
    {
        Log::info('ProcessReservationExpirationJob: Traitement réservation expirée', [
            'reservation_id' => $reservation->id,
            'status' => $reservation->status->value,
            'end_time' => $reservation->end_time?->toIso8601String(),
            'expires_at' => $reservation->expires_at?->toIso8601String(),
        ]);

        // Vérifier s'il y a une session active
        $activeSession = $reservation->chargingSessions()
            ->whereIn('status', [\App\Models\ChargingSession::STATUS_ACTIVE, \App\Models\ChargingSession::STATUS_INITIATING])
            ->first();

        if ($activeSession) {
            Log::info('ProcessReservationExpirationJob: Session active détectée', [
                'reservation_id' => $reservation->id,
                'session_id' => $activeSession->id,
                'status' => $activeSession->status,
            ]);

            // Ne pas déverrouiller si session active
            return [
                'success' => true,
                'unlocked' => false,
                'message' => 'Session active en cours',
            ];
        }

        DB::beginTransaction();

        try {
            // Déverrouiller le connecteur
            $unlockResult = $lockingService->unlockConnector($reservation, 'expiration');

            // Mettre à jour la réservation
            $reservation->update([
                'status' => ReservationStatus::CANCELED,
                'expiration_processed_at' => now(),
                'notes' => ($reservation->notes ?? '') . "\n[SYSTEM] Réservation expirée et connecteur libéré",
            ]);

            // Annuler les sessions en attente
            $reservation->chargingSessions()
                ->where('status', \App\Models\ChargingSession::STATUS_PENDING)
                ->update([
                    'status' => \App\Models\ChargingSession::STATUS_TIMEOUT,
                    'stopped_at' => now(),
                    'stop_reason' => 'Reservation expired',
                ]);

            DB::commit();

            Log::info('ProcessReservationExpirationJob: Réservation expirée traitée', [
                'reservation_id' => $reservation->id,
                'unlocked' => $unlockResult['success'],
            ]);

            return [
                'success' => true,
                'unlocked' => $unlockResult['success'],
                'message' => 'Réservation expirée traitée',
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('ProcessReservationExpirationJob: Erreur traitement', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'unlocked' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('ProcessReservationExpirationJob: Échec définitif', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Notification admin
        // TODO: Notifier l'administrateur
    }
}
