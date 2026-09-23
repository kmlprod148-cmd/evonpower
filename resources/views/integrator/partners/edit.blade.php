@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="text-gray-500 text-sm">Mes partenaires</div>
    
    <div class="flex items-center mb-6">
        <a href="{{ route('integrator.partners.show', $partner) }}" class="text-gray-500 hover:text-gray-700 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-medium">Modifier le partenaire</h1>
    </div>

    <!-- Messages d'erreur -->
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('integrator.partners.update', $partner) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-medium mb-4">Informations générales</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nom du partenaire *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $partner->name) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $partner->email) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $partner->phone) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                    <select name="type" id="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="Exploitant" {{ old('type', $partner->type) == 'Exploitant' ? 'selected' : '' }}>Exploitant</option>
                        <option value="Partenaire" {{ old('type', $partner->type) == 'Partenaire' ? 'selected' : '' }}>Partenaire</option>
                        <option value="Opérateur" {{ old('type', $partner->type) == 'Opérateur' ? 'selected' : '' }}>Opérateur</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-medium mb-4">Adresse</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                    <input type="text" name="address" id="address" value="{{ old('address', $partner->address) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $partner->city) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">Code postal</label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $partner->postal_code) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">Pays</label>
                    <input type="text" name="country" id="country" value="{{ old('country', $partner->country) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-medium mb-4">Configuration</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="business_profile_id" class="block text-sm font-medium text-gray-700 mb-2">Profil Business *</label>
                    <select name="business_profile_id" id="business_profile_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                        <option value="">Sélectionner un profil business</option>
                        @foreach($businessProfiles as $profile)
                            <option value="{{ $profile->id }}" 
                                    {{ old('business_profile_id', $partner->business_profile_id) == $profile->id ? 'selected' : '' }}>
                                {{ $profile->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">{{ old('description', $partner->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-medium mb-4">Options</h2>
            
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                           {{ old('is_active', $partner->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Partenaire actif
                    </label>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="add_to_list" id="add_to_list" value="1" 
                           {{ old('add_to_list', $partner->add_to_list) ? 'checked' : '' }}
                           class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                    <label for="add_to_list" class="ml-2 block text-sm text-gray-900">
                        Ajouter à la liste des partenaires disponibles
                    </label>
                    <div class="ml-2">
                        <span class="text-xs text-gray-500">Permet à ce partenaire d'être visible dans la liste générale</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('integrator.partners.show', $partner) }}" 
               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition duration-150">
                Annuler
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition duration-150">
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>
@endsection
