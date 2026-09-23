# 📋 Gestion de la Durée Maximale dans les Plans Tarifaires

## 🎯 Vue d'ensemble

La **durée maximale** est un paramètre obligatoire dans la création et modification des plans tarifaires. Elle définit la limite de temps autorisée pour une session de recharge.

## ⚙️ Configuration

### 📊 Paramètres techniques

- **Type de données** : `integer` (minutes)
- **Valeur minimale** : 20 minutes
- **Valeur maximale** : 120 minutes
- **Valeur par défaut** : 120 minutes
- **Obligatoire** : ✅ Oui

### 🔧 Validation

```php
'max_duration' => ['required', 'integer', 'min:20', 'max:120']
```

### 📝 Messages d'erreur personnalisés

- `required` : "La durée maximale est obligatoire."
- `integer` : "La durée maximale doit être un nombre entier."
- `min` : "La durée maximale doit être d'au moins 20 minutes."
- `max` : "La durée maximale ne peut pas dépasser 120 minutes."

## 🖥️ Interface utilisateur

### 📱 Formulaire de création (`/plans/create`)

```html
<label for="max_duration">Durée maximale (en minutes) <span class="text-red-500">*</span></label>
<input 
    type="number" 
    name="max_duration" 
    id="max_duration"
    min="20" 
    max="120" 
    value="120" 
    required
    placeholder="Ex: 120"
>
<div class="text-sm text-gray-500">
    <i class="fas fa-info-circle mr-1"></i>
    Durée maximale autorisée pour une session de recharge. Valeur entre 20 et 120 minutes.
</div>
```

### ✏️ Formulaire d'édition (`/plans/edit`)

Même structure que le formulaire de création, avec la valeur actuelle du plan pré-remplie.

## 🗄️ Base de données

### 📋 Table `pricing_plans`

```sql
CREATE TABLE pricing_plans (
    -- ... autres colonnes
    max_duration INTEGER NOT NULL CHECK (max_duration >= 20 AND max_duration <= 120),
    -- ... autres colonnes
);
```

### 🔍 Migration

```php
$table->integer('max_duration')->nullable(); // in minutes
```

## 🔄 Utilisation dans l'application

### 📋 Réservations

La durée maximale est utilisée pour valider les réservations :

```php
// Validation côté serveur
if ($reservationType === 'minute' && $plan && $plan->max_duration) {
    if ($value > $plan->max_duration) {
        return response()->json([
            'message' => "La durée de réservation ne peut pas dépasser {$plan->max_duration} minutes.",
            'error' => 'limit_exceeded'
        ], 422);
    }
}
```

### ⚡ Sessions de recharge

```php
// Validation dans CreateChargingSessionRequest
if ($pricingPlan->max_duration && $value > $pricingPlan->max_duration) {
    $fail("The duration in minutes cannot exceed the maximum allowed by the pricing plan ({$pricingPlan->max_duration} minutes).");
}
```

## 🎨 Interface utilisateur

### 📱 Validation côté client

```javascript
// Validation JavaScript dans les réservations
if (type === 'minute' && plan.max_duration && parseFloat(value) > plan.max_duration) {
    showError(`La durée de réservation ne peut pas dépasser ${plan.max_duration} minutes.`);
    return;
}
```

### 🔢 Calculs d'énergie

Pour les réservations en kWh, la durée maximale est utilisée pour calculer la limite d'énergie :

```javascript
if (type === 'kwh' && plan.max_duration && chargingPoint.power_output) {
    const maxEnergy = chargingPoint.power_output * (plan.max_duration / 60);
    if (parseFloat(value) > maxEnergy) {
        showError(`La quantité d'énergie ne peut pas dépasser ${maxEnergy.toFixed(2)} kWh.`);
        return;
    }
}
```

## 🎯 Application dans les Réservations

### 📱 Interface de création de réservation

La durée maximale est appliquée dans la vue de création de réservation (`resources/views/reservations/create.blade.php`) :

#### 🔍 Affichage des limites

```php
@if($plan->max_duration)
<div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-4 mb-4">
    <div class="flex items-center space-x-3">
        <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-clock text-amber-600 text-sm"></i>
        </div>
        <div class="flex-1">
            <h4 class="font-semibold text-amber-800 text-sm">Limite de durée</h4>
            <p class="text-amber-700 text-xs">
                @if($hasKwhPricing && !$hasMinutePricing)
                    Durée maximale : {{ $maxDuration }} minutes 
                    @if($maxEnergy)
                        ({{ number_format($maxEnergy, 1) }} kWh max)
                    @endif
                @else
                    Durée maximale : {{ $maxDuration }} minutes
                @endif
            </p>
        </div>
    </div>
