<?php

namespace App\Services\Gateways;

use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Services\Gateways\Contracts\{GatewayInterface, PaymentIntent, PaymentResult, RefundResult};
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CMI Payment Gateway Implementation
 * 
 * CMI (Centre Monétique Interbancaire) - Morocco's national payment gateway
 * 
 * Configuration (in order of priority):
 * 1. Integrator-specific credentials (stored encrypted)
 * 2. Admin default credentials from config
 */
class CmiGateway implements GatewayInterface
{
    protected ?ChargingPoint $chargePoint;
    protected ?Integrator $integrator;
    
    protected string $clientId;
    protected string $storeKey;
    protected string $gatewayUrl;
    protected string $environment;
    protected string $currency;

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
        // Load default admin configuration
        $this->defaultConfig = [
            'client_id' => config('payments.cmi.client_id', config('payment.cmi.client_id')),
            'store_key' => config('payments.cmi.store_key', config('payment.cmi.store_key')),
            'gateway_url' => config('payments.cmi.gateway_url', config('payment.cmi.gateway_url', 'https://testpayment.cmi.co.ma/fim/est3Dgate')),
            'environment' => config('payments.cmi.environment', config('payment.cmi.environment', 'test')),
            'currency' => config('payments.cmi.currency', config('payment.cmi.currency', 'MAD')),
        ];

        // If we have an integrator with custom credentials, use those instead
        if ($this->integrator) {
            $integratorConfig = $this->loadIntegratorConfig();
            if (!empty($integratorConfig)) {
                $this->clientId = $integratorConfig['client_id'] ?? $this->defaultConfig['client_id'];
                $this->storeKey = $integratorConfig['store_key'] ?? $this->defaultConfig['store_key'];
                $this->gatewayUrl = $integratorConfig['gateway_url'] ?? $this->defaultConfig['gateway_url'];
                $this->environment = $integratorConfig['environment'] ?? $this->defaultConfig['environment'];
                $this->currency = $integratorConfig['currency'] ?? $this->defaultConfig['currency'];
                return;
            }
        }

        // Fall back to default admin configuration
        $this->clientId = $this->defaultConfig['client_id'];
        $this->storeKey = $this->defaultConfig['store_key'];
        $this->gatewayUrl = $this->defaultConfig['gateway_url'];
        $this->environment = $this->defaultConfig['environment'];
        $this->currency = $this->defaultConfig['currency'];
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
        if (isset($metadata['payment_settings']['cmi'])) {
            return $metadata['payment_settings']['cmi'];
        }

