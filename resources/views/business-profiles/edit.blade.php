@extends('layouts.app')

@section('content')
@php
    $businessProfileRoutePrefix = $businessProfileRoutePrefix ?? 'business-profiles';
@endphp
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-6">
            <a href="{{ route($businessProfileRoutePrefix . '.index') }}" class="hover:text-gray-700">Configuration</a>
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
            <a href="{{ route($businessProfileRoutePrefix . '.show', $businessProfile) }}" class="hover:text-gray-700">{{ $businessProfile->name }}</a>
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
            <span>Modifier</span>
        </nav>

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center">
                <a href="{{ route($businessProfileRoutePrefix . '.show', $businessProfile) }}" class="mr-4 p-2 rounded-lg bg-white shadow-sm border border-gray-200 hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Modifier le profil business</h1>
                    <p class="text-gray-600 mt-1">Mettre à jour les informations et paramètres tarifaires</p>
                </div>
            </div>
        </div>

        @permission('edit_business_profiles')
        <form action="{{ route($businessProfileRoutePrefix . '.update', $businessProfile) }}" method="POST" id="profileForm">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Basic Information Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Informations générales
                            </h2>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            <!-- Profile Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nom du profil <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    value="{{ old('name', $businessProfile->name) }}"
                                    class="w-full px-4 py-3 border @error('name') border-red-300 @else border-gray-300 @enderror rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                    placeholder="Entrez le nom du profil"
                                    required
                                >
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            
                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea 
                                    id="description" 
                                    name="description" 
                                    rows="4"
                                    class="w-full px-4 py-3 border @error('description') border-red-300 @else border-gray-300 @enderror rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                                    placeholder="Description du profil business (optionnel)"
                                >{{ old('description', $businessProfile->description) }}</textarea>
                                @error('description')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            
                            <!-- Target Audience -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Audience cible <span class="text-red-500">*</span>
                                </label>
                                @php
                                    $isIntegrator = Auth::user()->hasRole(['integrator', 'Integrator']);
                                @endphp
                                <div class="grid grid-cols-1 {{ $isIntegrator ? '' : 'md:grid-cols-2' }} gap-4">
                                    <!-- Intégrateurs Option -->
                                    @if(!$isIntegrator)
                                    <div class="relative">
                                        <input type="checkbox"
                                               name="target_audience[]"
                                               value="integrator"
                                               id="integrator"
                                               class="sr-only peer"
                                               {{ (is_array(old('target_audience', $businessProfile->target_audience)) && in_array('integrator', old('target_audience', $businessProfile->target_audience))) ? 'checked' : '' }}>
                                        <label for="integrator" class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all">
                                            <div class="w-16 h-16 mb-3">
                                                <img src="{{ asset('images/Integrateur.png') }}" alt="Intégrateur" class="w-full h-full object-contain">
                                            </div>
                                            <span class="font-medium text-gray-900">Intégrateurs</span>
                                            <span class="text-sm text-gray-500 text-center mt-1">Entreprises qui intègrent des solutions</span>
                                        </label>
                                    </div>
                                    @endif

                                    <!-- Opérateurs Option -->
                                    <div class="relative">
                                        <input type="checkbox"
                                               name="target_audience[]"
                                               value="operator"
                                               id="operator"
                                               class="sr-only peer"
                                               {{ (is_array(old('target_audience', $businessProfile->target_audience)) && in_array('operator', old('target_audience', $businessProfile->target_audience))) ? 'checked' : '' }}>
                                        <label for="operator" class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all">
                                            <div class="w-16 h-16 mb-3">
                                                <img src="{{ asset('images/Operateur.png') }}" alt="Opérateur" class="w-full h-full object-contain">
                                            </div>
                                            <span class="font-medium text-gray-900">Opérateurs</span>
                                            <span class="text-sm text-gray-500 text-center mt-1">Entreprises qui opèrent des réseaux</span>
                                        </label>
                                    </div>
                                </div>
                                @error('target_audience')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message == 'The target audience field is required.' ? 'Veuillez sélectionner au moins une audience' : $message }}
                                    </p>
                                @enderror
                            </div>
                            
                            <!-- Profile Options -->
                            <div class="pt-4 border-t border-gray-200">
                                <h3 class="text-sm font-medium text-gray-700 mb-4">Options du profil</h3>
                                <div class="space-y-3">
                                    <label class="flex items-center group cursor-pointer">
                                        <input type="checkbox" 
                                               name="is_public" 
                                               value="1"
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                               {{ old('is_public', $businessProfile->is_public) ? 'checked' : '' }}>
                                        <div class="ml-3">
                                            <span class="text-sm font-medium text-gray-900 group-hover:text-blue-600">Profil public</span>
                                            <p class="text-xs text-gray-500">Le profil sera visible par tous les utilisateurs</p>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-center group cursor-pointer">
                                        <input type="checkbox" 
                                               name="is_active" 
                                               value="1"
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                               {{ old('is_active', $businessProfile->is_active) ? 'checked' : '' }}>
                                        <div class="ml-3">
                                            <span class="text-sm font-medium text-gray-900 group-hover:text-blue-600">Profil actif</span>
                                            <p class="text-xs text-gray-500">Le profil peut être utilisé par les utilisateurs</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Configuration -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                Configuration tarifaire
                            </h2>
                        </div>
                        
                        <div class="p-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Subscription Fees -->
                                <div class="space-y-6">
                                    <h3 class="text-base font-medium text-gray-900 flex items-center">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                                        Frais d'abonnement
                                    </h3>
                                    
                                    <!-- Billing Period -->
                                    <div>
                                        <label for="terminal_fee_period" class="block text-sm font-medium text-gray-700 mb-2">Période de facturation</label>
                                        <select id="terminal_fee_period"
                                                name="terminal_fee_period"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Aucune</option>
                                            <option value="monthly" {{ old('terminal_fee_period', $businessProfile->terminal_fee_period) == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                            <option value="quarterly" {{ old('terminal_fee_period', $businessProfile->terminal_fee_period) == 'quarterly' ? 'selected' : '' }}>Trimestriel</option>
                                            <option value="yearly" {{ old('terminal_fee_period', $businessProfile->terminal_fee_period) == 'yearly' ? 'selected' : '' }}>Annuel</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Base Fee -->
                                    <div>
                                        <label for="base_fee_amount" class="block text-sm font-medium text-gray-700 mb-2">Frais de base (€)</label>
                                        <div class="relative">
                                            <input type="number" 
                                                   id="base_fee_amount" 
                                                   name="base_fee_amount" 
                                                   step="0.01" 
                                                   min="0"
                                                   value="{{ old('base_fee_amount', $businessProfile->base_fee_amount ?? '0.00') }}"
                                                   class="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                                <span class="text-gray-500 font-medium">€</span>
                                            </div>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Frais fixes indépendants du nombre de bornes</p>
                                    </div>
                                    
                                    <!-- Terminal Fee -->
                                    <div>
                                        <label for="terminal_fee_amount" class="block text-sm font-medium text-gray-700 mb-2">Frais par borne (€)</label>
                                        <div class="relative">
                                            <input type="number" 
                                                   id="terminal_fee_amount" 
                                                   name="terminal_fee_amount" 
                                                   step="0.01" 
                                                   min="0"
                                                   value="{{ old('terminal_fee_amount', $businessProfile->terminal_fee_amount ?? '0.00') }}"
                                                   class="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                                <span class="text-gray-500 font-medium">€</span>
                                            </div>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Frais multipliés par le nombre de bornes actives</p>
                                    </div>
                                </div>
                                
                                <!-- Transaction & Charge Fees -->
                                <div class="space-y-6">
                                    <h3 class="text-base font-medium text-gray-900 flex items-center">
                                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                        Frais de transaction et recharge
                                    </h3>
                                    
                                    <!-- Transaction Fee Types -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-3">Types de frais de transaction</label>
                                        <div class="space-y-2">
                                            @php
                                                $transactionTypes = old('transaction_fee_type', $transactionFeeConfig['types'] ?? []);
                                                if (is_string($transactionTypes)) {
                                                    $transactionTypes = json_decode($transactionTypes, true) ?? [];
                                                }
                                            @endphp
                                            <label class="flex items-center">
                                                <input type="checkbox"
                                                       name="transaction_fee_type[]"
                                                       value="fixed"
                                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                       {{ (is_array($transactionTypes) && in_array('fixed', $transactionTypes)) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-700">Montant fixe (€)</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox"
                                                       name="transaction_fee_type[]"
                                                       value="percentage"
                                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                       {{ (is_array($transactionTypes) && in_array('percentage', $transactionTypes)) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-700">Pourcentage (%)</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Transaction Fixed Amount -->
                                    <div class="transaction-fixed-section">
                                        <label for="transaction_fee_fixed_amount" class="block text-sm font-medium text-gray-700 mb-2">Montant fixe transaction (€)</label>
                                        <div class="relative">
                                            <input type="number"
                                                   id="transaction_fee_fixed_amount"
                                                   name="transaction_fee_fixed_amount"
                                                   step="0.01"
                                                   min="0"
                                                   value="{{ old('transaction_fee_fixed_amount', $transactionFeeConfig['fixed_amount'] ?? '0.00') }}"
                                                   class="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                                <span class="text-gray-500 font-medium">€</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Transaction Percentage -->
                                    <div class="transaction-percentage-section">
                                        <label for="transaction_fee_percentage" class="block text-sm font-medium text-gray-700 mb-2">Pourcentage transaction (%)</label>
                                        <div class="relative">
                                            <input type="number"
                                                   id="transaction_fee_percentage"
                                                   name="transaction_fee_percentage"
                                                   step="0.01"
                                                   min="0"
                                                   max="100"
                                                   value="{{ old('transaction_fee_percentage', $transactionFeeConfig['percentage'] ?? '0.00') }}"
                                                   class="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                                <span class="text-gray-500 font-medium">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Charge Fees -->
                                    <div class="pt-4 border-t border-gray-200">
                                        <h4 class="text-sm font-medium text-gray-700 mb-4">Frais de recharge</h4>
                                        
                                        <div class="space-y-4">
                                            <div>
                                                <label for="charge_fee_fixed_amount" class="block text-sm font-medium text-gray-700 mb-2">Montant fixe (€)</label>
                                                <div class="relative">
                                                    <input type="number"
                                                           id="charge_fee_fixed_amount"
                                                           name="charge_fee_fixed_amount"
                                                           step="0.01"
                                                           min="0"
                                                           value="{{ old('charge_fee_fixed_amount', $chargeFeeConfig['fixed_amount'] ?? '0.00') }}"
                                                           class="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                                        <span class="text-gray-500 font-medium">€</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <label for="charge_fee_percentage" class="block text-sm font-medium text-gray-700 mb-2">Pourcentage (%)</label>
                                                <div class="relative">
                                                    <input type="number"
                                                           id="charge_fee_percentage"
                                                           name="charge_fee_percentage"
                                                           step="0.01"
                                                           min="0"
                                                           max="100"
                                                           value="{{ old('charge_fee_percentage', $chargeFeeConfig['percentage'] ?? '0.00') }}"
                                                           class="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                                        <span class="text-gray-500 font-medium">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Profile Metadata -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Métadonnées</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Créé par</label>
                                <div class="flex items-center space-x-2">
                                    @if(isset($businessProfile->creator))
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $businessProfile->creator->getRoleBadgeColor() ?? 'gray' }}-100 text-{{ $businessProfile->creator->getRoleBadgeColor() ?? 'gray' }}-800">
                                            {{ $businessProfile->creator->role ?? 'Utilisateur' }}
                                        </span>
                                        <span class="text-sm text-gray-900">{{ $businessProfile->creator->company_name ?? $businessProfile->creator->name ?? 'Système' }}</span>
                                    @else
                                        <span class="text-sm text-gray-500">Système</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Créé le</label>
                                <p class="text-sm text-gray-900">{{ $businessProfile->created_at?->format('d/m/Y à H:i') ?? 'Non défini' }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Dernière modification</label>
                                <p class="text-sm text-gray-900">{{ $businessProfile->updated_at?->format('d/m/Y à H:i') ?? 'Non défini' }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">ID du profil</label>
                                <code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono">{{ $businessProfile->id }}</code>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Actions rapides</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <a href="{{ route($businessProfileRoutePrefix . '.show', $businessProfile) }}" 
                               class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Voir le profil
                            </a>
                            
                            @can('managePartnerRates', $businessProfile)
                            <a href="{{ route($businessProfileRoutePrefix . '.manage-partner-rates', $businessProfile) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                Gérer les partenaires
                            </a>
                            @endcan
                        </div>
                    </div>
                    
                    <!-- Live Preview -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" id="livePreview">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Aperçu en temps réel</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Nom</label>
                                <p id="preview-name" class="text-sm text-gray-900">{{ $businessProfile->name }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Audience</label>
                                <div id="preview-audience" class="flex flex-wrap gap-1">
                                    @php
                                        $currentAudience = $businessProfile->target_audience ?? [];
                                        if (is_string($currentAudience)) {
                                            $currentAudience = json_decode($currentAudience, true) ?? [];
                                        }
                                    @endphp
                                    @if(in_array('integrator', $currentAudience))
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Intégrateurs
                                        </span>
                                    @endif
                                    @if(in_array('operator', $currentAudience))
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Opérateurs
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Statut</label>
                                <div id="preview-status" class="flex flex-wrap gap-1">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $businessProfile->is_public ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $businessProfile->is_public ? 'Public' : 'Privé' }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $businessProfile->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $businessProfile->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="pt-4 border-t border-gray-200">
                                <label class="block text-sm font-medium text-gray-500 mb-2">Configuration tarifaire</label>
                                <div id="preview-pricing" class="space-y-2 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Abonnement:</span>
                                        <span id="preview-subscription" class="font-medium">
                                            @if($businessProfile->terminal_fee_period)
                                                {{ number_format($businessProfile->base_fee_amount + $businessProfile->terminal_fee_amount, 2) }} €
                                            @else
                                                Non configuré
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="flex justify-between items-center pt-8 border-t border-gray-200 bg-white px-6 py-4 rounded-xl shadow-sm">
                <a href="{{ route($businessProfileRoutePrefix . '.show', $businessProfile) }}" 
                   class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Annuler
                </a>
                
                <button type="submit" 
                        class="inline-flex items-center px-8 py-3 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Enregistrer les modifications
                </button>
            </div>
        </form>
        @endpermission
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live preview updates
    const nameInput = document.getElementById('name');
    const audienceCheckboxes = document.querySelectorAll('input[name="target_audience[]"]');
    const isPublicCheckbox = document.querySelector('input[name="is_public"]');
    const isActiveCheckbox = document.querySelector('input[name="is_active"]');
    
    // Update preview name
    nameInput.addEventListener('input', function() {
        document.getElementById('preview-name').textContent = this.value || '{{ $businessProfile->name }}';
    });
    
    // Update preview audience
    function updateAudiencePreview() {
        const previewAudience = document.getElementById('preview-audience');
        previewAudience.innerHTML = '';
        
        audienceCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const badge = document.createElement('span');
                badge.className = checkbox.value === 'integrator' 
                    ? 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800'
                    : 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800';
                badge.textContent = checkbox.value === 'integrator' ? 'Intégrateurs' : 'Opérateurs';
                previewAudience.appendChild(badge);
            }
        });
    }
    
    // Update preview status
    function updateStatusPreview() {
        const previewStatus = document.getElementById('preview-status');
        previewStatus.innerHTML = '';
        
        // Public/Private badge
        const visibilityBadge = document.createElement('span');
        visibilityBadge.className = isPublicCheckbox.checked 
            ? 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800'
            : 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
        visibilityBadge.textContent = isPublicCheckbox.checked ? 'Public' : 'Privé';
        previewStatus.appendChild(visibilityBadge);
        
        // Active/Inactive badge
        const activeBadge = document.createElement('span');
        activeBadge.className = isActiveCheckbox.checked 
            ? 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-1'
            : 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-1';
        activeBadge.textContent = isActiveCheckbox.checked ? 'Actif' : 'Inactif';
        previewStatus.appendChild(activeBadge);
    }
    
    // Add event listeners
    audienceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateAudiencePreview);
    });
    
    isPublicCheckbox.addEventListener('change', updateStatusPreview);
    isActiveCheckbox.addEventListener('change', updateStatusPreview);
    
    // Transaction fee type toggles
    const transactionFeeTypeCheckboxes = document.querySelectorAll('input[name="transaction_fee_type[]"]');
    const fixedSection = document.querySelector('.transaction-fixed-section');
    const percentageSection = document.querySelector('.transaction-percentage-section');
    
    function toggleTransactionFeeFields() {
        const fixedChecked = document.querySelector('input[name="transaction_fee_type[]"][value="fixed"]').checked;
        const percentageChecked = document.querySelector('input[name="transaction_fee_type[]"][value="percentage"]').checked;
        
        if (fixedSection) {
            fixedSection.style.display = fixedChecked ? 'block' : 'none';
        }
        if (percentageSection) {
            percentageSection.style.display = percentageChecked ? 'block' : 'none';
        }
    }
    
    transactionFeeTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', toggleTransactionFeeFields);
    });
    
    // Initial setup
    toggleTransactionFeeFields();
    updateAudiencePreview();
    updateStatusPreview();
});
</script>
@endsection
