<?php

namespace App\Jobs;

use App\Models\ChargingSession;
use App\Services\OcppBusinessService;
use App\Services\StevePostpaidPaymentService;
use App\Services\TransactionStatusSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Job pour arrêter une session de charge
 * 
 * CE JOB NE DOIT PAS ÊTRE APPELÉ DEPUIS UN CONTROLLER
 * Il doit être dispatché via la queue ou par le scheduled command
 * 
 * FLUX:
 * 1. Vérifier que la session est bien active
 * 2. Appel SteVe RemoteStop
 * 3. Mise à jour ChargingSession
 * 4. Mise à jour Reservation status → FINISHED
 * 5. Calcul du coût final et débit wallet
 * 6. Unlock connector (optionnel)
 */
class StopChargingSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = [10, 30, 60];

    protected int $sessionId;
    protected ?string $stopReason;

    /**
     * Create a new job instance.
     */
    public function __construct(int $sessionId, string $stopReason = 'Manual')
    {
        $this->sessionId = $sessionId;
        $this->stopReason = $stopReason;
    }

    /**
     * Execute the job.
     */
    public function handle(OcppBusinessService $ocppService): void
    {
        Log::info("StopChargingSessionJob: Début", [
            'session_id' => $this->sessionId,
            'reason' => $this->stopReason,
        ]);

        DB::beginTransaction();

        try {
            // 1. Charger la session avec ses relations
            $session = ChargingSession::with([
                'reservation',
                'chargingPoint',
                'user'
            ])->findOrFail($this->sessionId);

            // 2. Vérifier que la session est bien active
            if (!in_array($session->status, [
                ChargingSession::STATUS_ACTIVE,
                ChargingSession::STATUS_PENDING,
                ChargingSession::STATUS_INITIATING,
                ChargingSession::STATUS_IN_PROGRESS,
            ], true)) {
                Log::warning("StopChargingSessionJob: Session déjà terminée", [
                    'session_id' => $this->sessionId,
                    'status' => $session->status,
                ]);
                DB::commit();
                return;
            }

            // 3. APPEL STEVE REMOTE STOP
            Log::info("StopChargingSessionJob: Appel RemoteStop", [
                'session_id' => $this->sessionId,
                'steve_transaction_id' => $session->steve_transaction_id,
            ]);

            $result = $ocppService->remoteStopCharging($session);

            if (!$result['success']) {
                // Même si SteVe échoue, on peut quand même terminer la session côté Laravel
                Log::warning("StopChargingSessionJob: SteVe a échoué, mais on termine quand même", [
                    'session_id' => $this->sessionId,
                    'error' => $result['error'] ?? 'Inconnu',
                ]);
            }

            // 4. Calculer l'énergie consommée et le coût
            // Option smart : récupérer les données depuis Steve (source de vérité)
            $consumedEnergy = 0;
            $actualCost = 0;
            $duration = (int) now()->diffInMinutes($session->started_at);

            $postpaidHandledBySteve = false;
            if ($session->payment_mode === 'postpaid' && $session->reservation && config('steve.postpaid_use_steve_data', true)) {
                $delay = config('steve.postpaid_fetch_delay', 3);
                if ($delay > 0) {
                    sleep(min($delay, 5)); // Attendre que la borne envoie StopTransaction à Steve
                }
                $steveService = app(StevePostpaidPaymentService::class);
                $steveResult = $steveService->finalizeFromSteve($session);
                if ($steveResult['success']) {
                    $consumedEnergy = $steveResult['energy_kwh'] ?? 0;
                    $actualCost = $steveResult['actual_cost'] ?? 0;
                    $postpaidHandledBySteve = true;
                    Log::info("StopChargingSessionJob: Paiement postpayé via Steve", [
                        'session_id' => $this->sessionId,
                        'actual_cost' => $actualCost,
                    ]);
                }
            }

            if (!$postpaidHandledBySteve) {
                $consumedEnergy = $ocppService->calculateConsumedEnergy($session);
                $actualCost = $ocppService->calculateSessionCost($session);

                Log::info("StopChargingSessionJob: Calculs", [
                    'session_id' => $this->sessionId,
                    'consumed_energy' => $consumedEnergy,
                    'actual_cost' => $actualCost,
                    'duration' => $duration,
                ]);

                // 5. METTRE À JOUR LA SESSION
                // Quand SteVe échoue, $result['transaction']['stopValue'] n'existe pas.
                // Utiliser la valeur déjà stockée (meter_stop) si disponible ; sinon meter_start.
                // Ne jamais écraser une valeur existante avec meter_start (consommation zéro erronée).
                $meterStop = $result['success']
                    ? ($result['transaction']['stopValue'] ?? $session->meter_stop ?? $session->meter_start)
                    : ($session->meter_stop ?? $session->meter_start);

                $session->update([
                    'status' => ChargingSession::STATUS_COMPLETED,
                    'stopped_at' => now(),
                    'actual_energy' => $consumedEnergy,
                    'actual_cost' => $actualCost,
                    'actual_duration' => $duration,
                    'stop_reason' => $this->stopReason,
                    'meter_stop' => $meterStop,
                    'steve_stop_response' => $result,
                ]);

                // 6. METTRE À JOUR LA RÉSERVATION
                if ($session->reservation) {
                    $session->reservation->update([
                        'status' => 'completed',
                        'actual_energy' => $consumedEnergy,
                        'actual_cost' => $actualCost,
                        'actual_duration' => $duration,
                    ]);
                }

                // 8. DÉBIT POSTPAYÉ (flux classique)
                if ($session->payment_mode === 'postpaid' && $session->reservation) {
                    $this->processPostpaidDebit($session, $actualCost);
                }
            }

            // 7. Mettre à jour le charging point (toujours)
            $session->chargingPoint->update([
                'status' => 'available',
                'current_session_id' => null,
                'last_session_end' => now(),
            ]);

            // 8. DÉBIT PRÉPAYÉ (remboursement ou complément)
            if ($session->payment_mode === 'prepaid') {
                $this->processPrePaidDebit($session, $actualCost);
            }

            // 9. Déverrouiller le connecteur (optionnel mais recommandé)
            if ($session->reservation && $session->reservation->connector) {
                $unlocked = $ocppService->unlockConnector($session->reservation->connector);
                Log::info("StopChargingSessionJob: Déverrouillage connecteur", [
                    'session_id' => $this->sessionId,
                    'unlocked' => $unlocked,
                ]);
            }

            DB::commit();

            Log::info("StopChargingSessionJob: Succès", [
                'session_id' => $this->sessionId,
                'actual_cost' => $actualCost,
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error("StopChargingSessionJob: Erreur", [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw pour retry
        }
    }

    /**
     * Traiter le débit pour une session prépayée
     */
    protected function processPrePaidDebit(ChargingSession $session, float $actualCost): void
    {
        try {
            $user = $session->user;
            if (!$user) {
                return;
            }

            // Calculer le remboursement si le coût réel est inférieur au montant prépayé
            $prepaidAmount = $session->prepaid_amount ?? 0;
            
            if ($actualCost < $prepaidAmount) {
                $refundAmount = $prepaidAmount - $actualCost;
                
                // Rembourser la différence au wallet
                // TODO: Implémenter la logique de remboursement wallet
                Log::info("StopChargingSessionJob: Remboursement calculé", [
                    'session_id' => $session->id,
                    'prepaid' => $prepaidAmount,
                    'actual' => $actualCost,
                    'refund' => $refundAmount,
                ]);

                $session->update(['refund_amount' => $refundAmount]);
            } elseif ($actualCost > $prepaidAmount) {
                // Débit supplémentaire nécessaire
                $additionalCharge = $actualCost - $prepaidAmount;
                
                Log::info("StopChargingSessionJob: Débit supplémentaire nécessaire", [
                    'session_id' => $session->id,
                    'prepaid' => $prepaidAmount,
                    'actual' => $actualCost,
                    'additional' => $additionalCharge,
                ]);

                // TODO: Implémenter la logique de débit supplémentaire
            }

        } catch (Exception $e) {
            Log::error("StopChargingSessionJob: Erreur lors du traitement du paiement", [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            // Ne pas throw, la session doit quand même se terminer
        }
    }

    /**
     * Débiter le wallet pour une session postpayée (débit à la fin de la session)
     */
    protected function processPostpaidDebit(ChargingSession $session, float $actualCost): void
    {
        try {
            $reservation = $session->reservation;
            if (!$reservation || !$reservation->user_id) {
                Log::warning("StopChargingSessionJob: Réservation ou user manquant pour postpayé", [
                    'session_id' => $session->id,
                ]);
                return;
            }

            $user = $reservation->user;
            if (!$user) {
                Log::warning("StopChargingSessionJob: Utilisateur introuvable pour débit postpayé", [
                    'session_id' => $session->id,
                    'user_id' => $reservation->user_id,
                ]);
                return;
            }
            $wallet = $user->getOrCreateWallet();

            if (!$wallet->hasSufficientBalance($actualCost)) {
                Log::error("StopChargingSessionJob: Solde insuffisant pour débit postpayé", [
                    'session_id' => $session->id,
                    'required' => $actualCost,
                    'balance' => $wallet->balance,
                ]);
                $reservation->update(['payment_status' => 'FAILED']);
                return;
            }

            $transaction = $wallet->debit(
                $actualCost,
                "Paiement postpayé - Session #{$session->id}",
                [
                    'session_id' => $session->id,
                    'reservation_id' => $reservation->id,
                    'payment_mode' => 'postpaid',
                    'type' => 'postpaid_payment',
                ]
            );

            $session->update([
                'wallet_transaction_id' => $transaction->id,
            ]);

            $reservation->update([
                'actual_cost' => $actualCost,
                'payment_status' => 'PAID',
            ]);

            // Synchroniser le statut de la transaction principale (completed) - sans intervention propriétaire
            $mainTransaction = $reservation->transaction;
            if ($mainTransaction && in_array($mainTransaction->status, ['pending'])) {
                app(TransactionStatusSyncService::class)->syncTransactionStatusIfPaid($mainTransaction);
            }

            Log::info("StopChargingSessionJob: Débit postpayé effectué", [
                'session_id' => $session->id,
                'actual_cost' => $actualCost,
                'wallet_transaction_id' => $transaction->id,
            ]);
        } catch (Exception $e) {
            Log::error("StopChargingSessionJob: Erreur débit postpayé", [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("StopChargingSessionJob: Échec définitif", [
            'session_id' => $this->sessionId,
            'error' => $exception->getMessage(),
        ]);

        // Notification admin
        // TODO: Envoyer notification
    }
}
