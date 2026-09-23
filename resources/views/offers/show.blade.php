@extends('layouts.app')

@section('title', 'Offre de Recharge - ' . $chargingPoint->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- En-tête de la borne -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $chargingPoint->name }}</h1>
                <p class="text-gray-600 mt-2">{{ $chargingPoint->address }}, {{ $chargingPoint->city }}</p>
                <div class="flex items-center mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($chargingPoint->status === 'online') bg-green-100 text-green-800
                        @elseif($chargingPoint->status === 'offline') bg-red-100 text-red-800
                        @else bg-yellow-100 text-yellow-800 @endif">
                        {{ ucfirst($chargingPoint->status) }}
                    </span>
                    <span class="ml-2 text-sm text-gray-500">
                        Puissance: {{ $chargingPoint->power_output }} kW
                    </span>
                </div>
            </div>
            <div class="text-right">
                @if($chargingPointInfo['pricing_plan'])
                    <p class="text-sm text-gray-500">Plan tarifaire</p>
                    <p class="text-lg font-semibold">{{ $chargingPointInfo['pricing_plan']['name'] }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Informations sur les limites de réservation -->
    @if($limitInfo['has_limit'])
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-blue-800">
                        Limite de réservations: {{ $limitInfo['current'] }}/{{ $limitInfo['max'] }}
                    </p>
                    <p class="text-sm text-blue-600">
                        Places disponibles: {{ $limitInfo['available'] }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Formulaire de réservation -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Réserver une session</h2>
            
            <form id="reservationForm" class="space-y-6">
                @csrf
                
                <!-- Mode de facturation -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Mode de facturation</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="mode" value="per_kw" class="sr-only" checked>
                            <div class="border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-500 transition-colors
                                peer-checked:border-blue-500 peer-checked:bg-blue-50" id="mode-per-kw">
                                <div class="text-lg font-semibold">Par kWh</div>
                                <div class="text-sm text-gray-600">Facturation à l'énergie</div>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="mode" value="per_min" class="sr-only">
                            <div class="border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-500 transition-colors
                                peer-checked:border-blue-500 peer-checked:bg-blue-50" id="mode-per-min">
                                <div class="text-lg font-semibold">Par minute</div>
                                <div class="text-sm text-gray-600">Facturation au temps</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Valeur -->
                <div>
                    <label for="value" class="block text-sm font-medium text-gray-700 mb-2">
                        <span id="value-label">Quantité d'énergie (kWh)</span>
                    </label>
                    <div class="relative">
                        <input type="number" 
                               id="value" 
                               name="value" 
                               min="0.1" 
                               step="0.1" 
                               value="10"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Entrez la quantité">
                        <span id="value-unit" class="absolute right-3 top-3 text-gray-500">kWh</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        <span id="value-help">Quantité d'énergie souhaitée</span>
                    </p>
                </div>

                <!-- Heure de début -->
                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">
                        Heure de début
                    </label>
                    <input type="time" 
                           id="start_time" 
                           name="start_time" 
                           value="{{ now()->addHour()->format('H:i') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Informations client (optionnelles) -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations client (optionnelles)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">Nom</label>
                            <input type="text" 
                                   id="customer_name" 
                                   name="customer_name"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" 
                                   id="customer_email" 
                                   name="customer_email"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                        <input type="tel" 
                               id="customer_phone" 
                               name="customer_phone"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Bouton de réservation -->
                <div class="pt-6">
                    <button type="submit" 
                            id="reserveButton"
                            class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                            @if(!$canReserve) disabled @endif>
                        @if($canReserve)
                            Réserver maintenant
                        @else
                            Réservation non disponible
                        @endif
                    </button>
                </div>
            </form>
        </div>

        <!-- Estimation du coût -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Estimation du coût</h2>
            
            <!-- Affichage de l'estimation -->
            <div id="estimation-display" class="space-y-4">
                <div class="text-center py-8">
                    <div class="animate-pulse">
                        <div class="h-4 bg-gray-200 rounded w-3/4 mx-auto mb-2"></div>
                        <div class="h-4 bg-gray-200 rounded w-1/2 mx-auto"></div>
                    </div>
                    <p class="text-gray-500 mt-4">Modifiez les paramètres pour voir l'estimation</p>
                </div>
            </div>

            <!-- Détails du plan tarifaire -->
            @if($chargingPointInfo['pricing_plan'])
                <div class="border-t pt-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Détails du plan tarifaire</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Prix par kWh:</span>
                            <span class="font-semibold">{{ number_format($chargingPointInfo['pricing_plan']['price_per_kwh'], 2) }} {{ $chargingPointInfo['pricing_plan']['currency'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Prix par minute:</span>
                            <span class="font-semibold">{{ number_format($chargingPointInfo['pricing_plan']['price_per_minute'], 2) }} {{ $chargingPointInfo['pricing_plan']['currency'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Frais d'activation:</span>
                            <span class="font-semibold">{{ number_format($chargingPointInfo['pricing_plan']['activation_fee'], 2) }} {{ $chargingPointInfo['pricing_plan']['currency'] }}</span>
                        </div>
                        @if($chargingPointInfo['pricing_plan']['max_duration'])
                            <div class="flex justify-between">
                                <span class="text-gray-600">Durée max:</span>
                                <span class="font-semibold">{{ $chargingPointInfo['pricing_plan']['max_duration'] }} min</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Frais du profil d'entreprise -->
            @if($chargingPointInfo['business_profile'])
                <div class="border-t pt-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Frais supplémentaires</h3>
                    <div class="space-y-3">
                        @if($chargingPointInfo['business_profile']['recharge_fee'] > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Frais de recharge:</span>
                                <span class="font-semibold">{{ number_format($chargingPointInfo['business_profile']['recharge_fee'], 2) }} {{ $chargingPointInfo['pricing_plan']['currency'] ?? 'EUR' }}</span>
                            </div>
                        @endif
                        @if($chargingPointInfo['business_profile']['other_fees'] > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Autres frais:</span>
                                <span class="font-semibold">{{ number_format($chargingPointInfo['business_profile']['other_fees'], 2) }} {{ $chargingPointInfo['pricing_plan']['currency'] ?? 'EUR' }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Messages d'erreur -->
<div id="error-messages" class="fixed top-4 right-4 z-50 space-y-2"></div>

<!-- Messages de succès -->
<div id="success-messages" class="fixed top-4 right-4 z-50 space-y-2"></div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reservationForm');
    const modeInputs = document.querySelectorAll('input[name="mode"]');
    const valueInput = document.getElementById('value');
    const valueLabel = document.getElementById('value-label');
    const valueUnit = document.getElementById('value-unit');
    const valueHelp = document.getElementById('value-help');
    const estimationDisplay = document.getElementById('estimation-display');
    const reserveButton = document.getElementById('reserveButton');
    const errorMessages = document.getElementById('error-messages');
    const successMessages = document.getElementById('success-messages');

    let currentEstimation = null;
    let canReserve = {{ $canReserve ? 'true' : 'false' }};

    // Gestion du changement de mode
    modeInputs.forEach(input => {
        input.addEventListener('change', function() {
            updateModeDisplay();
            calculateEstimation();
        });
    });

    // Gestion du changement de valeur
    valueInput.addEventListener('input', function() {
        calculateEstimation();
    });

    // Mise à jour de l'affichage selon le mode
    function updateModeDisplay() {
        const selectedMode = document.querySelector('input[name="mode"]:checked').value;
        
        if (selectedMode === 'per_kw') {
            valueLabel.textContent = 'Quantité d\'énergie (kWh)';
            valueUnit.textContent = 'kWh';
            valueHelp.textContent = 'Quantité d\'énergie souhaitée';
            valueInput.min = '0.1';
            valueInput.step = '0.1';
            valueInput.value = '10';
        } else {
            valueLabel.textContent = 'Durée (minutes)';
            valueUnit.textContent = 'min';
            valueHelp.textContent = 'Durée de la session en minutes';
            valueInput.min = '1';
            valueInput.step = '1';
            valueInput.value = '60';
        }
    }

    // Calcul de l'estimation
    function calculateEstimation() {
        const mode = document.querySelector('input[name="mode"]:checked').value;
        const value = parseFloat(valueInput.value);

        if (!value || value <= 0) {
            estimationDisplay.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-gray-500">Entrez une valeur pour voir l'estimation</p>
                </div>
            `;
            return;
        }

        // Afficher le loading
        estimationDisplay.innerHTML = `
            <div class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="text-gray-500 mt-2">Calcul en cours...</p>
            </div>
        `;

        // Appel AJAX
        fetch(`/offers/{{ $chargingPoint->id }}/estimate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ mode, value })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentEstimation = data.estimation;
                canReserve = data.canReserve;
                updateEstimationDisplay(data.estimation, data.limitInfo);
                updateReserveButton();
            } else {
                showError(data.message || 'Erreur lors du calcul');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Erreur de connexion');
        });
    }

    // Mise à jour de l'affichage de l'estimation
    function updateEstimationDisplay(estimation, limitInfo) {
        estimationDisplay.innerHTML = `
            <div class="space-y-4">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600">
                        ${estimation.total.toFixed(2)} ${estimation.currency}
                    </div>
                    <p class="text-gray-600">Coût total estimé</p>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3">Détail du coût</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Coût de base:</span>
                            <span>${estimation.base.toFixed(2)} ${estimation.currency}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Frais supplémentaires:</span>
                            <span>${estimation.fees.toFixed(2)} ${estimation.currency}</span>
                        </div>
                        <div class="border-t pt-2 flex justify-between font-semibold">
                            <span>Total:</span>
                            <span>${estimation.total.toFixed(2)} ${estimation.currency}</span>
                        </div>
                    </div>
                </div>

                ${limitInfo.has_limit ? `
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-blue-800">
                                Réservations: ${limitInfo.current}/${limitInfo.max} (${limitInfo.available} disponibles)
                            </span>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    // Mise à jour du bouton de réservation
    function updateReserveButton() {
        if (canReserve && currentEstimation) {
            reserveButton.disabled = false;
            reserveButton.textContent = 'Réserver maintenant';
            reserveButton.className = 'w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors';
        } else {
            reserveButton.disabled = true;
            reserveButton.textContent = 'Réservation non disponible';
            reserveButton.className = 'w-full bg-gray-400 text-white py-3 px-6 rounded-lg font-semibold cursor-not-allowed';
        }
    }

    // Soumission du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!canReserve || !currentEstimation) {
            showError('Impossible de réserver à ce moment');
            return;
        }

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Afficher le loading sur le bouton
        reserveButton.disabled = true;
        reserveButton.innerHTML = `
            <div class="flex items-center justify-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                Traitement...
            </div>
        `;

        // Appel AJAX pour créer la réservation
        fetch(`/public/charging-points/{{ $chargingPoint->id }}/offer/store-reservation`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('Réservation créée avec succès !');
                // Rediriger vers la page de confirmation
                setTimeout(() => {
                    window.location.href = `/reservations/thank-you/${data.reservation_id}`;
                }, 2000);
            } else {
                showError(data.message || 'Erreur lors de la création de la réservation');
                reserveButton.disabled = false;
                reserveButton.textContent = 'Réserver maintenant';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Erreur de connexion');
            reserveButton.disabled = false;
            reserveButton.textContent = 'Réserver maintenant';
        });
    });

    // Fonctions d'affichage des messages
    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative';
        errorDiv.innerHTML = `
            <span class="block sm:inline">${message}</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.remove()">
                <svg class="fill-current h-6 w-6 text-red-500" role="button" viewBox="0 0 20 20">
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </span>
        `;
        errorMessages.appendChild(errorDiv);
        setTimeout(() => errorDiv.remove(), 5000);
    }

    function showSuccess(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative';
        successDiv.innerHTML = `
            <span class="block sm:inline">${message}</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.remove()">
                <svg class="fill-current h-6 w-6 text-green-500" role="button" viewBox="0 0 20 20">
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </span>
        `;
        successMessages.appendChild(successDiv);
        setTimeout(() => successDiv.remove(), 5000);
    }

    // Initialisation
    updateModeDisplay();
    calculateEstimation();
});
</script>
@endsection