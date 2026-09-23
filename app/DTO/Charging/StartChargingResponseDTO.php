<?php

namespace App\DTO\Charging;

class StartChargingResponseDTO
{
    public function __construct(
        public string $sessionId,
        public string $status
    ) {
    }
}