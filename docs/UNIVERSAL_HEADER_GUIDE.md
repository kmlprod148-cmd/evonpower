# 🎨 Guide du Header Universel EVON

## Vue d'ensemble

Le **header premium** est maintenant disponible sur **toutes les pages** de votre application ! Il s'adapte automatiquement au contexte et offre une expérience utilisateur exceptionnelle.

---

## ✨ Fonctionnalités

### 🎯 Disponible Partout
- ✅ Dashboard
- ✅ Bornes de charge
- ✅ Transactions
- ✅ Réservations
- ✅ Profil utilisateur
- ✅ Paramètres
- ✅ Toutes les autres pages

### 🎨 Design Premium
- Gradient animé (Indigo → Bleu → Violet)
- 3 blobs flottants en arrière-plan
- Effets glassmorphism
- Animations fluides
- Mode sombre/clair

### 🔧 Composants Intégrés
- **Avatar utilisateur** avec statut en ligne
- **Notifications** avec badge animé
- **Actions rapides** (dropdown)
- **Menu utilisateur** (profil, paramètres, déconnexion)
- **Fil d'Ariane** (breadcrumbs)
- **Statistiques** (optionnel, pour dashboard)

---

## 🚀 Utilisation de Base

### Sur le Dashboard (avec statistiques)

```blade
@extends('layouts.app')

@section('content')
@php
    $showHeaderStats = true;  // Active les statistiques
    $pageTitle = __('dashboard.dashboard');
    $pageSubtitle = __('dashboard.realtime_overview');
@endphp

<!-- Votre contenu -->
@endsection
```

### Sur une Page Simple (sans statistiques)

```blade
@extends('layouts.app')

@section('content')
@php
    $pageTitle = "Mes Bornes de Charge";
    $pageSubtitle = "Gérez vos points de charge";
@endphp

<!-- Votre contenu -->
@endsection
```

### Avec Fil d'Ariane (Breadcrumbs)

```blade
@extends('layouts.app')

@section('content')
@php
    $pageTitle = "Détails de la Transaction";
    $breadcrumbs = [
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Transactions', 'url' => route('transactions.index')],
        ['label' => 'Transaction #12345']  // Sans URL = page actuelle
    ];
@endphp

<!-- Votre contenu -->
@endsection
```

---

## 📋 Options Disponibles

### Variables du Header

| Variable | Type | Description | Défaut |
|----------|------|-------------|--------|
| `$showHeaderStats` | Boolean | Afficher les 4 statistiques | `false` |
| `$stats` | Array | Données des statistiques | `[]` |
| `$pageTitle` | String | Titre de la page | Nom de la route |
| `$pageSubtitle` | String | Sous-titre | Date/heure actuelle |
| `$breadcrumbs` | Array | Fil d'Ariane | `[]` |

### Format des Statistiques

```php
$stats = [
    'totalPoints' => 50,           // Nombre total de bornes
    'onlinePoints' => 45,          // Bornes en ligne
    'activeTransactions' => 12,    // Sessions actives
    'todayRevenue' => 2450.00,     // Revenus du jour
    'todayTransactions' => 34,     // Transactions du jour
    'todayEnergy' => 125.5,        // Énergie distribuée (kWh)
    'availabilityRate' => 90       // Taux de disponibilité (%)
];
```

---

## 🎯 Exemples Pratiques

### Exemple 1 : Page de Liste

```blade
@extends('layouts.app')

@section('title', 'Mes Réservations')

@section('content')
@php
    $pageTitle = __('reservations.my_reservations');
    $pageSubtitle = count($reservations) . ' ' . __('reservations.total');
    $breadcrumbs = [
        ['label' => __('dashboard.home'), 'url' => route('dashboard')],
        ['label' => __('reservations.title')]
    ];
@endphp

<div class="container mx-auto px-4 py-6">
    <!-- Liste des réservations -->
    @foreach($reservations as $reservation)
        <!-- ... -->
    @endforeach
</div>
@endsection
```

### Exemple 2 : Page de Détails

