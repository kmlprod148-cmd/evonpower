@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto p-4">
    <!-- En-tête -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white p-4 rounded-t-lg">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold">{{ $chargingPoint->name }}</h1>
                <p class="flex items-center text-sm mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ $chargingPoint->location ?? $chargingPoint->city }}</span>
                </p>
            </div>
            <div class="bg-white p-2 rounded">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Carte principale -->
    <div class="bg-white rounded-b-lg shadow-sm mb-4">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-lg font-medium text-gray-900">Confirmation de recharge</h2>
            <p class="text-sm text-gray-500 mt-1">Vérifiez les détails avant de démarrer la recharge</p>
        </div>

        <!-- Détails de la borne -->
        <div class="p-4 border-b border-gray-100">
            <h3 class="font-medium text-gray-900 mb-3">Détails de la borne</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Borne</p>
                    <p class="font-medium">{{ $chargingPoint->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Emplacement</p>
                    <p class="font-medium">{{ $chargingPoint->city }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Connecteur</p>
                    <p class="font-medium">{{ $connector->type }} #{{ $connector->connector_id }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Puissance</p>
                    <p class="font-medium">{{ $connector->power }} kW</p>
                </div>
            </div>
        </div>

        <!-- Tarification -->
        <div class="p-4 border-b border-gray-100 bg-gray-50">
            <h3 class="font-medium text-gray-900 mb-3">Tarification</h3>
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">Plan tarifaire</span>
                <span class="font-medium">{{ $pricingPlan->name }}</span>
            </div>

            @if($pricingPlan->price_per_kwh > 0)
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">Prix par kWh</span>
                <span class="font-medium">{{ number_format($pricingPlan->price_per_kwh, 2) }} EUR</span>
            </div>
            @endif

            @if($pricingPlan->price_per_minute > 0)
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">Prix par minute</span>
                <span class="font-medium">{{ number_format($pricingPlan->price_per_minute, 2) }} EUR</span>
            </div>
            @endif

            @if($pricingPlan->activation_fee > 0)
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">Frais d'activation</span>
                <span class="font-medium">{{ number_format($pricingPlan->activation_fee, 2) }} EUR</span>
            </div>
            @endif

            <div class="flex justify-between items-center pt-2 mt-2 border-t border-gray-200">
                <span class="font-semibold">Total estimé pour 1h</span>
                <span class="font-semibold text-green-600">
                    @php
                        $subtotalHT = $pricingPlan->activation_fee ?? 0;

                        if ($pricingPlan->price_per_minute > 0) {
                            $subtotalHT += $pricingPlan->price_per_minute * 60; // 60 minutes
                        }

                        if ($pricingPlan->price_per_kwh > 0) {
                            // Estimation de la consommation basée sur la puissance du connecteur
                            $estimatedKwh = $connector->power; // En supposant 1h à pleine puissance
                            $subtotalHT += $pricingPlan->price_per_kwh * $estimatedKwh;
                        }

                        // Appliquer la TVA si configurée
                        $vatRate = optional($pricingPlan->vatRate)->rate ?? 0;
                        $estimatedCost = $vatRate > 0 
                            ? $subtotalHT * (1 + ($vatRate / 100))
                            : $subtotalHT;
                    @endphp
                    {{ number_format($estimatedCost, 2) }} EUR
                </span>
            </div>
        </div>

        <!-- Formulaire de confirmation -->
        <div class="p-4">
            <form action="{{ route('charging-points.offer.start', $chargingPoint->id) }}" method="POST">
                @csrf
                <input type="hidden" name="connector_id" value="{{ $connector->connector_id }}">
                <input type="hidden" name="pricing_plan_id" value="{{ $pricingPlan->id }}">

                <div class="mb-6">
                    <div class="flex items-center">
                        <input id="terms_agreement" name="terms_agreement" type="checkbox" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded" required>
                        <label for="terms_agreement" class="ml-2 block text-sm text-gray-700">
                            J'accepte les <a href="#" class="text-green-600 hover:text-green-800">conditions générales</a> et la <a href="#" class="text-green-600 hover:text-green-800">politique de tarification</a>
                        </label>
                    </div>
                    @error('terms_agreement')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <a href="{{ route('charging-points.offer.show', $chargingPoint->id) }}" class="text-gray-600 hover:text-gray-800">
                        Retour
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Démarrer la recharge
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Instructions -->
    <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
        <h3 class="font-medium text-blue-700 mb-2">Instructions de charge</h3>
        <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800">
            <li>Après confirmation, rendez-vous à la borne de recharge.</li>
            <li>Connectez votre câble à la borne (connecteur #{{ $connector->connector_id }}).</li>
            <li>Connectez l'autre extrémité du câble à votre véhicule.</li>
            <li>La charge démarrera automatiquement et sera tarifée selon le plan sélectionné.</li>
            <li>Pour arrêter la recharge, retournez à la borne et déconnectez votre câble.</li>
        </ol>
    </div>

    <div class="mt-6 text-center text-sm text-gray-500">
        En cas de problème, veuillez contacter notre service client au
        <a href="tel:+212123456789" class="text-green-600 hover:text-green-800">+212 12 34 56 789</a>
    </div>
</div>

<!-- Custom Alert Overlay -->
<div id="customAlertOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
        <div id="customAlertIcon" class="flex justify-center mb-4">
            <i id="customAlertIconClass" class="text-3xl"></i>
        </div>
        <h3 id="customAlertTitle" class="text-lg font-semibold text-center mb-2"></h3>
        <p id="customAlertMessage" class="text-gray-600 text-center mb-4"></p>
        <div id="customAlertBodyContent" class="text-sm text-gray-500 text-center mb-4"></div>
        <div class="flex justify-center space-x-3">
            <button id="customAlertPrimaryBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"></button>
            <button id="customAlertSecondaryBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors hidden"></button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Fonctions pour le popup d'alerte moderne
    function showCustomAlert(options) {
        const overlay = document.getElementById('customAlertOverlay');
        const icon = document.getElementById('customAlertIcon');
        const iconClass = document.getElementById('customAlertIconClass');
        const title = document.getElementById('customAlertTitle');
        const message = document.getElementById('customAlertMessage');
        const bodyContent = document.getElementById('customAlertBodyContent');
        const primaryBtn = document.getElementById('customAlertPrimaryBtn');
        const secondaryBtn = document.getElementById('customAlertSecondaryBtn');

        // Configuration de l'icône
        icon.className = `flex justify-center mb-4 ${options.type}`;
        
        // Configuration de l'icône selon le type
        switch(options.type) {
            case 'success':
                iconClass.className = 'fas fa-check text-green-500 text-3xl';
                break;
            case 'error':
                iconClass.className = 'fas fa-exclamation-triangle text-red-500 text-3xl';
                break;
            case 'warning':
                iconClass.className = 'fas fa-exclamation-circle text-yellow-500 text-3xl';
                break;
            default:
                iconClass.className = 'fas fa-info-circle text-blue-500 text-3xl';
        }

        // Configuration du contenu
        title.textContent = options.title || '';
        message.textContent = options.message || '';
        bodyContent.innerHTML = options.bodyContent || '';

        // Configuration des boutons
        if (options.primaryBtn) {
            primaryBtn.textContent = options.primaryBtn.text;
            primaryBtn.onclick = options.primaryBtn.action;
            primaryBtn.style.display = 'block';
        } else {
            primaryBtn.style.display = 'none';
        }

        if (options.secondaryBtn) {
            secondaryBtn.textContent = options.secondaryBtn.text;
            secondaryBtn.onclick = options.secondaryBtn.action;
            secondaryBtn.style.display = 'block';
        } else {
            secondaryBtn.style.display = 'none';
        }

        // Afficher le popup
        overlay.classList.remove('hidden');
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
    }

    function hideCustomAlert() {
        const overlay = document.getElementById('customAlertOverlay');
        overlay.classList.add('hidden');
        
        // Restaurer le scroll du body
        document.body.style.overflow = '';
    }

    // Fermer le popup en cliquant sur l'overlay
    document.getElementById('customAlertOverlay').addEventListener('click', function(e) {
        if (e.target === this) {
            hideCustomAlert();
        }
    });

    // Fermer le popup avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideCustomAlert();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Validation du formulaire
        const form = document.querySelector('form');
        const termsCheckbox = document.getElementById('terms_agreement');

        form.addEventListener('submit', function(e) {
            if (!termsCheckbox.checked) {
                e.preventDefault();
                showCustomAlert({
                    type: 'warning',
                    title: 'Conditions requises',
                    message: 'Veuillez accepter les conditions générales pour continuer',
                    primaryBtn: {
                        text: 'Compris',
                        action: () => {
                            hideCustomAlert();
                            termsCheckbox.focus();
                        }
                    }
                });
            }
        });

        // Compte à rebours pour expiration
        let remainingTime = 300; // 5 minutes en secondes
        const countdownElement = document.createElement('div');
        countdownElement.className = 'text-sm text-gray-500 mt-2 text-center';
        countdownElement.innerHTML = 'Cette session expirera dans <span class="font-medium">5:00</span>';
        form.appendChild(countdownElement);

        const countdownTimer = setInterval(function() {
            remainingTime--;
            const minutes = Math.floor(remainingTime / 60);
            const seconds = remainingTime % 60;
            countdownElement.innerHTML = `Cette session expirera dans <span class="font-medium">${minutes}:${seconds < 10 ? '0' : ''}${seconds}</span>`;

            if (remainingTime <= 0) {
                clearInterval(countdownTimer);
                showCustomAlert({
                    type: 'error',
                    title: 'Session expirée',
                    message: 'Votre session a expiré. Veuillez revenir à la page précédente pour redémarrer.',
                    primaryBtn: {
                        text: 'Retour',
                        action: () => {
                            hideCustomAlert();
                            window.location.href = form.querySelector('a').getAttribute('href');
                        }
                    }
                });
            }
        }, 1000);
    });
</script>
@endpush
@endsection