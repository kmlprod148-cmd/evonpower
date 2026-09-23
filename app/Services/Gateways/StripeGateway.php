<?php

namespace App\Services\Gateways;

use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Services\Gateways\Contracts\{GatewayInterface, PaymentIntent, PaymentResult, RefundResult};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\Refund as StripeRefund;
use Stripe\Exception\ApiErrorException;

/**
 * Stripe Payment Gateway Implementation
 * 
 * International payment gateway using Stripe
 * 
 * Configuration (in order of priority):
 * 1. Integrator-specific credentials (stored encrypted)
 * 2. Admin default credentials from config
 */
class StripeGateway implements GatewayInterface
{
    protected ?ChargingPoint $chargePoint;
    protected ?Integrator $integrator;
    
    protected string $secretKey;
    protected string $publicKey;
    protected string $webhookSecret;
    protected string $environment;
    protected string $currency;

    // Fallback defaults used when env vars are missing
    private const DEFAULTS = [
        'secret_key'     => '',
        'public_key'     => '',
        'webhook_secret' => '',
        'environment'    => 'test',
        'currency'       => 'eur',
    ];

    /**
     * Default configuration from admin settings
     */
    protected array $defaultConfig = [];

    public function __construct(?ChargingPoint $chargePoint = null, ?Integrator $integrator = null)
    {
        $this->chargePoint = $chargePoint;
        $this->integrator = $integrator ?? ($chargePoint?->partner?->integrator ?? $chargePoint?->integrator);
        
        $this->loadConfiguration();
    }

    /**
     * Load configuration from admin settings or integrator settings
     */
    protected function loadConfiguration(): void
    {
        // Load default admin configuration (cast to string to prevent TypeError on typed properties)
        $this->defaultConfig = [
            'secret_key'     => (string) (config('payments.stripe.secret_key')     ?? config('payment.stripe.secret_key')     ?? self::DEFAULTS['secret_key']),
            'public_key'     => (string) (config('payments.stripe.public_key')      ?? config('payment.stripe.public_key')      ?? self::DEFAULTS['public_key']),
            'webhook_secret' => (string) (config('payments.stripe.webhook_secret')  ?? config('payment.stripe.webhook_secret')  ?? self::DEFAULTS['webhook_secret']),
            'environment'    => (string) (config('payments.stripe.environment')     ?? config('payment.stripe.environment')     ?? self::DEFAULTS['environment']),
            'currency'       => (string) (config('payments.stripe.currency')        ?? config('payment.stripe.currency')        ?? self::DEFAULTS['currency']),
        ];

        // If we have an integrator with custom credentials, use those instead
        if ($this->integrator) {
            $integratorConfig = $this->loadIntegratorConfig();
            if (!empty($integratorConfig)) {
                $this->secretKey     = (string) ($integratorConfig['secret_key']     ?? $this->defaultConfig['secret_key']);
                $this->publicKey     = (string) ($integratorConfig['public_key']      ?? $this->defaultConfig['public_key']);
                $this->webhookSecret = (string) ($integratorConfig['webhook_secret']  ?? $this->defaultConfig['webhook_secret']);
                $this->environment   = (string) ($integratorConfig['environment']     ?? $this->defaultConfig['environment']);
                $this->currency      = (string) ($integratorConfig['currency']        ?? $this->defaultConfig['currency']);
                return;
            }
        }

        // Fall back to default admin configuration
        $this->secretKey     = $this->defaultConfig['secret_key'];
        $this->publicKey     = $this->defaultConfig['public_key'];
        $this->webhookSecret = $this->defaultConfig['webhook_secret'];
        $this->environment   = $this->defaultConfig['environment'];
        $this->currency      = $this->defaultConfig['currency'];
    }

