@extends('layouts.admin')

@section('title', 'Historique des Transactions - Admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i>
                        Historique des Transactions - Vue Admin
                    </h3>
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" onclick="refreshTransactions()">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                        <button class="btn btn-sm btn-success" onclick="exportTransactions('csv')">
                            <i class="fas fa-download"></i> Exporter CSV
                        </button>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.transaction-history.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="date_from">Date de début</label>
                                <input type="date" name="date_from" id="date_from" 
                                       class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to">Date de fin</label>
                                <input type="date" name="date_to" id="date_to" 
                                       class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label for="type">Type</label>
                                <select name="type" id="type" class="form-control">
                                    <option value="">Tous les types</option>
                                    <option value="commission" {{ ($filters['type'] ?? '') == 'commission' ? 'selected' : '' }}>Commission</option>
                                    <option value="revenue" {{ ($filters['type'] ?? '') == 'revenue' ? 'selected' : '' }}>Revenu</option>
                                    <option value="payment" {{ ($filters['type'] ?? '') == 'payment' ? 'selected' : '' }}>Paiement</option>
                                    <option value="withdrawal" {{ ($filters['type'] ?? '') == 'withdrawal' ? 'selected' : '' }}>Retrait</option>
                                    <option value="refund" {{ ($filters['type'] ?? '') == 'refund' ? 'selected' : '' }}>Remboursement</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status">Statut</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">Tous les statuts</option>
                                    <option value="completed" {{ ($filters['status'] ?? '') == 'completed' ? 'selected' : '' }}>Terminé</option>
                                    <option value="pending" {{ ($filters['status'] ?? '') == 'pending' ? 'selected' : '' }}>En attente</option>
                                    <option value="failed" {{ ($filters['status'] ?? '') == 'failed' ? 'selected' : '' }}>Échoué</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Filtrer
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Résumé -->
                    @if($summary)
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-list"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Transactions</span>
                                    <span class="info-box-number">{{ $summary['total_transactions'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Crédits</span>
                                    <span class="info-box-number">{{ number_format($summary['total_credits'], 2) }} EUR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Débits</span>
                                    <span class="info-box-number">{{ number_format($summary['total_debits'], 2) }} EUR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box {{ $summary['net_balance'] >= 0 ? 'bg-success' : 'bg-warning' }}">
                                <span class="info-box-icon"><i class="fas fa-balance-scale"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Solde Net</span>
                                    <span class="info-box-number">{{ number_format($summary['net_balance'], 2) }} EUR</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Table des transactions -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Utilisateur</th>
                                    <th>Type</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Description</th>
                                    <th>Point de Charge</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr class="transaction-row" data-transaction-id="{{ $transaction['id'] }}">
                                    <td>
                                        <span class="badge badge-secondary">#{{ $transaction['id'] }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $transaction['created_at']->format('d/m/Y H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $transaction['user_name'] }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $transaction['user_email'] }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $transaction['type'] == 'commission' ? 'success' : ($transaction['type'] == 'payment' ? 'warning' : 'info') }}">
                                            {{ ucfirst($transaction['type']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($transaction['is_credit'])
                                                <i class="fas fa-arrow-up text-success mr-1"></i>
                                                <span class="text-success font-weight-bold">
                                                    +{{ number_format($transaction['amount'], 2) }} EUR
                                                </span>
                                            @elseif($transaction['is_debit'])
                                                <i class="fas fa-arrow-down text-danger mr-1"></i>
                                                <span class="text-danger font-weight-bold">
                                                    -{{ number_format(abs($transaction['amount']), 2) }} EUR
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    {{ number_format($transaction['amount'], 2) }} EUR
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $transaction['status'] == 'completed' ? 'success' : ($transaction['status'] == 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($transaction['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $transaction['description'] }}">
                                            {{ $transaction['description'] }}
                                        </div>
                                        @if($transaction['reference'])
                                        <small class="text-muted">Ref: {{ $transaction['reference'] }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($transaction['charging_point'])
                                            <div>
                                                <strong>{{ $transaction['charging_point']['name'] }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $transaction['charging_point']['serial_number'] }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-info" 
                                                    onclick="viewTransactionDetails({{ $transaction['id'] }})"
                                                    title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($transaction['reservation'])
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewReservation({{ $transaction['reservation']['id'] }})"
                                                    title="Voir la réservation">
                                                <i class="fas fa-calendar"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <br>
                                            Aucune transaction trouvée
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($pagination)
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <small class="text-muted">
                                Affichage de {{ ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 }} 
                                à {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) }} 
                                sur {{ $pagination['total'] }} transactions
                            </small>
                        </div>
                        <div>
                            {{-- Pagination links would go here --}}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les détails de transaction -->
<div class="modal fade" id="transactionDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la Transaction</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="transactionDetailsContent">
                <!-- Contenu chargé dynamiquement -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function refreshTransactions() {
    location.reload();
}

function exportTransactions(format) {
    const url = new URL('{{ route("admin.transaction-history.export") }}', window.location.origin);
    url.searchParams.set('format', format);
    
    // Ajouter les filtres actuels
    const filters = @json($filters);
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            url.searchParams.set(key, filters[key]);
        }
    });
    
    window.open(url.toString(), '_blank');
}

function viewTransactionDetails(transactionId) {
    // Charger les détails de la transaction via AJAX
    fetch(`/admin/transaction-history/${transactionId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('transactionDetailsContent').innerHTML = data.html;
                const modalEl = document.getElementById('transactionDetailsModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else {
                alert('Erreur lors du chargement des détails');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du chargement des détails');
        });
}

function viewReservation(reservationId) {
    window.open(`/admin/reservations/${reservationId}`, '_blank');
}

// Auto-refresh toutes les 5 minutes
setInterval(function() {
    // Optionnel: actualiser automatiquement
}, 300000);
</script>
@endpush

@push('styles')
<style>
.transaction-row:hover {
    background-color: #f8f9fa;
}

.text-success {
    color: #28a745 !important;
}

.text-danger {
    color: #dc3545 !important;
}

.badge-success {
    background-color: #28a745;
}

.badge-danger {
    background-color: #dc3545;
}

.badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.info-box {
    border-radius: 0.375rem;
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
}

.info-box-icon {
    border-radius: 0.375rem 0 0 0.375rem;
}
</style>
@endpush
