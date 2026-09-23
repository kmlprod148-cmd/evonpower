# 📊 Analyse Complète : Gestion du Statut des Points de Charge basée sur les Connecteurs

## 🎯 Vue d'ensemble

Ce document analyse en profondeur la logique de récupération et de gestion du statut des points de charge (Charging Points) basée sur le statut des connecteurs dans votre application Laravel EVON.

---

## 📐 Architecture du Système

### 1. **Modèles de Données**

#### ChargingPoint (`app/Models/ChargingPoint.php`)
- **Champs clés pour le statut:**
  - `status` : Statut actuel ('online', 'offline', 'maintenance', 'charging', 'reserved', 'error')
  - `steve_charging_point_id` : ID du point de charge dans l'API Steve/OCPP
  - `steve_connection_status` : JSON du statut de connexion Steve (array cast)
  - `status_updated_at` : Timestamp de la dernière mise à jour du statut
  - `last_connection_attempt` : Dernière tentative de connexion

- **Relations importantes:**
  ```php
  public function connectors()  // HasMany Connector
  public function transactions()  // HasMany Transaction
  public function chargingSessions()  // HasMany ChargingSession
  ```

- **Méthodes de statut:**
  ```php
  isOnline(): bool  // Vérifie si status === 'online'
  hasSteveId(): bool  // Vérifie la présence d'un steve_charging_point_id
  updateStatus($newStatus, $reason = null)  // Met à jour et log le changement
  ```

#### Connector (`app/Models/Connector.php`)
- **Champs clés:**
  - `charging_point_id` : FK vers ChargingPoint
  - `connector_id` : ID du connecteur (1, 2, etc.)
  - `type` : Type de connecteur (Type 2, CCS, CHAdeMO, etc.)
  - `status` : Statut du connecteur
  - `power` : Puissance en kW
  - `format` : Format (Socket/Cable)

- **Relations:**
  ```php
  chargingPoint(): BelongsTo ChargingPoint
  ```

---

## 🔄 Flux de Récupération du Statut

### 2. **Service Principal : SteveService**

**Fichier:** `app/Services/SteveService.php`

#### Méthode Centrale : `getConnectorStatus()`

```php
public function getConnectorStatus(string $steveId, float $timeout = 8.0): array
```

**Logique:**
1. Vérifie que l'URL de base Steve est configurée
2. Essaie plusieurs endpoints dans l'ordre :
   - `/connectors/status/{steveId}`
   - `/charge-points/{steveId}/connectors/status`
   - `/charge-points/{steveId}/status`
3. Retourne :
   ```php
   [
       'ok' => true/false,
       'status' => HTTP status code,
       'body' => array (données JSON),
       'raw' => string (réponse brute),
       'endpoint' => string (endpoint utilisé),
       'error' => string (si échec)
   ]
   ```

#### Autres Méthodes Importantes

**`status(ChargingPoint $chargingPoint)`**
- Vérifie le statut via OCPP si disponible
- Fallback vers `getConnectorStatus()` avec l'API REST
- Retourne `['ok' => bool, 'message' => string]`

**`testConnection(ChargingPoint $chargingPoint)`**
- Teste la connexion OCPP ou REST
- Retourne `['success' => bool, 'data' => array, 'message' => string]`

---

## 🎛️ Contrôleur de Statut

### 3. **ChargingPointStatusController**

**Fichier:** `app/Http/Controllers/ChargingPointStatusController.php`

#### Endpoint Principal : `GET /charging-points/{id}/status`

**Flux de traitement :**

