@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header with X icon -->
    <div class="flex items-center p-4 border-b">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor" onclick="window.location.href='{{ route('groups.index') }}'">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span class="text-sm font-medium">Créer un nouveau groupe</span>
    </div>

    <div class="p-6 md:p-10">
        <!-- Progress Indicator -->
        <div class="flex justify-center mb-10">
            <div class="flex items-center space-x-4">
                <!-- Step 1: Type and Name (Completed) -->
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-green-500">Type et nom</span>
                </div>
                
                <!-- Divider -->
                <div class="w-20 h-0.5 bg-green-500"></div>
                
                <!-- Step 2: Coordinates (Active) -->
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mr-3">
                        <div class="w-2 h-2 bg-white rounded-full"></div>
                    </div>
                    <span class="text-sm font-medium text-green-500">Coordonnées</span>
                </div>
            </div>
        </div>

        @php
            $groupData = session('group_step1');
        @endphp

        @if(!$groupData)
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <p>Les informations du groupe précédentes sont manquantes. Veuillez recommencer la création du groupe.</p>
                <a href="{{ route('groups.create.step1') }}" class="text-red-700 font-bold underline mt-2 block">Retour à la première étape</a>
            </div>
        @else
            <!-- Group Summary from Step 1 -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold mb-4">Récapitulatif du groupe</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Nom du groupe:</p>
                        <p class="font-medium">{{ $groupData['name'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Type de groupe:</p>
                        <p class="font-medium">
                            {{ $groupData['type'] == 'private' ? 'Privé' : 'Public' }}
                        </p>
                    </div>
                </div>
            </div>

            <form action="{{ route('groups.store.step2') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Address Section -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Adresse complète*</label>
                    <input 
                        type="text" 
                        name="address" 
                        id="address" 
                        value="{{ old('address') }}"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 
                               focus:border-green-500 focus:ring-2 focus:ring-green-100 
                               @error('address') border-red-500 @enderror" 
                        placeholder="22, Rue de l'exemple"
                        required
                    >
                    @error('address')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- City Section -->
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">Ville*</label>
                    <input 
                        type="text" 
                        name="city" 
                        id="city" 
                        value="{{ old('city') }}"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 
                               focus:border-green-500 focus:ring-2 focus:ring-green-100 
                               @error('city') border-red-500 @enderror" 
                        placeholder="Ville"
                        required
                    >
                    @error('city')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Postal Code and Country -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">Code postal*</label>
                        <input 
                            type="text" 
                            name="postal_code" 
                            id="postal_code" 
                            value="{{ old('postal_code') }}"
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 
                                   focus:border-green-500 focus:ring-2 focus:ring-green-100 
                                   @error('postal_code') border-red-500 @enderror" 
                            placeholder="Code postal"
                            required
                        >
                        @error('postal_code')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-2">Pays*</label>
                        <input 
                            type="text" 
                            name="country" 
                            id="country" 
                            value="{{ old('country', 'Maroc') }}"
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 
                                   focus:border-green-500 focus:ring-2 focus:ring-green-100 
                                   @error('country') border-red-500 @enderror" 
                            placeholder="Pays"
                            required
                        >
                        @error('country')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- Optional Map Placeholder -->
                <div class="border border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.618V7.382a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <p class="text-gray-500 mb-4">Carte de localisation (optionnelle)</p>
                    <button type="button" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        Sélectionner sur la carte
                    </button>
                </div>
                
                <!-- Navigation Buttons -->
                <div class="flex justify-between items-center pt-6">
                    <a href="{{ route('groups.create.step1') }}" class="flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                        </svg>
                        Retour
                    </a>
                    
                    <div class="space-x-4">
                        <a href="{{ route('groups.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                            Annuler
                        </a>
                        <button type="submit" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                            Terminer
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Optional map selection button
    const mapSelectionBtn = document.querySelector('button[type="button"]');
    if (mapSelectionBtn) {
        mapSelectionBtn.addEventListener('click', function() {
            // Future implementation of map selection
            alert('Sélection de la carte à venir. Veuillez saisir l\'adresse manuellement pour le moment.');
        });
    }

    // Optional advanced address validation
    const addressInput = document.getElementById('address');
    const cityInput = document.getElementById('city');
    const postalCodeInput = document.getElementById('postal_code');

    function validatePostalCode(code) {
        // Basic Moroccan postal code validation (5 digits)
        return /^\d{5}$/.test(code);
    }

    postalCodeInput.addEventListener('input', function() {
        if (this.value && !validatePostalCode(this.value)) {
            this.setCustomValidity('Le code postal doit contenir 5 chiffres');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>
@endpush