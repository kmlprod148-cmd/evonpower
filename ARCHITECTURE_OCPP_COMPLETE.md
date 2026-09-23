# Architecture OCPP/SteVe Complète - Documentation

## 📋 Vue d'ensemble

Cette documentation décrit l'architecture complète d'intégration OCPP/SteVe pour le système EVON.

### Principe fondamental

```
┌─────────────────────────────────────────────────────────────┐
│  Laravel = CERVEAU (Business Logic)                         │
│  - Gestion des réservations                                  │
│  - Approbations (admin/paiement)                            │
│  - Vérification des soldes                                   │
│  - Calcul des coûts                                          │
│  - Enforcement des limites                                   │
└─────────────────────────────────────────────────────────────┘
                            ↕
┌─────────────────────────────────────────────────────────────┐
│  SteVe = MUSCLE (OCPP Execution)                            │
│  - RemoteStartTransaction                                    │
│  - RemoteStopTransaction                                     │
│  - GetConnectorStatus                                        │
│  - UnlockConnector                                           │
└─────────────────────────────────────────────────────────────┘
```

**⚠️ CRITIQUE**: SteVe n'est PAS un moteur de facturation. Il ne gère PAS automatiquement:
- Les limites de temps
- Les limites kWh
- Les soldes utilisateurs
- Les arrêts automatiques

→ **C'est VOTRE responsabilité** dans Laravel!

---

## 🗄️ Structure de la Base de Données

### 1. Table `ocpp_tags`
Tags OCPP (idTag) pour l'authentification OCPP.

