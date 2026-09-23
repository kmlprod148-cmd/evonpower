<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationPaymentConfirmed
{
    use Dispatchable, SerializesModels;

    public $reservation;
    public $paymentMethod;
    public $gatewayTransactionId;

    /**
     * Create a new event instance.
     */
    public function __construct(Reservation $reservation, string $paymentMethod = 'card', ?string $gatewayTransactionId = null)
    {
        $this->reservation = $reservation;
        $this->paymentMethod = $paymentMethod;
        $this->gatewayTransactionId = $gatewayTransactionId;
    }
}
