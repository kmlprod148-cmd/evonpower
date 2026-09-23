@extends('layouts.public')

@section('title', 'Offre de Recharge - ' . ($data['charging_point']->name ?? 'Borne de Recharge'))

@push('styles')
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

    /* Styles personnalisés pour le curseur de durée */
    .duration-slider-container {
        position: relative;
        padding: 16px 0;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .duration-slider-container:hover {
        border-color: #10b981;
        box-shadow: 0 4px 20px rgba(16, 185, 129, 0.15);
    }

    .duration-slider-container:focus-within {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    .duration-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 8px;
        border-radius: 4px;
        background: linear-gradient(90deg, #e2e8f0 0%, #cbd5e1 100%);
        outline: none;
        margin: 0 20px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .duration-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        cursor: pointer;
        border: 3px solid white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        transition: all 0.3s ease;
    }

    .duration-slider::-webkit-slider-thumb:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
    }

    .duration-slider::-webkit-slider-thumb:active {
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.5);
    }

    .duration-slider::-moz-range-thumb {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        cursor: pointer;
        border: 3px solid white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        transition: all 0.3s ease;
    }

    .duration-slider::-moz-range-thumb:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
    }

    .duration-slider::-moz-range-track {
        height: 8px;
        border-radius: 4px;
        background: linear-gradient(90deg, #e2e8f0 0%, #cbd5e1 100%);
        border: none;
    }

    .duration-value-display {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 12px;
        padding: 10px 16px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border-radius: 10px;
        font-weight: 600;
        font-size: 16px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        transition: all 0.3s ease;
    }

    .duration-value-display:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
    }

    .duration-min-max {
        display: flex;
        justify-content: space-between;
        margin: 8px 16px 0;
        font-size: 11px;
        color: #64748b;
        font-weight: 500;
    }

    .duration-presets {
        display: flex;
        justify-content: center;
        gap: 6px;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .duration-preset-btn {
        padding: 5px 10px;
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        font-size: 11px;
        font-weight: 500;
        color: #64748b;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .duration-preset-btn:hover {
        border-color: #10b981;
        color: #10b981;
        transform: translateY(-1px);
    }

    .duration-preset-btn.active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-color: #10b981;
        color: white;
    }

    .duration-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        margin-right: 8px;
    }

    /* Mobile-first responsive design - no media queries needed */

    /* Styles pour le popup d'alerte moderne */
    .custom-alert-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .custom-alert-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .custom-alert {
        background: white;
        border-radius: 12px;
        padding: 0;
        max-width: 95%;
        width: 350px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        transform: scale(0.7) translateY(-20px);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .custom-alert-overlay.show .custom-alert {
        transform: scale(1) translateY(0);
    }

    .custom-alert-header {
        padding: 16px 20px 12px;
        text-align: center;
        border-bottom: 1px solid #e5e7eb;
    }

    .custom-alert-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 20px;
    }

    .custom-alert-icon.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .custom-alert-icon.error {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .custom-alert-icon.warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }

    .custom-alert-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 6px;
    }

    .custom-alert-message {
        font-size: 14px;
        color: #6b7280;
        line-height: 1.5;
    }

    .custom-alert-body {
        padding: 16px 20px;
        text-align: center;
    }

    .custom-alert-footer {
        padding: 12px 20px 20px;
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-direction: column;
    }

    .custom-alert-btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        width: 100%;
    }

    .custom-alert-btn.primary {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .custom-alert-btn.primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }

    .custom-alert-btn.secondary {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    .custom-alert-btn.secondary:hover {
        background: #e5e7eb;
        transform: translateY(-1px);
    }

    /* Styles pour les options de paiement */
    .payment-option {
        position: relative;
    }
    
    .payment-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    
    .payment-option label {
        display: block;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .payment-option input[type="radio"]:checked + label,
    .payment-option input[type="radio"]:checked ~ label,
    .payment-option.active label {
        border-color: #10b981 !important;
        background-color: #f0fdf4 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .payment-option input[type="radio"]:checked + label .payment-icon,
    .payment-option input[type="radio"]:checked ~ label .payment-icon,
    .payment-option.active label .payment-icon {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .payment-option label:hover {
        border-color: #10b981;
        background-color: #f0fdf4;
    }
    
    /* Indicateur de sélection */
    .payment-option.active::after {
        content: '✓';
        position: absolute;
        top: 10px;
        right: 10px;
        width: 24px;
        height: 24px;
        background: #10b981;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }

    /* Mobile-first responsive design - no media queries needed */
</style>
@endpush

@section('content')
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-3 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    @if(file_exists(public_path('images/evon-logo.png')))
                        <img src="{{ asset('images/evon-logo.png') }}"
                             alt="EVON Logo"
                             class="h-6 w-auto">
                    @else
                        <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                            <i class="fas fa-bolt text-sm"></i>
                        </div>
                    @endif
                    <div>
                        <h1 class="text-base font-bold">{{ $data['charging_point']->name ?? 'Borne de Recharge' }}</h1>
                        <p class="text-green-100 text-xs">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            {{ $data['charging_point']->address ?? 'Adresse non disponible' }}, {{ $data['charging_point']->city ?? '' }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white bg-opacity-20">
                        <span class="w-1.5 h-1.5 rounded-full mr-1
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
    <div class="container mx-auto px-3 py-4"> {{-- Mobile-first padding --}}
        <div class="space-y-4"> {{-- Single column layout for all screens --}}
            <!-- Charging Point Details -->
            <div class="space-y-4">
                <!-- Charging Point Info Card -->
                <div class="bg-white rounded-xl shadow-md card-hover">
                    <div class="p-4">
                        <h2 class="text-lg font-bold text-gray-900 mb-3">
                            <i class="fas fa-info-circle text-green-500 mr-2"></i>
                            Informations de la Borne
                        </h2>
                        
                        <div class="grid grid-cols-1 gap-3">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm">
                                    <span class="text-gray-600 font-medium">Numéro de série</span>
                                    <span class="text-gray-900 font-semibold">{{ $data['charging_point']->serial_number ?? 'N/A' }}</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm">
                                    <span class="text-gray-600 font-medium">Marque</span>
                                    <span class="text-gray-900 font-semibold">{{ $data['charging_point']->manufacturer ?? 'N/A' }}</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm">
                                    <span class="text-gray-600 font-medium">Modèle</span>
                                    <span class="text-gray-900 font-semibold">{{ $data['charging_point']->model ?? 'N/A' }}</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm">
                                    <span class="text-gray-600 font-medium">Puissance max</span>
                                    <span class="text-gray-900 font-semibold">{{ number_format($data['charging_point']->power_output ?? 0, 1) }} kW</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm">
                                    <span class="text-gray-600 font-medium">Pays</span>
                                    <span class="text-gray-900 font-semibold">{{ $data['charging_point']->country ?? 'N/A' }}</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm">
                                    <span class="text-gray-600 font-medium">Code postal</span>
                                    <span class="text-gray-900 font-semibold">{{ $data['charging_point']->postal_code ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Pricing Plans -->
            <div class="space-y-4">
                <!-- Current Pricing Plan -->
                @if(isset($data['charging_point']->pricingPlan))
                <div class="bg-white rounded-xl shadow-md card-hover">
                    <div class="p-4">
                        <h2 class="text-lg font-bold text-gray-900 mb-3">
                            <i class="fas fa-tag text-green-500 mr-2"></i>
                            Plan Tarifaire Actuel
                        </h2>
                        
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-base font-semibold text-green-900">{{ $data['charging_point']->pricingPlan->name ?? 'Plan Standard' }}</h3>
                                <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    Actif
                                </span>
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Type de tarification:</span>
                                    <span class="font-medium">{{ ucfirst($data['charging_point']->pricingPlan->rate_type ?? 'mixte') }}</span>
                                </div>
                                
                                @if(isset($data['charging_point']->pricingPlan->price_per_kwh) && $data['charging_point']->pricingPlan->price_per_kwh > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Prix par kWh:</span>
                                    <span class="font-medium">{{ number_format($data['charging_point']->pricingPlan->price_per_kwh, 4) }} {{ $data['charging_point']->pricingPlan->currency ?? 'EUR' }}</span>
                                </div>
                                @endif
                                
                                @if(isset($data['charging_point']->pricingPlan->price_per_minute) && $data['charging_point']->pricingPlan->price_per_minute > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Prix par minute:</span>
                                    <span class="font-medium">{{ number_format($data['charging_point']->pricingPlan->price_per_minute, 2) }} {{ $data['charging_point']->pricingPlan->currency ?? 'EUR' }}</span>
                                </div>
                                @endif
                                
                                @if(isset($data['charging_point']->pricingPlan->description) && $data['charging_point']->pricingPlan->description)
                                <div class="mt-3 p-3 bg-white rounded border text-sm">
                                    <p class="text-gray-700">{{ $data['charging_point']->pricingPlan->description }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Reservation Form -->
                <div class="bg-white rounded-xl shadow-md card-hover">
                    <div class="p-4">
                        <h2 class="text-lg font-bold text-gray-900 mb-3">
                            <i class="fas fa-calendar-alt text-green-500 mr-2"></i>
                            Réserver une Session
                        </h2>
                        <form action="{{ route('public.charging-point.offer.store-reservation', $data['charging_point']->id) }}" method="POST" id="reservationForm" onsubmit="return false;">
                            @csrf
                            <input type="hidden" name="charging_point_id" value="{{ $data['charging_point']->id }}">
                            <input type="hidden" name="pricing_plan_id" id="pricing_plan_id" value="{{ $data['activePricingPlan']->id }}">

                            <!-- Available Pricing Plans -->
                            @if(isset($data['pricing_plans']) && count($data['pricing_plans']) > 0)
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Choisissez un plan tarifaire</label>
                                <div class="space-y-2">
                                    @foreach($data['pricing_plans'] as $plan)
                                        <label for="plan_{{ $plan->id }}" class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                            <input type="radio" name="selected_plan" id="plan_{{ $plan->id }}" value="{{ $plan->id }}" class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500" {{ $plan->id == $data['activePricingPlan']->id ? 'checked' : '' }}>
                                            <div class="ml-3 text-sm">
                                                <span class="font-semibold text-gray-900">{{ $plan->name }}</span>
                                                <div class="text-xs text-gray-600">
                                                    @if(isset($plan->price_per_kwh) && $plan->price_per_kwh > 0)
                                                        <span>{{ number_format($plan->price_per_kwh, 4) }} {{ $plan->currency ?? 'EUR' }}/kWh</span>
                                                    @endif
                                                    @if(isset($plan->price_per_minute) && $plan->price_per_minute > 0)
                                                        <span class="ml-2">{{ number_format($plan->price_per_minute, 2) }} {{ $plan->currency ?? 'EUR' }}/min</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <div class="mb-4">
                                <label for="reservation_type" class="block text-sm font-medium text-gray-700 mb-1">Type de réservation</label>
                                <input type="text" name="reservation_type" id="reservation_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="{{ $data['plan_type'] ?? '' }}" readonly>
                            </div>

                            {{-- Bloc de réservation --}}
                            @if($data['plan_type'] === 'both')
    <div class="mb-6">
        <label for="reservation_minutes" class="block text-sm font-medium text-gray-700 mb-3">
            <i class="fas fa-clock text-blue-600 mr-2"></i>
            Durée de recharge (minutes)
        </label>
        <div class="duration-slider-container">
            <div class="duration-min-max">
                <span>15 min</span>
                <span>{{ $data['max_duration'] ?? 120 }} min</span>
            </div>
            
            <input
                type="range"
                name="reservation_minutes"
                id="reservation_minutes"
                class="duration-slider"
                @if(isset($data['max_duration']) && !empty($data['max_duration']))
                    max="{{ $data['max_duration'] }}"
                @else
                    max="120"
                @endif
                min="15"
                step="15"
                value="30"
                oninput="updateBothDurationDisplay(this.value)"
            >
            
            <div class="duration-value-display">
                <div class="duration-icon">
                    <i class="fas fa-clock text-sm"></i>
                </div>
                <span id="bothMinutesValue">30</span> minutes
            </div>
            
            <div class="duration-presets">
                <button type="button" class="duration-preset-btn" onclick="setBothDuration(15)">15 min</button>
                <button type="button" class="duration-preset-btn" onclick="setBothDuration(30)">30 min</button>
                <button type="button" class="duration-preset-btn" onclick="setBothDuration(45)">45 min</button>
                <button type="button" class="duration-preset-btn" onclick="setBothDuration(60)">1 heure</button>
                <button type="button" class="duration-preset-btn" onclick="setBothDuration(90)">1h30</button>
                <button type="button" class="duration-preset-btn" onclick="setBothDuration(120)">2 heures</button>
            </div>
        </div>
    </div>
    
    <div class="mb-6">
        <label for="reservation_kwh" class="block text-sm font-medium text-gray-700 mb-3">
            <i class="fas fa-bolt text-green-600 mr-2"></i>
            Énergie de recharge (kWh)
        </label>
        <div class="duration-slider-container">
            <div class="duration-min-max">
                <span>1 kWh</span>
                <span>{{ $data['max_kwh'] ?? 50 }} kWh</span>
            </div>
            
            <input
                type="range"
                name="reservation_kwh"
                id="reservation_kwh"
                class="duration-slider"
                @if(!empty($data['max_kwh']))
                    max="{{ $data['max_kwh'] }}"
                @else
                    max="50"
                @endif
                min="1"
                step="1"
                value="10"
                oninput="updateBothEnergyDisplay(this.value)"
            >
            
            <div class="duration-value-display">
                <div class="duration-icon">
                    <i class="fas fa-bolt text-sm"></i>
                </div>
                <span id="bothKwhValue">10</span> kWh
            </div>
            
            <div class="duration-presets">
                <button type="button" class="duration-preset-btn" onclick="setBothEnergy(5)">5 kWh</button>
                <button type="button" class="duration-preset-btn" onclick="setBothEnergy(10)">10 kWh</button>
                <button type="button" class="duration-preset-btn" onclick="setBothEnergy(15)">15 kWh</button>
                <button type="button" class="duration-preset-btn" onclick="setBothEnergy(20)">20 kWh</button>
                <button type="button" class="duration-preset-btn" onclick="setBothEnergy(30)">30 kWh</button>
                <button type="button" class="duration-preset-btn" onclick="setBothEnergy(50)">50 kWh</button>
            </div>
        </div>
    </div>
@else
    <div class="mb-4">
        <label for="reservation_value" class="block text-sm font-medium text-gray-700 mb-3">
            @if(($data['plan_type'] ?? '') === 'minute')
                <i class="fas fa-clock text-blue-600 mr-2"></i>
                Saisissez la durée de votre réservation en minutes
            @else
                <i class="fas fa-bolt text-green-600 mr-2"></i>
                Saisissez la quantité d'énergie souhaitée en kWh
            @endif
        </label>
        @if(($data['plan_type'] ?? '') === 'minute')
            <div class="duration-slider-container">
                <div class="duration-min-max">
                    <span>15 min</span>
                    <span>{{ $data['max_duration'] ?? 120 }} min</span>
                </div>
                
                <input
                    type="range"
                    name="reservation_value"
                    id="reservation_value"
                    class="duration-slider"
                    @if(!empty($data['max_duration']))
                        max="{{ $data['max_duration'] }}"
                    @else
                        max="120"
                    @endif
                    min="15"
                    step="15"
                    value="30"
                    oninput="updateDurationDisplay(this.value)"
                >
                
                <div class="duration-value-display">
                    <div class="duration-icon">
                        <i class="fas fa-clock text-sm"></i>
                    </div>
                    <span id="minutesValue">30</span> minutes
                </div>
                
                <div class="duration-presets">
                    <button type="button" class="duration-preset-btn" onclick="setDuration(15)">15 min</button>
                    <button type="button" class="duration-preset-btn" onclick="setDuration(30)">30 min</button>
                    <button type="button" class="duration-preset-btn" onclick="setDuration(45)">45 min</button>
                    <button type="button" class="duration-preset-btn" onclick="setDuration(60)">1 heure</button>
                    <button type="button" class="duration-preset-btn" onclick="setDuration(90)">1h30</button>
                    <button type="button" class="duration-preset-btn" onclick="setDuration(120)">2 heures</button>
                </div>
            </div>
        @else
            <div class="duration-slider-container">
                <div class="duration-min-max">
                    <span>1 kWh</span>
                    <span>{{ $data['max_kwh'] ?? 50 }} kWh</span>
                </div>
                
                <input
                    type="range"
                    name="reservation_value"
                    id="reservation_value"
                    class="duration-slider"
                    @if(!empty($data['max_kwh']))
                        max="{{ $data['max_kwh'] }}"
                    @else
                        max="50"
                    @endif
                    min="1"
                    step="1"
                    value="10"
                    oninput="updateEnergyDisplay(this.value)"
                >
                
                <div class="duration-value-display">
                    <div class="duration-icon">
                        <i class="fas fa-bolt text-sm"></i>
                    </div>
                    <span id="kwhValue">10</span> kWh
                </div>
                
                <div class="duration-presets">
                    <button type="button" class="duration-preset-btn" onclick="setEnergy(5)">5 kWh</button>
                    <button type="button" class="duration-preset-btn" onclick="setEnergy(10)">10 kWh</button>
                    <button type="button" class="duration-preset-btn" onclick="setEnergy(15)">15 kWh</button>
                    <button type="button" class="duration-preset-btn" onclick="setEnergy(20)">20 kWh</button>
                    <button type="button" class="duration-preset-btn" onclick="setEnergy(30)">30 kWh</button>
                    <button type="button" class="duration-preset-btn" onclick="setEnergy(50)">50 kWh</button>
                </div>
            </div>
        @endif
    </div>
@endif

                            <!-- Immediate Start Mode -->
                            <div class="mb-4">
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-bolt text-green-600 mr-2"></i>
                                        <div>
                                            <h4 class="text-sm font-medium text-green-800">Démarrage Immédiat</h4>
                                            <p class="text-xs text-green-600">Votre recharge commencera immédiatement après le paiement</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="guest_email" class="block text-sm font-medium text-gray-700 mb-1">Email (optionnel)</label>
                                <input type="email" name="guest_email" id="guest_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="votre.email@example.com">
                            </div>

                            <div class="mb-4">
                                <label for="guest_phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone (optionnel)</label>
                                <input type="tel" name="guest_phone" id="guest_phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="+212XXXXXXXXX">
                            </div>

                            <!-- Options de paiement -->
                            <div class="mb-6">
                                <label class="block text-lg font-bold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-credit-card text-blue-600 mr-3"></i>
                                    Méthode de paiement
                                </label>
                                
                                <div class="grid grid-cols-1 gap-3">
                                    <!-- Stripe Payment Option -->
                                    <div class="payment-option" data-payment-method="stripe">
                                        <input type="radio" name="payment_method" id="payment_stripe" value="stripe" class="sr-only" checked>
                                        <label for="payment_stripe" class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-500 hover:bg-green-50 transition-all duration-200">
                                            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3 payment-icon">
                                                <i class="fab fa-stripe text-white text-lg"></i>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">Stripe</div>
                                                <div class="text-sm text-gray-600">Carte de crédit sécurisée</div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- CMI Payment Option -->
                                    <div class="payment-option" data-payment-method="cmi">
                                        <input type="radio" name="payment_method" id="payment_cmi" value="cmi" class="sr-only">
                                        <label for="payment_cmi" class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-500 hover:bg-green-50 transition-all duration-200">
                                            <div class="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center mr-3 payment-icon">
                                                <i class="fas fa-credit-card text-white text-lg"></i>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">CMI</div>
                                                <div class="text-sm text-gray-600">Paiement local</div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- Prepaid Credit Balance Payment Option -->
                                    <div class="payment-option" data-payment-method="prepaid_credit">
                                        <input type="radio" name="payment_method" id="payment_prepaid_credit" value="prepaid_credit" class="sr-only">
                                        <label for="payment_prepaid_credit" class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-500 hover:bg-green-50 transition-all duration-200">
                                            <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-3 payment-icon">
                                                <i class="fas fa-wallet text-white text-lg"></i>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">Crédit Prépayé</div>
                                                <div class="text-sm text-gray-600">Paiement anticipé</div>
                                                <div class="text-xs text-green-600 font-medium" id="prepaid_balance_display">
                                                    Solde: {{ auth()->user() ? ($user_formatted_balance ?? '0.00 ' . ($data['currency'] ?? 'EUR')) : 'Non connecté' }}
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- Postpaid Credit Balance Payment Option -->
                                    <div class="payment-option" data-payment-method="postpaid_credit">
                                        <input type="radio" name="payment_method" id="payment_postpaid_credit" value="postpaid_credit" class="sr-only">
                                        <label for="payment_postpaid_credit" class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-500 hover:bg-green-50 transition-all duration-200">
                                            <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center mr-3 payment-icon">
                                                <i class="fas fa-credit-card text-white text-lg"></i>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">Crédit Postpayé</div>
                                                <div class="text-sm text-gray-600">Paiement après usage</div>
                                                <div class="text-xs text-purple-600 font-medium" id="postpaid_balance_display">
                                                    Solde: {{ auth()->user() ? ($user_formatted_balance ?? '0.00 ' . ($data['currency'] ?? 'EUR')) : 'Non connecté' }}
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Informations sur les modes de paiement -->
                                <div class="mt-4 space-y-3">
                                    <!-- Information sur le prépayé -->
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-3" id="prepaid_info" style="display: none;">
                                        <div class="flex items-start">
                                            <i class="fas fa-wallet text-green-600 mt-0.5 mr-2"></i>
                                            <div class="text-sm text-green-800">
                                                <strong>Mode Prépayé :</strong> Le montant estimé sera débité de votre solde avant la recharge. Si la consommation réelle est inférieure, la différence vous sera remboursée.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Information sur le postpayé -->
                                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-3" id="postpaid_info" style="display: none;">
                                        <div class="flex items-start">
                                            <i class="fas fa-credit-card text-purple-600 mt-0.5 mr-2"></i>
                                            <div class="text-sm text-purple-800">
                                                <strong>Mode Postpayé :</strong> Le montant réel sera débité de votre solde après la recharge, basé sur la consommation effective. Un seuil minimum est requis.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Informations de sécurité -->
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                        <div class="flex items-start">
                                            <i class="fas fa-shield-alt text-blue-600 mt-0.5 mr-2"></i>
                                            <div class="text-sm text-blue-800">
                                                <strong>Sécurité :</strong> Tous les paiements sont sécurisés et cryptés. Vos informations bancaires ne sont jamais stockées sur nos serveurs.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Estimation détaillée du coût -->
                            <div class="mb-6">
                                <label class="block text-lg font-bold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-calculator text-blue-600 mr-3"></i>
                                    Estimation détaillée du coût
                                </label>
                                
                                <div id="cost_details" class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 shadow-lg">
                                    <!-- État initial -->
                                    <div id="cost_initial_state" class="text-center py-8">
                                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="fas fa-calculator text-blue-600 text-2xl"></i>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Calcul en cours...</h3>
                                        <p class="text-gray-500 text-sm">Saisissez les détails de votre réservation pour voir l'estimation</p>
                                    </div>
                                    
                                    <!-- Détails du calcul -->
                                    <div id="cost_calculation_details" class="hidden">
                                        <div class="space-y-4 mb-6">
                                            <!-- Informations de base -->
                                            <div class="bg-white rounded-lg p-4 border border-blue-100">
                                                <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                                                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                                    Informations de base
                                                </h4>
                                                <div class="space-y-2 text-sm">
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Type de plan:</span>
                                                        <span id="plan_type_display" class="font-medium text-gray-800"></span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Durée/Énergie:</span>
                                                        <span id="reservation_value_display" class="font-medium text-gray-800"></span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Prix unitaire:</span>
                                                        <span id="unit_price_display" class="font-medium text-gray-800"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Détail des frais -->
                                            <div class="bg-white rounded-lg p-4 border border-blue-100">
                                                <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                                                    <i class="fas fa-receipt text-green-500 mr-2"></i>
                                                    Détail des frais
                                                </h4>
                                                <div id="fees_breakdown" class="space-y-2 text-sm">
                                                    <!-- Les frais seront injectés ici -->
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Résumé total -->
                                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="font-bold text-gray-800 text-lg">Total estimé</h4>
                                                    <p class="text-gray-600 text-sm">Prix final de votre réservation</p>
                                                </div>
                                                <div class="text-right">
                                                    <div id="estimatedCost" class="text-2xl font-bold text-green-600">0.00 {{ $data['currency'] }}</div>
                                                    <div class="text-xs text-gray-500">TVA incluse</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Informations supplémentaires -->
                                        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                            <div class="flex items-start">
                                                <i class="fas fa-info-circle text-yellow-600 mt-0.5 mr-2"></i>
                                                <div class="text-sm text-yellow-800">
                                                    <strong>Note :</strong> Ce montant est calculé selon les tarifs actuels. Le montant total est fixe et calculé selon la durée réelle de recharge.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 sm:py-3 px-3 sm:px-4 rounded-lg transition duration-200 flex items-center justify-center text-sm sm:text-base">
                                <i class="fas fa-calendar-check mr-2"></i>
                                Confirmer la Réservation
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-8">
        <div class="container mx-auto px-4 text-center">
            <div class="flex items-center justify-center space-x-3 mb-3">
                <div class="w-7 h-7 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-bolt text-white text-base"></i>
                </div>
                <h3 class="text-lg font-bold">Evon Power</h3>
            </div>
            <p class="text-gray-300 text-sm">Votre partenaire de confiance pour la recharge électrique</p>
            <div class="mt-3 flex justify-center space-x-4 text-xs text-gray-400">
                <a href="{{ route('guest-reservations.index') }}" class="hover:text-white transition duration-200">Mes Réservations</a>
                <a href="#" class="hover:text-white transition duration-200">Support</a>
                <a href="#" class="hover:text-white transition duration-200">Conditions</a>
                <a href="#" class="hover:text-white transition duration-200">Confidentialité</a>
            </div>
        </div>
    </footer>

    <!-- Popup d'alerte moderne -->
    <div id="customAlertOverlay" class="custom-alert-overlay">
        <div class="custom-alert">
            <div class="custom-alert-header">
                <div id="customAlertIcon" class="custom-alert-icon">
                    <i id="customAlertIconClass"></i>
                </div>
                <div id="customAlertTitle" class="custom-alert-title"></div>
                <div id="customAlertMessage" class="custom-alert-message"></div>
            </div>
            <div class="custom-alert-body">
                <div id="customAlertBodyContent"></div>
            </div>
            <div class="custom-alert-footer">
                <button id="customAlertPrimaryBtn" class="custom-alert-btn primary" style="display: none;"></button>
                <button id="customAlertSecondaryBtn" class="custom-alert-btn secondary" style="display: none;"></button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @php
        // Cacher Frais Admin / Frais Intégrateur / Net Opérateur pour les clients
        $user = auth()->user();
        $hideFeeBreakdownForClients = true;
        if ($user) {
            $isAdmin = $user->hasRole(['admin', 'super_admin']);
            $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
            $isPartnerOrOperator = in_array('partner', $userRolesLower) || in_array('operator', $userRolesLower);
            $isIntegrator = in_array('integrator', $userRolesLower);
            $hideFeeBreakdownForClients = !$isAdmin && !$isPartnerOrOperator && !$isIntegrator;
        }
    @endphp
    <!-- Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const hideFeeBreakdownForClients = @json($hideFeeBreakdownForClients);
        // Initialiser Stripe
        const stripe = Stripe('{{ config("services.stripe.key") }}');
        document.addEventListener('DOMContentLoaded', function() {
            const reservationForm = document.getElementById('reservationForm');
            const chargingPointId = "{{ $data['charging_point']->id }}";
            const costDetailsDiv = document.getElementById('cost_details');
            const planType = "{{ $data['plan_type'] ?? '' }}";
            const pricingPlanInput = document.getElementById('pricing_plan_id');
            const planCurrency = "{{ $data['plan_currency'] ?? 'EUR' }}";
            let activationFee = 0;
            @if(isset($data['activePricingPlan']) && isset($data['activePricingPlan']->activation_fee))
                activationFee = parseFloat("{{ $data['activePricingPlan']->activation_fee }}");
            @endif

            function updateCost() {
                let minutes = 0;
                let kwh = 0;
                let requestBody = {
                    charging_point_id: chargingPointId,
                    _token: '{{ csrf_token() }}', // Include CSRF token for POST request
                    pricing_plan_id: pricingPlanInput.value
                };

                if (planType === 'minute') {
                    const reservationInput = document.getElementById('reservation_value');
                    if (reservationInput) {
                        minutes = parseFloat(reservationInput.value) || 0;
                        requestBody.duration_minutes = minutes;
                    }
                } else if (planType === 'kwh') {
                    const reservationInput = document.getElementById('reservation_value');
                    if (reservationInput) {
                        kwh = parseFloat(reservationInput.value) || 0;
                        requestBody.energy_kwh = kwh;
                    }
                } else if (planType === 'both') {
                    const minutesInput = document.getElementById('reservation_minutes');
                    const kwhInput = document.getElementById('reservation_kwh');
                    if (minutesInput) {
                        minutes = parseFloat(minutesInput.value) || 0;
                        requestBody.duration_minutes = minutes;
                    }
                    if (kwhInput) {
                        kwh = parseFloat(kwhInput.value) || 0;
                        requestBody.energy_kwh = kwh;
                    }
                }

                // Make API call to calculate cost
                fetch(`{{ route('calculate-cost', $data['charging_point']->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(requestBody)
                })
                .then(response => {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.indexOf('application/json') !== -1) {
                        return response.json();
                    } else {
                        throw new Error('Réponse non JSON');
                    }
                })
                .then(data => {
                    if (data.success && data.estimations && data.estimations.cost !== undefined) {
                        let details = '';
                        if (activationFee > 0) {
                            details += `<div>Frais d’activation : <b>${activationFee.toFixed(2)} ${planCurrency}</b></div>`;
                        }
                        if (data.estimations.price_per_minute_cost !== undefined && data.estimations.price_per_minute_cost > 0) {
                            details += `<div>Coût par minute : <b>${data.estimations.price_per_minute_cost.toFixed(2)} ${planCurrency}</b></div>`;
                        }
                        if (data.estimations.price_per_kwh_cost !== undefined && data.estimations.price_per_kwh_cost > 0) {
                            details += `<div>Coût par kWh : <b>${data.estimations.price_per_kwh_cost.toFixed(2)} ${planCurrency}</b></div>`;
                        }
                        details += `<div class="mt-1 border-t pt-1 font-semibold">Total estimé : <span class="text-green-700">${data.estimations.cost.toFixed(2)} ${planCurrency}</span></div>`;
                        // Masquer l'état initial et afficher les détails
                        document.getElementById('cost_initial_state').classList.add('hidden');
                        document.getElementById('cost_calculation_details').classList.remove('hidden');
                        
                        // Mettre à jour les informations de base
                        const planTypeDisplay = document.getElementById('plan_type_display');
                        const reservationValueDisplay = document.getElementById('reservation_value_display');
                        const unitPriceDisplay = document.getElementById('unit_price_display');
                        
                        // Déterminer le type de plan et les valeurs
                        let planTypeText = '';
                        let reservationValue = '';
                        let unitPrice = '';
                        
                        if (planType === 'minute') {
                            const reservationInput = document.getElementById('reservation_value');
                            const minutes = parseFloat(reservationInput.value) || 0;
                            planTypeText = 'Tarification par minute';
                            reservationValue = `${minutes} minutes`;
                            unitPrice = `${data.estimations.price_per_minute || 0} ${planCurrency}/min`;
                        } else if (planType === 'kwh') {
                            const reservationInput = document.getElementById('reservation_value');
                            const kwh = parseFloat(reservationInput.value) || 0;
                            planTypeText = 'Tarification par kWh';
                            reservationValue = `${kwh} kWh`;
                            unitPrice = `${data.estimations.price_per_kwh || 0} ${planCurrency}/kWh`;
                        } else if (planType === 'both') {
                            const minutesInput = document.getElementById('reservation_minutes');
                            const kwhInput = document.getElementById('reservation_kwh');
                            const minutes = parseFloat(minutesInput.value) || 0;
                            const kwh = parseFloat(kwhInput.value) || 0;
                            planTypeText = 'Tarification mixte';
                            reservationValue = `${minutes} min / ${kwh} kWh`;
                            unitPrice = `${data.estimations.price_per_minute || 0} ${planCurrency}/min + ${data.estimations.price_per_kwh || 0} ${planCurrency}/kWh`;
                        }
                        
                        planTypeDisplay.textContent = planTypeText;
                        reservationValueDisplay.textContent = reservationValue;
                        unitPriceDisplay.textContent = unitPrice;
                        
                        // Mettre à jour le détail des frais
                        const feesBreakdown = document.getElementById('fees_breakdown');
                        let feesHTML = '';
                        
                        if (activationFee > 0) {
                            feesHTML += `
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-plug text-blue-500 mr-2"></i>
                                        <span class="text-gray-600">Frais d'activation</span>
                                    </div>
                                    <span class="font-semibold text-gray-800">${activationFee.toFixed(2)} ${planCurrency}</span>
                                </div>
                            `;
                        }
                        
                        // Ajouter les frais d'administration et d'intégrateur si disponibles (cachés pour les clients)
                        if (!hideFeeBreakdownForClients) {
                            if (data.estimations.admin_fees !== undefined && data.estimations.admin_fees > 0) {
                                feesHTML += `
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <div class="flex items-center">
                                            <i class="fas fa-crown text-red-500 mr-2"></i>
                                            <span class="text-gray-600">Frais d'administration</span>
                                        </div>
                                        <span class="font-semibold text-red-600">${data.estimations.admin_fees.toFixed(2)} ${planCurrency}</span>
                                    </div>
                                `;
                            }
                            
                            if (data.estimations.integrator_fees !== undefined && data.estimations.integrator_fees > 0) {
                                feesHTML += `
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <div class="flex items-center">
                                            <i class="fas fa-handshake text-blue-500 mr-2"></i>
                                            <span class="text-gray-600">Frais d'intégrateur</span>
                                        </div>
                                        <span class="font-semibold text-blue-600">${data.estimations.integrator_fees.toFixed(2)} ${planCurrency}</span>
                                    </div>
                                `;
                            }
                        }
                        
                        if (data.estimations.price_per_minute_cost !== undefined && data.estimations.price_per_minute_cost > 0) {
                            feesHTML += `
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-green-500 mr-2"></i>
                                        <span class="text-gray-600">Coût par minute</span>
                                    </div>
                                    <span class="font-semibold text-gray-800">${data.estimations.price_per_minute_cost.toFixed(2)} ${planCurrency}</span>
                                </div>
                            `;
                        }
                        
                        if (data.estimations.price_per_kwh_cost !== undefined && data.estimations.price_per_kwh_cost > 0) {
                            feesHTML += `
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                                        <span class="text-gray-600">Coût par kWh</span>
                                    </div>
                                    <span class="font-semibold text-gray-800">${data.estimations.price_per_kwh_cost.toFixed(2)} ${planCurrency}</span>
                                </div>
                            `;
                        }
                        
                        // Ajouter la TVA si disponible
                        if (data.estimations.vat_amount !== undefined && data.estimations.vat_amount > 0) {
                            feesHTML += `
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-percentage text-purple-500 mr-2"></i>
                                        <span class="text-gray-600">TVA (${data.estimations.vat_rate || 20}%)</span>
                                    </div>
                                    <span class="font-semibold text-gray-800">${data.estimations.vat_amount.toFixed(2)} ${planCurrency}</span>
                                </div>
                            `;
                        }
                        
                        feesBreakdown.innerHTML = feesHTML;
                        
                        // Mettre à jour le total
                        document.getElementById('estimatedCost').textContent = `${data.estimations.cost.toFixed(2)} ${planCurrency}`;
                    } else {
                        // Afficher l'erreur dans le nouveau format
                        document.getElementById('cost_initial_state').classList.remove('hidden');
                        document.getElementById('cost_calculation_details').classList.add('hidden');
                        
                        const initialState = document.getElementById('cost_initial_state');
                        initialState.innerHTML = `
                            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Erreur de calcul</h3>
                            <p class="text-gray-500 text-sm">${data.message || 'Réponse inattendue'}</p>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du calcul du coût:', error);
                    // Afficher l'erreur dans le nouveau format
                    document.getElementById('cost_initial_state').classList.remove('hidden');
                    document.getElementById('cost_calculation_details').classList.add('hidden');
                    
                    const initialState = document.getElementById('cost_initial_state');
                    initialState.innerHTML = `
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Erreur de connexion</h3>
                        <p class="text-gray-500 text-sm">Erreur lors du calcul du coût: ${error.message}</p>
                    `;
                });
            }

            // Event listeners for input changes
            document.querySelectorAll('input[name="selected_plan"]').forEach(radio => {
               radio.addEventListener('change', (event) => {
                   pricingPlanInput.value = event.target.value;
                   updateCost();
               });
            });

            if (planType === 'both') {
                const minutesInput = document.getElementById('reservation_minutes');
                const kwhInput = document.getElementById('reservation_kwh');
                if (minutesInput) minutesInput.addEventListener('input', updateCost);
                if (kwhInput) kwhInput.addEventListener('input', updateCost);
            } else {
                const reservationInput = document.getElementById('reservation_value');
                if (reservationInput) {
                    reservationInput.addEventListener('input', updateCost);
                }
            }

            // Prevent default form submission and handle via fetch
            reservationForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Stop the form from submitting normally

                // Vérifier qu'une méthode de paiement est sélectionnée
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
                if (!paymentMethod) {
                    showCustomAlert({
                        type: 'warning',
                        title: 'Méthode de paiement requise',
                        message: 'Veuillez sélectionner une méthode de paiement.',
                        primaryBtn: {
                            text: 'Compris',
                            action: () => hideCustomAlert()
                        }
                    });
                    return;
                }

                // Vérifier le solde pour les paiements par crédit
                @if(auth()->check())
                    if (paymentMethod.value === 'prepaid_credit' || paymentMethod.value === 'postpaid_credit') {
                        const userBalance = {{ $user_balance ?? 0 }};
                        const estimatedCost = parseFloat(document.getElementById('estimatedCost').textContent.match(/[\d,]+\.?\d*/)[0].replace(',', ''));
                        
                        if (paymentMethod.value === 'prepaid_credit' && userBalance < estimatedCost) {
                            showCustomAlert({
                                type: 'warning',
                                title: 'Solde insuffisant',
                                message: `Votre solde actuel (${userBalance.toFixed(2)} {{ $data['currency'] ?? 'EUR' }}) est insuffisant pour couvrir le coût estimé (${estimatedCost.toFixed(2)} {{ $data['currency'] ?? 'EUR' }}).`,
                                primaryBtn: {
                                    text: 'Recharger mon solde',
                                    action: () => window.location.href = '/wallet/recharge'
                                },
                                secondaryBtn: {
                                    text: 'Changer de méthode',
                                    action: () => hideCustomAlert()
                                }
                            });
                            return;
                        }
                        
                        if (paymentMethod.value === 'postpaid_credit') {
                            const minThreshold = 10.00; // Seuil minimum pour le postpayé
                            if (userBalance < minThreshold) {
                                showCustomAlert({
                                    type: 'warning',
                                    title: 'Solde insuffisant',
                                    message: `Pour utiliser le paiement postpayé, vous devez avoir un solde minimum de ${minThreshold} {{ $data['currency'] ?? 'EUR' }}. Votre solde actuel est de ${userBalance.toFixed(2)} {{ $data['currency'] ?? 'EUR' }}.`,
                                    primaryBtn: {
                                        text: 'Recharger mon solde',
                                        action: () => window.location.href = '/wallet/recharge'
                                    },
                                    secondaryBtn: {
                                        text: 'Changer de méthode',
                                        action: () => hideCustomAlert()
                                    }
                                });
                                return;
                            }
                        }
                    }
                @endif

                // Prepare the submission data
                let submitBody = {
                    charging_point_id: chargingPointId,
                    pricing_plan_id: pricingPlanInput.value,
                    payment_method: paymentMethod.value,
                    _token: '{{ csrf_token() }}'
                };

                // Add duration_minutes or energy_kwh based on plan type
                if (planType === 'minute') {
                    const reservationInput = document.getElementById('reservation_value');
                    if (reservationInput) {
                        const minutes = parseFloat(reservationInput.value) || 0;
                        if (minutes > 0) {
                            submitBody.duration_minutes = minutes;
                        }
                    }
                } else if (planType === 'kwh') {
                    const reservationInput = document.getElementById('reservation_value');
                    if (reservationInput) {
                        const kwh = parseFloat(reservationInput.value) || 0;
                        if (kwh > 0) {
                            submitBody.energy_kwh = kwh;
                        }
                    }
                } else if (planType === 'both') {
                    const minutesInput = document.getElementById('reservation_minutes');
                    const kwhInput = document.getElementById('reservation_kwh');
                    if (minutesInput) {
                        const minutes = parseFloat(minutesInput.value) || 0;
                        if (minutes > 0) {
                            submitBody.duration_minutes = minutes;
                        }
                    }
                    if (kwhInput) {
                        const kwh = parseFloat(kwhInput.value) || 0;
                        if (kwh > 0) {
                            submitBody.energy_kwh = kwh;
                        }
                    }
                }

                // Add reservation_type and reservation_value for backend (single-request credit payment)
                if (planType === 'minute' && submitBody.duration_minutes) {
                    submitBody.reservation_type = 'minute';
                    submitBody.reservation_value = submitBody.duration_minutes;
                } else if (planType === 'kwh' && submitBody.energy_kwh) {
                    submitBody.reservation_type = 'kwh';
                    submitBody.reservation_value = submitBody.energy_kwh;
                } else if (planType === 'both') {
                    submitBody.reservation_type = 'minute';
                    submitBody.reservation_value = submitBody.duration_minutes || submitBody.energy_kwh;
                }

                // Add optional fields
                const startTimeInput = document.getElementById('start_time');
                const guestEmailInput = document.getElementById('guest_email');
                const guestPhoneInput = document.getElementById('guest_phone');

                if (startTimeInput && startTimeInput.value) {
                    let startTime = startTimeInput.value;
                    // Si la valeur est juste une heure (ex: 06:00), on complète avec la date du jour
                    if (/^\d{2}:\d{2}$/.test(startTime)) {
                        const today = new Date();
                        const yyyy = today.getFullYear();
                        const mm = String(today.getMonth() + 1).padStart(2, '0');
                        const dd = String(today.getDate()).padStart(2, '0');
                        startTime = `${yyyy}-${mm}-${dd} ${startTime}:00`;
                    }
                    submitBody.start_time = startTime;
                }
                if (guestEmailInput && guestEmailInput.value) {
                    submitBody.guest_email = guestEmailInput.value;
                }
                if (guestPhoneInput && guestPhoneInput.value) {
                    submitBody.guest_phone = guestPhoneInput.value;
                }

                // Validate that at least one reservation value is provided
                if (!submitBody.duration_minutes && !submitBody.energy_kwh) {
                    showCustomAlert({
                        type: 'warning',
                        title: 'Champ requis',
                        message: 'Veuillez spécifier une durée ou une quantité d\'énergie pour la réservation.',
                        primaryBtn: {
                            text: 'Compris',
                            action: () => hideCustomAlert()
                        }
                    });
                    return;
                }

                // Validate that at least one contact method is provided
                if (!submitBody.guest_email && !submitBody.guest_phone) {
                    showCustomAlert({
                        type: 'warning',
                        title: 'Contact requis',
                        message: 'Veuillez fournir un email ou un numéro de téléphone.',
                        primaryBtn: {
                            text: 'Compris',
                            action: () => hideCustomAlert()
                        }
                    });
                    return;
                }

                // Debug: Log the data being sent
                console.log('Submitting reservation data:', submitBody);

                // Gérer les différents types de paiement
                if (paymentMethod.value === 'stripe') {
                    handleStripePayment(submitBody);
                } else if (paymentMethod.value === 'cmi') {
                    handleCMIPayment(submitBody);
                } else if (paymentMethod.value === 'prepaid_credit' || paymentMethod.value === 'postpaid_credit') {
                    // Paiement par solde : une seule requête (création + paiement), redirection directe sans alerte
                    submitReservation(submitBody, true);
                } else {
                    // Fallback pour les autres méthodes
                    submitReservation(submitBody, false);
                }
            });

            // Fonction pour gérer le paiement Stripe
            function handleStripePayment(submitBody) {
                // D'abord créer la réservation
                fetch(reservationForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(submitBody)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.reservation_id) {
                        // Initier le paiement Stripe
                        return fetch(`/reservations/${data.reservation_id}/pay/stripe`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({})
                        });
                    } else {
                        throw new Error(data.message || 'Erreur lors de la création de la réservation');
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.client_secret) {
                        // Rediriger vers Stripe Checkout
                        return stripe.confirmCardPayment(data.client_secret, {
                            payment_method: {
                                card: {
                                    // Les détails de la carte seront collectés par Stripe
                                }
                            }
                        });
                    } else {
                        throw new Error(data.error || 'Erreur lors de l\'initialisation du paiement Stripe');
                    }
                })
                .then(result => {
                    if (result.error) {
                        showCustomAlert({
                            type: 'error',
                            title: 'Erreur de paiement',
                            message: result.error.message,
                            primaryBtn: {
                                text: 'OK',
                                action: () => hideCustomAlert()
                            }
                        });
                    } else {
                        showCustomAlert({
                            type: 'success',
                            title: 'Paiement réussi !',
                            message: 'Votre réservation a été confirmée et payée.',
                            primaryBtn: {
                                text: 'OK',
                                action: () => window.location.href = '/reservations/thank-you/' + data.reservation_id
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Erreur Stripe:', error);
                    showCustomAlert({
                        type: 'error',
                        title: 'Erreur de paiement',
                        message: 'Une erreur est survenue lors du paiement Stripe.',
                        primaryBtn: {
                            text: 'OK',
                            action: () => hideCustomAlert()
                        }
                    });
                });
            }

            // Fonction pour gérer le paiement CMI
            function handleCMIPayment(submitBody) {
                // D'abord créer la réservation
                fetch(reservationForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(submitBody)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.reservation_id) {
                        // Initier le paiement CMI
                        return fetch(`/reservations/${data.reservation_id}/pay/cmi`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({})
                        });
                    } else {
                        throw new Error(data.message || 'Erreur lors de la création de la réservation');
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.redirect_url) {
                        // Rediriger vers CMI
                        window.location.href = data.redirect_url;
                    } else {
                        throw new Error(data.error || 'Erreur lors de l\'initialisation du paiement CMI');
                    }
                })
                .catch(error => {
                    console.error('Erreur CMI:', error);
                    showCustomAlert({
                        type: 'error',
                        title: 'Erreur de paiement',
                        message: 'Une erreur est survenue lors du paiement CMI.',
                        primaryBtn: {
                            text: 'OK',
                            action: () => hideCustomAlert()
                        }
                    });
                });
            }

            // Fonction pour gérer le paiement par crédit prépayé
            function handlePrepaidCreditPayment(submitBody) {
                // Vérifier si l'utilisateur est connecté
                @if(!auth()->check())
                    showCustomAlert({
                        type: 'warning',
                        title: 'Connexion requise',
                        message: 'Vous devez être connecté pour utiliser le paiement par crédit.',
                        primaryBtn: {
                            text: 'Se connecter',
                            action: () => window.location.href = '/login'
                        }
                    });
                    return;
                @endif

                // Ajouter le mode de paiement au body
                submitBody.payment_mode = 'prepaid';
                
                // D'abord créer la réservation
                fetch(reservationForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(submitBody)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.reservation_id) {
                        // Traiter le paiement prépayé
                        return fetch(`/reservations/${data.reservation_id}/pay/prepaid-credit`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({})
                        });
                    } else {
                        throw new Error(data.message || 'Erreur lors de la création de la réservation');
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const balance = data.remaining_balance ?? 0;
                        const formatted = data.formatted_balance ?? (balance.toFixed(2) + ' EUR');
                        document.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                            detail: { balance: balance, formatted: formatted, remaining_balance: balance, currency: 'EUR' },
                            bubbles: true
                        }));
                        window.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                            detail: { balance: balance, formatted: formatted, remaining_balance: balance, currency: 'EUR' },
                            bubbles: true
                        }));
                        showCustomAlert({
                            type: 'success',
                            title: 'Paiement prépayé réussi !',
                            message: 'Votre réservation a été confirmée et le montant estimé a été débité de votre solde.',
                            primaryBtn: {
                                text: 'OK',
                                action: () => window.location.href = '/reservations/thank-you/' + data.reservation_id
                            }
                        });
                    } else {
                        throw new Error(data.error || 'Erreur lors du paiement prépayé');
                    }
                })
                .catch(error => {
                    console.error('Erreur paiement prépayé:', error);
                    showCustomAlert({
                        type: 'error',
                        title: 'Erreur de paiement',
                        message: 'Une erreur est survenue lors du paiement prépayé: ' + error.message,
                        primaryBtn: {
                            text: 'OK',
                            action: () => hideCustomAlert()
                        }
                    });
                });
            }

            // Fonction pour gérer le paiement par crédit postpayé
            function handlePostpaidCreditPayment(submitBody) {
                // Vérifier si l'utilisateur est connecté
                @if(!auth()->check())
                    showCustomAlert({
                        type: 'warning',
                        title: 'Connexion requise',
                        message: 'Vous devez être connecté pour utiliser le paiement par crédit.',
                        primaryBtn: {
                            text: 'Se connecter',
                            action: () => window.location.href = '/login'
                        }
                    });
                    return;
                @endif

                // Ajouter le mode de paiement au body
                submitBody.payment_mode = 'postpaid';
                
                // D'abord créer la réservation
                fetch(reservationForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(submitBody)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.reservation_id) {
                        // Traiter le paiement postpayé
                        return fetch(`/reservations/${data.reservation_id}/pay/postpaid-credit`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({})
                        });
                    } else {
                        throw new Error(data.message || 'Erreur lors de la création de la réservation');
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const balance = data.remaining_balance ?? data.current_balance ?? 0;
                        const formatted = data.formatted_balance ?? (balance.toFixed(2) + ' EUR');
                        document.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                            detail: { balance: balance, formatted: formatted, remaining_balance: balance, currency: 'EUR' },
                            bubbles: true
                        }));
                        window.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                            detail: { balance: balance, formatted: formatted, remaining_balance: balance, currency: 'EUR' },
                            bubbles: true
                        }));
                        showCustomAlert({
                            type: 'success',
                            title: 'Réservation postpayée confirmée !',
                            message: 'Votre réservation a été confirmée. Le montant réel sera débité de votre solde après la recharge.',
                            primaryBtn: {
                                text: 'OK',
                                action: () => window.location.href = '/reservations/thank-you/' + data.reservation_id
                            }
                        });
                    } else {
                        throw new Error(data.error || 'Erreur lors du paiement postpayé');
                    }
                })
                .catch(error => {
                    console.error('Erreur paiement postpayé:', error);
                    showCustomAlert({
                        type: 'error',
                        title: 'Erreur de paiement',
                        message: 'Une erreur est survenue lors du paiement postpayé: ' + error.message,
                        primaryBtn: {
                            text: 'OK',
                            action: () => hideCustomAlert()
                        }
                    });
                });
            }

            // Fonction de fallback pour les autres méthodes de paiement
            // autoRedirectCredit: true = paiement par solde, redirection directe vers thank-you sans alerte
            function submitReservation(submitBody, autoRedirectCredit) {
                fetch(reservationForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(submitBody)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (autoRedirectCredit && data.reservation_id) {
                            // Mise à jour solde avant redirection (évite affichage obsolète sur thank-you)
                            if (data.remaining_balance !== undefined || data.formatted_balance) {
                                const balance = data.remaining_balance !== undefined ? data.remaining_balance : parseFloat(data.formatted_balance) || 0;
                                const formatted = data.formatted_balance || (balance.toFixed(2) + ' EUR');
                                document.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                                    detail: { balance: balance, formatted: formatted, remaining_balance: balance, currency: 'EUR' },
                                    bubbles: true
                                }));
                                window.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                                    detail: { balance: balance, formatted: formatted, remaining_balance: balance, currency: 'EUR' },
                                    bubbles: true
                                }));
                                sessionStorage.setItem('evon_last_balance', formatted);
                            }
                            window.location.href = '{{ url("/reservations/thank-you") }}/' + data.reservation_id;
                            return;
                        }
                        showCustomAlert({
                            type: 'success',
                            title: 'Réservation réussie !',
                            message: 'Votre session a été réservée avec succès.',
                            primaryBtn: {
                                text: 'OK',
                                action: () => window.location.reload()
                            }
                        });
                    } else {
                        let errorMessage = 'Erreur lors de la réservation';
                        if (data.message) {
                            errorMessage = data.message;
                        }
                        if (data.errors) {
                            errorMessage += '\n' + Object.values(data.errors).flat().join('\n');
                        }
                        
                        // Gestion spéciale pour l'erreur de limite
                        if (data.error === 'limit_exceeded') {
                            showCustomAlert({
                                type: 'error',
                                title: 'Limite dépassée',
                                message: errorMessage,
                                bodyContent: 'Veuillez réduire la durée ou la quantité d\'énergie de votre réservation.',
                                primaryBtn: {
                                    text: 'Compris',
                                    action: () => hideCustomAlert()
                                }
                            });
                        } else {
                            showCustomAlert({
                                type: 'error',
                                title: 'Erreur de réservation',
                                message: errorMessage,
                                primaryBtn: {
                                    text: 'OK',
                                    action: () => hideCustomAlert()
                                }
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Erreur AJAX:', error);
                    showCustomAlert({
                        type: 'error',
                        title: 'Erreur de connexion',
                        message: 'Une erreur est survenue lors de la réservation.',
                        bodyContent: 'Veuillez vérifier votre connexion internet et réessayer.',
                        primaryBtn: {
                            text: 'Réessayer',
                            action: () => hideCustomAlert()
                        }
                    });
                });
            }

            });

            // Initial load
            updateCost(); // Call updateCost on initial load
            
            // Initialiser l'état des options de paiement
            function initializePaymentOptions() {
                document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
                    if (radio.checked) {
                        radio.closest('.payment-option').classList.add('active');
                    }
                });
            }
            
            // Gérer la sélection des options de paiement
            document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    // Retirer la classe active de toutes les options
                    document.querySelectorAll('.payment-option').forEach(option => {
                        option.classList.remove('active');
                    });
                    
                    // Masquer toutes les informations de mode de paiement
                    document.getElementById('prepaid_info').style.display = 'none';
                    document.getElementById('postpaid_info').style.display = 'none';
                    
                    // Ajouter la classe active à l'option sélectionnée
                    if (this.checked) {
                        this.closest('.payment-option').classList.add('active');
                        
                        // Afficher l'information correspondante
                        if (this.value === 'prepaid_credit') {
                            document.getElementById('prepaid_info').style.display = 'block';
                        } else if (this.value === 'postpaid_credit') {
                            document.getElementById('postpaid_info').style.display = 'block';
                        }
                    }
                });
            });
            
            // Gérer le clic sur les labels pour sélectionner les options
            document.querySelectorAll('.payment-option label').forEach(label => {
                label.addEventListener('click', function(e) {
                    e.preventDefault();
                    const radio = document.getElementById(this.getAttribute('for'));
                    if (radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                    }
                });
            });
            
            // Initialiser les options de paiement
            initializePaymentOptions();
            
            // Fonction pour mettre à jour l'affichage du solde
            function updateBalanceDisplay() {
                @if(auth()->check())
                    const balance = {{ $user_balance ?? 0 }};
                    const currency = '{{ $data['currency'] ?? 'EUR' }}';
                    const formattedBalance = balance.toFixed(2) + ' ' + currency;
                    
                    document.getElementById('prepaid_balance_display').textContent = 'Solde: ' + formattedBalance;
                    document.getElementById('postpaid_balance_display').textContent = 'Solde: ' + formattedBalance;
                @endif
            }
            
            // Mettre à jour l'affichage du solde au chargement
            updateBalanceDisplay();
        });

        // Fonctions pour le nouveau design du curseur de durée
        function updateDurationDisplay(value) {
            const minutesValue = document.getElementById('minutesValue');
            if (minutesValue) {
                minutesValue.textContent = value;
            }
            
            // Mettre à jour l'état actif des boutons de présélection
            updatePresetButtons(value);
            
            // Déclencher le calcul du coût
            if (typeof updateCost === 'function') {
                updateCost();
            }
        }

        function setDuration(minutes) {
            const slider = document.getElementById('reservation_value');
            if (slider) {
                slider.value = minutes;
                updateDurationDisplay(minutes);
            }
        }

        function updatePresetButtons(selectedValue) {
            const presetButtons = document.querySelectorAll('.duration-preset-btn');
            presetButtons.forEach(btn => {
                btn.classList.remove('active');
                const btnValue = parseInt(btn.textContent.match(/\d+/)[0]);
                if (btn.textContent.includes('heure') || btn.textContent.includes('h')) {
                    // Convertir les heures en minutes
                    if (btn.textContent.includes('1h30')) {
                        btnValue = 90;
                    } else {
                        btnValue = btnValue * 60;
                    }
                }
                if (btnValue == selectedValue) {
                    btn.classList.add('active');
                }
            });
        }

        // Fonctions pour l'affichage de l'énergie
        function updateEnergyDisplay(value) {
            const kwhValue = document.getElementById('kwhValue');
            if (kwhValue) {
                kwhValue.textContent = value;
            }
            
            // Mettre à jour l'état actif des boutons de présélection
            updateEnergyPresetButtons(value);
            
            // Déclencher le calcul du coût
            if (typeof updateCost === 'function') {
                updateCost();
            }
        }

        function setEnergy(kwh) {
            const slider = document.getElementById('reservation_value');
            if (slider) {
                slider.value = kwh;
                updateEnergyDisplay(kwh);
            }
        }

        function updateEnergyPresetButtons(selectedValue) {
            const presetButtons = document.querySelectorAll('.duration-preset-btn');
            presetButtons.forEach(btn => {
                btn.classList.remove('active');
                const btnValue = parseInt(btn.textContent.match(/\d+/)[0]);
                if (btnValue == selectedValue) {
                    btn.classList.add('active');
                }
            });
        }

        // Fonctions pour le plan "both" (durée et énergie)
        function updateBothDurationDisplay(value) {
            const minutesValue = document.getElementById('bothMinutesValue');
            if (minutesValue) {
                minutesValue.textContent = value;
            }
            
            // Mettre à jour l'état actif des boutons de présélection pour la durée
            updateBothDurationPresetButtons(value);
            
            // Déclencher le calcul du coût
            if (typeof updateCost === 'function') {
                updateCost();
            }
        }

        function updateBothEnergyDisplay(value) {
            const kwhValue = document.getElementById('bothKwhValue');
            if (kwhValue) {
                kwhValue.textContent = value;
            }
            
            // Mettre à jour l'état actif des boutons de présélection pour l'énergie
            updateBothEnergyPresetButtons(value);
            
            // Déclencher le calcul du coût
            if (typeof updateCost === 'function') {
                updateCost();
            }
        }

        function setBothDuration(minutes) {
            const slider = document.getElementById('reservation_minutes');
            if (slider) {
                slider.value = minutes;
                updateBothDurationDisplay(minutes);
            }
        }

        function setBothEnergy(kwh) {
            const slider = document.getElementById('reservation_kwh');
            if (slider) {
                slider.value = kwh;
                updateBothEnergyDisplay(kwh);
            }
        }

        function updateBothDurationPresetButtons(selectedValue) {
            const container = document.getElementById('reservation_minutes').closest('.duration-slider-container');
            const presetButtons = container.querySelectorAll('.duration-preset-btn');
            presetButtons.forEach(btn => {
                btn.classList.remove('active');
                const btnText = btn.textContent;
                let btnValue = parseInt(btnText.match(/\d+/)[0]);
                
                if (btnText.includes('heure') || btnText.includes('h')) {
                    if (btnText.includes('1h30')) {
                        btnValue = 90;
                    } else {
                        btnValue = btnValue * 60;
                    }
                }
                
                if (btnValue == selectedValue) {
                    btn.classList.add('active');
                }
            });
        }

        function updateBothEnergyPresetButtons(selectedValue) {
            const container = document.getElementById('reservation_kwh').closest('.duration-slider-container');
            const presetButtons = container.querySelectorAll('.duration-preset-btn');
            presetButtons.forEach(btn => {
                btn.classList.remove('active');
                const btnValue = parseInt(btn.textContent.match(/\d+/)[0]);
                if (btnValue == selectedValue) {
                    btn.classList.add('active');
                }
            });
        }

        // Initialiser l'état actif du bouton par défaut
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                // Détecter le type de plan et initialiser le bon bouton
                const planType = "{{ $data['plan_type'] ?? '' }}";
                if (planType === 'minute') {
                    updatePresetButtons(30);
                } else if (planType === 'kwh') {
                    updateEnergyPresetButtons(10);
                } else if (planType === 'both') {
                    updateBothDurationPresetButtons(30);
                    updateBothEnergyPresetButtons(10);
                }
            }, 100);
        });

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
            icon.className = `custom-alert-icon ${options.type}`;
            
            // Configuration de l'icône selon le type
            switch(options.type) {
                case 'success':
                    iconClass.className = 'fas fa-check';
                    break;
                case 'error':
                    iconClass.className = 'fas fa-exclamation-triangle';
                    break;
                case 'warning':
                    iconClass.className = 'fas fa-exclamation-circle';
                    break;
                default:
                    iconClass.className = 'fas fa-info-circle';
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
            overlay.classList.add('show');
            
            // Empêcher le scroll du body
            document.body.style.overflow = 'hidden';
        }

        function hideCustomAlert() {
            const overlay = document.getElementById('customAlertOverlay');
            overlay.classList.remove('show');
            
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
    </script>
@endpush