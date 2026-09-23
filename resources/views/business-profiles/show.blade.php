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
            <span>{{ $businessProfile->name }}</span>
        </nav>

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="{{ route($businessProfileRoutePrefix . '.index') }}" class="mr-4 p-2 rounded-lg bg-white shadow-sm border border-gray-200 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <div class="flex items-center">
                        <!-- Profile Icon -->
                        <div class="flex-shrink-0 mr-4">
                            @php
                                $targetAudience = is_array($businessProfile->target_audience) ? $businessProfile->target_audience : json_decode($businessProfile->target_audience, true) ?? [];
                                $hasIntegrator = in_array('integrator', $targetAudience);
                                $hasOperator = in_array('operator', $targetAudience);
                            @endphp
                            @if($hasIntegrator && $hasOperator)
                                <div class="h-16 w-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">
                                    {{ substr($businessProfile->name, 0, 2) }}
                                </div>
                            @elseif($hasIntegrator)
                                <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center shadow-lg">
                                    <img src="{{ asset('images/Integrateur.png') }}" alt="Intégrateur" class="h-10 w-10">
                                </div>
                            @elseif($hasOperator)
                                <div class="h-16 w-16 rounded-full bg-purple-100 flex items-center justify-center shadow-lg">
                                    <img src="{{ asset('images/Operateur.png') }}" alt="Opérateur" class="h-10 w-10">
                                </div>
                            @else
                                <div class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-bold text-xl shadow-lg">
                                    {{ substr($businessProfile->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ $businessProfile->name }}</h1>
                            <div class="flex items-center space-x-2 mt-2">
                                <!-- Status Badges -->
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $businessProfile->is_public ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    <div class="w-2 h-2 rounded-full {{ $businessProfile->is_public ? 'bg-green-400' : 'bg-gray-400' }} mr-2"></div>
                                    {{ $businessProfile->is_public ? 'Public' : 'Privé' }}
                                </span>
                                
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $businessProfile->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    <div class="w-2 h-2 rounded-full {{ $businessProfile->is_active ? 'bg-green-400' : 'bg-red-400' }} mr-2"></div>
                                    {{ $businessProfile->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                    @if($hasIntegrator && $hasOperator)
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        Intégrateurs & Opérateurs
                                    @elseif($hasIntegrator)
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        Intégrateurs
                                    @elseif($hasOperator)
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Opérateurs
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                            @if($businessProfile->description)
                            <p class="text-gray-600 mt-2 max-w-2xl">{{ $businessProfile->description }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex items-center space-x-3">
                    @can('update', $businessProfile)
                    <a href="{{ route($businessProfileRoutePrefix . '.edit', $businessProfile) }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        Modifier
                    </a>
                    @endcan
                    
                    @if($hasIntegrator)
                    <a href="{{ route($businessProfileRoutePrefix . '.manage-partner-rates', $businessProfile) }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Gérer les partenaires
                    </a>
                    @endif
                    
                    @can('delete', $businessProfile)
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="inline-flex items-center px-4 py-2 border border-red-300 rounded-lg shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Supprimer
                        </button>
                        
                        <!-- Delete Confirmation Modal -->
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <div class="p-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Confirmer la suppression</h3>
                                <p class="text-sm text-gray-600 mb-4">Êtes-vous sûr de vouloir supprimer ce profil ? Cette action est irréversible.</p>
                                <div class="flex justify-end space-x-2">
                                    <button @click="open = false" class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                                        Annuler
                                    </button>
                                    <form action="{{ route($businessProfileRoutePrefix . '.destroy', $businessProfile) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Main Information -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Frais d'abonnement</p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    @if($businessProfile->subscription_period && ($businessProfile->base_fee_amount > 0 || $businessProfile->terminal_fee_amount > 0))
                                        {{ number_format($businessProfile->base_fee_amount + $businessProfile->terminal_fee_amount, 2) }}€
                                    @else
                                        --
                                    @endif
                                </p>
                                @if($businessProfile->subscription_period)
                                <p class="text-xs text-gray-500">
                                    @switch($businessProfile->subscription_period)
                                        @case('monthly') par mois @break
                                        @case('quarterly') par trimestre @break
                                        @case('yearly') par an @break
                                        @default {{ $businessProfile->subscription_period }}
                                    @endswitch
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Frais de transaction</p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    @php
                                        $transactionConfig = is_string($businessProfile->transaction_fee_config) 
                                            ? json_decode($businessProfile->transaction_fee_config, true) 
                                            : $businessProfile->transaction_fee_config;
                                        $hasTransactionFee = false;
                                        if ($transactionConfig) {
                                            $fixedAmount = $transactionConfig['fixed_amount'] ?? 0;
                                            $percentage = $transactionConfig['percentage'] ?? 0;
                                            $hasTransactionFee = $fixedAmount > 0 || $percentage > 0;
                                        }
                                    @endphp
                                    {{ $hasTransactionFee ? 'Configuré' : '--' }}
                                </p>
                                @if($hasTransactionFee)
                                <p class="text-xs text-gray-500">Frais par transaction</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Frais de recharge</p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    @php
                                        $chargeConfig = is_string($businessProfile->charge_fee_config) 
                                            ? json_decode($businessProfile->charge_fee_config, true) 
                                            : $businessProfile->charge_fee_config;
                                        $hasChargeFee = false;
                                        if ($chargeConfig) {
                                            $fixedAmount = $chargeConfig['fixed_amount'] ?? 0;
                                            $percentage = $chargeConfig['percentage'] ?? 0;
                                            $hasChargeFee = $fixedAmount > 0 || $percentage > 0;
                                        }
                                    @endphp
                                    {{ $hasChargeFee ? 'Configuré' : '--' }}
                                </p>
                                @if($hasChargeFee)
                                <p class="text-xs text-gray-500">Frais par recharge</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Pricing Configuration -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            Configuration tarifaire détaillée
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Subscription Fees -->
                            <div>
                                <h3 class="text-base font-medium text-gray-900 mb-4 flex items-center">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                                    Frais d'abonnement
                                </h3>
                                
                                @if($businessProfile->subscription_period && ($businessProfile->base_fee_amount > 0 || $businessProfile->terminal_fee_amount > 0))
                                    <div class="bg-blue-50 rounded-lg p-4 space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Période de facturation</span>
                                            <span class="text-sm font-medium text-gray-900 capitalize">
                                                @switch($businessProfile->subscription_period)
                                                    @case('monthly') Mensuel @break
                                                    @case('quarterly') Trimestriel @break
                                                    @case('yearly') Annuel @break
                                                    @default {{ $businessProfile->subscription_period }}
                                                @endswitch
                                            </span>
                                        </div>
                                        
                                        @if($businessProfile->base_fee_amount > 0)
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Frais de base</span>
                                            <span class="text-sm font-medium text-gray-900">{{ number_format($businessProfile->base_fee_amount, 2) }} €</span>
                                        </div>
                                        @endif
                                        
                                        @if($businessProfile->terminal_fee_amount > 0)
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Frais par borne</span>
                                            <span class="text-sm font-medium text-gray-900">{{ number_format($businessProfile->terminal_fee_amount, 2) }} €</span>
                                        </div>
                                        @endif
                                        
                                        <div class="pt-3 border-t border-blue-200">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm font-medium text-gray-700">Coût pour 1 borne</span>
                                                <span class="text-base font-semibold text-blue-600">{{ number_format($businessProfile->base_fee_amount + $businessProfile->terminal_fee_amount, 2) }} €</span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                                        <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-sm text-gray-500">Aucun frais d'abonnement configuré</p>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Transaction & Charge Fees -->
                            <div>
                                <h3 class="text-base font-medium text-gray-900 mb-4 flex items-center">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                    Frais de transaction et recharge
                                </h3>
                                
                                <div class="space-y-4">
                                    <!-- Transaction Fees -->
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Frais de transaction</h4>
                                        @if($hasTransactionFee)
                                            <div class="bg-green-50 rounded-lg p-3 space-y-2">
                                                @if(isset($transactionConfig['fixed_amount']) && $transactionConfig['fixed_amount'] > 0)
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm text-gray-600">Montant fixe</span>
                                                    <span class="text-sm font-medium text-gray-900">{{ number_format($transactionConfig['fixed_amount'], 2) }} €</span>
                                                </div>
                                                @endif
                                                
                                                @if(isset($transactionConfig['percentage']) && $transactionConfig['percentage'] > 0)
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm text-gray-600">Pourcentage</span>
                                                    <span class="text-sm font-medium text-gray-900">{{ number_format($transactionConfig['percentage'], 2) }} %</span>
                                                </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                                <p class="text-sm text-gray-500">Non configuré</p>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Charge Fees -->
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Frais de recharge</h4>
                                        @if($hasChargeFee)
                                            <div class="bg-purple-50 rounded-lg p-3 space-y-2">
                                                @if(isset($chargeConfig['fixed_amount']) && $chargeConfig['fixed_amount'] > 0)
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm text-gray-600">Montant fixe</span>
                                                    <span class="text-sm font-medium text-gray-900">{{ number_format($chargeConfig['fixed_amount'], 2) }} €</span>
                                                </div>
                                                @endif
                                                
                                                @if(isset($chargeConfig['percentage']) && $chargeConfig['percentage'] > 0)
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm text-gray-600">Pourcentage</span>
                                                    <span class="text-sm font-medium text-gray-900">{{ number_format($chargeConfig['percentage'], 2) }} %</span>
                                                </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                                <p class="text-sm text-gray-500">Non configuré</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Configuration Status -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            @php
                                $hasValidConfig = ($businessProfile->subscription_period && ($businessProfile->base_fee_amount > 0 || $businessProfile->terminal_fee_amount > 0)) ||
                                                $hasTransactionFee || $hasChargeFee;
                            @endphp
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700">Statut de la configuration</span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $hasValidConfig ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    @if($hasValidConfig)
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Configuration complète
                                    @else
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        Configuration incomplète
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Distribution -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            Répartition des Revenus
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Commission Opérateur (Partenaire)</span>
                                <span class="text-sm font-medium text-gray-900">{{ number_format($businessProfile->operator_commission ?? 0, 2) }} %</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Commission Intégrateur</span>
                                <span class="text-sm font-medium text-gray-900">{{ number_format($businessProfile->integrator_commission ?? 0, 2) }} %</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Commission Propriétaire</span>
                                <span class="text-sm font-medium text-gray-900">{{ number_format($businessProfile->owner_commission ?? 0, 2) }} %</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing Plan Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2zM9 7h6m-3 4h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01"></path>
                            </svg>
                            Détails du Plan Tarifaire
                        </h2>
                    </div>
                    <div class="p-6">
                        @if($businessProfile->pricingPlan)
                            <div class="bg-orange-50 rounded-lg p-4 space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Nom du Plan</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $businessProfile->pricingPlan->name }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Type de Tarif</span>
                                    <span class="text-sm font-medium text-gray-900 capitalize">{{ $businessProfile->pricingPlan->rate_type }}</span>
                                </div>
                                @if($businessProfile->pricingPlan->rate_type === 'time')
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Prix par minute</span>
                                    <span class="text-sm font-medium text-gray-900">{{ number_format($businessProfile->pricingPlan->price_per_minute, 2) }} {{ $businessProfile->pricingPlan->currency ?? 'EUR' }}/min</span>
                                </div>
                                @elseif($businessProfile->pricingPlan->rate_type === 'energy')
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Prix par kWh</span>
                                    <span class="text-sm font-medium text-gray-900">{{ number_format($businessProfile->pricingPlan->price_per_kwh, 2) }} {{ $businessProfile->pricingPlan->currency ?? 'EUR' }}/kWh</span>
                                </div>
                                @else
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Tarif de base</span>
                                    <span class="text-sm font-medium text-gray-900">{{ number_format($businessProfile->pricingPlan->base_rate, 2) }} {{ $businessProfile->pricingPlan->currency ?? 'EUR' }}</span>
                                </div>
                                @endif
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Frais d'activation</span>
                                    <span class="text-sm font-medium text-gray-900">{{ number_format($businessProfile->pricingPlan->activation_fee, 2) }} {{ $businessProfile->pricingPlan->currency ?? 'EUR' }}</span>
                                </div>
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-sm text-gray-500">Aucun plan tarifaire associé</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Associated Partners (if applicable) -->
                @if(isset($partners) && count($partners) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Partenaires associés
                            </h2>
                            <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ count($partners) }} partenaire(s)</span>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partenaire</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($partners as $partner)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-medium">
                                                    {{ substr($partner->name, 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $partner->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $partner->email ?? 'Pas d\'email' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $partner->type ?? 'Partenaire' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $partner->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $partner->is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('partners.show', $partner) }}" class="text-blue-600 hover:text-blue-900 transition-colors">
                                            Voir détails
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
            
            <!-- Right Column: Sidebar -->
            <div class="space-y-6">
                <!-- Quick Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Informations rapides</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">ID du profil</label>
                            <code class="text-sm bg-gray-100 px-2 py-1 rounded font-mono">{{ $businessProfile->id }}</code>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Créé le</label>
                            <p class="text-sm text-gray-900">{{ $businessProfile->created_at?->format('d/m/Y à H:i') ?? 'Non défini' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Dernière modification</label>
                            <p class="text-sm text-gray-900">{{ $businessProfile->updated_at?->format('d/m/Y à H:i') ?? 'Non défini' }}</p>
                        </div>
                        
                        @if(isset($businessProfile->creator))
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Créé par</label>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $businessProfile->creator->getRoleBadgeColor() ?? 'gray' }}-100 text-{{ $businessProfile->creator->getRoleBadgeColor() ?? 'gray' }}-800">
                                    {{ $businessProfile->creator->role ?? 'Utilisateur' }}
                                </span>
                                <span class="text-sm text-gray-900">{{ $businessProfile->creator->company_name ?? $businessProfile->creator->name ?? 'Système' }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Actions rapides</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        @can('update', $businessProfile)
                        <a href="{{ route($businessProfileRoutePrefix . '.edit', $businessProfile) }}" 
                           class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                            </svg>
                            Modifier le profil
                        </a>
                        @endcan
                        
                        <a href="{{ route($businessProfileRoutePrefix . '.index') }}" 
                           class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                            </svg>
                            Retour à la liste
                        </a>
                        
                        @if($hasIntegrator)
                        <a href="{{ route($businessProfileRoutePrefix . '.manage-partner-rates', $businessProfile) }}" 
                           class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            Gérer les partenaires
                        </a>
                        @endif
                    </div>
                </div>
                
                <!-- Usage Statistics (if available) -->
                @if(isset($stats))
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Statistiques d'utilisation</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Utilisateurs actifs</span>
                            <span class="text-2xl font-bold text-blue-600">{{ $stats->active_users ?? 0 }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Bornes associées</span>
                            <span class="text-2xl font-bold text-green-600">{{ $stats->terminals_count ?? 0 }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Transactions</span>
                            <span class="text-2xl font-bold text-purple-600">{{ $stats->transactions_count ?? 0 }}</span>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/alpine.min.js') }}" defer></script>
@endsection