```
1. Récupérer le ChargingPoint
   ↓
2. Vérifier si steve_charging_point_id existe
   ↓ NON → Retourner status actuel avec ok=false
   ↓ OUI
3. Vérifier le cache (30 secondes)
   ↓ CACHE EXISTS → Retourner cache
   ↓ CACHE MISS
4. Appeler steve->getChargingPoint(steveId)
   ↓ SUCCESS → extractRealStatusFromResponse()
   ↓ FAIL
5. Fallback: steve->getConnectorStatus(steveId)
   ↓ SUCCESS → deriveOnlineFromConnectorBody()
   ↓ FAIL
6. Utiliser le statut actuel ou 'unknown'
   ↓
7. Mettre à jour la DB (status, status_updated_at)
   ↓
8. Mettre en cache (30 secondes)
   ↓
9. Retourner JSON
```

#### 🔍 Méthodes d'Analyse de Statut

##### `extractRealStatusFromResponse(array $body): string`

**Priorité de vérification :**

1. **Champs de statut directs** (essaie dans l'ordre):
   - `status`
   - `availabilityStatus` / `availability_status`
   - `connectionStatus` / `connection_status`
   - `state`
   - `availability`

2. **Mapping des valeurs** vers les statuts de l'application:

   | Valeur API Steve | Statut Application |
   |------------------|-------------------|
   | available, online, connected, active, ready, idle | **online** |
   | unavailable, offline, disconnected, inactive, faulted | **offline** |
   | preparing, charging, finishing, occupied | **online** |
   | maintenance, suspendedevse, suspendedev | **maintenance** |

3. **Vérification des connecteurs** (si `connectors` array existe):
   - Parcourt tous les connecteurs
   - Si au moins un connecteur est `available`, `preparing`, `charging`, ou `finishing` → **online**
   - Si tous sont `unavailable` ou `faulted` → **offline**

4. **Vérification du timestamp `lastSeen`**:
   - Parse `lastSeen` ou `last_seen`
   - Si vu dans les **5 dernières minutes** → **online**
   - Sinon → **offline**

5. **Vérification des flags booléens**:
   - `connected === true` → **online**
   - `isOnline === true` → **online**

6. **Fallback final**: Appelle `deriveOnlineFromChargingPointBody()`

##### `deriveOnlineFromConnectorBody(array $body): bool`

**Logique :**

```php
1. Vérifier array 'connectors'
   - Pour chaque connecteur:
     - Si status IN ['AVAILABLE', 'CHARGING', 'IN_USE', 'OCCUPIED', 'ACTIVE', 'ONLINE']
       → return true

2. Vérifier 'status' global
   - Si status IN ['ONLINE', 'CONNECTED', 'ACTIVE', 'AVAILABLE']
     → return true

3. Fallback: return false
```

##### `deriveOnlineFromChargingPointBody($body): bool`

**Logique :**

```php
1. Vérifier champ 'status' ou 'availability'
   - Si IN ['ONLINE', 'CONNECTED', 'ACTIVE', 'AVAILABLE']
     → return true

2. Parser 'lastSeen' / 'last_seen'
   - Si lastSeen > (now - 5 minutes)
     → return true

3. Vérifier flag 'connected'
   - Si === true
     → return true

4. Fallback: return false
```

---

## 🤖 Monitoring Automatique

### 4. **Commande Artisan : MonitorSteveChargingPoints**

**Fichier:** `app/Console/Commands/MonitorSteveChargingPoints.php`

#### Commande : `php artisan steve:monitor`

**Options :**
- `--limit=N` : Limiter le nombre de bornes à traiter
- `--batch=N` : Taille des batches (défaut: 10)

**Flux de traitement :**

```
1. Récupérer tous les ChargingPoints avec steve_charging_point_id
   ↓
2. Pour chaque batch de bornes:
   ↓
3. Pour chaque borne:
   a. Appeler steve->getConnectorStatus(steveId)
   b. Si succès: deriveOnlineFromConnectorBody()
   c. Si échec: Fallback vers steve->getChargingPoint(steveId)
   d. Si échec total: garder le statut actuel (failed++)
   e. Si statut changé: Mettre à jour status + status_updated_at
   f. Si statut identique: Mettre à jour uniquement status_updated_at
   ↓
4. Pause de 0.5s entre les batches (éviter surcharge API)
   ↓
5. Afficher rapport: X mises à jour, Y erreurs
```

**Méthodes de dérivation :**
- `deriveOnlineFromConnectorBody()`
- `deriveOnlineFromChargingPointBody()`

(Même logique que dans le contrôleur)

---

## 🔗 Intégration dans les Services Métier

### 5. **ChargingSessionManager**

**Fichier:** `app/Services/ChargingSessionManager.php`

#### Méthode : `isConnectorAvailable()`

```php
protected function isConnectorAvailable(string $chargeBoxId, int $connectorId): array
```

**Utilisation dans les réservations :**

```
1. Avant de démarrer une session de charge:
   ↓
2. Récupérer chargeBoxId et connectorId de la réservation
   ↓
3. Appeler steveClient->getJson('/api/v1/connectors/status', ['chargeBoxId' => $chargeBoxId])
   ↓
4. Parser la réponse JSON pour vérifier la disponibilité
   ↓
5. Retourner:
   [
       'ok' => bool,
       'available' => bool,
       'message' => string,
       'steve' => ['status', 'url']
   ]
```

### 6. **ImmediateStartService**

**Fichier:** `app/Services/ImmediateStartService.php`

#### Méthode : `getImmediateStartStatus()`

```php
public function getImmediateStartStatus(ChargingPoint $chargingPoint): array
```

**Logique :**

1. Vérifie la disponibilité du point de charge
2. Cherche une session active (status 'charging' ou 'starting')
3. Récupère le statut de connexion Steve via `getSteveConnectionStatus()`
4. Retourne un objet complet :
   ```php
   [
       'available' => bool,
       'status' => string,
       'active_session' => array|null,
       'steve_connection' => [
           'connected' => bool,
           'message' => string
       ]
   ]
   ```

### 7. **AutomaticChargerConnectionService**

**Fichier:** `app/Services/AutomaticChargerConnectionService.php`

#### Méthode : `getChargerConnectionStatus()`

**Avec système de cache :**

```
1. Vérifier le cache (clé: charger_connection_{id})
   ↓ CACHE HIT → Retourner cache + cached=true
   ↓ CACHE MISS
2. Appeler steveApiService->getChargerStatus(chargerId)
   ↓
3. Analyser via isStatusConnected()
   ↓
4. Mettre en cache (TTL configuré)
   ↓
5. Retourner status + cached=false
```

**Avantages du cache :**
- Réduit la charge sur l'API Steve
- Améliore les performances
- TTL configurable

---

## 📊 Mapping des Statuts OCPP vers Application

### 8. **Correspondance des Statuts**

#### Statuts OCPP Standard (Connecteurs)

| Statut OCPP | Signification | Statut Application |
|-------------|---------------|-------------------|
| **Available** | Disponible pour une charge | online |
| **Preparing** | En préparation | online |
| **Charging** | En charge | online |
| **SuspendedEVSE** | Suspendu côté borne | maintenance |
| **SuspendedEV** | Suspendu côté véhicule | maintenance |
| **Finishing** | Fin de charge | online |
| **Reserved** | Réservé | reserved |
| **Unavailable** | Non disponible | offline |
| **Faulted** | En défaut | error |

#### Statuts Point de Charge

| Valeur API | Description | Statut Final |
|------------|-------------|--------------|
| online, connected, active | Connecté et opérationnel | online |
| available, ready, idle | Disponible | online |
| offline, disconnected | Hors ligne | offline |
| inactive, unavailable | Non disponible | offline |
| faulted | En erreur | error |
| maintenance | En maintenance | maintenance |
| preparing, charging, finishing | En utilisation | online |
| occupied | Occupé | online |

---

## ⚙️ Configuration

### 9. **Variables d'Environnement**

**Fichier `.env` :**

```env
# Configuration Steve API
SERVICES_STEVE_URL=https://your-steve-server.com/api
SERVICES_STEVE_USERNAME=admin
SERVICES_STEVE_PASSWORD=your_password
SERVICES_STEVE_TIMEOUT=8.0

# Configuration OCPP
OCPP_ENABLED=true
OCPP_WEBSOCKET_URL=ws://your-steve-server.com:8180/websocket
```

**Fichier de config:** `config/services.php`

```php
'steve' => [
    'url' => env('SERVICES_STEVE_URL'),
    'username' => env('SERVICES_STEVE_USERNAME'),
    'password' => env('SERVICES_STEVE_PASSWORD'),
    'timeout' => env('SERVICES_STEVE_TIMEOUT', 8.0),
],
```

---

## 🔄 Stratégies de Mise à Jour

### 10. **Approches de Synchronisation**

#### ✅ Approche 1 : **Pull On-Demand** (Actuelle)

**Avantages :**
- Données fraîches à chaque requête
- Pas de consommation de ressources en arrière-plan
- Simple à maintenir

**Inconvénients :**
- Latence sur les requêtes utilisateur
- Charge sur l'API Steve lors de pics de trafic

**Implémentation :**
- `ChargingPointStatusController::status()`
- Cache de 30 secondes
- Force refresh avec paramètre `?force=1`

#### ✅ Approche 2 : **Scheduled Monitoring** (Disponible)

**Avantages :**
- Pas d'impact sur la latence utilisateur
- Contrôle de la charge API (batches + pauses)
- Historique de statuts

**Inconvénients :**
- Données potentiellement obsolètes entre les runs
- Consommation de ressources système

**Implémentation :**
- Commande : `php artisan steve:monitor`
- Scheduler dans `app/Console/Kernel.php`:
  ```php
  protected function schedule(Schedule $schedule)
  {
      $schedule->command('steve:monitor --batch=10')
               ->everyFiveMinutes()
               ->withoutOverlapping()
               ->onOneServer();
  }
  ```

#### 💡 Approche 3 : **Hybrid** (Recommandée)

**Combinaison des deux :**

1. **Monitoring planifié** toutes les 5-10 minutes
   - Mise à jour de masse des statuts
   - Détection des changements
   - Pas de cache

2. **Pull on-demand** avec cache étendu (5 minutes)
   - Utilise le statut de la DB (mis à jour par le monitoring)
   - Rafraîchissement manuel possible (`?force=1`)
   - Latence minimale

**Configuration suggérée :**

```php
// Dans ChargingPointStatusController::status()

// Cache de 5 minutes au lieu de 30 secondes
Cache::put($cacheKey, $response, now()->addMinutes(5));

// Scheduler
$schedule->command('steve:monitor --batch=20')
         ->everyFiveMinutes()
         ->withoutOverlapping()
         ->onOneServer()
         ->runInBackground();
```

---

## 🧪 Exemples d'Usage

### 11. **Scénarios Pratiques**

#### Scénario 1 : Vérifier le statut d'une borne

**API REST:**
```bash
curl -X GET https://your-app.com/api/charging-points/123/status
```

**Réponse:**
```json
{
  "ok": true,
  "status": "online",
  "updated_at": "2024-12-21T15:30:00Z"
}
```

**Dans le code:**
```php
$chargingPoint = ChargingPoint::find(123);
$isOnline = $chargingPoint->isOnline(); // bool
$status = $chargingPoint->status; // 'online', 'offline', etc.
```

#### Scénario 2 : Vérifier avant de démarrer une session

```php
use App\Services\ImmediateStartService;

$service = app(ImmediateStartService::class);
$statusInfo = $service->getImmediateStartStatus($chargingPoint);

if ($statusInfo['available']) {
    // Démarrer la session
    $session = $service->startImmediateSession($chargingPoint, $user);
} else {
    // Afficher message d'erreur
    $message = $statusInfo['steve_connection']['message'];
}
```

#### Scénario 3 : Monitoring manuel d'urgence

```bash
# Vérifier toutes les bornes immédiatement
php artisan steve:monitor --limit=100 --batch=5

# Output:
# Démarrage du monitoring des bornes Steve...
# Traitement de 100 borne(s)...
# [========================================] 100/100
# Terminé: 45 mise(s) à jour, 2 erreur(s)
```

#### Scénario 4 : Filtrer les bornes par statut

**Dans un contrôleur:**
```php
// Toutes les bornes en ligne
$onlinePoints = ChargingPoint::online()->get();

// Bornes par statut spécifique
$maintenancePoints = ChargingPoint::byStatus('maintenance')->get();

// Bornes en ligne avec connecteurs chargés
$points = ChargingPoint::online()
    ->with('connectors')
    ->get();
```

**Dans une vue:**
```blade
@foreach($chargingPoints as $point)
    <div class="charging-point">
        <h3>{{ $point->name }}</h3>
        <span class="badge badge-{{ $point->status_color }}">
            {{ ucfirst($point->status) }}
        </span>
        
        <div class="connectors">
            @foreach($point->connectors as $connector)
                <span>Connector #{{ $connector->connector_id }}: 
                    {{ $connector->status }}
                </span>
            @endforeach
        </div>
    </div>
@endforeach
```

---

## 🛠️ Recommandations d'Amélioration

### 12. **Optimisations Possibles**

#### 🚀 Performance

1. **Eager Loading systématique**
   ```php
   $points = ChargingPoint::with(['connectors', 'station', 'group'])
       ->online()
       ->get();
   ```

2. **Index de base de données**
   ```php
   Schema::table('charging_points', function (Blueprint $table) {
       $table->index('status');
       $table->index('steve_charging_point_id');
       $table->index(['status', 'status_updated_at']);
   });
   ```

3. **Cache Redis pour les statuts**
   ```php
   // Dans config/cache.php
   'stores' => [
       'charging_status' => [
           'driver' => 'redis',
           'connection' => 'cache',
           'lock_connection' => 'default',
       ],
   ],
   ```

#### 📊 Fonctionnalités

1. **WebSocket pour les mises à jour en temps réel**
   - Broadcaster Laravel avec Pusher/Socket.io
   - Event `ChargingPointStatusChanged`
   - Listener côté frontend

2. **Historique des statuts**
   ```php
   Schema::create('charging_point_status_history', function (Blueprint $table) {
       $table->id();
       $table->foreignId('charging_point_id');
       $table->string('old_status');
       $table->string('new_status');
       $table->text('reason')->nullable();
       $table->timestamp('changed_at');
       $table->index(['charging_point_id', 'changed_at']);
   });
   ```

3. **Méthode unifiée dans le modèle ChargingPoint**
   ```php
   public function refreshStatusFromConnectors(): string
   {
       $steveService = app(SteveService::class);
       $res = $steveService->getConnectorStatus($this->steve_charging_point_id);
       
       if ($res['ok'] && is_array($res['body'])) {
           $online = $this->deriveOnlineFromConnectorBody($res['body']);
           $newStatus = $online ? 'online' : 'offline';
           
           if ($this->status !== $newStatus) {
               $this->updateStatus($newStatus, 'Auto-refresh from connectors');
           }
           
           return $newStatus;
       }
       
       return $this->status;
   }
   ```

#### 🔒 Sécurité et Fiabilité

1. **Rate Limiting sur l'API Steve**
   ```php
   RateLimiter::for('steve-api', function (Request $request) {
       return Limit::perMinute(60)->by('steve-api');
   });
   ```

2. **Retry avec Exponential Backoff**
   ```php
   use Illuminate\Support\Facades\Http;
   
   $response = Http::retry(3, 100, function ($exception, $request) {
       return $exception instanceof ConnectionException;
   })->get($url);
   ```

3. **Health Check endpoint**
   ```php
   // Route: GET /api/health/steve
   public function steveHealth()
   {
       $testPoint = ChargingPoint::whereNotNull('steve_charging_point_id')
           ->first();
       
       if (!$testPoint) {
           return response()->json(['status' => 'no_test_data'], 200);
       }
       
       $steve = app(SteveService::class);
       $result = $steve->testConnection($testPoint);
       
       return response()->json([
           'status' => $result['success'] ? 'healthy' : 'unhealthy',
           'timestamp' => now(),
           'details' => $result
       ]);
   }
   ```

---

## 📈 Métriques et Monitoring

### 13. **KPIs à Surveiller**

1. **Disponibilité des bornes**
   ```sql
   SELECT 
       status,
       COUNT(*) as count,
       ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM charging_points), 2) as percentage
   FROM charging_points
   WHERE steve_charging_point_id IS NOT NULL
   GROUP BY status;
   ```

2. **Temps de réponse API Steve**
   - Ajouter des logs de timing
   - Alertes si > 5 secondes

3. **Taux d'échec de synchronisation**
   - Nombre de `failed` dans MonitorSteveChargingPoints
   - Alertes si > 10%

4. **Dashboard de statuts en temps réel**
   - Total bornes : X
   - Online : Y (Z%)
   - Offline : ...
   - En charge : ...
   - En erreur : ...

---

## 🎓 Résumé pour Développeur Senior

### 14. **Points Clés**

✅ **Architecture actuelle** : Solide et bien structurée
- Séparation claire des responsabilités (Models, Services, Controllers)
- Système de fallback robuste (OCPP → REST API)
- Cache intelligent (30s)

✅ **Forces** :
- Support multi-endpoints Steve (flexibilité)
- Mapping complet des statuts OCPP
- Commande de monitoring automatique
- Logs détaillés pour debugging

⚠️ **Points d'attention** :
- Pas d'historique des changements de statut
- Cache court (30s) peut causer des pics de requêtes
- Pas de retry automatique sur les échecs API
- Pas de WebSocket pour temps réel

💡 **Quick Wins** :
1. Augmenter le cache à 5 minutes
2. Ajouter un scheduler pour `steve:monitor` toutes les 5 minutes
3. Créer un index sur `(status, status_updated_at)`
4. Ajouter retry avec backoff sur les appels API

🚀 **Évolutions à moyen terme** :
1. Table `charging_point_status_history`
2. Event `ChargingPointStatusChanged` + WebSocket
3. Health check endpoint `/api/health/steve`
4. Dashboard temps réel avec Laravel Echo

---

## 📞 Aide Rapide

### 15. **Commandes Utiles**

```bash
# Monitoring manuel
php artisan steve:monitor

# Avec limite et batches
php artisan steve:monitor --limit=50 --batch=10

# Vérifier la configuration
php artisan config:show services.steve

# Vider le cache des statuts
php artisan cache:forget charging_point_status_*

# Logs en temps réel
tail -f storage/logs/laravel.log | grep -i "chargingpointstatuscontroller"
```

### 16. **Endpoints API**

```
GET  /api/charging-points/{id}/status
     → Statut d'une borne spécifique
     
GET  /api/charging-points/{id}/status?force=1
     → Force le refresh (ignore cache)

GET  /api/charging-points
     → Liste avec filtres (?status=online)
```

---

## 📄 Conclusion

Votre système de gestion des statuts de points de charge basé sur les connecteurs est **robuste, bien architecturé et production-ready**. 

**Points forts majeurs :**
- Séparation claire des responsabilités
- Système de fallback intelligent
- Support complet des standards OCPP
- Monitoring automatisable

**Avec les améliorations suggérées**, vous aurez un système de **niveau entreprise** avec temps réel, historique, et haute disponibilité.

---

**Auteur:** Analyse complète du codebase Laravel EVON  
**Date:** 21 Décembre 2024  
**Version:** 1.0

