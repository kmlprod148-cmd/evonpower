# 🚀 Quick Start Guide - Système de Statut des Points de Charge

## 📋 Vue d'Ensemble Rapide

Votre application Laravel EVON utilise un système sophistiqué pour obtenir le statut des **points de charge** (Charging Points) basé sur le statut de leurs **connecteurs** via l'API **Steve/OCPP**.

---

## ⚡ Flux Principal en 30 Secondes

```
ChargingPoint --[steve_charging_point_id]--> Steve API
                                                  ↓
                                         Connecteurs Status
                                                  ↓
                                    Mapping vers Statut Laravel
                                                  ↓
                            ['online', 'offline', 'maintenance', 'error']
```

---

## 🔑 Fichiers Clés

| Fichier | Rôle |
|---------|------|
| `app/Models/ChargingPoint.php` | Modèle principal avec relations et scopes |
| `app/Models/Connector.php` | Modèle des connecteurs |
| `app/Services/SteveService.php` | Service d'intégration Steve API |
| `app/Http/Controllers/ChargingPointStatusController.php` | API REST pour statuts |
| `app/Console/Commands/MonitorSteveChargingPoints.php` | Commande de monitoring |

---

## 📊 Statuts Disponibles

| Statut | Signification | Couleur |
|--------|---------------|---------|
| `online` | En ligne et disponible | 🟢 Vert |
| `offline` | Hors ligne | 🔴 Rouge |
| `maintenance` | En maintenance | 🟡 Jaune |
| `error` | En erreur/défaut | 🔴 Rouge |
| `charging` | En charge | 🔵 Bleu |
| `reserved` | Réservé | 🟠 Orange |

---

## 🔧 Utilisation en Code

### 1. Vérifier le Statut d'une Borne

```php
$chargingPoint = ChargingPoint::find(1);

// Simple check
if ($chargingPoint->isOnline()) {
    // Borne disponible
}

// Accès direct
$status = $chargingPoint->status; // 'online', 'offline', etc.
```

### 2. Rafraîchir le Statut depuis Steve

```php
use App\Services\SteveService;

$steveService = app(SteveService::class);

// Option 1: Via le service
$result = $steveService->status($chargingPoint);
if ($result['ok']) {
    $status = $result['body']['status'];
}

// Option 2: Via l'API REST
$response = Http::get("/api/charging-points/{$chargingPoint->id}/status");
$status = $response->json()['status'];

// Option 3: Force refresh (ignore cache)
$response = Http::get("/api/charging-points/{$chargingPoint->id}/status?force=1");
```

### 3. Filtrer par Statut

```php
// Toutes les bornes en ligne
$onlinePoints = ChargingPoint::online()->get();

// Par statut spécifique
$maintenancePoints = ChargingPoint::byStatus('maintenance')->get();

// Avec relations
$points = ChargingPoint::online()
    ->with(['connectors', 'station', 'group'])
    ->get();
```

### 4. Mettre à Jour le Statut

```php
// Méthode 1: Update simple
$chargingPoint->status = 'maintenance';
$chargingPoint->save();

// Méthode 2: Avec log (recommandé)
$chargingPoint->updateStatus('maintenance', 'Maintenance planifiée');
```

---

## 🌐 API Endpoints

### GET `/api/charging-points/{id}/status`

**Récupère le statut actuel d'une borne**

**Paramètres:**
- `force=1` : Force le refresh (ignore cache)

**Réponse:**
```json
{
  "ok": true,
  "status": "online",
  "updated_at": "2024-12-21T15:30:00Z"
}
```

**Exemples:**
```bash
# Statut normal (cache 30s)
curl https://your-app.com/api/charging-points/123/status

# Force refresh
curl https://your-app.com/api/charging-points/123/status?force=1
```

---

## 🤖 Commandes Artisan

### Monitoring des Bornes

```bash
# Monitor toutes les bornes
php artisan steve:monitor

# Limiter le nombre et taille des batches
php artisan steve:monitor --limit=50 --batch=10

# Options:
#   --limit=N  : Nombre max de bornes à traiter
#   --batch=N  : Taille des batches (défaut: 10)
```

**Output Exemple:**
```
Démarrage du monitoring des bornes Steve...
Traitement de 100 borne(s)...
[========================================] 100/100
Terminé: 45 mise(s) à jour, 2 erreur(s)
```

---

## ⏰ Automatisation (Scheduler)

**Ajoutez dans `app/Console/Kernel.php`:**

```php
protected function schedule(Schedule $schedule): void
{
    // Monitoring toutes les 5 minutes
    $schedule->command('steve:monitor --batch=20')
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->onOneServer()
        ->runInBackground();
}
```

**Configuration Cron:**
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🗺️ Mapping des Statuts OCPP → Laravel

### Statuts des Connecteurs

| Statut OCPP | → | Statut Laravel |
|-------------|---|----------------|
| Available | → | online |
| Preparing | → | online |
| Charging | → | online |
| Finishing | → | online |
| Reserved | → | reserved |
| Unavailable | → | offline |
| Faulted | → | error |
| SuspendedEVSE | → | maintenance |
| SuspendedEV | → | maintenance |

### Logique de Décision

