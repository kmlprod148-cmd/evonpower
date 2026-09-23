# Système de Notifications Publiques

Ce document décrit l'implémentation et l'utilisation du système de notifications publiques pour l'application EVON.

## Vue d'ensemble

Le système de notifications publiques permet d'afficher des notifications importantes aux utilisateurs sans authentification. Il est conçu pour être performant, sécurisé et facilement intégrable dans n'importe quelle page.

## Fonctionnalités

- ✅ Notifications publiques accessibles sans authentification
- ✅ Cache intelligent pour les performances
- ✅ Support des différents types de notifications (système, annonces, alertes)
- ✅ Priorités configurables (urgent, haute, normale, basse)
- ✅ Interface utilisateur moderne et responsive
- ✅ Webhook pour l'intégration avec des services tiers
- ✅ Composant JavaScript réutilisable
- ✅ Support des thèmes et personnalisation

## Structure des fichiers

```
app/
├── Http/Controllers/
│   └── PublicNotificationController.php    # Contrôleur principal
├── Models/
│   └── AdminNotification.php              # Modèle mis à jour
database/migrations/
└── 2025_07_25_000000_add_is_public_to_admin_notifications_table.php
resources/views/notifications/
├── public.blade.php                       # Vue principale
└── public-show.blade.php                  # Vue détaillée
public/
├── js/
│   ├── public-notifications.js            # Composant JavaScript
│   └── public-notifications-example.js    # Exemples d'utilisation
└── css/
    └── public-notifications.css           # Styles CSS
routes/
└── web.php                               # Routes publiques ajoutées
```

## Installation

### 1. Migration de base de données

Exécutez la migration pour ajouter le champ `is_public` :

```bash
php artisan migrate
```

### 2. Inclure les fichiers CSS et JS

Ajoutez les fichiers dans votre layout principal :

```html
<!-- Dans le head -->
<link rel="stylesheet" href="{{ asset('css/public-notifications.css') }}">

<!-- Avant la fermeture du body -->
<script src="{{ asset('js/public-notifications.js') }"></script>
```

## Utilisation

### Routes disponibles

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/public/notifications` | Liste des notifications publiques |
| GET | `/public/notifications/{id}` | Détails d'une notification |
| GET | `/public/notifications/system` | Notifications système (API) |
| GET | `/public/notifications/announcements` | Annonces publiques (API) |
| POST | `/public/notifications/{id}/viewed` | Marquer comme vue |
| POST | `/public/notifications/webhook` | Webhook pour notifications externes |

### Intégration simple

#### 1. Ajouter le container HTML

```html
<div id="public-notifications" class="public-notifications"></div>
```

#### 2. Initialiser le composant JavaScript

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const notifications = new PublicNotifications({
        container: '#public-notifications',
        apiUrl: '/public/notifications',
        maxNotifications: 5,
        autoRefresh: true,
        refreshInterval: 300000 // 5 minutes
    });
});
```

### Intégration avancée

#### Dans le header avec badge de compteur

```html
<div id="header-notifications" class="relative"></div>
```

```javascript
function initializeHeaderNotifications() {
    const notifications = new PublicNotifications({
        container: '#header-notifications',
        apiUrl: '/public/notifications',
        maxNotifications: 3,
        showSystemNotifications: true,
        showAnnouncements: false,
        autoRefresh: true,
        refreshInterval: 180000 // 3 minutes
    });
    
    // Ajouter un badge de compteur
    const badge = document.createElement('span');
    badge.className = 'notification-badge';
    badge.style.cssText = `
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    `;
    
    const container = document.getElementById('header-notifications');
    if (container) {
        container.style.position = 'relative';
        container.appendChild(badge);
        
        // Mettre à jour le badge
        notifications.on('notificationsLoaded', function(count) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    }
}
```

## Configuration

### Variables d'environnement

Ajoutez ces variables dans votre fichier `.env` :

```env
# Configuration du webhook
WEBHOOK_SECRET=your-secret-key-here
WEBHOOK_ENABLED=true
WEBHOOK_TIMEOUT=30
WEBHOOK_RETRY_ATTEMPTS=3
```

### Options du composant JavaScript

| Option | Type | Défaut | Description |
|--------|------|--------|-------------|
| `container` | string | `#public-notifications` | Sélecteur du container |
| `apiUrl` | string | `'/public/notifications'` | URL de l'API |
| `maxNotifications` | number | `5` | Nombre max de notifications |
| `showSystemNotifications` | boolean | `true` | Afficher les notifications système |
| `showAnnouncements` | boolean | `true` | Afficher les annonces |
| `autoRefresh` | boolean | `true` | Actualisation automatique |
| `refreshInterval` | number | `300000` | Intervalle d'actualisation (ms) |

