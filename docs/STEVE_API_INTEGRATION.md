# Steve API Integration

## Vue d'ensemble

Le service Steve API permet l'intégration complète avec le serveur SteVe (Open Charge Point Protocol) pour la gestion des bornes de recharge. Il fournit une interface unifiée pour connecter, démarrer, arrêter et surveiller les sessions de charge.

## Fonctionnalités

### 🔌 Connexion des Bornes
- **Connexion automatique** : Génération automatique de l'ID de borne (BORNE_ID)
- **URL WebSocket** : Génération automatique de l'URL WebSocket pour la connexion
- **Interface simplifiée** : Suppression du champ de saisie manuel de l'ID de borne
- **Bouton de connexion** : Interface intuitive avec feedback visuel

### ⚡ Gestion des Sessions de Charge
- **Démarrage de charge** : `startCharge(chargerId, sessionId)`
- **Arrêt de charge** : `stopCharge(chargerId, sessionId)`
- **Statut de session** : Surveillance en temps réel des sessions actives
- **Statut de borne** : Vérification de l'état des bornes

### 🔄 Mécanismes de Fiabilité
- **Retry automatique** : Mécanisme de retry avec backoff exponentiel
- **Gestion d'erreurs** : Gestion complète des erreurs réseau et API
- **Logging détaillé** : Logs complets pour le debugging et le monitoring
- **Timeouts configurables** : Configuration flexible des timeouts

### 📊 Monitoring et Statistiques
- **Test de connectivité** : Vérification de la disponibilité du serveur Steve
- **Statistiques de charge** : Récupération des statistiques globales et par borne
- **Informations de borne** : Détails techniques des bornes
- **Liste des bornes** : Inventaire complet des bornes disponibles

## Architecture Technique

### Services Principaux

#### SteveApiService
```php
class SteveApiService
{
    // Connexion des bornes
    public function connectCharger(string $chargerId): array
    
    // Gestion des sessions
    public function startCharge(string $chargerId, string $sessionId): array
    public function stopCharge(string $chargerId, string $sessionId): array
    
    // Surveillance
    public function getChargerStatus(string $chargerId): array
    public function getSessionStatus(string $chargerId, string $sessionId): array
    
    // Utilitaires
    public function generateWebSocketUrl(string $chargerId): string
    public function testConnection(): array
    public function isAvailable(): bool
}
```

#### SteveApiController
```php
class SteveApiController extends Controller
{
    // Endpoints API
    public function connectCharger(Request $request, $chargingPointId): JsonResponse
    public function startCharge(Request $request, $chargingPointId): JsonResponse
    public function stopCharge(Request $request, $chargingPointId): JsonResponse
    public function getChargerStatus($chargingPointId): JsonResponse
    public function getSessionStatus(Request $request, $chargingPointId): JsonResponse
    public function testConnection(): JsonResponse
}
```

### Configuration

#### Fichier de Configuration (`config/steve.php`)
```php
return [
    'api_url' => env('STEVE_API_URL', 'http://158.69.27.239:8080'),
    'websocket_url' => env('STEVE_WEBSOCKET_URL', 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/'),
    'timeout' => env('STEVE_TIMEOUT', 30),
    'max_retries' => env('STEVE_MAX_RETRIES', 3),
    'retry_delay' => env('STEVE_RETRY_DELAY', 1000),
    // ... autres configurations
];
```

#### Variables d'Environnement
```env
STEVE_API_URL=http://158.69.27.239:8080
STEVE_WEBSOCKET_URL=ws://158.69.27.239:8080/steve/websocket/CentralSystemService/
STEVE_TIMEOUT=30
STEVE_MAX_RETRIES=3
STEVE_RETRY_DELAY=1000
STEVE_LOG_REQUESTS=true
STEVE_LOG_RESPONSES=true
STEVE_CACHE_RESPONSES=true
```

## API Endpoints

### Routes API Steve

