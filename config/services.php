<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'webhook' => [
        'secret' => env('WEBHOOK_SECRET'),
        'enabled' => env('WEBHOOK_ENABLED', false),
        'timeout' => env('WEBHOOK_TIMEOUT', 30),
        'retry_attempts' => env('WEBHOOK_RETRY_ATTEMPTS', 3),
    ],

   'stripe' => [
       'key' => env('STRIPE_KEY'),
       'secret' => env('STRIPE_SECRET'),
       'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
   ],

   'cmi' => [
       'merchant_id' => env('CMI_MERCHANT_ID'),
       'secret' => env('CMI_SECRET'),
       'endpoint' => env('CMI_ENDPOINT', 'https://test.cmi/payment'),
   ],

    'infobip' => [
        'base_url'   => env('INFOBIP_BASE_URL'),         // e.g. https://xxxxx.api.infobip.com
        'api_key'    => env('INFOBIP_API_KEY'),
        'sender'     => env('INFOBIP_SENDER', 'EVON'),   // alphanumeric or short code
        'timeout'    => (int) env('INFOBIP_TIMEOUT', 8),
        'driver'     => env('SMS_DRIVER', 'infobip'),    // infobip | log
    ],

    'steve' => [
        'base_url' => env('STEVE_API_URL', env('STEVE_BASE_URL', '')),
        'token' => env('STEVE_API_TOKEN', null),
        'url' => env('STEVE_API_URL', env('STEVE_BASE_URL', '')),
        'key' => env('STEVE_API_KEY', null),
        'user' => env('STEVE_API_USER', env('STEVE_USERNAME', '')),
        'pass' => env('STEVE_API_PASS', env('STEVE_API_PASSWORD', env('STEVE_PASSWORD', ''))),
    ],

];
