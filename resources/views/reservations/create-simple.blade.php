@extends('layouts.app')

@section('title', 'Créer une Réservation')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Créer une Réservation
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Créez une nouvelle réservation de borne de recharge
            </p>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <form action="{{ route('reservations.store', $chargingPoint->id) }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Charging Point Info -->
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
                    <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-2">
                        Point de Charge Sélectionné
                    </h3>
                    <div class="flex items-center">
                        <i class="fas fa-charging-station text-blue-600 dark:text-blue-400 mr-3"></i>
                        <div>
                            <p class="font-medium text-blue-900 dark:text-blue-100">{{ $chargingPoint->name }}</p>
                            <p class="text-sm text-blue-700 dark:text-blue-300">{{ $chargingPoint->location ?? 'Localisation non définie' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Pricing Plan -->
                <div>
                    <label for="pricing_plan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Plan Tarifaire <span class="text-red-500">*</span>
                    </label>
                    <select name="pricing_plan_id" id="pricing_plan_id" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('pricing_plan_id') border-red-500 @enderror" required>
                        <option value="">Sélectionner un plan tarifaire</option>
                        @foreach($chargingPoint->pricingPlans as $plan)
                            <option value="{{ $plan->id }}" {{ old('pricing_plan_id') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} - {{ $plan->price_per_kwh }}€/kWh
                            </option>
                        @endforeach
                    </select>
                    @error('pricing_plan_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Reservation Type -->
                <div>
                    <label for="reservation_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Type de Réservation <span class="text-red-500">*</span>
                    </label>
                    <select name="reservation_type" id="reservation_type" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('reservation_type') border-red-500 @enderror" required>
                        <option value="">Sélectionner le type</option>
                        <option value="kwh" {{ old('reservation_type') == 'kwh' ? 'selected' : '' }}>Par kWh</option>
                        <option value="minute" {{ old('reservation_type') == 'minute' ? 'selected' : '' }}>Par Minute</option>
                    </select>
                    @error('reservation_type')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Reservation Value -->
                <div>
                    <label for="reservation_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Valeur <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="reservation_value" id="reservation_value" step="0.01" min="0.01" 
                               value="{{ old('reservation_value') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('reservation_value') border-red-500 @enderror" 
                               placeholder="0.00" required>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <span class="text-gray-500 dark:text-gray-400 text-sm" id="value-unit">kWh</span>
                        </div>
                    </div>
                    @error('reservation_value')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Start Time -->
                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Heure de Début
                    </label>
                    <input type="datetime-local" name="start_time" id="start_time" 
                           value="{{ old('start_time', now()->format('Y-m-d\TH:i')) }}"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('start_time') border-red-500 @enderror">
                    @error('start_time')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Type -->
                <div>
                    <label for="payment_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Type de Paiement <span class="text-red-500">*</span>
                    </label>
                    <select name="payment_type" id="payment_type" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('payment_type') border-red-500 @enderror" required>
                        <option value="">Sélectionner le type de paiement</option>
                        <option value="cmi" {{ old('payment_type') == 'cmi' ? 'selected' : '' }}>CMI (Paiement en ligne)</option>
                        <option value="offline" {{ old('payment_type', 'offline') == 'offline' ? 'selected' : '' }}>Paiement sur place</option>
                    </select>
                    @error('payment_type')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Guest Information (if not authenticated) -->
                @guest
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200 mb-3">
                        Informations Invité
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="guest_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email
                            </label>
                            <input type="email" name="guest_email" id="guest_email" 
                                   value="{{ old('guest_email') }}"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('guest_email') border-red-500 @enderror" 
                                   placeholder="votre@email.com">
                            @error('guest_email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="guest_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Téléphone
                            </label>
                            <input type="tel" name="guest_phone" id="guest_phone" 
                                   value="{{ old('guest_phone') }}"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('guest_phone') border-red-500 @enderror" 
                                   placeholder="+33 6 12 34 56 78">
                            @error('guest_phone')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                @endguest

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('charging-points.show', $chargingPoint->id) }}" 
                       class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        Créer la Réservation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Update value unit based on reservation type
document.getElementById('reservation_type').addEventListener('change', function() {
    const unitSpan = document.getElementById('value-unit');
    if (this.value === 'kwh') {
        unitSpan.textContent = 'kWh';
    } else if (this.value === 'minute') {
        unitSpan.textContent = 'min';
    }
});
</script>
@endsection
