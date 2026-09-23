<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ChargingSession;
use App\Models\Connector;
use App\DTO\OCPP\RemoteStartRequestDTO;
use App\Enums\ReservationStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

/**
 * Service de gestion des sessions de charge pour les réservations
 * 
 * Ce service gère :
 * - L'initiation automatique de session après réservation
 * - La validation des paiements par carte
 * - La confirmation de paiement pour crédit solde avec timeout
 * - La synchronisation entre paiement et démarrage de session
 */
class ReservationSessionService
{
    protected OcppOperationsService $ocppService;
    protected ReservationLockingService $lockingService;
    protected int $paymentConfirmationTimeoutMinutes;

    public function __construct(
        OcppOperationsService $ocppService,
        ReservationLockingService $lockingService
    ) {
        $this->ocppService = $ocppService;
        $this->lockingService = $lockingService;
        $this->paymentConfirmationTimeoutMinutes = config('reservation.payment_confirmation_timeout_minutes', 5);
    }

    // ============================================================================
    // SESSION INITIATION
    // ============================================================================

    /**
     * Initie une session de charge après confirmation de réservation
     * 
     * @param Reservation $reservation
     * @return array{success: bool, message: string, session_id?: int}
     */
    public function initiateSession(Reservation $reservation): array
    {
        Log::info('ReservationSessionService: Initiation de session', [
            'reservation_id' => $reservation->id,
            'payment_mode' => $reservation->payment_mode,
        ]);

        try {
            DB::beginTransaction();

            // Vérifier que la réservation est verrouillée
            if (!$reservation->connector_locked) {
                $lockResult = $this->lockingService->lockConnector($reservation);
                if (!$lockResult['success']) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'Impossible de verrouiller le connecteur: ' . $lockResult['message'],
                    ];
                }
            }

            // Créer la session de charge
            $session = $this->createChargingSession($reservation);

            // Mettre à jour la réservation
            $reservation->update([
                'status' => ReservationStatus::ACTIVE,
                'session_initiated_at' => now(),
                'session_initiation_status' => 'success',
            ]);

            // Envoyer la commande RemoteStart
            $remoteStartResult = $this->sendRemoteStart($reservation, $session);

            if (!$remoteStartResult['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Échec du démarrage distant: ' . $remoteStartResult['message'],
                ];
            }

            DB::commit();

            Log::info('ReservationSessionService: Session initiée avec succès', [
                'reservation_id' => $reservation->id,
                'session_id' => $session->id,
            ]);

            return [
                'success' => true,
                'message' => 'Session de charge initiée avec succès',
                'session_id' => $session->id,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            $this->handleError($reservation, $e, 'initiate_session');

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'initiation de la session: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Crée une session de charge
     */
    protected function createChargingSession(Reservation $reservation): ChargingSession
    {
        $session = ChargingSession::create([
            'reservation_id' => $reservation->id,
            'charging_point_id' => $reservation->charging_point_id,
            'user_id' => $reservation->user_id,
            'connector_id' => $reservation->connector_id,
            'ocpp_tag' => $reservation->ocppTag?->id_tag ?? $this->generateOcppTag($reservation),
            'status' => ChargingSession::STATUS_INITIATING,
            'started_at' => null,
            'start_initiated_at' => now(),
            'payment_mode' => $reservation->payment_mode ?? ChargingSession::PAYMENT_MODE_PREPAID,
            'prepaid_amount' => $reservation->prepaid_amount ?? $reservation->estimated_cost,
            'estimated_cost' => $reservation->estimated_cost,
            'estimated_energy' => $reservation->estimated_energy,
            'estimated_duration' => $reservation->estimated_duration,
            'payment_gateway_transaction_id' => $reservation->payment_gateway_transaction_id,
            'payment_webhook_received_at' => $reservation->payment_webhook_received_at,
        ]);

        return $session;
    }

    /**
     * Envoie la commande RemoteStart à la borne
     */
    protected function sendRemoteStart(Reservation $reservation, ChargingSession $session): array
    {
        $chargingPoint = $reservation->chargingPoint;
        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return [
                'success' => false,
                'message' => 'ChargeBoxId non configuré',
            ];
        }

        $request = new RemoteStartRequestDTO([
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $reservation->connector->connector_id,
            'ocppTag' => $session->ocpp_tag,
        ]);

        $response = $this->ocppService->remoteStart($request);

        if ($response->isAccepted()) {
            $session->update([
                'status' => ChargingSession::STATUS_ACTIVE,
                'started_at' => now(),
                'steve_response' => $response->toArray(),
            ]);

            return [
                'success' => true,
                'message' => 'Démarrage distant accepté',
            ];
        } else {
            $session->update([
                'status' => ChargingSession::STATUS_FAILED,
                'steve_response' => $response->toArray(),
            ]);

            return [
                'success' => false,
                'message' => $response->errorMessage ?? 'Démarrage distant refusé',
            ];
        }
    }

