@extends('layouts.app')

@section('content')
<div class="px-4 py-6" x-data="stationCreateManager()">
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('stations.index') }}" class="text-gray-700 hover:text-gray-900">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                    {{ __('extracted.stations') }}
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span class="ml-1 text-gray-500 md:ml-2">{{ __('extracted.creer') }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <h1 class="text-2xl font-semibold text-gray-900 mb-6">{{ __('extracted.creer_une_nouvelle_station') }}</h1>

    <form action="{{ route('stations.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column - Station Information -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('extracted.informations_de_la_station') }}</h3>
                    
                    <div class="space-y-4">
                        <!-- Station Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.nom_de_la_station') }} *</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('name') border-red-500 @enderror" 
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.adresse') }} *</label>
                            <input type="text" name="address" id="address" value="{{ old('address') }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('address') border-red-500 @enderror" 
                                   required>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- City and Postal Code -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.ville') }} *</label>
                                <input type="text" name="city" id="city" value="{{ old('city') }}" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('city') border-red-500 @enderror" 
                                       required>
                                @error('city')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.code_postal') }}</label>
                                <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('postal_code') border-red-500 @enderror">
                                @error('postal_code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Country -->
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.pays') }}</label>
                            <input type="text" name="country" id="country" value="{{ old('country', 'France') }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('country') border-red-500 @enderror">
                            @error('country')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Type and Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.type') }}</label>
                                <select name="type" id="type" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('type') border-red-500 @enderror">
                                    <option value="public" {{ old('type') == 'public' ? 'selected' : '' }}>{{ __('extracted.public') }}</option>
                                    <option value="private" {{ old('type') == 'private' ? 'selected' : '' }}>{{ __('extracted.prive') }}</option>
                                    <option value="commercial" {{ old('type') == 'commercial' ? 'selected' : '' }}>{{ __('extracted.commercial') }}</option>
                                </select>
                                @error('type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.statut') }}</label>
                                <select name="status" id="status" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('status') border-red-500 @enderror">
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>{{ __('extracted.actif') }}</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>{{ __('extracted.inactif') }}</option>
                                    <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>{{ __('extracted.maintenance') }}</option>
                                    <option value="planned" {{ old('status') == 'planned' ? 'selected' : '' }}>{{ __('extracted.planifie') }}</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Group Selection -->
                        <div>
                            <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.groupe') }}</label>
                            <select name="group_id" id="group_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('group_id') border-red-500 @enderror">
                                <option value="">{{ __('extracted.selectionner_groupe_optionnel') }}</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                        {{ $group->name ?? $group->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('group_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.description') }}</label>
                            <textarea name="description" id="description" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('description') border-red-500 @enderror"
                                      placeholder="{{ __('extracted.description_station') }}">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Location and Charging Points -->
            <div class="space-y-6">
                <!-- Location Information -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('extracted.coordonnees_gps') }}</h3>
                    
                    <div class="space-y-4">
                        <!-- Latitude and Longitude -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="latitude" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.latitude') }} *</label>
                                <input type="number" step="0.00000001" name="latitude" id="latitude" value="{{ old('latitude') }}" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('latitude') border-red-500 @enderror" 
                                       placeholder="{{ __('extracted.exemple_latitude') }}" required>
                                @error('latitude')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="longitude" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.longitude') }} *</label>
                                <input type="number" step="0.00000001" name="longitude" id="longitude" value="{{ old('longitude') }}" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('longitude') border-red-500 @enderror" 
                                       placeholder="{{ __('extracted.exemple_longitude') }}" required>
                                @error('longitude')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Map -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('extracted.selectionner_position_carte') }}</label>
                            <div id="leaflet-map" style="height: 300px; border-radius: 8px; overflow: hidden; border: 1px solid #d1d5db;"></div>
                        </div>

                        <!-- Get Current Location Button -->
                        <div>
                            <button type="button" id="getLocationBtn" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ __('extracted.obtenir_ma_position_actuelle') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Charging Points Selection -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('extracted.points_de_charge_optionnel') }}</h3>
                    <p class="text-sm text-gray-600 mb-4">{{ __('extracted.assigner_jusqua_2_points') }}</p>
                    
                    @if($availableChargingPoints->count() > 0)
                    <div class="space-y-3">
                        <template x-for="chargingPoint in availableChargingPoints" :key="chargingPoint.id">
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" 
                                       :value="chargingPoint.id" 
                                       x-model="selectedChargingPoints"
                                       class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900" x-text="chargingPoint.name"></div>
                                    <div class="text-xs text-gray-500" x-text="chargingPoint.power_output + ' kW - ' + chargingPoint.status"></div>
                                </div>
                            </label>
                        </template>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <p class="text-gray-600">{{ __('extracted.aucun_point_de_charge_disponible_assignation') }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ __('extracted.creez_dabord_des_points_de_charge') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <a href="{{ route('stations.index') }}" 
               class="px-6 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                {{ __('extracted.annuler') }}
            </a>
            <button type="submit" 
                    class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                {{ __('messages.create') }} {{ strtolower(__('messages.station')) }}
            </button>
        </div>
    </form>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset("css/leaflet/leaflet.css") }}" />
@endpush

@push('scripts')
<script src="{{ asset("js/leaflet/leaflet.js") }}"></script>
<script>
function stationCreateManager() {
    return {
        selectedChargingPoints: [],
        availableChargingPoints: @json($availableChargingPoints),

        init() {
            this.initializeMap();
        },

        initializeMap() {
            // Initialize map
            var defaultLat = parseFloat(document.getElementById('latitude').value) || 48.856614;
            var defaultLng = parseFloat(document.getElementById('longitude').value) || 2.352222;
            var map = L.map('leaflet-map').setView([defaultLat, defaultLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            var marker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(map);

            // Update fields when marker is dragged
            marker.on('dragend', function(e) {
                var latlng = marker.getLatLng();
                document.getElementById('latitude').value = latlng.lat.toFixed(8);
                document.getElementById('longitude').value = latlng.lng.toFixed(8);
            });

            // Update marker when clicking on map
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                document.getElementById('latitude').value = e.latlng.lat.toFixed(8);
                document.getElementById('longitude').value = e.latlng.lng.toFixed(8);
            });

            // Sync if user manually modifies fields
            document.getElementById('latitude').addEventListener('change', function() {
                var lat = parseFloat(this.value);
                var lng = parseFloat(document.getElementById('longitude').value);
                if (!isNaN(lat) && !isNaN(lng)) {
                    marker.setLatLng([lat, lng]);
                    map.setView([lat, lng]);
                }
            });
            document.getElementById('longitude').addEventListener('change', function() {
                var lat = parseFloat(document.getElementById('latitude').value);
                var lng = parseFloat(this.value);
                if (!isNaN(lat) && !isNaN(lng)) {
                    marker.setLatLng([lat, lng]);
                    map.setView([lat, lng]);
                }
            });

            // Geolocation button
            document.getElementById('getLocationBtn').addEventListener('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        var lat = position.coords.latitude;
                        var lng = position.coords.longitude;
                        document.getElementById('latitude').value = lat.toFixed(8);
                        document.getElementById('longitude').value = lng.toFixed(8);
                        marker.setLatLng([lat, lng]);
                        map.setView([lat, lng], 15);
                    }, function(error) {
                        alert('{{ __('extracted.erreur_obtention_position') }}' + error.message);
                    });
                } else {
                    alert('{{ __('extracted.geolocalisation_non_supportee') }}');
                }
            });
        }
    }
}
</script>
@endpush
@endsection