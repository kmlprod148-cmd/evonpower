# Système de Gestion du Solde Client

## Vue d'ensemble

Système centralisé et compact pour l'affichage et la mise à jour du solde client dans l'application.

**Stockage en DB** : Les soldes sont stockés dans `wallets.balance` et mis à jour instantanément à chaque crédit/débit via `Wallet::credit()` et `Wallet::debit()`. Chaque mouvement est enregistré dans `wallet_transactions`.

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    ClientBalanceService                           │
│  (app/Services/ClientBalanceService.php)                         │
│  • getBalance(?User) → float                                     │
│  • getFormatted(?User) → string                                  │
│  • toApiResponse(?User) → array                                  │
│  • hasSufficient(float, ?User) → bool                            │
└─────────────────────────────────────────────────────────────────┘
         │                    │                    │
         ▼                    ▼                    ▼
┌──────────────┐    ┌──────────────────┐    ┌─────────────────────┐
│ ViewComposer │    │ CreditController │    │ CreditRechargeCtrl  │
│ (AppService) │    │ getBalanceApi()  │    │ index()             │
└──────────────┘    └──────────────────┘    └─────────────────────┘
         │                    │
         ▼                    ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Frontend (JS)                                 │
│  wallet-balance-update.js → ClientBalanceManager                 │
│  • Événement: walletBalanceUpdated                               │
│  • Rafraîchissement: load, visibilitychange, pageshow             │
└─────────────────────────────────────────────────────────────────┘
```

## Utilisation

### Backend (PHP)

```php
// Via le service
$service = app(ClientBalanceService::class);
$balance = $service->getBalance();        // 1234.56
$formatted = $service->getFormatted();     // "1 234,56 EUR"

// Via le helper
$formatted = client_balance();            // ou client_balance($user)
```

### Blade

```blade
{{-- Composant réutilisable --}}
<x-client-balance :formatted="$user_formatted_balance ?? '0.00 EUR'" />

{{-- Ou avec valeur par défaut --}}
<x-client-balance />

{{-- Affichage simple avec data-balance-value (mis à jour par JS) --}}
<span data-balance-value>{{ $user_formatted_balance ?? client_balance() }}</span>
```

### JavaScript

```javascript
// Déclencher une mise à jour (après paiement, etc.)
window.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
    detail: { balance: 950, formatted: '950.00 EUR', remaining_balance: 950, currency: 'EUR' },
    bubbles: true
}));

// Rafraîchir manuellement
window.ClientBalanceManager?.fetchAndDispatch(apiUrl);
```

## API

**GET** `/credits/balance/api`

Réponse:
```json
{
  "success": true,
  "balance": 1234.56,
  "formatted_balance": "1 234,56 EUR",
  "currency": "EUR",
  "user_id": 1,
  "wallet_id": 1
}
```

## Sélecteurs DOM (mise à jour automatique)

- `[data-balance-value]` - Tous les éléments avec cet attribut
- `#user-balance-display` - Desktop
- `#user-balance-display-mobile` - Mobile
- `#available-balance`, `#available-balance-mobile`
- `#current-balance`, `#current-balance-mobile`

## Prérequis

- Meta tag `balance-api-url` dans le layout (layouts/app.blade.php, public.blade.php)
- Script `wallet-balance-update.js` chargé
- Utilisateur authentifié pour l'API
