# Solution Smart : Paiement Postpayé basé sur l'API Steve

## Vue d'ensemble

Solution pour le paiement postpayé qui utilise **Steve comme source de vérité** pour les données de consommation (énergie, durée, valeurs compteur), avec synchronisation automatique et résilience.

---

## Principes

1. **Steve = source de vérité** pour startValue, stopValue, energy (Wh)
2. **EVON = calcul du coût** (PricingPlan) + débit wallet + transaction
3. **Détection automatique** des sessions stoppées côté borne (sans RemoteStop manuel)
4. **Résilience** : retry avec backoff, fallback meter values, idempotence, job de synchronisation

---

## Architecture

```
┌─────────────────┐     StopTransaction      ┌─────────────┐
│  Borne OCPP     │ ───────────────────────► │   Steve     │
│  (charge point) │     meter values          │   API      │
└─────────────────┘                           └──────┬──────┘
                                                     │
        ┌───────────────────────────────────────────┘
        │ getTransaction(id) / getMeterValues(id)
        ▼
┌───────────────────────────────────────────────────────────┐
│              StevePostpaidPaymentService                   │
│  • Récupère startValue, stopValue depuis Steve             │
│  • Calcule energy (Wh → kWh)                               │
│  • Calcule coût (PricingPlan)                              │
│  • Débite wallet                                           │
│  • Met à jour reservation + transaction EVON               │
└───────────────────────────────────────────────────────────┘
```

---

## Flux

### Flux 1 : Arrêt manuel (RemoteStop)
1. User/Admin déclenche StopChargingSessionJob
2. OcppBusinessService.remoteStopCharging() → Steve
3. **Attendre 2-5 sec** (borne envoie StopTransaction à Steve)
4. **StevePostpaidPaymentService** : getTransaction(steve_transaction_id) → récupérer stopValue
5. Calcul energy, coût, débit wallet

### Flux 2 : Arrêt côté borne (câble débranché, etc.)
1. Borne envoie StopTransaction à Steve
2. **SyncSteVePostpaidJob** (toutes les 2 min) : détecte transactions STOPPED dans Steve sans paiement EVON
3. Pour chaque : appeler StevePostpaidPaymentService.finalizeFromSteve()

### Flux 3 : Mise à jour temps réel (session en cours)
1. **postpaid:update-all-sessions** (toutes les 30 sec) : getMeterValues() pour afficher consommation live
2. Pas de débit tant que session non stoppée

---

## API Steve utilisées

| Endpoint | Usage |
|----------|-------|
| `GET /api/v1/transactions/{id}` | Détails transaction (startValue, stopValue, status STOPPED) |
| `GET /api/v1/transactions/{id}/meter-values` | Valeurs compteur (fallback si transaction incomplète) |
| `POST /api/v1/ocpp/remote-stop` | Arrêt à distance (existant) |

---

## Configuration (.env)

```env
# Délai (secondes) après RemoteStop avant de récupérer les données Steve
STEVE_POSTPAID_FETCH_DELAY=3

# Utiliser les données Steve pour la consommation (sinon fallback local)
STEVE_POSTPAID_USE_STEVE_DATA=true

# Retries pour getTransaction / getMeterValues
STEVE_POSTPAID_FETCH_RETRIES=3
STEVE_POSTPAID_FETCH_RETRY_DELAY=500

# Activer la synchronisation automatique des transactions stoppées côté borne
STEVE_POSTPAID_SYNC_ENABLED=true

# Intervalle du job de sync (minutes) - utilisé pour la planification
STEVE_POSTPAID_SYNC_INTERVAL=2
```

---

## Commandes

| Commande | Usage |
|----------|-------|
| `php artisan postpaid:sync-steve` | Synchronise manuellement les sessions stoppées Steve |
| `php artisan postpaid:sync-steve --queue` | Lance le job en queue |
| `php artisan postpaid:sync-steve --limit=50 --max-age=120` | Personnaliser limite et âge max |

---

## Fichiers

| Fichier | Rôle |
|---------|------|
| `StevePostpaidPaymentService` | Orchestration : Steve → calcul → débit. Retries, idempotence, fallback |
| `SyncSteVePostpaidTransactionsJob` | Détecte et finalise les sessions stoppées côté borne (planifié toutes les 2 min) |
| `SyncStevePostpaidCommand` | Commande manuelle `postpaid:sync-steve` |
| `StopChargingSessionJob` | Utilise StevePostpaidPaymentService après RemoteStop |
