@extends('layouts.app')

@section('title', 'Paiements en Espèces')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Paiements en Espèces</h1>
                <a href="{{ route('admin.cash-payments.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouveau Paiement
                </a>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Paiements</h5>
                            <h2 class="mb-0">{{ $stats['count'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Montant Total (MAD)</h5>
                            <h2 class="mb-0">{{ number_format($stats['by_currency']['MAD'] ?? 0, 2) }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Montant Total (EUR)</h5>
                            <h2 class="mb-0">{{ number_format($stats['by_currency']['EUR'] ?? 0, 2) }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">En Attente</h5>
                            <h2 class="mb-0">{{ $stats['count'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.cash-payments.index') }}" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="all">Tous</option>
                                <option value="completed">Complétés</option>
                                <option value="pending">En attente</option>
                                <option value="failed">Échoués</option>
                                <option value="cancelled">Annulés</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date début</label>
                            <input type="date" name="date_from" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date fin</label>
                            <input type="date" name="date_to" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-outline-primary w-100">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Client</th>
                                    <th>Montant</th>
                                    <th>Devise</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cashPayments as $payment)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">{{ $payment->reference }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.users.show', $payment->user_id) }}">
                                            {{ $payment->user->name ?? 'N/A' }}
                                        </a>
                                    </td>
                                    <td>{{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ $payment->currency }}</td>
                                    <td>
                                        @switch($payment->status)
                                            @case('completed')
                                                <span class="badge bg-success">Complété</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning">En attente</span>
                                                @break
                                            @case('failed')
                                                <span class="badge bg-danger">Échoué</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-secondary">Annulé</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('admin.cash-payments.show', $payment->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-muted mb-0">Aucun paiement en espèces trouvé</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    {{ $cashPayments->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
