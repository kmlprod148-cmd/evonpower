<?php

use App\Services\CurrencyService;

if (!function_exists('currency')) {
    /**
     * Get the CurrencyService instance
     *
     * @return CurrencyService
     */
    function currency(): CurrencyService
    {
        return app(CurrencyService::class);
    }
}

if (!function_exists('format_money')) {
    /**
     * Format a monetary amount with currency symbol
     *
     * @param float|int $amount
     * @param string|null $currencyCode
     * @param bool $includeSymbol
     * @return string
     */
    function format_money(float|int $amount, ?string $currencyCode = null, bool $includeSymbol = true): string
    {
        return currency()->format((float) $amount, $currencyCode, $includeSymbol);
    }
}

if (!function_exists('convert_currency')) {
    /**
     * Convert amount from one currency to another
     *
     * @param float|int $amount
     * @param string $from
     * @param string $to
     * @return float
     */
    function convert_currency(float|int $amount, string $from, string $to): float
    {
        return currency()->convert((float) $amount, $from, $to);
    }
}

if (!function_exists('current_currency')) {
    /**
     * Get the current active currency code
     *
     * @return string
     */
    function current_currency(): string
    {
        return currency()->getCurrentCurrency();
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Get the currency symbol for a given currency code
     *
     * @param string|null $currencyCode
     * @return string
     */
    function currency_symbol(?string $currencyCode = null): string
    {
        $code = $currencyCode ?? current_currency();
        return currency()->getSymbol($code);
    }
}

if (!function_exists('format_price')) {
    /**
     * Format a price with automatic conversion to current currency
     *
     * @param float|int $amount
     * @param string $fromCurrency
     * @return string
     */
    function format_price(float|int $amount, string $fromCurrency = 'MAD'): string
    {
        return currency()->formatConverted((float) $amount, $fromCurrency);
    }
}

if (!function_exists('money')) {
    /**
     * Quick format for displaying money
     * Alias for format_money with default currency
     *
     * @param float|int $amount
     * @param string|null $currency
     * @return string
     */
    function money(float|int $amount, ?string $currency = null): string
    {
        return format_money($amount, $currency);
    }
}

if (!function_exists('client_balance')) {
    /**
     * Solde client formaté (via ClientBalanceService)
     */
    function client_balance(?\App\Models\User $user = null): string
    {
        return app(\App\Services\ClientBalanceService::class)->getFormatted($user);
    }
}

if (!function_exists('get_exchange_rate')) {
    /**
     * Get exchange rate between two currencies
     *
     * @param string $from
     * @param string $to
     * @return float
     */
    function get_exchange_rate(string $from, string $to): float
    {
        return currency()->getExchangeRate($from, $to);
    }
}
