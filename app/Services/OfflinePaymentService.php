<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion des paiements hors ligne
 *
 * Ce service gère les paiements par virement bancaire et espèces
 */
class OfflinePaymentService
{
    /**
     * Traite un paiement hors ligne
     *
     * @param Transaction $transaction
     * @param string $paymentMethod
     * @param array $paymentData
     * @return array
     */
    public function processOfflinePayment(Transaction $transaction, string $paymentMethod, array $paymentData = []): array
    {
        try {
            Log::info('Traitement paiement hors ligne', [
                'transaction_id' => $transaction->id,
                'payment_method' => $paymentMethod,
                'amount' => $transaction->price_total
            ]);

            // Créer une commande pour le paiement hors ligne
            $order = Order::create([
                'user_id' => $transaction->user_id,
                'plan_id' => $transaction->plan_id ?? null,
                'charging_point_id' => $transaction->charging_point_id ?? null,
                'amount' => $transaction->price_total,
                'status' => 'pending_offline_payment',
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentMethod . '_' . $transaction->id . '_' . time(),
                'details' => json_encode([
                    'transaction_id' => $transaction->id,
                    'payment_method' => $paymentMethod,
                    'instructions' => $this->getPaymentInstructions($paymentMethod),
                    'bank_details' => $paymentMethod === 'bank_transfer' ? $this->getBankDetails() : null,
                    'payment_data' => $paymentData,
                    'created_at' => now()->toISOString()
                ])
            ]);

            // Mettre à jour le statut de la transaction
            $transaction->update([
                'payment_status' => 'pending_offline_payment',
                'payment_method' => $paymentMethod
            ]);

            return [
                'success' => true,
                'order_id' => $order->id,
                'payment_reference' => $order->payment_reference,
                'instructions' => $this->getPaymentInstructions($paymentMethod),
                'bank_details' => $paymentMethod === 'bank_transfer' ? $this->getBankDetails() : null,
                'message' => 'Paiement hors ligne enregistré avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur traitement paiement hors ligne', [
                'transaction_id' => $transaction->id,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du traitement du paiement hors ligne: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Confirme un paiement hors ligne (admin seulement)
     *
     * @param Order $order
     * @param array $confirmationData
     * @return array
     */
    public function confirmOfflinePayment(Order $order, array $confirmationData = []): array
    {
        try {
            // Vérifier que c'est bien un paiement hors ligne (incluant CMI et Stripe en mode hors ligne)
            $offlineMethods = ['bank_transfer', 'cash', 'cmi', 'stripe'];
            if (!in_array($order->payment_method, $offlineMethods)) {
                return [
                    'success' => false,
                    'message' => 'Cette commande n\'est pas un paiement hors ligne'
                ];
            }

            // Mettre à jour la commande
            $order->update([
                'status' => 'completed',
                'payment_status' => 'completed',
                'details' => json_encode(array_merge(
                    json_decode($order->details, true),
                    [
                        'confirmed_at' => now()->toISOString(),
                        'confirmed_by' => auth()->id(),
                        'confirmation_data' => $confirmationData
                    ]
                ))
            ]);

            // Mettre à jour la transaction associée
            if (isset($order->details['transaction_id'])) {
                $transaction = Transaction::find($order->details['transaction_id']);
                if ($transaction) {
                    $transaction->update([
                        'payment_status' => 'paid',
                        'payment_method' => $order->payment_method
                    ]);
                }
            }

            Log::info('Paiement hors ligne confirmé', [
                'order_id' => $order->id,
                'payment_method' => $order->payment_method,
                'amount' => $order->amount
            ]);

            return [
                'success' => true,
                'message' => 'Paiement hors ligne confirmé avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur confirmation paiement hors ligne', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la confirmation du paiement: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Annule un paiement hors ligne
     *
     * @param Order $order
     * @param string $reason
     * @return array
     */
    public function cancelOfflinePayment(Order $order, string $reason = ''): array
    {
        try {
            // Vérifier que c'est bien un paiement hors ligne (incluant CMI et Stripe en mode hors ligne)
            $offlineMethods = ['bank_transfer', 'cash', 'cmi', 'stripe'];
            if (!in_array($order->payment_method, $offlineMethods)) {
                return [
                    'success' => false,
                    'message' => 'Cette commande n\'est pas un paiement hors ligne'
                ];
            }

            // Mettre à jour la commande
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
                'details' => json_encode(array_merge(
                    json_decode($order->details, true),
                    [
                        'cancelled_at' => now()->toISOString(),
                        'cancelled_by' => auth()->id(),
                        'cancellation_reason' => $reason
                    ]
                ))
            ]);

            // Mettre à jour la transaction associée
            if (isset($order->details['transaction_id'])) {
                $transaction = Transaction::find($order->details['transaction_id']);
                if ($transaction) {
                    $transaction->update([
                        'payment_status' => 'cancelled',
                        'payment_method' => $order->payment_method
                    ]);
                }
            }

            Log::info('Paiement hors ligne annulé', [
                'order_id' => $order->id,
                'payment_method' => $order->payment_method,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'message' => 'Paiement hors ligne annulé avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur annulation paiement hors ligne', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'annulation du paiement: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtient les instructions de paiement pour une méthode donnée
     *
     * @param string $paymentMethod
     * @return string
     */
    protected function getPaymentInstructions(string $paymentMethod): string
    {
        $adminSettings = app(AdminConfigurationService::class);
        $instructions = $adminSettings->getSetting('payments', 'offline_payment_instructions', '');

        if (empty($instructions)) {
            switch ($paymentMethod) {
                case 'bank_transfer':
                    return 'Veuillez effectuer un virement bancaire vers notre compte. Les coordonnées bancaires vous seront communiquées séparément.';
                case 'cash':
                    return 'Veuillez vous rendre à notre point de service pour effectuer le paiement en espèces.';
                default:
                    return 'Veuillez contacter notre équipe pour finaliser le paiement.';
            }
        }

        return $instructions;
    }

    /**
     * Obtient les coordonnées bancaires
     *
     * @return string
     */
    protected function getBankDetails(): string
    {
        $adminSettings = app(AdminConfigurationService::class);
        return $adminSettings->getSetting('payments', 'bank_details', '');
    }

    /**
     * Vérifie si une méthode de paiement hors ligne est activée
     *
     * @param string $paymentMethod
     * @return bool
     */
    public function isOfflinePaymentEnabled(string $paymentMethod): bool
    {
        $adminSettings = app(AdminConfigurationService::class);

        switch ($paymentMethod) {
            case 'bank_transfer':
                return $adminSettings->getSetting('payments', 'bank_transfer_enabled', true);
            case 'cash':
                return $adminSettings->getSetting('payments', 'cash_enabled', true);
            case 'offline':
                return $adminSettings->getSetting('payments', 'offline_enabled', true);
            case 'cmi':
                return $adminSettings->getSetting('payments', 'cmi_offline_mode_enabled', false);
            case 'stripe':
                return $adminSettings->getSetting('payments', 'stripe_offline_mode_enabled', false);
            default:
                return false;
        }
    }
}
