<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPlanCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $plan;

    /**
     * Create a new notification instance.
     */
    public function __construct($plan)
    {
        $this->plan = $plan;
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
            ->subject('Nouveau plan tarifaire')
            ->line('Un nouveau plan tarifaire a été créé : ' . $this->plan->name)
            ->action('Voir', url('/plans/' . $this->plan->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Un nouveau plan tarifaire a été créé : ' . $this->plan->name,
            'plan_id' => $this->plan->id,
        ];
    }
}
