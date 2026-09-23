<?php

namespace App\DTO\Charging;

class StopChargingResponseDTO
{
    public function __construct(
        public string $sessionId,
        public string $status
    ) {
    }
}