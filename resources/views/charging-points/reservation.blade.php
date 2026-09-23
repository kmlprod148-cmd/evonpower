@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-emerald-50/30">
    <!-- Header -->
    <div class="bg-white/80 backdrop-blur-sm border-b border-gray-200/60 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-6">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('charging-points.index') }}" 
                       class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50 text-gray-500 hover:text-emerald-600 transition-all duration-200 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <p class="text-sm text-gray-500">Réservation</p>
                        <h1 class="text-2xl font-bold text-gray-900">Réserver un point de charge</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Détails de la réservation</h2>
                            <p class="text-sm text-gray-500">Remplissez les informations pour réserver un point de charge</p>
                        </div>
                    </div>

                    <form id="reservationForm" class="space-y-6">
                        @csrf
                        
                        <!-- Point de charge sélectionné -->
                        <div class="bg-gradient-to-r from-emerald-50 to-emerald-100 rounded-xl p-4 border border-emerald-200">
                            <label class="block text-sm font-medium text-emerald-800 mb-2">Point de charge sélectionné</label>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-emerald-900" id="selectedChargingPointName">Sélectionnez un point de charge</p>
                                    <p class="text-sm text-emerald-700" id="selectedChargingPointLocation">Aucun point sélectionné</p>
                                </div>
                                <button type="button" onclick="openChargingPointSelector()" 
                                        class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-medium transition-colors">
                                    Changer
                                </button>
                            </div>
                            <input type="hidden" id="selectedChargingPointId" name="charging_point_id">
                        </div>

                        <!-- Plan tarifaire -->
                        <div>
                            <label for="pricing_plan_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Plan tarifaire <span class="text-red-500">*</span>
                            </label>
                            <select id="pricing_plan_id" name="pricing_plan_id" required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                                <option value="">Sélectionner un plan tarifaire</option>
                                @if(isset($pricingPlans) && $pricingPlans->count() > 0)
                                    @foreach($pricingPlans as $plan)
                                        <option value="{{ $plan->id }}" 
                                                data-price-per-minute="{{ $plan->price_per_minute ?? 0 }}"
                                                data-price-per-kwh="{{ $plan->price_per_kwh ?? 0 }}"
                                                data-activation-fee="{{ $plan->activation_fee ?? 0 }}"
                                                data-rate-type="{{ $plan->rate_type ?? 'fixed' }}"
                                                data-fixed-price="{{ $plan->fixed_price ?? $plan->base_rate ?? 0 }}"
                                                data-currency="{{ $plan->currency ?? 'EUR' }}">
                                            {{ $plan->name }} - {{ $plan->currency === 'EUR' ? '€' : $plan->currency }}
                                            @if($plan->rate_type === 'fixed')
                                                {{ number_format($plan->fixed_price ?? $plan->base_rate ?? 0, 2) }}
                                            @else
                                                ({{ $plan->price_per_minute ?? 0 }}/min, {{ $plan->price_per_kwh ?? 0 }}/kWh)
                                            @endif
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <!-- Type de réservation -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type de réservation <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="relative">
                                    <input type="radio" name="reservation_type" value="minute" class="sr-only peer" checked>
                                    <div class="p-4 border-2 border-gray-200 rounded-xl cursor-pointer peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-all">
                                        <div class="text-center">
                                            <svg class="w-8 h-8 mx-auto mb-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <p class="font-semibold text-gray-900">Par durée</p>
                                            <p class="text-sm text-gray-500">Minutes</p>
                                        </div>
                                    </div>
                                </label>
                                <label class="relative">
                                    <input type="radio" name="reservation_type" value="kwh" class="sr-only peer">
                                    <div class="p-4 border-2 border-gray-200 rounded-xl cursor-pointer peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-all">
                                        <div class="text-center">
                                            <svg class="w-8 h-8 mx-auto mb-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            <p class="font-semibold text-gray-900">Par énergie</p>
                                            <p class="text-sm text-gray-500">kWh</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Valeur de réservation -->
                        <div>
                            <label for="reservation_value" class="block text-sm font-medium text-gray-700 mb-2">
                                <span id="valueLabel">Durée (minutes)</span> <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" id="reservation_value" name="reservation_value" required min="0.01" step="0.01"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors pr-16"
                                       placeholder="Entrez la valeur">
                                <span class="absolute right-3 top-3 text-gray-500" id="valueUnit">min</span>
                            </div>
                        </div>

                        <!-- Estimation de prix -->
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                            <div class="flex items-center mb-3">
                                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                <h3 class="text-lg font-semibold text-blue-900">Estimation du prix</h3>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-blue-700">Coût estimé:</span>
                                    <span class="text-lg font-bold text-blue-900" id="estimatedCost">0.00 €</span>
                                </div>
                                <div class="flex justify-between items-center text-sm text-blue-600">
                                    <span>Frais d'activation:</span>
                                    <span id="activationFee">0.00 €</span>
                                </div>
                                <div class="flex justify-between items-center text-sm text-blue-600">
                                    <span>Coût par <span id="costUnit">minute</span>:</span>
                                    <span id="unitCost">0.00 €</span>
                                </div>
                            </div>
                        </div>

                        <!-- ID Tag -->
                        <div>
                            <label for="id_tag" class="block text-sm font-medium text-gray-700 mb-2">
                                ID Tag <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="id_tag" name="id_tag" required maxlength="20"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                   placeholder="Entrez votre ID Tag">
                        </div>

                        <!-- Informations importantes -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h3 class="text-lg font-semibold text-blue-900 mb-2">Démarrage immédiat</h3>
                                    <p class="text-sm text-blue-700">
                                        Votre réservation commencera immédiatement après confirmation du paiement. 
                                        Le point de charge sera réservé pour la durée sélectionnée à partir du moment où vous commencez la recharge.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Type de paiement -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type de paiement <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-3 gap-4">
                                <label class="relative">
                                    <input type="radio" name="payment_type" value="offline" class="sr-only peer" checked>
                                    <div class="p-4 border-2 border-gray-200 rounded-xl cursor-pointer peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-all">
                                        <div class="text-center">
                                            <svg class="w-8 h-8 mx-auto mb-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            <p class="font-semibold text-gray-900">Sur place</p>
                                            <p class="text-sm text-gray-500">Paiement à la borne</p>
                                        </div>
                                    </div>
                                </label>
                                <label class="relative">
                                    <input type="radio" name="payment_type" value="cmi" class="sr-only peer">
                                    <div class="p-4 border-2 border-gray-200 rounded-xl cursor-pointer peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-all">
                                        <div class="text-center">
                                            <svg class="w-8 h-8 mx-auto mb-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                            </svg>
                                            <p class="font-semibold text-gray-900">CMI</p>
                                            <p class="text-sm text-gray-500">Carte bancaire</p>
                                        </div>
                                    </div>
                                </label>
                                <label class="relative">
                                    <input type="radio" name="payment_type" value="stripe" class="sr-only peer">
                                    <div class="p-4 border-2 border-gray-200 rounded-xl cursor-pointer peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-all">
                                        <div class="text-center">
                                            <svg class="w-8 h-8 mx-auto mb-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                            </svg>
                                            <p class="font-semibold text-gray-900">Stripe</p>
                                            <p class="text-sm text-gray-500">Carte internationale</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Notes (optionnel)
                            </label>
                            <textarea id="notes" name="notes" rows="3"
                                      class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                      placeholder="Ajoutez des notes pour votre réservation..."></textarea>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                            <button type="button" onclick="window.history.back()"
                                    class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition-colors">
                                Annuler
                            </button>
                            <button type="submit" id="submitBtn"
                                    class="px-8 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-medium transition-colors flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Créer la réservation
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Informations de réservation -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations</h3>
                    <div class="space-y-3">
                        <div class="flex items-center text-sm">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-gray-600">Réservation valide 24h</span>
                        </div>
                        <div class="flex items-center text-sm">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-gray-600">Annulation gratuite</span>
                        </div>
                        <div class="flex items-center text-sm">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-gray-600">Support 24/7</span>
                        </div>
                    </div>
                </div>

                <!-- Réservations récentes -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Réservations récentes</h3>
                    <div class="space-y-3">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-sm font-medium text-gray-900">Point de charge #123</p>
                            <p class="text-xs text-gray-500">Aujourd'hui, 14:30</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-sm font-medium text-gray-900">Point de charge #456</p>
                            <p class="text-xs text-gray-500">Hier, 09:15</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de sélection des points de charge -->
