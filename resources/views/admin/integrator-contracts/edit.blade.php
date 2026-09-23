@extends('layouts.app')

@section('title', 'Modifier Contrat ' . $contract->contract_number)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-contract me-2"></i>
            Modifier Contrat: {{ $contract->contract_number }}
        </h1>
        <a href="{{ route('admin.integrator-contracts.show', $contract) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Annuler
        </a>
    </div>

    <form action="{{ route('admin.integrator-contracts.update', $contract) }}" method="POST" id="contractForm">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Main Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Informations générales</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="integrator_id" class="form-label">Intégrateur</label>
                                <input type="text" class="form-control" value="{{ $contract->integrator->name ?? 'N/A' }}" readonly>
                                <input type="hidden" name="integrator_id" value="{{ $contract->integrator_id }}">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom du contrat <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $contract->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" name="status" required>
                                    <option value="draft" {{ old('status', $contract->status) == 'draft' ? 'selected' : '' }}>Brouillon</option>
                                    <option value="active" {{ old('status', $contract->status) == 'active' ? 'selected' : '' }}>Actif</option>
                                    <option value="suspended" {{ old('status', $contract->status) == 'suspended' ? 'selected' : '' }}>Suspendu</option>
                                    <option value="terminated" {{ old('status', $contract->status) == 'terminated' ? 'selected' : '' }}>Terminé</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="currency" class="form-label">Devise <span class="text-danger">*</span></label>
                                <select class="form-select @error('currency') is-invalid @enderror" 
                                        id="currency" name="currency" required>
                                    <option value="EUR" {{ old('currency', $contract->currency) == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                    <option value="MAD" {{ old('currency', $contract->currency) == 'MAD' ? 'selected' : '' }}>MAD (DH)</option>
                                    <option value="USD" {{ old('currency', $contract->currency) == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $contract->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Fee -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="maintenance_fee_enabled" 
                                   name="maintenance_fee_enabled" {{ old('maintenance_fee_enabled', $contract->maintenance_fee_enabled) ? 'checked' : '' }}>
                            <label class="form-check-label" for="maintenance_fee_enabled">
                                <h6 class="m-0 font-weight-bold text-primary">Frais de maintenance</h6>
                            </label>
                        </div>
                    </div>
                    <div class="card-body maintenance-fee-fields" style="{{ $contract->maintenance_fee_enabled ? '' : 'display:none;' }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="maintenance_fee_amount" class="form-label">Montant</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="maintenance_fee_amount" 
                                           name="maintenance_fee_amount" step="0.01" min="0" 
                                           value="{{ old('maintenance_fee_amount', $contract->maintenance_fee_amount) }}">
                                    <span class="input-group-text">{{ $contract->currency }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="maintenance_fee_period" class="form-label">Période</label>
                                <select class="form-select" id="maintenance_fee_period" name="maintenance_fee_period">
                                    <option value="monthly" {{ old('maintenance_fee_period', $contract->maintenance_fee_period) == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                    <option value="quarterly" {{ old('maintenance_fee_period', $contract->maintenance_fee_period) == 'quarterly' ? 'selected' : '' }}>Trimestriel</option>
                                    <option value="yearly" {{ old('maintenance_fee_period', $contract->maintenance_fee_period) == 'yearly' ? 'selected' : '' }}>Annuel</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="maintenance_fee_start_date" class="form-label">Date de début</label>
                                <input type="date" class="form-control" id="maintenance_fee_start_date" 
                                       name="maintenance_fee_start_date" value="{{ old('maintenance_fee_start_date', $contract->maintenance_fee_start_date?->format('Y-m-d')) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terminal Fee -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terminal_fee_enabled" 
                                   name="terminal_fee_enabled" {{ old('terminal_fee_enabled', $contract->terminal_fee_enabled) ? 'checked' : '' }}>
                            <label class="form-check-label" for="terminal_fee_enabled">
                                <h6 class="m-0 font-weight-bold text-primary">Frais par borne active</h6>
                            </label>
                        </div>
                    </div>
                    <div class="card-body terminal-fee-fields" style="{{ $contract->terminal_fee_enabled ? '' : 'display:none;' }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="terminal_fee_amount" class="form-label">Montant par borne</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="terminal_fee_amount" 
                                           name="terminal_fee_amount" step="0.01" min="0" 
                                           value="{{ old('terminal_fee_amount', $contract->terminal_fee_amount) }}">
                                    <span class="input-group-text">{{ $contract->currency }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="terminal_fee_period" class="form-label">Période</label>
                                <select class="form-select" id="terminal_fee_period" name="terminal_fee_period">
                                    <option value="monthly" {{ old('terminal_fee_period', $contract->terminal_fee_period) == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                    <option value="quarterly" {{ old('terminal_fee_period', $contract->terminal_fee_period) == 'quarterly' ? 'selected' : '' }}>Trimestriel</option>
                                    <option value="yearly" {{ old('terminal_fee_period', $contract->terminal_fee_period) == 'yearly' ? 'selected' : '' }}>Annuel</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="terminal_fee_minimum" class="form-label">Minimum bornes</label>
                                <input type="number" class="form-control" id="terminal_fee_minimum" 
                                       name="terminal_fee_minimum" min="0" 
                                       value="{{ old('terminal_fee_minimum', $contract->terminal_fee_minimum) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="terminal_fee_free_count" class="form-label">Bornes gratuites</label>
                                <input type="number" class="form-control" id="terminal_fee_free_count" 
                                       name="terminal_fee_free_count" min="0" 
                                       value="{{ old('terminal_fee_free_count', $contract->terminal_fee_free_count) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction Commission -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="transaction_commission_enabled" 
                                   name="transaction_commission_enabled" {{ old('transaction_commission_enabled', $contract->transaction_commission_enabled) ? 'checked' : '' }}>
                            <label class="form-check-label" for="transaction_commission_enabled">
                                <h6 class="m-0 font-weight-bold text-primary">Commission par transaction</h6>
                            </label>
                        </div>
                    </div>
                    <div class="card-body commission-fields" style="{{ $contract->transaction_commission_enabled ? '' : 'display:none;' }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="transaction_commission_type" class="form-label">Type de commission</label>
                                <select class="form-select" id="transaction_commission_type" name="transaction_commission_type">
                                    <option value="percentage" {{ old('transaction_commission_type', $contract->transaction_commission_type) == 'percentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                                    <option value="fixed" {{ old('transaction_commission_type', $contract->transaction_commission_type) == 'fixed' ? 'selected' : '' }}>Montant fixe</option>
                                    <option value="combined" {{ old('transaction_commission_type', $contract->transaction_commission_type) == 'combined' ? 'selected' : '' }}>Combiné</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="transaction_commission_percentage" class="form-label">Pourcentage</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="transaction_commission_percentage" 
                                           name="transaction_commission_percentage" step="0.01" min="0" max="100" 
                                           value="{{ old('transaction_commission_percentage', $contract->transaction_commission_percentage) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="transaction_commission_fixed_amount" class="form-label">Montant fixe</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="transaction_commission_fixed_amount" 
                                           name="transaction_commission_fixed_amount" step="0.01" min="0" 
                                           value="{{ old('transaction_commission_fixed_amount', $contract->transaction_commission_fixed_amount) }}">
                                    <span class="input-group-text">{{ $contract->currency }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="transaction_commission_min_amount" class="form-label">Montant minimum</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="transaction_commission_min_amount" 
                                           name="transaction_commission_min_amount" step="0.01" min="0" 
                                           value="{{ old('transaction_commission_min_amount', $contract->transaction_commission_min_amount) }}">
                                    <span class="input-group-text">{{ $contract->currency }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="transaction_commission_max_amount" class="form-label">Montant maximum</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="transaction_commission_max_amount" 
                                           name="transaction_commission_max_amount" step="0.01" min="0" 
                                           value="{{ old('transaction_commission_max_amount', $contract->transaction_commission_max_amount) }}">
                                    <span class="input-group-text">{{ $contract->currency }}</span>
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
                                   name="contract_start_date" value="{{ old('contract_start_date', $contract->contract_start_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="mb-3">
                            <label for="contract_end_date" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="contract_end_date" 
                                   name="contract_end_date" value="{{ old('contract_end_date', $contract->contract_end_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="auto_renewal" 
                                   name="auto_renewal" {{ old('auto_renewal', $contract->auto_renewal) ? 'checked' : '' }}>
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
                        <textarea class="form-control" id="notes" name="notes" rows="4">{{ old('notes', $contract->notes) }}</textarea>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleFields = (checkboxId, fieldsSelector) => {
        const checkbox = document.getElementById(checkboxId);
        const fields = document.querySelector(fieldsSelector);
        
        if (checkbox && fields) {
            checkbox.addEventListener('change', () => {
                fields.style.display = checkbox.checked ? 'block' : 'none';
            });
        }
    };

    toggleFields('maintenance_fee_enabled', '.maintenance-fee-fields');
    toggleFields('terminal_fee_enabled', '.terminal-fee-fields');
    toggleFields('transaction_commission_enabled', '.commission-fields');
});
</script>
@endpush
@endsection
