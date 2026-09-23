<?php

namespace App\Services;

use App\Repositories\ChargingPointRepositoryInterface;
use App\Repositories\StationRepositoryInterface;
use App\Services\PricingPlanService;
use App\Services\OCPPService;
use App\DTO\ChargingSession\ChargingSessionDTO;
use App\DTO\ChargingSession\StartChargeDTO;
use App\DTO\ChargingSession\StopChargeDTO;
use App\Exceptions\ChargingPoint\ChargingPointNotFoundException;
use App\Exceptions\ChargingPoint\ConnectorUnavailableException;
use App\Exceptions\ChargingPoint\SessionNotFoundException;
use App\Models\ChargingSession;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\Station;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChargingService
{
    protected $chargingPointRepository;
    protected $stationRepository;
    protected PricingPlanService $pricingPlanService;
    protected OCPPService $ocppService;

    public function __construct(
        \App\Repositories\Interfaces\ChargingPointRepositoryInterface $chargingPointRepository,
        \App\Repositories\Interfaces\StationRepositoryInterface $stationRepository,
        PricingPlanService $pricingPlanService,
        OCPPService $ocppService
    ) {
        $this->chargingPointRepository = $chargingPointRepository;
        $this->stationRepository = $stationRepository;
        $this->pricingPlanService = $pricingPlanService;
        $this->ocppService = $ocppService;
    }

    /**
     * Start a charging session
     *
     * @param ChargingSessionDTO $sessionDTO
     * @return ChargingSession
     * @throws ChargingPointNotFoundException
     * @throws ConnectorUnavailableException
     * @throws \Exception
     */
    public function startSession(ChargingSessionDTO $sessionDTO): ChargingSession
    {
        return DB::transaction(function () use ($sessionDTO) {
        // Check if charging point exists
            $chargingPoint = $this->chargingPointRepository->find($sessionDTO->chargingPointId);
        if (!$chargingPoint) {
            throw new ChargingPointNotFoundException("Charging point not found");
        }

        // Check if station exists and is available
        $station = $this->stationRepository->findByChargingPointAndId(
            $sessionDTO->chargingPointId,
            $sessionDTO->stationId
        );
        
        if (!$station) {
            throw new \Exception("Station not found");
        }
        
        if ($station->status !== 'available') {
            throw new \Exception("Station is not available");
        }

            // Check if there's an active session for this station
            $activeSession = ChargingSession::where('station_id', $sessionDTO->stationId)
                ->where('status', 'active')
                ->first();

            if ($activeSession) {
                throw new \Exception('Une session est déjà active sur cette station');
            }

            // Vérifier la connectivité OCPP de la borne
            $connectivity = $this->ocppService->checkConnectivity($chargingPoint);
            if (!$connectivity['connected']) {
                throw new \Exception('Borne non connectée. Impossible de démarrer la recharge.');
            }

            // Envoyer la commande RemoteStartTransaction à la borne
            $ocppData = [
                'id_tag' => $sessionDTO->metaData['email'] ?? 'anonymous',
                'charging_profile' => null
            ];

            $ocppResponse = $this->ocppService->remoteStartTransaction($chargingPoint, $station, $ocppData);
            
            if (!$ocppResponse['success']) {
                throw new \Exception('Échec de la commande de démarrage OCPP: ' . ($ocppResponse['error'] ?? 'Erreur inconnue'));
            }

            // Attendre un peu pour que la borne traite la commande
            sleep(2);

            // Vérifier le statut de la station après la commande
            $stationStatus = $this->ocppService->getStationStatus($chargingPoint, $station);
            if ($stationStatus['status'] !== 'Charging') {
                throw new \Exception('La station n\'a pas démarré la recharge. Statut: ' . $stationStatus['status']);
            }

        // Create a unique monitoring token
        $monitoringToken = Str::random(32);

        // Create the charging session
        $chargingSession = new ChargingSession();
        $chargingSession->charging_point_id = $chargingPoint->id;
        $chargingSession->station_id = $station->id;
        $chargingSession->pricing_plan_id = $sessionDTO->pricingPlanId;
        $chargingSession->user_id = $sessionDTO->userId;
        $chargingSession->transaction_id = 'TR' . strtoupper(Str::random(10)) . time();
        $chargingSession->start_timestamp = now();
            $chargingSession->status = 'active';
        $chargingSession->meter_start = $station->meter_value ?? 0;
        $chargingSession->auth_method = 'app';
        $chargingSession->is_public = true;
        $chargingSession->email = $sessionDTO->metaData['email'] ?? null;
        $chargingSession->monitoring_token = $monitoringToken;
            $chargingSession->session_id = 'SESS_' . time() . '_' . Str::random(8);
            
            // Add metadata
            if ($sessionDTO->metaData) {
                $chargingSession->meta_data = $sessionDTO->metaData;
            }

        $chargingSession->save();

        // Update station status
            $this->stationRepository->updateStatus($station->id, 'charging');

            // Update charging point status
            if ($chargingPoint) {
                $chargingPoint->update(['status' => 'charging']);
            }

            Log::info('Session de recharge démarrée', [
                'session_id' => $chargingSession->id,
                'charging_point_id' => $sessionDTO->chargingPointId,
                'station_id' => $sessionDTO->stationId
            ]);

        return $chargingSession;
        });
    }

    /**
     * Get a charging session by its monitoring token
     *
     * @param string $token
     * @param int $chargingPointId
     * @return ChargingSession|null
     */
    public function getSessionByToken(string $token, int $chargingPointId): ?ChargingSession
    {
        return ChargingSession::where('monitoring_token', $token)
            ->where('charging_point_id', $chargingPointId)
            ->where('status', 'active')
            ->with(['chargingPoint', 'station', 'pricingPlan'])
            ->first();
    }

    /**
     * Get metrics for a charging session
     *
     * @param int $sessionId
     * @return array
     */
    public function getSessionMetrics(int $sessionId): array
    {
        $session = ChargingSession::find($sessionId);

        if (!$session) {
            return [
                'duration' => 0,
                'energy_delivered' => 0,
                'current_power' => 0,
                'current_cost' => 0
            ];
        }

        $startTime = Carbon::parse($session->start_timestamp);
        $endTime = $session->stopped_at ? Carbon::parse($session->stopped_at) : now();
        
        $duration = $startTime->diffInMinutes($endTime);
        $energyConsumed = $session->energy_consumed_kwh ?? $this->calculateEnergyConsumed($session, $duration);
        $totalCost = $session->total_cost ?? $this->calculateTotalCost($session, $energyConsumed);

        // Get station details
        $station = $this->stationRepository->findByChargingPointAndId(
            $session->charging_point_id,
            $session->station_id
        );

        // Simulate energy consumption for demonstration
        $energyDelivered = ($duration / 60) * ($station->power_output / 1000); // kWh

        // Simulate current power
        $currentPower = $station->power_output / 1000; // kW

        // Calculate cost based on pricing plan
        $pricingPlan = $this->pricingPlanService->find($session->pricing_plan_id);
        $pricePerMinute = $pricingPlan->price_per_minute ?? 0;
        $pricePerKwh = $pricingPlan->price_per_kwh ?? 0;

        $costByTime = ($duration / 60) * $pricePerMinute;
        $costByEnergy = $energyDelivered * $pricePerKwh;

        $currentCost = $costByTime + $costByEnergy;

        return [
            'duration_minutes' => $duration,
            'duration_formatted' => $this->formatDuration($duration),
            'energy_consumed_kwh' => round($energyConsumed, 2),
            'total_cost' => round($totalCost, 2),
            'cost_per_kwh' => $pricingPlan ? $pricingPlan->price_per_kwh : 0,
            'is_active' => $session->status === 'active',
            'start_time' => $startTime->format('H:i'),
            'current_time' => now()->format('H:i'),
            'energy_delivered' => round($energyDelivered, 2),
            'current_power' => $currentPower,
            'current_cost' => round($currentCost, 2)
        ];
    }

    /**
     * Stop a charging session
     *
     * @param int $sessionId
     * @param string $reason
     * @return array
     * @throws SessionNotFoundException
     * @throws \Exception
     */
    public function stopSession(int $sessionId, string $reason = 'user_stopped'): array
    {
        return DB::transaction(function () use ($sessionId, $reason) {
        $session = ChargingSession::find($sessionId);

            if (!$session || $session->status !== 'active') {
                throw new SessionNotFoundException("Charging session not found or already stopped");
        }

            // Calculate duration and energy consumed
            $duration = Carbon::parse($session->start_timestamp)->diffInMinutes(now());
            $energyConsumed = $this->calculateEnergyConsumed($session, $duration);

            // Update session
            $session->status = 'completed';
            $session->stopped_at = now();
            $session->duration_minutes = $duration;
            $session->energy_consumed_kwh = $energyConsumed;
            $session->total_cost = $this->calculateTotalCost($session, $energyConsumed);
            $session->stop_reason = $reason;
            $session->save();

            // Release station
        $station = $this->stationRepository->findByChargingPointAndId(
            $session->charging_point_id,
            $session->station_id
        );

        if ($station) {
                // Envoyer la commande RemoteStopTransaction à la borne
                $ocppData = [
                    'transaction_id' => $session->transaction_id
                ];

                try {
                    $ocppResponse = $this->ocppService->remoteStopTransaction($session->chargingPoint, $ocppData);
                    
                    if (!$ocppResponse['success']) {
                        Log::warning('OCPP RemoteStopTransaction failed', [
                            'session_id' => $session->id,
                            'error' => $ocppResponse['error'] ?? 'Unknown error'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('OCPP RemoteStopTransaction error', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage()
                    ]);
                }

            $this->stationRepository->updateStatus($station->id, 'available');
        }

            // Check if there are other active sessions on this charging point
            $activeSessions = ChargingSession::where('charging_point_id', $session->charging_point_id)
                ->where('status', 'active')
                ->count();

            if ($activeSessions === 0) {
                // No active sessions, release the charging point
                $chargingPoint = $this->chargingPointRepository->find($session->charging_point_id);
                if ($chargingPoint) {
                    $chargingPoint->update(['status' => 'online']);
                }
            }

            Log::info('Session de recharge arrêtée', [
                'session_id' => $session->id,
                'duration' => $duration,
                'energy_consumed' => $energyConsumed
            ]);

        // Create transaction
            $transaction = $this->createTransaction($session, $session->total_cost);

        // Format result
        return [
                'end_time' => $session->stopped_at,
                'duration' => $duration,
                'energy_delivered' => $energyConsumed,
                'total_cost' => $session->total_cost,
            'currency' => $transaction->currency ?? 'EUR',
            'receipt_url' => route('api.public.charging.receipt', $transaction->id),
            'transaction_id' => $transaction->id
        ];
        });
    }

    /**
     * Get a transaction by ID
     *
     * @param int $id
     * @return Transaction|null
     */
    public function getTransactionById(int $id): ?Transaction
    {
        return Transaction::find($id);
    }

    /**
     * Create a transaction from a charging session
     *
     * @param ChargingSession $session
     * @param float $amount
     * @return Transaction
     */
    protected function createTransaction(ChargingSession $session, float $amount): Transaction
    {
        $pricingPlan = $this->pricingPlanService->find($session->pricing_plan_id);

        $transaction = new Transaction();
        $transaction->transaction_id = $session->transaction_id;
        $transaction->charging_point_id = $session->charging_point_id;
        $transaction->station_id = $session->station_id;
        $transaction->user_id = $session->user_id;
        $transaction->charging_session_id = $session->id;
        $transaction->pricing_plan_id = $session->pricing_plan_id;
        $transaction->start_timestamp = $session->start_timestamp;
        $transaction->stop_timestamp = $session->stopped_at;
        $transaction->duration = $session->duration_minutes;
        $transaction->energy_delivered = $session->energy_consumed_kwh;
        $transaction->price_energy = $session->energy_consumed_kwh * ($pricingPlan->price_per_kwh ?? 0);
        $transaction->price_time = ($session->duration_minutes / 60) * ($pricingPlan->price_per_minute ?? 0);
        $transaction->price_tax = ($transaction->price_energy + $transaction->price_time) * 0.2; // 20% VAT by default
        $transaction->price_total = $amount;
        $transaction->currency = $pricingPlan->currency ?? 'EUR';
        $transaction->payment_status = 'completed';
        $transaction->status = 'completed';
        $transaction->save();

        return $transaction;
    }

    /**
     * Calculate energy consumed (simulation)
     */
    private function calculateEnergyConsumed(ChargingSession $session, int $durationMinutes): float
    {
        // Simulation based on duration and station power
        $station = $session->station;
        $powerKw = $station ? $station->power_output : 22; // Default power
        
        // Conversion: power (kW) * duration (hours)
        $durationHours = $durationMinutes / 60;
        $energyKwh = $powerKw * $durationHours;
        
        // Add a random variation to simulate reality
        $variation = rand(-10, 10) / 100; // ±10%
        return $energyKwh * (1 + $variation);
    }

    /**
     * Calculate total cost
     */
    private function calculateTotalCost(ChargingSession $session, float $energyKwh): float
    {
        if (!$session->pricingPlan) {
            return 0;
        }

        $baseCost = $energyKwh * $session->pricingPlan->price_per_kwh;
        
        // Add activation fees if applicable
        if ($session->pricingPlan->activation_fee > 0) {
            $baseCost += $session->pricingPlan->activation_fee;
        }

        return $baseCost;
    }

    /**
     * Format duration in a readable format
     */
    private function formatDuration(int $minutes): string
    {
        $hours = intval($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $mins);
        }
        
        return sprintf('%dm', $mins);
    }

    /**
     * Get active sessions of a user
     */
    public function getUserActiveSessions(int $userId): array
    {
        return ChargingSession::where('user_id', $userId)
            ->where('status', 'active')
            ->with(['chargingPoint', 'connector'])
            ->get()
            ->toArray();
    }

    /**
     * Get session history of a user
     */
    public function getUserSessionHistory(int $userId, int $limit = 10): array
    {
        return ChargingSession::where('user_id', $userId)
            ->where('status', 'completed')
            ->with(['chargingPoint', 'connector', 'pricingPlan'])
            ->orderBy('stopped_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}