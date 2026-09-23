# Système de Paiement pour Intégrateurs - Documentation Technique

## Vue d'ensemble

Ce système permet aux intégrateurs tiers d'utiliser leurs propres identifiants de paiement Stripe et CMI tout en maintenant le flux de paiement existant des sessions de charge EVON.

## Architecture

### Composants Principaux

```
┌─────────────────────────────────────────────────────────────────┐
│                    Application EVON                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────┐    ┌────────────────────────────────┐ │
│  │ PaymentGatewayService│    │ IntegratorCredentialService   │ │
│  │                     │    │                                │ │
│  │ - resolveGateway()  │    │ - storeCredentials()          │ │
│  │ - createGateway()   │    │ - getCredentials()            │ │
│  │ - getGatewayFor     │    │ - validateCredentials()       │ │
│  │   Integrator()      │    │ - testCredentials()           │ │
│  └──────────┬──────────┘    └───────────────┬────────────────┘ │
│             │                                │                   │
│             │        ┌──────────────────────┘                   │
│             ▼        ▼                                           │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │              Gateway (Stripe/CMI)                           │ │
│  │  - Uses integrator credentials if available                │ │
│  │  - Falls back to platform credentials                       │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Base de Données

### Table: `integrator_payment_credentials`

```php
// Migration: database/migrations/2026_03_18_000004_create_integrator_payment_credentials_table.php

- id: BigInt
- integrator_id: BigInt (FK)
- gateway_type: String ('stripe' | 'cmi')
- environment: String ('test' | 'production')
- encrypted_credentials: Text (JSON chiffré)
- public_key: String (nullable)
- webhook_url: String (nullable)
- merchant_id: String (nullable)
- is_active: Boolean
- is_validated: Boolean
- last_validated_at: Timestamp (nullable)
- validation_error: String (nullable)
- settings: JSON (nullable)
- created_by: BigInt (FK)
- created_at: Timestamp
- updated_at: Timestamp
```

### Colonnes additionnelles dans `integrators`

```php
- stripe_credentials: Text (chiffré, legacy)
- cmi_credentials: Text (chiffré, legacy)
- payment_settings: JSON
- preferred_payment_gateway: Enum ('stripe', 'cmi', 'platform')
```

## Services

### IntegratorCredentialService

**Fichier**: `app/Services/IntegratorCredentialService.php`

Responsabilités:
- Stockage sécurisé des identifiants avec chiffrement Laravel
- Validation des identifiants avant stockage
- Test de connexion aux gateways
- Support de la migration depuis l'ancien système
- Gestion du cache

**Méthodes principales**:

```php
// Stocker des identifiants
$credential = $this->credentialService->storeCredentials(
    $integrator,           // Modèle Integrator
    'stripe',             // Type de gateway
    $credentialsArray,    // Tableau des identifiants
    'production',         // Environnement
    $publicData           // Données publiques (non sensibles)
);

// Récupérer les identifiants
$credentials = $this->credentialService->getCredentials(
    $integrator,
    'stripe',
    'production'
);

// Vérifier si des identifiants existent
$hasCredentials = $this->credentialService->hasCredentials(
    $integrator,
    'cmi'
);

// Tester les identifiants
$result = $this->credentialService->testCredentials($credential);
// Retourne: ['success' => bool, 'message' => string]
```

### IntegratorWebhookService

**Fichier**: `app/Services/IntegratorWebhookService.php`

Responsabilités:
- Traitement des webhooks Stripe et CMI
- Identification de l'intégrateur à partir des métadonnées
- Vérification de signature par intégrateur
- Mise à jour des transactions

**Méthodes**:

```php
// Traiter webhook Stripe
$response = $this->webhookService->handleStripeWebhook($request);

// Traiter webhook CMI
$response = $this->webhookService->handleCmiWebhook($request);
```

## API Endpoints

### Gestion des Identifiants

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/integrator/payments/credentials` | Lister tous les identifiants |
| POST | `/api/integrator/payments/credentials` | Stocker de nouveaux identifiants |
| POST | `/api/integrator/payments/credentials/{id}/test` | Tester les identifiants |
| DELETE | `/api/integrator/payments/credentials/{id}` | Supprimer les identifiants |

### Transactions

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/integrator/payments/transactions` | Lister les transactions |
| GET | `/api/integrator/payments/statistics` | Obtenir les statistiques |

### Configuration

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/integrator/payments/webhook-url` | Obtenir les URLs de webhook |
| GET | `/api/integrator/payments/gateways` | Liste des gateways disponibles |
| GET | `/api/integrator/payments/resolve-gateway/{chargePointId}` | Résoudre le gateway pour une borne |

