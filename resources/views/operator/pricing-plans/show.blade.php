@extends('layouts.app')

@section('title', 'Détails du Plan Tarifaire')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $pricingPlan->name }}</h1>
                <div class="mt-2 flex items-center space-x-4">
                    @if($pricingPlan->is_public)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-globe mr-1"></i>Public
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <i class="fas fa-lock mr-1"></i>Privé
                        </span>
                    @endif
                    
                    @if($pricingPlan->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-check-circle mr-1"></i>Actif
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Inactif
                        </span>
                    @endif
                    
                    <span class="text-sm text-gray-500">
                        Créé le {{ $pricingPlan->created_at->format('d/m/Y à H:i') }}
                    </span>
                </div>
            </div>
            
            <div class="flex space-x-2">
                <a href="{{ route('operator.pricing-plans.edit', $pricingPlan) }}" 
                   class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    <i class="fas fa-edit mr-2"></i>Modifier
                </a>
                <a href="{{ route('operator.pricing-plans.index') }}" 
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
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Détails du plan tarifaire</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $pricingPlan->description ?: 'Aucune description' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Tarification -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Tarification</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Détails des prix</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Prix de base</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $pricingPlan->base_price }}€
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Prix par kWh</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $pricingPlan->price_per_kwh }}€
                        </dd>
                    </div>
                    @if($pricingPlan->price_per_minute)
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Prix par minute</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $pricingPlan->price_per_minute }}€
                        </dd>
                    </div>
                    @endif
                    @if($pricingPlan->connection_fee)
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Frais de connexion</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $pricingPlan->connection_fee }}€
                        </dd>
                    </div>
                    @endif
                    @if($pricingPlan->minimum_fee)
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Frais minimum</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $pricingPlan->minimum_fee }}€
                        </dd>
                    </div>
                    @endif
                    @if($pricingPlan->maximum_fee)
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Frais maximum</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $pricingPlan->maximum_fee }}€
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Charging Points utilisant ce plan -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Charging Points utilisant ce plan</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $chargingPoints->count() }} charging point(s) trouvé(s)</p>
            </div>
            
            @if($chargingPoints->count() > 0)
                <div class="border-t border-gray-200">
                    <ul class="divide-y divide-gray-200">
                        @foreach($chargingPoints as $chargingPoint)
                            <li class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900">{{ $chargingPoint->name }}</h4>
                                        <p class="text-sm text-gray-500">{{ $chargingPoint->location }}</p>
                                        <div class="mt-1 text-sm text-gray-500">
                                            <span>Puissance: {{ $chargingPoint->max_power }} kW</span>
                                            <span class="ml-4">Connecteur: {{ $chargingPoint->connector_type }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($chargingPoint->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Actif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Inactif
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="px-6 py-8 text-center">
                    <div class="mx-auto h-12 w-12 text-gray-400">
                        <i class="fas fa-charging-station text-2xl"></i>
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun charging point</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Ce plan n'est utilisé par aucun charging point pour le moment.
                    </p>
                </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="flex justify-between items-center">
            <div class="flex space-x-3">
                <a href="{{ route('operator.pricing-plans.edit', $pricingPlan) }}" 
                   class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    <i class="fas fa-edit mr-2"></i>Modifier
                </a>
                
                <form action="{{ route('operator.pricing-plans.destroy', $pricingPlan) }}" 
                      method="POST" 
                      class="inline"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce plan tarifaire ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-trash mr-2"></i>Supprimer
                    </button>
                </form>
            </div>
            
            <a href="{{ route('operator.pricing-plans.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
            </a>
        </div>
    </div>
</div>
@endsection
