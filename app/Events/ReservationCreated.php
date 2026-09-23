<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Reservation $reservation;
    public array $reservationData;

    /**
     * Create a new event instance.
     */
    public function __construct(Reservation $reservation, array $reservationData = [])
    {
        $this->reservation = $reservation;
        $this->reservationData = $reservationData;
    }
}
