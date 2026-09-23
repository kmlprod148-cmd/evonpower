<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\UserSubscription;

/**
 * Subscription Renewal Reminder Mail
 * 
 * Sent 3 days before subscription renewal
 */
class SubscriptionRenewalReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public UserSubscription $subscription;
    public int $daysUntilRenewal;

    /**
     * Create a new message instance.
     */
    public function __construct(UserSubscription $subscription, int $daysUntilRenewal = 3)
    {
        $this->subscription = $subscription;
        $this->daysUntilRenewal = $daysUntilRenewal;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = '⏰ ' . __('Subscription Renewal Reminder');

        return $this->subject($subject)
            ->view('emails.subscription-renewal-reminder')
            ->with([
                'subscription' => $this->subscription,
                'user' => $this->subscription->user,
                'plan' => $this->subscription->subscriptionPlan,
                'daysUntilRenewal' => $this->daysUntilRenewal,
                'renewalDate' => $this->subscription->end_date,
                'autoRenewEnabled' => $this->subscription->auto_renew,
                'quotaRemaining' => $this->getQuotaRemaining(),
            ]);
    }

    /**
     * Get remaining quota
     */
    protected function getQuotaRemaining(): array
    {
        $plan = $this->subscription->subscriptionPlan;
        
        $used = match ($plan->type) {
            'recharge_count' => $this->subscription->sessions_used ?? 0,
            'kwh' => $this->subscription->kwh_used ?? 0,
            'duration' => $this->subscription->duration_minutes_used ?? 0,
            default => 0,
        };

        $total = match ($plan->type) {
            'recharge_count' => $plan->max_sessions ?? 0,
            'kwh' => $plan->max_kwh ?? 0,
            'duration' => $plan->max_duration_minutes ?? 0,
            default => null,
        };

        $remaining = ($total ?? PHP_INT_MAX) - $used;

        return [
            'type' => $plan->type,
            'total' => $total,
            'used' => $used,
            'remaining' => $remaining,
        ];
    }
}
