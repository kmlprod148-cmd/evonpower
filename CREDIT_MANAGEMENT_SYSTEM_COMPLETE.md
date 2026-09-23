# 💳 Système Complet de Gestion des Crédits EVON

## 📋 Vue d'Ensemble

Système de gestion des crédits permettant aux **clients** de demander des crédits aux propriétaires de points de charge, et aux **propriétaires** (Admin, Intégrateurs, Opérateurs, Partenaires) de gérer ces demandes.

---

## 🎯 Règles Métier

### 👥 Rôles et Permissions

#### **Clients (Users réguliers)**
✅ Peuvent demander des crédits aux propriétaires de points de charge
✅ Peuvent ajouter des crédits via carte bancaire (paiement)
✅ Peuvent annuler leurs demandes en attente
✅ Voient uniquement leurs propres demandes
✅ Ne peuvent demander qu'aux propriétaires dont ils ont utilisé les bornes

#### **Propriétaires (Admin, Intégrateurs, Opérateurs, Partenaires)**
✅ Voient les demandes de LEURS clients uniquement
✅ Peuvent approuver/rejeter les demandes
✅ **NE PEUVENT PAS** ajouter de crédit directement
✅ Gèrent uniquement les clients ayant fait des réservations sur leurs bornes
✅ Reçoivent des notifications pour nouvelles demandes

---

## 📊 Base de Données

### Table: `credit_requests`

```sql
CREATE TABLE credit_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Relations
    client_id BIGINT UNSIGNED,              -- Client demandeur
    owner_id BIGINT UNSIGNED,               -- Propriétaire (qui approuve/rejette)
    charging_point_id BIGINT UNSIGNED NULL, -- Borne concernée (optionnel)
    reservation_id BIGINT UNSIGNED NULL,    -- Réservation liée (optionnel)
    
    -- Montant et statut
    amount DECIMAL(10,2),                   -- Montant demandé
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    request_type ENUM('reservation_based', 'manual') DEFAULT 'manual',
    
    -- Communication
    reason TEXT NULL,                       -- Raison du client
    owner_response TEXT NULL,               -- Réponse du propriétaire
    
    -- Dates
    processed_at TIMESTAMP NULL,            -- Date de traitement
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    -- Paiement
    payment_transaction_id VARCHAR(255) NULL,
    payment_method ENUM('credit_request', 'credit_card', 'bank_transfer') DEFAULT 'credit_request',
    
    -- Métadonnées
    metadata JSON NULL,
    
    -- Index
    INDEX idx_client_status (client_id, status),
    INDEX idx_owner_status (owner_id, status),
    INDEX idx_charging_point (charging_point_id),
    INDEX idx_status_created (status, created_at),
    
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (charging_point_id) REFERENCES charging_points(id) ON DELETE SET NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
);
```

---

## 🔧 Fichiers Créés

### 1. **Migration**
```
database/migrations/2024_12_22_create_credit_requests_table.php
```

### 2. **Modèle**
```
app/Models/CreditRequest.php
```

Méthodes principales:
- `approve($response)` - Approuve et ajoute le crédit
- `reject($response)` - Rejette la demande
- `cancel()` - Annule la demande (client uniquement)
- `isPending()`, `isApproved()`, `isRejected()`, `isCancelled()`

### 3. **Contrôleur**
```
app/Http/Controllers/CreditRequestController.php
```

Actions:
- `index()` - Liste les demandes (filtrées par rôle)
- `create()` - Formulaire de demande (clients)
- `store()` - Créer une demande
- `show()` - Détails d'une demande
- `approve()` - Approuver (propriétaires)
- `reject()` - Rejeter (propriétaires)
- `cancel()` - Annuler (clients)
- `getAvailableOwners()` - AJAX: liste des propriétaires disponibles
- `getOwnerChargingPoints()` - AJAX: bornes d'un propriétaire

### 4. **Routes**
```
routes/web_credit_requests.php
```

```php
// Inclure dans routes/web.php:
require __DIR__.'/web_credit_requests.php';
```

---

## 🎨 Vues à Créer

### 1. **Index** - `resources/views/credit-requests/index.blade.php`

