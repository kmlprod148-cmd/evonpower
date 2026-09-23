<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBusinessProfileCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $businessProfile;

    /**
     * Create a new notification instance.
     */
    public function __construct($businessProfile)
    {
        $this->businessProfile = $businessProfile;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Nouveau profil entreprise')
            ->line('Un nouveau profil entreprise a été créé : ' . $this->businessProfile->name)
            ->action('Voir', url('/business-profiles/' . $this->businessProfile->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'message' => 'Un nouveau profil entreprise a été créé : ' . $this->businessProfile->name,
            'business_profile_id' => $this->businessProfile->id,
        ];
    }
}
