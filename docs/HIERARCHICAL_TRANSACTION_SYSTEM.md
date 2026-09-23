# Système de Transactions Hiérarchiques

## 🎯 Vue d'ensemble

Le système de transactions hiérarchiques implémente une logique de double débit automatique lors de la fin d'une session de charge, respectant la hiérarchie organisationnelle :

- **Opérateur** → débité selon le profil créé par l'**Intégrateur**
- **Intégrateur** → débité selon le profil créé par l'**Admin**

## 🔄 Flux de Transaction

### 1️⃣ Identification de la Hiérarchie

```php
$hierarchy = $this->identifyHierarchy($chargingPointId);
// Retourne: charging_point, group, partner, integrator, operator, admin
```

### 2️⃣ Récupération des Business Profiles

#### Profil Opérateur (Intégrateur → Opérateur)
```php
$operatorProfile = BusinessProfile::where('created_by', $integrator->user_id)
    ->where('applies_to', 'operator')
    ->where('is_active', true)
    ->first();
```

#### Profil Intégrateur (Admin → Intégrateur)
```php
$integratorProfile = BusinessProfile::where('created_by', $admin->id)
    ->where('applies_to', 'integrator')
    ->where('is_active', true)
    ->first();
```

### 3️⃣ Calcul des Montants

```php
// Calcul pour l'opérateur (profil intégrateur)
$operatorAmount = $this->calculateAmountFromProfile(
    $operatorProfile,
    $energyDelivered,
    $duration
);

// Calcul pour l'intégrateur (profil admin)
$integratorAmount = $this->calculateAmountFromProfile(
    $integratorProfile,
    $energyDelivered,
    $duration
);
```

### 4️⃣ Débits avec Rollback

```php
// Débit opérateur
$operatorTransaction = $operatorWallet->debit($operatorAmount, $description);

// Débit intégrateur (avec rollback si échec)
if (!$integratorWallet->canDebit($integratorAmount)) {
    $this->rollbackTransaction($operatorTransaction);
    throw new Exception('Solde insuffisant intégrateur');
}
```

### 5️⃣ Transactions de Trace

```php
$traceTransaction = Transaction::create([
    'reference_id' => $walletTransaction->id,
    'charging_point_id' => $session->charging_point_id,
    'session_id' => $session->id,
    'type' => 'hierarchical_debit',
    'metadata' => [
        'role' => 'operator|integrator',
        'wallet_transaction_id' => $walletTransaction->id,
        'charging_session_id' => $session->id
    ]
]);
```

## 🛠️ Services Principaux

### HierarchicalTransactionService

**Responsabilités :**
- Identification de la hiérarchie complète
- Récupération des profils business
- Calcul des montants selon les profils
- Exécution des débits avec rollback
- Création des transactions de trace

**Méthodes principales :**
```php
public function processHierarchicalTransaction(ChargingSession $session): array
protected function identifyHierarchy(int $chargingPointId): ?array
protected function getBusinessProfiles(array $hierarchy): array
protected function calculateHierarchicalAmounts(ChargingSession $session, array $businessProfiles): array
protected function performHierarchicalDebits(array $hierarchy, array $amounts, ChargingSession $session): array
protected function createTraceTransactions(array $transactions, ChargingSession $session): array
```

### ChargingSessionCompletionService

**Responsabilités :**
- Traitement automatique des sessions terminées
- Vérification des prérequis
- Statistiques des transactions
- Traitement en lot des sessions en attente

**Méthodes principales :**
```php
public function processSessionCompletion(ChargingSession $session): array
public function processAllPendingSessions(): array
public function checkPrerequisites(ChargingSession $session): array
public function getTransactionStatistics(int $chargingPointId = null): array
```

## 🔧 Intégration Automatique

### Event Listener

```php
// ChargingSessionCompletedListener
public function handle(ChargingSessionCompleted $event): void
{
    ProcessHierarchicalTransactionsJob::dispatch(
        $event->session->charging_point_id, 
        $event->session->id
    )->delay(now()->addSeconds(30));
}
```

### Job de Traitement

```php
// ProcessHierarchicalTransactionsJob
public function handle(ChargingSessionCompletionService $completionService): void
{
    $result = $completionService->processSessionCompletion($session);
    // Logging et gestion d'erreurs
}
```

### Commande Artisan

```bash
# Traiter toutes les sessions en attente
php artisan transactions:process-hierarchical

# Traiter une session spécifique
php artisan transactions:process-hierarchical --session=123

# Traiter un point de charge spécifique
php artisan transactions:process-hierarchical --charging-point=456

# Forcer le retraitement
php artisan transactions:process-hierarchical --force
```

## 📊 API Endpoints

### Traitement de Session

```http
POST /api/hierarchical-transactions/process-session/{sessionId}
```

**Réponse :**
```json
{
    "success": true,
    "message": "Transaction hiérarchique traitée avec succès",
    "data": {
        "transactions": {
            "operator": { "id": 123, "amount": 15.50 },
            "integrator": { "id": 124, "amount": 20.00 }
        },
        "trace_transactions": {
            "operator": { "id": 125, "reference_id": 123 },
            "integrator": { "id": 126, "reference_id": 124 }
        },
        "amounts": {
            "operator": 15.50,
            "integrator": 20.00
        }
    }
}
```

### Vérification des Soldes