#### Pour Clients:
```blade
{{-- Liste de leurs demandes --}}
<div class="mb-6">
    <a href="{{ route('credit-requests.create') }}" class="btn-primary">
        💳 Nouvelle Demande de Crédit
    </a>
</div>

<div class="grid grid-cols-1 gap-6">
    @foreach($creditRequests as $request)
        <div class="card">
            <div class="flex justify-between">
                <div>
                    <p>Montant: {{ $request->amount }} MAD</p>
                    <p>Propriétaire: {{ $request->owner->name }}</p>
                    <p>Statut: 
                        <span class="badge badge-{{ $request->status_color }}">
                            {{ $request->status_label }}
                        </span>
                    </p>
                </div>
                @if($request->isPending())
                    <form method="POST" action="{{ route('credit-requests.cancel', $request) }}">
                        @csrf
                        <button class="btn-secondary">Annuler</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
</div>
```

#### Pour Propriétaires:
```blade
{{-- Statistiques --}}
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <h3>Total</h3>
        <p class="text-3xl">{{ $stats['total'] }}</p>
    </div>
    <div class="stat-card bg-yellow-50">
        <h3>En attente</h3>
        <p class="text-3xl">{{ $stats['pending'] }}</p>
    </div>
    <div class="stat-card bg-green-50">
        <h3>Approuvées</h3>
        <p class="text-3xl">{{ $stats['approved'] }}</p>
    </div>
    <div class="stat-card bg-red-50">
        <h3>Rejetées</h3>
        <p class="text-3xl">{{ $stats['rejected'] }}</p>
    </div>
</div>

{{-- Filtres --}}
<div class="mb-6">
    <form method="GET" class="flex gap-4">
        <select name="status" onchange="this.form.submit()">
            <option value="all">Tous les statuts</option>
            <option value="pending">En attente</option>
            <option value="approved">Approuvées</option>
            <option value="rejected">Rejetées</option>
        </select>
        <input type="text" name="search" placeholder="Rechercher..." />
    </form>
</div>

{{-- Liste des demandes --}}
<div class="space-y-4">
    @foreach($creditRequests as $request)
        <div class="card">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h3 class="font-semibold">{{ $request->client->name }}</h3>
                    <p class="text-sm text-gray-600">{{ $request->client->email }}</p>
                    <p class="mt-2">
                        <strong>Montant:</strong> {{ $request->amount }} MAD
                    </p>
                    <p class="mt-1">
                        <strong>Raison:</strong> {{ $request->reason }}
                    </p>
                    @if($request->charging_point)
                        <p class="text-sm text-gray-600">
                            Borne: {{ $request->chargingPoint->name }}
                        </p>
                    @endif
                    <p class="mt-2">
                        <span class="badge badge-{{ $request->status_color }}">
                            {{ $request->status_label }}
                        </span>
                    </p>
                </div>

                @if($request->isPending())
                    <div class="flex gap-2">
                        <button 
                            onclick="openApproveModal({{ $request->id }})"
                            class="btn-success">
                            ✅ Approuver
                        </button>
                        <button 
                            onclick="openRejectModal({{ $request->id }})"
                            class="btn-danger">
                            ❌ Rejeter
                        </button>
                    </div>
                @endif
            </div>

            @if($request->processed_at)
                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-600">
                        Traité le: {{ $request->processed_at->format('d/m/Y H:i') }}
                    </p>
                    @if($request->owner_response)
                        <p class="mt-2">
                            <strong>Réponse:</strong> {{ $request->owner_response }}
                        </p>
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</div>

{{ $creditRequests->links() }}
```

### 2. **Create** - `resources/views/credit-requests/create.blade.php`