```sql
CREATE TABLE ocpp_tags (
    id BIGINT PRIMARY KEY,
    ocpp_tag VARCHAR(50) UNIQUE NOT NULL,
    user_id BIGINT REFERENCES users(id),
    blocked BOOLEAN DEFAULT FALSE,
    is_default BOOLEAN DEFAULT FALSE,
    parent_id_tag VARCHAR(50),
    expiry_date TIMESTAMP,
    note TEXT,
    total_sessions INT DEFAULT 0,
    last_used_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

**Indices**: `(user_id, blocked)`, `(ocpp_tag, blocked)`, `is_default`

### 2. Table `reservations` (champs OCPP ajoutés)

```sql
-- Nouveaux champs critiques
connector_id BIGINT REFERENCES connectors(id),
ocpp_tag_id BIGINT REFERENCES ocpp_tags(id),
payment_status ENUM('PENDING', 'PAID', 'FAILED', 'REFUNDED') DEFAULT 'PENDING',
max_kwh DECIMAL(10,2),
max_minutes INT,
approved_at TIMESTAMP,
approved_by BIGINT REFERENCES users(id)
```

### 3. Table `charging_sessions`
Sessions de charge actives.

```sql
CREATE TABLE charging_sessions (
    id BIGINT PRIMARY KEY,
    reservation_id BIGINT REFERENCES reservations(id),
    charging_point_id BIGINT REFERENCES charging_points(id),
    user_id BIGINT REFERENCES users(id),
    steve_transaction_id VARCHAR(255),
    connector_id INT,
    ocpp_tag VARCHAR(50),
    status ENUM('PENDING', 'RUNNING', 'COMPLETED', 'FAILED'),
    started_at TIMESTAMP,
    stopped_at TIMESTAMP,
    meter_start DECIMAL(12,2),
    meter_stop DECIMAL(12,2),
    actual_energy DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    actual_duration INT,
    stop_reason VARCHAR(255),
    payment_mode ENUM('prepaid', 'postpaid'),
    prepaid_amount DECIMAL(10,2),
    refund_amount DECIMAL(10,2),
    steve_response JSON,
    steve_stop_response JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 🔄 Cycle de Vie Complet d'une Réservation

### 1️⃣ Création de la Réservation

```php
$reservation = Reservation::create([
    'user_id' => $userId,
    'charging_point_id' => $chargingPointId,
    'connector_id' => $connectorId,
    'ocpp_tag_id' => $ocppTagId, // optionnel
    'pricing_plan_id' => $pricingPlanId,
    'start_time' => now()->addHours(2),
    'end_time' => now()->addHours(4),
    'max_kwh' => 20,
    'max_minutes' => 120,
    'estimated_cost' => 15.00,
    'status' => 'pending',
    'payment_status' => 'PENDING',
]);
```

### 2️⃣ Approbation (2 voies possibles)

#### A. Approbation Admin

```php
// Via ReservationApprovalController
public function approve(Reservation $reservation)
{
    $reservation->approve(auth()->user());
    
    if ($reservation->canStartNow()) {
        StartChargingSessionJob::dispatch($reservation->id);
    }
}
```

#### B. Approbation Automatique (Paiement Carte)

```php
// Après paiement réussi
$reservation->markAsPaid();

// Le système vérifie automatiquement si c'est l'heure de démarrer
```

### 3️⃣ Démarrage de la Session (Automatique via Scheduler)

**Job**: `StartChargingSessionJob`

```php
// Dispatché automatiquement à start_time
// OU manuellement si besoin

StartChargingSessionJob::dispatch($reservation->id);
```

**Processus du Job**:

1. ✅ Vérifications de sécurité (CRITICAL)
   - Status = APPROVED
   - Payment status = PAID
   - Balance suffisant
   - Connector disponible
   - Borne online
   - Tag OCPP actif

2. 🚀 Appel SteVe RemoteStart
   ```php
   $response = $ocppService->remoteStartCharging($reservation);
   ```

3. 📝 Création ChargingSession
   ```php
   ChargingSession::create([
       'reservation_id' => $reservation->id,
       'steve_transaction_id' => $steveResponse['transactionId'],
       'status' => 'RUNNING',
       'started_at' => now(),
       'meter_start' => $steveResponse['startValue'],
   ]);
   ```

4. ✅ Mise à jour Reservation
   ```php
   $reservation->update(['status' => 'active']);
   ```

### 4️⃣ Enforcement des Limites (CRITIQUE)

**Commande**: `charging:enforce-limits`
**Fréquence**: Toutes les 60 secondes (scheduler)

```bash
php artisan charging:enforce-limits
```

**Ce que fait la commande**:

1. Récupère toutes les sessions actives
2. Pour chaque session, vérifie:
   - ❌ Balance ≤ 0 → STOP
   - ❌ Durée ≥ max_minutes → STOP
   - ❌ kWh ≥ max_kwh → STOP
   - ❌ end_time atteinte → STOP

3. Dispatche `StopChargingSessionJob` si nécessaire

**Exemple de sortie**:

```
═══════════════════════════════════════════════
  Enforcement des Limites de Charge
═══════════════════════════════════════════════

📊 Sessions actives trouvées: 5

🔍 Vérification Session #142
   Utilisateur: Jean Dupont
   Borne: Borne 001
   Démarré: 2025-12-21 14:30:00
   ✅ OK - Continue
      Durée: 45 / 120 min
      Énergie: 8.5 / 20 kWh
      Solde: 25.00 €
      Coût estimé: 12.50 €

🔍 Vérification Session #143
   Utilisateur: Marie Martin
   ⚠️  DOIT ÊTRE ARRÊTÉE
      • Durée maximale atteinte (121 / 120 min)

🛑 Arrêt Session #143
   Raison: Durée maximale atteinte
   ✅ Job d'arrêt dispatché
```

### 5️⃣ Arrêt de la Session

**Job**: `StopChargingSessionJob`

```php
StopChargingSessionJob::dispatch($sessionId, $reason);
```

**Processus du Job**:

1. 🛑 Appel SteVe RemoteStop
   ```php
   $response = $ocppService->remoteStopCharging($session);
   ```

2. 💰 Calcul du coût final
   ```php
   $actualCost = $ocppService->calculateSessionCost($session);
   ```

3. 📝 Mise à jour Session
   ```php
   $session->update([
       'status' => 'COMPLETED',
       'stopped_at' => now(),
       'actual_energy' => $consumedKwh,
       'actual_cost' => $actualCost,
       'actual_duration' => $durationMinutes,
   ]);
   ```

4. 💳 Traitement du paiement
   - **Prepaid**: Remboursement si coût réel < montant prépayé
   - **Postpaid**: Débit du wallet

5. 🔓 Déverrouillage du connecteur (optionnel)
   ```php
   $ocppService->unlockConnector($connector);
   ```

---

## 🎯 Services Clés

### `OcppBusinessService`

Service central de logique métier OCPP.

#### Méthodes principales:

```php
// Vérifier si une réservation peut démarrer
canStartReservation(Reservation $reservation): array

// Calculer le coût minimum requis
calculateMinimumCost(Reservation $reservation): float

// Vérifier la disponibilité d'un connecteur
connectorIsAvailable(string $chargeBoxId, int $connectorId): bool

// Démarrer une session
remoteStartCharging(Reservation $reservation): array

// Arrêter une session
remoteStopCharging(ChargingSession $session): array

// Vérifier si une session doit être arrêtée
shouldStopSession(ChargingSession $session): array

// Calculer l'énergie consommée
calculateConsumedEnergy(ChargingSession $session): float

// Calculer le coût d'une session
calculateSessionCost(ChargingSession $session): float

// Déverrouiller un connecteur
unlockConnector(Connector $connector): bool
```

#### Exemple d'utilisation:

```php
use App\Services\OcppBusinessService;

$ocppService = app(OcppBusinessService::class);

// Vérifier avant de démarrer
$check = $ocppService->canStartReservation($reservation);

if ($check['can_start']) {
    $result = $ocppService->remoteStartCharging($reservation);
    
    if ($result['success']) {
        // Session démarrée!
    }
} else {
    // Afficher les erreurs
    dd($check['errors']);
}
```

### `SteVeApiEndpointService`

Service bas niveau pour les appels SteVe API.

#### Endpoints principaux:

```php
// Démarrer une transaction
remoteStartTransaction([
    'chargeBoxId' => 'CB001',
    'connectorId' => 1,
    'idTag' => 'USER123',
])

// Arrêter une transaction
remoteStopTransaction([
    'chargeBoxId' => 'CB001',
    'transactionId' => 456,
])

// Obtenir le statut des connecteurs
getConnectorStatus('CB001')

// Déverrouiller un connecteur
unlockConnector([
    'chargeBoxId' => 'CB001',
    'connectorId' => 1,
])
```

---

## 🔐 Sécurité et Validations

### Conditions pour Démarrer une Session

```php
✔ Reservation status = 'confirmed' OU 'active'
✔ Payment status = 'PAID'
✔ User balance ≥ minimum cost
✔ Connector status = 'Available'
✔ Charge point status != 'offline'
✔ OCPP Tag is active (not blocked, not expired)
✔ start_time ≤ now() + 5 minutes
✔ end_time > now()
```

### Calcul du Coût Minimum

**Stratégie**: Éviter les micro-sessions et l'abus.

```php
public function calculateMinimumCost(Reservation $reservation): float
{
    $pricePerKwh = $reservation->pricingPlan->price_per_kwh ?? 0.30;
    $pricePerMinute = $reservation->pricingPlan->price_per_minute ?? 0.05;

    $costByKwh = ($reservation->max_kwh ?? 2) * $pricePerKwh;
    $costByMinutes = ($reservation->max_minutes ?? 10) * $pricePerMinute;

    // Minimum: 10 minutes OU 2 kWh (le plus petit)
    $minimumCost = min($costByKwh, $costByMinutes);

    return max($minimumCost, 1.0); // Au moins 1€
}
```

---

## ⏰ Configuration du Scheduler

**Fichier**: `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // OCPP Enforcement - CRITIQUE
    $schedule->command('charging:enforce-limits')
        ->everyMinute()
        ->withoutOverlapping()
        ->runInBackground()
        ->onSuccess(function () {
            \Log::debug('EnforceChargingLimits scheduled successfully');
        })
        ->onFailure(function () {
            \Log::error('EnforceChargingLimits scheduled failed');
        });
}
```

**Démarrer le scheduler en production**:

```bash
# Cron job
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

**Démarrer la queue (pour les Jobs)**:

```bash
php artisan queue:work --tries=3 --timeout=120
```

---

## 🧪 Tests et Débogage

### Test Manual d'une Réservation

```bash
# 1. Créer une réservation de test
php artisan tinker
>>> $reservation = Reservation::find(1);

# 2. Vérifier si elle peut démarrer
>>> $ocpp = app(\App\Services\OcppBusinessService::class);
>>> $check = $ocpp->canStartReservation($reservation);
>>> dump($check);

# 3. Démarrer manuellement
>>> dispatch(new \App\Jobs\StartChargingSessionJob($reservation->id));

# 4. Vérifier le statut
>>> $reservation->refresh();
>>> dump($reservation->status);

# 5. Forcer l'arrêt
>>> $session = $reservation->chargingSessions()->first();
>>> dispatch(new \App\Jobs\StopChargingSessionJob($session->id, 'Test'));
```

### Test de l'Enforcement

```bash
# Mode dry-run (ne fait rien, affiche seulement)
php artisan charging:enforce-limits --dry-run

# Test d'une session spécifique
php artisan charging:enforce-limits --session=142

# Exécution normale
php artisan charging:enforce-limits
```

### Logs Importants

```bash
# Logs généraux
tail -f storage/logs/laravel.log | grep -i "ocpp\|steve\|charging"

# Logs spécifiques aux jobs
tail -f storage/logs/laravel.log | grep -i "StartChargingSessionJob\|StopChargingSessionJob"

# Logs enforcement
tail -f storage/logs/laravel.log | grep -i "EnforceChargingLimits"
```

---

## 📊 Flux de Données Complet (Diagramme)

```
┌──────────────────┐
│ 1. CLIENT BOOKING│
│  (Web/Mobile)    │
└────────┬─────────┘
         │
         ▼
┌────────────────────────────────┐
│ 2. RESERVATION CREATED         │
│  - status: pending             │
│  - payment_status: PENDING     │
└────────┬───────────────────────┘
         │
         ├─────► Admin Approval ────────┐
         │                              │
         └─────► Payment (Card) ────────┤
                                        │
                                        ▼
                        ┌───────────────────────────┐
                        │ 3. RESERVATION APPROVED   │
                        │  - status: confirmed      │
                        │  - payment_status: PAID   │
                        └────────┬──────────────────┘
                                 │
                                 ▼
                    ┌────────────────────────────────┐
                    │ 4. START_TIME REACHED          │
                    │  → StartChargingSessionJob     │
                    └────────┬───────────────────────┘
                             │
                             ▼
                ┌─────────────────────────────────────┐
                │ 5. SAFETY CHECKS                    │
                │  ✔ Payment OK                       │
                │  ✔ Balance OK                       │
                │  ✔ Connector Available              │
                │  ✔ Tag Active                       │
                └────────┬────────────────────────────┘
                         │
                         ▼
            ┌────────────────────────────────────────┐
            │ 6. CALL STEVE REMOTE START             │
            │  POST /api/v1/ocpp/remote-start        │
            │  {                                     │
            │    chargeBoxId: "CB001",               │
            │    connectorId: 1,                     │
            │    idTag: "USER123"                    │
            │  }                                     │
            └────────┬───────────────────────────────┘
                     │
                     ▼
        ┌────────────────────────────────────────────┐
        │ 7. SESSION RUNNING                         │
        │  - ChargingSession created                 │
        │  - steve_transaction_id saved              │
        │  - status: RUNNING                         │
        └────────┬───────────────────────────────────┘
                 │
                 │◄───────────────────────────────────┐
                 │  ENFORCEMENT (every 60s)           │
                 │  charging:enforce-limits           │
                 │                                    │
                 │  Checks:                           │
                 │  ❌ Balance ≤ 0?                   │
                 │  ❌ Duration ≥ max_minutes?        │
                 │  ❌ kWh ≥ max_kwh?                 │
                 │  ❌ end_time reached?              │
                 │                                    │
                 │  If YES → dispatch StopJob ────────┤
                 │                                    │
                 └────────┬───────────────────────────┘
                          │
                          ▼
             ┌────────────────────────────────────────┐
             │ 8. STOP SESSION                        │
             │  → StopChargingSessionJob              │
             └────────┬───────────────────────────────┘
                      │
                      ▼
         ┌────────────────────────────────────────────┐
         │ 9. CALL STEVE REMOTE STOP                  │
         │  POST /api/v1/ocpp/remote-stop             │
         │  { chargeBoxId: "CB001" }                  │
         └────────┬───────────────────────────────────┘
                  │
                  ▼
     ┌────────────────────────────────────────────────┐
     │ 10. FINALIZE SESSION                           │
     │  - Calculate actual cost                       │
     │  - Debit/Refund wallet                         │
     │  - Update session: COMPLETED                   │
     │  - Update reservation: completed               │
     │  - Unlock connector                            │
     └────────────────────────────────────────────────┘
```

---

## 🚀 Démarrage en Production

### 1. Migrations

```bash
php artisan migrate
```

### 2. Configuration SteVe

**Fichier**: `config/steve.php`

```php
return [
    'api_url' => env('STEVE_API_URL', 'http://localhost:8180'),
    'username' => env('STEVE_USERNAME', 'admin'),
    'password' => env('STEVE_PASSWORD', ''),
    'timeout' => env('STEVE_TIMEOUT', 30),
    'retry_attempts' => env('STEVE_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('STEVE_RETRY_DELAY', 1000),
];
```

**Fichier**: `.env`

```env
STEVE_API_URL=http://your-steve-server:8180
STEVE_USERNAME=admin
STEVE_PASSWORD=your-secure-password
```

### 3. Démarrer les Services

```bash
# Queue worker
php artisan queue:work --daemon --tries=3 --timeout=120

# Scheduler (via cron)
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### 4. Test de Connexion SteVe

```bash
php artisan tinker
>>> $steve = app(\App\Services\SteVeApiEndpointService::class);
>>> $response = $steve->getChargePoints();
>>> dump($response);
```

---

## 🆘 Troubleshooting

### Problème: Session ne démarre pas

**Vérifications**:

```bash
# 1. Vérifier les conditions
php artisan tinker
>>> $reservation = Reservation::find(X);
>>> $ocpp = app(\App\Services\OcppBusinessService::class);
>>> $check = $ocpp->canStartReservation($reservation);
>>> dump($check);

# 2. Vérifier SteVe
>>> $steve = app(\App\Services\SteVeApiEndpointService::class);
>>> $status = $steve->getConnectorStatus($chargeBoxId);
>>> dump($status);

# 3. Vérifier les logs
tail -f storage/logs/laravel.log | grep -i "StartChargingSessionJob"
```

### Problème: Session ne s'arrête pas automatiquement

**Vérifications**:

```bash
# 1. Vérifier que le scheduler tourne
ps aux | grep "schedule:run"

# 2. Tester l'enforcement manuellement
php artisan charging:enforce-limits --dry-run

# 3. Vérifier les logs
tail -f storage/logs/laravel.log | grep -i "EnforceChargingLimits"
```

### Problème: SteVe retourne une erreur

**Erreurs courantes**:

- `ChargeBox not found` → Vérifier le chargeBoxId/serial_number
- `Connector not available` → Vérifier le statut du connecteur
- `IdTag not found` → Créer le tag dans SteVe d'abord
- `Transaction already active` → Arrêter la transaction précédente

---

## 📝 Checklist de Déploiement

- [ ] Migrations exécutées
- [ ] Configuration SteVe dans `.env`
- [ ] Test de connexion SteVe OK
- [ ] Queue worker démarré
- [ ] Scheduler configuré (cron)
- [ ] Logs accessibles
- [ ] Permissions configurées (Gates/Policies)
- [ ] OCPP Tags créés pour les utilisateurs de test
- [ ] Bornes enregistrées dans SteVe
- [ ] Test d'une réservation complète OK

---

## 📚 Ressources

- [Spécification OCPP 1.6](https://www.openchargealliance.org/protocols/ocpp-16/)
- [SteVe Documentation](https://github.com/steve-community/steve)
- [Laravel Jobs & Queues](https://laravel.com/docs/queues)
- [Laravel Task Scheduling](https://laravel.com/docs/scheduling)

---

**Dernière mise à jour**: 21 décembre 2025
**Version**: 1.0.0
**Auteur**: EVON Development Team

