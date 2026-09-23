# EVON - Guide des Layouts et Composants

Ce guide explique comment utiliser le nouveau système de layouts et composants EVON.

## 📋 Table des matières

1. [Structure des Layouts](#structure-des-layouts)
2. [Classes CSS Disponibles](#classes-css-disponibles)
3. [Composants EVON](#composants-evon)
4. [Utilisation](#utilisation)
5. [Responsive Design](#responsive-design)
6. [Accessibilité](#accessibilité)

## 🏗️ Structure des Layouts

### Layout Principal: `evon-dashboard.blade.php`

Le layout principal EVON offre une structure moderne avec sidebar et header.

```blade
@extends('layouts.evon-dashboard')

@section('content')
    <!-- Votre contenu ici -->
@endsection
```

### Composants du Layout

#### Sidebar (`evon-sidebar.blade.php`)
- Navigation principale verticale
- Logo EVON en haut
- Paramètres en bas
- Responsive et collapsible sur mobile

#### Header (`evon-header.blade.php`)
- Barre de recherche
- Sélecteur de langue
- Toggle dark mode
- Notifications
- Menu utilisateur

## 🎨 Classes CSS Disponibles

### Layout Classes

```css
/* Conteneurs */
.container-main         /* Conteneur principal avec marges */
.section-spacing        /* Espacement de section */

/* Sidebar */
.evon-sidebar           /* Sidebar principale */
.evon-sidebar-logo      /* Zone logo */
.evon-sidebar-nav       /* Navigation */
.evon-nav-item          /* Item de navigation */
.evon-nav-item-active   /* Item actif */

/* Header */
.evon-header            /* Header principal */
.evon-header-search     /* Barre de recherche */
.evon-header-button     /* Bouton du header */
```

### Composants UI

```css
/* Cards */
.card                   /* Card de base */
.card-hover             /* Card avec effet hover */
.card-elevated          /* Card avec élévation */
.evon-stat-card         /* Card statistique */

/* Boutons */
.btn-primary            /* Bouton principal (vert) */
.btn-secondary          /* Bouton secondaire */
.btn-danger             /* Bouton danger (rouge) */
.btn-outline            /* Bouton avec bordure */
.btn-ghost              /* Bouton transparent */

/* Formulaires */
.input-field            /* Input standard */
.form-label             /* Label de formulaire */
.form-error             /* Message d'erreur */
```

### Typographie

```css
.heading-1              /* Titre niveau 1 */
.heading-2              /* Titre niveau 2 */
.heading-3              /* Titre niveau 3 */
.body-large             /* Texte large */
.body-medium            /* Texte moyen */
.body-small             /* Texte petit */
```

### Tables

```css
.evon-table-container   /* Conteneur de table */
.evon-table             /* Table */
.evon-table-head-cell   /* Cellule d'en-tête */
.evon-table-cell        /* Cellule de données */
.evon-table-row         /* Ligne avec hover */
```

### Alerts

```css
.evon-alert-success     /* Alerte succès */
.evon-alert-warning     /* Alerte avertissement */
.evon-alert-danger      /* Alerte erreur */
.evon-alert-info        /* Alerte information */
```

## 🧩 Composants EVON

### 1. Page Header

Affiche le titre de la page avec actions optionnelles.

```blade
<x-evon.page-header 
    title="Tableau de Bord" 
    subtitle="Vue d'ensemble de vos statistiques">
    <x-slot:actions>
        <x-evon.button variant="primary">
            Nouvelle action
        </x-evon.button>
    </x-slot:actions>
</x-evon.page-header>
```

### 2. Stat Card

Carte pour afficher des statistiques.

```blade
<x-evon.stat-card 
    label="Total des utilisateurs"
    value="1,234"
    change="+12%"
    changeType="positive"
    color="green">
    <x-slot:icon>
        @include('components.icons.lucide-users', ['size' => 20])
    </x-slot:icon>
</x-evon.stat-card>
```

#### Options de couleurs
- `green` (défaut)
- `blue`
- `yellow`
- `red`
- `purple`

#### Types de changement
- `positive` : affiche en vert avec flèche montante
- `negative` : affiche en rouge avec flèche descendante

### 3. Data Table

Table de données avec tri et pagination.

```blade
<x-evon.data-table
    title="Liste des utilisateurs"
    :headers="['Nom', 'Email', 'Rôle', 'Actions']"
    :rows="$users">
    <x-slot:actions>
        <x-evon.button variant="outline" size="sm">
            Exporter
        </x-evon.button>
    </x-slot:actions>
</x-evon.data-table>
```

### 4. Button

Bouton personnalisable avec plusieurs variantes.

```blade
<!-- Bouton simple -->
<x-evon.button variant="primary">
    Sauvegarder
</x-evon.button>

<!-- Bouton avec icône -->
<x-evon.button variant="secondary">
    <x-slot:icon>
        @include('components.icons.lucide-plus', ['size' => 16])
    </x-slot:icon>
    Ajouter
</x-evon.button>

<!-- Bouton loading -->
<x-evon.button variant="primary" :loading="true">
    Enregistrement...
</x-evon.button>

<!-- Bouton lien -->
<x-evon.button variant="outline" href="/profile">
    Mon Profil
</x-evon.button>
```

#### Variantes
- `primary` : Bouton principal (vert)
- `secondary` : Bouton secondaire (gris)
- `danger` : Bouton danger (rouge)
- `outline` : Bouton avec bordure
- `ghost` : Bouton transparent

#### Tailles
- `sm` : Petit
- `md` : Moyen (défaut)
- `lg` : Grand

### 5. Alert

Affiche des messages d'alerte.

```blade
<x-evon.alert 
    type="success" 
    title="Succès"
    :dismissible="true">
    Votre opération a été effectuée avec succès.
</x-evon.alert>
```

#### Types
- `success` : Vert
- `warning` : Jaune
- `danger` : Rouge
- `info` : Bleu

### 6. Modal

Fenêtre modale personnalisable.

```blade
<x-evon.modal 
    id="delete-modal" 
    title="Confirmer la suppression"
    size="md">
    <p>Êtes-vous sûr de vouloir supprimer cet élément ?</p>
    
    <x-slot:footer>
        <x-evon.button 
            variant="secondary" 
            @click="$dispatch('delete-modal-close')">
            Annuler
        </x-evon.button>
        <x-evon.button variant="danger">
            Supprimer
        </x-evon.button>
    </x-slot:footer>
</x-evon.modal>

<!-- Bouton pour ouvrir -->
<x-evon.button @click="$dispatch('delete-modal-open')">
    Ouvrir Modal
</x-evon.button>
```

#### Tailles
- `sm` : Petit (max-w-sm)
- `md` : Moyen (max-w-lg)
- `lg` : Grand (max-w-2xl)
- `xl` : Extra large (max-w-4xl)

### 7. Loading Spinner

Indicateur de chargement.

```blade
<x-evon.loading-spinner 
    size="md" 
    message="Chargement en cours..." />
```

### 8. Empty State

État vide pour listes/tables.

```blade
<x-evon.empty-state
    title="Aucun utilisateur"
    description="Commencez par créer votre premier utilisateur.">
    <x-slot:action>
        <x-evon.button variant="primary">
            Créer un utilisateur
        </x-evon.button>
    </x-slot:action>
</x-evon.empty-state>
```

## 📱 Responsive Design

Le système est entièrement responsive avec breakpoints :

- **Mobile** : < 640px
- **Tablet** : 640px - 1023px
- **Desktop** : >= 1024px
- **Large** : >= 1280px
- **XL** : >= 1536px

### Optimisations Mobile

- Touch targets minimum 44x44px
- Sidebar collapsible
- Grilles adaptatives
- Textes redimensionnés
- Boutons pleine largeur
- Tables scrollables

### Classes Utilitaires

```blade
<div class="evon-hide-mobile">Visible seulement sur desktop</div>
<div class="evon-show-mobile">Visible seulement sur mobile</div>
```

## ♿ Accessibilité

Toutes les composantes suivent les standards WCAG 2.1 :

- Labels ARIA appropriés
- Navigation au clavier
- Focus visible
- Contraste suffisant
- Support lecteurs d'écran
- Reduced motion support

### Exemple

```blade
<button 
    class="evon-header-button"
    aria-label="Ouvrir le menu"
    aria-expanded="false"
    :aria-expanded="open.toString()">
    <!-- Icône -->
</button>
```

## 🎯 Exemple Complet

```blade
@extends('layouts.evon-dashboard')

@section('content')
    <!-- Page Header -->
    <x-evon.page-header 
        title="Tableau de Bord" 
        subtitle="Vue d'ensemble de vos statistiques">
        <x-slot:actions>
            <x-evon.button variant="outline">
                Exporter
            </x-evon.button>
            <x-evon.button variant="primary">
                Nouvelle action
            </x-evon.button>
        </x-slot:actions>
    </x-evon.page-header>

    <!-- Stats Grid -->
    <div class="evon-stats-grid mb-8">
        <x-evon.stat-card 
            label="Total Utilisateurs"
            value="1,234"
            change="+12%"
            changeType="positive"
            color="green">
            <x-slot:icon>
                @include('components.icons.lucide-users', ['size' => 20])
            </x-slot:icon>
        </x-evon.stat-card>
        
        <!-- Autres cards... -->
    </div>

    <!-- Data Table -->
    <x-evon.data-table
        title="Utilisateurs Récents"
        :headers="['Nom', 'Email', 'Rôle', 'Date']"
        :rows="$users">
        <x-slot:actions>
            <x-evon.button variant="outline" size="sm">
                Filtrer
            </x-evon.button>
        </x-slot:actions>
    </x-evon.data-table>
@endsection
```

## 🚀 Compilation des Assets

Pour compiler les nouveaux styles :

```bash
# Développement
npm run dev

# Production
npm run build

# Watch mode
npm run watch
```

## 📝 Notes Importantes

1. **AlpineJS** : Tous les composants utilisent Alpine.js pour l'interactivité
2. **Dark Mode** : Support complet du mode sombre
3. **RTL Support** : Support RTL pour l'arabe
4. **Performance** : CSS optimisé et minifié en production
5. **Compatibilité** : Testé sur Chrome, Firefox, Safari, Edge

## 🐛 Dépannage

### Les styles ne s'appliquent pas

```bash
# Effacer le cache
php artisan view:clear
php artisan cache:clear

# Recompiler les assets
npm run build
```

### Problèmes de responsive

Vérifiez que le viewport meta tag est présent :

```html
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
```

## 📞 Support

Pour toute question ou problème, consultez la documentation ou contactez l'équipe de développement.

