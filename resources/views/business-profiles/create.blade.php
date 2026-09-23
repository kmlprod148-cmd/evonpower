@extends('layouts.app')

@section('content')
@php
    $businessProfileRoutePrefix = $businessProfileRoutePrefix ?? 'business-profiles';
@endphp
<div class="px-4 py-6">
    <!-- Header -->
    <div class="flex items-center mb-6">
        <a href="{{ route($businessProfileRoutePrefix . '.index') }}" class="mr-3 text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <p class="text-gray-600">Configuration</p>
            <h1 class="text-2xl font-bold">Ajouter un nouveau profil business</h1>
        </div>
    </div>

    <!-- Form Section -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <form action="{{ route($businessProfileRoutePrefix . '.store') }}" method="POST" id="profileForm">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Left Column -->
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold mb-4">Informations générales</h2>
                        
                        <!-- Nom du profil -->
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du profil*</label>
                            <input type="text" id="name" name="name" 
                                class="w-full px-3 py-2 border @error('name') border-red-500 @else border-gray-300 @enderror rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                                value="{{ old('name') }}" required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea id="description" name="description" rows="3"
                                class="w-full px-3 py-2 border @error('description') border-red-500 @else border-gray-300 @enderror rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Type de profil -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Audience cible <span class="text-red-500">*</span></label>
                            @php
                                $isIntegrator = Auth::user()->hasRole(['integrator', 'Integrator']);
                            @endphp
                            <div class="grid {{ $isIntegrator ? 'grid-cols-1' : 'grid-cols-2' }} gap-4">
                                <!-- Intégrateurs Option -->
                                @if(!$isIntegrator)
                                <div class="border @error('target_audience') border-red-500 @else border-gray-300 @enderror rounded-lg p-4 flex flex-col items-center cursor-pointer hover:bg-gray-50 transition-colors target-audience-option" data-type="integrator">
                                    <div class="h-16 w-16 mb-2">
                                        <img src="{{ asset('images/Integrateur.png') }}"
                                             alt="Intégrateur - Icône"
                                             class="h-full w-full object-contain"
                                             loading="lazy"
                                             width="64"
                                             height="64">
                                    </div>
                                    <span class="text-center font-medium">Intégrateurs</span>
                                    <input type="checkbox" name="target_audience[]" value="integrator" class="hidden target-audience-checkbox" {{ (is_array(old('target_audience')) && in_array('integrator', old('target_audience'))) ? 'checked' : '' }}>
                                </div>
                                @endif

                                <!-- Opérateurs Option -->
                                <div class="border @error('target_audience') border-red-500 @else border-gray-300 @enderror rounded-lg p-4 flex flex-col items-center cursor-pointer hover:bg-gray-50 transition-colors target-audience-option" data-type="operator">
                                    <div class="h-16 w-16 mb-2">
                                        <img src="{{ asset('images/Operateur.png') }}"
                                             alt="Opérateur - Icône"
                                             class="h-full w-full object-contain"
                                             loading="lazy"
                                             width="64"
                                             height="64">
                                    </div>
                                    <span class="text-center font-medium">Opérateurs</span>
                                    @if($isIntegrator && !old('target_audience'))
                                        <span class="text-xs text-green-600 mt-1 font-medium">Présélectionné</span>
                                    @endif
                                    <input type="checkbox" name="target_audience[]" value="operator" class="hidden target-audience-checkbox" {{ (is_array(old('target_audience')) && in_array('operator', old('target_audience'))) || ($isIntegrator && !old('target_audience')) ? 'checked' : '' }}>
                                </div>
                            </div>
                            @error('target_audience')
                                <p class="mt-1 text-sm text-red-500">
                                    {{ $message == 'The target audience field is required.' ? 'Veuillez sélectionner au moins une audience' : $message }}
                                </p>
                            @enderror
                        </div>
                        
                        <!-- Options -->
                        <div class="mt-4 space-y-3">
                            <label class="inline-flex items-center">
                                <input type="checkbox" id="is_public" name="is_public" value="1" class="rounded text-green-500 focus:ring-green-500" {{ old('is_public') ? 'checked' : '' }}>
                                <span class="ml-2 text-gray-700">Rendre ce profil public</span>
                            </label>
                            
                            <label class="inline-flex items-center">
                                <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded text-green-500 focus:ring-green-500" {{ old('is_active', true) ? 'checked' : '' }}>
                                <span class="ml-2 text-gray-700">Profil actif</span>
                            </label>
                        </div>
                        
                        <!-- Attribution Section (Integrator / Partner) -->
                        <div class="mb-6">
                            <h3 class="text-base font-medium text-gray-900 mb-3">Attribution</h3>
                            <p class="text-sm text-gray-500 mb-3">Sélectionnez l'intégrateur ou le partenaire auquel ce profil business est attaché.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Integrator Select -->
                                <div>
                                    <label for="integrator_id" class="block text-sm font-medium text-gray-700 mb-1">Intégrateur</label>
                                    <select id="integrator_id" name="integrator_id" 
                                            class="w-full px-3 py-2 border @error('integrator_id') border-red-500 @else border-gray-300 @enderror rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                        <option value="">-- Aucun --</option>
                                        @foreach($integrators as $integrator)
                                            <option value="{{ $integrator->id }}" {{ old('integrator_id') == $integrator->id ? 'selected' : '' }}>
                                                {{ $integrator->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('integrator_id')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">Laissez vide si le profil est attaché à un partenaire uniquement.</p>
                                </div>
                                
                                <!-- Partner Select -->
                                <div>
                                    <label for="partner_id" class="block text-sm font-medium text-gray-700 mb-1">Partenaire</label>
                                    <select id="partner_id" name="partner_id" 
                                            class="w-full px-3 py-2 border @error('partner_id') border-red-500 @else border-gray-300 @enderror rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                        <option value="">-- Aucun --</option>
                                        @foreach($partners as $partner)
                                            <option value="{{ $partner->id }}" {{ old('partner_id') == $partner->id ? 'selected' : '' }}>
                                                {{ $partner->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('partner_id')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">Laissez vide si le profil est attaché à un intégrateur uniquement.</p>
                                </div>
                            </div>
                            @error('integrator_id')
                            @enderror
                            @error('partner_id')
                            @enderror
                        </div>
                        
                    </div>
                    
                    <!-- Right Column - Récapitulatif en temps réel -->
                    <div id="realTimeSummary" class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Récapitulatif</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Nom du profil</h4>
                                <p id="summary-name" class="text-gray-900">-</p>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Type de profil</h4>
                                <p id="summary-audience" class="text-gray-900">-</p>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Statut</h4>
                                <div id="summary-status" class="flex items-center">-</div>
                            </div>
                            
                            <div id="summary-fees" class="border-t border-gray-200 pt-4 mt-4">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Configuration tarifaire</h4>
                                
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Abonnement:</span>
                                        <span id="summary-subscription" class="font-medium">0.00 €</span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Frais par transaction:</span>
                                        <span id="summary-transaction" class="font-medium">0.00 €</span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Frais par recharge:</span>
                                        <span id="summary-charge" class="font-medium">0.00 €</span>
                                    </div>
                                    
                                    <div class="border-t border-gray-200 pt-2 mt-2">
                                        <div class="flex justify-between font-medium">
                                            <span class="text-gray-700">Coût mensuel estimé:</span>
                                            <span id="summary-monthly-cost" class="text-green-600">0.00 €</span>
                                        </div>
                                        <p id="summary-cost-details" class="text-xs text-gray-500 mt-1">Basé sur <span id="summary-terminal-count">10</span> borne(s)</p> {{-- Updated text and added span for terminal count --}}
                                        
                                        {{-- Single Terminal Cost Summary --}}
                                        <div class="flex justify-between font-medium mt-2 pt-2 border-t border-gray-200">
                                            <span class="text-gray-700">Coût pour 1 borne:</span> {{-- Added single terminal cost label --}}
                                            <span id="summary-single-terminal-cost" class="text-green-600">0.00 €</span> {{-- Added single terminal cost value --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 text-center" id="config-status">
                            <div class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-700 bg-yellow-100">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <span>Configuration incomplète</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Paramètres de tarification -->
                <div class="mt-8 mb-6">
                    <h2 class="text-lg font-semibold mb-6 pb-2 border-b">Paramètres de tarification</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Service d'abonnement (combinaison des frais de maintenance et par borne) -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h3 class="text-base font-medium mb-3">Service d'abonnement</h3>
                            
                            <!-- Type de facturation -->
                            <div class="mb-3">
                                <label for="subscription_period" class="block text-sm font-medium text-gray-700 mb-1">Période de facturation</label>
                                <select id="subscription_period" name="subscription_period" 
                                    class="w-full px-3 py-2 border @error('subscription_period') border-red-500 @else border-gray-300 @enderror rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                    <option value="">Aucun</option>
                                    <option value="monthly" {{ old('subscription_period') == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                    <option value="quarterly" {{ old('subscription_period') == 'quarterly' ? 'selected' : '' }}>Trimestriel</option>
                                    <option value="yearly" {{ old('subscription_period') == 'yearly' ? 'selected' : '' }}>Annuel</option>
                                </select>
                                @error('subscription_period')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Montant forfaitaire -->
                            <div class="mb-4">
                                <label for="base_fee_amount" class="block text-sm font-medium text-gray-700 mb-1">Frais forfaitaires (€)</label>
                                <div class="relative">
                                    <input type="number" id="base_fee_amount" name="base_fee_amount" step="0.01" min="0"
                                        class="w-full pl-3 pr-10 py-2 border @error('base_fee_amount') border-red-500 @else border-gray-300 @enderror rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        value="{{ old('base_fee_amount', '0.00') }}">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span class="text-gray-500">€</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Frais fixes indépendants du nombre de bornes.</p>
                                @error('base_fee_amount')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Montant par borne -->
                            <div class="mb-4"> {{-- Added mb-4 for spacing --}}
                                <label for="terminal_fee_amount" class="block text-sm font-medium text-gray-700 mb-1">Frais par borne additionnelle (€)</label> {{-- Updated label --}}
                                <div class="relative">
                                    <input type="number" id="terminal_fee_amount" name="terminal_fee_amount" step="0.01" min="0"
                                        class="w-full pl-3 pr-10 py-2 border @error('terminal_fee_amount') border-red-500 @else border-gray-300 @enderror rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        value="{{ old('terminal_fee_amount', '0.00') }}">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span class="text-gray-500">€</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Frais multipliés par le nombre de bornes actives.</p>
                                @error('terminal_fee_amount')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Terminal Count Input --}}
                            <div class="mb-4">
                                <label for="terminal_count" class="block text-sm font-medium text-gray-700 mb-1">Nombre de bornes pour l'estimation</label>
                                <input type="number" id="terminal_count" name="terminal_count" min="1" value="10"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <p class="mt-1 text-xs text-gray-500">Utilisé pour calculer le Total Facturé (TTC) par période et par borne.</p>
                            </div>
                            
                            <!-- Estimation automatique -->
                            <div class="mt-4 p-3 bg-blue-50 rounded-md">
                                <h4 class="text-sm font-medium text-blue-700 mb-2">Estimation automatique</h4>
                                <div id="cost-estimate" class="text-sm">
                                    <div class="grid grid-cols-2 gap-1">
                                        <span class="text-gray-600">Coût par période (estimé):</span> {{-- Updated label --}}
                                        <span id="estimate-per-period" class="font-medium text-right">0.00 €</span>
                                        
                                        <span class="text-gray-600">Coût mensuel (estimé):</span> {{-- Updated label --}}
                                        <span id="estimate-monthly" class="font-medium text-right">0.00 €</span>
                                        
                                        <span class="text-gray-600">Coût par borne (estimé):</span> {{-- Updated label --}}
                                        <span id="estimate-per-terminal" class="font-medium text-right">0.00 €</span>

                                        {{-- Single Terminal Cost --}}
                                        <span class="text-gray-600 font-semibold mt-2 pt-2 border-t border-gray-200">Coût pour 1 borne:</span> {{-- Added single terminal cost label --}}
                                        <span id="estimate-single-terminal" class="font-semibold text-right mt-2 pt-2 border-t border-gray-200">0.00 €</span> {{-- Added single terminal cost value --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        
                        <!-- Frais par transaction et recharge -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h3 class="text-base font-medium mb-3">Frais par transaction et recharge</h3>
                            
                            <!-- Fee Types (Checkboxes) -->
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Types de frais*</label>
                                <div class="flex flex-wrap gap-2">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="transaction_fee_type[]" value="fixed" class="rounded text-green-500 focus:ring-green-500" 
                                               {{ (is_array(old('transaction_fee_type')) && in_array('fixed', old('transaction_fee_type'))) ? 'checked' : '' }}>
                                        <span class="ml-2 text-gray-700">Fixe (€)</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="transaction_fee_type[]" value="percentage" class="rounded text-green-500 focus:ring-green-500" 
                                               {{ (is_array(old('transaction_fee_type')) && in_array('percentage', old('transaction_fee_type'))) ? 'checked' : '' }}>
                                        <span class="ml-2 text-gray-700">Pourcentage (%)</span>
                                    </label>
                                </div>
                                @error('transaction_fee_type')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Montant fixe (Transaction) -->
                            <div class="mb-3 fee-fixed-section {{ (is_array(old('transaction_fee_type')) && in_array('fixed', old('transaction_fee_type'))) ? '' : 'hidden' }}">
                                <label for="transaction_fee_fixed_amount" class="block text-sm font-medium text-gray-700 mb-1">Montant fixe (€)</label>
                                <div class="relative">
                                    <input type="number" id="transaction_fee_fixed_amount" name="transaction_fee_fixed_amount" step="0.01" min="0"
                                        class="w-full pl-3 pr-10 py-2 border @error('transaction_fee_fixed_amount') border-red-500 @else border-gray-300 @enderror rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        value="{{ old('transaction_fee_fixed_amount', '0.00') }}">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span class="text-gray-500">€</span>
                                    </div>
                                </div>
                                @error('transaction_fee_fixed_amount')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Pourcentage (Transaction) -->
                            <div class="mb-3 fee-percentage-section {{ (is_array(old('transaction_fee_type')) && in_array('percentage', old('transaction_fee_type'))) ? '' : 'hidden' }}">
                                <label for="transaction_fee_percentage" class="block text-sm font-medium text-gray-700 mb-1">Pourcentage (%)</label>
                                <div class="relative">
                                    <input type="number" id="transaction_fee_percentage" name="transaction_fee_percentage" step="0.01" min="0" max="100"
                                        class="w-full pl-3 pr-10 py-2 border @error('transaction_fee_percentage') border-red-500 @else border-gray-300 @enderror rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        value="{{ old('transaction_fee_percentage', '0.00') }}">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span class="text-gray-500">%</span>
                                    </div>
                                </div>
                                @error('transaction_fee_percentage')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Frais par recharge -->
                            <h4 class="text-sm font-medium text-gray-700 mt-4 mb-2">Frais de recharge</h4>
                            
                            <!-- Montant fixe (Recharge) -->
                            <div class="mb-3">
                                <label for="charge_fee_fixed_amount" class="block text-sm font-medium text-gray-700 mb-1">Montant fixe (€)</label>
                                <div class="relative">
                                    <input type="number" id="charge_fee_fixed_amount" name="charge_fee_fixed_amount" step="0.01" min="0"
                                        class="w-full pl-3 pr-10 py-2 border @error('charge_fee_fixed_amount') border-red-500 @else border-gray-300 @enderror rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        value="{{ old('charge_fee_fixed_amount', '0.00') }}">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span class="text-gray-500">€</span>
                                    </div>
                                </div>
                                @error('charge_fee_fixed_amount')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Pourcentage (Recharge) -->
                            <div>
                                <label for="charge_fee_percentage" class="block text-sm font-medium text-gray-700 mb-1">Pourcentage (%)</label>
                                <div class="relative">
                                    <input type="number" id="charge_fee_percentage" name="charge_fee_percentage" step="0.01" min="0" max="100"
                                        class="w-full pl-3 pr-10 py-2 border @error('charge_fee_percentage') border-red-500 @else border-gray-300 @enderror rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        value="{{ old('charge_fee_percentage', '0.00') }}">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span class="text-gray-500">%</span>
                                    </div>
                                </div>
                                @error('charge_fee_percentage')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Admin & Partner Fees Section -->
                <div class="mt-8">
                    <h2 class="text-lg font-semibold mb-6 pb-2 border-b">Frais administratifs et répartition</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Admin Fees -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h3 class="text-base font-medium mb-3">Frais Admin</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="admin_fee_fixed" class="block text-sm font-medium text-gray-700 mb-1">Montant fixe (€)</label>
                                    <input type="number" id="admin_fee_fixed" name="admin_fee_fixed" step="0.01" min="0"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                                        value="{{ old('admin_fee_fixed', 0) }}">
                                </div>
                                <div>
                                    <label for="admin_fee_percentage" class="block text-sm font-medium text-gray-700 mb-1">Pourcentage (%)</label>
                                    <input type="number" id="admin_fee_percentage" name="admin_fee_percentage" step="0.01" min="0" max="100"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                                        value="{{ old('admin_fee_percentage', 0) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Integrator Fees -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h3 class="text-base font-medium mb-3">Frais Intégrateur</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="integrator_fee_fixed" class="block text-sm font-medium text-gray-700 mb-1">Montant fixe (€)</label>
                                    <input type="number" id="integrator_fee_fixed" name="integrator_fee_fixed" step="0.01" min="0"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                                        value="{{ old('integrator_fee_fixed', 0) }}">
                                </div>
                                <div>
                                    <label for="integrator_fee_percentage" class="block text-sm font-medium text-gray-700 mb-1">Pourcentage (%)</label>
                                    <input type="number" id="integrator_fee_percentage" name="integrator_fee_percentage" step="0.01" min="0" max="100"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                                        value="{{ old('integrator_fee_percentage', 0) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Partner Fees -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h3 class="text-base font-medium mb-3">Frais Partenaire</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="partner_fee_fixed" class="block text-sm font-medium text-gray-700 mb-1">Montant fixe (€)</label>
                                    <input type="number" id="partner_fee_fixed" name="partner_fee_fixed" step="0.01" min="0"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                                        value="{{ old('partner_fee_fixed', 0) }}">
                                </div>
                                <div>
                                    <label for="partner_fee_percentage" class="block text-sm font-medium text-gray-700 mb-1">Pourcentage (%)</label>
                                    <input type="number" id="partner_fee_percentage" name="partner_fee_percentage" step="0.01" min="0" max="100"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                                        value="{{ old('partner_fee_percentage', 0) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Commission Split -->
                    <div class="mt-6 bg-white p-4 rounded-lg border border-gray-200">
                        <h3 class="text-base font-medium mb-3">Répartition des commissions</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label for="operator_commission" class="block text-sm font-medium text-gray-700 mb-1">Commission Opérateur (%)</label>
                                <input type="number" id="operator_commission" name="operator_commission" step="0.01" min="0" max="100"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                                    value="{{ old('operator_commission', 0) }}">
                            </div>
                            <div>
                                <label for="integrator_commission" class="block text-sm font-medium text-gray-700 mb-1">Commission Intégrateur (%)</label>
                                <input type="number" id="integrator_commission" name="integrator_commission" step="0.01" min="0" max="100"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                                    value="{{ old('integrator_commission', 0) }}">
                            </div>
                            <div>
                                <label for="owner_commission" class="block text-sm font-medium text-gray-700 mb-1">Commission Propriétaire (%)</label>
                                <input type="number" id="owner_commission" name="owner_commission" step="0.01" min="0" max="100"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                                    value="{{ old('owner_commission', 0) }}">
                            </div>
                            <div>
                                <label for="partner_commission" class="block text-sm font-medium text-gray-700 mb-1">Commission Partenaire (%)</label>
                                <input type="number" id="partner_commission" name="partner_commission" step="0.01" min="0" max="100"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                                    value="{{ old('partner_commission', 0) }}">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Buttons -->
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <a href="{{ route($businessProfileRoutePrefix . '.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md transition-colors">
                        Annuler
                    </a>
                    <button type="submit" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md transition-colors">
                        Créer le profil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Constants for better readability and maintainability
    const SUBSCRIPTION_PERIODS = {
        MONTHLY: 'monthly',
        QUARTERLY: 'quarterly',
        YEARLY: 'yearly',
        NONE: ''
    };

    const DIVISORS = {
        MONTHLY: 1,
        QUARTERLY: 3,
        YEARLY: 12
    };

    const CURRENCY_FORMATTER = new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    });

    // Helper function to parse float with default value
    const parseFloatOrDefault = (value, defaultValue = 0) => {
        const parsed = parseFloat(value);
        return isNaN(parsed) ? defaultValue : parsed;
    };

    // Helper function to format currency
    const formatCurrency = (value) => CURRENCY_FORMATTER.format(value);

    // DOM Elements - Grouped for better organization
    const DOMElements = {
        // Input Elements
        nameInput: document.getElementById('name'),
        descriptionInput: document.getElementById('description'),
        audienceOptions: document.querySelectorAll('.target-audience-option'),
        audienceCheckboxes: document.querySelectorAll('.target-audience-checkbox'),
        isPublicCheckbox: document.getElementById('is_public'),
        isActiveCheckbox: document.getElementById('is_active'),
        subscriptionPeriodSelect: document.getElementById('subscription_period'),
        baseFeeAmountInput: document.getElementById('base_fee_amount'),
        terminalFeeAmountInput: document.getElementById('terminal_fee_amount'),
        transactionFeeTypeCheckboxes: document.querySelectorAll('input[name="transaction_fee_type[]"]'),
        transactionFeeFixedAmountInput: document.getElementById('transaction_fee_fixed_amount'),
        transactionFeePercentageInput: document.getElementById('transaction_fee_percentage'),
        chargeFeeFixedAmountInput: document.getElementById('charge_fee_fixed_amount'),
        chargeFeePercentageInput: document.getElementById('charge_fee_percentage'),
        terminalCountInput: document.getElementById('terminal_count'),

        // Summary Elements
        summaryNameElement: document.getElementById('summary-name'),
        summaryAudienceElement: document.getElementById('summary-audience'),
        summaryStatusElement: document.getElementById('summary-status'),
        summarySubscriptionElement: document.getElementById('summary-subscription'),
        summaryTransactionElement: document.getElementById('summary-transaction'),
        summaryChargeElement: document.getElementById('summary-charge'),
        summaryMonthlyCostElement: document.getElementById('summary-monthly-cost'),
        summaryTerminalCountElement: document.getElementById('summary-terminal-count'),
        summarySingleTerminalCostElement: document.getElementById('summary-single-terminal-cost'),
        configurationStatusElement: document.getElementById('config-status'),

        // Fee Sections
        feeFixedSection: document.querySelector('.fee-fixed-section'),
        feePercentageSection: document.querySelector('.fee-percentage-section')
    };

    /**
     * Initializes event listeners and visual state for audience selection options.
     */
    function initializeAudienceSelection() {
        DOMElements.audienceOptions.forEach(option => {
            const checkbox = option.querySelector('.target-audience-checkbox');

            // Set initial visual state based on checkbox checked status
            if (checkbox.checked) {
                option.classList.add('border-green-500', 'bg-green-50');
            }

            option.addEventListener('click', function() {
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('border-green-500', checkbox.checked);
                this.classList.toggle('bg-green-50', checkbox.checked);
                updateSummary();
            });
        });
    }

    /**
     * Initializes event listeners for transaction fee type checkboxes to toggle visibility of related input fields.
     */
    function initializeFeeTypeToggles() {
        DOMElements.transactionFeeTypeCheckboxes.forEach(checkbox => {
            // Set initial visibility based on checkbox checked status
            if (checkbox.value === 'fixed') {
                DOMElements.feeFixedSection.classList.toggle('hidden', !checkbox.checked);
            } else if (checkbox.value === 'percentage') {
                DOMElements.feePercentageSection.classList.toggle('hidden', !checkbox.checked);
            }

            checkbox.addEventListener('change', function() {
                if (this.value === 'fixed') {
                    DOMElements.feeFixedSection.classList.toggle('hidden', !this.checked);
                } else if (this.value === 'percentage') {
                    DOMElements.feePercentageSection.classList.toggle('hidden', !this.checked);
                }
                updateSummary();
            });
        });
    }

    /**
     * Attaches input event listeners to relevant form fields to trigger summary updates.
     */
    function attachEventListeners() {
        [
            DOMElements.nameInput,
            DOMElements.descriptionInput,
            DOMElements.isPublicCheckbox,
            DOMElements.isActiveCheckbox,
            DOMElements.subscriptionPeriodSelect,
            DOMElements.baseFeeAmountInput,
            DOMElements.terminalFeeAmountInput,
            DOMElements.transactionFeeFixedAmountInput,
            DOMElements.transactionFeePercentageInput,
            DOMElements.chargeFeeFixedAmountInput,
            DOMElements.chargeFeePercentageInput,
            DOMElements.terminalCountInput
        ].forEach(element => {
            element.addEventListener('input', updateSummary);
        });
    }

    /**
     * Updates the general information section of the summary (name, audience, status).
     */
    function updateGeneralSummary() {
        DOMElements.summaryNameElement.textContent = DOMElements.nameInput.value || '-';

        const selectedAudiences = Array.from(DOMElements.audienceCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value === 'integrator' ? 'Intégrateurs' : 'Opérateurs');

        DOMElements.summaryAudienceElement.textContent = selectedAudiences.length > 0 ?
            selectedAudiences.join(', ') :
            '-';

        DOMElements.summaryStatusElement.innerHTML = '';

        const createBadge = (isChecked, publicText, privateText, activeColor, inactiveColor) => {
            const badge = document.createElement('span');
            badge.className = `px-2 py-1 rounded-full text-xs mr-2 ${isChecked ? activeColor : inactiveColor}`;
            badge.textContent = isChecked ? publicText : privateText;
            return badge;
        };

        DOMElements.summaryStatusElement.appendChild(
            createBadge(DOMElements.isPublicCheckbox.checked, 'Public', 'Privé', 'bg-green-100 text-green-800', 'bg-gray-100 text-gray-800')
        );
        DOMElements.summaryStatusElement.appendChild(
            createBadge(DOMElements.isActiveCheckbox.checked, 'Actif', 'Inactif', 'bg-green-100 text-green-800', 'bg-red-100 text-red-800')
        );
    }

    /**
     * Updates the fee-related sections of the summary, including subscription, transaction, and charge fees,
     * and cost estimations.
     */
    function updateFeeSummary() {
        const baseFee = parseFloatOrDefault(DOMElements.baseFeeAmountInput.value);
        const terminalFee = parseFloatOrDefault(DOMElements.terminalFeeAmountInput.value);
        const subscriptionPeriod = DOMElements.subscriptionPeriodSelect.value;
        const terminalCount = parseInt(DOMElements.terminalCountInput.value) || 1; // Default to 1 if invalid

        let costPerPeriod = 0;
        let monthlyCost = 0;
        let pricePerTerminal = 0;
        let subscriptionText = 'Aucun';

        if (subscriptionPeriod !== SUBSCRIPTION_PERIODS.NONE) {
            costPerPeriod = baseFee + (terminalFee * terminalCount);
            pricePerTerminal = terminalFee + (baseFee / terminalCount); // Cost per terminal for the given count

            switch (subscriptionPeriod) {
                case SUBSCRIPTION_PERIODS.MONTHLY:
                    monthlyCost = costPerPeriod / DIVISORS.MONTHLY;
                    subscriptionText = `${formatCurrency(costPerPeriod)} / mois`;
                    break;
                case SUBSCRIPTION_PERIODS.QUARTERLY:
                    monthlyCost = costPerPeriod / DIVISORS.QUARTERLY;
                    subscriptionText = `${formatCurrency(costPerPeriod)} / trimestre`;
                    break;
                case SUBSCRIPTION_PERIODS.YEARLY:
                    monthlyCost = costPerPeriod / DIVISORS.YEARLY;
                    subscriptionText = `${formatCurrency(costPerPeriod)} / an`;
                    break;
            }
        }

        // Calculate single terminal cost (base fee + one terminal fee)
        const singleTerminalCost = baseFee + terminalFee;

        DOMElements.summarySubscriptionElement.textContent = subscriptionText;
        DOMElements.summaryMonthlyCostElement.textContent = formatCurrency(monthlyCost);
        DOMElements.summaryTerminalCountElement.textContent = terminalCount;

        // Update cost estimation section
        document.getElementById('estimate-per-period').textContent = formatCurrency(costPerPeriod);
        document.getElementById('estimate-monthly').textContent = formatCurrency(monthlyCost);
        document.getElementById('estimate-per-terminal').textContent = formatCurrency(pricePerTerminal);
        document.getElementById('estimate-single-terminal').textContent = formatCurrency(singleTerminalCost);

        // Update transaction and charge fees
        const transactionFixed = parseFloatOrDefault(DOMElements.transactionFeeFixedAmountInput.value);
        const transactionPercent = parseFloatOrDefault(DOMElements.transactionFeePercentageInput.value);
        const chargeFixed = parseFloatOrDefault(DOMElements.chargeFeeFixedAmountInput.value);
        const chargePercent = parseFloatOrDefault(DOMElements.chargeFeePercentageInput.value);

        let transactionFeeText = [];
        if (DOMElements.transactionFeeTypeCheckboxes[0].checked && transactionFixed > 0) transactionFeeText.push(formatCurrency(transactionFixed));
        if (DOMElements.transactionFeeTypeCheckboxes[1].checked && transactionPercent > 0) transactionFeeText.push(`${transactionPercent.toFixed(2)} %`);
        DOMElements.summaryTransactionElement.textContent = transactionFeeText.length > 0 ? transactionFeeText.join(' + ') : formatCurrency(0);

        let chargeFeeText = [];
        if (chargeFixed > 0) chargeFeeText.push(formatCurrency(chargeFixed));
        if (chargePercent > 0) chargeFeeText.push(`${chargePercent.toFixed(2)} %`);
        DOMElements.summaryChargeElement.textContent = chargeFeeText.length > 0 ? chargeFeeText.join(' + ') : formatCurrency(0);
    }

    /**
     * Updates the configuration status badge (complete/incomplete).
     */
    function updateConfigurationStatus() {
        const selectedAudiences = Array.from(DOMElements.audienceCheckboxes).filter(cb => cb.checked);
        const baseFee = parseFloatOrDefault(DOMElements.baseFeeAmountInput.value);
        const terminalFee = parseFloatOrDefault(DOMElements.terminalFeeAmountInput.value);
        const transactionFixed = parseFloatOrDefault(DOMElements.transactionFeeFixedAmountInput.value);
        const transactionPercent = parseFloatOrDefault(DOMElements.transactionFeePercentageInput.value);
        const chargeFixed = parseFloatOrDefault(DOMElements.chargeFeeFixedAmountInput.value);
        const chargePercent = parseFloatOrDefault(DOMElements.chargeFeePercentageInput.value);

        const isConfigComplete = DOMElements.nameInput.value.trim() !== '' &&
            selectedAudiences.length > 0 &&
            (DOMElements.subscriptionPeriodSelect.value !== SUBSCRIPTION_PERIODS.NONE ||
                transactionFixed > 0 || transactionPercent > 0 ||
                chargeFixed > 0 || chargePercent > 0);

        if (isConfigComplete) {
            DOMElements.configurationStatusElement.innerHTML = `
                <div class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Configuration complète</span>
                </div>
            `;
        } else {
            DOMElements.configurationStatusElement.innerHTML = `
                <div class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-700 bg-yellow-100">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span>Configuration incomplète</span>
                </div>
            `;
        }
    }

    /**
     * Main function to update all summary sections.
     */
    function updateSummary() {
        updateGeneralSummary();
        updateFeeSummary();
        updateConfigurationStatus();
    }

    // Initialize all components on page load
    initializeAudienceSelection();
    initializeFeeTypeToggles();
    attachEventListeners();
    updateSummary(); // Initial update
});
</script>
@endsection
