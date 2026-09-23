@extends('layouts.app')

@section('page-title', 'Demande de Recharge')

@push('styles')
<style>
    .pack-card.selected { border-color: #10b981 !important; background: rgba(16, 185, 129, 0.08); }
    .payment-method-card.selected { border-color: #10b981 !important; }
</style>
@endpush

@section('content')
<div class="bg-gray-50 dark:bg-gray-950 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-5">
        <!-- Header compact -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-green-600 text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Demande de Recharge</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Choisissez un montant et une méthode de paiement</p>
                </div>
            </div>
            <!-- Solde inline (mis à jour via walletBalanceUpdated) -->
            <div class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                <span class="text-sm text-gray-500 dark:text-gray-400">Solde :</span>
                <span class="text-lg font-bold text-green-600 dark:text-green-400" data-balance-value>{{ $formattedBalance }}</span>
            </div>
        </div>

        <!-- Messages -->
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-800 dark:text-green-200 rounded-r text-sm flex items-center gap-2" role="alert">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-800 dark:text-red-200 rounded-r text-sm flex items-center gap-2" role="alert">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 text-blue-800 dark:text-blue-200 rounded-r flex items-center gap-2 text-sm" role="alert">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <p>{{ session('info') }}</p>
            </div>
        @endif

        <!-- Formulaire recharge compact -->
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-5 mb-6">
            <form action="{{ route('credit-recharge.store') }}" method="POST" id="rechargeForm">
                        @csrf
                        
                        <!-- Montant -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Montant</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2" id="packsContainer">
                                @foreach($creditPacks as $pack)
                                <label class="pack-card relative flex flex-col p-3 bg-gray-50 dark:bg-gray-800/50 border-2 border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:border-green-500 pack-option transition-all" 
                                       style="border-color: {{ $pack->color ?? '#10b981' }}30;"
                                       data-pack-id="{{ $pack->id }}"
                                       data-pack-amount="{{ $pack->total_amount_with_bonus }}">
                                    <input type="radio" name="recharge_type" value="pack" class="sr-only recharge-type-radio" data-pack-id="{{ $pack->id }}">
                                    <div class="flex items-center justify-between gap-1 mb-1">
                                        <span class="font-medium text-gray-900 dark:text-gray-100 text-sm truncate">{{ $pack->name }}</span>
                                        @if($pack->is_featured)
                                        <span class="shrink-0 w-1.5 h-1.5 bg-yellow-500 rounded-full" title="Populaire"></span>
                                        @endif
                                    </div>
                                    <div class="text-xl font-bold" style="color: {{ $pack->color ?? '#10b981' }}">{{ $pack->formatted_amount }}</div>
                                    @if($pack->hasBonus())
                                    <div class="text-xs text-green-600 dark:text-green-400 mt-0.5">{{ $pack->bonus_description }}</div>
                                    @endif
                                    <svg class="w-4 h-4 text-green-500 absolute top-2 right-2 hidden check-icon" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </label>
                                @endforeach
                                
                                <!-- Option Personnalisée -->
                                <label class="pack-card relative flex flex-col p-3 bg-gray-50 dark:bg-gray-800/50 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-green-500 pack-option custom-option items-center justify-center min-h-[72px]">
                                    <input type="radio" name="recharge_type" value="custom" class="sr-only recharge-type-radio" id="recharge_type_custom">
                                    <svg class="w-5 h-5 text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    <span class="font-medium text-gray-700 dark:text-gray-300 text-sm">Personnalisé</span>
                                    <svg class="w-4 h-4 text-green-500 absolute top-2 right-2 hidden check-icon" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </label>
                            </div>
                            
                            <input type="hidden" name="credit_pack_id" id="credit_pack_id" value="">
                            <div class="mt-3 hidden" id="customAmountContainer">
                                <label for="amount" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Montant (€)</label>
                                <input type="number" id="amount" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}"
                                       class="w-full max-w-[180px] px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-800 dark:text-gray-100"
                                       placeholder="0.00">
                                @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <!-- Méthode de paiement -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Méthode de paiement</label>
                            
                            @if(isset($paymentMethods['offline']) && $paymentMethods['offline']['enabled'] && auth()->user()->hasRole('user') && !auth()->user()->hasAnyRole(['admin', 'super-admin', 'integrator', 'operator', 'partner']))
                            <p class="mb-3 text-xs text-blue-700 dark:text-blue-300">Demande soumise à confirmation admin.</p>
                            @endif
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                @php
                                    $firstMethod = true;
                                @endphp
                                
                                {{-- IMPORTANT: CMI et Stripe sont affichés pour TOUS les utilisateurs authentifiés,
                                    y compris les clients avec le rôle 'user', s'ils sont configurés et activés --}}
                                
                                @if(isset($paymentMethods['offline']) && $paymentMethods['offline']['enabled'])
                                <label class="payment-method-card relative flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 border-2 border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:border-green-500" data-method="offline">
                                    <input type="radio" name="payment_method" value="offline" class="sr-only" required {{ ($filters['payment_method'] ?? '') === 'offline' || ($firstMethod && ($filters['payment_method'] ?? '') === '') ? 'checked' : '' }}>
                                    @php $isFirstOffline = $firstMethod; $firstMethod = false; @endphp
                                    <div class="p-1.5 bg-gray-200 dark:bg-gray-700 rounded"><svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">Hors Ligne</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Confirmation admin</div>
                                    </div>
                                    <svg class="w-4 h-4 text-green-500 payment-check-icon {{ $isFirstOffline ? '' : 'hidden' }} shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </label>
                                @endif

                                @if(isset($paymentMethods['cmi']) || true)
                                <label class="payment-method-card relative flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 border-2 border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:border-green-500 {{ (isset($paymentMethods['cmi']) && !$paymentMethods['cmi']['enabled']) ? 'opacity-50 cursor-not-allowed' : '' }}" data-method="cmi">
                                    <input type="radio" name="payment_method" value="cmi" class="sr-only" required {{ ($filters['payment_method'] ?? '') === 'cmi' ? 'checked' : '' }} {{ $firstMethod && ($filters['payment_method'] ?? '') === '' && (!isset($paymentMethods['offline']) || !$paymentMethods['offline']['enabled']) ? 'checked' : '' }} {{ (isset($paymentMethods['cmi']) && !$paymentMethods['cmi']['enabled']) ? 'disabled' : '' }}>
                                    @php $isFirstCmi = $firstMethod; $firstMethod = false; @endphp
                                    <div class="p-1.5 bg-blue-100 dark:bg-blue-900/30 rounded"><svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">Carte CMI</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ (isset($paymentMethods['cmi']) && $paymentMethods['cmi']['enabled']) ? 'Maroc · Instantané' : 'Non configuré' }}</div>
                                    </div>
                                    <svg class="w-4 h-4 text-green-500 payment-check-icon {{ $isFirstCmi ? '' : 'hidden' }} shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </label>
                                @endif

                                @if(isset($paymentMethods['stripe']) || true)
                                <label class="payment-method-card relative flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 border-2 border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:border-green-500 {{ (isset($paymentMethods['stripe']) && !$paymentMethods['stripe']['enabled']) ? 'opacity-50 cursor-not-allowed' : '' }}" data-method="stripe">
                                    <input type="radio" name="payment_method" value="stripe" class="sr-only" required {{ ($filters['payment_method'] ?? '') === 'stripe' ? 'checked' : '' }} {{ $firstMethod && ($filters['payment_method'] ?? '') === '' && (!isset($paymentMethods['offline']) || !$paymentMethods['offline']['enabled']) && (!isset($paymentMethods['cmi']) || !$paymentMethods['cmi']['enabled']) ? 'checked' : '' }} {{ (isset($paymentMethods['stripe']) && !$paymentMethods['stripe']['enabled']) ? 'disabled' : '' }}>
                                    @php $isFirstStripe = $firstMethod; $firstMethod = false; @endphp
                                    <div class="p-1.5 bg-purple-100 dark:bg-purple-900/30 rounded"><svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">Carte Stripe</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ (isset($paymentMethods['stripe']) && $paymentMethods['stripe']['enabled']) ? 'International · Instantané' : 'Non configuré' }}</div>
                                    </div>
                                    <svg class="w-4 h-4 text-green-500 payment-check-icon {{ $isFirstStripe ? '' : 'hidden' }} shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </label>
                                @endif
                            </div>
                            @error('payment_method')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Description (optionnelle)</label>
                            <textarea id="description" name="description" rows="2"
                                      class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-800 dark:text-gray-100 resize-none"
                                      placeholder="Note...">{{ old('description') }}</textarea>
                        </div>

                        <button type="submit" 
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Demander une recharge</span>
                        </button>
                    </form>
        </div>

        <!-- Historique -->
        <div class="mt-5">
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Mes demandes</h2>
                    
                    <!-- Filtres pour clients -->
                    @php
                        $user = auth()->user();
                        $userRoles = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
                        $isClientOnly = count($userRoles) === 1 && in_array('user', $userRoles);
                    @endphp
                    
                    @if($isClientOnly)
                        <form method="GET" action="{{ route('credit-recharge.index') }}" class="flex flex-wrap items-center gap-2">
                            <select name="status" class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-800 dark:text-white">
                                    <option value="">{{ __('Tous les statuts') }}</option>
                                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>{{ __('En attente') }}</option>
                                    <option value="processing" {{ ($filters['status'] ?? '') === 'processing' ? 'selected' : '' }}>{{ __('En traitement') }}</option>
                                    <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>{{ __('Complétée') }}</option>
                                    <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>{{ __('Échouée') }}</option>
                                    <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>{{ __('Annulée') }}</option>
                                </select>
                            <select name="payment_method" class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-800 dark:text-white">
                                <option value="">{{ __('Toutes les méthodes') }}</option>
                                <option value="offline" {{ ($filters['payment_method'] ?? '') === 'offline' ? 'selected' : '' }}>{{ __('Hors ligne') }}</option>
                                <option value="cmi" {{ ($filters['payment_method'] ?? '') === 'cmi' ? 'selected' : '' }}>CMI</option>
                                <option value="stripe" {{ ($filters['payment_method'] ?? '') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                            </select>
                            <button type="submit" class="px-3 py-1.5 text-sm bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg">{{ __('Filtrer') }}</button>
                            @if(!empty($filters))
                                <a href="{{ route('credit-recharge.index') }}" class="px-3 py-1.5 text-sm bg-gray-500 hover:bg-gray-600 text-white rounded-lg">{{ __('Réinitialiser') }}</a>
                            @endif
                        </form>
                    @endif
                </div>
                
                @if($recharges->count() > 0)
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 dark:text-gray-400">Réf.</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 dark:text-gray-400">Montant</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 dark:text-gray-400">Méthode</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 dark:text-gray-400">Statut</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 dark:text-gray-400">Date</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-600 dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($recharges as $recharge)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900 dark:text-gray-100">{{ $recharge->reference }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap font-semibold text-gray-900 dark:text-gray-100">{{ $recharge->formatted_amount }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $recharge->payment_method_name }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                            @if($recharge->status === 'completed') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300
                                            @elseif($recharge->status === 'pending') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300
                                            @elseif($recharge->status === 'processing') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300
                                            @elseif($recharge->status === 'failed') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300
                                            @else bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300
                                            @endif">{{ ucfirst($recharge->status) }}</span>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $recharge->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right">
                                        <a href="{{ route('credit-recharge.show', $recharge) }}" class="text-green-600 hover:text-green-700 dark:text-green-400 text-xs font-medium">Voir</a>
                                        @if(!$recharge->isCompleted() && !$recharge->isFailed())
                                        <form action="{{ route('credit-recharge.cancel', $recharge) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('Annuler cette recharge ?');">
                                            @csrf
                                            <button type="submit" class="text-red-600 hover:text-red-700 dark:text-red-400 text-xs font-medium">Annuler</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">{{ $recharges->links() }}</div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Aucune demande pour le moment</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    // Gestion de la sélection des packs améliorée
    document.querySelectorAll('.recharge-type-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            // Réinitialiser toutes les sélections
            document.querySelectorAll('.pack-option').forEach(option => {
                const checkIcon = option.querySelector('.check-icon');
                if (checkIcon) {
                    checkIcon.classList.add('hidden');
                }
                option.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20', 'selected');
                option.style.borderWidth = '2px';
            });
            
            // Activer la sélection
            const label = this.closest('.pack-option');
            const checkIcon = label.querySelector('.check-icon');
            if (checkIcon) {
                checkIcon.classList.remove('hidden');
            }
            label.classList.add('border-green-500', 'bg-green-50', 'dark:bg-green-900/20', 'selected');
            label.style.borderWidth = '3px';
            
            // Gérer le type de recharge
            if (this.value === 'pack') {
                const packId = this.dataset.packId;
                document.getElementById('credit_pack_id').value = packId;
                document.getElementById('customAmountContainer').classList.add('hidden');
                document.getElementById('amount').removeAttribute('required');
            } else if (this.value === 'custom') {
                document.getElementById('credit_pack_id').value = '';
                document.getElementById('customAmountContainer').classList.remove('hidden');
                document.getElementById('amount').setAttribute('required', 'required');
                document.getElementById('amount').focus();
            }
        });
    });
    
    // Gestion de la sélection des méthodes de paiement améliorée
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-method-card').forEach(card => {
                const checkIcon = card.querySelector('.payment-check-icon');
                if (checkIcon) {
                    checkIcon.classList.add('hidden');
                }
                card.classList.remove('selected', 'border-green-500');
                card.style.borderWidth = '2px';
            });
            
            if (this.checked) {
                const label = this.closest('.payment-method-card');
                const checkIcon = label.querySelector('.payment-check-icon');
                if (checkIcon) {
                    checkIcon.classList.remove('hidden');
                }
                label.classList.add('selected', 'border-green-500');
                label.style.borderWidth = '3px';
            }
        });
    });
    
    // Initialiser l'état visuel de la méthode sélectionnée par défaut
    const defaultPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
    if (defaultPaymentMethod) {
        const label = defaultPaymentMethod.closest('.payment-method-card');
        const checkIcon = label.querySelector('.payment-check-icon');
        if (checkIcon) {
            checkIcon.classList.remove('hidden');
        }
        label.classList.add('selected', 'border-green-500');
        label.style.borderWidth = '3px';
    }
    
    // Validation du formulaire
    document.getElementById('rechargeForm').addEventListener('submit', function(e) {
        const rechargeType = document.querySelector('input[name="recharge_type"]:checked');
        if (!rechargeType) {
            e.preventDefault();
            alert('Veuillez sélectionner un pack ou une recharge personnalisée');
            return false;
        }
        
        if (rechargeType.value === 'custom') {
            const amount = document.getElementById('amount').value;
            if (!amount || parseFloat(amount) <= 0) {
                e.preventDefault();
                alert('Veuillez saisir un montant valide');
                return false;
            }
        }
    });
</script>
@endsection

