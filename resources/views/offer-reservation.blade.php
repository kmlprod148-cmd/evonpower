@extends('layouts.public')

@section('title', 'Réservation de la borne')

@php
    // Vérifier si l'utilisateur est un client (pas admin, integrator, operator, partner)
    $isClient = true;
    if (Auth::check()) {
        $user = Auth::user();
        $systemRoles = ['admin', 'integrator', 'operator', 'partner'];
        foreach ($systemRoles as $role) {
            if ($user->hasRole($role)) {
                $isClient = false;
                break;
            }
        }
    } else {
        $isClient = false; // Pas connecté = pas client
    }
@endphp

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
                @if($pricingPlan->rate_type === 'fixed')
                    <div class="flex justify-between">
                        <span>Tarif fixe:</span>
                        <span id="base-amount">{{ number_format($pricingPlan->fixed_price ?? $pricingPlan->base_rate ?? 0, 2) }} {{ $currency ?? 'EUR' }}</span>
                    </div>
                @else
                    <div class="flex justify-between">
                        <span>Base:</span>
                        <span id="base-amount">0.00 {{ $currency ?? 'EUR' }}</span>
                    </div>
                    @if($pricingPlan->base_rate > 0)
                    <div class="flex justify-between">
                        <span>Tarif de base:</span>
                        <span id="base-rate-amount">{{ number_format($pricingPlan->base_rate, 2) }} {{ $currency ?? 'EUR' }}</span>
                    </div>
                    @endif
                @endif
                <div class="flex justify-between">
                    <span>Activation:</span>
                    <span id="activation-amount">{{ number_format($pricingPlan->activation_fee ?? 2.50, 2) }} {{ $currency ?? 'EUR' }}</span>
                </div>
                <div class="flex justify-between border-t border-green-200 pt-1 mt-1">
                    <span>Sous-total (HT):</span>
                    <span id="subtotal-ht">0.00 {{ $currency ?? 'EUR' }}</span>
                </div>
                <div class="flex justify-between">
                    <span>TVA (<span id="vat-rate-display">20</span>%):</span>
                    <span id="vat-amount">0.00 {{ $currency ?? 'EUR' }}</span>
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
                        <h3 class="text-lg font-bold text-gray-900">1. Choisissez votre durée de recharge</h3>
                        <p class="text-sm text-gray-600">Sélectionnez d'abord la durée de votre session de charge</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                @php
                    $maxDuration = $pricingPlan->max_duration ?? 120;
                    $minDuration = $pricingPlan->min_charge_duration ?? 10;
                    $durationOptions = [
                        ['value' => 15, 'label' => 'Rapide', 'color' => 'blue'],
                        ['value' => 30, 'label' => 'Standard', 'color' => 'green'],
                        ['value' => 45, 'label' => 'Complet', 'color' => 'orange'],
                        ['value' => 60, 'label' => '1 heure', 'color' => 'purple'],
                        ['value' => 90, 'label' => 'Longue', 'color' => 'indigo'],
                        ['value' => 120, 'label' => '2 heures', 'color' => 'red']
                    ];
                    $filteredOptions = array_filter($durationOptions, function($option) use ($maxDuration, $minDuration) {
                        return $option['value'] >= $minDuration && $option['value'] <= $maxDuration;
                    });
                @endphp
                
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-6">
                    @foreach($filteredOptions as $option)
                    <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-500 transition-all cursor-pointer relative group" 
                            data-duration="{{ $option['value'] }}" 
                            onmouseenter="showDurationTooltip(event, {{ $option['value'] }})" 
                            onmouseleave="hideDurationTooltip()">
                        <div class="text-xl font-bold text-gray-900">{{ $option['value'] }}</div>
                        <div class="text-xs text-gray-600">min</div>
                        <div class="text-xs text-{{ $option['color'] }}-600 font-medium mt-1">{{ $option['label'] }}</div>
                        <div class="text-xs text-gray-500 mt-1">${({{ $option['value'] }} * state.prices.perMinute + state.prices.activation).toFixed(2)} EUR</div>
                    </button>
                    @endforeach
                </div>

                <div class="mb-6">
                    <label for="custom-value" class="block text-sm font-medium text-gray-700 mb-2">Ou saisissez une valeur personnalisée</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" id="custom-value" placeholder="Ex: 15, 25..." min="{{ $minDuration }}" max="{{ $maxDuration }}" step="10" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-sm text-gray-600 font-medium">min</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Valeurs par intervalles de 10 minutes ({{ $minDuration }}-{{ $maxDuration }} min)
                        @if($maxDuration < 120)
                        <span class="text-orange-600 font-medium">• Limité par le plan tarifaire</span>
                        @endif
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
                        <h3 class="text-lg font-bold text-white">2. Méthode de Paiement</h3>
                        <p class="text-sm text-purple-100">Choisissez votre mode de paiement préféré</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Credit Payment (Replaces "Sur Place") -->
                    @if(auth()->check() && $isClient)
                    <div class="payment-method border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-green-500 transition-all credit-option" 
                         data-method="credit" 
                         onmouseenter="showPaymentTooltip(event, 'credit')" 
                         onmouseleave="hidePaymentTooltip()">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-3">
                                <i class="fas fa-wallet text-white text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-1">Paiement par Solde</h4>
                            <p class="text-sm text-gray-600 mb-2">Crédit: <span id="user-balance-display">{{ number_format(auth()->user()->getOrCreateWallet()->balance ?? 0, 2) }} EUR</span></p>
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
                    <div class="payment-method border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-purple-500 transition-all" data-method="stripe">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-3">
                                <i class="fab fa-stripe text-white text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-1">Stripe</h4>
                            <p class="text-sm text-gray-600 mb-2">International</p>
                            <div class="flex items-center justify-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="fas fa-shield-alt mr-1"></i>
                                    Sécurisé
                                </span>
                            </div>
                            <div class="payment-radio mt-3">
                                <input type="radio" name="payment_method" value="stripe" class="w-4 h-4 text-purple-600">
                            </div>
                        </div>
                    </div>

                    <!-- CMI Payment -->
                    <div class="payment-method border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-500 transition-all" data-method="cmi">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-3">
                                <i class="fas fa-university text-white text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-1">CMI</h4>
                            <p class="text-sm text-gray-600 mb-2">Local</p>
                            <div class="flex items-center justify-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-home mr-1"></i>
                                    Maroc
                                </span>
                            </div>
                            <div class="payment-radio mt-3">
                                <input type="radio" name="payment_method" value="cmi" class="w-4 h-4 text-blue-600">
                            </div>
                        </div>
                    </div>

                    <!-- Sur Place Payment (Offline) -->
                    <div class="payment-method border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-orange-500 transition-all" 
                         data-method="offline" 
                         onmouseenter="showPaymentTooltip(event, 'offline')" 
                         onmouseleave="hidePaymentTooltip()">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center mb-3">
                                <i class="fas fa-cash-register text-white text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-1">Sur Place</h4>
                            <p class="text-sm text-gray-600 mb-2">Espèces/Carte</p>
                            <div class="flex items-center justify-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    Physique
                                </span>
                            </div>
                            <div class="payment-radio mt-3">
                                <input type="radio" name="payment_method" value="offline" class="w-4 h-4 text-orange-600">
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
                        <span class="text-lg font-bold text-green-700" id="available-balance">{{ number_format(auth()->check() ? auth()->user()->getOrCreateWallet()->balance ?? 0 : 0, 2) }} EUR</span>
                    </div>
                    <div class="mt-2 text-xs text-green-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        Le montant sera déduit de votre solde de crédit instantanément
                    </div>
                    <div class="mt-2 flex items-center text-xs text-green-600">
                        <i class="fas fa-shield-alt mr-1"></i>
                        <span>Paiement sécurisé et instantané</span>
                    </div>
                </div>

                <!-- Insufficient Balance Warning -->
                <div id="insufficient-balance" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg hidden">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                        <span class="text-sm font-medium text-red-800">Solde insuffisant</span>
                    </div>
                    <p class="text-xs text-red-600 mt-1">
                        Votre solde de crédit est insuffisant pour cette transaction. 
                        <a href="{{ route('wallet.refill') }}" class="underline hover:no-underline font-medium">Rechargez votre compte</a>
                    </p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-xs text-red-600">Solde actuel: <span id="current-balance">{{ number_format(auth()->check() ? auth()->user()->getOrCreateWallet()->balance ?? 0 : 0, 2) }} EUR</span></span>
                        <span class="text-xs text-red-600">Montant requis: <span id="required-amount">0.00 EUR</span></span>
                    </div>
                </div>

                <!-- Credit Payment Benefits -->
                <div id="credit-benefits" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg hidden">
                    <h4 class="text-sm font-medium text-blue-800 mb-2">Avantages du paiement par crédit</h4>
                    <ul class="text-xs text-blue-700 space-y-1">
                        <li class="flex items-center">
                            <i class="fas fa-bolt text-blue-600 mr-2"></i>
                            Paiement instantané sans frais
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-shield-alt text-blue-600 mr-2"></i>
                            Sécurisé et traçable
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-history text-blue-600 mr-2"></i>
                            Historique complet des transactions
                        </li>
                    </ul>
                </div>

                <!-- Authentication Required Panel -->
                <div id="auth-required-panel" class="auth-required-panel mt-4 hidden">
                    <div class="bg-gradient-to-r from-yellow-100 to-yellow-200 border border-yellow-300 rounded-lg p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-user-shield text-white text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">Identification Requise</h4>
                                <p class="text-sm text-gray-600">Connectez-vous pour utiliser le paiement par solde</p>
                            </div>
                        </div>
                        <div class="flex gap-3 mb-4">
                            <a href="{{ route('login', ['redirect' => urlencode(url()->current()), 'reservation_data' => base64_encode(json_encode([
                                'charging_point_id' => $chargingPoint->id,
                                'pricing_plan_id' => $pricingPlan->id,
                                'payment_method' => 'credit',
                                'timestamp' => time()
                            ]))]) }}" class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-lg font-medium text-center hover:from-blue-600 hover:to-blue-700 transition-all">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Se connecter
                            </a>
                            <a href="{{ route('register', ['redirect' => urlencode(url()->current()), 'reservation_data' => base64_encode(json_encode([
                                'charging_point_id' => $chargingPoint->id,
                                'pricing_plan_id' => $pricingPlan->id,
                                'payment_method' => 'credit',
                                'timestamp' => time()
                            ]))]) }}" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg font-medium text-center hover:from-green-600 hover:to-green-700 transition-all">
                                <i class="fas fa-user-plus mr-2"></i>
                                S'inscrire
                            </a>
                        </div>
                        <div class="bg-white bg-opacity-50 rounded-lg p-4">
                            <h5 class="font-medium text-gray-900 mb-2">Avantages du compte client :</h5>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li class="flex items-center">
                                    <i class="fas fa-wallet text-yellow-600 mr-2"></i>
                                    Paiement par solde instantané
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-history text-yellow-600 mr-2"></i>
                                    Historique des transactions
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-percentage text-yellow-600 mr-2"></i>
                                    Tarifs préférentiels
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-shield-alt text-yellow-600 mr-2"></i>
                                    Paiements sécurisés
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Reservation Restored Panel -->
                <div id="reservation-restored-panel" class="reservation-restored-panel mt-4 hidden">
                    <div class="bg-gradient-to-r from-green-100 to-green-200 border border-green-300 rounded-lg p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">Réservation Restaurée</h4>
                                <p class="text-sm text-gray-600">Votre réservation a été restaurée. Complétez votre paiement par crédit.</p>
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <button onclick="hideReservationRestored()" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-2 rounded-lg font-medium hover:from-green-600 hover:to-green-700 transition-all">
                                <i class="fas fa-times mr-2"></i>
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-4">
            <button class="flex-1 bg-gradient-to-r from-green-600 to-green-700 text-white py-3 px-6 rounded-xl font-semibold hover:from-green-700 hover:to-green-800 transition-all duration-300 disabled:bg-gray-400 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:-translate-y-1" id="reserve-btn" onclick="submitReservation()" disabled>
                <i class="fas fa-check mr-2"></i>
                Confirmer la Réservation
            </button>
            <button class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 px-6 rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 transition-all duration-300 disabled:bg-gray-400 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:-translate-y-1" id="checkout-btn" onclick="processPayment()" disabled>
                <i class="fas fa-credit-card mr-2"></i>
                Payer Maintenant
            </button>
        </div>
        
        <!-- Progress Indicator -->
        <div class="mt-6 bg-white rounded-xl p-4 shadow-lg border border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Progression de la réservation</h4>
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                        <span>Étapes complétées</span>
                        <span id="progress-percentage">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progress-bar" class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <div class="mt-3 flex justify-between text-xs">
                <div class="flex items-center">
                    <div id="step-1" class="w-3 h-3 rounded-full bg-gray-300 mr-2"></div>
                    <span class="text-gray-600">Type</span>
                </div>
                <div class="flex items-center">
                    <div id="step-2" class="w-3 h-3 rounded-full bg-gray-300 mr-2"></div>
                    <span class="text-gray-600">Durée</span>
                </div>
                <div class="flex items-center">
                    <div id="step-3" class="w-3 h-3 rounded-full bg-gray-300 mr-2"></div>
                    <span class="text-gray-600">Paiement</span>
                </div>
                <div class="flex items-center">
                    <div id="step-4" class="w-3 h-3 rounded-full bg-gray-300 mr-2"></div>
                    <span class="text-gray-600">Confirmation</span>
                </div>
            </div>
        </div>
        
        <!-- Prominent Details Button -->
        <div class="mt-6">
            <button class="w-full bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 text-white py-4 px-8 rounded-2xl font-bold text-lg hover:from-purple-700 hover:via-indigo-700 hover:to-blue-700 transition-all duration-300 shadow-2xl hover:shadow-purple-500/25 transform hover:-translate-y-1" onclick="showReservationDetails()">
                <i class="fas fa-eye mr-3 text-xl"></i>
                Voir les Détails de la Réservation
            </button>
        </div>

    </div>
