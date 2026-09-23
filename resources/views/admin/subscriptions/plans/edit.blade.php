@extends('layouts.app')

@section('title', 'Modifier — ' . $plan->name)

@section('content')
<div class="space-y-5"
     x-data="planForm({
         type: '{{ old('type', $plan->type) }}',
         price: {{ old('price', $plan->price) }},
         vatRate: {{ old('vat_rate', $plan->vat_rate ?? 0) }},
         vatRateId: '{{ old('vat_rate_id', $plan->vat_rate_id ?? '') }}',
         planName: @json(old('name', $plan->name)),
         allowRenewal: {{ old('allow_renewal', $plan->allow_renewal) ? 'true' : 'false' }},
         isActive: {{ old('is_active', $plan->is_active) ? 'true' : 'false' }},
         isFeatured: {{ old('is_featured', $plan->is_featured) ? 'true' : 'false' }},
         features: @json(old('features', $plan->features ?? [])),
     })">

    {{-- ── Header ─────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 px-6 py-4">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('admin.subscriptions.plans.show', $plan->id) }}"
                   class="flex-shrink-0 w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-eco-green-100 hover:text-eco-green-600 transition-colors">
                    <i class="fas fa-arrow-left text-xs"></i>
                </a>
                <div class="min-w-0">
                    <nav class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500 mb-0.5">
                        <span>Abonnements</span>
                        <i class="fas fa-chevron-right text-[9px]"></i>
                        <a href="{{ route('admin.subscriptions.plans.index') }}" class="hover:text-eco-green-600 transition-colors">Plans</a>
                        <i class="fas fa-chevron-right text-[9px]"></i>
                        <span class="text-gray-600 dark:text-gray-300 truncate">{{ $plan->name }}</span>
                    </nav>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white truncate">
                        Modifier : <span class="text-eco-green-600 dark:text-eco-green-400">{{ $plan->name }}</span>
                    </h1>
                </div>
            </div>
            <button type="submit" form="plan-form"
                    class="flex-shrink-0 inline-flex items-center gap-2 px-5 py-2.5 bg-eco-green-600 hover:bg-eco-green-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </div>
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                <div>
                    <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-1">Veuillez corriger les erreurs suivantes :</p>
                    <ul class="text-sm text-red-600 dark:text-red-400 space-y-0.5 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form id="plan-form" method="POST" action="{{ route('admin.subscriptions.plans.update', $plan->id) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- ── LEFT COLUMN (2/3) ────────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- ① General Info --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-eco-green-100 dark:bg-eco-green-900/30 flex items-center justify-center">
                            <i class="fas fa-info-circle text-eco-green-600 dark:text-eco-green-400 text-xs"></i>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Informations générales</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Name --}}
                            <div class="sm:col-span-2">
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nom du plan <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name"
                                       value="{{ old('name', $plan->name) }}" required
                                       x-model="planName"
                                       class="w-full px-3 py-2 border @error('name') border-red-400 @else border-gray-300 dark:border-gray-600 @enderror rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                                @error('name')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- Type --}}
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Type de plan <span class="text-red-500">*</span>
                                </label>
                                <select id="type" name="type" required
                                        x-model="type"
                                        class="w-full px-3 py-2 border @error('type') border-red-400 @else border-gray-300 dark:border-gray-600 @enderror rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                                    @foreach($types as $value => $label)
                                        <option value="{{ $value }}" {{ old('type', $plan->type) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- Sort Order --}}
                            <div>
                                <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Ordre d'affichage
                                </label>
                                <input type="number" id="sort_order" name="sort_order"
                                       value="{{ old('sort_order', $plan->sort_order ?? 0) }}" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                            </div>
                        </div>
                        {{-- Description --}}
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500 resize-none">{{ old('description', $plan->description) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- ② Pricing --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <i class="fas fa-euro-sign text-blue-600 dark:text-blue-400 text-xs"></i>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Tarification</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Price --}}
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Prix HT (EUR) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">€</span>
                                    <input type="number" id="price" name="price"
                                           value="{{ old('price', $plan->price) }}" step="0.01" min="0" required
                                           x-model.number="price"
                                           class="w-full pl-7 pr-3 py-2 border @error('price') border-red-400 @else border-gray-300 dark:border-gray-600 @enderror rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                                </div>
                                @error('price')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- VAT Rate --}}
                            <div>
                                <label for="vat_rate_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Taux de TVA
                                </label>
                                <select id="vat_rate_id" name="vat_rate_id"
                                        @change="onVatSelect($event)"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                                    <option value="">Personnalisé…</option>
                                    @foreach($vatRates as $vr)
                                        <option value="{{ $vr->id }}"
                                                data-rate="{{ $vr->rate }}"
                                                {{ old('vat_rate_id', $plan->vat_rate_id) == $vr->id ? 'selected' : '' }}>
                                            {{ $vr->name }} ({{ $vr->rate }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Manual VAT --}}
                            <div x-show="!vatRateId">
                                <label for="vat_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    TVA personnalisée (%)
                                </label>
                                <div class="relative">
                                    <input type="number" id="vat_rate" name="vat_rate"
                                           value="{{ old('vat_rate', $plan->vat_rate ?? 0) }}" step="0.01" min="0" max="100"
                                           x-model.number="vatRate"
                                           class="w-full pr-7 pl-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                                </div>
                            </div>
                            {{-- Duration --}}
                            <div>
                                <label for="duration_months" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Durée du cycle (mois)
                                </label>
                                <input type="number" id="duration_months" name="duration_months"
                                       value="{{ old('duration_months', $plan->duration_months) }}" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                                <p class="mt-1 text-xs text-gray-400">0 = durée selon type</p>
                            </div>
                            {{-- Min Contract --}}
                            <div>
                                <label for="min_contract_months" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Durée min. contrat (mois)
                                </label>
                                <input type="number" id="min_contract_months" name="min_contract_months"
                                       value="{{ old('min_contract_months', $plan->min_contract_months) }}" min="1"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                            </div>
                            {{-- Cancellation Fee --}}
                            <div>
                                <label for="cancellation_fee" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Frais de résiliation (EUR)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">€</span>
                                    <input type="number" id="cancellation_fee" name="cancellation_fee"
                                           value="{{ old('cancellation_fee', $plan->cancellation_fee) }}" step="0.01" min="0"
                                           class="w-full pl-7 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                                </div>
                            </div>
                        </div>

                        {{-- Live Price Calculator --}}
                        <div class="rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                                <i class="fas fa-calculator mr-1.5"></i>Récapitulatif tarifaire
                            </p>
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Prix HT</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white"
                                       x-text="formatCurrency(priceHT)"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">TVA (<span x-text="vatRate + '%'"></span>)</p>
                                    <p class="text-lg font-bold text-amber-600 dark:text-amber-400"
                                       x-text="formatCurrency(vatAmount)"></p>
                                </div>
                                <div class="bg-eco-green-50 dark:bg-eco-green-900/20 rounded-lg p-2">
                                    <p class="text-xs text-eco-green-600 dark:text-eco-green-400 mb-1 font-medium">Prix TTC</p>
                                    <p class="text-lg font-bold text-eco-green-700 dark:text-eco-green-300"
                                       x-text="formatCurrency(priceTTC)"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ③ Quotas --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <i class="fas fa-tachometer-alt text-purple-600 dark:text-purple-400 text-xs"></i>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Limitations et quotas</h2>
                        <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">Laisser vide = illimité</span>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div x-show="showSessionQuota">
                                <label for="max_sessions" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fas fa-bolt text-blue-500 mr-1"></i>Sessions max
                                </label>
                                <input type="number" id="max_sessions" name="max_sessions"
                                       value="{{ old('max_sessions', $plan->max_sessions) }}" min="0" placeholder="Illimité"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                            </div>
                            <div x-show="showKwhQuota">
                                <label for="max_kwh" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fas fa-battery-three-quarters text-amber-500 mr-1"></i>Quota kWh max
                                </label>
                                <input type="number" id="max_kwh" name="max_kwh"
                                       value="{{ old('max_kwh', $plan->max_kwh) }}" step="0.01" min="0" placeholder="Illimité"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                            </div>
                            <div x-show="showDurationQuota">
                                <label for="max_duration_minutes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fas fa-clock text-purple-500 mr-1"></i>Durée max (minutes)
                                </label>
                                <input type="number" id="max_duration_minutes" name="max_duration_minutes"
                                       value="{{ old('max_duration_minutes', $plan->max_duration_minutes) }}" min="0" placeholder="Illimité"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                            </div>
                            <div>
                                <label for="max_charging_points" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fas fa-charging-station text-eco-green-500 mr-1"></i>Bornes accessibles max
                                </label>
                                <input type="number" id="max_charging_points" name="max_charging_points"
                                       value="{{ old('max_charging_points', $plan->max_charging_points) }}" min="0" placeholder="Illimité"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ④ Features List --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <i class="fas fa-list-check text-amber-600 dark:text-amber-400 text-xs"></i>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Points forts du plan</h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <template x-for="(feature, idx) in features" :key="idx">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 flex-shrink-0 rounded-full bg-eco-green-100 dark:bg-eco-green-900/30 flex items-center justify-center">
                                    <i class="fas fa-check text-eco-green-600 dark:text-eco-green-400 text-[10px]"></i>
                                </div>
                                <input type="text" :name="'features[' + idx + ']'" x-model="features[idx]"
                                       class="flex-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500">
                                <button type="button" @click="removeFeature(idx)"
                                        class="w-7 h-7 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-400 hover:text-red-600 hover:bg-red-100 transition-colors flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </template>
                        <div class="flex items-center gap-2 pt-1">
                            <input type="text" x-model="newFeature"
                                   @keydown.enter.prevent="addFeature()"
                                   placeholder="Ajouter un avantage…"
                                   class="flex-1 px-3 py-1.5 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                            <button type="button" @click="addFeature()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-eco-green-50 dark:bg-eco-green-900/20 text-eco-green-700 dark:text-eco-green-400 text-sm font-medium rounded-lg hover:bg-eco-green-100 transition-colors flex-shrink-0">
                                <i class="fas fa-plus text-xs"></i> Ajouter
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ⑤ Terms --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <i class="fas fa-file-contract text-gray-500 dark:text-gray-400 text-xs"></i>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Conditions générales</h2>
                    </div>
                    <div class="p-6">
                        <textarea id="terms_conditions" name="terms_conditions" rows="5"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500 resize-none">{{ old('terms_conditions', $plan->terms_conditions) }}</textarea>
                    </div>
                </div>

            </div>{{-- end left --}}

            {{-- ── RIGHT COLUMN (1/3) ───────────────────────────────── --}}
            <div class="space-y-5">

                {{-- Plan Preview Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <i class="fas fa-eye text-gray-400 text-sm"></i>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Aperçu en direct</h3>
                    </div>
                    <div class="p-5">
                        <div class="rounded-xl bg-gradient-to-br from-eco-green-50 to-teal-50 dark:from-eco-green-900/20 dark:to-teal-900/20 border border-eco-green-200 dark:border-eco-green-800/50 p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate"
                                       x-text="planName || 'Nom du plan'"></p>
                                    <template x-if="type">
                                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-white/70 dark:bg-gray-800/70 text-gray-600 dark:text-gray-300"
                                              x-text="getTypeLabel()"></span>
                                    </template>
                                </div>
                                <template x-if="isFeatured">
                                    <span class="flex-shrink-0 ml-2 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-amber-100 text-amber-600 text-[11px] font-semibold">
                                        <i class="fas fa-star text-[9px]"></i> Pro
                                    </span>
                                </template>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-2xl font-black text-eco-green-700 dark:text-eco-green-300"
                                          x-text="formatCurrency(priceHT)"></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">HT</span>
                                </div>
                                <template x-if="vatRate > 0">
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        <span x-text="formatCurrency(priceTTC)"></span>
                                        <span class="text-gray-400"> TTC</span>
                                    </p>
                                </template>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <template x-if="isActive">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-eco-green-100 text-eco-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-eco-green-500"></span> Actif
                                    </span>
                                </template>
                                <template x-if="!isActive">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactif
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Access Control --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                            <i class="fas fa-shield-alt text-indigo-600 dark:text-indigo-400 text-xs"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Contrôle d'accès</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        @if($groups->count() > 0)
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                                <i class="fas fa-users mr-1"></i>Groupes clients
                            </p>
                            <div class="max-h-36 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($groups as $group)
                                    <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                        <input type="checkbox" name="group_ids[]" value="{{ $group->id }}"
                                               {{ in_array($group->id, old('group_ids', $selectedGroupIds)) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-eco-green-600 focus:ring-eco-green-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $group->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Aucune sélection = tous les groupes</p>
                        </div>
                        @endif

                        @if($stations->count() > 0)
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                                <i class="fas fa-map-marker-alt mr-1"></i>Stations
                            </p>
                            <div class="max-h-36 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($stations as $station)
                                    <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                        <input type="checkbox" name="station_ids[]" value="{{ $station->id }}"
                                               {{ in_array($station->id, old('station_ids', $selectedStationIds)) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-eco-green-600 focus:ring-eco-green-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $station->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Aucune sélection = toutes les stations</p>
                        </div>
                        @endif

                        @if($chargingPoints->count() > 0)
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                                <i class="fas fa-charging-station mr-1"></i>Bornes spécifiques
                            </p>
                            <div class="max-h-36 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($chargingPoints as $cp)
                                    <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                        <input type="checkbox" name="charging_point_ids[]" value="{{ $cp->id }}"
                                               {{ in_array($cp->id, old('charging_point_ids', $selectedChargingPointIds)) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-eco-green-600 focus:ring-eco-green-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $cp->name ?? $cp->charge_point_id }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Aucune sélection = toutes les bornes</p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Options --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-eco-green-100 dark:bg-eco-green-900/30 flex items-center justify-center">
                            <i class="fas fa-sliders-h text-eco-green-600 dark:text-eco-green-400 text-xs"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Options & Statut</h3>
                    </div>
                    <div class="p-5 space-y-1">
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-center justify-between py-2.5 cursor-pointer">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Plan actif</p>
                                <p class="text-xs text-gray-400">Visible et disponible à la souscription</p>
                            </div>
                            <div class="relative flex-shrink-0 ml-3">
                                <input type="checkbox" name="is_active" value="1"
                                       {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                                       x-model="isActive"
                                       class="sr-only peer">
                                <div class="w-10 h-5 bg-gray-200 dark:bg-gray-600 peer-checked:bg-eco-green-500 rounded-full transition-colors cursor-pointer"></div>
                                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                            </div>
                        </label>

                        <input type="hidden" name="is_featured" value="0">
                        <label class="flex items-center justify-between py-2.5 cursor-pointer border-t border-gray-100 dark:border-gray-700">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Mis en avant</p>
                                <p class="text-xs text-gray-400">Affiché en priorité aux clients</p>
                            </div>
                            <div class="relative flex-shrink-0 ml-3">
                                <input type="checkbox" name="is_featured" value="1"
                                       {{ old('is_featured', $plan->is_featured) ? 'checked' : '' }}
                                       x-model="isFeatured"
                                       class="sr-only peer">
                                <div class="w-10 h-5 bg-gray-200 dark:bg-gray-600 peer-checked:bg-amber-400 rounded-full transition-colors cursor-pointer"></div>
                                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                            </div>
                        </label>

                        <input type="hidden" name="allow_renewal" value="0">
                        <label class="flex items-center justify-between py-2.5 cursor-pointer border-t border-gray-100 dark:border-gray-700">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Renouvellement auto.</p>
                                <p class="text-xs text-gray-400">Le plan peut être renouvelé</p>
                            </div>
                            <div class="relative flex-shrink-0 ml-3">
                                <input type="checkbox" name="allow_renewal" value="1"
                                       {{ old('allow_renewal', $plan->allow_renewal) ? 'checked' : '' }}
                                       x-model="allowRenewal"
                                       class="sr-only peer">
                                <div class="w-10 h-5 bg-gray-200 dark:bg-gray-600 peer-checked:bg-blue-500 rounded-full transition-colors cursor-pointer"></div>
                                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                            </div>
                        </label>

                        <input type="hidden" name="allow_upgrade" value="0">
                        <label class="flex items-center justify-between py-2.5 cursor-pointer border-t border-gray-100 dark:border-gray-700">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Mise à niveau</p>
                                <p class="text-xs text-gray-400">Upgrade autorisé</p>
                            </div>
                            <div class="relative flex-shrink-0 ml-3">
                                <input type="checkbox" name="allow_upgrade" value="1"
                                       {{ old('allow_upgrade', $plan->allow_upgrade) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-10 h-5 bg-gray-200 dark:bg-gray-600 peer-checked:bg-blue-500 rounded-full transition-colors cursor-pointer"></div>
                                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                            </div>
                        </label>

                        <input type="hidden" name="allow_downgrade" value="0">
                        <label class="flex items-center justify-between py-2.5 cursor-pointer border-t border-gray-100 dark:border-gray-700">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Déclassement</p>
                                <p class="text-xs text-gray-400">Downgrade autorisé</p>
                            </div>
                            <div class="relative flex-shrink-0 ml-3">
                                <input type="checkbox" name="allow_downgrade" value="1"
                                       {{ old('allow_downgrade', $plan->allow_downgrade) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-10 h-5 bg-gray-200 dark:bg-gray-600 peer-checked:bg-blue-500 rounded-full transition-colors cursor-pointer"></div>
                                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col gap-2">
                    <button type="submit" form="plan-form"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-eco-green-600 hover:bg-eco-green-700 text-white text-sm font-semibold rounded-xl shadow transition-all">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                    <a href="{{ route('admin.subscriptions.plans.show', $plan->id) }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>

            </div>{{-- end right --}}
        </div>
    </form>
</div>

@push('scripts')
<script>
function planForm(config) {
    const typeLabels = {
        per_charge:  'Par recharge',
        per_kwh:     'Par kWh',
        per_time:    'Par temps global',
        per_session: 'Par session',
        monthly:     'Mensuel (1 mois)',
        quarterly:   'Trimestriel (3 mois)',
        semi_annual: 'Semestriel (6 mois)',
        annual:      'Annuel (12 mois)',
    };
    return {
        type:         config.type         || '',
        price:        config.price        || 0,
        vatRate:      config.vatRate      || 0,
        vatRateId:    config.vatRateId    || '',
        planName:     config.planName     || '',
        allowRenewal: config.allowRenewal !== undefined ? config.allowRenewal : true,
        isActive:     config.isActive     !== undefined ? config.isActive     : true,
        isFeatured:   config.isFeatured   || false,
        features:     config.features     || [],
        newFeature:   '',

        get priceHT()   { return parseFloat(this.price)   || 0; },
        get vatAmount() { return this.priceHT * (parseFloat(this.vatRate) || 0) / 100; },
        get priceTTC()  { return this.priceHT + this.vatAmount; },

        get showSessionQuota()  { return this.type === 'per_charge' || this.type === 'per_session'; },
        get showKwhQuota()      { return this.type === 'per_kwh'; },
        get showDurationQuota() { return this.type === 'per_time'; },

        formatCurrency(val) {
            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val || 0);
        },
        getTypeLabel() { return typeLabels[this.type] || this.type; },
        onVatSelect(event) {
            this.vatRateId = event.target.value;
            const opt = event.target.options[event.target.selectedIndex];
            if (opt && opt.dataset.rate) this.vatRate = parseFloat(opt.dataset.rate);
        },
        addFeature() {
            const f = this.newFeature.trim();
            if (f) { this.features.push(f); this.newFeature = ''; }
        },
        removeFeature(idx) { this.features.splice(idx, 1); },
    };
}
</script>
@endpush
@endsection
