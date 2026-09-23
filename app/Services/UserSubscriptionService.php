<?php

namespace App\Services;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Enums\SubscriptionStatus;
use App\Services\PaymentGatewayService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * User Subscription Service
 * 
 * Handles user-facing subscription operations:
 * - Subscribe to a plan
 * - Check subscription eligibility
 * - Deduct from subscription quota
 * - Auto-renew subscriptions
 * - Cancel subscriptions
 */
class UserSubscriptionService
{
    protected PaymentGatewayService $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Subscribe a user to a plan
     * 
     * @param User $user
     * @param SubscriptionPlan $plan
     * @param string $paymentMethod payment method ID or 'wallet'
     * @param bool $autoRenew
     * @return UserSubscription
     */
    public function subscribe(
        User $user, 
        SubscriptionPlan $plan, 
        string $paymentMethod,
        bool $autoRenew = false
    ): UserSubscription {
        return DB::transaction(function () use ($user, $plan, $paymentMethod, $autoRenew) {
            // Calculate prices with VAT
            $priceExclVat = (float) $plan->price;
            $vatRate = (float) ($plan->vat_rate ?? 20);
            $vatAmount = round($priceExclVat * ($vatRate / 100), 2);
            $totalPrice = $priceExclVat + $vatAmount;

            // Process payment
            $paymentReference = null;
            $transactionId = null;

            if ($paymentMethod === 'wallet') {
                // Deduct from wallet
                $wallet = $user->wallet;
                if (!$wallet || $wallet->balance < $totalPrice) {
                    throw new \Exception('Insufficient wallet balance');
                }

                $wallet->debit(
                    $totalPrice,
                    "Subscription: {$plan->name}",
                    [
                        'type' => 'subscription',
                        'subscription_plan_id' => $plan->id,
                    ]
                );

                $paymentReference = 'wallet_' . time();
                $transactionId = null;
            } else {
                // Process payment via gateway
                $gateway = $this->paymentGatewayService->createDefaultGateway();
                $paymentIntent = $gateway->initiatePayment($totalPrice, $plan->currency ?? 'MAD', [
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'type' => 'subscription',
                ]);

                if ($paymentIntent->isFailed()) {
                    throw new \Exception('Payment failed: ' . $paymentIntent->errorMessage);
                }

                // Confirm payment
                $result = $gateway->confirmPayment($paymentIntent->id);
                if (!$result->success) {
                    throw new \Exception('Payment confirmation failed: ' . $result->errorMessage);
                }

                $paymentReference = $paymentIntent->id;
            }

            // Determine quota based on plan type
            $quota = match ($plan->type) {
                'recharge_count' => $plan->max_sessions,
                'kwh' => $plan->max_kwh,
                'duration' => $plan->max_duration_minutes,
                default => null,
            };

            // Calculate end date
            $startDate = Carbon::now();
            $endDate = $startDate->copy()->addMonths($plan->duration_months);

            // Create subscription
            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => SubscriptionStatus::ACTIVE,
                'sessions_used' => 0,
                'kwh_used' => 0,
                'duration_minutes_used' => 0,
                'amount_paid' => $totalPrice,
                'vat_amount' => $vatAmount,
                'currency' => $plan->currency ?? 'MAD',
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'transaction_id' => $transactionId,
                'auto_renew' => $autoRenew,
                'renewal_date' => $endDate->copy()->subDays(3),
            ]);

            Log::info('User subscription created', [
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
                'amount_paid' => $totalPrice,
                'auto_renew' => $autoRenew,
            ]);

            // Send confirmation email
            // TODO: Mail::to($user)->send(new SubscriptionConfirmationMail($subscription));

            return $subscription;
        });
    }

    /**
     * Check if user can charge at a given charge point using their subscription
     * 
     * @param User $user
     * @param ChargePoint $chargePoint
     * @return array ['allowed' => bool, 'reason' => string|null, 'subscription' => UserSubscription|null]
     */
    public function canChargeWithSubscription(User $user, ChargePoint $chargePoint): array
    {
        // Find active subscription
        $subscription = $user->userSubscriptions()
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('end_date', '>=', Carbon::now())
            ->first();

        if (!$subscription) {
            return [
                'allowed' => false,
                'reason' => 'No active subscription',
                'subscription' => null,
            ];
        }

        // Check if subscription is exhausted
        if ($subscription->status === SubscriptionStatus::EXHAUSTED) {
            return [
                'allowed' => false,
                'reason' => 'Subscription quota exhausted',
                'subscription' => $subscription,
            ];
        }

        // Check remaining quota
        $remainingQuota = $this->getRemainingQuota($subscription);
        if ($remainingQuota <= 0) {
            return [
                'allowed' => false,
                'reason' => 'No remaining quota',
                'subscription' => $subscription,
            ];
        }

        // Check if charge point is in subscribed groups
        $plan = $subscription->subscriptionPlan;
        
        // Check if subscription has charging point access
        $hasAccess = $this->checkChargePointAccess($subscription, $chargePoint);
        
        if (!$hasAccess) {
            return [
                'allowed' => false,
                'reason' => 'Charge point not covered by subscription',
                'subscription' => $subscription,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'subscription' => $subscription,
        ];
    }

    /**
     * Check if charge point is covered by subscription
     * 
     * @param UserSubscription $subscription
     * @param ChargePoint $chargePoint
     * @return bool
     */
    protected function checkChargePointAccess(UserSubscription $subscription, ChargePoint $chargePoint): bool
    {
        $plan = $subscription->subscriptionPlan;
        
        // Check if subscription is valid for all charge points
        if (empty($plan->max_charging_points)) {
            return true; // Unlimited access
        }

        // Check if specific charge points are assigned
        $assignedChargingPoints = $plan->chargingPoints ?? [];
        if ($assignedChargingPoints->isNotEmpty()) {
            return $assignedChargingPoints->contains('id', $chargePoint->id);
        }

        // Check if groups are assigned
        $assignedGroups = $plan->groups ?? [];
        if ($assignedGroups->isNotEmpty()) {
            return $assignedGroups->contains('id', $chargePoint->group_id);
        }

        // Default: allow if no restrictions
        return true;
    }

    /**
     * Get remaining quota for a subscription
     * 
     * @param UserSubscription $subscription
     * @return float|int
     */
    public function getRemainingQuota(UserSubscription $subscription): float|int
    {
        $plan = $subscription->subscriptionPlan;

        return match ($plan->type) {
            'recharge_count' => ($plan->max_sessions ?? 0) - ($subscription->sessions_used ?? 0),
            'kwh' => ($plan->max_kwh ?? 0) - ($subscription->kwh_used ?? 0),
            'duration' => ($plan->max_duration_minutes ?? 0) - ($subscription->duration_minutes_used ?? 0),
            default => PHP_INT_MAX, // Unlimited
        };
    }

    /**
     * Deduct from subscription quota after session
     * 
     * @param UserSubscription $subscription
     * @param ChargingSession $session
     * @return void
     */
    public function deductFromSubscription(UserSubscription $subscription, ChargingSession $session): void
    {
        DB::transaction(function () use ($subscription, $session) {
            $plan = $subscription->subscriptionPlan;

            // Update usage based on plan type
            $updates = [];
            
            switch ($plan->type) {
                case 'recharge_count':
                    $updates['sessions_used'] = ($subscription->sessions_used ?? 0) + 1;
                    break;
                    
                case 'kwh':
                    $energyUsed = (float) ($session->energy_delivered ?? $session->estimated_energy ?? 0);
                    $updates['kwh_used'] = ($subscription->kwh_used ?? 0) + $energyUsed;
                    break;
                    
                case 'duration':
                    $durationUsed = $session->duration_minutes ?? 0;
                    $updates['duration_minutes_used'] = ($subscription->duration_minutes_used ?? 0) + $durationUsed;
                    break;
            }

            $subscription->update($updates);

            // Check if quota is exhausted
            $remainingQuota = $this->getRemainingQuota($subscription);
            
            if ($remainingQuota <= 0) {
                $subscription->update([
                    'status' => SubscriptionStatus::EXHAUSTED,
                ]);

                Log::info('Subscription exhausted', [
                    'subscription_id' => $subscription->id,
                    'remaining_quota' => $remainingQuota,
                ]);

                // TODO: Send notification to user
            }

            Log::info('Subscription quota deducted', [
                'subscription_id' => $subscription->id,
                'session_id' => $session->id,
                'usage_type' => $plan->type,
            ]);
        });
    }

    /**
     * Auto-renew subscription
     * 
     * @param UserSubscription $subscription
     * @return bool
     */
    public function renewSubscription(UserSubscription $subscription): bool
    {
        if (!$subscription->auto_renew) {
            return false;
        }

        $user = $subscription->user;
        $plan = $subscription->subscriptionPlan;

        try {
            // Process renewal payment
            $this->subscribe($user, $plan, $subscription->payment_method, true);

            Log::info('Subscription auto-renewed', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Subscription auto-renewal failed', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Mark as payment failed
            $subscription->update([
                'status' => SubscriptionStatus::PAYMENT_FAILED,
            ]);

            // TODO: Send notification to user about failed renewal

            return false;
        }
    }

    /**
     * Cancel subscription
     * 
     * @param UserSubscription $subscription
     * @param string $reason
     * @return void
     */
    public function cancelSubscription(UserSubscription $subscription, string $reason = ''): void
    {
        DB::transaction(function () use ($subscription, $reason) {
            $subscription->update([
                'status' => SubscriptionStatus::CANCELLED,
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'cancelled_at' => now()->toIso8601String(),
                    'cancelled_reason' => $reason,
                ]),
            ]);

            Log::info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'reason' => $reason,
            ]);

            // TODO: Send confirmation email
        });
    }

    /**
     * Get all active subscriptions for a user
     * 
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveSubscriptions(User $user)
    {
        return $user->userSubscriptions()
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('end_date', '>=', Carbon::now())
            ->with('subscriptionPlan')
            ->get();
    }

    /**
     * Get subscription usage details
     * 
     * @param UserSubscription $subscription
     * @return array
     */
    public function getUsageDetails(UserSubscription $subscription): array
    {
        $plan = $subscription->subscriptionPlan;
        $remaining = $this->getRemainingQuota($subscription);

        $used = match ($plan->type) {
            'recharge_count' => $subscription->sessions_used ?? 0,
            'kwh' => $subscription->kwh_used ?? 0,
            'duration' => $subscription->duration_minutes_used ?? 0,
            default => 0,
        };

        $total = match ($plan->type) {
            'recharge_count' => $plan->max_sessions ?? 0,
            'kwh' => $plan->max_kwh ?? 0,
            'duration' => $plan->max_duration_minutes ?? 0,
            default => 0,
        };

        $percentage = $total > 0 ? round(($used / $total) * 100, 1) : 0;

        return [
            'type' => $plan->type,
            'used' => $used,
            'remaining' => $remaining,
            'total' => $total,
            'percentage' => $percentage,
            'unit' => match ($plan->type) {
                'recharge_count' => 'sessions',
                'kwh' => 'kWh',
                'duration' => 'minutes',
                default => '',
            },
        ];
    }
}
