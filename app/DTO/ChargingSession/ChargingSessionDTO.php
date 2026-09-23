<?php

namespace App\DTO\ChargingSession;

class ChargingSessionDTO
{
    /**
     * @var int Charging point ID
     */
    public $chargingPointId;

    /**
     * @var int Connector ID
     */
    public $connectorId;

    /**
     * @var int Pricing plan ID
     */
    public $pricingPlanId;

    /**
     * @var int|null User ID
     */
    public $userId;

    /**
     * @var array Additional metadata
     */
    public $metaData = [];

    /**
     * @var array|null Payment data
     */
    public $paymentData = null;

    /**
     * Constructor
     *
     * @param int $chargingPointId
     * @param int $connectorId
     * @param int $pricingPlanId
     * @param int|null $userId
     */
    public function __construct(int $chargingPointId, int $connectorId, int $pricingPlanId, ?int $userId = null)
    {
        $this->chargingPointId = $chargingPointId;
        $this->connectorId = $connectorId;
        $this->pricingPlanId = $pricingPlanId;
        $this->userId = $userId;
    }
}