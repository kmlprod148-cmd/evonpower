<?php

namespace App\Services;

use App\Models\Integrator;
use App\Models\IntegratorPaymentCredential;
use App\Models\ChargingSession;
use App\Models\Reservation;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Integrator Webhook Service
 * 
 * Handles payment webhooks from Stripe and CMI specific to each integrator.
 * Routes webhooks to the correct integrator based on event metadata
 * and processes them accordingly.
 * 
 * Features:
 * - Integrator identification from webhook payload
 * - Signature verification per integrator
 * - Event routing and processing
 * - Transaction integrity
 * - Comprehensive logging
 */
class IntegratorWebhookService
{
    protected IntegratorCredentialService $credentialService;
    
    public function __construct(IntegratorCredentialService $credentialService)
    {
        $this->credentialService = $credentialService;
    }
    
    /**
     * Handle Stripe webhook
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleStripeWebhook(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('stripe-signature');
        
        Log::info('Stripe webhook received', [
            'signature_present' => !empty($signature),
            'content_length' => strlen($payload),
        ]);
        
        // Extract integrator from metadata if present
        $eventData = json_decode($payload, true);
        $integratorId = $eventData['data']['object']['metadata']['integrator_id'] ?? null;
        
        // If no integrator in metadata, try to determine from other fields
        if (!$integratorId) {
            $integratorId = $eventData['data']['object']['metadata']['partner_id'] ?? null;
        }
        
        // Process the webhook
        return $this->processStripeEvent($eventData, $signature, $payload, $integratorId);
    }
    
    /**
     * Handle CMI webhook
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleCmiWebhook(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->all();
        
        Log::info('CMI webhook received', [
            'data' => $data,
        ]);
        
        // Extract integrator from the response
        $integratorId = $data['extra']['integrator_id'] ?? null;
        
        // Process the webhook
        return $this->processCmiEvent($data, $integratorId);
    }
    
    /**
     * Process Stripe event
     * 
     * @param array $eventData
     * @param string|null $signature
     * @param string $payload
     * @param int|null $integratorId
     * @return \Illuminate\Http\JsonResponse
     */
    protected function processStripeEvent(
        array $eventData,
        ?string $signature,
        string $payload,
        ?int $integratorId
    ): \Illuminate\Http\JsonResponse {
        $eventType = $eventData['type'] ?? 'unknown';
        
        Log::info('Processing Stripe event', [
            'event_type' => $eventType,
            'integrator_id' => $integratorId,
        ]);
        
        // Get integrator if specified
        $integrator = null;
        if ($integratorId) {
            $integrator = Integrator::find($integratorId);
        }
        
        // Verify signature if integrator has credentials
        if ($integrator) {
            $verified = $this->verifyStripeSignature($signature, $payload, $integrator);
            
            if (!$verified) {
                Log::warning('Stripe webhook signature verification failed', [
                    'integrator_id' => $integratorId,
                    'event_type' => $eventType,
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid signature'
                ], 400);
            }
        }
        
        // Process based on event type
        try {
            return match ($eventType) {
                'payment_intent.succeeded' => $this->handlePaymentSuccess($eventData, $integrator),
                'payment_intent.payment_failed' => $this->handlePaymentFailure($eventData, $integrator),
                'payment_intent.canceled' => $this->handlePaymentCanceled($eventData, $integrator),
                'charge.refunded' => $this->handleRefund($eventData, $integrator),
                'payment_intent.requires_action' => $this->handleRequiresAction($eventData, $integrator),
                default => $this->handleUnknownEvent($eventData, $integrator),
            };
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing error', [
                'event_type' => $eventType,
                'integrator_id' => $integratorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Processing error'
            ], 500);
        }
    }
    
    /**
     * Process CMI event
     * 
     * @param array $data
     * @param int|null $integratorId
     * @return \Illuminate\Http\JsonResponse
     */
    protected function processCmiEvent(array $data, ?int $integratorId): \Illuminate\Http\JsonResponse
    {
        $responseCode = $data['Response'] ?? '';
        $transactionId = $data['oid'] ?? $data['transaction_id'] ?? null;
        
        Log::info('Processing CMI event', [
            'response_code' => $responseCode,
            'transaction_id' => $transactionId,
            'integrator_id' => $integratorId,
        ]);
        
        // Find the reservation/transaction
        $reservation = Reservation::where('id', $transactionId)->first();
        
        if (!$reservation) {
            Log::warning('CMI webhook: Reservation not found', [
                'transaction_id' => $transactionId,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Transaction not found'
            ], 404);
        }
        
        // Verify integrator matches if specified
        if ($integratorId && $reservation->integrator_id !== $integratorId) {
            Log::warning('CMI webhook: Integrator mismatch', [
                'expected_integrator' => $reservation->integrator_id,
                'webhook_integrator' => $integratorId,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Integrator mismatch'
            ], 403);
        }
        
        // Process based on response code
        try {
            if ($responseCode === '00') {
                return $this->handleCmiSuccess($data, $reservation);
            } else {
                return $this->handleCmiFailure($data, $reservation);
            }
        } catch (\Exception $e) {
            Log::error('CMI webhook processing error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Processing error'
            ], 500);
        }
    }
    
    /**
     * Verify Stripe webhook signature
     * 
     * @param string|null $signature
     * @param string $payload
     * @param Integrator $integrator
     * @return bool
     */
    protected function verifyStripeSignature(
        ?string $signature,
        string $payload,
        Integrator $integrator
    ): bool {
        if (empty($signature)) {
            // In development, allow without signature
            if (app()->environment('local', 'development')) {
                Log::info('Stripe webhook: Development mode - signature not verified');
                return true;
            }
            
            return false;
        }
        
        // Get integrator's webhook secret
        $credentials = $this->credentialService->getAllCredentials($integrator, 'stripe');
        $webhookSecret = $credentials['webhook_secret'] ?? null;
        
        if (empty($webhookSecret)) {
            Log::warning('Stripe webhook: No webhook secret configured for integrator', [
                'integrator_id' => $integrator->id,
            ]);
            
            return app()->environment('local', 'development');
        }
        
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $signature, $webhookSecret);
            return $event !== null;
        } catch (\Exception $e) {
            Log::error('Stripe signature verification exception', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Handle successful payment
     */
    protected function handlePaymentSuccess(array $eventData, ?Integrator $integrator): \Illuminate\Http\JsonResponse
    {
        $paymentIntent = $eventData['data']['object'];
        $metadata = $paymentIntent['metadata'] ?? [];
        
        // Find the reservation
        $reservationId = $metadata['reservation_id'] ?? $metadata['session_id'] ?? null;
        
        if (!$reservationId) {
            Log::warning('Stripe webhook: No reservation_id in metadata', $metadata);
            return response()->json(['success' => false, 'error' => 'Missing reservation_id'], 400);
        }
        
        $reservation = Reservation::find($reservationId);
        
        if (!$reservation) {
            Log::warning('Stripe webhook: Reservation not found', ['reservation_id' => $reservationId]);
            return response()->json(['success' => false, 'error' => 'Reservation not found'], 404);
        }
        
        // Update reservation status
        $reservation->update([
            'status' => 'confirmed',
            'metadata' => array_merge($reservation->metadata ?? [], [
                'payment_intent_id' => $paymentIntent['id'],
                'payment_confirmed_at' => now()->toIso8601String(),
                'payment_gateway' => 'stripe',
                'integrator_id' => $integrator?->id,
            ]),
        ]);
        
        // Update transaction if exists
        if ($reservation->transaction_id) {
            Transaction::where('id', $reservation->transaction_id)->update([
                'status' => 'completed',
                'gateway_transaction_id' => $paymentIntent['id'],
            ]);
        }
        
        Log::info('Payment confirmed via webhook', [
            'reservation_id' => $reservation->id,
            'payment_intent_id' => $paymentIntent['id'],
            'integrator_id' => $integrator?->id,
        ]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Handle failed payment
     */
    protected function handlePaymentFailure(array $eventData, ?Integrator $integrator): \Illuminate\Http\JsonResponse
    {
        $paymentIntent = $eventData['data']['object'];
        $metadata = $paymentIntent['metadata'] ?? [];
        
        $reservationId = $metadata['reservation_id'] ?? null;
        
        if ($reservationId) {
            $reservation = Reservation::find($reservationId);
            
            if ($reservation) {
                $reservation->update([
                    'status' => 'payment_failed',
                    'metadata' => array_merge($reservation->metadata ?? [], [
                        'payment_error' => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed',
                        'failed_at' => now()->toIso8601String(),
                    ]),
                ]);
                
                Log::info('Payment failed via webhook', [
                    'reservation_id' => $reservation->id,
                    'integrator_id' => $integrator?->id,
                ]);
            }
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Handle canceled payment
     */
    protected function handlePaymentCanceled(array $eventData, ?Integrator $integrator): \Illuminate\Http\JsonResponse
    {
        $paymentIntent = $eventData['data']['object'];
        $metadata = $paymentIntent['metadata'] ?? [];
        
        $reservationId = $metadata['reservation_id'] ?? null;
        
        if ($reservationId) {
            $reservation = Reservation::find($reservationId);
            
            if ($reservation) {
                $reservation->update([
                    'status' => 'cancelled',
                    'metadata' => array_merge($reservation->metadata ?? [], [
                        'cancelled_at' => now()->toIso8601String(),
                        'cancelled_reason' => 'payment_canceled',
                    ]),
                ]);
                
                Log::info('Payment canceled via webhook', [
                    'reservation_id' => $reservation->id,
                    'integrator_id' => $integrator?->id,
                ]);
            }
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Handle refund
     */
    protected function handleRefund(array $eventData, ?Integrator $integrator): \Illuminate\Http\JsonResponse
    {
        $charge = $eventData['data']['object'];
        $paymentIntentId = $charge['payment_intent'] ?? null;
        
        if ($paymentIntentId) {
            // Find transaction and process refund
            $transaction = Transaction::where('gateway_transaction_id', $paymentIntentId)->first();
            
            if ($transaction) {
                $refundAmount = ($charge['amount_refunded'] ?? 0) / 100;
                
                $transaction->update([
                    'refund_amount' => $refundAmount,
                    'status' => $refundAmount >= $transaction->amount ? 'refunded' : 'partially_refunded',
                ]);
                
                Log::info('Refund processed via webhook', [
                    'transaction_id' => $transaction->id,
                    'refund_amount' => $refundAmount,
                    'integrator_id' => $integrator?->id,
                ]);
            }
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Handle payment requires action (3D Secure)
     */
    protected function handleRequiresAction(array $eventData, ?Integrator $integrator): \Illuminate\Http\JsonResponse
    {
        $paymentIntent = $eventData['data']['object'];
        $metadata = $paymentIntent['metadata'] ?? [];
        
        $reservationId = $metadata['reservation_id'] ?? null;
        
        if ($reservationId) {
            $reservation = Reservation::find($reservationId);
            
            if ($reservation) {
                $reservation->update([
                    'metadata' => array_merge($reservation->metadata ?? [], [
                        'requires_action' => true,
                        'client_secret' => $paymentIntent['client_secret'],
                        'action_required_at' => now()->toIso8601String(),
                    ]),
                ]);
                
                Log::info('Payment requires action via webhook', [
                    'reservation_id' => $reservation->id,
                    'integrator_id' => $integrator?->id,
                ]);
            }
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Handle unknown event type
     */
    protected function handleUnknownEvent(array $eventData, ?Integrator $integrator): \Illuminate\Http\JsonResponse
    {
        Log::info('Unknown Stripe event type received', [
            'event_type' => $eventData['type'] ?? 'unknown',
            'integrator_id' => $integrator?->id,
        ]);
        
        return response()->json(['success' => true, 'message' => 'Event received']);
    }
    
    /**
     * Handle CMI success
     */
    protected function handleCmiSuccess(array $data, Reservation $reservation): \Illuminate\Http\JsonResponse
    {
        $reservation->update([
            'status' => 'confirmed',
            'metadata' => array_merge($reservation->metadata ?? [], [
                'cmi_response_code' => $data['Response'],
                'cmi_transaction_id' => $data['transaction_id'] ?? null,
                'payment_confirmed_at' => now()->toIso8601String(),
                'payment_gateway' => 'cmi',
            ]),
        ]);
        
        // Update transaction
        if ($reservation->transaction_id) {
            Transaction::where('id', $reservation->transaction_id)->update([
                'status' => 'completed',
                'gateway_transaction_id' => $data['transaction_id'] ?? null,
            ]);
        }
        
        Log::info('CMI payment confirmed via webhook', [
            'reservation_id' => $reservation->id,
            'transaction_id' => $data['transaction_id'] ?? null,
        ]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Handle CMI failure
     */
    protected function handleCmiFailure(array $data, Reservation $reservation): \Illuminate\Http\JsonResponse
    {
        $errorMessage = $this->getCmiErrorMessage($data['Response'] ?? '');
        
        $reservation->update([
            'status' => 'payment_failed',
            'metadata' => array_merge($reservation->metadata ?? [], [
                'cmi_response_code' => $data['Response'],
                'cmi_error_message' => $errorMessage,
                'failed_at' => now()->toIso8601String(),
            ]),
        ]);
        
        Log::info('CMI payment failed via webhook', [
            'reservation_id' => $reservation->id,
            'response_code' => $data['Response'],
        ]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Get CMI error message from response code
     */
    protected function getCmiErrorMessage(string $code): string
    {
        return match ($code) {
            '01' => 'Card declined',
            '05' => 'Card not accepted',
            '12' => 'Invalid transaction',
            '14' => 'Invalid card number',
            '33' => 'Card expired',
            '34' => 'Suspicious card',
            '41' => 'Lost card',
            '43' => 'Stolen card',
            '51' => 'Insufficient funds',
            '75' => 'Maximum attempts exceeded',
            default => 'Payment failed',
        };
    }
}
