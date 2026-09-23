<?php

namespace App\DTO;

class StopChargeDTO
{
    public function __construct(
        public readonly string $chargingSessionId,
        public readonly string $userId,
    ) {
    }
}