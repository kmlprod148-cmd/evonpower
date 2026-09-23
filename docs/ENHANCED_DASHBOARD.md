# Dashboard Amélioré - Documentation

## Vue d'ensemble

Le nouveau dashboard amélioré offre une interface moderne et intuitive avec des données en temps réel provenant de l'API Steve et de la base de données locale.

## Caractéristiques principales

### 🎨 Design moderne et attractif
- Interface responsive avec Tailwind CSS
- Mode sombre/clair automatique
- Animations fluides et transitions élégantes
- Cartes avec effets de survol et ombres dynamiques
- Gradients modernes et palette de couleurs cohérente

### 📊 Données en temps réel
- Statistiques actualisées automatiquement toutes les 30 secondes
- Indicateurs visuels pour les sessions actives
- Graphiques interactifs avec Chart.js
- Actualisation manuelle disponible

### 🌍 Multilingue complet
- Support FR, EN, AR, ES
- Toutes les chaînes de texte sont traduites
- Direction RTL automatique pour l'arabe
- Changement de langue sans rechargement

### 📈 Métriques clés affichées

#### Statistiques principales
1. **Total des bornes de charge**
   - Nombre total de points de charge
   - Nombre de bornes en ligne
   - Statut en temps réel

2. **Sessions actives**
   - Nombre de sessions de charge en cours
   - Indicateur de pulsation en direct
   - Mise à jour automatique

3. **Revenus du jour**
   - Montant total des revenus aujourd'hui
   - Comparaison avec hier (%)
   - Tendance positive/négative

4. **Énergie distribuée**
   - Total kWh distribués aujourd'hui
   - Nombre de sessions complétées
   - Calcul basé sur les transactions

#### Graphiques

1. **Graphique des sessions**
   - Vue par jour (7 derniers jours) par défaut
   - Vue horaire (24 dernières heures)
   - Vue mensuelle (12 derniers mois)
   - Graphique en ligne avec dégradé

2. **Distribution des statuts**
   - Graphique en anneau (doughnut)
   - Bornes en ligne (vert)
   - Bornes hors ligne (rouge)
   - Bornes en maintenance (jaune)
   - Légende avec compteurs

#### Listes dynamiques

1. **Bornes en charge active**
   - Liste des bornes actuellement en charge
   - Nom et localisation
   - Énergie consommée en temps réel
   - Durée de la session
   - Mise à jour automatique

2. **Meilleures stations**
   - Top 5 des stations les plus performantes
   - Basé sur les 7 derniers jours
   - Nombre de sessions
   - Énergie totale distribuée
   - Classement visuel

3. **Transactions récentes**
   - 10 dernières transactions complétées
   - Point de charge
   - Utilisateur
   - Énergie consommée
   - Durée
   - Montant
   - Date et heure

## Filtrage par rôle utilisateur

Le dashboard s'adapte automatiquement selon le rôle de l'utilisateur :

### Admin
- Voit toutes les données de la plateforme
- Accès complet à toutes les statistiques
- Aucun filtre appliqué

### Intégrateur
- Voit uniquement les données liées à son integrator_id
- Bornes, transactions et réservations filtrées
- Statistiques agrégées pour son périmètre

### Opérateur
- Voit uniquement ses propres bornes (user_id)
- Transactions et réservations de ses bornes
- Statistiques personnelles

### Partenaire
- Voit les données liées à son partner_id
- Bornes associées au partenaire
- Statistiques du partenaire

### Client simple (user)
- Redirigé automatiquement vers ses réservations
- Pas d'accès au dashboard

## Routes

### Route principale
```php
GET /dashboard/enhanced
```
Affiche le dashboard amélioré avec toutes les données

### API temps réel
```php
GET /dashboard/enhanced/realtime
```
Retourne les données en JSON pour mise à jour AJAX

**Réponse JSON :**
```json
{
  "stats": {
    "totalPoints": 45,
    "onlinePoints": 38,
    "activeTransactions": 12,
    "todayRevenue": 2450.50,
    ...
  },
  "activeChargingPoints": [
    {
      "id": 1,
      "name": "Station Centre",
      "location": "Casablanca",
      "energy": 25.5,
      "duration": 45
    },
    ...
  ],
  "timestamp": "2025-12-21T10:30:00Z"
}
```

## Intégration avec l'API Steve

Le dashboard récupère les données réelles de :

### Base de données locale
- Modèle `ChargingPoint` : statut, localisation, configuration
- Modèle `Transaction` : historique, montants, énergie consommée
- Modèle `Reservation` : réservations actives et complétées

### Calculs en temps réel
- Agrégation des transactions par période
- Calcul des tendances (jour, semaine, mois)
- Statistiques de performance par station
- Taux de disponibilité des bornes

## Performances et optimisation

