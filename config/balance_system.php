<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration du Système de Balances Hiérarchiques
    |--------------------------------------------------------------------------
    |
    | Ce fichier contient la configuration du système de calcul de balances
    | hiérarchiques pour la hiérarchie Admin → Intégrateur → Opérateur
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Configuration du Cache
    |--------------------------------------------------------------------------
    |
    | Durées de cache en secondes pour différents types de données
    |
    */
    'cache' => [
        'user_balance' => env('BALANCE_CACHE_USER', 300),           // 5 minutes
        'hierarchy_balance' => env('BALANCE_CACHE_HIERARCHY', 600), // 10 minutes
        'global_stats' => env('BALANCE_CACHE_GLOBAL', 900),        // 15 minutes
        'commission_breakdown' => env('BALANCE_CACHE_COMMISSION', 1800), // 30 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Recalculs
    |--------------------------------------------------------------------------
    |
    | Intervalles de recalcul automatique en secondes
    |
    */
    'recalculation' => [
        'immediate' => env('BALANCE_RECALC_IMMEDIATE', 0),      // Immédiat
        'hourly' => env('BALANCE_RECALC_HOURLY', 3600),         // Toutes les heures
        'daily' => env('BALANCE_RECALC_DAILY', 86400),         // Quotidien
        'weekly' => env('BALANCE_RECALC_WEEKLY', 604800),      // Hebdomadaire
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Limites
    |--------------------------------------------------------------------------
    |
    | Limites pour les opérations de calcul de balance
    |
    */
    'limits' => [
        'max_users_per_batch' => env('BALANCE_MAX_USERS_BATCH', 1000),
        'max_execution_time' => env('BALANCE_MAX_EXECUTION_TIME', 300), // 5 minutes
        'max_memory_usage' => env('BALANCE_MAX_MEMORY', '512M'),
        'max_retry_attempts' => env('BALANCE_MAX_RETRY', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration des notifications pour les calculs de balance
    |
    */
    'notifications' => [
        'enabled' => env('BALANCE_NOTIFICATIONS_ENABLED', true),
        'email_on_success' => env('BALANCE_EMAIL_SUCCESS', false),
        'email_on_failure' => env('BALANCE_EMAIL_FAILURE', true),
        'slack_webhook' => env('BALANCE_SLACK_WEBHOOK', null),
        'discord_webhook' => env('BALANCE_DISCORD_WEBHOOK', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Exports
    |--------------------------------------------------------------------------
    |
    | Configuration des exports de données
    |
    */
    'exports' => [
        'enabled' => env('BALANCE_EXPORTS_ENABLED', true),
        'formats' => ['json', 'csv', 'pdf'],
        'storage_path' => env('BALANCE_EXPORTS_PATH', 'storage/app/reports'),
        'retention_days' => env('BALANCE_EXPORTS_RETENTION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Rôles
    |--------------------------------------------------------------------------
    |
    | Configuration des rôles et leurs permissions
    |
    */
    'roles' => [
        'admin' => [
            'can_view_global_stats' => true,
            'can_recalculate_all' => true,
            'can_export_data' => true,
            'can_clear_cache' => true,
            'can_view_hierarchy' => true,
        ],
        'integrator' => [
            'can_view_global_stats' => false,
            'can_recalculate_all' => false,
            'can_export_data' => false,
            'can_clear_cache' => false,
            'can_view_hierarchy' => true,
        ],
        'operator' => [
            'can_view_global_stats' => false,
            'can_recalculate_all' => false,
            'can_export_data' => false,
            'can_clear_cache' => false,
            'can_view_hierarchy' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Métriques
    |--------------------------------------------------------------------------
    |
    | Configuration des métriques de performance
    |
    */
    'metrics' => [
        'enabled' => env('BALANCE_METRICS_ENABLED', true),
        'collect_execution_time' => env('BALANCE_METRICS_EXECUTION_TIME', true),
        'collect_memory_usage' => env('BALANCE_METRICS_MEMORY', true),
        'collect_cache_hits' => env('BALANCE_METRICS_CACHE', true),
        'collect_user_stats' => env('BALANCE_METRICS_USERS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Snapshots
    |--------------------------------------------------------------------------
    |
    | Configuration des instantanés de balance
    |
    */
    'snapshots' => [
        'enabled' => env('BALANCE_SNAPSHOTS_ENABLED', true),
        'frequency' => env('BALANCE_SNAPSHOTS_FREQUENCY', 'daily'), // daily, weekly, monthly
        'retention_days' => env('BALANCE_SNAPSHOTS_RETENTION', 365),
        'auto_cleanup' => env('BALANCE_SNAPSHOTS_AUTO_CLEANUP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Historiques
    |--------------------------------------------------------------------------
    |
    | Configuration de l'historique des balances
    |
    */
    'history' => [
        'enabled' => env('BALANCE_HISTORY_ENABLED', true),
        'retention_days' => env('BALANCE_HISTORY_RETENTION', 730), // 2 ans
        'auto_cleanup' => env('BALANCE_HISTORY_AUTO_CLEANUP', true),
        'detailed_logging' => env('BALANCE_HISTORY_DETAILED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Verrouillages
    |--------------------------------------------------------------------------
    |
    | Configuration du système de verrouillage des balances
    |
    */
    'locking' => [
        'enabled' => env('BALANCE_LOCKING_ENABLED', true),
        'default_timeout' => env('BALANCE_LOCKING_TIMEOUT', 30), // minutes
        'max_timeout' => env('BALANCE_LOCKING_MAX_TIMEOUT', 120), // minutes
        'auto_unlock' => env('BALANCE_LOCKING_AUTO_UNLOCK', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Validations
    |--------------------------------------------------------------------------
    |
    | Configuration des validations de balance
    |
    */
    'validation' => [
        'check_sufficient_balance' => env('BALANCE_VALIDATION_SUFFICIENT', true),
        'check_balance_locked' => env('BALANCE_VALIDATION_LOCKED', true),
        'check_user_active' => env('BALANCE_VALIDATION_ACTIVE', true),
        'check_role_permissions' => env('BALANCE_VALIDATION_ROLE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Tests
    |--------------------------------------------------------------------------
    |
    | Configuration des tests du système de balance
    |
    */
    'testing' => [
        'enabled' => env('BALANCE_TESTING_ENABLED', false),
        'mock_calculations' => env('BALANCE_TESTING_MOCK', false),
        'test_data_retention' => env('BALANCE_TESTING_RETENTION', 7), // jours
        'performance_threshold' => env('BALANCE_TESTING_THRESHOLD', 5), // secondes
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Alertes
    |--------------------------------------------------------------------------
    |
    | Configuration des alertes du système
    |
    */
    'alerts' => [
        'enabled' => env('BALANCE_ALERTS_ENABLED', true),
        'high_execution_time' => env('BALANCE_ALERTS_EXECUTION_TIME', 60), // secondes
        'high_memory_usage' => env('BALANCE_ALERTS_MEMORY', '1G'),
        'high_error_rate' => env('BALANCE_ALERTS_ERROR_RATE', 10), // pourcentage
        'low_cache_hit_rate' => env('BALANCE_ALERTS_CACHE_HIT', 80), // pourcentage
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Rapports
    |--------------------------------------------------------------------------
    |
    | Configuration des rapports automatiques
    |
    */
    'reports' => [
        'enabled' => env('BALANCE_REPORTS_ENABLED', true),
        'daily_summary' => env('BALANCE_REPORTS_DAILY', true),
        'weekly_summary' => env('BALANCE_REPORTS_WEEKLY', true),
        'monthly_summary' => env('BALANCE_REPORTS_MONTHLY', true),
        'email_recipients' => env('BALANCE_REPORTS_EMAIL', ''),
        'include_charts' => env('BALANCE_REPORTS_CHARTS', true),
        'include_metrics' => env('BALANCE_REPORTS_METRICS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Performances
    |--------------------------------------------------------------------------
    |
    | Configuration des optimisations de performance
    |
    */
    'performance' => [
        'use_database_transactions' => env('BALANCE_PERF_TRANSACTIONS', true),
        'batch_size' => env('BALANCE_PERF_BATCH_SIZE', 100),
        'parallel_processing' => env('BALANCE_PERF_PARALLEL', false),
        'memory_limit' => env('BALANCE_PERF_MEMORY_LIMIT', '512M'),
        'time_limit' => env('BALANCE_PERF_TIME_LIMIT', 300), // secondes
    ],
];
