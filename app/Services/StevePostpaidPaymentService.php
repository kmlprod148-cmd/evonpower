<?php

namespace App\Services;

use App\Models\ChargingSession;
use App\Models\Reservation;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service smart pour le paiement postpayé basé sur l'API Steve.
 *
 * Steve = source de vérité pour les données de consommation (startValue, stopValue, energy).
 * EVON = calcul du coût (PricingPlan) + débit wallet.
 *
 * Fonctionnalités :
 * - Retry avec backoff pour les appels API Steve
 * - Idempotence : pas de double débit si déjà PAID
 * - Fallback sur données locales si Steve indisponible
 */
class StevePostpaidPaymentService
{
    protected int $fetchRetries = 3;
    protected int $fetchRetryDelayMs = 500;

    public function __construct(
        protected SteVeApiEndpointService $steveApi,
        protected TransactionStatusSyncService $transactionStatusSync,
        protected ReservationParticipantService $participantService
    ) {
        $this->fetchRetries = config('steve.postpaid_fetch_retries', 3);
        $this->fetchRetryDelayMs = config('steve.postpaid_fetch_retry_delay', 500);
    }

    /**
     * Récupère les données de consommation depuis Steve pour une session.
     * Avec retry et fallback getMeterValues.
     *
     * @return array{success: bool, start_value: float, stop_value: float, energy_wh: float, duration_minutes: int}
     */
    public function fetchConsumptionFromSteve(ChargingSession $session): array
    {
        $transactionId = $session->steve_transaction_id
            ?? data_get($session->metadata, 'steve_transaction_id');

        if (!$transactionId) {
            Log::warning('StevePostpaidPaymentService: Pas de steve_transaction_id', [
                'session_id' => $session->id,
            ]);
            return ['success' => false];
        }

        // 1. Essayer getTransaction avec retries (données complètes si StopTransaction reçu)
        $txResult = $this->fetchTransactionWithRetry((string) $transactionId);
        if ($txResult['success'] && !empty($txResult['data'])) {
            $data = $txResult['data'];
            $startValue = $this->extractMeterValue($data, 'startValue', 'start_value');
            $stopValue = $this->extractMeterValue($data, 'stopValue', 'stop_value');

            if ($stopValue !== null && $startValue !== null) {
                $energyWh = max(0, $stopValue - $startValue);
                $duration = $this->extractDuration($data, $session);

                return [
                    'success' => true,
                    'start_value' => $startValue,
                    'stop_value' => $stopValue,
                    'energy_wh' => $energyWh,
                    'energy_kwh' => $energyWh / 1000,
                    'duration_minutes' => $duration,
                ];
            }
        }

        // 2. Fallback : getMeterValues pour extraire la dernière valeur (avec retry)
        $mvResult = $this->fetchMeterValuesWithRetry((string) $transactionId);
        if ($mvResult['success'] && !empty($mvResult['data'])) {
            $values = is_array($mvResult['data']) ? $mvResult['data'] : [];
            $startValue = $session->meter_start ?? $session->meter_values['start']['value'] ?? 0;
            $lastValue = $this->getLastMeterValue($values);
            if ($lastValue !== null) {
                $energyWh = max(0, $lastValue - $startValue);
                $duration = (int) $session->started_at->diffInMinutes(now());

                return [
                    'success' => true,
                    'start_value' => $startValue,
                    'stop_value' => $lastValue,
                    'energy_wh' => $energyWh,
                    'energy_kwh' => $energyWh / 1000,
                    'duration_minutes' => $duration,
                ];
            }
        }

        return ['success' => false];
    }

    /**
     * Finalise le paiement postpayé en utilisant les données Steve.
     * Idempotent : ne débite pas si la réservation est déjà PAID.
     */
    public function finalizeFromSteve(ChargingSession $session): array
    {
        $reservation = $session->reservation;
        if ($reservation && in_array(strtoupper((string) ($reservation->payment_status ?? '')), ['PAID', 'PAYE'])) {
            return [
                'success' => true,
                'actual_cost' => (float) ($reservation->actual_cost ?? $session->actual_cost ?? 0),
                'energy_kwh' => (float) ($session->actual_energy ?? 0),
                'already_paid' => true,
            ];
        }

        $consumption = $this->fetchConsumptionFromSteve($session);

        if (!$consumption['success']) {
            // Fallback : utiliser les données locales (meter_start, estimation, etc.)
            $energyKwh = (float) ($session->actual_energy ?? 0);
            $duration = (int) $session->started_at->diffInMinutes(now());
            if ($energyKwh <= 0 && $session->meter_start !== null) {
                $energyKwh = max(0, (float) ($session->meter_stop ?? $session->meter_start) - (float) $session->meter_start) / 1000;
            }
            $consumption = [
                'success' => true,
                'energy_kwh' => $energyKwh,
                'duration_minutes' => $duration,
            ];
            Log::info('StevePostpaidPaymentService: Fallback données locales', [
                'session_id' => $session->id,
                'energy_kwh' => $energyKwh,
            ]);
        }

        return $this->processPostpaidDebit(
            $session,
            $consumption['energy_kwh'] ?? 0,
            $consumption['duration_minutes'] ?? 0,
        );
    }

