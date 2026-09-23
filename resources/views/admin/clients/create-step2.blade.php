@extends('layouts.app')

@section('title', 'Nouveau Client - Étape 2')
@section('page-title', 'Nouveau Client - Étape 2')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-600 text-white font-medium">
                            ✓
                        </div>
                        <div class="ml-3 text-sm font-medium text-green-600 dark:text-green-400">Informations personnelles</div>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 text-white font-medium">
                            2
                        </div>
                        <div class="ml-3 text-sm font-medium text-blue-600 dark:text-blue-400">Véhicule</div>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium">
                            ✓
                        </div>
                        <div class="ml-3 text-sm font-medium text-gray-500 dark:text-gray-400">Confirmation</div>
                    </div>
                </div>
            </div>
            <div class="mt-4 h-2 bg-gray-200 dark:bg-gray-700 rounded-full">
                <div class="h-2 bg-blue-600 rounded-full" style="width: 66%"></div>
            </div>
        </div>

        <!-- Summary from Step 1 -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
            <h3 class="text-sm font-medium text-blue-900 dark:text-blue-100">Résumé - Informations personnelles</h3>
            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                <p><strong>Nom :</strong> {{ $step1Data['first_name'] ?? '' }} {{ $step1Data['name'] ?? '' }}</p>
                <p><strong>Email :</strong> {{ $step1Data['email'] ?? '' }}</p>
                <p><strong>Téléphone :</strong> {{ $step1Data['phone'] ?? 'Non défini' }}</p>
            </div>
            <a href="{{ route('admin.clients.create-step1') }}" class="mt-2 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                Modifier les informations personnelles
            </a>
        </div>

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Nouveau Client - Véhicule Principal</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Étape 2 sur 2 : Renseignez les informations du véhicule principal du client</p>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
            <form method="POST" action="{{ route('admin.clients.store-step2') }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Vehicle Make -->
                    <div>
                        <label for="vehicle_make" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Marque <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="vehicle_make" 
                               id="vehicle_make" 
                               value="{{ old('vehicle_make', $step2Data['vehicle_make'] ?? '') }}"
                               placeholder="Ex: Tesla, Renault, Peugeot..."
                               class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100 @error('vehicle_make') border-red-500 @enderror"
                               required>
                        @error('vehicle_make')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Vehicle Model -->
                    <div>
                        <label for="vehicle_model" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Modèle <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="vehicle_model" 
                               id="vehicle_model" 
                               value="{{ old('vehicle_model', $step2Data['vehicle_model'] ?? '') }}"
                               placeholder="Ex: Model 3, Zoe, e-208..."
                               class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100 @error('vehicle_model') border-red-500 @enderror"
                               required>
                        @error('vehicle_model')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Vehicle Registration -->
                    <div>
                        <label for="vehicle_registration" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Immatriculation <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="vehicle_registration" 
                               id="vehicle_registration" 
                               value="{{ old('vehicle_registration', $step2Data['vehicle_registration'] ?? '') }}"
                               placeholder="Ex: AB-123-CD"
                               class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100 @error('vehicle_registration') border-red-500 @enderror"
                               required>
                        @error('vehicle_registration')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Vehicle Year -->
                    <div>
                        <label for="vehicle_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Année
                        </label>
                        <input type="number" 
                               name="vehicle_year" 
                               id="vehicle_year" 
                               value="{{ old('vehicle_year', $step2Data['vehicle_year'] ?? '') }}"
                               min="2000"
                               max="{{ date('Y') + 1 }}"
                               placeholder="{{ date('Y') }}"
                               class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                    </div>

                    <!-- Battery Capacity -->
                    <div>
                        <label for="vehicle_battery_capacity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Capacité batterie (kWh)
                        </label>
                        <input type="text" 
                               name="vehicle_battery_capacity" 
                               id="vehicle_battery_capacity" 
                               value="{{ old('vehicle_battery_capacity', $step2Data['vehicle_battery_capacity'] ?? '') }}"
                               placeholder="Ex: 50, 75, 100"
                               class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                    </div>

                    <!-- Connector Type -->
                    <div>
                        <label for="vehicle_connector_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Type de connecteur
                        </label>
                        <select name="vehicle_connector_type" 
                                id="vehicle_connector_type" 
                                class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                            <option value="">Sélectionner...</option>
                            @foreach(\App\Models\Vehicle::connectorTypes() as $value => $label)
                                <option value="{{ $value }}" {{ old('vehicle_connector_type', $step2Data['vehicle_connector_type'] ?? '') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Vehicle Color -->
                    <div>
                        <label for="vehicle_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Couleur
                        </label>
                        <input type="text" 
                               name="vehicle_color" 
                               id="vehicle_color" 
                               value="{{ old('vehicle_color', $step2Data['vehicle_color'] ?? '') }}"
                               placeholder="Ex: Blanc, Noir, Rouge..."
                               class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                    </div>
                </div>

                <!-- Info Box -->
                <div class="mt-6 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Le client pourra ajouter des véhicules supplémentaires depuis son espace utilisateur après la création du compte.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-8 flex items-center justify-between">
                    <a href="{{ route('admin.clients.create-step1') }}" 
                       class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300">
                        Retour
                    </a>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('admin.clients.cancel-create') }}" 
                           class="px-4 py-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300">
                            Annuler
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            Créer le client
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