```blade
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">💳 Demande de Crédit</h1>

    <div class="card">
        <form method="POST" action="{{ route('credit-requests.store') }}" id="creditRequestForm">
            @csrf

            {{-- Propriétaire --}}
            <div class="mb-6">
                <label class="block mb-2 font-medium">
                    Propriétaire de la borne
                </label>
                <select name="owner_id" id="owner_id" required class="form-select">
                    <option value="">Sélectionnez un propriétaire</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}">
                            {{ $owner->name }} - {{ $owner->email }}
                        </option>
                    @endforeach
                </select>
                <p class="text-sm text-gray-600 mt-1">
                    Uniquement les propriétaires dont vous avez utilisé les bornes
                </p>
            </div>

            {{-- Borne (optionnel) --}}
            <div class="mb-6">
                <label class="block mb-2 font-medium">
                    Borne de charge (optionnel)
                </label>
                <select name="charging_point_id" id="charging_point_id" class="form-select">
                    <option value="">Sélectionnez une borne</option>
                </select>
            </div>

            {{-- Montant --}}
            <div class="mb-6">
                <label class="block mb-2 font-medium">
                    Montant (MAD)
                </label>
                <input 
                    type="number" 
                    name="amount" 
                    min="10" 
                    max="10000" 
                    step="10"
                    required 
                    class="form-input"
                    placeholder="100">
                <p class="text-sm text-gray-600 mt-1">
                    Minimum: 10 MAD - Maximum: 10,000 MAD
                </p>
            </div>

            {{-- Raison --}}
            <div class="mb-6">
                <label class="block mb-2 font-medium">
                    Raison de la demande
                </label>
                <textarea 
                    name="reason" 
                    required 
                    rows="4"
                    maxlength="1000"
                    class="form-textarea"
                    placeholder="Expliquez pourquoi vous avez besoin de ce crédit..."></textarea>
            </div>

            {{-- Réservations récentes (info) --}}
            @if($recentReservations->isNotEmpty())
                <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-medium mb-2">📋 Vos réservations récentes:</h3>
                    <ul class="text-sm space-y-2">
                        @foreach($recentReservations->take(5) as $reservation)
                            <li class="flex justify-between">
                                <span>{{ $reservation->chargingPoint->name }}</span>
                                <span class="text-gray-600">
                                    {{ $reservation->created_at->format('d/m/Y') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex gap-4">
                <button type="submit" class="btn-primary flex-1">
                    💳 Envoyer la demande
                </button>
                <a href="{{ route('credit-requests.index') }}" class="btn-secondary flex-1 text-center">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Charger les bornes quand propriétaire sélectionné
document.getElementById('owner_id').addEventListener('change', async function() {
    const ownerId = this.value;
    const chargingPointSelect = document.getElementById('charging_point_id');
    
    chargingPointSelect.innerHTML = '<option value="">Chargement...</option>';
    
    if (!ownerId) {
        chargingPointSelect.innerHTML = '<option value="">Sélectionnez une borne</option>';
        return;
    }
    
    try {
        const response = await fetch(`/api/credit-requests/owner-charging-points/${ownerId}`);
        const chargingPoints = await response.json();
        
        chargingPointSelect.innerHTML = '<option value="">Sélectionnez une borne (optionnel)</option>';
        chargingPoints.forEach(cp => {
            const option = document.createElement('option');
            option.value = cp.id;
            option.textContent = `${cp.name} - ${cp.location}`;
            chargingPointSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading charging points:', error);
        chargingPointSelect.innerHTML = '<option value="">Erreur de chargement</option>';
    }
});
</script>
```

### 3. **Show** - `resources/views/credit-requests/show.blade.php`

