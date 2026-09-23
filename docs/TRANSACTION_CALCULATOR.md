# Service TransactionCalculator

## Vue d'ensemble

Le service `TransactionCalculator` est un service centralisé pour calculer la répartition des montants de transaction entre les différents acteurs (admin, intégrateur, opérateur) selon la logique métier spécifiée.

## Logique de calcul

### 1. Part Admin
La part admin est calculée selon les frais définis dans le `BusinessProfile` du point de charge :
- **Frais fixes** : `transaction_fee + recharge_fee + other_fees`
- **Frais en pourcentage** : Si `admin_percentage` est défini, utilise le pourcentage du montant total
- **Priorité** : Le montant le plus élevé entre les frais fixes et le pourcentage est retenu

### 2. Part Intégrateur
La part intégrateur est calculée selon deux scénarios :

#### Scénario A : Opérateur créé par intégrateur
- Utilise le `BusinessProfile` de l'intégrateur
- Applique `integrator_percentage` ou `integrator_fee_fixed`

#### Scénario B : Borne créée directement par intégrateur
- L'intégrateur prend sa part selon son `BusinessProfile`
- Pas d'opérateur intermédiaire

### 3. Part Opérateur
- **Calcul** : `montant_total - part_admin - part_integrator`
- **Protection** : Ne peut pas être négative (minimum 0€)

## Utilisation

### Calcul basique
```php
use App\Services\TransactionCalculator;

$calculator = new TransactionCalculator();
$result = $calculator->calculate($transaction);

// Résultat :
// [
//     'total' => 10.00,
//     'admin' => 2.50,
//     'integrator' => 1.50,
//     'operator' => 6.00
// ]
```

### Calcul détaillé
```php
$detailedResult = $calculator->calculateDetailed($transaction);

// Inclut les détails du calcul :
// - Breakdown des frais admin
// - Détails intégrateur
// - Détails opérateur
// - Informations business profile
```

### Création/sauvegarde de répartition
```php
$repartition = $calculator->createRepartition($transaction);

// Crée ou met à jour automatiquement la répartition en base
// Retourne une instance de TransactionRepartition
```

## Scénarios de test couverts

### 1. Scénario Admin-only
- Opérateur créé par admin
- Seuls les frais admin sont appliqués
- Intégrateur = 0€

### 2. Scénario Intégrateur + Opérateur
- Opérateur créé par intégrateur
- Frais intégrateur appliqués et déduits de l'opérateur
- Répartition : Admin + Intégrateur + Opérateur

### 3. Scénario Borne créée par intégrateur
- Pas d'opérateur intermédiaire
- Intégrateur prend sa part selon son business profile
- Frais admin toujours appliqués

### 4. Gestion des cas limites
- Business profile manquant
- Montants négatifs pour l'opérateur
- Validation de cohérence

## Commandes Artisan

### Recalculer toutes les répartitions
```bash
php artisan transactions:recalculate-repartitions
```

### Recalculer une transaction spécifique
```bash
php artisan transactions:recalculate-repartitions --transaction-id=123
```

### Options disponibles
- `--batch-size=100` : Nombre de transactions par lot
- `--force` : Forcer le recalcul même si une répartition existe

## Structure de la base de données

### Table `transaction_repartitions`
```sql
- id (bigint, primary key)
- transaction_id (bigint, foreign key)
- admin_amount (decimal 15,2)
- integrator_amount (decimal 15,2)
- operator_amount (decimal 15,2)
- created_at (timestamp)
- updated_at (timestamp)
```

## Intégration dans les contrôleurs

### TransactionDetailsController
```php
public function show($id)
{
    $transaction = Transaction::findOrFail($id);
    
    // Calcul et sauvegarde automatique
    $repartition = $this->transactionCalculator->createRepartition($transaction);
    $calculationDetails = $this->transactionCalculator->calculateDetailed($transaction);
    
    return view('transactions.details', compact('transaction', 'repartition', 'calculationDetails'));
}
```

### TransactionHistoryController
```php
public function index()
{
    $transactions = Transaction::paginate(20);
    
    $transactions->getCollection()->transform(function ($transaction) {
        // Calcul automatique pour chaque transaction
        $repartition = $this->transactionCalculator->createRepartition($transaction);
        return $transaction;
    });
    
    return view('transactions.history', compact('transactions'));
}
```

## Tests unitaires

Les tests couvrent tous les scénarios métier :
- Calculs avec différents business profiles
- Gestion des cas d'erreur
- Validation de cohérence
- Création et mise à jour des répartitions

```bash
php artisan test tests/Unit/Services/TransactionCalculatorTest.php
```

## Avantages

1. **Centralisé** : Un seul endroit pour la logique de calcul
2. **Réutilisable** : Utilisable dans tous les contrôleurs
3. **Testable** : Tests unitaires complets
4. **Maintenable** : Code clair et documenté
5. **Robuste** : Gestion des cas d'erreur
6. **Performant** : Calculs optimisés
7. **Traçable** : Détails complets du calcul

## Migration depuis l'ancien système

Pour migrer les répartitions existantes :

```bash
# Recalculer toutes les répartitions
php artisan transactions:recalculate-repartitions --force

# Vérifier la cohérence
php artisan transactions:recalculate-repartitions --transaction-id=1
```
