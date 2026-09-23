<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de recharge - Session #{{ $data['session']->session_id }}</title>
    {{-- Tailwind deja inclus dans app.css --}}
    <link href="{{ asset("vendor/fontawesome/css/all.min.css") }}" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white !important; }
            .card-shadow { box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg no-print">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="{{ asset('images/evon-logo.png') }}" alt="EVON Logo" class="h-12 w-auto">
                    <div>
                        <h1 class="text-2xl font-bold">Reçu de recharge</h1>
                        <p class="text-blue-100">Session terminée</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="window.print()" 
                            class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg transition-all">
                        <i class="fas fa-print mr-2"></i>Imprimer
                    </button>
                    <a href="{{ route('offre.show', $data['session']->charging_point_id) }}" 
                       class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg transition-all">
                        <i class="fas fa-home mr-2"></i>Accueil
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <!-- Reçu principal -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-xl card-shadow p-8 mb-8">
                <!-- En-tête du reçu -->
                <div class="text-center mb-8">
                    <img src="{{ asset('images/evon-logo.png') }}" alt="EVON Logo" class="h-16 w-auto mx-auto mb-4">
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">Reçu de recharge</h2>
                    <p class="text-gray-600">Session #{{ $data['session']->session_id }}</p>
                    <p class="text-sm text-gray-500">{{ now()->format('d/m/Y H:i') }}</p>
                </div>

                <!-- Informations de la borne -->
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-charging-station text-blue-500 mr-3"></i>
                        Borne de recharge
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Nom</p>
                            <p class="font-semibold text-gray-800">{{ $data['session']->chargingPoint->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Adresse</p>
                            <p class="font-semibold text-gray-800">{{ $data['session']->chargingPoint->address }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Connecteur</p>
                            <p class="font-semibold text-gray-800">{{ $data['session']->connector->connector_id }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Type</p>
                            <p class="font-semibold text-gray-800">{{ $data['session']->connector->connector_type }}</p>
                        </div>
                    </div>
                </div>

                <!-- Détails de la session -->
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-clock text-green-500 mr-3"></i>
                        Détails de la session
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Début</p>
                            <p class="font-semibold text-gray-800">{{ $data['session']->started_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Fin</p>
                            <p class="font-semibold text-gray-800">{{ $data['session']->stopped_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Durée</p>
                            <p class="font-semibold text-gray-800">{{ $data['metrics']['duration_formatted'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Statut</p>
                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                Terminé
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Plan tarifaire -->
                @if($data['session']->pricingPlan)
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-tag text-purple-500 mr-3"></i>
                        Plan tarifaire
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Nom du plan</p>
                            <p class="font-semibold text-gray-800">{{ $data['session']->pricingPlan->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Prix par kWh</p>
                            <p class="font-semibold text-gray-800">{{ number_format($data['session']->pricingPlan->price_per_kwh, 2) }} €</p>
                        </div>
                        @if($data['session']->pricingPlan->price_per_minute > 0)
                        <div>
                            <p class="text-sm text-gray-600">Prix par minute</p>
                            <p class="font-semibold text-gray-800">{{ number_format($data['session']->pricingPlan->price_per_minute, 2) }} €</p>
                        </div>
                        @endif
                        @if($data['session']->pricingPlan->activation_fee > 0)
                        <div>
                            <p class="text-sm text-gray-600">Frais d'activation</p>
                            <p class="font-semibold text-gray-800">{{ number_format($data['session']->pricingPlan->activation_fee, 2) }} €</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Consommation et coûts -->
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-bolt text-orange-500 mr-3"></i>
                        Consommation et coûts
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Énergie consommée</span>
                            <span class="font-semibold text-gray-800">{{ $data['metrics']['energy_consumed_kwh'] }} kWh</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Prix par kWh</span>
                            <span class="font-semibold text-gray-800">{{ number_format($data['metrics']['cost_per_kwh'], 2) }} €</span>
                        </div>
                        @if($data['session']->pricingPlan && $data['session']->pricingPlan->activation_fee > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Frais d'activation</span>
                            <span class="font-semibold text-gray-800">{{ number_format($data['session']->pricingPlan->activation_fee, 2) }} €</span>
                        </div>
                        @endif
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-800">Total</span>
                                <span class="text-2xl font-bold text-green-600">{{ number_format($data['metrics']['total_cost'], 2) }} €</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations client -->
                @if($data['session']->meta_data)
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user text-blue-500 mr-3"></i>
                        Informations client
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        @if(isset($data['session']->meta_data['email']))
                        <div>
                            <p class="text-sm text-gray-600">Email</p>
                            <p class="font-semibold text-gray-800">{{ $data['session']->meta_data['email'] }}</p>
                        </div>
                        @endif
                        @if(isset($data['session']->meta_data['vehicle_plate']))
                        <div>
                            <p class="text-sm text-gray-600">Plaque d'immatriculation</p>
                            <p class="font-semibold text-gray-800">{{ $data['session']->meta_data['vehicle_plate'] }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Pied de page -->
                <div class="text-center text-gray-500 text-sm">
                    <p>Merci d'avoir utilisé nos services de recharge</p>
                    <p class="mt-2">Pour toute question, contactez-nous à support@evon.com</p>
                    <p class="mt-1">Reçu généré le {{ now()->format('d/m/Y à H:i') }}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-center space-x-4 no-print">
                <button onclick="window.print()" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-print mr-2"></i>Imprimer le reçu
                </button>
                <a href="{{ route('offre.show', $data['session']->charging_point_id) }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-home mr-2"></i>Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</body>
</html> 