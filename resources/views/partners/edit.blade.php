@extends('layouts.app')

@section('content')
<div class="px-4 py-6">
    <div class="text-gray-500 text-sm mb-2">
        <a href="{{ route('partners.index') }}" class="hover:underline">Liste des partenaires</a> / Édition
    </div>
    
    <div class="flex items-center mb-6">
        <a href="{{ route('partners.index') }}" class="text-gray-500 hover:text-gray-700 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-medium">Modifier le partenaire</h1>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <!-- En-tête avec avatar et nom du partenaire -->
        <div class="flex items-center mb-6">
            <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center text-green-500 font-bold text-2xl mr-4">
                {{ substr($partner->name ?? '', 0, 1) }}
            </div>
            
            <div>
                <h2 class="text-xl font-medium">{{ $partner->name ?? '' }}</h2>
                <p class="text-gray-500">ID: {{ $partner->id ?? '' }}</p>
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
        
        <form id="partner-edit-form" action="{{ route('partners.update', $partner) }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PUT')
            
            <!-- Onglets pour organiser le formulaire -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-6">
                        <button type="button" class="active-tab border-green-500 text-green-600 whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center" onclick="showTab('general')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Informations générales
                            <span id="general-status" class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">✓</span>
                        </button>
                        <button type="button" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center" onclick="showTab('contact')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Contact
                            <span id="contact-status" class="ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">○</span>
                        </button>
                        <button type="button" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center" onclick="showTab('business')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            Profil business
                            <span id="business-status" class="ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">○</span>
                        </button>
                        <button type="button" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center" onclick="showTab('params')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Paramètres
                            <span id="params-status" class="ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">○</span>
                        </button>
                    </nav>
                </div>
                
                <!-- Barre de progression -->
                <div class="mt-4">
                    <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                        <span>Progression du formulaire</span>
                        <span id="progress-text">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progress-bar" class="bg-green-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Panneau Informations générales -->
            <div id="general-tab" class="tab-content active">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du partenaire*</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $partner->name ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                    
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type*</label>
                        <select name="type" id="type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            <option value="">Sélectionner un type</option>
                            <option value="Exploitant" {{ old('type', $partner->type ?? '') == 'Exploitant' ? 'selected' : '' }}>Exploitant</option>
                            <option value="Propriétaire" {{ old('type', $partner->type ?? '') == 'Propriétaire' ? 'selected' : '' }}>Propriétaire</option>
                            <option value="Intégrateur" {{ old('type', $partner->type ?? '') == 'Intégrateur' ? 'selected' : '' }}>Intégrateur</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email*</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $partner->email ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone*</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone', $partner->phone ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                        <input type="text" name="address" id="address" value="{{ old('address', $partner->address ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
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
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                        <input type="text" name="country" id="country" value="{{ old('country', $partner->country ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>
            </div>
            
            <!-- Panneau Contact -->
            <div id="contact-tab" class="tab-content hidden">
                <div class="space-y-6">
                    <!-- Informations du contact principal -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Contact principal</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="contact_name" class="block text-sm font-medium text-gray-700 mb-1">Nom du contact*</label>
                                <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name', $partner->contact_name ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                            
                            <div>
                                <label for="contact_title" class="block text-sm font-medium text-gray-700 mb-1">Fonction du contact</label>
                                <input type="text" name="contact_title" id="contact_title" value="{{ old('contact_title', $partner->contact_title ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                            
                            <div>
                                <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Email du contact</label>
                                <input type="email" name="contact_email" id="contact_email" value="{{ old('contact_email', $partner->contact_email ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                            
                            <div>
                                <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone du contact</label>
                                <input type="tel" name="contact_phone" id="contact_phone" value="{{ old('contact_phone', $partner->contact_phone ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                        </div>
                    </div>

                    <!-- Contact technique -->
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Contact technique</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="technical_contact_name" class="block text-sm font-medium text-gray-700 mb-1">Nom du contact technique</label>
                                <input type="text" name="technical_contact_name" id="technical_contact_name" value="{{ old('technical_contact_name', $partner->technical_contact_name ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                            
                            <div>
                                <label for="technical_contact_email" class="block text-sm font-medium text-gray-700 mb-1">Email technique</label>
                                <input type="email" name="technical_contact_email" id="technical_contact_email" value="{{ old('technical_contact_email', $partner->technical_contact_email ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                            
                            <div>
                                <label for="technical_contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone technique</label>
                                <input type="tel" name="technical_contact_phone" id="technical_contact_phone" value="{{ old('technical_contact_phone', $partner->technical_contact_phone ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                            
                            <div>
                                <label for="technical_contact_title" class="block text-sm font-medium text-gray-700 mb-1">Fonction technique</label>
                                <input type="text" name="technical_contact_title" id="technical_contact_title" value="{{ old('technical_contact_title', $partner->technical_contact_title ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                        </div>
                    </div>

                    <!-- Contact commercial -->
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Contact commercial</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="commercial_contact_name" class="block text-sm font-medium text-gray-700 mb-1">Nom du contact commercial</label>
                                <input type="text" name="commercial_contact_name" id="commercial_contact_name" value="{{ old('commercial_contact_name', $partner->commercial_contact_name ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                            
                            <div>
                                <label for="commercial_contact_email" class="block text-sm font-medium text-gray-700 mb-1">Email commercial</label>
                                <input type="email" name="commercial_contact_email" id="commercial_contact_email" value="{{ old('commercial_contact_email', $partner->commercial_contact_email ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                            
                            <div>
                                <label for="commercial_contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone commercial</label>
                                <input type="tel" name="commercial_contact_phone" id="commercial_contact_phone" value="{{ old('commercial_contact_phone', $partner->commercial_contact_phone ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                            
                            <div>
                                <label for="commercial_contact_title" class="block text-sm font-medium text-gray-700 mb-1">Fonction commerciale</label>
                                <input type="text" name="commercial_contact_title" id="commercial_contact_title" value="{{ old('commercial_contact_title', $partner->commercial_contact_title ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                        </div>
                    </div>

                    <!-- Notes et informations supplémentaires -->
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informations supplémentaires</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes générales</label>
                                <textarea name="notes" id="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" placeholder="Notes importantes sur le partenaire...">{{ old('notes', $partner->notes ?? '') }}</textarea>
                            </div>
                            
                            <div>
                                <label for="internal_notes" class="block text-sm font-medium text-gray-700 mb-1">Notes internes</label>
                                <textarea name="internal_notes" id="internal_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" placeholder="Notes internes (non visibles par le partenaire)...">{{ old('internal_notes', $partner->internal_notes ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Panneau Profil business -->
            <div id="business-tab" class="tab-content hidden">
                <div class="space-y-6">
                    <!-- Business Profile et informations légales -->
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Profil business et informations légales</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="business_profile_id" class="block text-sm font-medium text-gray-700 mb-1">Business Profile *</label>
                                <select name="business_profile_id" id="business_profile_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                    <option value="">-- Sélectionner un Business Profile --</option>
                                    @foreach($businessProfiles as $profile)
                                        <option value="{{ $profile->id }}"
                                                {{ old('business_profile_id', $partner->business_profile_id) == $profile->id ? 'selected' : '' }}>
                                            {{ $profile->name }} - {{ $profile->type }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('business_profile_id')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Site web</label>
                                <input type="url" name="website" id="website" value="{{ old('website', $partner->website ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" placeholder="https://example.com">
                            </div>
                            
                            <div>
                                <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-1">Numéro d'identification fiscale</label>
                                <input type="text" name="tax_id" id="tax_id" value="{{ old('tax_id', $partner->tax_id ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Devise de facturation</label>
                                <select name="currency" id="currency" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                    <option value="">Sélectionner une devise</option>
                                    <option value="MAD" {{ old('currency', $partner->currency ?? '') === 'MAD' ? 'selected' : '' }}>MAD - Dirham marocain</option>
                                    <option value="EUR" {{ old('currency', $partner->currency ?? '') === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">
                                    Idéalement ne pas modifier après création pour la cohérence des transactions.
                                </p>
                            </div>
                        </div>
                    </div>



                </div>
            </div>
            
            <!-- Panneau Paramètres -->
            <div id="params-tab" class="tab-content hidden">
                <div class="space-y-6">
                    <!-- Paramètres généraux -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Paramètres généraux</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">Statut du partenaire</label>
                                <select name="is_active" id="is_active" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                    <option value="1" {{ old('is_active', $partner->is_active ?? 1) == 1 ? 'selected' : '' }}>Actif</option>
                                    <option value="0" {{ old('is_active', $partner->is_active ?? 1) == 0 ? 'selected' : '' }}>Inactif</option>
                                </select>
                            </div>

                            <div>
                                <label for="collection_mode" class="block text-sm font-medium text-gray-700 mb-1">Mode de collecte financière</label>
                                <select name="collection_mode" id="collection_mode" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                    <option value="">Par défaut (collecte par l’admin)</option>
                                    <option value="admin" {{ old('collection_mode', $partner->collection_mode ?? '') === 'admin' ? 'selected' : '' }}>Collecte par Evon Charge (admin)</option>
                                    <option value="integrator" {{ old('collection_mode', $partner->collection_mode ?? '') === 'integrator' ? 'selected' : '' }}>Collecte par l’intégrateur</option>
                                    <option value="partner" {{ old('collection_mode', $partner->collection_mode ?? '') === 'partner' ? 'selected' : '' }}>Collecte autonome par le partenaire</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    Définit qui encaisse les fonds des transactions de ce partenaire.
                                </p>
                            </div>
                            
                            <div>
                                <label for="language" class="block text-sm font-medium text-gray-700 mb-1">Langue préférée</label>
                                <select name="language" id="language" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                    <option value="fr" {{ old('language', $partner->language ?? 'fr') == 'fr' ? 'selected' : '' }}>Français</option>
                                    <option value="en" {{ old('language', $partner->language ?? 'fr') == 'en' ? 'selected' : '' }}>Anglais</option>
                                    <option value="ar" {{ old('language', $partner->language ?? 'fr') == 'ar' ? 'selected' : '' }}>Arabe</option>
                                    <option value="es" {{ old('language', $partner->language ?? 'fr') == 'es' ? 'selected' : '' }}>Espagnol</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">Fuseau horaire</label>
                                <select name="timezone" id="timezone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                    <option value="Europe/Paris" {{ old('timezone', $partner->timezone ?? 'Europe/Paris') == 'Europe/Paris' ? 'selected' : '' }}>Europe/Paris (UTC+1)</option>
                                    <option value="Europe/London" {{ old('timezone', $partner->timezone ?? 'Europe/Paris') == 'Europe/London' ? 'selected' : '' }}>Europe/London (UTC+0)</option>
                                    <option value="America/New_York" {{ old('timezone', $partner->timezone ?? 'Europe/Paris') == 'America/New_York' ? 'selected' : '' }}>America/New_York (UTC-5)</option>
                                    <option value="Africa/Casablanca" {{ old('timezone', $partner->timezone ?? 'Europe/Paris') == 'Africa/Casablanca' ? 'selected' : '' }}>Africa/Casablanca (UTC+0)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="date_format" class="block text-sm font-medium text-gray-700 mb-1">Format de date</label>
                                <select name="date_format" id="date_format" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                    <option value="d/m/Y" {{ old('date_format', $partner->date_format ?? 'd/m/Y') == 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
                                    <option value="m/d/Y" {{ old('date_format', $partner->date_format ?? 'd/m/Y') == 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                                    <option value="Y-m-d" {{ old('date_format', $partner->date_format ?? 'd/m/Y') == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                                </select>
                            </div>
                        </div>
                    </div>




                </div>
            </div>
            
            <div class="border-t border-gray-200 mt-6 pt-6">
                <div class="flex justify-between items-center">
                    <div class="flex space-x-3">
                        <a href="{{ route('partners.index') }}" class="bg-gray-100 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Annuler
                        </a>
                        <button type="button" onclick="saveDraft()" class="bg-yellow-500 hover:bg-yellow-600 text-green-600 px-4 py-2 rounded-md transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Sauvegarder brouillon
                        </button>
                        <button type="button" onclick="exportData()" class="bg-purple-500 hover:bg-purple-600 text-green-600 px-4 py-2 rounded-md transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Exporter
                        </button>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="button" onclick="validateForm()" class="bg-blue-500 hover:bg-blue-600 text-green-600 px-4 py-2 rounded-md transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Valider
                        </button>
                        <button type="submit" id="save-button" class="bg-green-500 hover:bg-green-600 text-black px-6 py-2 rounded-md transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Enregistrer
                        </button>
                    </div>
                </div>
                
                <!-- Indicateur de sauvegarde automatique -->
                <div id="auto-save-indicator" class="mt-4 text-sm text-gray-500 hidden">
                    <svg class="w-4 h-4 inline mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Sauvegarde automatique en cours...
                </div>
            </div>
        </form>

        <!-- Formulaire de suppression séparé, en dehors du précédent -->
        <form id="delete-form" action="{{ route('partners.destroy', $partner) }}" method="POST" class="inline mt-4">
            @csrf
            @method('DELETE')
            <button type="button" class="text-red-500 flex items-center" onclick="confirmDelete()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Supprimer le partenaire
            </button>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    body {
        background-color: #f9fafb;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .active-tab {
        border-bottom-color: #10b981;
        color: #059669;
    }
</style>
@endpush

@push('scripts')
<script>
    // Variables globales
    let currentTab = 'general';
    let formData = {};
    let autoSaveTimeout;
    let isFormDirty = false;
    
    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        initializeForm();
        setupAutoSave();
        setupFormValidation();
        updateProgress();
    });
    
    function initializeForm() {
        // Collecter les données initiales
        collectFormData();
        
        // Écouter les changements
        document.getElementById('partner-edit-form').addEventListener('input', function() {
            isFormDirty = true;
            updateProgress();
            markTabAsModified(currentTab);
        });
        
        // Écouter les changements de focus
        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('blur', function() {
                validateField(this);
            });
        });
    }
    
    function showTab(tabName) {
        // Sauvegarder les données de l'onglet actuel
        if (isFormDirty) {
            collectFormData();
        }
        
        // Masquer tous les panneaux
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
            tab.classList.remove('active');
        });
        
        // Afficher le panneau sélectionné
        const tabContent = document.getElementById(tabName + '-tab');
        tabContent.classList.remove('hidden');
        tabContent.classList.add('active');
        
        // Mettre à jour les classes des onglets
        document.querySelectorAll('nav button').forEach(button => {
            button.classList.remove('active-tab');
            button.classList.remove('border-green-500');
            button.classList.remove('text-green-600');
            button.classList.add('border-transparent');
            button.classList.add('text-gray-500');
        });
        
        // Mettre en évidence l'onglet actif
        const activeButton = document.querySelector(`button[onclick="showTab('${tabName}')"]`);
        activeButton.classList.add('active-tab');
        activeButton.classList.add('border-green-500');
        activeButton.classList.add('text-green-600');
        activeButton.classList.remove('border-transparent');
        activeButton.classList.remove('text-gray-500');
        
        currentTab = tabName;
        updateProgress();
    }
    
    function collectFormData() {
        const form = document.getElementById('partner-edit-form');
        const formDataObj = new FormData(form);
        
        formData = {};
        for (let [key, value] of formDataObj.entries()) {
            formData[key] = value;
        }
    }
    
    function updateProgress() {
        const tabs = ['general', 'contact', 'business', 'params'];
        let completedTabs = 0;
        
        tabs.forEach(tab => {
            const tabContent = document.getElementById(tab + '-tab');
            const requiredFields = tabContent.querySelectorAll('input[required], select[required], textarea[required]');
            let completedFields = 0;
            
            requiredFields.forEach(field => {
                if (field.value.trim() !== '') {
                    completedFields++;
                }
            });
            
            if (requiredFields.length > 0 && completedFields === requiredFields.length) {
                completedTabs++;
                updateTabStatus(tab, 'completed');
            } else if (completedFields > 0) {
                updateTabStatus(tab, 'partial');
            } else {
                updateTabStatus(tab, 'empty');
            }
        });
        
        const progress = Math.round((completedTabs / tabs.length) * 100);
        document.getElementById('progress-bar').style.width = progress + '%';
        document.getElementById('progress-text').textContent = progress + '%';
    }
    
    function updateTabStatus(tab, status) {
        const statusElement = document.getElementById(tab + '-status');
        
        switch(status) {
            case 'completed':
                statusElement.className = 'ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full';
                statusElement.textContent = '✓';
                break;
            case 'partial':
                statusElement.className = 'ml-2 text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full';
                statusElement.textContent = '~';
                break;
            case 'empty':
                statusElement.className = 'ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full';
                statusElement.textContent = '○';
                break;
        }
    }
    
    function markTabAsModified(tab) {
        const statusElement = document.getElementById(tab + '-status');
        if (statusElement.textContent === '○') {
            statusElement.className = 'ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full';
            statusElement.textContent = '●';
        }
    }
    
    function setupAutoSave() {
        // Sauvegarde automatique toutes les 30 secondes
        setInterval(function() {
            if (isFormDirty) {
                saveDraft(true);
            }
        }, 30000);
    }
    
    function saveDraft(isAuto = false) {
        if (!isFormDirty && !isAuto) return;
        
        collectFormData();
        
        const indicator = document.getElementById('auto-save-indicator');
        if (isAuto) {
            indicator.classList.remove('hidden');
        }
        
        // Simuler la sauvegarde (remplacer par un appel API réel)
        setTimeout(() => {
            if (isAuto) {
                indicator.classList.add('hidden');
            } else {
                showNotification('Brouillon sauvegardé avec succès', 'success');
            }
            isFormDirty = false;
        }, 1000);
    }
    
    function validateForm() {
        const form = document.getElementById('partner-edit-form');
        const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        let errors = [];
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('border-red-500');
                errors.push(`Le champ "${field.previousElementSibling?.textContent || field.name}" est requis`);
            } else {
                field.classList.remove('border-red-500');
            }
        });
        
        if (!isValid) {
            showNotification('Veuillez remplir tous les champs requis', 'error');
            errors.forEach(error => console.log(error));
        } else {
            showNotification('Formulaire valide !', 'success');
        }
        
        return isValid;
    }
    
    function validateField(field) {
        if (field.hasAttribute('required') && !field.value.trim()) {
            field.classList.add('border-red-500');
            return false;
        } else {
            field.classList.remove('border-red-500');
            return true;
        }
    }
    
    function setupFormValidation() {
        // Validation en temps réel
        document.querySelectorAll('input[type="email"]').forEach(field => {
            field.addEventListener('blur', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value && !emailRegex.test(this.value)) {
                    this.classList.add('border-red-500');
                    showFieldError(this, 'Format d\'email invalide');
                } else {
                    this.classList.remove('border-red-500');
                    hideFieldError(this);
                }
            });
        });
        
        document.querySelectorAll('input[type="url"]').forEach(field => {
            field.addEventListener('blur', function() {
                try {
                    if (this.value && !new URL(this.value)) {
                        this.classList.add('border-red-500');
                        showFieldError(this, 'URL invalide');
                    } else {
                        this.classList.remove('border-red-500');
                        hideFieldError(this);
                    }
                } catch {
                    this.classList.add('border-red-500');
                    showFieldError(this, 'URL invalide');
                }
            });
        });
    }
    
    function showFieldError(field, message) {
        hideFieldError(field);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-500 text-sm mt-1 field-error';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
    
    function hideFieldError(field) {
        const errorDiv = field.parentNode.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    function exportData() {
        collectFormData();
        
        // Créer un objet avec toutes les données du formulaire
        const exportData = {
            partner: {
                general: {
                    name: formData.name || '',
                    type: formData.type || '',
                    email: formData.email || '',
                    phone: formData.phone || '',
                    city: formData.city || '',
                    address: formData.address || '',
                    postal_code: formData.postal_code || '',
                    country: formData.country || '',
                    website: formData.website || ''
                },
                contact: {
                    principal: {
                        name: formData.contact_name || '',
                        title: formData.contact_title || '',
                        email: formData.contact_email || '',
                        phone: formData.contact_phone || ''
                    },
                    technique: {
                        name: formData.technical_contact_name || '',
                        title: formData.technical_contact_title || '',
                        email: formData.technical_contact_email || '',
                        phone: formData.technical_contact_phone || ''
                    },
                    commercial: {
                        name: formData.commercial_contact_name || '',
                        title: formData.commercial_contact_title || '',
                        email: formData.commercial_contact_email || '',
                        phone: formData.commercial_contact_phone || ''
                    },
                    notes: {
                        general: formData.notes || '',
                        internal: formData.internal_notes || ''
                    }
                },
                business: {
                    legal: {
                        business_profile_id: formData.business_profile_id || '',
                        tax_id: formData.tax_id || '',
                        company_registration: formData.company_registration || ''
                    },
                    financial: {
                        bank_name: formData.bank_name || '',
                        bank_account: formData.bank_account || '',
                        iban: formData.iban || '',
                        bic: formData.bic || ''
                    },
                    commercial: {
                        sector: formData.sector || '',
                        company_size: formData.company_size || '',
                        annual_revenue: formData.annual_revenue || '',
                        employee_count: formData.employee_count || ''
                    },
                    billing: {
                        address: formData.billing_address || '',
                        email: formData.billing_email || '',
                        payment_terms: formData.payment_terms || '',
                        currency: formData.currency || ''
                    }
                },
                settings: {
                    general: {
                        is_active: formData.is_active || '',
                        language: formData.language || '',
                        timezone: formData.timezone || '',
                        date_format: formData.date_format || ''
                    },
                    communication: {
                        receive_reports: formData.receive_reports || false,
                        receive_notifications: formData.receive_notifications || false,
                        receive_marketing: formData.receive_marketing || false,
                        receive_sms: formData.receive_sms || false
                    },
                    access: {
                        access_level: formData.access_level || '',
                        api_access: formData.api_access || false,
                        max_users: formData.max_users || '',
                        max_charging_points: formData.max_charging_points || ''
                    },
                    security: {
                        two_factor_auth: formData.two_factor_auth || false,
                        password_expiry: formData.password_expiry || false,
                        ip_restriction: formData.ip_restriction || false,
                        allowed_ips: formData.allowed_ips || ''
                    },
                    billing: {
                        billing_frequency: formData.billing_frequency || '',
                        auto_renewal: formData.auto_renewal || false,
                        discount_percentage: formData.discount_percentage || '',
                        credit_limit: formData.credit_limit || ''
                    }
                }
            },
            export_info: {
                timestamp: new Date().toISOString(),
                user: '{{ auth()->user()->name ?? "Unknown" }}',
                partner_id: '{{ $partner->id }}'
            }
        };
        
        // Créer et télécharger le fichier JSON
        const dataStr = JSON.stringify(exportData, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = `partner_${formData.name || 'export'}_${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        
        showNotification('Données exportées avec succès', 'success');
    }
    
    function confirmDelete() {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce partenaire ? Cette action est irréversible.')) {
            document.getElementById('delete-form').submit();
        }
    }
    
    // Prévenir la perte de données
    window.addEventListener('beforeunload', function(e) {
        if (isFormDirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    
    // Gestion de la soumission du formulaire
    document.getElementById('partner-edit-form').addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
        
        // Désactiver le bouton de soumission
        const submitButton = document.getElementById('save-button');
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <svg class="w-4 h-4 inline mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Enregistrement...
        `;
    });
</script>
@endpush