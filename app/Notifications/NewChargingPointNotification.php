<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewChargingPointNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $chargingPoint;

    /**
     * Create a new notification instance.
     */
    public function __construct($chargingPoint)
    {
        $this->chargingPoint = $chargingPoint;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau point de charge')
            ->line('Un nouveau point de charge a été ajouté : ' . $this->chargingPoint->name)
            ->action('Voir', url('/charging-points/' . $this->chargingPoint->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Un nouveau point de charge a été ajouté : ' . $this->chargingPoint->name,
            'charging_point_id' => $this->chargingPoint->id,
        ];
    }
}