## Création de notifications

### Via l'interface d'administration

Les administrateurs peuvent créer des notifications publiques via l'interface d'administration en définissant `is_public = true`.

### Via le webhook

```bash
curl -X POST https://votre-domaine.com/public/notifications/webhook \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: your-signature" \
  -d '{
    "title": "Maintenance prévue",
    "message": "Une maintenance est prévue le 15/07/2025 de 2h à 4h du matin.",
    "type": "system",
    "priority": "high"
  }'
```

### Signature du webhook

```php
$payload = json_encode($data);
$signature = hash_hmac('sha256', $payload, config('services.webhook.secret'));
```

## Types de notifications

| Type | Description | Couleur |
|------|-------------|---------|
| `system` | Notifications système (maintenance, etc.) | Bleu |
| `announcement` | Annonces publiques | Jaune |
| `warning` | Alertes importantes | Rouge |
| `error` | Erreurs système | Rouge |
| `success` | Succès | Vert |
| `info` | Informations générales | Gris |

## Priorités

| Priorité | Description | Badge |
|----------|-------------|-------|
| `urgent` | Très important, action immédiate requise | Rouge |
| `high` | Important, attention requise | Orange |
| `normal` | Standard | Gris |
| `low` | Informations générales | Vert |

## Personnalisation CSS

### Thèmes

Le système supporte plusieurs thèmes via les classes CSS :

```css
/* Thème sombre */
.theme-dark .notifications-wrapper {
    background: #1f2937;
    color: #f9fafb;
}

/* Thème compact */
.theme-compact .notification-item {
    padding: 0.5rem;
}
```

### Variables CSS personnalisables

```css
:root {
    --notification-primary-color: #3b82f6;
    --notification-success-color: #22c55e;
    --notification-warning-color: #eab308;
    --notification-error-color: #ef4444;
    --notification-bg-color: #ffffff;
    --notification-text-color: #1f2937;
}
```

## Performance

### Cache

Les notifications sont mises en cache pour améliorer les performances :

- **Notifications publiques** : 5 minutes
- **Notifications système** : 1 minute
- **Annonces** : 5 minutes

### Optimisations

- Pagination des résultats
- Limitation du nombre de notifications affichées
- Actualisation intelligente (seulement si nécessaire)
- Compression des réponses JSON

## Sécurité

### Webhook

- Validation de signature HMAC-SHA256
- Timeout configurable
- Retry automatique en cas d'échec
- Logs des tentatives d'accès

### XSS Protection

- Échappement automatique du HTML
- Validation des entrées
- Sanitisation des données

### Rate Limiting

Les routes publiques sont protégées contre les abus :

```php
// Dans le contrôleur
public function __construct()
{
    $this->middleware('throttle:60,1')->only(['index', 'show']);
    $this->middleware('throttle:10,1')->only(['webhook']);
}
```

## Tests

### Tests unitaires

```bash
php artisan test --filter=PublicNotificationController
```

### Tests d'intégration

```bash
php artisan test --filter=PublicNotificationTest
```

## Dépannage

### Problèmes courants

1. **Notifications ne s'affichent pas**
   - Vérifiez que `is_public = true` dans la base de données
   - Vérifiez les logs d'erreur
   - Vérifiez la console JavaScript

2. **Webhook ne fonctionne pas**
   - Vérifiez la signature HMAC
   - Vérifiez la configuration dans `.env`
   - Vérifiez les logs d'erreur

3. **Performance lente**
   - Vérifiez la configuration du cache
   - Optimisez les requêtes de base de données
   - Vérifiez la pagination

### Logs

Les logs sont disponibles dans :

```
storage/logs/laravel.log
```

### Debug

Activez le mode debug pour plus d'informations :

```env
APP_DEBUG=true
```

## Support

Pour toute question ou problème, consultez :

1. La documentation Laravel
2. Les logs d'erreur
3. La console JavaScript du navigateur
4. L'équipe de développement

## Changelog

### Version 1.0.0 (2025-07-25)
- ✅ Système de notifications publiques
- ✅ Interface utilisateur moderne
- ✅ Composant JavaScript réutilisable
- ✅ Webhook pour intégrations externes
- ✅ Cache intelligent
- ✅ Support des thèmes
- ✅ Documentation complète 