```http
GET /api/hierarchical-transactions/check-balances/{chargingPointId}
```

**Réponse :**
```json
{
    "success": true,
    "data": {
        "hierarchy": {
            "operator": "John Doe",
            "integrator": "ACME Corp",
            "partner": "Partner Name"
        },
        "balances": {
            "operator": {
                "balance": 150.00,
                "formatted": "150,00 €"
            },
            "integrator": {
                "balance": 500.00,
                "formatted": "500,00 €"
            }
        }
    }
}
```

### Historique des Transactions

```http
GET /api/hierarchical-transactions/history/{chargingPointId}?limit=50
```

### Simulation de Transaction

```http
POST /api/hierarchical-transactions/simulate/{chargingPointId}
Content-Type: application/json

{
    "energy_delivered": 10.5,
    "duration": 3600
}
```

## 🗄️ Structure de la Base de Données

### Table `charging_sessions` (nouvelles colonnes)

```sql
ALTER TABLE charging_sessions ADD COLUMN hierarchical_transaction_processed BOOLEAN DEFAULT FALSE;
ALTER TABLE charging_sessions ADD COLUMN hierarchical_transaction_processed_at TIMESTAMP NULL;
ALTER TABLE charging_sessions ADD COLUMN hierarchical_transaction_data JSON NULL;
```

### Table `transactions` (transactions de trace)

```sql
CREATE TABLE transactions (
    id BIGINT PRIMARY KEY,
    reference_id BIGINT, -- ID de la transaction wallet
    charging_point_id BIGINT,
    session_id BIGINT,
    user_id BIGINT,
    user_type VARCHAR(255),
    amount DECIMAL(10,2),
    currency VARCHAR(3),
    type VARCHAR(50), -- 'hierarchical_debit'
    status VARCHAR(20), -- 'completed', 'failed', 'pending'
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## 🔒 Gestion des Erreurs et Rollback

### Mécanisme de Rollback

```php
protected function rollbackTransaction(WalletTransaction $transaction): void
{
    $wallet = $transaction->wallet;
    $wallet->credit(
        $transaction->amount,
        "Rollback - Transaction {$transaction->id}"
    );
}
```

### Scénarios d'Erreur

1. **Solde opérateur insuffisant** → Transaction échoue immédiatement
2. **Solde intégrateur insuffisant** → Rollback du débit opérateur
3. **Profil business manquant** → Transaction échoue avec message explicite
4. **Hiérarchie incomplète** → Transaction échoue avec diagnostic

### Logging et Monitoring

```php
Log::info('Transaction hiérarchique réussie', [
    'session_id' => $session->id,
    'charging_point_id' => $session->charging_point_id,
    'operator_amount' => $amounts['operator'],
    'integrator_amount' => $amounts['integrator']
]);
```

## 🧪 Tests

### Test de Transaction Réussie

```php
/** @test */
public function hierarchical_transaction_processes_correctly()
{
    // Créer la hiérarchie complète
    // Créer les profils business
    // Créer les wallets avec solde suffisant
    // Créer une session de charge
    // Traiter la transaction
    // Vérifier les débits et les traces
}
```

### Test de Rollback

```php
/** @test */
public function hierarchical_transaction_fails_with_insufficient_integrator_balance()
{
    // Créer la hiérarchie
    // Créer wallets - intégrateur avec solde insuffisant
    // Traiter la transaction
    // Vérifier l'échec et le rollback
}
```

## 📈 Statistiques et Monitoring

### Statistiques Disponibles

```php
$stats = $completionService->getTransactionStatistics();
// Retourne:
// - total_transactions
// - total_amount
// - operator_transactions
// - integrator_transactions
// - average_amount
// - by_status
// - by_date
```

### Dashboard de Monitoring

```php
// Vérifier les soldes avant transaction
$balances = $hierarchicalTransactionService->verifyBalances($hierarchy);

// Obtenir l'historique des transactions
$history = $hierarchicalTransactionService->getHierarchicalTransactionHistory($chargingPointId);
```

## 🚀 Déploiement et Configuration

### 1. Exécuter les Migrations

```bash
php artisan migrate
```

### 2. Enregistrer les Services

```php
// AppServiceProvider
$this->app->singleton(HierarchicalTransactionService::class);
$this->app->singleton(ChargingSessionCompletionService::class);
```

### 3. Configurer les Events

```php
// EventServiceProvider
protected $listen = [
    ChargingSessionCompleted::class => [
        ChargingSessionCompletedListener::class,
    ],
];
```

### 4. Programmer les Jobs

```php
// Kernel.php
protected function schedule(Schedule $schedule)
{
    // Traiter les sessions en attente toutes les heures
    $schedule->job(new ProcessHierarchicalTransactionsJob())
        ->hourly();
}
```

## ✅ Résultats Attendus

### Transaction Double Automatique

- ✅ **Opérateur débité** → selon profil Intégrateur
- ✅ **Intégrateur débité** → selon profil Admin
- ✅ **Soldes mis à jour** proprement
- ✅ **Rollback automatique** en cas d'échec
- ✅ **Traçabilité complète** avec transactions de référence
- ✅ **Gestion d'erreurs** robuste
- ✅ **Monitoring** et statistiques

Le système de transactions hiérarchiques est maintenant opérationnel et garantit l'intégrité financière de la hiérarchie organisationnelle ! 🎉
