# Mode Postpayé (Postpaid Mode) - Documentation Technique

## Vue d'ensemble

Le Mode Postpayé permet aux clients invités de démarrer une session de recharge sans paiement immédiat. Une autorisation temporaire est placé sur leur carte, et le montant réel est débité après la fin de la session de recharge.

## Flux de Fonctionnement

### 1. Réservation et Autorisation (Pré-session)

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│  Sélection      │    │  Informations    │    │  Mode Postpayé  │
│  Durée          │───▶│  Client          │───▶│  Autorisation   │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                                        │
                                                        ▼
                                               ┌─────────────────┐
                                               │  Authorization   │
                                               │  Hold (Stripe)   │
                                               └─────────────────┘
```

### 2. Session de Recharge

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│  Démarrage      │    │  Charge en       │    │  Fin de         │
│  Session        │───▶│  cours           │───▶│  Session        │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                                        │
                                                        ▼
                                               ┌─────────────────┐
                                               │  Récupération    │
                                               │  données session │
                                               └─────────────────┘
```

### 3. Capture du Paiement (Post-session)

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│  Calcul Montant│    │  Capture         │    │  Confirmation   │
│  Réel          │───▶│  Paiement        │───▶│  + Reçu         │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## Composants Implémentés

### Modèles

#### GuestPaymentMethod
- **Fichier**: `app/Models/GuestPaymentMethod.php`
- **Fonction**: Stocke les méthodes de paiement tokenisées pour les clients invités
- **Champs**:
  - `guest_email` - Email du client
  - `guest_phone` - Téléphone du client
  - `payment_gateway` - Gateway utilisé (stripe, cmi)
  - `payment_method_id` - ID du paiement chez le gateway
  - `card_last4` - 4 derniers chiffres de la carte
  - `card_brand` - Marque de la carte (visa, mastercard)
  - `expires_at` - Date d'expiration

#### ChargingSession (Extension)
- **Fichier**: `app/Models/ChargingSession.php`
- **Nouveaux champs**:
  - `payment_mode` - 'prepaid' ou 'postpaid'
  - `guest_payment_method_id` - Référence vers GuestPaymentMethod
  - `authorization_hold_id` - ID de l'autorisation
  - `authorization_hold_amount` - Montant autorisé
  - `capture_status` - Statut de la capture
  - `capture_transaction_id` - ID de la transaction de capture

### Services

#### GuestPostpaidService
- **Fichier**: `app/Services/GuestPostpaidService.php`
- **Méthodes principales**:
  - `createAuthorization()` - Crée une autorisation sur la carte
  - `capturePayment()` - Capture le montant réel après la session
  - `cancelAuthorization()` - Annule l'autorisation
  - `storePaymentMethod()` - Stocke la méthode de paiement
  - `capturePaymentWithRetry()` - Capture avec retry automatique

#### ChargingSessionCompletionService (Extension)
- **Fichier**: `app/Services/ChargingSessionCompletionService.php`
- **Nouvelle méthode**:
  - `processGuestPostpaidCapture()` - Gère la capture automatique après la session

### Contrôleurs

#### CheckoutController (Extension)
- **Fichier**: `app/Http/Controllers/Public/CheckoutController.php`
- **Modifications**:
  - Ajout du champ `payment_mode` dans la validation
  - Nouvelle méthode `handlePostpaidPayment()` pour le flux postpaid

### Migrations

1. **2026_03_18_000002_create_guest_payment_methods_table.php**
   - Crée la table `guest_payment_methods`
   - Ajoute `guest_payment_method_id` aux sessions et réservations

2. **2026_03_18_000003_add_authorization_hold_fields_to_charging_sessions_table.php**
   - Ajoute les champs d'autorisation et de capture

## Intégration Gateway

### GatewayInterface (Extension)
- **Fichier**: `app/Services/Gateways/Contracts/GatewayInterface.php`
- **Nouvelles méthodes**:
  - `authorize()` - Création d'une autorisation
  - `capture()` - Capture d'un montant
  - `cancelAuthorization()` - Annulation d'une autorisation

### StripeGateway (Implémentation)
- **Fichier**: `app/Services/Gateways/StripeGateway.php`
- Implémente les nouvelles méthodes du GatewayInterface

## Interface Utilisateur

### Modification des Vues

#### user-info.blade.php
- **Fichier**: `resources/views/public/checkout/user-info.blade.php`
- **Ajout**: Section de sélection du mode de paiement
  - Option Prépayé (défaut)
  - Option Postpayé avec description

## Tests Unitaires

### Fichier de Tests
- **Fichier**: `tests/Unit/Services/GuestPostpaidServiceTest.php`
- **Tests couverts**:
  - Création d'autorisation réussie
  - Échec d'autorisation
  - Validation des montants
  - Capture réussie
  - Échec de capture
  - Annulation d'autorisation
  - Stockage méthode de paiement
  - Retry automatique sur échec

## Conformité RGPD

Le système respecte les exigences RGPD:
- **Consentement explicite** pour le stockage des données de paiement
- **Droit à l'effacement** - Les méthodes de paiement peuvent être supprimées
- **Chiffrement** - Les données sensibles sont chiffrées
- **Journalisation** - Toutes les opérations sont journalisées

## Gestion des Erreurs

### Stratégie de Retry
- 3 tentatives de capture en cas d'échec
- Délai de 60 secondes entre chaque tentative
- Journalisation détaillée des erreurs

### Statuts de Capture
- `pending` - En attente de capture
- `processing` - Capture en cours
- `captured` - Capture réussie
- `failed` - Échec de la capture
- `cancelled` - Autorisation annulée
- `expired` - Autorisation expirée

## Configuration

### Variables d'Environnement
```env
# Stripe (pour le mode postpaid)
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Montant maximum d'autorisation
MAX_AUTHORIZATION_AMOUNT=1000.00
```

## Cas d'Usage

### Scénario 1: Session Normale
1. Client sélectionne "Postpayé"
2. Entre les informations de carte
3. Autorisation de 500 MAD créée
4. Session de charge démarre
5. Session terminée (300 MAD consommé)
6. Capture de 300 MAD
7. Autorisation libérée

### Scénario 2: Échec de Capture
1. Session terminée
2. Tentative de capture échoue
3. Retry après 60s
4. Retry échoue
5. Retry final
6. Si échec final: notification admin + contact client

### Scénario 3: Annulation
1. Client annule avant démarrage
2. Autorisation annulée
3. Montant libéré

## Monitoring

### Logs Importants
- `GuestPostpaidServiceTest.php` - Tests
- `ChargingSessionCompletedListener` - Déclenchement capture
- `ChargingSessionCompletionService` - Traitement capture

### Métriques à Surveiller
- Taux de succès des autorisations
- Taux de succès des captures
- Temps moyen de capture
- Échecs et retries

## Limitations Connues

1. **Gateway unique** - Pour l'instant seul Stripe est supporté pour le postpaid
2. **Currency** - Support limité à MAD pour le moment
3. **Amount maximum** - Limitation à 1000 MAD par défaut

## Améliorations Futures

1. Support multi-gateway (CMI, etc.)
2. Notifications push/email en temps réel
3. Tableau de bord admin pour les captures échouées
4. Intégration avec système de facturation
5. Support des abonnements postpaid
