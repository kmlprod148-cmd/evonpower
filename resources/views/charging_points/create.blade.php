@extends('layouts.app')

@section('title', 'Créer une borne de recharge')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/charging-points/form.css') }}">
@endsection

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Fil d'Ariane -->
        <nav class="mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
                <li>
                    <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-home"></i>
                        <span class="sr-only">Accueil</span>
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="flex-shrink-0 h-5 w-5 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                        </svg>
                        <a href="{{ route('charging-points.index') }}" class="ml-4 text-gray-500 hover:text-gray-700 text-sm font-medium">
                            Bornes de recharge
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="flex-shrink-0 h-5 w-5 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                        </svg>
                        <span class="ml-4 text-gray-800 text-sm font-medium">Ajouter</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Titre de la page -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Ajouter une nouvelle borne de recharge</h1>
            <p class="mt-1 text-sm text-gray-600">
                Remplissez les informations ci-dessous pour créer une nouvelle borne de recharge.
            </p>
        </div>

        <!-- Affichage des erreurs de validation -->
        @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        Des erreurs sont survenues lors de la validation du formulaire
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Formulaire avec onglets -->
        <form action="{{ route('charging-points.store') }}" method="POST" id="chargingPointForm">
            @csrf

            <!-- Navigation par onglets -->
            <div class="tab-nav mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    @foreach(['general-info' => 'Informations générales',
                              'technical-specs' => 'Spécifications techniques',
                              'location' => 'Emplacement',
                              'pricing' => 'Plan tarifaire'] as $tabId => $tabLabel)
                        <button type="button"
                                class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $tabId === 'general-info' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                data-tab="{{ $tabId }}">
                            {{ $tabLabel }}
                        </button>
                    @endforeach
                </nav>
            </div>

            <!-- Onglet : Informations générales -->
            <div id="general-info" class="tab-content active form-card bg-white shadow sm:rounded-lg p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Informations générales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Champ : Nom -->
                    <x-form-group
                        name="name"
                        label="Nom"
                        :value="old('name')"
                        required="true" />

                    <!-- Champ : Numéro de série -->
                    <x-form-group
                        name="serial_number"
                        label="Numéro de série"
                        :value="old('serial_number')"
                        required="true" />

                    <!-- Champ : Fabricant -->
                    <x-form-group
                        name="manufacturer"
                        label="Fabricant"
                        :value="old('manufacturer')" />

                    <!-- Champ : Modèle -->
                    <x-form-group
                        name="model"
                        label="Modèle"
                        :value="old('model')" />

                    <!-- Champ : Statut -->
                    <div class="form-group">
                        <label for="status" class="form-label required-field">Statut</label>
                        <select id="status" name="status" class="form-input @error('status') border-red-500 @enderror" required>
                            @foreach(['online' => 'En ligne',
                                    'offline' => 'Hors ligne',
                                    'maintenance' => 'En maintenance',
                                    'error' => 'Erreur'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status') == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Champ : Date d'installation -->
                    <x-form-group
                        name="installation_date"
                        label="Date d'installation"
                        type="date"
                        :value="old('installation_date')" />

                    <!-- Champ : Intégrateur -->
                    <div class="form-group">
                        <label for="integrator_id" class="form-label">Intégrateur</label>
                        <select id="integrator_id" name="integrator_id" class="form-input @error('integrator_id') border-red-500 @enderror">
                            <option value="">Sélectionnez un intégrateur</option>
                            @foreach($integrators as $integrator)
                                <option value="{{ $integrator->id }}" @selected(old('integrator_id') == $integrator->id)>
                                    {{ $integrator->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('integrator_id')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Champ : Groupe -->
                    <div class="form-group">
                        <label for="group_id" class="form-label">Groupe</label>
                        <select id="group_id" name="group_id" class="form-input @error('group_id') border-red-500 @enderror">
                            <option value="">Sélectionnez un groupe</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" @selected(old('group_id') == $group->id)>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('group_id')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 text-right">
                    <button type="button" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            onclick="showTab('technical-specs')">
                        Suivant <i class="fas fa-chevron-right ml-1"></i>
                    </button>
                </div>
            </div>

            <!-- Onglet : Spécifications techniques -->
            <div id="technical-specs" class="tab-content form-card bg-white shadow sm:rounded-lg p-6 mb-6" style="display: none;">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Spécifications techniques</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Champ : Puissance de sortie -->
                    <x-form-group
                        name="power_output"
                        label="Puissance de sortie (kW)"
                        type="number"
                        step="0.1"
                        :value="old('power_output')" />

                    <!-- Champ : Version du firmware -->
                    <x-form-group
                        name="firmware_version"
                        label="Version du firmware"
                        :value="old('firmware_version')" />

                    <!-- Champ : Protocole de communication -->
                    <div class="form-group">
                        <label for="communication_protocol" class="form-label">Protocole de communication</label>
                        <select id="communication_protocol" name="communication_protocol" class="form-input @error('communication_protocol') border-red-500 @enderror">
                            <option value="">Sélectionnez un protocole</option>
                            @foreach(['OCPP 1.6' => 'OCPP 1.6',
                                    'OCPP 2.0.1' => 'OCPP 2.0.1',
                                    'OCPP 2.1' => 'OCPP 2.1',
                                    'Propriétaire' => 'Propriétaire'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('communication_protocol') == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('communication_protocol')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Champ : Adresse IP -->
                    <x-form-group
                        name="ip_address"
                        label="Adresse IP"
                        :value="old('ip_address')" />

                    <!-- Champ : Adresse MAC -->
                    <x-form-group
                        name="mac_address"
                        label="Adresse MAC"
                        :value="old('mac_address')" />

                    <!-- Champ : Dernière maintenance -->
                    <x-form-group
                        name="last_maintenance_date"
                        label="Dernière maintenance"
                        type="date"
                        :value="old('last_maintenance_date')" />

                    <!-- Champ : Prochaine maintenance -->
                    <x-form-group
                        name="next_maintenance_date"
                        label="Prochaine maintenance"
                        type="date"
                        :value="old('next_maintenance_date')" />
                </div>

                <div class="mt-6 flex justify-between">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                            onclick="showTab('general-info')">
                        <i class="fas fa-chevron-left mr-1"></i> Précédent
                    </button>
                    <button type="button" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            onclick="showTab('location')">
                        Suivant <i class="fas fa-chevron-right ml-1"></i>
                    </button>
                </div>
            </div>

            <!-- Onglet : Emplacement -->
            <div id="location" class="tab-content form-card bg-white shadow sm:rounded-lg p-6 mb-6" style="display: none;">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Emplacement</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Champ : Nom de l'emplacement -->
                    <div class="form-group md:col-span-2">
                        <label for="location" class="form-label">Nom de l'emplacement</label>
                        <input type="text" id="location" name="location" value="{{ old('location') }}"
                               placeholder="Ex: Parking centre commercial"
                               class="form-input @error('location') border-red-500 @enderror">
                        @error('location')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Champ : Adresse -->
                    <div class="form-group md:col-span-2">
                        <label for="address" class="form-label">Adresse</label>
                        <input type="text" id="address" name="address" value="{{ old('address') }}"
                               class="form-input @error('address') border-red-500 @enderror">
                        @error('address')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Champ : Ville -->
                    <x-form-group
                        name="city"
                        label="Ville"
                        :value="old('city')" />

                    <!-- Champ : Code postal -->
                    <x-form-group
                        name="postal_code"
                        label="Code postal"
                        :value="old('postal_code')" />

                    <!-- Champ : Pays -->
                    <div class="form-group">
                        <label for="country" class="form-label">Pays</label>
                        <select id="country" name="country" class="form-input @error('country') border-red-500 @enderror">
                            @foreach(['France' => 'France',
                                    'Belgique' => 'Belgique',
                                    'Suisse' => 'Suisse',
                                    'Luxembourg' => 'Luxembourg'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('country', 'France') == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('country')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Champ : Type d'accès -->
                    <div class="form-group">
                        <label for="access_type" class="form-label">Type d'accès</label>
                        <select id="access_type" name="access_type" class="form-input @error('access_type') border-red-500 @enderror">
                            @foreach(['public' => 'Public',
                                    'private' => 'Privé',
                                    'restricted' => 'Restreint'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('access_type', 'public') == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('access_type')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Champ : Coordonnées géographiques -->
                    <div class="form-group md:col-span-2 grid grid-cols-2 gap-4">
                        <div>
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" id="latitude" name="latitude" value="{{ old('latitude') }}"
                                   class="form-input @error('latitude') border-red-500 @enderror">
                            @error('latitude')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" id="longitude" name="longitude" value="{{ old('longitude') }}"
                                   class="form-input @error('longitude') border-red-500 @enderror">
                            @error('longitude')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-between">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                            onclick="showTab('technical-specs')">
                        <i class="fas fa-chevron-left mr-1"></i> Précédent
                    </button>
                    <button type="button" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            onclick="showTab('pricing')">
                        Suivant <i class="fas fa-chevron-right ml-1"></i>
                    </button>
                </div>
            </div>

            <!-- Onglet : Plan tarifaire -->
            <div id="pricing" class="tab-content form-card bg-white shadow sm:rounded-lg p-6 mb-6" style="display: none;">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Sélection du plan tarifaire</h2>
                <p class="text-sm text-gray-600 mb-4">
                    Choisissez un plan tarifaire pour cette borne de recharge. Ce plan sera appliqué par défaut à tous les connecteurs,
                    sauf si un plan spécifique est défini pour un connecteur individuel.
                </p>

                <div class="mb-6">
                    <div class="form-group">
                        <label for="pricing_plan_id" class="form-label">Plan tarifaire</label>
                        <select id="pricing_plan_id" name="pricing_plan_id"
                                class="form-input @error('pricing_plan_id') border-red-500 @enderror">
                            <option value="">Sélectionnez un plan tarifaire</option>
                            @foreach($pricingPlans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('pricing_plan_id') == $plan->id)>
                                    {{ $plan->name }}
                                </option>
                            @endforeach
                            <option value="create_new" @selected(old('pricing_plan_id') == 'create_new')>Créer un nouveau plan tarifaire...</option>
                        </select>
                        @error('pricing_plan_id')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Création de nouveau plan tarifaire (affiché conditionnellement) -->
                <div id="new-pricing-plan" class="bg-gray-50 rounded-md p-4 mb-6 border border-gray-200" style="display: none;">
                    <h3 class="text-md font-medium mb-4 text-gray-700">Créer un nouveau plan tarifaire</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label for="new_pricing_plan_name" class="form-label required-field">Nom du plan</label>
                            <input type="text" id="new_pricing_plan_name" name="new_pricing_plan_name"
                                   value="{{ old('new_pricing_plan_name') }}"
                                   class="form-input @error('new_pricing_plan_name') border-red-500 @enderror">
                            @error('new_pricing_plan_name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="new_pricing_plan_price" class="form-label required-field">Prix par kWh (€)</label>
                            <input type="number" step="0.01" id="new_pricing_plan_price" name="new_pricing_plan_price"
                                   value="{{ old('new_pricing_plan_price') }}"
                                   class="form-input @error('new_pricing_plan_price') border-red-500 @enderror">
                            @error('new_pricing_plan_price')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group md:col-span-2">
                            <label for="new_pricing_plan_description" class="form-label">Description</label>
                            <textarea id="new_pricing_plan_description" name="new_pricing_plan_description" rows="3"
                                      class="form-input @error('new_pricing_plan_description') border-red-500 @enderror">{{ old('new_pricing_plan_description') }}</textarea>
                            @error('new_pricing_plan_description')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Plans tarifaires existants (cards) -->
                <div id="pricing-plans-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($pricingPlans as $plan)
                    <div class="pricing-card border rounded-lg p-4 cursor-pointer hover:shadow-md transition-shadow relative @if(old('pricing_plan_id') == $plan->id) border-indigo-500 ring-2 ring-indigo-500 @else border-gray-300 @endif"
                         data-plan-id="{{ $plan->id }}">
                        <div class="absolute top-2 right-2 text-indigo-600 plan-check-icon" style="display: {{ old('pricing_plan_id') == $plan->id ? 'block' : 'none' }};">
                            <i class="fas fa-check-circle fa-lg"></i>
                        </div>
                        <h3 class="font-medium text-gray-900">{{ $plan->name }}</h3>
                        @if(isset($plan->price_per_kwh) && $plan->price_per_kwh > 0)
                            <p class="text-xl font-bold text-gray-900 my-2">{{ number_format($plan->price_per_kwh, 2, ',', ' ') }} €<span class="text-sm font-normal text-gray-500">/kWh</span></p>
                        @elseif(isset($plan->price_per_minute) && $plan->price_per_minute > 0)
                            <p class="text-xl font-bold text-gray-900 my-2">{{ number_format($plan->price_per_minute, 2, ',', ' ') }} €<span class="text-sm font-normal text-gray-500">/min</span></p>
                        @else
                            <p class="text-xl font-bold text-gray-900 my-2">Gratuit</p>
                        @endif
                        <p class="text-sm text-gray-600 mt-2 line-clamp-2">{{ $plan->description ?: 'Aucune description' }}</p>
                    </div>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-between">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                            onclick="showTab('location')">
                        <i class="fas fa-chevron-left mr-1"></i> Précédent
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-save mr-1"></i> Enregistrer la borne
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Composant form-group (caché, seulement pour définir le composant) -->
@once
    @component('components.form-group', ['name' => '', 'label' => '', 'value' => '', 'type' => 'text', 'step' => null, 'required' => false])
    @endcomponent
@endonce
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        initTabs();
        initPricingPlan();
    });

    // Gestion des onglets
    const initTabs = () => {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => showTab(btn.dataset.tab));
        });

        // Show the first tab by default or the one with errors
        let initialTab = 'general-info';
        const errorTab = document.querySelector('.form-error')?.closest('.tab-content');
        if (errorTab) {
            initialTab = errorTab.id;
        }
        showTab(initialTab);
    };

    const showTab = (tabId) => {
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = tab.id === tabId ? 'block' : 'none';
            tab.classList.toggle('active', tab.id === tabId);
        });

        document.querySelectorAll('.tab-btn').forEach(btn => {
            const isTargetTab = btn.dataset.tab === tabId;
            btn.classList.toggle('border-indigo-500', isTargetTab);
            btn.classList.toggle('text-indigo-600', isTargetTab);
            btn.classList.toggle('border-transparent', !isTargetTab);
            btn.classList.toggle('text-gray-500', !isTargetTab);
            btn.classList.toggle('hover:text-gray-700', !isTargetTab);
            btn.classList.toggle('hover:border-gray-300', !isTargetTab);
        });
    };

    // Gestion du plan tarifaire
    const initPricingPlan = () => {
        const pricingPlanSelect = document.getElementById('pricing_plan_id');
        const newPricingPlanForm = document.getElementById('new-pricing-plan');
        const pricingPlansList = document.getElementById('pricing-plans-list');
        const newPlanNameInput = document.getElementById('new_pricing_plan_name');
        const newPlanPriceInput = document.getElementById('new_pricing_plan_price');

        const toggleNewPlanForm = () => {
            const selectedValue = pricingPlanSelect.value;
            const showNewPlan = selectedValue === 'create_new';

            newPricingPlanForm.style.display = showNewPlan ? 'block' : 'none';
            pricingPlansList.style.display = showNewPlan ? 'none' : 'grid'; // Use grid for list display

            // Set required attribute based on visibility
            newPlanNameInput.required = showNewPlan;
            newPlanPriceInput.required = showNewPlan;

            if (!showNewPlan && selectedValue) {
                selectPricingPlan(selectedValue);
            } else if (!showNewPlan && !selectedValue) {
                 // Deselect all cards if no plan is selected and not creating new
                 deselectAllPricingPlans();
            }
        };

        pricingPlanSelect.addEventListener('change', toggleNewPlanForm);

        pricingPlansList.addEventListener('click', (e) => {
            const card = e.target.closest('.pricing-card');
            if (card) {
                const planId = card.dataset.planId;
                selectPricingPlan(planId);
                pricingPlanSelect.value = planId; // Update select dropdown
                toggleNewPlanForm(); // Ensure new plan form is hidden
            }
        });

        // Initial state based on old input or default
        toggleNewPlanForm();
        const initialPlanId = pricingPlanSelect.value;
        if (initialPlanId && initialPlanId !== 'create_new') {
            selectPricingPlan(initialPlanId);
        }
    };

    const deselectAllPricingPlans = () => {
        document.querySelectorAll('.pricing-card').forEach(card => {
            card.classList.remove('selected', 'border-indigo-500', 'ring-2', 'ring-indigo-500');
            card.classList.add('border-gray-300');
            card.querySelector('.plan-check-icon').style.display = 'none';
        });
    };

    const selectPricingPlan = (planId) => {
        deselectAllPricingPlans(); // Deselect all first

        const card = document.querySelector(`.pricing-card[data-plan-id="${planId}"]`);
        if (card) {
            card.classList.add('selected', 'border-indigo-500', 'ring-2', 'ring-indigo-500');
            card.classList.remove('border-gray-300');
            card.querySelector('.plan-check-icon').style.display = 'block';
        }
    };


    // Validation du formulaire (simple client-side check for required fields)
    const setupFormValidation = () => {
        const form = document.getElementById('chargingPointForm');

        form.addEventListener('submit', (e) => {
            let firstInvalidField = null;

            // Clear previous errors visually
            form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
            form.querySelectorAll('.form-error-client').forEach(el => el.remove()); // Remove previous client-side error messages

            // Validate required fields in all tabs
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                // Check if the field is visible or part of a visible tab/section
                const parentTab = field.closest('.tab-content');
                const isVisible = field.offsetWidth > 0 || field.offsetHeight > 0 || (parentTab && parentTab.classList.contains('active'));

                // Special handling for the 'create_new' pricing plan fields
                const isNewPricingField = field.id.startsWith('new_pricing_plan_');
                const isCreatingNewPlan = form.pricing_plan_id.value === 'create_new';

                if (isVisible && (!isNewPricingField || isCreatingNewPlan)) {
                    if (!field.value.trim()) {
                        field.classList.add('border-red-500');
                        // Add a simple error message below the field
                        const errorMsg = document.createElement('p');
                        errorMsg.className = 'text-red-600 text-xs mt-1 form-error-client';
                        errorMsg.textContent = 'Ce champ est obligatoire.';
                        field.parentNode.appendChild(errorMsg);

                        if (!firstInvalidField) {
                            firstInvalidField = field;
                        }
                    }
                }
            });

            if (firstInvalidField) {
                e.preventDefault(); // Prevent form submission

                // Find the tab containing the first invalid field and switch to it
                const tabContent = firstInvalidField.closest('.tab-content');
                if (tabContent) {
                    showTab(tabContent.id);
                }

                // Focus the first invalid field
                firstInvalidField.focus();

                // Optionally, show a general alert
                // alert('Veuillez corriger les erreurs dans le formulaire.');
            }
        });
    };

</script>
@endsection