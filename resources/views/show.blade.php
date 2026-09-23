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
                        
                        <div class="flex flex-col md:items-end">
                            <div class="mb-2">
                                @if($chargingPoint->status == 'online')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-green-500"></span>
                                        En ligne
                                    </span>
                                @elseif($chargingPoint->status == 'offline')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-red-500"></span>
                                        Hors ligne
                                    </span>
                                @elseif($chargingPoint->status == 'maintenance')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-yellow-500"></span>
                                        Maintenance
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-gray-500"></span>
                                        {{ ucfirst($chargingPoint->status) }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="{{ route('charging-points.edit', $chargingPoint->id) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    Modifier
                                </a>
                                <button type="button" id="status-toggle" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                    Changer le statut
                                </button>
                                <a href="{{ $publicReservationUrl }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white hover:bg-green-700 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Réserver
                                </a>
                                
                                <!-- Bouton d'information sur les parts - Masqué pour les opérateurs et partenaires -->
                                @if(auth()->check() && !auth()->user()->hasRole('operator') && !auth()->user()->hasRole('partner'))
                                <button type="button" id="parts-info-btn" class="inline-flex items-center px-4 py-2 border border-blue-300 shadow-sm text-sm font-medium rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors" onclick="showPartsInfoModal()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Détails des Parts
                                </button>
                                @endif

                            </div>
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

                <!-- 3D Model Viewer -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Modèle 3D de la Borne</h3>
                            <p class="text-sm text-gray-600 mt-1">Visualisation interactive du point de charge</p>
                        </div>
                        <div class="flex space-x-2">
                            <button id="reset-camera" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Réinitialiser
                            </button>
                            <button id="toggle-rotation" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Rotation
                            </button>
                        </div>
                    </div>
                    
                    <div class="relative">
                        <canvas id="charging-point-3d" class="w-full h-96 bg-gray-100 dark:bg-gray-800 rounded-lg"></canvas>
                    </div>
                </div>

                <!-- Energy consumption chart -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Consommation d'énergie</h3>
                            <p class="text-sm text-gray-600 mt-1">Évolution de la consommation sur 24h</p>
                        </div>
                    </div>
                    <div class="h-64 w-full">
                        <!-- Chart placeholder - would be replaced with a real chart in implementation -->
                        <div id="energy-chart" class="w-full h-full">
                            <svg class="w-full h-full" viewBox="0 0 800 200">
                                <path d="M0,150 C100,100 150,180 200,150 C250,120 300,180 350,150 C400,120 450,100 500,50 C550,0 600,50 650,100 C700,150 750,120 800,150" stroke="#10B981" stroke-width="2" fill="none" />
                                <path d="M0,150 C100,100 150,180 200,150 C250,120 300,180 350,150 C400,120 450,100 500,50 C550,0 600,50 650,100 C700,150 750,120 800,150 L800,200 L0,200 Z" fill="url(#gradient)" fill-opacity="0.2" />
                                <defs>
                                    <linearGradient id="gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" stop-color="#10B981" />
                                        <stop offset="100%" stop-color="#10B981" stop-opacity="0" />
                                    </linearGradient>
                                </defs>

                                <!-- Chart labels -->
                                <text x="10" y="160" font-size="12" fill="#6B7280">10 kWh</text>
                                <text x="10" y="110" font-size="12" fill="#6B7280">15 kWh</text>
                                <text x="10" y="60" font-size="12" fill="#6B7280">20 kWh</text>
                                
                                <!-- X-axis labels -->
                                <text x="0" y="190" font-size="10" fill="#6B7280">00:00</text>
                                <text x="200" y="190" font-size="10" fill="#6B7280">06:00</text>
                                <text x="400" y="190" font-size="10" fill="#6B7280">12:00</text>
                                <text x="600" y="190" font-size="10" fill="#6B7280">18:00</text>
                                <text x="790" y="190" font-size="10" fill="#6B7280">24:00</text>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Station Information -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Informations de la Station</h3>
                            <p class="text-sm text-gray-600 mt-1">Détails techniques et configuration</p>
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="mr-4">
                                        <svg class="h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xl font-semibold text-gray-900">Station {{ $chargingPoint->name }}</h4>
                                        <p class="text-sm text-gray-600 mt-1">{{ $chargingPoint->power_output ?? 'N/A' }} kW • {{ $chargingPoint->model ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div>
                                    @php
                                        // Map status values to display text and CSS classes
                                        $statusMap = [
                                            'online' => ['text' => 'En ligne', 'class' => 'bg-green-100 text-green-800'],
                                            'offline' => ['text' => 'Hors ligne', 'class' => 'bg-red-100 text-red-800'],
                                            'maintenance' => ['text' => 'Maintenance', 'class' => 'bg-yellow-100 text-yellow-800'],
                                            'charging' => ['text' => 'En charge', 'class' => 'bg-blue-100 text-blue-800'],
                                            'available' => ['text' => 'Disponible', 'class' => 'bg-green-100 text-green-800'],
                                        ];
                                        $currentStatus = strtolower($chargingPoint->status ?? '');
                                        $defaultStatus = ['text' => $chargingPoint->status ?? 'Inconnu', 'class' => 'bg-gray-100 text-gray-800'];
                                        $statusInfo = $statusMap[$currentStatus] ?? $defaultStatus;
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusInfo['class'] }}">
                                        <span class="h-2 w-2 mr-2 rounded-full {{ str_contains($statusInfo['class'], 'green') ? 'bg-green-500' : (str_contains($statusInfo['class'], 'red') ? 'bg-red-500' : (str_contains($statusInfo['class'], 'yellow') ? 'bg-yellow-500' : (str_contains($statusInfo['class'], 'blue') ? 'bg-blue-500' : 'bg-gray-500'))) }}"></span>
                                        {{ $statusInfo['text'] }}
                                    </span>
                                </div>
                            </div>
                            @if($chargingPoint->pricingPlan)
                            <div class="mt-4 p-4 bg-white rounded-lg border border-gray-200">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900">Plan tarifaire: {{ $chargingPoint->pricingPlan->name }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Reservation Types Section -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Types de Réservation Disponibles</h3>
                            <p class="text-sm text-gray-600 mt-1">Options de réservation selon le plan tarifaire</p>
                        </div>
                    </div>
                    
                    @php
                        // Determine available reservation types based on pricing plan
                        $availableTypes = [];
                        $pricingPlan = $chargingPoint->pricingPlan ?? $pricingPlan ?? null;
                        
                        if ($pricingPlan) {
                            $rateType = strtolower($pricingPlan->rate_type ?? '');
                            
                            // Check if the plan has kWh pricing
                            $hasKwhPricing = !is_null($pricingPlan->price_per_kwh) && $pricingPlan->price_per_kwh > 0;
                            
                            // Check if the plan has minute pricing
                            $hasMinutePricing = !is_null($pricingPlan->price_per_minute) && $pricingPlan->price_per_minute > 0;
                            
                            switch ($rateType) {
                                case 'time':
                                case 'minute':
                                    if ($hasMinutePricing) {
                                        $availableTypes[] = 'minute';
                                    }
                                    break;
                                case 'energy':
                                case 'kwh':
                                    if ($hasKwhPricing) {
                                        $availableTypes[] = 'kwh';
                                    }
                                    break;
                                case 'mixed':
                                case 'both':
                                    if ($hasKwhPricing) {
                                        $availableTypes[] = 'kwh';
                                    }
                                    if ($hasMinutePricing) {
                                        $availableTypes[] = 'minute';
                                    }
                                    break;
                                default:
                                    // Default to both if rate_type is not specified, but only if pricing exists
                                    if ($hasKwhPricing) {
                                        $availableTypes[] = 'kwh';
                                    }
                                    if ($hasMinutePricing) {
                                        $availableTypes[] = 'minute';
                                    }
                                    break;
                            }
                        } else {
                            // If no pricing plan, show both types
                            $availableTypes[] = 'kwh';
                            $availableTypes[] = 'minute';
                        }
                    @endphp
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                        @if(in_array('kwh', $availableTypes))
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-6 hover:shadow-lg transition-all duration-200">
                            <div class="flex items-center mb-4">
                                <div class="mr-4">
                                    <div class="bg-blue-500 rounded-lg p-3">
                                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-xl font-semibold text-gray-900">Réservation par Énergie</h4>
                                    <p class="text-sm text-gray-600">kWh (kilowattheures)</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-700 mb-4">
                                Réservez une quantité d'énergie spécifique. Idéal pour les utilisateurs qui connaissent leur besoin énergétique.
                            </p>
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center justify-between text-sm bg-white rounded-lg p-3">
                                    <span class="text-gray-600">Prix par kWh:</span>
                                    <span class="font-semibold text-blue-600">
                                        @if($pricingPlan && $pricingPlan->price_per_kwh)
                                            {{ number_format($pricingPlan->price_per_kwh, 2) }} {{ $pricingPlan->currency ?? 'EUR' }}
                                        @else
                                            Variable
                                        @endif
                                    </span>
                                </div>
                                @if($pricingPlan && $pricingPlan->activation_fee)
                                <div class="flex items-center justify-between text-sm bg-white rounded-lg p-3">
                                    <span class="text-gray-600">Frais d'activation:</span>
                                    <span class="font-semibold text-blue-600">
                                        {{ number_format($pricingPlan->activation_fee, 2) }} {{ $pricingPlan->currency ?? 'EUR' }}
                                    </span>
                                </div>
                                @endif
                                @if($chargingPoint->power_output && $pricingPlan && $pricingPlan->max_duration)
                                <div class="flex items-center justify-between text-sm bg-white rounded-lg p-3">
                                    <span class="text-gray-600">Énergie max:</span>
                                    <span class="font-semibold text-blue-600">
                                        {{ number_format($chargingPoint->power_output * ($pricingPlan->max_duration / 60), 1) }} kWh
                                    </span>
                                </div>
                                @endif
                                
                                <!-- Prix estimés pour différentes quantités -->
                                @if($pricingPlan && $pricingPlan->price_per_kwh)
                                <div class="bg-white rounded-lg p-3 border border-blue-200">
                                    <div class="text-sm font-medium text-gray-700 mb-2">Prix estimés par quantité :</div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 lg:gap-3 text-xs">
                                        @php
                                            $energyOptions = [5, 10, 20, 30, 50, 75];
                                            $maxEnergy = $chargingPoint->power_output && $pricingPlan->max_duration ? 
                                                $chargingPoint->power_output * ($pricingPlan->max_duration / 60) : 100;
                                            
                                            // Filtrer les options qui ne dépassent pas la limite
                                            $energyOptions = array_filter($energyOptions, function($energy) use ($maxEnergy) {
                                                return $energy <= $maxEnergy;
                                            });
                                            
                                            // Ajouter l'énergie maximale si elle n'est pas dans la liste
                                            if (!in_array($maxEnergy, $energyOptions) && $maxEnergy > 0) {
                                                $energyOptions[] = round($maxEnergy, 1);
                                            }
                                            
                                            // Trier les options
                                            sort($energyOptions);
                                        @endphp
                                        
                                        @foreach($energyOptions as $energy)
                                            @php
                                                $energyCost = $energy * $pricingPlan->price_per_kwh;
                                                $totalCost = $energyCost + ($pricingPlan->activation_fee ?? 0);
                                                $estimatedDuration = $chargingPoint->power_output ? round(($energy / $chargingPoint->power_output) * 60) : 0;
                                            @endphp
                                            <div class="bg-blue-50 rounded p-2 text-center">
                                                <div class="font-semibold text-blue-800">{{ $energy }} kWh</div>
                                                <div class="text-blue-600">{{ number_format($totalCost, 2) }} {{ $pricingPlan->currency ?? 'EUR' }}</div>
                                                @if($estimatedDuration > 0)
                                                <div class="text-blue-500 text-xs">{{ $estimatedDuration }} min</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="flex space-x-3">
                                @if(in_array('kwh', $availableTypes))
                                <a href="{{ $publicReservationUrl }}?type=kwh" 
                                   class="flex-1 bg-blue-600 text-white text-center py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors text-sm font-semibold shadow-sm">
                                    Réserver par kWh
                                </a>
                                @else
                                <button disabled 
                                        class="flex-1 bg-gray-400 text-white text-center py-3 px-4 rounded-lg cursor-not-allowed text-sm font-semibold shadow-sm">
                                    Réserver par kWh (Non disponible)
                                </button>
                                @endif
                                <button onclick="showReservationExamples('kwh')" 
                                        class="px-3 py-2 border border-blue-300 text-blue-700 rounded-md hover:bg-blue-50 transition-colors text-sm">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @endif
                        
                        @if(in_array('minute', $availableTypes))
                        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-6 hover:shadow-lg transition-all duration-200">
                            <div class="flex items-center mb-4">
                                <div class="mr-4">
                                    <div class="bg-green-500 rounded-lg p-3">
                                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-xl font-semibold text-gray-900">Réservation par Durée</h4>
                                    <p class="text-sm text-gray-600">Minutes</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-700 mb-4">
                                Réservez une durée de charge spécifique. Parfait pour planifier votre temps de recharge.
                            </p>
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center justify-between text-sm bg-white rounded-lg p-3">
                                    <span class="text-gray-600">Prix par minute:</span>
                                    <span class="font-semibold text-green-600">
                                        @if($pricingPlan && $pricingPlan->price_per_minute)
                                            {{ number_format($pricingPlan->price_per_minute, 2) }} {{ $pricingPlan->currency ?? 'EUR' }}
                                        @else
                                            Variable
                                        @endif
                                    </span>
                                </div>
                                @if($pricingPlan && $pricingPlan->activation_fee)
                                <div class="flex items-center justify-between text-sm bg-white rounded-lg p-3">
                                    <span class="text-gray-600">Frais d'activation:</span>
                                    <span class="font-semibold text-green-600">
                                        {{ number_format($pricingPlan->activation_fee, 2) }} {{ $pricingPlan->currency ?? 'EUR' }}
                                    </span>
                                </div>
                                @endif
                                @if($pricingPlan && $pricingPlan->max_duration)
                                <div class="flex items-center justify-between text-sm bg-white rounded-lg p-3">
                                    <span class="text-gray-600">Durée max:</span>
                                    <span class="font-semibold text-green-600">
                                        {{ $pricingPlan->max_duration }} min
                                    </span>
                                </div>
                                @endif
                                
                                <!-- Prix estimés pour différentes durées -->
                                @if($pricingPlan && $pricingPlan->price_per_minute)
                                <div class="bg-white rounded-lg p-3 border border-green-200">
                                    <div class="text-sm font-medium text-gray-700 mb-2">Prix estimés par durée :</div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 lg:gap-3 text-xs">
                                        @php
                                            $timeOptions = [15, 30, 45, 60, 90, 120, 180];
                                            $maxDuration = $pricingPlan->max_duration ?? 240;
                                            
                                            // Filtrer les options qui ne dépassent pas la limite
                                            $timeOptions = array_filter($timeOptions, function($time) use ($maxDuration) {
                                                return $time <= $maxDuration;
                                            });
                                            
                                            // Ajouter la durée maximale si elle n'est pas dans la liste
                                            if (!in_array($maxDuration, $timeOptions) && $maxDuration > 0) {
                                                $timeOptions[] = $maxDuration;
                                            }
                                            
                                            // Trier les options
                                            sort($timeOptions);
                                        @endphp
                                        
                                        @foreach($timeOptions as $time)
                                            @php
                                                $timeCost = $time * $pricingPlan->price_per_minute;
                                                $totalCost = $timeCost + ($pricingPlan->activation_fee ?? 0);
                                                $estimatedEnergy = $chargingPoint->power_output ? round(($time / 60) * $chargingPoint->power_output, 1) : 0;
                                            @endphp
                                            <div class="bg-green-50 rounded p-2 text-center">
                                                <div class="font-semibold text-green-800">{{ $time }} min</div>
                                                <div class="text-green-600">{{ number_format($totalCost, 2) }} {{ $pricingPlan->currency ?? 'EUR' }}</div>
                                                @if($estimatedEnergy > 0)
                                                <div class="text-green-500 text-xs">~{{ $estimatedEnergy }} kWh</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="flex space-x-3">
                                @if(in_array('minute', $availableTypes))
                                <a href="{{ $publicReservationUrl }}?type=minute" 
                                   class="flex-1 bg-green-600 text-white text-center py-3 px-4 rounded-lg hover:bg-green-700 transition-colors text-sm font-semibold shadow-sm">
                                    Réserver par Minutes
                                </a>
                                @else
                                <button disabled 
                                        class="flex-1 bg-gray-400 text-white text-center py-3 px-4 rounded-lg cursor-not-allowed text-sm font-semibold shadow-sm">
                                    Réserver par Minutes (Non disponible)
                                </button>
                                @endif
                                <button onclick="showReservationExamples('minute')" 
                                        class="px-3 py-3 border border-green-300 text-green-700 rounded-lg hover:bg-green-50 transition-colors text-sm">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>
                    
                    @if($pricingPlan && ($pricingPlan->price_per_kwh > 0 || $pricingPlan->price_per_minute > 0))
                    <div class="mt-6 p-6 bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-lg">
                        <!-- Comparaison des prix -->
                        @if($pricingPlan->price_per_kwh > 0 && $pricingPlan->price_per_minute > 0)
                        <div class="mb-6 p-4 bg-white rounded-lg border border-purple-200">
                            <h5 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                                <svg class="h-5 w-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Comparaison des prix
                            </h5>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                @php
                                    $commonScenarios = [
                                        ['name' => 'Recharge rapide', 'kwh' => 10, 'minutes' => 30],
                                        ['name' => 'Recharge standard', 'kwh' => 20, 'minutes' => 60],
                                        ['name' => 'Recharge complète', 'kwh' => 40, 'minutes' => 120],
                                    ];
                                @endphp
                                
                                @foreach($commonScenarios as $scenario)
                                    @php
                                        $kwhCost = $scenario['kwh'] * $pricingPlan->price_per_kwh;
                                        $kwhTotal = $kwhCost + ($pricingPlan->activation_fee ?? 0);
                                        $minuteCost = $scenario['minutes'] * $pricingPlan->price_per_minute;
                                        $minuteTotal = $minuteCost + ($pricingPlan->activation_fee ?? 0);
                                        $kwhBetter = $kwhTotal < $minuteTotal;
                                    @endphp
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <div class="font-medium text-gray-900 mb-2">{{ $scenario['name'] }}</div>
                                        <div class="space-y-1">
                                            <div class="flex justify-between">
                                                <span class="text-blue-600">Par kWh ({{ $scenario['kwh'] }} kWh):</span>
                                                <span class="font-medium {{ $kwhBetter ? 'text-green-600' : 'text-gray-600' }}">
                                                    {{ number_format($kwhTotal, 2) }} {{ $pricingPlan->currency ?? 'EUR' }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-green-600">Par minute ({{ $scenario['minutes'] }} min):</span>
                                                <span class="font-medium {{ !$kwhBetter ? 'text-green-600' : 'text-gray-600' }}">
                                                    {{ number_format($minuteTotal, 2) }} {{ $pricingPlan->currency ?? 'EUR' }}
                                                </span>
                                            </div>
                                            @if($kwhTotal != $minuteTotal)
                                            <div class="text-xs text-gray-500 mt-1">
                                                <span class="font-medium {{ $kwhBetter ? 'text-green-600' : 'text-blue-600' }}">
                                                    {{ $kwhBetter ? 'kWh' : 'Minute' }} plus avantageux
                                                </span>
                                                ({{ number_format(abs($kwhTotal - $minuteTotal), 2) }} {{ $pricingPlan->currency ?? 'EUR' }} de différence)
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if(empty($availableTypes))
                        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <span class="text-sm text-yellow-800">
                                    Aucun type de réservation disponible avec la configuration actuelle du plan tarifaire.
                                </span>
                            </div>
                        </div>
                        @endif
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="bg-purple-500 rounded-lg p-2">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h5 class="text-lg font-semibold text-gray-900 mb-3">Plan tarifaire: {{ $pricingPlan->name }}</h5>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    @if($pricingPlan->max_duration)
                                    <div class="bg-white rounded-lg p-3 border border-purple-200">
                                        <div class="text-sm font-medium text-gray-600 mb-1">Durée maximale</div>
                                        <div class="text-lg font-semibold text-purple-600">{{ $pricingPlan->max_duration }} minutes</div>
                                    </div>
                                    @endif
                                    @if($chargingPoint->power_output && $pricingPlan->max_duration)
                                    <div class="bg-white rounded-lg p-3 border border-purple-200">
                                        <div class="text-sm font-medium text-gray-600 mb-1">Énergie maximale</div>
                                        <div class="text-lg font-semibold text-purple-600">{{ number_format($chargingPoint->power_output * ($pricingPlan->max_duration / 60), 1) }} kWh</div>
                                    </div>
                                    @endif
                                    @if($pricingPlan->min_charge_duration)
                                    <div class="bg-white rounded-lg p-3 border border-purple-200">
                                        <div class="text-sm font-medium text-gray-600 mb-1">Durée minimale</div>
                                        <div class="text-lg font-semibold text-purple-600">{{ $pricingPlan->min_charge_duration }} minutes</div>
                                    </div>
                                    @endif
                                </div>
                                @if($pricingPlan->description)
                                <div class="bg-white rounded-lg p-4 border border-purple-200">
                                    <p class="text-sm text-gray-700">{{ $pricingPlan->description }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @elseif($pricingPlan)
                    <div class="mt-6 p-6 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="bg-yellow-500 rounded-lg p-2">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h5 class="text-lg font-semibold text-yellow-900 mb-3">Plan tarifaire incomplet</h5>
                                <p class="text-sm text-yellow-700 mb-4">
                                    Le plan tarifaire "{{ $pricingPlan->name }}" n'a pas de prix configuré pour les kWh ou les minutes. 
                                    Veuillez configurer les tarifs dans les paramètres du plan tarifaire.
                                </p>
                                <div class="bg-white rounded-lg p-4 border border-yellow-200">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="font-medium text-gray-700">Prix par kWh:</span>
                                            <span class="ml-2 {{ $pricingPlan->price_per_kwh > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $pricingPlan->price_per_kwh > 0 ? number_format($pricingPlan->price_per_kwh, 2) . ' ' . ($pricingPlan->currency ?? 'EUR') : 'Non configuré' }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700">Prix par minute:</span>
                                            <span class="ml-2 {{ $pricingPlan->price_per_minute > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $pricingPlan->price_per_minute > 0 ? number_format($pricingPlan->price_per_minute, 2) . ' ' . ($pricingPlan->currency ?? 'EUR') : 'Non configuré' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="mt-6 p-6 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="bg-red-500 rounded-lg p-2">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h5 class="text-lg font-semibold text-red-900 mb-3">Aucun plan tarifaire configuré</h5>
                                <p class="text-sm text-red-700 mb-4">
                                    Cette borne de recharge n'a pas de plan tarifaire assigné. 
                                    Veuillez assigner un plan tarifaire pour permettre les réservations.
                                </p>
                                <a href="{{ route('charging-points.edit', $chargingPoint->id) }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    Configurer le plan tarifaire
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Quick Reservation Summary -->
                    <div class="mt-6 p-6 bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="bg-blue-500 rounded-lg p-2">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h5 class="text-lg font-semibold text-blue-900">Réservation Rapide</h5>
                                <p class="text-sm text-blue-700 mt-1">Choisissez le type de réservation qui correspond le mieux à vos besoins</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @if(in_array('kwh', $availableTypes))
                            <div class="bg-white p-3 rounded border border-blue-200">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">Par Énergie (kWh)</span>
                                    <span class="text-xs text-gray-500">Recommandé si vous connaissez votre besoin énergétique</span>
                                </div>
                            </div>
                            @endif
                            @if(in_array('minute', $availableTypes))
                            <div class="bg-white p-3 rounded border border-blue-200">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">Par Durée (Minutes)</span>
                                    <span class="text-xs text-gray-500">Recommandé pour planifier votre temps</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Recent Reservations Section -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Réservations Récentes</h3>
                        <a href="{{ route('reservations.index') }}?charging_point_id={{ $chargingPoint->id }}" class="text-sm text-green-600 hover:text-green-800">
                            Voir toutes les réservations
                        </a>
                    </div>
                    
                    @php
                        // Get recent reservations for this charging point
                        $recentReservations = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)
                            ->with(['user', 'pricingPlan'])
                            ->orderBy('created_at', 'desc')
                            ->limit(5)
                            ->get();
                    @endphp
                    
                    @if($recentReservations->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valeur</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($recentReservations as $reservation)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($reservation->reservation_type === 'kwh')
                                                <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                <span class="text-sm font-medium text-gray-900">Énergie</span>
                                            @else
                                                <svg class="h-5 w-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="text-sm font-medium text-gray-900">Durée</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($reservation->reservation_value, 1) }}
                                        @if($reservation->reservation_type === 'kwh')
                                            kWh
                                        @else
                                            min
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($reservation->user)
                                            {{ $reservation->user->name }}
                                        @elseif($reservation->guest_email)
                                            <span class="text-gray-500">{{ $reservation->guest_email }}</span>
                                        @else
                                            <span class="text-gray-400">Invité</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $reservation->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusMap = [
                                                'pending' => ['text' => 'En attente', 'class' => 'bg-yellow-100 text-yellow-800'],
                                                'pending_confirmation' => ['text' => 'En attente de confirmation', 'class' => 'bg-orange-100 text-orange-800'],
                                                'confirmed' => ['text' => 'Confirmée', 'class' => 'bg-blue-100 text-blue-800'],
                                                'active' => ['text' => 'Active', 'class' => 'bg-green-100 text-green-800'],
                                                'completed' => ['text' => 'Terminée', 'class' => 'bg-gray-100 text-gray-800'],
                                                'canceled' => ['text' => 'Annulée', 'class' => 'bg-red-100 text-red-800'],
                                            ];
                                            $status = $reservation->status?->value ?? 'unknown';
                                            $statusInfo = $statusMap[$status] ?? ['text' => $status, 'class' => 'bg-gray-100 text-gray-800'];
                                            if ($reservation->isPaidByBalance() || ($reservation->isPaid() && in_array($status, ['pending', 'pending_confirmation'], true))) {
                                                $statusInfo = ['text' => $reservation->getDisplayStatusLabel(), 'class' => 'bg-green-100 text-green-800'];
                                            }
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusInfo['class'] }}">
                                            {{ $statusInfo['text'] }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune réservation</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Aucune réservation n'a encore été effectuée pour cette borne de recharge.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('reservations.create', $chargingPoint->id) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Créer une réservation
                            </a>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Reservation Statistics Section -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Statistiques des Réservations</h3>
                    
                    @php
                        // Calculate reservation statistics
                        $totalReservations = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)->count();
                        $kwhReservations = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)
                            ->where('reservation_type', 'kwh')->count();
                        $minuteReservations = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)
                            ->where('reservation_type', 'minute')->count();
                        $completedReservations = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)
                            ->where('status', 'completed')->count();
                        $totalRevenue = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)
                            ->where('status', 'completed')
                            ->sum('actual_cost');
                        
                        // Calculate percentages
                        $kwhPercentage = $totalReservations > 0 ? round(($kwhReservations / $totalReservations) * 100, 1) : 0;
                        $minutePercentage = $totalReservations > 0 ? round(($minuteReservations / $totalReservations) * 100, 1) : 0;
                        $completionRate = $totalReservations > 0 ? round(($completedReservations / $totalReservations) * 100, 1) : 0;
                    @endphp
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-600">Total Réservations</p>
                                    <p class="text-2xl font-semibold text-blue-900">{{ $totalReservations }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-600">Taux de Réussite</p>
                                    <p class="text-2xl font-semibold text-green-900">{{ $completionRate }}%</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-purple-600">Revenus Totaux</p>
                                    <p class="text-2xl font-semibold text-purple-900">{{ number_format($totalRevenue, 2) }} EUR</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-orange-600">Moyenne/Réservation</p>
                                    <p class="text-2xl font-semibold text-orange-900">
                                        {{ $completedReservations > 0 ? number_format($totalRevenue / $completedReservations, 2) : '0.00' }} EUR
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reservation Type Distribution -->
                    @if($totalReservations > 0)
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Répartition par Type</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="text-sm text-gray-700">Par Énergie (kWh)</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-900 mr-2">{{ $kwhReservations }}</span>
                                        <span class="text-xs text-gray-500">({{ $kwhPercentage }}%)</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $kwhPercentage }}%"></div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-sm text-gray-700">Par Durée (Minutes)</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-900 mr-2">{{ $minuteReservations }}</span>
                                        <span class="text-xs text-gray-500">({{ $minutePercentage }}%)</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $minutePercentage }}%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Statut des Réservations</h4>
                            @php
                                $statusCounts = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)
                                    ->selectRaw('status, count(*) as count')
                                    ->groupBy('status')
                                    ->pluck('count', 'status')
                                    ->toArray();
                            @endphp
                            <div class="space-y-2">
                                @foreach($statusCounts as $status => $count)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">
                                        @switch($status)
                                            @case('pending')
                                                En attente
                                                @break
                                            @case('pending_confirmation')
                                                En attente de confirmation
                                                @break
                                            @case('confirmed')
                                                Confirmée
                                                @break
                                            @case('active')
                                                Active
                                                @break
                                            @case('completed')
                                                Terminée
                                                @break
                                            @case('canceled')
                                                Annulée
                                                @break
                                            @default
                                                {{ ucfirst($status) }}
                                        @endswitch
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune donnée</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Les statistiques apparaîtront une fois que des réservations auront été effectuées.
                        </p>
                    </div>
                    @endif
                </div>

                <!-- Integration Section -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Intégration</h3>
                    
                    <div class="space-y-6">
                        <!-- SteVe Connection -->
                        <div class="bg-white shadow rounded-lg p-6">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h4 class="text-xl font-semibold text-gray-900">Connexion SteVe OCPP</h4>
                                    <p class="text-sm text-gray-600 mt-1">Connecter la borne au serveur SteVe OCPP pour la gestion des communications</p>
                                </div>
                                <div class="flex space-x-3">
                                    <button onclick="testSteVeConnection()" 
                                            class="inline-flex items-center px-4 py-2 border border-green-300 shadow-sm text-sm font-medium rounded-lg text-green-700 bg-green-50 hover:bg-green-100 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Tester Connexion
                                    </button>
                                </div>
                            </div>
                            
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Charge Box ID</label>
                                        <input type="text" 
                                               id="charge-box-id-input"
                                               value="{{ $chargingPoint->charge_box_id ?? '' }}"
                                               placeholder="Ex: BORNE_001"
                                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm transition-colors">
                                        <p class="mt-2 text-xs text-gray-500 flex items-center">
                                            <svg class="h-3 w-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            ID unique de la borne pour le serveur SteVe
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">URL du serveur SteVe</label>
                                        <input type="text" 
                                               id="steve-server-url-input"
                                               value="{{ $chargingPoint->steve_server_url ?? '' }}"
                                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm transition-colors">
                                        <p class="mt-2 text-xs text-gray-500 flex items-center">
                                            <svg class="h-3 w-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            URL du serveur SteVe OCPP
                                        </p>
                                    </div>
                                </div>
                                
                                @if($chargingPoint->server_url)
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">URL complète</label>
                                    <div class="flex">
                                        <input type="text" 
                                               value="{{ $chargingPoint->server_url }}" 
                                               readonly 
                                               class="flex-1 block w-full px-4 py-3 border border-gray-300 rounded-l-lg shadow-sm bg-white text-sm font-mono">
                                        <button onclick="copyServerUrl()" 
                                                class="inline-flex items-center px-4 py-3 border border-l-0 border-gray-300 rounded-r-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 flex items-center">
                                        <svg class="h-3 w-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        URL complète pour le simulateur de borne
                                    </p>
                                </div>
                                @endif
                                
                                <div class="flex space-x-3 pt-4">
                                    <button onclick="connectToSteVe()" 
                                            class="flex-1 bg-green-600 text-white text-center py-3 px-6 rounded-lg hover:bg-green-700 transition-colors text-sm font-semibold shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Connecter à SteVe
                                    </button>
                                </div>
                                
                                @if($chargingPoint->last_connection_attempt)
                                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h5 class="text-sm font-medium text-blue-800">Dernière tentative de connexion</h5>
                                            <p class="text-sm text-blue-700">{{ \Carbon\Carbon::parse($chargingPoint->last_connection_attempt)->format('d/m/Y H:i:s') }}</p>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- QR Code Integration -->
                        <div class="bg-white shadow rounded-lg p-6">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h4 class="text-xl font-semibold text-gray-900">QR Code</h4>
                                    <p class="text-sm text-gray-600 mt-1">URL du QR code pour l'intégration</p>
                                </div>
                                <div class="flex space-x-3">
                                    <a href="{{ route('public.charging-points.qr-code', $chargingPoint->id) }}" 
                                       class="inline-flex items-center px-4 py-2 border border-green-300 shadow-sm text-sm font-medium rounded-lg text-green-700 bg-green-50 hover:bg-green-100 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Voir QR Code
                                    </a>
                                    <button onclick="generateQrCode()" 
                                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Régénérer
                                    </button>
                                </div>
                            </div>
                            
                            @if(isset($qrCodeUrl) && $qrCodeUrl)
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">URL du QR Code</label>
                                        <div class="flex">
                                            <input type="text" 
                                                   value="{{ $qrCodeUrl }}" 
                                                   readonly 
                                                   class="flex-1 block w-full px-3 py-2 border border-gray-300 rounded-l-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm bg-gray-50">
                                            <button onclick="copyQrCodeUrl()" 
                                                    class="inline-flex items-center px-3 py-2 border border-l-0 border-gray-300 rounded-r-md bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Aperçu du QR Code</label>
                                        <div class="flex justify-center">
                                            <img src="{{ $qrCodeUrl }}" 
                                                 alt="QR Code pour {{ $chargingPoint->name }}" 
                                                 class="w-32 h-32 border border-gray-200 rounded-lg">
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <p class="text-gray-500 mb-3">Aucun QR code généré</p>
                                </div>
                            @endif
                        </div>


                    </div>
                </div>

                <!-- Recent transactions -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Sessions récentes</h3>
                        <a href="#sessions" class="text-sm text-green-600 hover:text-green-800">Voir tout</a>
                    </div>
                    
                    @if(isset($recentTransactions) && count($recentTransactions) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durée</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Énergie</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coût</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($recentTransactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->start_timestamp ? \Carbon\Carbon::parse($transaction->start_timestamp)->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->duration ? gmdate('H:i:s', $transaction->duration) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ number_format($transaction->energy_delivered ?? 0, 2) }} kWh
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ number_format($transaction->price_total ?? 0, 2) }} €
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($transaction->status == 'completed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Terminée
                                            </span>
                                        @elseif($transaction->status == 'in_progress')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                En cours
                                            </span>
                                        @elseif($transaction->status == 'failed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Échouée
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ $transaction->status }}
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
                        Aucune session de recharge récente
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right sidebar - Details and Remote actions -->
            <div class="w-full lg:w-1/4 xl:w-1/5">
                <!-- Remote actions panel -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-900">Actions à distance</h3>
                        <p class="text-sm text-gray-600 mt-1">Contrôler la borne à distance</p>
                    </div>
                    <div class="p-4">
                        <ul class="space-y-2">
                            <li>
                                <button class="w-full text-left block px-4 py-3 text-green-600 hover:bg-green-50 rounded-lg transition-colors font-medium">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Déclencher une recharge
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button class="w-full text-left block px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg transition-colors font-medium">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Arrêter une recharge
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button class="w-full text-left block px-4 py-3 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors font-medium">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        Déblocage de connexion
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button class="w-full text-left block px-4 py-3 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors font-medium">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Réinitialisation
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button class="w-full text-left block px-4 py-3 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors font-medium">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Mise à jour des paramètres
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button class="w-full text-left block px-4 py-3 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors font-medium">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        Diagnostic
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button class="w-full text-left block px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors font-medium">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Récupérer le log
                                    </div>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Charging point details -->
                <div class="bg-white shadow rounded-lg">
                    <div class="p-6 space-y-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Détails</h3>
                            <p class="text-sm text-gray-600 mt-1">Informations techniques de la borne</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Marque & Modèle</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->manufacturer }} - {{ $chargingPoint->model }}</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Puissance maximale</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->power_output }} kW</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Numéro de série</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->serial_number }}</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Groupe</p>
                            <p class="text-lg font-medium">
                                @if($chargingPoint->group)
                                    <a href="{{ route('groups.show', $chargingPoint->group) }}" class="text-green-600 hover:text-green-800">{{ $chargingPoint->group->name }}</a>
                                @else
                                    <span class="text-gray-400">Non assigné</span>
                                @endif
                            </p>
                        </div>

                        @if($chargingPoint->partner)
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Partenaire</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->partner->name }}</p>
                        </div>
                        @endif

                        @if($chargingPoint->integrator)
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Intégrateur</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->integrator->name }}</p>
                        </div>
                        @endif
        
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm font-semibold text-gray-700 mb-1">Localisation</p>
            <p class="text-lg font-medium text-gray-900">
                @if($chargingPoint->location && is_object($chargingPoint->location))
                    {{ $chargingPoint->location->address ?? 'N/A' }}, {{ $chargingPoint->location->city ?? 'N/A' }}
                @elseif($chargingPoint->location && is_string($chargingPoint->location))
                    {{ $chargingPoint->location }} {{-- Display the string directly --}}
                @else
                    <span class="text-gray-400">Non définie</span>
                @endif
            </p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm font-semibold text-gray-700 mb-1">Date d'installation</p>
            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->installation_date ? \Carbon\Carbon::parse($chargingPoint->installation_date)->format('d/m/Y') : 'Non définie' }}</p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm font-semibold text-gray-700 mb-1">Dernière connexion</p>
            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->last_connection ? \Carbon\Carbon::parse($chargingPoint->last_connection)->format('d/m/Y H:i') : 'Jamais' }}</p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm font-semibold text-gray-700 mb-1">Version du firmware</p>
            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->firmware_version ?? 'Inconnue' }}</p>
        </div>
    </div>
