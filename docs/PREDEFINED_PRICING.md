# Documentation - Prix Prédéfinis (Week-end et Nuit)

## Vue d'ensemble

Cette implémentation améliore la gestion des prix prédéfinis pour les plans tarifaires, permettant d'activer et de configurer des tarifs supplémentaires pour les week-ends et les heures de nuit.

## Architecture

### Service Principal : `PredefinedPricingService`

Le service `App\Services\PredefinedPricingService` centralise toute la logique de calcul et de validation pour les prix prédéfinis.

#### Méthodes principales :

- `calculatePredefinedRates()` : Calcule les prix supplémentaires pour une transaction
- `calculateWeekendPrice()` : Calcule spécifiquement le prix week-end
- `calculateNightPrice()` : Calcule spécifiquement le prix de nuit
- `validatePredefinedPricing()` : Valide les paramètres de configuration
- `isWeekend()` : Vérifie si une date est un week-end
- `isNightTime()` : Vérifie si une date/heure est dans une période de nuit

### Modèle Plan

Le modèle `App\Models\Plan` a été amélioré avec :

- Correction du cast pour `night_start_time` et `night_end_time` (maintenant des strings au format H:i:s)
- Accessors pour garantir le format correct des heures
- Méthodes helper : `hasPredefinedPricing()`, `getPredefinedPricingSummary()`
- Intégration du service dans `calculatePrice()` pour un calcul précis

### Validation

Les FormRequests (`StorePlanRequest` et `UpdatePlanRequest`) incluent maintenant :

- Validation conditionnelle : les prix sont requis si les options sont activées
- Validation des formats d'heure (H:i)
- Messages d'erreur personnalisés en français

## Utilisation

### Configuration d'un plan avec prix prédéfinis

```php
$plan = Plan::create([
    'name' => 'Plan Premium',
    'rate_type' => 'energy',
    'price_per_kwh' => 0.25,
    
    // Activer les prix week-end
    'has_weekend_pricing' => true,
    'weekend_price' => 0.10, // Prix supplémentaire par kWh le week-end
    
    // Activer les prix de nuit
    'has_night_pricing' => true,
    'night_price' => 0.05, // Prix supplémentaire par kWh la nuit
    'night_start_time' => '22:00:00',
    'night_end_time' => '06:00:00',
]);
```

### Calcul du prix pour une transaction

```php
use Carbon\Carbon;

$plan = Plan::find(1);

// Calcul avec date/heure de début et de fin
$startDateTime = Carbon::parse('2024-01-13 23:00:00'); // Samedi soir
$endDateTime = Carbon::parse('2024-01-14 01:00:00'); // Dimanche matin

$result = $plan->calculatePrice(
    energyKwh: 20.5,
    durationMinutes: 120,
    startDateTime: $startDateTime,
    endDateTime: $endDateTime
);

// Résultat :
// [
//     'base_price' => 5.13, // 20.5 kWh * 0.25 €/kWh
//     'additional_rates' => [
//         'weekend' => [
//             'enabled' => true,
//             'price' => 2.05, // 20.5 kWh * 0.10 €/kWh
//             'applied' => true,
//             'details' => [...]
//         ],
//         'night' => [
//             'enabled' => true,
//             'price' => 1.03, // Proportion d'énergie la nuit
//             'applied' => true,
//             'details' => [...]
//         ],
//         'total' => 3.08
//     ],
//     'additional_price' => 3.08,
//     'vat_amount' => ...,
//     'total_price' => ...,
//     'currency' => 'EUR'
// ]
```

### Utilisation directe du service

```php
use App\Services\PredefinedPricingService;
use Carbon\Carbon;

$service = app(PredefinedPricingService::class);
$plan = Plan::find(1);

$result = $service->calculatePredefinedRates(
    plan: $plan,
    energyKwh: 15.0,
    durationMinutes: 60,
    startDateTime: Carbon::now(),
    endDateTime: Carbon::now()->addHour()
);
```

### Vérification des prix prédéfinis

```php
$plan = Plan::find(1);

// Vérifier si des prix prédéfinis sont activés
if ($plan->hasPredefinedPricing()) {
    // Obtenir un résumé
    $summary = $plan->getPredefinedPricingSummary();
    // [
    //     'weekend' => [
    //         'enabled' => true,
    //         'price' => 0.10,
    //         'description' => 'Prix supplémentaire appliqué les samedis et dimanches'
    //     ],
    //     'night' => [
    //         'enabled' => true,
    //         'price' => 0.05,
    //         'start_time' => '22:00:00',
    //         'end_time' => '06:00:00',
    //         'description' => 'Prix supplémentaire appliqué de 22:00:00 à 06:00:00'
    //     ]
    // ]
}
```

