@extends('layouts.app')

@section('title', 'Modifier l\'Opérateur')
@section('page-title', 'Modifier l\'Opérateur')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Modifier l'Opérateur</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Modifiez les informations de l'opérateur</p>
            </div>
            <a href="{{ route('integrator.operators.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Retour à la liste
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <form action="{{ route('integrator.operators.update', $operator) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Informations personnelles -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                        Informations personnelles
                    </h3>

                    <!-- Nom -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nom complet <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $operator->name) }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', $operator->email) }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('email') border-red-500 @enderror"
                               required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Téléphone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Téléphone
                        </label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               value="{{ old('phone', $operator->phone) }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('phone') border-red-500 @enderror">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Informations professionnelles -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                        Informations professionnelles
                    </h3>

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
                                <option value="{{ $profile->id }}" 
                                        {{ old('business_profile_id', $operator->partner->business_profile_id ?? '') == $profile->id ? 'selected' : '' }}>
                                    {{ $profile->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('business_profile_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Statut -->
                    <div>
                        <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Statut
                        </label>
                        <select id="is_active" 
                                name="is_active"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="1" {{ old('is_active', $operator->is_active) == 1 ? 'selected' : '' }}>Actif</option>
                            <option value="0" {{ old('is_active', $operator->is_active) == 0 ? 'selected' : '' }}>Inactif</option>
                        </select>
                    </div>

                    <!-- Mot de passe (optionnel) -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nouveau mot de passe (laisser vide pour ne pas changer)
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('integrator.operators.index') }}" 
                   class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Annuler
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors">
                    Mettre à jour l'opérateur
                </button>
            </div>
        </form>
    </div>

    <!-- Informations supplémentaires -->
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations supplémentaires</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Créé le</h4>
                <p class="text-sm text-gray-900 dark:text-white">{{ $operator->created_at->format('d/m/Y à H:i') }}</p>
            </div>
            
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Dernière modification</h4>
                <p class="text-sm text-gray-900 dark:text-white">{{ $operator->updated_at->format('d/m/Y à H:i') }}</p>
            </div>
            
            @if($operator->partner)
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">ID Partenaire</h4>
                <p class="text-sm text-gray-900 dark:text-white">{{ $operator->partner->id }}</p>
            </div>
            
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Business Profile actuel</h4>
                <p class="text-sm text-gray-900 dark:text-white">{{ $operator->partner->businessProfile->name ?? 'Aucun' }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
