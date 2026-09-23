<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HierarchicalTransactionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = $this->getSubjectByType();
        $greeting = $this->getGreetingByType();
        $message = $this->getMessageByType();

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($message)
            ->line('Montant: ' . number_format($this->data['amount'], 2) . ' EUR')
            ->line('Borne de recharge: #' . $this->data['charging_point_id'])
            ->action('Voir les détails', url('/transactions/' . $this->data['transaction_id']))
            ->line('Merci d\'utiliser notre plateforme!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => $this->data['type'],
            'transaction_id' => $this->data['transaction_id'],
            'amount' => $this->data['amount'],
            'charging_point_id' => $this->data['charging_point_id'],
            'percentage' => $this->data['percentage'] ?? null,
            'operator_name' => $this->data['operator_name'] ?? null,
            'integrator_name' => $this->data['integrator_name'] ?? null,
            'admin_name' => $this->data['admin_name'] ?? null,
            'message' => $this->getMessageByType(),
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Get subject by notification type
     */
    private function getSubjectByType(): string
    {
        switch ($this->data['type']) {
            case 'admin_revenue':
                return 'Nouveau revenu administrateur - Transaction #' . $this->data['transaction_id'];
            case 'integrator_revenue':
                return 'Nouveau revenu intégrateur - Transaction #' . $this->data['transaction_id'];
            case 'operator_revenue':
                return 'Nouveau revenu opérateur - Transaction #' . $this->data['transaction_id'];
            default:
                return 'Nouvelle transaction hiérarchique - #' . $this->data['transaction_id'];
        }
    }

    /**
     * Get greeting by notification type
     */
    private function getGreetingByType(): string
    {
        switch ($this->data['type']) {
            case 'admin_revenue':
                return 'Bonjour Administrateur,';
            case 'integrator_revenue':
                return 'Bonjour Intégrateur,';
            case 'operator_revenue':
                return 'Bonjour Opérateur,';
            default:
                return 'Bonjour,';
        }
    }

    /**
     * Get message by notification type
     */
    private function getMessageByType(): string
    {
        switch ($this->data['type']) {
            case 'admin_revenue':
                $percentage = isset($this->data['percentage']) ? ' (' . $this->data['percentage'] . '%)' : '';
                return "Vous avez reçu votre part administrateur{$percentage} d'une transaction de recharge. " .
                       "Opérateur: {$this->data['operator_name']}, Intégrateur: {$this->data['integrator_name']}.";
                       
            case 'integrator_revenue':
                $percentage = isset($this->data['percentage']) ? ' (' . $this->data['percentage'] . '%)' : '';
                return "Vous avez reçu votre part intégrateur{$percentage} d'une transaction de recharge. " .
                       "Opérateur: {$this->data['operator_name']}, Admin: {$this->data['admin_name']}.";
                       
            case 'operator_revenue':
                return "Vous avez reçu votre part opérateur d'une transaction de recharge. " .
                       "Intégrateur: {$this->data['integrator_name']}, Admin: {$this->data['admin_name']}.";
                       
            default:
                return "Une nouvelle transaction hiérarchique a été traitée.";
        }
    }

    /**
     * Get the notification icon by type
     */
    public function getIcon(): string
    {
        switch ($this->data['type']) {
            case 'admin_revenue':
                return 'fas fa-crown';
            case 'integrator_revenue':
                return 'fas fa-cogs';
            case 'operator_revenue':
                return 'fas fa-user-tie';
            default:
                return 'fas fa-exchange-alt';
        }
    }

    /**
     * Get the notification color by type
     */
    public function getColor(): string
    {
        switch ($this->data['type']) {
            case 'admin_revenue':
                return 'text-red-600';
            case 'integrator_revenue':
                return 'text-blue-600';
            case 'operator_revenue':
                return 'text-green-600';
            default:
                return 'text-gray-600';
        }
    }
}
