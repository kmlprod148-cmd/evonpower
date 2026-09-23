<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ChargingPoint;
use App\Models\ClientUser;
use App\Models\Transaction;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Partner;
use App\Services\PaymentGatewayService;
use App\Services\Gateways\Contracts\PaymentIntent;
use App\Jobs\StartChargingJob;
use App\Services\PricingService;
use App\Services\GuestPostpaidService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Public Checkout Controller
 * 
 * Handles the 3-step QR checkout flow for guests and authenticated users:
 * - Step 1: Choose duration
 * - Step 2: Enter personal info (optional account creation)
 * - Step 3: Payment
 * 
 * Routes (no auth required, rate limited):
 * GET  /pay/{slug}              → show    (step 1: choose duration)
 * POST /pay/{slug}/calculate    → calculate (AJAX: calculate price)
 * POST /pay/{slug}/session      → createSession (step 2: personal info + create guest)
 * POST /pay/{slug}/pay          → pay (step 3: initiate payment)
 * GET  /pay/{slug}/confirm/{id} → confirm (payment return URL)
 * GET  /pay/{slug}/receipt/{id} → receipt (receipt page + email)
 */
class CheckoutController extends Controller
{
    protected PaymentGatewayService $paymentGatewayService;
    protected PricingService $pricingService;
    protected ?GuestPostpaidService $guestPostpaidService;

    /**
     * Duration options in minutes
     */
    protected array $durationOptions = [10, 20, 30, 40, 50, 60, 90, 120, 150];

    public function __construct(
        PaymentGatewayService $paymentGatewayService,
        PricingService $pricingService,
        ?GuestPostpaidService $guestPostpaidService = null
    ) {
        $this->paymentGatewayService = $paymentGatewayService;
        $this->pricingService = $pricingService;
        $this->guestPostpaidService = $guestPostpaidService;
    }