</div>

<script>
    // State Management
    const state = {
        paymentMethod: null,
        reservationType: null,
        duration: null,
        powerOutput: {{ (float)($chargingPoint->power_output ?? 22) }},
        userBalance: {{ auth()->check() ? auth()->user()->getOrCreateWallet()->balance ?? 0 : 0 }},
        prices: {
            perKwh: {{ $pricingPlan->price_per_kwh ?? 0.45 }},
            perMinute: {{ $pricingPlan->price_per_minute ?? 0.25 }},
            activation: {{ $pricingPlan->activation_fee ?? 2.50 }},
            baseRate: {{ $pricingPlan->base_rate ?? 0 }},
            fixedPrice: {{ $pricingPlan->fixed_price ?? 0 }},
            rateType: '{{ $pricingPlan->rate_type ?? 'variable' }}',
            vatRate: {{ ($pricingPlan->vatRate && $pricingPlan->vatRate->rate > 0) ? ($pricingPlan->vatRate->rate / 100) : (isset($pricingPlan->vat_rate) && $pricingPlan->vat_rate > 0 ? ($pricingPlan->vat_rate / 100) : 0) }}
        },
        planLimits: {
            maxDuration: {{ $pricingPlan->max_duration ?? 120 }},
            minDuration: {{ $pricingPlan->min_charge_duration ?? 10 }}
        }
    };

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        updateButtonStates();
        initializePaymentMethods();
        
        // Auto-select reservation type if only one option is available
        @if($showReservationType && $reservationType !== 'mixed')
        selectReservationType('{{ $reservationType }}');
        @else
        // Default selection: Par Durée (minute)
        selectReservationType('minute');
        @endif
        
        // Check if user is authenticated and restore reservation state
        @if(auth()->check())
        restoreReservationState();
        @endif
        
        // Load user preferences
        loadUserPreferences();
        
        // Initialize animations
        initializeAnimations();
    });
    
    // Enhanced Animation System
    function initializeAnimations() {
        // Add entrance animations to cards
        const cards = document.querySelectorAll('.payment-method, .reservation-option, .duration-btn');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        // Add hover animations
        addHoverAnimations();
    }
    
    function addHoverAnimations() {
        // Payment method hover effects
        document.querySelectorAll('.payment-method').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
                this.style.boxShadow = '0 20px 40px rgba(0,0,0,0.1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
                this.style.boxShadow = '';
            });
        });
        
        // Button hover effects
        document.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(-2px)';
                }
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }
    
    // Smooth scroll to element
    function smoothScrollTo(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }
    
    // Animate element entrance
    function animateElementEntrance(element, delay = 0) {
        setTimeout(() => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'all 0.6s ease-out';
            
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, 50);
        }, delay);
    }

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
        autoSavePreferences();
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
        autoSavePreferences();
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

    // Payment Method Functions
    function initializePaymentMethods() {
        // Add click handlers to payment method cards
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                const methodValue = this.dataset.method;
                selectPaymentMethod(methodValue);
            });
        });

        // Load user credit balance only if user is a client
        @if($isClient)
        loadUserCreditBalance();
        @endif
    }

    function selectPaymentMethod(method) {
        // Check if authentication is required for credit payment
        if (method === 'credit') {
            // This should only be called for authenticated users
        state.paymentMethod = method;
        
        // Update UI
        document.querySelectorAll('.payment-method').forEach(card => {
                card.classList.remove('border-blue-500', 'border-green-500', 'border-orange-500', 'border-purple-500', 'border-yellow-500');
            card.classList.add('border-gray-200');
        });
        
        const selectedCard = document.querySelector(`[data-method="${method}"]`);
        if (selectedCard) {
            selectedCard.classList.remove('border-gray-200');
                selectedCard.classList.add('border-green-500');
        }
        
        // Update radio button
        document.querySelector(`input[value="${method}"]`).checked = true;
            
            showMessage('Paiement par solde sélectionné', 'success');
            updateButtonStates();
            updateSummary();
        } else if (method === 'auth-required') {
            // Show authentication panel for non-authenticated users
            showAuthRequired();
        } else {
            state.paymentMethod = method;
            
            // Update UI
            document.querySelectorAll('.payment-method').forEach(card => {
                card.classList.remove('border-blue-500', 'border-green-500', 'border-orange-500', 'border-purple-500', 'border-yellow-500');
                card.classList.add('border-gray-200');
            });
            
            const selectedCard = document.querySelector(`[data-method="${method}"]`);
            if (selectedCard) {
                const colorClass = method === 'stripe' ? 'border-purple-500' : 
                                 method === 'cmi' ? 'border-blue-500' : 
                                 method === 'offline' ? 'border-orange-500' : 'border-gray-500';
                selectedCard.classList.remove('border-gray-200');
                selectedCard.classList.add(colorClass);
            }
            
            // Update radio button
            const radioButton = document.querySelector(`input[value="${method}"]`);
            if (radioButton) {
                radioButton.checked = true;
            }

            // Show success message for all payment methods
            showMessage('Méthode de paiement sélectionnée', 'success');
            
            updateButtonStates();
            updateSummary();
            autoSavePreferences();
        }
        
        // Show/hide credit info based on method
        if (method === 'credit') {
            checkCreditBalance();
        } else {
            hideCreditInfo();
        }
        
        updateButtonStates();
    }

    async function loadUserCreditBalance() {
        // Only load if user is authenticated and is a client
        @if(!auth()->check() || !$isClient)
        console.log('User not authenticated or not a client, skipping credit balance load');
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
                const balanceElements = document.querySelectorAll('#user-balance, #user-balance-display, #available-balance, #current-balance');
                balanceElements.forEach(element => {
                    if (element) element.textContent = data.formatted_balance;
                });
                state.userBalance = data.balance;
            } else {
                document.getElementById('user-balance').textContent = 'Erreur';
                state.userBalance = 0;
            }
        } catch (error) {
            console.error('Error loading credit balance:', error);
            document.getElementById('user-balance').textContent = 'Erreur';
            state.userBalance = 0;
        }
    }

    function checkCreditBalance() {
        if (!state.paymentMethod || state.paymentMethod !== 'credit') {
            hideCreditInfo();
            return;
        }

        const totalCost = calculateTotalCost();
        const userBalance = state.userBalance || 0;
        
        // Mettre à jour les informations de solde
        document.getElementById('available-balance').textContent = userBalance.toFixed(2) + ' EUR';
        document.getElementById('current-balance').textContent = userBalance.toFixed(2) + ' EUR';
        document.getElementById('required-amount').textContent = totalCost.toFixed(2) + ' EUR';
        
        if (userBalance >= totalCost) {
            // Solde suffisant
            document.getElementById('credit-info').classList.remove('hidden');
            document.getElementById('insufficient-balance').classList.add('hidden');
            document.getElementById('credit-benefits').classList.remove('hidden');
            
            // Activer les boutons
            document.getElementById('reserve-btn').disabled = false;
            document.getElementById('checkout-btn').disabled = false;
        } else {
            // Solde insuffisant
            document.getElementById('credit-info').classList.add('hidden');
            document.getElementById('insufficient-balance').classList.remove('hidden');
            document.getElementById('credit-benefits').classList.add('hidden');
            
            // Désactiver les boutons
            document.getElementById('reserve-btn').disabled = true;
            document.getElementById('checkout-btn').disabled = true;
        }
    }

    function hideCreditInfo() {
        document.getElementById('credit-info').classList.add('hidden');
        document.getElementById('insufficient-balance').classList.add('hidden');
        document.getElementById('credit-benefits').classList.add('hidden');
    }

    function calculateTotalCost() {
        if (!state.reservationType || !state.duration) return 0;

        let baseAmount = 0;
        if (state.prices.rateType === 'fixed') {
            baseAmount = state.prices.fixedPrice || state.prices.baseRate || 0;
        } else {
            if (state.reservationType === 'kwh') {
                const estimatedKwh = (state.duration / 60) * state.powerOutput;
                baseAmount = estimatedKwh * state.prices.perKwh;
            } else {
                baseAmount = state.duration * state.prices.perMinute;
            }
            if (state.prices.baseRate > 0) {
                baseAmount += state.prices.baseRate;
            }
        }

        const subtotal = baseAmount + state.prices.activation;
        const vatAmount = subtotal * state.prices.vatRate;
        return subtotal + vatAmount;
    }


    // Cost Calculation
    function calculateCost() {
        if (!state.reservationType || !state.duration) {
            return;
        }

        let baseAmount = 0;
        
        // Si le plan est de type 'fixed', utiliser fixed_price ou base_rate
        if (state.prices.rateType === 'fixed') {
            baseAmount = state.prices.fixedPrice || state.prices.baseRate || 0;
        } else {
            // Plan variable : calculer selon le type de réservation
            if (state.reservationType === 'kwh') {
                // Estimate kWh from duration using the station's actual power output (kW)
                const estimatedKwh = (state.duration / 60) * state.powerOutput;
                baseAmount = estimatedKwh * state.prices.perKwh;
            } else {
                baseAmount = state.duration * state.prices.perMinute;
            }
        }
        
        // Ajouter le base_rate si présent (pour les plans variables)
        if (state.prices.rateType !== 'fixed' && state.prices.baseRate > 0) {
            baseAmount += state.prices.baseRate;
        }

        const subtotal = baseAmount + state.prices.activation;
        const vat = subtotal * state.prices.vatRate;
        const total = subtotal + vat;

        // Update UI
        const baseAmountElement = document.getElementById('base-amount');
        if (baseAmountElement) {
            baseAmountElement.textContent = `${baseAmount.toFixed(2)} {{ $currency ?? 'EUR' }}`;
        }
        const baseRateElement = document.getElementById('base-rate-amount');
        if (baseRateElement) {
            baseRateElement.textContent = `${state.prices.baseRate.toFixed(2)} {{ $currency ?? 'EUR' }}`;
        }
        document.getElementById('activation-amount').textContent = `${state.prices.activation.toFixed(2)} {{ $currency ?? 'EUR' }}`;
        document.getElementById('subtotal-ht').textContent = `${subtotal.toFixed(2)} {{ $currency ?? 'EUR' }}`;
        document.getElementById('vat-rate-display').textContent = (state.prices.vatRate * 100).toFixed(0);
        document.getElementById('vat-amount').textContent = `${vat.toFixed(2)} {{ $currency ?? 'EUR' }}`;
        document.getElementById('total-ttc').textContent = `${total.toFixed(2)} {{ $currency ?? 'EUR' }}`;
        
        // Check credit balance if credit payment is selected
        if (state.paymentMethod === 'credit') {
            checkCreditBalance();
        }
    }

    // Update Button States - Enhanced with Validation
    function updateButtonStates() {
        // Use the new validation system
        updateValidationState();
    }
    
    // Auto-save User Preferences
    function saveUserPreferences() {
        const preferences = {
            paymentMethod: state.paymentMethod,
            reservationType: state.reservationType,
            duration: state.duration,
            timestamp: Date.now()
        };
        
        localStorage.setItem('charging_preferences', JSON.stringify(preferences));
    }
    
    // Load User Preferences
    function loadUserPreferences() {
        try {
            const saved = localStorage.getItem('charging_preferences');
            if (saved) {
                const preferences = JSON.parse(saved);
                
                // Check if preferences are recent (within 24 hours)
                const oneDayAgo = Date.now() - (24 * 60 * 60 * 1000);
                if (preferences.timestamp > oneDayAgo) {
                    // Restore preferences
                    if (preferences.paymentMethod) {
                        state.paymentMethod = preferences.paymentMethod;
                        selectPaymentMethod(preferences.paymentMethod);
                    }
                    
                    if (preferences.reservationType) {
                        state.reservationType = preferences.reservationType;
                        selectReservationType(preferences.reservationType);
                    }
                    
                    if (preferences.duration) {
                        state.duration = preferences.duration;
                        selectDuration(preferences.duration);
                    }
                    
                    showMessage('Préférences restaurées', 'info', 2000);
                }
            }
        } catch (error) {
            console.error('Error loading preferences:', error);
        }
    }
    
    // Auto-save on changes
    function autoSavePreferences() {
        // Debounce the save operation
        clearTimeout(window.preferencesSaveTimeout);
        window.preferencesSaveTimeout = setTimeout(() => {
            saveUserPreferences();
        }, 1000);
    }
    
    // Update Progress Indicator
    function updateProgressIndicator() {
        let completedSteps = 0;
        const totalSteps = 4;
        
        // Step 1: Reservation Type
        if (state.reservationType) {
            completedSteps++;
            document.getElementById('step-1').className = 'w-3 h-3 rounded-full bg-green-500 mr-2';
        } else {
            document.getElementById('step-1').className = 'w-3 h-3 rounded-full bg-gray-300 mr-2';
        }
        
        // Step 2: Duration
        if (state.duration) {
            completedSteps++;
            document.getElementById('step-2').className = 'w-3 h-3 rounded-full bg-green-500 mr-2';
        } else {
            document.getElementById('step-2').className = 'w-3 h-3 rounded-full bg-gray-300 mr-2';
        }
        
        // Step 3: Payment Method
        if (state.paymentMethod) {
            completedSteps++;
            document.getElementById('step-3').className = 'w-3 h-3 rounded-full bg-green-500 mr-2';
        } else {
            document.getElementById('step-3').className = 'w-3 h-3 rounded-full bg-gray-300 mr-2';
        }
        
        // Step 4: Validation (all steps completed)
        const validation = validateReservation();
        if (validation.isValid) {
            completedSteps++;
            document.getElementById('step-4').className = 'w-3 h-3 rounded-full bg-green-500 mr-2';
        } else {
            document.getElementById('step-4').className = 'w-3 h-3 rounded-full bg-gray-300 mr-2';
        }
        
        // Update progress bar
        const percentage = (completedSteps / totalSteps) * 100;
        document.getElementById('progress-bar').style.width = percentage + '%';
        document.getElementById('progress-percentage').textContent = Math.round(percentage) + '%';
        
        // Add completion animation
        if (percentage === 100) {
            document.getElementById('progress-bar').classList.add('animate-pulse');
            setTimeout(() => {
                document.getElementById('progress-bar').classList.remove('animate-pulse');
            }, 2000);
        }
    }

    // Enhanced Credit Payment Processing
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
            const submittedValue = state.reservationType === 'kwh'
                ? Math.round((state.duration / 60) * state.powerOutput * 100) / 100
                : state.duration;
            formData.append('type', state.reservationType === 'kwh' ? 'energy' : 'duration');
            formData.append('value', submittedValue);
            formData.append('time', 'immediate');
            formData.append('customer_name', '{{ auth()->check() ? auth()->user()->name : "Client Web" }}');
            formData.append('customer_email', '{{ auth()->check() ? auth()->user()->email : "" }}');
            formData.append('customer_phone', '');
            formData.append('payment_method', 'credit');

            // Soumettre la réservation avec paiement par crédit
            const response = await fetch('/reservations/store/{{ $chargingPoint->id }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                // Mettre à jour le solde utilisateur si disponible
                if (result.remaining_balance !== undefined) {
                    state.userBalance = result.remaining_balance;
                    const formatted = result.remaining_balance.toFixed(2) + ' EUR';
                    document.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                        detail: { balance: result.remaining_balance, formatted: formatted }
                    }));
                    updateCreditDisplay();
                }
                
                showMessage(result.message || 'Réservation créée et payée avec succès !', 'success');
                
                // Rediriger vers la page de confirmation
                setTimeout(() => {
                    if (result.redirect_url) {
                        window.location.href = result.redirect_url;
                    } else if (result.reservation_id) {
                        window.location.href = '{{ route("reservations.thank-you", ":id") }}'.replace(':id', result.reservation_id);
                    }
                }, 2000);
            } else {
                showMessage(result.message || 'Erreur lors du paiement par crédit', 'error');
                
                // Si l'utilisateur doit se connecter
                if (result.requires_auth) {
                    showAuthRequired();
                }
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

    // Update Credit Display
    function updateCreditDisplay() {
        const balanceElements = document.querySelectorAll('#user-balance, #available-balance, #current-balance');
        balanceElements.forEach(element => {
            element.textContent = state.userBalance.toFixed(2) + ' EUR';
        });
    }

    // Enhanced Payment Processing
    function processPayment() {
        if (state.paymentMethod === 'credit') {
            processCreditPayment();
        } else if (state.paymentMethod === 'offline') {
            processOfflinePayment();
        } else {
            // Handle other payment methods
            showMessage('Traitement du paiement...', 'success');
            // Add logic for other payment methods here
        }
    }

    // Process Offline Payment
    function processOfflinePayment() {
        if (!state.paymentMethod || state.paymentMethod !== 'offline') {
            showMessage('Méthode de paiement offline non sélectionnée', 'error');
            return;
        }

        const totalCost = calculateTotalCost();
        
        try {
            // Show loading state
            const btn = document.getElementById('checkout-btn');
            btn.classList.add('loading');
            btn.disabled = true;

            showMessage('Réservation en cours de traitement...', 'success');
            
            // Simulate processing time
            setTimeout(() => {
                // Redirect to reservation thank you page
                const params = new URLSearchParams({
                    duration: state.duration,
                    total_cost: totalCost,
                    payment_method: 'offline'
                });
                
                window.location.href = '{{ route("charging-points.reservation-thank-you", ["id" => $chargingPoint->id ?? 1]) }}?' + params.toString();
            }, 2000);

        } catch (error) {
            console.error('Erreur lors du traitement de la réservation:', error);
            showMessage('Erreur lors du traitement de la réservation', 'error');
        } finally {
            // Remove loading state
            const btn = document.getElementById('checkout-btn');
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    }

    // Show Reservation Details Modal - Enhanced Design
    function showReservationDetails() {
        const totalCost = calculateTotalCost();
        const startTime = state.startTime || 'Maintenant';
        const duration = state.duration || 'Non sélectionné';
        const reservationType = state.reservationType || 'Non sélectionné';
        const paymentMethod = state.paymentMethod || 'Non sélectionné';
        
        // Create enhanced modal content with premium design
        const modalContent = `
            <div class="fixed inset-0 bg-gradient-to-br from-black/60 via-black/40 to-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" id="reservation-details-modal">
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-hidden border border-white/20">
                    <!-- Header with gradient -->
                    <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 p-6 text-white relative overflow-hidden">
                        <div class="absolute inset-0 bg-black/10"></div>
                        <div class="relative z-10">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-2xl font-bold">Détails de la Réservation</h3>
                                <button onclick="closeReservationDetails()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-full p-2 transition-all duration-200">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            <p class="text-blue-100 text-sm">Vérifiez tous les détails avant de confirmer</p>
                        </div>
                        <!-- Decorative elements -->
                        <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-2 -left-2 w-16 h-16 bg-white/5 rounded-full"></div>
                    </div>
                    
                    <!-- Content with enhanced styling -->
                    <div class="p-6 space-y-6 max-h-[60vh] overflow-y-auto">
                        <!-- Charging Point Info Card -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-charging-station text-white text-lg"></i>
                                </div>
                                <h4 class="font-bold text-gray-800 text-lg">Borne de Recharge</h4>
                            </div>
                            <div class="space-y-2">
                                <p class="text-gray-700 font-medium">📍 {{ $chargingPoint->name }}</p>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                                    <span>{{ $chargingPoint->power_level }}kW - {{ $chargingPoint->voltage }}V</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Billing Type Card -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-calculator text-white text-lg"></i>
                                </div>
                                <h4 class="font-bold text-gray-800 text-lg">Type de Facturation</h4>
                            </div>
                            <div class="flex items-center">
                                ${reservationType === 'kwh' ? `
                                    <div class="flex items-center text-blue-700">
                                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                                        <span class="font-medium">Par Énergie (kWh)</span>
                                    </div>
                                ` : reservationType === 'minute' ? `
                                    <div class="flex items-center text-blue-700">
                                        <i class="fas fa-clock text-blue-500 mr-2"></i>
                                        <span class="font-medium">Par Durée (minutes)</span>
                                    </div>
                                ` : `
                                    <div class="flex items-center text-red-500">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        <span class="font-medium">Non sélectionné</span>
                                    </div>
                                `}
                            </div>
                        </div>
                        
                        <!-- Duration Card -->
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-xl p-4">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-hourglass-half text-white text-lg"></i>
                                </div>
                                <h4 class="font-bold text-gray-800 text-lg">Durée de Recharge</h4>
                            </div>
                            ${paymentMethod === 'offline' ? `
                                <div class="space-y-3">
                                    <p class="text-sm text-gray-600 mb-3">Choisissez la durée de votre recharge :</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button onclick="selectDurationInModal(15)" class="duration-btn-modal p-3 border-2 border-gray-200 rounded-lg text-center hover:border-purple-500 hover:bg-purple-50 transition-all ${state.duration === 15 ? 'border-purple-500 bg-purple-50' : ''}">
                                            <div class="font-semibold text-gray-800">15 min</div>
                                            <div class="text-xs text-gray-500">${(15 * state.prices.perMinute + state.prices.activation).toFixed(2)} EUR</div>
                                        </button>
                                        <button onclick="selectDurationInModal(30)" class="duration-btn-modal p-3 border-2 border-gray-200 rounded-lg text-center hover:border-purple-500 hover:bg-purple-50 transition-all ${state.duration === 30 ? 'border-purple-500 bg-purple-50' : ''}">
                                            <div class="font-semibold text-gray-800">30 min</div>
                                            <div class="text-xs text-gray-500">${(30 * state.prices.perMinute + state.prices.activation).toFixed(2)} EUR</div>
                                        </button>
                                        <button onclick="selectDurationInModal(60)" class="duration-btn-modal p-3 border-2 border-gray-200 rounded-lg text-center hover:border-purple-500 hover:bg-purple-50 transition-all ${state.duration === 60 ? 'border-purple-500 bg-purple-50' : ''}">
                                            <div class="font-semibold text-gray-800">1 heure</div>
                                            <div class="text-xs text-gray-500">${(60 * state.prices.perMinute + state.prices.activation).toFixed(2)} EUR</div>
                                        </button>
                                        <button onclick="selectDurationInModal(120)" class="duration-btn-modal p-3 border-2 border-gray-200 rounded-lg text-center hover:border-purple-500 hover:bg-purple-50 transition-all ${state.duration === 120 ? 'border-purple-500 bg-purple-50' : ''}">
                                            <div class="font-semibold text-gray-800">2 heures</div>
                                            <div class="text-xs text-gray-500">${(120 * state.prices.perMinute + state.prices.activation).toFixed(2)} EUR</div>
                                        </button>
                                    </div>
                                    <div class="mt-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Durée personnalisée (10-480 min) :</label>
                                        <div class="flex gap-2">
                                            <input type="number" id="custom-duration-modal" min="10" max="480" step="10" 
                                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                                                   placeholder="Ex: 45" value="${state.duration && ![15, 30, 60, 120].includes(state.duration) ? state.duration : ''}">
                                            <button onclick="selectCustomDurationInModal()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ` : `
                                <div class="flex items-center">
                                    ${duration !== 'Non sélectionné' ? `
                                        <div class="flex items-center text-purple-700">
                                            <i class="fas fa-clock text-purple-500 mr-2"></i>
                                            <span class="font-medium text-lg">${duration} minutes</span>
                                        </div>
                                    ` : `
                                        <div class="flex items-center text-red-500">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            <span class="font-medium">Non sélectionné</span>
                                        </div>
                                    `}
                                </div>
                            `}
                        </div>
                        
                        <!-- Start Time Card -->
                        <div class="bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200 rounded-xl p-4">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-play text-white text-lg"></i>
                                </div>
                                <h4 class="font-bold text-gray-800 text-lg">Heure de Début</h4>
                            </div>
                            <div class="flex items-center text-orange-700">
                                <i class="fas fa-clock text-orange-500 mr-2"></i>
                                <span class="font-medium">${startTime}</span>
                            </div>
                        </div>
                        
                        <!-- Payment Method Card -->
                        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-xl p-4">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-credit-card text-white text-lg"></i>
                                </div>
                                <h4 class="font-bold text-gray-800 text-lg">Mode de Paiement</h4>
                            </div>
                            <div class="flex items-center">
                                ${paymentMethod === 'credit' ? `
                                    <div class="flex items-center text-green-700">
                                        <i class="fas fa-wallet text-green-500 mr-2"></i>
                                        <span class="font-medium">Paiement par Solde</span>
                                    </div>
                                ` : paymentMethod === 'stripe' ? `
                                    <div class="flex items-center text-purple-700">
                                        <i class="fab fa-stripe text-purple-500 mr-2"></i>
                                        <span class="font-medium">Stripe</span>
                                    </div>
                                ` : paymentMethod === 'cmi' ? `
                                    <div class="flex items-center text-blue-700">
                                        <i class="fas fa-university text-blue-500 mr-2"></i>
                                        <span class="font-medium">CMI</span>
                                    </div>
                                ` : paymentMethod === 'offline' ? `
                                    <div class="flex items-center text-orange-700">
                                        <i class="fas fa-cash-register text-orange-500 mr-2"></i>
                                        <span class="font-medium">Sur Place</span>
                                    </div>
                                ` : `
                                    <div class="flex items-center text-red-500">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        <span class="font-medium">Non sélectionné</span>
                                    </div>
                                `}
                            </div>
                        </div>
                        
                        <!-- Cost Summary Card -->
                        <div class="bg-gradient-to-r from-emerald-50 to-green-50 border-2 border-emerald-200 rounded-xl p-5 relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/5 to-green-500/5"></div>
                            <div class="relative z-10">
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 bg-gradient-to-r from-emerald-500 to-green-500 rounded-xl flex items-center justify-center mr-4">
                                        <i class="fas fa-euro-sign text-white text-xl"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-800 text-xl">Coût Total</h4>
                                </div>
                                <div class="text-center">
                                    <p class="text-4xl font-bold text-emerald-600 mb-2">${totalCost.toFixed(2)} EUR</p>
                                    ${state.paymentMethod === 'credit' ? `
                                        <div class="bg-white/80 rounded-lg p-3 mt-3">
                                            <p class="text-sm text-gray-600 mb-1">Solde disponible</p>
                                            <p class="text-lg font-semibold text-gray-800">${state.userBalance.toFixed(2)} EUR</p>
                                            <div class="flex items-center justify-center mt-2">
                                                ${state.userBalance >= totalCost ? `
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle mr-1"></i>
                                                        Solde suffisant
                                                    </span>
                                                ` : `
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                                        Solde insuffisant
                                                    </span>
                                                `}
                                            </div>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        
                        ${!state.reservationType || !state.duration ? `
                            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-xl p-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-exclamation-triangle text-white"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-yellow-800">Attention</p>
                                        <p class="text-sm text-yellow-700">Veuillez compléter tous les détails avant de procéder au paiement.</p>
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    
                    <!-- Enhanced Footer -->
                    <div class="bg-gray-50/80 backdrop-blur-sm p-6 border-t border-gray-200">
                        <div class="flex gap-3">
                            <button onclick="closeReservationDetails()" class="flex-1 bg-white border-2 border-gray-300 text-gray-700 py-3 px-6 rounded-xl font-semibold hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 shadow-sm">
                                <i class="fas fa-times mr-2"></i>
                                Fermer
                            </button>
                            ${state.reservationType && state.duration ? `
                                <button onclick="closeReservationDetails(); processPayment();" class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                    <i class="fas fa-credit-card mr-2"></i>
                                    Procéder au Paiement
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('reservation-details-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalContent);
    }
    
    // Close Reservation Details Modal
    function closeReservationDetails() {
        const modal = document.getElementById('reservation-details-modal');
        if (modal) {
            modal.remove();
        }
    }

    // Functions for duration selection in modal
    function selectDurationInModal(duration) {
        state.duration = duration;
        
        // Update button states in modal
        document.querySelectorAll('.duration-btn-modal').forEach(btn => {
            btn.classList.remove('border-purple-500', 'bg-purple-50');
            btn.classList.add('border-gray-200');
        });
        
        // Highlight selected button
        event.target.closest('.duration-btn-modal').classList.remove('border-gray-200');
        event.target.closest('.duration-btn-modal').classList.add('border-purple-500', 'bg-purple-50');
        
        // Clear custom input
        document.getElementById('custom-duration-modal').value = '';
        
        // Update cost display
        updateCostInModal();
        
        showMessage(`${duration} minutes sélectionnées`, 'success');
    }

    function selectCustomDurationInModal() {
        const input = document.getElementById('custom-duration-modal');
        const value = parseInt(input.value);
        
        if (value && value >= state.planLimits.minDuration && value <= state.planLimits.maxDuration && value % 10 === 0) {
            state.duration = value;
            
            // Clear preset buttons
            document.querySelectorAll('.duration-btn-modal').forEach(btn => {
                btn.classList.remove('border-purple-500', 'bg-purple-50');
                btn.classList.add('border-gray-200');
            });
            
            // Update cost display
            updateCostInModal();
            
            showMessage(`${value} minutes sélectionnées`, 'success');
        } else {
            showMessage(`Veuillez entrer une durée valide (${state.planLimits.minDuration}-${state.planLimits.maxDuration} minutes, multiple de 10)`, 'error');
        }
    }

    function updateCostInModal() {
        // This function will be called to update the cost display in the modal
        // The modal will be refreshed with new cost information
        setTimeout(() => {
            showReservationDetails();
        }, 100);
    }

    // Floating Info Tooltips for Duration Selection
    function showDurationTooltip(event, duration) {
        // Remove existing tooltip
        hideDurationTooltip();
        
        const _kwh = (duration / 60) * state.powerOutput;
        const _base = state.prices.rateType === 'fixed'
            ? (state.prices.fixedPrice || state.prices.baseRate || 0)
            : ((state.reservationType === 'kwh' ? _kwh * state.prices.perKwh : duration * state.prices.perMinute) + state.prices.baseRate);
        const _subtotal = _base + state.prices.activation;
        const totalCost = _subtotal * (1 + state.prices.vatRate);
        const energyEstimate = _kwh.toFixed(1);
        
        const tooltip = document.createElement('div');
        tooltip.id = 'duration-tooltip';
        tooltip.className = 'fixed z-50 bg-white border border-gray-200 rounded-xl shadow-2xl p-4 max-w-sm pointer-events-none';
        tooltip.style.left = (event.pageX + 10) + 'px';
        tooltip.style.top = (event.pageY - 10) + 'px';
        
        tooltip.innerHTML = `
            <div class="space-y-3">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                    <h4 class="font-bold text-gray-800 text-sm">Détails de la Réservation</h4>
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                </div>
                
                <!-- Duration Info -->
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-clock text-blue-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">${duration} minutes</p>
                        <p class="text-xs text-gray-600">Durée de recharge</p>
                    </div>
                </div>
                
                <!-- Energy Estimate -->
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-bolt text-yellow-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">~${energyEstimate} kWh</p>
                        <p class="text-xs text-gray-600">Énergie estimée</p>
                    </div>
                </div>
                
                <!-- Charging Point -->
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-charging-station text-green-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">{{ $chargingPoint->name }}</p>
                        <p class="text-xs text-gray-600">{{ $chargingPoint->power_level ?? 22 }}kW - {{ $chargingPoint->voltage ?? 400 }}V</p>
                    </div>
                </div>
                
                <!-- Cost Breakdown -->
                <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Durée (${duration} min)</span>
                        <span class="font-medium">${(duration * state.prices.perMinute).toFixed(2)} EUR</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Frais d'activation</span>
                        <span class="font-medium">${state.prices.activation.toFixed(2)} EUR</span>
                    </div>
                    <div class="border-t border-gray-200 pt-2 flex justify-between">
                        <span class="font-bold text-gray-800">Total</span>
                        <span class="font-bold text-green-600 text-lg">${totalCost.toFixed(2)} EUR</span>
                    </div>
                </div>
                
                <!-- Payment Info -->
                <div class="bg-blue-50 rounded-lg p-2">
                    <p class="text-xs text-blue-700 text-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Sélectionnez ensuite votre mode de paiement
                    </p>
                </div>
            </div>
        `;
        
        document.body.appendChild(tooltip);
        
        // Position tooltip to stay within viewport
        const rect = tooltip.getBoundingClientRect();
        if (rect.right > window.innerWidth) {
            tooltip.style.left = (event.pageX - rect.width - 10) + 'px';
        }
        if (rect.bottom > window.innerHeight) {
            tooltip.style.top = (event.pageY - rect.height - 10) + 'px';
        }
    }

    function hideDurationTooltip() {
        const tooltip = document.getElementById('duration-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    // Floating Info Tooltips for Payment Methods
    function showPaymentTooltip(event, paymentMethod) {
        // Remove existing tooltip
        hidePaymentTooltip();
        
        const tooltip = document.createElement('div');
        tooltip.id = 'payment-tooltip';
        tooltip.className = 'fixed z-50 bg-white border border-gray-200 rounded-xl shadow-2xl p-4 max-w-sm pointer-events-none';
        tooltip.style.left = (event.pageX + 10) + 'px';
        tooltip.style.top = (event.pageY - 10) + 'px';
        
        let tooltipContent = '';
        
        if (paymentMethod === 'credit') {
            const currentBalance = state.userBalance || 0;
            const estimatedCost = calculateTotalCost();
            const canAfford = currentBalance >= estimatedCost;
            
            tooltipContent = `
                <div class="space-y-3">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                        <h4 class="font-bold text-gray-800 text-sm">Paiement par Solde</h4>
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    </div>
                    
                    <!-- Current Balance -->
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-wallet text-green-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">${currentBalance.toFixed(2)} EUR</p>
                            <p class="text-xs text-gray-600">Solde actuel</p>
                        </div>
                    </div>
                    
                    ${state.duration ? `
                        <!-- Estimated Cost -->
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-calculator text-blue-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">${estimatedCost.toFixed(2)} EUR</p>
                                <p class="text-xs text-gray-600">Coût estimé</p>
                            </div>
                        </div>
                        
                        <!-- Affordability Check -->
                        <div class="bg-${canAfford ? 'green' : 'red'}-50 rounded-lg p-3">
                            <div class="flex items-center">
                                <i class="fas fa-${canAfford ? 'check-circle' : 'exclamation-circle'} text-${canAfford ? 'green' : 'red'}-600 mr-2"></i>
                                <p class="text-sm font-medium text-${canAfford ? 'green' : 'red'}-800">
                                    ${canAfford ? 'Solde suffisant' : 'Solde insuffisant'}
                                </p>
                            </div>
                            ${!canAfford ? `
                                <p class="text-xs text-red-600 mt-1">
                                    Manque: ${(estimatedCost - currentBalance).toFixed(2)} EUR
                                </p>
                            ` : ''}
                        </div>
                    ` : `
                        <div class="bg-yellow-50 rounded-lg p-3">
                            <p class="text-sm text-yellow-800 text-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                Sélectionnez d'abord une durée pour voir le coût
                            </p>
                        </div>
                    `}
                    
                    <!-- Benefits -->
                    <div class="bg-green-50 rounded-lg p-2">
                        <p class="text-xs text-green-700 text-center">
                            <i class="fas fa-bolt mr-1"></i>
                            Paiement instantané et sécurisé
                        </p>
                    </div>
                </div>
            `;
        } else if (paymentMethod === 'offline') {
            const estimatedCost = calculateTotalCost();
            
            tooltipContent = `
                <div class="space-y-3">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                        <h4 class="font-bold text-gray-800 text-sm">Paiement Sur Place</h4>
                        <div class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></div>
                    </div>
                    
                    <!-- Payment Info -->
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-cash-register text-orange-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Espèces / Carte</p>
                            <p class="text-xs text-gray-600">Paiement physique</p>
                        </div>
                    </div>
                    
                    ${state.duration ? `
                        <!-- Estimated Cost -->
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-calculator text-blue-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">${estimatedCost.toFixed(2)} EUR</p>
                                <p class="text-xs text-gray-600">Coût estimé</p>
                            </div>
                        </div>
                    ` : `
                        <div class="bg-yellow-50 rounded-lg p-3">
                            <p class="text-sm text-yellow-800 text-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                Sélectionnez d'abord une durée pour voir le coût
                            </p>
                        </div>
                    `}
                    
                    <!-- Process Info -->
                    <div class="bg-orange-50 rounded-lg p-3">
                        <h5 class="font-semibold text-orange-800 text-sm mb-2">Processus :</h5>
                        <div class="space-y-1 text-xs text-orange-700">
                            <div class="flex items-center">
                                <span class="w-4 h-4 bg-orange-200 rounded-full flex items-center justify-center mr-2 text-xs">1</span>
                                Sélectionnez votre durée
                            </div>
                            <div class="flex items-center">
                                <span class="w-4 h-4 bg-orange-200 rounded-full flex items-center justify-center mr-2 text-xs">2</span>
                                Confirmez la réservation
                            </div>
                            <div class="flex items-center">
                                <span class="w-4 h-4 bg-orange-200 rounded-full flex items-center justify-center mr-2 text-xs">3</span>
                                Payez sur place
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        tooltip.innerHTML = tooltipContent;
        document.body.appendChild(tooltip);
        
        // Position tooltip to stay within viewport
        const rect = tooltip.getBoundingClientRect();
        if (rect.right > window.innerWidth) {
            tooltip.style.left = (event.pageX - rect.width - 10) + 'px';
        }
        if (rect.bottom > window.innerHeight) {
            tooltip.style.top = (event.pageY - rect.height - 10) + 'px';
        }
    }

    function hidePaymentTooltip() {
        const tooltip = document.getElementById('payment-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    // Submit Reservation
    function submitReservation() {
        // Show reservation details first
        showReservationDetails();
        
        // If details are incomplete, don't proceed to payment
        if (!state.reservationType || !state.duration) {
            showMessage('Veuillez compléter tous les détails de réservation avant de procéder au paiement', 'warning');
            return;
        }
        
        // If payment method is credit, proceed directly
        if (state.paymentMethod === 'credit') {
            processCreditPayment();
            return;
        }
        
        showMessage('Réservation en cours...', 'success');
        
        // Prepare form data
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        const submittedValue = state.reservationType === 'kwh'
            ? Math.round((state.duration / 60) * state.powerOutput * 100) / 100
            : state.duration;
        formData.append('type', state.reservationType === 'kwh' ? 'energy' : 'duration');
        formData.append('value', submittedValue);
        formData.append('time', 'immediate');
        formData.append('customer_name', 'Client Web');
        formData.append('customer_email', '');
        formData.append('customer_phone', '');
        formData.append('payment_method', state.paymentMethod || 'offline');
        
        // Submit reservation
        fetch('/reservations/store/{{ $chargingPoint->id }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
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
                showMessage(data.message || 'Erreur lors de la réservation', 'error');
            }
        })
        .catch(error => {
            showMessage('Erreur lors de la réservation: ' + error.message, 'error');
        });
    }

    // Enhanced Validation System
    function validateReservation() {
        const errors = [];
        const warnings = [];
        
        // Validate reservation type
        if (!state.reservationType) {
            errors.push('Veuillez sélectionner un type de facturation');
        }
        
        // Validate duration
        if (!state.duration) {
            errors.push('Veuillez sélectionner une durée de recharge');
        }
        
        // Validate payment method
        if (!state.paymentMethod) {
            errors.push('Veuillez sélectionner un mode de paiement');
        }
        
        // Validate credit balance for credit payments
        if (state.paymentMethod === 'credit') {
            const totalCost = calculateTotalCost();
            if (state.userBalance < totalCost) {
                errors.push(`Solde insuffisant. Coût: ${totalCost.toFixed(2)} EUR, Solde: ${state.userBalance.toFixed(2)} EUR`);
            }
        }
        
        // Add warnings for incomplete information
        if (state.reservationType && !state.duration) {
            warnings.push('Durée de recharge non sélectionnée');
        }
        
        if (state.duration && !state.reservationType) {
            warnings.push('Type de facturation non sélectionné');
        }
        
        return { errors, warnings, isValid: errors.length === 0 };
    }
    
    // Enhanced Form Validation with Visual Feedback
    function updateValidationState() {
        const validation = validateReservation();
        
        // Update button states
        const reserveBtn = document.getElementById('reserve-btn');
        const checkoutBtn = document.getElementById('checkout-btn');
        
        if (validation.isValid) {
            reserveBtn.disabled = false;
            checkoutBtn.disabled = false;
            reserveBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Confirmer la Réservation';
            checkoutBtn.innerHTML = '<i class="fas fa-credit-card mr-2"></i>Payer Maintenant';
        } else {
            reserveBtn.disabled = true;
            checkoutBtn.disabled = true;
            
            if (validation.errors.length > 0) {
                const firstError = validation.errors[0];
                reserveBtn.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i>${firstError}`;
                checkoutBtn.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i>${firstError}`;
            }
        }
        
        // Show warnings if any
        if (validation.warnings.length > 0) {
            validation.warnings.forEach(warning => {
                showMessage(warning, 'warning', 3000);
            });
        }
        
        // Update progress indicator
        updateProgressIndicator();
        
        return validation;
    }

    // Show Message
    // Enhanced Toast Notification System
    function showMessage(message, type = 'info', duration = 4000) {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.toast-notification');
        existingToasts.forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        toast.className = 'toast-notification fixed top-4 right-4 z-50 max-w-sm transform transition-all duration-500 translate-x-full';
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        const colors = {
            success: 'bg-gradient-to-r from-green-500 to-emerald-500',
            error: 'bg-gradient-to-r from-red-500 to-rose-500',
            warning: 'bg-gradient-to-r from-yellow-500 to-orange-500',
            info: 'bg-gradient-to-r from-blue-500 to-indigo-500'
        };
        
        toast.innerHTML = `
            <div class="${colors[type] || colors.info} text-white p-4 rounded-xl shadow-2xl border border-white/20 backdrop-blur-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="${icons[type] || icons.info} text-xl"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <button onclick="this.closest('.toast-notification').remove()" class="text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="mt-2 w-full bg-white/20 rounded-full h-1">
                    <div class="bg-white h-1 rounded-full transition-all duration-${duration}" style="width: 100%; animation: shrink ${duration}ms linear forwards;"></div>
                </div>
            </div>
        `;
        
        // Add CSS animation
        if (!document.getElementById('toast-animations')) {
            const style = document.createElement('style');
            style.id = 'toast-animations';
            style.textContent = `
                @keyframes shrink {
                    from { width: 100%; }
                    to { width: 0%; }
                }
                .toast-notification {
                    animation: slideIn 0.5s ease-out forwards;
                }
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(toast);
        
        // Auto remove
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // Show Authentication Required Panel
    function showAuthRequired() {
        // Save current reservation state before showing auth panel
        saveReservationState();
        
        const authPanel = document.getElementById('auth-required-panel');
        if (authPanel) {
            authPanel.classList.remove('hidden');
            
            // Scroll to the panel
            authPanel.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            
            showMessage('Identification requise pour le paiement par solde', 'warning');
        }
    }

    // Hide Authentication Required Panel
    function hideAuthRequired() {
        const authPanel = document.getElementById('auth-required-panel');
        if (authPanel) {
            authPanel.classList.add('hidden');
        }
    }

    // Show Reservation Restored Panel
    function showReservationRestored() {
        const restoredPanel = document.getElementById('reservation-restored-panel');
        if (restoredPanel) {
            restoredPanel.classList.remove('hidden');
            
            // Scroll to the panel
            restoredPanel.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                hideReservationRestored();
        }, 5000);
        }
    }

    // Hide Reservation Restored Panel
    function hideReservationRestored() {
        const restoredPanel = document.getElementById('reservation-restored-panel');
        if (restoredPanel) {
            restoredPanel.classList.add('hidden');
        }
    }

    // Save current reservation state to localStorage
    function saveReservationState() {
        const reservationState = {
            charging_point_id: {{ $chargingPoint->id }},
            pricing_plan_id: {{ $pricingPlan->id }},
            payment_method: 'credit',
            reservation_type: state.reservationType,
            duration: state.duration,
            start_time: state.startTime,
            prices: state.prices,
            timestamp: Date.now(),
            url: window.location.href
        };
        
        localStorage.setItem('pending_reservation', JSON.stringify(reservationState));
        console.log('Reservation state saved:', reservationState);
    }

    // Restore reservation state from localStorage
    function restoreReservationState() {
        const savedState = localStorage.getItem('pending_reservation');
        if (savedState) {
            try {
                const reservationData = JSON.parse(savedState);
                
                // Check if the data is recent (within 1 hour)
                const oneHourAgo = Date.now() - (60 * 60 * 1000);
                if (reservationData.timestamp > oneHourAgo) {
                    // Restore the state
                    state.reservationType = reservationData.reservation_type;
                    state.duration = reservationData.duration;
                    state.startTime = reservationData.start_time;
                    state.paymentMethod = reservationData.payment_method;
                    
                    // Update UI
                    if (reservationData.reservation_type) {
                        selectReservationType(reservationData.reservation_type);
                    }
                    if (reservationData.duration) {
                        selectDuration(reservationData.duration);
                    }
                    if (reservationData.payment_method === 'credit') {
                        selectPaymentMethod('credit');
                    }
                    
                    showMessage('Réservation restaurée - Complétez votre paiement par crédit', 'success');
                    showReservationRestored();
                    
                    // Clear the saved state
                    localStorage.removeItem('pending_reservation');
                } else {
                    // Clear expired data
                    localStorage.removeItem('pending_reservation');
                }
            } catch (error) {
                console.error('Error restoring reservation state:', error);
                localStorage.removeItem('pending_reservation');
            }
        }
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
</script>
@endsection
