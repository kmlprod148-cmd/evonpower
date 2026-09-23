@extends('layouts.app')

@section('content')
<div class="partner-create-container">
    <div class="text-gray-500 text-sm">Partenaires</div>
    
    <div class="flex items-center mb-6">
        <a href="{{ route('partners.index') }}" class="text-gray-500 hover:text-gray-700 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-medium">Créer un nouveau partenaire</h1>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        @if ($errors->any())
        <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <form action="{{ route('partners.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Partner Information Section -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Information du partenaire</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du partenaire*</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type de partenaire*</label>
                            <select name="type" id="type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                <option value="">Sélectionner un type</option>
                                <option value="Exploitant" {{ old('type') == 'Exploitant' ? 'selected' : '' }}>Exploitant</option>
                                <option value="Propriétaire" {{ old('type') == 'Propriétaire' ? 'selected' : '' }}>Propriétaire</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                            <input type="file" name="logo" id="logo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email professionnel*</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone*</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Site web</label>
                            <input type="url" name="website" id="website" value="{{ old('website') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="business_profile_id" class="block text-sm font-medium text-gray-700 mb-1">Profil d'entreprise</label>
                            <select name="business_profile_id" id="business_profile_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                <option value="">Sélectionner un profil d'entreprise</option>
                                @forelse($businessProfiles as $profile)
                                    <option value="{{ $profile->id }}" {{ old('business_profile_id') == $profile->id ? 'selected' : '' }}>{{ $profile->name }}</option>
                                @empty
                                    <!-- Ajouter des options statiques de secours si la liste est vide -->
                                    <option value="default">Profil Standard</option>
                                    <option value="premium">Profil Premium</option>
                                @endforelse
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Ce profil détermine les paramètres commerciaux et les tarifs du partenaire.</p>
                            @if($businessProfiles->isEmpty())
                                <p class="text-xs text-red-500 mt-1">Attention: Aucun profil business disponible. Les options affichées sont temporaires.</p>
                            @endif
                        </div>
                        
                
                @if(auth()->user()->hasRole('admin'))
                <div>
                    <label for="integrator_id" class="block text-sm font-medium text-gray-700 mb-1">Intégrateur*</label>
                    <select name="integrator_id" id="integrator_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        <option value="">-- Sélectionner un intégrateur --</option>
                        @foreach($integrators as $integrator)
                            <option value="{{ $integrator->id }}" {{ old('integrator_id') == $integrator->id ? 'selected' : '' }}>
                                {{ $integrator->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">L'intégrateur qui gère ce partenaire.</p>
                </div>
                @endif

                <!-- Address Section -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Adresse</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                            <input type="text" name="address" id="address" value="{{ old('address') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ville*</label>
                            <input type="text" name="city" id="city" value="{{ old('city') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                            <select name="country" id="country" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                <option value="">Sélectionner un pays</option>
                                <option value="Morocco" {{ old('country') == 'Morocco' ? 'selected' : '' }}>Maroc</option>
                                <option value="Algeria" {{ old('country') == 'Algeria' ? 'selected' : '' }}>Algérie</option>
                                <option value="Tunisia" {{ old('country') == 'Tunisia' ? 'selected' : '' }}>Tunisie</option>
                                <option value="France" {{ old('country') == 'France' ? 'selected' : '' }}>France</option>
                                <option value="Spain" {{ old('country') == 'Spain' ? 'selected' : '' }}>Espagne</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Person Section -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Personne de contact</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="contact_name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet*</label>
                            <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="contact_title" class="block text-sm font-medium text-gray-700 mb-1">Titre/Fonction</label>
                            <input type="text" name="contact_title" id="contact_title" value="{{ old('contact_title') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                </div>
                
                <!-- Station Information -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Informations sur les bornes</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="station_count" class="block text-sm font-medium text-gray-700 mb-1">Nombre de bornes prévues*</label>
                            <input type="number" name="station_count" id="station_count" min="1" value="{{ old('station_count', 1) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="installation_date" class="block text-sm font-medium text-gray-700 mb-1">Date d'installation prévue</label>
                            <input type="date" name="installation_date" id="installation_date" value="{{ old('installation_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div class="col-span-1 md:col-span-2">
                            <label for="installation_notes" class="block text-sm font-medium text-gray-700 mb-1">Notes d'installation</label>
                            <textarea name="installation_notes" id="installation_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">{{ old('installation_notes') }}</textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Admin Account -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Compte administrateur</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1">Email administrateur*</label>
                            <input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            <p class="text-xs text-gray-500 mt-1">Cet email sera utilisé pour la connexion de l'administrateur du partenaire.</p>
                        </div>
                        
                        <div>
                            <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe*</label>
                            <input type="password" name="admin_password" id="admin_password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            <p class="text-xs text-gray-500 mt-1">Minimum 8 caractères.</p>
                        </div>
                        
                        <div>
                            <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe*</label>
                            <input type="password" name="admin_password_confirmation" id="admin_password_confirmation" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div class="col-span-2">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Informations supplémentaires</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div class="col-span-1 md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description du partenaire</label>
                            <textarea name="description" id="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">{{ old('description') }}</textarea>
                        </div>
                        
                        <div>
                            <label for="business_hours" class="block text-sm font-medium text-gray-700 mb-1">Heures d'ouverture</label>
                            <input type="text" name="business_hours" id="business_hours" value="{{ old('business_hours') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active') ? 'checked' : '' }} class="h-4 w-4 text-green-500 border-gray-300 rounded focus:ring-green-500">
                                <label for="is_active" class="ml-2 block text-sm font-medium text-gray-700">Activer le partenaire</label>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Le partenaire sera immédiatement actif dans le système.</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <a href="{{ route('partner-profiles.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Annuler
                        </a>
                        <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-md text-sm font-medium hover:bg-green-600">
                            Créer le partenaire
                        </button>
                    </div>
                </div>
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