@extends('layouts.app')

@section('title', 'Nouveau Paiement en Espèces')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Enregistrer un Paiement en Espèces</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.cash-payments.store') }}" id="cashPaymentForm">
                        @csrf

                        <!-- Client Selection -->
                        <div class="mb-4">
                            <label class="form-label">Client <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       id="clientSearch" 
                                       placeholder="Rechercher un client (nom, email, téléphone)..."
                                       autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" id="searchClientBtn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="clientSearchResults" class="list-group mt-2" style="display: none;"></div>
                            
                            <input type="hidden" name="client_id" id="selectedClientId" required>
                            
                            @error('client_id')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                            
                            <div id="selectedClientInfo" class="mt-2" style="display: none;">
                                <div class="alert alert-info mb-0">
                                    <strong>Client sélectionné:</strong> <span id="selectedClientName"></span><br>
                                    <small>Email: <span id="selectedClientEmail"></span></small>
                                </div>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="mb-4">
                            <label class="form-label">Montant <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control @error('amount') is-invalid @enderror" 
                                       name="amount" 
                                       id="amount"
                                       step="0.01" 
                                       min="1" 
                                       max="10000"
                                       value="{{ old('amount') }}"
                                       required>
                                <select name="currency" class="form-select @error('currency') is-invalid @enderror" style="max-width: 120px;">
                                    <option value="MAD" {{ old('currency', 'MAD') == 'MAD' ? 'selected' : '' }}>MAD</option>
                                    <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                    <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                                </select>
                            </div>
                            @error('amount')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                            
                            <!-- Suggested Amounts -->
                            <div class="mt-2">
                                <small class="text-muted">Montants suggérés:</small>
                                <div class="btn-group btn-group-sm mt-1">
                                    @foreach($suggestedAmounts as $suggested)
                                    <button type="button" 
                                            class="btn btn-outline-secondary suggested-amount"
                                            data-amount="{{ $suggested }}">
                                        {{ $suggested }} MAD
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="form-label">Description (optionnel)</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      name="description" 
                                      rows="2"
                                      placeholder="Notez tout contexte ou motif du paiement...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Validation Warning -->
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Attention:</strong> Ce paiement sera immédiatement crédité sur le wallet du client.
                            Assurez-vous d'avoir bien reçu le montant en espèces.
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.cash-payments.index') }}" class="btn btn-secondary">
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-success" id="submitBtn">
                                <i class="fas fa-check"></i> Enregistrer le Paiement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const clientSearch = document.getElementById('clientSearch');
    const searchClientBtn = document.getElementById('searchClientBtn');
    const clientSearchResults = document.getElementById('clientSearchResults');
    const selectedClientId = document.getElementById('selectedClientId');
    const selectedClientInfo = document.getElementById('selectedClientInfo');
    const selectedClientName = document.getElementById('selectedClientName');
    const selectedClientEmail = document.getElementById('selectedClientEmail');
    const amountInput = document.getElementById('amount');
    const suggestedAmountBtns = document.querySelectorAll('.suggested-amount');
    const submitBtn = document.getElementById('submitBtn');

    // Search client
    let searchTimeout;
    async function searchClients(query) {
        if (query.length < 2) {
            clientSearchResults.style.display = 'none';
            return;
        }

        try {
            const response = await fetch(`/api/admin/cash-payments/search-clients?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            clientSearchResults.innerHTML = '';
            
            if (data.clients && data.clients.length > 0) {
                data.clients.forEach(client => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action';
                    item.innerHTML = `
                        <strong>${client.name}</strong><br>
                        <small class="text-muted">${client.email} - ${client.phone || 'N/A'}</small>
                    `;
                    item.addEventListener('click', () => selectClient(client));
                    clientSearchResults.appendChild(item);
                });
                clientSearchResults.style.display = 'block';
            } else {
                clientSearchResults.innerHTML = '<div class="list-group-item">Aucun client trouvé</div>';
                clientSearchResults.style.display = 'block';
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    clientSearch.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => searchClients(e.target.value), 300);
    });

    searchClientBtn.addEventListener('click', () => {
        searchClients(clientSearch.value);
    });

    function selectClient(client) {
        selectedClientId.value = client.id;
        clientSearch.value = client.name;
        selectedClientName.textContent = client.name;
        selectedClientEmail.textContent = client.email;
        selectedClientInfo.style.display = 'block';
        clientSearchResults.style.display = 'none';
    }

    // Suggested amounts
    suggestedAmountBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            amountInput.value = btn.dataset.amount;
        });
    });

    // Form validation
    document.getElementById('cashPaymentForm').addEventListener('submit', function(e) {
        if (!selectedClientId.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un client');
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Traitement...';
    });
});
</script>
@endpush
@endsection
