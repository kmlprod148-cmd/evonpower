# API Documentation - Demandes de Retrait

## Vue d'ensemble

Cette documentation décrit les endpoints API pour le système de demandes de retrait avec validation hors-ligne, permettant aux utilisateurs de soumettre des demandes de retrait de leurs fonds.

## Fonctionnalités

- ✅ Soumission de demandes de retrait
- ✅ Validation IBAN et coordonnées bancaires
- ✅ Workflow d'approbation multi-niveaux
- ✅ Traitement asynchrone avec queue
- ✅ Retry automatique en cas d'échec
- ✅ Validation offline
- ✅ Historique et suivi

---

## Endpoints Utilisateur

### 1. Soumettre une demande de retrait

**POST** `/api/user/withdrawals`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
    "amount": 100.00,
    "bank_name": "Crédit Agricole",
    "bank_account": "FR7612345678901234567890123",
    "bank_code": "12345",
    "method": "bank_transfer",
    "notes": "Retrait vers compte principal"
}
```

**Paramètres:**
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| amount | float | Oui | Montant du retrait (10-10000 EUR) |
| bank_name | string | Oui | Nom de la banque |
| bank_account | string | Oui* | Numéro de compte ou IBAN |
| bank_code | string | Non | Code banque |
| iban | string | Non* | IBAN (alternative à bank_account) |
| method | string | Non | bank_transfer ou card (défaut: bank_transfer) |
| notes | string | Non | Notes optionnelles |

*Soit bank_account soit iban requis

**Réponse成功 (201):**
```json
{
    "success": true,
    "message": "Demande de retrait soumise avec succès",
    "withdrawal": {
        "id": 1,
        "amount": 100.00,
        "fee": 1.00,
        "net_amount": 99.00,
        "currency": "EUR",
        "status": "pending",
        "status_label": "En attente",
        "created_at": "2026-03-18T12:00:00Z"
    }
}
```

**Réponse erreur (422):**
```json
{
    "success": false,
    "message": "Le montant minimum de retrait est de 10.00 EUR"
}
```

---

### 2. Historique des retraits

**GET** `/api/user/withdrawals`

**Headers:**
```
Authorization: Bearer {token}
```

**Paramètres query:**
| Paramètre | Type | Description |
|-----------|------|-------------|
| per_page | int | Nombre de résultats par page (défaut: 20) |
| page | int | Numéro de page |

**Réponse成功 (200):**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "amount": 100.00,
            "fee": 1.00,
            "net_amount": 99.00,
            "currency": "EUR",
            "status": "pending",
            "status_label": "En attente",
            "bank_name": "Crédit Agricole",
            "masked_account": "****0123",
            "created_at": "2026-03-18T12:00:00Z"
        }
    ],
    "first_page_url": "...",
    "last_page_url": "...",
    "next_page_url": null,
    "prev_page_url": null,
    "per_page": 20,
    "total": 1
}
```

---

### 3. Détails d'un retrait

**GET** `/api/user/withdrawals/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Réponse成功 (200):**
```json
{
    "withdrawal": {
        "id": 1,
        "amount": 100.00,
        "fee": 1.00,
        "net_amount": 99.00,
        "currency": "EUR",
        "status": "completed",
        "status_label": "Terminé",
        "bank_name": "Crédit Agricole",
        "masked_account": "****0123",
        "created_at": "2026-03-18T12:00:00Z",
        "processed_at": "2026-03-18T14:30:00Z",
        "notes": null,
        "rejection_reason": null
    }
}
```

---

### 4. Annuler un retrait

**POST** `/api/user/withdrawals/{id}/cancel`

**Headers:**
```
Authorization: Bearer {token}
```

**Réponse成功 (200):**
```json
{
    "success": true,
    "message": "Demande annulée avec succès"
}
```

**Réponse erreur (422):**
```json
{
    "success": false,
    "message": "Cette demande ne peut pas être annulée"
}
```

---

### 5. Obtenir les limites

**GET** `/api/user/withdrawals/limits`

**Headers:**
```
Authorization: Bearer {token}
```

**Réponse成功 (200):**
```json
{
    "min_amount": 10.00,
    "max_amount": 10000.00,
    "fee_percentage": 1.0,
    "daily_limit": 5000.00,
    "available_balance": 1500.00
}
```

---

### 6. Calculer les frais

**POST** `/api/user/withdrawals/calculate-fee`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
    "amount": 100.00
}
```

**Réponse成功 (200):**
```json
{
    "amount": 100.00,
    "fee": 1.00,
    "net_amount": 99.00
}
```

---

## Endpoints Admin

### 1. Liste des retraits (Admin)

**GET** `/api/admin/withdrawals`

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Paramètres query:**
| Paramètre | Type | Description |
|-----------|------|-------------|
| status | string | Filtrer par statut |
| date_from | date | Date de début |
| date_to | date | Date de fin |
| search | string | Rechercher par nom/email |
| per_page | int | Résultats par page |

**Réponse成功 (200):**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "amount": 100.00,
            "net_amount": 99.00,
            "currency": "EUR",
            "status": "pending",
            "bank_name": "Crédit Agricole",
            "created_at": "2026-03-18T12:00:00Z",
            "owner": {
                "id": 5,
                "name": "John Doe",
                "email": "john@example.com"
            }
        }
    ],
    "total": 50
}
```

---

### 2. Détails d'un retrait (Admin)

**GET** `/api/admin/withdrawals/{id}`

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Réponse成功 (200):**
```json
{
    "withdrawal": {
        "id": 1,
        "amount": 100.00,
        "fee": 1.00,
        "net_amount": 99.00,
        "currency": "EUR",
        "status": "pending",
        "withdrawal_method": "bank_transfer",
        "bank_name": "Crédit Agricole",
        "bank_account": "FR7612345678901234567890123",
        "created_at": "2026-03-18T12:00:00Z",
        "metadata": {
            "ip_address": "192.168.1.1",
            "offline_validation_pending": true
        }
    },
    "status_history": [
        {
            "id": 1,
            "status": "pending",
            "changed_by": 5,
            "changed_at": "2026-03-18T12:00:00Z",
            "notes": null
        }
    ]
}
```

---

### 3. Approuver un retrait

**POST** `/api/admin/withdrawals/{id}/approve`

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Body:**
```json
{
    "notes": "Compte vérifié, approuvé"
}
```

**Réponse成功 (200):**
```json
{
    "success": true,
    "message": "Demande approuvée avec succès"
}
```

---

### 4. Rejeter un retrait

**POST** `/api/admin/withdrawals/{id}/reject`

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Body:**
```json
{
    "reason": "Compte bancaire invalide"
}
```

**Réponse成功 (200):**
```json
{
    "success": true,
    "message": "Demande rejetée"
}
```

---

### 5. Marquer comme en cours

**POST** `/api/admin/withdrawals/{id}/processing`

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Réponse成功 (200):**
```json
{
    "success": true,
    "message": "Statut mis à jour"
}
```

---

### 6. Marquer comme terminé

**POST** `/api/admin/withdrawals/{id}/completed`

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Body:**
```json
{
    "external_id": "TXN_123456",
    "notes": "Virement effectué"
}
```

---

### 7. Marquer comme échoué

**POST** `/api/admin/withdrawals/{id}/failed`

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Body:**
```json
{
    "reason": "Compte destinataire fermé"
}
```

---

### 8. Statistiques

**GET** `/api/admin/withdrawals/statistics`

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Réponse成功 (200):**
```json
{
    "pending": 5,
    "approved": 2,
    "processing": 1,
    "completed": 150,
    "failed": 3,
    "cancelled": 10,
    "today_count": 8,
    "today_amount": 1250.00,
    "month_count": 45,
    "month_amount": 15000.00,
    "completed_month_amount": 14500.00
}
```

---

### 9. Traitement en masse

**POST** `/api/admin/withdrawals/bulk-process`

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Body:**
```json
{
    "withdrawal_ids": [1, 2, 3],
    "action": "approve",
    "reason": "Vérifié en masse"
}
```

**Réponse成功 (200):**
```json
{
    "results": {
        "1": {"success": true},
        "2": {"success": true},
        "3": {"success": false, "error": "Statut invalide"}
    },
    "success_count": 2,
    "failed_count": 1
}
```

---

## Statuts des Retraits

| Statut | Description |
|--------|-------------|
| `pending` | En attente de validation |
| `approved` | Approuvé, en attente de traitement |
| `processing` | En cours de traitement |
| `completed` | Terminé avec succès |
| `failed` | Échec du traitement |
| `cancelled` | Annulé |

---

## Flux de Traitement

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  PENDING    │────▶│  APPROVED   │────▶│ PROCESSING  │
└─────────────┘     └─────────────┘     └─────────────┘
      │                   │                   │
      │ reject            │ process            │
      ▼                   ▼                   ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  CANCELLED  │     │  REJECTED   │     │ COMPLETED   │
└─────────────┘     └─────────────┘     └─────────────┘
                          │                   │
                          │ retry             │ failed
                          ▼                   ▼
                    ┌─────────────┐     ┌─────────────┐
                    │   PENDING   │     │   FAILED    │
                    └─────────────┘     └─────────────┘
```

---

## Jobs de Queue

### Validation Offline
- **Queue:** `withdrawal-validation`
- **Retry:** 3 tentatives avec backoff exponentiel

### Traitement du Retrait
- **Queue:** `withdrawals`
- **Retry:** 3 tentatives avec 5 minutes de délai

---

## Schéma de Base de Données

### Table: `withdrawal_requests`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | ID unique |
| owner_type | varchar | Type de propriétaire (User::class) |
| owner_id | bigint | ID du propriétaire |
| wallet_id | bigint | ID du wallet |
| amount | decimal | Montant brut |
| currency | varchar | Devise (EUR) |
| fee | decimal | Frais de retrait |
| net_amount | decimal | Montant net |
| status | varchar | Statut actuel |
| withdrawal_method | varchar | Méthode (bank_transfer, card) |
| bank_name | varchar | Nom de la banque |
| bank_account | varchar | Numéro de compte |
| bank_code | varchar | Code banque |
| card_last4 | varchar | 4 derniers chiffres carte |
| card_brand | varchar | Type de carte |
| external_id | varchar | ID externe (paiement) |
| notes | text | Notes |
| rejection_reason | text | Raison du rejet |
| processed_at | timestamp | Date de traitement |
| metadata | json | Métadonnées supplémentaires |
| created_at | timestamp | Date de création |
| updated_at | timestamp | Date de mise à jour |

---

## Codes d'Erreur

| Code | Message |
|------|---------|
| 400 | Requête invalide |
| 401 | Non authentifié |
| 403 | Accès refusé |
| 422 | Erreur de validation |
| 500 | Erreur serveur |