    /**
     * STEP 1: Show checkout page with duration selector
     * 
     * GET /pay/{slug}
     * 
     * @param string $slug
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $slug)
    {
        // Find charge point by slug (using name as slug or dedicated slug field)
        $chargePoint = ChargingPoint::where('name', 'like', '%' . $slug . '%')
            ->orWhere('qr_code', $slug)
            ->orWhere('external_id', $slug)
            ->first();

        if (!$chargePoint) {
            abort(404, __('Charge point not found'));
        }

        // Check if charge point is available
        if (!$chargePoint->is_active || $chargePoint->status === 'offline') {
            abort(403, __('This charge point is currently unavailable'));
        }

        // Get partner info
        $partner = $chargePoint->partner;
        
        // Get tariff plan info
        $tariffPlan = $chargePoint->tariffPlan ?? $chargePoint->pricingPlan;
        
        // Determine billing type (per minute or per kWh)
        $billingType = $tariffPlan?->price_per_minute > 0 ? 'per_minute' : 'per_kwh';

        // Get connector info (first connector)
        $connector = $chargePoint->connectors()->first();
        $maxPower = $connector?->max_power ?? 22; // Default 22kW

        return view('public.checkout.show', [
            'chargePoint' => $chargePoint,
            'partner' => $partner,
            'tariffPlan' => $tariffPlan,
            'billingType' => $billingType,
            'maxPower' => $maxPower,
            'connectorType' => $connector?->connector_type ?? 'Type 2',
            'durationOptions' => $this->durationOptions,
            'slug' => $slug,
        ]);
    }

    /**
     * Calculate price based on duration
     * 
     * POST /pay/{slug}/calculate
     * 
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    public function calculate(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'duration_minutes' => 'required|integer|min:1|max:300',
            'charge_point_id' => 'required|exists:charge_points,id',
        ]);

        $chargePoint = ChargingPoint::findOrFail($request->charge_point_id);
        $durationMinutes = $request->duration_minutes;

        // Get tariff plan
        $tariffPlan = $chargePoint->tariffPlan ?? $chargePoint->pricingPlan;
        
        if (!$tariffPlan) {
            return response()->json([
                'error' => 'No pricing plan configured for this charge point'
            ], 400);
        }

        // Calculate estimated consumption (assume average power)
        $connector = $chargePoint->connectors()->first();
        $avgPower = $connector?->max_power ?? 22; // kW
        $estimatedKwh = ($avgPower * $durationMinutes) / 60;

        // Calculate price
        $priceExclVat = 0;
        $currency = $tariffPlan->currency ?? 'MAD';

        if ($tariffPlan->price_per_minute > 0) {
            // Per minute billing
            $basePrice = $tariffPlan->price_per_minute * $durationMinutes;
            $activationFee = $tariffPlan->activation_fee ?? 0;
            $priceExclVat = $basePrice + $activationFee;
        } elseif ($tariffPlan->price_per_kwh > 0) {
            // Per kWh billing
            $basePrice = $tariffPlan->price_per_kwh * $estimatedKwh;
            $activationFee = $tariffPlan->activation_fee ?? 0;
            $priceExclVat = $basePrice + $activationFee;
        } else {
            // Flat rate
            $priceExclVat = $tariffPlan->base_rate ?? 0;
        }

        // Apply VAT
        $vatRate = $tariffPlan->vat_rate ?? 20;
        $vatAmount = round($priceExclVat * ($vatRate / 100), 2);
        $totalPrice = $priceExclVat + $vatAmount;

        // Use BCMath for precision
        $priceExclVat = round($priceExclVat, 2);
        $vatAmount = round($vatAmount, 2);
        $totalPrice = round($totalPrice, 2);

        return response()->json([
            'estimated_kwh' => round($estimatedKwh, 2),
            'price_excl_vat' => $priceExclVat,
            'vat_amount' => $vatAmount,
            'vat_rate' => $vatRate,
            'total_price' => $totalPrice,
            'currency' => $currency,
            'billing_type' => $tariffPlan->price_per_minute > 0 ? 'per_minute' : 'per_kwh',
            'duration_minutes' => $durationMinutes,
        ]);
    }

    /**
     * STEP 2: Create checkout session with personal info
     * 
     * POST /pay/{slug}/session
     * 
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    public function createSession(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'charge_point_id' => 'required|exists:charge_points,id',
            'duration_minutes' => 'required|integer|min:1|max:300',
            'estimated_kwh' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            // Personal info
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            // Payment mode (prepaid or postpaid)
            'payment_mode' => 'nullable|in:prepaid,postpaid',
            // GDPR Consent (Required)
            'gdpr_consent' => 'required|accepted',
            // Optional GDPR Consent
            'marketing_consent' => 'nullable|boolean',
            // Optional account creation
            'create_account' => 'boolean',
            'password' => 'nullable|string|min:8|required_if:create_account,1',
        ]);

        $chargePoint = ChargingPoint::findOrFail($request->charge_point_id);

        // Check if user already exists
        $user = User::where('email', $request->email)->first();

        if ($user && !$request->create_account) {
            // User exists but doesn't want to create account
            // We'll create a guest session linked to this email
            $isGuest = true;
        } elseif ($user && $request->create_account) {
            // User wants to create account but email already exists
            return response()->json([
                'error' => 'An account with this email already exists. Please login or use a different email.',
                'code' => 'email_exists'
            ], 422);
        } elseif (!$user && $request->create_account) {
            // Create new user account
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'is_active' => true,
            ]);
            $isGuest = false;
        } else {
            // Guest checkout (no account)
            $isGuest = true;
        }

        // Store GDPR consent data
        $gdprConsent = [
            'consented' => true,
            'consent_timestamp' => now()->toIso8601String(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'privacy_policy_version' => '1.0',
            'marketing_consent' => $request->boolean('marketing_consent'),
        ];

        // Determine payment mode (default to prepaid)
        $paymentMode = $request->input('payment_mode', 'prepaid');

        // Create checkout session record
        // We'll use the Reservation model for this or create a dedicated CheckoutSession model
        $sessionData = [
            'user_id' => $user?->id,
            'charge_point_id' => $chargePoint->id,
            'partner_id' => $chargePoint->partner_id,
            'integrator_id' => $chargePoint->integrator_id,
            'duration_minutes' => $request->duration_minutes,
            'estimated_kwh' => $request->estimated_kwh,
            'estimated_price' => $request->total_price,
            'currency' => $request->currency,
            'status' => 'pending',
            'payment_mode' => $paymentMode,
            'guest_info' => $isGuest ? [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
            ] : null,
            'is_guest' => $isGuest,
            'session_token' => Str::random(64),
        ];

        // Use Reservation model to store checkout session
        // Or create a dedicated CheckoutSession model
        $checkoutSession = Reservation::create([
            'user_id' => $user?->id,
            'charge_point_id' => $chargePoint->id,
            'partner_id' => $chargePoint->partner_id,
            'integrator_id' => $chargePoint->integrator_id,
            'start_time' => now(),
            'end_time' => now()->addMinutes($request->duration_minutes),
            'duration_minutes' => $request->duration_minutes,
            'estimated_energy' => $request->estimated_kwh,
            'estimated_cost' => $request->total_price,
            'amount' => $request->total_price,
            'status' => 'pending',
            'guest_info' => $sessionData['guest_info'],
            'is_guest' => $isGuest,
            'guest_email' => $request->email,
            'guest_phone' => $request->phone,
            'payment_type' => 'cmi',
            'metadata' => [
                'checkout_type' => 'public_qr',
                'estimated_price' => $request->total_price,
                'currency' => $request->currency,
                'session_token' => $sessionData['session_token'],
                'gdpr_consent' => $gdprConsent,
                'payment_mode' => $paymentMode,
                'created_at' => now()->toIso8601String(),
            ],
        ]);

        Log::info('Checkout session created', [
            'checkout_session_id' => $checkoutSession->id,
            'charge_point_id' => $chargePoint->id,
            'user_id' => $user?->id,
            'is_guest' => $isGuest,
            'duration_minutes' => $request->duration_minutes,
            'estimated_price' => $request->total_price,
            'gdpr_consent_given' => true,
            'marketing_consent_given' => $request->boolean('marketing_consent'),
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $checkoutSession->id,
            'session_token' => $sessionData['session_token'],
            'is_guest' => $isGuest,
            'user_id' => $user?->id,
            'payment_mode' => $paymentMode,
            'continue_to_payment' => true,
        ]);
    }

    /**
     * STEP 3: Initiate payment
     * 
     * POST /pay/{slug}/pay
     * 
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    public function pay(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:reservations,id',
            'session_token' => 'required|string',
            // Payment method token (required for postpaid, optional for prepaid)
            'payment_method_id' => 'nullable|string',
        ]);

        $checkoutSession = Reservation::where('id', $request->session_id)
            ->where('metadata->session_token', $request->session_token)
            ->first();

        if (!$checkoutSession) {
            return response()->json([
                'error' => 'Invalid checkout session'
            ], 404);
        }

        if ($checkoutSession->status !== 'pending') {
            return response()->json([
                'error' => 'Checkout session is not pending'
            ], 400);
        }

        // Get payment mode from metadata
        $paymentMode = $checkoutSession->metadata['payment_mode'] ?? 'prepaid';

        $chargePoint = $checkoutSession->chargePoint;
        $amount = $checkoutSession->metadata['estimated_price'] ?? $checkoutSession->estimated_energy * 10;
        $currency = $checkoutSession->metadata['currency'] ?? 'MAD';

        // Resolve payment gateway
        $gateway = $this->paymentGatewayService->resolveGateway($chargePoint);
        $gatewayType = $gateway->getGatewayType();

        // Prepare metadata
        $metadata = [
            'charge_point_id' => $chargePoint->id,
            'reservation_id' => $checkoutSession->id,
            'user_id' => $checkoutSession->user_id,
            'partner_id' => $chargePoint->partner_id,
            'integrator_id' => $chargePoint->integrator_id,
            'duration_minutes' => $checkoutSession->duration_minutes,
            'estimated_kwh' => $checkoutSession->estimated_energy,
            'order_id' => 'EVON-' . $checkoutSession->id . '-' . Str::uuid()->toString(),
            'ok_url' => route('public.checkout.confirm', ['slug' => $slug, 'id' => $checkoutSession->id]),
            'fail_url' => route('public.checkout.cancel', ['slug' => $slug, 'id' => $checkoutSession->id]),
            'customer_email' => $checkoutSession->guest_info['email'] ?? ($checkoutSession->user?->email),
            'customer_phone' => $checkoutSession->guest_info['phone'] ?? ($checkoutSession->user?->phone),
        ];

        // Handle payment based on mode
        if ($paymentMode === 'postpaid') {
            // Postpaid mode: Create authorization hold
            return $this->handlePostpaidPayment($checkoutSession, $request, $amount, $currency, $metadata);
        }

        // Prepaid mode: Initiate immediate payment
        // Initiate payment
        $paymentIntent = $gateway->initiatePayment($amount, $currency, $metadata);

        if ($paymentIntent->isFailed()) {
            $checkoutSession->update([
                'status' => 'payment_failed',
                'metadata' => array_merge($checkoutSession->metadata ?? [], [
                    'payment_error' => $paymentIntent->errorMessage,
                ]),
            ]);

            return response()->json([
                'success' => false,
                'error' => $paymentIntent->errorMessage ?? 'Payment initiation failed',
            ], 400);
        }

        // Create transaction record
        $transaction = Transaction::create([
            'user_id' => $checkoutSession->user_id,
            'charge_point_id' => $chargePoint->id,
            'partner_id' => $chargePoint->partner_id,
            'amount' => $amount,
            'currency' => $currency,
            'type' => 'prepaid',
            'status' => 'pending',
            'gateway_type' => $gatewayType,
            'gateway_transaction_id' => $paymentIntent->id,
            'payment_data' => [
                'client_secret' => $paymentIntent->clientSecret,
                'redirect_url' => $paymentIntent->redirectUrl,
                'metadata' => $metadata,
            ],
        ]);

        // Update checkout session with transaction
        $checkoutSession->update([
            'status' => 'payment_initiated',
            'transaction_id' => $transaction->id,
            'metadata' => array_merge($checkoutSession->metadata ?? [], [
                'transaction_id' => $transaction->id,
                'gateway_type' => $gatewayType,
                'payment_intent_id' => $paymentIntent->id,
            ]),
        ]);

        Log::info('Payment initiated', [
            'checkout_session_id' => $checkoutSession->id,
            'transaction_id' => $transaction->id,
            'gateway_type' => $gatewayType,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        // Return response based on gateway type
        if ($gatewayType === 'stripe' && $paymentIntent->clientSecret) {
            return response()->json([
                'success' => true,
                'payment_type' => 'stripe',
                'client_secret' => $paymentIntent->clientSecret,
                'transaction_id' => $transaction->id,
                'checkout_session_id' => $checkoutSession->id,
            ]);
        } else {
            // For CMI or other redirect-based gateways
            return response()->json([
                'success' => true,
                'payment_type' => 'redirect',
                'redirect_url' => $paymentIntent->redirectUrl,
                'payment_data' => $paymentIntent->metadata['cmi_request_data'] ?? null,
                'transaction_id' => $transaction->id,
                'checkout_session_id' => $checkoutSession->id,
            ]);
        }
    }

    /**
     * Payment confirmation/return handler
     * 
     * GET /pay/{slug}/confirm/{id}
     * 
     * @param string $slug
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function confirm(string $slug, int $id)
    {
        $checkoutSession = Reservation::findOrFail($id);
        
        // Verify token if provided
        $token = request('token');
        if ($token && ($checkoutSession->metadata['session_token'] ?? '') !== $token) {
            abort(403, 'Invalid session token');
        }

        // Get transaction
        $transaction = Transaction::find($checkoutSession->transaction_id);

        // Check payment status
        if ($transaction && $transaction->gateway_type) {
            $gateway = $this->paymentGatewayService->resolveGateway($checkoutSession->chargePoint);
            $paymentStatus = $gateway->getPaymentStatus($transaction->gateway_transaction_id);

            if ($paymentStatus === 'succeeded') {
                // Update transaction status
                $transaction->update([
                    'status' => 'completed',
                ]);

                // Update checkout session
                $checkoutSession->update([
                    'status' => 'confirmed',
                    'metadata' => array_merge($checkoutSession->metadata ?? [], [
                        'payment_confirmed_at' => now()->toIso8601String(),
                    ]),
                ]);

                // Dispatch job to start charging
                StartChargingJob::dispatch($checkoutSession);

                Log::info('Payment confirmed, charging started', [
                    'checkout_session_id' => $checkoutSession->id,
                    'transaction_id' => $transaction->id,
                ]);

                return redirect()->route('public.checkout.receipt', [
                    'slug' => $slug,
                    'id' => $checkoutSession->id,
                ]);
            }
        }

        // Payment not confirmed yet
        return view('public.checkout.payment-pending', [
            'checkoutSession' => $checkoutSession,
            'transaction' => $transaction,
            'slug' => $slug,
        ]);
    }

    /**
     * Payment receipt page
     * 
     * GET /pay/{slug}/receipt/{id}
     * 
     * @param string $slug
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function receipt(string $slug, int $id)
    {
        $checkoutSession = Reservation::with(['chargePoint', 'chargePoint.partner', 'user'])
            ->findOrFail($id);

        $transaction = Transaction::find($checkoutSession->transaction_id);

        // Send receipt email if not already sent
        if (!$checkoutSession->metadata['receipt_sent'] ?? false) {
            // TODO: Dispatch email job
            $checkoutSession->update([
                'metadata' => array_merge($checkoutSession->metadata ?? [], [
                    'receipt_sent' => true,
                    'receipt_sent_at' => now()->toIso8601String(),
                ]),
            ]);
        }

        return view('public.checkout.receipt', [
            'checkoutSession' => $checkoutSession,
            'transaction' => $transaction,
            'slug' => $slug,
        ]);
    }

    /**
     * Payment cancel handler
     * 
     * GET /pay/{slug}/cancel/{id}
     * 
     * @param string $slug
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(string $slug, int $id)
    {
        $checkoutSession = Reservation::findOrFail($id);

        $checkoutSession->update([
            'status' => 'cancelled',
            'metadata' => array_merge($checkoutSession->metadata ?? [], [
                'cancelled_at' => now()->toIso8601String(),
            ]),
        ]);

        return redirect()->route('public.checkout.show', ['slug' => $slug])
            ->with('error', 'Payment was cancelled. Please try again.');
    }

    /**
     * Handle CMI callback (webhook)
     * 
     * POST /pay/{slug}/callback
     * 
     * @param Request $request
     * @param string $slug
     * @return \Illuminate\Http\Response
     */
    public function handleCallback(Request $request, string $slug)
    {
        $responseCode = $request->input('Response');
        $orderId = $request->input('oid');

        Log::info('CMI Callback received', [
            'order_id' => $orderId,
            'response_code' => $responseCode,
            'slug' => $slug,
        ]);

        // Find the checkout session
        $checkoutSession = Reservation::where('metadata->order_id', $orderId)
            ->orWhere('id', explode('-', $orderId)[1] ?? 0)
            ->first();

        if (!$checkoutSession) {
            Log::error('CMI Callback: Checkout session not found', [
                'order_id' => $orderId,
            ]);
            return response('OK', 200);
        }

        // Get gateway to process callback
        $gateway = $this->paymentGatewayService->resolveGateway($checkoutSession->chargePoint);
        
        if (method_exists($gateway, 'processCallback')) {
            $result = $gateway->processCallback($request->all());

            if ($result->success) {
                // Update transaction
                $transaction = Transaction::find($checkoutSession->transaction_id);
                if ($transaction) {
                    $transaction->update([
                        'status' => 'completed',
                        'payment_data' => array_merge($transaction->payment_data ?? [], [
                            'callback_response' => $request->all(),
                        ]),
                    ]);
                }

                // Update checkout session
                $checkoutSession->update([
                    'status' => 'confirmed',
                    'metadata' => array_merge($checkoutSession->metadata ?? [], [
                        'payment_confirmed_at' => now()->toIso8601String(),
                        'callback_response' => $request->all(),
                    ]),
                ]);

                // Start charging
                StartChargingJob::dispatch($checkoutSession);
            } else {
                $checkoutSession->update([
                    'status' => 'payment_failed',
                    'metadata' => array_merge($checkoutSession->metadata ?? [], [
                        'payment_error' => $result->errorMessage,
                    ]),
                ]);
            }
        }

        // Redirect to confirmation page
        return redirect()->route('public.checkout.confirm', [
            'slug' => $slug,
            'id' => $checkoutSession->id,
        ]);
    }

