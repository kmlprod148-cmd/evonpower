@extends('layouts.app')

@section('title', 'Factures Intégrateurs')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-invoice me-2"></i>
            Factures Intégrateurs
        </h1>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.integrator-invoices.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Recherche</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="N° facture..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label for="integrator_id" class="form-label">Intégrateur</label>
                    <select class="form-select" id="integrator_id" name="integrator_id">
                        <option value="">Tous les intégrateurs</option>
                        @foreach($integrators as $integrator)
                            <option value="{{ $integrator->id }}" 
                                    {{ request('integrator_id') == $integrator->id ? 'selected' : '' }}>
                                {{ $integrator->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tous</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Payée</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>En retard</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulée</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="invoice_type" class="form-label">Type</label>
                    <select class="form-select" id="invoice_type" name="invoice_type">
                        <option value="">Tous</option>
                        <option value="maintenance" {{ request('invoice_type') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="terminal" {{ request('invoice_type') == 'terminal' ? 'selected' : '' }}>Borne active</option>
                        <option value="commission" {{ request('invoice_type') == 'commission' ? 'selected' : '' }}>Commission</option>
                        <option value="consolidated" {{ request('invoice_type') == 'consolidated' ? 'selected' : '' }}>Consolidée</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>
                        Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Intégrateur</th>
                            <th>Type</th>
                            <th>Période</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Échéance</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.integrator-invoices.show', $invoice) }}">
                                        {{ $invoice->invoice_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($invoice->integrator)
                                        {{ $invoice->integrator->name }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @switch($invoice->invoice_type)
                                        @case('maintenance')
                                            <span class="badge bg-info">Maintenance</span>
                                            @break
                                        @case('terminal')
                                            <span class="badge bg-info">Borne active</span>
                                            @break
                                        @case('commission')
                                            <span class="badge bg-primary">Commission</span>
                                            @break
                                        @case('consolidated')
                                            <span class="badge bg-dark">Consolidée</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>{{ $invoice->formatted_period }}</td>
                                <td>
                                    <strong>{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</strong>
                                </td>
                                <td>
                                    @switch($invoice->status)
                                        @case('paid')
                                            <span class="badge bg-success">Payée</span>
                                            @break
                                        @case('pending')
                                            <span class="badge bg-warning">En attente</span>
                                            @break
                                        @case('overdue')
                                            <span class="badge bg-danger">En retard</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-secondary">Annulée</span>
                                            @break
                                        @default
                                            <span class="badge bg-info">{{ $invoice->status }}</span>
                                    @endswitch
                                </td>
                                <td>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.integrator-invoices.show', $invoice) }}" 
                                           class="btn btn-sm btn-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($invoice->status === 'pending')
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#payModal{{ $invoice->id }}"
                                                    title="Marquer payée">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Pay Modal -->
                            @if($invoice->status === 'pending')
                                <div class="modal fade" id="payModal{{ $invoice->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Marquer la facture comme payée</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('admin.integrator-invoices.mark-paid', $invoice) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="payment_method" class="form-label">Mode de paiement</label>
                                                        <select class="form-select" name="payment_method" required>
                                                            <option value="">Sélectionner</option>
                                                            <option value="bank_transfer">Virement bancaire</option>
                                                            <option value="cash">Espèces</option>
                                                            <option value="card">Carte</option>
                                                            <option value="other">Autre</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="payment_reference" class="form-label">Référence</label>
                                                        <input type="text" class="form-control" name="payment_reference">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-success">Confirmer le paiement</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p>Aucune facture trouvée</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
