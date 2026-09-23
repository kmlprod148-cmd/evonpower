<?php

namespace App\Http\Controllers;

use App\Jobs\StartChargingSessionJob;
use App\Models\ChargingSession;
use App\Models\Reservation;
use App\Services\ReservationPaymentApprovalService;
use App\Services\StripeReservationCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected StripeReservationCheckoutService $checkoutService,
        protected ReservationPaymentApprovalService $approvalService
    ) {}

    /**
     * Handle incoming Stripe webhook events.
     *
     * Stripe signs every webhook delivery with a HMAC-SHA256 signature sent in
     * the Stripe-Signature header. We verify the signature via the official
     * stripe-php SDK before touching any application logic.
     *
     * Important: this route must be excluded from CSRF verification (see
     * VerifyCsrfToken::$except — the 'webhooks/*' pattern already covers it).
     * The raw request body must be read before any middleware parses it, which
     * is why we call $request->getContent() rather than $request->all().
     */
    public function handleStripeWebhook(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $sigHeader  = (string) $request->header('Stripe-Signature', '');

        $webhookSecret = config('payments.stripe.webhook_secret')
            ?? config('payment.stripe.webhook_secret')
            ?? config('services.stripe.webhook_secret')
            ?? env('STRIPE_WEBHOOK_SECRET', '');

        if (empty($webhookSecret)) {
            Log::critical('PaymentWebhookController: STRIPE_WEBHOOK_SECRET is not configured');
            // Return 500 so Stripe retries – this is a server misconfiguration
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        // ── 1. Signature verification ─────────────────────────────────────────
        try {
            $event = Webhook::constructEvent($rawPayload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::warning('PaymentWebhookController: Stripe signature mismatch', [
                'error' => $e->getMessage(),
                'sig'   => $sigHeader ? substr($sigHeader, 0, 40) . '…' : '(none)',
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('PaymentWebhookController: Malformed Stripe payload', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $eventArray = $event->toArray();

        Log::info('PaymentWebhookController: Stripe event received', [
            'event_id'   => $event->id,
            'event_type' => $event->type,
        ]);

        // ── 2. Business logic via StripeReservationCheckoutService ──────────────
        //
        // The service handles:
        //  • checkout.session.completed / async_payment_succeeded
        //  • payment_intent.succeeded
        //  • payment_intent.payment_failed / canceled / async_payment_failed
        //
        // On success it:
        //  1. Marks the Transaction as 'completed'
        //  2. Sets Reservation.payment_status = 'PAID', status = 'confirmed'
        //  3. Fires ReservationApproved → TriggerAutoRemoteStart listener
        //     → AutoStartTransactionJob (queued)
        $result = $this->checkoutService->handleWebhookEvent($eventArray);

        // ── 3. Idempotent direct-dispatch for payment_intent.succeeded ────────
        //
        // In addition to the event-driven path above we explicitly dispatch
        // StartChargingSessionJob when a payment_intent.succeeded webhook
        // arrives for a reservation that has no active charging session yet.
        // This guarantees the job is queued even when the queue worker is
        // slow to process the ReservationApproved event.
        if ($event->type === 'payment_intent.succeeded' && ($result['success'] ?? false)) {
            $this->dispatchChargingJobIfNeeded($result['reservation'] ?? null);
        }

        // ── 4. Response ─────────────────────────────────────────────────────────
        //
        // Always respond 200 for recognised-but-unprocessable events so Stripe
        // does not flood us with retries for business-logic failures (e.g.
        // reservation not found). Stripe retries on 4xx/5xx only.
        if (!($result['success'] ?? false) && !($result['ignored'] ?? false)) {
            Log::error('PaymentWebhookController: Checkout service processing failed', [
                'event_id'   => $event->id,
                'event_type' => $event->type,
                'error'      => $result['error'] ?? 'Unknown error',
            ]);
        }

        return response()->json(['received' => true], 200);
    }

    /**
     * Dispatch StartChargingSessionJob if the reservation is confirmed+paid
     * and does not already have an active charging session.
     *
     * This is idempotent: StartChargingSessionJob itself checks for existing
     * sessions and exits early if one is already active.
     */
    protected function dispatchChargingJobIfNeeded(?Reservation $reservation): void
    {
        if (!$reservation) {
            return;
        }

        $reservation->refresh();

        if (!$reservation->isApproved()) {
            return;
        }

        $hasActiveSession = ChargingSession::where('reservation_id', $reservation->id)
            ->whereIn('status', [
                ChargingSession::STATUS_PENDING,
                ChargingSession::STATUS_INITIATING,
                ChargingSession::STATUS_ACTIVE,
                ChargingSession::STATUS_IN_PROGRESS,
            ])
            ->exists();

        if ($hasActiveSession) {
            Log::info('PaymentWebhookController: Active session exists, skipping job dispatch', [
                'reservation_id' => $reservation->id,
            ]);
            return;
        }

        Log::info('PaymentWebhookController: Dispatching StartChargingSessionJob from webhook', [
            'reservation_id' => $reservation->id,
        ]);

        StartChargingSessionJob::dispatch($reservation->id);
    }

    // =========================================================================
    // CMI Payment Callback
    // =========================================================================

    /**
     * Handle CMI payment gateway callbacks.
     *
     * CMI POSTs a signed callback to this endpoint after every transaction.
     * We verify the source IP, validate the CMI hash, then update the relevant
     * Order and Reservation records.
     */
    public function handleCmiCallback(Request $request): JsonResponse
    {
        try {
            Log::info('PaymentWebhookController: CMI callback received', $request->all());

            if (!$this->isValidCmiIp($request->ip())) {
                Log::warning('PaymentWebhookController: CMI callback from disallowed IP', [
                    'ip'   => $request->ip(),
                    'data' => $request->all(),
                ]);
                return response()->json(['error' => 'Invalid IP'], 403);
            }

            $isValidSignature = $this->verifyCmiSignature(
                $request->all(),
                (string) $request->get('hash', '')
            );

            if (!$isValidSignature) {
                Log::error('PaymentWebhookController: CMI callback with invalid signature', [
                    'data' => $request->all(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $this->processCmiCallback($request);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('PaymentWebhookController: CMI callback processing failed', [
                'error' => $e->getMessage(),
                'data'  => $request->all(),
            ]);
            return response()->json(['success' => false, 'error' => 'Callback processing failed'], 400);
        }
    }

    // ── CMI helpers ─────────────────────────────────────────────────────────

    private function isValidCmiIp(string $ip): bool
    {
        $allowed = config('cmi.ip_whitelist', []);

        foreach ($allowed as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    private function ipInRange(string $ip, string $range): bool
    {
        if (str_contains($range, '/')) {
            [$subnet, $bits] = explode('/', $range, 2);
            $mask = -1 << (32 - (int) $bits);
            return (ip2long($ip) & $mask) === (ip2long($subnet) & $mask);
        }

        return $ip === $range;
    }

    private function verifyCmiSignature(array $data, string $hash): bool
    {
        $storeKey = config('cmi.store_key', env('CMI_STOREKEY', ''));

        if (empty($storeKey)) {
            Log::warning('PaymentWebhookController: CMI store key is not configured');
            return false;
        }

        // Remove the hash field itself before re-computing
        $params = $data;
        unset($params['hash']);

        ksort($params);
        $hashStr = implode('|', array_values($params)) . '|' . $storeKey;
        $expected = strtoupper(hash('sha512', $hashStr));

        return hash_equals($expected, strtoupper($hash));
    }

    private function processCmiCallback(Request $request): void
    {
        $orderId  = (string) $request->get('oid', '');
        $status   = (string) $request->get('ProcReturnCode', '');
        $amount   = $request->get('amount');
        $currency = $request->get('currency');

        Log::info('PaymentWebhookController: Processing CMI callback', [
            'order_id' => $orderId,
            'status'   => $status,
            'amount'   => $amount,
            'currency' => $currency,
        ]);

        $order = \App\Models\Order::where('payment_reference', $orderId)->first();

        if (!$order) {
            Log::error('PaymentWebhookController: CMI order not found', ['order_id' => $orderId]);
            return;
        }

        $existingDetails = is_array($order->details)
            ? $order->details
            : (json_decode($order->details ?? '{}', true) ?? []);

        if ($status === '00') {
            // ── 1. Update order ───────────────────────────────────────────────
            $order->update([
                'status'         => 'completed',
                'payment_status' => 'completed',
                'details'        => array_merge($existingDetails, ['cmi_callback' => $request->all()]),
            ]);

            $reservationId = $existingDetails['reservation_id'] ?? null;
            $reservation   = $reservationId ? Reservation::find($reservationId) : null;

            if (!$reservation) {
                Log::warning('PaymentWebhookController: CMI reservation not found', [
                    'order_id'       => $order->id,
                    'reservation_id' => $reservationId,
                ]);
                return;
            }

            // ── 2. Idempotence: skip if already fully approved ────────────────
            if ($reservation->isPaid() && $reservation->isApproved()) {
                Log::info('PaymentWebhookController: CMI reservation already approved, skipping', [
                    'order_id'       => $order->id,
                    'reservation_id' => $reservation->id,
                ]);
                return;
            }

            // ── 3. Auto-approve via central service (sets approved_by = operator) ──
            $approvalResult = $this->approvalService->applyPaymentSuccess(
                $reservation,
                'cmi',
                'cmi_callback'
            );
            $reservation = $approvalResult['reservation'];

            // ── 4. Mark linked transaction as completed ───────────────────────
            $transaction = $reservation->transaction;
            if ($transaction && !$transaction->isCompleted()) {
                $transaction->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                    'payment_method' => 'cmi',
                    'metadata'     => array_merge($transaction->metadata ?? [], [
                        'cmi_order_id'      => $orderId,
                        'cmi_confirmed_at'  => now()->toIso8601String(),
                    ]),
                ]);
            }

            // ── 5. Fire ReservationApproved → triggers AutoRemoteStart listener ──
            if ($reservation->isApproved()) {
                $this->approvalService->dispatchApprovalEvent($reservation, 'cmi_callback');
            }

            // ── 6. Dispatch StartChargingSessionJob if the session can start now ──
            $this->dispatchChargingJobIfNeeded($reservation->fresh());

            Log::info('PaymentWebhookController: CMI payment auto-approved', [
                'order_id'       => $order->id,
                'reservation_id' => $reservation->id,
                'approved_by'    => $reservation->approved_by,
                'auto_approved'  => $approvalResult['auto_approved'],
            ]);
        } else {
            // ── Payment failed ────────────────────────────────────────────────
            $order->update([
                'status'         => 'failed',
                'payment_status' => 'failed',
                'details'        => array_merge($existingDetails, ['cmi_callback' => $request->all()]),
            ]);

            $reservationId = $existingDetails['reservation_id'] ?? null;
            if ($reservationId) {
                Reservation::where('id', $reservationId)->update([
                    'payment_status' => 'FAILED',
                    'last_error'     => "CMI payment failed (ProcReturnCode={$status})",
                    'last_error_at'  => now(),
                ]);
            }

            Log::info('PaymentWebhookController: CMI payment failed', [
                'order_id'   => $order->id,
                'error_code' => $status,
            ]);
        }
    }
}
