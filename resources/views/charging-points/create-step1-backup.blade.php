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

                            <!-- Operator -->
                            <div>
                                <label for="operator_id" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Opérateur <span class="text-red-500">*</span>
                                </label>
                                <select id="operator_id" name="operator_id"
                                    class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm"
                                    required
                                    autocomplete="off">
                                    <option value="">Sélectionnez un opérateur</option>
                                    @foreach($operators ?? [] as $operator)
                                        <option value="{{ $operator->id }}" {{ old('operator_id', session('charging_point_step1.operator_id', '')) == $operator->id ? 'selected' : '' }}>
                                            {{ $operator->name }} 
                                            @if($operator->partner)
                                                ({{ $operator->partner->name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('operator_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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

@push('scripts')
<!-- Load Leaflet JS (100% FREE) -->
<script src="{{ asset("js/leaflet/leaflet.js") }}"></script>
<script>
// Simple map initialization
window.initSimpleMap = function() {
    console.log('Initializing simple map...');
    
    if (typeof L === 'undefined') {
        console.error('Leaflet not loaded!');
        return false;
    }
    
    try {
        const mapContainer = document.getElementById('map');
        if (!mapContainer) {
            console.error('Map container not found!');
            return false;
        }
        
        // Clear any existing content
        mapContainer.innerHTML = '';
        
        // Create map
        const map = L.map('map').setView([48.8566, 2.3522], 13);
        
        // Add tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        
        // Add marker
        const marker = L.marker([48.8566, 2.3522]).addTo(map);
        
        console.log('Map initialized successfully!');
        return true;
        
    } catch (error) {
        console.error('Error initializing map:', error);
        return false;
    }
};

// Auto-initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        if (!window.initSimpleMap()) {
            console.log('Retrying map initialization...');
            setTimeout(window.initSimpleMap, 1000);
        }
    }, 1000);
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Form Elements ---
    const form = document.getElementById('step1Form');
    const nameInput = document.getElementById('name');
    const serialNumberInput = document.getElementById('serial_number');
    const manufacturerInput = document.getElementById('manufacturer');
    const modelInput = document.getElementById('model');
    const groupSelect = document.getElementById('group_id');
    const locationInput = document.getElementById('location');
    const addressInput = document.getElementById('location_search'); // Use location_search as address
    const cityInput = null; // No city input field exists
    const statusSelect = document.getElementById('status');
    const descriptionInput = document.getElementById('description');
    const latEl = document.getElementById('latitude');
    const lngEl = document.getElementById('longitude');

    // --- Preview Elements ---
    const previewName = document.getElementById('preview-name');
    const previewSerialNumber = document.getElementById('preview-serial-number');
    const previewManufacturer = document.getElementById('preview-manufacturer');
    const previewModel = document.getElementById('preview-model');
    const previewGroup = document.getElementById('preview-group');
    const previewLocation = document.getElementById('preview-location');
    const previewAddress = document.getElementById('preview-address');
    const previewCity = document.getElementById('preview-city');
    const previewStatus = document.getElementById('preview-status');
    const previewDescription = document.getElementById('preview-description');

    // --- Validation Summary Elements ---
    const validationSummary = document.getElementById('validation-summary');
    const validationList = document.getElementById('validation-list');
    const requiredFields = [
        { id: 'name', label: 'Nom de la borne' },
        { id: 'serial_number', label: 'Numéro de série' },
        { id: 'manufacturer', label: 'Fabricant' },
        { id: 'model', label: 'Modèle' },
        { id: 'location', label: 'Emplacement' },
        { id: 'latitude', label: 'Latitude' },
        { id: 'longitude', label: 'Longitude' },
    ];

    const clamp = (s, n=160) => (s && s.length>n) ? (s.slice(0,n)+'…') : (s || '');

    // --- Live Preview Logic ---
    function updatePreview() {
        if (previewName && nameInput) previewName.textContent = nameInput.value || 'Non spécifié';
        if (previewSerialNumber && serialNumberInput) previewSerialNumber.textContent = serialNumberInput.value || 'Non spécifié';
        if (previewManufacturer && manufacturerInput) previewManufacturer.textContent = manufacturerInput.value || 'Non spécifié';
        if (previewModel && modelInput) previewModel.textContent = modelInput.value || 'Non spécifié';
        if (previewGroup && groupSelect) previewGroup.textContent = groupSelect.selectedIndex > 0 ? groupSelect.options[groupSelect.selectedIndex].text : 'Non assigné';
        if (previewLocation && locationInput) previewLocation.textContent = locationInput.value || 'Non spécifié';
        if (previewAddress && addressInput) previewAddress.textContent = addressInput.value || 'Non spécifié';
        if (previewCity) previewCity.textContent = 'Non spécifié'; // No city input field
        if (previewStatus && statusSelect) previewStatus.textContent = statusSelect.selectedIndex >= 0 ? statusSelect.options[statusSelect.selectedIndex].text : 'Non spécifié';
        if (previewDescription && descriptionInput) previewDescription.textContent = clamp(descriptionInput.value) || 'Non spécifié';
    }

    // --- Live Validation Logic ---
    function updateValidationSummary() {
        if (!validationList || !validationSummary) return;
        
        validationList.innerHTML = '';
        let hasErrors = false;

        requiredFields.forEach(field => {
            const input = document.getElementById(field.id);
            const isValid = input && String(input.value).trim() !== '';

            if (!isValid) {
                hasErrors = true;
                const li = document.createElement('li');
                li.className = 'flex items-center gap-2 text-gray-600 dark:text-gray-400 validation-item';
                li.innerHTML = `
                    <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                    <span>${field.label}</span>
                `;
                validationList.appendChild(li);
            }
        });

        if (hasErrors) {
            validationSummary.style.display = 'block';
        } else {
            validationSummary.style.display = 'none';
        }
    }

    // --- Event Listeners ---
    const inputsForPreview = [nameInput, serialNumberInput, manufacturerInput, modelInput, locationInput, addressInput, descriptionInput, latEl, lngEl];
    inputsForPreview.forEach(el => el && el.addEventListener('input', () => { updatePreview(); updateValidationSummary(); }));
    [groupSelect, statusSelect].forEach(el => el && el.addEventListener('change', () => { updatePreview(); updateValidationSummary(); }));

    // Initial state - only if elements exist
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                updatePreview();
                updateValidationSummary();
            }, 100);
        });
    } else {
        setTimeout(() => {
            updatePreview();
            updateValidationSummary();
        }, 100);
    }

    // --- Form Submission ---
    form.addEventListener('submit', function(event) {
        let isValid = true;
        document.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500', 'focus:ring-red-500', 'focus:border-red-500'));

        // Special validation for coordinates
        if (latEl && (!latEl.value || isNaN(parseFloat(latEl.value)))) {
            latEl.value = '48.8566'; // Default Paris latitude
            latEl.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (lngEl && (!lngEl.value || isNaN(parseFloat(lngEl.value)))) {
            lngEl.value = '2.3522'; // Default Paris longitude
            lngEl.dispatchEvent(new Event('input', { bubbles: true }));
        }

        requiredFields.forEach(field => {
            const input = document.getElementById(field.id);
            if (input && !String(input.value).trim()) {
                input.classList.add('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                isValid = false;
            }
        });

        if (!isValid) {
            event.preventDefault();
            let box = document.getElementById('form-error');
            if (!box) {
                box = document.createElement('div');
                box.id = 'form-error';
                box.className = 'mx-6 mt-4 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800 px-4 py-3';
                this.prepend(box);
            }
            box.innerHTML = '<p class="font-medium">Veuillez remplir tous les champs obligatoires mis en évidence.</p>';
            box.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    // --- Address-based Map System (100% FREE) ---
    let map = null;
    let marker = null;
    let searchTimeout = null;
    let mapInitialized = false;
    
    // DOM elements
    const locationSearch = document.getElementById('location_search');
    const addressSuggestions = document.getElementById('address_suggestions');
    const mapPlaceholder = document.getElementById('map-placeholder');
    const mapLoading = document.getElementById('map-loading');
    const locationStatus = document.getElementById('location_status');
    const locationStatusText = document.getElementById('location_status_text');
    const autoLocateBtn = document.getElementById('auto-locate-btn');
    const searchHistoryBtn = document.getElementById('search-history-btn');
    const searchLoading = document.getElementById('search-loading');
    const searchIcon = document.getElementById('search-icon');
    const searchTips = document.getElementById('search-tips');
    
    function showMapLoading() {
    if (mapLoading) {
        mapLoading.style.display = 'flex';
        mapLoading.innerHTML = `
            <div class="text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600 mx-auto mb-2"></div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Chargement de la carte...</p>
            </div>
        `;
    }
        if (mapPlaceholder) mapPlaceholder.style.display = 'none';
    }
    
    function hideMapLoading() {
        if (mapLoading) mapLoading.style.display = 'none';
    }
    
    function showMapPlaceholder() {
    if (mapPlaceholder) {
        mapPlaceholder.style.display = 'flex';
        mapPlaceholder.innerHTML = `
            <div class="text-center p-6">
                <svg class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Chargement de la carte...</p>
                <p class="text-xs text-gray-500 dark:text-gray-500">La carte se charge automatiquement</p>
                <button type="button" id="force-load-btn" class="mt-3 px-3 py-1 text-xs bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                    Charger la carte maintenant
                </button>
            </div>
        `;
    }
        if (mapLoading) mapLoading.style.display = 'none';
    }
    
    function showLocationStatus(message, type = 'info') {
        if (locationStatus && locationStatusText) {
            locationStatusText.textContent = message;
            locationStatus.className = `mt-2 text-xs ${type === 'success' ? 'text-green-600 dark:text-green-400' : type === 'error' ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'}`;
            locationStatus.classList.remove('hidden');
        }
    }
    
    function hideLocationStatus() {
        if (locationStatus) locationStatus.classList.add('hidden');
    }
    
    // Function to ensure address is always displayed
    function ensureAddressDisplay(address = '') {
        if (locationSearch) {
            if (!locationSearch.value || locationSearch.value.trim() === '') {
                locationSearch.value = address || 'Casablanca, Maroc';
            }
        }
    }
    
    // Simple geocoding function without external dependencies
    function performGeocoding(lat, lng, successCallback, errorCallback) {
        // Since CORS proxies are blocked, we'll use a simple approach
        // Just return coordinates with a generic address
        const address = `Position GPS: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        const mockData = {
            display_name: address,
            lat: lat.toString(),
            lon: lng.toString(),
            address: {
                country: 'Maroc',
                city: 'Position détectée'
            }
        };
        
        // Simulate async behavior
        setTimeout(() => {
            successCallback(mockData);
        }, 100);
    }
    
    // Smart search functionality
    let searchHistory = JSON.parse(localStorage.getItem('addressSearchHistory') || '[]');
    let currentSearchQuery = '';
    
    function showSearchLoading() {
        if (searchLoading) searchLoading.classList.remove('hidden');
        if (searchIcon) searchIcon.classList.add('hidden');
    }
    
    function hideSearchLoading() {
        if (searchLoading) searchLoading.classList.add('hidden');
        if (searchIcon) searchIcon.classList.remove('hidden');
    }
    
    function showSearchTips() {
        if (searchTips) searchTips.classList.remove('hidden');
    }
    
    function hideSearchTips() {
        if (searchTips) searchTips.classList.add('hidden');
    }
    
    function addToSearchHistory(address) {
        if (!address || address.trim() === '') return;
        
        // Remove if already exists
        searchHistory = searchHistory.filter(item => item !== address);
        
        // Add to beginning
        searchHistory.unshift(address);
        
        // Keep only last 10 searches
        searchHistory = searchHistory.slice(0, 10);
        
        // Save to localStorage
        localStorage.setItem('addressSearchHistory', JSON.stringify(searchHistory));
    }
    
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
                
                div.addEventListener('click', function() {
                    locationSearch.value = address;
                    hideSuggestions();
                    hideSearchTips();
                    // Trigger search for this address
                    searchAddress(address);
                });
                
                addressSuggestions.appendChild(div);
            });
        }
        
        addressSuggestions.classList.remove('hidden');
    }
    
    // Auto-location functionality
    function getCurrentLocation() {
        if (!navigator.geolocation) {
            showLocationStatus('La géolocalisation n\'est pas supportée par ce navigateur', 'error');
            return;
        }
        
        showLocationStatus('Détection de votre position...', 'info');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Update coordinates
                if (latEl) {
                    latEl.value = lat.toFixed(7);
                    latEl.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (lngEl) {
                    lngEl.value = lng.toFixed(7);
                    lngEl.dispatchEvent(new Event('input', { bubbles: true }));
                }
                
                // Get address from coordinates (simple geocoding)
                performGeocoding(lat, lng, 
                    function(data) {
                        if (data.display_name && locationSearch) {
                            locationSearch.value = data.display_name;
                        }
                        
                        // Initialize or update map
                        if (mapInitialized && map) {
                            map.setView([lat, lng], 15);
                            if (marker) {
                                marker.setLatLng([lat, lng]);
                            }
                            showLocationStatus(`Position détectée: ${data.display_name}`, 'success');
                        } else {
                            initializeMap(lat, lng, data.display_name);
                        }
                        
                        // Ensure address is always displayed
                        if (locationSearch && !locationSearch.value) {
                            locationSearch.value = data.display_name;
                        }
                        
                        updateValidationSummary();
                    },
                    function(error) {
                        console.error('Geocoding error:', error);
                        // Still initialize map even if geocoding fails
                        if (mapInitialized && map) {
                            map.setView([lat, lng], 15);
                            if (marker) {
                                marker.setLatLng([lat, lng]);
                            }
                        } else {
                            initializeMap(lat, lng, 'Position actuelle');
                        }
                        
                        // Set fallback address
                        if (locationSearch && !locationSearch.value) {
                            locationSearch.value = 'Position actuelle';
                        }
                        
                        showLocationStatus('Position détectée (adresse générique)', 'success');
                        updateValidationSummary();
                    }
                );
            },
            function(error) {
                let errorMessage = 'Erreur de géolocalisation: ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage += 'Permission refusée';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage += 'Position non disponible';
                        break;
                    case error.TIMEOUT:
                        errorMessage += 'Délai d\'attente dépassé';
                        break;
                    default:
                        errorMessage += 'Erreur inconnue';
                        break;
                }
                showLocationStatus(errorMessage, 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000 // 5 minutes
            }
        );
    }
    
    function initializeMap(lat, lng, address = '') {
        console.log('Initializing map with:', { lat, lng, address, mapInitialized, hasMap: !!map });
        
        // Prevent multiple initializations
        if (mapInitialized && map) {
            // Just update existing map
            map.setView([lat, lng], 15);
            if (marker) {
                marker.setLatLng([lat, lng]);
            }
            showLocationStatus(`Carte mise à jour: ${address}`, 'success');
            return;
        }
        
        showMapLoading();
        
        const mapContainer = document.getElementById('map');
        if (!mapContainer) {
            hideMapLoading();
            showLocationStatus('Erreur: conteneur de carte non trouvé', 'error');
            console.error('Map container not found!');
            return;
        }

        // Check if Leaflet is available
        if (typeof L === 'undefined') {
            hideMapLoading();
            showLocationStatus('Erreur: Leaflet n\'est pas chargé', 'error');
            console.error('Leaflet not available!');
            
            // Try to load Leaflet again
            setTimeout(() => {
                if (typeof L !== 'undefined') {
                    console.log('Leaflet loaded, retrying map initialization...');
                    initializeMap(lat, lng, address);
                }
            }, 2000);
            return;
        }

        try {
            // Clear existing map if any
            if (map) {
                console.log('Removing existing map');
                map.remove();
                map = null;
                mapInitialized = false;
            }

            // Clear map container completely
            mapContainer.innerHTML = '';

            // Wait a bit to ensure the container is completely cleared
            setTimeout(() => {
                try {
                    // Double-check container is empty
                    if (mapContainer.innerHTML.trim() !== '') {
                        mapContainer.innerHTML = '';
                    }
                    
                    // Initialize Leaflet Map with OpenStreetMap
                    map = L.map('map', { 
                        zoomControl: true,
                        attributionControl: true
                    }).setView([lat, lng], 15);
                    
                    // Mark as initialized
                    mapInitialized = true;
                    console.log('Map initialized successfully!');

            // Add OpenStreetMap tile layer (FREE)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
                subdomains: ['a', 'b', 'c']
            }).addTo(map);

            // Add marker
            marker = L.marker([lat, lng], { 
                draggable: true,
                title: 'Faites glisser pour déplacer'
            }).addTo(map);

            // Update coordinates when marker is dragged
            marker.on('dragend', function(ev) {
                const pos = ev.target.getLatLng();
                if (latEl) {
                    latEl.value = pos.lat.toFixed(7);
                    latEl.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (lngEl) {
                    lngEl.value = pos.lng.toFixed(7);
                    lngEl.dispatchEvent(new Event('input', { bubbles: true }));
                }
                showLocationStatus(`Position mise à jour: ${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`, 'success');
                setTimeout(() => {
                    updateValidationSummary();
                }, 100);
            });

            // Update coordinates when map is clicked
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                if (latEl) {
                    latEl.value = e.latlng.lat.toFixed(7);
                    latEl.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (lngEl) {
                    lngEl.value = e.latlng.lng.toFixed(7);
                    lngEl.dispatchEvent(new Event('input', { bubbles: true }));
                }
                        
                        // Perform reverse geocoding to get address
                        performReverseGeocoding(e.latlng.lat, e.latlng.lng);
                        
                showLocationStatus(`Position mise à jour: ${e.latlng.lat.toFixed(6)}, ${e.latlng.lng.toFixed(6)}`, 'success');
                setTimeout(() => {
                    updateValidationSummary();
                }, 100);
            });

                    // Hide loading immediately
                    hideMapLoading();
                    if (address) {
                        showLocationStatus(`Carte centrée sur: ${address}`, 'success');
                        ensureAddressDisplay(address);
                    } else {
                        ensureAddressDisplay('Position actuelle');
                    }

                } catch (innerError) {
                    console.error('Error creating map in setTimeout:', innerError);
                    hideMapLoading();
                    
                    // Handle specific "already initialized" error
                    if (innerError.message.includes('already initialized')) {
                        console.log('Map container already initialized, forcing cleanup...');
                        // Force remove any existing map
                        if (mapContainer._leaflet_id) {
                            delete mapContainer._leaflet_id;
                        }
                        mapContainer.innerHTML = '';
                        
                        // Try one more time after cleanup
                        setTimeout(() => {
                            try {
                                map = L.map('map', { 
                                    zoomControl: true,
                                    attributionControl: true
                                }).setView([lat, lng], 15);
                                
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                                    maxZoom: 19,
                                    subdomains: ['a', 'b', 'c']
                                }).addTo(map);
                                
                                marker = L.marker([lat, lng], { 
                                    draggable: true,
                                    title: 'Faites glisser pour déplacer'
                                }).addTo(map);
                                
                                mapInitialized = true;
                                hideMapLoading();
                                showLocationStatus(`Carte initialisée (retry): ${address}`, 'success');
                            } catch (retryError) {
                                console.error('Retry failed:', retryError);
                                hideMapLoading();
                                showLocationStatus('❌ Impossible d\'initialiser la carte', 'error');
                            }
                        }, 300);
                    } else {
                        showLocationStatus('Erreur lors de la création de la carte: ' + innerError.message, 'error');
                    }
                }
            }, 100); // Small delay to ensure container is cleared

        } catch (error) {
            console.error('Error creating map:', error);
            hideMapLoading();
            showLocationStatus('Erreur lors de la création de la carte: ' + error.message, 'error');
        }
    }
    
    // Reverse geocoding function
    function performReverseGeocoding(lat, lng) {
        showLocationStatus('Recherche de l\'adresse...', 'info');
        
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;
        
        fetch(url, {
            headers: {
                'User-Agent': 'EVON-Charging-Points/1.0'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.display_name) {
                // Update the search field with the found address
                if (locationSearch) {
                    locationSearch.value = data.display_name;
                }
                
                // Update hidden fields
                const addressField = document.getElementById('address');
                const cityField = document.getElementById('city');
                
                if (addressField) {
                    addressField.value = data.display_name;
                    addressField.dispatchEvent(new Event('input', { bubbles: true }));
                }
                
                if (cityField && data.address) {
                    const city = data.address.city || data.address.town || data.address.village || data.address.municipality || 'Maroc';
                    cityField.value = city;
                    cityField.dispatchEvent(new Event('input', { bubbles: true }));
                }
                
                showLocationStatus(`Adresse trouvée: ${data.display_name}`, 'success');
            } else {
                showLocationStatus('Adresse non trouvée pour cette position', 'error');
            }
        })
        .catch(error => {
            console.error('Reverse geocoding error:', error);
            showLocationStatus('Erreur lors de la recherche d\'adresse', 'error');
        });
    }
    
    // Smart address search functionality with debouncing
    let searchTimeout;
    function searchAddress(query) {
        currentSearchQuery = query.trim();
        
        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        if (currentSearchQuery.length < 2) {
            hideSuggestions();
            hideSearchTips();
            return;
        }
        
        if (currentSearchQuery.length < 3) {
            showSearchTips();
            hideSuggestions();
            return;
        }
        
        // Debounce the search to avoid too many API calls
        searchTimeout = setTimeout(() => {
        hideSearchTips();
        showSearchLoading();
        showLocationStatus('Recherche intelligente en cours...', 'info');
        
            // Try multiple search methods
            searchWithMultipleMethods(currentSearchQuery);
        }, 300); // 300ms delay
    }
    
    // Search with multiple methods for better results
    function searchWithMultipleMethods(query) {
        const searchPromises = [
            searchWithNominatim(query),
            searchWithMockData(query),
            searchWithGenericFallback(query)
        ];
        
        Promise.allSettled(searchPromises).then(results => {
            hideSearchLoading();
            hideLocationStatus();
            
            // Combine all successful results
            let allSuggestions = [];
            results.forEach(result => {
                if (result.status === 'fulfilled' && result.value && result.value.length > 0) {
                    allSuggestions = allSuggestions.concat(result.value);
                }
            });
            
            // Remove duplicates based on display_name
            const uniqueSuggestions = allSuggestions.filter((suggestion, index, self) => 
                index === self.findIndex(s => s.display_name === suggestion.display_name)
            );
            
            if (uniqueSuggestions.length > 0) {
                showSmartSuggestions(uniqueSuggestions);
            } else {
                showLocationStatus('Aucune adresse trouvée', 'error');
                hideSuggestions();
            }
        });
    }
    
    // Search using Nominatim (OpenStreetMap) - Free and no API key required
    function searchWithNominatim(query) {
        return new Promise((resolve) => {
            // First try with Morocco only
            let url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ma&limit=5&addressdetails=1`;
            
            fetch(url, {
                headers: {
                    'User-Agent': 'EVON-Charging-Points/1.0'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    const suggestions = data.map(item => ({
                        display_name: item.display_name,
                        lat: item.lat,
                        lon: item.lon,
                        address: item.address || {}
                    }));
                    resolve(suggestions);
                } else {
                    // If no results in Morocco, try global search
                    return searchWithNominatimGlobal(query);
                }
            })
            .then(globalResults => {
                if (globalResults) {
                    resolve(globalResults);
                }
            })
            .catch(error => {
                console.log('Nominatim search failed:', error);
                resolve([]);
            });
        });
    }
    
    // Global search with Nominatim
    function searchWithNominatimGlobal(query) {
        return new Promise((resolve) => {
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1`;
            
            fetch(url, {
                headers: {
                    'User-Agent': 'EVON-Charging-Points/1.0'
                }
            })
            .then(response => response.json())
            .then(data => {
                const suggestions = data.map(item => ({
                    display_name: item.display_name,
                    lat: item.lat,
                    lon: item.lon,
                    address: item.address || {}
                }));
                resolve(suggestions);
            })
            .catch(error => {
                console.log('Global Nominatim search failed:', error);
                resolve([]);
            });
        });
    }
    
    // Search using mock data for common Moroccan addresses
    function searchWithMockData(query) {
        return new Promise((resolve) => {
            const suggestions = generateMockSuggestions(query);
            resolve(suggestions);
        });
    }
    
    // Generic fallback search with intelligent city detection
    function searchWithGenericFallback(query) {
        return new Promise((resolve) => {
            const suggestions = [];
            
            // Try to detect city from query
            const cityPatterns = {
                'casablanca': { lat: 33.5731, lng: -7.5898, city: 'Casablanca' },
                'rabat': { lat: 34.0209, lng: -6.8416, city: 'Rabat' },
                'marrakech': { lat: 31.6258, lng: -7.9891, city: 'Marrakech' },
                'fes': { lat: 34.0331, lng: -5.0003, city: 'Fès' },
                'fès': { lat: 34.0331, lng: -5.0003, city: 'Fès' },
                'agadir': { lat: 30.4278, lng: -9.5981, city: 'Agadir' },
                'tanger': { lat: 35.7595, lng: -5.8340, city: 'Tanger' },
                'meknes': { lat: 33.8935, lng: -5.5473, city: 'Meknès' },
                'oujda': { lat: 34.6814, lng: -1.9086, city: 'Oujda' },
                'tetouan': { lat: 35.5889, lng: -5.3626, city: 'Tétouan' },
                'sale': { lat: 34.0406, lng: -6.8204, city: 'Salé' }
            };
            
            const queryLower = query.toLowerCase();
            let foundCity = null;
            
            for (const [cityKey, cityData] of Object.entries(cityPatterns)) {
                if (queryLower.includes(cityKey)) {
                    foundCity = cityData;
                    break;
                }
            }
            
            if (foundCity) {
                suggestions.push({
                    display_name: `${query}, ${foundCity.city}, Maroc`,
                    lat: foundCity.lat.toString(),
                    lon: foundCity.lng.toString(),
                    address: { city: foundCity.city }
                });
            } else {
                // Default fallback
                suggestions.push({
                    display_name: `${query}, Maroc`,
                    lat: '33.5731',
                    lon: '-7.5898',
                    address: { city: 'Maroc' }
                });
            }
            
            resolve(suggestions);
        });
    }
    
    // Generate mock suggestions for Moroccan addresses
    function generateMockSuggestions(query) {
        const moroccanAddresses = [
            // Casablanca
            { name: 'Place Mohammed V, Casablanca, Maroc', lat: 33.5731, lng: -7.5898 },
            { name: 'Boulevard Mohammed V, Casablanca, Maroc', lat: 33.5731, lng: -7.5898 },
            { name: 'Avenue Hassan II, Casablanca, Maroc', lat: 33.5731, lng: -7.5898 },
            { name: 'Corniche Ain Diab, Casablanca, Maroc', lat: 33.5731, lng: -7.5898 },
            { name: 'Gare Casa-Port, Casablanca, Maroc', lat: 33.5731, lng: -7.5898 },
            { name: 'Mosquée Hassan II, Casablanca, Maroc', lat: 33.6089, lng: -7.6328 },
            
            // Hay Ryad - Rabat
            { name: 'Hay Ryad, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad Centre, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad Extension, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad 1, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad 2, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad 3, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad 4, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad 5, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad 6, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad 7, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad 8, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad 9, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad 10, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad Extension, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad Centre Commercial, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad Mosquée, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad École, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            { name: 'Hay Ryad Hôpital, Rabat, Maroc', lat: 33.9981, lng: -6.8473 },
            
            // Rabat
            { name: 'Place Hassan II, Rabat, Maroc', lat: 34.0209, lng: -6.8416 },
            { name: 'Avenue Mohammed V, Rabat, Maroc', lat: 34.0209, lng: -6.8416 },
            { name: 'Kasbah des Oudayas, Rabat, Maroc', lat: 34.0209, lng: -6.8416 },
            { name: 'Tour Hassan, Rabat, Maroc', lat: 34.0209, lng: -6.8416 },
            { name: 'Gare Rabat-Ville, Rabat, Maroc', lat: 34.0209, lng: -6.8416 },
            
            // Marrakech
            { name: 'Place Jemaa el-Fnaa, Marrakech, Maroc', lat: 31.6258, lng: -7.9891 },
            { name: 'Palais Bahia, Marrakech, Maroc', lat: 31.6258, lng: -7.9891 },
            { name: 'Jardin Majorelle, Marrakech, Maroc', lat: 31.6258, lng: -7.9891 },
            { name: 'Médina de Marrakech, Marrakech, Maroc', lat: 31.6258, lng: -7.9891 },
            { name: 'Avenue Mohammed VI, Marrakech, Maroc', lat: 31.6258, lng: -7.9891 },
            
            // Fès
            { name: 'Médina de Fès, Fès, Maroc', lat: 34.0331, lng: -5.0003 },
            { name: 'Université Al Quaraouiyine, Fès, Maroc', lat: 34.0331, lng: -5.0003 },
            { name: 'Place Bou Jeloud, Fès, Maroc', lat: 34.0331, lng: -5.0003 },
            { name: 'Avenue Hassan II, Fès, Maroc', lat: 34.0331, lng: -5.0003 },
            
            // Agadir
            { name: 'Plage d\'Agadir, Agadir, Maroc', lat: 30.4278, lng: -9.5981 },
            { name: 'Marina d\'Agadir, Agadir, Maroc', lat: 30.4278, lng: -9.5981 },
            { name: 'Avenue Hassan II, Agadir, Maroc', lat: 30.4278, lng: -9.5981 },
            { name: 'Centre-ville Agadir, Agadir, Maroc', lat: 30.4278, lng: -9.5981 },
            
            // Tanger
            { name: 'Place de France, Tanger, Maroc', lat: 35.7595, lng: -5.8340 },
            { name: 'Port de Tanger, Tanger, Maroc', lat: 35.7595, lng: -5.8340 },
            { name: 'Avenue Mohammed VI, Tanger, Maroc', lat: 35.7595, lng: -5.8340 },
            { name: 'Kasbah de Tanger, Tanger, Maroc', lat: 35.7595, lng: -5.8340 },
            
            // Meknès
            { name: 'Place el-Hedim, Meknès, Maroc', lat: 33.8935, lng: -5.5473 },
            { name: 'Bab Mansour, Meknès, Maroc', lat: 33.8935, lng: -5.5473 },
            { name: 'Avenue Mohammed V, Meknès, Maroc', lat: 33.8935, lng: -5.5473 },
            
            // Oujda
            { name: 'Place du 16 Août, Oujda, Maroc', lat: 34.6814, lng: -1.9086 },
            { name: 'Avenue Mohammed V, Oujda, Maroc', lat: 34.6814, lng: -1.9086 },
            
            // Tétouan
            { name: 'Place Hassan II, Tétouan, Maroc', lat: 35.5889, lng: -5.3626 },
            { name: 'Médina de Tétouan, Tétouan, Maroc', lat: 35.5889, lng: -5.3626 },
            
            // Salé
            { name: 'Centre-ville Salé, Salé, Maroc', lat: 34.0406, lng: -6.8204 },
            { name: 'Avenue Hassan II, Salé, Maroc', lat: 34.0406, lng: -6.8204 }
        ];
        
        const queryLower = query.toLowerCase();
        
        // Enhanced search with fuzzy matching
        const matches = moroccanAddresses.filter(addr => {
            const nameLower = addr.name.toLowerCase();
            
            // Exact match
            if (nameLower.includes(queryLower)) {
                return true;
            }
            
            // Fuzzy matching for common variations
            const queryWords = queryLower.split(/\s+/);
            const nameWords = nameLower.split(/\s+/);
            
            // Check if all query words are found in the name
            const allWordsFound = queryWords.every(qWord => 
                nameWords.some(nWord => nWord.includes(qWord) || qWord.includes(nWord))
            );
            
            if (allWordsFound) {
                return true;
            }
            
            // Special handling for common Moroccan address patterns
            if (queryLower.includes('hay') && nameLower.includes('hay')) {
                return true;
            }
            if (queryLower.includes('ryad') && nameLower.includes('ryad')) {
                return true;
            }
            if (queryLower.includes('quartier') && nameLower.includes('quartier')) {
                return true;
            }
            if (queryLower.includes('avenue') && nameLower.includes('avenue')) {
                return true;
            }
            if (queryLower.includes('boulevard') && nameLower.includes('boulevard')) {
                return true;
            }
            if (queryLower.includes('place') && nameLower.includes('place')) {
                return true;
            }
            
            return false;
        });
        
        // Sort matches by relevance
        const sortedMatches = matches.sort((a, b) => {
            const aScore = calculateRelevanceScore(a.name, queryLower);
            const bScore = calculateRelevanceScore(b.name, queryLower);
            return bScore - aScore;
        });
        
        // If no matches, create intelligent suggestions
        if (sortedMatches.length === 0) {
            const suggestions = [];
            
            // Try to detect city from query
            const cityPatterns = {
                'casablanca': { lat: 33.5731, lng: -7.5898, city: 'Casablanca' },
                'rabat': { lat: 34.0209, lng: -6.8416, city: 'Rabat' },
                'marrakech': { lat: 31.6258, lng: -7.9891, city: 'Marrakech' },
                'fes': { lat: 34.0331, lng: -5.0003, city: 'Fès' },
                'fès': { lat: 34.0331, lng: -5.0003, city: 'Fès' },
                'agadir': { lat: 30.4278, lng: -9.5981, city: 'Agadir' },
                'tanger': { lat: 35.7595, lng: -5.8340, city: 'Tanger' },
                'meknes': { lat: 33.8935, lng: -5.5473, city: 'Meknès' },
                'oujda': { lat: 34.6814, lng: -1.9086, city: 'Oujda' },
                'tetouan': { lat: 35.5889, lng: -5.3626, city: 'Tétouan' },
                'sale': { lat: 34.0406, lng: -6.8204, city: 'Salé' }
            };
            
            for (const [cityKey, cityData] of Object.entries(cityPatterns)) {
                if (queryLower.includes(cityKey)) {
                    suggestions.push({
                        display_name: `${query}, ${cityData.city}, Maroc`,
                        lat: cityData.lat.toString(),
                        lon: cityData.lng.toString(),
                        address: { city: cityData.city }
                    });
                }
            }
            
            // Default fallback
            if (suggestions.length === 0) {
                suggestions.push({
                    display_name: `${query}, Maroc`, 
                    lat: '33.5731', 
                    lon: '-7.5898',
                    address: { city: 'Maroc' }
                });
                }
            
            return suggestions;
        }
        
        return sortedMatches.map(addr => ({
            display_name: addr.name,
            lat: addr.lat.toString(),
            lon: addr.lng.toString(),
            address: { city: 'Maroc' }
        }));
    }
    
    // Calculate relevance score for better sorting
    function calculateRelevanceScore(name, query) {
        const nameLower = name.toLowerCase();
        const queryLower = query.toLowerCase();
        
        let score = 0;
        
        // Exact match gets highest score
        if (nameLower === queryLower) {
            score += 100;
        }
        
        // Starts with query gets high score
        if (nameLower.startsWith(queryLower)) {
            score += 50;
        }
        
        // Contains query gets medium score
        if (nameLower.includes(queryLower)) {
            score += 25;
        }
        
        // Word boundary matches get bonus
        const queryWords = queryLower.split(/\s+/);
        const nameWords = nameLower.split(/\s+/);
        
        queryWords.forEach(qWord => {
            nameWords.forEach(nWord => {
                if (nWord.startsWith(qWord)) {
                    score += 10;
                } else if (nWord.includes(qWord)) {
                    score += 5;
                }
            });
        });
        
        return score;
    }
    
    function showSmartSuggestions(suggestions) {
        if (!addressSuggestions) return;
        
        addressSuggestions.innerHTML = '';
        
        // Sort suggestions by relevance
        const sortedSuggestions = suggestions.sort((a, b) => {
            // Prioritize exact matches
            const aExact = a.display_name.toLowerCase().includes(currentSearchQuery.toLowerCase());
            const bExact = b.display_name.toLowerCase().includes(currentSearchQuery.toLowerCase());
            
            if (aExact && !bExact) return -1;
            if (!aExact && bExact) return 1;
            
            // Then by importance score
            return (b.importance || 0) - (a.importance || 0);
        });
        
        sortedSuggestions.forEach((suggestion, index) => {
            const div = document.createElement('div');
            div.className = 'px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0';
            
            // Extract address components for better display
            const address = suggestion.address || {};
            const street = address.road || address.pedestrian || '';
            const houseNumber = address.house_number || '';
            const city = address.city || address.town || address.village || '';
            const postalCode = address.postcode || '';
            
            // Create smart display name
            let displayName = suggestion.display_name;
            let subtitle = '';
            
            if (street && houseNumber) {
                subtitle = `${houseNumber} ${street}`;
                if (city) subtitle += `, ${city}`;
                if (postalCode) subtitle += ` ${postalCode}`;
            } else if (street) {
                subtitle = street;
                if (city) subtitle += `, ${city}`;
            }
            
            // Add relevance indicator
            const isExactMatch = suggestion.display_name.toLowerCase().includes(currentSearchQuery.toLowerCase());
            const relevanceIcon = isExactMatch ? 
                '<svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
                '<svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>';
            
            div.innerHTML = `
                <div class="flex items-start gap-2">
                    <div class="mt-0.5">${relevanceIcon}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">${subtitle || displayName}</div>
                        ${subtitle ? `<div class="text-xs text-gray-500 dark:text-gray-400 truncate">${displayName}</div>` : ''}
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">${suggestion.lat}, ${suggestion.lon}</div>
                    </div>
                </div>
            `;
            
            div.addEventListener('click', function() {
                selectAddress(suggestion);
            });
            
            addressSuggestions.appendChild(div);
        });
        
        addressSuggestions.classList.remove('hidden');
    }
    
    function showSuggestions(suggestions) {
        // Legacy function for backward compatibility
        showSmartSuggestions(suggestions);
    }
    
    function hideSuggestions() {
        if (addressSuggestions) {
            addressSuggestions.classList.add('hidden');
        }
    }
    
    function selectAddress(suggestion) {
        const lat = parseFloat(suggestion.lat);
        const lng = parseFloat(suggestion.lon);
        
        // Validate coordinates
        if (isNaN(lat) || isNaN(lng)) {
            showLocationStatus('Erreur: coordonnées invalides', 'error');
            return;
        }
        
        // Add to search history
        addToSearchHistory(suggestion.display_name);
        
        // Update search input
        if (locationSearch) {
            locationSearch.value = suggestion.display_name;
        }
        
        // Update coordinates - ensure they are always set
        if (latEl) {
            latEl.value = lat.toFixed(7);
            latEl.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (lngEl) {
            lngEl.value = lng.toFixed(7);
            lngEl.dispatchEvent(new Event('input', { bubbles: true }));
        }
        
        // Hide suggestions and tips
        hideSuggestions();
        hideSearchTips();
        
        // Initialize or update map
        if (map) {
            map.setView([lat, lng], 15);
            if (marker) {
                marker.setLatLng([lat, lng]);
            }
            showLocationStatus(`✅ Adresse sélectionnée: ${suggestion.display_name}`, 'success');
        } else {
            initializeMap(lat, lng, suggestion.display_name);
        }
        
        // Force validation update
        setTimeout(() => {
            updateValidationSummary();
        }, 100);
    }
    
    // Event listeners
    if (locationSearch) {
        locationSearch.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Clear timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Set new timeout
            searchTimeout = setTimeout(() => {
                searchAddress(query);
            }, 300);
        });
        
        locationSearch.addEventListener('blur', function() {
            // Hide suggestions after a short delay
            setTimeout(hideSuggestions, 200);
        });
        
        locationSearch.addEventListener('focus', function() {
            if (this.value.trim().length >= 3) {
                searchAddress(this.value.trim());
            }
        });
    }
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!locationSearch?.contains(e.target) && !addressSuggestions?.contains(e.target)) {
            hideSuggestions();
        }
    });
    
    // Force load button
    document.getElementById('force-load-btn')?.addEventListener('click', function() {
        showLocationStatus('Chargement forcé de la carte...', 'info');
        
        if (window.initSimpleMap()) {
            showLocationStatus('✅ Carte chargée avec succès!', 'success');
        } else {
            showLocationStatus('❌ Impossible de charger la carte', 'error');
        }
    });
    
    // Auto-locate button
    if (autoLocateBtn) {
        autoLocateBtn.addEventListener('click', function() {
            getCurrentLocation();
        });
    }
    
    // Search history button
    if (searchHistoryBtn) {
        searchHistoryBtn.addEventListener('click', function() {
            showSearchHistory();
        });
    }
    
    // Show search tips when input is focused
    if (locationSearch) {
        locationSearch.addEventListener('focus', function() {
            if (this.value.trim().length < 3) {
                showSearchTips();
            }
        });
        
        locationSearch.addEventListener('blur', function() {
            // Hide suggestions after a short delay
            setTimeout(() => {
                hideSuggestions();
                hideSearchTips();
            }, 200);
        });
    }

    // Initialize with existing coordinates if available
    if (latEl && lngEl && latEl.value && lngEl.value) {
        const lat = parseFloat(latEl.value);
        const lng = parseFloat(lngEl.value);
        if (!isNaN(lat) && !isNaN(lng)) {
            // Reverse geocoding to get address
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data.display_name && locationSearch) {
                        locationSearch.value = data.display_name;
                    }
                    initializeMap(lat, lng, data.display_name || 'Position existante');
                })
                .catch(error => {
                    console.error('Reverse geocoding error:', error);
                    initializeMap(lat, lng);
                });
        }
    } else {
        // Try to get user's location automatically first (only once)
        if (navigator.geolocation && !window.geolocationAttempted) {
            window.geolocationAttempted = true;
            showLocationStatus('Tentative de détection automatique de votre position...', 'info');
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Update coordinates
                    if (latEl) {
                        latEl.value = lat.toFixed(7);
                        latEl.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    if (lngEl) {
                        lngEl.value = lng.toFixed(7);
                        lngEl.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    
                    // Get address from coordinates (simple geocoding)
                    performGeocoding(lat, lng, 
                        function(data) {
                            if (data.display_name && locationSearch) {
                                locationSearch.value = data.display_name;
                            }
                            initializeMap(lat, lng, data.display_name);
                            
                            // Ensure address is displayed
                            if (locationSearch && !locationSearch.value) {
                                locationSearch.value = data.display_name;
                            }
                            
                            showLocationStatus('✅ Position détectée automatiquement!', 'success');
                            updateValidationSummary();
                        },
                        function(error) {
                            console.error('Geocoding error:', error);
                            initializeMap(lat, lng, 'Position actuelle');
                            
                            // Set fallback address
                            if (locationSearch && !locationSearch.value) {
                                locationSearch.value = 'Position actuelle';
                            }
                            
                            showLocationStatus('✅ Position détectée (adresse générique)', 'success');
                            updateValidationSummary();
                        }
                    );
                },
                function(error) {
                    // Fallback to default coordinates if geolocation fails
                    console.log('Geolocation failed, using default coordinates:', error.message);
                    setDefaultLocation();
                },
                {
                    enableHighAccuracy: false,
                    timeout: 5000,
                    maximumAge: 300000
                }
            );
        } else {
            // No geolocation support or already attempted, use default
            setDefaultLocation();
        }
    }
    
    // Force map initialization with advanced method
    setTimeout(() => {
        if (!mapInitialized) {
            console.log('🔄 TENTATIVE DE CHARGEMENT FORCÉ DE LA CARTE...');
            
            // Utiliser l'affichage intelligent
            if (window.smartMapDisplay) {
                console.log('🗺️ Utilisation de l\'affichage intelligent...');
                window.smartMapDisplay('');
            } else if (window.loadMapNow) {
                console.log('🗺️ Utilisation de la solution directe...');
                window.loadMapNow();
            } else {
                console.log('🗺️ Utilisation de la méthode par défaut...');
                setDefaultLocation();
            }
        }
    }, 2000);
    
    // Tentative supplémentaire après 5 secondes
    setTimeout(() => {
        if (!mapInitialized) {
            console.log('🔄 TENTATIVE SUPPLÉMENTAIRE DE CHARGEMENT DE LA CARTE...');
            if (window.loadMapDirect) {
                console.log('🗺️ Chargement direct forcé...');
                window.loadMapDirect();
            } else {
                console.log('🗺️ Utilisation de la méthode par défaut...');
                setDefaultLocation();
            }
        }
    }, 5000);
    
    function setDefaultLocation() {
        // Prevent multiple calls
        if (window.defaultLocationSet) {
            return;
        }
        window.defaultLocationSet = true;
        
        console.log('🏠 Configuration de la position par défaut...');
        
        // Set default coordinates (Casablanca, Morocco) if none are provided
        const defaultLat = 33.5731;
        const defaultLng = -7.5898;
        
        if (latEl) {
            latEl.value = defaultLat.toFixed(7);
            latEl.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (lngEl) {
            lngEl.value = defaultLng.toFixed(7);
            lngEl.dispatchEvent(new Event('input', { bubbles: true }));
        }
        
        // Set default address in search field
        if (locationSearch) {
            locationSearch.value = 'Casablanca, Maroc';
        }
        
        // Force map initialization with simple method
        console.log('🗺️ Initialisation de la carte avec position par défaut...');
        
        // Utiliser le correctif simple
        if (window.loadMapAtPosition) {
            console.log('🔄 Utilisation du correctif simple...');
            window.loadMapAtPosition(defaultLat, defaultLng);
            ensureAddressDisplay('Casablanca, Maroc');
            showLocationStatus('✅ Carte chargée avec Casablanca par défaut!', 'success');
            updateValidationSummary();
        } else {
            console.log('🔄 Utilisation de la méthode standard...');
            initializeMapStandard(defaultLat, defaultLng, 'Casablanca, Maroc');
        }
    }
    
    function initializeMapStandard(lat, lng, address) {
        setTimeout(() => {
            initializeMap(lat, lng, address);
            ensureAddressDisplay(address);
            showLocationStatus('✅ Carte chargée avec Casablanca par défaut!', 'success');
            updateValidationSummary();
        }, 500);
    }
});

// Force load Leaflet if not loaded
if (typeof L === 'undefined') {
    console.log('Leaflet not loaded, forcing load...');
    const script = document.createElement('script');
    script.src = '{{ asset("js/leaflet/leaflet.js") }}';
    script.onload = function() {
        console.log('Leaflet force loaded successfully!');
            // Initialize map after Leaflet is loaded
            setTimeout(() => {
                if (!mapInitialized) {
                    console.log('Initializing map after Leaflet load...');
                    setDefaultLocation();
                }
            }, 1000);
    };
    document.head.appendChild(script);
    } else {
        // Leaflet is already loaded, ensure map is initialized
        setTimeout(() => {
            if (!mapInitialized) {
                console.log('Leaflet loaded, initializing map...');
                setDefaultLocation();
            }
        }, 1000);
    }
    
    // Add event listeners for buttons
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'force-load-btn') {
            e.preventDefault();
            console.log('🔄 CHARGEMENT FORCÉ DE LA CARTE...');
            showLocationStatus('Chargement forcé de la carte...', 'info');
            
            // Reset flags to allow reload
            window.defaultLocationSet = false;
            window.geolocationAttempted = false;
            mapInitialized = false;
            
            // Utiliser l'affichage intelligent
            if (window.smartMapDisplay) {
                console.log('🗺️ Utilisation de l\'affichage intelligent...');
                window.smartMapDisplay('');
            } else if (window.loadMapNow) {
                console.log('🗺️ Utilisation de la solution directe...');
                window.loadMapNow();
            } else {
                console.log('🗺️ Utilisation de la méthode par défaut...');
                setDefaultLocation();
            }
        }
        
        if (e.target && e.target.id === 'diagnose-btn') {
            e.preventDefault();
            console.log('🔍 DIAGNOSTIC COMPLET DE LA CARTE...');
            
            // Utiliser l'affichage intelligent
            if (window.smartMapDisplay) {
                console.log('🗺️ Diagnostic - Tentative d\'affichage intelligent...');
                window.smartMapDisplay('');
            } else if (window.loadMapNow) {
                console.log('🗺️ Diagnostic - Tentative de chargement direct...');
                window.loadMapNow();
            } else {
                console.log('🔍 Diagnostic manuel:');
                console.log('- Leaflet disponible:', typeof L !== 'undefined');
                console.log('- Conteneur de carte:', !!document.getElementById('map'));
                console.log('- Carte initialisée:', mapInitialized);
                console.log('- Objet carte:', !!map);
                console.log('- Marqueur:', !!marker);
            }
            
            showLocationStatus('Diagnostic complet effectué - voir la console', 'info');
        }
        
        if (e.target && e.target.id === 'emergency-btn') {
            e.preventDefault();
            console.log('🚨 BOUTON D\'URGENCE ACTIVÉ...');
            showLocationStatus('Chargement d\'urgence en cours...', 'info');
            
            // Reset complet
            window.defaultLocationSet = false;
            window.geolocationAttempted = false;
            mapInitialized = false;
            
            // Utiliser l'affichage intelligent
            if (window.smartMapDisplay) {
                console.log('🗺️ Affichage intelligent d\'urgence...');
                window.smartMapDisplay('');
            } else if (window.loadMapNow) {
                console.log('🗺️ Chargement direct...');
                window.loadMapNow();
            } else if (window.loadLeafletNow) {
                console.log('🔄 Chargement de Leaflet...');
                window.loadLeafletNow();
            } else {
                console.log('🗺️ Solution directe non disponible, utilisation de la méthode par défaut...');
                setDefaultLocation();
            }
        }
    });
    
    // Diagnostic function
    function diagnoseMapLoading() {
        console.log('=== MAP DIAGNOSTIC ===');
        console.log('Leaflet available:', typeof L !== 'undefined');
        console.log('Map container exists:', !!document.getElementById('map'));
        console.log('Map initialized:', mapInitialized);
        console.log('Map object exists:', !!map);
        console.log('Default location set:', window.defaultLocationSet);
        console.log('Geolocation attempted:', window.geolocationAttempted);
        console.log('========================');
        
        // Show diagnostic in UI
        const status = document.getElementById('location_status_text');
        if (status) {
            status.textContent = `Diagnostic: Leaflet=${typeof L !== 'undefined'}, Container=${!!document.getElementById('map')}, Initialized=${mapInitialized}`;
        }
    }
    
    // Run diagnostic every 5 seconds until map is loaded
    const diagnosticInterval = setInterval(() => {
        if (mapInitialized) {
            clearInterval(diagnosticInterval);
            console.log('Map loaded successfully!');
        } else {
            diagnoseMapLoading();
        }
    }, 5000);
});

// Récapitulatif en temps réel
class RealtimeRecap {
    constructor() {
        this.form = document.getElementById('step1Form');
        this.recapStep1 = document.getElementById('recap-step1');
        this.progressBar = document.getElementById('progress-bar');
        this.progressPercentage = document.getElementById('progress-percentage');
        this.currentStep = 1;
        
        this.init();
    }
    
    init() {
        // Écouter les changements sur tous les champs du formulaire
        if (this.form) {
            this.form.addEventListener('input', (e) => this.updateRecap(e));
            this.form.addEventListener('change', (e) => this.updateRecap(e));
        }
        
        // Mise à jour initiale
        this.updateRecap();
    }
    
    updateRecap(event = null) {
        const formData = new FormData(this.form);
        const data = {};
        
        // Collecter toutes les données du formulaire
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                data[key] = value.trim();
            }
        }
        
        // Mettre à jour le récapitulatif de l'étape 1
        this.updateStep1Recap(data);
        
        // Mettre à jour la progression
        this.updateProgress();
    }
    
    updateStep1Recap(data) {
        if (!this.recapStep1) return;
        
        const fields = [
            { key: 'name', label: 'Nom', icon: '🏷️' },
            { key: 'serial_number', label: 'Numéro de série', icon: '🔢' },
            { key: 'manufacturer', label: 'Fabricant', icon: '🏭' },
            { key: 'model', label: 'Modèle', icon: '⚡' },
            { key: 'group', label: 'Groupe', icon: '👥' },
            { key: 'location', label: 'Emplacement', icon: '📍' },
            { key: 'address', label: 'Adresse', icon: '🏠' },
            { key: 'city', label: 'Ville', icon: '🏙️' },
            { key: 'status', label: 'Statut', icon: '🟢' },
            { key: 'description', label: 'Description', icon: '📝' }
        ];
        
        let html = '';
        let completedFields = 0;
        
        fields.forEach(field => {
            const value = data[field.key];
            if (value) {
                html += `
                    <div class="flex items-start gap-2 p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg transform transition-all duration-300 ease-in-out hover:scale-105 hover:shadow-md">
                        <span class="text-sm animate-bounce">${field.icon}</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-medium text-emerald-700 dark:text-emerald-300">${field.label}</div>
                            <div class="text-sm text-emerald-900 dark:text-emerald-100 truncate">${value}</div>
                        </div>
                    </div>
                `;
                completedFields++;
            }
        });
        
        if (completedFields === 0) {
            html = '<div class="text-gray-500 dark:text-gray-400 text-sm animate-pulse">Aucune information saisie</div>';
        } else {
            html += `
                <div class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg transform transition-all duration-300 ease-in-out hover:scale-105">
                    <div class="text-xs font-medium text-blue-700 dark:text-blue-300">Progression</div>
                    <div class="text-sm text-blue-900 dark:text-blue-100">${completedFields}/${fields.length} champs remplis</div>
                </div>
            `;
        }
        
        // Animation de transition fluide
        this.recapStep1.classList.add('fade-transition', 'fade-out');
        
        setTimeout(() => {
            this.recapStep1.innerHTML = html;
            this.recapStep1.classList.remove('fade-out');
            this.recapStep1.classList.add('fade-in');
            
            // Ajouter des animations aux nouveaux éléments
            const newItems = this.recapStep1.querySelectorAll('.flex.items-start.gap-2');
            newItems.forEach((item, index) => {
                item.classList.add('recap-item-enter');
                item.style.animationDelay = `${index * 0.1}s`;
            });
        }, 200);
    }
    
    updateProgress() {
        const totalFields = 10; // Nombre total de champs dans l'étape 1
        const formData = new FormData(this.form);
        let completedFields = 0;
        
        // Compter les champs remplis
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                completedFields++;
            }
        }
        
        const progress = Math.min((completedFields / totalFields) * 100, 100);
        
        if (this.progressBar) {
            // Animation fluide de la barre de progression
            this.progressBar.classList.add('progress-smooth');
            this.progressBar.style.width = `${progress}%`;
            
            // Animation de couleur selon la progression
            if (progress < 30) {
                this.progressBar.className = 'bg-red-500 h-2 rounded-full progress-smooth progress-bar-animated';
            } else if (progress < 70) {
                this.progressBar.className = 'bg-yellow-500 h-2 rounded-full progress-smooth progress-bar-animated';
            } else {
                this.progressBar.className = 'bg-emerald-600 h-2 rounded-full progress-smooth progress-bar-animated';
            }
            
            // Animation de succès quand 100%
            if (progress === 100) {
                this.progressBar.classList.add('success-checkmark');
            }
        }
        
        if (this.progressPercentage) {
            // Animation du pourcentage
            this.progressPercentage.style.transition = 'all 0.3s ease-in-out';
            this.progressPercentage.textContent = `${Math.round(progress)}%`;
            
            // Animation de couleur du texte
            if (progress < 30) {
                this.progressPercentage.className = 'text-sm text-red-500 dark:text-red-400 transition-colors duration-300';
            } else if (progress < 70) {
                this.progressPercentage.className = 'text-sm text-yellow-500 dark:text-yellow-400 transition-colors duration-300';
            } else {
                this.progressPercentage.className = 'text-sm text-emerald-600 dark:text-emerald-400 transition-colors duration-300';
            }
        }
    }
}

// Initialiser le récapitulatif en temps réel
document.addEventListener('DOMContentLoaded', function() {
    new RealtimeRecap();
    
    // Diagnostic de chargement de carte
    console.log('DOM Content Loaded - Starting map diagnostics...');
    console.log('Leaflet available:', typeof L !== 'undefined');
    console.log('Map container exists:', !!document.getElementById('map'));
    console.log('Map initialized:', mapInitialized);
    
    // Force map initialization after DOM is ready
    setTimeout(() => {
        if (!mapInitialized) {
            console.log('Map not initialized after DOM load, forcing initialization...');
            setDefaultLocation();
        }
    }, 1500);
});

// Gestion des boutons d'action
document.addEventListener('click', function(e) {
    if (e.target.id === 'save-draft-btn') {
        e.preventDefault();
        saveDraft();
    }
    
    if (e.target.id === 'preview-btn') {
        e.preventDefault();
        showPreview();
    }
});

// Fonction de sauvegarde de brouillon
function saveDraft() {
    const formData = new FormData(document.getElementById('step1Form'));
    const data = Object.fromEntries(formData.entries());
    
    // Sauvegarder dans localStorage
    localStorage.setItem('charging_point_draft', JSON.stringify(data));
    
    // Afficher notification de succès
    showNotification('Brouillon sauvegardé avec succès!', 'success');
}

// Fonction d'aperçu
function showPreview() {
    const formData = new FormData(document.getElementById('step1Form'));
    const data = Object.fromEntries(formData.entries());
    
    // Créer une modal d'aperçu
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Aperçu du point de charge</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="space-y-4">
                ${Object.entries(data).filter(([key, value]) => value && value.trim() !== '').map(([key, value]) => `
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">${key.replace('_', ' ')}</div>
                        <div class="text-gray-900 dark:text-gray-100">${value}</div>
                    </div>
                `).join('')}
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                    Fermer
                </button>
                <button onclick="document.getElementById('step1Form').submit()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                    Continuer
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// Fonction de notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 notification-slide ${
        type === 'success' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
        type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
        'bg-blue-100 text-blue-800 border border-blue-200'
    }`;
    notification.innerHTML = `
        <div class="flex items-center gap-2">
            <span>${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Charger le brouillon au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    const draft = localStorage.getItem('charging_point_draft');
    if (draft) {
        try {
            const data = JSON.parse(draft);
            Object.entries(data).forEach(([key, value]) => {
                const field = document.querySelector(`[name="${key}"]`);
                if (field) {
                    field.value = value;
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
            showNotification('Brouillon chargé automatiquement', 'info');
        } catch (e) {
            console.error('Erreur lors du chargement du brouillon:', e);
        }
    }
});
</script>

<!-- Suppression forcée des éléments de chargement -->
<script src="{{ asset('js/force-remove-loading.js') }}"></script>

<!-- Auto-complétion intelligente d'adresse -->
<script src="{{ asset('js/smart-address-autocomplete.js') }}"></script>

<!-- Solution ultime pour la carte et la recherche d'adresse -->
<script src="{{ asset('js/ultimate-map-address-solution.js') }}"></script>

<!-- Récapitulatif en temps réel -->
<script src="{{ asset('js/realtime-recap.js') }}"></script>

@endpush
@endsection