    // ============================================================================
    // CARD PAYMENT VALIDATION
    // ============================================================================

    /**
     * Valide une réservation avec paiement par carte
     * Le démarrage de session doit être synchronisé avec la confirmation de paiement
     * 
     * @param Reservation $reservation
     * @param string $gatewayTransactionId
     * @param array $webhookData
     * @return array{success: bool, message: string}
     */
    public function validateCardPayment(Reservation $reservation, string $gatewayTransactionId, array $webhookData): array
    {
        Log::info('ReservationSessionService: Validation paiement carte', [
            'reservation_id' => $reservation->id,
            'gateway_transaction_id' => $gatewayTransactionId,
        ]);

        try {
            DB::beginTransaction();

            // Mettre à jour les informations de paiement
            $reservation->update([
                'payment_status' => 'PAID',
                'payment_gateway_transaction_id' => $gatewayTransactionId,
                'payment_webhook_received_at' => now(),
                'payment_confirmed_at' => now(),
            ]);

            // Valider que le timestamp de début correspond à la confirmation
            $confirmationTimestamp = now();
            $sessionStartTime = $reservation->session_initiated_at;

            if ($sessionStartTime !== null) {
                $diffInSeconds = abs($confirmationTimestamp->diffInSeconds($sessionStartTime));
                
                if ($diffInSeconds > 60) { // Tolérance de 60 secondes
                    Log::warning('ReservationSessionService: Décalage important entre paiement et session', [
                        'reservation_id' => $reservation->id,
                        'diff_seconds' => $diffInSeconds,
                    ]);
                }
            }

            // Approuver la réservation
            $reservation->approve();

            // Verrouiller le connecteur
            $lockResult = $this->lockingService->lockConnector($reservation);
            if (!$lockResult['success']) {
                Log::error('ReservationSessionService: Échec du verrouillage après paiement', [
                    'reservation_id' => $reservation->id,
                    'error' => $lockResult['message'],
                ]);
                // On continue quand même, le job de retry s'en occupera
            }

            // Initier la session immédiatement
            $sessionResult = $this->initiateSession($reservation);

            if (!$sessionResult['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Paiement validé mais échec de l\'initiation de session: ' . $sessionResult['message'],
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Paiement validé et session initiée',
                'session_id' => $sessionResult['session_id'],
            ];

        } catch (Exception $e) {
            DB::rollBack();
            $this->handleError($reservation, $e, 'validate_card_payment');

            return [
                'success' => false,
                'message' => 'Erreur lors de la validation du paiement: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Gère un webhook de paiement différé ou en échec
     */
    public function handleDelayedPaymentWebhook(Reservation $reservation, array $webhookData): array
    {
        Log::info('ReservationSessionService: Webhook paiement différé', [
            'reservation_id' => $reservation->id,
            'webhook_status' => $webhookData['status'] ?? 'unknown',
        ]);

        $status = $webhookData['status'] ?? 'unknown';

        if ($status === 'success') {
            return $this->validateCardPayment(
                $reservation,
                $webhookData['transaction_id'] ?? '',
                $webhookData
            );
        } elseif ($status === 'failed') {
            // Annuler la réservation
            $reservation->update([
                'status' => ReservationStatus::CANCELED,
                'payment_status' => 'FAILED',
                'notes' => ($reservation->notes ?? '') . "\n[SYSTEM] Paiement échoué via webhook",
            ]);

            // Déverrouiller si nécessaire
            if ($reservation->connector_locked) {
                $this->lockingService->unlockConnector($reservation, 'payment_failed');
            }

            return [
                'success' => false,
                'message' => 'Paiement échoué, réservation annulée',
            ];
        }

        return [
            'success' => false,
            'message' => 'Statut de webhook non géré: ' . $status,
        ];
    }

    // ============================================================================
    // CREDIT BALANCE PAYMENT CONFIRMATION
    // ============================================================================

    /**
     * Démarre le workflow de confirmation pour paiement par crédit solde
     * L'utilisateur doit confirmer dans les 5 minutes
     * 
     * @param Reservation $reservation
     * @return array{success: bool, message: string, expires_at?: Carbon}
     */
    public function startCreditBalanceConfirmation(Reservation $reservation): array
    {
        Log::info('ReservationSessionService: Début confirmation crédit solde', [
            'reservation_id' => $reservation->id,
        ]);

        try {
            // Vérifier le solde utilisateur
            if (!$reservation->userHasSufficientBalance()) {
                return [
                    'success' => false,
                    'message' => 'Solde insuffisant pour cette réservation',
                ];
            }

            // Calculer l'expiration
            $expiresAt = now()->addMinutes($this->paymentConfirmationTimeoutMinutes);

            // Mettre à jour la réservation
            $reservation->update([
                'status' => ReservationStatus::PENDING_CONFIRMATION,
                'payment_mode' => 'credit',
                'payment_confirmation_expires_at' => $expiresAt,
            ]);

            // Verrouiller le connecteur immédiatement
            $lockResult = $this->lockingService->lockConnector($reservation);
            if (!$lockResult['success']) {
                Log::warning('ReservationSessionService: Échec du verrouillage initial', [
                    'reservation_id' => $reservation->id,
                    'error' => $lockResult['message'],
                ]);
            }

            // Programmer l'annulation automatique si pas de confirmation
            // Ce job sera dispatché avec un délai
            // TODO: Dispatch CancelReservationJob::dispatch($reservation->id)->delay($expiresAt);

            return [
                'success' => true,
                'message' => 'Veuillez confirmer le paiement dans les ' . $this->paymentConfirmationTimeoutMinutes . ' minutes',
                'expires_at' => $expiresAt,
            ];

        } catch (Exception $e) {
            $this->handleError($reservation, $e, 'start_credit_confirmation');

            return [
                'success' => false,
                'message' => 'Erreur lors du démarrage de la confirmation: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Confirme le paiement par crédit solde et démarre la session
     * 
     * @param Reservation $reservation
     * @return array{success: bool, message: string, session_id?: int}
     */
    public function confirmCreditBalancePayment(Reservation $reservation): array
    {
        Log::info('ReservationSessionService: Confirmation paiement crédit', [
            'reservation_id' => $reservation->id,
        ]);

        try {
            // Vérifier que la confirmation n'a pas expiré
            if ($this->isConfirmationExpired($reservation)) {
                // Annuler la réservation
                $this->cancelReservationForTimeout($reservation);

                return [
                    'success' => false,
                    'message' => 'Le délai de confirmation a expiré. La réservation a été annulée.',
                ];
            }

            DB::beginTransaction();

            // Vérifier à nouveau le solde
            if (!$reservation->userHasSufficientBalance()) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Solde insuffisant',
                ];
            }

            // Débiter le solde (via le service de wallet)
            $debitResult = $this->debitUserBalance($reservation);
            if (!$debitResult['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Échec du débit du solde: ' . $debitResult['message'],
                ];
            }

            // Mettre à jour la réservation
            $reservation->update([
                'payment_status' => 'PAID',
                'payment_confirmed_at' => now(),
            ]);

            $reservation->approve();

            // S'assurer que le connecteur est verrouillé
            if (!$reservation->connector_locked) {
                $lockResult = $this->lockingService->lockConnector($reservation);
                if (!$lockResult['success']) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'Impossible de verrouiller le connecteur',
                    ];
                }
            }

            // Initier la session
            $sessionResult = $this->initiateSession($reservation);

            if (!$sessionResult['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Échec de l\'initiation de session: ' . $sessionResult['message'],
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Paiement confirmé et session initiée',
                'session_id' => $sessionResult['session_id'],
            ];

        } catch (Exception $e) {
            DB::rollBack();
            $this->handleError($reservation, $e, 'confirm_credit_payment');

            return [
                'success' => false,
                'message' => 'Erreur lors de la confirmation: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifie si la confirmation de paiement a expiré
     */
    public function isConfirmationExpired(Reservation $reservation): bool
    {
        if (!$reservation->payment_confirmation_expires_at) {
            return false;
        }

        return now()->greaterThan($reservation->payment_confirmation_expires_at);
    }

    /**
     * Annule une réservation pour timeout de confirmation
     */
    public function cancelReservationForTimeout(Reservation $reservation): void
    {
        Log::info('ReservationSessionService: Annulation pour timeout', [
            'reservation_id' => $reservation->id,
        ]);

        try {
            // Déverrouiller le connecteur
            if ($reservation->connector_locked) {
                $this->lockingService->unlockConnector($reservation, 'confirmation_timeout');
            }

            // Mettre à jour la réservation
            $reservation->update([
                'status' => ReservationStatus::CANCELED,
                'notes' => ($reservation->notes ?? '') . "\n[SYSTEM] Annulée: délai de confirmation dépassé (5 minutes)",
            ]);

            // Notifier l'utilisateur
            // TODO: Notification::send($reservation->user, new ReservationCancelledTimeout($reservation));

        } catch (Exception $e) {
            Log::error('ReservationSessionService: Erreur lors de l\'annulation timeout', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Débite le solde utilisateur
     */
    protected function debitUserBalance(Reservation $reservation): array
    {
        try {
            // TODO: Implémenter via WalletService ou TransactionService
            // Pour l'instant, on simule le succès
            
            // $walletService = app(WalletService::class);
            // $result = $walletService->debit(
            //     user: $reservation->user,
            //     amount: $reservation->estimated_cost,
            //     description: 'Réservation #' . $reservation->id,
            //     reference: 'reservation_' . $reservation->id
            // );

            return [
                'success' => true,
                'message' => 'Solde débité avec succès',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    // ============================================================================
    // UTILITAIRES
    // ============================================================================

    /**
     * Génère un tag OCPP temporaire pour la session
     */
    protected function generateOcppTag(Reservation $reservation): string
    {
        return 'RES-' . str_pad($reservation->id, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Gère une erreur
     */
    protected function handleError(Reservation $reservation, Exception $e, string $operation): void
    {
        Log::error('ReservationSessionService: Erreur ' . $operation, [
            'reservation_id' => $reservation->id,
            'operation' => $operation,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        try {
            $reservation->update([
                'session_initiation_status' => $operation . '_error',
                'last_error' => $e->getMessage(),
                'last_error_at' => now(),
            ]);
        } catch (Exception $updateEx) {
            Log::error('ReservationSessionService: Impossible de mettre à jour la réservation', [
                'reservation_id' => $reservation->id,
                'error' => $updateEx->getMessage(),
            ]);
        }
    }
}