<div id="chargingPointModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-4xl w-full max-h-[80vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Sélectionner un point de charge</h3>
                    <button onclick="closeChargingPointSelector()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6 overflow-y-auto max-h-96">
                <div id="chargingPointsList" class="space-y-3">
                    <!-- Les points de charge seront chargés ici -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la date et l'heure actuelles pour le début de réservation
    const now = new Date();
    const startTime = now.toISOString();
    
    // Charger les points de charge
    loadChargingPoints();
    
    // Gestion du formulaire
    document.getElementById('reservationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        createReservation();
    });
    
    // Gestion des changements de plan tarifaire
    document.getElementById('pricing_plan_id').addEventListener('change', function() {
        updatePriceEstimation();
    });
    
    // Gestion des changements de type de réservation
    document.querySelectorAll('input[name="reservation_type"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            updateReservationType();
            updatePriceEstimation();
        });
    });
    
    // Gestion des changements de valeur de réservation
    document.getElementById('reservation_value').addEventListener('input', function() {
        updatePriceEstimation();
    });
    
    // Initialiser l'estimation de prix
    updatePriceEstimation();
});

function updateReservationType() {
    const reservationType = document.querySelector('input[name="reservation_type"]:checked').value;
    const valueLabel = document.getElementById('valueLabel');
    const valueUnit = document.getElementById('valueUnit');
    const costUnit = document.getElementById('costUnit');
    const valueInput = document.getElementById('reservation_value');
    
    if (reservationType === 'minute') {
        valueLabel.textContent = 'Durée (minutes)';
        valueUnit.textContent = 'min';
        costUnit.textContent = 'minute';
        valueInput.placeholder = 'Entrez la durée en minutes';
        valueInput.min = '1';
        valueInput.step = '1';
    } else {
        valueLabel.textContent = 'Énergie (kWh)';
        valueUnit.textContent = 'kWh';
        costUnit.textContent = 'kWh';
        valueInput.placeholder = 'Entrez l\'énergie en kWh';
        valueInput.min = '0.01';
        valueInput.step = '0.01';
    }
}

