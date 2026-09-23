<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\WithdrawalRequest;

/**
 * Withdrawal Approved Mail
 * 
 * Sent to partner when a withdrawal request is approved
 */
class WithdrawalApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public WithdrawalRequest $withdrawal;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, WithdrawalRequest $withdrawal)
    {
        $this->user = $user;
        $this->withdrawal = $withdrawal;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = '✅ ' . __('Withdrawal Approved');

        return $this->subject($subject)
            ->view('emails.withdrawal-approved')
            ->with([
                'user' => $this->user,
                'withdrawal' => $this->withdrawal,
                'amount' => number_format($this->withdrawal->amount, 2),
                'currency' => $this->withdrawal->currency ?? 'MAD',
                'approvedAt' => $this->withdrawal->approved_at ?? now(),
                'paymentMethod' => $this->withdrawal->payment_method ?? 'bank_transfer',
                'notes' => $this->withdrawal->notes,
            ]);
    }
}
