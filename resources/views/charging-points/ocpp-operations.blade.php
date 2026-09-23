@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header with back button -->
    <div class="border-b border-gray-200 bg-white">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center py-4">
                <a href="{{ route('charging-points.show', $chargingPoint->id) }}" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold text-gray-900">{{ __('Opérations OCPP Tag') }}</h1>
                    <p class="text-sm text-gray-500">{{ $chargingPoint->name }} - {{ $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        @if(session('success'))
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Colonne principale - Actions -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Carte d'actions rapides -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            {{ __('Contrôle à Distance') }}
                        </h2>
                        <p class="text-green-100 text-sm mt-1">{{ __('Démarrer ou arrêter une session de charge avec le Tag ID') }}: <strong>{{ $defaultTag }}</strong></p>
                    </div>
                    
                    <div class="p-6">
                        <!-- Quick Start avec Tag par défaut -->
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <h3 class="font-semibold text-green-800 mb-3 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ __('Démarrage Rapide') }}
                            </h3>
                            <p class="text-sm text-green-700 mb-4">{{ __('Démarre immédiatement une session de charge avec le tag') }} <code class="bg-green-100 px-2 py-1 rounded font-mono">{{ $defaultTag }}</code></p>
                            
                            <form action="{{ route('charging-points.ocpp-tag.quick-start', $chargingPoint) }}" method="POST" class="flex flex-wrap gap-3" id="quickStartForm">
                                @csrf
                                <div class="flex items-center gap-2">
                                    <label for="quick_connector_id" class="text-sm font-medium text-gray-700">{{ __('Connecteur') }}:</label>
                                    <select name="connector_id" id="quick_connector_id" class="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                    </select>
                                </div>
                                <button type="submit" class="inline-flex items-center px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors shadow-md" id="quickStartBtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    </svg>
                                    {{ __('Démarrer la Charge') }}
                                </button>
                            </form>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Démarrer avec Tag personnalisé -->
                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <h3 class="font-semibold text-blue-800 mb-3 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    {{ __('Démarrer (Tag Personnalisé)') }}
                                </h3>
                                <form action="{{ route('charging-points.ocpp-tag.start', $chargingPoint) }}" method="POST" id="startForm">
                                    @csrf
                                    <div class="space-y-3">
                                        <div>
                                            <label for="id_tag" class="block text-sm font-medium text-gray-700">{{ __('ID Tag OCPP') }}</label>
                                            <input type="text" name="id_tag" id="id_tag" value="{{ $defaultTag }}" 
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                placeholder="Ex: Open10Tag">
                                        </div>
                                        <div>
                                            <label for="connector_id" class="block text-sm font-medium text-gray-700">{{ __('Connecteur') }}</label>
                                            <select name="connector_id" id="connector_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                                <option value="1">{{ __('Connecteur') }} 1</option>
                                                <option value="2">{{ __('Connecteur') }} 2</option>
                                                <option value="3">{{ __('Connecteur') }} 3</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            </svg>
                                            {{ __('Démarrer') }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Arrêter une transaction -->
                            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                <h3 class="font-semibold text-red-800 mb-3 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                                    </svg>
                                    {{ __('Arrêter une Transaction') }}
                                </h3>
                                <form action="{{ route('charging-points.ocpp-tag.stop', $chargingPoint) }}" method="POST" id="stopForm">
                                    @csrf
                                    <div class="space-y-3">
                                        <div>
                                            <label for="transaction_id" class="block text-sm font-medium text-gray-700">{{ __('ID Transaction') }}</label>
                                            <input type="number" name="transaction_id" id="transaction_id" required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"
                                                placeholder="Ex: 12345">
                                            <p class="mt-1 text-xs text-gray-500">{{ __('Entrez l\'ID de la transaction active à arrêter') }}</p>
                                        </div>
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z" />
                                            </svg>
                                            {{ __('Arrêter') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Arrêter toutes les transactions -->
                        @if(auth()->user()->hasRole(['admin', 'super_admin']))
                        <div class="mt-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                            <h3 class="font-semibold text-orange-800 mb-3 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ __('Arrêt d\'Urgence') }} (Admin)
                            </h3>
                            <p class="text-sm text-orange-700 mb-4">{{ __('Arrête toutes les transactions actives sur cette borne. À utiliser avec précaution.') }}</p>
                            <form action="{{ route('charging-points.ocpp-tag.stop-all', $chargingPoint) }}" method="POST" onsubmit="return confirm('{{ __('Êtes-vous sûr de vouloir arrêter TOUTES les transactions actives ?') }}');">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white font-medium rounded-lg hover:bg-orange-700 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {{ __('Arrêter Toutes les Transactions') }}
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Historique des opérations -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('Historique des Opérations') }}
                        </h2>
                    </div>
                    <div class="p-6">
                        @if(count($operationHistory) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Heure') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Opération') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ID Tag') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Statut') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($operationHistory as $operation)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($operation['timestamp'])->format('H:i:s') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $operation['operation'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $operation['id_tag'] ?? 'N/A' }}</code>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($operation['success'])
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                    {{ __('Succès') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                    </svg>
                                                    {{ __('Échec') }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-8 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p>{{ __('Aucune opération récente') }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Colonne latérale - Informations -->
            <div class="space-y-6">
                <!-- Statut du point de charge -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-800 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('Statut') }}
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">{{ __('Point de charge') }}</span>
                                <span class="font-semibold">{{ $chargingPoint->name }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">ChargeBox ID</span>
                                <code class="bg-gray-100 px-2 py-1 rounded text-sm font-mono">{{ $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id ?? 'N/A' }}</code>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">{{ __('Statut') }}</span>
                                @php
                                    $cpStatus = $chargingPoint->status ?? 'unknown';
                                @endphp
                                @if($cpStatus === 'online')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-green-500"></span>
                                        {{ __('En ligne') }}
                                    </span>
                                @elseif($cpStatus === 'offline')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-red-500"></span>
                                        {{ __('Hors ligne') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-gray-500"></span>
                                        {{ __('Inconnu') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations sur le Tag OCPP -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-indigo-700 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            {{ __('Tag OCPP par Défaut') }}
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">ID Tag</span>
                                <code class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded text-sm font-mono font-bold">{{ $defaultTag }}</code>
                            </div>
                            @if($tagInfo['success'] && isset($tagInfo['data']))
                                @if(isset($tagInfo['data']['expiryDate']))
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">{{ __('Expiration') }}</span>
                                    <span class="text-sm">{{ \Carbon\Carbon::parse($tagInfo['data']['expiryDate'])->format('d/m/Y H:i') }}</span>
                                </div>
                                @endif
                                @if(isset($tagInfo['data']['maxActiveTransactionCount']))
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">{{ __('Max Transactions') }}</span>
                                    <span class="font-semibold">{{ $tagInfo['data']['maxActiveTransactionCount'] }}</span>
                                </div>
                                @endif
                                @if(isset($tagInfo['data']['activeTransactionCount']))
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">{{ __('Actives') }}</span>
                                    <span class="font-semibold text-orange-600">{{ $tagInfo['data']['activeTransactionCount'] }}</span>
                                </div>
                                @endif
                            @else
                            <div class="text-sm text-gray-500 italic">
                                {{ __('Informations non disponibles depuis l\'API Steve') }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Transactions actives -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-yellow-600 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            {{ __('Transactions Actives') }}
                        </h2>
                    </div>
                    <div class="p-6">
                        @if(count($activeTransactions) > 0)
                        <div class="space-y-3">
                            @foreach($activeTransactions as $transaction)
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-semibold text-gray-800">
                                            {{ __('Transaction') }} #{{ $transaction['transactionPk'] ?? $transaction['transactionId'] ?? 'N/A' }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            Tag: <code class="bg-gray-100 px-1 rounded">{{ $transaction['ocppIdTag'] ?? $transaction['idTag'] ?? 'N/A' }}</code>
                                        </p>
                                    </div>
                                    <button type="button" 
                                        onclick="document.getElementById('transaction_id').value='{{ $transaction['transactionPk'] ?? $transaction['transactionId'] ?? '' }}'; document.getElementById('stopForm').scrollIntoView({behavior: 'smooth'});"
                                        class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200 transition-colors">
                                        {{ __('Arrêter') }}
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-4 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm">{{ __('Aucune transaction active') }}</p>
                        </div>
                        @endif
                        
                        @if($cachedTransaction)
                        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-800 font-semibold">{{ __('Dernière transaction locale') }}</p>
                            <p class="text-xs text-blue-600">
                                Tag: {{ $cachedTransaction['id_tag'] ?? 'N/A' }} - 
                                Démarrée: {{ $cachedTransaction['started_at'] ?? 'N/A' }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Configuration -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-600 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ __('Configuration API') }}
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Base URL</span>
                                <code class="bg-gray-100 px-2 py-1 rounded text-xs truncate max-w-[150px]" title="{{ $config['base_url'] ?? 'N/A' }}">{{ $config['base_url'] ?? 'N/A' }}</code>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Timeout</span>
                                <span class="font-semibold">{{ $config['timeout'] ?? 30 }}s</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation de chargement pour les boutons
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Traitement...';
            }
        });
    });

    // Auto-refresh des transactions actives toutes les 30 secondes
    setInterval(function() {
        fetch('{{ route("charging-points.ocpp-tag.active-transactions", $chargingPoint) }}')
            .then(response => response.json())
            .then(data => {
                console.log('Active transactions updated:', data);
            })
            .catch(error => console.log('Error fetching active transactions:', error));
    }, 30000);
});
</script>
@endpush
@endsection