    /**
     * STEP 2: Show user info / account choice page
     *
     * GET /pay/{slug}/info
     * GET /pay/{slug}/info?action=login   → store session + redirect to /login
     * GET /pay/{slug}/info?action=register → store session + redirect to /register
     *
     * @param Request $request
     * @param string $slug
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function userInfo(Request $request, string $slug)
    {
        // Prefer URL data, fall back to session for post-login/register returns
        $rawData = $request->query('data') ?? session('checkout.data');

        // Handle pre-auth redirect actions (store data in session first)
        $action = $request->query('action');
        if ($action === 'login' || $action === 'register') {
            if ($rawData) {
                session([
                    'checkout.data'       => $rawData,
                    'checkout.return_url' => route('public.checkout.user-info', ['slug' => $slug]),
                    'checkout.slug'       => $slug,
                ]);
            }
            return $action === 'login'
                ? redirect()->route('login')
                : redirect()->route('register');
        }

        // Decode checkout data
        $checkoutData = [];
        if ($rawData) {
            try {
                $decoded = json_decode(base64_decode($rawData), true);
                if (is_array($decoded)) {
                    $checkoutData = $decoded;
                    // Persist to session so login/register redirects can survive without the URL param
                    if ($request->query('data')) {
                        session(['checkout.data' => $rawData]);
                    }
                }
            } catch (\Exception $e) {
                // fall through
            }
        }

        if (empty($checkoutData)) {
            return redirect()->route('public.checkout.show', ['slug' => $slug])
                ->with('error', __('Session expired. Please start again.'));
        }

        // Enrich charge point name for display
        if (empty($checkoutData['charge_point_name']) && !empty($checkoutData['charge_point_id'])) {
            $cp = ChargingPoint::find($checkoutData['charge_point_id']);
            if ($cp) {
                $checkoutData['charge_point_name'] = $cp->name;
            }
        }

        // If a ClientUser is authenticated (returned from register/login), skip the choice page
        /** @var ClientUser|null $clientUser */
        $clientUser = auth('client')->user();
        if ($clientUser) {
            session()->forget(['checkout.data', 'checkout.return_url', 'checkout.slug']);
            return $this->autoInitiatePayment($request, $slug, $checkoutData, $clientUser);
        }

