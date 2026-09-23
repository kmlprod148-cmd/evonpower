<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoneyService
{
    /**
     * Default currency for internal storage
     */
    public const DEFAULT_CURRENCY = 'EUR';
    
    /**
     * Decimal precision for monetary values
     */
    public const DECIMAL_PRECISION = 2;
    
    /**
     * Maximum decimal places for calculations
     */
    public const CALCULATION_PRECISION = 4;
    
    /**
     * Supported currencies for display
     */
    public const SUPPORTED_CURRENCIES = [
        'EUR' => ['symbol' => '€', 'name' => 'Euro'],
        'USD' => ['symbol' => '$', 'name' => 'US Dollar'],
        'GBP' => ['symbol' => '£', 'name' => 'British Pound'],
        'MAD' => ['symbol' => 'د.م.', 'name' => 'Moroccan Dirham'],
        'CAD' => ['symbol' => 'C$', 'name' => 'Canadian Dollar'],
    ];
    
    /**
     * Cache duration for exchange rates (in minutes)
     */
    public const EXCHANGE_RATE_CACHE_DURATION = 60;
    
    /**
     * Convert amount from any currency to EUR (internal storage)
     */
    public static function toEur(float $amount, string $fromCurrency = self::DEFAULT_CURRENCY): float
    {
        if ($fromCurrency === self::DEFAULT_CURRENCY) {
            return self::roundToPrecision($amount);
        }
        
        $exchangeRate = self::getExchangeRate($fromCurrency, self::DEFAULT_CURRENCY);
        $convertedAmount = $amount * $exchangeRate;
        
        return self::roundToPrecision($convertedAmount);
    }
    
    /**
     * Convert amount from EUR (internal storage) to any currency for display
     */
    public static function fromEur(float $amount, string $toCurrency = self::DEFAULT_CURRENCY): float
    {
        if ($toCurrency === self::DEFAULT_CURRENCY) {
            return self::roundToPrecision($amount);
        }
        
        $exchangeRate = self::getExchangeRate(self::DEFAULT_CURRENCY, $toCurrency);
        $convertedAmount = $amount * $exchangeRate;
        
        return self::roundToPrecision($convertedAmount);
    }
    
    /**
     * Format amount for display with currency symbol
     */
    public static function format(float $amount, string $currency = self::DEFAULT_CURRENCY, bool $showSymbol = true): string
    {
        $displayAmount = self::fromEur($amount, $currency);
        $formattedAmount = number_format($displayAmount, self::DECIMAL_PRECISION);
        
        if (!$showSymbol) {
            return $formattedAmount;
        }
        
        $symbol = self::SUPPORTED_CURRENCIES[$currency]['symbol'] ?? $currency;
        
        return $formattedAmount . ' ' . $symbol;
    }
    
    /**
     * Format amount for display with currency symbol (alternative format)
     */
    public static function formatWithSymbol(float $amount, string $currency = self::DEFAULT_CURRENCY): string
    {
        $displayAmount = self::fromEur($amount, $currency);
        $symbol = self::SUPPORTED_CURRENCIES[$currency]['symbol'] ?? $currency;
        
        return $symbol . number_format($displayAmount, self::DECIMAL_PRECISION);
    }
    
    /**
     * Get exchange rate between two currencies
     */
    public static function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }
        
        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";
        
        return Cache::remember($cacheKey, self::EXCHANGE_RATE_CACHE_DURATION, function () use ($fromCurrency, $toCurrency) {
            return self::fetchExchangeRate($fromCurrency, $toCurrency);
        });
    }
    
    /**
     * Fetch exchange rate from external API
     */
    protected static function fetchExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        try {
            // Try multiple exchange rate APIs for reliability
            $rate = self::fetchFromExchangeRatesAPI($fromCurrency, $toCurrency) ??
                    self::fetchFromFixerAPI($fromCurrency, $toCurrency) ??
                    self::getFallbackRate($fromCurrency, $toCurrency);
            
            Log::info("Exchange rate fetched", [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'rate' => $rate
            ]);
            
            return $rate;
        } catch (\Exception $e) {
            Log::error("Failed to fetch exchange rate", [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'error' => $e->getMessage()
            ]);
            
            return self::getFallbackRate($fromCurrency, $toCurrency);
        }
    }
    
    /**
     * Fetch from exchangerates-api.io
     */
    protected static function fetchFromExchangeRatesAPI(string $fromCurrency, string $toCurrency): ?float
    {
        try {
            $response = Http::timeout(5)->get("https://api.exchangerates-api.io/v4/latest/{$fromCurrency}");
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['rates'][$toCurrency] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning("ExchangeRates API failed", ['error' => $e->getMessage()]);
        }
        
        return null;
    }
    
    /**
     * Fetch from fixer.io
     */
    protected static function fetchFromFixerAPI(string $fromCurrency, string $toCurrency): ?float
    {
        try {
            $apiKey = config('services.fixer.api_key');
            if (!$apiKey) {
                return null;
            }
            
            $response = Http::timeout(5)->get("http://data.fixer.io/api/latest", [
                'access_key' => $apiKey,
                'base' => $fromCurrency,
                'symbols' => $toCurrency
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['rates'][$toCurrency] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning("Fixer API failed", ['error' => $e->getMessage()]);
        }
        
        return null;
    }
    
    /**
     * Get fallback exchange rates (static rates)
     */
    protected static function getFallbackRate(string $fromCurrency, string $toCurrency): float
    {
        $fallbackRates = [
            'EUR' => [
                'USD' => 1.08,
                'GBP' => 0.85,
                'MAD' => 10.8,
                'CAD' => 1.48,
            ],
            'USD' => [
                'EUR' => 0.93,
                'GBP' => 0.79,
                'MAD' => 10.0,
                'CAD' => 1.37,
            ],
            'GBP' => [
                'EUR' => 1.18,
                'USD' => 1.27,
                'MAD' => 12.7,
                'CAD' => 1.73,
            ],
            'MAD' => [
                'EUR' => 0.093,
                'USD' => 0.10,
                'GBP' => 0.079,
                'CAD' => 0.137,
            ],
            'CAD' => [
                'EUR' => 0.68,
                'USD' => 0.73,
                'GBP' => 0.58,
                'MAD' => 7.3,
            ],
        ];
        
        return $fallbackRates[$fromCurrency][$toCurrency] ?? 1.0;
    }
    
    /**
     * Round amount to proper precision
     */
    public static function roundToPrecision(float $amount, int $precision = self::DECIMAL_PRECISION): float
    {
        return round($amount, $precision);
    }
    
    /**
     * Round amount for calculations (higher precision)
     */
    public static function roundForCalculation(float $amount): float
    {
        return round($amount, self::CALCULATION_PRECISION);
    }
    
    /**
     * Validate currency code
     */
    public static function isValidCurrency(string $currency): bool
    {
        return array_key_exists(strtoupper($currency), self::SUPPORTED_CURRENCIES);
    }
    
    /**
     * Get currency symbol
     */
    public static function getCurrencySymbol(string $currency): string
    {
        return self::SUPPORTED_CURRENCIES[strtoupper($currency)]['symbol'] ?? $currency;
    }
    
    /**
     * Get currency name
     */
    public static function getCurrencyName(string $currency): string
    {
        return self::SUPPORTED_CURRENCIES[strtoupper($currency)]['name'] ?? $currency;
    }
    
    /**
     * Convert and format amount for display
     */
    public static function convertAndFormat(float $amount, string $fromCurrency, string $toCurrency, bool $showSymbol = true): string
    {
        $convertedAmount = self::fromEur(
            self::toEur($amount, $fromCurrency),
            $toCurrency
        );
        
        return self::format($convertedAmount, $toCurrency, $showSymbol);
    }
    
    /**
     * Get all supported currencies
     */
    public static function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }
    
    /**
     * Clear exchange rate cache
     */
    public static function clearExchangeRateCache(): void
    {
        $currencies = array_keys(self::SUPPORTED_CURRENCIES);
        
        foreach ($currencies as $from) {
            foreach ($currencies as $to) {
                if ($from !== $to) {
                    Cache::forget("exchange_rate_{$from}_{$to}");
                }
            }
        }
    }
    
    /**
     * Get current exchange rates for all supported currencies
     */
    public static function getAllExchangeRates(string $baseCurrency = self::DEFAULT_CURRENCY): array
    {
        $rates = [];
        $currencies = array_keys(self::SUPPORTED_CURRENCIES);
        
        foreach ($currencies as $currency) {
            if ($currency !== $baseCurrency) {
                $rates[$currency] = self::getExchangeRate($baseCurrency, $currency);
            }
        }
        
        return $rates;
    }
    
    /**
     * Calculate percentage of amount
     */
    public static function calculatePercentage(float $amount, float $percentage): float
    {
        return self::roundToPrecision(($amount * $percentage) / 100);
    }
    
    /**
     * Add percentage to amount
     */
    public static function addPercentage(float $amount, float $percentage): float
    {
        return self::roundToPrecision($amount + self::calculatePercentage($amount, $percentage));
    }
    
    /**
     * Subtract percentage from amount
     */
    public static function subtractPercentage(float $amount, float $percentage): float
    {
        return self::roundToPrecision($amount - self::calculatePercentage($amount, $percentage));
    }
    
    /**
     * Check if amount is positive
     */
    public static function isPositive(float $amount): bool
    {
        return $amount > 0;
    }
    
    /**
     * Check if amount is negative
     */
    public static function isNegative(float $amount): bool
    {
        return $amount < 0;
    }
    
    /**
     * Check if amount is zero
     */
    public static function isZero(float $amount): bool
    {
        return abs($amount) < 0.01; // Account for floating point precision
    }
    
    /**
     * Get absolute value
     */
    public static function abs(float $amount): float
    {
        return abs($amount);
    }
    
    /**
     * Compare two amounts
     */
    public static function compare(float $amount1, float $amount2): int
    {
        $diff = $amount1 - $amount2;
        
        if (abs($diff) < 0.01) {
            return 0; // Equal
        }
        
        return $diff > 0 ? 1 : -1;
    }
    
    /**
     * Get minimum of two amounts
     */
    public static function min(float $amount1, float $amount2): float
    {
        return min($amount1, $amount2);
    }
    
    /**
     * Get maximum of two amounts
     */
    public static function max(float $amount1, float $amount2): float
    {
        return max($amount1, $amount2);
    }
}