```blade
@extends('layouts.app')

@section('title', 'Détails de la Borne')

@section('content')
@php
    $pageTitle = $chargingPoint->name;
    $pageSubtitle = $chargingPoint->city . ' - ' . $chargingPoint->status;
    $breadcrumbs = [
        ['label' => __('dashboard.home'), 'url' => route('dashboard')],
        ['label' => __('charging_points.title'), 'url' => route('charging-points.index')],
        ['label' => $chargingPoint->name]
    ];
@endphp

<div class="container mx-auto px-4 py-6">
    <!-- Détails de la borne -->
</div>
@endsection
```

### Exemple 3 : Dashboard Personnalisé

```blade
@extends('layouts.app')

@section('content')
@php
    $showHeaderStats = true;
    $pageTitle = "Mon Dashboard Personnel";
    $stats = [
        'totalPoints' => $user->chargingPoints->count(),
        'onlinePoints' => $user->chargingPoints->where('status', 'online')->count(),
        'activeTransactions' => $activeTransactions->count(),
        'todayRevenue' => $todayRevenue,
        'todayTransactions' => $todayTransactionsCount,
        'todayEnergy' => $todayEnergySum,
        'availabilityRate' => $availabilityPercentage
    ];
@endphp

<!-- Votre dashboard -->
@endsection
```

---

## 🎨 Personnalisation

### Changer le Gradient

**Fichier :** `resources/views/components/app-header.blade.php`

```html
<!-- Ligne 11 -->
<div class="relative bg-gradient-to-br from-indigo-600 via-blue-600 to-purple-700 ...">

<!-- Modifier les couleurs : -->
from-[couleur1] via-[couleur2] to-[couleur3]

<!-- Exemples : -->
from-green-600 via-teal-600 to-cyan-700     <!-- Vert/Bleu -->
from-orange-600 via-red-600 to-pink-700     <!-- Chaud -->
from-gray-700 via-gray-800 to-gray-900      <!-- Sombre -->
```

### Masquer des Éléments

```blade
{{-- Dans le contrôleur ou la vue --}}
@php
    $hideNotifications = true;      // Masquer notifications
    $hideQuickActions = true;       // Masquer actions rapides
    $hideUserMenu = true;           // Masquer menu utilisateur
@endphp

{{-- Puis modifier le composant app-header.blade.php --}}
@if(!isset($hideNotifications) || !$hideNotifications)
    <!-- Bouton notifications -->
@endif
```

### Ajouter des Actions Personnalisées

**Dans le dropdown "Actions rapides" :**

```blade
<!-- Fichier: resources/views/components/app-header.blade.php -->
<!-- Ligne ~125 dans le dropdown -->

<a href="{{ route('ma-route') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
    <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <!-- Votre icône SVG -->
        </svg>
    </div>
    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Mon Action</span>
</a>
```

---

## 🔧 Configuration Avancée

### Statistiques Dynamiques Globales

Pour afficher les statistiques sur plusieurs pages :

```php
// Dans un Service Provider ou Middleware
View::composer('*', function ($view) {
    if (Auth::check()) {
        $view->with('globalStats', [
            'totalPoints' => Cache::remember('stats.totalPoints', 300, function() {
                return ChargingPoint::count();
            }),
            // ... autres stats
        ]);
    }
});
```

### Personnaliser par Rôle

```blade
@php
    $user = auth()->user();
    
    if($user->hasRole('admin')) {
        $pageTitle = "Dashboard Administrateur";
        $showHeaderStats = true;
    } elseif($user->hasRole('operator')) {
        $pageTitle = "Dashboard Opérateur";
        $showHeaderStats = true;
        // Stats filtrées pour l'opérateur
    } else {
        $pageTitle = "Mon Espace";
        $showHeaderStats = false;
    }
@endphp
```

---

## 📱 Responsive

Le header s'adapte automatiquement :

### Mobile (< 768px)
- Bouton menu hamburger visible
- Avatar + titre empilés verticalement
- Actions en ligne avec scroll horizontal
- Stats en 2 colonnes si activées

### Tablette (768px - 1024px)
- Menu hamburger caché
- Layout intermédiaire
- Stats en 2×2 grille

### Desktop (> 1024px)
- Tout sur une ligne
- Stats en 4 colonnes
- Espacement optimal

