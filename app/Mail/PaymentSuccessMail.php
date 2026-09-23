<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\Reservation;

class PaymentSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $reservation;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, Reservation $reservation)
    {
        $this->order = $order;
        $this->reservation = $reservation;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('✅ Paiement Confirmé - Réservation EvonPower')
                    ->view('emails.payment-success')
                    ->with([
                        'order' => $this->order,
                        'reservation' => $this->reservation,
                        'user' => $this->reservation->user,
                        'chargingPoint' => $this->reservation->chargingPoint,
                        'amount' => $this->order->amount,
                        'currency' => 'EUR',
                        'paymentMethod' => $this->order->payment_method,
                        'reservationDate' => $this->reservation->start_time,
                        'reservationDuration' => $this->reservation->duration_minutes,
                        'reservationEnergy' => $this->reservation->energy_kwh
                    ]);
    }
}
