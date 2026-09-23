@extends('layouts.public')

@section('title', 'Réservation de la borne')

@push('styles')
    <link rel="stylesheet" href="{{ asset("vendor/fontawesome/css/all.min.css") }}">
    <style>
        body {
            /* set the entire page background to white */
            background-color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .header-gradient {
            background: linear-gradient(to right, #3b82f6, #1d4ed8);
            border-radius: 12px 12px 0 0;
        }

        .qr-code-container {
            background-color: white;
            padding: 8px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .reservation-option {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid #e5e7eb;
        }

        .reservation-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
        }

        .reservation-option.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        .reservation-option.disabled {
            cursor: not-allowed;
            opacity: 0.3;
            filter: grayscale(100%);
            pointer-events: none;
        }
        
        .reservation-option.disabled:hover {
            transform: none;
            box-shadow: none;
            border-color: #e5e7eb;
        }

        .time-slot {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            background-color: white;
        }

        .time-slot:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .time-slot.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
        }

        .time-slot.available {
            border-color: #10b981;
        }

        .time-slot.unavailable {
            border-color: #ef4444;
            opacity: 0.5;
            background-color: #fef2f2;
        }

        .duration-btn {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .duration-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: var(--mobile-color);
        }

        .duration-btn.selected {
            border-color: var(--mobile-color);
            background-color: #eff6ff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .duration-btn.disabled {
            cursor: not-allowed;
            opacity: 0.3;
            filter: grayscale(100%);
            pointer-events: none;
        }
        
        .duration-btn.disabled:hover {
            transform: none;
            box-shadow: none;
            border-color: #e5e7eb;
        }
        
        /* Missing selection highlight */
        .missing-selection {
            animation: pulse-red 2s infinite;
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3) !important;
        }
        
        @keyframes pulse-red {
            0%, 100% {
                box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3);
            }
            50% {
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0.1);
            }
        }

        /* Responsive improvements */
        @media (max-width: 640px) {
            .mobile-padding {
                padding: 0.75rem;
            }
            
            .mobile-text-sm {
                font-size: 0.875rem;
            }
            
            .mobile-text-lg {
                font-size: 1.125rem;
            }
            
            .mobile-grid-1 {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            
            .mobile-grid-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 641px) and (max-width: 768px) {
            .tablet-grid-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            
            .tablet-grid-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        /* Mobile color theme */
        :root {
            --mobile-color: {{ $pricingPlan->mobile_theme_color ?? '#3b82f6' }};
        }

        .header-gradient { background: var(--mobile-color); }
        .step.active { background: var(--mobile-color); border-color: var(--mobile-color); color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.12); }
        .reservation-option:hover { border-color: var(--mobile-color); }
        .reservation-option.selected { border-color: var(--mobile-color); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .time-slot.selected { border-color: var(--mobile-color); }
        .duration-btn:hover { border-color: var(--mobile-color); }
        .duration-btn.selected { border-color: var(--mobile-color); }
    </style>
@endpush

@section('content')
<div class="max-w-full sm:max-w-lg md:max-w-xl lg:max-w-2xl mx-auto p-4 sm:p-6 min-h-screen flex flex-col bg-white">
        <!-- Back Button -->
        <div class="mb-4">
            <a href="{{ route('public.charging-point.offer', $chargingPoint->id) }}" 
               class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour à la borne
            </a>
        </div>
        
        <!-- App Logo Header -->
        <div class="flex justify-center mb-6">
            <div class="bg-white rounded-lg p-4 shadow-md border border-gray-100">
                <img src="{{ \App\Helpers\Brand::logo() }}" alt="{{ \App\Helpers\Brand::name() }}" class="h-8 sm:h-10 w-auto">
            </div>
        </div>

        <!-- Header -->
        <header class="header-gradient text-white p-3 sm:p-4 rounded-t-lg flex flex-col sm:flex-row items-center sm:justify-between mb-3 sm:mb-4 text-center sm:text-left">
            <div class="flex-1 mb-3 sm:mb-0">
                <h1 class="text-lg sm:text-xl font-bold" id="station-name">{{ $chargingPoint->name ?? 'N/A' }}</h1>
                <p class="flex items-center justify-center sm:justify-start text-xs sm:text-sm mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span id="station-address" class="text-xs sm:text-sm">{{ $chargingPoint->address ?? optional($chargingPoint->location)->address ?? 'N/A' }}</span>
                </p>
            </div>
            <div class="qr-code-container w-16 h-16 sm:w-20 sm:h-20">
                <img id="qr-code" src="{{ $qrCode }}" alt="QR Code de la borne" class="w-full h-full object-contain">
            </div>
        </header>

        <!-- Single Step Reservation Form -->
        <div class="card mb-6 flex-grow">
            <div class="p-4 sm:p-6">
                <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-6 text-center">
                    <i class="fas fa-charging-station text-blue-600 mr-2"></i>
                    Réserver la Borne
                </h3>

                <!-- Station Info Summary -->
                <div class="bg-blue-50 rounded-lg p-4 mb-6 border border-blue-200">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Puissance:</span>
                            <span class="font-semibold">{{ $chargingPoint->power_output ?? 'N/A' }} kW</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Partenaire:</span>
                            <span class="font-semibold">{{ optional($chargingPoint->partner)->name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Plan tarifaire:</span>
                            <span class="font-semibold text-blue-600">{{ $pricingPlan->name ?? 'Plan Standard' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Type:</span>
                            <span class="font-semibold">{{ ucfirst($pricingPlan->rate_type) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Reservation Type Selection -->
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-900 mb-4">Type de Réservation</h4>
                    <div class="text-sm text-gray-600 mb-3">
                        <span id="pricing-plan-info">Plan tarifaire: {{ $pricingPlan->name }} ({{ $pricingPlan->rate_type }})</span>
                        <div id="reservation-type-info" class="text-xs text-blue-600 mt-1"></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="reservation-option bg-white border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-500 transition-all cursor-pointer" data-type="kwh">
                            <div class="text-3xl text-blue-600 mb-2">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="text-lg font-bold text-gray-900 mb-1">Par Énergie</div>
                            <div class="text-sm text-gray-600">kWh</div>
                        </div>
                        <div class="reservation-option bg-white border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-500 transition-all cursor-pointer" data-type="minute">
                            <div class="text-3xl text-green-600 mb-2">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="text-lg font-bold text-gray-900 mb-1">Par Durée</div>
                            <div class="text-sm text-gray-600">Minutes</div>
                        </div>
                    </div>
                </div>

                <!-- Duration/Energy Selection -->
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-900 mb-4">Durée ou Quantité</h4>
                    
                    <!-- Limit Information -->
                    <div id="limit-info" class="text-sm text-orange-600 mb-3 p-2 bg-orange-50 rounded-lg border border-orange-200">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span id="limit-text">Limites du plan tarifaire</span>
                    </div>
                    
                    <!-- Quick Selection Buttons -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
                        <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-500 transition-all cursor-pointer" data-duration="10">
                            <div class="text-lg font-bold text-gray-900">10 min</div>
                            <div class="text-sm text-gray-600">Rapide</div>
                        </button>
                        <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-500 transition-all cursor-pointer" data-duration="20">
                            <div class="text-lg font-bold text-gray-900">20 min</div>
                            <div class="text-sm text-gray-600">Standard</div>
                        </button>
                        <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-500 transition-all cursor-pointer" data-duration="30">
                            <div class="text-lg font-bold text-gray-900">30 min</div>
                            <div class="text-sm text-gray-600">Complet</div>
                        </button>
                        <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-500 transition-all cursor-pointer" data-duration="40">
                            <div class="text-lg font-bold text-gray-900">40 min</div>
                            <div class="text-sm text-gray-600">Étendue</div>
                        </button>
                        <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-500 transition-all cursor-pointer" data-duration="50">
                            <div class="text-lg font-bold text-gray-900">50 min</div>
                            <div class="text-sm text-gray-600">Longue</div>
                        </button>
                        <button class="duration-btn bg-white border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-500 transition-all cursor-pointer" data-duration="60">
                            <div class="text-lg font-bold text-gray-900">1 heure</div>
                            <div class="text-sm text-gray-600">Maximale</div>
                        </button>
                    </div>

                    <!-- Custom Input -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label for="custom-value" class="block text-sm font-medium text-gray-700 mb-2">Ou saisissez une valeur personnalisée</label>
                        <div class="flex items-center space-x-2">
                            <input type="number" id="custom-value" min="10" step="10" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   placeholder="Ex: 10, 20, 30...">
                            <span id="value-unit" class="text-sm text-gray-600 font-medium">min</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Valeurs par intervalles de 10 minutes
                        </div>
                    </div>
                </div>

                <!-- Time Selection -->
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-900 mb-4">Heure de Début</h4>
                    
                    <!-- Custom Time Input -->
                    <div class="mb-4 bg-gray-50 rounded-lg p-4">
                        <label for="custom-time" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-clock mr-1"></i>
                            Ou saisissez une heure personnalisée
                        </label>
                        <div class="flex items-center space-x-2">
                            <input type="time" id="custom-time" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   min="06:00" max="22:00"
                                   value="{{ date('H:i') }}">
                            <button type="button" id="use-current-time" 
                                    class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-clock mr-1"></i>
                                Maintenant
                            </button>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Heures disponibles: 06:00 - 22:00
                        </div>
                    </div>
                    
                    <!-- Time Slots Grid -->
                    <div class="mb-3">
                        <h5 class="text-sm font-medium text-gray-700 mb-2">Ou choisissez parmi les créneaux disponibles:</h5>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 sm:gap-3">
                            @foreach($timeSlots as $slot)
                            <div class="time-slot {{ $slot['available'] ? 'available' : 'unavailable' }} bg-white border-2 border-gray-200 rounded-lg p-2 sm:p-3 text-center text-sm hover:border-blue-500 transition-all cursor-pointer"
                                 data-time="{{ $slot['time'] }}"
                                 data-available="{{ $slot['available'] ? 'true' : 'false' }}">
                                <div class="font-medium">{{ $slot['time'] }}</div>
                                <div class="text-xs text-gray-500">{{ $slot['available'] ? 'Disponible' : 'Occupé' }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Real Cost Calculation -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
                    <h4 class="text-sm sm:text-base font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-euro-sign text-green-600 mr-2"></i>
                        Coût Réel
                    </h4>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600" id="calculation-label">Tarif de base:</span>
                            <span class="font-semibold text-green-600" id="calculation-amount">0.00 {{ $currency ?? 'EUR' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Frais d'activation:</span>
                            <span class="font-semibold text-green-600" id="activation-fee-display">{{ number_format($pricingPlan->activation_fee ?? 0, 2) }} {{ $currency ?? 'EUR' }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t pt-2">
                            <span class="text-gray-600 font-semibold">Sous-total (HT):</span>
                            <span class="font-semibold text-green-600" id="subtotal-ht">0.00 {{ $currency ?? 'EUR' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">TVA ({{ number_format($pricingPlan->vatRate->rate ?? 0, 1) }}%):</span>
                            <span class="font-semibold text-green-600" id="vat-amount">0.00 {{ $currency ?? 'EUR' }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t pt-2 bg-green-100 p-3 rounded-lg">
                            <span class="text-gray-900 font-bold text-base">Montant Total:</span>
                            <span class="font-bold text-green-700 text-xl" id="total-ttc">0.00 {{ $currency ?? 'EUR' }}</span>
                        </div>
                    </div>
                    
                    <!-- Accuracy Note -->
                    <div class="mt-3 text-xs text-gray-500 bg-white p-2 rounded border">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Montant final calculé</strong> selon les tarifs du plan {{ $pricingPlan->name }}. 
                        Le montant total est fixe et calculé selon la consommation réelle.
                    </div>
                </div>
            </div>
        </div>

        <!-- Bouton de réservation -->
        <button id="reserve-btn"
                class="w-full bg-blue-600 text-white py-4 px-6 rounded-lg font-semibold text-lg hover:bg-blue-700 transition-all duration-300 disabled:bg-gray-400 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:-translate-y-1"
                disabled>
            <i class="fas fa-check mr-2"></i>
            Réserver la borne
        </button>
        

        <!-- Hidden form for submission -->
        <form id="reservation-form" action="{{ route('reservations.store', $chargingPoint->id) }}" method="POST" class="hidden">
            @csrf
            <input type="hidden" name="charging_point_id" value="{{ $chargingPoint->id }}">
            <input type="hidden" name="pricing_plan_id" value="{{ $pricingPlan->id }}">
            <input type="hidden" name="user_id" value="{{ auth()->id() }}">
            <input type="hidden" name="reservation_type" id="form-reservation-type">
            <input type="hidden" name="reservation_value" id="form-reservation-value">
            <input type="hidden" name="start_time" id="form-start-time">
            <input type="hidden" name="estimated_amount" id="form-estimated-amount">
            <input type="hidden" name="currency" value="{{ $currency ?? 'EUR' }}">
            <input type="hidden" name="price_per_kwh" value="{{ $pricingPlan->price_per_kwh ?? 0 }}">
            <input type="hidden" name="price_per_minute" value="{{ $pricingPlan->price_per_minute ?? 0 }}">
            <input type="hidden" name="activation_fee" value="{{ $pricingPlan->activation_fee ?? 0 }}">
            <input type="hidden" name="vat_rate" value="{{ $pricingPlan->vatRate->rate ?? 0 }}">
            <input type="hidden" name="admin_commission" id="form-admin-commission">
            <input type="hidden" name="integrator_commission" id="form-integrator-commission">
            <input type="hidden" name="partner_commission" id="form-partner-commission">
            <input type="hidden" name="business_profile_id" value="{{ $chargingPoint->business_profile_id ?? null }}">
            <input type="hidden" name="commission_plan_id" value="{{ $pricingPlan->commission_plan_id ?? null }}">
        </form>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Pass pricing data to JavaScript -->
    <script>
        window.pricingData = {
            activationFee: {{ $pricingPlan->activation_fee ?? 0 }},
            pricePerKwh: {{ $pricingPlan->price_per_kwh ?? 0 }},
            pricePerMinute: {{ $pricingPlan->price_per_minute ?? 0 }},
            vatRate: {{ $pricingPlan->vatRate->rate ?? 0 }},
            currency: '{{ $currency ?? 'EUR' }}',
            pricingPlan: @json($pricingPlan),
            chargingPoint: @json($chargingPoint),
            chargingPointId: {{ $chargingPoint->id }},
            pricingPlanId: {{ $pricingPlan->id }},
            reservationUrl: '{{ route("reservations.store", $chargingPoint->id) }}',
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    
    <script src="/js/reservation-manager.js"></script>
@endsection
