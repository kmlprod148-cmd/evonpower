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
use Exception;

/**
 * Job pour traiter les expirations de confirmation de paiement crédit solde
 * 
 * Ce job vérifie les réservations en attente de confirmation de paiement
 * et les annule si le délai de 5 minutes est dépassé.
 * 
 * IL EST EXÉCUTÉ PLUS FRÉQUEMMENT (toutes les 30 secondes)
 * que ProcessReservationExpirationJob (toutes les 5 minutes)
 */
class ProcessCreditPaymentTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes max
    public $tries = 3;
    public $backoff = [10, 20, 30];

    protected int $batchSize;

    public function __construct(int $batchSize = 50)
    {
        $this->batchSize = $batchSize;
        $this->onQueue('high'); // Haute priorité
    }

    /**
     * Execute the job.
     */
    public function handle(
        ReservationLockingService $lockingService,
        ReservationSessionService $sessionService
    ): void
    {
        Log::info('ProcessCreditPaymentTimeoutJob: Début du traitement');

        $startTime = microtime(true);
        $stats = [
            'checked' => 0,
            'expired' => 0,
            'cancelled' => 0,
            'errors' => 0,
        ];

        // Récupérer les réservations en attente de confirmation
        $pendingConfirmations = $this->getPendingConfirmations();

        foreach ($pendingConfirmations as $reservation) {
            try {
                $stats['checked']++;

                if ($sessionService->isConfirmationExpired($reservation)) {
                    $stats['expired']++;
                    
                    $result = $this->cancelForTimeout($reservation, $lockingService);
                    
                    if ($result['success']) {
                        $stats['cancelled']++;
                    }
                }
            } catch (Exception $e) {
                $stats['errors']++;
                Log::error('ProcessCreditPaymentTimeoutJob: Erreur', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($stats['checked'] >= $this->batchSize) {
                break;
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        Log::info('ProcessCreditPaymentTimeoutJob: Fin', [
            'duration' => $duration,
            'stats' => $stats,
        ]);
    }

    /**
     * Récupère les réservations en attente de confirmation
     */
    protected function getPendingConfirmations(): \Illuminate\Database\Eloquent\Collection
    {
        return Reservation::where('status', ReservationStatus::PENDING_CONFIRMATION)
            ->whereNotNull('payment_confirmation_expires_at')
            ->where('payment_confirmation_expires_at', '<', now())
            ->where('connector_locked', true)
            ->with(['chargingPoint', 'connector', 'user'])
            ->limit($this->batchSize)
            ->get();
    }

    /**
     * Annule une réservation pour timeout de confirmation
     */
    protected function cancelForTimeout(Reservation $reservation, ReservationLockingService $lockingService): array
    {
        Log::info('ProcessCreditPaymentTimeoutJob: Annulation pour timeout', [
            'reservation_id' => $reservation->id,
            'expires_at' => $reservation->payment_confirmation_expires_at?->toIso8601String(),
        ]);

        DB::beginTransaction();

        try {
            // Déverrouiller le connecteur
            $unlockResult = $lockingService->unlockConnector($reservation, 'payment_confirmation_timeout');

            // Mettre à jour la réservation
            $reservation->update([
                'status' => ReservationStatus::CANCELED,
                'expiration_processed_at' => now(),
                'notes' => ($reservation->notes ?? '') . 
                    "\n[SYSTEM] Réservation annulée: délai de confirmation dépassé (5 minutes)",
            ]);

            DB::commit();

            // Envoyer notification à l'utilisateur
            // TODO: $reservation->user->notify(new PaymentConfirmationExpiredNotification($reservation));

            Log::info('ProcessCreditPaymentTimeoutJob: Annulation réussie', [
                'reservation_id' => $reservation->id,
                'unlocked' => $unlockResult['success'],
            ]);

            return [
                'success' => true,
                'message' => 'Réservation annulée pour timeout',
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('ProcessCreditPaymentTimeoutJob: Erreur annulation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('ProcessCreditPaymentTimeoutJob: Échec définitif', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
