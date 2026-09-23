# 📖 Guide d'Utilisation du Header Premium EVON

## 🎯 Vue d'Ensemble

Le header premium EVON est un composant Blade réutilisable qui offre une interface moderne et cohérente sur toutes les pages de l'application.

## 🏗️ Architecture

```
┌─────────────────────────────────────────┐
│         Contrôleur Laravel              │
│  (passe les variables à la vue)         │
└──────────────┬──────────────────────────┘
               │
               ↓
┌─────────────────────────────────────────┐
│      Layout (layouts/app.blade.php)     │
│  <x-app-header :showStats="..." />      │
└──────────────┬──────────────────────────┘
               │
               ↓
┌─────────────────────────────────────────┐
│  Composant (components/app-header.blade.php) │
│  Affiche le header avec tous les éléments    │
└─────────────────────────────────────────┘
```

## 📝 Utilisation de Base

### Option 1 : Depuis le Contrôleur (Recommandé)

```php
<?php

namespace App\Http\Controllers;

class MaPageController extends Controller
{
    public function index()
    {
        return view('ma-page', [
            'pageTitle' => 'Titre de ma Page',
            'pageSubtitle' => 'Description optionnelle',
            // Autres données...
        ]);
    }
}
```

### Option 2 : Depuis la Vue

```blade
@extends('layouts.app')

@section('title', 'Ma Page')

@section('content')
@php
    $pageTitle = 'Titre de ma Page';
    $pageSubtitle = 'Description optionnelle';
@endphp

<!-- Contenu de votre page -->
@endsection
```

## 🎨 Options Disponibles

### Variables Principales

| Variable | Type | Description | Défaut |
|----------|------|-------------|--------|
| `$pageTitle` | string | Titre principal affiché | `__('dashboard.dashboard')` |
| `$pageSubtitle` | string\|null | Sous-titre optionnel | Date/heure actuelle |
| `$showHeaderStats` | boolean | Afficher les statistiques | `false` |
| `$headerStats` | array | Données des statistiques | `[]` |
| `$breadcrumbs` | array | Fil d'Ariane | `[]` |

### Structure de `$headerStats`

```php
$headerStats = [
    'onlinePoints' => 16,           // Nombre de bornes en ligne
    'totalPoints' => 20,            // Nombre total de bornes
    'availabilityRate' => 80,       // Taux de disponibilité (%)
    'activeTransactions' => 4,      // Sessions actives
    'todayRevenue' => 546.00,       // Revenus du jour
    'todayTransactions' => 12,      // Transactions du jour
    'todayEnergy' => 422.4,         // Énergie distribuée (kWh)
];
```

### Structure de `$breadcrumbs`

```php
$breadcrumbs = [
    ['label' => 'Accueil', 'url' => route('dashboard')],
    ['label' => 'Liste', 'url' => route('items.index')],
    ['label' => 'Détails'] // Page actuelle (sans URL)
];
```

## 📊 Exemples d'Utilisation

### Exemple 1 : Page Simple (Sans Statistiques)

```php
// Contrôleur
public function show()
{
    return view('charging-points.show', [
        'pageTitle' => 'Détails de la Borne',
        'pageSubtitle' => 'Borne #CP-001',
        'breadcrumbs' => [
            ['label' => 'Accueil', 'url' => route('dashboard')],
            ['label' => 'Bornes', 'url' => route('charging-points.index')],
            ['label' => 'Détails']
        ]
    ]);
}
```

**Résultat** :
- Header avec titre et sous-titre
- Fil d'Ariane complet
- Pas de cartes statistiques
- Avatar utilisateur et menu

### Exemple 2 : Dashboard (Avec Statistiques)

```php
// Contrôleur
public function dashboard()
{
    $bornesActives = ChargingPoint::where('status', 'online')->count();
    $rechargesActives = ChargingSession::active()->count();
    $totalRecharges = ChargingSession::today()->count();
    
    $headerStats = [
        'onlinePoints' => $bornesActives,
        'totalPoints' => ChargingPoint::count(),
        'availabilityRate' => ($bornesActives / ChargingPoint::count()) * 100,
        'activeTransactions' => $rechargesActives,
        'todayRevenue' => Transaction::today()->sum('amount'),
        'todayTransactions' => $totalRecharges,
        'todayEnergy' => ChargingSession::today()->sum('energy_kwh'),
    ];

    return view('dashboard', [
        'showHeaderStats' => true,
        'pageTitle' => __('messages.dashboard'),
        'pageSubtitle' => __('messages.welcome_back'),
        'headerStats' => $headerStats,
    ]);
}
```

**Résultat** :
- Header avec gradient animé
- 4 cartes statistiques en temps réel
- Avatar, notifications, actions rapides
- Menu utilisateur complet

### Exemple 3 : Page avec Titre Dynamique

