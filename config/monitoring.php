<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour le système de monitoring des API
    |
    */

    // Activer/désactiver le monitoring
    'enabled' => env('API_MONITORING_ENABLED', true),

    // Nombre de jours de rétention des données
    'retention_days' => env('API_MONITORING_RETENTION_DAYS', 30),

    // Taille maximale du corps de la réponse à logger
    'max_response_body_size' => env('API_MONITORING_MAX_RESPONSE_SIZE', 10000),

    // APIs à monitorer
    'monitored_apis' => [
        'evon' => [
            'name' => 'Evon API',
            'base_url' => env('EVON_API_URL', 'http://localhost:8000'),
            'timeout' => 30,
            'retry_attempts' => 3
        ],
        'steve' => [
            'name' => 'Steve API',
            'base_url' => env('STEVE_API_URL', 'http://158.69.27.239:8080'),
            'timeout' => 30,
            'retry_attempts' => 3
        ]
    ],

    // Seuils d'alerte
    'alert_thresholds' => [
        'success_rate_min' => 95, // Taux de succès minimum (%)
        'response_time_max' => 5000, // Temps de réponse maximum (ms)
        'error_count_max' => 10, // Nombre maximum d'erreurs
        'consecutive_failures_max' => 5 // Échecs consécutifs maximum
    ],

    // Configuration des graphiques
    'charts' => [
        'performance_interval' => 5, // Intervalle en minutes pour les graphiques de performance
        'max_data_points' => 100, // Nombre maximum de points de données
        'default_time_range' => 60 // Période par défaut en minutes
    ],

    // Configuration de l'export
    'export' => [
        'max_records' => 10000, // Nombre maximum d'enregistrements à exporter
        'formats' => ['json', 'csv'],
        'default_format' => 'json'
    ],

    // Configuration du cache
    'cache' => [
        'enabled' => true,
        'ttl' => 300, // TTL en secondes (5 minutes)
        'prefix' => 'api_monitoring_'
    ],

    // Configuration des notifications
    'notifications' => [
        'enabled' => env('API_MONITORING_NOTIFICATIONS_ENABLED', false),
        'channels' => ['mail', 'slack'],
        'recipients' => [
            'mail' => env('API_MONITORING_MAIL_RECIPIENTS', 'admin@example.com'),
            'slack' => env('API_MONITORING_SLACK_WEBHOOK', null)
        ]
    ],

    // Configuration des métriques en temps réel
    'realtime' => [
        'enabled' => true,
        'refresh_interval' => 30, // Intervalle de rafraîchissement en secondes
        'max_requests_display' => 50 // Nombre maximum de requêtes à afficher
    ],

    // Configuration du nettoyage automatique
    'cleanup' => [
        'enabled' => env('API_MONITORING_CLEANUP_ENABLED', true),
        'schedule' => 'daily', // daily, weekly, monthly
        'time' => '02:00' // Heure du nettoyage (format 24h)
    ],

    // Configuration des tests de santé
    'health_checks' => [
        'enabled' => true,
        'interval' => 60, // Intervalle en secondes
        'timeout' => 10, // Timeout en secondes
        'endpoints' => [
            'evon' => '/api/health',
            'steve' => '/api/v1/health'
        ]
    ],

    // Configuration des logs
    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'channel' => 'monitoring'
    ]
];
