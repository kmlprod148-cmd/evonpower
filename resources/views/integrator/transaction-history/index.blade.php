@extends('layouts.integrator')

@section('title', 'Historique des Transactions - Intégrateur')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i>
                        Historique des Transactions - Vue Intégrateur
                    </h3>
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                        <button class="btn btn-sm btn-success" onclick="exportTransactions()">
                            <i class="fas fa-download"></i> Exporter CSV
                        </button>
                    </div>
                </div>

                <div class="card-body">

                    {{-- Filtres --}}
                    <form method="GET" action="{{ route('integrator.transaction-history.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-2">
                                <label for="date_from">Date de début</label>
                                <input type="date" name="date_from" id="date_from"
                                       class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to">Date de fin</label>
                                <input type="date" name="date_to" id="date_to"
                                       class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label for="status">Statut</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">Tous les statuts</option>
                                    <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Terminé</option>
                                    <option value="confirmed" {{ ($filters['status'] ?? '') === 'confirmed' ? 'selected' : '' }}>Confirmé</option>
                                    <option value="pending"   {{ ($filters['status'] ?? '') === 'pending'   ? 'selected' : '' }}>En attente</option>
                                    <option value="failed"    {{ ($filters['status'] ?? '') === 'failed'    ? 'selected' : '' }}>Échoué</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="search">Recherche</label>
                                <input type="text" name="search" id="search" class="form-control"
                                       placeholder="ID, description, référence…"
                                       value="{{ $filters['search'] ?? '' }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-block mr-1">
                                    <i class="fas fa-search"></i> Filtrer
                                </button>
                                <a href="{{ route('integrator.transaction-history.index') }}"
                                   class="btn btn-secondary btn-block">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- Résumé statistique --}}
                    @if($summary)
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-list"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Transactions</span>
                                    <span class="info-box-number">{{ number_format($summary['total_transactions']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-euro-sign"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Revenus Filtrés</span>
                                    <span class="info-box-number">{{ number_format($summary['total_credits'], 2) }} EUR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Débits</span>
                                    <span class="info-box-number">{{ number_format($summary['total_debits'], 2) }} EUR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box {{ $summary['net_balance'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Solde Wallet</span>
                                    <span class="info-box-number">{{ number_format($summary['net_balance'], 2) }} EUR</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Tableau des transactions --}}
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#ID</th>
                                    <th>Date</th>
                                    <th>Opérateur / Client</th>
                                    <th>Point de Charge</th>
                                    <th>Type</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr class="transaction-row">
                                    <td>
                                        <span class="badge badge-secondary">#{{ $transaction['id'] }}</span>
                                        @if($transaction['reference'] && $transaction['reference'] != $transaction['id'])
                                            <br><small class="text-muted">Ref: {{ $transaction['reference'] }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $transaction['created_at'] instanceof \Carbon\Carbon
                                                ? $transaction['created_at']->format('d/m/Y H:i')
                                                : \Carbon\Carbon::parse($transaction['created_at'])->format('d/m/Y H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        <strong>{{ $transaction['user_name'] }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $transaction['user_email'] }}</small>
                                    </td>
                                    <td>
                                        @if($transaction['charging_point'])
                                            <strong>{{ $transaction['charging_point']['name'] }}</strong>
                                            @if(!empty($transaction['charging_point']['serial_number']))
                                                <br><small class="text-muted">{{ $transaction['charging_point']['serial_number'] }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ ucfirst($transaction['type']) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $collectedAmount = $transaction['transaction']['collected_amount']
                                                ?? $transaction['transaction']['price_total']
                                                ?? ($transaction['transaction']['reservation']['estimated_cost'] ?? null);
                                            $displayAmount = $transaction['amount'];
                                        @endphp

                                        <div class="d-flex flex-column align-items-start">
                                            @if($transaction['is_credit'])
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-arrow-up text-success mr-1"></i>
                                                    <span class="text-success font-weight-bold">
                                                        +{{ number_format($displayAmount, 2) }} EUR
                                                    </span>
                                                </div>
                                                @if($collectedAmount && abs($collectedAmount - $displayAmount) > 0.001)
                                                    <small class="text-muted" title="Montant total TTC collecté">
                                                        Total: {{ number_format($collectedAmount, 2) }} EUR
                                                    </small>
                                                @endif
                                            @elseif($transaction['is_debit'])
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-arrow-down text-danger mr-1"></i>
                                                    <span class="text-danger font-weight-bold">
                                                        -{{ number_format(abs($displayAmount), 2) }} EUR
                                                    </span>
                                                </div>
                                                @if($collectedAmount)
                                                    <small class="text-muted">Total: {{ number_format($collectedAmount, 2) }} EUR</small>
                                                @endif
                                            @else
                                                <span class="font-weight-bold">
                                                    {{ number_format($collectedAmount ?? $displayAmount, 2) }} EUR
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $statusColor = match($transaction['status']) {
                                                'completed', 'confirmed', 'approved' => 'success',
                                                'pending'                            => 'warning',
                                                'failed', 'cancelled', 'rejected'    => 'danger',
                                                default                              => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $statusColor }}">
                                            {{ ucfirst($transaction['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width:220px;" title="{{ $transaction['description'] }}">
                                            {{ $transaction['description'] ?: '—' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('transactions.show', $transaction['id']) }}"
                                               class="btn btn-sm btn-outline-info"
                                               title="Voir les détails"
                                               target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($transaction['reservation'])
                                                <a href="/integrator/reservations/{{ $transaction['reservation']['id'] }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Voir la réservation"
                                                   target="_blank">
                                                    <i class="fas fa-calendar"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            Aucune transaction trouvée pour les filtres sélectionnés.
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($pagination && $pagination['total'] > $pagination['per_page'])
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Affichage de
                            {{ ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 }}
                            à
                            {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) }}
                            sur {{ number_format($pagination['total']) }} transactions
                        </small>
                        <div>
                            @if($pagination['current_page'] > 1)
                                <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-chevron-left"></i> Précédent
                                </a>
                            @endif
                            @if($pagination['current_page'] < $pagination['last_page'])
                                <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    Suivant <i class="fas fa-chevron-right"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function exportTransactions() {
    const exportUrl = new URL('{{ route("integrator.transaction-history.export") }}', window.location.origin);
    const filters = @json($filters);
    Object.entries(filters).forEach(([key, value]) => {
        if (value) exportUrl.searchParams.set(key, value);
    });
    window.location.href = exportUrl.toString();
}
</script>
@endpush

@push('styles')
<style>
.transaction-row:hover { background-color: #f8f9fa; }
.info-box { border-radius: 0.375rem; box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2); }
.info-box-icon { border-radius: 0.375rem 0 0 0.375rem; }
.badge-success  { background-color: #28a745; }
.badge-danger   { background-color: #dc3545; }
.badge-warning  { background-color: #ffc107; color: #212529; }
.badge-info     { background-color: #17a2b8; }
.badge-secondary { background-color: #6c757d; }
</style>
@endpush
