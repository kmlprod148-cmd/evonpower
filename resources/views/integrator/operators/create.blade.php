@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-2xl p-6">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4">
                            <i class="fas fa-user-plus text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">Créer un Opérateur</h1>
                            <p class="text-blue-100 mt-1">Nouvel opérateur avec business profile</p>
                        </div>
                    </div>
                    <a href="{{ route('integrator.operators.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white bg-opacity-20 text-white font-semibold rounded-xl hover:bg-opacity-30 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-600 transition-all duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour
                    </a>
                </div>
            </div>
            
            <!-- Information Section -->
            <div class="px-6 pb-4">
                <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-xl p-4">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mr-3"></i>
                        <div class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>Important :</strong> L'opérateur sera automatiquement lié au business profile sélectionné. 
                            Cela déterminera les commissions et tarifs appliqués à ses bornes de recharge.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
            <form action="{{ route('integrator.operators.store') }}" method="POST" class="p-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Informations Personnelles -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-user text-blue-600 mr-2"></i>
                            Informations Personnelles
                        </h3>
                    </div>
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nom complet *
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email *
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('email') border-red-500 @enderror"
                               required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Mot de passe *
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('password') border-red-500 @enderror"
                               required>
                        @error('password')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Confirmer le mot de passe *
                        </label>
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                               required>
                    </div>
                    

                    <!-- Sélection d'Intégrateur (Admin seulement) -->
                    @if(auth()->user()->hasRole('admin'))
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-building text-blue-600 mr-2"></i>
                            Assignation d'Intégrateur
                        </h3>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="integrator_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Intégrateur *
                        </label>
                        <select id="integrator_id" 
                                name="integrator_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('integrator_id') border-red-500 @enderror"
                                required>
                            <option value="">Choisir un intégrateur</option>
                            @if(isset($integrators))
                                @foreach($integrators as $integrator)
                                    <option value="{{ $integrator->id }}" {{ old('integrator_id') == $integrator->id ? 'selected' : '' }}>
                                        {{ $integrator->name }} ({{ $integrator->user->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            @else
                                <option value="">Aucun intégrateur disponible</option>
                            @endif
                        </select>
                        @error('integrator_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Sélectionnez l'intégrateur qui gérera cet opérateur.
                        </p>
                    </div>
                    @else
                    <!-- Champ caché pour les intégrateurs -->
                    <input type="hidden" name="integrator_id" value="{{ auth()->user()->integrator_id }}">
                    @endif
                    
                    <!-- Informations de Contact -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-address-book text-blue-600 mr-2"></i>
                            Informations de Contact
                        </h3>
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Téléphone
                        </label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               value="{{ old('phone') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('phone') border-red-500 @enderror">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Adresse
                        </label>
                        <input type="text" 
                               id="address" 
                               name="address" 
                               value="{{ old('address') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('address') border-red-500 @enderror">
                        @error('address')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ville
                        </label>
                        <input type="text" 
                               id="city" 
                               name="city" 
                               value="{{ old('city') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('city') border-red-500 @enderror">
                        @error('city')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Code postal
                        </label>
                        <input type="text" 
                               id="postal_code" 
                               name="postal_code" 
                               value="{{ old('postal_code') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('postal_code') border-red-500 @enderror">
                        @error('postal_code')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Pays
                        </label>
                        <input type="text" 
                               id="country" 
                               name="country" 
                               value="{{ old('country') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('country') border-red-500 @enderror">
                        @error('country')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Business Profile Selection -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-briefcase text-blue-600 mr-2"></i>
                            Business Profile
                        </h3>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="business_profile_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Business Profile *
                        </label>
                        
                        {{-- L'intégrateur peut appliquer SES business profiles à SES opérateurs --}}
                        <select id="business_profile_id" 
                                name="business_profile_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('business_profile_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionner un Business Profile</option>
                            @foreach($businessProfiles as $bp)
                                <option value="{{ $bp->id }}" {{ old('business_profile_id') == $bp->id ? 'selected' : '' }}>
                                    {{ $bp->name }} — créé par {{ $bp->creator->name ?? 'Intégrateur' }}
                                </option>
                            @endforeach
                        </select>
                        @error('business_profile_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        
                        @if($businessProfiles->count() == 0)
                            <div class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mr-2"></i>
                                    <div class="text-sm text-yellow-800 dark:text-yellow-200">
                                        <strong>Aucun business profile disponible.</strong> 
                                        Vous pouvez créer vos propres business profiles ou utiliser ceux créés par l'admin.
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Vous pouvez utiliser vos business profiles (vous déduisez en premier) ou ceux créés par l'admin (admin déduit en premier)
                            </p>
                        @endif
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('integrator.operators.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200">
                        <i class="fas fa-times mr-2"></i>
                        Annuler
                    </a>
                    
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform hover:scale-105 transition-all duration-200"
                            {{ $businessProfiles->count() == 0 ? 'disabled' : '' }}>
                        <i class="fas fa-save mr-2"></i>
                        Créer l'Opérateur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
