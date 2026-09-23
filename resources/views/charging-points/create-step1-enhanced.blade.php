@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-emerald-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-emerald-900/10">
    @include('charging-points.partials._creation-header', [
        'title' => 'Nouveau point de charge',
        'currentStep' => 1,
        'totalSteps' => 4
    ])

    <main class="max-w-7xl mx-auto px-4 py-8">
        @include('charging-points.partials._creation-stepper', ['currentStep' => 1])

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Main Form - Left Side (75%) -->
            <div class="lg:col-span-9">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5">
                    <!-- Form Header -->
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-info-circle text-white"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Informations générales</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Renseignez les informations de base de votre point de charge</p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('charging-points.create.store.step1') }}" method="POST" id="step1Form" class="p-6 space-y-6">
                        @csrf

                        <!-- Error Display -->
                        @if ($errors->any())
                            <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-red-500 text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">
                                            Veuillez corriger les erreurs suivantes :
                                        </h3>
                                        <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                                            @foreach ($errors->all() as $error)
                                                <li class="flex items-center gap-2">
                                                    <i class="fas fa-dot-circle text-xs"></i>
                                                    {{ $error }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Form Fields Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name Field -->
                            <div class="md:col-span-2">
                                <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-tag text-emerald-500 mr-2"></i>
                                    Nom du point de charge
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="name" id="name"
                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 transition-all duration-200 pl-4 pr-10 py-3 text-sm"
                                        required
                                        placeholder="Ex: Parking Centre Ville - Borne 01"
                                        value="{{ old('name', session('charging_point_step1.name', '')) }}"
                                        oninput="validateField(this)">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <i class="fas fa-check text-emerald-500 hidden" id="name-check"></i>
                                    </div>
                                </div>
                                @error('name') 
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ $message }}
                                    </p> 
                                @enderror
                            </div>

                            <!-- Serial Number -->
                            <div>
                                <label for="serial_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-barcode text-emerald-500 mr-2"></i>
                                    Numéro de série
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <input type="text" name="serial_number" id="serial_number"
                                    class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 transition-all duration-200 px-4 py-3 text-sm"
                                    required
                                    placeholder="Ex: SN-CHG-2025-0001"
                                    value="{{ old('serial_number', session('charging_point_step1.serial_number', '')) }}"
                                    oninput="validateField(this)">
                                @error('serial_number') 
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ $message }}
                                    </p> 
                                @enderror
                            </div>

                            <!-- Manufacturer -->
                            <div>
                                <label for="manufacturer" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-industry text-emerald-500 mr-2"></i>
                                    Fabricant
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <select name="manufacturer" id="manufacturer"
                                    class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 transition-all duration-200 px-4 py-3 text-sm"
                                    required onchange="validateField(this)">
                                    <option value="">Sélectionner un fabricant</option>
                                    <option value="ABB" {{ old('manufacturer', session('charging_point_step1.manufacturer', '')) == 'ABB' ? 'selected' : '' }}>ABB</option>
                                    <option value="Schneider Electric" {{ old('manufacturer', session('charging_point_step1.manufacturer', '')) == 'Schneider Electric' ? 'selected' : '' }}>Schneider Electric</option>
                                    <option value="Tesla" {{ old('manufacturer', session('charging_point_step1.manufacturer', '')) == 'Tesla' ? 'selected' : '' }}>Tesla</option>
                                    <option value="ChargePoint" {{ old('manufacturer', session('charging_point_step1.manufacturer', '')) == 'ChargePoint' ? 'selected' : '' }}>ChargePoint</option>
                                    <option value="EVBox" {{ old('manufacturer', session('charging_point_step1.manufacturer', '')) == 'EVBox' ? 'selected' : '' }}>EVBox</option>
                                    <option value="Wallbox" {{ old('manufacturer', session('charging_point_step1.manufacturer', '')) == 'Wallbox' ? 'selected' : '' }}>Wallbox</option>
                                    <option value="Autre" {{ old('manufacturer', session('charging_point_step1.manufacturer', '')) == 'Autre' ? 'selected' : '' }}>Autre</option>
                                </select>
                                @error('manufacturer') 
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ $message }}
                                    </p> 
                                @enderror
                            </div>

                            <!-- Model -->
                            <div>
                                <label for="model" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-cube text-emerald-500 mr-2"></i>
                                    Modèle
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <input type="text" name="model" id="model"
                                    class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 transition-all duration-200 px-4 py-3 text-sm"
                                    required
                                    placeholder="Ex: Terra AC W7"
                                    value="{{ old('model', session('charging_point_step1.model', '')) }}"
                                    oninput="validateField(this)">
                                @error('model') 
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ $message }}
                                    </p> 
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-power-off text-emerald-500 mr-2"></i>
                                    Statut initial
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <select name="status" id="status"
                                    class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 transition-all duration-200 px-4 py-3 text-sm"
                                    required onchange="validateField(this)">
                                    <option value="">Sélectionner un statut</option>
                                    <option value="offline" {{ old('status', session('charging_point_step1.status', 'offline')) == 'offline' ? 'selected' : '' }}>Hors ligne</option>
                                    <option value="online" {{ old('status', session('charging_point_step1.status', '')) == 'online' ? 'selected' : '' }}>En ligne</option>
                                    <option value="maintenance" {{ old('status', session('charging_point_step1.status', '')) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                </select>
                                @error('status') 
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ $message }}
                                    </p> 
                                @enderror
                            </div>
                        </div>

                        <!-- Location Section -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                <i class="fas fa-map-marker-alt text-emerald-500"></i>
                                Localisation
                            </h3>
                            
                            <!-- Smart Address Search -->
                            <div class="mt-2">
                                <div class="flex items-center justify-between mb-1">
                                    <label for="location_search" class="block text-xs font-medium text-gray-600 dark:text-gray-400">Recherche intelligente d'adresse</label>
                                    <div class="flex items-center gap-2">
                                        <button type="button" id="search-history-btn" class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Historique
                                        </button>
                                        <button type="button" id="auto-locate-btn" class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-lg hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition-colors">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Ma position
                                        </button>
                                    </div>
                                </div>
                                <div class="relative">
                                    <input type="text" id="location_search" 
                                        class="block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm pr-20"
                                        placeholder="Recherche intelligente: 'Casablanca', 'Rabat', 'Marrakech', 'Place Mohammed V'..."
                                        autocomplete="off">
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
                                <div id="address_suggestions" class="mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-64 overflow-y-auto hidden">
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
                                    <div id="map-loading" class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800 z-10">
                                        <div class="text-center">
                                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600 mx-auto mb-2"></div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Chargement de la carte...</p>
                                        </div>
                                    </div>
                                    <div id="map-placeholder" class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 z-10">
                                        <div class="text-center p-6 max-w-sm">
                                            <div class="relative mb-4">
                                                <svg class="h-16 w-16 text-emerald-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                <div class="absolute -top-1 -right-1 h-4 w-4 bg-emerald-500 rounded-full animate-pulse"></div>
                                            </div>
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Carte interactive</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Recherchez une adresse ou utilisez votre position pour localiser le point de charge</p>
                                            <div class="space-y-2 text-xs text-gray-500 dark:text-gray-500 mb-4">
                                                <div class="flex items-center justify-center gap-2">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                    </svg>
                                                    <span>Recherche par adresse ou code postal</span>
                                                </div>
                                                <div class="flex items-center justify-center gap-2">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    </svg>
                                                    <span>Cliquez sur la carte pour positionner</span>
                                                </div>
                                            </div>
                                            <button type="button" id="force-load-btn" class="px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors shadow-lg">
                                                <svg class="h-4 w-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                Charger la carte
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            

                            <!-- Hidden fields (auto-filled by smart address search) -->
                            <input type="hidden" name="address" id="address" value="{{ old('address', session('charging_point_step1.address', '')) }}">
                            <input type="hidden" name="city" id="city" value="{{ old('city', session('charging_point_step1.city', '')) }}">
                            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', session('charging_point_step1.latitude', '')) }}">
                            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', session('charging_point_step1.longitude', '')) }}">
                            <input type="hidden" name="location" id="location" value="{{ old('location', session('charging_point_step1.location', '')) }}">
                            
                            <!-- Status display -->
                            <div id="location_status" class="mt-2 text-xs text-gray-500 dark:text-gray-400 hidden">
                                <span id="location_status_text"></span>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" onclick="loadDraft()" 
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-all duration-200">
                                <i class="fas fa-upload"></i>
                                Charger brouillon
                            </button>
                            
                            <button type="submit" 
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all duration-200 transform hover:scale-105">
                                Continuer
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar - Right Side (25%) -->
            <div class="lg:col-span-3 order-first lg:order-last">
                <!-- Récapitulatif en temps réel -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <i class="fas fa-clipboard-list text-emerald-500"></i>
                        Récapitulatif
                    </h3>
                    <div class="space-y-4" id="live-summary">
                        <div class="text-sm">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-600 dark:text-gray-400">Étape</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">1 / 4</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-3">
                                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 h-2 rounded-full transition-all duration-500" style="width: 25%"></div>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Informations saisies :</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Nom :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-name">-</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Fabricant :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-manufacturer">-</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Modèle :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-model">-</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Localisation :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-location">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help Card -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl border border-blue-200/50 dark:border-blue-800/50 p-6">
                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                        <i class="fas fa-question-circle text-blue-500"></i>
                        Aide
                    </h3>
                    <div class="space-y-3 text-sm text-blue-800 dark:text-blue-200">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-lightbulb text-blue-500 mt-0.5"></i>
                            <p>Choisissez un nom descriptif pour faciliter l'identification de votre point de charge.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-map-pin text-blue-500 mt-0.5"></i>
                            <p>Les coordonnées GPS sont essentielles pour la localisation précise sur la carte.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-save text-blue-500 mt-0.5"></i>
                            <p>Vos données sont automatiquement sauvegardées à chaque étape.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Form validation
