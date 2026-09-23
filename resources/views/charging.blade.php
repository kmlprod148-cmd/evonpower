@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto p-4">
    <!-- En-tête avec animation -->
    <div class="text-center mb-6">
        <div class="charging-animation inline-block mb-4">
            <div class="h-24 w-24 rounded-full bg-green-100 flex items-center justify-center mx-auto relative overflow-hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-500 z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <div class="absolute bottom-0 left-0 right-0 bg-green-500 charging-indicator"></div>
            </div>
        </div>

        <h1 class="text-2xl font-bold">Recharge en cours</h1>
        <p class="text-gray-600 mt-2" id="charging-message">Connexion établie, recharge en cours...</p>
    </div>

    <!-- Carte d'information sur la session -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-900">Session de recharge</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <span class="animate-pulse mr-1 h-2 w-2 rounded-full bg-green-500"></span>
                    Active
                </span>
            </div>

            <div class="mt-4 space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-500">ID Session</span>
                    <span class="font-medium">{{ $sessionId ?? 'CH-' . rand(100000, 999999) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Point de charge</span>
                    <span class="font-medium">{{ $chargingPoint->name ?? 'Station-' . rand(1000, 9999) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Connecteur</span>
                    <span class="font-medium">{{ $connector->type ?? 'Type2' }} #{{ $connector->connector_id ?? '1' }}</span>
                </div>
                <!-- Mode de consommation -->
                <div class="flex justify-between">
                    <span class="text-gray-500">Mode de facturation</span>
                    <span class="font-medium">
                        @if(isset($activeSession->meta_data['consumption_mode']))
                            @if($activeSession->meta_data['consumption_mode'] === 'prepaid')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    Prépayée
                                </span>
                            @elseif($activeSession->meta_data['consumption_mode'] === 'postpaid')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Postpayée
                                </span>
                            @endif
                        @else
                            <span class="text-gray-500">Non défini</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Heure de début</span>
                    <span class="font-medium" id="start-time">{{ $activeSession->started_at->format('H:i:s') ?? now()->format('H:i:s') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Durée</span>
                    <span class="font-medium" id="duration">00:00:00</span>
                </div>
            </div>
        </div>

        <div class="p-6 bg-gray-50">
            <h3 class="text-base font-medium text-gray-900 mb-4">Informations en temps réel</h3>

            <div class="space-y-4">
                <!-- Puissance de charge -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm text-gray-500">Puissance</span>
                        <span class="text-sm font-medium text-gray-900" id="current-power">0.0 kW</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-green-600 h-2.5 rounded-full" id="power-gauge" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Énergie consommée -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm text-gray-500">Énergie consommée</span>
                        <span class="text-sm font-medium text-gray-900" id="energy-consumed">0.0 kWh</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" id="energy-gauge" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Total Facturé (TTC) -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm text-gray-500">{{ __('reservations.total_price') }}</span>
                        <span class="text-sm font-medium text-gray-900" id="estimated-cost">0.00 EUR</span>
                    </div>
                </div>

                <!-- Notification spéciale pour mode postpayé -->
                @if(isset($activeSession->meta_data['consumption_mode']) && $activeSession->meta_data['consumption_mode'] === 'postpaid')
                <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-green-800">Mode Postpayé Activé</h4>
                            <p class="text-xs text-green-700 mt-1">
                                Vous serez facturé à la fin de la session selon votre consommation réelle. 
                                Votre solde sera débité automatiquement.
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- État de la batterie (simulé) -->
            <div class="mt-6">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm text-gray-500">État de charge estimé</span>
                    <span class="text-sm font-medium text-gray-900" id="battery-percentage">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4 relative">
                    <div class="bg-green-600 h-4 rounded-full" id="battery-gauge" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div class="p-6 border-t border-gray-200 flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3">
            <a href="#" class="inline-flex justify-center items-center sm:flex-1 px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Aide
            </a>

            <form action="{{ route('charging-points.offer.stop', ['id' => $chargingPoint->id ?? 1]) }}" method="POST" class="sm:flex-1">
                @csrf
                <button type="button" id="stop-charging-btn" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Arrêter la recharge
                </button>
            </form>
        </div>
    </div>

    <!-- Conseils et astuces -->
    <div class="mt-6 bg-blue-50 rounded-lg p-4 border border-blue-200">
        <h3 class="font-medium text-blue-800 mb-2">Conseils pour une recharge optimale</h3>
        <ul class="list-disc list-inside text-sm text-blue-700 space-y-1">
            <li>Pour les véhicules modernes, une recharge à 80% est généralement recommandée pour préserver la batterie.</li>
            <li>Le temps de recharge peut varier en fonction de la température extérieure.</li>
            <li>N'oubliez pas de déconnecter correctement le câble à la fin de votre recharge.</li>
        </ul>
    </div>

    <!-- Confirmation d'arrêt de la recharge -->
    <div id="stop-confirmation-modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm mx-4 sm:mx-0">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmer l'arrêt de la recharge</h3>
            <p class="text-sm text-gray-500 mb-4">Êtes-vous sûr de vouloir arrêter cette session de recharge?</p>
            <div class="flex justify-end space-x-3">
                <button id="cancel-stop" class="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Annuler
                </button>
                <button id="confirm-stop" class="px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Arrêter la recharge
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .charging-animation .charging-indicator {
        height: 0%;
        animation: charging-animation 3s ease-in-out infinite;
    }

    @keyframes charging-animation {
        0% { height: 0%; }
        50% { height: 80%; }
        100% { height: 0%; }
    }

    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .5; }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Éléments DOM
        const durationElement = document.getElementById('duration');
        const currentPowerElement = document.getElementById('current-power');
        const powerGaugeElement = document.getElementById('power-gauge');
        const energyElement = document.getElementById('energy-consumed');
        const energyGaugeElement = document.getElementById('energy-gauge');
        const estimatedCostElement = document.getElementById('estimated-cost');
        const batteryPercentageElement = document.getElementById('battery-percentage');
        const batteryGaugeElement = document.getElementById('battery-gauge');
        const chargingMessageElement = document.getElementById('charging-message');
        const stopButton = document.getElementById('stop-charging-btn');
        const stopModal = document.getElementById('stop-confirmation-modal');
        const cancelStopButton = document.getElementById('cancel-stop');
        const confirmStopButton = document.getElementById('confirm-stop');
        const stopForm = stopButton.closest('form');

        // Modèle de tarification (à obtenir depuis le contrôleur en situation réelle)
        const pricing = {
            pricePerKwh: {{ $pricingPlan->price_per_kwh ?? 0.30 }},
            pricePerMinute: {{ $pricingPlan->price_per_minute ?? 0 }},
            activationFee: {{ $pricingPlan->activation_fee ?? 0 }},
            vatRate: {{ ($pricingPlan->vatRate->rate ?? 0) / 100 }}
        };

        // Informations de la session
        const session = {
            startTime: new Date('{{ $activeSession->started_at ?? now() }}'), // Use actual start time
            maxPower: {{ $connector->power ?? 22 }}, // kW
            currentPower: 0,
            energyConsumed: 0,
            batteryPercentage: 0,
            initialBatteryPercentage: 25, // Valeur simulée
            targetBatteryPercentage: 80, // Valeur simulée
            batteryCapacity: 60, // kWh, valeur simulée
            estimatedCost: pricing.activationFee,
            isCharging: true,
            messages: [
                "Connexion établie, recharge en cours...",
                "Recharge en cours. Puissance optimale.",
                "Recharge en cours. Vitesse de charge stable.",
                "Recharge en cours. Optimisation de la batterie.",
                "Phase de recharge rapide en cours...",
                "Phase de recharge lente en cours...",
                "Recharge en cours. Surveillance de la température."
            ]
        };

        // Timer pour mise à jour régulière des valeurs
        let startTime = session.startTime.getTime(); // Get timestamp
        let timerInterval;

        // Simulation de la courbe de recharge (plus rapide au début, plus lente à la fin)
        function simulateCharging() {
            if (!session.isCharging) return;

            const elapsedTimeInSeconds = (Date.now() - startTime) / 1000;
            const elapsedTimeInMinutes = elapsedTimeInSeconds / 60;

            // Simuler la durée de la session
            const hours = Math.floor(elapsedTimeInSeconds / 3600);
            const minutes = Math.floor((elapsedTimeInSeconds % 3600) / 60);
            const seconds = Math.floor(elapsedTimeInSeconds % 60);
            durationElement.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            // Simuler la courbe de puissance (démarre à 80% de la puissance max, puis diminue progressivement)
            const timeProgress = Math.min(elapsedTimeInMinutes / 60, 1); // Supposons une recharge complète en 60 minutes
            const powerPercent = Math.max(0.8 * (1 - timeProgress * 1.5), 0.1);
            session.currentPower = session.maxPower * powerPercent;
            currentPowerElement.textContent = `${session.currentPower.toFixed(1)} kW`;
            powerGaugeElement.style.width = `${(session.currentPower / session.maxPower) * 100}%`;

            // Calculer l'énergie consommée (kWh) - intégration de la puissance sur le temps
            const energyIncrement = session.currentPower * (1 / 3600); // Energy in kWh for 1 second interval
            session.energyConsumed += energyIncrement;
            energyElement.textContent = `${session.energyConsumed.toFixed(1)} kWh`;
            energyGaugeElement.style.width = `${Math.min((session.energyConsumed / session.batteryCapacity) * 100, 100)}%`;

            // Calculer le total facturé (HT d'abord, puis TTC avec TVA)
            const subtotalHT = pricing.activationFee +
                              (pricing.pricePerKwh * session.energyConsumed) +
                              (pricing.pricePerMinute * elapsedTimeInMinutes);
            // Appliquer la TVA si configurée
            session.estimatedCost = pricing.vatRate > 0 
                ? subtotalHT * (1 + pricing.vatRate)
                : subtotalHT;
            estimatedCostElement.textContent = `${session.estimatedCost.toFixed(2)} EUR`;

            // Simuler l'état de la batterie
            const batteryCapacityToCharge = session.batteryCapacity *
                                         (session.targetBatteryPercentage - session.initialBatteryPercentage) / 100;
            const chargingProgress = batteryCapacityToCharge > 0 ? Math.min(session.energyConsumed / batteryCapacityToCharge, 1) : 0;
            session.batteryPercentage = session.initialBatteryPercentage +
                                     (session.targetBatteryPercentage - session.initialBatteryPercentage) * chargingProgress;
            batteryPercentageElement.textContent = `${Math.round(session.batteryPercentage)}%`;
            batteryGaugeElement.style.width = `${session.batteryPercentage}%`;

            // Changer le message de recharge toutes les 30 secondes
            if (Math.floor(elapsedTimeInSeconds) % 30 === 0 && elapsedTimeInSeconds > 1) {
                const randomIndex = Math.floor(Math.random() * session.messages.length);
                chargingMessageElement.textContent = session.messages[randomIndex];
            }

            // Ralentir la recharge quand on approche de l'objectif
            if (session.batteryPercentage >= 75) {
                powerGaugeElement.classList.remove('bg-green-600');
                powerGaugeElement.classList.add('bg-yellow-500');
            } else {
                 powerGaugeElement.classList.add('bg-green-600');
                powerGaugeElement.classList.remove('bg-yellow-500');
            }

            // Arrêter la simulation si la batterie est pleine (ou cible atteinte)
            if (session.batteryPercentage >= session.targetBatteryPercentage) {
                stopSimulation();
                chargingMessageElement.textContent = "Recharge terminée !";
            }
        }

        function startSimulation() {
            if (!timerInterval) {
                timerInterval = setInterval(simulateCharging, 1000); // Update every second
            }
        }

        function stopSimulation() {
            clearInterval(timerInterval);
            timerInterval = null;
            session.isCharging = false;
            // Mettre à jour l'UI pour indiquer l'arrêt
            powerGaugeElement.style.width = '0%';
            currentPowerElement.textContent = '0.0 kW';
            chargingMessageElement.textContent = "Recharge arrêtée.";
            // Optionnel: désactiver le bouton d'arrêt
            stopButton.disabled = true;
            stopButton.classList.add('opacity-50', 'cursor-not-allowed');
        }

        // Gestion du modal d'arrêt
        stopButton.addEventListener('click', function() {
            stopModal.classList.remove('hidden');
        });

        cancelStopButton.addEventListener('click', function() {
            stopModal.classList.add('hidden');
        });

        confirmStopButton.addEventListener('click', function() {
            stopModal.classList.add('hidden');
            stopSimulation(); // Arrêter la simulation JS
            stopForm.submit(); // Soumettre le formulaire pour arrêter côté serveur
        });

        // Démarrer la simulation
        startSimulation();
    });
</script>
@endpush
@endsection