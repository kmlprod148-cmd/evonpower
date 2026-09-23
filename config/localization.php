<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | This value is the default locale for your application, which will be used
    | by the localization service when no other locale is specified.
    |
    */

    'default_locale' => env('LOCALIZATION_DEFAULT_LOCALE', 'fr'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Locale
    |--------------------------------------------------------------------------
    |
    | This value is the fallback locale for your application, which will be used
    | when the requested locale is not available.
    |
    */

    'fallback_locale' => env('LOCALIZATION_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Available Locales
    |--------------------------------------------------------------------------
    |
    | This array contains all the locales that your application supports.
    |
    */

    'available_locales' => [
        'fr' => [
            'name' => 'Français',
            'native' => 'Français',
            'flag' => '🇫🇷',
            'direction' => 'ltr',
            'currency' => 'EUR',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'datetime_format' => 'd/m/Y H:i',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
        ],
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => '🇺🇸',
            'direction' => 'ltr',
            'currency' => 'USD',
            'date_format' => 'm/d/Y',
            'time_format' => 'g:i A',
            'datetime_format' => 'm/d/Y g:i A',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ],
        'ar' => [
            'name' => 'Arabic',
            'native' => 'العربية',
            'flag' => '🇸🇦',
            'direction' => 'rtl',
            'currency' => 'EUR',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'datetime_format' => 'd/m/Y H:i',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
        ],
        'es' => [
            'name' => 'Spanish',
            'native' => 'Español',
            'flag' => '🇪🇸',
            'direction' => 'ltr',
            'currency' => 'EUR',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'datetime_format' => 'd/m/Y H:i',
            'decimal_separator' => ',',
            'thousands_separator' => '.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RTL Locales
    |--------------------------------------------------------------------------
    |
    | This array contains all the locales that use right-to-left text direction.
    |
    */

    'rtl_locales' => ['ar'],

    /*
    |--------------------------------------------------------------------------
    | Locale Detection
    |--------------------------------------------------------------------------
    |
    | This array contains the methods used to detect the user's preferred locale.
    | The methods are executed in order until a valid locale is found.
    |
    */

    'detection_methods' => [
        'url',
        'cookie',
        'session',
        'user',
        'browser',
        'default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Storage
    |--------------------------------------------------------------------------
    |
    | This array contains the methods used to store the user's locale preference.
    |
    */

    'storage_methods' => [
        'session',
        'cookie',
        'user',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Cookie
    |--------------------------------------------------------------------------
    |
    | Configuration for the locale cookie.
    |
    */

    'cookie' => [
        'name' => 'locale',
        'lifetime' => 60 * 24 * 30, // 30 days
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'http_only' => false,
        'same_site' => 'lax',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Session
    |--------------------------------------------------------------------------
    |
    | Configuration for the locale session.
    |
    */

    'session' => [
        'key' => 'locale',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale User
    |--------------------------------------------------------------------------
    |
    | Configuration for the locale user preference.
    |
    */

    'user' => [
        'column' => 'locale',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Browser
    |--------------------------------------------------------------------------
    |
    | Configuration for browser locale detection.
    |
    */

    'browser' => [
        'enabled' => true,
        'fallback' => 'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale URL
    |--------------------------------------------------------------------------
    |
    | Configuration for URL-based locale detection.
    |
    */

    'url' => [
        'enabled' => true,
        'parameter' => 'lang',
        'prefix' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Cache
    |--------------------------------------------------------------------------
    |
    | Configuration for locale caching.
    |
    */

    'cache' => [
        'enabled' => true,
        'key' => 'localization',
        'lifetime' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Middleware
    |--------------------------------------------------------------------------
    |
    | Configuration for locale middleware.
    |
    */

    'middleware' => [
        'enabled' => true,
        'priority' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Helpers
    |--------------------------------------------------------------------------
    |
    | Configuration for locale helpers.
    |
    */

    'helpers' => [
        'enabled' => true,
        'functions' => [
            'localization',
            'locale',
            'is_rtl',
            'is_ltr',
            'get_direction',
            'get_locale_name',
            'get_locale_flag',
            'get_locale_currency',
            'get_locale_date_format',
            'get_locale_time_format',
            'get_locale_datetime_format',
            'get_locale_decimal_separator',
            'get_locale_thousands_separator',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Validation
    |--------------------------------------------------------------------------
    |
    | Configuration for locale validation.
    |
    */

    'validation' => [
        'enabled' => true,
        'rules' => [
            'locale' => 'required|string|in:fr,en,ar,es',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Events
    |--------------------------------------------------------------------------
    |
    | Configuration for locale events.
    |
    */

    'events' => [
        'enabled' => true,
        'listeners' => [
            'locale.changed' => [
                'App\Listeners\LocaleChangedListener',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for locale logging.
    |
    */

    'logging' => [
        'enabled' => env('LOCALIZATION_LOGGING', false),
        'level' => 'info',
        'channel' => 'localization',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Debug
    |--------------------------------------------------------------------------
    |
    | Configuration for locale debugging.
    |
    */

    'debug' => [
        'enabled' => env('LOCALIZATION_DEBUG', false),
        'show_locale' => true,
        'show_direction' => true,
        'show_available_locales' => true,
    ],
];