</div>
@endif
```

#### ⚡ Validation en temps réel

```javascript
function validateLimits() {
    const type = document.querySelector('input[name="reservation_type"]:checked')?.value;
    const value = parseFloat(reservationValueInput.value) || 0;
    const plan = @json($plan ?? null);
    const chargingPoint = @json($chargingPoint ?? null);
    
    if (plan && plan.max_duration) {
        if (type === 'minute' && value > plan.max_duration) {
            // Afficher erreur
            showLimitError(`La durée ne peut pas dépasser ${plan.max_duration} minutes.`);
        } else if (type === 'kwh' && chargingPoint && chargingPoint.power_output) {
            const maxEnergy = (plan.max_duration / 60) * chargingPoint.power_output;
            if (value > maxEnergy) {
                showLimitError(`L'énergie ne peut pas dépasser ${maxEnergy.toFixed(1)} kWh (${plan.max_duration} min max).`);
            }
        }
    }
}
```

## 🚀 Fonctionnalités

### ✅ Validation en temps réel

- Validation côté client avec HTML5
- Validation côté serveur avec Laravel
- Messages d'erreur personnalisés en français

### 🔄 Cohérence des données

- Valeur par défaut : 120 minutes
- Contraintes : 20-120 minutes
- Validation obligatoire

### 📊 Affichage informatif

- Icône d'information
- Description claire de l'utilisation
- Placeholder avec exemple

## 🔧 Maintenance

### 📝 Modifications récentes

1. **Harmonisation des validations** : Correction de l'incohérence entre les vues et les contrôleurs
2. **Messages d'erreur** : Ajout de messages personnalisés en français
3. **Interface utilisateur** : Amélioration de l'affichage avec des informations contextuelles
4. **Correction des valeurs NULL** : Script SQL pour corriger les durées maximales NULL dans les plans existants
5. **Affichage cohérent** : Remplacement de "N/A" et "Illimitée" par "Non définie" pour plus de clarté

### 🎯 Bonnes pratiques

- Toujours valider côté serveur ET côté client
- Utiliser des messages d'erreur clairs et informatifs
- Maintenir la cohérence entre les différentes parties de l'application
- Documenter les contraintes métier

## 📞 Support

Pour toute question concernant la durée maximale des plans tarifaires, consultez :

- Les logs d'erreur de validation
- La documentation des modèles `PricingPlan` et `Plan`
- Les tests unitaires pour la validation

## 🔧 Correction des problèmes

### ❌ Problème "N/A" ou valeurs NULL

Si vous voyez "N/A" ou des valeurs NULL pour la durée maximale :

1. **Exécutez le script SQL** : `fix_max_duration_immediate.sql`
2. **Exécutez la migration** : `php artisan migrate`
3. **Vérifiez les données** : Consultez les logs de migration

### 📊 Script de correction

Le fichier `fix_max_duration_immediate.sql` contient :
- Vérification des plans actuels
- Correction des valeurs NULL → 120 minutes
- Correction des valeurs 0 → 120 minutes
- Correction des valeurs < 20 → 20 minutes
- Correction des valeurs > 120 → 120 minutes
- Vérification finale et statistiques

### 🧪 Tests de validation

Le fichier `test_max_duration_limits.php` contient des tests complets pour vérifier :
- Validation côté serveur
- Validation côté client (JavaScript)
- Affichage des limites dans l'interface
- Calculs d'énergie basés sur la durée maximale
