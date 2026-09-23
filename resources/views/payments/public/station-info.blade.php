@extends('layouts.app')

@section('title', 'Informations Borne de Recharge')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto">
        <!-- En-tête -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $station['name'] }}</h1>
            <p class="text-gray-600">{{ $station['address'] }}</p>
        </div>

        <!-- QR Code -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="text-center">
                @if(!empty($qrCodeUrl))
                <img src="{{ $qrCodeUrl }}" alt="QR Code" class="mx-auto w-48 h-48">
                @else
                <div class="mx-auto w-48 h-48 bg-gray-100 flex items-center justify-center rounded">
                    <span class="text-gray-400 text-sm">QR Code indisponible</span>
                </div>
                @endif
                <p class="text-sm text-gray-500 mt-2">Scannez ce code pour démarrer</p>
            </div>
        </div>

        <!-- Informations de la borne -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Détails de la borne</h2>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Puissance maximale</p>
                    <p class="font-medium">{{ $station['max_power'] ?? 22 }} kW</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Statut</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $station['availability']['available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $station['availability']['available'] ? 'Disponible' : 'Indisponible' }}
                    </span>
                </div>
            </div>

            <!-- Connecteurs -->
            <div class="mt-4">
                <p class="text-sm text-gray-500 mb-2">Connecteurs disponibles</p>
                <div class="flex gap-2">
                    @foreach($station['connectors'] as $connector)
                    <span class="px-3 py-1 bg-gray-100 rounded-full text-sm">
                        {{ $connector['type'] }} - {{ $connector['power'] }}kW
                    </span>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Grille tarifaire -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Grille Tarifaire</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Prix par kWh</span>
                    <span class="font-medium">{{ number_format($station['pricing']['price_per_kwh'] ?? 0, 2) }} {{ $station['pricing']['currency'] ?? '' }}</span>
                </div>
                
                @if($station['pricing']['price_per_minute'])
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Prix par minute</span>
                    <span class="font-medium">{{ number_format($station['pricing']['price_per_minute'], 2) }} {{ $station['pricing']['currency'] }}</span>
                </div>
                @endif

                @if($station['pricing']['activation_fee'])
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Frais d'activation</span>
                    <span class="font-medium">{{ number_format($station['pricing']['activation_fee'], 2) }} {{ $station['pricing']['currency'] }}</span>
                </div>
                @endif

                <div class="flex justify-between items-center pt-3 border-t">
                    <span class="text-gray-600">TVA</span>
                    <span class="font-medium">{{ $station['pricing']['vat_rate'] }}%</span>
                </div>
            </div>
        </div>

        <!-- Mode de facturation -->
        <div class="bg-blue-50 rounded-lg p-4 mb-6">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="font-medium text-blue-800">Mode de facturation: {{ ucfirst($consumptionMode) }}</p>
                    <p class="text-sm text-blue-600">
                        @if($consumptionMode === 'prepaid')
                        Paiement immédiat. Crédit non utilisé remboursé automatiquement.
                        @else
                        Paiement à la fin de la session. Débit automatique depuis votre carte.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Formulaire de sélection -->
        <form id="reservation-form" method="POST" action="{{ route('public.payment.customer-form', $chargingPointId) }}">
            @csrf
            
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Sélectionnez vos options</h2>
                
                <!-- Type de réservation -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type de recharge</label>
                    <div class="flex gap-4">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="reservation_type" value="energy" class="sr-only peer" checked>
                            <div class="p-4 border-2 border-gray-200 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 transition text-center">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-600 peer-checked:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span class="font-medium">Energie (kWh)</span>
                            </div>
                        </label>
                        
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="reservation_type" value="duration" class="sr-only peer">
                            <div class="p-4 border-2 border-gray-200 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 transition text-center">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-600 peer-checked:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-medium">Durée (minutes)</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Valeur -->
                <div class="mb-4">
                    <label for="reservation_value" class="block text-sm font-medium text-gray-700 mb-2">
                        <span id="value-label">Quantité (kWh)</span>
                    </label>
                    <input type="number" id="reservation_value" name="reservation_value" 
                           min="1" max="100" value="20" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Estimation -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Estimation du coût:</span>
                        <span id="estimated-cost" class="text-2xl font-bold text-blue-600">--</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1" id="estimation-details">TVA incluse</p>
                </div>
            </div>

            <!-- Bouton continuer -->
            <button type="submit" class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition">
                Continuer
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const reservationTypeRadios = document.querySelectorAll('input[name="reservation_type"]');
    const reservationValueInput = document.getElementById('reservation_value');
    const valueLabel = document.getElementById('value-label');
    const estimatedCostEl = document.getElementById('estimated-cost');
    const estimationDetails = document.getElementById('estimation-details');
    
    const pricing = @json($station['pricing']);
    const currency = pricing.currency || 'EUR';
    
    // Mettre à jour le label
    function updateLabel() {
        const type = document.querySelector('input[name="reservation_type"]:checked').value;
        valueLabel.textContent = type === 'energy' ? 'Quantité (kWh)' : 'Durée (minutes)';
        calculateEstimate();
    }
    
    // Calculer l'estimation
    function calculateEstimate() {
        const type = document.querySelector('input[name="reservation_type"]:checked').value;
        const value = parseFloat(reservationValueInput.value) || 0;
        
        let cost = 0;
        if (type === 'energy') {
            cost = value * pricing.price_per_kwh;
        } else {
            cost = value * pricing.price_per_minute;
        }
        
        // Ajouter les frais d'activation
        if (pricing.activation_fee) {
            cost += pricing.activation_fee;
        }
        
        // Ajouter la TVA
        const vatMultiplier = 1 + (pricing.vat_rate / 100);
        cost = cost * vatMultiplier;
        
        estimatedCostEl.textContent = cost.toFixed(2) + ' ' + currency;
        estimationDetails.textContent = 'TVA ' + pricing.vat_rate + '% incluse';
    }
    
    // Event listeners
    reservationTypeRadios.forEach(radio => {
        radio.addEventListener('change', updateLabel);
    });
    
    reservationValueInput.addEventListener('input', calculateEstimate);
    
    // Calcul initial
    calculateEstimate();
});
</script>
@endpush
@endsection