    /**
     * Traite le débit postpayé (coût calculé + débit wallet).
     * Idempotent : ne débite pas si wallet_transaction_id déjà présent ou payment_status PAID.
     */
    public function processPostpaidDebit(
        ChargingSession $session,
        float $energyKwh,
        int $durationMinutes,
    ): array {
        $reservation = $session->reservation;
        if (!$reservation || !$reservation->user_id) {
            return ['success' => false, 'error' => 'Réservation ou user manquant'];
        }

        if ($session->wallet_transaction_id || in_array(strtoupper((string) ($reservation->payment_status ?? '')), ['PAID', 'PAYE'])) {
            return [
                'success' => true,
                'actual_cost' => (float) ($reservation->actual_cost ?? $session->actual_cost ?? 0),
                'energy_kwh' => $energyKwh,
                'already_paid' => true,
            ];
        }

        $pricingPlan = $reservation->pricingPlan ?? $session->chargingPoint?->pricingPlan;
        if (!$pricingPlan) {
            return ['success' => false, 'error' => 'Plan tarifaire manquant'];
        }

        $pricePerKwh = (float) ($pricingPlan->price_per_kwh ?? 0);
        $pricePerMinute = (float) ($pricingPlan->price_per_minute ?? 0);
        $activationFee = (float) ($pricingPlan->activation_fee ?? 0);

        $energyCost = $energyKwh * $pricePerKwh;
        $timeCost = $durationMinutes * $pricePerMinute;
        $actualCost = $activationFee + $energyCost + $timeCost;
        $actualCost = max(0, round($actualCost, 2));

        $user = $reservation->user;
        $wallet = $user->getOrCreateWallet();
        $wallet->refresh();

        try {
            DB::beginTransaction();

            $this->participantService->syncParticipants($reservation, null, $actualCost);
            $chargeResult = $this->participantService->applyWalletCharges($reservation, 'postpaid');

            $session->update([
                'wallet_transaction_id' => collect($chargeResult['results'] ?? [])
                    ->pluck('wallet_transaction_id')
                    ->filter()
                    ->first(),
                'actual_energy' => $energyKwh,
                'actual_duration' => $durationMinutes,
                'actual_cost' => $actualCost,
                'status' => 'COMPLETED',
                'stopped_at' => now(),
            ]);

            $reservation->update([
                'actual_cost' => $actualCost,
                'actual_energy' => $energyKwh,
                'actual_duration' => $durationMinutes,
                'payment_status' => 'PAID',
                'status' => 'completed',
            ]);

            $mainTransaction = $reservation->transaction;
            if ($mainTransaction && in_array($mainTransaction->status, ['pending'])) {
                $this->transactionStatusSync->syncTransactionStatusIfPaid($mainTransaction);
            }

            $user->unsetRelation('wallet');
            DB::commit();

            Log::info('StevePostpaidPaymentService: Paiement postpayé finalisé', [
                'session_id' => $session->id,
                'actual_cost' => $actualCost,
                'energy_kwh' => $energyKwh,
                'wallet_transactions' => $chargeResult['results'] ?? [],
            ]);

            return [
                'success' => true,
                'actual_cost' => $actualCost,
                'energy_kwh' => $energyKwh,
                'remaining_balance' => $wallet->fresh()->balance,
                'transaction_ids' => collect($chargeResult['results'] ?? [])
                    ->pluck('wallet_transaction_id')
                    ->filter()
                    ->values(),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('StevePostpaidPaymentService: Erreur débit', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            if (str_contains($e->getMessage(), 'Solde insuffisant')) {
                $reservation->update(['payment_status' => 'FAILED']);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function fetchTransactionWithRetry(string $transactionId): array
    {
        $lastError = null;
        for ($attempt = 1; $attempt <= $this->fetchRetries; $attempt++) {
            $result = $this->steveApi->getTransaction($transactionId);
            if ($result['success']) {
                return $result;
            }
            $lastError = $result['error'] ?? 'Inconnu';
            if ($attempt < $this->fetchRetries) {
                usleep($this->fetchRetryDelayMs * 1000 * $attempt);
            }
        }
        Log::warning('StevePostpaidPaymentService: getTransaction échoué après retries', [
            'transaction_id' => $transactionId,
            'error' => $lastError,
        ]);
        return ['success' => false, 'error' => $lastError];
    }

    protected function fetchMeterValuesWithRetry(string $transactionId): array
    {
        $lastError = null;
        for ($attempt = 1; $attempt <= $this->fetchRetries; $attempt++) {
            $result = $this->steveApi->getMeterValues($transactionId);
            if ($result['success']) {
                return $result;
            }
            $lastError = $result['error'] ?? 'Inconnu';
            if ($attempt < $this->fetchRetries) {
                usleep($this->fetchRetryDelayMs * 1000 * $attempt);
            }
        }
        return ['success' => false, 'error' => $lastError];
    }

    protected function extractMeterValue(array $data, string $key1, string $key2): ?float
    {
        $v = $data[$key1] ?? $data[$key2] ?? null;
        if ($v === null) {
            return null;
        }
        return (float) $v;
    }

    protected function extractDuration(array $data, ChargingSession $session): int
    {
        $stop = $data['stopTimestamp'] ?? $data['stop_timestamp'] ?? null;
        if ($stop) {
            try {
                $stopDt = \Carbon\Carbon::parse($stop);
                return (int) $session->started_at->diffInMinutes($stopDt);
            } catch (\Throwable $e) {
                // ignore
            }
        }
        return (int) $session->started_at->diffInMinutes(now());
    }

    protected function getLastMeterValue(array $meterValues): ?float
    {
        if (empty($meterValues)) {
            return null;
        }
        $last = end($meterValues);
        if (is_array($last)) {
            return (float) ($last['value'] ?? $last['values'][0]['value'] ?? null);
        }
        return null;
    }
}
