<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available Currencies
    |--------------------------------------------------------------------------
    |
    | This array contains all the available currencies for the application.
    | Each currency has a code, name, symbol, and formatting options.
    |
    */
    'available' => [
        'EUR' => [
            'name' => 'Euro',
            'native_name' => 'Euro',
            'symbol' => '€',
            'code' => 'EUR',
            'symbol_position' => 'after',
            'decimal_places' => 2,
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'enabled' => true,
            'is_default' => true,
            'flag' => 'https://flagcdn.com/w20/eu.png',
        ],
        'USD' => [
            'name' => 'Dollar américain',
            'native_name' => 'US Dollar',
            'symbol' => '$',
            'code' => 'USD',
            'symbol_position' => 'before',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'enabled' => true,
            'is_default' => false,
            'flag' => 'https://flagcdn.com/w20/us.png',
        ],
        'GBP' => [
            'name' => 'Livre sterling',
            'native_name' => 'British Pound',
            'symbol' => '£',
            'code' => 'GBP',
            'symbol_position' => 'before',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'enabled' => true,
            'is_default' => false,
            'flag' => 'https://flagcdn.com/w20/gb.png',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | This is the default currency that will be used when no currency
    | is specified or when the user's currency is not available.
    |
    */
    'default' => 'EUR',

    /*
    |--------------------------------------------------------------------------
    | Currency Detection
    |--------------------------------------------------------------------------
    |
    | Configure how the application detects the user's preferred currency.
    | Options: 'session', 'browser', 'url', 'ip'
    |
    */
    'detection' => [
        'session' => true,
        'browser' => false,
        'url' => false,
        'ip' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Switcher Settings
    |--------------------------------------------------------------------------
    |
    | Configure the currency switcher component settings.
    |
    */
    'switcher' => [
        'show_flags' => true,
        'show_symbols' => true,
        'show_names' => true,
        'dropdown_style' => 'dropdown', // 'dropdown' or 'buttons'
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Settings
    |--------------------------------------------------------------------------
    |
    | Configure exchange rate settings for currency conversion.
    |
    */
    'exchange_rates' => [
        'enabled' => true,
        'update_frequency' => 'daily', // 'hourly', 'daily', 'weekly'
        'api_provider' => 'fixer', // 'fixer', 'exchangerate', 'currencylayer'
        'api_key' => env('EXCHANGE_RATE_API_KEY'),
        'base_currency' => 'EUR',
    ],
];
