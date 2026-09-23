<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration du système de recharge
    |--------------------------------------------------------------------------
    |
    | Configuration pour la gestion des sessions de recharge et l'intégration
    | avec l'API Steve
    |
    */

    // Seuil minimum de crédit pour le mode postpayé
    'postpaid_min_threshold' => env('CHARGING_POSTPAID_MIN_THRESHOLD', 10.00),

    // Durée maximale d'une session en minutes (8 heures par défaut)
    'max_session_duration' => env('CHARGING_MAX_SESSION_DURATION', 480),

    // Intervalle de monitoring en secondes
    'monitor_interval' => env('CHARGING_MONITOR_INTERVAL', 60),

    // Facteur de sécurité pour la durée maximale (150% = session arrêtée si durée > 1.5x estimation)
    'max_duration_factor' => env('CHARGING_MAX_DURATION_FACTOR', 1.5),

    // Démarrage automatique des sessions après confirmation de réservation
    'auto_start_enabled' => env('CHARGING_AUTO_START_ENABLED', true),

    // Tolérance pour les différences de calcul de coût (en euros)
    'cost_tolerance' => env('CHARGING_COST_TOLERANCE', 0.01),

    // Remboursement automatique si coût réel < prépayé
    'auto_refund_enabled' => env('CHARGING_AUTO_REFUND_ENABLED', true),

    // Notifications
    'notifications' => [
        'session_started' => env('CHARGING_NOTIFY_SESSION_STARTED', true),
        'session_stopped' => env('CHARGING_NOTIFY_SESSION_STOPPED', true),
        'low_credit_warning' => env('CHARGING_NOTIFY_LOW_CREDIT', true),
        'low_credit_threshold' => env('CHARGING_LOW_CREDIT_THRESHOLD', 5.00),
    ],

    // Logging
    'logging' => [
        'enabled' => env('CHARGING_LOGGING_ENABLED', true),
        'level' => env('CHARGING_LOG_LEVEL', 'info'), // debug, info, warning, error
        'channel' => env('CHARGING_LOG_CHANNEL', 'stack'),
    ],
];