<div>
            <p class="text-sm text-gray-500">Plan tarifaire</p>
            @if(isset($chargingPoint->pricingPlan) && $chargingPoint->pricingPlan)
                <p class="font-medium">{{ $chargingPoint->pricingPlan->name }}</p>
            @else
                <p class="font-medium text-yellow-600">Plan par défaut</p>
            @endif
        </div>
</div>


<!-- Status change modal -->
<div id="status-modal" class="fixed inset-0 z-10 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="{{ route('charging-points.toggle-status', $chargingPoint->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Changer le statut
                        </h3>
                        <div class="mt-4">
                            <p class="text-sm text-gray-500">
                                Sélectionnez le nouveau statut pour cette borne de recharge.
                            </p>
                            <div class="mt-4 space-y-4">
                                <div class="flex items-center">
                                    <input id="status-online" name="status" type="radio" value="online" class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300" {{ $chargingPoint->status == 'online' ? 'checked' : '' }}>
                                    <label for="status-online" class="ml-3 block text-sm font-medium text-gray-700">
                                        En ligne
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="status-offline" name="status" type="radio" value="offline" class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300" {{ $chargingPoint->status == 'offline' ? 'checked' : '' }}>
                                    <label for="status-offline" class="ml-3 block text-sm font-medium text-gray-700">
                                        Hors ligne
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="status-maintenance" name="status" type="radio" value="maintenance" class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300" {{ $chargingPoint->status == 'maintenance' ? 'checked' : '' }}>
                                    <label for="status-maintenance" class="ml-3 block text-sm font-medium text-gray-700">
                                        Maintenance
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Enregistrer
                    </button>
                    <button type="button" id="close-modal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for interactive elements -->
<script>
    // Status modal controls
    const statusToggle = document.getElementById('status-toggle');
    const statusModal = document.getElementById('status-modal');
    const closeModal = document.getElementById('close-modal');
    
    if (statusToggle && statusModal && closeModal) {
        statusToggle.addEventListener('click', () => {
            statusModal.classList.remove('hidden');
        });
        
        closeModal.addEventListener('click', () => {
            statusModal.classList.add('hidden');
        });
    }
    
    // Charts data handling would go here in real implementation
    // This is just a placeholder for demonstration purposes
    document.getElementById('stats-period').addEventListener('change', function() {
        // In a real implementation, this would fetch data for the selected period
        // and update the charts and statistics
        console.log('Period changed to:', this.value);
    });

    // QR Code functions
    function copyQrCodeUrl() {
        const qrCodeInput = document.querySelector('input[value*="qrcodes"]');
        if (qrCodeInput) {
            qrCodeInput.select();
            document.execCommand('copy');
            showNotification('URL du QR code copiée !', 'success');
        }
    }



    function generateQrCode() {
        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="animate-spin h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Génération...';
        button.disabled = true;

        // Make API call to generate QR code
        fetch(`{{ route('charging-points.generate-qr-code', $chargingPoint->id) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('QR code généré avec succès !', 'success');
                // Reload the page to show the new QR code
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification('Erreur lors de la génération du QR code', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erreur lors de la génération du QR code', 'error');
        })
        .finally(() => {
            // Restore button state
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }

        function showReservationExamples(type) {
        const pricingPlan = @json($pricingPlan);
        let message = '';
        let title = '';
        
        if (type === 'kwh' && pricingPlan && pricingPlan.price_per_kwh && pricingPlan.price_per_kwh > 0) {
            title = 'Exemples de réservation par Énergie (kWh)';
            
            // Calculer plusieurs exemples
            const examples = [5, 10, 20, 30, 50];
            const powerOutput = {{ $chargingPoint->power_output ?? 22 }};
            
            message = `Exemples de réservation par Énergie (kWh) :

${examples.map(kwh => {
    const energyCost = kwh * pricingPlan.price_per_kwh;
    const activationFee = pricingPlan.activation_fee || 0;
    const totalCost = energyCost + activationFee;
    const estimatedDuration = powerOutput ? Math.round((kwh / powerOutput) * 60) : 0;
    
    return `• ${kwh} kWh = ${totalCost.toFixed(2)} ${pricingPlan.currency || 'EUR'} (${estimatedDuration} min)`;
}).join('\n')}

Détail du calcul pour 10 kWh :
• Coût énergie: 10 kWh × ${pricingPlan.price_per_kwh} ${pricingPlan.currency || 'EUR'} = ${(10 * pricingPlan.price_per_kwh).toFixed(2)} ${pricingPlan.currency || 'EUR'}
${pricingPlan.activation_fee > 0 ? `• Frais d'activation: ${pricingPlan.activation_fee.toFixed(2)} ${pricingPlan.currency || 'EUR'}` : ''}
• Coût total: ${(10 * pricingPlan.price_per_kwh + (pricingPlan.activation_fee || 0)).toFixed(2)} ${pricingPlan.currency || 'EUR'}

Note: Les durées sont estimées selon la puissance de la borne (${powerOutput} kW)`;
        } else if (type === 'minute' && pricingPlan && pricingPlan.price_per_minute && pricingPlan.price_per_minute > 0) {
            title = 'Exemples de réservation par Durée (Minutes)';
            
            // Calculer plusieurs exemples
            const examples = [15, 30, 45, 60, 90, 120];
            const powerOutput = {{ $chargingPoint->power_output ?? 22 }};
            
            message = `Exemples de réservation par Durée (Minutes) :

${examples.map(minutes => {
    const timeCost = minutes * pricingPlan.price_per_minute;
    const activationFee = pricingPlan.activation_fee || 0;
    const totalCost = timeCost + activationFee;
    const estimatedEnergy = powerOutput ? (minutes / 60) * powerOutput : 0;
    
    return `• ${minutes} min = ${totalCost.toFixed(2)} ${pricingPlan.currency || 'EUR'} (~${estimatedEnergy.toFixed(1)} kWh)`;
}).join('\n')}

Détail du calcul pour 60 minutes :
• Coût temps: 60 min × ${pricingPlan.price_per_minute} ${pricingPlan.currency || 'EUR'} = ${(60 * pricingPlan.price_per_minute).toFixed(2)} ${pricingPlan.currency || 'EUR'}
${pricingPlan.activation_fee > 0 ? `• Frais d'activation: ${pricingPlan.activation_fee.toFixed(2)} ${pricingPlan.currency || 'EUR'}` : ''}
• Coût total: ${(60 * pricingPlan.price_per_minute + (pricingPlan.activation_fee || 0)).toFixed(2)} ${pricingPlan.currency || 'EUR'}

Note: L'énergie est estimée selon la puissance de la borne (${powerOutput} kW)`;
        } else {
            title = 'Informations de réservation';
            if (!pricingPlan) {
                message = 'Aucun plan tarifaire n\'est configuré pour cette borne de recharge. Veuillez contacter l\'administrateur.';
            } else if (type === 'kwh' && (!pricingPlan.price_per_kwh || pricingPlan.price_per_kwh <= 0)) {
                message = 'Le prix par kWh n\'est pas configuré dans le plan tarifaire. Veuillez configurer le tarif kWh dans les paramètres du plan.';
            } else if (type === 'minute' && (!pricingPlan.price_per_minute || pricingPlan.price_per_minute <= 0)) {
                message = 'Le prix par minute n\'est pas configuré dans le plan tarifaire. Veuillez configurer le tarif minute dans les paramètres du plan.';
            } else {
                message = 'Les détails de tarification ne sont pas disponibles pour ce type de réservation.';
            }
        }
        
        // Create a modal instead of using alert
        showReservationModal(title, message);
    }
    
    function showReservationModal(title, message) {
        // Remove existing modal if any
        const existingModal = document.getElementById('reservation-examples-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Create modal HTML
        const modalHTML = `
            <div id="reservation-examples-modal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        ${title}
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 whitespace-pre-line">
                                            ${message}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" onclick="closeReservationModal()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    function closeReservationModal() {
        const modal = document.getElementById('reservation-examples-modal');
        if (modal) {
            modal.remove();
        }
    }

    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.textContent = message;

        // Add to page
        document.body.appendChild(notification);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // SteVe Connection Functions
    function connectToSteVe() {
        const chargeBoxId = document.getElementById('charge-box-id-input').value.trim();
        const steveServerUrl = document.getElementById('steve-server-url-input').value.trim();
        
        if (!chargeBoxId) {
            showNotification('Veuillez saisir un Charge Box ID', 'error');
            return;
        }
        
        if (!steveServerUrl) {
            showNotification('Veuillez saisir l\'URL du serveur SteVe', 'error');
            return;
        }

        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="animate-spin h-4 w-4 mr-1 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Connexion...';
        button.disabled = true;

        // Make API call to connect to SteVe
        fetch(`{{ route('charging-points.connect-steve', $chargingPoint->id) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                charge_box_id: chargeBoxId,
                steve_server_url: steveServerUrl
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // Reload the page to show updated information
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(data.message || 'Erreur lors de la connexion', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erreur lors de la connexion au serveur SteVe', 'error');
        })
        .finally(() => {
            // Restore button state
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }

    function testSteVeConnection() {
        const chargeBoxId = document.getElementById('charge-box-id-input').value.trim();
        
        if (!chargeBoxId) {
            showNotification('Veuillez saisir un Charge Box ID pour tester la connexion', 'error');
            return;
        }

        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="animate-spin h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Test...';
        button.disabled = true;

        // Make API call to test SteVe connection
        fetch(`{{ route('charging-points.test-steve-connection', $chargingPoint->id) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                charge_box_id: chargeBoxId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message || 'Erreur lors du test de connexion', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erreur lors du test de connexion', 'error');
        })
        .finally(() => {
            // Restore button state
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }

    function copyServerUrl() {
        const serverUrlInput = document.querySelector('input[value*="http"]');
        if (serverUrlInput) {
            serverUrlInput.select();
            document.execCommand('copy');
            showNotification('URL copiée !', 'success');
        }
    }

    // 3D Model Initialization
    let chargingPoint3DViewer = null;
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize 3D model viewer
        if (document.getElementById('charging-point-3d')) {
            chargingPoint3DViewer = new ChargingPoint3DViewer('charging-point-3d', {
                modelPath: '/models/charging-point.glb',
                autoRotate: true,
                rotationSpeed: 0.005,
                enableShadows: true,
                enableControls: true,
                showLoading: true
            });
        }
        
        // Control buttons
        const resetCameraBtn = document.getElementById('reset-camera');
        const toggleRotationBtn = document.getElementById('toggle-rotation');
        
        if (resetCameraBtn) {
            resetCameraBtn.addEventListener('click', function() {
                if (chargingPoint3DViewer) {
                    chargingPoint3DViewer.resetCamera();
                }
            });
        }
        
        if (toggleRotationBtn) {
            toggleRotationBtn.addEventListener('click', function() {
                if (chargingPoint3DViewer) {
                    chargingPoint3DViewer.toggleAutoRotate();
                    // Update button text
                    const isRotating = chargingPoint3DViewer.config.autoRotate;
                    this.innerHTML = isRotating ? 
                        '<svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>Rotation' :
                        '<svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>Rotation';
                }
            });
        }
    });

    // Fonction pour afficher le modal des détails des parts
    function showPartsInfoModal() {
        // Créer le modal s'il n'existe pas
        let modal = document.getElementById('parts-info-modal');
        if (!modal) {
            modal = createPartsInfoModal();
            document.body.appendChild(modal);
        }
        
        // Afficher le modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function createPartsInfoModal() {
        const modal = document.createElement('div');
        modal.id = 'parts-info-modal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden';
        
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="bg-blue-100 rounded-full p-3 mr-4">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Détails des Parts - Répartition des Revenus</h2>
                                <p class="text-gray-600">Borne: {{ $chargingPoint->name }}</p>
                            </div>
                        </div>
                        <button onclick="hidePartsInfoModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Contenu du modal -->
                    <div class="space-y-6">
                        <!-- Parts par Pourcentage -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 border border-blue-200">
                            <h3 class="text-lg font-semibold text-blue-900 mb-4 flex items-center">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Répartition des Parts par Pourcentage
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white rounded-lg p-4 border border-blue-100">
                                    <h4 class="font-semibold text-blue-800 mb-2">Borne créée par Opérateur</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-blue-700">Part Admin:</span>
                                            <span class="font-semibold text-blue-900">10% du total</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-blue-700">Part Intégrateur:</span>
                                            <span class="font-semibold text-blue-900">17% du total</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-blue-700">Part Opérateur:</span>
                                            <span class="font-semibold text-blue-900">63% du total</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white rounded-lg p-4 border border-blue-100">
                                    <h4 class="font-semibold text-blue-800 mb-2">Opérateur créé par Admin</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-blue-700">Part Admin:</span>
                                            <span class="font-semibold text-blue-900">15% du total</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-blue-700">Part Intégrateur:</span>
                                            <span class="font-semibold text-blue-900">0% du total</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-blue-700">Part Opérateur:</span>
                                            <span class="font-semibold text-blue-900">85% du total</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Détails des Calculs Basés sur Business Profiles -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-6 border border-green-200">
                            <h3 class="text-lg font-semibold text-green-900 mb-4 flex items-center">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Détails des Calculs Basés sur Business Profiles Appliqués
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white rounded-lg p-4 border border-green-100">
                                    <h4 class="font-semibold text-green-800 mb-2">Transaction 2: Admin → Intégrateur</h4>
                                    <div class="space-y-1 text-sm text-green-700">
                                        <div>• Part Admin: 10% du montant total (selon business profile)</div>
                                        <div>• Cette part est déduite de la part intégrateur</div>
                                        <div>• Business Profile: Admin → Intégrateur</div>
                                    </div>
                                </div>
                                <div class="bg-white rounded-lg p-4 border border-green-100">
                                    <h4 class="font-semibold text-green-800 mb-2">Transaction 1: Intégrateur → Opérateur</h4>
                                    <div class="space-y-1 text-sm text-green-700">
                                        <div>• Part Intégrateur: 27% du montant total</div>
                                        <div>• Frais de transaction: 1.5% + 1 EUR</div>
                                        <div>• Business Profile: Intégrateur → Opérateur</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Exemple de Calcul -->
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-6 border border-purple-200">
                            <h3 class="text-lg font-semibold text-purple-900 mb-4 flex items-center">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Exemple de Calcul (Transaction 200 EUR)
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white rounded-lg p-4 border border-purple-100">
                                    <h4 class="font-semibold text-purple-800 mb-2">Borne créée par Opérateur</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-purple-700">Transaction 1 - Intégrateur (27%):</span>
                                            <span class="font-semibold text-purple-900">54 EUR</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-purple-700">Transaction 2 - Admin (10% du total):</span>
                                            <span class="font-semibold text-purple-900">20 EUR</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-purple-700">Part Intégrateur finale (17%):</span>
                                            <span class="font-semibold text-purple-900">34 EUR</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-purple-700">Part Opérateur (63%):</span>
                                            <span class="font-semibold text-purple-900">126 EUR</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white rounded-lg p-4 border border-purple-100">
                                    <h4 class="font-semibold text-purple-800 mb-2">Opérateur créé par Admin</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-purple-700">Part Admin (15%):</span>
                                            <span class="font-semibold text-purple-900">30 EUR</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-purple-700">Part Intégrateur (0%):</span>
                                            <span class="font-semibold text-purple-900">0 EUR</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-purple-700">Part Opérateur (85%):</span>
                                            <span class="font-semibold text-purple-900">170 EUR</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end mt-6 pt-4 border-t border-gray-200">
                        <button onclick="hidePartsInfoModal()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        return modal;
    }

    function hidePartsInfoModal() {
        const modal = document.getElementById('parts-info-modal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }

    // Fermer le modal en cliquant à l'extérieur
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('parts-info-modal');
        if (modal && !modal.classList.contains('hidden')) {
            if (event.target === modal) {
                hidePartsInfoModal();
            }
        }
    });
</script>
@endsection
