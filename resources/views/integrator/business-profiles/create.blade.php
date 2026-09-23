@extends('layouts.app')

@section('title', 'Créer un Business Profile')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Créer un Business Profile</h1>
            <p class="mt-1 text-sm text-gray-600">
                Créez un nouveau business profile pour votre intégrateur.
            </p>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('integrator.business-profiles.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- Informations de base -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Informations de base</h3>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom du profil *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-300 @enderror" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Type de profil *</label>
                        <select name="type" id="type" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('type') border-red-300 @enderror" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="integrator" {{ old('type') == 'integrator' ? 'selected' : '' }}>Intégrateur</option>
                            <option value="operator" {{ old('type') == 'operator' ? 'selected' : '' }}>Opérateur</option>
                            <option value="partner" {{ old('type') == 'partner' ? 'selected' : '' }}>Partenaire</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Configuration des frais -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Configuration des frais</h3>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="transaction_fee_fixed_amount" class="block text-sm font-medium text-gray-700">Frais de transaction (montant fixe)</label>
                        <input type="number" name="transaction_fee_fixed_amount" id="transaction_fee_fixed_amount" 
                               value="{{ old('transaction_fee_fixed_amount') }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('transaction_fee_fixed_amount') border-red-300 @enderror">
                        @error('transaction_fee_fixed_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="transaction_fee_percentage" class="block text-sm font-medium text-gray-700">Frais de transaction (pourcentage)</label>
                        <input type="number" name="transaction_fee_percentage" id="transaction_fee_percentage" 
                               value="{{ old('transaction_fee_percentage') }}" step="0.01" min="0" max="100"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('transaction_fee_percentage') border-red-300 @enderror">
                        @error('transaction_fee_percentage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="charge_fee_fixed_amount" class="block text-sm font-medium text-gray-700">Frais de charge (montant fixe)</label>
                        <input type="number" name="charge_fee_fixed_amount" id="charge_fee_fixed_amount" 
                               value="{{ old('charge_fee_fixed_amount') }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('charge_fee_fixed_amount') border-red-300 @enderror">
                        @error('charge_fee_fixed_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="charge_fee_percentage" class="block text-sm font-medium text-gray-700">Frais de charge (pourcentage)</label>
                        <input type="number" name="charge_fee_percentage" id="charge_fee_percentage" 
                               value="{{ old('charge_fee_percentage') }}" step="0.01" min="0" max="100"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('charge_fee_percentage') border-red-300 @enderror">
                        @error('charge_fee_percentage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Paramètres avancés -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Paramètres avancés</h3>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_public" id="is_public" value="1" 
                               {{ old('is_public') ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_public" class="ml-2 block text-sm text-gray-900">
                            Profil public (visible par d'autres utilisateurs)
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" 
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            Profil actif
                        </label>
                    </div>
                </div>

                @if(auth()->user()->hasRole('integrator'))
                    {{-- Pour les intégrateurs, masquer le champ et pré-sélectionner automatiquement "Opérateur" --}}
                    <input type="hidden" name="target_audience[]" value="operators">
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700">Audience cible</label>
                        <div class="mt-1 p-3 bg-blue-50 border border-blue-200 rounded-md">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-blue-800 font-medium">Opérateur - Icône</span>
                            </div>
                            <p class="mt-1 text-sm text-blue-600">Ce business profile sera automatiquement appliqué à vos opérateurs.</p>
                        </div>
                    </div>
                @else
                    {{-- Pour les autres rôles, afficher le champ de sélection normal --}}
                    <div class="mt-6">
                        <label for="target_audience" class="block text-sm font-medium text-gray-700">Audience cible *</label>
                        <select name="target_audience[]" id="target_audience" multiple
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('target_audience') border-red-300 @enderror" required>
                            <option value="individuals" {{ in_array('individuals', old('target_audience', [])) ? 'selected' : '' }}>Particuliers</option>
                            <option value="businesses" {{ in_array('businesses', old('target_audience', [])) ? 'selected' : '' }}>Entreprises</option>
                            <option value="fleet" {{ in_array('fleet', old('target_audience', [])) ? 'selected' : '' }}>Flottes</option>
                            <option value="public" {{ in_array('public', old('target_audience', [])) ? 'selected' : '' }}>Public</option>
                            <option value="operators" {{ in_array('operators', old('target_audience', [])) ? 'selected' : '' }}>Opérateur - Icône</option>
                        </select>
                        @error('target_audience')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('integrator.business-profiles.index') }}" 
                   class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Annuler
                </a>
                <button type="submit" 
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Créer le Business Profile
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
