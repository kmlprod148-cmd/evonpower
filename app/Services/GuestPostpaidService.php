<?php

namespace App\Services;

use App\Models\GuestPaymentMethod;
use App\Models\Reservation;
use App\Models\ChargingSession;
use App\Models\Transaction;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Guest Postpaid Service
 * 
 * Handles postpaid charging for guest users without requiring an account.
 * 
 * Flow:
 * 1. Guest enters payment method details at checkout
 * 2. Payment method is tokenized and stored
 * 3. At session start: Payment is pre-authorized (hold funds)
 * 4. At session end: Actual cost is calculated and charged
 * 5. If payment fails: Retry mechanism kicks in
 * 
 * @package App\Services
 */
class GuestPostpaidService
{
    /**
     * Maximum retry attempts for payment capture
     */
    public const MAX_RETRY_ATTEMPTS = 3;

    /**
     * Retry delay in seconds between attempts
     */
    public const RETRY_DELAY_SECONDS = 60;

    /**
     * Default maximum charge amount in MAD
     */
    public const DEFAULT_MAX_CHARGE_AMOUNT = 500.00;

    public function __construct(
        protected PaymentGatewayService $paymentGatewayService,
        protected TransactionService $transactionService
    ) {}

    /**
     * Create a postpaid checkout session for guest
     * 
     * @param Reservation $reservation
     * @param GuestPaymentMethod $paymentMethod
     * @param float $estimatedCost
     * @return array
     */
    public function createPostpaidSession(
        Reservation $reservation,
        GuestPaymentMethod $paymentMethod,
        float $estimatedCost
    ): array {
        try {
            DB::beginTransaction();

            // Calculate authorization amount (estimated cost + 20% buffer)
            $authAmount = $this->calculateAuthorizationAmount($estimatedCost);

            // Get the gateway for this charge point
            $gateway = $this->paymentGatewayService->resolveGateway($reservation->chargingPoint);

            // Create pre-authorization
            $authorization = $gateway->createAuthorization(
                $authAmount,
                $reservation->currency ?? 'MAD',
                [
                    'reservation_id' => $reservation->id,
                    'guest_email' => $reservation->guest_email,
                    'payment_method_id' => $paymentMethod->payment_method_id,
                    'estimated_cost' => $estimatedCost,
                    'charge_point_id' => $reservation->charging_point_id,
                ]
            );

            if ($authorization->isFailed()) {
                throw new \Exception($authorization->errorMessage ?? 'Authorization failed');
            }

            // Update reservation with postpaid details
            $reservation->update([
                'payment_mode' => 'postpaid',
                'payment_method' => 'guest_postpaid',
                'guest_payment_method_id' => $paymentMethod->id,
                'payment_status' => 'authorized',
                'postpaid_auth_code' => $authorization->id,
                'metadata' => array_merge($reservation->metadata ?? [], [
                    'postpaid' => [
                        'authorization_id' => $authorization->id,
                        'authorization_amount' => $authAmount,
                        'estimated_cost' => $estimatedCost,
                        'authorized_at' => now()->toIso8601String(),
                        'gateway_type' => $gateway->getGatewayType(),
                    ],
                ]),
            ]);

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $reservation->user_id,
                'charge_point_id' => $reservation->charging_point_id,
                'reservation_id' => $reservation->id,
                'amount' => $authAmount,
                'currency' => $reservation->currency ?? 'MAD',
                'type' => 'prepaid', // This is authorization hold
                'status' => 'pending', // Will be marked authorized after webhook
                'gateway_type' => $gateway->getGatewayType(),
                'gateway_transaction_id' => $authorization->id,
                'payment_data' => [
                    'authorization_id' => $authorization->id,
                    'payment_method_id' => $paymentMethod->payment_method_id,
                    'is_authorization' => true,
                ],
                'payment_method' => 'guest_postpaid',
            ]);

            DB::commit();

            Log::info('GuestPostpaidService: Postpaid session created', [
                'reservation_id' => $reservation->id,
                'authorization_id' => $authorization->id,
                'auth_amount' => $authAmount,
                'guest_email' => $reservation->guest_email,
            ]);

