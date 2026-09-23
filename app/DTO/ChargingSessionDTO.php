<?php

namespace App\DTO;

/**
 * Data Transfer Object pour les sessions de recharge
 */
class ChargingSessionDTO
{
    /**
     * ID de la borne de recharge
     * @var int
     */
    public $chargingPointId;

    /**
     * ID de la station
     * @var int
     */
    public $stationId;

    /**
     * ID du plan tarifaire
     * @var int
     */
    public $pricingPlanId;

    /**
     * ID de l'utilisateur
     * @var int|null
     */
    public $userId;

    /**
     * Énergie maximale à charger (en kWh) si applicable
     * @var float|null
     */
    public $maxEnergyKwh;

    /**
     * Durée maximale de recharge (en minutes) si applicable
     * @var int|null
     */
    public $maxDurationMinutes;

    /**
     * Montant maximal à charger (en devise) si applicable
     * @var float|null
     */
    public $maxAmount;

    /**
     * Constructeur
     *
     * @param int $chargingPointId ID de la borne
     * @param int $stationId ID de la station
     * @param int $pricingPlanId ID du plan tarifaire
     * @param int|null $userId ID de l'utilisateur (null si session anonyme)
     * @param float|null $maxEnergyKwh Énergie maximale à charger (optionnel)
     * @param int|null $maxDurationMinutes Durée maximale de recharge (optionnel)
     * @param float|null $maxAmount Montant maximal à charger (optionnel)
     */
    public function __construct(
        $chargingPointId,
        $stationId,
        $pricingPlanId,
        $userId = null,
        $maxEnergyKwh = null,
        $maxDurationMinutes = null,
        $maxAmount = null
    ) {
        $this->chargingPointId = $chargingPointId;
        $this->stationId = $stationId;
        $this->pricingPlanId = $pricingPlanId;
        $this->userId = $userId;
        $this->maxEnergyKwh = $maxEnergyKwh;
        $this->maxDurationMinutes = $maxDurationMinutes;
        $this->maxAmount = $maxAmount;
    }

    /**
     * Vérifie si la session est limitée par l'énergie
     *
     * @return bool
     */
    public function isEnergyLimited()
    {
        return $this->maxEnergyKwh !== null && $this->maxEnergyKwh > 0;
    }

    /**
     * Vérifie si la session est limitée par la durée
     *
     * @return bool
     */
    public function isDurationLimited()
    {
        return $this->maxDurationMinutes !== null && $this->maxDurationMinutes > 0;
    }

    /**
     * Vérifie si la session est limitée par le montant
     *
     * @return bool
     */
    public function isAmountLimited()
    {
        return $this->maxAmount !== null && $this->maxAmount > 0;
    }

    /**
     * Vérifie si la session est authentifiée (avec un utilisateur)
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->userId !== null;
    }

    /**
     * Convertit le DTO en tableau
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'charging_point_id' => $this->chargingPointId,
            'station_id' => $this->stationId,
            'pricing_plan_id' => $this->pricingPlanId,
            'user_id' => $this->userId,
            'max_energy_kwh' => $this->maxEnergyKwh,
            'max_duration_minutes' => $this->maxDurationMinutes,
            'max_amount' => $this->maxAmount
        ];
    }
}