<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offre de Recharge - {{ $data['charging_point']->name ?? 'Borne de Recharge' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link href="{{ asset("vendor/fontawesome/css/all.min.css") }}" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .status-online {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .status-offline {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .status-maintenance {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-2 py-4 sm:px-4 sm:py-6">
            <div class="flex flex-col sm:flex-row items-center justify-between text-center sm:text-left">
                <div class="flex items-center space-x-2 sm:space-x-4 mb-2 sm:mb-0">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-bolt text-lg sm:text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold">{{ $data['charging_point']->name ?? 'Borne de Recharge' }}</h1>
                        <p class="text-green-100 text-xs sm:text-sm">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            {{ $data['charging_point']->address ?? 'Adresse non disponible' }}, {{ $data['charging_point']->city ?? '' }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center px-2 py-1 rounded-full text-xs sm:text-sm font-medium bg-white bg-opacity-20">
                        <span class="w-2 h-2 rounded-full mr-1 sm:mr-2
                            @if(($data['charging_point']->status ?? '') === 'online') bg-green-300
                            @elseif(($data['charging_point']->status ?? '') === 'offline') bg-red-300
                            @else bg-yellow-300 @endif"></span>
                        {{ ucfirst($data['charging_point']->status ?? 'inconnu') }}
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Charging Point Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Charging Point Info Card -->
                <div class="bg-white rounded-xl shadow-md card-hover">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-info-circle text-green-500 mr-2"></i>
                            Informations de la Borne
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-600 font-medium">Numéro de série</span>
                                    <span class="text-gray-900 font-semibold">{{ $data['charging_point']->serial_number ?? 'N/A' }}</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-600 font-medium">Marque</span>
                                    <span class="text-gray-900 font-semibold">{{ $data['charging_point']->manufacturer ?? 'N/A' }}</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-600 font-medium">Modèle</span>
                                    <span class="text-gray-900 font-semibold">{{ $data['charging_point']->model ?? 'N/A' }}</span>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-600 font-medium">Puissance max</span>
                                    <span class="text-gray-900 font-semibold">{{ number_format($data['charging_point']->power_output ?? 0, 1) }} kW</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-600 font-medium">Pays</span>
                                    <span class="text-gray-900 font-semibold">{{ $data['charging_point']->country ?? 'N/A' }}</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-600 font-medium">Code postal</span>
                                    <span class="text-gray-900 font-semibold">{{ $data['charging_point']->postal_code ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Suppression de la carte des connecteurs disponibles -->
            </div>

            <!-- Right Column - Pricing Plans -->
            <div class="space-y-6">
                <!-- Current Pricing Plan -->
                @if($data['charging_point']->pricingPlan)
                <div class="bg-white rounded-xl shadow-md card-hover">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-tag text-green-500 mr-2"></i>
                            Plan Tarifaire Actuel
                        </h2>
                        
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold text-green-900">{{ $data['charging_point']->pricingPlan->name ?? 'Plan Standard' }}</h3>
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    Actif
                                </span>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Type de tarification:</span>
                                    <span class="font-medium">{{ ucfirst($data['charging_point']->pricingPlan->rate_type ?? 'mixte') }}</span>
                                </div>
                                
                                @if(isset($data['charging_point']->pricingPlan->price_per_kwh) && $data['charging_point']->pricingPlan->price_per_kwh > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Prix par kWh:</span>
                                    <span class="font-medium">{{ number_format($data['charging_point']->pricingPlan->price_per_kwh, 4) }} {{ $data['charging_point']->pricingPlan->currency ?? 'EUR' }}</span>
                                </div>
                                @endif
                                
                                @if(isset($data['charging_point']->pricingPlan->price_per_minute) && $data['charging_point']->pricingPlan->price_per_minute > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Prix par minute:</span>
                                    <span class="font-medium">{{ number_format($data['charging_point']->pricingPlan->price_per_minute, 2) }} {{ $data['charging_point']->pricingPlan->currency ?? 'EUR' }}</span>
                                </div>
                                @endif
                                
                                @if(isset($data['charging_point']->pricingPlan->description) && $data['charging_point']->pricingPlan->description)
                                <div class="mt-3 p-3 bg-white rounded border">
                                    <p class="text-sm text-gray-700">{{ $data['charging_point']->pricingPlan->description }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif


                <!-- Action Buttons -->
                <div class="bg-white rounded-xl shadow-md card-hover">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-play text-green-500 mr-2"></i>
                            Réserver
                        </h2>
                        
                        <div class="space-y-3">
                            <a href="{{ route('public.charging-point.offer.reservation', $data['charging_point']->id) }}"
                               class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <i class="fas fa-bolt mr-2"></i>
                                Réserver Maintenant
                            </a>
                            
                            <a href="{{ route('guest-reservations.index') }}" 
                               class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <i class="fas fa-list mr-2"></i>
                                Mes Réservations
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-8 sm:py-8 sm:mt-12">
        <div class="container mx-auto px-2 text-center sm:px-4">
            <div class="flex items-center justify-center space-x-2 mb-3 sm:space-x-4 sm:mb-4">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-bolt text-white text-base sm:text-lg"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-bold">Evon Power</h3>
            </div>
            <p class="text-gray-300 text-sm sm:text-base">Votre partenaire de confiance pour la recharge électrique</p>
            <div class="mt-3 flex justify-center space-x-4 text-xs sm:text-sm text-gray-400 sm:mt-4 sm:space-x-6">
                <a href="{{ route('guest-reservations.index') }}" class="hover:text-white transition duration-200">Mes Réservations</a>
                <a href="#" class="hover:text-white transition duration-200">Support</a>
                <a href="#" class="hover:text-white transition duration-200">Conditions</a>
                <a href="#" class="hover:text-white transition duration-200">Confidentialité</a>
            </div>
        </div>
    </footer>

    <script>
        // Add any interactive functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Example: Add click handlers for action buttons
            const startChargingBtn = document.querySelector('a[href*="charging.start.form"]');
            if (startChargingBtn) {
                startChargingBtn.addEventListener('click', function(e) {
                    // The link will handle the navigation
                    console.log('Starting charging process...');
                });
            }
        });
    </script>
</body>
</html> 