            return [
                'success' => true,
                'authorization_id' => $authorization->id,
                'authorization_amount' => $authAmount,
                'transaction_id' => $transaction->id,
                'status' => 'authorized',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('GuestPostpaidService: Failed to create postpaid session', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'authorization_failed',
            ];
        }
    }

    /**
     * Capture payment when charging session ends
     * 
     * @param ChargingSession $session
     * @param float $actualCost
     * @param int $retryAttempt
     * @return array
     */
    public function capturePayment(
        ChargingSession $session,
        float $actualCost,
        int $retryAttempt = 1
    ): array {
        $reservation = $session->reservation;

        if (!$reservation) {
            return [
                'success' => false,
                'error' => 'Reservation not found',
                'error_code' => 'reservation_not_found',
            ];
        }

        if (!$reservation->guest_payment_method_id) {
            return [
                'success' => false,
                'error' => 'No payment method found for this reservation',
                'error_code' => 'no_payment_method',
            ];
        }

        try {
            DB::beginTransaction();

            // Get the payment method
            $paymentMethod = GuestPaymentMethod::find($reservation->guest_payment_method_id);
            
            if (!$paymentMethod || !$paymentMethod->is_active) {
                throw new \Exception('Payment method is not available');
            }

            // Get the gateway
            $gateway = $this->paymentGatewayService->resolveGateway($session->chargingPoint);

            // Get the original authorization
            $authCode = $reservation->postpaid_auth_code;

            // Capture the payment
            $captureResult = $gateway->captureAuthorization(
                $authCode,
                $actualCost,
                $session->currency ?? 'MAD',
                [
                    'reservation_id' => $reservation->id,
                    'session_id' => $session->id,
                    'guest_email' => $reservation->guest_email,
                    'actual_cost' => $actualCost,
                    'energy_consumed' => $session->energy_consumed ?? 0,
                    'duration_minutes' => $session->duration ?? 0,
                ]
            );

            if ($captureResult->isFailed()) {
                throw new \Exception($captureResult->errorMessage ?? 'Capture failed');
            }

            // Update transaction
            $transaction = Transaction::where('reservation_id', $reservation->id)
                ->where('gateway_transaction_id', $authCode)
                ->first();

            if ($transaction) {
                $transaction->update([
                    'status' => 'completed',
                    'amount' => $actualCost,
                    'payment_data' => array_merge($transaction->payment_data ?? [], [
                        'captured_at' => now()->toIso8601String(),
                        'capture_id' => $captureResult->id,
                        'actual_cost' => $actualCost,
                    ]),
                ]);
            }

            // Update reservation
            $reservation->update([
                'actual_cost' => $actualCost,
                'payment_status' => 'paid',
                'postpaid_captured_at' => now(),
                'metadata' => array_merge($reservation->metadata ?? [], [
                    'postpaid' => [
                        'captured_at' => now()->toIso8601String(),
                        'capture_id' => $captureResult->id,
                        'actual_cost' => $actualCost,
                        'retry_attempt' => $retryAttempt,
                    ],
                ]),
            ]);

            DB::commit();

            Log::info('GuestPostpaidService: Payment captured successfully', [
                'reservation_id' => $reservation->id,
                'session_id' => $session->id,
                'actual_cost' => $actualCost,
                'capture_id' => $captureResult->id,
            ]);

            return [
                'success' => true,
                'capture_id' => $captureResult->id,
                'amount' => $actualCost,
                'status' => 'captured',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('GuestPostpaidService: Payment capture failed', [
                'reservation_id' => $reservation->id,
                'session_id' => $session->id,
                'actual_cost' => $actualCost,
                'retry_attempt' => $retryAttempt,
                'error' => $e->getMessage(),
            ]);

            // Check if we should retry
            if ($retryAttempt < self::MAX_RETRY_ATTEMPTS) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'error_code' => 'capture_failed_retry',
                    'retry_attempt' => $retryAttempt,
                    'max_retries' => self::MAX_RETRY_ATTEMPTS,
                    'retry_delay' => self::RETRY_DELAY_SECONDS,
                ];
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'capture_failed',
                'retry_attempt' => $retryAttempt,
                'permanent_failure' => true,
            ];
        }
    }

    /**
     * Cancel authorization (if session is cancelled before start)
     * 
     * @param Reservation $reservation
     * @return array
     */
    public function cancelAuthorization(Reservation $reservation): array
    {
        if (!$reservation->postpaid_auth_code) {
            return [
                'success' => true,
                'message' => 'No authorization to cancel',
            ];
        }

        try {
            $gateway = $this->paymentGatewayService->resolveGateway($reservation->chargingPoint);
            
            $result = $gateway->cancelAuthorization(
                $reservation->postpaid_auth_code,
                [
                    'reservation_id' => $reservation->id,
                    'guest_email' => $reservation->guest_email,
                ]
            );

            if ($result->isFailed()) {
                Log::warning('GuestPostpaidService: Failed to cancel authorization', [
                    'reservation_id' => $reservation->id,
                    'auth_code' => $reservation->postpaid_auth_code,
                    'error' => $result->errorMessage,
                ]);
            }

            // Update reservation regardless of gateway response
            $reservation->update([
                'payment_status' => 'cancelled',
                'metadata' => array_merge($reservation->metadata ?? [], [
                    'postpaid' => [
                        'cancelled_at' => now()->toIso8601String(),
                        'cancellation_reason' => 'user_cancelled',
                    ],
                ]),
            ]);

            return [
                'success' => true,
                'message' => 'Authorization cancelled',
            ];

        } catch (\Exception $e) {
            Log::error('GuestPostpaidService: Error cancelling authorization', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle webhook for payment events
     * 
     * @param array $payload
     * @param string $gatewayType
     * @return array
     */
    public function handleWebhook(array $payload, string $gatewayType): array
    {
        $eventType = $payload['type'] ?? $payload['event_type'] ?? '';

        Log::info('GuestPostpaidService: Processing webhook', [
            'gateway_type' => $gatewayType,
            'event_type' => $eventType,
        ]);

        return match($eventType) {
            'payment_intent.succeeded', 'authorization.success' => $this->handleAuthorizationSuccess($payload),
            'payment_intent.payment_failed', 'authorization.failed' => $this->handleAuthorizationFailed($payload),
            'payment_intent.captured', 'capture.success' => $this->handleCaptureSuccess($payload),
            'payment_intent.capture_failed', 'capture.failed' => $this->handleCaptureFailed($payload),
            default => [
                'success' => true,
                'message' => 'Event type not handled: ' . $eventType,
            ],
        };
    }

    /**
     * Handle successful authorization
     */
    protected function handleAuthorizationSuccess(array $payload): array
    {
        $authId = $payload['data']['object']['id'] ?? $payload['authorization_id'] ?? null;

        if (!$authId) {
            return ['success' => false, 'error' => 'No authorization ID found'];
        }

        $transaction = Transaction::where('gateway_transaction_id', $authId)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'authorized',
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'authorized_at' => now()->toIso8601String(),
                    'webhook_received' => true,
                ]),
            ]);

            // Update reservation status
            if ($transaction->reservation) {
                $transaction->reservation->update([
                    'payment_status' => 'authorized',
                ]);
            }
        }

        return ['success' => true];
    }

    /**
     * Handle failed authorization
     */
    protected function handleAuthorizationFailed(array $payload): array
    {
        $authId = $payload['data']['object']['id'] ?? $payload['authorization_id'] ?? null;

        if (!$authId) {
            return ['success' => false, 'error' => 'No authorization ID found'];
        }

        $transaction = Transaction::where('gateway_transaction_id', $authId)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'failed',
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'failed_at' => now()->toIso8601String(),
                    'error_message' => $payload['data']['object']['error']['message'] ?? 'Authorization failed',
                ]),
            ]);

            // Update reservation status
            if ($transaction->reservation) {
                $transaction->reservation->update([
                    'payment_status' => 'failed',
                    'status' => 'cancelled',
                ]);
            }
        }

        return ['success' => true];
    }

    /**
     * Handle successful capture
     */
    protected function handleCaptureSuccess(array $payload): array
    {
        $captureId = $payload['data']['object']['id'] ?? $payload['capture_id'] ?? null;

        if (!$captureId) {
            return ['success' => false, 'error' => 'No capture ID found'];
        }

        // Find the transaction by capture ID in payment_data
        $transaction = Transaction::where('payment_data->capture_id', $captureId)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'completed',
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'captured_at' => now()->toIso8601String(),
                    'webhook_received' => true,
                ]),
            ]);
        }

        return ['success' => true];
    }

    /**
     * Handle failed capture
     */
    protected function handleCaptureFailed(array $payload): array
    {
        $captureId = $payload['data']['object']['id'] ?? $payload['capture_id'] ?? null;

        Log::warning('GuestPostpaidService: Capture failed webhook received', [
            'capture_id' => $captureId,
            'payload' => $payload,
        ]);

        // This should trigger a retry or manual intervention
        return ['success' => true, 'requires_action' => true];
    }

    /**
     * Calculate authorization amount (estimated cost + buffer)
     */
    protected function calculateAuthorizationAmount(float $estimatedCost): float
    {
        // Add 20% buffer to cover potential overage
        return round($estimatedCost * 1.2, 2);
    }

    /**
     * Get payment methods for a guest
     * 
     * @param string $email
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPaymentMethodsForGuest(string $email)
    {
        return GuestPaymentMethod::forGuest($email)
            ->active()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Deactivate a payment method
     * 
     * @param GuestPaymentMethod $paymentMethod
     * @return bool
     */
    public function deactivatePaymentMethod(GuestPaymentMethod $paymentMethod): bool
    {
        $paymentMethod->update(['is_active' => false]);
        
        Log::info('GuestPostpaidService: Payment method deactivated', [
            'payment_method_id' => $paymentMethod->id,
            'guest_email' => $paymentMethod->guest_email,
        ]);

        return true;
    }

    /**
     * Process refund for a guest postpaid session
     * 
     * @param Reservation $reservation
     * @param float $refundAmount
     * @param string $reason
     * @return array
     */
    public function processRefund(Reservation $reservation, float $refundAmount, string $reason = ''): array
    {
        try {
            $transaction = Transaction::where('reservation_id', $reservation->id)
                ->where('status', 'completed')
                ->first();

            if (!$transaction) {
                return [
                    'success' => false,
                    'error' => 'No completed transaction found',
                ];
            }

            $gateway = $this->paymentGatewayService->resolveGateway($reservation->chargingPoint);

            $result = $gateway->refund(
                $transaction->gateway_transaction_id,
                $refundAmount,
                $reservation->currency ?? 'MAD',
                [
                    'reservation_id' => $reservation->id,
                    'reason' => $reason,
                    'original_amount' => $transaction->amount,
                ]
            );

            if ($result->isFailed()) {
                throw new \Exception($result->errorMessage ?? 'Refund failed');
            }

            // Create refund transaction
            $refundTransaction = Transaction::create([
                'user_id' => $reservation->user_id,
                'charge_point_id' => $reservation->charging_point_id,
                'reservation_id' => $reservation->id,
                'amount' => -$refundAmount, // Negative for refund
                'currency' => $reservation->currency ?? 'MAD',
                'type' => 'refund',
                'status' => 'completed',
                'gateway_type' => $gateway->getGatewayType(),
                'gateway_transaction_id' => $result->id,
                'payment_data' => [
                    'original_transaction_id' => $transaction->id,
                    'refund_id' => $result->id,
                    'reason' => $reason,
                ],
                'payment_method' => 'guest_postpaid',
            ]);

            Log::info('GuestPostpaidService: Refund processed', [
                'reservation_id' => $reservation->id,
                'refund_amount' => $refundAmount,
                'refund_id' => $result->id,
            ]);

            return [
                'success' => true,
                'refund_id' => $result->id,
                'refund_amount' => $refundAmount,
            ];

        } catch (\Exception $e) {
            Log::error('GuestPostpaidService: Refund failed', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
