<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto Remote Start Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour le système de démarrage automatique des transactions OCPP.
    | Ce système détecte et démarre automatiquement les sessions de charge pour
    | les réservations approuvées et payées.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Activation du démarrage automatique
    |--------------------------------------------------------------------------
    |
    | Activer ou désactiver complètement le système de démarrage automatique.
    | Si désactivé, aucune réservation ne sera traitée automatiquement.
    |
    */
    'enabled' => env('AUTO_REMOTE_START_ENABLED', true),

    /*
    |----------------------------------------------------------------------
    | Auto-approval on payment confirmation
    |----------------------------------------------------------------------
    |
    | Si activé, une réservation payée (CMI/Stripe/webhook) est automatiquement
    | approuvée et peut déclencher RemoteStartTransaction sans action admin.
    |
    */
    'auto_approve_on_payment' => env('AUTO_REMOTE_START_AUTO_APPROVE', true),

    /*
    |--------------------------------------------------------------------------
    | Fenêtre de démarrage (minutes)
    |--------------------------------------------------------------------------
    |
    | Combien de minutes APRÈS l'heure de début prévue une réservation peut
    | encore être démarrée automatiquement.
    |
    | Exemple: Si start_time = 14:00 et start_window_minutes = 15,
    | la réservation pourra être démarrée jusqu'à 14:15.
    |
    */
    'start_window_minutes' => env('AUTO_REMOTE_START_WINDOW', 15),

    /*
    |--------------------------------------------------------------------------
    | Période de grâce (minutes)
    |--------------------------------------------------------------------------
    |
    | Combien de minutes AVANT l'heure de début prévue une réservation peut
    | commencer à être traitée pour démarrage automatique.
    |
    | Exemple: Si start_time = 14:00 et grace_period_minutes = 5,
    | le système peut commencer à traiter la réservation dès 13:55.
    |
    */
    'grace_period_minutes' => env('AUTO_REMOTE_START_GRACE_PERIOD', 5),

    /*
    |--------------------------------------------------------------------------
    | Tentatives de retry
    |--------------------------------------------------------------------------
    |
    | Nombre maximum de tentatives de démarrage pour une réservation en cas
    | d'échec. Après ce nombre, la réservation est marquée comme échouée.
    |
    */
    'max_retry_attempts' => env('AUTO_REMOTE_START_MAX_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Délai entre les tentatives (secondes)
    |--------------------------------------------------------------------------
    |
    | Temps d'attente entre chaque tentative de retry en cas d'échec.
    |
    */
    'retry_delay_seconds' => env('AUTO_REMOTE_START_RETRY_DELAY', 30),

    /*
    |--------------------------------------------------------------------------
    | Fréquence du scheduler (expression cron)
    |--------------------------------------------------------------------------
    |
    | À quelle fréquence le scheduler doit exécuter la commande de traitement
    | automatique. Par défaut: toutes les 2 minutes.
    |
    | Exemples:
    | - 'everyMinute' : Chaque minute
    | - 'everyTwoMinutes' : Toutes les 2 minutes (recommandé)
    | - 'everyFiveMinutes' : Toutes les 5 minutes
    |
    */
    'scheduler_frequency' => env('AUTO_REMOTE_START_SCHEDULER_FREQUENCY', 'everyTwoMinutes'),

    /*
    |--------------------------------------------------------------------------
    | Utilisation de la queue
    |--------------------------------------------------------------------------
    |
    | Si activé, les démarrages seront traités de manière asynchrone via
    | la queue Laravel. Recommandé en production pour éviter les timeouts.
    |
    */
    'use_queue' => env('AUTO_REMOTE_START_USE_QUEUE', true),

    /*
    |--------------------------------------------------------------------------
    | Nom de la queue
    |--------------------------------------------------------------------------
    |
    | Nom de la queue Laravel à utiliser pour les jobs de démarrage automatique.
    | Utiliser une queue dédiée permet de prioriser ces jobs.
    |
    */
    'queue_name' => env('AUTO_REMOTE_START_QUEUE', 'high'),

    /*
    |--------------------------------------------------------------------------
    | Limite de traitement par exécution
    |--------------------------------------------------------------------------
    |
    | Nombre maximum de réservations à traiter lors d'une seule exécution
    | de la commande. Évite la surcharge du système.
    |
    */
    'processing_limit' => env('AUTO_REMOTE_START_LIMIT', 50),

    /*
    |--------------------------------------------------------------------------
    | Validation du solde utilisateur
    |--------------------------------------------------------------------------
    |
    | Si activé, vérifie que l'utilisateur a un solde suffisant avant
    | de démarrer une session en mode postpaid.
    |
    */
    'check_user_balance' => env('AUTO_REMOTE_START_CHECK_BALANCE', true),

    /*
    |--------------------------------------------------------------------------
    | Validation stricte du connecteur
    |--------------------------------------------------------------------------
    |
    | Si activé, vérifie systématiquement que le connecteur est disponible
    | via l'API Steve avant de tenter un démarrage.
    |
    */
    'strict_connector_validation' => env('AUTO_REMOTE_START_STRICT_CONNECTOR', true),

    /*
    |--------------------------------------------------------------------------
    | Timeout des requêtes OCPP (secondes)
    |--------------------------------------------------------------------------
    |
    | Temps maximum d'attente pour les requêtes vers l'API Steve/OCPP.
    |
    */
    'ocpp_timeout' => env('AUTO_REMOTE_START_OCPP_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration des notifications envoyées aux utilisateurs lors des
    | démarrages automatiques (succès, échec).
    |
    */
    'notifications' => [
        // Envoyer notification en cas de succès
        'on_success' => env('AUTO_REMOTE_START_NOTIFY_SUCCESS', true),

        // Envoyer notification en cas d'échec
        'on_failure' => env('AUTO_REMOTE_START_NOTIFY_FAILURE', true),

        // Envoyer notification uniquement en cas d'échec définitif (après tous les retries)
        'on_final_failure_only' => env('AUTO_REMOTE_START_NOTIFY_FINAL_ONLY', true),

        // Canaux de notification (email, sms, push, database)
        'channels' => [
            'email' => env('AUTO_REMOTE_START_NOTIFY_EMAIL', true),
            'sms' => env('AUTO_REMOTE_START_NOTIFY_SMS', false),
            'push' => env('AUTO_REMOTE_START_NOTIFY_PUSH', false),
            'database' => env('AUTO_REMOTE_START_NOTIFY_DATABASE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuration du logging pour le système de démarrage automatique.
    |
    */
    'logging' => [
        // Niveau de log (debug, info, warning, error)
        'level' => env('AUTO_REMOTE_START_LOG_LEVEL', 'info'),

        // Canal de log Laravel à utiliser
        'channel' => env('AUTO_REMOTE_START_LOG_CHANNEL', 'daily'),

        // Logger tous les événements (même les skip)
        'log_all_events' => env('AUTO_REMOTE_START_LOG_ALL', false),

        // Conserver les logs pendant X jours
        'retention_days' => env('AUTO_REMOTE_START_LOG_RETENTION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring et alertes
    |--------------------------------------------------------------------------
    |
    | Configuration du monitoring du système de démarrage automatique.
    |
    */
    'monitoring' => [
        // Activer le monitoring
        'enabled' => env('AUTO_REMOTE_START_MONITORING', true),

        // Envoyer une alerte si le taux d'échec dépasse ce seuil (en %)
        'failure_rate_threshold' => env('AUTO_REMOTE_START_FAILURE_THRESHOLD', 50),

        // Période de calcul du taux d'échec (en heures)
        'failure_rate_period_hours' => env('AUTO_REMOTE_START_FAILURE_PERIOD', 24),

        // Email des administrateurs pour les alertes
        'alert_emails' => env('AUTO_REMOTE_START_ALERT_EMAILS', null), // Séparés par virgules
    ],

    /*
    |--------------------------------------------------------------------------
    | Mode de fonctionnement
    |--------------------------------------------------------------------------
    |
    | Différents modes de fonctionnement du système.
    |
    */
    'mode' => [
        // Mode de démarrage
        // - 'auto': Démarrage automatique basé sur start_time
        // - 'immediate': Démarrage immédiat dès paiement confirmé
        // - 'scheduled': Démarrage uniquement à l'heure prévue (strict)
        'start_mode' => env('AUTO_REMOTE_START_MODE', 'auto'),

        // Autoriser le démarrage anticipé (avant start_time avec grace period)
        'allow_early_start' => env('AUTO_REMOTE_START_ALLOW_EARLY', true),

        // Autoriser le démarrage tardif (après start_time avec start window)
        'allow_late_start' => env('AUTO_REMOTE_START_ALLOW_LATE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Conditions de démarrage
    |--------------------------------------------------------------------------
    |
    | Conditions qui doivent être remplies pour qu'un démarrage automatique
    | soit possible.
    |
    */
    'conditions' => [
        // Statuts de réservation éligibles
        'eligible_statuses' => ['confirmed', 'pending_confirmation'],

        // Statuts de paiement requis
        'required_payment_statuses' => ['PAID'],

        // Statuts de connecteur acceptables
        'acceptable_connector_statuses' => ['Available', 'AVAILABLE', 'Preparing', 'PREPARING'],

        // Vérifier que le charging point est online
        'require_online_charging_point' => env('AUTO_REMOTE_START_REQUIRE_ONLINE', true),

        // Vérifier que l'utilisateur n'a pas d'autre session active
        'prevent_concurrent_sessions' => env('AUTO_REMOTE_START_PREVENT_CONCURRENT', true),

        // Seuil de solde minimum pour postpaid (en devise de l'application)
        'minimum_balance_postpaid' => env('AUTO_REMOTE_START_MIN_BALANCE', 10.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gestion des échecs
    |--------------------------------------------------------------------------
    |
    | Configuration de la gestion des échecs et actions correctives.
    |
    */
    'failure_handling' => [
        // Action en cas d'échec définitif
        // - 'cancel': Annuler la réservation
        // - 'hold': Mettre en attente pour traitement manuel
        // - 'notify_only': Notifier uniquement, ne pas changer le statut
        'action_on_final_failure' => env('AUTO_REMOTE_START_FAILURE_ACTION', 'hold'),

        // Rembourser automatiquement en cas d'échec (mode prepaid)
        'auto_refund_on_failure' => env('AUTO_REMOTE_START_AUTO_REFUND', false),

        // Créer un ticket support en cas d'échec
        'create_support_ticket' => env('AUTO_REMOTE_START_CREATE_TICKET', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance et cache
    |--------------------------------------------------------------------------
    |
    | Optimisations de performance pour le système.
    |
    */
    'performance' => [
        // Activer le cache des validations de connecteur (en secondes)
        'cache_connector_status' => env('AUTO_REMOTE_START_CACHE_CONNECTOR', 60),

        // Activer le cache des validations de tag OCPP (en secondes)
        'cache_tag_validation' => env('AUTO_REMOTE_START_CACHE_TAG', 300),

        // Utiliser des transactions DB pour garantir la cohérence
        'use_db_transactions' => env('AUTO_REMOTE_START_USE_TRANSACTIONS', true),

        // Paralléliser les démarrages multiples (nécessite queue)
        'parallel_processing' => env('AUTO_REMOTE_START_PARALLEL', true),

        // Nombre max de jobs parallèles
        'max_parallel_jobs' => env('AUTO_REMOTE_START_MAX_PARALLEL', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Développement et débogage
    |--------------------------------------------------------------------------
    |
    | Options utiles pour le développement et le débogage.
    |
    */
    'development' => [
        // Mode dry-run: simulate sans exécuter
        'dry_run' => env('AUTO_REMOTE_START_DRY_RUN', false),

        // Forcer le démarrage même si conditions non remplies (DANGER: dev uniquement)
        'bypass_validation' => env('AUTO_REMOTE_START_BYPASS_VALIDATION', false),

        // Logger toutes les requêtes HTTP vers Steve
        'log_http_requests' => env('AUTO_REMOTE_START_LOG_HTTP', false),

        // Activer le mode verbose dans les logs
        'verbose_logging' => env('AUTO_REMOTE_START_VERBOSE', false),
    ],
];