```blade
<div class="max-w-3xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Demande de Crédit #{{ $creditRequest->id }}</h1>
        <span class="badge badge-{{ $creditRequest->status_color }} text-lg px-4 py-2">
            {{ $creditRequest->status_label }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Informations Client --}}
        <div class="card">
            <h2 class="font-semibold mb-4 text-lg">👤 Client</h2>
            <div class="space-y-2">
                <p><strong>Nom:</strong> {{ $creditRequest->client->name }}</p>
                <p><strong>Email:</strong> {{ $creditRequest->client->email }}</p>
                <p><strong>Date demande:</strong> {{ $creditRequest->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        {{-- Informations Propriétaire --}}
        <div class="card">
            <h2 class="font-semibold mb-4 text-lg">🏢 Propriétaire</h2>
            <div class="space-y-2">
                <p><strong>Nom:</strong> {{ $creditRequest->owner->name }}</p>
                <p><strong>Email:</strong> {{ $creditRequest->owner->email }}</p>
                @if($creditRequest->processed_at)
                    <p><strong>Traité le:</strong> {{ $creditRequest->processed_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Détails de la demande --}}
    <div class="card mb-6">
        <h2 class="font-semibold mb-4 text-lg">💰 Détails</h2>
        <div class="space-y-4">
            <div>
                <p class="text-sm text-gray-600">Montant demandé</p>
                <p class="text-3xl font-bold text-green-600">{{ $creditRequest->amount }} MAD</p>
            </div>
            
            @if($creditRequest->chargingPoint)
                <div>
                    <p class="text-sm text-gray-600">Borne de charge</p>
                    <p class="font-medium">{{ $creditRequest->chargingPoint->name }}</p>
                    <p class="text-sm text-gray-600">{{ $creditRequest->chargingPoint->location }}</p>
                </div>
            @endif

            @if($creditRequest->reservation)
                <div>
                    <p class="text-sm text-gray-600">Réservation liée</p>
                    <p class="font-medium">Réservation #{{ $creditRequest->reservation->id }}</p>
                </div>
            @endif

            <div>
                <p class="text-sm text-gray-600">Raison de la demande</p>
                <div class="mt-2 p-4 bg-gray-50 rounded-lg">
                    <p>{{ $creditRequest->reason }}</p>
                </div>
            </div>

            @if($creditRequest->owner_response)
                <div>
                    <p class="text-sm text-gray-600">Réponse du propriétaire</p>
                    <div class="mt-2 p-4 bg-blue-50 rounded-lg">
                        <p>{{ $creditRequest->owner_response }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Actions --}}
    <div class="card">
        <div class="flex gap-4">
            @if($creditRequest->isPending() && auth()->id() === $creditRequest->owner_id)
                {{-- Actions pour propriétaire --}}
                <form method="POST" action="{{ route('credit-requests.approve', $creditRequest) }}" class="flex-1">
                    @csrf
                    <textarea 
                        name="owner_response" 
                        placeholder="Commentaire (optionnel)" 
                        class="form-textarea mb-2"
                        rows="2"></textarea>
                    <button type="submit" class="btn-success w-full">
                        ✅ Approuver et Ajouter le Crédit
                    </button>
                </form>

                <form method="POST" action="{{ route('credit-requests.reject', $creditRequest) }}" class="flex-1">
                    @csrf
                    <textarea 
                        name="owner_response" 
                        placeholder="Raison du rejet (requis)" 
                        class="form-textarea mb-2"
                        rows="2"
                        required></textarea>
                    <button type="submit" class="btn-danger w-full">
                        ❌ Rejeter la Demande
                    </button>
                </form>
            @elseif($creditRequest->isPending() && auth()->id() === $creditRequest->client_id)
                {{-- Actions pour client --}}
                <form method="POST" action="{{ route('credit-requests.cancel', $creditRequest) }}" class="flex-1">
                    @csrf
                    <button type="submit" class="btn-secondary w-full" onclick="return confirm('Annuler cette demande ?')">
                        🚫 Annuler la Demande
                    </button>
                </form>
            @endif

            <a href="{{ route('credit-requests.index') }}" class="btn-primary flex-1 text-center">
                ← Retour à la liste
            </a>
        </div>
    </div>
</div>
```

---

## 🔔 Notifications à Créer

### 1. **NewCreditRequest** - `app/Notifications/NewCreditRequest.php`

```php
<?php

namespace App\Notifications;

use App\Models\CreditRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewCreditRequest extends Notification
{
    use Queueable;

    public function __construct(public CreditRequest $creditRequest)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle demande de crédit')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line($this->creditRequest->client->name . ' vous a envoyé une demande de crédit.')
            ->line('Montant: ' . $this->creditRequest->amount . ' MAD')
            ->line('Raison: ' . $this->creditRequest->reason)
            ->action('Voir la demande', route('credit-requests.show', $this->creditRequest))
            ->line('Merci d\'utiliser EVON Power!');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'credit_request_id' => $this->creditRequest->id,
            'client_name' => $this->creditRequest->client->name,
            'amount' => $this->creditRequest->amount,
            'title' => 'Nouvelle demande de crédit',
            'message' => $this->creditRequest->client->name . ' demande ' . $this->creditRequest->amount . ' MAD de crédit',
            'action_url' => route('credit-requests.show', $this->creditRequest),
        ];
    }
}
```

