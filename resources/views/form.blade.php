@extends('layouts.app')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">
            {{ isset($chargingPoint) ? 'Modifier la borne : ' . $chargingPoint->name : 'Ajouter une nouvelle borne' }}
        </h1>
        
        <a href="{{ route('charging-points.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retour à la liste
        </a>
    </div>
    
    <!-- Affichage des erreurs -->
    @if ($errors->any())
    <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Certains champs contiennent des erreurs :</h3>
                <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Formulaire principal -->
    <form action="{{ isset($chargingPoint) ? route('charging-points.update', $chargingPoint->id) : route('charging-points.store') }}" 
          method="POST" 
          id="chargingPointForm"
          class="space-y-8">
        @csrf
        @if(isset($chargingPoint))
            @method('PUT')
        @endif
        
        <!-- Navigation par onglets -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button type="button" class="tab-btn active border-green-500 text-green-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="basic-info">
                    Informations de base
                </button>
                <button type="button" class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="associations">
                    Associations
                </button>
                <button type="button" class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="location">
                    Localisation
                </button>
                <button type="button" class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="technical">
                    Informations techniques
                </button>
                <button type="button" class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="access">
                    Accès
                </button>
            </nav>
        </div>
        
        <!-- Onglet 1: Informations de base -->
        <div class="tab-content" id="basic-info">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 required">Nom de la borne</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $chargingPoint->name ?? '') }}" required
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Nom visible par les utilisateurs</p>
                </div>
                
                <div>
                    <label for="serial_number" class="block text-sm font-medium text-gray-700 required">Numéro de série</label>
                    <input type="text" name="serial_number" id="serial_number" value="{{ old('serial_number', $chargingPoint->serial_number ?? '') }}" required
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Numéro de série unique de la borne</p>
                </div>
                
                <div>
                    <label for="manufacturer" class="block text-sm font-medium text-gray-700">Fabricant</label>
                    <input type="text" name="manufacturer" id="manufacturer" value="{{ old('manufacturer', $chargingPoint->manufacturer ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="model" class="block text-sm font-medium text-gray-700">Modèle</label>
                    <input type="text" name="model" id="model" value="{{ old('model', $chargingPoint->model ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                    <select name="status" id="status" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                        <option value="offline" {{ (old('status', $chargingPoint->status ?? '') == 'offline') ? 'selected' : '' }}>Hors ligne</option>
                        <option value="online" {{ (old('status', $chargingPoint->status ?? '') == 'online') ? 'selected' : '' }}>En ligne</option>
                        <option value="maintenance" {{ (old('status', $chargingPoint->status ?? '') == 'maintenance') ? 'selected' : '' }}>En maintenance</option>
                        <option value="error" {{ (old('status', $chargingPoint->status ?? '') == 'error') ? 'selected' : '' }}>Erreur</option>
                    </select>
                </div>
                
                <div>
                    <label for="installation_date" class="block text-sm font-medium text-gray-700">Date d'installation</label>
                    <input type="date" name="installation_date" id="installation_date" value="{{ old('installation_date', isset($chargingPoint) && $chargingPoint->installation_date ? $chargingPoint->installation_date->format('Y-m-d') : '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
            </div>
        </div>
        
        <!-- Onglet 2: Associations -->
        <div class="tab-content hidden" id="associations">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="integrator_id" class="block text-sm font-medium text-gray-700">Intégrateur</label>
                    <select name="integrator_id" id="integrator_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                        <option value="">Sélectionner un intégrateur</option>
                        @foreach($integrators ?? [] as $integrator)
                            <option value="{{ $integrator->id }}" {{ (old('integrator_id', $chargingPoint->integrator_id ?? '') == $integrator->id) ? 'selected' : '' }}>
                                {{ $integrator->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label for="partner_id" class="block text-sm font-medium text-gray-700">Partenaire</label>
                    <select name="partner_id" id="partner_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                        <option value="">Sélectionner un partenaire</option>
                        @foreach($partners ?? [] as $partner)
                            <option value="{{ $partner->id }}" {{ (old('partner_id', $chargingPoint->partner_id ?? '') == $partner->id) ? 'selected' : '' }}
                                    data-integrator="{{ $partner->integrator_id }}">
                                {{ $partner->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label for="group_id" class="block text-sm font-medium text-gray-700">Groupe / Station</label>
                    <select name="group_id" id="group_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                        <option value="">Sélectionner un groupe</option>
                        @foreach($groups ?? [] as $group)
                            <option value="{{ $group->id }}" {{ (old('group_id', $chargingPoint->group_id ?? '') == $group->id) ? 'selected' : '' }}
                                    data-partner="{{ $group->partner_id }}" 
                                    data-integrator="{{ $group->integrator_id }}">
                                {{ $group->name }} {{ $group->type ? '('.$group->type.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label for="pricing_plan_id" class="block text-sm font-medium text-gray-700">Business Plan</label>
                    <select name="pricing_plan_id" id="pricing_plan_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                        <option value="">Sélectionner un plan tarifaire</option>
                        @foreach($pricingPlans ?? [] as $plan)
                            <option value="{{ $plan->id }}" {{ (old('pricing_plan_id', $chargingPoint->pricing_plan_id ?? '') == $plan->id) ? 'selected' : '' }}>
                                {{ $plan->name }} ({{ $plan->energy_fee }} €/kWh)
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Business Plan par défaut pour cette borne</p>
                </div>
            </div>
        </div>
        
        <!-- Onglet 3: Localisation -->
        <div class="tab-content hidden" id="location">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="location" class="block text-sm font-medium text-gray-700">Emplacement</label>
                    <input type="text" name="location" id="location" value="{{ old('location', $chargingPoint->location ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Description générale de l'emplacement</p>
                </div>
                
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Adresse</label>
                    <input type="text" name="address" id="address" value="{{ old('address', $chargingPoint->address ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700">Ville</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $chargingPoint->city ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700">Code postal</label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $chargingPoint->postal_code ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700">Pays</label>
                    <input type="text" name="country" id="country" value="{{ old('country', $chargingPoint->country ?? 'France') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                    <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $chargingPoint->latitude ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                    <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $chargingPoint->longitude ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Carte</label>
                    <div id="map" class="h-64 w-full bg-gray-100 rounded-md"></div>
                    <p class="mt-1 text-xs text-gray-500">Cliquez sur la carte pour définir les coordonnées</p>
                </div>
            </div>
        </div>
        
        <!-- Onglet 4: Informations techniques -->
        <div class="tab-content hidden" id="technical">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="firmware_version" class="block text-sm font-medium text-gray-700">Version du firmware</label>
                    <input type="text" name="firmware_version" id="firmware_version" value="{{ old('firmware_version', $chargingPoint->firmware_version ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="communication_protocol" class="block text-sm font-medium text-gray-700">Protocole de communication</label>
                    <select name="communication_protocol" id="communication_protocol" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                        <option value="">Sélectionner un protocole</option>
                        <option value="OCPP 1.6" {{ (old('communication_protocol', $chargingPoint->communication_protocol ?? '') == 'OCPP 1.6') ? 'selected' : '' }}>OCPP 1.6</option>
                        <option value="OCPP 2.0" {{ (old('communication_protocol', $chargingPoint->communication_protocol ?? '') == 'OCPP 2.0') ? 'selected' : '' }}>OCPP 2.0</option>
                        <option value="Proprietary" {{ (old('communication_protocol', $chargingPoint->communication_protocol ?? '') == 'Proprietary') ? 'selected' : '' }}>Propriétaire</option>
                    </select>
                </div>
                
                <div>
                    <label for="ip_address" class="block text-sm font-medium text-gray-700">Adresse IP</label>
                    <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address', $chargingPoint->ip_address ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="mac_address" class="block text-sm font-medium text-gray-700">Adresse MAC</label>
                    <input type="text" name="mac_address" id="mac_address" value="{{ old('mac_address', $chargingPoint->mac_address ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="last_maintenance_date" class="block text-sm font-medium text-gray-700">Dernière maintenance</label>
                    <input type="date" name="last_maintenance_date" id="last_maintenance_date" value="{{ old('last_maintenance_date', isset($chargingPoint) && $chargingPoint->last_maintenance_date ? $chargingPoint->last_maintenance_date->format('Y-m-d') : '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="next_maintenance_date" class="block text-sm font-medium text-gray-700">Prochaine maintenance</label>
                    <input type="date" name="next_maintenance_date" id="next_maintenance_date" value="{{ old('next_maintenance_date', isset($chargingPoint) && $chargingPoint->next_maintenance_date ? $chargingPoint->next_maintenance_date->format('Y-m-d') : '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
            </div>
        </div>
        
        <!-- Onglet 5: Accès -->
        <div class="tab-content hidden" id="access">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="access_type" class="block text-sm font-medium text-gray-700">Type d'accès</label>
                    <select name="access_type" id="access_type" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                        <option value="public" {{ (old('access_type', $chargingPoint->access_type ?? '') == 'public') ? 'selected' : '' }}>Public</option>
                        <option value="private" {{ (old('access_type', $chargingPoint->access_type ?? '') == 'private') ? 'selected' : '' }}>Privé</option>
                        <option value="restricted" {{ (old('access_type', $chargingPoint->access_type ?? '') == 'restricted') ? 'selected' : '' }}>Restreint</option>
                    </select>
                </div>
                
                <div>
                    <label for="public_access" class="block text-sm font-medium text-gray-700">Accès public</label>
                    <div class="mt-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="public_access" value="1" class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300" {{ (old('public_access', $chargingPoint->public_access ?? '1') == '1') ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">Oui</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="public_access" value="0" class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300" {{ (old('public_access', $chargingPoint->public_access ?? '1') == '0') ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">Non</span>
                        </label>
                    </div>
                </div>
                
                <div id="access_code_container" class="{{ (old('access_type', $chargingPoint->access_type ?? '') == 'restricted') ? '' : 'hidden' }}">
                    <label for="access_code" class="block text-sm font-medium text-gray-700">Code d'accès</label>
                    <input type="text" name="access_code" id="access_code" value="{{ old('access_code', $chargingPoint->access_code ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Code d'accès pour les bornes à accès restreint</p>
                </div>
                
                <div>
                    <label for="qr_code" class="block text-sm font-medium text-gray-700">QR Code</label>
                    <input type="text" name="qr_code" id="qr_code" value="{{ old('qr_code', $chargingPoint->qr_code ?? '') }}"
                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Identifiant pour la génération de QR code</p>
                </div>
                
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" id="notes" rows="3" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">{{ old('notes', $chargingPoint->notes ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Informations supplémentaires sur la borne</p>
                </div>
            </div>
        </div>
        
        <!-- Boutons d'action -->
        <div class="flex justify-between pt-5 border-t border-gray-200">
            <button type="button" id="prev-tab" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Précédent
            </button>
            
            <div>
                <button type="button" onclick="window.location.href='{{ route('charging-points.index') }}'" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Annuler
                </button>
                <button type="submit" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    {{ isset($chargingPoint) ? 'Mettre à jour' : 'Créer' }}
                </button>
                <button type="button" id="next-tab" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Suivant
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 -mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .required:after {
        content: " *";
        color: red;
    }
</style>
@endpush

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Navigation par onglets
        const tabs = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        const prevTabBtn = document.getElementById('prev-tab');
        const nextTabBtn = document.getElementById('next-tab');
        let currentTabIndex = 0;
        
        function showTab(index) {
            tabs.forEach(tab => tab.classList.remove('active', 'border-green-500', 'text-green-600'));
            tabs.forEach(tab => tab.classList.add('border-transparent', 'text-gray-500'));
            
            tabContents.forEach(content => content.classList.add('hidden'));
            
            tabs[index].classList.add('active', 'border-green-500', 'text-green-600');
            tabs[index].classList.remove('border-transparent', 'text-gray-500');
            tabContents[index].classList.remove('hidden');
            
            currentTabIndex = index;
            
            // Mettre à jour l'état des boutons Précédent/Suivant
            prevTabBtn.disabled = (currentTabIndex === 0);
            prevTabBtn.classList.toggle('opacity-50', currentTabIndex === 0);
            
            nextTabBtn.disabled = (currentTabIndex === tabs.length - 1);
            nextTabBtn.classList.toggle('opacity-50', currentTabIndex === tabs.length - 1);
            
            // Cacher le bouton Suivant sur le dernier onglet
            nextTabBtn.style.display = (currentTabIndex === tabs.length - 1) ? 'none' : 'inline-flex';
        }
        
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', function() {
                showTab(index);
            });
        });
        
        prevTabBtn.addEventListener('click', function() {
            if (currentTabIndex > 0) {
                showTab(currentTabIndex - 1);
            }
        });
        
        nextTabBtn.addEventListener('click', function() {
            if (currentTabIndex < tabs.length - 1) {
                showTab(currentTabIndex + 1);
            }
        });
        
        // Initialisation: montrer le premier onglet
        showTab(0);
        
        // Gestion de la carte
        let map;
        let marker;
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const addressInput = document.getElementById('address');
        const cityInput = document.getElementById('city');
        const postalCodeInput = document.getElementById('postal_code');
        
        function initMap() {
            // Centre par défaut: Paris
            const defaultLat = 48.864716;
            const defaultLng = 2.349014;
            
            // Utiliser les coordonnées existantes si disponibles
            const lat = latInput.value ? parseFloat(latInput.value) : defaultLat;
            const lng = lngInput.value ? parseFloat(lngInput.value) : defaultLng;
            
            const mapOptions = {
                center: { lat, lng },
                zoom: 13,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            
            map = new google.maps.Map(document.getElementById('map'), mapOptions);
            
            // Placer le marqueur
            marker = new google.maps.Marker({
                position: { lat, lng },
                map: map,
                draggable: true
            });
            
            // Mettre à jour les coordonnées lors du déplacement du marqueur
            google.maps.event.addListener(marker, 'dragend', function() {
                const position = marker.getPosition();
                latInput.value = position.lat();
                lngInput.value = position.lng();
                
                // Reverse geocoding pour obtenir l'adresse
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: position }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        updateAddressFields(results[0]);
                    }
                });
            });
            
            // Cliquer sur la carte pour déplacer le marqueur
            google.maps.event.addListener(map, 'click', function(event) {
                marker.setPosition(event.latLng);
                latInput.value = event.latLng.lat();
                lngInput.value = event.latLng.lng();
                
                // Reverse geocoding pour obtenir l'adresse
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: event.latLng }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        updateAddressFields(results[0]);
                    }
                });
            });
            
            // Recherche d'adresse
            const searchInput = document.getElementById('address');
            const autocomplete = new google.maps.places.Autocomplete(searchInput);
            
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                
                if (!place.geometry) return;
                
                // Centrer la carte sur le lieu trouvé
                map.setCenter(place.geometry.location);
                marker.setPosition(place.geometry.location);
                
                // Mettre à jour les champs
                latInput.value = place.geometry.location.lat();
                lngInput.value = place.geometry.location.lng();
                updateAddressFields(place);
            });
        }
        
        function updateAddressFields(place) {
            let address = '';
            let city = '';
            let postalCode = '';
            let country = '';
            
            // Parcourir les composants d'adresse
            for (const component of place.address_components) {
                const componentType = component.types[0];
                
                switch (componentType) {
                    case 'street_number':
                        address = component.long_name + ' ' + address;
                        break;
                    case 'route':
                        address += component.long_name;
                        break;
                    case 'locality':
                        city = component.long_name;
                        break;
                    case 'postal_code':
                        postalCode = component.long_name;
                        break;
                    case 'country':
                        country = component.long_name;
                        break;
                }
            }
            
            // Mettre à jour les champs d'adresse
            if (address) addressInput.value = address;
            if (city) cityInput.value = city;
            if (postalCode) postalCodeInput.value = postalCode;
            if (country) document.getElementById('country').value = country;
        }
        
        // Initialiser la carte lorsque l'onglet Localisation est affiché
        tabs.forEach((tab, index) => {
            if (tab.dataset.tab === 'location') {
                tab.addEventListener('click', function() {
                    setTimeout(initMap, 100); // Un petit délai pour s'assurer que la div map est visible
                });
                
                // Si c'est l'onglet actif au chargement, initialiser la carte
                if (index === currentTabIndex) {
                    setTimeout(initMap, 100);
                }
            }
        });
        
        // Suppression de la gestion des connecteurs dans le JS et le formulaire
        
        // Gestion du champ de code d'accès
        const accessTypeSelect = document.getElementById('access_type');
        const accessCodeContainer = document.getElementById('access_code_container');
        
        accessTypeSelect.addEventListener('change', function() {
            if (this.value === 'restricted') {
                accessCodeContainer.classList.remove('hidden');
            } else {
                accessCodeContainer.classList.add('hidden');
            }
        });
        
        // Filtrer les partenaires en fonction de l'intégrateur sélectionné
        const integratorSelect = document.getElementById('integrator_id');
        const partnerSelect = document.getElementById('partner_id');
        const groupSelect = document.getElementById('group_id');
        
        integratorSelect.addEventListener('change', function() {
            const selectedIntegratorId = this.value;
            
            // Filtrer les partenaires
            Array.from(partnerSelect.options).forEach(option => {
                const integratorId = option.dataset.integrator;
                if (!selectedIntegratorId || option.value === '' || integratorId === selectedIntegratorId) {
                    option.hidden = false;
                } else {
                    option.hidden = true;
                    if (option.selected) {
                        option.selected = false;
                    }
                }
            });
            
            // Filtrer les groupes
            Array.from(groupSelect.options).forEach(option => {
                const integratorId = option.dataset.integrator;
                if (!selectedIntegratorId || option.value === '' || integratorId === selectedIntegratorId) {
                    option.hidden = false;
                } else {
                    option.hidden = true;
                    if (option.selected) {
                        option.selected = false;
                    }
                }
            });
        });
        
        // Filtrer les groupes en fonction du partenaire sélectionné
        partnerSelect.addEventListener('change', function() {
            const selectedPartnerId = this.value;
            
            Array.from(groupSelect.options).forEach(option => {
                const partnerId = option.dataset.partner;
                if (!selectedPartnerId || option.value === '' || partnerId === selectedPartnerId) {
                    option.hidden = false;
                } else {
                    option.hidden = true;
                    if (option.selected) {
                        option.selected = false;
                    }
                }
            });
        });
        
        // Déclencher les événements au chargement pour appliquer les filtres initiaux
        const changeEvent = new Event('change');
        integratorSelect.dispatchEvent(changeEvent);
        partnerSelect.dispatchEvent(changeEvent);
    });
</script>
@endpush