@extends('layouts.app')

@section('title', 'Facture ' . $invoice->invoice_number)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-invoice me-2"></i>
                Facture: {{ $invoice->invoice_number }}
            </h1>
            <p class="text-muted mb-0">{{ $invoice->type_label }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.integrator-invoices.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Retour
            </a>
        </div>
    </div>

    <!-- Status -->
    <div class="mb-4">
        @switch($invoice->status)
            @case('paid')
                <span class="badge bg-success fs-5">Payée</span>
                @break
            @case('pending')
                <span class="badge bg-warning fs-5">En attente</span>
                @break
            @case('overdue')
                <span class="badge bg-danger fs-5">En retard</span>
                @break
            @case('cancelled')
                <span class="badge bg-secondary fs-5">Annulée</span>
                @break
            @default
                <span class="badge bg-info fs-5">{{ $invoice->status }}</span>
        @endswitch
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Invoice Details -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Détails de la facture</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Intégrateur</dt>
                                <dd class="col-sm-8">
                                    @if($invoice->integrator)
                                        {{ $invoice->integrator->name }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </dd>
                                
                                <dt class="col-sm-4">Contrat</dt>
                                <dd class="col-sm-8">
                                    @if($invoice->contractProfile)
                                        <a href="{{ route('admin.integrator-contracts.show', $invoice->contractProfile) }}">
                                            {{ $invoice->contractProfile->contract_number }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </dd>
                                
                                <dt class="col-sm-4">Période</dt>
                                <dd class="col-sm-8">{{ $invoice->formatted_period }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Date emission</dt>
                                <dd class="col-sm-8">{{ $invoice->issue_date?->format('d/m/Y') }}</dd>
                                
                                <dt class="col-sm-4">Date échéance</dt>
                                <dd class="col-sm-8">{{ $invoice->due_date?->format('d/m/Y') }}</dd>
                                
                                @if($invoice->paid_date)
                                    <dt class="col-sm-4">Date paiement</dt>
                                    <dd class="col-sm-8">{{ $invoice->paid_date?->format('d/m/Y') }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Éléments de facturation</h6>
                </div>
                <div class="card-body">
                    @if($invoice->lineItems->count() > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th class="text-end">Qté</th>
                                        <th class="text-end">Prix unitaire</th>
                                        <th class="text-end">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->lineItems as $item)
                                        <tr>
                                            <td>{{ $item->description }}</td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $item->type_label }}</span>
                                            </td>
                                            <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                                            <td class="text-end">{{ number_format($item->unit_price, 2) }} {{ $item->currency }}</td>
                                            <td class="text-end">{{ number_format($item->subtotal, 2) }} {{ $item->currency }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Sous-total</strong></td>
                                        <td class="text-end">{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end">TVA</td>
                                        <td class="text-end">{{ number_format($invoice->tax_amount, 2) }} {{ $invoice->currency }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total</strong></td>
                                        <td class="text-end"><strong>{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>Aucun élément</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Payment Details -->
            @if($invoice->payment_method)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Informations de paiement</h6>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-md-3">Mode de paiement</dt>
                            <dd class="col-md-9">
                                @switch($invoice->payment_method)
                                    @case('bank_transfer')
                                        Virement bancaire
                                        @break
                                    @case('cash')
                                        Espèces
                                        @break
                                    @case('card')
                                        Carte
                                        @break
                                    @default
                                        {{ $invoice->payment_method }}
                                @endswitch
                            </dd>
                            
                            @if($invoice->payment_reference)
                                <dt class="col-md-3">Référence</dt>
                                <dd class="col-md-9">{{ $invoice->payment_reference }}</dd>
                            @endif
                            
                            @if($invoice->payment_notes)
                                <dt class="col-md-3">Notes</dt>
                                <dd class="col-md-9">{{ $invoice->payment_notes }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Summary -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Résumé</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sous-total</span>
                        <span>{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>TVA</span>
                        <span>{{ number_format($invoice->tax_amount, 2) }} {{ $invoice->currency }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total</strong>
                        <strong class="text-primary">{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</strong>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Actions</h6>
                </div>
                <div class="card-body">
                    @if($invoice->status === 'pending')
                        <button type="button" class="btn btn-success w-100 mb-2" 
                                data-bs-toggle="modal" data-bs-target="#payModal">
                            <i class="fas fa-check me-1"></i>
                            Marquer comme payée
                        </button>
                        
                        <button type="button" class="btn btn-danger w-100" 
                                data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="fas fa-times me-1"></i>
                            Annuler la facture
                        </button>
                    @elseif($invoice->status === 'paid')
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-1"></i>
                            Cette facture a été payée.
                        </div>
                    @elseif($invoice->status === 'cancelled')
                        <div class="alert alert-secondary mb-0">
                            <i class="fas fa-times-circle me-1"></i>
                            Cette facture a été annulée.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            @if($invoice->notes)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Notes</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $invoice->notes }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Pay Modal -->
@if($invoice->status === 'pending')
    <div class="modal fade" id="payModal" tabindex="-1">
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
                            <label for="payment_method" class="form-label">Mode de paiement <span class="text-danger">*</span></label>
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

    <!-- Cancel Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Annuler la facture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.integrator-invoices.cancel', $invoice) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir annuler cette facture ?</p>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Motif d'annulation</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Non</button>
                        <button type="submit" class="btn btn-danger">Oui, annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