```
1. Si AU MOINS UN connecteur est "Charging" → online
2. Si AU MOINS UN connecteur est "Available" → online
3. Si TOUS les connecteurs sont "Unavailable" → offline
4. Si AU MOINS UN connecteur est "Faulted" → error
5. Si status global = "maintenance" → maintenance
```

---

## 🔍 Debugging

### Vérifier les Logs

```bash
# Logs en temps réel
tail -f storage/logs/laravel.log | grep -i "chargingpointstatuscontroller"

# Logs du monitoring
tail -f storage/logs/steve-monitor.log
```

### Vérifier le Cache

```php
// En PHP/Tinker
Cache::get('charging_point_status_123');

// Vider le cache des statuts
Cache::forget('charging_point_status_123');

// Vider tout le cache
php artisan cache:clear
```

### Tester la Connexion Steve

```php
php artisan tinker

>>> $cp = App\Models\ChargingPoint::find(1);
>>> $steve = app(App\Services\SteveService::class);
>>> $result = $steve->testConnection($cp);
>>> dd($result);
```

---

## ⚙️ Configuration

### Variables d'Environnement (.env)

```env
# Steve API
SERVICES_STEVE_URL=https://your-steve-server.com/api
SERVICES_STEVE_USERNAME=admin
SERVICES_STEVE_PASSWORD=your_password
SERVICES_STEVE_TIMEOUT=8.0

# OCPP WebSocket
OCPP_ENABLED=true
OCPP_WEBSOCKET_URL=ws://your-steve-server.com:8180/websocket
```

### Vérifier la Configuration

```bash
php artisan config:show services.steve
```

---

## 🐛 Problèmes Courants

### 1. Statut toujours "unknown"

**Cause:** `steve_charging_point_id` non défini

**Solution:**
```php
$chargingPoint->steve_charging_point_id = 'CP001';
$chargingPoint->save();
```

### 2. Cache qui ne se rafraîchit pas

**Cause:** Cache TTL trop long

**Solution:**
```bash
# Vider le cache
php artisan cache:clear

# Ou forcer le refresh
curl https://your-app.com/api/charging-points/123/status?force=1
```

### 3. API Steve timeout

**Cause:** Timeout trop court ou serveur lent

**Solution:**
```env
# Augmenter le timeout dans .env
SERVICES_STEVE_TIMEOUT=15.0
```

### 4. Monitoring échoue

**Cause:** Trop de bornes ou API rate limit

**Solution:**
```bash
# Réduire batch size et ajouter limite
php artisan steve:monitor --limit=20 --batch=5
```

---

## 📈 Métriques Importantes

### Requête SQL pour Statistiques

```sql
-- Distribution des statuts
SELECT 
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM charging_points), 2) as percentage
FROM charging_points
WHERE steve_charging_point_id IS NOT NULL
GROUP BY status
ORDER BY count DESC;

-- Statuts obsolètes (> 10 minutes)
SELECT 
    id, 
    name, 
    status, 
    status_updated_at,
    TIMESTAMPDIFF(MINUTE, status_updated_at, NOW()) as minutes_old
FROM charging_points
WHERE steve_charging_point_id IS NOT NULL
  AND status_updated_at < NOW() - INTERVAL 10 MINUTE
ORDER BY status_updated_at ASC;
```

---

## ✅ Checklist de Déploiement

- [ ] Variables `.env` configurées
- [ ] Steve API accessible depuis le serveur
- [ ] Cron job configuré pour le scheduler
- [ ] Cache Redis/Memcached configuré (optionnel mais recommandé)
- [ ] Index de base de données créés:
  ```sql
  CREATE INDEX idx_status ON charging_points(status);
  CREATE INDEX idx_steve_id ON charging_points(steve_charging_point_id);
  CREATE INDEX idx_status_updated ON charging_points(status, status_updated_at);
  ```
- [ ] Logs accessibles et rotatifs
- [ ] Monitoring configuré (Sentry, etc.)
- [ ] Tests passent: `php artisan test --filter ChargingPointStatus`

---

## 📚 Documentation Complète

Pour plus de détails, consultez :

1. **ANALYSE_STATUT_CHARGING_POINTS.md** - Analyse complète du système (15 pages)
2. **EXEMPLES_AMELIORATIONS_STATUT.md** - Code d'améliorations (WebSocket, historique, etc.)
3. **tests/Feature/ChargingPointStatusTest.php** - Tests unitaires complets

---

## 🆘 Support

**En cas de problème :**

1. Vérifier les logs : `storage/logs/laravel.log`
2. Tester la connexion Steve : `php artisan tinker` → `$steve->testConnection($cp)`
3. Vider le cache : `php artisan cache:clear`
4. Vérifier la configuration : `php artisan config:show services.steve`
5. Tester manuellement : `php artisan steve:monitor --limit=1`

---

## 🎯 Résumé en 3 Points

1. **Statut = Fonction(Connecteurs)**
   - L'application interroge l'API Steve
   - Parse les statuts des connecteurs
   - Mappe vers les statuts Laravel

2. **Deux Modes de Mise à Jour**
   - **On-Demand** : Via API REST (cache 30s)
   - **Scheduled** : Via commande Artisan (toutes les 5 min)

3. **Système Robuste**
   - Fallback multi-niveau (OCPP → REST API)
   - Cache intelligent
   - Logs détaillés
   - Tests complets

---

**Bon développement ! 🚀**

*Document généré le 21 Décembre 2024*
*Version: 1.0*