function validateField(field) {
    const checkIcon = document.getElementById(field.id + '-check');
    if (field.value.trim() !== '') {
        field.classList.remove('border-red-300', 'dark:border-red-700');
        field.classList.add('border-emerald-300', 'dark:border-emerald-700');
        if (checkIcon) {
            checkIcon.classList.remove('hidden');
        }
    } else {
        field.classList.remove('border-emerald-300', 'dark:border-emerald-700');
        if (checkIcon) {
            checkIcon.classList.add('hidden');
        }
    }
}

// Load draft from localStorage
function loadDraft() {
    const draft = localStorage.getItem('charging_point_draft');
    if (draft) {
        const data = JSON.parse(draft);
        Object.keys(data).forEach(key => {
            const field = document.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = data[key];
                validateField(field);
            }
        });
        
        // Show success message
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 z-50 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg';
        toast.innerHTML = '<i class="fas fa-upload mr-2"></i>Brouillon chargé';
        document.body.appendChild(toast);
        
        setTimeout(() => document.body.removeChild(toast), 3000);
    } else {
        alert('Aucun brouillon trouvé');
    }
}

// Auto-save form data and update summary
document.addEventListener('input', function(e) {
    if (e.target.form && e.target.form.id === 'step1Form') {
        const formData = new FormData(e.target.form);
        const data = Object.fromEntries(formData);
        localStorage.setItem('charging_point_draft', JSON.stringify(data));
        
        // Update live summary
        updateLiveSummary(data);
    }
});