## Types de tarifs supportés

Les prix prédéfinis fonctionnent avec tous les types de tarifs :

### Tarif à l'énergie (energy/kwh)
- Le prix supplémentaire est multiplié par l'énergie consommée
- Pour les transactions qui chevauchent plusieurs périodes, l'énergie est répartie proportionnellement

### Tarif au temps (time/minute)
- Le prix supplémentaire est multiplié par les minutes dans la période concernée
- Calcul précis des minutes pour chaque période (week-end ou nuit)

### Tarif fixe (fixed)
- Le prix supplémentaire est appliqué une fois si la transaction touche la période concernée

## Gestion des périodes qui chevauchent

L'implémentation gère correctement les cas où une transaction s'étend sur plusieurs périodes :

- **Week-end** : Si une transaction commence le vendredi soir et se termine le dimanche, le prix week-end est appliqué pour la partie week-end
- **Nuit** : Si une transaction commence à 23h et se termine à 7h, le prix de nuit est calculé pour les heures de nuit (23h-6h)
- **Combinaison** : Si une transaction touche à la fois le week-end ET la nuit, les deux prix supplémentaires sont appliqués

## Validation

### Dans les FormRequests

La validation est automatique via `StorePlanRequest` et `UpdatePlanRequest` :

```php
// Si has_weekend_pricing est activé, weekend_price devient requis
'weekend_price' => [
    'nullable',
    'numeric',
    'min:0',
    'required_if:has_weekend_pricing,1,true',
],

// Si has_night_pricing est activé, les heures deviennent requises
'night_start_time' => [
    'nullable',
    'date_format:H:i',
    'required_if:has_night_pricing,1,true',
],
```

### Validation supplémentaire via le service

Le contrôleur utilise également le service pour une validation métier :

```php
$pricingService = app(PredefinedPricingService::class);
$validation = $pricingService->validatePredefinedPricing($data);

if (!$validation['valid']) {
    // Gérer les erreurs
    return redirect()->back()
        ->withErrors($validation['errors'])
        ->withInput();
}
```

## Migration de la base de données

Les champs suivants ont été ajoutés à la table `pricing_plans` :

- `weekend_price` : decimal(8,4) - Prix supplémentaire pour les week-ends
- `night_price` : decimal(8,4) - Prix supplémentaire pour les heures de nuit
- `has_weekend_pricing` : boolean - Activer les prix week-end
- `has_night_pricing` : boolean - Activer les prix de nuit
- `night_start_time` : time - Heure de début des tarifs de nuit (défaut: 22:00:00)
- `night_end_time` : time - Heure de fin des tarifs de nuit (défaut: 06:00:00)

Migration : `2025_01_15_000000_add_predefined_additional_rates_to_pricing_plans.php`

## Améliorations apportées

1. **Service dédié** : Logique centralisée et testable
2. **Gestion des périodes qui chevauchent** : Calcul précis pour les transactions longues
3. **Validation robuste** : Validation conditionnelle et métier
4. **Correction des casts** : Format correct pour les heures (H:i:s)
5. **Méthodes helper** : Facilite l'utilisation dans le code
6. **Documentation complète** : Code commenté et documentation

## Exemples de cas d'usage

### Cas 1 : Transaction uniquement en semaine
- Début : Lundi 10h00
- Fin : Lundi 11h00
- Résultat : Aucun prix supplémentaire

### Cas 2 : Transaction uniquement le week-end
- Début : Samedi 14h00
- Fin : Samedi 15h00
- Résultat : Prix week-end appliqué

### Cas 3 : Transaction qui traverse la nuit
- Début : Vendredi 23h00
- Fin : Samedi 07h00
- Résultat : Prix de nuit (23h-6h) + Prix week-end (pour la partie samedi)

### Cas 4 : Transaction qui traverse le week-end
- Début : Vendredi 20h00
- Fin : Dimanche 10h00
- Résultat : Prix week-end pour la partie week-end (samedi et dimanche)

## Notes importantes

- Les heures sont stockées au format `H:i:s` (ex: `22:00:00`)
- Les périodes de nuit peuvent s'étendre sur deux jours (ex: 22h-6h)
- Le calcul est proportionnel pour les transactions qui chevauchent plusieurs périodes
- Les prix supplémentaires sont additionnés au prix de base (pas de remplacement)

