@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="border-b border-gray-200 bg-white">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center">
                    <a href="{{ route('ocpp-tags.index') }}" class="mr-4 text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl lg:text-2xl font-bold text-gray-900">{{ __('Détails du Tag OCPP') }}</h1>
                        <p class="text-sm text-gray-500">
                            <code class="bg-gray-100 px-2 py-1 rounded font-mono">{{ $tag['idTag'] ?? 'N/A' }}</code>
                        </p>
                    </div>
                </div>
                @if(auth()->user()->hasRole(['admin', 'super_admin']))
                <div class="flex gap-3">
                    <a href="{{ route('ocpp-tags.edit', $tag['ocppTagPk']) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ __('Modifier') }}
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Informations principales -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Carte d'identité du tag -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                {{ $tag['idTag'] ?? 'N/A' }}
                            </h2>
                            @if($validation && $validation['valid'])
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('Valide') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('Invalide') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('ID Tag') }}</dt>
                                <dd class="mt-1 text-lg font-mono font-bold text-gray-900">{{ $tag['idTag'] ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('OCPP Tag PK') }}</dt>
                                <dd class="mt-1 text-lg font-mono text-gray-600">{{ $tag['ocppTagPk'] ?? 'N/A' }}</dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">{{ __('Note') }}</dt>
                                <dd class="mt-1 text-gray-900">{{ $tag['note'] ?? __('Aucune note') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Tag Parent') }}</dt>
                                <dd class="mt-1">
                                    @if(!empty($tag['parentIdTag']))
                                        <code class="bg-gray-100 px-2 py-1 rounded text-sm font-mono">{{ $tag['parentIdTag'] }}</code>
                                    @else
                                        <span class="text-gray-400">{{ __('Aucun (tag racine)') }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Utilisateur Associé') }}</dt>
                                <dd class="mt-1">
                                    @if(!empty($tag['userPk']))
                                        <span class="font-semibold">User #{{ $tag['userPk'] }}</span>
                                    @else
                                        <span class="text-gray-400">{{ __('Aucun') }}</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Validation détaillée -->
                @if($validation)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-6 py-4 border-b">
                        <h3 class="font-semibold text-gray-700">{{ __('Résultat de Validation') }}</h3>
                    </div>
                    <div class="p-6">
                        <div class="rounded-lg p-4 {{ $validation['valid'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                            <div class="flex">
                                @if($validation['valid'])
                                    <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @else
                                    <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @endif
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium {{ $validation['valid'] ? 'text-green-800' : 'text-red-800' }}">
                                        {{ $validation['message'] ?? 'N/A' }}
                                    </h3>
                                    @if(!empty($validation['active_count']) || !empty($validation['max_active']))
                                    <div class="mt-2 text-sm {{ $validation['valid'] ? 'text-green-700' : 'text-red-700' }}">
                                        <p>{{ __('Transactions actives') }}: {{ $validation['active_count'] ?? 0 }} / {{ ($validation['max_active'] ?? -1) < 0 ? '∞' : $validation['max_active'] }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar - Statut et Métriques -->
            <div class="space-y-6">
                <!-- Statut -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-800 px-6 py-4">
                        <h3 class="font-semibold text-white">{{ __('Statut') }}</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">{{ __('Bloqué') }}</span>
                            @if($tag['blocked'] ?? false)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <span class="h-2 w-2 mr-1 rounded-full bg-red-500"></span>
                                    {{ __('Oui') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="h-2 w-2 mr-1 rounded-full bg-green-500"></span>
                                    {{ __('Non') }}
                                </span>
                            @endif
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">{{ __('En Transaction') }}</span>
                            @if($tag['inTransaction'] ?? false)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <span class="h-2 w-2 mr-1 rounded-full bg-yellow-500 animate-pulse"></span>
                                    {{ __('Oui') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ __('Non') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Métriques -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-indigo-700 px-6 py-4">
                        <h3 class="font-semibold text-white">{{ __('Métriques') }}</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">{{ __('Transactions Actives') }}</span>
                            <span class="text-2xl font-bold text-indigo-600">{{ $tag['activeTransactionCount'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">{{ __('Max Autorisé') }}</span>
                            @php $maxActive = $tag['maxActiveTransactionCount'] ?? -1; @endphp
                            @if($maxActive == 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    {{ __('Bloqué') }}
                                </span>
                            @elseif($maxActive < 0)
                                <span class="text-2xl font-bold text-green-600">∞</span>
                            @else
                                <span class="text-2xl font-bold text-gray-900">{{ $maxActive }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Expiration -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-600 px-6 py-4">
                        <h3 class="font-semibold text-white">{{ __('Expiration') }}</h3>
                    </div>
                    <div class="p-6">
                        @if(!empty($tag['expiryDate']))
                            @php
                                $expiryDate = \Carbon\Carbon::parse($tag['expiryDate']);
                                $isExpired = $expiryDate->isPast();
                            @endphp
                            <div class="text-center">
                                <p class="text-2xl font-bold {{ $isExpired ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $expiryDate->format('d/m/Y') }}
                                </p>
                                <p class="text-sm text-gray-500">{{ $expiryDate->format('H:i') }}</p>
                                @if($isExpired)
                                    <p class="mt-2 text-red-600 font-medium">{{ __('Expiré depuis') }} {{ $expiryDate->diffForHumans() }}</p>
                                @else
                                    <p class="mt-2 text-green-600 font-medium">{{ __('Expire') }} {{ $expiryDate->diffForHumans() }}</p>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-green-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-gray-600 font-medium">{{ __('Pas d\'expiration') }}</p>
                                <p class="text-sm text-gray-400">{{ __('Ce tag n\'expire jamais') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

