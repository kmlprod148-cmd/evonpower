<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\ClientBalanceService;
use App\Services\PaymentIntegrationService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected ClientBalanceService $balanceService,
        protected PaymentIntegrationService $paymentIntegrationService,
    ) {
        $this->middleware('auth:web,client');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Listing & browsing
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * My subscriptions page.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $subscriptions = UserSubscription::forUser($user->id)
            ->with('subscriptionPlan')
            ->orderByDesc('created_at')
            ->paginate(10);

        $activeSubscription = $this->subscriptionService->getActiveSubscription($user);
        $balance            = $this->balanceService->getFormatted($user);

        return view('subscriptions.index', compact('subscriptions', 'activeSubscription', 'balance'));
    }

    /**
     * Browse available plans.
     */
    public function plans()
    {
        $plans = SubscriptionPlan::active()->ordered()->get();

        return view('subscriptions.plans', compact('plans'));
    }

    /**
     * Show a single plan detail.
     */
    public function showPlan(SubscriptionPlan $plan)
    {
        abort_if(!$plan->is_active, 404);

        return view('subscriptions.plan-detail', compact('plan'));
    }

    /**
     * Show checkout form for a plan.
     */
    public function checkout(SubscriptionPlan $plan)
    {
        abort_if(!$plan->is_active, 404);

        $user           = Auth::user();
        $wallet         = $user->getOrCreateWallet();
        $total          = round($plan->price * (1 + $plan->vat_rate / 100), 2);
        $hasSufficientBalance = $wallet->hasSufficientBalance($total);

        $paymentMethods = $this->getAvailablePaymentMethods();

        return view('subscriptions.checkout', compact(
            'plan',
            'total',
            'hasSufficientBalance',
            'paymentMethods',
            'wallet',
        ));
    }

    /**
     * Show a subscription detail.
     */
    public function show(UserSubscription $subscription)
    {
        $user = Auth::user();

        if ($subscription->user_id !== $user->id && !$user->hasAnyRole(['admin', 'super_admin'])) {
            abort(403);
        }

        $subscription->load('subscriptionPlan', 'usageLogs');

        return view('subscriptions.show', compact('subscription'));
    }

    /**
     * Usage stats for a subscription.
     */
    public function usage(UserSubscription $subscription)
    {
        $user = Auth::user();

        if ($subscription->user_id !== $user->id && !$user->hasAnyRole(['admin', 'super_admin'])) {
            abort(403);
        }

        $usagePercentage = $subscription->getUsagePercentage();

        return view('subscriptions.usage', compact('subscription', 'usagePercentage'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Subscribe (initiate payment)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Initiate a subscription purchase.
     */
    public function subscribe(Request $request, SubscriptionPlan $plan)
    {
        abort_if(!$plan->is_active, 404);

        $availableMethods = $this->getAvailablePaymentMethods();
        $allowedKeys      = array_keys(array_filter($availableMethods, fn($m) => $m['enabled'] ?? false));
        $allowedKeys[]    = 'wallet';
        $allowedKeys[]    = 'offline';

        $validator = Validator::make($request->all(), [
            'payment_method' => ['required', 'in:' . implode(',', $allowedKeys)],
            'auto_renew'     => 'boolean',
        ], [
            'payment_method.required' => 'Veuillez sélectionner une méthode de paiement.',
            'payment_method.in'       => 'La méthode de paiement sélectionnée n\'est pas valide.',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $result = $this->subscriptionService->subscribe(
            Auth::user(),
            $plan,
            $request->payment_method,
            [
                'auto_renew' => $request->boolean('auto_renew', false),
                'metadata'   => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]
        );

        if (!$result['success']) {
            if ($request->expectsJson()) {
                return response()->json($result, 400);
            }
            return redirect()->back()->with('error', $result['message'])->withInput();
        }

        // Wallet payment → already activated, redirect to my subscriptions
        if ($request->payment_method === 'wallet') {
            return redirect()->route('subscriptions.show', $result['subscription_id'])
                ->with('success', $result['message']);
        }

        // Offline → show pending message
        if ($request->payment_method === 'offline') {
            return redirect()->route('subscriptions.show', $result['subscription_id'])
                ->with('info', $result['message']);
        }

        // CMI → redirect to auto-submit form page
        if ($request->payment_method === 'cmi' && isset($result['subscription_id'])) {
            return redirect()->route('subscriptions.cmi.send', $result['subscription_id']);
        }

        // Stripe → redirect directly to Stripe hosted checkout
        if (isset($result['payment_url'])) {
            return redirect($result['payment_url']);
        }

        return redirect()->route('subscriptions.index')->with('error', 'Erreur lors de l\'initialisation du paiement.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Cancel / Renew
    // ──────────────────────────────────────────────────────────────────────────

    public function cancel(Request $request, UserSubscription $subscription)
    {
        $user = Auth::user();

        if ($subscription->user_id !== $user->id && !$user->hasAnyRole(['admin', 'super_admin'])) {
            abort(403);
        }

        $result = $this->subscriptionService->cancelSubscription(
            $subscription,
            $request->input('reason', 'Annulé par l\'utilisateur')
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->route('subscriptions.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function renew(Request $request, UserSubscription $subscription)
    {
        $user = Auth::user();

        if ($subscription->user_id !== $user->id && !$user->hasAnyRole(['admin', 'super_admin'])) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:stripe,cmi,wallet,offline',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $result = $this->subscriptionService->renewSubscription(
            $subscription,
            $request->payment_method
        );

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        if ($request->payment_method === 'wallet') {
            return redirect()->route('subscriptions.show', $result['subscription_id'])
                ->with('success', $result['message']);
        }

        if ($request->payment_method === 'cmi' && isset($result['subscription_id'])) {
            return redirect()->route('subscriptions.cmi.send', $result['subscription_id']);
        }

        if (isset($result['payment_url'])) {
            return redirect($result['payment_url']);
        }

        return redirect()->route('subscriptions.index')
            ->with('info', $result['message'] ?? 'Renouvellement initié.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CMI payment flow
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Auto-submit CMI form page.
     */
    public function sendCmiPayment(UserSubscription $subscription)
    {
        $user = Auth::user();

        if ($subscription->user_id !== $user->id) {
            abort(403);
        }

        if (!in_array($subscription->status, ['pending', 'processing'])) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'Cet abonnement ne peut pas être payé.');
        }

        $paymentData = $subscription->metadata['cmi_payment_data'] ?? null;
        $paymentUrl  = $subscription->metadata['cmi_payment_url']
            ?? config('payments.cmi.payment_url', 'https://payment.cmi.co.ma/fim/est3Dgate');

        if (!$paymentData) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'Données de paiement CMI introuvables.');
        }

        return response()
            ->view('subscriptions.cmi.send-data', compact('subscription', 'paymentData', 'paymentUrl'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * CMI server-to-server callback (public route, no auth).
     */
    public function handleCmiCallback(Request $request, UserSubscription $subscription)
    {
        try {
            $postData = $request->all();

            Log::info('CMI subscription callback received', [
                'subscription_id' => $subscription->id,
                'post_data_keys'  => array_keys($postData),
            ]);

            $response = $this->subscriptionService->handleCmiCallback($postData, $subscription);

            return response($response, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        } catch (\Exception $e) {
            Log::error('CMI subscription callback exception', [
                'subscription_id' => $subscription->id,
                'error'           => $e->getMessage(),
            ]);
            return response('ACTION=FAILURE', 500)->header('Content-Type', 'text/plain; charset=UTF-8');
        }
    }

    /**
     * CMI return page (browser redirect after payment attempt).
     */
    public function handleCmiReturn(Request $request, UserSubscription $subscription)
    {
        try {
            $result = $this->subscriptionService->handleCmiReturn($request->all(), $subscription);

            return view('subscriptions.cmi.result', [
                'subscription' => $result['subscription'],
                'result'       => $result,
                'postData'     => $request->all(),
            ]);
        } catch (\Exception $e) {
            Log::error('CMI subscription return exception', [
                'subscription_id' => $subscription->id,
                'error'           => $e->getMessage(),
            ]);

            return view('subscriptions.cmi.result', [
                'subscription' => $subscription,
                'result'       => [
                    'hash_valid'       => false,
                    'payment_approved' => false,
                    'error_message'    => 'Erreur lors du traitement: ' . $e->getMessage(),
                ],
                'postData' => $request->all(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Stripe payment flow
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Stripe checkout success return (browser redirect).
     */
    public function stripeSuccess(Request $request)
    {
        $sessionId = $request->input('session_id');

        if ($sessionId) {
            $subscription = UserSubscription::whereJsonContains('metadata->stripe_session_id', $sessionId)
                ->first();

            if ($subscription && $subscription->status === 'active') {
                return redirect()->route('subscriptions.show', $subscription->id)
                    ->with('success', 'Votre abonnement est maintenant actif !');
            }
        }

        return redirect()->route('subscriptions.index')
            ->with('info', 'Paiement en cours de traitement. Votre abonnement sera activé dans quelques instants.');
    }

    /**
     * Stripe checkout cancel return (browser redirect).
     */
    public function stripeCancel()
    {
        return redirect()->route('subscriptions.plans')
            ->with('info', 'Paiement annulé. Vous pouvez réessayer à tout moment.');
    }

    /**
     * Stripe webhook (public route, no auth).
     */
    public function stripeWebhook(Request $request)
    {
        try {
            $stripeConfig   = config('payments.stripe', []);
            $endpointSecret = $stripeConfig['webhook_secret'] ?? null;
            $payload        = $request->getContent();

            if ($endpointSecret) {
                $sigHeader = $request->header('Stripe-Signature');

                if (!$sigHeader) {
                    return response()->json(['success' => false, 'message' => 'Signature manquante'], 400);
                }

                try {
                    $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
                } catch (\Stripe\Exception\SignatureVerificationException $e) {
                    Log::error('Stripe subscription webhook: signature invalide', ['error' => $e->getMessage()]);
                    return response()->json(['success' => false, 'message' => 'Signature invalide'], 400);
                }
            } else {
                Log::warning('Stripe subscription webhook: mode développement — signature non vérifiée');
                $event = json_decode($payload, true);
            }

            $result = $this->subscriptionService->handleStripeWebhook((array) $event);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Stripe subscription webhook exception', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    protected function getAvailablePaymentMethods(): array
    {
        return $this->paymentIntegrationService->getAvailablePaymentMethods();
    }
}
