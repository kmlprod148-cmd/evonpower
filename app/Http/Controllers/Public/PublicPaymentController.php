<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Services\PaymentService;
use App\Services\ReservationService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Public Payment Controller
 *
 * Handles the QR-code payment flow accessed by numeric charging point ID.
 * Routes: /payment/{chargingPointId}/...  (chargingPointId is numeric only)
 *
 * This controller complements CheckoutController (/pay/{slug}/...) and
 * handles the same payment lifecycle but keyed on the charging point's
 * numeric database ID instead of a slug/QR string.
 */
class PublicPaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected ReservationService $reservationService,
        protected PricingService $pricingService,
    ) {}

    /**
     * Step 1 — Show charging station info and payment options.
     * GET /payment/{chargingPointId}
     */
    public function showStationInfo(int $chargingPointId)
    {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

        if (!$chargingPoint->is_active) {
            abort(403, 'This charging point is currently unavailable.');
        }

        $pricingPlan = $chargingPoint->pricingPlan;

        return view('public.payment.station', compact('chargingPoint', 'pricingPlan'));
    }

    /**
     * AJAX — Calculate price estimate.
     * POST /payment/{chargingPointId}/calculate
     */
    public function calculateEstimate(Request $request, int $chargingPointId): JsonResponse
    {
        $validated = $request->validate([
            'reservation_type'  => 'required|in:kwh,minute',
            'reservation_value' => 'required|numeric|min:1',
        ]);

        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
        $pricingPlan   = $chargingPoint->pricingPlan;

        if (!$pricingPlan) {
            return response()->json(['error' => 'No pricing plan configured for this charging point.'], 400);
        }

        try {
            $amount = $this->pricingService->calculateAmount(
                $pricingPlan,
                $validated['reservation_type'],
                (float) $validated['reservation_value']
            );

            return response()->json([
                'success' => true,
                'amount'  => $amount,
                'currency' => $pricingPlan->currency ?? 'EUR',
            ]);
        } catch (\Exception $e) {
            Log::error('PublicPaymentController::calculateEstimate failed', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Unable to calculate price.'], 500);
        }
    }

    /**
     * Step 2a — Show customer information form.
     * GET /payment/{chargingPointId}/customer
     */
    public function showCustomerForm(int $chargingPointId)
    {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

        return view('public.payment.customer-form', compact('chargingPoint'));
    }

    /**
     * Step 2b — Store customer info in session and continue.
     * POST /payment/{chargingPointId}/customer
     */
    public function processCustomerForm(Request $request, int $chargingPointId)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $request->session()->put("public_payment.{$chargingPointId}.customer", $validated);

        return redirect()->route('public.payment.select-method', $chargingPointId);
    }

    /**
     * Step 3a — Show payment method selection.
     * GET /payment/{chargingPointId}/method
     */
    public function showPaymentMethod(int $chargingPointId)
    {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

        return view('public.payment.payment-method', compact('chargingPoint'));
    }

    /**
     * Step 3b — Initiate payment (CMI or Stripe) for the public flow.
     * POST /payment/{chargingPointId}/initiate
     */
    public function initiatePayment(Request $request, int $chargingPointId)
    {
        $validated = $request->validate([
            'payment_method'    => 'required|in:cmi,stripe',
            'reservation_type'  => 'required|in:kwh,minute',
            'reservation_value' => 'required|numeric|min:1',
            'start_time'        => 'required|date_format:H:i',
            'estimated_amount'  => 'required|numeric|min:0',
            'currency'          => 'required|string|size:3',
            'pricing_plan_id'   => 'required|exists:pricing_plans,id',
        ]);

        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            // Build the reservation payload expected by ReservationService
            $payload = array_merge($validated, [
                'charging_point_id' => $chargingPointId,
            ]);

            $reservationResult = $this->reservationService->createReservation($payload);

            if (empty($reservationResult['reservation'])) {
                return back()->withErrors(['error' => $reservationResult['message'] ?? 'Failed to create reservation.']);
            }

            $reservation = $reservationResult['reservation'];

            $result = $this->paymentService->processPayment(
                $reservation,
                $validated['payment_method'],
                $validated
            );

            if ($result['success'] && !empty($result['redirect_url'])) {
                return redirect($result['redirect_url']);
            }

            return back()->withErrors(['error' => $result['message'] ?? 'Payment initiation failed.']);

        } catch (\Exception $e) {
            Log::error('PublicPaymentController::initiatePayment failed', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred. Please try again.']);
        }
    }

    /**
     * Payment success page.
     * GET /payment/{chargingPointId}/success/{reservationId}
     */
    public function paymentSuccess(int $chargingPointId, int $reservationId)
    {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
        $reservation   = Reservation::findOrFail($reservationId);

        return view('public.payment.success', compact('chargingPoint', 'reservation'));
    }

    /**
     * Payment cancel page.
     * GET /payment/{chargingPointId}/cancel
     */
    public function paymentCancel(int $chargingPointId)
    {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

        return view('public.payment.cancel', compact('chargingPoint'));
    }

    /**
     * AJAX — Check payment status.
     * GET /payment/{chargingPointId}/status/{reservationId}
     */
    public function checkPaymentStatus(int $chargingPointId, int $reservationId): JsonResponse
    {
        $reservation = Reservation::findOrFail($reservationId);

        return response()->json([
            'status'         => $reservation->status,
            'payment_status' => $reservation->payment_status ?? null,
        ]);
    }

    /**
     * Start a postpaid charging session.
     * POST /payment/{chargingPointId}/postpaid/start
     */
    public function startPostpaidSession(Request $request, int $chargingPointId)
    {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

        // Delegate to the postpaid service if needed in the future.
        return response()->json([
            'success' => false,
            'message' => 'Postpaid sessions are not available for this charging point.',
        ], 422);
    }
}
