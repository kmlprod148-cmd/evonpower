@extends('layouts.app')

@section('title', 'Demandes de retrait')

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-wallet me-2"></i>
            Demandes de retrait
        </h1>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total demandé</h6>
                            <h3 class="mb-0">{{ number_format($stats['total_amount'] ?? 0, 2) }} €</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">En attente</h6>
                            <h3 class="mb-0">{{ $stats['pending_count'] ?? 0 }}</h3>
                            <small>{{ number_format($stats['pending_amount'] ?? 0, 2) }} €</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Terminés</h6>
                            <h3 class="mb-0">{{ $stats['completed_count'] ?? 0 }}</h3>
                            <small>{{ number_format($stats['completed_amount'] ?? 0, 2) }} €</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Rejetés/Échoués</h6>
                            <h3 class="mb-0">{{ ($stats['rejected_count'] ?? 0) + ($stats['failed_count'] ?? 0) }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.withdrawals.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tous</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['status'] ?? '') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="withdrawal_method" class="form-label">Méthode</label>
                    <select class="form-select" id="withdrawal_method" name="withdrawal_method">
                        <option value="">Toutes</option>
                        <option value="bank_transfer" {{ ($filters['withdrawal_method'] ?? '') == 'bank_transfer' ? 'selected' : '' }}>Virement bancaire</option>
                        <option value="card" {{ ($filters['withdrawal_method'] ?? '') == 'card' ? 'selected' : '' }}>Carte bancaire</option>
                        <option value="wallet" {{ ($filters['withdrawal_method'] ?? '') == 'wallet' ? 'selected' : '' }}>Portefeuille</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Du</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Au</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des demandes -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Propriétaire</th>
                            <th>Montant</th>
                            <th>Frais</th>
                            <th>Net</th>
                            <th>Méthode</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($withdrawals as $withdrawal)
                            <tr>
                                <td>
                                    <strong>#{{ $withdrawal->id }}</strong>
                                </td>
                                <td>
                                    @if($withdrawal->owner)
                                        <span class="badge bg-secondary">
                                            {{ class_basename($withdrawal->owner_type) }}
                                        </span>
                                        <br>
                                        {{ $withdrawal->owner->name ?? $withdrawal->owner->id }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $withdrawal->formatted_amount }}</strong>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $withdrawal->formatted_fee }}</span>
                                </td>
                                <td>
                                    <strong class="text-success">{{ $withdrawal->formatted_net_amount }}</strong>
                                </td>
                                <td>
                                    @switch($withdrawal->withdrawal_method)
                                        @case('bank_transfer')
                                            <i class="fas fa-university me-1"></i>
                                            @if($withdrawal->bank_name)
                                                {{ $withdrawal->bank_name }}
                                            @else
                                                Virement
                                            @endif
                                            @break
                                        @case('card')
                                            <i class="fas fa-credit-card me-1"></i>
                                            @if($withdrawal->card_brand)
                                                {{ $withdrawal->card_brand }} ****{{ $withdrawal->card_last4 }}
                                            @else
                                                Carte
                                            @endif
                                            @break
                                        @case('wallet')
                                            <i class="fas fa-wallet me-1"></i>
                                            Wallet
                                            @break
                                        @default
                                            {{ $withdrawal->withdrawal_method }}
                                    @endswitch
                                </td>
                                <td>
                                    @switch($withdrawal->status)
                                        @case('pending')
                                            <span class="badge bg-warning">En attente</span>
                                            @break
                                        @case('approved')
                                            <span class="badge bg-info">Approuvé</span>
                                            @break
                                        @case('processing')
                                            <span class="badge bg-primary">En cours</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-success">Terminé</span>
                                            @break
                                        @case('rejected')
                                            <span class="badge bg-danger">Rejeté</span>
                                            @break
                                        @case('failed')
                                            <span class="badge bg-danger">Échoué</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-secondary">Annulé</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>
                                    {{ $withdrawal->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.withdrawals.show', $withdrawal->id) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Aucune demande de retrait trouvée</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($withdrawals->hasPages())
            <div class="card-footer">
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
