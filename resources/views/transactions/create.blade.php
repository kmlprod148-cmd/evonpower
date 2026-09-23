@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus"></i> Créer une Nouvelle Transaction
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('transactions.store') }}" method="POST" id="transactionForm">
                        @csrf
                        
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
                                           value="{{ old('transaction_id', 'TXN-' . strtoupper(uniqid())) }}" required>
                                    @error('transaction_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Identifiant unique de la transaction</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transaction_type">Type de Transaction <span class="text-danger">*</span></label>
                                    <select class="form-control @error('transaction_type') is-invalid @enderror" 
                                            id="transaction_type" name="transaction_type" required>
                                        <option value="">Sélectionner un type</option>
                                        <option value="client" {{ old('transaction_type') == 'client' ? 'selected' : '' }}>Transaction Client</option>
                                        <option value="admin_integrator" {{ old('transaction_type') == 'admin_integrator' ? 'selected' : '' }}>Admin → Intégrateur</option>
                                        <option value="integrator_operator" {{ old('transaction_type') == 'integrator_operator' ? 'selected' : '' }}>Intégrateur → Opérateur</option>
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
                                            <option value="{{ $chargingPoint->id }}" {{ old('charging_point_id') == $chargingPoint->id ? 'selected' : '' }}>
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
                                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
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

                        <!-- Montants et tarification -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-euro-sign"></i> Montants et Tarification
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="price_total">Montant Total <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('price_total') is-invalid @enderror" 
                                               id="price_total" name="price_total" 
                                               value="{{ old('price_total') }}" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">EUR</span>
                                        </div>
                                    </div>
                                    @error('price_total')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="price_energy">Prix Énergie</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('price_energy') is-invalid @enderror" 
                                               id="price_energy" name="price_energy" 
                                               value="{{ old('price_energy') }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text">EUR</span>
                                        </div>
                                    </div>
                                    @error('price_energy')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="price_time">Prix Temps</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('price_time') is-invalid @enderror" 
                                               id="price_time" name="price_time" 
                                               value="{{ old('price_time') }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text">EUR</span>
                                        </div>
                                    </div>
                                    @error('price_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="price_service">Prix Service</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('price_service') is-invalid @enderror" 
                                               id="price_service" name="price_service" 
                                               value="{{ old('price_service') }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text">EUR</span>
                                        </div>
                                    </div>
                                    @error('price_service')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="price_tax">Taxes</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('price_tax') is-invalid @enderror" 
                                               id="price_tax" name="price_tax" 
                                               value="{{ old('price_tax') }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text">EUR</span>
                                        </div>
                                    </div>
                                    @error('price_tax')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="activation_fee">Frais d'Activation</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('activation_fee') is-invalid @enderror" 
                                               id="activation_fee" name="activation_fee" 
                                               value="{{ old('activation_fee') }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text">EUR</span>
                                        </div>
                                    </div>
                                    @error('activation_fee')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="amount">Montant (Legacy)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" 
                                               id="amount" name="amount" 
                                               value="{{ old('amount') }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text">EUR</span>
                                        </div>
                                    </div>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="currency">Devise</label>
                                    <select class="form-control @error('currency') is-invalid @enderror" 
                                            id="currency" name="currency">
                                        <option value="EUR" {{ old('currency', 'EUR') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                        <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                        <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                                    </select>
                                    @error('currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Business Profile et Commissions -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-chart-pie"></i> Business Profile et Commissions
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="business_profile_id">Business Profile</label>
                                    <select class="form-control @error('business_profile_id') is-invalid @enderror" 
                                            id="business_profile_id" name="business_profile_id">
                                        <option value="">Sélectionner un Business Profile</option>
                                        @foreach($businessProfiles ?? [] as $profile)
                                            <option value="{{ $profile->id }}" {{ old('business_profile_id') == $profile->id ? 'selected' : '' }}>
                                                {{ $profile->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('business_profile_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Statut</label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            id="status" name="status">
                                        <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>En attente</option>
                                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Terminée</option>
                                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Annulée</option>
                                        <option value="failed" {{ old('status') == 'failed' ? 'selected' : '' }}>Échouée</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Commissions manuelles (optionnel) -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="admin_commission">Commission Admin</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('admin_commission') is-invalid @enderror" 
                                               id="admin_commission" name="admin_commission" 
                                               value="{{ old('admin_commission') }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text">EUR</span>
                                        </div>
                                    </div>
                                    @error('admin_commission')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="integrator_commission">Commission Intégrateur</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('integrator_commission') is-invalid @enderror" 
                                               id="integrator_commission" name="integrator_commission" 
                                               value="{{ old('integrator_commission') }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text">EUR</span>
                                        </div>
                                    </div>
                                    @error('integrator_commission')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="partner_commission">Commission Partenaire</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('partner_commission') is-invalid @enderror" 
                                               id="partner_commission" name="partner_commission" 
                                               value="{{ old('partner_commission') }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text">EUR</span>
                                        </div>
                                    </div>
                                    @error('partner_commission')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Informations de session -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-clock"></i> Informations de Session
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_timestamp">Début de Session</label>
                                    <input type="datetime-local" class="form-control @error('start_timestamp') is-invalid @enderror" 
                                           id="start_timestamp" name="start_timestamp" 
                                           value="{{ old('start_timestamp') }}">
                                    @error('start_timestamp')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="stop_timestamp">Fin de Session</label>
                                    <input type="datetime-local" class="form-control @error('stop_timestamp') is-invalid @enderror" 
                                           id="stop_timestamp" name="stop_timestamp" 
                                           value="{{ old('stop_timestamp') }}">
                                    @error('stop_timestamp')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="meter_start">Compteur Début</label>
                                    <input type="number" step="0.01" class="form-control @error('meter_start') is-invalid @enderror" 
                                           id="meter_start" name="meter_start" 
                                           value="{{ old('meter_start') }}">
                                    @error('meter_start')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="meter_stop">Compteur Fin</label>
                                    <input type="number" step="0.01" class="form-control @error('meter_stop') is-invalid @enderror" 
                                           id="meter_stop" name="meter_stop" 
                                           value="{{ old('meter_stop') }}">
                                    @error('meter_stop')
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
                                              placeholder="Description de la transaction...">{{ old('description') }}</textarea>
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
                                              placeholder="Notes internes (non visibles par le client)...">{{ old('notes') }}</textarea>
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
                                        <i class="fas fa-save"></i> Créer la Transaction
                                    </button>
                                    <a href="{{ route('transactions.index') }}" class="btn btn-secondary btn-lg ml-2">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calcul du montant total
    const priceEnergy = document.getElementById('price_energy');
    const priceTime = document.getElementById('price_time');
    const priceService = document.getElementById('price_service');
    const priceTax = document.getElementById('price_tax');
    const activationFee = document.getElementById('activation_fee');
    const priceTotal = document.getElementById('price_total');

    function calculateTotal() {
        const energy = parseFloat(priceEnergy.value) || 0;
        const time = parseFloat(priceTime.value) || 0;
        const service = parseFloat(priceService.value) || 0;
        const tax = parseFloat(priceTax.value) || 0;
        const activation = parseFloat(activationFee.value) || 0;
        
        const total = energy + time + service + tax + activation;
        priceTotal.value = total.toFixed(2);
    }

    [priceEnergy, priceTime, priceService, priceTax, activationFee].forEach(input => {
        input.addEventListener('input', calculateTotal);
    });

    // Génération automatique de l'ID de transaction
    const transactionIdInput = document.getElementById('transaction_id');
    if (!transactionIdInput.value) {
        transactionIdInput.value = 'TXN-' + Date.now();
    }
});
</script>
@endsection