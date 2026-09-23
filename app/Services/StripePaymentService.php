<?php

namespace App\Services;

use App\Models\Payment;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Illuminate\Support\Facades\Log;

class StripePaymentService
{
    private $secretKey;
    private $webhookSecret;

    public function __construct()
    {
        // Récupérer les clés actives selon l'environnement sélectionné
        $paymentKeysService = app(\App\Services\PaymentKeysService::class);
        $activeKeys = $paymentKeysService->getActiveStripeKeys();
        
        $this->secretKey = $activeKeys['secret_key'] ?? config('payment.stripe.secret_key') ?? env('STRIPE_SECRET_KEY');
        $this->webhookSecret = $activeKeys['webhook_secret'] ?? config('payment.stripe.webhook_secret') ?? env('STRIPE_WEBHOOK_SECRET');
        
        Stripe::setApiKey($this->secretKey);
    }

    /**
     * Create Stripe payment intent
     */
    public function createPayment(Payment $payment)
    {
        try {
            // Valider la configuration
            if (empty($this->secretKey)) {
                throw new \Exception('Stripe Secret Key manquante. Veuillez vérifier la configuration.');
            }

            // Valider le montant
            if ($payment->amount <= 0) {
                throw new \Exception('Le montant du paiement doit être supérieur à 0');
            }

            // Convertir le montant en centimes avec arrondi
            $amountInCents = (int) round($payment->amount * 100);
            
            // Valider le montant minimum (50 centimes pour Stripe)
            if ($amountInCents < 50) {
                throw new \Exception('Le montant minimum pour Stripe est de 0.50 EUR');
            }

            $currency = strtolower($payment->currency ?? 'eur');
            
            // Valider la devise
            $supportedCurrencies = ['eur', 'usd', 'gbp', 'cad', 'aud', 'chf', 'sek', 'nok', 'dkk', 'pln', 'czk'];
            if (!in_array($currency, $supportedCurrencies)) {
                Log::warning('Devise non standard utilisée avec Stripe', [
                    'currency' => $currency,
                    'payment_id' => $payment->id
                ]);
            }

            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => $currency,
                'metadata' => [
                    'transaction_id' => $payment->transaction_id ?? null,
                    'reservation_id' => $payment->reservation_id ?? null,
                    'payment_id' => (string) $payment->id,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ], [
                'idempotency_key' => 'payment_' . $payment->id . '_' . time()
            ]);

            $payment->update([
                'external_id' => $paymentIntent->id,
                'payment_data' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id,
                    'amount_in_cents' => $amountInCents,
                    'currency' => $currency,
                ],
                'status' => 'processing',
            ]);

            Log::info('Stripe Payment Intent créé avec succès', [
                'payment_id' => $payment->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $payment->amount,
                'amount_in_cents' => $amountInCents
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];
        } catch (\Stripe\Exception\CardException $e) {
            // Erreur spécifique de la carte
            $errorMessage = $e->getError()->message ?? 'Erreur de carte bancaire';
            Log::error('Stripe Card Error', [
                'payment_id' => $payment->id,
                'error' => $errorMessage,
                'code' => $e->getError()->code ?? null,
            ]);

            $payment->markAsFailed($errorMessage);
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'error_code' => $e->getError()->code ?? null,
            ];
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Trop de requêtes
            Log::error('Stripe Rate Limit Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->markAsFailed('Trop de requêtes. Veuillez réessayer dans quelques instants.');
            
            return [
                'success' => false,
                'message' => 'Trop de requêtes. Veuillez réessayer dans quelques instants.',
            ];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Requête invalide
            Log::error('Stripe Invalid Request', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->markAsFailed('Requête de paiement invalide: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur de configuration du paiement. Veuillez contacter le support.',
            ];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Erreur d'authentification
            Log::error('Stripe Authentication Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->markAsFailed('Erreur d\'authentification Stripe.');
            
            return [
                'success' => false,
                'message' => 'Erreur de configuration du système de paiement. Veuillez contacter l\'administrateur.',
            ];
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            // Erreur de connexion
            Log::error('Stripe API Connection Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->markAsFailed('Erreur de connexion au service de paiement.');
            
            return [
                'success' => false,
                'message' => 'Erreur de connexion. Veuillez réessayer.',
            ];
        } catch (\Exception $e) {
            Log::error('Stripe payment creation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $payment->markAsFailed($e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la création du paiement: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function handleWebhook(array $data)
    {
        try {
            $event = $data['event'] ?? null;
            $paymentIntentId = $event['data']['object']['id'] ?? null;

            if (!$paymentIntentId) {
                return ['success' => false, 'message' => 'No payment intent ID'];
            }

            $payment = Payment::where('external_id', $paymentIntentId)->first();

            if (!$payment) {
                return ['success' => false, 'message' => 'Payment not found'];
            }

            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $payment->markAsCompleted();
                    Log::info('Stripe Payment succeeded', ['payment_id' => $payment->id]);
                    break;

                case 'payment_intent.payment_failed':
                    $payment->markAsFailed('Payment failed');
                    Log::warning('Stripe Payment failed', ['payment_id' => $payment->id]);
                    break;

                case 'payment_intent.canceled':
                    $payment->update(['status' => 'cancelled']);
                    Log::info('Stripe Payment cancelled', ['payment_id' => $payment->id]);
                    break;
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Stripe webhook handling failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create payment method
     */
    public function createPaymentMethod(array $data)
    {
        try {
            $paymentMethod = \Stripe\PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'number' => $data['card_number'],
                    'exp_month' => $data['exp_month'],
                    'exp_year' => $data['exp_year'],
                    'cvc' => $data['cvc'],
                ],
                'billing_details' => [
                    'name' => $data['name'] ?? '',
                    'email' => $data['email'] ?? '',
                ],
            ]);

            return [
                'success' => true,
                'payment_method_id' => $paymentMethod->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
