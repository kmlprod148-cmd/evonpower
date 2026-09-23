<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\UserSubscription;

/**
 * Subscription Confirmation Mail
 * 
 * Sent after successfully subscribing to a plan
 */
class SubscriptionConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public UserSubscription $subscription;
    public ?string $partnerName;

    /**
     * Create a new message instance.
     */
    public function __construct(UserSubscription $subscription)
    {
        $this->subscription = $subscription;
        
        // Get partner name from user's charging points if any
        $this->partnerName = $subscription->user?->partner?->name;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = '✅ ' . __('Subscription Confirmed');

        return $this->subject($subject)
            ->view('emails.subscription-confirmation')
            ->with([
                'subscription' => $this->subscription,
                'user' => $this->subscription->user,
                'plan' => $this->subscription->subscriptionPlan,
                'partnerName' => $this->partnerName,
                'startDate' => $this->subscription->start_date,
                'endDate' => $this->subscription->end_date,
                'amountPaid' => $this->subscription->amount_paid,
                'currency' => $this->subscription->currency,
                'quotaDetails' => $this->getQuotaDetails(),
            ]);
    }

    /**
     * Get quota details based on plan type
     */
    protected function getQuotaDetails(): array
    {
        $plan = $this->subscription->subscriptionPlan;
        
        return match ($plan->type) {
            'recharge_count' => [
                'type' => 'sessions',
                'total' => $plan->max_sessions,
                'remaining' => ($plan->max_sessions ?? 0) - ($this->subscription->sessions_used ?? 0),
            ],
            'kwh' => [
                'type' => 'kWh',
                'total' => $plan->max_kwh,
                'remaining' => ($plan->max_kwh ?? 0) - ($this->subscription->kwh_used ?? 0),
            ],
            'duration' => [
                'type' => 'minutes',
                'total' => $plan->max_duration_minutes,
                'remaining' => ($plan->max_duration_minutes ?? 0) - ($this->subscription->duration_minutes_used ?? 0),
            ],
            default => [
                'type' => 'unlimited',
                'total' => null,
                'remaining' => null,
            ],
        };
    }
}
