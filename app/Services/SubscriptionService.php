<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionService
{
    public function __construct(
        protected PaymentIntegrationService $paymentIntegrationService,
        protected ClientBalanceService $balanceService,
    ) {}

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Initiate subscription purchase.
     * Returns payment URL/data so the controller can redirect the user.
     */
    public function subscribe(
        User $user,
        SubscriptionPlan $plan,
        string $paymentMethod,
        array $options = []
    ): array {
        try {
            if (!$plan->is_active) {
                return ['success' => false, 'message' => 'Ce plan n\'est plus disponible.'];
            }

            if (!in_array($paymentMethod, ['stripe', 'cmi', 'wallet', 'offline'])) {
                return ['success' => false, 'message' => 'Méthode de paiement invalide.'];
            }

            if ($paymentMethod === 'wallet') {
                return $this->subscribeWithWallet($user, $plan, $options);
            }

            $subscription = $this->createPendingSubscription($user, $plan, $paymentMethod, $options);

            return match ($paymentMethod) {
                'stripe'  => $this->initiateStripePayment($subscription, $plan, $options),
                'cmi'     => $this->initiateCmiPayment($subscription, $plan, $options),
                'offline' => $this->initiateOfflinePayment($subscription, $options),
                default   => ['success' => false, 'message' => 'Méthode non supportée.'],
            };
        } catch (\Exception $e) {
            Log::error('SubscriptionService::subscribe error', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error'   => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Erreur lors de l\'abonnement: ' . $e->getMessage()];
        }
    }

    /**
     * Activate a pending subscription after successful payment.
     */
    public function activateSubscription(UserSubscription $subscription): UserSubscription
    {
        return DB::transaction(function () use ($subscription) {
            $plan           = $subscription->subscriptionPlan;
            $durationMonths = $plan->getDurationInMonths();

            $startDate = now()->toDateString();
            $endDate   = $durationMonths > 0
                ? now()->addMonths($durationMonths)->toDateString()
                : now()->addYears(10)->toDateString();

            $subscription->update([
                'status'       => SubscriptionStatus::ACTIVE->value,
                'start_date'   => $startDate,
                'end_date'     => $endDate,
                'renewal_date' => $subscription->auto_renew
                    ? now()->addMonths(max(1, $durationMonths))->toDateString()
                    : null,
            ]);

            Log::info('Subscription activated', [
                'subscription_id' => $subscription->id,
                'user_id'         => $subscription->user_id,
                'plan_id'         => $subscription->subscription_plan_id,
                'end_date'        => $endDate,
            ]);

            return $subscription->fresh('subscriptionPlan');
        });
    }

    /**
     * Handle Stripe webhook event (checkout.session.completed).
     */
    public function handleStripeWebhook(array $event): array
    {
        try {
            $type      = $event['type'] ?? null;
            $sessionId = $event['data']['object']['id'] ?? null;

            if ($type !== 'checkout.session.completed' || !$sessionId) {
                return ['success' => true, 'message' => 'Événement ignoré.'];
            }

            $stripeConfig = config('payments.stripe', []);
            if (empty($stripeConfig['secret_key'])) {
                throw new \Exception('Configuration Stripe incomplète: secret_key manquant.');
            }

            \Stripe\Stripe::setApiKey($stripeConfig['secret_key']);
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            $subscriptionId = $session->metadata['subscription_id'] ?? null;
            if (!$subscriptionId) {
                Log::warning('Stripe webhook (subscription): subscription_id absent des métadonnées', [
                    'session_id' => $sessionId,
                ]);
                return ['success' => false, 'message' => 'subscription_id manquant.'];
            }

            $subscription = UserSubscription::find($subscriptionId);
            if (!$subscription) {
                return ['success' => false, 'message' => "Abonnement #{$subscriptionId} introuvable."];
            }

            if ($subscription->status === SubscriptionStatus::ACTIVE->value) {
                return ['success' => true, 'message' => 'Abonnement déjà actif.'];
            }

            return DB::transaction(function () use ($subscription, $session) {
                $subscription->update([
                    'payment_reference' => $session->payment_intent ?? $session->id,
                    'metadata'          => array_merge($subscription->metadata ?? [], [
                        'stripe_session_id'     => $session->id,
                        'stripe_payment_intent' => $session->payment_intent ?? null,
                    ]),
                ]);

                $this->activateSubscription($subscription);

                return [
                    'success'         => true,
                    'message'         => 'Abonnement activé via Stripe.',
                    'subscription_id' => $subscription->id,
                ];
            });
        } catch (\Exception $e) {
            Log::error('SubscriptionService::handleStripeWebhook error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle CMI server-to-server callback for subscriptions.
     * Returns the string CMI expects: 'ACTION=POSTAUTH' or 'ACTION=FAILURE'
     */
    public function handleCmiCallback(array $postData, UserSubscription $subscription): string
    {
        try {
            $returnCode = $postData['ProcReturnCode'] ?? null;

            if ($returnCode !== '00') {
                Log::warning('CMI subscription callback: paiement non approuvé', [
                    'subscription_id' => $subscription->id,
                    'return_code'     => $returnCode,
                ]);
                $subscription->update(['status' => SubscriptionStatus::CANCELLED->value]);
                return 'ACTION=FAILURE';
            }

            if (!$this->verifyCmiSignature($postData)) {
                Log::error('CMI subscription callback: signature invalide', ['subscription_id' => $subscription->id]);
                return 'ACTION=FAILURE';
            }

            if ($subscription->status === SubscriptionStatus::ACTIVE->value) {
                return 'ACTION=POSTAUTH';
            }

            DB::transaction(function () use ($subscription, $postData) {
                $subscription->update([
                    'payment_reference' => $postData['TransId'] ?? null,
                    'metadata'          => array_merge($subscription->metadata ?? [], [
                        'cmi_trans_id'  => $postData['TransId'] ?? null,
                        'cmi_auth_code' => $postData['AuthCode'] ?? null,
                    ]),
                ]);
                $this->activateSubscription($subscription);
            });

            return 'ACTION=POSTAUTH';
        } catch (\Exception $e) {
            Log::error('SubscriptionService::handleCmiCallback error', [
                'subscription_id' => $subscription->id,
                'error'           => $e->getMessage(),
            ]);
            return 'ACTION=FAILURE';
        }
    }

    /**
     * Handle CMI return page (browser redirect from CMI after payment).
     */
    public function handleCmiReturn(array $postData, UserSubscription $subscription): array
    {
        $hashValid       = $this->verifyCmiSignature($postData);
        $paymentApproved = isset($postData['Response']) && $postData['Response'] === 'Approved';

        if ($hashValid && $paymentApproved && $subscription->status !== SubscriptionStatus::ACTIVE->value) {
            try {
                DB::transaction(function () use ($subscription, $postData) {
                    $subscription->update([
                        'payment_reference' => $postData['TransId'] ?? null,
                        'metadata'          => array_merge($subscription->metadata ?? [], [
                            'cmi_trans_id' => $postData['TransId'] ?? null,
                        ]),
                    ]);
                    $this->activateSubscription($subscription);
                });
            } catch (\Exception $e) {
                Log::error('CMI subscription return activation failed', ['error' => $e->getMessage()]);
            }
        } elseif (!$paymentApproved) {
            $subscription->update(['status' => SubscriptionStatus::CANCELLED->value]);
        }

        return [
            'hash_valid'       => $hashValid,
            'payment_approved' => $paymentApproved,
            'subscription'     => $subscription->fresh('subscriptionPlan'),
            'error_message'    => !$paymentApproved ? ($postData['ErrMsg'] ?? 'Paiement refusé') : null,
        ];
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(UserSubscription $subscription, string $reason = ''): array
    {
        if (!in_array($subscription->status, [
            SubscriptionStatus::ACTIVE->value,
            SubscriptionStatus::PENDING->value,
        ])) {
            return ['success' => false, 'message' => 'Cet abonnement ne peut pas être annulé.'];
        }

        $subscription->update([
            'status'   => SubscriptionStatus::CANCELLED->value,
            'metadata' => array_merge($subscription->metadata ?? [], [
                'cancelled_at'        => now()->toISOString(),
                'cancellation_reason' => $reason,
            ]),
        ]);

        Log::info('Subscription cancelled', ['subscription_id' => $subscription->id]);

        return ['success' => true, 'message' => 'Abonnement annulé avec succès.'];
    }

    /**
     * Renew an existing subscription (creates a fresh pending one).
     */
    public function renewSubscription(UserSubscription $subscription, string $paymentMethod, array $options = []): array
    {
        $plan = $subscription->subscriptionPlan;

        if (!$plan->allow_renewal) {
            return ['success' => false, 'message' => 'Ce plan ne supporte pas le renouvellement.'];
        }

        $options['renewed_from_id'] = $subscription->id;

        return $this->subscribe($subscription->user, $plan, $paymentMethod, $options);
    }

    /**
     * Mark all active subscriptions past their end_date as expired.
     */
    public function processExpiredSubscriptions(): int
    {
        $count = UserSubscription::where('status', SubscriptionStatus::ACTIVE->value)
            ->where('end_date', '<', now())
            ->update(['status' => SubscriptionStatus::EXPIRED->value]);

        Log::info("SubscriptionService: expired {$count} subscription(s).");

        return $count;
    }

    /**
     * Get the latest active subscription for a user.
     */
    public function getActiveSubscription(User $user): ?UserSubscription
    {
        return UserSubscription::forUser($user->id)
            ->active()
            ->with('subscriptionPlan')
            ->latest()
            ->first();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function createPendingSubscription(
        User $user,
        SubscriptionPlan $plan,
        string $paymentMethod,
        array $options = []
    ): UserSubscription {
        $vatAmount = round($plan->price * ($plan->vat_rate / 100), 2);

        return UserSubscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'start_date'           => now()->toDateString(),
            'end_date'             => now()->addMonth()->toDateString(),
            'status'               => SubscriptionStatus::PENDING->value,
            'amount_paid'          => $plan->price,
            'vat_amount'           => $vatAmount,
            'currency'             => 'EUR',
            'payment_method'       => $paymentMethod,
            'auto_renew'           => $options['auto_renew'] ?? false,
            'renewed_from_id'      => $options['renewed_from_id'] ?? null,
            'metadata'             => $options['metadata'] ?? [],
        ]);
    }

    private function subscribeWithWallet(User $user, SubscriptionPlan $plan, array $options = []): array
    {
        $wallet = $user->getOrCreateWallet();
        $total  = round($plan->price * (1 + $plan->vat_rate / 100), 2);

        if (!$wallet->hasSufficientBalance($total)) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Solde insuffisant. Disponible: %.2f EUR, Requis: %.2f EUR.',
                    $wallet->balance,
                    $total
                ),
            ];
        }

        return DB::transaction(function () use ($user, $plan, $wallet, $total, $options) {
            $subscription = $this->createPendingSubscription($user, $plan, 'wallet', $options);

            $wallet->debit(
                $total,
                "Abonnement {$plan->name} #{$subscription->id}",
                ['subscription_id' => $subscription->id, 'plan_id' => $plan->id],
                'EUR'
            );

            $subscription->update(['payment_reference' => 'wallet-' . Str::uuid()]);
            $this->activateSubscription($subscription);

            return [
                'success'         => true,
                'subscription_id' => $subscription->id,
                'message'         => 'Abonnement activé via votre solde.',
            ];
        });
    }

    private function initiateStripePayment(
        UserSubscription $subscription,
        SubscriptionPlan $plan,
        array $options = []
    ): array {
        $stripeConfig = config('payments.stripe', []);
        if (empty($stripeConfig['secret_key'])) {
            return ['success' => false, 'message' => 'Configuration Stripe incomplète.'];
        }

        \Stripe\Stripe::setApiKey($stripeConfig['secret_key']);

        $total = (int) round($plan->price * (1 + $plan->vat_rate / 100) * 100);

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => strtolower($stripeConfig['currency'] ?? 'eur'),
                    'product_data' => [
                        'name'        => "Abonnement {$plan->name}",
                        'description' => $plan->description ?? "Plan {$plan->type_label}",
                    ],
                    'unit_amount' => $total,
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => route('subscriptions.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('subscriptions.stripe.cancel'),
            'metadata'    => [
                'subscription_id' => $subscription->id,
                'plan_id'         => $plan->id,
                'user_id'         => $subscription->user_id,
            ],
        ]);

        $subscription->update([
            'metadata' => array_merge($subscription->metadata ?? [], [
                'stripe_session_id' => $session->id,
            ]),
        ]);

        return [
            'success'         => true,
            'subscription_id' => $subscription->id,
            'payment_method'  => 'stripe',
            'payment_url'     => $session->url,
        ];
    }

    private function initiateCmiPayment(
        UserSubscription $subscription,
        SubscriptionPlan $plan,
        array $options = []
    ): array {
        try {
            $cmiConfig = config('payments.cmi', []);
            $storeKey  = $cmiConfig['store_key'] ?? $cmiConfig['store_password'] ?? '';

            if (empty($storeKey)) {
                return ['success' => false, 'message' => 'Configuration CMI incomplète.'];
            }

            $total    = round($plan->price * (1 + $plan->vat_rate / 100), 2);
            $rnd      = Str::random(16);
            $clientId = $cmiConfig['client_id'] ?? '';
            $okUrl    = route('subscriptions.cmi.return', $subscription->id);
            $failUrl  = route('subscriptions.cmi.return', $subscription->id);

            $hashStr = $clientId . $subscription->id . number_format($total, 2, '.', '') .
                       $okUrl . $failUrl . $rnd . $storeKey;
            $hash    = base64_encode(pack('H*', sha1($hashStr)));

            $paymentData = [
                'clientid'    => $clientId,
                'storetype'   => '3d_pay_hosting',
                'amount'      => number_format($total, 2, '.', ''),
                'currency'    => '978',
                'okUrl'       => $okUrl,
                'failUrl'     => $failUrl,
                'oid'         => (string) $subscription->id,
                'lang'        => 'fr',
                'rnd'         => $rnd,
                'hash'        => $hash,
                'callbackUrl' => route('subscriptions.cmi.callback', $subscription->id),
            ];

            $paymentUrl = $cmiConfig['payment_url'] ?? 'https://payment.cmi.co.ma/fim/est3Dgate';

            $subscription->update([
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'cmi_payment_data' => $paymentData,
                    'cmi_payment_url'  => $paymentUrl,
                ]),
            ]);

            return [
                'success'         => true,
                'subscription_id' => $subscription->id,
                'payment_method'  => 'cmi',
                'payment_url'     => route('subscriptions.cmi.send', $subscription->id),
                'payment_data'    => $paymentData,
            ];
        } catch (\Exception $e) {
            Log::error('CMI subscription initiation failed', ['error' => $e->getMessage()]);
            $subscription->update(['status' => SubscriptionStatus::CANCELLED->value]);
            return ['success' => false, 'message' => 'Erreur CMI: ' . $e->getMessage()];
        }
    }

    private function initiateOfflinePayment(UserSubscription $subscription, array $options = []): array
    {
        return [
            'success'         => true,
            'subscription_id' => $subscription->id,
            'payment_method'  => 'offline',
            'message'         => 'Abonnement créé. En attente de confirmation par un administrateur.',
            'requires_admin'  => true,
        ];
    }

    private function verifyCmiSignature(array $data): bool
    {
        try {
            $cmiConfig = config('payments.cmi', []);
            $storeKey  = $cmiConfig['store_key'] ?? $cmiConfig['store_password'] ?? '';

            if (empty($storeKey) || empty($data['clientid']) || empty($data['oid']) ||
                empty($data['amount']) || empty($data['okUrl']) || empty($data['failUrl']) ||
                empty($data['rnd']) || empty($data['hash'])) {
                return false;
            }

            $hashStr  = $data['clientid'] . $data['oid'] . $data['amount'] .
                        $data['okUrl'] . $data['failUrl'] . $data['rnd'] . $storeKey;
            $expected = base64_encode(pack('H*', sha1($hashStr)));

            return hash_equals($expected, $data['hash']);
        } catch (\Exception $e) {
            Log::error('CMI signature verification error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
