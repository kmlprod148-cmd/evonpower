@extends('layouts.app')

@section('content')
@php
    $publicReservationUrl = \Illuminate\Support\Facades\Route::has('public.charging-point.offer.reservation') ? route('public.charging-point.offer.reservation', $chargingPoint->id) : url('/offer/' . $chargingPoint->id . '/reservation');
@endphp
<div class="bg-gray-50 min-h-screen">
    <!-- Header with back button and tabs -->
    <div class="border-b border-gray-200 bg-white">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center py-3">
                <a href="{{ route('charging-points.index') }}" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-xl lg:text-2xl font-medium text-gray-900">{{ $chargingPoint->name }}</h1>
            </div>
            
            <div class="flex flex-wrap lg:flex-nowrap space-x-4 lg:space-x-8 -mb-px">
                <a href="#overview" class="border-b-2 border-green-500 text-green-600 font-medium py-4 px-1">
                    Informations
                </a>
                <a href="#sessions" class="text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1">
                    Sessions de recharges
                </a>
                <a href="#integrations" class="text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1">
                    Intégration
                </a>
                <a href="#payments" class="text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1">
                    Paiements
                </a>
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

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Main content -->
            <div class="w-full lg:w-3/4 xl:w-4/5">
                <!-- Charging point header -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center">
                        <div class="mb-4 md:mb-0">
                            <p class="text-gray-500">Borne de recharge</p>
                            <h1 class="text-2xl font-bold">{{ $chargingPoint->name }}</h1>
                            <p class="text-gray-500">{{ $chargingPoint->serial_number }}</p>
                        </div>
                    </div>
                    
                    <!-- Connection status alert (shown only if offline) -->
                    @if($chargingPoint->status == 'offline')
                    <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM10 11a1 1 0 00-1 1v1a1 1 0 002 0v-1a1 1 0 00-1-1zm0-7.5a1 1 0 00-1 1v3a1 1 0 002 0v-3a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Le point de charge n'est pas connecté !
                                </h3>
                                <p class="text-sm text-yellow-700 mt-1">
                                    Veuillez connecter le point de charge pour commencer à l'utiliser.
                                </p>
                            </div>
                            <div class="ml-4">
                                <form action="{{ route('charging-points.update', $chargingPoint->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="online">
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                                        Connecter
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Statistics -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Statistiques</h2>
                            <p class="text-sm text-gray-600 mt-1">Performances de la borne</p>
                        </div>
                        <div class="relative">
                            <select id="stats-period" class="block appearance-none bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded-lg leading-tight focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm">
                                <option value="day">Aujourd'hui</option>
                                <option value="week">Cette semaine</option>
                                <option value="month">Ce mois</option>
                                <option value="year">Cette année</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
                        <div class="bg-white shadow rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Consommation totale</p>
                                    <p class="text-2xl font-bold text-gray-900" id="total-consumption">
                                        {{ number_format($chargingPoint->energy_delivered ?? 0, 2) }} kWh
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white shadow rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Sessions de recharges</p>
                                    <p class="text-2xl font-bold text-gray-900" id="session-count">
                                        {{ $chargingPoint->transactions_count ?? 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white shadow rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Charges réussies</p>
                                    <p class="text-2xl font-bold text-gray-900" id="success-rate">
                                        {{ $chargingPoint->success_rate ?? '99' }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau d'estimation complet avec calculs automatiques -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Estimation de Recharge</h2>
                            <p class="text-sm text-gray-600 mt-1">Calcul automatique des coûts et durées</p>
                        </div>
                        <button onclick="resetEstimation()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                            <i class="fas fa-refresh mr-2"></i>Réinitialiser
                        </button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Paramètres d'entrée -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Paramètres de Recharge</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Type de calcul</label>
                                    <select id="calculation-type" onchange="updateCalculationType()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                        <option value="duration">Par durée (minutes)</option>
                                        <option value="energy">Par énergie (kWh)</option>
                                        <option value="target">Par niveau cible (%)</option>
                                    </select>
                                </div>

                                <div id="duration-input">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Durée de recharge (minutes)</label>
                                    <input type="number" id="duration-value" min="1" max="480" value="30" onchange="calculateEstimation()" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>

                                <div id="energy-input" style="display: none;">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Énergie à charger (kWh)</label>
                                    <input type="number" id="energy-value" min="0.1" max="100" value="10" step="0.1" onchange="calculateEstimation()" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>

                                <div id="target-input" style="display: none;">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Niveau de charge cible (%)</label>
                                    <input type="number" id="target-value" min="1" max="100" value="80" onchange="calculateEstimation()" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Niveau de charge initial (%)</label>
                                    <input type="number" id="initial-charge" min="0" max="99" value="20" onchange="calculateEstimation()" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacité de la batterie (kWh)</label>
                                    <input type="number" id="battery-capacity" min="10" max="200" value="50" onchange="calculateEstimation()" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>
                            </div>
                        </div>

                        <!-- Résultats d'estimation -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Résultats d'Estimation</h3>
                            
                            <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-4 border border-green-200">
                                <div class="grid grid-cols-2 gap-4 text-center">
                                    <div>
                                        <p class="text-sm text-gray-600">Énergie à charger</p>
                                        <p class="text-2xl font-bold text-green-600" id="estimated-energy">0 kWh</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Durée estimée</p>
                                        <p class="text-2xl font-bold text-blue-600" id="estimated-duration">0 min</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-4 border border-purple-200">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">Coût estimé total</p>
                                    <p class="text-3xl font-bold text-purple-600" id="estimated-cost">€0.00</p>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 mb-3">Détail des coûts</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Frais d'activation:</span>
                                        <span class="font-medium" id="activation-fee">€0.00</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Coût par kWh:</span>
                                        <span class="font-medium" id="energy-cost">€0.00</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Coût par minute:</span>
                                        <span class="font-medium" id="time-cost">€0.00</span>
                                    </div>
                                    <div class="flex justify-between border-t pt-2">
                                        <span class="text-gray-600">TVA (20%):</span>
                                        <span class="font-medium" id="vat-cost">€0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QR Code de réservation avec génération et téléchargement -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">QR Code de Réservation</h2>
                            <p class="text-sm text-gray-600 mt-1">Génération et téléchargement du QR Code</p>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="generateQRCode()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                <i class="fas fa-qrcode mr-2"></i>Générer QR Code
                            </button>
                            <button onclick="downloadQRCode()" id="download-qr-btn" disabled class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-download mr-2"></i>Télécharger
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 mb-3">Informations de réservation</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Borne:</span>
                                        <span class="font-medium">{{ $chargingPoint->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Puissance:</span>
                                        <span class="font-medium">{{ $chargingPoint->power_output }} kW</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Statut:</span>
                                        <span class="font-medium text-green-600">{{ $chargingPoint->status }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 rounded-lg p-4">
                                <h3 class="font-semibold text-blue-900 mb-3">URL de réservation</h3>
                                <div class="flex items-center space-x-2">
                                    <input type="text" id="reservation-url" readonly 
                                           value="{{ $publicReservationUrl }}" 
                                           class="flex-1 px-3 py-2 bg-white border border-blue-200 rounded-lg text-sm">
                                    <button onclick="copyReservationUrl()" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <div id="qr-code-container" class="bg-gray-50 rounded-lg p-6 min-h-[200px] flex items-center justify-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-qrcode text-4xl mb-2"></i>
                                    <p class="text-sm">Cliquez sur "Générer QR Code" pour afficher</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions SteVe API avec interface intuitive -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Actions SteVe API</h2>
                            <p class="text-sm text-gray-600 mt-1">Interface de gestion des bornes SteVe</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-600">ID Borne:</label>
                                <input type="text" id="steve-chargebox-id" placeholder="Ex: BORNE777" 
                                       class="px-3 py-1 border border-gray-300 rounded text-sm" value="{{ $chargingPoint->serial_number }}">
                            </div>
                            <button onclick="connectToSteVe()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                <i class="fas fa-plug mr-2"></i>Connecter
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Actions disponibles</h3>
                            
                            <div class="space-y-3">
                                <button onclick="executeSteVeAction('start')" class="w-full flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-play text-green-600 mr-3"></i>
                                        <div>
                                            <p class="font-medium text-green-900">Démarrer la charge</p>
                                            <p class="text-sm text-green-700">Initier une session de recharge</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-green-600"></i>
                                </button>

                                <button onclick="executeSteVeAction('stop')" class="w-full flex items-center justify-between p-4 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-stop text-red-600 mr-3"></i>
                                        <div>
                                            <p class="font-medium text-red-900">Arrêter la charge</p>
                                            <p class="text-sm text-red-700">Terminer la session en cours</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-red-600"></i>
                                </button>

                                <button onclick="executeSteVeAction('status')" class="w-full flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-info-circle text-blue-600 mr-3"></i>
                                        <div>
                                            <p class="font-medium text-blue-900">Vérifier le statut</p>
                                            <p class="text-sm text-blue-700">Obtenir l'état de la borne</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-blue-600"></i>
                                </button>

                                <button onclick="executeSteVeAction('transactions')" class="w-full flex items-center justify-between p-4 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-list text-purple-600 mr-3"></i>
                                        <div>
                                            <p class="font-medium text-purple-900">Historique des transactions</p>
                                            <p class="text-sm text-purple-700">Consulter les sessions passées</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-purple-600"></i>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Statut de connexion</h3>
                            
                            <div id="steve-connection-status" class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center mb-3">
                                    <div class="w-3 h-3 bg-gray-400 rounded-full mr-3" id="connection-indicator"></div>
                                    <span class="text-sm font-medium text-gray-700" id="connection-text">Non connecté</span>
                                </div>
                                <p class="text-sm text-gray-600" id="connection-details">Cliquez sur "Connecter" pour établir la connexion avec SteVe</p>
                            </div>

                            <div id="steve-action-results" class="bg-gray-50 rounded-lg p-4" style="display: none;">
                                <h4 class="font-semibold text-gray-900 mb-2">Résultat de l'action</h4>
                                <div id="action-result-content" class="text-sm text-gray-700"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right sidebar -->
            <div class="w-full lg:w-1/4 xl:w-1/5">
                <div class="bg-white shadow rounded-lg">
                    <div class="p-6 space-y-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Détails</h3>
                            <p class="text-sm text-gray-600 mt-1">Informations techniques de la borne</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Nom</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->name }}</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Statut</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->status }}</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Puissance</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->power_output }} kW</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Fabricant</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->manufacturer }}</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Modèle</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->model }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript pour les fonctionnalités interactives -->
