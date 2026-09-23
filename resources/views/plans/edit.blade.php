@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8 px-4">
    @php
        $currentMainRate = match ($plan->rate_type) {
            'fixed' => $plan->fixed_price ?? $plan->base_rate,
            'energy', 'kwh' => $plan->price_per_kwh ?? $plan->base_rate,
            default => $plan->price_per_minute ?? $plan->base_rate,
        };
        $billingIntervals = [
            'session' => 'Par session',
            'hourly' => 'Horaire',
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'monthly' => 'Mensuel',
            'quarterly' => 'Trimestriel',
            'semi-annually' => 'Semestriel',
            'annually' => 'Annuel',
            'yearly' => 'Annuel (legacy)',
        ];
    @endphp
    <!-- En-tête avec breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center text-sm text-gray-600 mb-4">
            <a href="{{ route('plans.index') }}" class="hover:text-blue-600">Plans tarifaires</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 font-medium">{{ $plan->name }}</span>
        </nav>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $plan->name }}</h1>
                <p class="text-gray-600 mt-1">Modifiez les paramètres de ce plan tarifaire</p>
            </div>
            <div class="flex items-center gap-3 mt-4 md:mt-0">
                <span class="badge {{ $plan->is_active ? 'badge-success' : 'badge-secondary' }} text-sm">
                    {{ $plan->is_active ? '✓ Actif' : '✗ Inactif' }}
                </span>
                <span class="badge badge-info text-sm">{{ ucfirst($plan->rate_type) }}</span>
                <a href="{{ route('plans.index') }}" class="btn btn-outline btn-sm">← Retour</a>
            </div>
        </div>
    </div>

    <!-- Affichage des erreurs -->
    @if ($errors->any())
        <div class="alert alert-error mb-6">
            <div class="flex">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="font-semibold">Erreurs de validation</h3>
                    <ul class="list-disc list-inside mt-2 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('plans.update', $plan->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Section Informations de base -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-xl font-bold text-gray-900">Informations de base</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nom du plan <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                        value="{{ old('name', $plan->name) }}" placeholder="Ex: Plan Standard">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="rate_type" class="block text-sm font-semibold text-gray-700 mb-2">Type de tarification <span class="text-red-500">*</span></label>
                    <select name="rate_type" id="rate_type" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('rate_type') border-red-500 @enderror">
                        <option value="">-- Sélectionner --</option>
                        <option value="fixed" {{ old('rate_type', $plan->rate_type) == 'fixed' ? 'selected' : '' }}>Tarif fixe</option>
                        <option value="energy" {{ in_array(old('rate_type', $plan->rate_type), ['energy', 'kwh'], true) ? 'selected' : '' }}>Par kWh</option>
                        <option value="time" {{ in_array(old('rate_type', $plan->rate_type), ['time', 'minute'], true) ? 'selected' : '' }}>Par minute</option>
                    </select>
                    @error('rate_type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                        placeholder="Décrivez ce plan tarifaire...">{{ old('description', $plan->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Section Tarification principale -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-xl font-bold text-gray-900">Tarification principale</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="base_rate" class="block text-sm font-semibold text-gray-700 mb-2">Prix HT <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" step="0.01" name="base_rate" id="base_rate" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('base_rate') border-red-500 @enderror"
                            value="{{ old('base_rate', $currentMainRate ?? 0) }}" placeholder="0.00">
                        <span class="absolute right-3 top-2 text-gray-500 text-sm">€</span>
                    </div>
                    @error('base_rate')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="vat_rate_id" class="block text-sm font-semibold text-gray-700 mb-2">TVA</label>
                    <select name="vat_rate_id" id="vat_rate_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('vat_rate_id') border-red-500 @enderror"
                        required>
                        @foreach($vatRates as $vat)
                            <option value="{{ $vat->id }}" {{ (string) old('vat_rate_id', $plan->vat_rate_id ?? optional($defaultVatRate)->id) === (string) $vat->id ? 'selected' : '' }}>
                                {{ $vat->name }} ({{ $vat->rate }}%)
                            </option>
                        @endforeach
                    </select>
                    @error('vat_rate_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ttc_preview" class="block text-sm font-semibold text-gray-700 mb-2">Prix TTC (aperçu)</label>
                    <div class="relative">
                        <input type="text" id="ttc_preview"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700 font-semibold"
                            value="{{ number_format((float) old('base_rate', $currentMainRate ?? 0) * (1 + ((float) old('vat_rate_preview', $plan->vatRate->rate ?? optional($defaultVatRate)->rate ?? 0) / 100)), 2) }}" readonly>
                        <span class="absolute right-3 top-2 text-gray-500 text-sm">€</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-6 border-t border-gray-200">
                <div>
                    <label for="activation_fee" class="block text-sm font-semibold text-gray-700 mb-2">Frais d'activation</label>
                    <div class="relative">
                        <input type="number" step="0.01" name="activation_fee" id="activation_fee"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('activation_fee') border-red-500 @enderror"
                            value="{{ old('activation_fee', $plan->activation_fee ?? 0) }}" placeholder="0.00">
                        <span class="absolute right-3 top-2 text-gray-500 text-sm">€</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Frais appliqués à chaque réservation</p>
                    @error('activation_fee')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="priority" class="block text-sm font-semibold text-gray-700 mb-2">Priorité</label>
                    <input type="number" name="priority" id="priority"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('priority') border-red-500 @enderror"
                        value="{{ old('priority', $plan->priority ?? 0) }}" placeholder="0">
                    <p class="text-xs text-gray-500 mt-1">Plus élevé = plus prioritaire</p>
                    @error('priority')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Section Durée et validité -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
            <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-xl font-bold text-gray-900">Durée et validité</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="max_duration" class="block text-sm font-semibold text-gray-700 mb-2">Durée maximale (minutes) <span class="text-red-500">*</span></label>
                    <input type="number" name="max_duration" id="max_duration" required min="20" max="120"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('max_duration') border-red-500 @enderror"
                        value="{{ old('max_duration', $plan->max_duration ?? 120) }}" placeholder="120">
                    <p class="text-xs text-gray-500 mt-1">Entre 20 et 120 minutes</p>
                    @error('max_duration')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="valid_from" class="block text-sm font-semibold text-gray-700 mb-2">Valide à partir du</label>
                    <input type="datetime-local" name="valid_from" id="valid_from"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('valid_from') border-red-500 @enderror"
                        value="{{ old('valid_from', $plan->valid_from ? $plan->valid_from->format('Y-m-d\TH:i') : '') }}">
                    @error('valid_from')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="valid_until" class="block text-sm font-semibold text-gray-700 mb-2">Valide jusqu'au</label>
                    <input type="datetime-local" name="valid_until" id="valid_until"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('valid_until') border-red-500 @enderror"
                        value="{{ old('valid_until', $plan->valid_until ? $plan->valid_until->format('Y-m-d\TH:i') : '') }}">
                    @error('valid_until')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-6 border-t border-gray-200">
                <div>
                    <label for="currency" class="block text-sm font-semibold text-gray-700 mb-2">Devise</label>
                    <select name="currency" id="currency"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('currency') border-red-500 @enderror">
                        <option value="EUR" {{ old('currency', $plan->currency ?? 'EUR') == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                        <option value="USD" {{ old('currency', $plan->currency ?? 'EUR') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                        <option value="GBP" {{ old('currency', $plan->currency ?? 'EUR') == 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                    </select>
                    @error('currency')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="billing_interval" class="block text-sm font-semibold text-gray-700 mb-2">Intervalle de facturation</label>
                    <select name="billing_interval" id="billing_interval"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('billing_interval') border-red-500 @enderror">
                        @foreach($billingIntervals as $value => $label)
                            <option value="{{ $value }}" {{ old('billing_interval', $plan->billing_interval ?? 'session') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('billing_interval')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>



        <!-- Section Prix supplémentaires prédéfinis -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
            <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-orange-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <h2 class="text-xl font-bold text-gray-900">Prix supplémentaires</h2>
            </div>

            <!-- Prix week-end -->
            <div class="mb-6 pb-6 border-b border-gray-200">
                <div class="flex items-center mb-4">
                    <input type="checkbox" name="has_weekend_pricing" id="has_weekend_pricing" value="1"
                        {{ old('has_weekend_pricing', $plan->has_weekend_pricing ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 text-orange-500 rounded focus:ring-2 focus:ring-orange-500">
                    <label for="has_weekend_pricing" class="ml-3 text-sm font-semibold text-gray-700">
                        Activer les prix week-end
                    </label>
                </div>
                <div id="weekend_pricing_fields" class="ml-8 {{ old('has_weekend_pricing', $plan->has_weekend_pricing ?? false) ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="weekend_price" class="block text-sm font-semibold text-gray-700 mb-2">Prix supplémentaire week-end</label>
                            <div class="relative">
                                <input type="number" name="weekend_price" id="weekend_price" step="0.0001" min="0"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('weekend_price') border-red-500 @enderror"
                                    value="{{ old('weekend_price', $plan->weekend_price ?? 0) }}" placeholder="0.10">
                                <span class="absolute right-3 top-2 text-gray-500 text-sm">€</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Appliqué les samedis et dimanches</p>
                            @error('weekend_price')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prix de nuit -->
            <div>
                <div class="flex items-center mb-4">
                    <input type="checkbox" name="has_night_pricing" id="has_night_pricing" value="1"
                        {{ old('has_night_pricing', $plan->has_night_pricing ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 text-orange-500 rounded focus:ring-2 focus:ring-orange-500">
                    <label for="has_night_pricing" class="ml-3 text-sm font-semibold text-gray-700">
                        Activer les prix de nuit
                    </label>
                </div>
                <div id="night_pricing_fields" class="ml-8 {{ old('has_night_pricing', $plan->has_night_pricing ?? false) ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="night_price" class="block text-sm font-semibold text-gray-700 mb-2">Prix supplémentaire nuit</label>
                            <div class="relative">
                                <input type="number" name="night_price" id="night_price" step="0.0001" min="0"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('night_price') border-red-500 @enderror"
                                    value="{{ old('night_price', $plan->night_price ?? 0) }}" placeholder="0.05">
                                <span class="absolute right-3 top-2 text-gray-500 text-sm">€</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Pendant les heures de nuit</p>
                            @error('night_price')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="night_start_time" class="block text-sm font-semibold text-gray-700 mb-2">Heure de début</label>
                            <input type="time" name="night_start_time" id="night_start_time"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('night_start_time') border-red-500 @enderror"
                                value="{{ old('night_start_time', $plan->night_start_time ?? '22:00') }}">
                            @error('night_start_time')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="night_end_time" class="block text-sm font-semibold text-gray-700 mb-2">Heure de fin</label>
                            <input type="time" name="night_end_time" id="night_end_time"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('night_end_time') border-red-500 @enderror"
                                value="{{ old('night_end_time', $plan->night_end_time ?? '06:00') }}">
                            @error('night_end_time')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Statut et métadonnées -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-gray-400">
            <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-xl font-bold text-gray-900">Statut et métadonnées</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="is_active" class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                            {{ old('is_active', $plan->is_active ?? false) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                        <span class="ml-3 text-sm font-semibold text-gray-700">Plan actif</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-2 ml-7">Cochez pour rendre ce plan disponible</p>
                </div>

                <div>
                    <label for="mobile_theme_color" class="block text-sm font-semibold text-gray-700 mb-2">Couleur du thème mobile</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="mobile_theme_color" id="mobile_theme_color"
                            class="w-12 h-10 border border-gray-300 rounded cursor-pointer"
                            value="{{ old('mobile_theme_color', $plan->mobile_theme_color ?? '#3B82F6') }}">
                        <input type="text" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700 text-sm"
                            id="mobile_theme_color_text" value="{{ old('mobile_theme_color', $plan->mobile_theme_color ?? '#3B82F6') }}" readonly>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="text-gray-600">
                            <span class="font-semibold">Créé le :</span>
                            {{ $plan->created_at->format('d/m/Y à H:i') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600">
                            <span class="font-semibold">Modifié le :</span>
                            {{ $plan->updated_at->format('d/m/Y à H:i') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Boutons d’action -->
        <div class="flex justify-between items-center gap-4 mt-8">
            <a href="{{ route('plans.index') }}" class="btn btn-outline">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Annuler
            </a>
            <div class="flex gap-3">
                <button type="reset" class="btn btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Réinitialiser
                </button>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Sauvegarder les modifications
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let additionalRateIndex = {{ isset($additionalRates) ? count($additionalRates) : 0 }};

const addAdditionalRateButton = document.getElementById('add-additional-rate');

if (addAdditionalRateButton) {
    addAdditionalRateButton.addEventListener('click', function() {
        const container = document.getElementById('additional-rates-container');

        if (!container) {
            return;
        }

        const html = `
            <div class=\"flex gap-2 mb-2 items-end additional-rate-row\">
                <input type=\"text\" name=\"additional_rates[${additionalRateIndex}][name]\" class=\"form-input\" placeholder=\"Nom\">
                <input type=\"number\" step=\"0.01\" name=\"additional_rates[${additionalRateIndex}][price]\" class=\"form-input\" placeholder=\"Prix\">
                <select name=\"additional_rates[${additionalRateIndex}][rate_type]\" class=\"form-select\">
                    <option value=\"fixed\">Fixe</option>
                    <option value=\"time\">À la minute</option>
                    <option value=\"energy\">Par kWh</option>
                </select>
                <input type=\"text\" name=\"additional_rates[${additionalRateIndex}][condition_description]\" class=\"form-input\" placeholder=\"Condition\">
                <button type=\"button\" class=\"btn btn-danger btn-sm\" onclick=\"removeAdditionalRate(this)\">Supprimer</button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        additionalRateIndex++;
    });
}

window.removeAdditionalRate = function(btn) {
    btn.parentElement.remove();
};

function updateTTC() {
    const ht = parseFloat(document.getElementById('base_rate').value) || 0;
    const vatRateSelect = document.getElementById('vat_rate_id');
    const selectedOption = vatRateSelect ? vatRateSelect.options[vatRateSelect.selectedIndex] : null;
    const match = selectedOption ? selectedOption.text.match(/\(([\d.,]+)%\)/) : null;
    const tva = match ? parseFloat(match[1].replace(',', '.')) : 0;
    const ttc = ht * (1 + tva/100);
    document.getElementById('ttc_preview').value = ttc.toFixed(2);
}
if(document.getElementById('base_rate') && document.getElementById('vat_rate_id')) {
    document.getElementById('base_rate').addEventListener('input', updateTTC);
    document.getElementById('vat_rate_id').addEventListener('change', updateTTC);
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

// Sync color picker with text input
const colorPicker = document.getElementById('mobile_theme_color');
const colorText = document.getElementById('mobile_theme_color_text');

if (colorPicker && colorText) {
    colorPicker.addEventListener('input', function() {
        colorText.value = this.value;
    });
}

// Form validation
const form = document.querySelector('form');
if (form) {
    form.addEventListener('submit', function(e) {
        const baseRate = document.getElementById('base_rate');
        const maxDuration = document.getElementById('max_duration');

        if (baseRate && parseFloat(baseRate.value) < 0) {
            e.preventDefault();
            alert('Le prix HT ne peut pas être négatif');
            baseRate.focus();
            return false;
        }

        if (maxDuration && (parseInt(maxDuration.value) < 20 || parseInt(maxDuration.value) > 120)) {
            e.preventDefault();
            alert('La durée maximale doit être entre 20 et 120 minutes');
            maxDuration.focus();
            return false;
        }
    });
}
</script>
@endpush
