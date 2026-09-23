@extends('layouts.app')

@section('title', 'Ajouter un Plan Tarifaire')
@section('page-title', 'Ajouter un Plan Tarifaire')

@push('styles')
<style>
    .rate-tab { display: none; }
    .rate-tab.active { display: block; }
</style>
@endpush

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="bg-gradient-to-r from-orange-500 to-amber-500 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.pricing-plans.index') }}" class="text-orange-200 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold">Créer un Plan Tarifaire</h1>
                <p class="text-orange-100 text-sm mt-0.5">Configurez une grille de prix pour vos bornes</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 rounded-xl p-4">
            <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pricing-plans.store') }}" class="space-y-6" x-data="{ rateType: '{{ old('rate_type', 'kwh') }}' }">
        @csrf

        {{-- Type de tarif --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="w-7 h-7 bg-orange-100 dark:bg-orange-900/40 rounded-lg flex items-center justify-center">
                    <i class="fas fa-tags text-orange-600 text-xs"></i>
                </span>
                Type de facturation
            </h2>
            <div class="grid grid-cols-3 gap-3">
                @foreach(['minute' => ['label' => 'À la minute', 'icon' => 'fa-clock', 'desc' => 'Prix par min de recharge'],
                           'kwh'    => ['label' => 'Par kWh', 'icon' => 'fa-bolt', 'desc' => 'Prix par kWh consommé'],
                           'fixed'  => ['label' => 'Prix fixe', 'icon' => 'fa-tag', 'desc' => 'Montant fixe/recharge']] as $type => $info)
                    <label class="flex flex-col items-center gap-2 p-4 border-2 rounded-xl cursor-pointer transition-all"
                           :class="rateType === '{{ $type }}' ? 'border-orange-400 bg-orange-50 dark:bg-orange-900/20' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                        <input type="radio" name="rate_type" value="{{ $type }}" x-model="rateType" class="sr-only">
                        <i class="fas {{ $info['icon'] }} text-xl {{ old('rate_type', 'kwh') === $type ? 'text-orange-500' : 'text-gray-400' }}"
                           :class="rateType === '{{ $type }}' ? 'text-orange-500' : 'text-gray-400'"></i>
                        <div class="text-center">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $info['label'] }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $info['desc'] }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Informations --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Informations du plan</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom du plan <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ex: Tarif Standard Casablanca"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 @error('name') border-red-500 @enderror">
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Prix selon le type --}}
                <div x-show="rateType === 'minute'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prix par minute <span class="text-red-500">*</span></label>
                    <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-orange-500">
                        <input type="number" name="price_per_minute" value="{{ old('price_per_minute') }}" step="0.01" min="0" placeholder="0.00"
                               class="flex-1 px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none">
                        <span class="px-3 py-2 bg-gray-50 dark:bg-gray-600 text-gray-500 dark:text-gray-400 text-sm border-l border-gray-300 dark:border-gray-600">MAD/min</span>
                    </div>
                </div>

                <div x-show="rateType === 'kwh'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prix par kWh <span class="text-red-500">*</span></label>
                    <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-orange-500">
                        <input type="number" name="price_per_kwh" value="{{ old('price_per_kwh') }}" step="0.01" min="0" placeholder="0.00"
                               class="flex-1 px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none">
                        <span class="px-3 py-2 bg-gray-50 dark:bg-gray-600 text-gray-500 dark:text-gray-400 text-sm border-l border-gray-300 dark:border-gray-600">MAD/kWh</span>
                    </div>
                </div>

                <div x-show="rateType === 'fixed'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prix fixe par recharge <span class="text-red-500">*</span></label>
                    <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-orange-500">
                        <input type="number" name="fixed_price" value="{{ old('fixed_price') }}" step="0.01" min="0" placeholder="0.00"
                               class="flex-1 px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none">
                        <span class="px-3 py-2 bg-gray-50 dark:bg-gray-600 text-gray-500 dark:text-gray-400 text-sm border-l border-gray-300 dark:border-gray-600">MAD</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Frais d'activation (optionnel)</label>
                    <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-orange-500">
                        <input type="number" name="activation_fee" value="{{ old('activation_fee', 0) }}" step="0.01" min="0" placeholder="0.00"
                               class="flex-1 px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none">
                        <span class="px-3 py-2 bg-gray-50 dark:bg-gray-600 text-gray-500 dark:text-gray-400 text-sm border-l border-gray-300 dark:border-gray-600">MAD</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">TVA</label>
                    <select name="vat_rate_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                        <option value="">Sans TVA</option>
                        @foreach($vatRates as $vat)
                            <option value="{{ $vat->id }}" @selected(old('vat_rate_id') == $vat->id || $vat->is_default)>
                                {{ $vat->name }} ({{ $vat->rate }}%)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Devise</label>
                    <select name="currency" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                        <option value="MAD" @selected(old('currency', 'MAD') === 'MAD')>MAD — Dirham</option>
                        <option value="EUR" @selected(old('currency') === 'EUR')>EUR — Euro</option>
                        <option value="USD" @selected(old('currency') === 'USD')>USD — Dollar</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priorité (0 = plus haute)</label>
                    <input type="number" name="priority" value="{{ old('priority', 0) }}" min="0" max="100"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durée max (min, optionnel)</label>
                    <input type="number" name="max_duration" value="{{ old('max_duration') }}" min="0" placeholder="illimité"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valide du</label>
                    <input type="date" name="valid_from" value="{{ old('valid_from') }}"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valide au</label>
                    <input type="date" name="valid_until" value="{{ old('valid_until') }}"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea name="description" rows="2" placeholder="Description du plan..."
                              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">{{ old('description') }}</textarea>
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="text-orange-500 rounded">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Actif</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_public" value="1" {{ old('is_public') ? 'checked' : '' }} class="text-orange-500 rounded">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Public</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Groupes associés --}}
        @if($groups->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Groupes associés</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto">
                @foreach($groups as $group)
                    <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <input type="checkbox" name="group_ids[]" value="{{ $group->id }}"
                               {{ in_array($group->id, old('group_ids', [])) ? 'checked' : '' }}
                               class="text-orange-500 rounded">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $group->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $group->city ?? '' }} · {{ ucfirst($group->type) }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.pricing-plans.index') }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 transition-colors">
                Annuler
            </a>
            <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg transition-colors shadow-sm">
                <i class="fas fa-save mr-1.5"></i> Créer le plan tarifaire
            </button>
        </div>
    </form>
</div>
@endsection
