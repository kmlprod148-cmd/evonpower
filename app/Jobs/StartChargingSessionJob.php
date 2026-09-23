<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Models\ChargingSession;
use App\Models\OcppTag;
use App\Services\OcppBusinessService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Job pour démarrer une session de charge
 * 
 * CE JOB NE DOIT PAS ÊTRE APPELÉ DEPUIS UN CONTROLLER
 * Il doit être dispatché via la queue (artisan queue:work)
 * ou via un scheduled job (artisan schedule:run)
 * 
 * FLUX:
 * 1. Vérifications de sécurité (CRITICAL)
 * 2. Appel SteVe RemoteStart
 * 3. Création ChargingSession
 * 4. Mise à jour Reservation status → RUNNING
 */
class StartChargingSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes max
    public $tries = 3; // 3 tentatives
    public $backoff = [10, 30, 60]; // Délais entre tentatives

    protected int $reservationId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $reservationId)
    {
        $this->reservationId = $reservationId;
    }

    /**
     * Execute the job.
     */
    public function handle(OcppBusinessService $ocppService): void
    {
        Log::info("StartChargingSessionJob: Début", [
            'reservation_id' => $this->reservationId,
        ]);

        DB::beginTransaction();

        try {
            // 1. Charger la réservation avec ses relations
            $reservation = Reservation::with([
                'user',
                'chargingPoint',
                'connector',
                'pricingPlan',
                'ocppTag'
            ])->findOrFail($this->reservationId);

            // 2. VÉRIFICATIONS DE SÉCURITÉ (CRITICAL)
            Log::info("StartChargingSessionJob: Vérifications de sécurité", [
                'reservation_id' => $this->reservationId,
            ]);

            $canStart = $ocppService->canStartReservation($reservation);
            
            if (!$canStart['can_start']) {
                Log::warning("StartChargingSessionJob: Impossible de démarrer", [
                    'reservation_id' => $this->reservationId,
                    'errors' => $canStart['errors'],
                ]);

                $reservation->update([
                    'status' => 'canceled',
                    'notes' => 'Annulé: ' . implode(', ', $canStart['errors']),
                ]);

                DB::commit();
                return;
            }

            // 3. Vérifier qu'il n'y a pas déjà une session active pour cette réservation
            $existingSession = ChargingSession::where('reservation_id', $reservation->id)
                ->whereIn('status', [
                    ChargingSession::STATUS_PENDING,
                    ChargingSession::STATUS_INITIATING,
                    ChargingSession::STATUS_ACTIVE,
                    ChargingSession::STATUS_IN_PROGRESS,
                ])
                ->first();

            if ($existingSession) {
                Log::warning("StartChargingSessionJob: Session déjà active", [
                    'reservation_id' => $this->reservationId,
                    'session_id' => $existingSession->id,
                ]);
                DB::commit();
                return;
            }

            // 4. APPEL STEVE REMOTE START
            Log::info("StartChargingSessionJob: Appel RemoteStart", [
                'reservation_id' => $this->reservationId,
            ]);

            $result = $ocppService->remoteStartCharging($reservation);

            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Échec du démarrage via SteVe');
            }

            // 5. CRÉER LA SESSION DE CHARGE (payment_mode selon groupe: prépayé ou postpayé)
            $paymentMode = $reservation->payment_mode ?? 'prepaid';
            $session = ChargingSession::create([
                'reservation_id' => $reservation->id,
                'charging_point_id' => $reservation->charging_point_id,
                'user_id' => $reservation->user_id,
                'steve_transaction_id' => $result['transaction']['transactionId'] ?? null,
                'connector_id' => $reservation->connector->connector_id,
                'ocpp_tag' => $result['ocpp_tag'] ?? null,
                'status' => ChargingSession::STATUS_ACTIVE,
                'started_at' => now(),
                'payment_mode' => $paymentMode,
                'prepaid_amount' => $reservation->isPrepaid() ? ($reservation->prepaid_amount ?? $reservation->estimated_cost) : null,
                'estimated_cost' => $reservation->estimated_cost,
                'estimated_energy' => $reservation->estimated_energy,
                'estimated_duration' => $reservation->estimated_duration,
                'meter_start' => $result['transaction']['startValue'] ?? 0,
                'steve_response' => $result,
            ]);

            Log::info("StartChargingSessionJob: Session créée", [
                'reservation_id' => $this->reservationId,
                'session_id' => $session->id,
                'steve_transaction_id' => $session->steve_transaction_id,
            ]);

            // 6. METTRE À JOUR LA RÉSERVATION
            $reservation->update([
                'status' => 'active', // RUNNING dans votre logique
            ]);

            // 7. Mettre à jour le charging point
            $reservation->chargingPoint->update([
                'status' => 'charging',
                'current_session_id' => $session->id,
                'last_session_start' => now(),
            ]);

            // 8. Incrémenter le compteur du tag OCPP
            if ($reservation->ocpp_tag_id) {
                $ocppTag = OcppTag::find($reservation->ocpp_tag_id);
                $ocppTag?->incrementSessions();
            }

            DB::commit();

            Log::info("StartChargingSessionJob: Succès", [
                'reservation_id' => $this->reservationId,
                'session_id' => $session->id,
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error("StartChargingSessionJob: Erreur", [
                'reservation_id' => $this->reservationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mettre à jour la réservation avec l'erreur
            try {
                $reservation = Reservation::find($this->reservationId);
                $reservation?->update([
                    'status' => 'canceled',
                    'notes' => "Erreur au démarrage: {$e->getMessage()}",
                ]);
            } catch (Exception $updateException) {
                Log::error("StartChargingSessionJob: Impossible de mettre à jour la réservation", [
                    'error' => $updateException->getMessage(),
                ]);
            }

            throw $e; // Re-throw pour retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("StartChargingSessionJob: Échec définitif", [
            'reservation_id' => $this->reservationId,
            'error' => $exception->getMessage(),
        ]);

        // Notification admin ou utilisateur
        // TODO: Envoyer notification
    }
}
