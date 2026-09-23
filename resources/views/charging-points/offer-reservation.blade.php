@extends('layouts.public')

@section('title', 'Réservation de la borne')

@push('styles')
    <link rel="stylesheet" href="{{ asset("vendor/fontawesome/css/all.min.css") }}">
    <link rel="stylesheet" href="{{ asset('css/offer-reservation.css') }}">
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-500 to-green-700 text-white p-6 rounded-t-lg flex items-center justify-between mb-6 shadow-lg">
            <div>
                <h1 class="text-2xl font-bold" id="station-name">{{ $chargingPoint->name ?? 'N/A' }}</h1>
                <p class="flex items-center mt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ $chargingPoint->address ?? 'Adresse non disponible' }}</span>
                </p>
            </div>
            <div class="bg-white p-3 rounded-lg flex items-center justify-center shadow-md">
                <img id="qr-code" src="{{ $qrCode }}" alt="QR Code de la borne" class="w-16 h-16 object-contain">
            </div>
        </div>

        <!-- Messages -->
        <div id="messages"></div>

        @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-4">
            {{ session('error') }}
        </div>
        @endif

        <!-- Floating Cost Card -->
        <div class="fixed top-4 left-4 right-4 z-40 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200 shadow-lg max-w-sm mx-auto backdrop-blur-sm" id="cost-calculation-card">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-calculator text-green-600 mr-2"></i>
                    <span class="text-sm font-medium text-green-800">Estimation</span>
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold text-green-700" id="total-ttc">0.00 {{ $currency ?? 'EUR' }}</div>
                    <div class="text-xs text-green-600">TTC</div>
                </div>
            </div>
            <div class="mt-3 text-xs text-green-700">
                <div class="flex justify-between">
                    <span>Base:</span>
                    <span id="base-amount">0.00 {{ $currency ?? 'EUR' }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Activation:</span>
                    <span>{{ number_format($pricingPlan->activation_fee ?? 2.50, 2) }} {{ $currency ?? 'EUR' }}</span>
                </div>
            </div>
        </div>

        <!-- Station Information -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200 mb-6">
            <div class="bg-blue-50 p-4 border-b border-blue-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4 text-center">
                    <i class="fas fa-charging-station text-blue-600 mr-2"></i>
                    Informations de la Station
                </h3>
                <div class="bg-blue-50 rounded-lg p-4 mb-6 border border-blue-200">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Puissance:</span>
                            <span class="font-semibold">{{ $chargingPoint->power_output ?? 'N/A' }} kW</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Partenaire:</span>
                            <span class="font-semibold">{{ optional($chargingPoint->partner)->name ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Plan tarifaire:</span>
                            <span class="font-semibold text-blue-600">{{ $pricingPlan->name ?? 'Plan Standard' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Type:</span>
                            <span class="font-semibold">{{ ucfirst($pricingPlan->rate_type ?? 'Mixte') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservation Type -->
        @if($showReservationType)
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200 mb-6">
            <div class="bg-blue-50 p-4 border-b border-blue-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-charging-station text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Type de Réservation</h3>
                        <p class="text-sm text-gray-600">Mode de facturation du plan</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if($reservationType === 'kwh' || $reservationType === 'mixed')
                    <div class="reservation-option bg-white border-2 border-gray-200 rounded-lg p-6 text-center hover:border-blue-500 transition-all cursor-pointer {{ $reservationType === 'kwh' ? 'border-blue-500 bg-blue-50' : '' }}" onclick="selectReservationType('kwh')" data-type="kwh">
                        <div class="text-4xl text-blue-600 mb-3">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Par Énergie</h4>
                        <p class="text-sm text-gray-600 mb-2">Facturation par kWh</p>
                        <div class="text-sm font-semibold text-blue-600">
                            {{ number_format($pricingPlan->price_per_kwh ?? 0.45, 2) }} {{ $currency ?? 'EUR' }}/kWh
                        </div>
                    </div>
                    @endif
                    @if($reservationType === 'minute' || $reservationType === 'mixed')
                    <div class="reservation-option bg-white border-2 border-gray-200 rounded-lg p-6 text-center hover:border-green-500 transition-all cursor-pointer {{ $reservationType === 'minute' ? 'border-green-500 bg-green-50' : '' }}" onclick="selectReservationType('minute')" data-type="minute">
                        <div class="text-4xl text-green-600 mb-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Par Durée</h4>
                        <p class="text-sm text-gray-600 mb-2">Facturation par minute</p>
                        <div class="text-sm font-semibold text-green-600">
                            {{ number_format($pricingPlan->price_per_minute ?? 0.25, 2) }} {{ $currency ?? 'EUR' }}/min
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Duration Selection -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200 mb-6">
            <div class="bg-purple-50 p-4 border-b border-purple-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-hourglass-half text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Durée ou Quantité</h3>
                        <p class="text-sm text-gray-600">Sélectionnez votre durée de charge</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
                    <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-500 transition-all cursor-pointer" data-duration="10">
                        <div class="text-2xl font-bold text-gray-900">10</div>
                        <div class="text-sm text-gray-600">min</div>
                    </button>
                    <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-500 transition-all cursor-pointer" data-duration="20">
                        <div class="text-2xl font-bold text-gray-900">20</div>
                        <div class="text-sm text-gray-600">min</div>
                    </button>
                    <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-500 transition-all cursor-pointer" data-duration="30">
                        <div class="text-2xl font-bold text-gray-900">30</div>
                        <div class="text-sm text-gray-600">min</div>
                    </button>
                    <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-500 transition-all cursor-pointer" data-duration="40">
                        <div class="text-2xl font-bold text-gray-900">40</div>
                        <div class="text-sm text-gray-600">min</div>
                    </button>
                    <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-500 transition-all cursor-pointer" data-duration="50">
                        <div class="text-2xl font-bold text-gray-900">50</div>
                        <div class="text-sm text-gray-600">min</div>
                    </button>
                    <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-500 transition-all cursor-pointer" data-duration="60">
                        <div class="text-2xl font-bold text-gray-900">60</div>
                        <div class="text-sm text-gray-600">min</div>
                    </button>
                </div>

                <div class="mb-6">
                    <label for="custom-value" class="block text-sm font-medium text-gray-700 mb-2">Ou saisissez une valeur personnalisée</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" id="custom-value" placeholder="Ex: 15, 25..." min="10" max="480" step="10" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-sm text-gray-600 font-medium">min</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Valeurs par intervalles de 10 minutes
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Method Selection -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200 mb-6">
            <div class="bg-gradient-to-r from-purple-500 to-purple-700 p-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-credit-card text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Méthode de Paiement</h3>
                        <p class="text-sm text-purple-100">Choisissez votre mode de paiement préféré</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Credit Payment -->
                    @php
                        // Vérifier si l'utilisateur est un client (pas admin, integrator, operator, partner)
                        $isClient = false;
                        if (Auth::check()) {
                            $user = Auth::user();
                            $systemRoles = ['admin', 'integrator', 'operator', 'partner'];
                            $hasSystemRole = false;
                            foreach ($systemRoles as $role) {
                                if ($user->hasRole($role)) {
                                    $hasSystemRole = true;
                                    break;
                                }
                            }
                            $isClient = !$hasSystemRole;
                        }
                    @endphp
                    @if(auth()->check() && $isClient)
                    <div class="payment-method border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-green-500 transition-all credit-option" 
                         data-method="credit" 
                         onclick="selectPaymentMethod('credit')">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-3">
                                <i class="fas fa-wallet text-white text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-1">Paiement par Solde</h4>
                            <p class="text-sm text-gray-600 mb-2">Crédit: <span id="user-balance-display">{{ number_format(auth()->user()->wallet->balance ?? auth()->user()->getOrCreateWallet()->balance ?? 0, 2) }} EUR</span></p>
                            <div class="flex items-center justify-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-bolt mr-1"></i>
                                    Instantané
                                </span>
                            </div>
                            <div class="payment-radio mt-3">
                                <input type="radio" name="payment_method" value="credit" class="w-4 h-4 text-green-600">
                            </div>
                        </div>
                    </div>
                    @else
                    <!-- Authentication Required for Credit Payment -->
                    <div class="payment-method border-2 border-yellow-400 rounded-lg p-4 cursor-pointer hover:border-yellow-500 transition-all auth-required" data-method="auth-required" onclick="showAuthRequired()">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center mb-3">
                                <i class="fas fa-user-shield text-white text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-1">Paiement par Solde</h4>
                            <p class="text-sm text-gray-600 mb-2">Identification requise</p>
                            <div class="flex items-center justify-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-lock mr-1"></i>
                                    Connexion
                                </span>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Stripe Payment -->
                    <div class="payment-method border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-purple-500 transition-all" 
                         data-method="stripe"
                         onclick="selectPaymentMethod('stripe')">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-3">
                                <i class="fab fa-stripe text-white text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-1">Stripe</h4>
                            <p class="text-sm text-gray-600 mb-2">International</p>
                            <div class="payment-radio mt-3">
                                <input type="radio" name="payment_method" value="stripe" class="w-4 h-4 text-purple-600">
                            </div>
                        </div>
                    </div>

                    <!-- CMI Payment -->
                    <div class="payment-method border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-500 transition-all" 
                         data-method="cmi"
                         onclick="selectPaymentMethod('cmi')">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-3">
                                <i class="fas fa-university text-white text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-1">CMI</h4>
                            <p class="text-sm text-gray-600 mb-2">Local</p>
                            <div class="payment-radio mt-3">
                                <input type="radio" name="payment_method" value="cmi" class="w-4 h-4 text-blue-600">
                            </div>
                        </div>
                    </div>

                    <!-- Sur Place Payment (Offline) -->
                    <div class="payment-method border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-orange-500 transition-all" 
                         data-method="offline"
                         onclick="selectPaymentMethod('offline')">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center mb-3">
                                <i class="fas fa-cash-register text-white text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-1">Sur Place</h4>
                            <p class="text-sm text-gray-600 mb-2">Espèces/Carte</p>
                            <div class="payment-radio mt-3">
                                <input type="radio" name="payment_method" value="offline" class="w-4 h-4 text-orange-600" checked>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Credit Balance Check -->
                <div id="credit-info" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg hidden">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-wallet text-green-600 mr-2"></i>
                            <span class="text-sm font-medium text-green-800">Solde disponible:</span>
                        </div>
                        <span class="text-lg font-bold text-green-700" id="available-balance">0.00 EUR</span>
                    </div>
                    <div class="mt-2 text-xs text-green-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        Le montant sera déduit de votre solde de crédit instantanément
                    </div>
                </div>

                <!-- Insufficient Balance Warning -->
                <div id="insufficient-balance" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg hidden">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                        <div>
                            <p class="font-semibold text-red-800">Solde insuffisant</p>
                            <p class="text-sm text-red-700">Votre solde actuel: <span id="current-balance">0.00</span> EUR</p>
                            <p class="text-sm text-red-700">Montant requis: <span id="required-amount">0.00</span> EUR</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Immediate Start Mode -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200 mb-6">
            <div class="bg-green-50 p-4 border-b border-green-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-bolt text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Démarrage Immédiat</h3>
                        <p class="text-sm text-gray-600">Votre recharge commencera immédiatement après le paiement</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-check-circle text-green-600"></i>
                        <span class="text-sm font-medium text-gray-700">Mode de démarrage immédiat activé</span>
                    </div>
                </div>
                <div class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    La recharge commencera automatiquement via l'API Steve après validation du paiement
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-4">
            <button class="flex-1 bg-gradient-to-r from-green-600 to-green-700 text-green-600 py-3 px-6 rounded-xl font-semibold hover:from-green-700 hover:to-green-800 transition-all duration-300 disabled:bg-gray-400 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:-translate-y-1" id="reserve-btn" onclick="submitReservation()" disabled>
                <i class="fas fa-check mr-2"></i>
                Confirmer la Réservation
            </button>
            <button class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-green-600 py-3 px-6 rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 transition-all duration-300 disabled:bg-gray-400 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:-translate-y-1" id="checkout-btn" onclick="processPayment()" disabled>
                <i class="fas fa-credit-card mr-2"></i>
                Payer Maintenant
            </button>
        </div>


        <!-- Module Crédit (Admin/Intégrateur uniquement) -->
        @if(isset($creditData) && $creditData)
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200 mt-6">
            <div class="bg-gradient-to-r from-purple-500 to-purple-700 p-4 border-b border-purple-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-wallet text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Gestion des Crédits</h3>
                            <p class="text-sm text-purple-100">Ajouter du crédit et consulter l'historique</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglets -->
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button onclick="switchCreditTab('add')" id="credit-tab-add" class="credit-tab-button active border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-plus-circle mr-2"></i>Ajouter du crédit
                    </button>
                    <button onclick="switchCreditTab('history')" id="credit-tab-history" class="credit-tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-history mr-2"></i>Historique
                    </button>
                </nav>
            </div>

            <!-- Contenu onglet "Ajouter du crédit" -->
            <div id="credit-tab-content-add" class="credit-tab-content p-6">
                <form id="add-credit-form-offer" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label for="credit_user_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Utilisateur <span class="text-red-500">*</span>
                        </label>
                        <select name="user_id" id="credit_user_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Sélectionnez un utilisateur</option>
                            @foreach($creditData['users'] as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="credit_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Montant (EUR) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="amount" id="credit_amount" step="0.01" min="0.01" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="0.00">
                        </div>

                        <div>
                            <label for="credit_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Type <span class="text-red-500">*</span>
                            </label>
                            <select name="type" id="credit_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="manuel">Manuel</option>
                                <option value="bonus">Bonus</option>
                                <option value="automatique">Automatique</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="credit_commentaire" class="block text-sm font-medium text-gray-700 mb-2">
                            Description/Commentaire
                        </label>
                        <textarea name="commentaire" id="credit_commentaire" rows="2"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Description du crédit (optionnel)"></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-4">
                        <button type="button" onclick="resetCreditForm()"
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            Réinitialiser
                        </button>
                        <button type="submit"
                            class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium">
                            <i class="fas fa-check mr-2"></i>Ajouter le crédit
                        </button>
                    </div>
                </form>

                <!-- Message de résultat -->
                <div id="credit-form-result" class="mt-4 hidden"></div>
            </div>

            <!-- Contenu onglet "Historique" -->
            <div id="credit-tab-content-history" class="credit-tab-content hidden p-6">
                <!-- Statistiques -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Total crédits</p>
                        <p class="text-2xl font-bold text-green-600">
                            {{ number_format($creditData['statistics']['total_credits'] ?? 0, 2) }} EUR
                        </p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Total transactions</p>
                        <p class="text-2xl font-bold text-blue-600">
                            {{ $creditData['statistics']['total_transactions'] ?? 0 }}
                        </p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Par type</p>
                        <div class="text-sm text-gray-700 mt-1">
                            @foreach($creditData['statistics']['by_type'] ?? [] as $type => $total)
                                <div>{{ ucfirst($type) }}: {{ number_format($total, 2) }} EUR</div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Tableau de l'historique -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Créé par</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commentaire</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($creditData['history']->items() as $transaction)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->user->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-green-600">
                                        +{{ number_format($transaction->amount, 2) }} EUR
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                                            @if($transaction->type == 'bonus') bg-yellow-100 text-yellow-800
                                            @elseif($transaction->type == 'automatique') bg-blue-100 text-blue-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->creator->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ $transaction->commentaire ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-center text-sm text-gray-500">
                                        Aucune transaction de crédit trouvée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($creditData['history']->hasPages())
                    <div class="mt-4">
                        {{ $creditData['history']->links() }}
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    // State Management
    const state = {
        paymentMethod: 'offline', // Par défaut, paiement sur place
        reservationType: null,
        duration: null,
        startTime: null,
        userBalance: {{ auth()->check() && $isClient ? (float)(auth()->user()->wallet->balance ?? auth()->user()->getOrCreateWallet()->balance ?? 0) : 0 }},
        prices: {
            perKwh: {{ $pricingPlan->price_per_kwh ?? 0.45 }},
            perMinute: {{ $pricingPlan->price_per_minute ?? 0.25 }},
            activation: {{ $pricingPlan->activation_fee ?? 2.50 }},
            vatRate: 0.20
        }
    };

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        setCurrentTime();
        updateButtonStates();
        
        // Set default start time if not set
        if (!state.startTime) {
            state.startTime = 'immediate';
        }
        
        // Auto-select reservation type if only one option is available
        @if($showReservationType && $reservationType !== 'mixed')
        selectReservationType('{{ $reservationType }}');
        @endif

        // Load user balance if authenticated and is client
        @if(auth()->check() && $isClient)
        loadUserCreditBalance();
        @endif
    });

    // Reservation Type Selection
    function selectReservationType(type) {
        state.reservationType = type;
        
        // Update UI
        document.querySelectorAll('.reservation-option').forEach(opt => {
            opt.classList.remove('border-blue-500', 'border-green-500', 'bg-blue-50', 'bg-green-50');
            opt.classList.add('border-gray-200');
        });
        
        const selectedOption = document.querySelector(`[data-type="${type}"]`);
        if (selectedOption) {
            selectedOption.classList.remove('border-gray-200');
            if (type === 'kwh') {
                selectedOption.classList.add('border-blue-500', 'bg-blue-50');
            } else {
                selectedOption.classList.add('border-green-500', 'bg-green-50');
            }
        }
        
        showMessage(`Facturation par ${type === 'kwh' ? 'énergie' : 'durée'} sélectionnée`, 'success');
        updateButtonStates();
        calculateCost();
    }

    // Duration Selection
    function selectDuration(duration) {
        state.duration = duration;
        
        // Update UI
        document.querySelectorAll('.duration-btn').forEach(btn => {
            btn.classList.remove('border-blue-500', 'bg-blue-50');
            btn.classList.add('border-gray-200');
        });
        
        const selectedBtn = document.querySelector(`[data-duration="${duration}"]`);
        if (selectedBtn) {
            selectedBtn.classList.remove('border-gray-200');
            selectedBtn.classList.add('border-blue-500', 'bg-blue-50');
        }
        
        // Clear custom input
        document.getElementById('custom-value').value = '';
        
        showMessage(`${duration} minutes sélectionnées`, 'success');
        updateButtonStates();
        calculateCost();
    }

    // Custom Value Handler
    function handleCustomValue() {
        const input = document.getElementById('custom-value');
        const value = parseInt(input.value);
        
        // Clear duration buttons
        document.querySelectorAll('.duration-btn').forEach(btn => {
            btn.classList.remove('border-blue-500', 'bg-blue-50');
            btn.classList.add('border-gray-200');
        });
        
        if (value && value >= 10 && value <= 480 && value % 10 === 0) {
            state.duration = value;
            showMessage(`${value} minutes sélectionnées`, 'success');
            calculateCost();
        } else if (value) {
            showMessage('Valeur entre 10 et 480 minutes, par intervalles de 10', 'error');
            state.duration = null;
        } else {
            state.duration = null;
        }
        
        updateButtonStates();
    }

    // Time Functions
    function setCurrentTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const timeInput = document.getElementById('start-time');
        
        // Vérifier que l'élément existe avant de le modifier
        if (timeInput) {
            timeInput.value = `${hours}:${minutes}`;
            state.startTime = timeInput.value;
            updateButtonStates();
        } else {
            // Si l'élément n'existe pas, définir l'heure actuelle dans l'état
            state.startTime = `${hours}:${minutes}`;
            // Ne pas afficher de warning si l'élément n'existe pas (c'est normal pour certaines vues)
        }
    }

    // Cost Calculation
    function calculateCost() {
        if (!state.reservationType || !state.duration) {
            return;
        }

        let baseAmount = 0;
        
        if (state.reservationType === 'kwh') {
            // Estimate: 50kW charger, efficiency ~80%
            const estimatedKwh = (state.duration / 60) * 50 * 0.8;
            baseAmount = estimatedKwh * state.prices.perKwh;
        } else {
            baseAmount = state.duration * state.prices.perMinute;
        }

        const subtotal = baseAmount + state.prices.activation;
        const vat = subtotal * state.prices.vatRate;
        const total = subtotal + vat;

        // Update UI
        document.getElementById('base-amount').textContent = `${baseAmount.toFixed(2)} {{ $currency ?? 'EUR' }}`;
        document.getElementById('total-ttc').textContent = `${total.toFixed(2)} {{ $currency ?? 'EUR' }}`;
        
        // Vérifier le solde si paiement par crédit sélectionné
        if (state.paymentMethod === 'credit') {
            checkCreditBalance();
        }
    }

    // Update Button States
    function updateButtonStates() {
        const reserveBtn = document.getElementById('reserve-btn');
        const checkoutBtn = document.getElementById('checkout-btn');
        
        const isValid = state.reservationType && state.duration && state.startTime;
        
        reserveBtn.disabled = !isValid;
        checkoutBtn.disabled = !isValid;
    }

    // Payment Method Selection
    function selectPaymentMethod(method) {
        state.paymentMethod = method;
        
        // Update UI
        document.querySelectorAll('.payment-method').forEach(opt => {
            opt.classList.remove('border-green-500', 'border-purple-500', 'border-blue-500', 'border-orange-500', 'bg-green-50', 'bg-purple-50', 'bg-blue-50', 'bg-orange-50');
            opt.classList.add('border-gray-200');
        });
        
        const selectedOption = document.querySelector(`[data-method="${method}"]`);
        if (selectedOption) {
            selectedOption.classList.remove('border-gray-200');
            if (method === 'credit') {
                selectedOption.classList.add('border-green-500', 'bg-green-50');
            } else if (method === 'stripe') {
                selectedOption.classList.add('border-purple-500', 'bg-purple-50');
            } else if (method === 'cmi') {
                selectedOption.classList.add('border-blue-500', 'bg-blue-50');
            } else {
                selectedOption.classList.add('border-orange-500', 'bg-orange-50');
            }
        }
        
        // Update radio button
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.checked = (radio.value === method);
        });
        
        // Check credit balance if credit payment selected
        if (method === 'credit') {
            checkCreditBalance();
        } else {
            hideCreditInfo();
        }
        
        updateButtonStates();
    }

    // Load User Credit Balance
    async function loadUserCreditBalance() {
        @php
            $isClient = false;
            if (Auth::check()) {
                $user = Auth::user();
                $systemRoles = ['admin', 'integrator', 'operator', 'partner'];
                $hasSystemRole = false;
                foreach ($systemRoles as $role) {
                    if ($user->hasRole($role)) {
                        $hasSystemRole = true;
                        break;
                    }
                }
                $isClient = !$hasSystemRole;
            }
        @endphp
        @if(!auth()->check() || !$isClient)
        return;
        @endif
        
        try {
            const response = await fetch('/credits/balance/api', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                const balanceElements = document.querySelectorAll('#user-balance-display, #available-balance, #current-balance');
                balanceElements.forEach(element => {
                    if (element) element.textContent = data.formatted_balance;
                });
                state.userBalance = data.balance;
                
                // Check balance if credit payment is selected
                if (state.paymentMethod === 'credit') {
                    checkCreditBalance();
                }
            } else {
                const balanceElement = document.getElementById('user-balance-display');
                if (balanceElement) {
                    balanceElement.textContent = 'Erreur';
                }
                state.userBalance = 0;
            }
        } catch (error) {
            console.error('Error loading credit balance:', error);
            const balanceElement = document.getElementById('user-balance-display');
            if (balanceElement) {
                balanceElement.textContent = 'Erreur';
            }
            state.userBalance = 0;
        }
    }

    // Check Credit Balance
    function checkCreditBalance() {
        if (!state.paymentMethod || state.paymentMethod !== 'credit') {
            hideCreditInfo();
            return;
        }

        const totalCost = calculateTotalCost();
        const userBalance = state.userBalance || 0;
        
        // Update balance display
        const availableBalanceEl = document.getElementById('available-balance');
        const currentBalanceEl = document.getElementById('current-balance');
        const requiredAmountEl = document.getElementById('required-amount');
        
        if (availableBalanceEl) availableBalanceEl.textContent = userBalance.toFixed(2) + ' EUR';
        if (currentBalanceEl) currentBalanceEl.textContent = userBalance.toFixed(2);
        if (requiredAmountEl) requiredAmountEl.textContent = totalCost.toFixed(2);
        
        if (userBalance >= totalCost) {
            // Solde suffisant
            document.getElementById('credit-info')?.classList.remove('hidden');
            document.getElementById('insufficient-balance')?.classList.add('hidden');
            
            // Activer les boutons
            document.getElementById('reserve-btn').disabled = false;
            document.getElementById('checkout-btn').disabled = false;
        } else {
            // Solde insuffisant
            document.getElementById('credit-info')?.classList.add('hidden');
            document.getElementById('insufficient-balance')?.classList.remove('hidden');
            
            // Désactiver les boutons
            document.getElementById('reserve-btn').disabled = true;
            document.getElementById('checkout-btn').disabled = true;
        }
    }

    function hideCreditInfo() {
        document.getElementById('credit-info')?.classList.add('hidden');
        document.getElementById('insufficient-balance')?.classList.add('hidden');
    }

    function calculateTotalCost() {
        if (!state.duration) return 0;
        
        let baseAmount = 0;
        if (state.reservationType === 'kwh') {
            const estimatedKwh = (state.duration / 60) * 50 * 0.8;
            baseAmount = estimatedKwh * state.prices.perKwh;
        } else {
            baseAmount = state.duration * state.prices.perMinute;
        }
        
        const subtotal = baseAmount + state.prices.activation;
        const vat = subtotal * state.prices.vatRate;
        return subtotal + vat;
    }

    // Submit Reservation
    function submitReservation() {
        if (!state.reservationType || !state.duration) {
            showMessage('Veuillez sélectionner un type de réservation et une durée', 'error');
            return;
        }
        
        // Si paiement par crédit, utiliser processCreditPayment
        if (state.paymentMethod === 'credit') {
            processCreditPayment();
            return;
        }
        
        showMessage('Réservation en cours...', 'success');
        
        // Prepare form data
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        formData.append('type', state.reservationType === 'kwh' ? 'energy' : 'duration');
        formData.append('value', state.duration);
        formData.append('time', state.startTime || 'immediate');
        formData.append('customer_name', '{{ auth()->check() ? auth()->user()->name : "Client Web" }}');
        formData.append('customer_email', '{{ auth()->check() ? auth()->user()->email : "" }}');
        formData.append('customer_phone', '');
        formData.append('payment_method', state.paymentMethod || 'offline');
        
        // Submit reservation
        const storeUrl = '{{ route("reservations.store", ["chargingPoint" => $chargingPoint->id]) }}';
        fetch(storeUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Réponse serveur invalide (vérifiez les logs)');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showMessage('Réservation confirmée avec succès!', 'success');
                setTimeout(() => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else if (data.reservation_id) {
                        window.location.href = '{{ route("reservations.thank-you", ":id") }}'.replace(':id', data.reservation_id);
                    }
                }, 2000);
            } else {
                showMessage(data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Erreur lors de la réservation'), 'error');
            }
        })
        .catch(error => {
            showMessage('Erreur lors de la réservation: ' + error.message, 'error');
        });
    }

    // Process Credit Payment
    async function processCreditPayment() {
        if (!state.paymentMethod || state.paymentMethod !== 'credit') {
            showMessage('Méthode de paiement par crédit non sélectionnée', 'error');
            return;
        }

        const totalCost = calculateTotalCost();
        const userBalance = state.userBalance || 0;

        if (userBalance < totalCost) {
            showMessage('Solde insuffisant pour cette transaction', 'error');
            return;
        }

        // Vérifier que les détails de réservation sont complets
        if (!state.reservationType || !state.duration) {
            showMessage('Veuillez compléter tous les détails de réservation avant de procéder au paiement', 'warning');
            return;
        }

        try {
            // Show loading state
            const btn = document.getElementById('reserve-btn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Traitement en cours...';
            }

            // Préparer les données de réservation avec paiement par crédit
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('type', state.reservationType === 'kwh' ? 'energy' : 'duration');
            formData.append('value', state.duration);
            formData.append('time', 'immediate');
            formData.append('customer_name', '{{ auth()->check() ? auth()->user()->name : "Client Web" }}');
            formData.append('customer_email', '{{ auth()->check() ? auth()->user()->email : "" }}');
            formData.append('customer_phone', '');
            formData.append('payment_method', 'credit');

            // Soumettre la réservation avec paiement par crédit
            const storeUrl = '{{ route("reservations.store", ["chargingPoint" => $chargingPoint->id]) }}';
            const response = await fetch(storeUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Réponse serveur invalide (HTTP ' + response.status + ')');
            }
            const result = await response.json();

            if (result.success) {
                // Mettre à jour le solde utilisateur si disponible (après débit TTC)
                if (result.remaining_balance !== undefined) {
                    state.userBalance = result.remaining_balance;
                    const formatted = result.remaining_balance.toFixed(2) + ' EUR';
                    const balanceElement = document.getElementById('user-balance-display');
                    if (balanceElement) {
                        balanceElement.textContent = formatted;
                    }
                    document.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                        detail: { balance: result.remaining_balance, formatted: formatted }
                    }));
                    checkCreditBalance();
                }
                
                showMessage(result.message || 'Réservation créée et payée avec succès !', 'success');
                
                // Rediriger immédiatement vers la page de confirmation (paiement crédit déjà traité)
                const thankYouUrl = result.redirect_url || (result.reservation_id && '{{ route("reservations.thank-you", ":id") }}'.replace(':id', result.reservation_id));
                if (thankYouUrl) {
                    setTimeout(() => { window.location.href = thankYouUrl; }, 500);
                }
            } else {
                showMessage(result.message || (result.errors ? Object.values(result.errors).flat().join(' ') : 'Erreur lors du paiement par crédit'), 'error');
            }

        } catch (error) {
            console.error('Erreur lors du paiement par crédit:', error);
            showMessage('Erreur de connexion lors du paiement', 'error');
        } finally {
            // Remove loading state
            const btn = document.getElementById('reserve-btn');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check mr-2"></i>Confirmer la Réservation';
            }
        }
    }

    // Process Payment and Start Immediately
    function processPayment() {
        if (!state.duration || state.duration <= 0) {
            showMessage('Veuillez sélectionner une durée de recharge', 'error');
            return;
        }

        // Credit payment: use credit balance
        if (state.paymentMethod === 'credit') {
            processCreditPayment();
            return;
        }

        // CMI / Stripe: redirect to payment gateway via form POST
        if (state.paymentMethod === 'cmi' || state.paymentMethod === 'stripe') {
            showMessage('Redirection vers le paiement...', 'success');

            const paymentData = {
                _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                charging_point_id: '{{ $chargingPoint->id }}',
                pricing_plan_id: '{{ $pricingPlan->id }}',
                reservation_type: state.reservationType,
                reservation_value: state.duration,
                start_time: state.startTime || 'immediate',
                payment_method: state.paymentMethod,
                estimated_amount: calculateTotalCost(),
                currency: '{{ $currency ?? "EUR" }}'
            };

            const action = state.paymentMethod === 'cmi'
                ? '{{ route("payment.cmi.initiate") }}'
                : '{{ route("payment.stripe.initiate.public") }}';

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;

            Object.keys(paymentData).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = paymentData[key];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            return;
        }

        // Offline / immediate-start: check wallet balance then start
        checkWalletBalance()
            .then(balanceData => {
                if (!balanceData.sufficient_balance) {
                    showMessage(`Solde insuffisant. Solde actuel: ${balanceData.formatted_balance}, Coût estimé: ${balanceData.formatted_estimated_cost}`, 'error');
                    return;
                }
                startImmediateCharging();
            })
            .catch(error => {
                showMessage('Erreur lors de la vérification du solde: ' + error.message, 'error');
            });
    }

    // Check wallet balance
    async function checkWalletBalance() {
        const response = await fetch(`/api/immediate-start/check-balance/{{ $chargingPoint->id }}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) {
            throw new Error('Failed to check wallet balance');
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to check wallet balance');
        }

        return data.data;
    }

    // Start immediate charging
    async function startImmediateCharging() {
        const formData = new FormData();
        formData.append('amount', calculateEstimatedCost());
        formData.append('connector_id', 1);
        formData.append('id_tag', '{{ auth()->user()->id ?? "guest" }}');
        formData.append('estimated_cost', calculateEstimatedCost());
        formData.append('prepaid_amount', calculateEstimatedCost());
        formData.append('min_threshold', 5.00);

        try {
            showMessage('Démarrage de la recharge en cours...', 'info');
            
            const response = await fetch(`/api/immediate-start/process-payment/{{ $chargingPoint->id }}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                showMessage('Recharge démarrée avec succès!', 'success');
                // Redirect to charging session page
                setTimeout(() => {
                    window.location.href = `/charging-sessions/${data.data.charging.session.id}`;
                }, 2000);
            } else {
                showMessage(data.message || 'Erreur lors du démarrage de la recharge', 'error');
            }
        } catch (error) {
            showMessage('Erreur lors du démarrage de la recharge: ' + error.message, 'error');
        }
    }

    // Calculate estimated cost
    function calculateEstimatedCost() {
        const baseRate = 0.25; // €0.25 per kWh
        const estimatedEnergy = (state.duration / 60) * 7; // 7 kW average power
        return baseRate * estimatedEnergy;
    }

    // Show Message
    function showMessage(text, type = 'success') {
        const messagesDiv = document.getElementById('messages');
        const message = document.createElement('div');
        message.className = `p-4 rounded-lg mb-4 ${
            type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
            type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
            'bg-blue-100 text-blue-800 border border-blue-200'
        }`;
        message.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                <span>${text}</span>
            </div>
        `;
        
        messagesDiv.innerHTML = '';
        messagesDiv.appendChild(message);
        
        setTimeout(() => {
            message.remove();
        }, 5000);
    }

    // Event listeners
    document.getElementById('custom-value').addEventListener('input', handleCustomValue);

    // Duration button event listeners
    document.querySelectorAll('.duration-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const duration = parseInt(this.getAttribute('data-duration'));
            selectDuration(duration);
        });
    });
    // ============================================
    // Module Crédit - Fonctions de gestion
    // ============================================
    
    // Gestion des onglets crédit
    function switchCreditTab(tab) {
        // Masquer tous les contenus
        document.querySelectorAll('.credit-tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Désactiver tous les onglets
        document.querySelectorAll('.credit-tab-button').forEach(button => {
            button.classList.remove('active', 'border-purple-500', 'text-purple-600');
            button.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Afficher le contenu sélectionné
        document.getElementById('credit-tab-content-' + tab).classList.remove('hidden');
        
        // Activer l'onglet sélectionné
        const activeTab = document.getElementById('credit-tab-' + tab);
        activeTab.classList.add('active', 'border-purple-500', 'text-purple-600');
        activeTab.classList.remove('border-transparent', 'text-gray-500');
    }

    // Réinitialiser le formulaire de crédit
    function resetCreditForm() {
        document.getElementById('add-credit-form-offer').reset();
        document.getElementById('credit-form-result').classList.add('hidden');
    }

    // Gestion du formulaire d'ajout de crédit
    @if(isset($creditData) && $creditData)
    document.getElementById('add-credit-form-offer').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        const resultDiv = document.getElementById('credit-form-result');
        const originalButtonText = submitButton.innerHTML;
        
        // Désactiver le bouton
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Traitement...';
        
        try {
            const response = await fetch('{{ route("credits.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                resultDiv.innerHTML = `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span class="text-green-800 font-medium">${data.message}</span>
                        </div>
                        <p class="text-sm text-green-700 mt-2">
                            Nouveau solde: ${parseFloat(data.data.new_balance).toFixed(2)} EUR
                        </p>
                    </div>
                `;
                resultDiv.classList.remove('hidden');
                
                // Réinitialiser le formulaire
                resetCreditForm();
                
                // Recharger la page après 2 secondes pour mettre à jour l'historique
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                resultDiv.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                            <span class="text-red-800 font-medium">${data.message || 'Erreur lors de l\'ajout du crédit'}</span>
                        </div>
                    </div>
                `;
                resultDiv.classList.remove('hidden');
            }
        } catch (error) {
            resultDiv.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                        <span class="text-red-800 font-medium">Erreur: ${error.message}</span>
                    </div>
                </div>
            `;
            resultDiv.classList.remove('hidden');
        } finally {
            // Réactiver le bouton
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    });
    @endif

    // Show Authentication Required
    function showAuthRequired() {
        showMessage('Vous devez être connecté pour utiliser le paiement par solde. Veuillez vous connecter ou créer un compte.', 'warning', 5000);
        
        // Optionnel: Rediriger vers la page de connexion après un délai
        setTimeout(() => {
            if (confirm('Souhaitez-vous être redirigé vers la page de connexion ?')) {
                window.location.href = '/login';
            }
        }, 2000);
    }

    // Style pour les onglets crédit actifs
    const style = document.createElement('style');
    style.textContent = `
        .credit-tab-button.active {
            border-color: #9333ea;
            color: #9333ea;
        }
    `;
    document.head.appendChild(style);
</script>
@endsection
