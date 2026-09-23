<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Connector;
use App\Models\Reservation;
use App\Models\OcppTag;
use App\Models\User;
use App\Models\Transaction;
use App\Models\ChargingSession;
use App\Services\SteVeApiEndpointService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service de logique métier OCPP
 * 
 * CE SERVICE EST LE CERVEAU - SteVe est le muscle
 * 
 * Responsabilités:
 * - Vérifier les conditions avant de démarrer une session
 * - Calculer les coûts minimums requis
 * - Vérifier les soldes et limites
 * - Orchestrer les appels SteVe
 * - Gérer l'état des réservations et transactions
 */
class OcppBusinessService
{
    protected SteVeApiEndpointService $steve;

    public function __construct(SteVeApiEndpointService $steve)
    {
        $this->steve = $steve;
    }

    /**
     * Vérifier si une réservation peut démarrer
     * 
     * CONDITIONS CRITIQUES:
     * ✔ Reservation status = APPROVED (ou active)
     * ✔ Payment status = PAID
     * ✔ User balance ≥ minimum session cost
     * ✔ Connector status = Available
     * ✔ Charge point is online
     * ✔ OCPP Tag is active (not blocked)
     */
    public function canStartReservation(Reservation $reservation): array
    {
        $errors = [];

        // 1. Vérifier le statut de la réservation
        // $reservation->status is a ReservationStatus enum – extract value for comparison
        $statusVal = $reservation->status instanceof \BackedEnum
            ? $reservation->status->value
            : (string) $reservation->status;
        if (!in_array($statusVal, ['confirmed', 'active', 'pending_confirmation'])) {
            $errors[] = "Réservation non approuvée (status: {$statusVal})";
        }

        // 2. Vérifier le statut de paiement
        if ($reservation->payment_status !== 'PAID') {
            $errors[] = "Paiement non effectué (payment_status: {$reservation->payment_status})";
        }

        // 3. Vérifier le solde utilisateur
        $user = $reservation->user;
        if ($user) {
            $minimumCost = $this->calculateMinimumCost($reservation);
            $balance = $user->balance ?? 0;
            
            if ($balance < $minimumCost) {
                $errors[] = "Solde insuffisant (requis: {$minimumCost} €, disponible: {$balance} €)";
            }
        }

        // 4. Vérifier le tag OCPP
        if ($reservation->ocpp_tag_id) {
            $ocppTag = OcppTag::find($reservation->ocpp_tag_id);
            if (!$ocppTag || !$ocppTag->isActive()) {
                $errors[] = "Tag OCPP bloqué ou expiré";
            }
        } else {
            // Pas de tag défini, utiliser le tag par défaut de l'utilisateur
            $defaultTag = $user?->ocppTags()->active()->default()->first();
            if (!$defaultTag) {
                $errors[] = "Aucun tag OCPP actif trouvé pour l'utilisateur";
            }
        }

        // 5. Vérifier le connecteur
        $connector = $reservation->connector;
        if (!$connector) {
            $errors[] = "Aucun connecteur assigné à cette réservation";
        } else {
            $isAvailable = $this->connectorIsAvailable(
                $connector->chargingPoint->charge_box_id ?? $connector->chargingPoint->serial_number,
                $connector->connector_id
            );
            
            if (!$isAvailable) {
                $errors[] = "Connecteur non disponible";
            }
        }

        // 6. Vérifier la borne de charge
        $chargingPoint = $reservation->chargingPoint;
        if (!$chargingPoint) {
            $errors[] = "Borne de recharge introuvable";
        } elseif ($chargingPoint->status === 'offline') {
            $errors[] = "Borne de recharge hors ligne";
        }

        return [
            'can_start' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Calculer le coût minimum requis pour démarrer une session
     * 
     * Stratégie: Calculer le minimum entre kWh et minutes
     * pour éviter les micro-sessions et l'abus
     */
    public function calculateMinimumCost(Reservation $reservation): float
    {
        $pricePerKwh = $reservation->pricingPlan->price_per_kwh ?? 0.30; // Prix par défaut
        $pricePerMinute = $reservation->pricingPlan->price_per_minute ?? 0.05;

        // Prendre le minimum entre les deux limites
        $costByKwh = ($reservation->max_kwh ?? 2) * $pricePerKwh;
        $costByMinutes = ($reservation->max_minutes ?? 10) * $pricePerMinute;

        // Minimum session: 10 minutes OU 2 kWh (le plus petit)
        $minimumCost = min($costByKwh, $costByMinutes);

        return max($minimumCost, 1.0); // Au moins 1€
    }

    /**
     * Vérifier la disponibilité d'un connecteur via SteVe
     */
    public function connectorIsAvailable(string $chargeBoxId, int $connectorId): bool
    {
        try {
            $response = $this->steve->getConnectorStatus($chargeBoxId);
            
            if (!isset($response['connectors'])) {
                Log::warning("Impossible de vérifier le statut du connecteur", [
                    'chargeBoxId' => $chargeBoxId,
                    'connectorId' => $connectorId,
                    'response' => $response,
                ]);
                return false;
            }

            $connector = collect($response['connectors'])
                ->firstWhere('connectorId', $connectorId);

            return $connector && ($connector['status'] === 'Available' || $connector['status'] === 'AVAILABLE');

        } catch (Exception $e) {
            Log::error("Erreur lors de la vérification du connecteur", [
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Démarrer une session de charge via SteVe
     * 
     * @return array ['success' => bool, 'transaction' => array, 'error' => string]
     */
    public function remoteStartCharging(Reservation $reservation): array
    {
        try {
            $connector = $reservation->connector;
            $chargingPoint = $reservation->chargingPoint;
            $user = $reservation->user;

            // Obtenir le chargeBoxId (peut être serial_number ou charge_box_id)
            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->serial_number;

            // Obtenir le tag OCPP
            if ($reservation->ocpp_tag_id) {
                $ocppTag = OcppTag::find($reservation->ocpp_tag_id);
            } else {
                $ocppTag = $user?->ocppTags()->active()->default()->first();
            }

            if (!$ocppTag) {
                return [
                    'success' => false,
                    'error' => 'Aucun tag OCPP actif trouvé',
                ];
            }

            // Appeler SteVe pour démarrer la charge
            Log::info("Démarrage de charge via SteVe", [
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connector->connector_id,
                'ocppTag' => $ocppTag->ocpp_tag,
                'reservation_id' => $reservation->id,
            ]);

            $response = $this->steve->remoteStartTransaction([
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connector->connector_id,
                'idTag' => $ocppTag->ocpp_tag,
            ]);

            if (!isset($response['status']) || $response['status'] !== 'ACCEPTED') {
                return [
                    'success' => false,
                    'error' => 'SteVe a rejeté la demande de démarrage',
                    'response' => $response,
                ];
            }

            return [
                'success' => true,
                'transaction' => $response,
                'ocpp_tag' => $ocppTag->ocpp_tag,
            ];

        } catch (Exception $e) {
            Log::error("Erreur lors du démarrage de charge", [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Arrêter une session de charge via SteVe
     * 
     * @return array ['success' => bool, 'transaction' => array, 'error' => string]
     */
    public function remoteStopCharging(ChargingSession $session): array
    {
        try {
            $chargingPoint = $session->chargingPoint;
            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->serial_number;

            Log::info("Arrêt de charge via SteVe", [
                'chargeBoxId' => $chargeBoxId,
                'steve_transaction_id' => $session->steve_transaction_id,
                'session_id' => $session->id,
            ]);

            $response = $this->steve->remoteStopTransaction([
                'chargeBoxId' => $chargeBoxId,
                'transactionId' => $session->steve_transaction_id,
            ]);

            if (!isset($response['status']) || $response['status'] !== 'ACCEPTED') {
                return [
                    'success' => false,
                    'error' => 'SteVe a rejeté la demande d\'arrêt',
                    'response' => $response,
                ];
            }

            return [
                'success' => true,
                'transaction' => $response,
            ];

        } catch (Exception $e) {
            Log::error("Erreur lors de l'arrêt de charge", [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifier si une session doit être arrêtée (enforcement)
     * 
     * RÈGLES D'ARRÊT:
     * ❌ User balance ≤ 0
     * ❌ Reservation end_time reached
     * ❌ Consumed kWh ≥ max_kwh
     * ❌ Duration ≥ max_minutes
     */
    public function shouldStopSession(ChargingSession $session): array
    {
        $reasons = [];
        
        // 1. Vérifier le solde utilisateur
        $user = $session->user;
        if ($user && ($user->balance ?? 0) <= 0) {
            $reasons[] = 'Solde insuffisant';
        }

        // 2. Vérifier l'heure de fin de réservation
        $reservation = $session->reservation;
        if ($reservation && $reservation->end_time && now()->isAfter($reservation->end_time)) {
            $reasons[] = 'Heure de fin de réservation atteinte';
        }

        // 3. Vérifier la consommation kWh
        if ($reservation && $reservation->max_kwh) {
            $consumed = $this->calculateConsumedEnergy($session);
            if ($consumed >= $reservation->max_kwh) {
                $reasons[] = "Limite kWh atteinte ({$consumed} / {$reservation->max_kwh})";
            }
        }

        // 4. Vérifier la durée
        if ($reservation && $reservation->max_minutes) {
            $duration = now()->diffInMinutes($session->started_at);
            if ($duration >= $reservation->max_minutes) {
                $reasons[] = "Durée maximale atteinte ({$duration} / {$reservation->max_minutes} min)";
            }
        }

        return [
            'should_stop' => !empty($reasons),
            'reasons' => $reasons,
        ];
    }

    /**
     * Calculer l'énergie consommée (en kWh) pour une session
     */
    public function calculateConsumedEnergy(ChargingSession $session): float
    {
        if (!$session->meter_start || !$session->meter_stop) {
            // Si pas de valeur stop, essayer de récupérer via SteVe
            return $session->actual_energy ?? 0;
        }

        // Les valeurs de compteur sont généralement en Wh, convertir en kWh
        $consumedWh = $session->meter_stop - $session->meter_start;
        return $consumedWh / 1000;
    }

    /**
     * Calculer le coût d'une session terminée
     */
    public function calculateSessionCost(ChargingSession $session): float
    {
        $reservation = $session->reservation;
        if (!$reservation || !$reservation->pricingPlan) {
            return 0;
        }

        $pricePerKwh = $reservation->pricingPlan->price_per_kwh ?? 0;
        $pricePerMinute = $reservation->pricingPlan->price_per_minute ?? 0;

        $consumed = $this->calculateConsumedEnergy($session);
        $duration = now()->diffInMinutes($session->started_at);

        $energyCost = $consumed * $pricePerKwh;
        $timeCost = $duration * $pricePerMinute;

        return $energyCost + $timeCost;
    }

    /**
     * Déverrouiller un connecteur après une session
     */
    public function unlockConnector(Connector $connector): bool
    {
        try {
            $chargingPoint = $connector->chargingPoint;
            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->serial_number;

            $response = $this->steve->unlockConnector([
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connector->connector_id,
            ]);

            return isset($response['status']) && $response['status'] === 'ACCEPTED';

        } catch (Exception $e) {
            Log::error("Erreur lors du déverrouillage du connecteur", [
                'connector_id' => $connector->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