function updatePriceEstimation() {
    const pricingPlanSelect = document.getElementById('pricing_plan_id');
    const selectedOption = pricingPlanSelect.options[pricingPlanSelect.selectedIndex];
    const reservationValue = parseFloat(document.getElementById('reservation_value').value) || 0;
    const reservationType = document.querySelector('input[name="reservation_type"]:checked').value;
    
    if (!selectedOption || !selectedOption.value) {
        // Aucun plan sélectionné
        document.getElementById('estimatedCost').textContent = '0.00 €';
        document.getElementById('activationFee').textContent = '0.00 €';
        document.getElementById('unitCost').textContent = '0.00 €';
        return;
    }
    
    const rateType = selectedOption.getAttribute('data-rate-type');
    const currency = selectedOption.getAttribute('data-currency');
    const currencySymbol = currency === 'EUR' ? '€' : currency;
    
    let estimatedCost = 0;
    let unitCost = 0;
    let activationFee = parseFloat(selectedOption.getAttribute('data-activation-fee')) || 0;
    
    if (rateType === 'fixed') {
        estimatedCost = parseFloat(selectedOption.getAttribute('data-fixed-price')) || 0;
    } else {
        // Plan basé sur la durée ou l'énergie
        if (reservationType === 'minute') {
            const pricePerMinute = parseFloat(selectedOption.getAttribute('data-price-per-minute')) || 0;
            unitCost = pricePerMinute;
            estimatedCost = reservationValue * pricePerMinute;
        } else {
            const pricePerKwh = parseFloat(selectedOption.getAttribute('data-price-per-kwh')) || 0;
            unitCost = pricePerKwh;
            estimatedCost = reservationValue * pricePerKwh;
        }
    }
    
    const totalCost = estimatedCost + activationFee;
    
    document.getElementById('estimatedCost').textContent = totalCost.toFixed(2) + ' ' + currencySymbol;
    document.getElementById('activationFee').textContent = activationFee.toFixed(2) + ' ' + currencySymbol;
    document.getElementById('unitCost').textContent = unitCost.toFixed(2) + ' ' + currencySymbol;
}

