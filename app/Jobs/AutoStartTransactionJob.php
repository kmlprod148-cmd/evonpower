<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\AutoRemoteStartService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Job pour démarrer automatiquement une transaction OCPP
 * 
 * Ce job est mis en queue pour:
 * - Traitement asynchrone des démarrages
 * - Gestion automatique des retry
 * - Éviter de bloquer les requêtes web
 * - Parallélisation des démarrages multiples
 */
class AutoStartTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives du job
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Timeout du job en secondes
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Délai avant retry en secondes
     *
     * @var int
     */
    public $backoff = 30;

    /**
     * ID de la réservation
     *
     * @var int
     */
    protected $reservationId;

    /**
     * Forcer le démarrage (bypass des vérifications temporelles)
     *
     * @var bool
     */
    protected $force;

    /**
     * Créer une nouvelle instance du job
     *
     * @param int $reservationId
     * @param bool $force
     * @return void
     */
    public function __construct(int $reservationId, bool $force = false)
    {
        $this->reservationId = $reservationId;
        $this->force = $force;
        
        // Utiliser la queue "high" pour les démarrages prioritaires
        $this->onQueue('high');
    }

    /**
     * Exécuter le job
     *
     * @param AutoRemoteStartService $autoStartService
     * @return void
     */
    public function handle(AutoRemoteStartService $autoStartService)
    {
        Log::info('AutoStartTransactionJob: Démarrage du job', [
            'reservation_id' => $this->reservationId,
            'attempt' => $this->attempts(),
            'force' => $this->force
        ]);

        try {
            // Charger la réservation
            $reservation = Reservation::with([
                'chargingPoint',
                'connector',
                'user',
                'ocppTag'
            ])->findOrFail($this->reservationId);

            // Traiter le démarrage automatique
            if ($this->force) {
                $result = $autoStartService->forceStartReservation($this->reservationId);
            } else {
                $result = $autoStartService->processReservation($reservation);
            }

            if ($result['success']) {
                Log::info('AutoStartTransactionJob: Transaction démarrée avec succès', [
                    'reservation_id' => $this->reservationId,
                    'result' => $result
                ]);

                // Envoyer notification à l'utilisateur (optionnel)
                $this->sendSuccessNotification($reservation);

            } elseif ($result['skipped']) {
                Log::info('AutoStartTransactionJob: Démarrage ignoré', [
                    'reservation_id' => $this->reservationId,
                    'reason' => $result['message']
                ]);

            } else {
                Log::warning('AutoStartTransactionJob: Échec du démarrage', [
                    'reservation_id' => $this->reservationId,
                    'error' => $result['message'],
                    'attempt' => $this->attempts()
                ]);

                // Si ce n'est pas la dernière tentative, le job sera automatiquement retry
                if ($this->attempts() < $this->tries) {
                    throw new Exception($result['message'] ?? 'Échec du démarrage automatique');
                } else {
                    // Dernière tentative échouée, notifier l'échec final
                    $this->sendFailureNotification($reservation, $result['message']);
                }
            }

        } catch (Exception $e) {
            Log::error('AutoStartTransactionJob: Exception', [
                'reservation_id' => $this->reservationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts()
            ]);

            // Relancer l'exception pour déclencher le retry automatique
            throw $e;
        }
    }

    /**
     * Gérer l'échec du job après toutes les tentatives
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        Log::error('AutoStartTransactionJob: Échec final après toutes les tentatives', [
            'reservation_id' => $this->reservationId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        try {
            // Charger la réservation pour notification
            $reservation = Reservation::find($this->reservationId);
            
            if ($reservation) {
                // Mettre à jour le statut de la réservation
                $reservation->update([
                    'status' => 'failed',
                    'notes' => ($reservation->notes ?? '') . "\n[AUTO] Échec démarrage automatique: " . $exception->getMessage()
                ]);

                // Envoyer notification d'échec final
                $this->sendFailureNotification($reservation, $exception->getMessage(), true);
            }

        } catch (Exception $e) {
            Log::error('AutoStartTransactionJob: Erreur lors de la gestion d\'échec', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envoyer une notification de succès à l'utilisateur
     *
     * @param Reservation $reservation
     * @return void
     */
    protected function sendSuccessNotification(Reservation $reservation)
    {
        try {
            if (!$reservation->user) {
                return;
            }

            // Notification simple (peut être étendu avec des notifications Laravel)
            Log::info('AutoStartTransactionJob: Notification succès envoyée', [
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'user_email' => $reservation->user->email ?? null
            ]);

            // TODO: Implémenter l'envoi d'email/SMS/push notification
            // $reservation->user->notify(new ChargingSessionStartedNotification($reservation));

        } catch (Exception $e) {
            Log::warning('AutoStartTransactionJob: Erreur envoi notification succès', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envoyer une notification d'échec à l'utilisateur
     *
     * @param Reservation $reservation
     * @param string $error
     * @param bool $isFinal
     * @return void
     */
    protected function sendFailureNotification(Reservation $reservation, string $error, bool $isFinal = false)
    {
        try {
            if (!$reservation->user) {
                return;
            }

            $message = $isFinal
                ? "Échec définitif du démarrage automatique de votre session de charge"
                : "Le démarrage automatique de votre session a échoué, nouvelles tentatives en cours";

            Log::warning('AutoStartTransactionJob: Notification échec envoyée', [
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'user_email' => $reservation->user->email ?? null,
                'error' => $error,
                'is_final' => $isFinal
            ]);

            // TODO: Implémenter l'envoi d'email/SMS/push notification
            // $reservation->user->notify(new ChargingSessionFailedNotification($reservation, $error, $isFinal));

        } catch (Exception $e) {
            Log::warning('AutoStartTransactionJob: Erreur envoi notification échec', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtenir le nom unique du job pour éviter les doublons
     *
     * @return string
     */
    public function uniqueId()
    {
        return "auto_start_transaction_{$this->reservationId}";
    }

    /**
     * Tags pour identifier le job dans les logs et monitoring
     *
     * @return array
     */
    public function tags()
    {
        return [
            'auto-start',
            'reservation:' . $this->reservationId,
            'ocpp'
        ];
    }
}

