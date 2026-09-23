@extends('layouts.app')

@section('title', 'Détails du Business Profile')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $businessProfile->name }}</h1>
                <div class="mt-2 flex items-center space-x-4">
                    @if($businessProfile->is_public)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-globe mr-1"></i>Public
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <i class="fas fa-lock mr-1"></i>Privé
                        </span>
                    @endif
                    
                    @if($businessProfile->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-check-circle mr-1"></i>Actif
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Inactif
                        </span>
                    @endif
                    
                    <span class="text-sm text-gray-500">
                        Créé le {{ $businessProfile->created_at->format('d/m/Y à H:i') }}
                    </span>
                </div>
            </div>
            
            <div class="flex space-x-2">
                <a href="{{ route('integrator.business-profiles.edit', $businessProfile) }}" 
                   class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    <i class="fas fa-edit mr-2"></i>Modifier
                </a>
                <a href="{{ route('integrator.business-profiles.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Informations générales -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Informations générales</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Détails du business profile</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ ucfirst($businessProfile->type) }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $businessProfile->description ?: 'Aucune description' }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Audience cible</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            @if($businessProfile->target_audience)
                                @php
                                    $audience = is_string($businessProfile->target_audience) 
                                        ? json_decode($businessProfile->target_audience, true) 
                                        : $businessProfile->target_audience;
                                @endphp
                                @if(is_array($audience))
                                    {{ implode(', ', $audience) }}
                                @else
                                    {{ $audience }}
                                @endif
                            @else
                                Non spécifiée
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Configuration des frais -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Configuration des frais</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Frais de transaction et de charge</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <!-- Frais de transaction -->
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Frais de transaction</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            @if($businessProfile->transaction_fee_config)
                                @php
                                    $config = is_string($businessProfile->transaction_fee_config) 
                                        ? json_decode($businessProfile->transaction_fee_config, true) 
                                        : $businessProfile->transaction_fee_config;
                                @endphp
                                @if(isset($config['fixed_amount']) && $config['fixed_amount'] > 0)
                                    Montant fixe: {{ number_format($config['fixed_amount'], 2) }} EUR
                                @endif
                                @if(isset($config['percentage']) && $config['percentage'] > 0)
                                    @if(isset($config['fixed_amount']) && $config['fixed_amount'] > 0), @endif
                                    Pourcentage: {{ number_format($config['percentage'], 2) }}%
                                @endif
                                @if((!isset($config['fixed_amount']) || $config['fixed_amount'] == 0) && (!isset($config['percentage']) || $config['percentage'] == 0))
                                    Aucun frais configuré
                                @endif
                            @else
                                Aucun frais configuré
                            @endif
                        </dd>
                    </div>
                    
                    <!-- Frais de charge -->
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Frais de charge</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            @if($businessProfile->charge_fee_config)
                                @php
                                    $config = is_string($businessProfile->charge_fee_config) 
                                        ? json_decode($businessProfile->charge_fee_config, true) 
                                        : $businessProfile->charge_fee_config;
                                @endphp
                                @if(isset($config['fixed_amount']) && $config['fixed_amount'] > 0)
                                    Montant fixe: {{ number_format($config['fixed_amount'], 2) }} EUR
                                @endif
                                @if(isset($config['percentage']) && $config['percentage'] > 0)
                                    @if(isset($config['fixed_amount']) && $config['fixed_amount'] > 0), @endif
                                    Pourcentage: {{ number_format($config['percentage'], 2) }}%
                                @endif
                                @if((!isset($config['fixed_amount']) || $config['fixed_amount'] == 0) && (!isset($config['percentage']) || $config['percentage'] == 0))
                                    Aucun frais configuré
                                @endif
                            @else
                                Aucun frais configuré
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-between items-center">
            <div class="flex space-x-3">
                <a href="{{ route('integrator.business-profiles.edit', $businessProfile) }}" 
                   class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    <i class="fas fa-edit mr-2"></i>Modifier
                </a>
                
                <form action="{{ route('integrator.business-profiles.destroy', $businessProfile) }}" 
                      method="POST" 
                      class="inline"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce business profile ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-trash mr-2"></i>Supprimer
                    </button>
                </form>
            </div>
            
            <a href="{{ route('integrator.business-profiles.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
            </a>
        </div>
    </div>
</div>
@endsection
