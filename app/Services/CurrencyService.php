<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\AdminSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Cache duration for exchange rates in seconds (24 hours)
     */
    protected const CACHE_DURATION = 86400;

    /**
     * Available currencies with their configurations
     */
    protected array $currencies = [
        'MAD' => [
            'name' => 'Dirham Marocain',
            'symbol' => 'DH',
            'code' => 'MAD',
            'symbol_position' => 'after',
            'decimal_places' => 2,
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'flag' => '🇲🇦',
        ],
        'EUR' => [
            'name' => 'Euro',
            'symbol' => '€',
            'code' => 'EUR',
            'symbol_position' => 'after',
            'decimal_places' => 2,
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'flag' => '🇪🇺',
        ],
        'USD' => [
            'name' => 'Dollar Américain',
            'symbol' => '$',
            'code' => 'USD',
            'symbol_position' => 'before',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'flag' => '🇺🇸',
        ],
        'GBP' => [
            'name' => 'Livre Sterling',
            'symbol' => '£',
            'code' => 'GBP',
            'symbol_position' => 'before',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'flag' => '🇬🇧',
        ],
        'CHF' => [
            'name' => 'Franc Suisse',
            'symbol' => 'CHF',
            'code' => 'CHF',
            'symbol_position' => 'before',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => "'",
            'flag' => '🇨🇭',
        ],
        'CAD' => [
            'name' => 'Dollar Canadien',
            'symbol' => 'CA$',
            'code' => 'CAD',
            'symbol_position' => 'before',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'flag' => '🇨🇦',
        ],
    ];

    /**
     * Fallback exchange rates (updated manually as backup)
     */
    protected array $fallbackRates = [
        'EUR' => [
            'MAD' => 10.80,
            'USD' => 1.08,
            'GBP' => 0.86,
            'CHF' => 0.94,
            'CAD' => 1.47,
        ],
        'MAD' => [
            'EUR' => 0.093,
            'USD' => 0.10,
            'GBP' => 0.080,
            'CHF' => 0.087,
            'CAD' => 0.136,
        ],
        'USD' => [
            'EUR' => 0.93,
            'MAD' => 10.00,
            'GBP' => 0.80,
            'CHF' => 0.87,
            'CAD' => 1.36,
        ],
    ];

    /**
     * Get the default/admin-set currency
     */
    public function getDefaultCurrency(): string
    {
        return Cache::remember('app_default_currency', 3600, function () {
            // Try AdminSetting first
            $currency = AdminSetting::getSetting('currency', 'app_default_currency');
            if ($currency) {
                return $currency;
            }

            // Try Setting model
            $currency = Setting::get('app_currency');
            if ($currency) {
                return $currency;
            }

            // Fall back to config
            return config('app.currency', 'MAD');
        });
    }

    /**
     * Set the default currency (admin action)
     */
    public function setDefaultCurrency(string $currencyCode): bool
    {
        if (!$this->isValidCurrency($currencyCode)) {
            return false;
        }

        // Update in AdminSetting
        AdminSetting::updateSetting('currency', 'app_default_currency', $currencyCode, 'select');
        
        // Also update in Setting for backward compatibility
        Setting::set('app_currency', $currencyCode, 'string', 'Default application currency');
        
        // Clear cache
        Cache::forget('app_default_currency');
        Cache::forget('currency_config');

        Log::info('Default currency changed', ['currency' => $currencyCode]);

        return true;
    }

    /**
     * Get the current active currency (from session or default)
     */
    public function getCurrentCurrency(): string
    {
        return session('currency', $this->getDefaultCurrency());
    }

    /**
     * Set the current session currency
     */
    public function setCurrentCurrency(string $currencyCode): bool
    {
        if (!$this->isValidCurrency($currencyCode)) {
            return false;
        }

        session(['currency' => $currencyCode]);
        return true;
    }

    /**
     * Check if a currency code is valid
     */
    public function isValidCurrency(string $code): bool
    {
        return array_key_exists(strtoupper($code), $this->currencies);
    }

    /**
     * Get all available currencies
     */
    public function getAvailableCurrencies(): array
    {
        return $this->currencies;
    }

    /**
     * Get enabled currencies (from admin settings)
     */
    public function getEnabledCurrencies(): array
    {
        $enabledCodes = Cache::remember('enabled_currencies', 3600, function () {
            $setting = AdminSetting::getSetting('currency', 'enabled_currencies');
            if ($setting && is_array($setting)) {
                return $setting;
            }
            return ['MAD', 'EUR', 'USD']; // Default enabled
        });

        return array_filter($this->currencies, function ($code) use ($enabledCodes) {
            return in_array($code, $enabledCodes);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get currency configuration
     */
    public function getCurrencyConfig(string $code): ?array
    {
        $code = strtoupper($code);
        return $this->currencies[$code] ?? null;
    }

    /**
     * Get currency symbol
     */
    public function getSymbol(string $code): string
    {
        $config = $this->getCurrencyConfig($code);
        return $config['symbol'] ?? $code;
    }

    /**
     * Format amount with currency
     */
    public function format(float $amount, ?string $currencyCode = null, bool $includeSymbol = true): string
    {
        $currencyCode = $currencyCode ?? $this->getCurrentCurrency();
        $config = $this->getCurrencyConfig($currencyCode);

        if (!$config) {
            return number_format($amount, 2) . ' ' . $currencyCode;
        }

        $formattedNumber = number_format(
            $amount,
            $config['decimal_places'],
            $config['decimal_separator'],
            $config['thousands_separator']
        );

        if (!$includeSymbol) {
            return $formattedNumber;
        }

        if ($config['symbol_position'] === 'before') {
            return $config['symbol'] . $formattedNumber;
        }

        return $formattedNumber . ' ' . $config['symbol'];
    }

    /**
     * Convert amount between currencies
     */
    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = $this->getExchangeRate($from, $to);
        return round($amount * $rate, 2);
    }

    /**
     * Get exchange rate between two currencies
     */
    public function getExchangeRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$from}_{$to}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($from, $to) {
            // Try to fetch from API
            $rate = $this->fetchExchangeRateFromApi($from, $to);
            
            if ($rate !== null) {
                return $rate;
            }

            // Fall back to stored rates
            return $this->getFallbackRate($from, $to);
        });
    }

    /**
     * Fetch exchange rate from external API
     */
    protected function fetchExchangeRateFromApi(string $from, string $to): ?float
    {
        $apiKey = config('currencies.exchange_rates.api_key');
        $provider = config('currencies.exchange_rates.api_provider', 'exchangerate');

        if (empty($apiKey)) {
            return null;
        }

        try {
            switch ($provider) {
                case 'exchangerate':
                    return $this->fetchFromExchangeRateApi($from, $to, $apiKey);
                case 'fixer':
                    return $this->fetchFromFixerApi($from, $to, $apiKey);
                case 'currencylayer':
                    return $this->fetchFromCurrencyLayerApi($from, $to, $apiKey);
                default:
                    return null;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch exchange rate from API', [
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fetch from ExchangeRate-API
     */
    protected function fetchFromExchangeRateApi(string $from, string $to, string $apiKey): ?float
    {
        $response = Http::timeout(10)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$from}/{$to}");
        
        if ($response->successful() && $response->json('result') === 'success') {
            return (float) $response->json('conversion_rate');
        }

        return null;
    }

    /**
     * Fetch from Fixer.io
     */
    protected function fetchFromFixerApi(string $from, string $to, string $apiKey): ?float
    {
        $response = Http::timeout(10)->get("https://data.fixer.io/api/latest", [
            'access_key' => $apiKey,
            'base' => $from,
            'symbols' => $to,
        ]);

        if ($response->successful() && $response->json('success')) {
            $rates = $response->json('rates');
            return (float) ($rates[$to] ?? null);
        }

        return null;
    }

    /**
     * Fetch from CurrencyLayer
     */
    protected function fetchFromCurrencyLayerApi(string $from, string $to, string $apiKey): ?float
    {
        $response = Http::timeout(10)->get("https://api.currencylayer.com/live", [
            'access_key' => $apiKey,
            'source' => $from,
            'currencies' => $to,
        ]);

        if ($response->successful() && $response->json('success')) {
            $quotes = $response->json('quotes');
            $key = $from . $to;
            return (float) ($quotes[$key] ?? null);
        }

        return null;
    }

    /**
     * Get fallback exchange rate
     */
    protected function getFallbackRate(string $from, string $to): float
    {
        // Direct rate
        if (isset($this->fallbackRates[$from][$to])) {
            return $this->fallbackRates[$from][$to];
        }

        // Try inverse rate
        if (isset($this->fallbackRates[$to][$from])) {
            return 1 / $this->fallbackRates[$to][$from];
        }

        // Try through EUR as base
        if ($from !== 'EUR' && $to !== 'EUR') {
            $toEur = $this->getFallbackRate($from, 'EUR');
            $fromEur = $this->getFallbackRate('EUR', $to);
            return $toEur * $fromEur;
        }

        Log::warning('No exchange rate found', ['from' => $from, 'to' => $to]);
        return 1.0;
    }

    /**
     * Refresh all exchange rates from API
     */
    public function refreshExchangeRates(): bool
    {
        $baseCurrency = $this->getDefaultCurrency();
        $currencies = array_keys($this->currencies);
        $refreshed = 0;

        foreach ($currencies as $targetCurrency) {
            if ($targetCurrency === $baseCurrency) {
                continue;
            }

            $cacheKey = "exchange_rate_{$baseCurrency}_{$targetCurrency}";
            Cache::forget($cacheKey);

            $rate = $this->getExchangeRate($baseCurrency, $targetCurrency);
            if ($rate > 0) {
                $refreshed++;
            }
        }

        Log::info('Exchange rates refreshed', ['count' => $refreshed, 'base' => $baseCurrency]);
        return $refreshed > 0;
    }

    /**
     * Format amount and convert to current currency
     */
    public function formatConverted(float $amount, string $fromCurrency): string
    {
        $toCurrency = $this->getCurrentCurrency();
        $convertedAmount = $this->convert($amount, $fromCurrency, $toCurrency);
        return $this->format($convertedAmount, $toCurrency);
    }

    /**
     * Get currency display info for UI
     */
    public function getCurrencyDisplayInfo(string $code): array
    {
        $config = $this->getCurrencyConfig($code);
        
        if (!$config) {
            return [
                'code' => $code,
                'name' => $code,
                'symbol' => $code,
                'flag' => '',
            ];
        }

        return [
            'code' => $config['code'],
            'name' => $config['name'],
            'symbol' => $config['symbol'],
            'flag' => $config['flag'] ?? '',
        ];
    }

    /**
     * Clear all currency-related caches
     */
    public function clearCache(): void
    {
        Cache::forget('app_default_currency');
        Cache::forget('enabled_currencies');
        Cache::forget('currency_config');

        // Clear exchange rate caches
        foreach (array_keys($this->currencies) as $from) {
            foreach (array_keys($this->currencies) as $to) {
                if ($from !== $to) {
                    Cache::forget("exchange_rate_{$from}_{$to}");
                }
            }
        }

        Log::info('Currency caches cleared');
    }
}
