<?php

namespace App\Services\Gateways\Contracts;

/**
 * Interface for payment gateway implementations
 * 
 * Each payment gateway (CMI, Stripe, etc.) must implement this interface
 * to provide standardized payment operations for the Evon Charge application.
 */
interface GatewayInterface
{
    /**
     * Initiate a payment with the gateway
     *
     * @param float $amount The payment amount
     * @param string $currency The currency code (e.g., 'MAD', 'EUR')
     * @param array $metadata Additional metadata (charge_point_id, user_id, session_id, etc.)
     * @return PaymentIntent
     */
    public function initiatePayment(float $amount, string $currency, array $metadata = []): PaymentIntent;

    /**
     * Confirm a payment that was initiated
     *
     * @param string $paymentIntentId The payment intent ID from the gateway
     * @return PaymentResult
     */
    public function confirmPayment(string $paymentIntentId): PaymentResult;

    /**
     * Refund a payment
     *
     * @param string $paymentIntentId The original payment intent ID
     * @param float $amount The amount to refund (partial or full)
     * @return RefundResult
     */
    public function refundPayment(string $paymentIntentId, float $amount): RefundResult;

    /**
     * Get the current status of a payment
     *
     * @param string $paymentIntentId The payment intent ID
     * @return string Payment status (pending, completed, failed, refunded, etc.)
     */
    public function getPaymentStatus(string $paymentIntentId): string;

    /**
     * Validate a webhook signature/event
     *
     * @param array $payload The raw payload
     * @param string $signature The signature header
     * @return bool
     */
    public function validateWebhook(array $payload, string $signature): bool;

    /**
     * Get the gateway type identifier
     *
     * @return string
     */
    public function getGatewayType(): string;

    /**
     * Create an authorization (hold) on a payment method
     * 
     * Used for postpaid mode - authorize amount at session start,
     * then capture later when actual cost is known.
     *
     * @param float $amount The authorization amount
     * @param string $currency The currency code
     * @param array $metadata Additional metadata
     * @return PaymentIntent
     */
    public function createAuthorization(float $amount, string $currency, array $metadata = []): PaymentIntent;

    /**
     * Capture an authorized payment
     *
     * Used for postpaid mode - capture the actual cost after
     * the charging session ends.
     *
     * @param string $authorizationId The authorization ID
     * @param float $amount The amount to capture (can be less than authorized)
     * @param string $currency The currency code
     * @param array $metadata Additional metadata
     * @return PaymentResult
     */
    public function captureAuthorization(string $authorizationId, float $amount, string $currency, array $metadata = []): PaymentResult;

    /**
     * Cancel an authorization
     *
     * Used when a session is cancelled before starting or
     * authorization needs to be released.
     *
     * @param string $authorizationId The authorization ID
     * @param array $metadata Additional metadata
     * @return PaymentResult
     */
    public function cancelAuthorization(string $authorizationId, array $metadata = []): PaymentResult;

    /**
     * Create and store a payment method for future use
     *
     * Used for guest postpaid - tokenize a card for later use.
     *
     * @param array $cardDetails Card information
     * @param string $guestEmail Guest email for identification
     * @return PaymentResult
     */
    public function createPaymentMethod(array $cardDetails, string $guestEmail): PaymentResult;

    /**
     * Process refund with additional metadata
     *
     * @param string $paymentIntentId The original payment intent ID
     * @param float $amount The amount to refund
     * @param string $currency The currency code
     * @param array $metadata Additional metadata
     * @return RefundResult
     */
    public function refund(string $paymentIntentId, float $amount, string $currency, array $metadata = []): RefundResult;
}

/**
 * Represents a payment intent from the gateway
 */
class PaymentIntent
{
    public string $id;
    public string $status;
    public float $amount;
    public string $currency;
    public ?string $clientSecret;
    public ?string $redirectUrl;
    public array $metadata;
    public ?string $errorMessage;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->status = $data['status'] ?? 'pending';
        $this->amount = $data['amount'] ?? 0.0;
        $this->currency = $data['currency'] ?? 'MAD';
        $this->clientSecret = $data['client_secret'] ?? null;
        $this->redirectUrl = $data['redirect_url'] ?? null;
        $this->metadata = $data['metadata'] ?? [];
        $this->errorMessage = $data['error_message'] ?? null;
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'succeeded' || $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' || $this->status === 'requires_action';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isAuthorized(): bool
    {
        return $this->status === 'requires_capture' || $this->status === 'authorized';
    }
}

/**
 * Represents the result of a payment confirmation
 */
class PaymentResult
{
    public bool $success;
    public string $status;
    public ?string $transactionId;
    public ?string $errorMessage;
    public array $rawResponse;

    public function __construct(array $data)
    {
        $this->success = $data['success'] ?? false;
        $this->status = $data['status'] ?? 'unknown';
        $this->transactionId = $data['transaction_id'] ?? null;
        $this->errorMessage = $data['error_message'] ?? null;
        $this->rawResponse = $data['raw_response'] ?? [];
    }

    public function isFailed(): bool
    {
        return !$this->success || $this->status === 'failed';
    }
}

/**
 * Represents the result of a refund operation
 */
class RefundResult
{
    public bool $success;
    public string $status;
    public ?string $refundId;
    public float $amount;
    public ?string $errorMessage;
    public array $rawResponse;

    public function __construct(array $data)
    {
        $this->success = $data['success'] ?? false;
        $this->status = $data['status'] ?? 'unknown';
        $this->refundId = $data['refund_id'] ?? null;
        $this->amount = $data['amount'] ?? 0.0;
        $this->errorMessage = $data['error_message'] ?? null;
        $this->rawResponse = $data['raw_response'] ?? [];
    }

    public function isFailed(): bool
    {
        return !$this->success || $this->status === 'failed';
    }
}
