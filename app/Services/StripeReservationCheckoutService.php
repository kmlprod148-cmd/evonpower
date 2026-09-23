<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StripeReservationCheckoutService
{
    public function __construct(
        protected ReservationPaymentApprovalService $approvalService
    ) {
    }

    public function handleWebhookEvent(array $event): array
    {
        $type = (string) Arr::get($event, 'type', '');

        return match ($type) {
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded' => $this->handleSuccessfulCheckoutSessionWebhook(
                (array) Arr::get($event, 'data.object', []),
                $event
            ),
            'payment_intent.succeeded' => $this->handleSuccessfulPaymentIntentWebhook(
                (array) Arr::get($event, 'data.object', []),
                $event
            ),
            'payment_intent.payment_failed',
            'payment_intent.canceled',
            'checkout.session.async_payment_failed' => $this->handleFailedPaymentWebhook(
                (array) Arr::get($event, 'data.object', []),
                $event
            ),
            default => [
                'success' => true,
                'ignored' => true,
                'message' => "Unhandled Stripe event type [{$type}]",
            ],
        };
    }

    public function resolveReservationForSuccessRedirect(?string $reservationId, ?string $sessionId = null): ?Reservation
    {
        if ($reservationId !== null && $reservationId !== '') {
            $reservation = Reservation::find($reservationId);
            if ($reservation) {
                return $reservation;
            }
        }

        if (!$sessionId) {
            return null;
        }

        $transaction = Transaction::with('reservation')
            ->where('stripe_session_id', $sessionId)
            ->orWhere('payment_reference', $sessionId)
            ->latest('id')
            ->first();

        return $transaction?->reservation;
    }

    protected function handleSuccessfulCheckoutSessionWebhook(array $session, array $event): array
    {
        $paymentStatus = strtolower((string) ($session['payment_status'] ?? ''));
        if ($paymentStatus !== '' && $paymentStatus !== 'paid') {
            return [
                'success' => true,
                'ignored' => true,
                'message' => "Checkout session not paid yet [{$paymentStatus}]",
            ];
        }

        return $this->finalizeSuccessfulPayment($this->extractContextFromStripeObject($session), $session, $event);
    }

    protected function handleSuccessfulPaymentIntentWebhook(array $paymentIntent, array $event): array
    {
        return $this->finalizeSuccessfulPayment(
            $this->extractContextFromStripeObject($paymentIntent),
            $paymentIntent,
            $event
        );
    }

    protected function handleFailedPaymentWebhook(array $payload, array $event): array
    {
        $context = $this->extractContextFromStripeObject($payload);
        $reason = (string) (
            Arr::get($payload, 'last_payment_error.message')
            ?? Arr::get($payload, 'cancellation_reason')
            ?? Arr::get($payload, 'status')
            ?? 'Stripe payment failed'
        );

        try {
            $transaction = $this->resolveTransaction($context);
            if (!$transaction) {
                Log::warning('StripeReservationCheckoutService: failed webhook could not resolve transaction', [
                    'context' => $context,
                    'event_type' => $event['type'] ?? null,
                ]);

                return [
                    'success' => false,
                    'error' => 'Transaction not found for failed payment webhook',
                ];
            }

            DB::transaction(function () use ($transaction, $context, $reason, $event) {
                $lockedTransaction = Transaction::whereKey($transaction->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $reservation = Reservation::whereKey($lockedTransaction->reservation_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $gatewayTransactionId = $context['payment_intent_id'] ?? $context['session_id'] ?? null;

                $lockedTransaction->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'failure_reason' => $reason,
                    'stripe_session_id' => $context['session_id'] ?? $lockedTransaction->stripe_session_id,
                    'payment_reference' => $lockedTransaction->payment_reference ?: ($context['session_id'] ?? $gatewayTransactionId),
                    'gateway_response' => $payload,
                    'webhook_data' => $event,
                    'metadata' => array_merge($lockedTransaction->metadata ?? [], [
                        'stripe_payment_intent_id' => $context['payment_intent_id'] ?? null,
                        'stripe_failed_at' => now()->toIso8601String(),
                        'stripe_event_type' => $event['type'] ?? null,
                        'stripe_failure_reason' => $reason,
                    ]),
                ]);

                $reservation->update([
                    'payment_status' => 'FAILED',
                    'payment_method' => $reservation->payment_method ?: 'stripe',
                    'payment_gateway_transaction_id' => $gatewayTransactionId,
                    'payment_webhook_received_at' => now(),
                    'last_error' => $reason,
                    'last_error_at' => now(),
                ]);
            });

            return [
                'success' => true,
                'message' => 'Failed Stripe payment recorded',
            ];
        } catch (Throwable $e) {
            Log::error('StripeReservationCheckoutService: failed payment webhook processing failed', [
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function finalizeSuccessfulPayment(array $context, array $payload, array $event): array
    {
        try {
            $transaction = $this->resolveTransaction($context);
            if (!$transaction) {
                Log::warning('StripeReservationCheckoutService: successful webhook could not resolve transaction', [
                    'context' => $context,
                    'event_type' => $event['type'] ?? null,
                ]);

                return [
                    'success' => false,
                    'error' => 'Transaction not found for successful Stripe webhook',
                ];
            }

            $result = DB::transaction(function () use ($transaction, $context, $payload, $event) {
                $lockedTransaction = Transaction::whereKey($transaction->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $reservation = Reservation::whereKey($lockedTransaction->reservation_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $alreadyProcessed = strtolower((string) $lockedTransaction->status) === 'completed'
                    && $reservation->isPaid();

                $gatewayTransactionId = $context['payment_intent_id'] ?? $context['session_id'] ?? null;

                $metadata = array_merge($lockedTransaction->metadata ?? [], [
                    'stripe_event_id' => $event['id'] ?? null,
                    'stripe_event_type' => $event['type'] ?? null,
                    'stripe_payment_intent_id' => $context['payment_intent_id'] ?? null,
                    'stripe_checkout_session_id' => $context['session_id'] ?? null,
                    'stripe_payment_confirmed_at' => now()->toIso8601String(),
                ]);

                $lockedTransaction->update([
                    'status' => 'completed',
                    'completed_at' => $lockedTransaction->completed_at ?? now(),
                    'stripe_session_id' => $context['session_id'] ?? $lockedTransaction->stripe_session_id,
                    'payment_reference' => $lockedTransaction->payment_reference ?: ($context['session_id'] ?? $gatewayTransactionId),
                    'gateway_response' => $payload,
                    'webhook_data' => $event,
                    'metadata' => $metadata,
                ]);

                if (!$alreadyProcessed) {
                    $approvalResult = $this->approvalService->applyPaymentSuccess(
                        $reservation,
                        'stripe',
                        'stripe_webhook'
                    );

                    $reservation = $approvalResult['reservation'];
                }

                $reservationUpdate = [
                    'payment_status' => 'PAID',
                    'payment_method' => 'stripe',
                    'payment_confirmed_at' => $reservation->payment_confirmed_at ?? now(),
                    'payment_gateway_transaction_id' => $gatewayTransactionId,
                    'payment_webhook_received_at' => now(),
                    'last_error' => null,
                    'last_error_at' => null,
                ];

                if (!$alreadyProcessed) {
                    $initialRemoteStartStatus = $this->determineInitialRemoteStartStatus($reservation);

                    if (
                        $initialRemoteStartStatus
                        && !in_array((string) $reservation->session_initiation_status, ['queued', 'scheduled', 'processing', 'success'], true)
                    ) {
                        $reservationUpdate['session_initiation_status'] = $initialRemoteStartStatus;
                    }
                }

                $reservation->update($reservationUpdate);
                $reservation->refresh();

                $shouldDispatchApprovalEvent = !$alreadyProcessed
                    && config('auto-remote-start.enabled', true)
                    && $reservation->isApproved()
                    && !$reservation->charging_session_id;

                if ($shouldDispatchApprovalEvent) {
                    DB::afterCommit(function () use ($reservation) {
                        $this->approvalService->dispatchApprovalEvent($reservation, 'stripe_webhook');
                    });
                }

                return [
                    'reservation' => $reservation,
                    'transaction' => $lockedTransaction->fresh(),
                    'already_processed' => $alreadyProcessed,
                    'remote_start_status' => $reservation->session_initiation_status,
                ];
            });

            Log::info('StripeReservationCheckoutService: Stripe payment confirmed', [
                'reservation_id' => $result['reservation']->id,
                'transaction_id' => $result['transaction']->id,
                'already_processed' => $result['already_processed'],
                'remote_start_status' => $result['remote_start_status'],
            ]);

            return array_merge(['success' => true], $result);
        } catch (Throwable $e) {
            Log::error('StripeReservationCheckoutService: successful payment webhook processing failed', [
                'context' => $context,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function determineInitialRemoteStartStatus(Reservation $reservation): ?string
    {
        if (!config('auto-remote-start.enabled', true) || !$reservation->isApproved()) {
            return null;
        }

        if ($reservation->charging_session_id) {
            return 'success';
        }

        if (config('auto-remote-start.mode.start_mode', 'auto') === 'immediate') {
            return 'queued';
        }

        return $reservation->canStartNow() ? 'queued' : 'scheduled';
    }

    protected function resolveTransaction(array $context): ?Transaction
    {
        if (!empty($context['transaction_id'])) {
            $transaction = Transaction::with('reservation')->find($context['transaction_id']);
            if ($transaction) {
                return $transaction;
            }
        }

        if (!empty($context['reservation_id'])) {
            $reservation = Reservation::find($context['reservation_id']);
            if ($reservation) {
                return $reservation->transaction ?: $this->createFallbackTransaction($reservation, $context);
            }
        }

        if (!empty($context['session_id'])) {
            return Transaction::with('reservation')
                ->where('stripe_session_id', $context['session_id'])
                ->orWhere('payment_reference', $context['session_id'])
                ->latest('id')
                ->first();
        }

        return null;
    }

    protected function createFallbackTransaction(Reservation $reservation, array $context): Transaction
    {
        return Transaction::create([
            'reservation_id' => $reservation->id,
            'charging_point_id' => $reservation->charging_point_id,
            'user_id' => $reservation->user_id,
            'amount' => $reservation->estimated_cost ?? $reservation->amount ?? 0,
            'currency' => strtoupper((string) ($context['currency'] ?? 'EUR')),
            'payment_method' => 'stripe',
            'payment_reference' => $context['session_id'] ?? $context['payment_intent_id'] ?? null,
            'stripe_session_id' => $context['session_id'] ?? null,
            'status' => 'pending',
            'transaction_type' => 'client',
        ]);
    }

    protected function extractContextFromStripeObject(array $payload): array
    {
        return [
            'reservation_id' => Arr::get($payload, 'metadata.reservation_id'),
            'transaction_id' => Arr::get($payload, 'metadata.transaction_id'),
            'session_id' => $this->detectStripeSessionId($payload),
            'payment_intent_id' => $this->detectStripePaymentIntentId($payload),
            'currency' => Arr::get($payload, 'currency'),
            'customer_email' => Arr::get($payload, 'customer_details.email')
                ?? Arr::get($payload, 'customer_email'),
        ];
    }

    protected function detectStripeSessionId(array $payload): ?string
    {
        $id = (string) ($payload['id'] ?? '');
        if (str_starts_with($id, 'cs_')) {
            return $id;
        }

        $sessionId = (string) ($payload['checkout_session'] ?? '');
        return str_starts_with($sessionId, 'cs_') ? $sessionId : null;
    }

    protected function detectStripePaymentIntentId(array $payload): ?string
    {
        $id = (string) ($payload['id'] ?? '');
        if (str_starts_with($id, 'pi_')) {
            return $id;
        }

        $paymentIntentId = $payload['payment_intent'] ?? null;
        if (is_string($paymentIntentId) && str_starts_with($paymentIntentId, 'pi_')) {
            return $paymentIntentId;
        }

        if (is_array($paymentIntentId)) {
            $nestedId = (string) ($paymentIntentId['id'] ?? '');
            return str_starts_with($nestedId, 'pi_') ? $nestedId : null;
        }

        return null;
    }
}