```php
// Steve API routes
Route::prefix('steve')->middleware('auth:sanctum')->group(function () {
    Route::post('/connect/{chargingPoint}', [SteveApiController::class, 'connectCharger']);
    Route::post('/start-charge/{chargingPoint}', [SteveApiController::class, 'startCharge']);
    Route::post('/stop-charge/{chargingPoint}', [SteveApiController::class, 'stopCharge']);
    Route::get('/status/{chargingPoint}', [SteveApiController::class, 'getChargerStatus']);
    Route::get('/session-status/{chargingPoint}', [SteveApiController::class, 'getSessionStatus']);
    Route::get('/info/{chargingPoint}', [SteveApiController::class, 'getChargerInfo']);
    Route::get('/stats/{chargingPoint?}', [SteveApiController::class, 'getChargingStats']);
    Route::get('/websocket-url/{chargingPoint}', [SteveApiController::class, 'generateWebSocketUrl']);
    Route::get('/test-connection', [SteveApiController::class, 'testConnection']);
    Route::get('/availability', [SteveApiController::class, 'checkAvailability']);
    Route::get('/config', [SteveApiController::class, 'getConfig']);
    Route::get('/chargers', [SteveApiController::class, 'listChargers']);
});
```

### Exemples d'Utilisation

#### 1. Connexion d'une Borne
```javascript
// Frontend - Connexion automatique
async function connectChargingPoint() {
    const chargingPointId = 123;
    const borneId = `BORNE_${chargingPointId}`;
    const websocketUrl = `ws://158.69.27.239:8080/steve/websocket/CentralSystemService/${borneId}`;
    
    const response = await fetch(`/api/steve/connect/${chargingPointId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            charger_id: borneId,
            websocket_url: websocketUrl
        })
    });
    
    const data = await response.json();
    if (data.success) {
        console.log('Borne connectée:', data.data);
    }
}
```

#### 2. Démarrage d'une Session de Charge
```javascript
// Frontend - Démarrage de charge
async function startChargingSession(chargingPointId, sessionId) {
    const response = await fetch(`/api/steve/start-charge/${chargingPointId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            session_id: sessionId,
            charger_id: `BORNE_${chargingPointId}`
        })
    });
    
    const data = await response.json();
    return data;
}
```

#### 3. Vérification du Statut
```javascript
// Frontend - Vérification du statut
async function checkChargerStatus(chargingPointId) {
    const response = await fetch(`/api/steve/status/${chargingPointId}`);
    const data = await response.json();
    return data;
}
```

## Interface Utilisateur

### Modifications de l'Interface

#### Avant (Saisie Manuelle)
```html
<!-- Ancien système avec saisie manuelle -->
<input type="text" id="borneIdInput" 
       placeholder="ID Borne (ex: BORNE777)" 
       value="{{ $chargingPoint->charge_box_id ?? 'BORNE777' }}"
       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
<button onclick="connectByIdWithInput()">Connecter</button>
```

#### Après (Connexion Automatique)
```html
<!-- Nouveau système avec génération automatique -->
<div class="flex items-center space-x-2">
    <div class="flex-1">
        <input type="text" 
               id="borneIdDisplay"
               value="BORNE_{{ $chargingPoint->id }}"
               readonly
               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
    </div>
    <button onclick="connectChargingPoint()" 
            id="connect-btn"
            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg">
        <span id="connect-btn-text">Connecter</span>
    </button>