### Cache
- Pas de cache sur les statistiques principales (données temps réel)
- Requêtes optimisées avec `with()` pour éviter N+1
- Utilisation de `select()` pour limiter les colonnes

### Actualisation automatique
- Intervalle : 30 secondes
- Requête AJAX silencieuse
- Mise à jour uniquement des éléments modifiés
- Pas de rechargement complet de la page

### Optimisations des requêtes
```php
// Utilisation de withCount et withSum
ChargingPoint::withCount(['transactions as completed_transactions' => function($q) {
    $q->where('status', 'completed')
      ->whereDate('created_at', '>=', Carbon::now()->subDays(7));
}])
->withSum(['transactions as total_energy' => function($q) {
    $q->where('status', 'completed')
      ->whereDate('created_at', '>=', Carbon::now()->subDays(7));
}], DB::raw('COALESCE(energy_consumed_wh, 0) / 1000'))
```

## Personnalisation

### Modifier l'intervalle d'actualisation
Dans `resources/views/dashboard-enhanced.blade.php` :
```javascript
// Ligne 460 - Changer 30000 (30 secondes) selon vos besoins
setInterval(() => {
    // ...
}, 30000); // Modifier cette valeur
```

### Ajouter de nouvelles statistiques
1. Ajouter la méthode dans `EnhancedDashboardController.php`
2. Passer les données à la vue
3. Créer le composant visuel dans `dashboard-enhanced.blade.php`
4. Ajouter les traductions dans les fichiers de langue

### Modifier les couleurs
Les couleurs sont définies avec Tailwind CSS :
- Bleu : `bg-blue-600`, `text-blue-600`
- Vert : `bg-green-600`, `text-green-600`
- Rouge : `bg-red-600`, `text-red-600`
- Jaune : `bg-yellow-600`, `text-yellow-600`

## Traductions

### Ajouter une nouvelle traduction
1. Ouvrir `resources/lang/{locale}/dashboard.php`
2. Ajouter la clé et la valeur
3. Utiliser dans la vue : `{{ __('dashboard.ma_cle') }}`

### Langues supportées
- Français (fr) : `resources/lang/fr/dashboard.php`
- Anglais (en) : `resources/lang/en/dashboard.php`
- Arabe (ar) : `resources/lang/ar/dashboard.php`
- Espagnol (es) : `resources/lang/es/dashboard.php`

## Dépendances

### Frontend
- **Tailwind CSS** : Framework CSS (inclus dans le layout)
- **Chart.js 4.4.0** : Bibliothèque de graphiques
- **Alpine.js** : (optionnel) Pour les interactions

### Backend
- **Laravel 10+**
- **Carbon** : Manipulation des dates
- **Eloquent ORM** : Requêtes base de données

## Migration depuis l'ancien dashboard

### Étape 1 : Tester le nouveau dashboard
```
GET /dashboard/enhanced
```

### Étape 2 : Comparer les données
Vérifier que les statistiques correspondent entre l'ancien et le nouveau dashboard

### Étape 3 : Basculer la route principale
Dans `routes/web.php`, remplacer :
```php
Route::get('/dashboard', [DashboardController::class, 'index'])
```
par :
```php
Route::get('/dashboard', [EnhancedDashboardController::class, 'index'])
```

## Troubleshooting

### Les données ne s'actualisent pas
1. Vérifier la console JavaScript pour les erreurs
2. Vérifier que la route `/dashboard/enhanced/realtime` est accessible
3. Vérifier les permissions de l'utilisateur

### Les graphiques ne s'affichent pas
1. Vérifier que Chart.js est chargé : ouvrir la console et taper `Chart`
2. Vérifier les données passées : `console.log(chartData)`
3. Vérifier qu'il n'y a pas d'erreur JavaScript

### Problèmes de traduction
1. Vérifier que la locale est correctement définie
2. Vérifier que les fichiers de langue existent
3. Vider le cache : `php artisan cache:clear`

### Performance lente
1. Réduire l'intervalle d'actualisation automatique
2. Ajouter des index sur les colonnes filtrées
3. Utiliser le cache Redis pour les statistiques

## Support

Pour toute question ou problème :
1. Consulter les logs Laravel : `storage/logs/laravel.log`
2. Activer le mode debug : `APP_DEBUG=true` dans `.env`
3. Vérifier la documentation de l'API Steve

## Évolutions futures

### Prévues
- [ ] Export des données en PDF/Excel
- [ ] Alertes en temps réel (WebSockets)
- [ ] Widgets personnalisables
- [ ] Comparaison de périodes
- [ ] Prévisions basées sur l'IA

### En cours de réflexion
- [ ] Dashboard mobile dédié
- [ ] Notifications push
- [ ] Intégration avec d'autres APIs
- [ ] Rapports automatisés par email

