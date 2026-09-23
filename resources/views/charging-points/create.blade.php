@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="bg-white shadow-sm">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-medium text-gray-900">Ajouter une borne de recharge</h1>
                <a href="{{ route('charging-points.index') }}" class="text-green-600 hover:text-green-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <div class="px-4 py-6 flex flex-col md:flex-row gap-6">
        <!-- Main Form Content -->
        <div class="flex-1">
            <!-- Form Content -->
            <div class="bg-white shadow rounded-lg">
                <form action="{{ route('charging-points.store') }}" method="POST" id="chargingPointForm">
                    @csrf
                    <meta name="csrf-token" content="{{ csrf_token() }}">
                    <input type="hidden" name="station_data" id="stationData">
                    
                    <!-- Loading overlay -->
                    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-opacity duration-300 hidden">
                        <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col items-center">
                            <svg class="animate-spin h-12 w-12 text-green-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-lg font-medium text-gray-900">Enregistrement en cours...</p>
                            <p class="text-sm text-gray-600 mt-1">Veuillez ne pas quitter cette page</p>
                        </div>
                    </div>

                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-6">Informations sur la borne de recharge</h2>

                        <div class="space-y-6">
                            <!-- General Information Section -->
                            <div>
                                <h3 class="text-md font-medium text-gray-900 mb-4 border-b pb-2">Informations générales</h3>
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">Nom de la borne *</label>
                                        <input type="text" name="name" id="name" required
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('name') }}">
                                        @error('name')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

<!-- Sélection du groupe uniquement -->
<div class="form-group">
  <label for="group_id">Groupe *</label>
  <select class="form-control @error('group_id') is-invalid @enderror"
          id="group_id" name="group_id" required>
      <option value="">-- Sélectionner un groupe --</option>
      @foreach($groups as $group)
          <option value="{{ $group->id }}"
                  data-partner="{{ $group->partner->name ?? 'Non défini' }}"
                  data-integrator="{{ $group->partner->integrator->name ?? 'Non défini' }}"
                  {{ old('group_id') == $group->id ? 'selected' : '' }}>
              {{ $group->name }} ({{ $group->city }})
          </option>
      @endforeach
  </select>
  @error('group_id')
      <span class="invalid-feedback">{{ $message }}</span>
  @enderror
  
  <!-- Affichage des informations liées -->
  <div id="group-info" class="mt-2 text-muted" style="display: none;">
      <small>
          <i class="fas fa-info-circle"></i>
          Partenaire: <span id="partner-name">-</span> |
          Intégrateur: <span id="integrator-name">-</span>
      </small>
  </div>
</div>

                                    <div>
                                        <label for="location" class="block text-sm font-medium text-gray-700">Emplacement *</label>
                                        <input type="text" name="location" id="location" required
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('location') }}">
                                        @error('location')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="address" class="block text-sm font-medium text-gray-700">Adresse</label>
                                        <input type="text" name="address" id="address"
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('address') }}">
                                        @error('address')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="city" class="block text-sm font-medium text-gray-700">Ville</label>
                                        <input type="text" name="city" id="city"
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('city') }}">
                                        @error('city')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="postal_code" class="block text-sm font-medium text-gray-700">Code postal</label>
                                        <input type="text" name="postal_code" id="postal_code"
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('postal_code') }}">
                                        @error('postal_code')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="country" class="block text-sm font-medium text-gray-700">Pays</label>
                                        <input type="text" name="country" id="country"
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('country', 'Maroc') }}">
                                        @error('country')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700">Statut initial *</label>
                                        <select id="status" name="status" required
                                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                            <option value="online" {{ old('status', 'online') == 'online' ? 'selected' : '' }}>En ligne</option>
                                            <option value="offline" {{ old('status') == 'offline' ? 'selected' : '' }}>Hors ligne</option>
                                            <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                            <option value="error" {{ old('status') == 'error' ? 'selected' : '' }}>Erreur</option>
                                        </select>
                                        @error('status')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="md:col-span-2">
                                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                        <textarea id="description" name="description" rows="3"
                                                  class="mt-1 shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ old('description') }}</textarea>
                                        @error('description')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Technical Specifications Section -->
                            <div>
                                <h3 class="text-md font-medium text-gray-900 mb-4 border-b pb-2">Spécifications techniques</h3>
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label for="manufacturer" class="block text-sm font-medium text-gray-700">Fabricant *</label>
                                        <input type="text" name="manufacturer" id="manufacturer" required
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('manufacturer') }}">
                                        @error('manufacturer')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="model" class="block text-sm font-medium text-gray-700">Modèle *</label>
                                        <input type="text" name="model" id="model" required
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('model') }}">
                                        @error('model')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="power_output" class="block text-sm font-medium text-gray-700">Puissance de sortie (kW) *</label>
                                        <input type="number" name="power_output" id="power_output" step="0.1" min="0" required
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('power_output') }}">
                                        @error('power_output')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="serial_number" class="block text-sm font-medium text-gray-700">Numéro de série *</label>
                                        <input type="text" name="serial_number" id="serial_number" required
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('serial_number') }}">
                                        @error('serial_number')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="installation_date" class="block text-sm font-medium text-gray-700">Date d'installation</label>
                                        <input type="date" name="installation_date" id="installation_date"
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('installation_date') }}">
                                        @error('installation_date')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

