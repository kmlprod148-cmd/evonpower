<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ChargingPoint;
use App\Models\Connector;
use App\DTO\OCPP\LockConnectorRequestDTO;
use App\DTO\OCPP\UnlockConnectorRequestDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Service de gestion du verrouillage des connecteurs pour les réservations
 * 
 * Ce service gère :
 * - Le verrouillage automatique des connecteurs lors d'une réservation
 * - Le déverrouillage automatique à l'expiration
 * - La gestion des tentatives et retry
 * - La traçabilité des opérations OCPP
 */
class ReservationLockingService
{
    protected OcppOperationsService $ocppService;
    protected int $maxLockAttempts;
    protected int $lockTimeoutSeconds;

    public function __construct(OcppOperationsService $ocppService)
    {
        $this->ocppService = $ocppService;
        $this->maxLockAttempts = config('reservation.locking.max_attempts', 3);
        $this->lockTimeoutSeconds = config('reservation.locking.timeout_seconds', 30);
    }

    /**
     * Verrouille le connecteur pour une réservation
     * 
     * @param Reservation $reservation
     * @return array{success: bool, message: string, data?: array}
     */
    public function lockConnector(Reservation $reservation): array
    {
        Log::info('ReservationLockingService: Début du verrouillage', [
            'reservation_id' => $reservation->id,
            'charging_point_id' => $reservation->charging_point_id,
            'connector_id' => $reservation->connector_id,
        ]);

        // Vérifier si déjà verrouillé
        if ($reservation->connector_locked) {
            Log::info('ReservationLockingService: Connecteur déjà verrouillé', [
                'reservation_id' => $reservation->id,
            ]);
            return [
                'success' => true,
                'message' => 'Connecteur déjà verrouillé',
                'data' => ['already_locked' => true]
            ];
        }

        // Vérifier le nombre de tentatives
        if ($reservation->lock_operation_attempts >= $this->maxLockAttempts) {
            Log::error('ReservationLockingService: Nombre maximum de tentatives atteint', [
                'reservation_id' => $reservation->id,
                'attempts' => $reservation->lock_operation_attempts,
            ]);
            return [
                'success' => false,
                'message' => 'Nombre maximum de tentatives de verrouillage atteint',
            ];
        }

        // Incrémenter le compteur de tentatives
        $reservation->increment('lock_operation_attempts');
        $reservation->update([
            'lock_operation_last_attempt_at' => now(),
            'lock_operation_status' => 'pending',
        ]);

        try {
            // Récupérer le point de charge et le connecteur
            $chargingPoint = $reservation->chargingPoint;
            $connector = $reservation->connector;

            if (!$chargingPoint || !$connector) {
                throw new Exception('Point de charge ou connecteur non trouvé');
            }

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                throw new Exception('ChargeBoxId non configuré pour ce point de charge');
            }

            // Créer la requête de verrouillage
            $lockRequest = new LockConnectorRequestDTO([
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connector->connector_id,
            ]);

            // Appel OCPP
            $response = $this->ocppService->lockConnector($lockRequest);

            // Log de l'opération
            $this->logOperation($reservation, 'lock', $response->toArray());

            if ($response->isLocked()) {
                // Mise à jour de la réservation
                $reservation->update([
                    'connector_locked' => true,
                    'connector_locked_at' => now(),
                    'lock_operation_status' => 'success',
                ]);

                // Mise à jour du statut du connecteur
                $connector->updateStatus(Connector::STATUS_RESERVED);

                Log::info('ReservationLockingService: Verrouillage réussi', [
                    'reservation_id' => $reservation->id,
                    'connector_id' => $connector->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'Connecteur verrouillé avec succès',
                    'data' => [
                        'locked_at' => now()->toIso8601String(),
                        'connector_status' => Connector::STATUS_RESERVED,
                    ]
                ];
            } else {
                // Échec du verrouillage
                $reservation->update([
                    'lock_operation_status' => 'failed',
                    'last_error' => $response->errorMessage ?? 'Échec du verrouillage',
                    'last_error_at' => now(),
                ]);

                Log::error('ReservationLockingService: Échec du verrouillage', [
                    'reservation_id' => $reservation->id,
                    'error' => $response->errorMessage,
                ]);

                return [
                    'success' => false,
                    'message' => $response->errorMessage ?? 'Échec du verrouillage du connecteur',
                ];
            }

        } catch (Exception $e) {
            $this->handleError($reservation, $e, 'lock');

            return [
                'success' => false,
                'message' => 'Erreur lors du verrouillage: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Déverrouille le connecteur pour une réservation
     * 
     * @param Reservation $reservation
     * @param string $reason Raison du déverrouillage
     * @return array{success: bool, message: string, data?: array}
     */
    public function unlockConnector(Reservation $reservation, string $reason = 'expiration'): array
    {
        Log::info('ReservationLockingService: Début du déverrouillage', [
            'reservation_id' => $reservation->id,
            'reason' => $reason,
        ]);

        // Vérifier si déjà déverrouillé
        if (!$reservation->connector_locked && $reservation->connector_unlocked_at !== null) {
            Log::info('ReservationLockingService: Connecteur déjà déverrouillé', [
                'reservation_id' => $reservation->id,
            ]);
            return [
                'success' => true,
                'message' => 'Connecteur déjà déverrouillé',
                'data' => ['already_unlocked' => true]
            ];
        }

        try {
            $chargingPoint = $reservation->chargingPoint;
            $connector = $reservation->connector;

            if (!$chargingPoint || !$connector) {
                throw new Exception('Point de charge ou connecteur non trouvé');
            }

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                throw new Exception('ChargeBoxId non configuré');
            }

            // Créer la requête de déverrouillage
            $unlockRequest = new UnlockConnectorRequestDTO([
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connector->connector_id,
            ]);

            // Appel OCPP
            $response = $this->ocppService->unlockConnector($unlockRequest);

            // Log de l'opération
            $this->logOperation($reservation, 'unlock', [
                'response' => $response->toArray(),
                'reason' => $reason,
            ]);

            if ($response->isUnlocked()) {
                // Mise à jour de la réservation
                $reservation->update([
                    'connector_locked' => false,
                    'connector_unlocked_at' => now(),
                    'lock_operation_status' => 'unlocked',
                ]);

                // Mise à jour du statut du connecteur
                $connector->updateStatus(Connector::STATUS_AVAILABLE);

                Log::info('ReservationLockingService: Déverrouillage réussi', [
                    'reservation_id' => $reservation->id,
                    'reason' => $reason,
                ]);

                return [
                    'success' => true,
                    'message' => 'Connecteur déverrouillé avec succès',
                    'data' => [
                        'unlocked_at' => now()->toIso8601String(),
                        'reason' => $reason,
                    ]
                ];
            } else {
                // Échec du déverrouillage - on met quand même à jour le statut local
                // car le connecteur pourrait être déjà déverrouillé physiquement
                $reservation->update([
                    'connector_locked' => false,
                    'connector_unlocked_at' => now(),
                    'lock_operation_status' => 'unlock_failed',
                    'last_error' => $response->errorMessage ?? 'Échec du déverrouillage',
                    'last_error_at' => now(),
                ]);

                $connector->updateStatus(Connector::STATUS_AVAILABLE);

                Log::warning('ReservationLockingService: Échec du déverrouillage OCPP, statut local mis à jour', [
                    'reservation_id' => $reservation->id,
                    'error' => $response->errorMessage,
                ]);

                return [
                    'success' => true, // On considère ça comme un succès local
                    'message' => 'Déverrouillage local effectué (erreur OCPP: ' . ($response->errorMessage ?? 'inconnue') . ')',
                    'data' => ['local_only' => true]
                ];
            }

        } catch (Exception $e) {
            $this->handleError($reservation, $e, 'unlock');

            // Même en cas d'erreur, on libère le connecteur localement
            try {
                $reservation->update([
                    'connector_locked' => false,
                    'connector_unlocked_at' => now(),
                    'lock_operation_status' => 'unlock_error',
                ]);

                if ($reservation->connector) {
                    $reservation->connector->updateStatus(Connector::STATUS_AVAILABLE);
                }
            } catch (Exception $updateEx) {
                Log::error('ReservationLockingService: Impossible de mettre à jour le statut local', [
                    'reservation_id' => $reservation->id,
                    'error' => $updateEx->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'message' => 'Erreur lors du déverrouillage: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifie si une réservation peut verrouiller son connecteur
     * 
     * @param Reservation $reservation
     * @return array{can_lock: bool, reason?: string}
     */
    public function canLockConnector(Reservation $reservation): array
    {
        // Vérifier le statut de la réservation
        if (!in_array($reservation->status->value, ['confirmed', 'pending_confirmation'])) {
            return [
                'can_lock' => false,
                'reason' => 'Statut de réservation invalide: ' . $reservation->status->value,
            ];
        }

        // Vérifier si le paiement est confirmé
        if ($reservation->payment_status !== 'PAID') {
            return [
                'can_lock' => false,
                'reason' => 'Paiement non confirmé',
            ];
        }

        // Vérifier le connecteur
        $connector = $reservation->connector;
        if (!$connector) {
            return [
                'can_lock' => false,
                'reason' => 'Connecteur non trouvé',
            ];
        }

        // Vérifier si le connecteur est disponible
        if (!$connector->isAvailable() && !$connector->isReserved()) {
            return [
                'can_lock' => false,
                'reason' => 'Connecteur non disponible (statut: ' . $connector->status . ')',
            ];
        }

        // Vérifier le nombre de tentatives
        if ($reservation->lock_operation_attempts >= $this->maxLockAttempts) {
            return [
                'can_lock' => false,
                'reason' => 'Nombre maximum de tentatives atteint',
            ];
        }

        return ['can_lock' => true];
    }

    /**
     * Vérifie si le connecteur est verrouillé par une autre réservation active
     * 
     * @param ChargingPoint $chargingPoint
     * @param int $connectorId
     * @param int|null $excludeReservationId
     * @return bool
     */
    public function isConnectorLockedByAnother(ChargingPoint $chargingPoint, int $connectorId, ?int $excludeReservationId = null): bool
    {
        $query = Reservation::where('charging_point_id', $chargingPoint->id)
            ->where('connector_id', $connectorId)
            ->where('connector_locked', true)
            ->whereIn('status', ['confirmed', 'pending_confirmation', 'active']);

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->exists();
    }

    /**
     * Log une opération OCPP
     */
    protected function logOperation(Reservation $reservation, string $operation, array $data): void
    {
        try {
            $log = $reservation->ocpp_operations_log ?? [];
            $log[] = [
                'operation' => $operation,
                'timestamp' => now()->toIso8601String(),
                'data' => $data,
            ];

            // Garder seulement les 50 dernières opérations
            if (count($log) > 50) {
                $log = array_slice($log, -50);
            }

            $reservation->update(['ocpp_operations_log' => $log]);
        } catch (Exception $e) {
            Log::error('ReservationLockingService: Impossible de logger l\'opération', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Gère une erreur
     */
    protected function handleError(Reservation $reservation, Exception $e, string $operation): void
    {
        Log::error('ReservationLockingService: Erreur ' . $operation, [
            'reservation_id' => $reservation->id,
            'operation' => $operation,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        try {
            $reservation->update([
                'lock_operation_status' => $operation . '_error',
                'last_error' => $e->getMessage(),
                'last_error_at' => now(),
            ]);

            $this->logOperation($reservation, $operation . '_error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } catch (Exception $updateEx) {
            Log::error('ReservationLockingService: Impossible de mettre à jour la réservation après erreur', [
                'reservation_id' => $reservation->id,
                'error' => $updateEx->getMessage(),
            ]);
        }
    }
}
