<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\Log;

class TransactionNotificationService
{
    protected $adminNotificationService;

    public function __construct(AdminNotificationService $adminNotificationService)
    {
        $this->adminNotificationService = $adminNotificationService;
    }

    /**
     * Envoie une notification pour une nouvelle transaction importante
     */
    public function notifyNewTransaction(Transaction $transaction): void
    {
        try {
            $amount = $transaction->price_total;
            $user = $transaction->user;
            $chargingPoint = $transaction->chargingPoint;

            // Déterminer si la transaction est importante
            if ($this->isImportantTransaction($transaction)) {
                $this->sendImportantTransactionNotification($transaction);
            }

            // Notifier les utilisateurs concernés selon leur rôle
            $this->notifyUsersByRole($transaction);

            // Notifier les administrateurs pour les transactions de montant élevé
            if ($amount >= 1000) {
                $this->notifyAdminsForHighAmountTransaction($transaction);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de notification de transaction: ' . $e->getMessage());
        }
    }

    /**
     * Détermine si une transaction est importante
     */
    private function isImportantTransaction(Transaction $transaction): bool
    {
        // Transaction importante si :
        // - Montant élevé (>= 500 EUR)
        // - Transaction admin
        // - Transaction échouée
        // - Transaction d'activation
        
        return $transaction->price_total >= 500 ||
               $transaction->transaction_type === 'admin' ||
               $transaction->status === 'failed' ||
               $transaction->transaction_category === 'activation';
    }

    /**
     * Envoie une notification pour une transaction importante
     */
    private function sendImportantTransactionNotification(Transaction $transaction): void
    {
        $title = 'Transaction Importante';
        $message = sprintf(
            'Transaction #%d de %s EUR pour %s (%s)',
            $transaction->id,
            number_format($transaction->price_total, 2),
            $transaction->user->name ?? 'Utilisateur inconnu',
            $transaction->chargingPoint->name ?? 'Borne inconnue'
        );

        $this->adminNotificationService->create(
            $title,
            $message,
            'warning',
            [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'amount' => $transaction->price_total,
                'type' => 'important_transaction'
            ]
        );
    }

    /**
     * Notifie les utilisateurs selon leur rôle
     */
    private function notifyUsersByRole(Transaction $transaction): void
    {
        $user = $transaction->user;
        
        if (!$user) return;

        // Notifier l'utilisateur concerné
        $this->notifyUser($user, $transaction);

        // Notifier l'intégrateur si applicable
        if ($transaction->chargingPoint && $transaction->chargingPoint->integrator) {
            $this->notifyIntegrator($transaction->chargingPoint->integrator, $transaction);
        }

        // Notifier l'opérateur si applicable
        if ($transaction->chargingPoint && $transaction->chargingPoint->user) {
            $this->notifyOperator($transaction->chargingPoint->user, $transaction);
        }
    }

    /**
     * Notifie un utilisateur spécifique
     */
    private function notifyUser(User $user, Transaction $transaction): void
    {
        $title = 'Nouvelle Transaction';
        $message = sprintf(
            'Votre transaction #%d de %s EUR a été %s',
            $transaction->id,
            number_format($transaction->price_total, 2),
            $this->getStatusLabel($transaction->status)
        );

        $this->adminNotificationService->create(
            $title,
            $message,
            $this->getNotificationType($transaction),
            [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'type' => 'user_transaction'
            ]
        );
    }

    /**
     * Notifie un intégrateur
     */
    private function notifyIntegrator(User $integrator, Transaction $transaction): void
    {
        $title = 'Transaction sur votre réseau';
        $message = sprintf(
            'Transaction #%d de %s EUR sur la borne %s',
            $transaction->id,
            number_format($transaction->price_total, 2),
            $transaction->chargingPoint->name
        );

        $this->adminNotificationService->create(
            $title,
            $message,
            'info',
            [
                'user_id' => $integrator->id,
                'transaction_id' => $transaction->id,
                'type' => 'integrator_transaction'
            ]
        );
    }

    /**
     * Notifie un opérateur
     */
    private function notifyOperator(User $operator, Transaction $transaction): void
    {
        $title = 'Transaction sur votre borne';
        $message = sprintf(
            'Transaction #%d de %s EUR sur votre borne %s',
            $transaction->id,
            number_format($transaction->price_total, 2),
            $transaction->chargingPoint->name
        );

        $this->adminNotificationService->create(
            $title,
            $message,
            'info',
            [
                'user_id' => $operator->id,
                'transaction_id' => $transaction->id,
                'type' => 'operator_transaction'
            ]
        );
    }

    /**
     * Notifie les administrateurs pour les transactions de montant élevé
     */
    private function notifyAdminsForHighAmountTransaction(Transaction $transaction): void
    {
        $title = 'Transaction de Montant Élevé';
        $message = sprintf(
            'Transaction #%d de %s EUR nécessite votre attention',
            $transaction->id,
            number_format($transaction->price_total, 2)
        );

        $this->adminNotificationService->create(
            $title,
            $message,
            'warning',
            [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->price_total,
                'type' => 'high_amount_transaction'
            ]
        );
    }

    /**
     * Obtient le type de notification selon le statut
     */
    private function getNotificationType(Transaction $transaction): string
    {
        return match($transaction->status) {
            'completed' => 'success',
            'pending' => 'warning',
            'cancelled' => 'danger',
            'failed' => 'danger',
            default => 'info'
        };
    }

    /**
     * Obtient le label du statut
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'completed' => 'terminée',
            'pending' => 'en attente',
            'cancelled' => 'annulée',
            'failed' => 'échouée',
            default => $status
        };
    }

    /**
     * Envoie une notification pour une transaction mise à jour
     */
    public function notifyTransactionUpdated(Transaction $transaction, array $changes): void
    {
        try {
            $title = 'Transaction Mise à Jour';
            $message = sprintf(
                'La transaction #%d a été mise à jour',
                $transaction->id
            );

            // Ajouter les détails des changements
            if (!empty($changes)) {
                $changeDetails = [];
                foreach ($changes as $field => $value) {
                    $changeDetails[] = "$field: $value";
                }
                $message .= ' - Changements: ' . implode(', ', $changeDetails);
            }

            $this->adminNotificationService->create(
                $title,
                $message,
                'info',
                [
                    'transaction_id' => $transaction->id,
                    'changes' => $changes,
                    'type' => 'transaction_updated'
                ]
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de notification de mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Envoie une notification pour une transaction supprimée
     */
    public function notifyTransactionDeleted(Transaction $transaction): void
    {
        try {
            $title = 'Transaction Supprimée';
            $message = sprintf(
                'La transaction #%d de %s EUR a été supprimée',
                $transaction->id,
                number_format($transaction->price_total, 2)
            );

            $this->adminNotificationService->create(
                $title,
                $message,
                'danger',
                [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->price_total,
                    'type' => 'transaction_deleted'
                ]
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de notification de suppression: ' . $e->getMessage());
        }
    }
}