## Flux de Paiement

### 1. Résolution du Gateway

```php
// Le PaymentGatewayService détermine quel gateway utiliser:

// 1. Via la borne → partenaire → intégrateur
$integrator = $chargePoint->partner->integrator;

// 2. Vérifier si l'intégrateur a ses propres identifiants
if ($credentialService->hasCredentials($integrator, 'stripe')) {
    // Utiliser les identifiants de l'intégrateur
    return new StripeGateway($chargePoint, $integrator);
}

// 3. Sinon, utiliser les identifiants par défaut de la plateforme
return new StripeGateway();
```

### 2. Initiation du Paiement

```php
// Le gateway utilise les identifiants appropriés
$gateway = $paymentGatewayService->resolveGateway($chargePoint);

// Pour Stripe avec identifiants intégrateur
$paymentIntent = $gateway->initiatePayment($amount, $currency, [
    'charge_point_id' => $chargePoint->id,
    'integrator_id' => $integrator->id,  // Identifiant de l'intégrateur
    'partner_id' => $chargePoint->partner_id,
    // ... autres métadonnées
]);
```

### 3. Webhook

```php
// Le webhook reçoit l'événement avec les métadonnées
$eventData = json_decode($payload, true);
$integratorId = $eventData['data']['object']['metadata']['integrator_id'];

// Vérifier la signature avec les identifiants de l'intégrateur
$verified = $this->verifyStripeSignature($signature, $payload, $integrator);

// Traiter l'événement
```

## Conformité PCI-DSS

### Stockage Sécurisé

1. **Chiffrement**: Les identifiants sont chiffrés avec `Crypt::encryptString()`
2. **Masquage**: Les données sensibles sont masquées dans les logs
3. **Validation**: Les identifiants sont validés avant stockage

### Méthodes Sensibles (Masquées dans les logs)

```php
// Stripe
'secret_key'
'webhook_secret'
'client_secret'

// CMI
'store_key'
'store_password'
'client_id'
'username'
'password'
```

### Fonction de Masquage

```php
$masked = IntegratorPaymentCredential::maskSensitiveData($credentials);
// Retourne les identifiants avec les valeurs sensibles masquées
```

## Gestion des Erreurs

### Journalisation

Tous les événements critiques sont journalisés:

```php
Log::info('Payment credentials stored for integrator', [
    'integrator_id' => $integrator->id,
    'gateway_type' => $gatewayType,
    'environment' => $environment,
]);

Log::error('Failed to decrypt payment credentials', [
    'integrator_id' => $integrator->id,
    'error' => $e->getMessage(),
]);
```

### Statuts de Validation

- `is_validated`: Indique si les identifiants ont été testés avec succès
- `last_validated_at`: Date du dernier test
- `validation_error`: Message d'erreur si le test a échoué

## Migration depuis l'Ancien Système

Le service prend en charge la migration automatique depuis l'ancien système:

```php
// Migrer les identifiants legacy vers la nouvelle table
$migrated = $this->credentialService->migrateLegacyCredentials($integrator);
// Retourne: ['stripe', 'cmi'] si migré
```

## Tests

### Exemple de Test

```php
// Test de storage d'identifiants
$credentials = [
    'secret_key' => 'sk_test_123456789',
    'public_key' => 'pk_test_123456789',
    'webhook_secret' => 'whsec_123456789',
];

$credential = $credentialService->storeCredentials(
    $integrator,
    'stripe',
    $credentials,
    'test'
);

$this->assertNotNull($credential->id);
$this->assertTrue($credential->is_active);
```

## Limites et Considerations

1. **Performance**: Le cache est utilisé (TTL: 10 min) pour éviter les appels fréquents à la base
2. **Rétrocompatibilité**: Le système supporte l'ancien stockage dans les métadonnées JSON
3. **Validation**: Les identifiants doivent être testés avant utilisation en production
4. **Webhooks**: Chaque intégrateur doit configurer son propre URL de webhook

## Variables d'Environnement

```env
# Clé principale pour le chiffrement (doit être définie dans .env)
APP_KEY=

# Stripe (platforme)
STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=

# CMI (platforme)
CMI_STORE_KEY=
CMI_CLIENT_ID=
```

## Améliorations Futures

1. **Rotation des clés**: Service automatique de rotation des identifiants
2. **Métriques**: Tableau de bord des performances par intégrateur
3. **Alertes**: Notifications en cas d'échec de paiement
4. **Rapports**: Génération de rapports financiers par intégrateur