</div>
```

### Fonctionnalités JavaScript

#### Connexion Automatique
```javascript
async function connectChargingPoint() {
    const connectBtn = document.getElementById('connect-btn');
    const connectBtnText = document.getElementById('connect-btn-text');
    const chargingPointId = {{ $chargingPoint->id }};
    const borneId = `BORNE_${chargingPointId}`;
    const websocketUrl = `ws://158.69.27.239:8080/steve/websocket/CentralSystemService/${borneId}`;
    
    try {
        // Mise à jour de l'interface
        connectBtn.disabled = true;
        connectBtn.classList.add('bg-yellow-500');
        connectBtnText.textContent = 'Connexion...';
        
        // Appel API
        const response = await fetch(`/api/steve/connect/${chargingPointId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                charger_id: borneId,
                websocket_url: websocketUrl
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            connectBtn.classList.remove('bg-yellow-500');
            connectBtn.classList.add('bg-green-600');
            connectBtnText.textContent = 'Connecté';
            showConnectionMessage(`Borne connectée! WebSocket: ${websocketUrl}`, 'success');
        } else {
            connectBtn.classList.add('bg-red-600');
            connectBtnText.textContent = 'Erreur';
            showConnectionMessage(`Erreur: ${data.message}`, 'error');
        }
    } catch (error) {
        connectBtn.classList.add('bg-red-600');
        connectBtnText.textContent = 'Erreur';
        showConnectionMessage(`Erreur réseau: ${error.message}`, 'error');
    }
}
```

## Gestion des Erreurs

### Types d'Erreurs Gérées

1. **Erreurs Réseau**
   - Timeout de connexion
   - Connexion refusée
   - DNS non résolu

2. **Erreurs API**
   - Réponses HTTP d'erreur (4xx, 5xx)
   - Format de réponse invalide
   - Champs manquants

3. **Erreurs Métier**
   - Borne non trouvée
   - Session déjà active
   - Permissions insuffisantes

### Mécanisme de Retry

```php
protected function makeRequestWithRetry(string $method, string $endpoint, array $data = []): array
{
    $lastException = null;
    
    for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
        try {
            // Tentative de requête
            $response = $this->makeHttpRequest($method, $endpoint, $data);
            return $response;
        } catch (Exception $e) {
            $lastException = $e;
            
            if ($attempt < $this->maxRetries) {
                $delay = $this->retryDelay * $attempt; // Backoff exponentiel
                usleep($delay * 1000);
            }
        }
    }
    
    throw $lastException;
}
```

## Logging et Monitoring

### Logs Générés

#### Logs de Connexion
```php
Log::info('SteveApiService: Attempting to connect charger', [
    'charger_id' => $chargerId,
    'base_url' => $this->baseUrl
]);
```

#### Logs de Succès
```php
Log::info('SteveApiService: Charger connection successful', [
    'charger_id' => $chargerId,
    'duration_ms' => round($duration, 2),
    'response' => $response
]);
```

#### Logs d'Erreur
```php
Log::error('SteveApiService: Failed to connect charger', [
    'charger_id' => $chargerId,
    'error' => $e->getMessage(),
    'duration_ms' => round($duration, 2),
    'trace' => $e->getTraceAsString()
]);
```

### Métriques de Performance

- **Durée des requêtes** : Mesure du temps de réponse
- **Taux de succès** : Pourcentage de requêtes réussies
- **Taux d'erreur** : Pourcentage de requêtes échouées
- **Temps de retry** : Durée moyenne des tentatives de retry

## Tests

### Tests Unitaires

```php
class SteveApiServiceTest extends TestCase
{
    /** @test */
    public function it_can_connect_charger_successfully()
    {
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => Http::response([
                'success' => true,
                'charger_id' => 'BORNE_123',
                'connected' => true
            ], 200)
        ]);

        $result = $this->steveApiService->connectCharger('BORNE_123');

        $this->assertTrue($result['success']);
        $this->assertEquals('BORNE_123', $result['charger_id']);
        $this->assertTrue($result['connected']);
    }
}
```

### Tests d'Intégration

```php
class SteveApiIntegrationTest extends TestCase
{
    /** @test */
    public function it_can_handle_full_charging_workflow()
    {
        // 1. Connecter la borne
        $connectResult = $this->steveApiService->connectCharger('BORNE_123');
        $this->assertTrue($connectResult['success']);

        // 2. Démarrer la charge
        $startResult = $this->steveApiService->startCharge('BORNE_123', 'SESSION_123');
        $this->assertTrue($startResult['success']);

        // 3. Vérifier le statut
        $statusResult = $this->steveApiService->getSessionStatus('BORNE_123', 'SESSION_123');
        $this->assertTrue($statusResult['success']);

        // 4. Arrêter la charge
        $stopResult = $this->steveApiService->stopCharge('BORNE_123', 'SESSION_123');
        $this->assertTrue($stopResult['success']);
    }
}
```

## Sécurité

### Authentification
- Toutes les routes API nécessitent l'authentification Sanctum
- Validation des tokens CSRF pour les requêtes POST
- Vérification des permissions utilisateur

### Validation des Données
```php
$request->validate([
    'charger_id' => 'required|string',
    'websocket_url' => 'required|string',
    'session_id' => 'required|string'
]);
```

### Protection contre les Attaques
- Limitation du taux de requêtes (rate limiting)
- Validation stricte des entrées
- Échappement des données utilisateur
- Logs de sécurité pour les tentatives suspectes

## Performance

### Optimisations

1. **Cache des Réponses**
   ```php
   'cache_responses' => env('STEVE_CACHE_RESPONSES', true),
   'cache_ttl' => env('STEVE_CACHE_TTL', 300),
   ```

2. **Connexions HTTP Réutilisables**
   ```php
   $httpClient = Http::timeout($this->timeout);
   ```

3. **Requêtes Asynchrones**
   ```php
   // Support pour les requêtes asynchrones futures
   ```

### Monitoring de Performance

- **Durée des requêtes** : Mesure précise en millisecondes
- **Taux de succès** : Monitoring en temps réel
- **Utilisation des ressources** : CPU, mémoire, réseau
- **Alertes automatiques** : Notification en cas de problème

## Déploiement

### Configuration de Production

```env
STEVE_API_URL=https://steve.yourdomain.com
STEVE_WEBSOCKET_URL=wss://steve.yourdomain.com/steve/websocket/CentralSystemService/
STEVE_TIMEOUT=60
STEVE_MAX_RETRIES=5
STEVE_RETRY_DELAY=2000
STEVE_LOG_REQUESTS=true
STEVE_LOG_RESPONSES=false
STEVE_CACHE_RESPONSES=true
STEVE_CACHE_TTL=600
```

### Variables d'Environnement Requises

```env
# Configuration Steve API
STEVE_API_URL=http://158.69.27.239:8080
STEVE_WEBSOCKET_URL=ws://158.69.27.239:8080/steve/websocket/CentralSystemService/
STEVE_TIMEOUT=30
STEVE_MAX_RETRIES=3
STEVE_RETRY_DELAY=1000

# Logging
STEVE_LOG_REQUESTS=true
STEVE_LOG_RESPONSES=true
STEVE_LOG_ERRORS=true

# Cache
STEVE_CACHE_RESPONSES=true
STEVE_CACHE_TTL=300

# Rate Limiting
STEVE_RATE_LIMIT_ENABLED=true
STEVE_RATE_LIMIT_REQUESTS=100
STEVE_RATE_LIMIT_WINDOW=60
```

## Maintenance

### Tâches de Maintenance

1. **Surveillance des Logs**
   - Vérification quotidienne des erreurs
   - Analyse des patterns d'erreur
   - Optimisation des performances

2. **Mise à Jour de Configuration**
   - Ajustement des timeouts
   - Optimisation des retry
   - Mise à jour des URLs

3. **Monitoring de Santé**
   - Vérification de la connectivité
   - Test des endpoints
   - Validation des réponses

### Commandes de Maintenance

```bash
# Test de connectivité
php artisan steve:test-connection

# Vérification de la santé
php artisan steve:health-check

# Nettoyage des logs
php artisan steve:clean-logs

# Statistiques de performance
php artisan steve:stats
```

## Dépannage

### Problèmes Courants

1. **Connexion Refusée**
   - Vérifier l'URL du serveur Steve
   - Vérifier la connectivité réseau
   - Vérifier les paramètres de firewall

2. **Timeouts**
   - Augmenter le timeout dans la configuration
   - Vérifier la charge du serveur Steve
   - Optimiser les requêtes

3. **Erreurs de Retry**
   - Vérifier la configuration des retry
   - Analyser les logs d'erreur
   - Ajuster le délai de retry

### Outils de Dépannage

```bash
# Test de connectivité manuel
curl -X GET "http://158.69.27.239:8080/api/v1/health"

# Vérification des logs
tail -f storage/logs/laravel.log | grep "SteveApiService"

# Test des endpoints
php artisan steve:test-endpoints
```

## Conclusion

Le service Steve API fournit une intégration complète et robuste avec le serveur SteVe, offrant :

- ✅ **Interface simplifiée** : Suppression de la saisie manuelle d'ID
- ✅ **Connexion automatique** : Génération automatique des URLs WebSocket
- ✅ **Gestion d'erreurs robuste** : Retry automatique et logging détaillé
- ✅ **Monitoring complet** : Surveillance des performances et de la santé
- ✅ **Sécurité renforcée** : Authentification et validation des données
- ✅ **Tests complets** : Couverture de test unitaire et d'intégration

Cette intégration permet une gestion efficace et fiable des bornes de recharge via le protocole OCPP, avec une expérience utilisateur optimisée et une maintenance simplifiée.
