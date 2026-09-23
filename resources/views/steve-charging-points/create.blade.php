@extends('layouts.app')

@section('page-title', 'Créer une Borne')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6">
            <a href="{{ route('steve-charging-points.index') }}" 
               class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-4">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Retour à la liste
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Créer une Borne sur l'API Steve</h1>
        </div>

        <!-- Messages -->
        @if(session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg shadow-sm" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <!-- Form Card -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('steve-charging-points.store') }}" class="p-6">
                @csrf

                <div class="space-y-6">
                    <!-- ChargeBox ID -->
                    <div>
                        <label for="chargeBoxId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ChargeBox ID <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="chargeBoxId" 
                               id="chargeBoxId" 
                               value="{{ old('chargeBoxId') }}"
                               required
                               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500"
                               placeholder="Ex: CP-001">
                        @error('chargeBoxId')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Identifiant unique de la borne</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="description" 
                               id="description" 
                               value="{{ old('description') }}"
                               required
                               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500"
                               placeholder="Ex: Borne de recharge Parking A">
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Note -->
                    <div>
                        <label for="note" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Note
                        </label>
                        <textarea name="note" 
                                  id="note" 
                                  rows="3"
                                  class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500"
                                  placeholder="Notes optionnelles...">{{ old('note') }}</textarea>
                        @error('note')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Section Adresse -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Adresse</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Rue -->
                            <div class="md:col-span-2">
                                <label for="address_street" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Rue
                                </label>
                                <input type="text" 
                                       name="address[street]" 
                                       id="address_street" 
                                       value="{{ old('address.street') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500"
                                       placeholder="Ex: Avenue des Champs-Élysées">
                            </div>

                            <!-- Numéro -->
                            <div>
                                <label for="address_houseNumber" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Numéro
                                </label>
                                <input type="text" 
                                       name="address[houseNumber]" 
                                       id="address_houseNumber" 
                                       value="{{ old('address.houseNumber') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500"
                                       placeholder="Ex: 42">
                            </div>

                            <!-- Code postal -->
                            <div>
                                <label for="address_zipCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Code postal
                                </label>
                                <input type="text" 
                                       name="address[zipCode]" 
                                       id="address_zipCode" 
                                       value="{{ old('address.zipCode') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500"
                                       placeholder="Ex: 75008">
                            </div>

                            <!-- Ville -->
                            <div>
                                <label for="address_city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Ville
                                </label>
                                <input type="text" 
                                       name="address[city]" 
                                       id="address_city" 
                                       value="{{ old('address.city') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500"
                                       placeholder="Ex: Paris">
                            </div>

                            <!-- Pays -->
                            <div>
                                <label for="address_country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Pays
                                </label>
                                <select name="address[country]" 
                                        id="address_country"
                                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500">
                                    <option value="UNDEFINED" {{ old('address.country') == 'UNDEFINED' ? 'selected' : '' }}>Non défini</option>
                                    <option value="FR" {{ old('address.country') == 'FR' ? 'selected' : '' }}>France</option>
                                    <option value="BE" {{ old('address.country') == 'BE' ? 'selected' : '' }}>Belgique</option>
                                    <option value="CH" {{ old('address.country') == 'CH' ? 'selected' : '' }}>Suisse</option>
                                    <option value="DE" {{ old('address.country') == 'DE' ? 'selected' : '' }}>Allemagne</option>
                                    <option value="ES" {{ old('address.country') == 'ES' ? 'selected' : '' }}>Espagne</option>
                                    <option value="IT" {{ old('address.country') == 'IT' ? 'selected' : '' }}>Italie</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section Localisation -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Coordonnées GPS</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Latitude -->
                            <div>
                                <label for="locationLatitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Latitude
                                </label>
                                <input type="number" 
                                       name="locationLatitude" 
                                       id="locationLatitude" 
                                       value="{{ old('locationLatitude') }}"
                                       step="0.000001"
                                       class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500"
                                       placeholder="Ex: 48.8566">
                            </div>

                            <!-- Longitude -->
                            <div>
                                <label for="locationLongitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Longitude
                                </label>
                                <input type="number" 
                                       name="locationLongitude" 
                                       id="locationLongitude" 
                                       value="{{ old('locationLongitude') }}"
                                       step="0.000001"
                                       class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500"
                                       placeholder="Ex: 2.3522">
                            </div>
                        </div>
                    </div>

                    <!-- Admin Address -->
                    <div>
                        <label for="adminAddress" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Adresse Administrateur
                        </label>
                        <input type="text" 
                               name="adminAddress" 
                               id="adminAddress" 
                               value="{{ old('adminAddress') }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500"
                               placeholder="Ex: admin@example.com">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Email ou nom d'utilisateur de l'administrateur</p>
                    </div>

                    <!-- Registration Status -->
                    <div>
                        <label for="registrationStatus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Statut d'enregistrement
                        </label>
                        <select name="registrationStatus" 
                                id="registrationStatus"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="">Sélectionner un statut</option>
                            <option value="Accepted" {{ old('registrationStatus') == 'Accepted' ? 'selected' : '' }}>Accepté</option>
                            <option value="Pending" {{ old('registrationStatus') == 'Pending' ? 'selected' : '' }}>En attente</option>
                            <option value="Rejected" {{ old('registrationStatus') == 'Rejected' ? 'selected' : '' }}>Rejeté</option>
                        </select>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex items-center justify-end space-x-3">
                    <a href="{{ route('steve-charging-points.index') }}" 
                       class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Créer la Borne
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

