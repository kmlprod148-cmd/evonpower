@extends('layouts.app')

@section('content')
<div class="integrator-edit-container">
    <div class="text-gray-500 text-sm">Intégrateurs</div>
    
    <div class="flex items-center mb-6">
        <a href="{{ route('integrators.show', $integrator ?? 1) ?? '#' }}" class="text-gray-500 hover:text-gray-700 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-medium">Modifier l'intégrateur</h1>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center mb-6">
            @if(isset($integrator) && $integrator->logo)
                <img src="{{ asset('storage/' . $integrator->logo) }}" alt="Logo" class="h-16 w-16 rounded-full object-cover mr-4">
            @else
                <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center text-green-500 font-bold text-2xl mr-4">
                    {{ substr($integrator->name ?? 'EcoSolutions', 0, 1) }}
                </div>
                
                <!-- Contact Person Section -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Personne de contact</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="contact_name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet*</label>
                            <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name', $integrator->contact_name ?? 'Mohammed Alami') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div class="col-span-1 md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description de l'entreprise</label>
                            <textarea name="description" id="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">{{ old('description', $integrator->description ?? 'EcoSolutions est une entreprise spécialisée dans l\'installation et la maintenance des solutions de recharge pour véhicules électriques. Avec une expérience de plus de 8 ans dans le secteur, nous avons réalisé plus de 200 installations à travers le Maroc, et nous continuons à développer notre expertise pour offrir les meilleures solutions à nos clients.') }}</textarea>
                        </div>
                        
                        <div>
                            <label for="experience" class="block text-sm font-medium text-gray-700 mb-1">Années d'expérience</label>
                            <input type="number" name="experience" id="experience" min="0" value="{{ old('experience', $integrator->experience ?? '8') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="active" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                            <select name="active" id="active" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                <option value="1" {{ old('active', $integrator->active ?? 1) == 1 ? 'selected' : '' }}>Actif</option>
                                <option value="0" {{ old('active', $integrator->active ?? 0) == 0 ? 'selected' : '' }}>Inactif</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <a href="{{ route('integrators.show', $integrator ?? 1) ?? '#' }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
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
                        
                        <div>
                            <label for="contact_title" class="block text-sm font-medium text-gray-700 mb-1">Titre/Fonction</label>
                            <input type="text" name="contact_title" id="contact_title" value="{{ old('contact_title', $integrator->contact_title ?? 'Directeur Général') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Email*</label>
                            <input type="email" name="contact_email" id="contact_email" value="{{ old('contact_email', $integrator->contact_email ?? 'm.alami@ecosolutions.com') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone*</label>
                            <input type="tel" name="contact_phone" id="contact_phone" value="{{ old('contact_phone', $integrator->contact_phone ?? '+212 661 123 456') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div class="col-span-2">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Informations supplémentaires</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label for="certifications" class="block text-sm font-medium text-gray-700 mb-1">Certifications</label>
                            <input type="text" name="certifications" id="certifications" value="{{ old('certifications', $integrator->certifications ?? 'ISO 9001, ISO 14001') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            <p class="text-xs text-gray-500 mt-1">Séparés par des virgules (ex: ISO 9001, ISO 14001)</p>
                        </div>
                        
                        <div>
                            <label for="specialization" class="block text-sm font-medium text-gray-700 mb-1">Spécialisation</label>
                            <select name="specialization" id="specialization" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                <option value="">Sélectionner une spécialisation</option>
                                <option value="residential" {{ old('specialization', $integrator->specialization ?? '') == 'residential' ? 'selected' : '' }}>Résidentiel</option>
                                <option value="commercial" {{ old('specialization', $integrator->specialization ?? 'commercial') == 'commercial' ? 'selected' : '' }}>Commercial</option>
                                <option value="industrial" {{ old('specialization', $integrator->specialization ?? '') == 'industrial' ? 'selected' : '' }}>Industriel</option>
                                <option value="public" {{ old('specialization', $integrator->specialization ?? '') == 'public' ? 'selected' : '' }}>Secteur public</option>
                                <option value="all" {{ old('specialization', $integrator->specialization ?? '') == 'all' ? 'selected' : '' }}>Tous secteurs</option>
                            </select>
                        </div>
            @endif
            
            <div>
                <h2 class="text-xl font-medium">{{ $integrator->name ?? 'EcoSolutions' }}</h2>
                <p class="text-gray-500">ID: {{ $integrator->id ?? '12345' }}</p>
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
        
        <form action="{{ route('integrators.update', $integrator ?? 1) ?? '#' }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Company Information Section -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Information de l'entreprise</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de l'entreprise*</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $integrator->name ?? 'EcoSolutions') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email professionnel*</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $integrator->email ?? 'contact@ecosolutions.com') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone*</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $integrator->phone ?? '+212 522 123 456') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Site web</label>
                            <input type="url" name="website" id="website" value="{{ old('website', $integrator->website ?? 'www.ecosolutions.com') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-1">Numéro d'enregistrement</label>
                            <input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number', $integrator->registration_number ?? 'RC12345') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                </div>
                
                <!-- Address Section -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Adresse</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adresse*</label>
                            <input type="text" name="address" id="address" value="{{ old('address', $integrator->address ?? '123 Boulevard Mohammed V') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ville*</label>
                            <input type="text" name="city" id="city" value="{{ old('city', $integrator->city ?? 'Casablanca') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="state" class="block text-sm font-medium text-gray-700 mb-1">Région/Province</label>
                            <input type="text" name="state" id="state" value="{{ old('state', $integrator->state ?? 'Casablanca-Settat') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $integrator->postal_code ?? '20000') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays*</label>
                            <select name="country" id="country" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                <option value="">Sélectionner un pays</option>
                                <option value="Morocco" {{ old('country', $integrator->country ?? 'Morocco') == 'Morocco' ? 'selected' : '' }}>Maroc</option>
                                <option value="Algeria" {{ old('country', $integrator->country ?? '') == 'Algeria' ? 'selected' : '' }}>Algérie</option>
                                <option value="Tunisia" {{ old('country', $integrator->country ?? '') == 'Tunisia' ? 'selected' : '' }}>Tunisie</option>
                                <option value="France" {{ old('country', $integrator->country ?? '') == 'France' ? 'selected' : '' }}>France</option>
                                <option value="Spain" {{ old('country', $integrator->country ?? '') == 'Spain' ? 'selected' : '' }}>Espagne</option>
                            </select>
                        </div>
                    </div>
                </div>
                        
                        <div>
                            <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                            <input type="file" name="logo" id="logo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            @if(isset($integrator) && $integrator->logo)
                                <p class="text-xs text-gray-500 mt-1">Logo actuel: {{ $integrator->logo }}</p>
                            @endif