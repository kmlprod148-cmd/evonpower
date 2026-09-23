<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Reservation;

class ReservationStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reservation;
    protected $status;
    protected $reason;

    public function __construct(Reservation $reservation, string $status, string $reason = null)
    {
        $this->reservation = $reservation;
        $this->status = $status;
        $this->reason = $reason;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $subject = $this->getSubject();
        $greeting = $this->getGreeting();
        $message = $this->getMessage();

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($message)
            ->line('Détails de votre réservation :')
            ->line('• ID: #' . $this->reservation->id)
            ->line('• Point de charge: ' . ($this->reservation->chargingPoint->name ?? 'N/A'))
            ->line('• Type: ' . ucfirst($this->reservation->reservation_type))
            ->line('• Valeur: ' . $this->reservation->reservation_value . ' ' . ($this->reservation->reservation_type === 'kwh' ? 'kWh' : 'min'))
            ->line('• Montant: ' . number_format($this->reservation->estimated_cost, 2) . ' EUR')
            ->when($this->reason, function ($mail) {
                return $mail->line('Raison: ' . $this->reason);
            })
            ->action('Voir ma réservation', route('reservations.show', $this->reservation))
            ->line('Merci d\'utiliser notre service de recharge !');
    }

    public function toDatabase($notifiable)
    {
        return [
            'reservation_id' => $this->reservation->id,
            'status' => $this->status,
            'reason' => $this->reason,
            'message' => $this->getMessage(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
        ];
    }

    private function getSubject()
    {
        switch ($this->status) {
            case 'approved':
                return 'Réservation approuvée - Votre recharge est confirmée';
            case 'rejected':
                return 'Réservation rejetée - Votre demande a été refusée';
            case 'pending':
                return 'Réservation en attente - Votre demande est en cours d\'examen';
            default:
                return 'Mise à jour de votre réservation';
        }
    }

    private function getGreeting()
    {
        $name = $this->reservation->user ? $this->reservation->user->name : 'Cher client';
        return "Bonjour {$name},";
    }

    private function getMessage()
    {
        switch ($this->status) {
            case 'approved':
                return 'Excellente nouvelle ! Votre réservation a été approuvée par le propriétaire du point de charge. Vous pouvez maintenant procéder à votre recharge.';
            case 'rejected':
                return 'Nous sommes désolés, mais votre réservation a été rejetée par le propriétaire du point de charge.';
            case 'pending':
                return 'Votre réservation a été soumise avec succès et est en cours d\'examen par le propriétaire du point de charge.';
            default:
                return 'Le statut de votre réservation a été mis à jour.';
        }
    }

    private function getIcon()
    {
        switch ($this->status) {
            case 'approved':
                return 'check-circle';
            case 'rejected':
                return 'times-circle';
            case 'pending':
                return 'clock';
            default:
                return 'info-circle';
        }
    }

    private function getColor()
    {
        switch ($this->status) {
            case 'approved':
                return 'success';
            case 'rejected':
                return 'danger';
            case 'pending':
                return 'warning';
            default:
                return 'info';
        }
    }
}
