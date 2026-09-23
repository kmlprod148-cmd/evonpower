@extends('layouts.app')

@section('title', 'Nouveau Contrat Intégrateur')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-contract me-2"></i>
            Nouveau Contrat Intégrateur
        </h1>
        <a href="{{ route('admin.integrator-contracts.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Retour à la liste
        </a>
    </div>

    <form action="{{ route('admin.integrator-contracts.store') }}" method="POST" id="contractForm">
        @csrf
        
        <div class="row">
            <!-- Main Information -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Informations générales</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="integrator_id" class="form-label">Intégrateur <span class="text-danger">*</span></label>
                                <select class="form-select @error('integrator_id') is-invalid @enderror" 
                                        id="integrator_id" name="integrator_id" required>
                                    <option value="">Sélectionner un intégrateur</option>
                                    @foreach($integrators as $integrator)
                                        <option value="{{ $integrator->id }}" {{ old('integrator_id') == $integrator->id ? 'selected' : '' }}>
                                            {{ $integrator->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('integrator_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom du contrat <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" name="status" required>
                                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Actif</option>
                                    <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspendu</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="currency" class="form-label">Devise <span class="text-danger">*</span></label>
                                <select class="form-select @error('currency') is-invalid @enderror" 
                                        id="currency" name="currency" required>
                                    <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                    <option value="MAD" {{ old('currency') == 'MAD' ? 'selected' : '' }}>MAD (DH)</option>
                                    <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Fee -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="maintenance_fee_enabled" 
                                   name="maintenance_fee_enabled" {{ old('maintenance_fee_enabled') ? 'checked' : '' }}>
                            <label class="form-check-label" for="maintenance_fee_enabled">
                                <h6 class="m-0 font-weight-bold text-primary">Frais de maintenance (Frais maintenance)</h6>
                            </label>
                        </div>
                    </div>
                    <div class="card-body maintenance-fee-fields">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="maintenance_fee_amount" class="form-label">Montant</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="maintenance_fee_amount" 
                                           name="maintenance_fee_amount" step="0.01" min="0" 
                                           value="{{ old('maintenance_fee_amount', 0) }}">
                                    <span class="input-group-text">{{ old('currency', 'EUR') }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="maintenance_fee_period" class="form-label">Période</label>
                                <select class="form-select" id="maintenance_fee_period" name="maintenance_fee_period">
                                    <option value="monthly" {{ old('maintenance_fee_period') == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                    <option value="quarterly" {{ old('maintenance_fee_period') == 'quarterly' ? 'selected' : '' }}>Trimestriel</option>
                                    <option value="yearly" {{ old('maintenance_fee_period') == 'yearly' ? 'selected' : '' }}>Annuel</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="maintenance_fee_start_date" class="form-label">Date de début</label>
                                <input type="date" class="form-control" id="maintenance_fee_start_date" 
                                       name="maintenance_fee_start_date" value="{{ old('maintenance_fee_start_date') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terminal Fee -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terminal_fee_enabled" 
                                   name="terminal_fee_enabled" {{ old('terminal_fee_enabled') ? 'checked' : '' }}>
                            <label class="form-check-label" for="terminal_fee_enabled">
                                <h6 class="m-0 font-weight-bold text-primary">Frais par borne active (Frais par borne active)</h6>
                            </label>
                        </div>
                    </div>
                    <div class="card-body terminal-fee-fields">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="terminal_fee_amount" class="form-label">Montant par borne</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="terminal_fee_amount" 
                                           name="terminal_fee_amount" step="0.01" min="0" 
                                           value="{{ old('terminal_fee_amount', 0) }}">
                                    <span class="input-group-text">{{ old('currency', 'EUR') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="terminal_fee_period" class="form-label">Période</label>
                                <select class="form-select" id="terminal_fee_period" name="terminal_fee_period">
                                    <option value="monthly" {{ old('terminal_fee_period') == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                    <option value="quarterly" {{ old('terminal_fee_period') == 'quarterly' ? 'selected' : '' }}>Trimestriel</option>
                                    <option value="yearly" {{ old('terminal_fee_period') == 'yearly' ? 'selected' : '' }}>Annuel</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="terminal_fee_minimum" class="form-label">Minimum bornes</label>
                                <input type="number" class="form-control" id="terminal_fee_minimum" 
                                       name="terminal_fee_minimum" min="0" 
                                       value="{{ old('terminal_fee_minimum', 0) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="terminal_fee_free_count" class="form-label">Bornes gratuites</label>
                                <input type="number" class="form-control" id="terminal_fee_free_count" 
                                       name="terminal_fee_free_count" min="0" 
                                       value="{{ old('terminal_fee_free_count', 0) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction Commission -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="transaction_commission_enabled" 
                                   name="transaction_commission_enabled" {{ old('transaction_commission_enabled') ? 'checked' : '' }}>
                            <label class="form-check-label" for="transaction_commission_enabled">
                                <h6 class="m-0 font-weight-bold text-primary">Commission par transaction (Commission par transaction)</h6>
                            </label>
                        </div>
                    </div>
                    <div class="card-body commission-fields">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="transaction_commission_type" class="form-label">Type de commission</label>
                                <select class="form-select" id="transaction_commission_type" name="transaction_commission_type">
                                    <option value="percentage" {{ old('transaction_commission_type') == 'percentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                                    <option value="fixed" {{ old('transaction_commission_type') == 'fixed' ? 'selected' : '' }}>Montant fixe</option>
                                    <option value="combined" {{ old('transaction_commission_type') == 'combined' ? 'selected' : '' }}>Combiné</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="transaction_commission_percentage" class="form-label">Pourcentage</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="transaction_commission_percentage" 
                                           name="transaction_commission_percentage" step="0.01" min="0" max="100" 
                                           value="{{ old('transaction_commission_percentage', 0) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="transaction_commission_fixed_amount" class="form-label">Montant fixe</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="transaction_commission_fixed_amount" 
                                           name="transaction_commission_fixed_amount" step="0.01" min="0" 
                                           value="{{ old('transaction_commission_fixed_amount', 0) }}">
                                    <span class="input-group-text">{{ old('currency', 'EUR') }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="transaction_commission_min_amount" class="form-label">Montant minimum</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="transaction_commission_min_amount" 
                                           name="transaction_commission_min_amount" step="0.01" min="0" 
                                           value="{{ old('transaction_commission_min_amount', 0) }}">
                                    <span class="input-group-text">{{ old('currency', 'EUR') }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="transaction_commission_max_amount" class="form-label">Montant maximum</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="transaction_commission_max_amount" 
                                           name="transaction_commission_max_amount" step="0.01" min="0" 
                                           value="{{ old('transaction_commission_max_amount') }}">
                                    <span class="input-group-text">{{ old('currency', 'EUR') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Contract Dates -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Dates du contrat</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="contract_start_date" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="contract_start_date" 
                                   name="contract_start_date" value="{{ old('contract_start_date') }}">
                        </div>
                        <div class="mb-3">
                            <label for="contract_end_date" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="contract_end_date" 
                                   name="contract_end_date" value="{{ old('contract_end_date') }}">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="auto_renewal" 
                                   name="auto_renewal" {{ old('auto_renewal') ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_renewal">
                                Renouvellement automatique
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Notes</h6>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>
                        Créer le contrat
                    </button>
                    <a href="{{ route('admin.integrator-contracts.index') }}" class="btn btn-secondary">
                        Annuler
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle fields based on checkbox state
    const toggleFields = (checkboxId, fieldsSelector) => {
        const checkbox = document.getElementById(checkboxId);
        const fields = document.querySelector(fieldsSelector);
        
        if (checkbox && fields) {
            const updateState = () => {
                fields.style.display = checkbox.checked ? 'block' : 'none';
            };
            checkbox.addEventListener('change', updateState);
            updateState();
        }
    };

    toggleFields('maintenance_fee_enabled', '.maintenance-fee-fields');
    toggleFields('terminal_fee_enabled', '.terminal-fee-fields');
    toggleFields('transaction_commission_enabled', '.commission-fields');

    // Update currency symbol when currency changes
    const currencySelect = document.getElementById('currency');
    if (currencySelect) {
        currencySelect.addEventListener('change', function() {
            const currency = this.value;
            document.querySelectorAll('.input-group-text').forEach((span, index) => {
                if (index < 3) { // Only update first 3 currency-related inputs
                    const symbols = { EUR: '€', MAD: 'DH', USD: '$' };
                    span.textContent = symbols[currency] || currency;
                }
            });
        });
    }
});
</script>
@endpush
@endsection
