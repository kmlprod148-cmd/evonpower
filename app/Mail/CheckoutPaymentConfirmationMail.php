<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Reservation;
use App\Models\Transaction;

/**
 * Payment Confirmation Mail
 * 
 * Sent after successful payment for checkout sessions
 */
class CheckoutPaymentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Reservation $reservation;
    public ?Transaction $transaction;
    public ?string $partnerName;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation, ?Transaction $transaction = null)
    {
        $this->reservation = $reservation;
        $this->transaction = $transaction;
        $this->partnerName = $reservation->chargePoint?->partner?->name;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = '✅ ' . __('Payment Confirmed - Charging Session');

        return $this->subject($subject)
            ->view('emails.checkout-payment-confirmation')
            ->with([
                'reservation' => $this->reservation,
                'transaction' => $this->transaction,
                'user' => $this->reservation->user,
                'chargePoint' => $this->reservation->chargePoint,
                'partnerName' => $this->partnerName,
                'amount' => $this->transaction?->amount ?? $this->reservation->metadata['estimated_price'] ?? 0,
                'currency' => $this->transaction?->currency ?? $this->reservation->metadata['currency'] ?? 'MAD',
                'durationMinutes' => $this->reservation->duration_minutes,
                'estimatedEnergy' => $this->reservation->estimated_energy,
                'startTime' => $this->reservation->start_time,
            ]);
    }
}
