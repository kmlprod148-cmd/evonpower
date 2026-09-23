<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Wallet;

class WalletBalanceRule implements Rule
{
    protected $wallet;
    protected $message;

    /**
     * Create a new rule instance.
     */
    public function __construct(Wallet $wallet)
    {
        $this->wallet = $wallet;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value)
    {
        if (!is_numeric($value) || $value <= 0) {
            $this->message = 'Amount must be a positive number.';
            return false;
        }

        if (!$this->wallet->hasSufficientBalance($value)) {
            $this->message = 'Insufficient balance. Available: ' . $this->wallet->getFormattedBalance();
            return false;
        }

        // Check minimum balance constraint
        $newBalance = $this->wallet->balance - $value;
        if ($this->wallet->min_balance && $newBalance < $this->wallet->min_balance) {
            $this->message = 'Transaction would violate minimum balance constraint of ' . $this->wallet->min_balance;
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message()
    {
        return $this->message ?: 'Invalid wallet balance.';
    }
}