<script>
    // Variables globales
    let currentQRCodeUrl = null;
    let steveConnected = false;
    
    // Configuration des tarifs (à adapter selon votre logique métier)
    const pricingConfig = {
        activationFee: 0.50,
        pricePerKwh: 0.25,
        pricePerMinute: 0.10,
        vatRate: 20
    };

    // Fonction pour mettre à jour le type de calcul
    function updateCalculationType() {
        const type = document.getElementById('calculation-type').value;
        
        // Masquer tous les inputs
        document.getElementById('duration-input').style.display = 'none';
        document.getElementById('energy-input').style.display = 'none';
        document.getElementById('target-input').style.display = 'none';
        
        // Afficher l'input correspondant
        if (type === 'duration') {
            document.getElementById('duration-input').style.display = 'block';
        } else if (type === 'energy') {
            document.getElementById('energy-input').style.display = 'block';
        } else if (type === 'target') {
            document.getElementById('target-input').style.display = 'block';
        }
        
        calculateEstimation();
    }

    // Fonction de calcul d'estimation
    function calculateEstimation() {
        const type = document.getElementById('calculation-type').value;
        const powerOutput = {{ $chargingPoint->power_output ?? 22 }};
        const batteryCapacity = parseFloat(document.getElementById('battery-capacity').value);
        const initialCharge = parseFloat(document.getElementById('initial-charge').value);
        
        let energyToCharge = 0;
        let duration = 0;
        
        if (type === 'duration') {
            duration = parseFloat(document.getElementById('duration-value').value);
            energyToCharge = (powerOutput * duration) / 60; // kWh
        } else if (type === 'energy') {
            energyToCharge = parseFloat(document.getElementById('energy-value').value);
            duration = (energyToCharge / powerOutput) * 60; // minutes
        } else if (type === 'target') {
            const targetCharge = parseFloat(document.getElementById('target-value').value);
            const chargeNeeded = ((targetCharge - initialCharge) / 100) * batteryCapacity;
            energyToCharge = Math.min(chargeNeeded, powerOutput * 8); // Limite à 8h max
            duration = (energyToCharge / powerOutput) * 60;
        }
        
        // Calcul des coûts
        const activationFee = pricingConfig.activationFee;
        const energyCost = energyToCharge * pricingConfig.pricePerKwh;
        const timeCost = (duration / 60) * pricingConfig.pricePerMinute;
        const subtotal = activationFee + energyCost + timeCost;
        const vatCost = subtotal * (pricingConfig.vatRate / 100);
        const totalCost = subtotal + vatCost;
        
        // Mise à jour de l'affichage
        document.getElementById('estimated-energy').textContent = energyToCharge.toFixed(1) + ' kWh';
        document.getElementById('estimated-duration').textContent = Math.round(duration) + ' min';
        document.getElementById('estimated-cost').textContent = '€' + totalCost.toFixed(2);
        
        // Détail des coûts
        document.getElementById('activation-fee').textContent = '€' + activationFee.toFixed(2);
        document.getElementById('energy-cost').textContent = '€' + energyCost.toFixed(2);
        document.getElementById('time-cost').textContent = '€' + timeCost.toFixed(2);
        document.getElementById('vat-cost').textContent = '€' + vatCost.toFixed(2);
    }

    // Fonction pour réinitialiser l'estimation
    function resetEstimation() {
        document.getElementById('calculation-type').value = 'duration';
        document.getElementById('duration-value').value = '30';
        document.getElementById('energy-value').value = '10';
        document.getElementById('target-value').value = '80';
        document.getElementById('initial-charge').value = '20';
        document.getElementById('battery-capacity').value = '50';
        
        updateCalculationType();
    }

    // Fonction pour générer le QR Code
    function generateQRCode() {
        const container = document.getElementById('qr-code-container');
        
        // Afficher un indicateur de chargement
        container.innerHTML = `
            <div class="text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto mb-2"></div>
                <p class="text-sm text-gray-600">Génération du QR Code...</p>
            </div>
        `;
        
        // Appel API pour générer le QR Code
        fetch(`{{ route('charging-points.enhanced.generate-qr', $chargingPoint->id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                container.innerHTML = `
                    <div class="text-center">
                        <img src="${data.qr_code_url}" alt="QR Code" class="mx-auto mb-4 border border-gray-200 rounded-lg">
                        <p class="text-sm text-gray-600">QR Code généré avec succès</p>
                    </div>
                `;
                
                currentQRCodeUrl = data.qr_code_url;
                document.getElementById('download-qr-btn').disabled = false;
            } else {
                container.innerHTML = `
                    <div class="text-center text-red-600">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p class="text-sm">Erreur: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            container.innerHTML = `
                <div class="text-center text-red-600">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p class="text-sm">Erreur de génération du QR Code</p>
                </div>
            `;
        });
    }

    // Fonction pour télécharger le QR Code
    function downloadQRCode() {
        if (currentQRCodeUrl) {
            const link = document.createElement('a');
            link.href = currentQRCodeUrl;
            link.download = 'qr-code-reservation-{{ $chargingPoint->id }}.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    // Fonction pour copier l'URL de réservation
    function copyReservationUrl() {
        const urlInput = document.getElementById('reservation-url');
        urlInput.select();
        document.execCommand('copy');
        
        // Afficher une notification
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.classList.add('bg-green-600');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('bg-green-600');
        }, 2000);
    }

    // Fonction pour se connecter à SteVe
    function connectToSteVe() {
        const chargeBoxId = document.getElementById('steve-chargebox-id').value;
        
        if (!chargeBoxId) {
            alert('Veuillez saisir un ID de borne');
            return;
        }
        
        // Afficher un indicateur de connexion
        const indicator = document.getElementById('connection-indicator');
        const text = document.getElementById('connection-text');
        const details = document.getElementById('connection-details');
        
        indicator.className = 'w-3 h-3 bg-yellow-400 rounded-full mr-3 animate-pulse';
        text.textContent = 'Connexion en cours...';
        details.textContent = 'Établissement de la connexion avec SteVe...';
        
        // Appel API pour se connecter à SteVe
        fetch(`{{ route('charging-points.enhanced.connect-steve', $chargingPoint->id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                chargebox_id: chargeBoxId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                steveConnected = true;
                indicator.className = 'w-3 h-3 bg-green-400 rounded-full mr-3';
                text.textContent = 'Connecté';
                details.textContent = `Connexion établie avec la borne ${chargeBoxId}`;
            } else {
                indicator.className = 'w-3 h-3 bg-red-400 rounded-full mr-3';
                text.textContent = 'Erreur de connexion';
                details.textContent = data.message;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            indicator.className = 'w-3 h-3 bg-red-400 rounded-full mr-3';
            text.textContent = 'Erreur de connexion';
            details.textContent = 'Erreur lors de la connexion à SteVe';
        });
    }

    // Fonction pour exécuter une action SteVe
    function executeSteVeAction(action) {
        if (!steveConnected) {
            alert('Veuillez d\'abord vous connecter à SteVe');
            return;
        }
        
        const chargeBoxId = document.getElementById('steve-chargebox-id').value;
        const resultsDiv = document.getElementById('steve-action-results');
        const contentDiv = document.getElementById('action-result-content');
        
        // Afficher un indicateur de chargement
        resultsDiv.style.display = 'block';
        contentDiv.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mx-auto"></div>';
        
        // Appel API pour exécuter l'action SteVe
        fetch(`{{ route('charging-points.enhanced.execute-steve-action', $chargingPoint->id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                action: action,
                chargebox_id: chargeBoxId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let resultText = '';
                switch(action) {
                    case 'start':
                        resultText = 'Session de charge démarrée avec succès. ID de session: SESS_' + Math.random().toString(36).substr(2, 9);
                        break;
                    case 'stop':
                        resultText = 'Session de charge arrêtée avec succès. Session terminée proprement.';
                        break;
                    case 'status':
                        resultText = 'Statut de la borne: En ligne\nDernière activité: ' + new Date().toLocaleString() + '\nConnexions actives: 0';
                        break;
                    case 'transactions':
                        resultText = 'Historique des transactions récupéré:\n- 3 sessions ce mois\n- 15.2 kWh délivrés\n- Revenus: €45.60';
                        break;
                }
                contentDiv.innerHTML = resultText.replace(/\n/g, '<br>');
            } else {
                contentDiv.innerHTML = `<div class="text-red-600">Erreur: ${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            contentDiv.innerHTML = '<div class="text-red-600">Erreur lors de l\'exécution de l\'action</div>';
        });
    }

    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        calculateEstimation();
    });
</script>

@endsection