### 2. **CreditRequestApproved** - `app/Notifications/CreditRequestApproved.php`

```php
<?php

namespace App\Notifications;

use App\Models\CreditRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CreditRequestApproved extends Notification
{
    use Queueable;

    public function __construct(public CreditRequest $creditRequest)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Demande de crédit approuvée ✅')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Votre demande de crédit a été approuvée!')
            ->line('Montant: ' . $this->creditRequest->amount . ' MAD')
            ->line('Le crédit a été ajouté à votre compte.')
            @if($this->creditRequest->owner_response)
                ->line('Message: ' . $this->creditRequest->owner_response)
            @endif
            ->action('Voir mes crédits', route('credits.index'))
            ->line('Merci d\'utiliser EVON Power!');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'credit_request_id' => $this->creditRequest->id,
            'owner_name' => $this->creditRequest->owner->name,
            'amount' => $this->creditRequest->amount,
            'title' => 'Demande de crédit approuvée',
            'message' => 'Votre demande de ' . $this->creditRequest->amount . ' MAD a été approuvée',
            'action_url' => route('credit-requests.show', $this->creditRequest),
        ];
    }
}
```

### 3. **CreditRequestRejected** - `app/Notifications/CreditRequestRejected.php`

```php
<?php

namespace App\Notifications;

use App\Models\CreditRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CreditRequestRejected extends Notification
{
    use Queueable;

    public function __construct(public CreditRequest $creditRequest)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Demande de crédit rejetée')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Votre demande de crédit a été rejetée.')
            ->line('Montant: ' . $this->creditRequest->amount . ' MAD')
            ->line('Raison: ' . ($this->creditRequest->owner_response ?? 'Non spécifiée'))
            ->action('Voir la demande', route('credit-requests.show', $this->creditRequest))
            ->line('Vous pouvez faire une nouvelle demande ou contacter le propriétaire.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'credit_request_id' => $this->creditRequest->id,
            'owner_name' => $this->creditRequest->owner->name,
            'amount' => $this->creditRequest->amount,
            'title' => 'Demande de crédit rejetée',
            'message' => 'Votre demande de ' . $this->creditRequest->amount . ' MAD a été rejetée',
            'action_url' => route('credit-requests.show', $this->creditRequest),
        ];
    }
}
```

---

## 🔗 Mise à Jour de la Sidebar

Ajouter dans `resources/views/layouts/partials/evon-sidebar.blade.php`:

```blade
{{-- Section Finances --}}
[
    'title' => 'Finances',
    'visible' => true,
    'items' => [
        // ... autres items ...
        
        // Credit Requests - Pour TOUS les utilisateurs
        [
            'label' => __('messages.credit_requests'),
            'route' => 'credit-requests.index',
            'icon' => 'wallet',  // ou 'credit-card'
            'badge' => auth()->user()->hasAnyRole(['admin', 'integrator', 'operator', 'partner']) 
                ? \App\Models\CreditRequest::where('owner_id', auth()->id())->pending()->count()
                : null,
            'badge_class' => 'bg-yellow-500',
            'permission' => null  // Accessible à tous
        ],
    ]
],
```

---

## 🔄 Mise à Jour du Modèle User

Ajouter dans `app/Models/User.php`:

```php
/**
 * Get credit requests made by this user (as client)
 */
public function creditRequests(): HasMany
{
    return $this->hasMany(CreditRequest::class, 'client_id');
}

/**
 * Get credit requests received by this user (as owner)
 */
public function receivedCreditRequests(): HasMany
{
    return $this->hasMany(CreditRequest::class, 'owner_id');
}

/**
 * Add credit to user account
 */
public function addCredit(float $amount, string $description = null): bool
{
    // Assuming you have a credits table or wallet system
    // This is a placeholder - adapt to your existing credit system
    
    DB::table('user_credits')->insert([
        'user_id' => $this->id,
        'amount' => $amount,
        'type' => 'credit',
        'description' => $description,
        'created_at' => now(),
    ]);
    
    // Or update a balance column:
    // $this->increment('credit_balance', $amount);
    
    return true;
}
```

---

## 📝 Traductions

Ajouter dans `resources/lang/fr/messages.php`:

```php
'credit_requests' => 'Demandes de Crédit',
'new_credit_request' => 'Nouvelle Demande',
'credit_request_details' => 'Détails de la Demande',
'approve_request' => 'Approuver',
'reject_request' => 'Rejeter',
'cancel_request' => 'Annuler',
'request_amount' => 'Montant Demandé',
'request_reason' => 'Raison',
'owner_response' => 'Réponse du Propriétaire',
'request_status' => 'Statut',
'pending_requests' => 'Demandes en Attente',
'approved_requests' => 'Demandes Approuvées',
'rejected_requests' => 'Demandes Rejetées',
```

---

## 🚀 Installation

### Étape 1: Inclure les routes
```php
// Dans routes/web.php
require __DIR__.'/web_credit_requests.php';
```

### Étape 2: Exécuter la migration
```bash
php artisan migrate
```

### Étape 3: Créer les vues
Créer les fichiers Blade mentionnés ci-dessus.

### Étape 4: Créer les notifications
Créer les 3 classes de notifications.

### Étape 5: Tester
```bash
# Créer des données de test
php artisan tinker

# Créer une demande de test
$client = User::find(1);
$owner = User::find(2);

CreditRequest::create([
    'client_id' => $client->id,
    'owner_id' => $owner->id,
    'amount' => 100,
    'reason' => 'Test de demande de crédit',
    'status' => 'pending',
]);
```

---

## 🎯 Flux Utilisateur

### Client:
1. Va sur "Demandes de Crédit" (sidebar)
2. Clique "Nouvelle Demande"
3. Sélectionne un propriétaire (liste filtrée des propriétaires utilisés)
4. Entre le montant et la raison
5. Envoie la demande
6. Reçoit une notification quand approuvée/rejetée

### Propriétaire:
1. Reçoit notification de nouvelle demande
2. Va sur "Demandes de Crédit"
3. Voit badge avec nombre de demandes en attente
4. Examine la demande
5. Approuve (crédit ajouté automatiquement) ou Rejette
6. Client est notifié

---

## 📊 Statistiques Dashboard

Ajouter sur le dashboard admin:

```blade
{{-- Statistiques Credit Requests --}}
<div class="stat-card">
    <h3>Demandes de Crédit</h3>
    <div class="grid grid-cols-3 gap-4 mt-4">
        <div>
            <p class="text-yellow-600 text-2xl font-bold">
                {{ \App\Models\CreditRequest::pending()->count() }}
            </p>
            <p class="text-sm">En attente</p>
        </div>
        <div>
            <p class="text-green-600 text-2xl font-bold">
                {{ \App\Models\CreditRequest::approved()->whereMonth('created_at', now()->month)->count() }}
            </p>
            <p class="text-sm">Approuvées ce mois</p>
        </div>
        <div>
            <p class="text-blue-600 text-2xl font-bold">
                {{ number_format(\App\Models\CreditRequest::approved()->whereMonth('created_at', now()->month)->sum('amount'), 0) }} MAD
            </p>
            <p class="text-sm">Total ce mois</p>
        </div>
    </div>
</div>
```

---

## ✅ Checklist Finale

- [ ] Migration créée et exécutée
- [ ] Modèle CreditRequest créé
- [ ] Contrôleur créé avec toutes les méthodes
- [ ] Routes ajoutées
- [ ] Vues créées (index, create, show)
- [ ] Notifications créées (3 types)
- [ ] Sidebar mise à jour
- [ ] Traductions ajoutées
- [ ] Méthodes User ajoutées
- [ ] Tests effectués
- [ ] Documentation complète

---

## 🎉 Résultat Final

✅ **Système complet** de gestion des demandes de crédit
✅ **Rôles respectés** : clients demandent, propriétaires approuvent
✅ **Notifications** automatiques
✅ **Interface intuitive** pour tous les rôles
✅ **Statistiques** en temps réel
✅ **Historique complet** des demandes
✅ **Sécurité** : vérifications des permissions
✅ **Performance** : requêtes optimisées avec index

---

**Version:** 1.0  
**Date:** Décembre 2025  
**Status:** ✅ Ready for Implementation

**Profitez de votre nouveau système de gestion des crédits! 💳✨**