function loadChargingPoints() {
    fetch('/api/charging-points')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('chargingPointsList');
            container.innerHTML = '';
            
            if (data.success && data.data.length > 0) {
                data.data.forEach(cp => {
                    const div = document.createElement('div');
                    div.className = 'p-4 border border-gray-200 rounded-lg hover:border-emerald-300 cursor-pointer transition-colors';
                    div.onclick = () => selectChargingPoint(cp);
                    div.innerHTML = `
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-900">${cp.name}</h4>
                                <p class="text-sm text-gray-500">${cp.location || 'Localisation non définie'}</p>
                                <div class="flex items-center mt-1">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${cp.status === 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                        ${cp.status === 'online' ? 'En ligne' : 'Hors ligne'}
                                    </span>
                                    <span class="ml-2 text-sm text-gray-500">${cp.power_output || '0'} kW</span>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    `;
                    container.appendChild(div);
                });
            } else {
                container.innerHTML = '<p class="text-center text-gray-500 py-4">Aucun point de charge disponible</p>';
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des points de charge:', error);
            document.getElementById('chargingPointsList').innerHTML = '<p class="text-center text-red-500 py-4">Erreur lors du chargement</p>';
        });
}

function selectChargingPoint(cp) {
    document.getElementById('selectedChargingPointId').value = cp.id;
    document.getElementById('selectedChargingPointName').textContent = cp.name;
    document.getElementById('selectedChargingPointLocation').textContent = cp.location || 'Localisation non définie';
    closeChargingPointSelector();
    
    // Recharger les plans tarifaires pour ce point de charge
    loadPricingPlans(cp.id);
}

function loadPricingPlans(chargingPointId) {
    // Ici nous pourrions charger les plans tarifaires spécifiques au point de charge
    // Pour l'instant, nous gardons les plans existants
    console.log('Plans tarifaires pour le point de charge:', chargingPointId);
}

function openChargingPointSelector() {
    document.getElementById('chargingPointModal').classList.remove('hidden');
}

function closeChargingPointSelector() {
    document.getElementById('chargingPointModal').classList.add('hidden');
}

function createReservation() {
    const form = document.getElementById('reservationForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitBtn');
    
    // Validation des champs requis
    const requiredFields = ['charging_point_id', 'pricing_plan_id', 'reservation_value', 'connector_id', 'id_tag'];
    let isValid = true;
    
    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element || !element.value) {
            element.classList.add('border-red-500');
            isValid = false;
        } else {
            element.classList.remove('border-red-500');
        }
    });
    
    // Vérifier qu'un type de paiement est sélectionné
    const paymentType = document.querySelector('input[name="payment_type"]:checked');
    if (!paymentType) {
        alert('Veuillez sélectionner un type de paiement.');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Création en cours...';
    
    // Ajouter le type de réservation et l'heure de début
    const reservationType = document.querySelector('input[name="reservation_type"]:checked').value;
    formData.set('reservation_type', reservationType);
    
    // Définir l'heure de début comme maintenant (immédiat)
    const now = new Date();
    formData.set('start_time', now.toISOString());
    
    fetch('/api/reservations', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Rediriger vers la page de confirmation
            window.location.href = `/reservations/${data.data.reservation_id}?success=true`;
        } else {
            alert('Erreur: ' + (data.message || 'Erreur lors de la création de la réservation'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la création de la réservation');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Créer la réservation';
    });
}
</script>
@endsection
