# Système de Transactions Hiérarchiques

## 📋 Vue d'ensemble

Ce système implémente la gestion des transactions entre plusieurs types d'utilisateurs (Admin, Intégrateur, Opérateur) avec un système de parts, frais et double enregistrement selon vos spécifications.

## 🏗️ Architecture

### Modèles principaux

1. **Transaction** - Transaction principale de recharge
2. **TransactionDetail** - Détails des parts et frais séparés
3. **TransactionHierarchy** - Double enregistrement des transactions hiérarchiques
4. **User** - Utilisateurs avec champ balance et created_by

### Tables créées

- `transaction_details` - Stockage des frais et parts détaillées
- `transaction_hierarchies` - Double enregistrement Admin↔Intégrateur et Intégrateur↔Opérateur
- Colonnes ajoutées à `users` : `balance`, `currency`, `created_by`

## 💰 Logique métier

### Exemple de calcul (Transaction de 200 DH avec Business Profile)

```
Montant original: 200 DH

Frais basés sur le Business Profile:
- Frais de transaction: 1 DH (fixe)
- Frais de recharge: 2 DH (fixe)  
- Frais de base: 1 DH (fixe)
- Total frais: 4 DH

Montant net: 200 - 4 = 196 DH

Répartition (basée sur les commissions du Business Profile):
- Part Admin (10%): 19.6 DH
- Part Intégrateur (5%): 9.8 DH  
- Part Opérateur: 196 - 19.6 - 9.8 = 166.6 DH
```

### Double enregistrement

1. **Transaction Admin ↔ Intégrateur**
   - Payeur: MEHDI (Intégrateur)
   - Bénéficiaire: ALAA (Admin)
   - Montant: 19.6 DH

2. **Transaction Intégrateur ↔ Opérateur**
   - Payeur: ANAS (Opérateur)
   - Bénéficiaire: MEHDI (Intégrateur)
   - Montant: 9.8 DH

## 🔧 Services

### HierarchicalTransactionService

Service principal qui gère:
- Identification de la hiérarchie des utilisateurs
- **Recherche du Business Profile approprié** (borne → intégrateur → partenaire → utilisateur)
- **Calcul des frais basés sur le Business Profile** (transaction_fee, charge_fee, base_fee)
- **Calcul des parts basées sur les commissions du Business Profile**
- Création du double enregistrement
- Mise à jour des soldes
- Gestion des erreurs avec rollback

### Priorité de recherche du Business Profile

1. **Borne de recharge** : `charging_point.business_profile_id`
2. **Intégrateur de la borne** : `charging_point.integrator.business_profile`
3. **Partenaire de la borne** : `charging_point.partner.business_profile`
4. **Intégrateur de l'utilisateur** : `user.integrator.business_profile`
5. **Partenaire de l'utilisateur** : `user.partner.business_profile`

## 📡 API Endpoints

### Traitement des transactions
- `POST /api/v1/transaction-hierarchy/process` - Traiter une transaction
- `GET /api/v1/transaction-hierarchy/summary/{id}` - Résumé d'une transaction

### Consultation des données
- `GET /api/v1/transaction-hierarchy/admin-shares` - Parts Admin détaillées
- `GET /api/v1/transaction-hierarchy/hierarchical-transactions` - Transactions hiérarchiques
- `GET /api/v1/transaction-hierarchy/user-balances` - Soldes des utilisateurs
- `GET /api/v1/transaction-hierarchy/stats` - Statistiques globales

### Actions
- `POST /api/v1/transaction-hierarchy/mark-share-paid` - Marquer une part comme payée
- `GET /api/v1/transaction-hierarchy/example-calculation` - Exemple de calcul

## 🎯 Utilisation

### 1. Traitement d'une transaction

```php
use App\Services\HierarchicalTransactionService;

$service = new HierarchicalTransactionService();
$result = $service->processTransaction($transaction);

if ($result['success']) {
    // Transaction traitée avec succès
    $transactionDetail = $result['transaction_detail'];
    $hierarchicalTransactions = $result['hierarchical_transactions'];
}
```

### 2. Consultation des parts Admin

```php
$adminShares = TransactionDetail::with(['transaction', 'adminCreator', 'integratorCreator', 'operator'])
    ->whereNotNull('admin_creator_id')
    ->get();
```

### 3. Gestion des soldes

```php
// Ajouter de l'argent
$user->addBalance(100.00);

// Retirer de l'argent
if ($user->hasSufficientBalance(50.00)) {
    $user->subtractBalance(50.00);
}
```

## 🧪 Tests

### Seeder de test

Exécuter le seeder pour créer des données de test:

```bash
php artisan db:seed --class=TransactionHierarchySeeder
```

Cela créera:
- Admin ALAA
- Intégrateur MEHDI (créé par ALAA)
- Opérateur ANAS (créé par MEHDI)
- Une transaction de 200 DH traitée avec le système hiérarchique

### Interface web

Accéder à la vue `/transactions/hierarchy-details` pour:
- Voir les parts Admin détaillées
- Consulter les transactions hiérarchiques
- Gérer les soldes des utilisateurs
- Tester l'exemple de calcul

## 📊 Monitoring

### Logs

Le système enregistre des logs détaillés pour:
- Traitement des transactions
- Mise à jour des soldes
- Erreurs et rollbacks

### Métriques disponibles

- Total des transactions
- Montant total traité
- Frais totaux collectés
- Parts Admin distribuées
- Soldes par type d'utilisateur

## 🔒 Sécurité

- Transactions atomiques avec rollback automatique
- Validation des montants et soldes
- Logs d'audit pour traçabilité
- Vérification de la hiérarchie des utilisateurs

## 🚀 Déploiement

### Migrations

```bash
php artisan migrate
```

### Tests

```bash
# Tester le système avec des données de test
php artisan transaction:hierarchy-test --create-data --amount=200

# Exécuter les tests unitaires
php artisan test tests/Feature/TransactionHierarchyWithBusinessProfileTest.php
```

### Permissions

Le système utilise les rôles Spatie existants:
- `admin` - Accès complet
- `integrator` - Gestion des opérateurs créés
- `partner` - Consultation des propres transactions

## 🔧 Configuration des Business Profiles

### Champs importants pour les frais

- `transaction_fee_amount` - Montant des frais de transaction
- `transaction_fee_type` - Type de frais ('fixed' ou 'percentage')
- `charge_fee_amount` - Frais de recharge
- `base_fee_amount` - Frais de base
- `admin_fee_percentage` - Pourcentage de la part Admin
- `integrator_fee_percentage` - Pourcentage de la part Intégrateur

### Exemple de Business Profile

```php
BusinessProfile::create([
    'name' => 'Profile Standard',
    'transaction_fee_amount' => 1.00,    // 1 DH fixe
    'transaction_fee_type' => 'fixed',
    'charge_fee_amount' => 2.00,         // 2 DH de recharge
    'base_fee_amount' => 1.00,           // 1 DH de base
    'admin_fee_percentage' => 10.0,      // 10% pour Admin
    'integrator_fee_percentage' => 5.0,  // 5% pour Intégrateur
]);
```

## 📈 Évolutions possibles

1. **Configuration dynamique** des pourcentages de parts
2. **Frais variables** selon le type de transaction
3. **Notifications** automatiques lors des paiements
4. **Rapports** détaillés par période
5. **Intégration** avec des systèmes de paiement externes
