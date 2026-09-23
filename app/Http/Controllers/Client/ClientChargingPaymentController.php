<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Jobs\StartChargingSessionJob;
use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Models\OcppTag;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Exceptions\ReservationOtpException;
use App\Services\ChargingPointService;
use App\Services\Gateways\StripeGateway;
use App\Services\PaymentGatewayService;
use App\Services\ReservationPaymentApprovalService;
use App\Services\ReservationOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * ClientChargingPaymentController
 *
 * Handles the full offer → Stripe payment → Steve API charging flow for
 * authenticated users.
 *
 * Routes (require 'auth' middleware via the `web` group):
 *   GET  /client/charging/{id}                   → showOffer
 *   POST /client/charging/{id}/pay               → initiatePayment
 *   GET  /client/charging/{id}/confirm/{res}     → confirmPayment
 *   GET  /client/charging/{id}/success/{res}     → success
 *   GET  /client/charging/{id}/status/{res}      → chargingStatus
 */
class ClientChargingPaymentController extends Controller
{
    /** Hard cap on a single reservation's value (kWh or minutes). */
    private const MAX_RESERVATION_VALUE = 1000;

    public function __construct(
        protected PaymentGatewayService $paymentGatewayService,
        protected ChargingPointService  $chargingPointService,
        protected ReservationPaymentApprovalService $approvalService,
        protected ReservationOtpService $reservationOtpService,
    ) {}

    // -------------------------------------------------------------------------
    // STEP 1 – Show offer page
    // -------------------------------------------------------------------------

    public function showOffer(int $id)
    {
        $chargingPoint = $this->chargingPointService->getChargingPointWithDetails($id);
        abort_if(!$chargingPoint, 404, 'Borne de recharge non trouvée.');

        $chargingPoint->loadMissing(['connectors', 'pricingPlan.vatRate']);

        $pricingPlan = $chargingPoint->pricingPlan
            ?? PricingPlan::where('is_active', true)->first();
        abort_if(!$pricingPlan, 400, 'Aucun plan tarifaire disponible pour cette borne.');

        $gateway = new StripeGateway($chargingPoint);
        $stripePublicKey = $gateway->getPublicKey();

        return view('client.charging.offer', [
            'chargingPoint'   => $chargingPoint,
            'pricingPlan'     => $pricingPlan,
            'currency'        => strtoupper($pricingPlan->currency ?? 'EUR'),
            'stripePublicKey' => $stripePublicKey,
            'client'          => auth()->user(),
        ]);
    }

    // -------------------------------------------------------------------------
    // STEP 2 – Create reservation + Stripe PaymentIntent
    // -------------------------------------------------------------------------

