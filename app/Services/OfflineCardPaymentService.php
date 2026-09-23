<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\Order;
use App\Enums\ReservationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use Throwable;

/**
 * Service de gestion des paiements par carte (CMI/Stripe) en mode hors ligne
 * 
 * Ce service permet de traiter les paiements CMI et Stripe comme des paiements hors ligne
 * avec confirmation automatique basée sur le statut de paiement réussi
 */
class OfflineCardPaymentService
{
    protected OfflinePaymentService $offlinePaymentService;
    protected UnifiedPaymentService $unifiedPaymentService;
    protected AdminConfigurationService $adminSettings;

    public function __construct(
        OfflinePaymentService $offlinePaymentService,
        UnifiedPaymentService $unifiedPaymentService,
        AdminConfigurationService $adminSettings
    ) {
        $this->offlinePaymentService = $offlinePaymentService;
        $this->unifiedPaymentService = $unifiedPaymentService;
        $this->adminSettings = $adminSettings;
    }

    /**
     * Traite un paiement CMI ou Stripe en mode hors ligne
     * 
     * @param Reservation $reservation
     * @param string $paymentMethod 'cmi' ou 'stripe'
     * @param array $options
     * @return array
     */
    public function processOfflineCardPayment(Reservation $reservation, string $paymentMethod, array $options = []): array
    {
        try {
            // Valider la méthode de paiement
            if (!in_array($paymentMethod, ['cmi', 'stripe'])) {
                throw new Exception("Méthode de paiement non supportée pour le mode hors ligne: {$paymentMethod}");
            }

            DB::beginTransaction();

            // Initier le paiement via le service unifié
            $paymentResult = $this->unifiedPaymentService->initiatePayment($reservation, $paymentMethod, $options);

            if (!$paymentResult['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'error' => $paymentResult['error'] ?? 'Erreur lors de l\'initiation du paiement',
                    'message' => $paymentResult['message'] ?? 'Erreur lors de l\'initiation du paiement'
                ];
            }

            // Créer une Order pour le paiement hors ligne
            $order = $this->createOfflineOrder($reservation, $paymentMethod, $paymentResult);

            // Mettre à jour la réservation
            $reservation->update([
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending_offline_payment',
                'status' => ReservationStatus::PENDING_CONFIRMATION->value,
                'order_id' => $order->id
            ]);

            // Trouver ou créer la transaction
            $transaction = Transaction::where('reservation_id', $reservation->id)->first();
            if ($transaction) {
                $transaction->update([
                    'payment_status' => 'pending_offline_payment',
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentResult['payment_reference'] ?? null,
                    'gateway_response' => $paymentResult['data'] ?? null,
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'offline_mode' => true,
                        'payment_initiated_at' => now()->toIso8601String(),
                        'order_id' => $order->id
                    ])
                ]);
            }

            DB::commit();

            Log::info('Paiement carte hors ligne initié', [
                'reservation_id' => $reservation->id,
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'amount' => $reservation->estimated_cost
            ]);