```php
// Contrôleur
public function edit(ChargingPoint $chargingPoint)
{
    return view('charging-points.edit', [
        'chargingPoint' => $chargingPoint,
        'pageTitle' => "Modifier {$chargingPoint->name}",
        'pageSubtitle' => "ID: {$chargingPoint->id}",
        'breadcrumbs' => [
            ['label' => 'Accueil', 'url' => route('dashboard')],
            ['label' => 'Bornes', 'url' => route('charging-points.index')],
            ['label' => $chargingPoint->name, 'url' => route('charging-points.show', $chargingPoint)],
            ['label' => 'Modifier']
        ]
    ]);
}
```

## 🎭 Personnalisation Avancée

### Désactiver le Sous-titre

```php
return view('ma-page', [
    'pageTitle' => 'Mon Titre',
    'pageSubtitle' => null, // Affichera la date/heure par défaut
]);
```

### Breadcrumbs avec Icônes

```blade
@php
$breadcrumbs = [
    [
        'label' => '🏠 Accueil', 
        'url' => route('dashboard')
    ],
    [
        'label' => '⚡ Bornes', 
        'url' => route('charging-points.index')
    ],
    ['label' => 'Détails']
];
@endphp
```

### Statistiques Personnalisées

```php
// Vous pouvez ajouter vos propres calculs
$headerStats = [
    'onlinePoints' => $customCalculation,
    'totalPoints' => $anotreCalcul,
    // ... autres stats
];
```

## 🌍 Support Multilingue

Le header supporte automatiquement 4 langues :

```php
// Français
'pageTitle' => 'Tableau de bord',

// Anglais
'pageTitle' => __('messages.dashboard'),

// Arabe (avec RTL automatique)
'pageTitle' => 'لوحة القيادة',

// Espagnol
'pageTitle' => 'Panel de control',
```

## 📱 Responsive Design

Le header s'adapte automatiquement :

- **Mobile (< 640px)** : Menu hamburger, stats empilées
- **Tablette (640-1024px)** : Layout intermédiaire
- **Desktop (> 1024px)** : Affichage complet

## 🎨 Éléments du Header

### Toujours Présents
1. **Avatar utilisateur** avec initiales
2. **Indicateur de statut** (en ligne)
3. **Titre de la page**
4. **Bouton menu mobile** (sur mobile)
5. **Notifications** avec badge
6. **Actions rapides** (dropdown)
7. **Menu utilisateur** (profil, paramètres, déconnexion)

### Conditionnels
1. **Sous-titre** (si fourni)
2. **Fil d'Ariane** (si fourni)
3. **4 Cartes statistiques** (si `$showHeaderStats = true`)

## 🔧 Dépannage

### Le header ne s'affiche pas

**Vérifiez** :
1. Le layout utilisé est bien `layouts.app`
2. Le composant `app-header.blade.php` existe
3. Alpine.js est chargé (pour les dropdowns)

```blade
@extends('layouts.app') <!-- ✅ Correct -->
@extends('layouts.guest') <!-- ❌ Pas de header -->
```

### Les statistiques ne s'affichent pas

**Vérifiez** :
1. `$showHeaderStats` est bien à `true`
2. `$headerStats` est bien un tableau
3. Les clés du tableau correspondent aux noms attendus

```php
// ✅ Correct
'showHeaderStats' => true,
'headerStats' => ['onlinePoints' => 16, ...]

// ❌ Incorrect
'showStats' => true, // Mauvais nom de variable
```

### Les breadcrumbs ne s'affichent pas

**Vérifiez** :
1. `$breadcrumbs` est bien un tableau
2. Chaque élément a une clé `label`
3. Les URLs sont valides

```php
// ✅ Correct
$breadcrumbs = [
    ['label' => 'Accueil', 'url' => route('dashboard')],
    ['label' => 'Page']
];

// ❌ Incorrect
$breadcrumbs = ['Accueil', 'Page']; // Pas de structure
```

## 🎯 Bonnes Pratiques

### ✅ À Faire

1. **Passer les variables depuis le contrôleur** (plus propre)
2. **Utiliser les traductions** pour le multilingue
3. **Fournir des breadcrumbs** pour améliorer la navigation
4. **Calculer les stats en temps réel** (pas de valeurs statiques)

### ❌ À Éviter

1. **Ne pas dupliquer le header** dans les vues
2. **Ne pas modifier directement** `app-header.blade.php` pour une page
3. **Ne pas oublier** `@extends('layouts.app')`
4. **Ne pas passer de HTML** dans les titres (risque XSS)

## 📚 Ressources

- **Composant** : `resources/views/components/app-header.blade.php`
- **Layout** : `resources/views/layouts/app.blade.php`
- **CSS** : `public/css/header-fix.css`
- **Traductions** : `resources/lang/{fr,en,ar,es}/dashboard.php`

## 🆘 Support

Si vous rencontrez des problèmes :

1. Vérifiez les logs Laravel : `storage/logs/laravel.log`
2. Inspectez le HTML généré dans le navigateur
3. Vérifiez la console JavaScript pour les erreurs Alpine.js
4. Consultez `HEADER_FIX_SUMMARY.md` pour l'architecture

---

**Dernière mise à jour** : 21 décembre 2025
**Version** : 1.0
**Auteur** : Équipe EVON