    public function initiatePayment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'type'  => 'required|in:energy,duration',
            'value' => 'required|numeric|min:1|max:' . self::MAX_RESERVATION_VALUE,
        ]);

        try {
            $this->reservationOtpService->assertCanCreateReservation($request->user());
        } catch (ReservationOtpException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->errorCode,
            ], $e->statusCode);
        }

        $chargingPoint = ChargingPoint::with([
            'pricingPlan', 'pricingPlan.vatRate',
            'group', 'partner', 'integrator', 'connectors',
        ])->findOrFail($id);

        $pricingPlan = $chargingPoint->pricingPlan
            ?? PricingPlan::where('is_active', true)->firstOrFail();

        $user     = auth()->user();
        $currency = strtoupper($pricingPlan->currency ?? 'EUR');
        $cost     = $this->calculateCost($request->type, $request->value, $pricingPlan);

        // Stripe minimum varies by currency; centralize so non-EUR/USD currencies aren't
        // silently misvalidated. Values are gateway-side minimums in major units.
        $stripeMinimums = ['EUR' => 0.50, 'USD' => 0.50, 'GBP' => 0.30, 'MAD' => 5.00];
        $minCost = $stripeMinimums[$currency] ?? 0.50;
        if ($cost < $minCost) {
            return response()->json([
                'success' => false,
                'error'   => 'Le montant minimum est de ' . number_format($minCost, 2) . ' ' . $currency . '.',
            ], 422);
        }

        $connector = $chargingPoint->connectors
            ->where('status', 'Available')->first()
            ?? $chargingPoint->connectors->first();

        if (!$connector) {
            return response()->json([
                'success' => false,
                'error'   => 'Aucun connecteur disponible pour cette borne.',
            ], 422);
        }

        // Resolve or auto-create an OCPP tag for this user. Tag format: EVON-USR-{id}.
        // Relies on the unique index on ocpp_tags.ocpp_tag to make firstOrCreate safe.
        $ocppTagValue = 'EVON-USR-' . $user->id;
        $ocppTag = OcppTag::firstOrCreate(
            ['ocpp_tag' => $ocppTagValue],
            [
                'user_id'    => $user->id,
                'blocked'    => false,
                'is_default' => true,
                'note'       => 'Auto-créé pour utilisateur #' . $user->id . ' (' . $user->email . ')',
            ]
        );

        DB::beginTransaction();
        try {
            $reservation = Reservation::create([
                'user_id'           => $user->id,
                'charging_point_id' => $chargingPoint->id,
                'connector_id'      => $connector->id,
                'ocpp_tag_id'       => $ocppTag->id,
                'pricing_plan_id'   => $pricingPlan->id,
                'partner_id'        => $chargingPoint->partner_id,
                'integrator_id'     => $chargingPoint->integrator_id,
                'reservation_type'  => $request->type === 'energy' ? 'kwh' : 'minute',
                'reservation_value' => $request->value,
                'start_time'        => now(),
                'estimated_cost'    => $cost,
                'amount'            => $cost,
                'status'            => 'pending',
                // payment_type ENUM only allows 'cmi'|'offline'; stripe maps to 'cmi'.
                // TODO: extend the ENUM to include 'stripe' so reporting can distinguish gateways.
                'payment_type'      => 'cmi',
                'payment_method'    => 'stripe',
                'payment_mode'      => 'prepaid',
                'payment_status'    => 'PENDING',
                'guest_email'       => $user->email,
                'guest_phone'       => $user->phone,
                'is_guest'          => false,
            ]);

            $txRef = 'CLI-STR-' . strtoupper(uniqid());
            $transaction = Transaction::create([
                'transaction_id'    => $txRef,
                'charging_point_id' => $chargingPoint->id,
                'reservation_id'    => $reservation->id,
                'pricing_plan_id'   => $pricingPlan->id,
                'user_id'           => $user->id,
                'start_timestamp'   => now(),
                'status'            => 'pending',
                'payment_method'    => 'stripe',
                'amount'            => $cost,
                'price_total'       => $cost,
                'currency'          => $currency,
                'meter_start'       => 0,
                'metadata'          => [
                    'user_id'        => $user->id,
                    'charging_type'  => $request->type,
                    'charging_value' => $request->value,
                    'initiated_at'   => now()->toIso8601String(),
                ],
            ]);

            $gateway = new StripeGateway($chargingPoint);
            $metadata = [
                'charge_point_id' => $chargingPoint->id,
                'reservation_id'  => $reservation->id,
                'transaction_id'  => $transaction->id,
                'user_id'         => $user->id,
                'order_id'        => $txRef,
                'return_url'      => route('client.charging.confirm', [
                    'id'            => $id,
                    'reservationId' => $reservation->id,
                ]),
            ];

            $paymentIntent = $gateway->initiatePayment($cost, $currency, $metadata);

            if ($paymentIntent->isFailed()) {
                DB::rollBack();
                Log::error('ClientChargingPaymentController: Stripe initiatePayment failed', [
                    'error'             => $paymentIntent->errorMessage,
                    'charging_point_id' => $id,
                    'user_id'           => $user->id,
                ]);
                return response()->json([
                    'success' => false,
                    'error'   => $paymentIntent->errorMessage ?? 'Échec de l\'initialisation du paiement.',
                ], 400);
            }

            $transaction->update([
                'payment_reference' => $paymentIntent->id,
                'stripe_session_id' => $paymentIntent->id,
                'gateway_response'  => [
                    'payment_intent_id' => $paymentIntent->id,
                    'status'            => $paymentIntent->status,
                    'created_at'        => now()->toIso8601String(),
                ],
            ]);

            DB::commit();

            Log::info('ClientChargingPaymentController: PaymentIntent created', [
                'reservation_id'    => $reservation->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount'            => $cost,
                'currency'          => $currency,
                'user_id'           => $user->id,
            ]);

            return response()->json([
                'success'           => true,
                'client_secret'     => $paymentIntent->clientSecret,
                'payment_intent_id' => $paymentIntent->id,
                'reservation_id'    => $reservation->id,
                'transaction_id'    => $transaction->id,
                'amount'            => $cost,
                'currency'          => $currency,
                'confirm_url'       => route('client.charging.confirm', [
                    'id'            => $id,
                    'reservationId' => $reservation->id,
                ]),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ClientChargingPaymentController: initiatePayment exception', [
                'error'             => $e->getMessage(),
                'charging_point_id' => $id,
                'user_id'           => auth()->id(),
            ]);
            return response()->json([
                'success' => false,
                'error'   => 'Une erreur est survenue. Veuillez réessayer.',
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // STEP 3 – Verify payment and dispatch charging job
    // -------------------------------------------------------------------------

    public function confirmPayment(Request $request, int $id, int $reservationId)
    {
        $reservation = Reservation::with(['chargingPoint'])->findOrFail($reservationId);
        $this->authorizeReservationAccess($reservation);

        $transaction = Transaction::where('reservation_id', $reservation->id)
            ->latest()
            ->first();

        $paymentIntentId = $request->query('payment_intent')
            ?? $transaction?->payment_reference
            ?? $transaction?->stripe_session_id;

        if (!$paymentIntentId) {
            return redirect()->route('client.charging.offer', $id)
                ->with('error', 'Impossible de vérifier le paiement. Veuillez réessayer.');
        }

        try {
            $gateway = new StripeGateway($reservation->chargingPoint);
            $status  = $gateway->getPaymentStatus($paymentIntentId);

            if ($status === 'succeeded') {
                $transaction?->update([
                    'status'           => 'completed',
                    'completed_at'     => now(),
                    'gateway_response' => array_merge(
                        $transaction->gateway_response ?? [],
                        ['confirmed_status' => $status, 'confirmed_at' => now()->toIso8601String()]
                    ),
                ]);

                $approval = $this->approvalService->applyPaymentSuccess(
                    $reservation,
                    'stripe',
                    'client_payment_confirm'
                );

                $reservation = $approval['reservation'];

                $reservation->update([
                    'payment_confirmed_at'           => $reservation->payment_confirmed_at ?? now(),
                    'payment_gateway_transaction_id' => $paymentIntentId,
                ]);

                // Idempotency: only dispatch the start job if there is not already a
                // pending/active session for this reservation. Refreshing the confirm
                // URL must not enqueue a second RemoteStart.
                $hasActiveSession = ChargingSession::where('reservation_id', $reservation->id)
                    ->whereIn('status', [
                        ChargingSession::STATUS_PENDING,
                        ChargingSession::STATUS_INITIATING,
                        ChargingSession::STATUS_ACTIVE,
                        ChargingSession::STATUS_IN_PROGRESS,
                    ])
                    ->exists();

                if (!$hasActiveSession) {
                    StartChargingSessionJob::dispatch($reservation->id);
                    Log::info('ClientChargingPaymentController: payment confirmed, charging job dispatched', [
                        'reservation_id'    => $reservation->id,
                        'payment_intent_id' => $paymentIntentId,
                        'user_id'           => auth()->id(),
                    ]);
                } else {
                    Log::info('ClientChargingPaymentController: payment re-confirmed, session already in progress', [
                        'reservation_id' => $reservation->id,
                        'user_id'        => auth()->id(),
                    ]);
                }

                return redirect()->route('client.charging.success', [
                    'id'            => $id,
                    'reservationId' => $reservation->id,
                ]);
            }

            if (in_array($status, ['processing', 'requires_action'], true)) {
                return redirect()->route('client.charging.success', [
                    'id'            => $id,
                    'reservationId' => $reservation->id,
                ])->with('info', 'Votre paiement est en cours de traitement. La recharge démarrera dans quelques instants.');
            }

            $reservation->update(['status' => 'canceled', 'payment_status' => 'FAILED']);
            $transaction?->update(['status' => 'failed', 'failed_at' => now()]);

            return redirect()->route('client.charging.offer', $id)
                ->with('error', 'Le paiement n\'a pas abouti. Veuillez réessayer.');

        } catch (\Exception $e) {
            Log::error('ClientChargingPaymentController: confirmPayment exception', [
                'error'          => $e->getMessage(),
                'reservation_id' => $reservationId,
            ]);
            return redirect()->route('client.charging.offer', $id)
                ->with('error', 'Une erreur est survenue lors de la vérification du paiement.');
        }
    }

    // -------------------------------------------------------------------------
    // STEP 4 – Success page
    // -------------------------------------------------------------------------

    public function success(int $id, int $reservationId)
    {
        $reservation   = Reservation::with(['chargingPoint'])->findOrFail($reservationId);
        $this->authorizeReservationAccess($reservation);

        $chargingPoint = $reservation->chargingPoint;

        return view('client.charging.success', [
            'reservation'   => $reservation,
            'chargingPoint' => $chargingPoint,
            'client'        => auth()->user(),
        ]);
    }

    // -------------------------------------------------------------------------
    // STEP 5 – Charging session status (JSON polling endpoint)
    // -------------------------------------------------------------------------

    public function chargingStatus(int $id, int $reservationId): JsonResponse
    {
        $reservation = Reservation::with(['chargingPoint'])->findOrFail($reservationId);
        $this->authorizeReservationAccess($reservation);

        $statusVal = is_object($reservation->status)
            ? $reservation->status->value
            : (string) ($reservation->status ?? '');

        $session = ChargingSession::where('reservation_id', $reservation->id)
            ->latest('id')
            ->first();

        $remoteStartStatus = $reservation->session_initiation_status;

        $state = match (true) {
            $session && in_array($session->status, ['active', 'in_progress'], true)
                => 'charging',
            $statusVal === 'active'
                => 'charging',
            in_array($remoteStartStatus, ['processing', 'queued', 'scheduled'], true)
                => 'starting',
            $remoteStartStatus === 'success'
                => 'charging',
            $remoteStartStatus === 'failed'
                => 'failed',
            $reservation->payment_status === 'PAID'
                => 'paid',
            default => 'pending',
        };

        return response()->json([
            'state'                     => $state,
            'reservation_status'        => $statusVal,
            'payment_status'            => $reservation->payment_status,
            'session_initiation_status' => $remoteStartStatus,
            'session'                   => $session ? [
                'id'                   => $session->id,
                'status'               => $session->status,
                'started_at'           => $session->started_at?->toIso8601String(),
                'steve_transaction_id' => $session->steve_transaction_id,
                'meter_start'          => $session->meter_start,
            ] : null,
            'last_error'                => $reservation->last_error,
            'charging_point'            => [
                'name' => $reservation->chargingPoint?->name,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Reject access if the authenticated user does not own the reservation.
     *
     * Only the user who created the reservation may confirm payment, view the
     * success page, or poll status. This is the primary defense against IDOR
     * on the {reservationId} route parameter.
     */
    private function authorizeReservationAccess(Reservation $reservation): void
    {
        $userId = auth()->id();

        // Reservations created via this flow always carry user_id. Legacy guest
        // reservations do not — they are not reachable through these routes
        // because the routes themselves require auth.
        if (!$reservation->user_id || $reservation->user_id !== $userId) {
            throw new AccessDeniedHttpException('You do not have access to this reservation.');
        }
    }

    private function calculateCost(string $type, $value, PricingPlan $plan): float
    {
        $cost = (float) ($plan->activation_fee ?? 0) + (float) ($plan->base_rate ?? 0);

        if ($type === 'energy' && $plan->price_per_kwh) {
            $cost += (float) $value * (float) $plan->price_per_kwh;
        } elseif ($type === 'duration' && $plan->price_per_minute) {
            $cost += (float) $value * (float) $plan->price_per_minute;
        }

        $vatRate = 0;
        if ($plan->vatRate && $plan->vatRate->rate > 0) {
            $vatRate = $plan->vatRate->rate;
        } elseif (!empty($plan->vat_rate)) {
            $vatRate = $plan->vat_rate;
        }

        if ($vatRate > 0) {
            $cost *= 1 + ($vatRate / 100);
        }

        return round($cost, 2);
    }
}