// Update live summary function
function updateLiveSummary(data) {
    const summaryName = document.getElementById('summary-name');
    const summaryManufacturer = document.getElementById('summary-manufacturer');
    const summaryModel = document.getElementById('summary-model');
    const summaryLocation = document.getElementById('summary-location');
    
    if (summaryName) summaryName.textContent = data.name || '-';
    if (summaryManufacturer) summaryManufacturer.textContent = data.manufacturer || '-';
    if (summaryModel) summaryModel.textContent = data.model || '-';
    if (summaryLocation) summaryLocation.textContent = data.location || '-';
}

// Initialize summary on page load
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('step1Form');
    if (form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        updateLiveSummary(data);
    }
});
</script>

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="{{ asset("css/leaflet/leaflet.css") }}" />
<style>
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
<!-- Load Leaflet JS -->
<script src="{{ asset("js/leaflet/leaflet.js") }}"></script>
<script>
// Smart location system with Leaflet
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
const latEl = document.getElementById('latitude');
const lngEl = document.getElementById('longitude');
const locationEl = document.getElementById('location');

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

// Smart search functionality
let searchHistory = JSON.parse(localStorage.getItem('addressSearchHistory') || '[]');

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
    
    searchHistory = searchHistory.filter(item => item !== address);
    searchHistory.unshift(address);
    searchHistory = searchHistory.slice(0, 10);
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
            
            if (latEl) {
                latEl.value = lat.toFixed(7);
                latEl.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (lngEl) {
                lngEl.value = lng.toFixed(7);
                lngEl.dispatchEvent(new Event('input', { bubbles: true }));
            }
            
            if (locationSearch) {
                locationSearch.value = `Position actuelle (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
            }
            if (locationEl) {
                locationEl.value = `Position actuelle (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
                locationEl.dispatchEvent(new Event('input', { bubbles: true }));
            }
            
            // Update address and city hidden fields for current location
            const addressField = document.getElementById('address');
            const cityField = document.getElementById('city');
            if (addressField) {
                addressField.value = `Position actuelle (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
                addressField.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (cityField) {
                cityField.value = 'Position GPS';
                cityField.dispatchEvent(new Event('input', { bubbles: true }));
            }
            
            if (mapInitialized && map) {
                map.setView([lat, lng], 15);
                if (marker) {
                    marker.setLatLng([lat, lng]);
                }
                showLocationStatus(`Position détectée: ${lat.toFixed(6)}, ${lng.toFixed(6)}`, 'success');
            } else {
                initializeMap(lat, lng, 'Position actuelle');
            }
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
            maximumAge: 300000
        }
    );
}

function initializeMap(lat, lng, address = '') {
    if (mapInitialized && map) {
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
        return;
    }

    if (typeof L === 'undefined') {
        hideMapLoading();
        showLocationStatus('Erreur: Leaflet n\'est pas chargé', 'error');
        return;
    }

    try {
        // Clear existing map if any
        if (map) {
            map.remove();
            map = null;
            mapInitialized = false;
        }

        // Clear map container completely
        mapContainer.innerHTML = '';

        // Wait a bit to ensure the container is completely cleared
        setTimeout(() => {
            try {
                // Create a new div for the map
                const mapDiv = document.createElement('div');
                mapDiv.id = 'map';
                mapDiv.style.width = '100%';
                mapDiv.style.height = '320px';
                mapContainer.appendChild(mapDiv);

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
                });

                // Update marker when clicking on map
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
                    showLocationStatus(`Position mise à jour: ${e.latlng.lat.toFixed(6)}, ${e.latlng.lng.toFixed(6)}`, 'success');
                });

                hideMapLoading();
                if (address) {
                    showLocationStatus(`Carte centrée sur: ${address}`, 'success');
                } else {
                    showLocationStatus('Carte initialisée', 'success');
                }

            } catch (innerError) {
                console.error('Error creating map:', innerError);
                hideMapLoading();
                showLocationStatus('Erreur lors de la création de la carte: ' + innerError.message, 'error');
            }
        }, 200);

    } catch (error) {
        console.error('Error creating map:', error);
        hideMapLoading();
        showLocationStatus('Erreur lors de la création de la carte: ' + error.message, 'error');
    }
}

// Smart address search functionality
function searchAddress(query) {
    const currentSearchQuery = query.trim();
    
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
    
    hideSearchTips();
    showSearchLoading();
    showLocationStatus('Recherche intelligente en cours...', 'info');
    
    const mockSuggestions = generateMockSuggestions(currentSearchQuery);
    
    setTimeout(() => {
        hideSearchLoading();
        hideLocationStatus();
        
        if (mockSuggestions.length > 0) {
            showSmartSuggestions(mockSuggestions);
        } else {
            showLocationStatus('Aucune adresse trouvée', 'error');
            hideSuggestions();
        }
    }, 500);
}

function generateMockSuggestions(query) {
    const moroccanAddresses = [
        { name: 'Place Mohammed V, Casablanca 20000, Maroc', lat: 33.5731, lng: -7.5898, zip: '20000' },
        { name: 'Boulevard Mohammed V, Casablanca 20000, Maroc', lat: 33.5731, lng: -7.5898, zip: '20000' },
        { name: 'Avenue Hassan II, Casablanca 20000, Maroc', lat: 33.5731, lng: -7.5898, zip: '20000' },
        { name: 'Place Hassan II, Rabat 10000, Maroc', lat: 34.0209, lng: -6.8416, zip: '10000' },
        { name: 'Avenue Mohammed V, Rabat 10000, Maroc', lat: 34.0209, lng: -6.8416, zip: '10000' },
        { name: 'Place Jemaa el-Fnaa, Marrakech 40000, Maroc', lat: 31.6258, lng: -7.9891, zip: '40000' },
        { name: 'Avenue Mohammed VI, Marrakech 40000, Maroc', lat: 31.6258, lng: -7.9891, zip: '40000' },
        { name: 'Médina de Fès, Fès 30000, Maroc', lat: 34.0331, lng: -5.0003, zip: '30000' },
        { name: 'Plage d\'Agadir, Agadir 80000, Maroc', lat: 30.4278, lng: -9.5981, zip: '80000' },
        { name: 'Place de France, Tanger 90000, Maroc', lat: 35.7595, lng: -5.8340, zip: '90000' },
        { name: 'Centre ville, Meknès 50000, Maroc', lat: 33.8935, lng: -5.5473, zip: '50000' },
        { name: 'Avenue Zerktouni, Casablanca 20000, Maroc', lat: 33.5731, lng: -7.5898, zip: '20000' },
        { name: 'Hay Riad, Rabat 10000, Maroc', lat: 34.0209, lng: -6.8416, zip: '10000' },
        { name: 'Gueliz, Marrakech 40000, Maroc', lat: 31.6258, lng: -7.9891, zip: '40000' },
        { name: 'Ain Diab, Casablanca 20000, Maroc', lat: 33.5731, lng: -7.5898, zip: '20000' }
    ];
    
    const queryLower = query.toLowerCase();
    
    // Check if query contains a zip code (5 digits)
    const zipMatch = query.match(/\b\d{5}\b/);
    const zipCode = zipMatch ? zipMatch[0] : null;
    
    // Filter by zip code if present, otherwise by name
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
    
    // If no matches found, try to generate a generic suggestion
    if (matches.length === 0) {
        // Try to extract city name from query
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
        
        for (const [city, coords] of Object.entries(cityPatterns)) {
            if (queryLower.includes(city)) {
                return [{
                    display_name: `${query}, ${city.charAt(0).toUpperCase() + city.slice(1)} ${coords.zip}, Maroc`,
                    lat: coords.lat.toString(),
                    lon: coords.lng.toString(),
                    address: { city: city.charAt(0).toUpperCase() + city.slice(1) }
                }];
            }
        }
        
        // Default fallback
        return [{
            display_name: `${query}, Maroc`,
            lat: '33.5731',
            lon: '-7.5898',
            address: { city: 'Maroc' }
        }];
    }
    
    return matches.map(addr => ({
        display_name: addr.name,
        lat: addr.lat.toString(),
        lon: addr.lng.toString(),
        address: { city: addr.name.split(',')[1]?.trim() || 'Maroc' }
    }));
}

function showSmartSuggestions(suggestions) {
    if (!addressSuggestions) return;
    
    addressSuggestions.innerHTML = '';
    
    suggestions.forEach((suggestion, index) => {
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

function hideSuggestions() {
    if (addressSuggestions) {
        addressSuggestions.classList.add('hidden');
    }
}

function selectAddress(suggestion) {
    const lat = parseFloat(suggestion.lat);
    const lng = parseFloat(suggestion.lon);
    
    if (isNaN(lat) || isNaN(lng)) {
        showLocationStatus('Erreur: coordonnées invalides', 'error');
        return;
    }
    
    addToSearchHistory(suggestion.display_name);
    
    if (locationSearch) {
        locationSearch.value = suggestion.display_name;
    }
    
    // Extract address and city from the suggestion
    const addressParts = suggestion.display_name.split(',');
    const address = addressParts[0]?.trim() || suggestion.display_name;
    const city = addressParts[1]?.trim() || 'Ville non définie';
    
    // Update hidden fields
    if (latEl) {
        latEl.value = lat.toFixed(7);
        latEl.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (lngEl) {
        lngEl.value = lng.toFixed(7);
        lngEl.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (locationEl) {
        locationEl.value = suggestion.display_name;
        locationEl.dispatchEvent(new Event('input', { bubbles: true }));
    }
    
    // Update address and city hidden fields
    const addressField = document.getElementById('address');
    const cityField = document.getElementById('city');
    if (addressField) {
        addressField.value = address;
        addressField.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (cityField) {
        cityField.value = city;
        cityField.dispatchEvent(new Event('input', { bubbles: true }));
    }
    
    hideSuggestions();
    hideSearchTips();
    
    if (map) {
        map.setView([lat, lng], 15);
        if (marker) {
            marker.setLatLng([lat, lng]);
        }
        showLocationStatus(`✅ Adresse sélectionnée: ${suggestion.display_name}`, 'success');
    } else {
        initializeMap(lat, lng, suggestion.display_name);
    }
}

// Event listeners
if (locationSearch) {
    locationSearch.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        searchTimeout = setTimeout(() => {
            searchAddress(query);
        }, 300);
    });
    
    locationSearch.addEventListener('blur', function() {
        setTimeout(hideSuggestions, 200);
    });
    
    locationSearch.addEventListener('focus', function() {
        if (this.value.trim().length >= 3) {
            searchAddress(this.value.trim());
        }
    });
}

document.addEventListener('click', function(e) {
    if (!locationSearch?.contains(e.target) && !addressSuggestions?.contains(e.target)) {
        hideSuggestions();
    }
});

if (autoLocateBtn) {
    autoLocateBtn.addEventListener('click', function() {
        getCurrentLocation();
    });
}

if (searchHistoryBtn) {
    searchHistoryBtn.addEventListener('click', function() {
        showSearchHistory();
    });
}

if (locationSearch) {
    locationSearch.addEventListener('focus', function() {
        if (this.value.trim().length < 3) {
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

// Initialize with default location
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Leaflet to be loaded
    const checkLeaflet = () => {
        if (typeof L === 'undefined') {
            setTimeout(checkLeaflet, 100);
            return;
        }
        
        if (latEl && lngEl && latEl.value && lngEl.value) {
            const lat = parseFloat(latEl.value);
            const lng = parseFloat(lngEl.value);
            if (!isNaN(lat) && !isNaN(lng)) {
                initializeMap(lat, lng, 'Position existante');
            }
        } else {
            // Prevent multiple calls
            if (window.defaultLocationSet) {
                return;
            }
            window.defaultLocationSet = true;
            
            // Set default coordinates (Casablanca, Morocco)
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
            
            if (locationSearch) {
                locationSearch.value = 'Casablanca, Maroc';
            }
            if (locationEl) {
                locationEl.value = 'Casablanca, Maroc';
                locationEl.dispatchEvent(new Event('input', { bubbles: true }));
            }
            
            // Auto-load map with default coordinates
            setTimeout(() => {
                initializeMap(defaultLat, defaultLng, 'Casablanca, Maroc');
                showLocationStatus('✅ Carte chargée avec Casablanca par défaut!', 'success');
            }, 500);
        }
    };
    
    checkLeaflet();
    
    // Add event listener for force load button
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'force-load-btn') {
            e.preventDefault();
            console.log('Force loading map...');
            showLocationStatus('Chargement forcé de la carte...', 'info');
            
            // Reset flags to allow reload
            window.defaultLocationSet = false;
            window.geolocationAttempted = false;
            mapInitialized = false;
            
            // Try to get current location first, then fallback to default
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        initializeMap(lat, lng, 'Position actuelle');
                        showLocationStatus('✅ Carte chargée avec votre position!', 'success');
                    },
                    function(error) {
                        console.log('Geolocation failed, using default location');
                        setDefaultLocation();
                    },
                    { enableHighAccuracy: false, timeout: 3000, maximumAge: 300000 }
                );
            } else {
                setDefaultLocation();
            }
        }
    });
});

// Helper function to set default location
function setDefaultLocation() {
    const defaultLat = 33.5731;
    const defaultLng = -7.5898;
    initializeMap(defaultLat, defaultLng, 'Casablanca, Maroc');
    showLocationStatus('✅ Carte chargée avec Casablanca par défaut!', 'success');
}
</script>
@endpush
@endsection
