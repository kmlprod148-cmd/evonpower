@extends('layouts.app')

@section('title', 'Détails du Paiement')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Back Link -->
            <div class="mb-3">
                <a href="{{ route('admin.cash-payments.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </div>

            <!-- Payment Details Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Paiement en Espèces</h5>
                    @switch($cashPayment->status)
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
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Informations du Paiement</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Référence:</strong></td>
                                    <td><span class="badge bg-secondary">{{ $cashPayment->reference }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Montant:</strong></td>
                                    <td>{{ number_format($cashPayment->amount, 2) }} {{ $cashPayment->currency }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Méthode:</strong></td>
                                    <td>Espèces Client</td>
                                </tr>
                                <tr>
                                    <td><strong>Date:</strong></td>
                                    <td>{{ $cashPayment->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                @if($cashPayment->processed_at)
                                <tr>
                                    <td><strong>Traité le:</strong></td>
                                    <td>{{ $cashPayment->processed_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Informations Client</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Client:</strong></td>
                                    <td>
                                        <a href="{{ route('admin.users.show', $cashPayment->user_id) }}">
                                            {{ $cashPayment->user->name ?? 'N/A' }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $cashPayment->user->email ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Téléphone:</strong></td>
                                    <td>{{ $cashPayment->user->phone ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Wallet ID:</strong></td>
                                    <td>{{ $cashPayment->wallet_id }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($cashPayment->description)
                    <div class="mt-3">
                        <h6 class="text-muted">Description</h6>
                        <p class="mb-0">{{ $cashPayment->description }}</p>
                    </div>
                    @endif

                    @if($cashPayment->processor)
                    <div class="mt-3">
                        <h6 class="text-muted">Traité par</h6>
                        <p class="mb-0">{{ $cashPayment }}</p>
                   ->processor->name </div>
                    @endif
                </div>
            </div>

            <!-- Receipt Card -->
            @if(isset($receipt))
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Reçu</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h4>REÇU DE PAIEMENT</h4>
                        <p class="text-muted mb-0">N° {{ $receipt['receipt_number'] }}</p>
                        <small class="text-muted">Date: {{ $receipt['date'] }}</small>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6 mx-auto">
                            <table class="table table-sm">
                                <tr>
                                    <td>Client:</td>
                                    <td class="text-end"><strong>{{ $receipt['client']['name'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Email:</td>
                                    <td class="text-end">{{ $receipt['client']['email'] }}</td>
                                </tr>
                                @if($receipt['client']['phone'])
                                <tr>
                                    <td>Téléphone:</td>
                                    <td class="text-end">{{ $receipt['client']['phone'] }}</td>
                                </tr>
                                @endif
                            </table>
                            
                            <table class="table table-sm table-bordered">
                                <tr class="table-success">
                                    <td><strong>Montant Payé</strong></td>
                                    <td class="text-end">
                                        <strong>{{ $receipt['payment']['amount'] }} {{ $receipt['payment']['currency'] }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Crédits ajoutés</td>
                                    <td class="text-end">{{ $receipt['credits']['amount'] }} {{ $receipt['credits']['currency'] }}</td>
                                </tr>
                            </table>

                            <p class="text-center text-muted small mb-0">
                                Mode de paiement: {{ $receipt['payment']['method'] }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Cancel Button (only for completed payments) -->
            @if($cashPayment->status === 'completed')
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Annuler le Paiement</h5>
                </div>
                <div class="card-body">
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Attention:</strong> L'annulation d'un paiement déduira les crédits du wallet du client.
                        Cette action est irréversible.
                    </p>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        <i class="fas fa-times"></i> Annuler ce Paiement
                    </button>
                </div>
            </div>

            <!-- Cancel Modal -->
            <div class="modal fade" id="cancelModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirmer l'annulation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="{{ route('admin.cash-payments.cancel', $cashPayment->id) }}">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Motif d'annulation <span class="text-danger">*</span></label>
                                    <textarea class="form-control" 
                                              name="reason" 
                                              rows="3" 
                                              required
                                              placeholder="Veuillez fournir une raison pour cette annulation..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                <button type="submit" class="btn btn-danger">Confirmer l'annulation</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
