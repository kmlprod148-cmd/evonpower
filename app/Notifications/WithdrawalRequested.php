<?php

namespace App\Notifications;

use App\Models\WithdrawalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalRequested extends Notification implements ShouldQueue
{
    use Queueable;

    protected $withdrawalRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(WithdrawalRequest $withdrawalRequest)
    {
        $this->withdrawalRequest = $withdrawalRequest;
    }

    /**
     * Get the notification's delivery channels.
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
            ->subject('Nouvelle demande de retrait')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Une nouvelle demande de retrait a été soumise.')
            ->line('**Détails du retrait:**')
            ->line('- Montant: ' . number_format($this->withdrawalRequest->amount, 2) . ' ' . ($this->withdrawalRequest->currency ?? 'MAD'))
            ->line('- Demandeur: ' . $this->withdrawalRequest->requester->name ?? 'Inconnu')
            ->line('- Date: ' . ($this->withdrawalRequest->requested_at ? $this->withdrawalRequest->requested_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i')))
            ->action('Voir la demande', route('admin.withdrawals.show', $this->withdrawalRequest->id))
            ->line('Merci de traiter cette demande dans les plus brefs délais.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nouvelle demande de retrait',
            'message' => 'Une nouvelle demande de retrait de ' . number_format($this->withdrawalRequest->amount, 2) . ' ' . ($this->withdrawalRequest->currency ?? 'MAD') . ' a été soumise.',
            'withdrawal_id' => $this->withdrawalRequest->id,
            'amount' => $this->withdrawalRequest->amount,
            'requester_name' => $this->withdrawalRequest->requester->name ?? 'Inconnu',
            'action_url' => route('admin.withdrawals.show', $this->withdrawalRequest->id),
        ];
    }
}
