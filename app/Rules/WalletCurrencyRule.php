<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class WalletCurrencyRule implements Rule
{
    protected $supportedCurrencies;
    protected $message;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        $this->supportedCurrencies = ['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'AUD', 'JPY'];
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value)
    {
        if (!is_string($value) || strlen($value) !== 3) {
            $this->message = 'Currency must be a 3-character code.';
            return false;
        }

        if (!in_array(strtoupper($value), $this->supportedCurrencies)) {
            $this->message = 'Currency must be one of: ' . implode(', ', $this->supportedCurrencies);
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message()
    {
        return $this->message ?: 'Invalid currency code.';
    }
}
