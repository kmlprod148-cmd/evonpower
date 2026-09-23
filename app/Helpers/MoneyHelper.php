<?php

namespace App\Helpers;

use App\Services\MoneyService;

class MoneyHelper
{
    /**
     * Format amount for display
     */
    public static function format(float $amount, string $currency = MoneyService::DEFAULT_CURRENCY, bool $showSymbol = true): string
    {
        return MoneyService::format($amount, $currency, $showSymbol);
    }

    /**
     * Format amount with symbol prefix
     */
    public static function formatWithSymbol(float $amount, string $currency = MoneyService::DEFAULT_CURRENCY): string
    {
        return MoneyService::formatWithSymbol($amount, $currency);
    }

    /**
     * Convert and format amount
     */
    public static function convertAndFormat(float $amount, string $fromCurrency, string $toCurrency, bool $showSymbol = true): string
    {
        return MoneyService::convertAndFormat($amount, $fromCurrency, $toCurrency, $showSymbol);
    }

    /**
     * Get currency symbol
     */
    public static function getCurrencySymbol(string $currency): string
    {
        return MoneyService::getCurrencySymbol($currency);
    }

    /**
     * Get currency name
     */
    public static function getCurrencyName(string $currency): string
    {
        return MoneyService::getCurrencyName($currency);
    }

    /**
     * Check if currency is valid
     */
    public static function isValidCurrency(string $currency): bool
    {
        return MoneyService::isValidCurrency($currency);
    }

    /**
     * Get all supported currencies
     */
    public static function getSupportedCurrencies(): array
    {
        return MoneyService::getSupportedCurrencies();
    }

    /**
     * Format amount for display with color coding
     */
    public static function formatWithColor(float $amount, string $currency = MoneyService::DEFAULT_CURRENCY, bool $showSymbol = true): string
    {
        $formatted = self::format($amount, $currency, $showSymbol);
        $color = $amount >= 0 ? 'text-success' : 'text-danger';
        
        return "<span class=\"{$color}\">{$formatted}</span>";
    }

    /**
     * Format amount for display with badge
     */
    public static function formatWithBadge(float $amount, string $currency = MoneyService::DEFAULT_CURRENCY, string $type = null): string
    {
        $formatted = self::format($amount, $currency);
        $badgeClass = $type === 'credit' ? 'badge-success' : ($type === 'debit' ? 'badge-danger' : 'badge-secondary');
        
        return "<span class=\"badge {$badgeClass}\">{$formatted}</span>";
    }

    /**
     * Format amount for table display
     */
    public static function formatForTable(float $amount, string $currency = MoneyService::DEFAULT_CURRENCY, string $type = null): string
    {
        $formatted = self::format($amount, $currency);
        $sign = $type === 'credit' ? '+' : ($type === 'debit' ? '-' : '');
        $color = $type === 'credit' ? 'text-success' : ($type === 'debit' ? 'text-danger' : '');
        
        return "<span class=\"{$color}\">{$sign}{$formatted}</span>";
    }

    /**
     * Format amount for card display
     */
    public static function formatForCard(float $amount, string $currency = MoneyService::DEFAULT_CURRENCY): string
    {
        $formatted = self::format($amount, $currency);
        $color = $amount >= 0 ? 'text-success' : 'text-danger';
        
        return "<span class=\"h4 {$color}\">{$formatted}</span>";
    }

    /**
     * Format amount for small display
     */
    public static function formatSmall(float $amount, string $currency = MoneyService::DEFAULT_CURRENCY): string
    {
        $formatted = self::format($amount, $currency);
        
        return "<small>{$formatted}</small>";
    }

    /**
     * Format amount for large display
     */
    public static function formatLarge(float $amount, string $currency = MoneyService::DEFAULT_CURRENCY): string
    {
        $formatted = self::format($amount, $currency);
        $color = $amount >= 0 ? 'text-success' : 'text-danger';
        
        return "<span class=\"h2 {$color}\">{$formatted}</span>";
    }

    /**
     * Format amount for input display
     */
    public static function formatForInput(float $amount, string $currency = MoneyService::DEFAULT_CURRENCY): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Format amount for export
     */
    public static function formatForExport(float $amount, string $currency = MoneyService::DEFAULT_CURRENCY): string
    {
        return self::format($amount, $currency, false);
    }

    /**
     * Format amount for API response
     */
    public static function formatForApi(float $amount, string $currency = MoneyService::DEFAULT_CURRENCY): array
    {
        return [
            'amount' => $amount,
            'formatted' => self::format($amount, $currency),
            'currency' => $currency,
            'symbol' => self::getCurrencySymbol($currency),
        ];
    }
}
