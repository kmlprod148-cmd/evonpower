<?php

namespace App\Notifications;

use App\Models\WithdrawalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalStatusUpdated extends Notification implements ShouldQueue
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
        $mail = (new MailMessage)
            ->subject('Mise à jour du statut de votre demande de retrait')
            ->greeting('Bonjour ' . $notifiable->name . ',');

        switch ($this->withdrawalRequest->status) {
            case 'approved':
                $mail->line('Votre demande de retrait a été **approuvée**.')
                     ->line('Le montant de ' . number_format($this->withdrawalRequest->amount, 2) . ' ' . ($this->withdrawalRequest->currency ?? 'MAD') . ' sera bientôt transféré sur votre compte.');
                break;
            case 'paid':
                $mail->line('Votre demande de retrait a été traitée.')
                     ->line('Le montant de ' . number_format($this->withdrawalRequest->amount, 2) . ' ' . ($this->withdrawalRequest->currency ?? 'MAD') . ' a été transféré.');
                if ($this->withdrawalRequest->external_transaction_id) {
                    $mail->line('Référence de transaction: ' . $this->withdrawalRequest->external_transaction_id);
                }
                break;
            case 'rejected':
                $mail->line('Votre demande de retrait a été **rejetée**.')
                     ->line('Motif: ' . ($this->withdrawalRequest->collector_note ?? 'Non spécifié'));
                break;
            default:
                $mail->line('Le statut de votre demande de retrait a été mis à jour: ' . $this->withdrawalRequest->status);
        }

        if ($this->withdrawalRequest->processed_at) {
            $mail->line('Date de traitement: ' . $this->withdrawalRequest->processed_at->format('d/m/Y H:i'));
        }

        return $mail
            ->action('Voir les détails', route('partner.withdrawals.index'))
            ->line('Merci de votre confiance.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $title = 'Mise à jour du retrait';
        $message = '';

        switch ($this->withdrawalRequest->status) {
            case 'approved':
                $title = 'Retrait approuvé';
                $message = 'Votre demande de retrait de ' . number_format($this->withdrawalRequest->amount, 2) . ' ' . ($this->withdrawalRequest->currency ?? 'MAD') . ' a été approuvée.';
                break;
            case 'paid':
                $title = 'Retrait traité';
                $message = 'Le montant de ' . number_format($this->withdrawalRequest->amount, 2) . ' ' . ($this->withdrawalRequest->currency ?? 'MAD') . ' a été transféré.';
                break;
            case 'rejected':
                $title = 'Retrait rejeté';
                $message = 'Votre demande de retrait a été rejetée. Motif: ' . ($this->withdrawalRequest->collector_note ?? 'Non spécifié');
                break;
            default:
                $message = 'Statut: ' . $this->withdrawalRequest->status;
        }

        return [
            'title' => $title,
            'message' => $message,
            'withdrawal_id' => $this->withdrawalRequest->id,
            'amount' => $this->withdrawalRequest->amount,
            'status' => $this->withdrawalRequest->status,
            'action_url' => route('partner.withdrawals.index'),
        ];
    }
}
