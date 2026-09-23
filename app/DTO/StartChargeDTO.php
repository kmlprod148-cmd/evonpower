<?php

namespace App\DTO;

class StartChargeDTO
{
    public function __construct(
        public readonly string $chargingPointId,
        public readonly string $userId,
    ) {
    }
}