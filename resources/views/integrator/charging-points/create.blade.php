@extends('layouts.app')

@section('title', 'Créer une Borne de Recharge')
@section('page-title', 'Créer une Borne de Recharge')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Créer une Borne de Recharge</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Ajoutez une nouvelle borne pour vos opérateurs</p>
            </div>
            <a href="{{ route('integrator.charging-points.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Retour à la liste
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <form action="{{ route('integrator.charging-points.store') }}" method="POST" class="p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Informations de base -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                        Informations de base
                    </h3>

                    <!-- Nom -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nom de la borne <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Adresse -->
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Adresse <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="address" 
                               name="address" 
                               value="{{ old('address') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('address') border-red-500 @enderror"
                               required>
                        @error('address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Ville -->
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ville <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="city" 
                               name="city" 
                               value="{{ old('city') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('city') border-red-500 @enderror"
                               required>
                        @error('city')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Puissance -->
                    <div>
                        <label for="power_output" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Puissance (kW) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="power_output" 
                               name="power_output" 
                               value="{{ old('power_output') }}"
                               step="0.1"
                               min="0.1"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('power_output') border-red-500 @enderror"
                               required>
                        @error('power_output')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Statut -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Statut <span class="text-red-500">*</span>
                        </label>
                        <select id="status" 
                                name="status"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('status') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionnez un statut</option>
                            <option value="online" {{ old('status') == 'online' ? 'selected' : '' }}>En ligne</option>
                            <option value="offline" {{ old('status') == 'offline' ? 'selected' : '' }}>Hors ligne</option>
                            <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Informations techniques -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                        Informations techniques
                    </h3>

                    <!-- Coordonnées GPS -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Latitude <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   id="latitude" 
                                   name="latitude" 
                                   value="{{ old('latitude') }}"
                                   step="0.000001"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('latitude') border-red-500 @enderror"
                                   required>
                            @error('latitude')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Longitude <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   id="longitude" 
                                   name="longitude" 
                                   value="{{ old('longitude') }}"
                                   step="0.000001"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('longitude') border-red-500 @enderror"
                                   required>
                            @error('longitude')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Opérateur -->
                    <div>
                        <label for="operator_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Opérateur <span class="text-red-500">*</span>
                        </label>
                        <select id="operator_id" 
                                name="operator_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('operator_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionnez un opérateur</option>
                            @foreach($operators as $operator)
                                <option value="{{ $operator->id }}" {{ old('operator_id') == $operator->id ? 'selected' : '' }}>
                                    {{ $operator->name }} ({{ $operator->partner->businessProfile->name ?? 'Aucun business profile' }})
                                </option>
                            @endforeach
                        </select>
                        @error('operator_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Business Profile -->
                    <div>
                        <label for="business_profile_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Business Profile <span class="text-red-500">*</span>
                        </label>
                        <select id="business_profile_id" 
                                name="business_profile_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('business_profile_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionnez un business profile</option>
                            @foreach($businessProfiles as $profile)
                                <option value="{{ $profile->id }}" {{ old('business_profile_id') == $profile->id ? 'selected' : '' }}>
                                    {{ $profile->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('business_profile_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Groupe -->
                    <div>
                        <label for="group_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Groupe
                        </label>
                        <select id="group_id" 
                                name="group_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('group_id') border-red-500 @enderror">
                            <option value="">Aucun groupe</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('group_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Plan tarifaire -->
                    <div>
                        <label for="pricing_plan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Plan tarifaire
                        </label>
                        <select id="pricing_plan_id" 
                                name="pricing_plan_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('pricing_plan_id') border-red-500 @enderror">
                            <option value="">Aucun plan tarifaire</option>
                            @foreach($pricingPlans as $plan)
                                <option value="{{ $plan->id }}" {{ old('pricing_plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} ({{ $plan->price_per_kwh }} EUR/kWh)
                                </option>
                            @endforeach
                        </select>
                        @error('pricing_plan_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Connecteurs -->
            <div class="mt-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
                    Connecteurs
                </h3>
                
                <div id="connectors-container">
                    <div class="connector-item bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Type de connecteur <span class="text-red-500">*</span>
                                </label>
                                <select name="connectors[0][type]" 
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white"
                                        required>
                                    <option value="">Sélectionnez un type</option>
                                    <option value="Type1">Type 1</option>
                                    <option value="Type2">Type 2</option>
                                    <option value="CCS">CCS</option>
                                    <option value="CHAdeMO">CHAdeMO</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Puissance (kW) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       name="connectors[0][power]" 
                                       step="0.1"
                                       min="0.1"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Statut <span class="text-red-500">*</span>
                                </label>
                                <select name="connectors[0][status]" 
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white"
                                        required>
                                    <option value="">Sélectionnez un statut</option>
                                    <option value="available">Disponible</option>
                                    <option value="occupied">Occupé</option>
                                    <option value="out_of_order">Hors service</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="button" 
                        id="add-connector" 
                        class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Ajouter un connecteur
                </button>
            </div>

            <!-- Description -->
            <div class="mt-8">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Description
                </label>
                <textarea id="description" 
                          name="description" 
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('integrator.charging-points.index') }}" 
                   class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Annuler
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors">
                    Créer la borne
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let connectorIndex = 1;
    
    document.getElementById('add-connector').addEventListener('click', function() {
        const container = document.getElementById('connectors-container');
        const newConnector = document.createElement('div');
        newConnector.className = 'connector-item bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4';
        newConnector.innerHTML = `
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Connecteur ${connectorIndex + 1}</h4>
                <button type="button" class="remove-connector text-red-600 hover:text-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Type de connecteur <span class="text-red-500">*</span>
                    </label>
                    <select name="connectors[${connectorIndex}][type]" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="">Sélectionnez un type</option>
                        <option value="Type1">Type 1</option>
                        <option value="Type2">Type 2</option>
                        <option value="CCS">CCS</option>
                        <option value="CHAdeMO">CHAdeMO</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Puissance (kW) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="connectors[${connectorIndex}][power]" 
                           step="0.1"
                           min="0.1"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white"
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Statut <span class="text-red-500">*</span>
                    </label>
                    <select name="connectors[${connectorIndex}][status]" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="">Sélectionnez un statut</option>
                        <option value="available">Disponible</option>
                        <option value="occupied">Occupé</option>
                        <option value="out_of_order">Hors service</option>
                    </select>
                </div>
            </div>
        `;
        
        container.appendChild(newConnector);
        connectorIndex++;
        
        // Ajouter l'événement de suppression
        newConnector.querySelector('.remove-connector').addEventListener('click', function() {
            newConnector.remove();
        });
    });
});
</script>
@endpush
@endsection
