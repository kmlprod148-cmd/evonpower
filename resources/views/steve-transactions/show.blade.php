@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="border-b border-gray-200 bg-white">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center py-4">
                <a href="{{ route('steve-transactions.index') }}" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold text-gray-900">{{ __('Transaction OCPP') }} #{{ $transaction['id'] ?? 'N/A' }}</h1>
                    <p class="text-sm text-gray-500">{{ __('Détails de la transaction de charge') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Informations principales -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Carte d'identité -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                {{ __('Informations de Transaction') }}
                            </h2>
                            @if($transaction['is_active'])
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                    <span class="h-2 w-2 mr-1 rounded-full bg-yellow-500 animate-pulse"></span>
                                    {{ __('En cours') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('Terminée') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Transaction ID') }}</dt>
                                <dd class="mt-1 text-lg font-mono font-bold text-gray-900">#{{ $transaction['id'] ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('ChargeBox ID') }}</dt>
                                <dd class="mt-1">
                                    <code class="bg-gray-100 px-3 py-1 rounded text-sm font-mono">{{ $transaction['chargeBoxId'] ?? 'N/A' }}</code>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Tag OCPP') }}</dt>
                                <dd class="mt-1">
                                    <code class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded text-sm font-mono font-bold">{{ $transaction['ocppIdTag'] ?? 'N/A' }}</code>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Connecteur') }}</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $transaction['connectorId'] ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Chronologie -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-6 py-4 border-b">
                        <h3 class="font-semibold text-gray-700 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('Chronologie') }}
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <!-- Début -->
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="flex items-center justify-center h-10 w-10 rounded-full bg-green-100">
                                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ __('Début de la charge') }}</p>
                                    <p class="text-sm text-gray-500">{{ $transaction['start_formatted'] ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ __('Compteur') }}: {{ $transaction['startValue'] ?? 'N/A' }} Wh</p>
                                </div>
                            </div>

                            @if(!$transaction['is_active'])
                            <!-- Fin -->
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="flex items-center justify-center h-10 w-10 rounded-full bg-red-100">
                                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ __('Fin de la charge') }}</p>
                                    <p class="text-sm text-gray-500">{{ $transaction['stop_formatted'] ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ __('Compteur') }}: {{ $transaction['stopValue'] ?? 'N/A' }} Wh</p>
                                    @if(!empty($transaction['stopReason']))
                                    <p class="text-xs text-gray-500 mt-1">{{ __('Raison') }}: {{ $transaction['stopReason'] }}</p>
                                    @endif
                                    @if(!empty($transaction['stopEventActor']))
                                    <p class="text-xs text-gray-500">{{ __('Acteur') }}: {{ $transaction['stopEventActor'] }}</p>
                                    @endif
                                </div>
                            </div>
                            @else
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="flex items-center justify-center h-10 w-10 rounded-full bg-yellow-100">
                                        <svg class="h-6 w-6 text-yellow-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-yellow-800">{{ __('Charge en cours...') }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('La transaction est toujours active') }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Données techniques -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-6 py-4 border-b">
                        <h3 class="font-semibold text-gray-700 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            {{ __('Données Techniques') }}
                        </h3>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('ChargeBox PK') }}</dt>
                                <dd class="mt-1 text-sm font-mono text-gray-900">{{ $transaction['chargeBoxPk'] ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('OCPP Tag PK') }}</dt>
                                <dd class="mt-1 text-sm font-mono text-gray-900">{{ $transaction['ocppTagPk'] ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Valeur Début') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $transaction['startValue'] ?? 'N/A' }} Wh</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Valeur Fin') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $transaction['stopValue'] ?? 'N/A' }} Wh</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Sidebar - Métriques -->
            <div class="space-y-6">
                <!-- Énergie consommée -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                        <h3 class="font-semibold text-white flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            {{ __('Énergie') }}
                        </h3>
                    </div>
                    <div class="p-6 text-center">
                        @if($transaction['energy_consumed_kwh'])
                            <p class="text-4xl font-bold text-green-600">{{ $transaction['energy_consumed_kwh'] }}</p>
                            <p class="text-sm text-gray-500 mt-1">kWh</p>
                            <p class="text-xs text-gray-400 mt-2">{{ $transaction['energy_consumed_wh'] }} Wh</p>
                        @else
                            <p class="text-gray-400">{{ __('Non disponible') }}</p>
                        @endif
                    </div>
                </div>

                <!-- Durée -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                        <h3 class="font-semibold text-white flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('Durée') }}
                        </h3>
                    </div>
                    <div class="p-6 text-center">
                        @if($transaction['duration_formatted'])
                            <p class="text-4xl font-bold text-indigo-600">{{ $transaction['duration_formatted'] }}</p>
                            <p class="text-xs text-gray-400 mt-2">{{ $transaction['duration_minutes'] }} minutes</p>
                            <p class="text-xs text-gray-400">{{ $transaction['duration_hours'] }} heures</p>
                        @else
                            <p class="text-gray-400">{{ __('En cours...') }}</p>
                        @endif
                    </div>
                </div>

                <!-- Statut -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-800 px-6 py-4">
                        <h3 class="font-semibold text-white">{{ __('Statut') }}</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">{{ __('État') }}</span>
                            @if($transaction['is_active'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ __('Active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ __('Terminée') }}
                                </span>
                            @endif
                        </div>
                        @if(!empty($transaction['stopReason']))
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">{{ __('Raison Arrêt') }}</span>
                            <span class="text-sm font-medium text-gray-900">{{ $transaction['stopReason'] }}</span>
                        </div>
                        @endif
                        @if(!empty($transaction['stopEventActor']))
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">{{ __('Acteur Arrêt') }}</span>
                            <span class="text-sm font-medium text-gray-900">{{ ucfirst($transaction['stopEventActor']) }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Identifiants -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-600 px-6 py-4">
                        <h3 class="font-semibold text-white">{{ __('Identifiants') }}</h3>
                    </div>
                    <div class="p-6 space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Transaction PK</span>
                            <code class="bg-gray-100 px-2 py-1 rounded font-mono">{{ $transaction['id'] ?? 'N/A' }}</code>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">ChargeBox PK</span>
                            <code class="bg-gray-100 px-2 py-1 rounded font-mono">{{ $transaction['chargeBoxPk'] ?? 'N/A' }}</code>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">OCPP Tag PK</span>
                            <code class="bg-gray-100 px-2 py-1 rounded font-mono">{{ $transaction['ocppTagPk'] ?? 'N/A' }}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

