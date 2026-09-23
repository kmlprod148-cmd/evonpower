@extends('layouts.app')

@section('title', 'Modifier la Transaction #' . $transaction->id)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i> Modifier la Transaction #{{ $transaction->id }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('transactions.show', $transaction->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('transactions.update', $transaction->id) }}" method="POST" id="transactionForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- Informations générales -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-info-circle"></i> Informations Générales
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transaction_id">ID Transaction <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('transaction_id') is-invalid @enderror" 
                                           id="transaction_id" name="transaction_id" 
                                           value="{{ old('transaction_id', $transaction->transaction_id) }}" required>
                                    @error('transaction_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transaction_type">Type de Transaction <span class="text-danger">*</span></label>
                                    <select class="form-control @error('transaction_type') is-invalid @enderror" 
                                            id="transaction_type" name="transaction_type" required>
                                        <option value="">Sélectionner un type</option>
                                        <option value="client" {{ old('transaction_type', $transaction->transaction_type) == 'client' ? 'selected' : '' }}>Transaction Client</option>
                                        <option value="admin_integrator" {{ old('transaction_type', $transaction->transaction_type) == 'admin_integrator' ? 'selected' : '' }}>Admin → Intégrateur</option>
                                        <option value="integrator_operator" {{ old('transaction_type', $transaction->transaction_type) == 'integrator_operator' ? 'selected' : '' }}>Intégrateur → Opérateur</option>
                                    </select>
                                    @error('transaction_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="charging_point_id">Point de Charge <span class="text-danger">*</span></label>
                                    <select class="form-control @error('charging_point_id') is-invalid @enderror" 
                                            id="charging_point_id" name="charging_point_id" required>
                                        <option value="">Sélectionner un point de charge</option>
                                        @foreach($chargingPoints ?? [] as $chargingPoint)
                                            <option value="{{ $chargingPoint->id }}" {{ old('charging_point_id', $transaction->charging_point_id) == $chargingPoint->id ? 'selected' : '' }}>
                                                {{ $chargingPoint->name }} - {{ $chargingPoint->location ?? 'Localisation non définie' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('charging_point_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="user_id">Utilisateur</label>
                                    <select class="form-control @error('user_id') is-invalid @enderror" 
                                            id="user_id" name="user_id">
                                        <option value="">Sélectionner un utilisateur</option>
                                        @foreach($users ?? [] as $user)
                                            <option value="{{ $user->id }}" {{ old('user_id', $transaction->user_id) == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Montants -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-euro-sign"></i> Montants
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price_total">Montant Total <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('price_total') is-invalid @enderror" 
                                               id="price_total" name="price_total" 
                                               value="{{ old('price_total', $transaction->price_total) }}" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">EUR</span>
                                        </div>
                                    </div>
                                    @error('price_total')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Statut <span class="text-danger">*</span></label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="pending" {{ old('status', $transaction->status) == 'pending' ? 'selected' : '' }}>En attente</option>
                                        <option value="completed" {{ old('status', $transaction->status) == 'completed' ? 'selected' : '' }}>Terminée</option>
                                        <option value="cancelled" {{ old('status', $transaction->status) == 'cancelled' ? 'selected' : '' }}>Annulée</option>
                                        <option value="failed" {{ old('status', $transaction->status) == 'failed' ? 'selected' : '' }}>Échouée</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Description et notes -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-sticky-note"></i> Description et Notes
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3" 
                                              placeholder="Description de la transaction...">{{ old('description', $transaction->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="notes">Notes Internes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="2" 
                                              placeholder="Notes internes (non visibles par le client)...">{{ old('notes', $transaction->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="form-group text-right">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Mettre à jour
                                    </button>
                                    <a href="{{ route('transactions.show', $transaction->id) }}" class="btn btn-secondary btn-lg ml-2">
                                        <i class="fas fa-times"></i> Annuler
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
