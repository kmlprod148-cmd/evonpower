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
                                
                                <!-- Map Display -->
                                <div id="map-container" class="mt-3 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
                                    <div id="map" class="w-full relative" style="height: 320px; min-height: 320px;">
                                        <!-- Carte sera chargée directement ici -->
                                    </div>
                                </div>
                                
                                <!-- Hidden coordinate fields (auto-filled) -->
                                <input type="hidden" name="latitude" id="latitude" autocomplete="off" value="{{ old('latitude', session('charging_point_step1.latitude', '')) }}">
                                <input type="hidden" name="longitude" id="longitude" autocomplete="off" value="{{ old('longitude', session('charging_point_step1.longitude', '')) }}">
                                
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


<script>
// Script de carte simplifié
document.addEventListener("DOMContentLoaded", function() {
    console.log("Page chargée - version simplifiée");
    
    // Initialiser les coordonnées par défaut
    const latInput = document.getElementById("latitude");
    const lngInput = document.getElementById("longitude");
    
    if (latInput && lngInput) {
        latInput.value = "33.5731";
        lngInput.value = "-7.5898";
    }
    
    // Afficher un message simple
    const mapContainer = document.getElementById("map");
    if (mapContainer) {
        mapContainer.innerHTML = `
            <div style="padding: 20px; text-align: center; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px;">
                <h3>🗺️ Carte Simplifiée</h3>
                <p>Coordonnées par défaut: Casablanca (33.5731, -7.5898)</p>
                <p>Vous pouvez modifier les coordonnées manuellement dans les champs Latitude et Longitude.</p>
            </div>
        `;
    }
});
</script>
@endsection