<!-- Sélection du plan tarifaire -->
<div class="form-group">
  <label for="pricing_plan_id">Plan tarifaire</label>
  <select class="form-control @error('pricing_plan_id') is-invalid @enderror"
          id="pricing_plan_id" name="pricing_plan_id">
      <option value="">-- Sélectionner un plan tarifaire --</option>
      @foreach($pricingPlans as $plan)
          <option value="{{ $plan->id }}" {{ old('pricing_plan_id') == $plan->id ? 'selected' : '' }}>
              {{ $plan->name }}
              @if($plan->rate_type == 'minute')
                  ({{ $plan->price_per_minute }} €/min)
              @elseif($plan->rate_type == 'kwh')
                  ({{ $plan->price_per_kwh }} €/kWh)
              @else
                  ({{ $plan->base_rate }} € forfait)
              @endif
          </option>
      @endforeach
                                </div>
                            </div>

                            <!-- New Pricing Plan Section -->
                            <div id="new-pricing-plan-form" class="hidden">
                                <h3 class="text-md font-medium text-gray-900 mb-4 border-b pb-2">Nouveau plan tarifaire</h3>
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label for="new_pricing_plan_name" class="block text-sm font-medium text-gray-700">Nom du plan *</label>
                                        <input type="text" name="new_pricing_plan_name" id="new_pricing_plan_name"
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        @error('new_pricing_plan_name')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="new_pricing_plan_price" class="block text-sm font-medium text-gray-700">Prix par kWh (€) *</label>
                                        <input type="number" name="new_pricing_plan_price" id="new_pricing_plan_price" step="0.01" min="0"
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        @error('new_pricing_plan_price')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="new_pricing_plan_description" class="block text-sm font-medium text-gray-700">Description</label>
                                        <textarea name="new_pricing_plan_description" id="new_pricing_plan_description" rows="3"
                                                  class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                                        @error('new_pricing_plan_description')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Connectivity Section -->
                            <div>
                                <h3 class="text-md font-medium text-gray-900 mb-4 border-b pb-2">Connectivité</h3>
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label for="connection_type" class="block text-sm font-medium text-gray-700">Type de connexion *</label>
                                        <select id="connection_type" name="connection_type" required
                                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                            <option value="ethernet" {{ old('connection_type', 'ethernet') == 'ethernet' ? 'selected' : '' }}>Ethernet</option>
                                            <option value="wifi" {{ old('connection_type') == 'wifi' ? 'selected' : '' }}>Wi-Fi</option>
                                            <option value="gsm" {{ old('connection_type') == 'gsm' ? 'selected' : '' }}>GSM/4G</option>
                                            <option value="other" {{ old('connection_type') == 'other' ? 'selected' : '' }}>Autre</option>
                                        </select>
                                        @error('connection_type')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="ip_address" class="block text-sm font-medium text-gray-700">Adresse IP</label>
                                        <input type="text" name="ip_address" id="ip_address"
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('ip_address') }}">
                                        @error('ip_address')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="communication_protocol" class="block text-sm font-medium text-gray-700">Protocole de communication *</label>
                                        <select id="communication_protocol" name="communication_protocol" required
                                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                            <option value="ocpp16" {{ old('communication_protocol', 'ocpp16') == 'ocpp16' ? 'selected' : '' }}>OCPP 1.6</option>
                                            <option value="ocpp20" {{ old('communication_protocol') == 'ocpp20' ? 'selected' : '' }}>OCPP 2.0</option>
                                            <option value="proprietary" {{ old('communication_protocol') == 'proprietary' ? 'selected' : '' }}>Propriétaire</option>
                                            <option value="other" {{ old('communication_protocol') == 'other' ? 'selected' : '' }}>Autre</option>
                                        </select>
                                        @error('communication_protocol')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="firmware_version" class="block text-sm font-medium text-gray-700">Version du firmware</label>
                                        <input type="text" name="firmware_version" id="firmware_version"
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('firmware_version') }}">
                                        @error('firmware_version')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="mac_address" class="block text-sm font-medium text-gray-700">Adresse MAC</label>
                                        <input type="text" name="mac_address" id="mac_address"
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('mac_address') }}">
                                        @error('mac_address')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="last_maintenance_date" class="block text-sm font-medium text-gray-700">Dernière maintenance</label>
                                        <input type="date" name="last_maintenance_date" id="last_maintenance_date"
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('last_maintenance_date') }}">
                                        @error('last_maintenance_date')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="next_maintenance_date" class="block text-sm font-medium text-gray-700">Prochaine maintenance</label>
                                        <input type="date" name="next_maintenance_date" id="next_maintenance_date"
                                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                               value="{{ old('next_maintenance_date') }}">
                                        @error('next_maintenance_date')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="md:col-span-2">
                                        <div class="flex items-start">
                                            <div class="flex items-center h-5">
                                                <input id="authentication_required" name="authentication_required" type="checkbox"
                                                       class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded"
                                                       {{ old('authentication_required') ? 'checked' : '' }} value="1">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="authentication_required" class="font-medium text-gray-700">Authentification requise</label>
                                                <p class="text-gray-500">Les utilisateurs doivent s'authentifier pour utiliser cette borne de recharge.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="access_type" class="block text-sm font-medium text-gray-700">Type d'accès</label>
                                        <select id="access_type" name="access_type"
                                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                            <option value="public" {{ old('access_type', 'public') == 'public' ? 'selected' : '' }}>Public</option>
                                            <option value="private" {{ old('access_type') == 'private' ? 'selected' : '' }}>Privé</option>
                                            <option value="restricted" {{ old('access_type') == 'restricted' ? 'selected' : '' }}>Restreint</option>
                                        </select>
                                        @error('access_type')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="public_access" class="block text-sm font-medium text-gray-700">Accès public</label>
                                        <select id="public_access" name="public_access"
                                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                            <option value="1" {{ old('public_access', '1') == '1' ? 'selected' : '' }}>Oui</option>
                                            <option value="0" {{ old('public_access') == '0' ? 'selected' : '' }}>Non</option>
                                        </select>
                                        @error('public_access')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Submission -->
                    <div class="px-6 py-4 bg-gray-50 border-t text-right">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Créer la borne
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Real-time Summary Section -->
        <div class="md:w-80 lg:w-96 flex-shrink-0">
            <div class="bg-white shadow rounded-lg sticky top-6">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Récapitulatif en temps réel</h2>
                </div>
                <div class="p-4" id="realtimeSummary">
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Informations générales</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm text-gray-900"><span class="font-medium">Nom:</span> <span id="summary-name">-</span></p>
                                <p class="text-sm text-gray-900"><span class="font-medium">Emplacement:</span> <span id="summary-location">-</span></p>
                                <p class="text-sm text-gray-900"><span class="font-medium">Statut:</span> <span id="summary-status">-</span></p>
                                <p class="text-sm text-gray-900"><span class="font-medium">Ville:</span> <span id="summary-city">-</span></p>
                                <p class="text-sm text-gray-900"><span class="font-medium">Groupe:</span> <span id="summary-group">-</span></p>
                                <p class="text-sm text-gray-900"><span class="font-medium">Partenaire:</span> <span id="summary-partner">-</span></p>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Spécifications techniques</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm text-gray-900"><span class="font-medium">Fabricant:</span> <span id="summary-manufacturer">-</span></p>
                                <p class="text-sm text-gray-900"><span class="font-medium">Modèle:</span> <span id="summary-model">-</span></p>
                                <p class="text-sm text-gray-900"><span class="font-medium">Puissance:</span> <span id="summary-power">-</span></p>
                                <p class="text-sm text-gray-900"><span class="font-medium">N° série:</span> <span id="summary-serial">-</span></p>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Connectivité</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm text-gray-900"><span class="font-medium">Type:</span> <span id="summary-connection">-</span></p>
                                <p class="text-sm text-gray-900"><span class="font-medium">Protocole:</span> <span id="summary-protocol">-</span></p>
                                <p class="text-sm text-gray-900"><span class="font-medium">Auth:</span> <span id="summary-auth">Non</span></p>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Station</h3>
                            <div class="mt-2 space-y-2" id="summary-station">
                                <p class="text-sm text-gray-900"><span class="font-medium">Type:</span> <span id="summary-station-type">-</span></p>
                                <p class="text-sm text-gray-900"><span class="font-medium">Puissance:</span> <span id="summary-station-power">-</span></p>
                                <p class="text-sm text-gray-900"><span class="font-medium">Capacité:</span> <span id="summary-station-capacity">1-2 bornes</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('chargingPointForm');
        const stationDataInput = document.getElementById('stationData');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const pricingPlanSelect = document.getElementById('pricing_plan_id');
        const newPricingPlanForm = document.getElementById('new-pricing-plan-form');

        // Real-time Summary Update
        const summaryElements = {
            name: document.getElementById('summary-name'),
            location: document.getElementById('summary-location'),
            status: document.getElementById('summary-status'),
            city: document.getElementById('summary-city'),
            group: document.getElementById('summary-group'),
            partner: document.getElementById('summary-partner'),
            manufacturer: document.getElementById('summary-manufacturer'),
            model: document.getElementById('summary-model'),
            power: document.getElementById('summary-power'),
            serial: document.getElementById('summary-serial'),
            connection: document.getElementById('summary-connection'),
            protocol: document.getElementById('summary-protocol'),
            auth: document.getElementById('summary-auth'),
            station: document.getElementById('summary-station'),
        };

        const formInputs = form.querySelectorAll('input, select, textarea');

        function updateRealtimeSummary() {
            // General Information
            summaryElements.name.textContent = document.getElementById('name').value || '-';
            summaryElements.location.textContent = document.getElementById('location').value || '-';
            summaryElements.status.textContent = document.getElementById('status').options[document.getElementById('status').selectedIndex]?.text || '-';
            summaryElements.city.textContent = document.getElementById('city').value || '-';
            
            // Group and Partner
            const groupSelect = document.getElementById('group_id');
            summaryElements.group.textContent = groupSelect.options[groupSelect.selectedIndex]?.text || '-';
            
            const partnerSelect = document.getElementById('partner_id');
            summaryElements.partner.textContent = partnerSelect.options[partnerSelect.selectedIndex]?.text || '-';

            // Technical Specifications
            summaryElements.manufacturer.textContent = document.getElementById('manufacturer').value || '-';
            summaryElements.model.textContent = document.getElementById('model').value || '-';
            summaryElements.power.textContent = (document.getElementById('power_output').value ? document.getElementById('power_output').value + ' kW' : '-');
            summaryElements.serial.textContent = document.getElementById('serial_number').value || '-';

            // Connectivity
            summaryElements.connection.textContent = document.getElementById('connection_type').options[document.getElementById('connection_type').selectedIndex]?.text || '-';
            summaryElements.protocol.textContent = document.getElementById('communication_protocol').options[document.getElementById('communication_protocol').selectedIndex]?.text || '-';
            summaryElements.auth.textContent = document.getElementById('authentication_required').checked ? 'Oui' : 'Non';

            // Update station summary
            const stationType = document.getElementById('model').value || 'Standard';
            const stationPower = document.getElementById('power_output').value || 'N/A';
            
            document.getElementById('summary-station-type').textContent = stationType;
            document.getElementById('summary-station-power').textContent = stationPower + ' kW';
        }

        // Attach input listeners to update summary
        function attachInputListeners(container) {
            container.querySelectorAll('input, select, textarea').forEach(input => {
                input.addEventListener('input', updateRealtimeSummary);
                input.addEventListener('change', updateRealtimeSummary); // For select and checkbox
            });
        }

        // Attach listeners to initial form inputs
        attachInputListeners(form);

        // Initial summary update
        updateRealtimeSummary();

        // Handle "Create new pricing plan" option
        pricingPlanSelect.addEventListener('change', function() {
            if (this.value === 'create_new') {
                newPricingPlanForm.classList.remove('hidden');
                // Make new pricing plan fields required
                newPricingPlanForm.querySelectorAll('input, select').forEach(input => input.setAttribute('required', 'required'));
            } else {
                newPricingPlanForm.classList.add('hidden');
                // Remove required attribute from new pricing plan fields
                newPricingPlanForm.querySelectorAll('input, select').forEach(input => input.removeAttribute('required'));
            }
        });

        // Form submission handling
        form.addEventListener('submit', function (e) {
            // Collect station data
            const stationData = {
                type: document.getElementById('model').value,
                power: document.getElementById('power_output').value,
                capacity: '1-2 bornes'
            };

            // Set the hidden input value
            stationDataInput.value = JSON.stringify(stationData);

            // Show loading overlay
            loadingOverlay.classList.remove('hidden');
        });
    });
</script>
@endpush
@endsection