<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service Stripe amélioré pour les paiements internationaux
 * 
 * Support multi-devises et gestion sécurisée des webhooks
 */
class EnhancedStripePaymentService
{
    protected array $config;
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->config = config('payments.stripe', []);
        $this->currencyService = $currencyService;
        
        // Initialiser Stripe
        Stripe::setApiKey($this->config['secret_key']);
    }

    /**
     * Créer une session de paiement Stripe
     */
    public function createPaymentSession(array $paymentData): array
    {
        try {
            // Validation des données
            $this->validatePaymentData($paymentData);

            // Préparer les données Stripe
            $stripeData = $this->prepareStripeData($paymentData);

            // Créer la session Stripe
            $session = Session::create($stripeData);

            // Enregistrer la transaction
            $transaction = $this->createPaymentTransaction($session, $paymentData);

            Log::info('Stripe Payment session created', [
                'session_id' => $session->id,
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency']
            ]);

            return [
                'success' => true,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'transaction_id' => $transaction['id']
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment session creation failed', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Valider les données de paiement
     */
    protected function validatePaymentData(array $data): void
    {
        $required = ['amount', 'currency', 'order_id', 'customer_email'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Le champ {$field} est requis");
            }
        }

        // Vérifier le montant minimum
        $minAmount = config('payments.general.min_amount', 1.0);
        if ($data['amount'] < $minAmount) {
            throw new \InvalidArgumentException("Le montant minimum est de {$minAmount}");
        }

        // Vérifier le montant maximum
        $maxAmount = config('payments.general.max_amount', 10000.0);
        if ($data['amount'] > $maxAmount) {
            throw new \InvalidArgumentException("Le montant maximum est de {$maxAmount}");
        }

        // Vérifier la devise supportée
        if (!$this->currencyService->isCurrencySupportedByPaymentMethod($data['currency'], 'stripe')) {
            throw new \InvalidArgumentException("La devise {$data['currency']} n'est pas supportée par Stripe");
        }
    }

    /**
     * Préparer les données Stripe
     */
    protected function prepareStripeData(array $paymentData): array
    {
        $currency = strtolower($paymentData['currency']);
        $amount = $this->convertToStripeAmount($paymentData['amount'], $currency);

        return [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $paymentData['product_name'] ?? 'Réservation de borne de recharge',
                        'description' => $paymentData['product_description'] ?? 'Paiement pour réservation de borne de recharge EvonPower'
                    ],
                    'unit_amount' => $amount
                ],
                'quantity' => 1
            ]],
            'mode' => 'payment',
            'success_url' => $this->config['success_url'] . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->config['cancel_url'],
            'metadata' => [
                'order_id' => $paymentData['order_id'],
                'reservation_id' => $paymentData['reservation_id'] ?? null,
                'user_id' => $paymentData['user_id'] ?? null
            ],
            'customer_email' => $paymentData['customer_email'],
            'billing_address_collection' => 'required',
            'shipping_address_collection' => [
                'allowed_countries' => $this->getAllowedCountries()
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'order_id' => $paymentData['order_id'],
                    'reservation_id' => $paymentData['reservation_id'] ?? null
                ]
            ]
        ];
    }

    /**
     * Convertir le montant pour Stripe (en centimes)
     */
    protected function convertToStripeAmount(float $amount, string $currency): int
    {
        // Stripe utilise les centimes pour la plupart des devises
        $zeroDecimalCurrencies = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'];
        
        if (in_array($currency, $zeroDecimalCurrencies)) {
            return (int) $amount;
        }
        
        return (int) ($amount * 100);
    }

    /**
     * Obtenir les pays autorisés
     */
    protected function getAllowedCountries(): array
    {
        return [
            'MA', 'FR', 'ES', 'IT', 'DE', 'GB', 'US', 'CA', 'AU', 'BE', 'NL', 'CH', 'AT', 'SE', 'NO', 'DK', 'FI'
        ];
    }

    /**
     * Créer une transaction de paiement
     */
    protected function createPaymentTransaction(Session $session, array $paymentData): array
    {
        $transactionId = 'STRIPE_' . $paymentData['order_id'] . '_' . time();
        
        $transaction = [
            'id' => $transactionId,
            'session_id' => $session->id,
            'order_id' => $paymentData['order_id'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'],
            'status' => 'pending',
            'created_at' => now()
        ];

        // Stocker temporairement
        Cache::put("stripe_payment_{$transactionId}", $transaction, 3600);
        Cache::put("stripe_session_{$session->id}", $transaction, 3600);

        return $transaction;
    }

    /**
     * Récupérer une session Stripe
     */
    public function getSession(string $sessionId): array
    {
        try {
            $session = Session::retrieve($sessionId);
            
            return [
                'success' => true,
                'session' => [
                    'id' => $session->id,
                    'payment_status' => $session->payment_status,
                    'amount_total' => $session->amount_total,
                    'currency' => $session->currency,
                    'customer_email' => $session->customer_details->email ?? null,
                    'metadata' => $session->metadata->toArray()
                ]
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe session retrieval failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function verifyPayment(string $sessionId): array
    {
        try {
            $session = Session::retrieve($sessionId);
            
            return [
                'success' => true,
                'paid' => $session->payment_status === 'paid',
                'status' => $session->payment_status,
                'amount' => $session->amount_total,
                'currency' => $session->currency
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe payment verification failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Traiter un webhook Stripe
     */
    public function handleWebhook(string $payload, string $signature): array
    {
        try {
            // Vérifier la signature du webhook
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $this->config['webhook_secret']
            );

            Log::info('Stripe webhook received', [
                'event_type' => $event->type,
                'event_id' => $event->id
            ]);

            // Traiter l'événement
            $result = $this->processWebhookEvent($event);

            return [
                'success' => true,
                'event_type' => $event->type,
                'result' => $result
            ];

        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Traiter un événement webhook
     */
    protected function processWebhookEvent($event): array
    {
        switch ($event->type) {
            case 'checkout.session.completed':
                return $this->handleCheckoutSessionCompleted($event->data->object);
            
            case 'payment_intent.succeeded':
                return $this->handlePaymentIntentSucceeded($event->data->object);
            
            case 'payment_intent.payment_failed':
                return $this->handlePaymentIntentFailed($event->data->object);
            
            case 'payment_intent.canceled':
                return $this->handlePaymentIntentCanceled($event->data->object);
            
            default:
                Log::info('Unhandled Stripe webhook event', [
                    'event_type' => $event->type
                ]);
                return ['status' => 'unhandled'];
        }
    }

    /**
     * Gérer l'événement checkout.session.completed
     */
    protected function handleCheckoutSessionCompleted($session): array
    {
        Log::info('Stripe checkout session completed', [
            'session_id' => $session->id,
            'payment_status' => $session->payment_status
        ]);

        // Mettre à jour la transaction
        $transaction = Cache::get("stripe_session_{$session->id}");
        if ($transaction) {
            $transaction['status'] = 'completed';
            Cache::put("stripe_payment_{$transaction['id']}", $transaction, 3600);
        }

        return ['status' => 'completed'];
    }

    /**
     * Gérer l'événement payment_intent.succeeded
     */
    protected function handlePaymentIntentSucceeded($paymentIntent): array
    {
        Log::info('Stripe payment intent succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount
        ]);

        return ['status' => 'succeeded'];
    }

    /**
     * Gérer l'événement payment_intent.payment_failed
     */
    protected function handlePaymentIntentFailed($paymentIntent): array
    {
        Log::error('Stripe payment intent failed', [
            'payment_intent_id' => $paymentIntent->id,
            'last_payment_error' => $paymentIntent->last_payment_error
        ]);

        return ['status' => 'failed'];
    }

    /**
     * Gérer l'événement payment_intent.canceled
     */
    protected function handlePaymentIntentCanceled($paymentIntent): array
    {
        Log::info('Stripe payment intent canceled', [
            'payment_intent_id' => $paymentIntent->id
        ]);

        return ['status' => 'canceled'];
    }

    /**
     * Créer un intent de paiement direct
     */
    public function createPaymentIntent(array $paymentData): array
    {
        try {
            $currency = strtolower($paymentData['currency']);
            $amount = $this->convertToStripeAmount($paymentData['amount'], $currency);

            $intent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => [
                    'order_id' => $paymentData['order_id'],
                    'reservation_id' => $paymentData['reservation_id'] ?? null
                ],
                'automatic_payment_methods' => [
                    'enabled' => true
                ]
            ]);

            Log::info('Stripe payment intent created', [
                'intent_id' => $intent->id,
                'order_id' => $paymentData['order_id']
            ]);

            return [
                'success' => true,
                'intent_id' => $intent->id,
                'client_secret' => $intent->client_secret
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe payment intent creation failed', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Rembourser un paiement
     */
    public function refundPayment(string $paymentIntentId, int $amount = null): array
    {
        try {
            $refund = \Stripe\Refund::create([
                'payment_intent' => $paymentIntentId,
                'amount' => $amount
            ]);

            Log::info('Stripe refund created', [
                'refund_id' => $refund->id,
                'payment_intent_id' => $paymentIntentId,
                'amount' => $refund->amount
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe refund failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir les méthodes de paiement supportées
     */
    public function getSupportedPaymentMethods(): array
    {
        return [
            'card' => 'Carte bancaire',
            'sepa_debit' => 'Prélèvement SEPA',
            'ideal' => 'iDEAL',
            'bancontact' => 'Bancontact',
            'eps' => 'EPS',
            'giropay' => 'Giropay',
            'p24' => 'Przelewy24',
            'sofort' => 'Sofort'
        ];
    }

    /**
     * Obtenir les devises supportées par Stripe
     */
    public function getSupportedCurrencies(): array
    {
        return $this->currencyService->getSupportedCurrencies('stripe');
    }
}
