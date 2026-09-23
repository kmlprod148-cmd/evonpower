<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Wallet;

class WalletTransferRule implements Rule
{
    protected $sourceWallet;
    protected $destinationWallet;
    protected $message;

    /**
     * Create a new rule instance.
     */
    public function __construct(Wallet $sourceWallet, Wallet $destinationWallet)
    {
        $this->sourceWallet = $sourceWallet;
        $this->destinationWallet = $destinationWallet;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value)
    {
        if (!is_numeric($value) || $value <= 0) {
            $this->message = 'Transfer amount must be a positive number.';
            return false;
        }

        // Check if source wallet has sufficient balance
        if (!$this->sourceWallet->hasSufficientBalance($value)) {
            $this->message = 'Insufficient balance in source wallet. Available: ' . $this->sourceWallet->getFormattedBalance();
            return false;
        }

        // Check if wallets are different
        if ($this->sourceWallet->id === $this->destinationWallet->id) {
            $this->message = 'Cannot transfer to the same wallet.';
            return false;
        }

        // Check if both wallets are active
        if (!$this->sourceWallet->is_active) {
            $this->message = 'Source wallet is not active.';
            return false;
        }

        if (!$this->destinationWallet->is_active) {
            $this->message = 'Destination wallet is not active.';
            return false;
        }

        // Check currency compatibility
        if ($this->sourceWallet->currency !== $this->destinationWallet->currency) {
            $this->message = 'Currency mismatch between wallets.';
            return false;
        }

        // Check minimum balance constraint for source wallet
        $newSourceBalance = $this->sourceWallet->balance - $value;
        if ($this->sourceWallet->min_balance && $newSourceBalance < $this->sourceWallet->min_balance) {
            $this->message = 'Transfer would violate minimum balance constraint of ' . $this->sourceWallet->min_balance;
            return false;
        }

        // Check maximum balance constraint for destination wallet
        $newDestBalance = $this->destinationWallet->balance + $value;
        if ($this->destinationWallet->max_balance && $newDestBalance > $this->destinationWallet->max_balance) {
            $this->message = 'Transfer would exceed maximum balance constraint of ' . $this->destinationWallet->max_balance;
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message()
    {
        return $this->message ?: 'Invalid transfer operation.';
    }
}
