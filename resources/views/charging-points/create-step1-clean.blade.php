@extends('layouts.app')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    @include('charging-points.partials._creation-header', [
        'title' => 'Ajouter un point de charge',
        'currentStep' => 1,
        'totalSteps' => 4
    ])

    <main class="max-w-7xl mx-auto px-4 py-8">
        @include('charging-points.partials._creation-stepper', ['currentStep' => 1])

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form card -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm">
                <form action="{{ route('charging-points.store.step1') }}" method="POST" id="step1Form" novalidate>
                    @csrf

                    <!-- Errors -->
                    @if ($errors->any())
                        <div class="mx-6 mt-6 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800 px-4 py-3">
                            <div class="flex items-start gap-3">
                                <svg class="h-5 w-5 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l6.518 11.59c.75 1.335-.213 3.011-1.742 3.011H3.48c-1.53 0-2.492-1.676-1.742-3.01L8.257 3.1zM11 14a1 1 0 10-2 0 1 1 0 002 0zm-1-7a1 1 0 00-1 1v3a1 1 0 002 0V8a1 1 0 00-1-1z"/></svg>
                                <div>
                                    <p class="font-medium">Veuillez corriger les champs suivants :</p>
                                    <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-sm">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Informations générales</h2>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Les champs marqués d’un <span class="text-red-500">*</span> sont obligatoires</span>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Name -->
                            <div>
                                <label for="name" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Nom de la borne <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name"
                                    class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm"
                                    required
                                    autocomplete="name"
                                    placeholder="Ex. Parking A — Borne 01"
                                    value="{{ old('name', session('charging_point_step1.name', '')) }}">
                                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <!-- Serial -->
                            <div>
                                <label for="serial_number" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Numéro de série <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="serial_number" id="serial_number"
                                    class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm"
                                    required
                                    autocomplete="off"
                                    placeholder="Ex. SN-CHG-2025-0001"
                                    value="{{ old('serial_number', session('charging_point_step1.serial_number', '')) }}">
                                @error('serial_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <!-- Manufacturer -->
                            <div>
                                <label for="manufacturer" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Fabricant <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="manufacturer" id="manufacturer"
                                    class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm"
                                    required
                                    autocomplete="organization"
                                    placeholder="Ex. ABB, Schneider..."
                                    value="{{ old('manufacturer', session('charging_point_step1.manufacturer', '')) }}">
                                @error('manufacturer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <!-- Model -->
                            <div>
                                <label for="model" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Modèle <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="model" id="model"
                                    class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm"
                                    required
                                    autocomplete="off"
                                    placeholder="Ex. Terra AC W7"
                                    value="{{ old('model', session('charging_point_step1.model', '')) }}">
                                @error('model') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <!-- Location -->
                            <div>
                                <label for="location" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Emplacement <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="location" id="location"
                                    class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm"
                                    required
                                    autocomplete="off"
                                    placeholder="Ex. Rez-de-chaussée, place 12"
                                    value="{{ old('location', session('charging_point_step1.location', '')) }}">
                                @error('location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut initial</label>
                                <select id="status" name="status"
                                    class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm"
                                    autocomplete="off">
                                    <option value="online" {{ old('status', session('charging_point_step1.status', 'online')) == 'online' ? 'selected' : '' }}>En ligne</option>
                                    <option value="offline" {{ old('status', session('charging_point_step1.status', '')) == 'offline' ? 'selected' : '' }}>Hors ligne</option>
                                    <option value="maintenance" {{ old('status', session('charging_point_step1.status', '')) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                </select>
                                @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <!-- Group -->
                            <div>
                                <label for="group_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Groupe</label>
                                <select id="group_id" name="group_id"
                                    class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm"
                                    autocomplete="off">
                                    <option value="">Non assigné</option>
                                    @foreach($groups ?? [] as $group)
                                        <option value="{{ $group->id }}" {{ old('group_id', session('charging_point_step1.group_id', '')) == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('group_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>




                            <!-- Address-based Location -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Localisation <span class="text-red-500">*</span>
                                </label>
                                
                                <!-- Smart Address Search -->
                                <div class="mt-2">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="location_search" class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                                            Recherche intelligente d'adresse
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <button type="button" id="search-history-btn" 
                                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                                                    title="Voir l'historique des recherches">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Historique
                                            </button>
                                            <button type="button" id="auto-locate-btn" 
                                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-lg hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition-colors"
                                                    title="Utiliser ma position actuelle">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                Ma position
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="relative">
                                        <input type="text" 
                                               id="location_search" 
                                               name="address"
                                               class="block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm pr-20"
                                               placeholder="Recherche intelligente: 'Hay Ryad', 'Casablanca', 'Rabat', 'Marrakech', 'Place Mohammed V'..."
                                               autocomplete="off"
                                               value="{{ old('address', session('charging_point_step1.address', '')) }}">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <div id="search-loading" class="hidden">
                                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-emerald-600"></div>
                                            </div>
                                            <svg id="search-icon" class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    
                                    <!-- Smart Suggestions -->
                                    <div id="address_suggestions" class="mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-64 overflow-y-auto hidden z-50">
                                        <!-- Suggestions will be populated here -->
                                    </div>
                                    
                                    <!-- Search Tips -->
                                    <div id="search-tips" class="mt-2 text-xs text-gray-500 dark:text-gray-400 hidden">
                                        <div class="flex items-center gap-1">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span>Conseil: Tapez au moins 3 caractères pour des suggestions intelligentes</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Coordinate fields (visible for manual editing) -->
                                <div class="grid grid-cols-2 gap-4 mt-3">
                                    <div>
                                        <label for="latitude" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                            Latitude
                                        </label>
                                        <input type="number" 
                                               step="0.000001" 
                                               name="latitude" 
                                               id="latitude" 
                                               autocomplete="off" 
                                               value="{{ old('latitude', session('charging_point_step1.latitude', '33.5731')) }}"
                                               class="block w-full rounded-lg border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm text-sm">
                                    </div>
                                    <div>
                                        <label for="longitude" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                            Longitude
                                        </label>
                                        <input type="number" 
                                               step="0.000001" 
                                               name="longitude" 
                                               id="longitude" 
                                               autocomplete="off" 
                                               value="{{ old('longitude', session('charging_point_step1.longitude', '-7.5898')) }}"
                                               class="block w-full rounded-lg border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm text-sm">
                                    </div>
                                </div>
                                
                                <!-- Map Display -->
                                <div id="map-container" class="mt-3 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
                                    <div id="map" class="w-full relative" style="height: 320px; min-height: 320px;">
                                        <!-- Carte sera chargée directement ici -->
                                    </div>
                                </div>
                                
                                <!-- Status display -->
                                <div id="location_status" class="mt-2 text-xs text-gray-500 dark:text-gray-400 hidden">
                                    <span id="location_status_text"></span>
                                </div>
                                
                                <!-- Error display -->
                                @error('latitude')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @error('longitude')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @error('address')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea id="description" name="description" rows="4"
                                    class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm"
                                    autocomplete="off"
                                    placeholder="Notes, consignes d'accès, particularités…">{{ old('description', session('charging_point_step1.description', '')) }}</textarea>
                                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-gray-50/80 dark:bg-gray-900/40 border-t border-gray-200/80 dark:border-gray-800/80 rounded-b-2xl flex items-center justify-end">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 shadow-sm transition active:scale-[.99]">
                            Suivant
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.293 15.707a1 1 0 010-1.414L12.586 12H4a1 1 0 110-2h8.586l-2.293-2.293a1 1 0 111.414-1.414l4.0 4.0a1 1 0 010 1.414l-4.0 4.0a1 1 0 01-1.414 0z"/></svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Récapitulatif en temps réel -->
            @include('charging-points.partials._realtime-recap', ['currentStep' => 1])
        </div>
    </main>
</div>

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="{{ asset("css/leaflet/leaflet.css") }}" />
<!-- Récapitulatif en temps réel CSS -->
<link rel="stylesheet" href="{{ asset('css/realtime-recap.css') }}">
<style>
/* extra polish */
input, select, textarea { transition: box-shadow .15s ease, border-color .15s ease, transform .03s ease; }
input:active, select:active, textarea:active { transform: translateY(0.5px); }
.validation-item { transition: opacity .2s ease, transform .2s ease; }

/* Map styles */
#map {
    z-index: 1;
    position: relative;
}

.leaflet-container {
    font-family: inherit;
}

.leaflet-control-zoom {
    border: none !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
}

.leaflet-control-zoom a {
    background: white !important;
    color: #374151 !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 8px !important;
    width: 30px !important;
    height: 30px !important;
    line-height: 30px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
}

.leaflet-control-zoom a:hover {
    background: #f9fafb !important;
    color: #111827 !important;
}

.dark .leaflet-control-zoom a {
    background: #1f2937 !important;
    color: #d1d5db !important;
    border-color: #374151 !important;
}

.dark .leaflet-control-zoom a:hover {
    background: #374151 !important;
    color: #f9fafb !important;
}
</style>
@endpush


@push('scripts')
<script src="{{ asset("js/leaflet/leaflet.js") }}"></script>
<script>
(function() {
    'use strict';
    
    console.log('🚀 Recherche intelligente d\'adresse - Initialisation...');
    
    // Configuration
    const CONFIG = {
        defaultLat: 33.5731,
        defaultLng: -7.5898,
        defaultZoom: 13,
        minSearchLength: 3,
        debounceDelay: 300
    };
    
    // Éléments DOM
    const locationSearch = document.getElementById('location_search');
    const addressSuggestions = document.getElementById('address_suggestions');
    const searchTips = document.getElementById('search-tips');
    const searchLoading = document.getElementById('search-loading');
    const searchIcon = document.getElementById('search-icon');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const locationStatus = document.getElementById('location_status');
    const locationStatusText = document.getElementById('location_status_text');
    const autoLocateBtn = document.getElementById('auto-locate-btn');
    const searchHistoryBtn = document.getElementById('search-history-btn');
    const mapContainer = document.getElementById('map');
    
    // Variables globales
    let map = null;
    let marker = null;
    let searchTimeout = null;
    let searchHistory = [];
    let mapInitialized = false;
    
    // Adresses marocaines prédéfinies
    const moroccanAddresses = [
        { name: 'Place Mohammed V, Casablanca 20000, Maroc', lat: 33.5731, lng: -7.5898, zip: '20000' },
        { name: 'Boulevard Mohammed V, Casablanca 20000, Maroc', lat: 33.5731, lng: -7.5898, zip: '20000' },
        { name: 'Avenue Hassan II, Casablanca 20000, Maroc', lat: 33.5731, lng: -7.5898, zip: '20000' },
        { name: 'Place Hassan II, Rabat 10000, Maroc', lat: 34.0209, lng: -6.8416, zip: '10000' },
        { name: 'Avenue Mohammed V, Rabat 10000, Maroc', lat: 34.0209, lng: -6.8416, zip: '10000' },
        { name: 'Hay Ryad, Rabat 10000, Maroc', lat: 34.0209, lng: -6.8416, zip: '10000' },
        { name: 'Place Jemaa el-Fnaa, Marrakech 40000, Maroc', lat: 31.6258, lng: -7.9891, zip: '40000' },
        { name: 'Avenue Mohammed VI, Marrakech 40000, Maroc', lat: 31.6258, lng: -7.9891, zip: '40000' },
        { name: 'Médina de Fès, Fès 30000, Maroc', lat: 34.0331, lng: -5.0003, zip: '30000' },
        { name: 'Plage d\'Agadir, Agadir 80000, Maroc', lat: 30.4278, lng: -9.5981, zip: '80000' },
        { name: 'Place de France, Tanger 90000, Maroc', lat: 35.7595, lng: -5.8340, zip: '90000' },
        { name: 'Avenue Zerktouni, Casablanca 20000, Maroc', lat: 33.5731, lng: -7.5898, zip: '20000' },
        { name: 'Ain Diab, Casablanca 20000, Maroc', lat: 33.5731, lng: -7.5898, zip: '20000' }
    ];
    
    // Patterns de villes
    const cityPatterns = {
        'casablanca': { lat: 33.5731, lng: -7.5898, zip: '20000' },
        'rabat': { lat: 34.0209, lng: -6.8416, zip: '10000' },
        'marrakech': { lat: 31.6258, lng: -7.9891, zip: '40000' },
        'fes': { lat: 34.0331, lng: -5.0003, zip: '30000' },
        'fès': { lat: 34.0331, lng: -5.0003, zip: '30000' },
        'agadir': { lat: 30.4278, lng: -9.5981, zip: '80000' },
        'tanger': { lat: 35.7595, lng: -5.8340, zip: '90000' },
        'meknes': { lat: 33.8935, lng: -5.5473, zip: '50000' },
        'meknès': { lat: 33.8935, lng: -5.5473, zip: '50000' }
    };
    
    // Charger l'historique
    function loadSearchHistory() {
        try {
            const stored = localStorage.getItem('addressSearchHistory');
            if (stored) {
                searchHistory = JSON.parse(stored);
            }
        } catch (e) {
            console.error('Erreur chargement historique:', e);
        }
    }
    
    // Sauvegarder l'historique
    function saveSearchHistory() {
        try {
            localStorage.setItem('addressSearchHistory', JSON.stringify(searchHistory));
        } catch (e) {
            console.error('Erreur sauvegarde historique:', e);
        }
    }
    
    // Initialiser la carte
    function initializeMap(lat, lng, address) {
        if (mapInitialized && map) {
            map.setView([lat, lng], CONFIG.defaultZoom);
            if (marker) {
                marker.setLatLng([lat, lng]);
            }
            return;
        }
        
        try {
            // Nettoyer le conteneur
    if (mapContainer) {
                mapContainer.innerHTML = '';
            }
            
            // Créer la carte
            map = L.map('map', {
                zoomControl: true,
                preferCanvas: true
            }).setView([lat, lng], CONFIG.defaultZoom);
            
            // Ajouter les tuiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19
            }).addTo(map);
            
            // Créer le marqueur
            marker = L.marker([lat, lng], {
                draggable: true
            }).addTo(map);
            
            // Événements
            marker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                updateCoordinates(pos.lat, pos.lng);
                showLocationStatus(`Position mise à jour: ${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`, 'success');
            });
            
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                updateCoordinates(e.latlng.lat, e.latlng.lng);
                showLocationStatus(`Position mise à jour: ${e.latlng.lat.toFixed(6)}, ${e.latlng.lng.toFixed(6)}`, 'success');
            });
            
            mapInitialized = true;
            
            if (address) {
                showLocationStatus(`Carte centrée sur: ${address}`, 'success');
            } else {
                showLocationStatus('Carte initialisée', 'success');
            }
            
        } catch (error) {
            console.error('Erreur initialisation carte:', error);
            showLocationStatus('Erreur lors de l\'initialisation de la carte', 'error');
        }
    }
    
    // Mettre à jour les coordonnées
    function updateCoordinates(lat, lng) {
        if (latInput) latInput.value = lat.toFixed(6);
        if (lngInput) lngInput.value = lng.toFixed(6);
        
        // Mettre à jour la carte si nécessaire
        if (map && marker && mapInitialized) {
            const currentPos = marker.getLatLng();
            if (Math.abs(currentPos.lat - lat) > 0.0001 || Math.abs(currentPos.lng - lng) > 0.0001) {
                marker.setLatLng([lat, lng]);
                map.setView([lat, lng], map.getZoom());
            }
        }
    }
    
    // Recherche d'adresse
    function searchAddress(query) {
        const queryTrim = query.trim();
        
        if (queryTrim.length < 2) {
            hideSuggestions();
            hideSearchTips();
            return;
        }
        
        if (queryTrim.length < CONFIG.minSearchLength) {
            showSearchTips();
            hideSuggestions();
            return;
        }
        
        hideSearchTips();
        showSearchLoading();
        
        // Recherche dans les adresses prédéfinies
        const suggestions = generateSuggestions(queryTrim);
        
        setTimeout(() => {
            hideSearchLoading();
            if (suggestions.length > 0) {
                showSuggestions(suggestions);
            } else {
                hideSuggestions();
                showLocationStatus('Aucune adresse trouvée', 'error');
            }
        }, 200);
    }
    
    // Générer les suggestions
    function generateSuggestions(query) {
        const queryLower = query.toLowerCase();
        
        // Recherche par code postal
        const zipMatch = query.match(/\b\d{5}\b/);
        const zipCode = zipMatch ? zipMatch[0] : null;
        
        let matches = [];
        if (zipCode) {
            matches = moroccanAddresses.filter(addr => 
                addr.zip === zipCode || addr.name.toLowerCase().includes(queryLower)
            );
        } else {
            matches = moroccanAddresses.filter(addr => 
                addr.name.toLowerCase().includes(queryLower)
            );
        }
        
        // Si pas de match, essayer les villes
        if (matches.length === 0) {
            for (const [city, coords] of Object.entries(cityPatterns)) {
                if (queryLower.includes(city)) {
                    return [{
                        display_name: `${query}, ${city.charAt(0).toUpperCase() + city.slice(1)} ${coords.zip}, Maroc`,
                        lat: coords.lat,
                        lng: coords.lng
                    }];
                }
            }
        }
        
        return matches.map(addr => ({
            display_name: addr.name,
            lat: addr.lat,
            lng: addr.lng
        }));
    }
    
    // Afficher les suggestions
    function showSuggestions(suggestions) {
        if (!addressSuggestions) return;
        
        addressSuggestions.innerHTML = '';
        
        suggestions.forEach((suggestion) => {
            const div = document.createElement('div');
            div.className = 'px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0';
            div.innerHTML = `
                <div class="flex items-start gap-2">
                    <div class="mt-0.5">
                        <svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">${suggestion.display_name}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">${suggestion.lat.toFixed(6)}, ${suggestion.lng.toFixed(6)}</div>
                    </div>
                </div>
            `;
            
            div.addEventListener('click', () => selectAddress(suggestion));
            addressSuggestions.appendChild(div);
        });
        
        addressSuggestions.classList.remove('hidden');
    }
    
    // Masquer les suggestions
    function hideSuggestions() {
        if (addressSuggestions) {
            addressSuggestions.classList.add('hidden');
        }
    }
    
    // Afficher/masquer les conseils
    function showSearchTips() {
        if (searchTips) searchTips.classList.remove('hidden');
    }
    
    function hideSearchTips() {
        if (searchTips) searchTips.classList.add('hidden');
    }
    
    // Afficher/masquer le chargement
    function showSearchLoading() {
        if (searchLoading) searchLoading.classList.remove('hidden');
        if (searchIcon) searchIcon.classList.add('hidden');
    }
    
    function hideSearchLoading() {
        if (searchLoading) searchLoading.classList.add('hidden');
        if (searchIcon) searchIcon.classList.remove('hidden');
    }
    
    // Afficher le statut
    function showLocationStatus(message, type) {
        if (!locationStatus || !locationStatusText) return;
        
        locationStatusText.textContent = message;
        locationStatus.className = `mt-2 text-xs ${type === 'success' ? 'text-emerald-600 dark:text-emerald-400' : type === 'error' ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400'}`;
        locationStatus.classList.remove('hidden');
        
        if (type === 'success') {
            setTimeout(() => {
                if (locationStatus) locationStatus.classList.add('hidden');
            }, 3000);
        }
    }
    
    // Sélectionner une adresse
    function selectAddress(suggestion) {
        const lat = parseFloat(suggestion.lat);
        const lng = parseFloat(suggestion.lng);
        
        if (isNaN(lat) || isNaN(lng)) {
            showLocationStatus('Erreur: coordonnées invalides', 'error');
            return;
        }
        
        // Mettre à jour le champ de recherche
        if (locationSearch) {
            locationSearch.value = suggestion.display_name;
        }
        
        // Mettre à jour les coordonnées
        updateCoordinates(lat, lng);
        
        // Ajouter à l'historique
        if (!searchHistory.includes(suggestion.display_name)) {
            searchHistory.unshift(suggestion.display_name);
            searchHistory = searchHistory.slice(0, 10);
            saveSearchHistory();
        }
        
        hideSuggestions();
        hideSearchTips();
        
        // Initialiser ou mettre à jour la carte
        if (!mapInitialized) {
            initializeMap(lat, lng, suggestion.display_name);
        } else {
            map.setView([lat, lng], 15);
            marker.setLatLng([lat, lng]);
            showLocationStatus(`✅ Adresse sélectionnée: ${suggestion.display_name}`, 'success');
        }
    }
    
    // Afficher l'historique
    function showSearchHistory() {
        if (!addressSuggestions) return;
        
        if (searchHistory.length === 0) {
            addressSuggestions.innerHTML = `
                <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 text-center">
                    Aucun historique de recherche
            </div>
        `;
        } else {
            addressSuggestions.innerHTML = '';
            searchHistory.forEach(address => {
                const div = document.createElement('div');
                div.className = 'px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0 flex items-center gap-2';
                div.innerHTML = `
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm text-gray-900 dark:text-gray-100">${address}</span>
                `;
                
                div.addEventListener('click', () => {
                    locationSearch.value = address;
                    hideSuggestions();
                    searchAddress(address);
                });
                
                addressSuggestions.appendChild(div);
            });
        }
        
        addressSuggestions.classList.remove('hidden');
    }
    
    // Géolocalisation
    function getCurrentLocation() {
        if (!navigator.geolocation) {
            showLocationStatus('La géolocalisation n\'est pas supportée', 'error');
            return;
        }
        
        showLocationStatus('Détection de votre position...', 'info');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                updateCoordinates(lat, lng);
                
                if (locationSearch) {
                    locationSearch.value = `Position actuelle (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
                }
                
                if (!mapInitialized) {
                    initializeMap(lat, lng, 'Position actuelle');
                } else {
                    map.setView([lat, lng], 15);
                    marker.setLatLng([lat, lng]);
                }
                
                showLocationStatus(`✅ Position détectée: ${lat.toFixed(6)}, ${lng.toFixed(6)}`, 'success');
            },
            function(error) {
                showLocationStatus('Impossible de détecter votre position', 'error');
            },
            { enableHighAccuracy: false, timeout: 5000 }
        );
    }
    
    // Initialisation
    function init() {
        console.log('✅ Initialisation de la recherche intelligente...');
        
        loadSearchHistory();
        
        // Initialiser les coordonnées par défaut si vides
        if (latInput && lngInput) {
            const lat = parseFloat(latInput.value) || CONFIG.defaultLat;
            const lng = parseFloat(lngInput.value) || CONFIG.defaultLng;
            
            if (!latInput.value || !lngInput.value) {
                updateCoordinates(lat, lng);
            }
            
            // Synchroniser la carte avec les champs manuels
            latInput.addEventListener('change', function() {
                const lat = parseFloat(this.value);
                const lng = parseFloat(lngInput.value);
                if (!isNaN(lat) && !isNaN(lng) && mapInitialized) {
                    map.setView([lat, lng], map.getZoom());
                    marker.setLatLng([lat, lng]);
                }
            });
            
            lngInput.addEventListener('change', function() {
                const lat = parseFloat(latInput.value);
                const lng = parseFloat(this.value);
                if (!isNaN(lat) && !isNaN(lng) && mapInitialized) {
                    map.setView([lat, lng], map.getZoom());
                    marker.setLatLng([lat, lng]);
                }
            });
        }
        
        // Événements de recherche
        if (locationSearch) {
            locationSearch.addEventListener('input', function() {
                const query = this.value.trim();
                
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                searchTimeout = setTimeout(() => {
                    searchAddress(query);
                }, CONFIG.debounceDelay);
            });
            
            locationSearch.addEventListener('focus', function() {
                if (this.value.trim().length >= CONFIG.minSearchLength) {
                    searchAddress(this.value.trim());
                } else {
                    showSearchTips();
                }
            });
            
            locationSearch.addEventListener('blur', function() {
                setTimeout(() => {
                    hideSuggestions();
                    hideSearchTips();
                }, 200);
            });
        }
        
        // Bouton géolocalisation
        if (autoLocateBtn) {
            autoLocateBtn.addEventListener('click', getCurrentLocation);
        }
        
        // Bouton historique
        if (searchHistoryBtn) {
            searchHistoryBtn.addEventListener('click', showSearchHistory);
        }
        
        // Masquer les suggestions en cliquant ailleurs
        document.addEventListener('click', function(e) {
            if (!locationSearch?.contains(e.target) && !addressSuggestions?.contains(e.target)) {
                hideSuggestions();
            }
        });
        
        // Initialiser la carte avec les coordonnées par défaut ou existantes
        const lat = parseFloat(latInput?.value) || CONFIG.defaultLat;
        const lng = parseFloat(lngInput?.value) || CONFIG.defaultLng;
        
        // Vérifier que Leaflet est chargé avant d'initialiser
        function checkLeafletAndInit() {
            if (typeof L === 'undefined') {
                setTimeout(checkLeafletAndInit, 100);
                return;
            }
            
            setTimeout(() => {
                initializeMap(lat, lng, 'Casablanca, Maroc');
                if (locationSearch && !locationSearch.value) {
                    locationSearch.value = 'Casablanca, Maroc';
                }
            }, 300);
        }
        
        checkLeafletAndInit();
    }
    
    // Attendre que le DOM soit chargé
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush
@endsection