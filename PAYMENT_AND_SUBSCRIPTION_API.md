# Documentation API - Flux de Paiement et Abonnements

## Table des Matières

1. [Flux de Paiement Public (3 étapes)](#flux-de-paiement-public)
2. [Gestion Postpayé](#gestion-postpayé)
3. [Gestion des Abonnements](#gestion-des-abonnements)
4. [Schéma de Base de Données](#schéma-de-base-de-données)

---

## Flux de Paiement Public

### Vue d'ensemble

Le système de paiement public permet aux utilisateurs de payer et démarrer une session de recharge en 3 étapes via QR Code:

1. **Scan QR Code** → Identification de la borne et récupération des tarifs
2. **Initiation du paiement** → Sélection de la méthode de paiement
3. **Confirmation et démarrage** → Validation et démarrage de la session

### Méthodes de paiement supportées

| Méthode | Code | Description |
|---------|------|-------------|
| Wallet | `wallet` | Paiement immédiat depuis le portefeuille |
| QR Code | `qr_code` | Paiement par QR Code externe |
| Postpayé | `postpaid` | Facturation différée |

### Endpoints API

#### Étape 1: Scanner le QR Code

```http
POST /api/public/payment/scan
Content-Type: application/json

{
    "qr_code": "{\"charging_point_id\": 123}",
    "station_id": 1 (optionnel)
}
```

**Réponse réussie (200):**
```json
{
    "success": true,
    "data": {
        "step": 1,
        "charging_point": {
            "id": 123,
            "name": "Borne Centre-Ville",
            "location": "123 Rue Principale",
            "latitude": 33.5731,
            "longitude": -7.5890,
            "connectors": [
                {"id": 1, "type": "Type 2", "power": 22}
            ],
            "status": "available"
        },
        "pricing_plans": [
            {
                "id": 1,
                "name": "Standard",
                "price_per_kwh": 0.50,
                "price_per_minute": 0.10,
                "activation_fee": 1.00,
                "currency": "EUR"
            }
        ],
        "payment_token": "PAY_123_abc123def456...",
        "expires_at": "2026-03-18T12:00:00+00:00"
    }
}
```

#### Étape 2: Initier le paiement

```http
POST /api/public/payment/initiate
Content-Type: application/json

{
    "payment_token": "PAY_123_abc123def456...",
    "pricing_plan_id": 1,
    "payment_method": "wallet"
}
```

**Réponse pour wallet (200):**
```json
{
    "success": true,
    "data": {
        "step": 2,
        "reservation_id": 456,
        "payment_token": "PAY_123_...",
        "payment_method": "wallet",
        "pricing_plan": {
            "id": 1,
            "name": "Standard",
            "price_per_kwh": 0.50,
            "price_per_minute": 0.10,
            "activation_fee": 1.00
        },
        "wallet_balance": 50.00,
        "estimated_cost": 25.00,
        "can_start": true,
        "expires_at": "2026-03-18T12:00:00+00:00"
    }
}
```

**Réponse pour postpayé (200):**
```json
{
    "success": true,
    "data": {
        "step": 2,
        "reservation_id": 456,
        "payment_method": "postpaid",
        "is_authorized": true,
        "user_credit_limit": 500.00,
        "user_current_usage": 100.00,
        "requires_approval": false
    }
}
```

#### Étape 3: Confirmer et démarrer la session

```http
POST /api/public/payment/confirm
Content-Type: application/json

{
    "reservation_id": 456,
    "payment_token": "PAY_123_abc123def456...",
    "payment_method": "wallet"
}
```

**Réponse réussie (200):**
```json
{
    "success": true,
    "data": {
        "step": 3,
        "success": true,
        "session": {
            "id": 789,
            "transaction_id": "TRABC123DEF",
            "monitoring_token": "mon_abc123...",
            "start_timestamp": "2026-03-18T10:30:00+00:00",
            "charging_point": {
                "id": 123,
                "name": "Borne Centre-Ville"
            }
        },
        "payment": {
            "method": "wallet",
            "status": "confirmed",
            "reservation_id": 456
        },
        "monitoring_url": "/api/public/charging/session/789/monitoring?token=mon_abc123..."
    }
}
```

#### Validation en temps réel du paiement

```http
GET /api/public/payment/validate?reservation_id=456
```

**Réponse (200):**
```json
{
    "success": true,
    "data": {
        "valid": true,
        "payment_status": "confirmed",
        "is_expired": false,
        "reservation_status": "confirmed",
        "checked_at": "2026-03-18T10:35:00+00:00"
    }
}
```

#### Obtenir le statut du paiement

```http
GET /api/public/payment/456/status
```

---

## Gestion Postpayé

### Endpoints Admin

#### Liste des utilisateurs postpayés

```http
GET /api/admin/postpaid/users?status=approved&search=john
```

#### Détails d'un utilisateur

```http
GET /api/admin/postpaid/users/123
```

#### Autoriser un utilisateur

```http
POST /api/admin/postpaid/users/123/authorize
Content-Type: application/json

{
    "credit_limit": 500.00
}
```

#### Révoquer l'autorisation

```http
POST /api/admin/postpaid/users/123/revoke
Content-Type: application/json

{
    "reason": "Non-paiement des factures"
}
```

#### Suspendre un utilisateur

```http
POST /api/admin/postpaid/users/123/suspend
Content-Type: application/json

{
    "reason": "Suspicion de fraude"
}
```

#### Réactiver un utilisateur

```http
POST /api/admin/postpaid/users/123/reactivate
```

#### Mettre à jour la limite de crédit

```http
POST /api/admin/postpaid/users/123/update-limit
Content-Type: application/json

{
    "credit_limit": 1000.00
}
```

#### Générer les factures mensuelles

```http
POST /api/admin/postpaid/generate-invoices
```

---

## Gestion des Abonnements

### Endpoints API

#### Vérifier l'éligibilité

```http
GET /api/subscription/check?charging_point_id=123
```

**Réponse (200):**
```json
{
    "success": true,
    "data": {
        "can_access": true,
        "subscription": {
            "id": 1,
            "plan_name": "Premium"
        }
    }
}
```

#### Obtenir le résumé d'utilisation

```http
GET /api/subscription/usage
```

**Réponse (200):**
```json
{
    "success": true,
    "data": [
        {
            "subscription_id": 1,
            "plan_name": "Premium",
            "plan_type": "per_session",
            "status": "active",
            "end_date": "2026-04-18",
            "sessions": {
                "used": 3,
                "max": 10,
                "remaining": 7,
                "exhausted": false
            },
            "kwh": {
                "used": 25.50,
                "max": 100.00,
                "remaining": 74.50,
                "exhausted": false
            },
            "duration": {
                "used": 150,
                "max": 600,
                "remaining": 450,
                "exhausted": false
            },
            "is_exhausted": false
        }
    ]
}
```

---

## Schéma de Base de Données

### Tables ajoutées/modifiées

#### Table: `users` (colonnes ajoutées)

| Colonne | Type | Description |
|---------|------|-------------|
| `postpaid_status` | enum | Statut de l'autorisation postpayé |
| `postpaid_credit_limit` | decimal(12,2) | Limite de crédit accordée |
| `postpaid_approved_at` | timestamp | Date d'approbation |
| `postpaid_approved_by` | unsignedBigInteger | Utilisateur qui a approuvé |
| `postpaid_billing_day` | tinyInteger | Jour de facturation (1-31) |
| `postpaid_suspended_at` | timestamp | Date de suspension |
| `postpaid_suspension_reason` | string(500) | Raison de la suspension |
| `postpaid_revoked_at` | timestamp | Date de révocation |
| `postpaid_revoked_by` | unsignedBigInteger | Utilisateur qui a révoqué |
| `postpaid_revocation_reason` | string(500) | Raison de la révocation |

#### Table: `charging_sessions` (colonnes ajoutées)

| Colonne | Type | Description |
|---------|------|-------------|
| `user_subscription_id` | unsignedBigInteger | Lien vers l'abonnement |
| `subscription_session_counted` | boolean | Si la session compte pour l'abonnement |
| `postpaid_authorized_at` | timestamp | Moment de l'autorisation postpayé |
| `estimated_cost` | decimal(10,2) | Coût estimé au démarrage |
| `actual_cost` | decimal(10,2) | Coût réel à la fin |
| `actual_energy` | decimal(8,2) | Énergie réellement consommée (kWh) |
| `actual_duration` | integer | Durée réelle (minutes) |
| `payment_status` | enum | Statut du paiement postpayé |

### Enum: `postpaid_status`

| Valeur | Description |
|--------|-------------|
| `not_authorized` | Utilisateur non autorisé |
| `pending` | En attente d'approbation |
| `approved` | Approuvé et actif |
| `suspended` | Suspendu temporairement |
| `revoked` | Révoqué |

### Enum: `payment_type`

| Valeur | Description |
|--------|-------------|
| `cmi` | Paiement par carte CMI |
| `offline` | Paiement hors ligne |
| `qr_code` | Paiement par QR Code |
| `postpaid` | Paiement postpayé |
| `wallet` | Paiement par portefeuille |
| `stripe` | Paiement Stripe |

---

## Exemples d'Utilisation

### Exemple: Flux complet de paiement

```javascript
// Étape 1: Scanner le QR Code
const scanResponse = await fetch('/api/public/payment/scan', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({qr_code: scannedData})
});
const {payment_token, charging_point, pricing_plans} = scanResponse.data;

// Étape 2: Initier le paiement
const initiateResponse = await fetch('/api/public/payment/initiate', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        payment_token,
        pricing_plan_id: pricing_plans[0].id,
        payment_method: 'wallet'
    })
});
const {reservation_id, estimated_cost} = initiateResponse.data;

// Étape 3: Confirmer et démarrer
const confirmResponse = await fetch('/api/public/payment/confirm', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        reservation_id,
        payment_token,
        payment_method: 'wallet'
    })
});
const {session, monitoring_url} = confirmResponse.data;

// Démarrer le monitoring
startMonitoring(monitoring_url);
```

### Exemple: Vérification du postpayé

```javascript
// Vérifier si l'utilisateur peut utiliser le postpayé
const creditResponse = await fetch('/api/admin/postpaid/users/123');
const {credit_limit, available_credit, current_usage} = creditResponse.data;

// Vérifier l'historique d'utilisation
const historyResponse = await fetch('/api/admin/postpaid/users/123/history');
const {sessions, total_sessions, total_cost} = historyResponse.data;
```

---

## Messages d'Erreur Courants

| Code | Message | Solution |
|------|---------|----------|
| `CHARGING_POINT_NOT_FOUND` | Borne de recharge non trouvée | Vérifier l'ID dans le QR Code |
| `INSUFFICIENT_BALANCE` | Solde insuffisant | Recharger le portefeuille |
| `SESSION_LIMIT_REACHED` | Nombre de sessions atteint | Souscrire à un autre abonnement |
| `QUOTA_EXHAUSTED` | Quotas d'abonnement épuisés | Attendre le renouvellement |
| `POSTPAID_NOT_AUTHORIZED` | Utilisateur non autorisé | Contacter l'administrateur |
