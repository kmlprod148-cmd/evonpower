@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h1 class="h3 mb-0"><i class="bi bi-bag-check"></i> Commandes en attente</h1>
        <a href="{{ route('admin.orders.history') }}" class="btn btn-outline-primary"><i class="bi bi-clock-history"></i> Historique</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-x-circle-fill"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="GET" class="row g-3 mb-4 bg-light p-3 rounded shadow-sm">
        <div class="col-md-3">
            <label for="from" class="form-label">Date de début</label>
            <input type="date" name="from" id="from" class="form-control" value="{{ request('from') }}">
        </div>
        <div class="col-md-3">
            <label for="to" class="form-label">Date de fin</label>
            <input type="date" name="to" id="to" class="form-control" value="{{ request('to') }}">
        </div>
        <div class="col-md-3">
            <label for="status" class="form-label">Statut</label>
            <select name="status" id="status" class="form-select">
                <option value="pending" {{ request('status', 'pending') == 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmée</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulée</option>
            </select>
        </div>
        <div class="col-md-3 align-self-end d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filtrer</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary w-100"><i class="bi bi-arrow-clockwise"></i> Réinitialiser</a>
        </div>
    </form>

    <div class="table-responsive shadow-sm rounded bg-white">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Utilisateur</th>
                    <th>Borne</th>
                    <th>Plan</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Détails</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="fw-bold">#{{ $order->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $order->user ? $order->user->name : '-' }}</div>
                            <div class="text-muted small">ID: {{ $order->user_id }}</div>
                        </td>
                        <td>
                            @if($order->chargingPoint)
                                <div class="fw-semibold">{{ $order->chargingPoint->name ?? $order->chargingPoint->id }}</div>
                                <div class="text-muted small">ID: {{ $order->chargingPoint->id }}</div>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($order->plan)
                                <div class="fw-semibold">{{ $order->plan->name ?? $order->plan->id }}</div>
                                <div class="text-muted small">ID: {{ $order->plan->id }}</div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end">{{ $order->amount ?? '-' }} €</td>
                        <td>
                            @if($order->status == 'pending')
                                <span class="badge bg-warning text-dark px-3 py-2">En attente</span>
                            @elseif($order->status == 'confirmed')
                                <span class="badge bg-success px-3 py-2">Confirmée</span>
                            @elseif($order->status == 'cancelled')
                                <span class="badge bg-danger px-3 py-2">Annulée</span>
                            @else
                                <span class="badge bg-secondary px-3 py-2">{{ ucfirst($order->status) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($order->details)
                                <button class="btn btn-link p-0 text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#details-{{ $order->id }}" aria-expanded="false" aria-controls="details-{{ $order->id }}">
                                    <i class="bi bi-eye"></i> Voir
                                </button>
                                <div class="collapse mt-2" id="details-{{ $order->id }}">
                                    <pre class="bg-light p-2 rounded border" style="font-size: 0.85em;">{{ json_encode(json_decode($order->details), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('admin.orders.confirm', $order->id) }}" onsubmit="return confirm('Confirmer cette commande ?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-circle"></i> Confirmer</button>
                                </form>
                                <form method="POST" action="{{ route('admin.orders.cancel', $order->id) }}" onsubmit="return confirm('Annuler cette commande ?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-x-circle"></i> Annuler</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">Aucune commande en attente.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection 