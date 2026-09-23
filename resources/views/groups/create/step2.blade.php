@extends('layouts.app')

@section('content')
<div>
    <!-- Header with X icon -->
    <div class="flex items-center p-4 border-b">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor" onclick="window.location.href='{{ route('groups.index') }}'">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span class="text-sm">Ajouter un nouveau groupe</span>
    </div>

    <div class="p-6">
        <!-- Progress Indicator -->
        <div class="flex mb-10">
            <div class="flex-shrink-0">
                <div class="flex items-center">
                    <!-- Completed step with green checkmark -->
                    <div class="flex items-center">
                        <div class="relative">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <span class="ml-2 text-sm text-green-500">Type et nom</span>
                    </div>
                </div>
            </div>
            
            <div class="flex-grow mx-4 mt-4">
                <div class="h-px bg-green-500"></div>
            </div>
            
            <div class="flex-shrink-0">
                <div class="flex items-center">
                    <!-- Active step (step 2) -->
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <div class="w-2 h-2 bg-white rounded-full"></div>
                        </div>
                        <span class="ml-2 text-sm text-green-500">Coordonnées</span>
                    </div>
                </div>
            </div>
        </div>

        @php
            $groupData = session('group_step1');
        @endphp

        @if(!$groupData)
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <p>Les informations du groupe n'ont pas été trouvées. Veuillez revenir à l'étape précédente.</p>
                <a href="{{ route('groups.create.step1') }}" class="text-red-700 font-bold underline">Retour au formulaire précédent</a>
            </div>
        @else
            <!-- Group summary from Step 1 -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-200">
                <h3 class="font-medium mb-2">Informations du groupe</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Nom:</p>
                        <p class="font-medium">{{ $groupData['name'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Type:</p>
                        <p class="font-medium">{{ $groupData['type'] == 'private' ? 'Privé' : 'Publique' }}</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('groups.store.step2') }}" method="POST">
                @csrf
                
                <!-- Address Section -->
                <div class="mb-6">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adresse*</label>
                    <input 
                        type="text" 
                        name="address" 
                        id="address" 
                        class="w-full border-gray-200 rounded-lg p-3 focus:border-green-500 @error('address') border-red-500 @enderror" 
                        value="{{ old('address', '') }}"
                        required
                        placeholder="Adresse complète"
                    >
                    @error('address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- City Section -->
                <div class="mb-6">
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ville*</label>
                    <input 
                        type="text" 
                        name="city" 
                        id="city" 
                        class="w-full border-gray-200 rounded-lg p-3 focus:border-green-500 @error('city') border-red-500 @enderror" 
                        value="{{ old('city', '') }}"
                        required
                        placeholder="Ville"
                    >
                    @error('city')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Two Column Layout for Postal Code and Country -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Code postal*</label>
                        <input 
                            type="text" 
                            name="postal_code" 
                            id="postal_code" 
                            class="w-full border-gray-200 rounded-lg p-3 focus:border-green-500 @error('postal_code') border-red-500 @enderror" 
                            value="{{ old('postal_code', '') }}"
                            required
                            placeholder="Code postal"
                        >
                        @error('postal_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays*</label>
                        <input 
                            type="text" 
                            name="country" 
                            id="country" 
                            class="w-full border-gray-200 rounded-lg p-3 focus:border-green-500 @error('country') border-red-500 @enderror" 
                            value="{{ old('country', 'Maroc') }}"
                            required
                            placeholder="Pays"
                        >
                        @error('country')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- Optional map preview placeholder -->
                <div class="mb-8 border border-gray-200 rounded-lg p-4 h-48 flex items-center justify-center bg-gray-50">
                    <span class="text-gray-400">Carte de localisation (optionnelle)</span>
                </div>
                
                <!-- Consumption Mode Selection -->
                <div class="mb-6">
                    <label for="consumption_mode" class="block text-sm font-medium text-gray-700 mb-1">Mode de consommation*</label>
                    <select name="consumption_mode" id="consumption_mode" class="w-full border-gray-200 rounded-lg p-3 focus:border-green-500 @error('consumption_mode') border-red-500 @enderror" required>
                        <option value="prepaid" {{ old('consumption_mode', 'prepaid') == 'prepaid' ? 'selected' : '' }}>Recharge prépayée</option>
                        <option value="postpaid" {{ old('consumption_mode', 'prepaid') == 'postpaid' ? 'selected' : '' }}>Recharge postpayée</option>
                    </select>
                    @error('consumption_mode')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if(!auth()->user()->hasRole('partner'))
                <!-- Partner Info (Auto-assigned) -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Partenaire</label>
                    <div class="w-full border border-gray-200 rounded-lg p-3 bg-gray-50 text-gray-600">
                        {{ auth()->user()->partner ? auth()->user()->partner->name : (auth()->user()->partner_id ? 'Partenaire #' . auth()->user()->partner_id : 'Aucun') }}
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Le groupe sera automatiquement associé à votre profil partenaire.</p>
                </div>
                @endif
                
                <!-- Navigation Buttons -->
                <div class="flex justify-between">
                    <a href="{{ route('groups.create.step1') }}" class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50 transition-colors">
                        Retour
                    </a>
                    <div>
                        <a href="{{ route('groups.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg mr-3 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50 transition-colors">
                            Annuler
                        </a>
                        <button type="submit" class="px-6 py-3 bg-green-500 text-black font-medium rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 transition-colors">
                            Terminer
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection