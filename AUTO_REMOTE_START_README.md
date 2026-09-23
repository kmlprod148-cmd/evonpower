# 🚗⚡ Système de Démarrage Automatique OCPP (Auto Remote Start)

## 📋 Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Utilisation](#utilisation)
6. [API Endpoints](#api-endpoints)
7. [Logique de Démarrage](#logique-de-démarrage)
8. [Enforcement](#enforcement)
9. [Monitoring](#monitoring)
10. [Dépannage](#dépannage)

---

## 🎯 Vue d'ensemble

Le système **Auto Remote Start** détecte et démarre automatiquement les transactions OCPP pour les réservations approuvées et payées, sans intervention manuelle.

### Fonctionnalités Clés

- ✅ **Démarrage automatique** basé sur l'heure de début de réservation
- ✅ **Validation complète** (tag OCPP, connecteur, solde utilisateur)
- ✅ **Retry automatique** en cas d'échec (3 tentatives par défaut)
- ✅ **Logging détaillé** pour audit et debugging
- ✅ **Monitoring en temps réel** via API et dashboard
- ✅ **Enforcement automatique** (solde, temps, kWh)
- ✅ **Notifications** aux utilisateurs (succès/échec)

---

## 🏗️ Architecture

### Composants Principaux

```
┌─────────────────────────────────────────────────────────────┐
│                     SYSTÈME AUTO REMOTE START                │
└─────────────────────────────────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
        ▼                     ▼                     ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   SCHEDULER  │    │    EVENTS    │    │   API REST   │
│  (Cron Job)  │    │  (Realtime)  │    │  (Manual)    │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                    │
       └───────────────────┼────────────────────┘
                           │
                           ▼
                ┌──────────────────────┐
                │  AutoRemoteStart     │
                │      Service         │
                └──────────┬───────────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  Validation  │  │ OCPP Command │  │   Logging    │
│   & Checks   │  │   (SteVe)    │  │  & Metrics   │
└──────────────┘  └──────────────┘  └──────────────┘
```

### Fichiers Créés

```
app/
├── Services/
│   └── AutoRemoteStartService.php         # Logique métier principale
├── Jobs/
│   └── AutoStartTransactionJob.php        # Job de traitement async
├── Console/Commands/
│   └── AutoStartTransactionsCommand.php   # Commande CLI
├── Http/Controllers/
│   └── AutoRemoteStartController.php      # API REST
├── Events/
│   └── ReservationApproved.php            # Événement custom
├── Listeners/
│   └── TriggerAutoRemoteStart.php         # Listener événements
└── Models/
    └── AutoRemoteStartLog.php             # Modèle logs

config/
└── auto-remote-start.php                  # Configuration

database/migrations/
└── 2025_01_01_000000_create_auto_remote_start_logs_table.php

routes/
└── auto-remote-start.php                  # Routes API
```

---

## 📦 Installation

### Étape 1: Exécuter la Migration

```bash
php artisan migrate
```

Cela créera la table `auto_remote_start_logs`.

### Étape 2: Configurer les Variables d'Environnement

Ajouter dans `.env`:

```env
# Auto Remote Start Configuration
AUTO_REMOTE_START_ENABLED=true
AUTO_REMOTE_START_WINDOW=15
AUTO_REMOTE_START_GRACE_PERIOD=5
AUTO_REMOTE_START_MAX_RETRIES=3
AUTO_REMOTE_START_RETRY_DELAY=30
AUTO_REMOTE_START_USE_QUEUE=true
AUTO_REMOTE_START_QUEUE=high
AUTO_REMOTE_START_SCHEDULER_FREQUENCY=everyTwoMinutes
AUTO_REMOTE_START_AUTO_APPROVE=true

# Notifications
AUTO_REMOTE_START_NOTIFY_SUCCESS=true
AUTO_REMOTE_START_NOTIFY_FAILURE=true
AUTO_REMOTE_START_NOTIFY_EMAIL=true
```

### Étape 3: Démarrer le Scheduler

Le scheduler Laravel doit être actif. Ajouter dans votre crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Étape 4: Démarrer la Queue (Important!)

```bash
# En production (avec supervisord)
php artisan queue:work --queue=high,default --tries=3 --timeout=120

# En développement
php artisan queue:listen --queue=high,default
```

### Étape 5: Vérifier l'Installation

```bash
# Health check
php artisan ocpp:auto-start-transactions --health

# Test avec dry-run
php artisan ocpp:auto-start-transactions --dry-run
```

---

## ⚙️ Configuration

Le fichier `config/auto-remote-start.php` contient toute la configuration.

### Paramètres Importants

#### Fenêtre de Démarrage

```php
'start_window_minutes' => 15,      // 15 min APRÈS l'heure prévue
'grace_period_minutes' => 5,       // 5 min AVANT l'heure prévue
```

**Exemple**: Si `start_time = 14:00`
- Démarrage possible entre **13:55** et **14:15**

#### Retry et Timeout

```php
'max_retry_attempts' => 3,         // 3 tentatives max
'retry_delay_seconds' => 30,       // 30s entre chaque tentative
'ocpp_timeout' => 30,              // 30s timeout OCPP
```

#### Mode de Fonctionnement

```php
'mode' => [
    'start_mode' => 'auto',        // 'auto', 'immediate', 'scheduled'
    'allow_early_start' => true,   // Autoriser démarrage anticipé
    'allow_late_start' => true,    // Autoriser démarrage tardif
],

#### Auto-approbation après paiement

```php
'auto_approve_on_payment' => true, // Paiement confirmé => réservation approuvée automatiquement
```
```

---

## 🚀 Utilisation

### 1. Démarrage Automatique (Scheduler)

Le scheduler s'exécute **toutes les 2 minutes** par défaut:

```bash
# Le scheduler appelle automatiquement:
php artisan ocpp:auto-start-transactions --queue
```

### 2. Démarrage Manuel (CLI)

```bash
# Traiter toutes les réservations éligibles
php artisan ocpp:auto-start-transactions

# Forcer une réservation spécifique
php artisan ocpp:auto-start-transactions --force-reservation=123

# Afficher les statistiques
php artisan ocpp:auto-start-transactions --stats

# Health check
php artisan ocpp:auto-start-transactions --health

# Dry-run (simulation)
php artisan ocpp:auto-start-transactions --dry-run
```

### 3. Via API

#### Traiter toutes les réservations

```bash
POST /api/auto-remote-start/process
Authorization: Bearer {token}
```

#### Traiter une réservation spécifique

```bash
POST /api/auto-remote-start/reservation/123
Authorization: Bearer {token}
Content-Type: application/json

{
  "force": false
}
```

#### Mettre en queue

```bash
POST /api/auto-remote-start/queue/123
Authorization: Bearer {token}
```

### 4. Via Events (Automatique)

Le système écoute automatiquement les événements:

```php
// Quand une réservation est approuvée
event(new ReservationApproved($reservation));

// Quand une réservation est créée (et déjà payée)
event(new ReservationCreated($reservation));
```

---

## 🔌 API Endpoints

Toutes les routes sont préfixées par `/api/auto-remote-start` et nécessitent une authentification Sanctum.

### Actions

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET/POST | `/process` | Traiter toutes les réservations éligibles |
| POST | `/reservation/{id}` | Traiter une réservation spécifique |
| POST | `/queue/{id}` | Mettre une réservation en queue |
| GET | `/check-eligibility/{id}` | Vérifier l'éligibilité |

### Monitoring

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/stats` | Statistiques globales |
| GET | `/dashboard` | Dashboard complet |
| GET | `/health` | Health check système |
| GET | `/logs` | Liste des logs (paginée) |
| GET | `/logs/{id}` | Détail d'un log |
| GET | `/eligible-reservations` | Réservations éligibles actuelles |

### Configuration

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/config` | Configuration actuelle (admin) |

---

## 🎯 Logique de Démarrage

### Endpoint OCPP Utilisé

```http
POST /api/v1/ocpp/remote-start
Content-Type: application/json

{
  "chargeBoxId": "CP-001",
  "connectorId": 1,
  "ocppTag": "USER-TAG-001"
}
```

### Safety Checks (Vérifications de Sécurité)

Avant chaque démarrage, le système vérifie:

#### ✅ 1. Réservation Approuvée

```php
$reservation->isApproved()
// status = 'confirmed' || 'active'
// payment_status = 'PAID'
```

#### ✅ 2. Heure Appropriée

```php
$now >= ($start_time - grace_period)
$now <= ($start_time + start_window)
```

#### ✅ 3. Point de Charge En Ligne

```php
$chargingPoint->isOnline()
// status = 'online'
```

#### ✅ 4. Connecteur Disponible

```php
$connector->status IN ['Available', 'AVAILABLE']
// Vérifié via API Steve
```

#### ✅ 5. Tag OCPP Valide

```php
$ocppTag = $reservation->getOcppTag();
// Tag existe
// Tag non bloqué (blocked = false)
// Tag non expiré
// Transactions actives < max_active
```

#### ✅ 6. Solde Utilisateur (Mode Postpaid)

```php
if ($reservation->isPostpaid()) {
    $user->balance >= $minimum_threshold
}
```

#### ✅ 7. Pas de Session Active

```php
!ChargingSession::where('reservation_id', $id)
    ->whereIn('status', ['active', 'in_progress'])
    ->exists()
```

### Processus de Démarrage

```
1. Vérifications d'éligibilité
   └─→ Non éligible → SKIP (logged)
   
2. Obtenir lock Redis (éviter doublons)
   └─→ Lock échoué → SKIP
   
3. Appeler Steve API
   POST /api/v1/ocpp/remote-start
   │
   ├─→ ACCEPTED
   │   ├─→ Créer ChargingSession
   │   ├─→ Mettre à jour Reservation (status='active')
   │   ├─→ Logger SUCCESS
   │   └─→ Notifier utilisateur
   │
   └─→ REJECTED ou Erreur
       ├─→ Retry (max 3 fois)
       ├─→ Logger FAILED
       └─→ Notifier échec
```

### Code de Démarrage Simplifié

```php
// Le StartChargingSessionJob est automatiquement appelé
// par AutoStartTransactionJob

public function startTransaction(Reservation $reservation)
{
    // 1. SAFETY CHECKS
    if (!$reservation->isApproved()) {
        throw new Exception('Reservation not approved');
    }
    
    if (!$reservation->user->hasSufficientBalance()) {
        throw new Exception('Insufficient balance');
    }
    
    // 2. Préparer les données
    $connector = $reservation->connector;
    $chargingPoint = $connector->chargingPoint;
    $chargeBoxId = $chargingPoint->charge_box_id;
    $ocppTag = $reservation->getOcppTag();
    
    if (!$ocppTag || $ocppTag->blocked) {
        throw new Exception('Invalid or blocked OCPP tag');
    }
    
    // 3. CALL STEVE
    $response = $steveClient->postJson('/api/v1/ocpp/remote-start', [
        'chargeBoxId' => $chargeBoxId,
        'connectorId' => $connector->connector_id,
        'ocppTag'     => $ocppTag->ocpp_tag,
    ]);
    
    // 4. Vérifier la réponse
    if ($response['status'] !== 'ACCEPTED') {
        throw new Exception('Remote start rejected: ' . $response['status']);
    }
    
    // 5. SAVE TRANSACTION
    $transaction = Transaction::create([
        'reservation_id' => $reservation->id,
        'steve_transaction_id' => $response['transaction']['id'] ?? null,
        'start_time' => now(),
        'start_meter' => $response['transaction']['startValue'] ?? 0,
    ]);
    
    // 6. Mettre à jour la réservation
    $reservation->update(['status' => 'active']);
    
    return $transaction;
}
```

---

## 🛡️ Enforcement (Balance / Time / kWh)

**⚠️ TRÈS IMPORTANT**: SteVe NE GÈRE PAS automatiquement l'arrêt basé sur le solde!

### Job d'Enforcement

Le système exécute **toutes les 1-2 minutes** un job qui vérifie:

```php
// Déjà implémenté dans: app/Console/Commands/EnforceChargingLimits.php

class EnforceChargingLimits extends Command
{
    public function handle()
    {
        $activeSessions = ChargingSession::active()->get();
        
        foreach ($activeSessions as $session) {
            // RÈGLE 1: Balance épuisée
            if ($session->user->balance <= 0) {
                $this->stopSession($session, 'Insufficient balance');
                continue;
            }
            
            // RÈGLE 2: Temps dépassé
            if ($session->reservation->end_time < now()) {
                $this->stopSession($session, 'Time limit reached');
                continue;
            }
            
            // RÈGLE 3: kWh dépassé
            $consumed = $this->getConsumedKwh($session);
            if ($consumed >= $session->reservation->max_kwh) {
                $this->stopSession($session, 'Energy limit reached');
                continue;
            }
        }
    }
    
    protected function stopSession($session, $reason)
    {
        // POST /api/v1/ocpp/remote-stop
        $this->steveClient->postQuery('/api/v1/ocpp/remote-stop', [
            'chargeBoxId' => $session->chargingPoint->charge_box_id
        ]);
        
        $session->update([
            'status' => 'stopped',
            'stopped_reason' => $reason
        ]);
    }
    
    protected function getConsumedKwh($session)
    {
        // Option 1: Via SteVe API
        $transaction = $this->steve->getTransaction($session->steve_transaction_id);
        $consumed = $transaction['current_meter'] - $transaction['start_meter'];
        
        // Option 2: Via callbacks (préféré)
        $consumed = $session->current_meter - $session->start_meter;
        
        return $consumed / 1000; // Wh → kWh
    }
}
```

### Configuration du Scheduler

```php
// app/Console/Kernel.php

$schedule->command('charging:enforce-limits')
    ->everyMinute()           // Toutes les minutes
    ->withoutOverlapping()    // Éviter les chevauchements
    ->runInBackground();      // Async
```

### Calcul du Coût

```php
$consumed_kwh = ($current_meter - $start_meter) / 1000;
$cost = $consumed_kwh * $tariff->price_per_kwh;

// Débiter progressivement
$user->debit($cost);
```

---

## 📊 Monitoring

### Dashboard API

```bash
GET /api/auto-remote-start/dashboard
```

Retourne:
- Statistiques 24h et 7 jours
- Health check du système
- Logs nécessitant une action manuelle
- Nombre de réservations éligibles

### Logs Détaillés

Chaque tentative est loggée dans `auto_remote_start_logs`:

```sql
SELECT 
    id,
    reservation_id,
    status,              -- success, failed, skipped, error
    message,
    attempt_number,
    processing_duration_ms,
    created_at
FROM auto_remote_start_logs
ORDER BY created_at DESC;
```

### Statistiques

```bash
# Via CLI
php artisan ocpp:auto-start-transactions --stats

# Via API
GET /api/auto-remote-start/stats?date_from=2025-01-01
```

### Health Check

```bash
# Via CLI
php artisan ocpp:auto-start-transactions --health

# Via API
GET /api/auto-remote-start/health
```

Vérifie:
- ✅ Service activé
- ✅ Connexion à SteVe API
- ✅ Table de logs accessible
- ✅ Réservations bloquées

---

## 🐛 Dépannage

### Problème: Aucune Réservation N'est Démarrée

**Vérifications**:

```bash
# 1. Vérifier que le service est activé
php artisan tinker
>>> config('auto-remote-start.enabled')

# 2. Vérifier le scheduler
php artisan schedule:list

# 3. Vérifier les réservations éligibles
GET /api/auto-remote-start/eligible-reservations

# 4. Vérifier les logs
php artisan ocpp:auto-start-transactions --stats
```

### Problème: Erreur "Tag OCPP Bloqué"

```bash
# Vérifier le tag dans SteVe
GET /api/v1/steve/ocppTags?idTag=USER-TAG-001

# Via service
php artisan tinker
>>> $service = app(\App\Services\OcppTagRemoteOperationsService::class);
>>> $service->validateOcppTag('USER-TAG-001');
```

### Problème: "Connecteur Non Disponible"

```bash
# Vérifier le statut du connecteur
GET /api/v1/steve/chargepoints/{chargeBoxId}/connectorStatus

# Forcer un refresh
php artisan steve:sync-charge-points
```

### Problème: Queue Ne Fonctionne Pas

```bash
# Vérifier la queue
php artisan queue:failed

# Nettoyer les jobs échoués
php artisan queue:flush

# Redémarrer le worker
php artisan queue:restart
```

### Logs de Debug

```bash
# Activer le mode verbose
AUTO_REMOTE_START_VERBOSE=true

# Consulter les logs Laravel
tail -f storage/logs/laravel.log | grep "AutoRemoteStart"

# Consulter les logs de la table
SELECT * FROM auto_remote_start_logs 
WHERE created_at > NOW() - INTERVAL 1 HOUR
ORDER BY created_at DESC;
```

---

## 📝 Tests

### Test Manuel Complet

```bash
# 1. Créer une réservation de test
php artisan tinker
>>> $reservation = Reservation::create([
    'user_id' => 1,
    'charging_point_id' => 1,
    'connector_id' => 1,
    'start_time' => now()->addMinutes(2),
    'end_time' => now()->addHours(1),
    'status' => 'confirmed',
    'payment_status' => 'PAID',
]);

# 2. Vérifier l'éligibilité
>>> $service = app(\App\Services\AutoRemoteStartService::class);
>>> $service->checkReservationEligibility($reservation);

# 3. Forcer le démarrage (bypass temps)
php artisan ocpp:auto-start-transactions --force-reservation={id}

# 4. Vérifier les logs
>>> AutoRemoteStartLog::recent(10);
```

### Test avec Dry-Run

```bash
php artisan ocpp:auto-start-transactions --dry-run
```

---

## 🔄 Workflow Complet

```
┌─────────────────────────────────────────────────────────┐
│  1. Utilisateur crée une réservation                    │
│     └─→ Paiement validé (payment_status = 'PAID')      │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  2. Event: ReservationApproved                          │
│     └─→ Listener: TriggerAutoRemoteStart               │
│         └─→ Si mode 'immediate': dispatch job           │
│         └─→ Sinon: scheduler le gérera                  │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  3. Scheduler (toutes les 2 minutes)                    │
│     └─→ AutoStartTransactionsCommand                    │
│         └─→ Récupère réservations éligibles             │
│         └─→ Dispatch AutoStartTransactionJob (queue)    │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  4. AutoStartTransactionJob                             │
│     └─→ AutoRemoteStartService::processReservation()   │
│         ├─→ Vérifications d'éligibilité                 │
│         ├─→ Appel Steve: POST /api/v1/ocpp/remote-start│
│         ├─→ Si ACCEPTED: créer Transaction              │
│         └─→ Logger le résultat                          │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  5. Session de charge active                            │
│     └─→ EnforceChargingLimits (toutes les minutes)     │
│         ├─→ Vérifier balance                            │
│         ├─→ Vérifier temps                              │
│         ├─→ Vérifier kWh                                │
│         └─→ Si limite atteinte: remote-stop             │
└─────────────────────────────────────────────────────────┘
```

---

## 📚 Ressources Additionnelles

- **SteVe API Documentation**: [API Docs](https://github.com/steve-community/steve)
- **OCPP 1.6 Specification**: [OCPP 1.6J](https://www.openchargealliance.org/protocols/ocpp-16/)
- **Laravel Scheduling**: [Laravel Docs](https://laravel.com/docs/scheduling)
- **Laravel Queues**: [Laravel Docs](https://laravel.com/docs/queues)

---

## 🆘 Support

Pour toute question ou problème:

1. Consulter les logs: `storage/logs/laravel.log`
2. Health check: `php artisan ocpp:auto-start-transactions --health`
3. Statistiques: `php artisan ocpp:auto-start-transactions --stats`
4. Documentation OCPP du projet: `ARCHITECTURE_OCPP_COMPLETE.md`

---

## ✅ Checklist de Mise en Production

- [ ] Migration exécutée: `php artisan migrate`
- [ ] Variables d'environnement configurées dans `.env`
- [ ] Scheduler configuré dans crontab
- [ ] Queue worker démarré (supervisord recommandé)
- [ ] Health check validé: `--health`
- [ ] Test avec une réservation: `--force-reservation=X`
- [ ] Monitoring configuré (logs, alertes)
- [ ] Enforcement job actif: `charging:enforce-limits`
- [ ] Documentation lue par l'équipe

---

**🎉 Votre système de démarrage automatique OCPP est maintenant opérationnel !**

