<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for internal storage. All amounts are stored
    | internally in this currency and converted for display.
    |
    */
    'default_currency' => env('MONEY_DEFAULT_CURRENCY', 'EUR'),

    /*
    |--------------------------------------------------------------------------
    | Decimal Precision
    |--------------------------------------------------------------------------
    |
    | The number of decimal places to use for monetary values.
    | This affects both storage and display formatting.
    |
    */
    'decimal_precision' => env('MONEY_DECIMAL_PRECISION', 2),

    /*
    |--------------------------------------------------------------------------
    | Calculation Precision
    |--------------------------------------------------------------------------
    |
    | The number of decimal places to use for internal calculations.
    | This is higher than display precision to maintain accuracy.
    |
    */
    'calculation_precision' => env('MONEY_CALCULATION_PRECISION', 4),

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of supported currencies with their symbols and names.
    | Only these currencies can be used for display and conversion.
    |
    */
    'supported_currencies' => [
        'EUR' => [
            'symbol' => '€',
            'name' => 'Euro',
            'code' => 'EUR',
        ],
        'USD' => [
            'symbol' => '$',
            'name' => 'US Dollar',
            'code' => 'USD',
        ],
        'GBP' => [
            'symbol' => '£',
            'name' => 'British Pound',
            'code' => 'GBP',
        ],
        'MAD' => [
            'symbol' => 'د.م.',
            'name' => 'Moroccan Dirham',
            'code' => 'MAD',
        ],
        'CAD' => [
            'symbol' => 'C$',
            'name' => 'Canadian Dollar',
            'code' => 'CAD',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate APIs
    |--------------------------------------------------------------------------
    |
    | Configuration for external exchange rate APIs.
    | Multiple APIs are supported for redundancy.
    |
    */
    'exchange_rate_apis' => [
        'exchangerates' => [
            'url' => 'https://api.exchangerates-api.io/v4/latest',
            'timeout' => 5,
            'enabled' => env('EXCHANGERATES_API_ENABLED', true),
        ],
        'fixer' => [
            'url' => 'http://data.fixer.io/api/latest',
            'api_key' => env('FIXER_API_KEY'),
            'timeout' => 5,
            'enabled' => env('FIXER_API_ENABLED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching exchange rates.
    |
    */
    'cache' => [
        'duration' => env('MONEY_CACHE_DURATION', 60), // minutes
        'prefix' => 'money_exchange_rate',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Exchange Rates
    |--------------------------------------------------------------------------
    |
    | Static exchange rates used when APIs are unavailable.
    | These rates should be updated periodically.
    |
    */
    'fallback_rates' => [
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Formatting Options
    |--------------------------------------------------------------------------
    |
    | Configuration for amount formatting.
    |
    */
    'formatting' => [
        'thousands_separator' => ',',
        'decimal_separator' => '.',
        'symbol_position' => 'after', // 'before' or 'after'
        'space_between_symbol' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Configuration for amount validation.
    |
    */
    'validation' => [
        'min_amount' => 0.01,
        'max_amount' => 999999.99,
        'allow_negative' => false,
        'allow_zero' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for logging money service operations.
    |
    */
    'logging' => [
        'enabled' => env('MONEY_LOGGING_ENABLED', true),
        'level' => env('MONEY_LOGGING_LEVEL', 'info'),
        'log_conversions' => env('MONEY_LOG_CONVERSIONS', false),
        'log_api_calls' => env('MONEY_LOG_API_CALLS', true),
    ],
];
