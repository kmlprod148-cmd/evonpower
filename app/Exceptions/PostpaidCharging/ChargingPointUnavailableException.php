<?php

namespace App\Exceptions\PostpaidCharging;

class ChargingPointUnavailableException extends PostpaidChargingException
{
    public function __construct(string $reason = 'La borne n\'est pas disponible', array $context = [])
    {
        parent::__construct(
            $reason,
            'charging_point_unavailable',
            503, // Service Unavailable
            null,
            $context
        );
    }
}

