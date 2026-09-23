<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ChargingPoint;
use App\Models\User;
use App\Models\ChargingSession;
use App\Services\SteveService;
use App\Services\SteVe\SteVeClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service de gestion des sessions de recharge
 * Intègre les réservations avec l'API Steve pour contrôler les bornes
 */
class ChargingSessionManager
{
    protected SteveService $steveService;
    protected SteVeClient $steveClient;

    public function __construct(SteveService $steveService, SteVeClient $steveClient)
    {
        $this->steveService = $steveService;
        $this->steveClient = $steveClient;
    }

    /**
     * Démarrer une session de recharge pour une réservation
     * 
     * @param Reservation $reservation
     * @return array
     */
    public function startChargingSession(Reservation $reservation): array
    {
        try {
            DB::beginTransaction();

            Log::info('ChargingSessionManager: Starting charging session', [
                'reservation_id' => $reservation->id,
                'charging_point_id' => $reservation->charging_point_id,
                'payment_mode' => $reservation->payment_mode,
                'user_id' => $reservation->user_id
            ]);

            // Validations préalables
            $validation = $this->validateChargingStart($reservation);
            if (!$validation['success']) {
                return $validation;
            }

            $chargingPoint = $reservation->chargingPoint;
            $user = $reservation->user;

            // Déterminer l'identifiant SteVe attendu par Swagger: chargeBoxId
            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            // Vérifier que la borne a un identifiant chargeBoxId configuré
            if (empty($chargeBoxId)) {
                return [
                    'success' => false,
                    'error' => 'charge_point_not_configured',
                    'message' => 'La borne n\'a pas de chargeBoxId configuré pour l\'API SteVe'
                ];
            }

            // Obtenir ou générer un tag OCPP pour l'utilisateur
            $ocppTag = $this->getOrCreateOcppTag($user);

            // Déterminer le connecteur (par défaut connecteur 1)
            $connectorId = $chargingPoint->connector_id ?? 1;

            // Appeler l'API Steve pour démarrer la charge
            $steveResponse = $this->steveService->remoteStart(
                $chargeBoxId,
                $connectorId,
                $ocppTag
            );

            if (!$steveResponse['success']) {
                DB::rollBack();
                Log::error('ChargingSessionManager: Steve API remote start failed', [
                    'reservation_id' => $reservation->id,
                    'error' => $steveResponse['error'] ?? 'Unknown error'
                ]);
                
                return [
                    'success' => false,
                    'error' => 'steve_api_failed',
                    'message' => 'Impossible de démarrer la charge via l\'API Steve: ' . ($steveResponse['error'] ?? 'Erreur inconnue'),
                    'steve_response' => $steveResponse
                ];
            }

            // Créer une session de recharge
            $session = ChargingSession::create([
                'reservation_id' => $reservation->id,
                'charging_point_id' => $chargingPoint->id,
                'user_id' => $user->id,
                'steve_transaction_id' => $steveResponse['data']['transaction']['id'] ?? null,
                'connector_id' => $connectorId,
                'ocpp_tag' => $ocppTag,
                'status' => ChargingSession::STATUS_ACTIVE,
                'started_at' => now(),
                'payment_mode' => $reservation->payment_mode,
                'prepaid_amount' => $reservation->isPrepaid() ? $reservation->prepaid_amount : null,
                'estimated_cost' => $reservation->estimated_cost,
                'estimated_energy' => $reservation->estimated_energy,
                'estimated_duration' => $reservation->estimated_duration,
                'steve_response' => $steveResponse
            ]);

            // Mettre à jour la réservation
            $reservation->update([
                'charging_session_id' => $session->id,
                'status' => 'active',
                'started_at' => now(),
                'actual_start_time' => now()
            ]);

            // Mettre à jour le statut de la borne
            $chargingPoint->update([
                'status' => 'occupied',
                'current_session_id' => $session->id,
                'last_session_start' => now()
            ]);

            DB::commit();

            Log::info('ChargingSessionManager: Charging session started successfully', [
                'session_id' => $session->id,
                'reservation_id' => $reservation->id,
                'steve_transaction_id' => $session->steve_transaction_id
            ]);

            return [
                'success' => true,
                'message' => 'Session de recharge démarrée avec succès',
                'session' => $session,
                'steve_response' => $steveResponse
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('ChargingSessionManager: Error starting charging session', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'exception',
                'message' => 'Erreur lors du démarrage de la session: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Arrêter une session de recharge
     * 
     * @param ChargingSession $session
     * @param string $reason Raison de l'arrêt (manual, reservation_end, credit_exhausted, etc.)
     * @return array
     */
    public function stopChargingSession(ChargingSession $session, string $reason = 'manual'): array
    {
        try {
            DB::beginTransaction();

            Log::info('ChargingSessionManager: Stopping charging session', [
                'session_id' => $session->id,
                'reservation_id' => $session->reservation_id,
                'reason' => $reason
            ]);

            $chargingPoint = $session->chargingPoint;

            // Appeler l'API Steve pour arrêter la charge
            $steveResponse = $this->steveService->remoteStop(
                $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id
            );

            if (!$steveResponse['success']) {
                Log::warning('ChargingSessionManager: Steve API remote stop failed, but continuing', [
                    'session_id' => $session->id,
                    'error' => $steveResponse['error'] ?? 'Unknown error'
                ]);
            }

            // Mettre à jour la session
            $session->update([
                'status' => ChargingSession::STATUS_COMPLETED,
                'stopped_at' => now(),
                'stop_reason' => $reason,
                'steve_stop_response' => $steveResponse
            ]);

            // Mettre à jour la réservation
            $reservation = $session->reservation;
            $reservation->update([
                'status' => 'completed',
                'actual_end_time' => now()
            ]);

            // Mettre à jour le statut de la borne
            $chargingPoint->update([
                'status' => 'available',
                'current_session_id' => null,
                'last_session_end' => now()
            ]);

            // Pour le mode postpayé, calculer et débiter le coût réel
            if ($reservation->isPostpaid()) {
                $this->processPostpaidCharging($session, $reservation);
            } else {
                // Pour le prépayé, gérer le remboursement si nécessaire
                $this->processRrepaidRefund($session, $reservation);
            }

            DB::commit();

            Log::info('ChargingSessionManager: Charging session stopped successfully', [
                'session_id' => $session->id,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'message' => 'Session de recharge arrêtée avec succès',
                'session' => $session->fresh(),
                'steve_response' => $steveResponse
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('ChargingSessionManager: Error stopping charging session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'exception',
                'message' => 'Erreur lors de l\'arrêt de la session: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valider qu'une réservation peut démarrer une session de recharge
     */
    protected function validateChargingStart(Reservation $reservation): array
    {
        // Vérifier que la réservation est confirmée
        if ($reservation->status !== 'confirmed' && $reservation->status->value !== 'confirmed') {
            return [
                'success' => false,
                'error' => 'reservation_not_confirmed',
                'message' => 'La réservation n\'est pas confirmée'
            ];
        }

        // Vérifier que la réservation a une borne assignée
        if (empty($reservation->charging_point_id)) {
            return [
                'success' => false,
                'error' => 'no_charging_point',
                'message' => 'Aucune borne de recharge n\'est assignée à cette réservation'
            ];
        }

        // Vérifier que la borne existe et est disponible
        $chargingPoint = $reservation->chargingPoint;
        if (!$chargingPoint) {
            return [
                'success' => false,
                'error' => 'charging_point_not_found',
                'message' => 'Borne de recharge introuvable'
            ];
        }

        if ($chargingPoint->status === 'occupied' || $chargingPoint->status === 'offline') {
            return [
                'success' => false,
                'error' => 'charging_point_unavailable',
                'message' => 'La borne de recharge n\'est pas disponible'
            ];
        }

        // Vérifier que la session n'est pas déjà démarrée
        if ($reservation->charging_session_id) {
            return [
                'success' => false,
                'error' => 'session_already_exists',
                'message' => 'Une session de recharge est déjà active pour cette réservation'
            ];
        }

        // Vérifier le paiement selon le mode
        if ($reservation->isPrepaid()) {
            // Vérifier que le paiement prépayé est validé
            if (!$reservation->isWalletValidated()) {
                return [
                    'success' => false,
                    'error' => 'prepaid_not_validated',
                    'message' => 'Le paiement prépayé n\'est pas validé'
                ];
            }
        } elseif ($reservation->isPostpaid()) {
            // Vérifier que le solde est suffisant pour le seuil minimum
            $user = $reservation->user;
            $wallet = $user->getOrCreateWallet();
            $minThreshold = $reservation->min_threshold ?? config('charging.postpaid_min_threshold', 10.00);

            if (!$wallet->hasSufficientBalance($minThreshold)) {
                return [
                    'success' => false,
                    'error' => 'insufficient_balance',
                    'message' => "Solde insuffisant. Minimum requis: {$minThreshold}€, Disponible: {$wallet->balance}€"
                ];
            }
        }

        // Vérifier la disponibilité du connecteur via SteVe (si possible)
        $chargeBoxId = $reservation->chargingPoint?->charge_box_id ?? $reservation->chargingPoint?->steve_charging_point_id;
        if (!empty($chargeBoxId)) {
            $connectorId = $reservation->chargingPoint?->connector_id ?? 1;
            $availability = $this->isConnectorAvailable($chargeBoxId, $connectorId);

            if ($availability['ok'] === false) {
                return [
                    'success' => false,
                    'error' => 'steve_connector_status_failed',
                    'message' => $availability['message'] ?? 'Impossible de vérifier le statut du connecteur via SteVe',
                    'steve' => $availability['steve'] ?? null,
                ];
            }

            if ($availability['available'] === false) {
                return [
                    'success' => false,
                    'error' => 'connector_unavailable',
                    'message' => $availability['message'] ?? 'Connecteur non disponible',
                    'steve' => $availability['steve'] ?? null,
                ];
            }
        }

        return ['success' => true];
    }

    /**
     * Vérifie le statut d’un connecteur depuis SteVe.
     */
    protected function isConnectorAvailable(string $chargeBoxId, int $connectorId): array
    {
        // Swagger (selon doc fournie): GET /api/v1/connectors/status?chargeBoxId=CB123
        $res = $this->steveClient->getJson('/api/v1/connectors/status', ['chargeBoxId' => $chargeBoxId]);

        if (!($res['ok'] ?? false)) {
            return [
                'ok' => false,
                'available' => false,
                'message' => 'Échec SteVe connecteurs/status: ' . ($res['error'] ?? 'Erreur inconnue'),
                'steve' => ['status' => $res['status'] ?? null, 'url' => $res['url'] ?? null],
            ];
        }

        $json = is_array($res['json'] ?? null) ? $res['json'] : [];

        // Format A: {connectors: [...]}
        $connectors = [];
        if (isset($json['connectors']) && is_array($json['connectors'])) {
            $connectors = $json['connectors'];
        }
        // Format B: réponse directement tableau
        if (empty($connectors) && array_is_list($json)) {
            $connectors = $json;
        }

        $found = null;
        foreach ($connectors as $c) {
            if (!is_array($c)) {
                continue;
            }
            if ((int)($c['connectorId'] ?? -1) === (int)$connectorId) {
                $found = $c;
                break;
            }
        }

        if (!$found) {
            return [
                'ok' => true,
                'available' => false,
                'message' => "Connecteur {$connectorId} introuvable dans SteVe pour {$chargeBoxId}",
                'steve' => ['url' => $res['url'] ?? null],
            ];
        }

        $status = (string)($found['status'] ?? '');
        $available = strtoupper($status) === 'AVAILABLE';

        return [
            'ok' => true,
            'available' => $available,
            'message' => $available ? 'Connecteur disponible' : "Connecteur non disponible (status={$status})",
            'steve' => [
                'url' => $res['url'] ?? null,
                'status' => $status,
                'connector' => $found,
            ],
        ];
    }

    /**
     * Obtenir ou créer un tag OCPP pour un utilisateur
     */
    protected function getOrCreateOcppTag(User $user): string
    {
        // Si l'utilisateur a déjà un OCPP tag, le retourner
        if (!empty($user->ocpp_tag)) {
            return $user->ocpp_tag;
        }

        // Sinon, générer un nouveau tag basé sur l'ID utilisateur
        $tag = 'USER-' . str_pad($user->id, 8, '0', STR_PAD_LEFT);
        
        // Sauvegarder le tag pour usage futur
        $user->update(['ocpp_tag' => $tag]);

        return $tag;
    }

    /**
     * Traiter le paiement postpayé après la fin de la session
     */
    protected function processPostpaidCharging(ChargingSession $session, Reservation $reservation): void
    {
        try {
            // Calculer le coût réel basé sur l'énergie et la durée
            $actualCost = $this->calculateActualCost($session);

            $user = $reservation->user;
            $wallet = $user->getOrCreateWallet();

            // Débiter le coût réel
            $transaction = $wallet->debit(
                $actualCost,
                "Paiement postpayé - Session #{$session->id}",
                [
                    'session_id' => $session->id,
                    'reservation_id' => $reservation->id,
                    'payment_mode' => 'postpaid',
                    'type' => 'postpaid_charging'
                ]
            );

            // Mettre à jour la session et la réservation
            $session->update([
                'actual_cost' => $actualCost,
                'wallet_transaction_id' => $transaction->id
            ]);

            $reservation->update([
                'actual_cost' => $actualCost,
                'payment_status' => 'PAID'
            ]);

            Log::info('ChargingSessionManager: Postpaid charging processed', [
                'session_id' => $session->id,
                'actual_cost' => $actualCost,
                'transaction_id' => $transaction->id
            ]);

        } catch (Exception $e) {
            Log::error('ChargingSessionManager: Error processing postpaid charging', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Traiter le remboursement pour le prépayé si applicable
     */
    protected function processRrepaidRefund(ChargingSession $session, Reservation $reservation): void
    {
        try {
            // Calculer le coût réel
            $actualCost = $this->calculateActualCost($session);
            $prepaidAmount = $reservation->prepaid_amount;

            // Si le coût réel est inférieur au montant prépayé, rembourser la différence
            if ($actualCost < $prepaidAmount) {
                $refundAmount = $prepaidAmount - $actualCost;

                $user = $reservation->user;
                $wallet = $user->getOrCreateWallet();

                // Créditer le remboursement
                $transaction = $wallet->credit(
                    $refundAmount,
                    "Remboursement prépayé - Session #{$session->id}",
                    [
                        'session_id' => $session->id,
                        'reservation_id' => $reservation->id,
                        'payment_mode' => 'prepaid',
                        'type' => 'prepaid_refund'
                    ]
                );

                // Mettre à jour la session et la réservation
                $session->update([
                    'actual_cost' => $actualCost,
                    'refund_amount' => $refundAmount,
                    'refund_transaction_id' => $transaction->id
                ]);

                $reservation->update([
                    'actual_cost' => $actualCost,
                    'refund_amount' => $refundAmount
                ]);

                Log::info('ChargingSessionManager: Prepaid refund processed', [
                    'session_id' => $session->id,
                    'prepaid_amount' => $prepaidAmount,
                    'actual_cost' => $actualCost,
                    'refund_amount' => $refundAmount
                ]);
            } else {
                // Pas de remboursement nécessaire
                $session->update(['actual_cost' => $actualCost]);
                $reservation->update(['actual_cost' => $actualCost]);
            }

        } catch (Exception $e) {
            Log::error('ChargingSessionManager: Error processing prepaid refund', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculer le coût réel d'une session
     */
    protected function calculateActualCost(ChargingSession $session): float
    {
        // Récupérer les valeurs réelles depuis la session ou l'API Steve
        $actualEnergy = $session->actual_energy ?? 0; // kWh
        $actualDuration = $session->actual_duration ?? 0; // minutes

        // Récupérer le plan tarifaire
        $reservation = $session->reservation;
        $pricingPlan = $reservation->pricingPlan;

        if (!$pricingPlan) {
            Log::warning('ChargingSessionManager: No pricing plan found for session', [
                'session_id' => $session->id
            ]);
            return 0;
        }

        // Calculer le coût basé sur l'énergie et la durée
        $energyCost = $actualEnergy * $pricingPlan->price_per_kwh;
        $durationCost = ($actualDuration / 60) * $pricingPlan->price_per_hour;

        $totalCost = $energyCost + $durationCost;

        return round($totalCost, 2);
    }

    /**
     * Vérifier les sessions actives et arrêter celles qui ont dépassé leur période
     */
    public function checkAndStopExpiredSessions(): array
    {
        $stoppedSessions = [];

        try {
            $activeSessions = ChargingSession::where('status', ChargingSession::STATUS_ACTIVE)
                ->with(['reservation', 'chargingPoint'])
                ->get();

            foreach ($activeSessions as $session) {
                $shouldStop = false;
                $stopReason = '';

                // Vérifier si la période de réservation est terminée
                if ($session->reservation && $session->reservation->end_time) {
                    if (now()->isAfter($session->reservation->end_time)) {
                        $shouldStop = true;
                        $stopReason = 'reservation_period_ended';
                    }
                }

                // Vérifier le crédit pour le mode postpayé
                if ($session->payment_mode === 'postpaid' && $session->reservation) {
                    $user = $session->reservation->user;
                    $wallet = $user->getOrCreateWallet();
                    $minThreshold = $session->reservation->min_threshold ?? 5.00;

                    if (!$wallet->hasSufficientBalance($minThreshold)) {
                        $shouldStop = true;
                        $stopReason = 'insufficient_credit';
                    }
                }

                // Vérifier la durée maximale (sécurité)
                $maxDuration = $session->estimated_duration ?? 480; // 8 heures par défaut
                $sessionDuration = now()->diffInMinutes($session->started_at);
                
                if ($sessionDuration > ($maxDuration * 1.5)) { // 150% de la durée estimée
                    $shouldStop = true;
                    $stopReason = 'max_duration_exceeded';
                }

                if ($shouldStop) {
                    $result = $this->stopChargingSession($session, $stopReason);
                    if ($result['success']) {
                        $stoppedSessions[] = [
                            'session_id' => $session->id,
                            'reservation_id' => $session->reservation_id,
                            'reason' => $stopReason
                        ];
                    }
                }
            }

            Log::info('ChargingSessionManager: Checked expired sessions', [
                'total_active' => $activeSessions->count(),
                'stopped' => count($stoppedSessions)
            ]);

            return [
                'success' => true,
                'checked' => $activeSessions->count(),
                'stopped' => $stoppedSessions
            ];

        } catch (Exception $e) {
            Log::error('ChargingSessionManager: Error checking expired sessions', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