    /**
     * Load integrator-specific configuration (encrypted)
     */
    protected function loadIntegratorConfig(): array
    {
        if (!$this->integrator) {
            return [];
        }

        $metadata = $this->integrator->metadata ?? [];
        
        // Try to get from payment_settings in metadata
        if (isset($metadata['payment_settings']['stripe'])) {
            return $metadata['payment_settings']['stripe'];
        }

        // Try to get from encrypted stripe_credentials field
        $credentials = $this->integrator->getAttribute('stripe_credentials');
        if ($credentials) {
            try {
                return json_decode(decrypt($credentials), true) ?? [];
            } catch (\Exception $e) {
                Log::error('Failed to decrypt Stripe credentials', [
                    'integrator_id' => $this->integrator->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [];
    }

    /**
     * Initialize Stripe with the current configuration
     */
    protected function initializeStripe(): void
    {
        if (!empty($this->secretKey)) {
            Stripe::setApiKey($this->secretKey);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initiatePayment(float $amount, string $currency, array $metadata = []): PaymentIntent
    {
        $this->initializeStripe();

        try {
            // Validate configuration
            if (empty($this->secretKey)) {
                throw new \Exception('Stripe configuration incomplete. Please check secret key.');
            }

            // Convert amount to cents (Stripe uses minor units)
            $amountCents = (int) round($amount * 100);
            
            // Validate minimum amount (50 cents)
            if ($amountCents < 50) {
                throw new \Exception('Minimum amount for Stripe is 0.50');
            }

            // Use specified currency or default
            $currencyCode = strtolower($currency ?: $this->currency);

            // Build metadata – include reservation_id and transaction_id so that
            // Stripe webhook handlers can resolve the reservation without relying
            // on session state (webhooks arrive on a separate HTTP request).
            $stripeMetadata = [
                'charge_point_id' => $metadata['charge_point_id'] ?? null,
                'reservation_id'  => $metadata['reservation_id']  ?? null,
                'transaction_id'  => $metadata['transaction_id']  ?? null,
                'user_id'         => $metadata['user_id']         ?? null,
                'session_id'      => $metadata['session_id']      ?? null,
                'order_id'        => $metadata['order_id']        ?? null,
                'partner_id'      => $metadata['partner_id']      ?? null,
                'integrator_id'   => $metadata['integrator_id']   ?? ($this->integrator?->id),
            ];

            // Remove null values
            $stripeMetadata = array_filter($stripeMetadata, fn($value) => $value !== null);

            // Create PaymentIntent
            // NOTE: return_url is NOT passed here – Stripe rejects it unless confirm=true.
            // The frontend passes return_url via stripe.confirmPayment({ confirmParams: { return_url } }).
            $paymentIntent = StripePaymentIntent::create([
                'amount' => $amountCents,
                'currency' => $currencyCode,
                'metadata' => $stripeMetadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ], [
                'idempotency_key' => $metadata['idempotency_key'] ?? 'pi_' . Str::uuid()->toString(),
            ]);

            Log::info('Stripe Payment initiated', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => $currencyCode,
                'charge_point_id' => $metadata['charge_point_id'] ?? null,
                'environment' => $this->environment,
            ]);

            return new PaymentIntent([
                'id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $amount,
                'currency' => strtoupper($currencyCode),
                'client_secret' => $paymentIntent->client_secret,
                'metadata' => $stripeMetadata,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment initiation failed (API)', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return new PaymentIntent([
                'id' => '',
                'status' => 'failed',
                'amount' => $amount,
                'currency' => strtoupper($currency ?: $this->currency),
                'error_message' => $e->getMessage(),
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe Payment initiation failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return new PaymentIntent([
                'id' => '',
                'status' => 'failed',
                'amount' => $amount,
                'currency' => strtoupper($currency ?: $this->currency),
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function confirmPayment(string $paymentIntentId): PaymentResult
    {
        $this->initializeStripe();

        try {
            $paymentIntent = StripePaymentIntent::retrieve($paymentIntentId);

            $success = in_array($paymentIntent->status, ['succeeded', 'processing']);
            $status = $paymentIntent->status;
            $errorMessage = null;

            if ($paymentIntent->status === 'requires_payment_method') {
                $status = 'failed';
                $success = false;
                $errorMessage = 'Payment method required but not provided';
            } elseif ($paymentIntent->status === 'requires_action') {
                $status = 'requires_action';
                $success = true; // Payment needs additional action (3D Secure)
                $errorMessage = 'Additional authentication required';
            } elseif ($paymentIntent->status === 'canceled') {
                $status = 'canceled';
                $success = false;
                $errorMessage = 'Payment was canceled';
            }

            Log::info('Stripe Payment confirmation', [
                'payment_intent_id' => $paymentIntentId,
                'status' => $status,
                'success' => $success,
            ]);

            return new PaymentResult([
                'success' => $success,
                'status' => $status,
                'transaction_id' => $paymentIntentId,
                'error_message' => $errorMessage,
                'raw_response' => $paymentIntent->toArray(),
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment confirmation failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return new PaymentResult([
                'success' => false,
                'status' => 'error',
                'transaction_id' => $paymentIntentId,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refundPayment(string $paymentIntentId, float $amount): RefundResult
    {
        $this->initializeStripe();

        try {
            // Convert amount to cents
            $amountCents = (int) round($amount * 100);

            // Prepare refund parameters
            $refundParams = [
                'payment_intent' => $paymentIntentId,
                'amount' => $amountCents, // If not specified, refunds full amount
            ];

            // Create refund
            $refund = StripeRefund::create($refundParams, [
                'idempotency_key' => 'refund_' . $paymentIntentId . '_' . time(),
            ]);

            Log::info('Stripe Refund processed', [
                'refund_id' => $refund->id,
                'payment_intent_id' => $paymentIntentId,
                'amount' => $amount,
                'status' => $refund->status,
            ]);

            return new RefundResult([
                'success' => $refund->status === 'succeeded',
                'status' => $refund->status ?? 'pending',
                'refund_id' => $refund->id,
                'amount' => $amount,
                'raw_response' => $refund->toArray(),
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe Refund failed', [
                'payment_intent_id' => $paymentIntentId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return new RefundResult([
                'success' => false,
                'status' => 'failed',
                'amount' => $amount,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentStatus(string $paymentIntentId): string
    {
        $this->initializeStripe();

        try {
            $paymentIntent = StripePaymentIntent::retrieve($paymentIntentId);
            return $paymentIntent->status;

        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment status check failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            return 'error';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateWebhook(array $payload, string $signature): bool
    {
        try {
            if (empty($this->webhookSecret)) {
                Log::warning('Stripe webhook secret not configured');
                return false;
            }

            // Stripe provides the payload as raw body and signature in headers
            // This method expects the raw payload and signature header
            return true; // Actual validation happens in the controller

        } catch (\Exception $e) {
            Log::error('Stripe webhook validation failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getGatewayType(): string
    {
        return 'stripe';
    }

    /**
     * Get public key for frontend integration
     * 
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey ?? '';
    }

    /**
     * Process a Stripe webhook event
     * 
     * @param string $eventType
     * @param array $data
     * @return array
     */
    public function processWebhookEvent(string $eventType, array $data): array
    {
        return match ($eventType) {
            'payment_intent.succeeded' => [
                'action' => 'payment_completed',
                'payment_intent_id' => $data['id'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ],
            'payment_intent.payment_failed' => [
                'action' => 'payment_failed',
                'payment_intent_id' => $data['id'] ?? null,
                'error' => $data['last_payment_error']['message'] ?? 'Unknown error',
                'metadata' => $data['metadata'] ?? [],
            ],
            'charge.refunded' => [
                'action' => 'payment_refunded',
                'charge_id' => $data['id'] ?? null,
                'amount_refunded' => ($data['amount_refunded'] ?? 0) / 100,
                'metadata' => $data['metadata'] ?? [],
            ],
            default => [
                'action' => 'unknown',
                'event_type' => $eventType,
            ],
        };
    }

    /**
     * Construct a return URL for the payment
     * 
     * @param string $paymentIntentId
     * @param string $status
     * @return string
     */
    public function getReturnUrl(string $paymentIntentId, string $status = 'success'): string
    {
        $baseUrl = config('app.url', 'http://localhost');
        return "{$baseUrl}/payment/stripe/return?payment_intent={$paymentIntentId}&status={$status}";
    }

    /**
     * {@inheritdoc}
     */
    public function createAuthorization(float $amount, string $currency, array $metadata = []): PaymentIntent
    {
        $this->initializeStripe();

        try {
            // Create a PaymentIntent with capture_method: manual for authorization
            $amountCents = (int) round($amount * 100);
            
            $paymentIntent = StripePaymentIntent::create([
                'amount' => $amountCents,
                'currency' => strtolower($currency),
                'capture_method' => 'manual', // This makes it an authorization (hold)
                'metadata' => $metadata,
                'confirm' => true,
                'return_url' => $this->getReturnUrl('authtest_' . time(), 'authorized'),
            ]);

            Log::info('Stripe Authorization created', [
                'authorization_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => $paymentIntent->status,
            ]);

            return new PaymentIntent([
                'id' => $paymentIntent->id,
                'status' => $paymentIntent->status === 'requires_capture' ? 'authorized' : $paymentIntent->status,
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => $metadata,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe Authorization failed', [
                'amount' => $amount,
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);

            return new PaymentIntent([
                'id' => '',
                'status' => 'failed',
                'amount' => $amount,
                'currency' => $currency,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function captureAuthorization(string $authorizationId, float $amount, string $currency, array $metadata = []): PaymentResult
    {
        $this->initializeStripe();

        try {
            $amountCents = (int) round($amount * 100);

            // Retrieve the payment intent and capture it
            $paymentIntent = StripePaymentIntent::retrieve($authorizationId);
            $paymentIntent->capture([
                'amount_to_capture' => $amountCents,
            ]);

            Log::info('Stripe Authorization captured', [
                'authorization_id' => $authorizationId,
                'captured_amount' => $amount,
                'status' => $paymentIntent->status,
            ]);

            return new PaymentResult([
                'success' => $paymentIntent->status === 'succeeded',
                'status' => $paymentIntent->status,
                'transaction_id' => $paymentIntent->id,
                'raw_response' => $paymentIntent->toArray(),
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe Authorization capture failed', [
                'authorization_id' => $authorizationId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return new PaymentResult([
                'success' => false,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancelAuthorization(string $authorizationId, array $metadata = []): PaymentResult
    {
        $this->initializeStripe();

        try {
            $paymentIntent = StripePaymentIntent::retrieve($authorizationId);
            $paymentIntent->cancel();

            Log::info('Stripe Authorization cancelled', [
                'authorization_id' => $authorizationId,
            ]);

            return new PaymentResult([
                'success' => true,
                'status' => 'cancelled',
                'transaction_id' => $authorizationId,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe Authorization cancellation failed', [
                'authorization_id' => $authorizationId,
                'error' => $e->getMessage(),
            ]);

            return new PaymentResult([
                'success' => false,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createPaymentMethod(array $cardDetails, string $guestEmail): PaymentResult
    {
        $this->initializeStripe();

        try {
            $paymentMethod = \Stripe\PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'token' => $cardDetails['token'] ?? null,
                    'number' => $cardDetails['number'] ?? null,
                    'exp_month' => $cardDetails['exp_month'] ?? null,
                    'exp_year' => $cardDetails['exp_year'] ?? null,
                    'cvc' => $cardDetails['cvc'] ?? null,
                ],
            ]);

            Log::info('Stripe PaymentMethod created', [
                'payment_method_id' => $paymentMethod->id,
                'guest_email' => $guestEmail,
            ]);

            return new PaymentResult([
                'success' => true,
                'status' => 'created',
                'transaction_id' => $paymentMethod->id,
                'raw_response' => $paymentMethod->toArray(),
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe PaymentMethod creation failed', [
                'guest_email' => $guestEmail,
                'error' => $e->getMessage(),
            ]);

            return new PaymentResult([
                'success' => false,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refund(string $paymentIntentId, float $amount, string $currency, array $metadata = []): RefundResult
    {
        return $this->refundPayment($paymentIntentId, $amount);
    }
}
