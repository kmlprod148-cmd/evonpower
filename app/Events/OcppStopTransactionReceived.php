<?php

namespace App\Events;

use App\Models\ChargingSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OcppStopTransactionReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ChargingSession $session,
        public array $payload
    ) {}
}