        return view('public.checkout.user-info', [
            'checkoutData' => $checkoutData,
            'rawData'      => $rawData,
            'slug'         => $slug,
        ]);
    }

    /**
     * Auto-create reservation + initiate payment for an authenticated ClientUser.
     * Serves payment.blade.php directly so the user skips the info form entirely.
     *
     * @param Request    $request
     * @param string     $slug
     * @param array      $checkoutData
     * @param ClientUser $clientUser
     */
    protected function autoInitiatePayment(
        Request $request,
        string $slug,
        array $checkoutData,
        ClientUser $clientUser
    ) {
        $chargePoint = ChargingPoint::find($checkoutData['charge_point_id'] ?? null);
        if (!$chargePoint) {
            return redirect()->route('public.checkout.show', ['slug' => $slug])
                ->with('error', __('Charge point not found. Please start again.'));
        }

        $totalPrice      = (float)  ($checkoutData['total_price']     ?? 0);
        $currency        = (string) ($checkoutData['currency']         ?? 'MAD');
        $durationMinutes = (int)    ($checkoutData['duration_minutes'] ?? 30);
        $estimatedKwh    = (float)  ($checkoutData['estimated_kwh']    ?? 0);
        $sessionToken    = Str::random(64);

        // Build guest_info from the ClientUser profile
        $nameParts = explode(' ', trim($clientUser->name ?? ''), 2);
        $guestInfo = [
            'first_name' => $clientUser->first_name ?? ($nameParts[0] ?? ''),
            'last_name'  => $nameParts[1]            ?? ($clientUser->name ?? ''),
            'email'      => $clientUser->email,
            'phone'      => $clientUser->phone ?? null,
            'address'    => $clientUser->address ?? null,
            'city'       => $clientUser->city ?? null,
        ];

        // Create the Reservation (is_guest=true, user_id=null — ClientUser ≠ User model)
        $reservation = Reservation::create([
            'user_id'           => null,
            'charging_point_id' => $chargePoint->id,
            'partner_id'        => $chargePoint->partner_id,
            'integrator_id'     => $chargePoint->integrator_id,
            'start_time'        => now(),
            'end_time'          => now()->addMinutes($durationMinutes),
            'duration_minutes'  => $durationMinutes,
            'estimated_energy'  => $estimatedKwh,
            'estimated_cost'    => $totalPrice,
            'amount'            => $totalPrice,
            'status'            => 'pending',
            'guest_info'        => $guestInfo,
            'is_guest'          => true,
            'guest_email'       => $clientUser->email,
            'guest_phone'       => $clientUser->phone ?? null,
            'payment_type'      => 'stripe',
        ]);

        // Store metadata separately to bypass mass-assignment guard on that column
        $reservation->metadata = [
            'checkout_type'   => 'public_qr_client_auth',
            'estimated_price' => $totalPrice,
            'currency'        => $currency,
            'session_token'   => $sessionToken,
            'payment_mode'    => 'prepaid',
            'created_at'      => now()->toIso8601String(),
        ];
        $reservation->save();

        // Resolve gateway
        $gateway     = $this->paymentGatewayService->resolveGateway($chargePoint);
        $gatewayType = $gateway->getGatewayType();

        $metadata = [
            'charge_point_id'  => $chargePoint->id,
            'reservation_id'   => $reservation->id,
            'partner_id'       => $chargePoint->partner_id,
            'integrator_id'    => $chargePoint->integrator_id,
            'duration_minutes' => $durationMinutes,
            'estimated_kwh'    => $estimatedKwh,
            'order_id'         => 'EVON-' . $reservation->id . '-' . Str::uuid()->toString(),
            'ok_url'           => route('public.checkout.confirm', ['slug' => $slug, 'id' => $reservation->id]),
            'fail_url'         => route('public.checkout.cancel',  ['slug' => $slug, 'id' => $reservation->id]),
            'customer_email'   => $clientUser->email,
            'customer_phone'   => $clientUser->phone ?? null,
        ];

        $paymentIntent = $gateway->initiatePayment($totalPrice, $currency, $metadata);

        if ($paymentIntent->isFailed()) {
            Log::warning('autoInitiatePayment: gateway failed', [
                'error'          => $paymentIntent->errorMessage,
                'reservation_id' => $reservation->id,
            ]);
            $reservation->update(['status' => 'payment_failed']);
            return redirect()->route('public.checkout.show', ['slug' => $slug])
                ->with('error', $paymentIntent->errorMessage ?? __('Payment initiation failed. Please try again.'));
        }

        // Create Transaction record
        $transaction = Transaction::create([
            'user_id'                => null,
            'charge_point_id'        => $chargePoint->id,
            'partner_id'             => $chargePoint->partner_id,
            'amount'                 => $totalPrice,
            'currency'               => $currency,
            'type'                   => 'prepaid',
            'status'                 => 'pending',
            'gateway_type'           => $gatewayType,
            'gateway_transaction_id' => $paymentIntent->id,
            'payment_data'           => [
                'client_secret' => $paymentIntent->clientSecret,
                'redirect_url'  => $paymentIntent->redirectUrl,
                'metadata'      => $metadata,
            ],
        ]);

        $reservation->metadata = array_merge($reservation->metadata ?? [], [
            'transaction_id'    => $transaction->id,
            'gateway_type'      => $gatewayType,
            'payment_intent_id' => $paymentIntent->id,
        ]);
        $reservation->update([
            'status'         => 'payment_initiated',
            'transaction_id' => $transaction->id,
        ]);

        Log::info('autoInitiatePayment: payment initiated', [
            'client_user_id' => $clientUser->id,
            'reservation_id' => $reservation->id,
            'transaction_id' => $transaction->id,
            'gateway_type'   => $gatewayType,
        ]);

        // Get public key (may be integrator-specific)
        $stripePublicKey = (method_exists($gateway, 'getPublicKey'))
            ? $gateway->getPublicKey()
            : config('payments.stripe.public_key', '');

        // For CMI or other redirect-based gateways, just redirect
        if ($gatewayType !== 'stripe') {
            return redirect($paymentIntent->redirectUrl);
        }

        // Serve the payment form with the Stripe client_secret pre-loaded
        return view('public.checkout.payment', [
            'session'         => $reservation,
            'sessionToken'    => $sessionToken,
            'chargePoint'     => $chargePoint,
            'totalPrice'      => $totalPrice,
            'currency'        => $currency,
            'durationMinutes' => $durationMinutes,
            'estimatedKwh'    => $estimatedKwh,
            'guestInfo'       => $guestInfo,
            'gatewayType'     => 'stripe',
            'stripePublicKey' => $stripePublicKey,
            'clientSecret'    => $paymentIntent->clientSecret,
            'slug'            => $slug,
        ]);
    }

    /**
     * Display privacy policy page
     *
     * GET /privacy-policy
     *
     * @return \Illuminate\View\View
     */
    public function privacyPolicy()
    {
        return view('public.checkout.privacy-policy', [
            'companyName' => config('brand.display_name', 'EVON Power'),
            'companyEmail' => config('brand.contact_email', 'contact@evon.ma'),
            'lastUpdated' => now()->format('d/m/Y'),
        ]);
    }

    /**
     * Handle postpaid payment (authorization hold)
     * 
     * For postpaid mode, we create an authorization hold on the payment method
     * and will capture the actual amount when the charging session ends.
     *
     * @param Reservation $checkoutSession
     * @param Request $request
     * @param float $amount
     * @param string $currency
     * @param array $metadata
     * @return JsonResponse
     */
    protected function handlePostpaidPayment(
        Reservation $checkoutSession,
        Request $request,
        float $amount,
        string $currency,
        array $metadata
    ): JsonResponse {
        // Check if GuestPostpaidService is available
        if (!$this->guestPostpaidService) {
            return response()->json([
                'success' => false,
                'error' => 'Postpaid payment is not available at this time'
            ], 503);
        }

        // Get payment method ID from request
        $paymentMethodId = $request->input('payment_method_id');
        
        if (!$paymentMethodId) {
            return response()->json([
                'success' => false,
                'error' => 'Payment method is required for postpaid mode'
            ], 400);
        }

        try {
            // Create authorization hold
            $authResult = $this->guestPostpaidService->createAuthorization(
                $checkoutSession,
                $amount,
                $currency,
                $paymentMethodId,
                $metadata
            );

            if (!$authResult['success']) {
                $checkoutSession->update([
                    'status' => 'payment_failed',
                    'metadata' => array_merge($checkoutSession->metadata ?? [], [
                        'payment_error' => $authResult['error'] ?? 'Authorization failed',
                    ]),
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $authResult['error'] ?? 'Failed to create authorization'
                ], 400);
            }

            // Update checkout session with authorization details
            $checkoutSession->update([
                'status' => 'payment_authorized',
                'metadata' => array_merge($checkoutSession->metadata ?? [], [
                    'authorization_hold_id' => $authResult['authorization_id'],
                    'authorization_hold_amount' => $amount,
                    'authorization_created_at' => now()->toIso8601String(),
                    'guest_payment_method_id' => $authResult['guest_payment_method_id'],
                ]),
            ]);

            Log::info('Postpaid authorization created', [
                'checkout_session_id' => $checkoutSession->id,
                'authorization_id' => $authResult['authorization_id'],
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return response()->json([
                'success' => true,
                'payment_type' => 'postpaid',
                'authorization_id' => $authResult['authorization_id'],
                'authorization_amount' => $amount,
                'checkout_session_id' => $checkoutSession->id,
                'continue_to_charging' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Postpaid payment error', [
                'checkout_session_id' => $checkoutSession->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $checkoutSession->update([
                'status' => 'payment_failed',
                'metadata' => array_merge($checkoutSession->metadata ?? [], [
                    'payment_error' => $e->getMessage(),
                ]),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while processing your payment'
            ], 500);
        }
    }
}
