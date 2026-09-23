# Solution : Statut des transactions et synchronisation des soldes

## Vue d'ensemble

Solution complète pour corriger automatiquement le statut des transactions (prépayé/postpayé) **sans intervention du propriétaire de la borne**, et synchroniser les soldes dans toute l'application.

**Mise en place** : La solution est entièrement déployée et opérationnelle.

---

## 1. Statut des transactions

### Règle unique

**Prépayé ET postpayé** : dès que `reservation.payment_status = PAID` → `transaction.status = completed`

- **Prépayé** : paiement immédiat (solde ou carte) → PAID immédiatement
- **Postpayé** : débit à la fin de la session → PAID après débit

### Mécanismes de synchronisation (multi-couches)

| Couche | Mécanisme | Fichier |
|--------|-----------|---------|
| **1. Event-driven** | `ReservationObserver` : quand `payment_status` devient PAID |
| **2. Flux explicite** | `CreditPaymentService`, `StopChargingSessionJob` : appellent le service après mise à jour |
| **3. Lecture** | `TransactionObserver.retrieved` : corrige à l'affichage si la transaction est chargée |
| **4. Planifié** | `TransactionStatusSyncJob` : toutes les heures, corrige les cas orphelins |

### Service centralisé

`TransactionStatusSyncService` est la source unique de vérité :

- `syncTransactionStatusIfPaid(Transaction)` : mise à jour d'une transaction
- `syncForReservation(Reservation)` : appelé par l'observer
- `syncAllPendingTransactionsForPaidReservations()` : correction batch

---

## 2. Synchronisation des soldes

### Points de refresh

| Contexte | Action |
|----------|--------|
| `CreditPaymentService` | `$wallet->refresh()` avant chaque vérification de solde |
| `CreditRechargeController` | `$wallet->refresh()` avant affichage |
| `CreditController` | `$wallet->refresh()` avant affichage et API |
| `PaymentController::show` | `$wallet->refresh()` avant affichage |
| `ClientDashboardController` | `$wallet->refresh()` avant affichage et statistiques |
| `ImmediateStartController` | `$wallet->refresh()` avant affichage |
| `TransactionViewController` | `$wallet->refresh()` avant affichage |
| `ReservationService` | `$wallet->refresh()` avant vérification solde |

### Après modification du wallet

- `$user->unsetRelation('wallet')` après chaque crédit/débit dans `CreditPaymentService` et `CreditRechargeService`
- `Wallet::getFormattedBalance()` utilise `$this->fresh()` pour le solde

---

## 3. Commandes et jobs

### Commande manuelle

```bash
# Corriger toutes les transactions concernées
php artisan reservations:fix-paid-transactions

# Mode simulation (sans modification)
php artisan reservations:fix-paid-transactions --dry-run
```

### Job planifié

`TransactionStatusSyncJob` s'exécute **toutes les heures** via le scheduler.

---

## 4. Fichiers modifiés/créés

| Fichier | Rôle |
|---------|------|
| `app/Services/TransactionStatusSyncService.php` | **Nouveau** – logique centralisée |
| `app/Observers/ReservationObserver.php` | Écoute `payment_status` → PAID |
| `app/Observers/TransactionObserver.php` | Corrige à la lecture via le service |
| `app/Jobs/StopChargingSessionJob.php` | Sync explicite après débit postpayé |
| `app/Services/CreditPaymentService.php` | Sync explicite après finalisation postpayé |
| `app/Jobs/TransactionStatusSyncJob.php` | **Nouveau** – job planifié |
| `app/Console/Commands/FixPaidReservationTransactions.php` | **Modifié** – utilise le service |
| `app/Console/Kernel.php` | Planification du job |

---

## 5. Schéma de flux

```
PRÉPAYÉ:
  User paie → CreditPaymentService.processPrepaidPayment()
    → reservation.payment_status = PAID
    → reservation.transaction.status = completed (dans le service)
    → ReservationObserver.updated() déclenche aussi sync (redondance)

POSTPAYÉ:
  Session finie → StopChargingSessionJob.processPostpaidDebit()
    → wallet.debit()
    → reservation.payment_status = PAID
    → TransactionStatusSyncService.syncTransactionStatusIfPaid()
    → transaction.status = completed

CAS ORPHELINS:
  TransactionObserver.retrieved() → corrige à l'affichage
  TransactionStatusSyncJob (toutes les heures) → corrige en batch
```
