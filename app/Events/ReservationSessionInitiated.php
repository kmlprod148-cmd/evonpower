<?php

namespace App\Events;

use App\Models\Reservation;
use App\Models\ChargingSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationSessionInitiated
{
    use Dispatchable, SerializesModels;

    public $reservation;
    public $session;

    /**
     * Create a new event instance.
     */
    public function __construct(Reservation $reservation, ChargingSession $session)
    {
        $this->reservation = $reservation;
        $this->session = $session;
    }
}
