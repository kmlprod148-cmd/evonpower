@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Ajouter un nouveau plan tarifaire</h1>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('plans.store') }}" method="POST">
            @csrf
            <div class="mb-6">
                <label for="name" class="block text-gray-700 font-medium mb-2">Nom du plan <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    value="{{ old('name') }}"
                    required
                >
                @error('name')
                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Configuration du prix de base -->
            <section class="mb-10">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
                    <i class="fas fa-cog mr-2 text-primary"></i>Configuration du prix de base
                </h2>
                <div class="mb-6">
                    <h3 class="text-base font-medium text-gray-700 mb-3">Choisir le type</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label>
                            <input type="radio" name="rate_type" value="time" class="sr-only peer" {{ old('rate_type', 'time') == 'time' ? 'checked' : '' }}>
                            <div class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-lg peer-checked:border-primary peer-checked:bg-green-50 hover:border-gray-300 transition-all cursor-pointer">
                                <div class="mb-3 text-5xl">
                                    ⏱️
                                </div>
                                <div class="text-center">
                                    <span class="font-semibold text-gray-800 block">⏱️ À la minute</span>
                                    <span class="text-sm text-gray-600 mt-1">🕐 Tarification par durée</span>
                                </div>
                            </div>
                        </label>
                        <label>
                            <input type="radio" name="rate_type" value="energy" class="sr-only peer" {{ old('rate_type') == 'energy' ? 'checked' : '' }}>
                            <div class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-lg peer-checked:border-primary peer-checked:bg-green-50 hover:border-gray-300 transition-all cursor-pointer">
                                <div class="mb-3 text-5xl">
                                    🌱
                                </div>
                                <div class="text-center">
                                    <span class="font-semibold text-gray-800 block">⚡ Par kWh</span>
                                    <span class="text-sm text-gray-600 mt-1">🔋 Tarification par énergie</span>
                                </div>
                            </div>
                        </label>
                        <label>
                            <input type="radio" name="rate_type" value="fixed" class="sr-only peer" {{ old('rate_type') == 'fixed' ? 'checked' : '' }}>
                            <div class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-lg peer-checked:border-primary peer-checked:bg-green-50 hover:border-gray-300 transition-all cursor-pointer">
                                <div class="mb-3 text-5xl">
                                    💚
                                </div>
                                <div class="text-center">
                                    <span class="font-semibold text-gray-800 block">💰 Montant fixe</span>
                                    <span class="text-sm text-gray-600 mt-1">💵 Tarification fixe</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">TVA</label>
                        <div class="relative">
                                <select name="vat_rate_id" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                                    @foreach($vatRates as $vat)
                                        <option value="{{ $vat->id }}" {{ (string) old('vat_rate_id', optional($defaultVatRate)->id) === (string) $vat->id ? 'selected' : '' }}>
                                            {{ $vat->name }} ({{ $vat->rate }}%)
                                        </option>
                                    @endforeach
                                </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Montant</label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" name="price" class="w-full p-3 border border-gray-300 rounded-lg pl-10 focus:ring-2 focus:ring-primary focus:border-transparent" value="{{ old('price', 0) }}" required>
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                <i class="fas fa-money-bill"></i>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Unité</label>
                        <div class="relative">
                            <div class="w-full p-3 bg-gray-100 border border-gray-300 rounded-lg">
                                <span id="unit-display">EUR/min</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                    <label class="block text-gray-700 font-medium mb-2">Tarif initial</label>
                    <div class="flex items-center">
                        <div class="text-2xl font-bold text-blue-700 mr-3" id="initial-rate-value">{{ number_format(old('price', 0), 2, ',', ' ') }}</div>
                        <div class="text-gray-600" id="initial-rate-unit">EUR/min</div>
                    </div>
                </div>
            </section>

            <div class="mb-6">
                <label for="max_duration" class="block text-gray-700 font-medium mb-2">Durée maximale (en minutes) <span class="text-red-500">*</span></label>
                <input
                    type="number"
                    name="max_duration"
                    id="max_duration"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    value="{{ old('max_duration', 120) }}"
                    min="20" max="120" required
                    placeholder="Ex: 120"
                >
                <div class="text-sm text-gray-500 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    Durée maximale autorisée pour une session de recharge. Valeur entre 20 et 120 minutes.
                </div>
                @error('max_duration')
                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Activation fee -->
            <div class="mb-6">
                <label for="activation_fee" class="block text-gray-700 font-medium mb-2">{{ __('Frais d\'activation') }}</label>
                <input type="number"
                       name="activation_fee"
                       id="activation_fee"
                       step="0.01"
                       min="0"
                       value="{{ old('activation_fee') }}"
                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Ex: 5.00">
                <div class="text-sm text-gray-500 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    Frais d'activation appliqués à chaque réservation (optionnel).
                </div>
                @error('activation_fee')
                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Prix supplémentaires prédéfinis -->
            <section class="mb-10">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
                    <i class="fas fa-clock mr-2 text-primary"></i>Prix supplémentaires prédéfinis
                </h2>
                
                <!-- Prix week-end -->
                <div class="mb-6">
                    <div class="flex items-center mb-3">
                        <input type="checkbox" 
                               name="has_weekend_pricing" 
                               id="has_weekend_pricing" 
                               value="1" 
                               {{ old('has_weekend_pricing') ? 'checked' : '' }}
                               class="mr-2">
                        <label for="has_weekend_pricing" class="text-gray-700 font-medium">Activer les prix week-end</label>
                    </div>
                    <div id="weekend_pricing_fields" class="ml-6 {{ old('has_weekend_pricing') ? '' : 'hidden' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="weekend_price" class="block text-gray-700 font-medium mb-2">Prix supplémentaire week-end</label>
                                <input type="number"
                                       name="weekend_price"
                                       id="weekend_price"
                                       step="0.0001"
                                       min="0"
                                       value="{{ old('weekend_price', 0) }}"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                       placeholder="Ex: 0.10">
                                <div class="text-sm text-gray-500 mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Prix supplémentaire appliqué les samedis et dimanches.
                                </div>
                                @error('weekend_price')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prix de nuit -->
                <div class="mb-6">
                    <div class="flex items-center mb-3">
                        <input type="checkbox" 
                               name="has_night_pricing" 
                               id="has_night_pricing" 
                               value="1" 
                               {{ old('has_night_pricing') ? 'checked' : '' }}
                               class="mr-2">
                        <label for="has_night_pricing" class="text-gray-700 font-medium">Activer les prix de nuit</label>
                    </div>
                    <div id="night_pricing_fields" class="ml-6 {{ old('has_night_pricing') ? '' : 'hidden' }}">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="night_price" class="block text-gray-700 font-medium mb-2">Prix supplémentaire nuit</label>
                                <input type="number"
                                       name="night_price"
                                       id="night_price"
                                       step="0.0001"
                                       min="0"
                                       value="{{ old('night_price', 0) }}"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                       placeholder="Ex: 0.05">
                                <div class="text-sm text-gray-500 mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Prix supplémentaire appliqué pendant les heures de nuit.
                                </div>
                                @error('night_price')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label for="night_start_time" class="block text-gray-700 font-medium mb-2">Heure de début</label>
                                <input type="time"
                                       name="night_start_time"
                                       id="night_start_time"
                                       value="{{ old('night_start_time', '22:00') }}"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                @error('night_start_time')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label for="night_end_time" class="block text-gray-700 font-medium mb-2">Heure de fin</label>
                                <input type="time"
                                       name="night_end_time"
                                       id="night_end_time"
                                       value="{{ old('night_end_time', '06:00') }}"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                @error('night_end_time')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="border-t border-gray-200 my-8"></div>

            <!-- Tarifs supplémentaires -->
            <section class="mb-10">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
                    <i class="fas fa-plus-circle mr-2 text-primary"></i>Ajouter un prix supplémentaire
                </h2>
                <div class="mb-4">
                    <div id="additional-rates-list"></div>
                    <button type="button" class="mt-2 px-4 py-2 bg-green-100 text-green-700 rounded hover:bg-green-200 transition" id="add-additional-rate">
                        + Ajouter un tarif supplémentaire
                    </button>
                </div>
                <template id="additional-rate-template">
                    <div class="relative bg-gray-50 border border-gray-200 rounded-lg p-4 mb-3 additional-rate-item">
                        <button type="button" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 remove-additional-rate" title="Supprimer">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom du tarif</label>
                                <input type="text" name="additional_rates[__INDEX__][name]" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-400" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Montant</label>
                                <input type="number" step="0.01" min="0" name="additional_rates[__INDEX__][price]" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-400" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">TVA</label>
                                <select name="additional_rates[__INDEX__][vat_rate_id]" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-400" required>
                                    @foreach($vatRates as $vat)
                                        <option value="{{ $vat->id }}">{{ $vat->name }} ({{ $vat->rate }}%)</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </template>
            </section>

            <section class="mb-10">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
                    <i class="fas fa-sliders-h mr-2 text-primary"></i>Conditions de tarification personnalisées
                </h2>
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-4">
                        Définissez des règles conditionnelles pour ajuster le prix en fonction de la durée, du segment client, de la zone géographique ou de la quantité.
                    </p>
                    <div id="rule-conditions-list"></div>
                    <button type="button" class="mt-2 px-4 py-2 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 transition" id="add-rule-condition">
                        <i class="fas fa-plus mr-1"></i> Ajouter une condition
                    </button>
                </div>
                <template id="rule-condition-template">
                    <div class="relative bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-3 rule-condition-item">
                        <button type="button" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 remove-rule-condition" title="Supprimer">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la règle</label>
                                <input type="text" name="rule_conditions[__INDEX__][name]" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-400" placeholder="Ex: Réduction weekend" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type de condition</label>
                                <select name="rule_conditions[__INDEX__][condition_type]" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                    <option value="single">Simple</option>
                                    <option value="and">ET (toutes les conditions)</option>
                                    <option value="or">OU (au moins une)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Champ</label>
                                <select name="rule_conditions[__INDEX__][field]" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-400 rule-field-select">
                                    <option value="duration">Durée (minutes)</option>
                                    <option value="energy">Énergie (kWh)</option>
                                    <option value="power">Puissance (kW)</option>
                                    <option value="customer_segment">Segment client</option>
                                    <option value="location_zone">Zone géographique</option>
                                    <option value="quantity">Quantité</option>
                                    <option value="day_of_week">Jour de la semaine</option>
                                    <option value="is_weekend">Est un week-end</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Opérateur</label>
                                <select name="rule_conditions[__INDEX__][operator]" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-400 rule-operator-select">
                                    <option value="eq">Égal (=)</option>
                                    <option value="ne">Différent (!=)</option>
                                    <option value="gt">Supérieur (>)</option>
                                    <option value="gte">Supérieur ou égal (>=)</option>
                                    <option value="lt">Inférieur (<)</option>
                                    <option value="lte">Inférieur ou égal (<=)</option>
                                    <option value="in">Dans la liste</option>
                                    <option value="not_in">Pas dans la liste</option>
                                    <option value="between">Entre deux valeurs</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Valeur</label>
                                <input type="text" name="rule_conditions[__INDEX__][value]" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-400 rule-value-input" placeholder="Ex: 60 ou [1,2,3]" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type de tarif</label>
                                <select name="rule_conditions[__INDEX__][rate_type]" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                    <option value="fixed">Fixe (EUR)</option>
                                    <option value="percentage">Pourcentage (%)</option>
                                    <option value="time">Par minute</option>
                                    <option value="energy">Par kWh</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Valeur du prix</label>
                                <input type="number" step="0.0001" name="rule_conditions[__INDEX__][price_value]" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-400" placeholder="Ex: 10 ou 0.50" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Application</label>
                                <select name="rule_conditions[__INDEX__][apply_type]" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                    <option value="add">Ajouter au prix</option>
                                    <option value="multiply">Multiplier le prix</option>
                                    <option value="replace">Remplacer le prix</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priorité</label>
                                <input type="number" name="rule_conditions[__INDEX__][priority]" value="0" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-400" min="0">
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="rule_conditions[__INDEX__][is_percentage]" id="is_percentage__INDEX__" class="mr-2">
                                <label for="is_percentage__INDEX__" class="text-sm font-medium text-gray-700">Pourcentage</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="rule_conditions[__INDEX__][is_active]" id="is_active__INDEX__" class="mr-2" checked>
                                <label for="is_active__INDEX__" class="text-sm font-medium text-gray-700">Active</label>
                            </div>
                        </div>
                    </div>
                </template>
            </section>

            <div class="flex justify-end pt-4 border-t border-gray-200">
                <a href="{{ route('plans.index') }}" class="px-5 py-2.5 mr-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primaryDark text-green-600 rounded-lg shadow-md transition flex items-center">
                    <i class="fas fa-save mr-2"></i> Sauvegarder
                </button>
            </div>
        </form>
    </div>

    <div class="mt-8 bg-blue-50 border border-blue-100 rounded-xl p-5">
        <div class="flex">
            <div class="mr-4 mt-1 text-blue-500">
                <i class="fas fa-info-circle text-2xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-lg text-blue-800 mb-1">Informations importantes</h3>
                <p class="text-blue-700">
                    Configurez votre plan tarifaire en sélectionnant le type de tarification de base et en ajoutant des tarifs supplémentaires si nécessaire. 
                    Les modifications seront appliquées immédiatement après sauvegarde.
                </p>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Unité dynamique
    const rateTypeRadios = document.querySelectorAll('input[name="rate_type"]');
    const unitDisplay = document.getElementById('unit-display');
    const initialRateUnit = document.getElementById('initial-rate-unit');
    const initialRateValue = document.getElementById('initial-rate-value');
    const priceInput = document.querySelector('input[name="price"]');

    function updateUnitDisplay() {
        const selectedValue = document.querySelector('input[name="rate_type"]:checked').value;
        switch(selectedValue) {
            case 'time':
                unitDisplay.textContent = 'EUR/min';
                initialRateUnit.textContent = 'EUR/min';
                break;
            case 'energy':
                unitDisplay.textContent = 'EUR/kWh';
                initialRateUnit.textContent = 'EUR/kWh';
                break;
            case 'fixed':
                unitDisplay.textContent = 'EUR';
                initialRateUnit.textContent = 'EUR';
                break;
        }
    }
    rateTypeRadios.forEach(radio => {
        radio.addEventListener('change', updateUnitDisplay);
    });
    updateUnitDisplay();

    // Met à jour le tarif initial en temps réel
    if (priceInput && initialRateValue) {
        priceInput.addEventListener('input', function() {
            initialRateValue.textContent = parseFloat(this.value || 0).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        });
    }

                // Gestion dynamique des tarifs supplémentaires
            let additionalRateIndex = 0;
            const addBtn = document.getElementById('add-additional-rate');
            const list = document.getElementById('additional-rates-list');
            const template = document.getElementById('additional-rate-template').innerHTML;
            if (addBtn) {
                addBtn.addEventListener('click', function () {
                    let html = template.replace(/__INDEX__/g, additionalRateIndex++);
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = html;
                    list.appendChild(wrapper);
                    // Ajout du bouton de suppression
                    wrapper.querySelector('.remove-additional-rate').onclick = function () {
                        wrapper.remove();
                    };
                });
            }

            // Gestion des prix supplémentaires prédéfinis
            const weekendCheckbox = document.getElementById('has_weekend_pricing');
            const weekendFields = document.getElementById('weekend_pricing_fields');
            const nightCheckbox = document.getElementById('has_night_pricing');
            const nightFields = document.getElementById('night_pricing_fields');

            if (weekendCheckbox) {
                weekendCheckbox.addEventListener('change', function() {
                    weekendFields.classList.toggle('hidden', !this.checked);
                });
            }

            if (nightCheckbox) {
                nightCheckbox.addEventListener('change', function() {
                    nightFields.classList.toggle('hidden', !this.checked);
                });
            }

            // Gestion dynamique des conditions de tarification
            let ruleConditionIndex = 0;
            const addRuleBtn = document.getElementById('add-rule-condition');
            const ruleList = document.getElementById('rule-conditions-list');
            const ruleTemplate = document.getElementById('rule-condition-template').innerHTML;
            
            if (addRuleBtn) {
                addRuleBtn.addEventListener('click', function () {
                    let html = ruleTemplate.replace(/__INDEX__/g, ruleConditionIndex++);
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = html;
                    ruleList.appendChild(wrapper);
                    // Ajout du bouton de suppression
                    wrapper.querySelector('.remove-rule-condition').onclick = function () {
                        wrapper.remove();
                    };
                    // Mise à jour des opérateurs en fonction du champ sélectionné
                    const fieldSelect = wrapper.querySelector('.rule-field-select');
                    const operatorSelect = wrapper.querySelector('.rule-operator-select');
                    if (fieldSelect && operatorSelect) {
                        fieldSelect.addEventListener('change', function() {
                            updateOperatorsForField(this.value, operatorSelect);
                        });
                    }
                });
            }

            function updateOperatorsForField(field, operatorSelect) {
                const numericOperators = ['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'between'];
                const stringOperators = ['eq', 'ne', 'in', 'not_in', 'contains'];
                const booleanOperators = ['eq'];
                const allOperators = ['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'in', 'not_in', 'contains', 'between'];
                
                let operators = allOperators;
                if (['duration', 'energy', 'power', 'quantity', 'hour'].includes(field)) {
                    operators = numericOperators;
                } else if (['customer_segment', 'location_zone'].includes(field)) {
                    operators = stringOperators;
                } else if (['is_weekend'].includes(field)) {
                    operators = booleanOperators;
                }
                
                operatorSelect.innerHTML = operators.map(op => {
                    const labels = {
                        'eq': 'Égal (=)',
                        'ne': 'Différent (!=)',
                        'gt': 'Supérieur (>)',
                        'gte': 'Supérieur ou égal (>=)',
                        'lt': 'Inférieur (<)',
                        'lte': 'Inférieur ou égal (<=)',
                        'in': 'Dans la liste',
                        'not_in': 'Pas dans la liste',
                        'contains': 'Contient',
                        'between': 'Entre deux valeurs'
                    };
                    return `<option value="${op}">${labels[op] || op}</option>`;
                }).join('');
            }
});
</script>@endsection
