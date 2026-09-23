<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Refund;

/**
 * Refund Confirmation Mail
 * 
 * Sent after a refund has been processed
 */
class RefundConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Refund $refund;
    public float $amount;
    public string $currency;
    public string $refundType;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Refund $refund)
    {
        $this->user = $user;
        $this->refund = $refund;
        $this->amount = (float) $refund->amount;
        $this->currency = $refund->currency ?? 'MAD';
        $this->refundType = $refund->refund_type ?? 'unknown';
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = '💰 ' . __('Refund Processed');

        return $this->subject($subject)
            ->view('emails.refund-confirmation')
            ->with([
                'user' => $this->user,
                'refund' => $this->refund,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'refundType' => $this->refundType,
                'refundTypeLabel' => $this->getRefundTypeLabel(),
                'processedAt' => $this->refund->processed_at ?? now(),
                'reason' => $this->refund->reason,
            ]);
    }

    /**
     * Get human-readable refund type label
     */
    protected function getRefundTypeLabel(): string
    {
        return match ($this->refundType) {
            'wallet' => __('Wallet Credit'),
            'bank_card' => __('Bank Card'),
            'excess' => __('Excess Prepaid'),
            default => __('Refund'),
        };
    }
}
