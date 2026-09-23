<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationConnectorUnlocked
{
    use Dispatchable, SerializesModels;

    public $reservation;
    public $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(Reservation $reservation, string $reason = 'unknown')
    {
        $this->reservation = $reservation;
        $this->reason = $reason;
    }
}
