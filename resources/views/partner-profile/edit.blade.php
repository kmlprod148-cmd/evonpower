@extends('layouts.app')

@section('content')
<div class="partner-edit-container">
    <div class="text-gray-500 text-sm">Partenaires</div>
    
    <div class="flex items-center mb-6">
        <a href="{{ route('partners.show', $partner ?? 1) ?? '#' }}" class="text-gray-500 hover:text-gray-700 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-medium">Modifier le partenaire</h1>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center mb-6">
            @if(isset($partner) && $partner->logo)
                <img src="{{ asset('storage/' . $partner->logo) }}" alt="Logo" class="h-16 w-16 rounded-full object-cover mr-4">
            @else
                <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center text-green-500 font-bold text-2xl mr-4">
                    {{ substr($partner->name ?? 'Morocco Mall', 0, 1) }}
                </div>
            @endif
            
            <div>
                <h2 class="text-xl font-medium">{{ $partner->name ?? 'Morocco Mall' }}</h2>
                <p class="text-gray-500">ID: {{ $partner->id ?? '12345' }}</p>
            </div>
        </div>
        
        @if ($errors->any())
        <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <form action="{{ route('partners.update', $partner ?? 1) ?? '#' }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Partner Information Section -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Information du partenaire</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du partenaire*</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $partner->name ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type de partenaire*</label>
                            <select name="type" id="type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                <option value="">Sélectionner un type</option>
                                <option value="Centre commercial" {{ old('type', $partner->type ?? '') == 'Centre commercial' ? 'selected' : '' }}>Centre commercial</option>
                                <option value="Supermarché" {{ old('type', $partner->type ?? '') == 'Supermarché' ? 'selected' : '' }}>Supermarché</option>
                                <option value="Station-service" {{ old('type', $partner->type ?? '') == 'Station-service' ? 'selected' : '' }}>Station-service</option>
                                <option value="Hôtel" {{ old('type', $partner->type ?? '') == 'Hôtel' ? 'selected' : '' }}>Hôtel</option>
                                <option value="Restaurant" {{ old('type', $partner->type ?? '') == 'Restaurant' ? 'selected' : '' }}>Restaurant</option>
                                <option value="Parking public" {{ old('type', $partner->type ?? '') == 'Parking public' ? 'selected' : '' }}>Parking public</option>
                                <option value="Autre" {{ old('type', $partner->type ?? '') == 'Autre' ? 'selected' : '' }}>Autre</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                            <input type="file" name="logo" id="logo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            @if(isset($partner) && $partner->logo)
                                <p class="text-xs text-gray-500 mt-1">Logo actuel: {{ $partner->logo }}</p>
                            @endif
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email professionnel*</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $partner->email ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone*</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $partner->phone ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Site web</label>
                            <input type="url" name="website" id="website" value="{{ old('website', $partner->website ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                </div>
                
                <!-- Address Section -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Adresse</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adresse*</label>
                            <input type="text" name="address" id="address" value="{{ old('address', $partner->address ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ville*</label>
                            <input type="text" name="city" id="city" value="{{ old('city', $partner->city ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $partner->postal_code ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays*</label>
                            <select name="country" id="country" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                <option value="">Sélectionner un pays</option>
                                <option value="Morocco" {{ old('country', $partner->country ?? '') == 'Morocco' ? 'selected' : '' }}>Maroc</option>
                                <option value="Algeria" {{ old('country', $partner->country ?? '') == 'Algeria' ? 'selected' : '' }}>Algérie</option>
                                <option value="Tunisia" {{ old('country', $partner->country ?? '') == 'Tunisia' ? 'selected' : '' }}>Tunisie</option>
                                <option value="France" {{ old('country', $partner->country ?? '') == 'France' ? 'selected' : '' }}>France</option>
                                <option value="Spain" {{ old('country', $partner->country ?? '') == 'Spain' ? 'selected' : '' }}>Espagne</option>
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
                            <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name', $partner->contact_name ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="contact_title" class="block text-sm font-medium text-gray-700 mb-1">Titre/Fonction</label>
                            <input type="text" name="contact_title" id="contact_title" value="{{ old('contact_title', $partner->contact_title ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Email*</label>
                            <input type="email" name="contact_email" id="contact_email" value="{{ old('contact_email', $partner->contact_email ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone*</label>
                            <input type="tel" name="contact_phone" id="contact_phone" value="{{ old('contact_phone', $partner->contact_phone ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                </div>
                
                <!-- Station Information -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Informations sur les bornes</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="station_count" class="block text-sm font-medium text-gray-700 mb-1">Nombre de bornes prévues*</label>
                            <input type="number" name="station_count" id="station_count" min="1" value="{{ old('station_count', $partner->station_count ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="installation_date" class="block text-sm font-medium text-gray-700 mb-1">Date d'installation</label>
                            <input type="date" name="installation_date" id="installation_date" value="{{ old('installation_date', $partner->installation_date ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div class="col-span-1 md:col-span-2">
                            <label for="installation_notes" class="block text-sm font-medium text-gray-700 mb-1">Notes d'installation</label>
                            <textarea name="installation_notes" id="installation_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">{{ old('installation_notes', $partner->installation_notes ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div class="col-span-2">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Informations supplémentaires</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div class="col-span-1 md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description du partenaire</label>
                            <textarea name="description" id="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">{{ old('description', $partner->description ?? '') }}</textarea>
                        </div>
                        
                        <div>
                            <label for="business_hours" class="block text-sm font-medium text-gray-700 mb-1">Heures d'ouverture</label>
                            <input type="text" name="business_hours" id="business_hours" value="{{ old('business_hours', $partner->business_hours ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="active" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                            <select name="active" id="active" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                <option value="1" {{ old('active', $partner->active ?? 1) == 1 ? 'selected' : '' }}>Actif</option>
                                <option value="0" {{ old('active', $partner->active ?? 0) == 0 ? 'selected' : '' }}>Inactif</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <a href="{{ route('partners.show', $partner ?? 1) ?? '#' }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Annuler
                        </a>
                        <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-md text-sm font-medium hover:bg-green-600">
                            Enregistrer les modifications
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