---

## 🎯 Cas d'Usage

### 1. Page de Formulaire

```blade
@php
    $pageTitle = "Ajouter une Borne";
    $pageSubtitle = "Configurez votre nouveau point de charge";
    $breadcrumbs = [
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Bornes', 'url' => route('charging-points.index')],
        ['label' => 'Nouvelle borne']
    ];
@endphp
```

### 2. Page de Rapport

```blade
@php
    $showHeaderStats = true;
    $pageTitle = "Rapport Mensuel";
    $pageSubtitle = Carbon::now()->format('F Y');
    $stats = [
        'totalPoints' => $totalPoints,
        'onlinePoints' => $onlinePoints,
        'activeTransactions' => 0,  // Non applicable
        'todayRevenue' => $monthRevenue,
        'todayTransactions' => $monthTransactions,
        'todayEnergy' => $monthEnergy,
        'availabilityRate' => $monthAvailability
    ];
@endphp
```

### 3. Page de Profil

```blade
@php
    $pageTitle = $user->name;
    $pageSubtitle = $user->email . ' · ' . $user->getRoleNames()->first();
    $breadcrumbs = [
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Profil']
    ];
@endphp
```

---

## 🌍 Traductions

Le header est entièrement traduit en 4 langues :
- 🇫🇷 Français
- 🇬🇧 Anglais
- 🇸🇦 Arabe (RTL)
- 🇪🇸 Espagnol

**Utiliser les traductions :**

```blade
$pageTitle = __('dashboard.my_charging_points');
$pageSubtitle = __('dashboard.manage_your_fleet');
```

**Ajouter une traduction :**

```php
// resources/lang/fr/dashboard.php
'my_charging_points' => 'Mes Bornes de Charge',
'manage_your_fleet' => 'Gérez votre flotte de véhicules',
```

---

## ⚡ Performance

### Cache des Statistiques

```php
// Dans le contrôleur
$stats = Cache::remember('dashboard.stats.user.' . $user->id, 300, function() use ($user) {
    return [
        'totalPoints' => $user->chargingPoints()->count(),
        // ... autres stats
    ];
});
```

### Lazy Loading

```blade
{{-- Charger les stats uniquement si nécessaire --}}
@if($showHeaderStats)
    @php
        $stats = app(DashboardService::class)->getStats();
    @endphp
@endif
```

---

## 🐛 Dépannage

### Le header ne s'affiche pas

**Solution :**
```bash
php artisan view:clear
php artisan config:clear
```

### Les statistiques sont vides

**Vérifier :**
1. `$showHeaderStats = true` est défini
2. `$stats` contient les données
3. Les clés correspondent : `totalPoints`, `onlinePoints`, etc.

### Les dropdowns ne s'ouvrent pas

**Vérifier :**
1. Alpine.js est chargé : `<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>`
2. Console navigateur (F12) pour erreurs JavaScript

### Le gradient ne s'affiche pas

**Vérifier :**
1. Tailwind CSS est compilé
2. Classes custom sont dans le fichier
3. Purge CSS n'a pas supprimé les classes

---

## 📚 Documentation Complète

- **QUICK_START.md** : Démarrage rapide général
- **DASHBOARD_ENHANCED_README.md** : Guide du dashboard
- **docs/ENHANCED_DASHBOARD.md** : Documentation technique
- **Ce fichier** : Guide du header universel

---

## ✅ Checklist d'Implémentation

Sur chaque nouvelle page, définissez :

- [ ] `$pageTitle` : Titre de la page
- [ ] `$pageSubtitle` (optionnel) : Sous-titre
- [ ] `$breadcrumbs` (optionnel) : Fil d'Ariane
- [ ] `$showHeaderStats` (si dashboard) : Activer stats
- [ ] `$stats` (si stats) : Données statistiques

---

## 🎉 Résultat

Avec ce header universel, toutes vos pages ont maintenant :
- ✅ Design professionnel cohérent
- ✅ Navigation fluide et intuitive
- ✅ Responsive parfait
- ✅ Animations élégantes
- ✅ Accessible et performant

**Le header s'adapte automatiquement au contexte de chaque page !** 🚀

