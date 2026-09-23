<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PrepaidSessionCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public Reservation $reservation;
    public float $refundAmount;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation, float $refundAmount = 0)
    {
        $this->reservation = $reservation;
        $this->refundAmount = $refundAmount;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Fin de votre session de recharge EVON')
            ->markdown('emails.prepaid-session-completed', [
                'reservation' => $this->reservation,
                'refundAmount' => $this->refundAmount,
            ]);
    }
}
