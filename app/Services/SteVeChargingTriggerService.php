<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Services\Reservations\ReservationChargingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Service de déclenchement automatique de la recharge SETEVE
 *
 * @deprecated Use {@see ReservationChargingService::start()} /
 *             {@see ReservationChargingService::stop()} directly.
 *             This class survives as a thin shim so existing callers
 *             (SecurePaymentController, SteVeRetryService) keep working
 *             while the deprecation is propagated. Slice B.2 moved the
 *             reservation state-machine and the OCPP dispatch out of here.
 */
class SteVeChargingTriggerService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected array $endpoints;
    protected OcppService $ocppService;
    protected SteVeRetryService $retryService;
    protected ReservationChargingService $reservationCharging;

    public function __construct(
        OcppService $ocppService,
        SteVeRetryService $retryService,
        ?ReservationChargingService $reservationCharging = null,
    ) {
        $this->baseUrl = config('steve.api_url', '');
        $this->username = config('steve.username', '');
        $this->password = config('steve.password', '');
        $this->timeout = config('steve.timeout', 30);
        $this->ocppService = $ocppService;
        $this->retryService = $retryService;
        $this->reservationCharging = $reservationCharging ?? app(ReservationChargingService::class);

        $this->endpoints = [
            'start_transaction' => '/steve/services/CentralSystemService',
            'websocket' => '/steve/websocket/CentralSystemService',
            'api' => '/api/v1',
            'charging_sessions' => '/api/v1/charging-sessions'
        ];
    }

    /**
     * Déclencher automatiquement la recharge après un paiement réussi.
     *
     * Behaviour preserved: validates the reservation, then dispatches via the
     * canonical OCPP layer (Slice B.2). The old retry/OCPP/REST 3-way fallback
     * was removed — OcppOperationsService already does idempotent dispatch with
     * its own retry/lock semantics and audit trail, so the fallbacks were
     * compounding retries on the same wire failures.
     */
    public function triggerChargingAfterPayment(Reservation $reservation, array $paymentData = []): array
    {
        Log::info('SteVeChargingTriggerService: Déclenchement de la recharge après paiement (delegating)', [
            'reservation_id'    => $reservation->id,
            'charging_point_id' => $reservation->charging_point_id,
        ]);

        if (!$this->validateReservationForCharging($reservation)) {
            return [
                'success' => false,
                'error'   => 'Réservation non valide pour le déclenchement de la recharge',
                'code'    => 'invalid_reservation',
            ];
        }

        $idempotencyKey = $paymentData['idempotency_key'] ?? null;
        if ($idempotencyKey === null && !empty($paymentData['transaction_id'])) {
            $idempotencyKey = 'reservation:' . $reservation->id . ':' . $paymentData['transaction_id'];
        }

        return $this->reservationCharging->start($reservation, [
            'connector_id'    => $paymentData['connector_id'] ?? null,
            'ocpp_tag'        => $paymentData['id_tag']       ?? $paymentData['ocpp_tag'] ?? null,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    /**
     * Valider qu'une réservation peut déclencher une recharge
     */
    protected function validateReservationForCharging(Reservation $reservation): bool
    {
        // Vérifier que la réservation existe et a un statut valide
        if (!$reservation || !$reservation->id) {
            return false;
        }

        // Vérifier que le paiement a été effectué
        if (!$reservation->payment_confirmed) {
            return false;
        }

        // Vérifier que la réservation n'est pas déjà en cours de charge
        if ($reservation->status === 'active' || $reservation->status === 'charging') {
            return false;
        }

        return true;
    }

    /**
     * Vérifier la disponibilité du point de charge
     */
    protected function checkChargingPointAvailability(ChargingPoint $chargingPoint): array
    {
        try {
            // Vérifier le statut du point de charge via l'API SETEVE
            $statusUrl = $this->baseUrl . '/api/v1/charging-points/' . $chargingPoint->charge_box_id . '/status';
            
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->get($statusUrl);

            if ($response->successful()) {
                $statusData = $response->json();
                
                if (isset($statusData['status']) && $statusData['status'] === 'available') {
                    return [
                        'available' => true,
                        'status' => $statusData['status'],
                        'details' => $statusData
                    ];
                } else {
                    return [
                        'available' => false,
                        'reason' => 'Point de charge occupé ou en maintenance',
                        'status' => $statusData['status'] ?? 'unknown'
                    ];
                }
            } else {
                // En cas d'erreur API, considérer comme disponible (fallback)
                Log::warning('SteVeChargingTriggerService: Impossible de vérifier le statut du point de charge', [
                    'charging_point_id' => $chargingPoint->id,
                    'status_code' => $response->status()
                ]);
                
                return [
                    'available' => true,
                    'reason' => 'Statut non vérifiable, tentative de déclenchement'
                ];
            }

        } catch (\Exception $e) {
            Log::warning('SteVeChargingTriggerService: Erreur lors de la vérification de disponibilité', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            // En cas d'erreur, considérer comme disponible (fallback)
            return [
                'available' => true,
                'reason' => 'Vérification de disponibilité échouée, tentative de déclenchement'
            ];
        }
    }

    /**
     * Préparer les données pour le déclenchement de la recharge
     */
    protected function prepareChargingData(Reservation $reservation, array $paymentData = []): array
    {
        $chargingPoint = $reservation->chargingPoint;
        
        return [
            'connector_id' => $paymentData['connector_id'] ?? config('steve.defaults.connector_id', 1),
            'id_tag' => $paymentData['id_tag'] ?? config('steve.defaults.id_tag', 'admin'),
            'meter_start' => $paymentData['meter_start'] ?? config('steve.defaults.meter_start', 0),
            'reservation_id' => $reservation->id,
            'charge_box_id' => $chargingPoint->charge_box_id ?? 'CP_' . $chargingPoint->id,
            'customer_email' => $reservation->guest_email ?? $reservation->user->email ?? 'guest@evonpower.ma',
            'customer_name' => $reservation->user ? $reservation->user->name : 'Client Invité',
            'estimated_duration' => $reservation->duration_minutes ?? null,
            'estimated_energy' => $reservation->energy_kwh ?? null,
            'payment_method' => $paymentData['payment_method'] ?? 'unknown',
            'payment_transaction_id' => $paymentData['transaction_id'] ?? null,
            'timestamp' => Carbon::now()->toISOString()
        ];
    }

    /**
     * Déclencher la recharge via OCPP
     */
    protected function triggerOcppCharging(ChargingPoint $chargingPoint, array $chargingData): array
    {
        try {
            Log::info('SteVeChargingTriggerService: Tentative de déclenchement OCPP', [
                'charging_point_id' => $chargingPoint->id,
                'charging_data' => $chargingData
            ]);

            $result = $this->ocppService->remoteStartTransaction($chargingPoint, $chargingData);
            
            if ($result['success']) {
                Log::info('SteVeChargingTriggerService: Déclenchement OCPP réussi', [
                    'charging_point_id' => $chargingPoint->id,
                    'result' => $result
                ]);
                
                return [
                    'success' => true,
                    'method' => 'ocpp',
                    'transaction_id' => $result['transaction_id'] ?? uniqid('tx_'),
                    'data' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Erreur OCPP inconnue',
                    'method' => 'ocpp'
                ];
            }

        } catch (\Exception $e) {
            Log::error('SteVeChargingTriggerService: Erreur OCPP', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Erreur OCPP: ' . $e->getMessage(),
                'method' => 'ocpp'
            ];
        }
    }

    /**
     * Déclencher la recharge via l'API REST
     */
    protected function triggerApiCharging(ChargingPoint $chargingPoint, array $chargingData): array
    {
        try {
            Log::info('SteVeChargingTriggerService: Tentative de déclenchement API REST', [
                'charging_point_id' => $chargingPoint->id,
                'charging_data' => $chargingData
            ]);

            $apiUrl = $this->baseUrl . $this->endpoints['charging_sessions'];
            
            $payload = [
                'chargeBoxId' => $chargingData['charge_box_id'],
                'connectorId' => $chargingData['connector_id'],
                'idTag' => $chargingData['id_tag'],
                'meterStart' => $chargingData['meter_start'],
                'reservationId' => $chargingData['reservation_id'],
                'timestamp' => $chargingData['timestamp']
            ];

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->post($apiUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('SteVeChargingTriggerService: Déclenchement API REST réussi', [
                    'charging_point_id' => $chargingPoint->id,
                    'response' => $responseData
                ]);
                
                return [
                    'success' => true,
                    'method' => 'api',
                    'transaction_id' => $responseData['transactionId'] ?? uniqid('tx_'),
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Erreur API: ' . $response->body(),
                    'method' => 'api',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('SteVeChargingTriggerService: Erreur API REST', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Erreur API REST: ' . $e->getMessage(),
                'method' => 'api'
            ];
        }
    }

    /**
     * Gérer le succès du déclenchement de la recharge
     */
    protected function handleSuccessfulChargingStart(Reservation $reservation, array $result, string $method): array
    {
        try {
            // Mettre à jour le statut de la réservation
            $reservation->update([
                'status' => 'active',
                'charging_started_at' => now(),
                'charging_method' => $method,
                'charging_transaction_id' => $result['transaction_id'] ?? null
            ]);

            // Créer ou mettre à jour la transaction
            $transaction = $reservation->transaction;
            if ($transaction) {
                $transaction->update([
                    'status' => 'in_progress',
                    'start_timestamp' => now(),
                    'charging_transaction_id' => $result['transaction_id'] ?? null,
                    'charging_method' => $method
                ]);
            }

            // Envoyer une notification (optionnel)
            $this->sendChargingNotification($reservation, $result);

            Log::info('SteVeChargingTriggerService: Recharge démarrée avec succès', [
                'reservation_id' => $reservation->id,
                'method' => $method,
                'transaction_id' => $result['transaction_id'] ?? null
            ]);

            return [
                'success' => true,
                'message' => 'Recharge démarrée avec succès',
                'method' => $method,
                'transaction_id' => $result['transaction_id'] ?? null,
                'reservation' => $reservation->fresh()
            ];

        } catch (\Exception $e) {
            Log::error('SteVeChargingTriggerService: Erreur lors de la mise à jour après succès', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => true, // Le déclenchement a réussi même si la mise à jour échoue
                'message' => 'Recharge démarrée mais erreur lors de la mise à jour',
                'method' => $method,
                'transaction_id' => $result['transaction_id'] ?? null,
                'warning' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer une notification de démarrage de recharge
     */
    protected function sendChargingNotification(Reservation $reservation, array $result): void
    {
        try {
            // Log de la notification
            Log::info('SteVeChargingTriggerService: Notification de démarrage de recharge', [
                'reservation_id' => $reservation->id,
                'customer_email' => $reservation->guest_email ?? $reservation->user->email ?? null,
                'charging_point' => $reservation->chargingPoint->name ?? 'Inconnu',
                'transaction_id' => $result['transaction_id'] ?? null
            ]);

            // Ici vous pouvez ajouter l'envoi d'email, SMS, etc.
            // Par exemple:
            // Mail::to($reservation->guest_email)->send(new ChargingStartedMail($reservation));

        } catch (\Exception $e) {
            Log::warning('SteVeChargingTriggerService: Erreur lors de l\'envoi de notification', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Vérifier le statut d'une session de recharge
     */
    public function checkChargingStatus(Reservation $reservation): array
    {
        try {
            $transactionId = $reservation->charging_transaction_id;
            
            if (!$transactionId) {
                return [
                    'success' => false,
                    'error' => 'Aucun ID de transaction de recharge trouvé'
                ];
            }

            // Utiliser le service de retry
            return $this->retryService->checkChargingStatusWithRetry($reservation);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la vérification du statut: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Arrêter une session de recharge (delegates to ReservationChargingService).
     */
    public function stopCharging(Reservation $reservation): array
    {
        return $this->reservationCharging->stop($reservation, 'manual');
    }

    /**
     * Gérer l'arrêt réussi de la recharge
     */
    protected function handleSuccessfulChargingStop(Reservation $reservation, array $result): array
    {
        try {
            // Mettre à jour le statut de la réservation
            $reservation->update([
                'status' => 'completed',
                'charging_stopped_at' => now()
            ]);

            // Mettre à jour la transaction
            $transaction = $reservation->transaction;
            if ($transaction) {
                $transaction->update([
                    'status' => 'completed',
                    'stop_timestamp' => now(),
                    'meter_stop' => $result['meter_stop'] ?? null,
                    'energy_delivered' => $result['energy_delivered'] ?? null
                ]);
            }

            Log::info('SteVeChargingTriggerService: Recharge arrêtée avec succès', [
                'reservation_id' => $reservation->id,
                'result' => $result
            ]);

            return [
                'success' => true,
                'message' => 'Recharge arrêtée avec succès',
                'reservation' => $reservation->fresh()
            ];

        } catch (\Exception $e) {
            Log::error('SteVeChargingTriggerService: Erreur lors de la mise à jour après arrêt', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => true, // L'arrêt a réussi même si la mise à jour échoue
                'message' => 'Recharge arrêtée mais erreur lors de la mise à jour',
                'warning' => $e->getMessage()
            ];
        }
    }
}
