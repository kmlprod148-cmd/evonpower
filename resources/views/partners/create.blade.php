@extends('layouts.app')

@section('content')
<div class="partner-create-container">
    <div class="text-gray-500 text-sm">Liste des partenaires</div>
    
    <div class="flex items-center mb-6">
        <a href="{{ route('partners.index') }}" class="text-gray-500 hover:text-gray-700 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-medium">Ajouter un partenaire</h1>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-lg font-medium mb-6">Informations du partenaire</div>
        
        @if ($errors->any())
        <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        

        <form action="{{ route('partners.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du partenaire*</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div>
                    <label for="contact_name" class="block text-sm font-medium text-gray-700 mb-1">Nom de contact*</label>
                    <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type*</label>
                    <select name="type" id="type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        <option value="">Sélectionner un type</option>
                        <option value="Exploitant" {{ old('type') == 'Exploitant' ? 'selected' : '' }}>Exploitant</option>
                        <option value="Propriétaire" {{ old('type') == 'Propriétaire' ? 'selected' : '' }}>Propriétaire</option>
                        <option value="Intégrateur" {{ old('type') == 'Intégrateur' ? 'selected' : '' }}>Intégrateur</option>
                    </select>
                </div>
                
                <!-- Sélection d'Intégrateur (Admin seulement) -->
                @if(auth()->user()->hasRole(['admin', 'Admin', 'super-admin', 'super_admin', 'Super Admin', 'Super-Admin']))
                <div>
                    <label for="integrator_id" class="block text-sm font-medium text-gray-700 mb-1">Intégrateur responsable</label>
                    <select name="integrator_id" id="integrator_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        <option value="">Choisir un intégrateur (optionnel)</option>
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
                    <p class="mt-1 text-xs text-gray-500">
                        Sélectionnez l'intégrateur qui sera responsable de ce partenaire.
                    </p>
                </div>
                @else
                <!-- Champ caché pour les intégrateurs -->
                <input type="hidden" name="integrator_id" value="{{ auth()->user()->integrator_id }}">
                @endif
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email*</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone*</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ville*</label>
                    <input type="text" name="city" id="city" value="{{ old('city') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                    <input type="text" name="address" id="address" value="{{ old('address') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div>
                    <label for="stations_count" class="block text-sm font-medium text-gray-700 mb-1">Nombre de bornes</label>
                    <input type="number" name="stations_count" id="stations_count" min="0" value="{{ old('stations_count', 0) }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Devise de facturation*</label>
                    <select name="currency" id="currency" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        <option value="">Sélectionner une devise</option>
                        <option value="MAD" {{ old('currency') === 'MAD' ? 'selected' : '' }}>MAD - Dirham marocain</option>
                        <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        La devise ne pourra plus être modifiée après création du partenaire.
                    </p>
                </div>

                <div>
                    <label for="collection_mode" class="block text-sm font-medium text-gray-700 mb-1">Mode de collecte financière</label>
                    <select name="collection_mode" id="collection_mode" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        <option value="">Par défaut (collecte par l’admin)</option>
                        <option value="admin" {{ old('collection_mode') === 'admin' ? 'selected' : '' }}>Collecte par Evon Charge (admin)</option>
                        <option value="integrator" {{ old('collection_mode') === 'integrator' ? 'selected' : '' }}>Collecte par l’intégrateur</option>
                        <option value="partner" {{ old('collection_mode') === 'partner' ? 'selected' : '' }}>Collecte autonome par le partenaire</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Définit qui encaisse les fonds des transactions (admin, intégrateur ou partenaire).
                    </p>
                </div>

                <div>
                    <label for="business_profile_id" class="block text-sm font-medium text-gray-700 mb-1">Profil business*</label>
                    <select name="business_profile_id" id="business_profile_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500 @error('business_profile_id') border-red-500 @enderror" required>
                        <option value="">Sélectionner un Business Profile</option>
                        @foreach($businessProfiles as $profile)
                            <option value="{{ $profile->id }}" {{ old('business_profile_id') == $profile->id ? 'selected' : '' }}>
                                {{ $profile->name }} — créé par {{ $profile->creator ? $profile->creator->name : 'Système' }}
                                @if($profile->creator && $profile->creator->hasRole('admin'))
                                    (Admin)
                                @elseif($profile->creator && $profile->creator->hasRole('integrator'))
                                    (Intégrateur)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('business_profile_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">
                        @if(auth()->user()->hasRole('integrator'))
                            Vos profils business et ceux créés par les administrateurs sont disponibles
                        @elseif(auth()->user()->hasRole('admin'))
                            Tous les profils business sont disponibles
                        @else
                            Seuls vos profils business sont disponibles
                        @endif
                    </p>
                </div>
            </div>

            {{-- Champ integrator_id supprimé ici car il est déjà défini plus haut dans le formulaire (lignes 54-78) --}}

            <div class="text-lg font-medium mt-8 mb-6">Informations de l'administrateur</div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1">Email administrateur*</label>
                    <input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe administrateur*</label>
                    <input type="password" name="admin_password" id="admin_password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmation du mot de passe*</label>
                    <input type="password" name="admin_password_confirmation" id="admin_password_confirmation" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>
            </div>
            
            <div class="border-t border-gray-200 mt-6 pt-6 flex justify-end">
                <a href="{{ route('partners.index') }}" class="bg-gray-100 text-gray-800 px-4 py-2 rounded-md mr-2">Annuler</a>
                <button type="submit" class="bg-green-500 text-black px-4 py-2 rounded-md">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    body {
        background-color: #f9fafb;
    }
</style>
@endpush