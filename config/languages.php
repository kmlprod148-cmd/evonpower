<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available Languages
    |--------------------------------------------------------------------------
    |
    | This array contains all the languages that are available in the application.
    | The first language in the array will be used as the default language.
    |
    */
    'available' => [
        'fr' => [
            'name' => 'Français',
            'native_name' => 'Français',
            'flag' => 'fr',
            'direction' => 'ltr',
            'locale' => 'fr_FR',
            'enabled' => true,
        ],
        'en' => [
            'name' => 'English',
            'native_name' => 'English',
            'flag' => 'gb',
            'direction' => 'ltr',
            'locale' => 'en_US',
            'enabled' => true,
        ],
        'ar' => [
            'name' => 'Arabic',
            'native_name' => 'العربية',
            'flag' => 'ma',
            'direction' => 'rtl',
            'locale' => 'ar_MA',
            'enabled' => true,
        ],
        'es' => [
            'name' => 'Spanish',
            'native_name' => 'Español',
            'flag' => 'es',
            'direction' => 'ltr',
            'locale' => 'es_ES',
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | This is the default language that will be used when no language is
    | specified or when the requested language is not available.
    |
    */
    'default' => 'fr',

    /*
    |--------------------------------------------------------------------------
    | Fallback Language
    |--------------------------------------------------------------------------
    |
    | This is the fallback language that will be used when a translation
    | is not found in the current language.
    |
    */
    'fallback' => 'en',

    /*
    |--------------------------------------------------------------------------
    | RTL Languages
    |--------------------------------------------------------------------------
    |
    | This array contains the languages that use right-to-left text direction.
    |
    */
    'rtl' => ['ar'],

    /*
    |--------------------------------------------------------------------------
    | Language Detection
    |--------------------------------------------------------------------------
    |
    | This determines how the application detects the user's preferred language.
    | Options: 'session', 'cookie', 'header', 'auto'
    |
    */
    'detection' => 'session',

    /*
    |--------------------------------------------------------------------------
    | Language Persistence
    |--------------------------------------------------------------------------
    |
    | This determines how long the language preference is stored.
    | Options: 'session', 'cookie', 'database'
    |
    */
    'persistence' => 'session',

    /*
    |--------------------------------------------------------------------------
    | Auto-detect Browser Language
    |--------------------------------------------------------------------------
    |
    | When enabled, the application will try to detect the user's browser
    | language and set it as the default if it's available.
    |
    */
    'auto_detect' => true,

    /*
    |--------------------------------------------------------------------------
    | Language Switcher
    |--------------------------------------------------------------------------
    |
    | Configuration for the language switcher component.
    |
    */
    'switcher' => [
        'enabled' => true,
        'show_flags' => true,
        'show_names' => true,
        'show_native_names' => true,
        'animation' => true,
        'position' => 'top-right', // top-right, top-left, bottom-right, bottom-left
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Files
    |--------------------------------------------------------------------------
    |
    | Configuration for translation files.
    |
    */
    'translations' => [
        'path' => 'resources/lang',
        'files' => [
            'messages',
            'auth',
            'validation',
            'dashboard',
            'charging_points',
            'reservations',
            'transactions',
            'users',
            'reports',
            'credits',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Names
    |--------------------------------------------------------------------------
    |
    | This array maps language codes to their display names.
    |
    */
    'names' => [
        'fr' => 'Français',
        'en' => 'English',
        'ar' => 'العربية',
        'es' => 'Español',
    ],

    /*
    |--------------------------------------------------------------------------
    | Flag URLs
    |--------------------------------------------------------------------------
    |
    | This array maps language codes to their flag image URLs.
    |
    */
    'flags' => [
        'fr' => 'https://flagcdn.com/w20/fr.png',
        'en' => 'https://flagcdn.com/w20/gb.png',
        'ar' => 'https://flagcdn.com/w20/ma.png',
        'es' => 'https://flagcdn.com/w20/es.png',
    ],
];