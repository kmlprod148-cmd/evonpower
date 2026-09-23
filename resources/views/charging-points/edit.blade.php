@extends('layouts.app')

@push('scripts')
<script src="{{ asset('js/vendor/three.min.js') }}"></script>
<script src="{{ asset('js/vendor/GLTFLoader.js') }}"></script>
<script src="{{ asset('js/charging-point-3d-viewer.js') }}"></script>
@endpush

@section('content')
<div class="bg-gray-50 min-h-screen dark:bg-gray-900">
    <!-- Enhanced Header with back button and progress -->
    <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
        <div class="container mx-auto px-4">
            <div class="flex items-center py-4">
                <a href="{{ route('charging-points.show', $chargingPoint->id) }}" class="mr-4 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Modifier {{ $chargingPoint->name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Mise à jour des informations de la borne de recharge</p>
                </div>
            </div>
            
            <!-- Multi-step Progress Indicator -->
            <div class="pb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium step-indicator" data-step="1">
                                <span class="step-number">1</span>
                                <svg class="w-4 h-4 step-check hidden" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Informations générales</span>
                        </div>
                        
                        <div class="hidden sm:block flex-1 h-0.5 bg-gray-200 dark:bg-gray-600 mx-4">
                            <div class="h-full bg-green-600 transition-all duration-300 progress-bar" style="width: 0%"></div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-400 rounded-full text-sm font-medium step-indicator" data-step="2">
                                <span class="step-number">2</span>
                                <svg class="w-4 h-4 step-check hidden" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Localisation</span>
                        </div>
                        
                        <div class="hidden sm:block flex-1 h-0.5 bg-gray-200 dark:bg-gray-600 mx-4">
                            <div class="h-full bg-green-600 transition-all duration-300 progress-bar" style="width: 0%"></div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-400 rounded-full text-sm font-medium step-indicator" data-step="3">
                                <span class="step-number">3</span>
                                <svg class="w-4 h-4 step-check hidden" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Configuration</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        @if(session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded-lg shadow-sm" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg shadow-sm" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('charging-points.update', $chargingPoint->id) }}" method="POST" class="space-y-8" id="multiStepForm">
            @csrf
            @method('PUT')
            
            <!-- Step 1: Basic Information Section -->
            <div class="step-content bg-white dark:bg-gray-800 shadow-xl rounded-2xl border border-gray-200 dark:border-gray-700 p-8 step-1" data-step="1">
                <div class="flex items-center mb-8">
                    <div class="h-12 w-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Informations générales</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">Configurez les détails de base de votre borne de recharge</p>
                    </div>
                </div>
                
                <!-- 3D Model Preview Section -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Aperçu 3D de la Borne</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Visualisation du modèle 3D de votre point de charge</p>
                        </div>
                        <div class="flex space-x-2">
                            <button type="button" id="reset-preview-camera" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Réinitialiser
                            </button>
                            <button type="button" id="toggle-preview-rotation" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Rotation
                            </button>
                        </div>
                    </div>
                    
                    <div class="relative">
                        <canvas id="edit-model-preview" class="w-full h-64 bg-gray-100 dark:bg-gray-800 rounded-lg"></canvas>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nom de la borne <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $chargingPoint->name) }}" required
                               class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500" 
                               placeholder="Ex: Borne Centre-ville">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="serial_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Numéro de série <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="serial_number" id="serial_number" value="{{ old('serial_number', $chargingPoint->serial_number) }}" required
                               class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500" 
                               placeholder="Ex: CP001234">
                        @error('serial_number')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="manufacturer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Marque
                        </label>
                        <input type="text" name="manufacturer" id="manufacturer" value="{{ old('manufacturer', $chargingPoint->manufacturer) }}" 
                               class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500" 
                               placeholder="Ex: Schneider Electric">
                        @error('manufacturer')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Modèle
                        </label>
                        <input type="text" name="model" id="model" value="{{ old('model', $chargingPoint->model) }}" 
                               class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500" 
                               placeholder="Ex: EVlink Wallbox">
                        @error('model')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="power_output" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Puissance (kW)
                        </label>
                        <input type="number" name="power_output" id="power_output" value="{{ old('power_output', $chargingPoint->power_output) }}" 
                               class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500" 
                               placeholder="Ex: 22">
                        @error('power_output')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Statut <span class="text-red-500">*</span>
                        </label>
                        <select name="status" id="status" required class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500">
                            <option value="online" {{ old('status', $chargingPoint->status) == 'online' ? 'selected' : '' }}>En ligne</option>
                            <option value="offline" {{ old('status', $chargingPoint->status) == 'offline' ? 'selected' : '' }}>Hors ligne</option>
                            <option value="maintenance" {{ old('status', $chargingPoint->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Location Information Section -->
            <div class="step-content bg-white dark:bg-gray-800 shadow-xl rounded-2xl border border-gray-200 dark:border-gray-700 p-8 step-2 hidden" data-step="2">
                <div class="flex items-center mb-8">
                    <div class="h-12 w-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Localisation</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">Définissez l'emplacement de votre borne de recharge</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ville <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="city" id="city" value="{{ old('city', $chargingPoint->city) }}" required
                               class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-green-500 dark:focus:border-green-400 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500" 
                               placeholder="Ex: Paris">
                        @error('city')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Adresse <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="address" id="address" value="{{ old('address', $chargingPoint->address) }}" required
                               class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-green-500 dark:focus:border-green-400 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500" 
                               placeholder="Ex: 123 Rue de la Paix">
                        @error('address')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Latitude
                        </label>
                        <input type="number" name="latitude" id="latitude" value="{{ old('latitude', $chargingPoint->latitude) }}" 
                               class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-green-500 dark:focus:border-green-400 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500" 
                               placeholder="Ex: 48.8566" step="any">
                        @error('latitude')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Longitude
                        </label>
                        <input type="number" name="longitude" id="longitude" value="{{ old('longitude', $chargingPoint->longitude) }}" 
                               class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-green-500 dark:focus:border-green-400 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500" 
                               placeholder="Ex: 2.3522" step="any">
                        @error('longitude')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Configuration Section -->
            <div class="step-content bg-white dark:bg-gray-800 shadow-xl rounded-2xl border border-gray-200 dark:border-gray-700 p-8 step-3 hidden" data-step="3">
                <div class="flex items-center mb-8">
                    <div class="h-12 w-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Configuration</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">Configurez les paramètres avancés de votre borne</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="group_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Groupe
                        </label>
                        <select name="group_id" id="group_id" class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-500 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500">
                            <option value="">Sélectionner un groupe</option>
                            @foreach($groups ?? [] as $group)
                                <option value="{{ $group->id }}" {{ old('group_id', $chargingPoint->group_id) == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('group_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="partner_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Partenaire
                        </label>
                        <select name="partner_id" id="partner_id" class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-500 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500">
                            <option value="">Sélectionner un partenaire</option>
                            @foreach($partners ?? [] as $partner)
                                <option value="{{ $partner->id }}" {{ old('partner_id', $chargingPoint->partner_id) == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('partner_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="pricing_plan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Plan tarifaire
                        </label>
                        <select name="pricing_plan_id" id="pricing_plan_id" class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-500 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500">
                            <option value="">Sélectionner un plan tarifaire</option>
                            @foreach($pricingPlans ?? [] as $plan)
                                <option value="{{ $plan->id }}" {{ old('pricing_plan_id', $chargingPoint->pricing_plan_id) == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('pricing_plan_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea name="description" id="description" rows="3" 
                                  class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-500 dark:focus:border-purple-400 text-sm transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500 resize-none" 
                                  placeholder="Description optionnelle de la borne...">{{ old('description', $chargingPoint->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            
            <!-- Step Navigation Controls -->
            <div class="flex flex-col sm:flex-row gap-4 justify-between items-center bg-white dark:bg-gray-800 shadow-xl rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('charging-points.show', $chargingPoint->id) }}" 
                       class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 hover:shadow-md">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Annuler
                    </a>
                    
                    <button type="button" id="prevStep" 
                            class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 hover:shadow-md hidden">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Précédent
                    </button>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button type="button" id="nextStep" 
                            class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 hover:shadow-md">
                        Suivant
                        <svg class="h-4 w-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    
                    <button type="submit" id="submitBtn"
                            class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 hover:shadow-md hidden">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Enregistrer les modifications
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Multi-step form management
    document.addEventListener('DOMContentLoaded', function() {
        let currentStep = 1;
        const totalSteps = 3;
        const form = document.getElementById('multiStepForm');
        const steps = document.querySelectorAll('.step-content');
        const stepIndicators = document.querySelectorAll('.step-indicator');
        const progressBars = document.querySelectorAll('.progress-bar');
        const nextBtn = document.getElementById('nextStep');
        const prevBtn = document.getElementById('prevStep');
        const submitBtn = document.getElementById('submitBtn');
        
        // Initialize form
        updateStepDisplay();
        updateProgressBar();
        
        // Step navigation
        nextBtn.addEventListener('click', function() {
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateStepDisplay();
                    updateProgressBar();
                    updateStepIndicators();
                }
            }
        });
        
        prevBtn.addEventListener('click', function() {
            if (currentStep > 1) {
                currentStep--;
                updateStepDisplay();
                updateProgressBar();
                updateStepIndicators();
            }
        });
        
        // Step indicator click navigation
        stepIndicators.forEach((indicator, index) => {
            indicator.addEventListener('click', function() {
                const stepNumber = parseInt(this.dataset.step);
                if (stepNumber <= currentStep || isStepCompleted(stepNumber)) {
                    currentStep = stepNumber;
                    updateStepDisplay();
                    updateProgressBar();
                    updateStepIndicators();
                }
            });
        });
        
        // Form submission
        form.addEventListener('submit', function(e) {
            if (!validateAllSteps()) {
                e.preventDefault();
                return false;
            }
            
            // Add loading state
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = `
                <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Enregistrement...
            `;
            submitBtn.disabled = true;
            
            // Re-enable after 10 seconds as fallback
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });
        
        function updateStepDisplay() {
            steps.forEach((step, index) => {
                if (index + 1 === currentStep) {
                    step.classList.remove('hidden');
                    step.classList.add('animate-fadeIn');
                } else {
                    step.classList.add('hidden');
                    step.classList.remove('animate-fadeIn');
                }
            });
            
            // Update navigation buttons
            prevBtn.classList.toggle('hidden', currentStep === 1);
            nextBtn.classList.toggle('hidden', currentStep === totalSteps);
            submitBtn.classList.toggle('hidden', currentStep !== totalSteps);
        }
        
        function updateProgressBar() {
            const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
            progressBars.forEach(bar => {
                bar.style.width = `${progress}%`;
            });
        }
        
        function updateStepIndicators() {
            stepIndicators.forEach((indicator, index) => {
                const stepNumber = index + 1;
                const stepNumberSpan = indicator.querySelector('.step-number');
                const stepCheck = indicator.querySelector('.step-check');
                
                if (stepNumber < currentStep) {
                    // Completed step
                    indicator.classList.remove('bg-gray-200', 'dark:bg-gray-600', 'text-gray-600', 'dark:text-gray-400');
                    indicator.classList.add('bg-green-600', 'text-white');
                    stepNumberSpan.classList.add('hidden');
                    stepCheck.classList.remove('hidden');
                } else if (stepNumber === currentStep) {
                    // Current step
                    indicator.classList.remove('bg-gray-200', 'dark:bg-gray-600', 'text-gray-600', 'dark:text-gray-400', 'bg-green-600', 'text-white');
                    indicator.classList.add('bg-blue-600', 'text-white');
                    stepNumberSpan.classList.remove('hidden');
                    stepCheck.classList.add('hidden');
                } else {
                    // Future step
                    indicator.classList.remove('bg-blue-600', 'text-white', 'bg-green-600');
                    indicator.classList.add('bg-gray-200', 'dark:bg-gray-600', 'text-gray-600', 'dark:text-gray-400');
                    stepNumberSpan.classList.remove('hidden');
                    stepCheck.classList.add('hidden');
                }
            });
        }
        
        function validateCurrentStep() {
            const currentStepElement = document.querySelector(`.step-${currentStep}`);
            const requiredFields = currentStepElement.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                }
            });
            
            return isValid;
        }
        
        function validateAllSteps() {
            let isValid = true;
            
            for (let step = 1; step <= totalSteps; step++) {
                const stepElement = document.querySelector(`.step-${step}`);
                const requiredFields = stepElement.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                });
            }
            
            return isValid;
        }
        
        function isStepCompleted(stepNumber) {
            const stepElement = document.querySelector(`.step-${stepNumber}`);
            const requiredFields = stepElement.querySelectorAll('[required]');
            
            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    return false;
                }
            }
            
            return true;
        }
        
        function validateField(field) {
            const value = field.value.trim();
            const fieldName = field.name;
            let isValid = true;
            let errorMessage = '';
            
            // Remove existing error styling
            field.classList.remove('border-red-500', 'focus:ring-red-500', 'focus:border-red-500', 'error');
            field.classList.add('border-gray-300', 'dark:border-gray-600', 'focus:ring-green-500', 'focus:border-green-500');
            
            // Remove existing error message
            const existingError = field.parentNode.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Validation rules
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                errorMessage = 'Ce champ est requis';
            } else if (fieldName === 'power_output' && value && (isNaN(value) || value < 0)) {
                isValid = false;
                errorMessage = 'La puissance doit être un nombre positif';
            } else if (fieldName === 'latitude' && value && (isNaN(value) || value < -90 || value > 90)) {
                isValid = false;
                errorMessage = 'La latitude doit être comprise entre -90 et 90';
            } else if (fieldName === 'longitude' && value && (isNaN(value) || value < -180 || value > 180)) {
                isValid = false;
                errorMessage = 'La longitude doit être comprise entre -180 et 180';
            }
            
            // Apply error styling if invalid
            if (!isValid) {
                field.classList.remove('border-gray-300', 'dark:border-gray-600', 'focus:ring-green-500', 'focus:border-green-500');
                field.classList.add('border-red-500', 'focus:ring-red-500', 'focus:border-red-500', 'error');
                
                // Add error message
                const errorElement = document.createElement('p');
                errorElement.className = 'mt-1 text-sm text-red-600 dark:text-red-400 field-error';
                errorElement.textContent = errorMessage;
                field.parentNode.appendChild(errorElement);
            }
            
            return isValid;
        }
    });

    // 3D Model Preview Initialization
    let editModelPreview = null;
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize 3D model preview for edit form
        if (document.getElementById('edit-model-preview')) {
            editModelPreview = new ChargingPoint3DViewer('edit-model-preview', {
                modelPath: '/models/charging-point.glb',
                autoRotate: true,
                rotationSpeed: 0.005,
                enableShadows: true,
                enableControls: true,
                showLoading: true
            });
        }
        
        // Control buttons for edit form preview
        const resetPreviewCameraBtn = document.getElementById('reset-preview-camera');
        const togglePreviewRotationBtn = document.getElementById('toggle-preview-rotation');
        
        if (resetPreviewCameraBtn) {
            resetPreviewCameraBtn.addEventListener('click', function() {
                if (editModelPreview) {
                    editModelPreview.resetCamera();
                }
            });
        }
        
        if (togglePreviewRotationBtn) {
            togglePreviewRotationBtn.addEventListener('click', function() {
                if (editModelPreview) {
                    editModelPreview.toggleAutoRotate();
                    // Update button text
                    const isRotating = editModelPreview.config.autoRotate;
                    this.innerHTML = isRotating ? 
                        '<svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>Rotation' :
                        '<svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>Rotation';
                }
            });
        }
    });
</script>

<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out;
    }
    
    .step-indicator {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .step-indicator:hover {
        transform: scale(1.1);
    }
    
    .step-content {
        transition: all 0.3s ease;
    }
    
    .progress-bar {
        transition: width 0.5s ease;
    }
</style>
@endsection