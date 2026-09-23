<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfflinePaymentPendingConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;
    protected Reservation $reservation;

    public function __construct(Order $order, Reservation $reservation)
    {
        $this->order = $order;
        $this->reservation = $reservation;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Paiement en attente de confirmation - ' . $this->order->payment_method)
            ->line('Un nouveau paiement par carte bancaire nécessite votre confirmation.')
            ->line("Réservation #{$this->reservation->id}")
            ->line("Montant: {$this->order->amount} EUR")
            ->line("Méthode: " . strtoupper($this->order->payment_method))
            ->action('Voir la demande', route('admin.orders.show', $this->order->id))
            ->line('Le paiement a été validé avec succès et attend votre confirmation.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'offline_payment_pending_confirmation',
            'order_id' => $this->order->id,
            'reservation_id' => $this->reservation->id,
            'amount' => $this->order->amount,
            'payment_method' => $this->order->payment_method,
            'message' => "Un paiement {$this->order->payment_method} de {$this->order->amount} EUR nécessite votre confirmation."
        ];
    }
}

