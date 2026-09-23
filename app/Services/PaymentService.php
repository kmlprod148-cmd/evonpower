<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Services\CMIReservationPaymentService;
use App\Notifications\ReservationStatusNotification;
use App\Enums\ReservationStatus;
use App\Services\ReservationPaymentApprovalService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentService
{
    protected $cmiService;
    protected $stripeService;

    public function __construct()
    {
        // Initialize payment gateways with proper service resolution
        try {
            $this->cmiService = app(CMIPaymentService::class);
        } catch (\Exception $e) {
            $this->cmiService = null;
        }
        
        try {
            $this->stripeService = app(StripePaymentService::class);
        } catch (\Exception $e) {
            $this->stripeService = null;
        }
    }

    /**
     * Process payment for a reservation
     */
    public function processPayment(Reservation $reservation, string $paymentMethod, array $paymentData = [])
    {
        try {
            DB::beginTransaction();

            Log::info('Processing payment for reservation', [
                'reservation_id' => $reservation->id,
                'payment_method' => $paymentMethod,
                'amount' => $reservation->estimated_cost
            ]);

            $result = $this->initiatePayment($reservation, $paymentMethod, $paymentData);

            if ($result['success']) {
            // Update reservation status (utiliser 'pending' car 'payment_pending' n'existe pas dans l'enum)
            $reservation->update([
                'status' => ReservationStatus::PENDING->value,
                'payment_method' => $paymentMethod,
                'payment_status' => 'PENDING',
            ]);

            // Create transaction record
            $transaction = $this->createTransaction($reservation, $result);
            
            // Stocker les données de paiement dans la transaction
            if ($transaction && isset($result['data'])) {
                $transaction->update([
                    'gateway_response' => $result['data'],
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'payment_data' => $result['data']
                    ])
                ]);
            }

                DB::commit();

                return [
                    'success' => true,
                    'redirect_url' => $result['redirect_url'] ?? null,
                    'message' => 'Paiement initié avec succès'
                ];
            } else {
                DB::rollback();
                return [
                    'success' => false,
                    'error' => $result['error'],
                    'message' => 'Erreur lors de l\'initiation du paiement'
                ];
            }

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Payment processing failed', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors du traitement du paiement'
            ];
        }
    }

    /**
     * Initiate payment based on method
     */
    private function initiatePayment(Reservation $reservation, string $paymentMethod, array $paymentData)
    {
        switch ($paymentMethod) {
            case 'cmi':
                return $this->processCmiPayment($reservation, $paymentData);
            case 'stripe':
                return $this->processStripePayment($reservation, $paymentData);
            default:
                throw new Exception('Méthode de paiement non supportée');
        }
    }

    /**
     * Process CMI payment
     * Utilise le nouveau service CMI basé sur les fichiers API fournis
     */
    private function processCmiPayment(Reservation $reservation, array $paymentData)
    {
        try {
            // Utiliser le nouveau service CMI pour les réservations
            $cmiService = app(CMIReservationPaymentService::class);
            
            // Préparer les données de paiement selon l'API CMI
            $cmiPaymentData = $cmiService->preparePaymentData($reservation, $paymentData);
            $paymentUrl = $cmiService->getPaymentUrl();

            return [
                'success' => true,
                'reference' => 'CMI-' . $reservation->id . '-' . time(),
                'redirect_url' => route('payment.cmi.reservation.send', $reservation->id),
                'data' => $cmiPaymentData
            ];

        } catch (Exception $e) {
            Log::error('CMI Payment processing failed', [
                'reservation_id' => $reservation->id,
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
     * Process Stripe payment
     */
    private function processStripePayment(Reservation $reservation, array $paymentData)
    {
        try {
            // Utiliser PaymentKeysService pour obtenir les clés Stripe actives
            $paymentKeysService = app(\App\Services\PaymentKeysService::class);
            $stripeKeys = $paymentKeysService->getActiveStripeKeys();
            
            if (empty($stripeKeys['secret_key'])) {
                Log::error('Stripe Payment processing failed: secret key missing', [
                    'reservation_id' => $reservation->id,
                    'hint' => 'Check AdminSetting stripe_test_secret_key or STRIPE_SECRET_KEY / STRIPE_SECRET env vars on this server.',
                ]);
                return [
                    'success' => false,
                    'error' => 'Configuration Stripe incomplète. Veuillez configurer les clés API dans les paramètres admin.'
                ];
            }

            \Stripe\Stripe::setApiKey($stripeKeys['secret_key']);

            // Montant en centimes (Stripe utilise les centimes)
            $amount = (int) round(($reservation->estimated_cost ?? $reservation->amount ?? 0) * 100);

            // Use currency from request data, fall back to config, then EUR
            $currency = strtolower(
                $paymentData['currency']
                ?? $stripeKeys['currency']
                ?? config('payments.stripe.currency', 'eur')
            );

            // Validate minimum amount for Stripe (50 cents minimum)
            if ($amount < 50) {
                return [
                    'success' => false,
                    'error' => 'Le montant minimum pour Stripe est de 0.50 ' . strtoupper($currency),
                ];
            }

            // Créer une session de paiement Stripe Checkout
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => "Réservation #{$reservation->id}",
                            'description' => "Réservation EVON - " . ($reservation->chargingPoint->name ?? 'Borne de charge'),
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                // {CHECKOUT_SESSION_ID} is a Stripe template literal — do NOT url-encode it
                'success_url' => route('payment.reservation.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}&reservation_id=' . $reservation->id,
                'cancel_url'  => route('payment.reservation.stripe.cancel') . '?reservation_id=' . $reservation->id,
                // webhook_url is NOT a valid Stripe parameter — webhooks are configured in the Dashboard
                'metadata' => [
                    'reservation_id'    => (string) $reservation->id,
                    'charging_point_id' => (string) $reservation->charging_point_id,
                    'user_id'           => (string) ($reservation->user_id ?? ''),
                ],
            ]);

            Log::info('Stripe payment session created for reservation', [
                'reservation_id' => $reservation->id,
                'session_id' => $session->id,
                'amount' => $amount / 100
            ]);

            return [
                'success' => true,
                'reference' => $session->id,
                'redirect_url' => $session->url,
                'data' => [
                    'session_id' => $session->id,
                    'payment_intent_id' => $session->payment_intent ?? null
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Stripe Payment processing failed', [
                'reservation_id' => $reservation->id,
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
     * Create transaction record
     */
    private function createTransaction(Reservation $reservation, array $paymentResult)
    {
        $reference  = $paymentResult['reference'] ?? null;
        $isStripe   = str_starts_with((string) $reference, 'cs_') || str_starts_with((string) $reference, 'pi_');
        $updateData = [
            'payment_reference' => $reference,
            'payment_method'    => $reservation->payment_method ?? 'stripe',
            'status'            => 'pending',
            'gateway_response'  => $paymentResult['data'] ?? null,
        ];
        if ($isStripe) {
            $updateData['stripe_session_id'] = $reference;
        }

        // Update existing transaction created by ReservationService
        $existingTransaction = Transaction::where('reservation_id', $reservation->id)->first();
        if ($existingTransaction) {
            $existingTransaction->update($updateData);
            return $existingTransaction;
        }

        // Fallback: create a new one (should rarely happen)
        return Transaction::create(array_merge($updateData, [
            'reservation_id'   => $reservation->id,
            'charging_point_id' => $reservation->charging_point_id,
            'user_id'          => $reservation->user_id,
            'amount'           => $reservation->estimated_cost,
            'currency'         => 'EUR',
            'transaction_type' => 'client',
        ]));
    }

    /**
     * Handle payment success
     */
    public function handlePaymentSuccess(string $paymentReference, array $webhookData = [])
    {
        try {
            $transaction = null;

            // 1. Try by payment_reference (guarded — column may not exist on old DBs)
            try {
                $transaction = Transaction::where('payment_reference', $paymentReference)->first();
            } catch (\Exception $e) {
                Log::warning('handlePaymentSuccess: payment_reference lookup failed', ['error' => $e->getMessage()]);
            }

            // 2. Try by stripe_session_id for Stripe checkout sessions
            if (!$transaction && str_starts_with($paymentReference, 'cs_')) {
                try {
                    $transaction = Transaction::where('stripe_session_id', $paymentReference)->first();
                } catch (\Exception $e) {
                    // column may not exist yet
                }
            }

            // 3. Numeric paymentReference = direct reservation ID (CMI uses oid)
            if (!$transaction && is_numeric($paymentReference)) {
                $reservation = Reservation::find((int) $paymentReference);
                if ($reservation) {
                    $transaction = $reservation->transaction;
                }
            }

            // 4. Stripe success callback passes reservation_id directly in webhookData
            if (!$transaction && isset($webhookData['reservation_id'])) {
                $reservation = Reservation::find($webhookData['reservation_id']);
                if ($reservation) {
                    $transaction = $reservation->transaction;
                }
            }

            // 5. Stripe webhook event format: data.object.metadata.reservation_id
            if (!$transaction && isset($webhookData['data']['object']['metadata']['reservation_id'])) {
                $reservation = Reservation::find($webhookData['data']['object']['metadata']['reservation_id']);
                if ($reservation) {
                    $transaction = $reservation->transaction;
                }
            }

            // 6. Last resort: find or create by reservation_id when paymentReference is numeric
            if (!$transaction && is_numeric($paymentReference)) {
                $reservation = Reservation::find((int) $paymentReference);
                if ($reservation) {
                    $transaction = Transaction::firstOrCreate(
                        ['reservation_id' => $reservation->id],
                        [
                            'charging_point_id' => $reservation->charging_point_id,
                            'user_id'           => $reservation->user_id,
                            'amount'            => $reservation->estimated_cost,
                            'currency'          => 'EUR',
                            'payment_method'    => $reservation->payment_method ?? 'stripe',
                            'payment_reference' => $paymentReference,
                            'status'            => 'pending',
                            'transaction_type'  => 'client',
                        ]
                    );
                }
            }

            if (!$transaction) {
                throw new Exception('Transaction non trouvée pour la référence: ' . $paymentReference);
            }

            $reservation = $transaction->reservation;
            
            if (!$reservation) {
                throw new Exception('Réservation non trouvée pour la transaction: ' . $transaction->id);
            }

            DB::beginTransaction();

            // Update transaction status
            $transaction->update([
                'status' => 'completed',
                'completed_at' => now(),
                'webhook_data' => $webhookData
            ]);

            $approvalService = app(ReservationPaymentApprovalService::class);
            $approvalResult = $approvalService->applyPaymentSuccess(
                $reservation,
                $transaction->payment_method ?? $reservation->payment_method,
                'payment_gateway'
            );

            $reservation = $approvalResult['reservation'];
            $autoApproved = (bool) ($approvalResult['auto_approved'] ?? false);

            $notificationStatus = $autoApproved ? 'approved' : 'pending';

            // Notify charging point owner
            $this->notifyChargingPointOwner($reservation, $notificationStatus);

            // Notify user
            $this->notifyUser($reservation, $notificationStatus);

            if ($autoApproved) {
                DB::afterCommit(function () use ($approvalService, $reservation) {
                    $approvalService->dispatchApprovalEvent($reservation, 'payment_gateway');
                });
            }

            DB::commit();

            Log::info('Payment success handled', [
                'reservation_id' => $reservation->id,
                'transaction_id' => $transaction->id,
                'payment_reference' => $paymentReference
            ]);

            return [
                'success' => true,
                'reservation' => $reservation
            ];

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Payment success handling failed', [
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
     * Handle payment failure
     */
    public function handlePaymentFailure(string $paymentReference, string $reason = '')
    {
        try {
            $transaction = null;

            try {
                $transaction = Transaction::where('payment_reference', $paymentReference)->first();
            } catch (\Exception $e) {
                // column may not exist on older installs
            }

            if (!$transaction && is_numeric($paymentReference)) {
                $reservation = Reservation::find((int) $paymentReference);
                if ($reservation) {
                    $transaction = $reservation->transaction;
                }
            }

            if (!$transaction) {
                throw new Exception('Transaction non trouvée pour la référence: ' . $paymentReference);
            }

            $reservation = $transaction->reservation;
            
            if (!$reservation) {
                throw new Exception('Réservation non trouvée pour la transaction: ' . $transaction->id);
            }

            DB::beginTransaction();

            // Update transaction status
            $transaction->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $reason
            ]);

            // Update reservation status — 'payment_failed' is not a valid ReservationStatus case,
            // so we use CANCELED and track the failure via payment_status.
            $reservation->update([
                'status' => ReservationStatus::CANCELED,
                'payment_status' => 'FAILED'
            ]);

            // Notify user
            $this->notifyUser($reservation, 'payment_failed', $reason);

            DB::commit();

            Log::info('Payment failure handled', [
                'reservation_id' => $reservation->id,
                'transaction_id' => $transaction->id,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'reservation' => $reservation
            ];

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Payment failure handling failed', [
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
     * Notify charging point owner
     */
    private function notifyChargingPointOwner(Reservation $reservation, string $status = 'pending')
    {
        $chargingPoint = $reservation->chargingPoint;
        $owner = $chargingPoint->user
            ?? $chargingPoint->partner?->user
            ?? $chargingPoint->businessProfile?->user;

        if ($owner) {
            $owner->notify(new ReservationStatusNotification($reservation, $status));
        }
    }

    /**
     * Notify user
     */
    private function notifyUser(Reservation $reservation, string $status, ?string $reason = null)
    {
        $user = $reservation->user;
        
        if ($user) {
            $user->notify(new ReservationStatusNotification($reservation, $status, $reason));
        }
    }

    /**
     * Process refund
     */
    public function processRefund(Reservation $reservation, float $amount = null)
    {
        try {
            $amount = $amount ?? $reservation->estimated_cost;
            $transaction = $reservation->transactions()->where('status', 'completed')->first();

            if (!$transaction) {
                throw new Exception('Transaction non trouvée');
            }

            $refundResult = $this->initiateRefund($transaction, $amount);

            if ($refundResult['success']) {
                // Update transaction
                $transaction->update([
                    'refund_amount' => $amount,
                    'refund_reference' => $refundResult['reference'],
                    'refund_status' => 'completed',
                    'refunded_at' => now()
                ]);

                // Update reservation
                $reservation->update([
                    'status' => 'refunded',
                    'refund_amount' => $amount
                ]);

                return [
                    'success' => true,
                    'refund_reference' => $refundResult['reference']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $refundResult['error']
                ];
            }

        } catch (Exception $e) {
            Log::error('Refund processing failed', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Initiate refund based on payment method
     */
    private function initiateRefund(Transaction $transaction, float $amount)
    {
        switch ($transaction->payment_method) {
            case 'cmi':
                return $this->processCmiRefund($transaction, $amount);
            case 'stripe':
                return $this->processStripeRefund($transaction, $amount);
            default:
                throw new Exception('Méthode de remboursement non supportée');
        }
    }

    /**
     * Process CMI refund
     */
    private function processCmiRefund(Transaction $transaction, float $amount)
    {
        // Implement CMI refund logic
        return [
            'success' => true,
            'reference' => 'CMI_REFUND_' . time()
        ];
    }

    /**
     * Process Stripe refund
     */
    private function processStripeRefund(Transaction $transaction, float $amount)
    {
        // Implement Stripe refund logic
        return [
            'success' => true,
            'reference' => 'STRIPE_REFUND_' . time()
        ];
    }
}