            return [
                'success' => true,
                'order_id' => $order->id,
                'payment_reference' => $paymentResult['payment_reference'],
                'redirect_url' => $paymentResult['redirect_url'] ?? null,
                'payment_url' => $paymentResult['payment_url'] ?? null,
                'session_id' => $paymentResult['session_id'] ?? null,
                'message' => 'Paiement initié. Votre demande sera soumise à confirmation après validation du paiement.'
            ];

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erreur traitement paiement carte hors ligne', [
                'reservation_id' => $reservation->id,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors du traitement du paiement'
            ];
        }
    }

    /**
     * Crée une Order pour le paiement hors ligne
     */
    protected function createOfflineOrder(Reservation $reservation, string $paymentMethod, array $paymentResult): Order
    {
        $orderDetails = [
            'reservation_id' => $reservation->id,
            'transaction_id' => null, // Sera mis à jour après création de la transaction
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentResult['payment_reference'] ?? null,
            'payment_data' => $paymentResult['data'] ?? null,
            'offline_mode' => true,
            'auto_confirm_on_success' => true, // Confirmation automatique si paiement réussi
            'created_at' => now()->toISOString(),
            'instructions' => $this->getPaymentInstructions($paymentMethod)
        ];

        return Order::create([
            'user_id' => $reservation->user_id,
            'plan_id' => $reservation->pricing_plan_id,
            'charging_point_id' => $reservation->charging_point_id,
            'amount' => $reservation->estimated_cost ?? $reservation->amount ?? 0,
            'status' => 'pending_offline_payment',
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentResult['payment_reference'] ?? Str::random(16),
            'details' => json_encode($orderDetails)
        ]);
    }

    /**
     * Gère le succès d'un paiement et crée automatiquement la demande de confirmation
     * 
     * @param string $paymentReference
     * @param array $webhookData
     * @return array
     */
    public function handlePaymentSuccessForOfflineMode(string $paymentReference, array $webhookData = []): array
    {
        try {
            DB::beginTransaction();

            // Trouver la transaction
            $transaction = $this->findTransactionByReference($paymentReference, $webhookData);
            
            if (!$transaction) {
                throw new Exception("Transaction non trouvée pour la référence: {$paymentReference}");
            }

            $reservation = $transaction->reservation;
            if (!$reservation) {
                throw new Exception("Réservation non trouvée pour la transaction: {$transaction->id}");
            }

            // Vérifier si c'est un paiement en mode hors ligne
            $isOfflineMode = $transaction->metadata['offline_mode'] ?? false;
            if (!$isOfflineMode) {
                // Si ce n'est pas en mode hors ligne, utiliser le traitement normal
                return $this->unifiedPaymentService->handlePaymentSuccess($paymentReference, $webhookData);
            }

            // Trouver l'Order associée
            $order = Order::where('payment_reference', $paymentReference)
                ->orWhere('details->payment_reference', $paymentReference)
                ->first();

            if (!$order && $reservation->order_id) {
                $order = Order::find($reservation->order_id);
            }

            if (!$order) {
                // Créer une nouvelle Order si elle n'existe pas
                $order = $this->createOfflineOrder($reservation, $transaction->payment_method, [
                    'payment_reference' => $paymentReference,
                    'data' => $webhookData
                ]);
            }

            // Mettre à jour la transaction avec le statut de paiement réussi
            $transaction->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'completed_at' => now(),
                'webhook_data' => $webhookData,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'payment_completed_at' => now()->toIso8601String(),
                    'payment_status' => 'paid',
                    'webhook_data' => $webhookData,
                    'awaiting_admin_confirmation' => true
                ])
            ]);

            // Mettre à jour l'Order pour indiquer que le paiement est réussi et en attente de confirmation admin
            $orderDetails = json_decode($order->details, true) ?? [];
            $orderDetails['payment_status'] = 'paid';
            $orderDetails['payment_completed_at'] = now()->toISOString();
            $orderDetails['webhook_data'] = $webhookData;
            $orderDetails['awaiting_admin_confirmation'] = true;

            $order->update([
                'status' => 'pending_admin_confirmation',
                'payment_status' => 'paid',
                'details' => json_encode($orderDetails)
            ]);

            // Mettre à jour la réservation
            $reservation->update([
                'payment_status' => 'paid',
                'status' => ReservationStatus::PENDING_CONFIRMATION->value,
                'order_id' => $order->id
            ]);

            DB::commit();

            Log::info('Paiement carte hors ligne réussi - en attente de confirmation admin', [
                'reservation_id' => $reservation->id,
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'payment_reference' => $paymentReference
            ]);

            // Optionnel : Envoyer une notification à l'admin
            $this->notifyAdminForConfirmation($order, $reservation);

            return [
                'success' => true,
                'reservation' => $reservation,
                'transaction' => $transaction,
                'order' => $order,
                'message' => 'Paiement réussi. Votre demande est en attente de confirmation par un administrateur.'
            ];

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erreur traitement succès paiement carte hors ligne', [
                'payment_reference' => $paymentReference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Trouve une transaction par référence
     */
    protected function findTransactionByReference(string $paymentReference, array $webhookData = []): ?Transaction
    {
        // Essayer par payment_reference
        $transaction = Transaction::where('payment_reference', $paymentReference)->first();
        if ($transaction) {
            return $transaction;
        }

        // Essayer par reservation_id
        if (is_numeric($paymentReference)) {
            $reservation = Reservation::find((int) $paymentReference);
            if ($reservation) {
                $transaction = Transaction::where('reservation_id', $reservation->id)->first();
                if ($transaction) {
                    return $transaction;
                }
            }
        }

        // Essayer via webhook data
        if (isset($webhookData['data']['object']['metadata']['transaction_id'])) {
            $transactionId = $webhookData['data']['object']['metadata']['transaction_id'];
            return Transaction::find($transactionId);
        }

        if (isset($webhookData['data']['object']['metadata']['reservation_id'])) {
            $reservationId = $webhookData['data']['object']['metadata']['reservation_id'];
            $reservation = Reservation::find($reservationId);
            if ($reservation) {
                return Transaction::where('reservation_id', $reservation->id)->first();
            }
        }

        return null;
    }

    /**
     * Obtient les instructions de paiement
     */
    protected function getPaymentInstructions(string $paymentMethod): string
    {
        switch ($paymentMethod) {
            case 'cmi':
                return 'Paiement par carte bancaire via CMI. Votre demande de recharge sera soumise à confirmation automatique après validation du paiement par un administrateur.';
            case 'stripe':
                return 'Paiement par carte bancaire via Stripe. Votre demande de recharge sera soumise à confirmation automatique après validation du paiement par un administrateur.';
            default:
                return 'Paiement par carte bancaire. Votre demande sera soumise à confirmation par un administrateur.';
        }
    }

    /**
     * Notifie l'admin pour confirmation
     */
    protected function notifyAdminForConfirmation(Order $order, Reservation $reservation): void
    {
        try {
            // Récupérer les admins
            $admins = \App\Models\User::whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })->get();

            foreach ($admins as $admin) {
                // Envoyer une notification
                $admin->notify(new \App\Notifications\OfflinePaymentPendingConfirmation($order, $reservation));
            }

            Log::info('Notifications admin envoyées pour confirmation paiement', [
                'order_id' => $order->id,
                'reservation_id' => $reservation->id
            ]);
        } catch (Throwable $e) {
            Log::warning('Erreur envoi notification admin', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Vérifie si une méthode de paiement peut être utilisée en mode hors ligne
     */
    public function isOfflineModeEnabled(string $paymentMethod): bool
    {
        $settingKey = "{$paymentMethod}_offline_mode_enabled";
        return $this->adminSettings->getSetting('payments', $settingKey, false);
    }

    /**
     * Obtient les méthodes de paiement disponibles en mode hors ligne
     */
    public function getAvailableOfflineCardMethods(): array
    {
        $methods = [];

        if ($this->isOfflineModeEnabled('cmi')) {
            $methods['cmi'] = [
                'name' => 'Paiement par carte bancaire - CMI',
                'description' => 'Paiement par carte bancaire via CMI. Votre demande sera soumise à confirmation par un administrateur. Le montant sera ajouté à votre solde après validation.',
                'enabled' => true
            ];
        }

        if ($this->isOfflineModeEnabled('stripe')) {
            $methods['stripe'] = [
                'name' => 'Paiement par carte bancaire - Stripe',
                'description' => 'Paiement par carte bancaire via Stripe. Votre demande sera soumise à confirmation par un administrateur. Le montant sera ajouté à votre solde après validation.',
                'enabled' => true
            ];
        }

        return $methods;
    }
}