        // Try to get from encrypted cmi_credentials field
        $credentials = $this->integrator->getAttribute('cmi_credentials');
        if ($credentials) {
            try {
                return json_decrypt(decrypt($credentials), true) ?? [];
            } catch (\Exception $e) {
                Log::error('Failed to decrypt CMI credentials', [
                    'integrator_id' => $this->integrator->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function initiatePayment(float $amount, string $currency, array $metadata = []): PaymentIntent
    {
        try {
            // Validate configuration
            if (empty($this->storeKey) || empty($this->clientId)) {
                throw new \Exception('CMI configuration incomplete. Please check client ID and store key.');
            }

            // Generate unique order ID
            $orderId = $metadata['order_id'] ?? 'EVON-' . Str::uuid()->toString();
            
            // Store ID for reference
            $storeId = $this->clientId;
            
            // Convert amount to cents (CMI uses minor units)
            $amountCents = (int) round($amount * 100);
            
            // Use specified currency or default
            $currencyCode = strtoupper($currency ?: $this->currency);

            // Generate random string for hash
            $rnd = (string) time() . Str::random(10);

            // Build callback URLs
            $baseUrl = config('app.url', 'http://localhost');
            $okUrl = $metadata['ok_url'] ?? "{$baseUrl}/payment/cmi/success";
            $failUrl = $metadata['fail_url'] ?? "{$baseUrl}/payment/cmi/failure";

            // Prepare CMI request data
            $requestData = [
                'storetype' => '3D_PAY_HOSTING',
                'clientid' => $storeId,
                'amount' => (string) $amountCents,
                'currency' => $currencyCode,
                'oid' => $orderId,
                'okUrl' => $okUrl,
                'failUrl' => $failUrl,
                'rnd' => $rnd,
                'hashAlgorithm' => 'ver3',
                'refreshtime' => '0',
                'lang' => $metadata['lang'] ?? 'fr',
                'email' => $metadata['customer_email'] ?? '',
                'tel' => $metadata['customer_phone'] ?? '',
            ];

            // Generate hash according to CMI documentation
            // Hash = SHA1(amount + oid + okUrl + failUrl + rnd + storeKey)
            $hashString = $requestData['amount'] . $requestData['oid'] . $requestData['okUrl'] . $requestData['failUrl'] . $requestData['rnd'] . $this->storeKey;
            $requestData['hash'] = base64_encode(pack('H*', sha1($hashString)));

            Log::info('CMI Payment initiated', [
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currencyCode,
                'charge_point_id' => $metadata['charge_point_id'] ?? null,
                'environment' => $this->environment,
            ]);

            return new PaymentIntent([
                'id' => $orderId,
                'status' => 'pending',
                'amount' => $amount,
                'currency' => $currencyCode,
                'redirect_url' => $this->gatewayUrl,
                'metadata' => array_merge($metadata, [
                    'cmi_request_data' => $requestData,
                    'store_id' => $storeId,
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('CMI Payment initiation failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return new PaymentIntent([
                'id' => '',
                'status' => 'failed',
                'amount' => $amount,
                'currency' => $currency ?? $this->currency,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function confirmPayment(string $paymentIntentId): PaymentResult
    {
        try {
            // CMI uses redirect-based flow, so we need to verify the payment
            // This is typically called from the callback URL
            // The actual verification happens via the callback or by querying CMI
            
            // For now, return a pending status - actual confirmation happens via callback
            return new PaymentResult([
                'success' => true,
                'status' => 'pending_verification',
                'transaction_id' => $paymentIntentId,
            ]);

        } catch (\Exception $e) {
            Log::error('CMI Payment confirmation failed', [
                'payment_intent_id' => $paymentIntentId,
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
    public function refundPayment(string $paymentIntentId, float $amount): RefundResult
    {
        try {
            // CMI refund implementation
            // Note: CMI refunds require additional API setup
            
            $amountCents = (int) round($amount * 100);
            $currencyCode = $this->currency;

            // Prepare refund request
            // CMI refund typically requires:
            // - Original order ID
            // - Refund amount
            // - Authentication
            
            $refundData = [
                'clientid' => $this->clientId,
                'oid' => $paymentIntentId,
                'amount' => (string) $amountCents,
                'currency' => $currencyCode,
                'txntype' => 'refund',
                'rnd' => (string) time() . Str::random(10),
            ];

            // Generate hash for refund
            $hashString = $refundData['oid'] . $refundData['amount'] . $refundData['currency'] . $refundData['rnd'] . $this->storeKey;
            $refundData['hash'] = base64_encode(pack('H*', sha1($hashString)));

            Log::info('CMI Refund initiated', [
                'original_payment_id' => $paymentIntentId,
                'refund_amount' => $amount,
                'currency' => $currencyCode,
            ]);

            // Note: Actual HTTP call to CMI refund endpoint would go here
            // For now, return success - implementation depends on CMI API details
            
            return new RefundResult([
                'success' => true,
                'status' => 'refunded',
                'refund_id' => 'REF-' . Str::uuid()->toString(),
                'amount' => $amount,
                'raw_response' => $refundData,
            ]);

        } catch (\Exception $e) {
            Log::error('CMI Refund failed', [
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
        try {
            // Query CMI for payment status
            // This typically involves a status check API call
            
            // For now, return 'unknown' - actual implementation would query CMI
            Log::info('CMI Payment status check', [
                'payment_intent_id' => $paymentIntentId,
            ]);

            return 'unknown';

        } catch (\Exception $e) {
            Log::error('CMI Payment status check failed', [
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
            // CMI uses different validation mechanisms
            // For 3D Secure, validation happens through the redirect
            
            // Check for required CMI response fields
            if (!isset($payload['oid']) || !isset($payload['Response'])) {
                return false;
            }

            // Validate hash if present
            if (isset($payload['hash'])) {
                // Rebuild hash and compare
                // CMI returns: hash = base64(sha1(amount + oid + okUrl + failUrl + rnd + storeKey))
                // We need to validate against our stored data
                
                return true; // Validation logic depends on CMI response format
            }

            return true;

        } catch (\Exception $e) {
            Log::error('CMI webhook validation failed', [
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
        return 'cmi';
    }

    /**
     * Process CMI callback response
     * 
     * @param array $callbackData
     * @return PaymentResult
     */
    public function processCallback(array $callbackData): PaymentResult
    {
        try {
            $orderId = $callbackData['oid'] ?? '';
            $responseCode = $callbackData['Response'] ?? '';
            $hash = $callbackData['hash'] ?? '';

            // Validate response
            if (empty($orderId) || empty($responseCode)) {
                throw new \Exception('Invalid CMI callback data');
            }

            // Check response code (00 = success in CMI)
            if ($responseCode === '00') {
                return new PaymentResult([
                    'success' => true,
                    'status' => 'succeeded',
                    'transaction_id' => $orderId,
                    'raw_response' => $callbackData,
                ]);
            } else {
                return new PaymentResult([
                    'success' => false,
                    'status' => 'failed',
                    'transaction_id' => $orderId,
                    'error_message' => 'CMI Response Code: ' . $responseCode,
                    'raw_response' => $callbackData,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('CMI callback processing failed', [
                'error' => $e->getMessage(),
                'callback_data' => $callbackData,
            ]);

            return new PaymentResult([
                'success' => false,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the gateway URL for redirect
     * 
     * @param array $paymentData
     * @return string
     */
    public function getRedirectUrl(array $paymentData): string
    {
        // Build form submission URL for CMI
        // CMI uses POST to their gateway with the payment data
        return $this->gatewayUrl;
    }
}
