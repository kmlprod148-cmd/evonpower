@extends('layouts.app')

@section('content')
@php
    $publicReservationUrl = \Illuminate\Support\Facades\Route::has('public.charging-point.offer.reservation') ? route('public.charging-point.offer.reservation', $chargingPoint->id) : url('/offer/' . $chargingPoint->id . '/reservation');
@endphp
<div class="bg-gray-50 min-h-screen" x-data="{ activeTab: 'overview', realtimeStatus: '{{ $realtimeStatus ?? $chargingPoint->status ?? 'unknown' }}', steveStatusSuccess: {{ $steveStatusSuccess ? 'true' : 'false' }}, chargingPointId: {{ $chargingPoint->id }}, lastUpdate: null }" x-init="
    // Auto-refresh status every 30 seconds
    setInterval(async () => {
        try {
            const response = await fetch(`/charging-points/${chargingPointId}/status-ajax?force=1`);
            const data = await response.json();
            
            if (data.ok) {
                realtimeStatus = data.status;
                steveStatusSuccess = true;
                lastUpdate = data.updated_at;
                
                // Update the status badge
                updateStatusBadge(realtimeStatus, steveStatusSuccess);
            }
        } catch (error) {
            console.error('Failed to refresh status:', error);
        }
    }, 30000);
">
    <script>
    function updateStatusBadge(status, isRealtime) {
        const statusBadge = document.getElementById('cp-status-badge-{{ $chargingPoint->id }}');
        if (!statusBadge) return;
        
        const statusClasses = {
            'online': {
                bg: 'bg-green-100 dark:bg-green-900',
                text: 'text-green-800 dark:text-green-200',
                dot: 'bg-green-500',
                label: 'En ligne'
            },
            'offline': {
                bg: 'bg-red-100',
                text: 'text-red-800',
                dot: 'bg-red-500',
                label: 'Hors ligne'
            },
            'maintenance': {
                bg: 'bg-yellow-100',
                text: 'text-yellow-800',
                dot: 'bg-yellow-500',
                label: 'Maintenance'
            },
            'charging': {
                bg: 'bg-blue-100',
                text: 'text-blue-800',
                dot: 'bg-blue-500',
                label: 'En charge'
            },
            'reserved': {
                bg: 'bg-orange-100',
                text: 'text-orange-800',
                dot: 'bg-orange-500',
                label: 'RÃ©servÃ©'
            },
            'error': {
                bg: 'bg-red-100',
                text: 'text-red-800',
                dot: 'bg-red-500',
                label: 'Erreur'
            },
            'unknown': {
                bg: 'bg-gray-100',
                text: 'text-gray-800',
                dot: 'bg-gray-500',
                label: 'Inconnu'
            }
        };
        
        const info = statusClasses[status] || statusClasses['unknown'];
        const realtimeLabel = isRealtime ? '<span class="ml-1 text-xs ' + info.text + '">( temps réel)</span>' : '';
        const pulseClass = (status === 'online' || status === 'charging') ? ' animate-pulse' : '';
        
        statusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ' + info.bg + ' ' + info.text;
        statusBadge.innerHTML = '<span class="h-2 w-2 mr-1 rounded-full ' + info.dot + pulseClass + '"></span>' + info.label + realtimeLabel;
    }
    </script>
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
                <button @click="activeTab = 'overview'" 
                        :class="activeTab === 'overview' ? 'border-b-2 border-green-500 text-green-600 font-medium' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 transition-colors">
                    {{ __('Informations') }}
                </button>
                <button @click="activeTab = 'sessions'" 
                        :class="activeTab === 'sessions' ? 'border-b-2 border-green-500 text-green-600 font-medium' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 transition-colors">
                    {{ __('Sessions de recharges') }}
                </button>
                <button @click="activeTab = 'integrations'" 
                        :class="activeTab === 'integrations' ? 'border-b-2 border-green-500 text-green-600 font-medium' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 transition-colors">
                    {{ __('Intégration') }}
                </button>
                <button @click="activeTab = 'payments'" 
                        :class="activeTab === 'payments' ? 'border-b-2 border-green-500 text-green-600 font-medium' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 transition-colors">
                    {{ __('Paiements') }}
                </button>
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
                
                <!-- TAB: Overview (Informations) -->
                <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                
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
                                @php
                                    // Use realtime status from Steve API if available, otherwise fall back to local status
                                    $status = $realtimeStatus ?? $chargingPoint->status ?? 'unknown';
                                    $isRealtime = !empty($steveStatusSuccess);
                                @endphp
                                @if($status === 'online')
                                    <span id="cp-status-badge-{{ $chargingPoint->id }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-green-500 animate-pulse"></span>
                                        En ligne
                                        @if($isRealtime)<span class="ml-1 text-xs text-green-600">( temps réel)</span>@endif
                                    </span>
                                @elseif($status === 'offline')
                                    <span id="cp-status-badge-{{ $chargingPoint->id }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-red-500"></span>
                                        Hors ligne
                                        @if($isRealtime)<span class="ml-1 text-xs text-red-600">( temps réel)</span>@endif
                                    </span>
                                @elseif($status === 'maintenance')
                                    <span id="cp-status-badge-{{ $chargingPoint->id }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-yellow-500"></span>
                                        Maintenance
                                        @if($isRealtime)<span class="ml-1 text-xs text-yellow-600">( temps réel)</span>@endif
                                    </span>
                                @elseif($status === 'charging')
                                    <span id="cp-status-badge-{{ $chargingPoint->id }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-blue-500 animate-pulse"></span>
                                        En charge
                                        @if($isRealtime)<span class="ml-1 text-xs text-blue-600">( temps réel)</span>@endif
                                    </span>
                                @elseif($status === 'reserved')
                                    <span id="cp-status-badge-{{ $chargingPoint->id }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-orange-500"></span>
                                        RÃ©servÃ©
                                        @if($isRealtime)<span class="ml-1 text-xs text-orange-600">( temps rÃ©el)</span>@endif
                                    </span>
                                @elseif($status === 'error')
                                    <span id="cp-status-badge-{{ $chargingPoint->id }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-red-500"></span>
                                        Erreur
                                        @if($isRealtime)<span class="ml-1 text-xs text-red-600">( temps rÃ©el)</span>@endif
                                    </span>
                                @else
                                    <span id="cp-status-badge-{{ $chargingPoint->id }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-gray-500"></span>
                                        Inconnu
                                        @if($isRealtime)<span class="ml-1 text-xs text-gray-600">( temps réel)</span>@endif
                                    </span>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-3">
                                @can('update', $chargingPoint)
                                <a href="{{ route('charging-points.edit', $chargingPoint->id) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    {{ __('messages.edit') }}
                                </a>
                                @endcan
                                @can('delete', $chargingPoint)
                                <form action="{{ route('charging-points.destroy', $chargingPoint->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-lg text-red-700 bg-red-50 hover:bg-red-100 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        {{ __('messages.delete') }}
                                    </button>
                                </form>
                                @endcan
                                <a href="{{ route('public.charging-points.qr-code', $chargingPoint->id) }}" class="inline-flex items-center px-4 py-2 border border-green-300 shadow-sm text-sm font-medium rounded-lg text-green-700 bg-green-50 hover:bg-green-100 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    {{ __('messages.generate_qr_code') }}
                                </a>
                                @can('update', $chargingPoint)
                                <a href="{{ route('charging-points.ocpp-tag.index', $chargingPoint->id) }}" class="inline-flex items-center px-4 py-2 border border-indigo-300 shadow-sm text-sm font-medium rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    {{ __('messages.ocpp_tag_operations') }}
                                </a>
                                @endcan
                                <a href="{{ $publicReservationUrl }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white hover:bg-green-700 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ __('messages.reserve') }}
                                </a>
                                
                                <!-- Bouton d'information sur les parts - Masqué pour les opérateurs et partenaires -->
                                @if(auth()->check() && !auth()->user()->hasRole('operator') && !auth()->user()->hasRole('partner'))
                                <button type="button" id="parts-info-btn" class="inline-flex items-center px-4 py-2 border border-blue-300 shadow-sm text-sm font-medium rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors" onclick="showPartsInfoModal()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ __('messages.shares_details') }}
                                </button>
                                @endif

                            </div>
                        </div>
                    </div>
                    
                    <!-- Connection status alert (shown only if offline based on realtime status) -->
                    @if($realtimeStatus == 'offline' || $chargingPoint->status == 'offline')
                    <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM10 11a1 1 0 00-1 1v1a1 1 0 002 0v-1a1 1 0 00-1-1zm0-7.5a1 1 0 00-1 1v3a1 1 0 002 0v-3a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    {{ __('messages.charging_point_not_connected') }}
                                </h3>
                                <p class="text-sm text-yellow-700 mt-1">
                                    {{ __('messages.connect_charging_point_message') }}
                                </p>
                            </div>
                            <div class="ml-4">
                                <form action="{{ route('charging-points.update', $chargingPoint->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="online">
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                                        {{ __('messages.connect') }}
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
                            <h2 class="text-xl font-semibold text-gray-900">{{ __('messages.statistics') }}</h2>
                            <p class="text-sm text-gray-600 mt-1">{{ __('messages.station_performance') }}</p>
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
                                    <p class="text-sm font-medium text-gray-500">{{ __('messages.total_consumption') }}</p>
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

                <!-- Energy consumption chart -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">{{ __('messages.energy_consumption') }}</h3>
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
                            <h3 class="text-xl font-semibold text-gray-900">{{ __('messages.station_information') }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ __('messages.technical_details') }}</p>
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
                                        // Map status values to display text and CSS classes - use realtime status from Steve API
                                        $status = $realtimeStatus ?? $chargingPoint->status ?? 'unknown';
                                        $statusMap = [
                                            'online' => ['text' => 'En ligne', 'class' => 'bg-green-100 text-green-800'],
                                            'offline' => ['text' => 'Hors ligne', 'class' => 'bg-red-100 text-red-800'],
                                            'maintenance' => ['text' => 'Maintenance', 'class' => 'bg-yellow-100 text-yellow-800'],
                                            'charging' => ['text' => 'En charge', 'class' => 'bg-blue-100 text-blue-800'],
                                            'reserved' => ['text' => 'RÃ©servÃ©', 'class' => 'bg-orange-100 text-orange-800'],
                                            'error' => ['text' => 'Erreur', 'class' => 'bg-red-100 text-red-800'],
                                            'available' => ['text' => 'Disponible', 'class' => 'bg-green-100 text-green-800'],
                                        ];
                                        $currentStatus = strtolower($status);
                                        $defaultStatus = ['text' => ucfirst($status), 'class' => 'bg-gray-100 text-gray-800'];
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
                            <h3 class="text-xl font-semibold text-gray-900">{{ __('messages.available_reservation_types') }}</h3>
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
                                    <h4 class="text-xl font-semibold text-gray-900">{{ __('messages.reservation_by_energy') }}</h4>
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
                                            {{ number_format($pricingPlan->price_per_kwh, 2) }} EUR
                                        @else
                                            Variable
                                        @endif
                                    </span>
                                </div>
                                @if($pricingPlan && $pricingPlan->activation_fee)
                                <div class="flex items-center justify-between text-sm bg-white rounded-lg p-3">
                                    <span class="text-gray-600">Frais d'activation:</span>
                                    <span class="font-semibold text-blue-600">
                                        {{ number_format($pricingPlan->activation_fee, 2) }} EUR
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
                                                <div class="text-blue-600">{{ number_format($totalCost, 2) }} EUR</div>
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
                                    <h4 class="text-xl font-semibold text-gray-900">{{ __('messages.reservation_by_duration') }}</h4>
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
                                            {{ number_format($pricingPlan->price_per_minute, 2) }} EUR
                                        @else
                                            Variable
                                        @endif
                                    </span>
                                </div>
                                @if($pricingPlan && $pricingPlan->activation_fee)
                                <div class="flex items-center justify-between text-sm bg-white rounded-lg p-3">
                                    <span class="text-gray-600">Frais d'activation:</span>
                                    <span class="font-semibold text-green-600">
                                        {{ number_format($pricingPlan->activation_fee, 2) }} EUR
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
                                                <div class="text-green-600">{{ number_format($totalCost, 2) }} EUR</div>
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
                                                    {{ number_format($kwhTotal, 2) }} EUR
                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-green-600">Par minute ({{ $scenario['minutes'] }} min):</span>
                                                <span class="font-medium {{ !$kwhBetter ? 'text-green-600' : 'text-gray-600' }}">
                                                    {{ number_format($minuteTotal, 2) }} EUR
                                                </span>
                                            </div>
                                            @if($kwhTotal != $minuteTotal)
                                            <div class="text-xs text-gray-500 mt-1">
                                                <span class="font-medium {{ $kwhBetter ? 'text-green-600' : 'text-blue-600' }}">
                                                    {{ $kwhBetter ? 'kWh' : 'Minute' }} plus avantageux
                                                </span>
                                                ({{ number_format(abs($kwhTotal - $minuteTotal), 2) }} EUR de différence)
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
                                                {{ $pricingPlan->price_per_kwh > 0 ? number_format($pricingPlan->price_per_kwh, 2) . ' EUR' : 'Non configuré' }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700">Prix par minute:</span>
                                            <span class="ml-2 {{ $pricingPlan->price_per_minute > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $pricingPlan->price_per_minute > 0 ? number_format($pricingPlan->price_per_minute, 2) . ' EUR' : 'Non configuré' }}
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
                        <h3 class="text-lg font-medium text-gray-900">{{ __('messages.recent_reservations') }}</h3>
                        <a href="{{ route('reservations.index') }}?charging_point_id={{ $chargingPoint->id }}" class="text-sm text-green-600 hover:text-green-800">
                            {{ __('messages.view_all_reservations') }}
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
                        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('messages.no_reservation') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('messages.no_reservation_message') }}
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('reservations.create', $chargingPoint->id) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                {{ __('messages.create_reservation') }}
                            </a>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Reservation Statistics Section -->
                <div class="bg-white shadow-lg rounded-xl p-6 mb-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Statistiques des Réservations</h3>
                            <p class="text-sm text-gray-500">Vue d'ensemble des performances de la borne</p>
                        </div>
                        <div class="hidden sm:block">
                            <svg class="h-12 w-12 text-gray-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                    
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
                        $averageRevenue = $completedReservations > 0 ? ($totalRevenue / $completedReservations) : 0;
                    @endphp
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6">
                        <!-- Total Réservations Card -->
                        <div class="group relative bg-gradient-to-br from-blue-50 via-blue-50 to-blue-100 border border-blue-200 rounded-xl p-5 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1 overflow-hidden">
                            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-blue-200 rounded-full opacity-20 group-hover:opacity-30 transition-opacity"></div>
                            <div class="relative flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-blue-700 uppercase tracking-wider mb-2">{{ __('messages.total_reservations') }}</p>
                                    <p class="text-3xl font-bold text-blue-900 mb-1">{{ number_format($totalReservations, 0, ',', ' ') }}</p>
                                    <p class="text-xs text-blue-600">Toutes réservations</p>
                                </div>
                                <div class="flex-shrink-0 bg-blue-500 rounded-xl p-3 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Taux de Réussite Card -->
                        <div class="group relative bg-gradient-to-br from-green-50 via-green-50 to-emerald-100 border border-green-200 rounded-xl p-5 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1 overflow-hidden">
                            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-green-200 rounded-full opacity-20 group-hover:opacity-30 transition-opacity"></div>
                            <div class="relative flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-green-700 uppercase tracking-wider mb-2">{{ __('messages.success_rate') }}</p>
                                    <p class="text-3xl font-bold text-green-900 mb-1">{{ number_format($completionRate, 1) }}<span class="text-xl">%</span></p>
                                    <p class="text-xs text-green-600">{{ $completedReservations }} terminées</p>
                                </div>
                                <div class="flex-shrink-0 bg-green-500 rounded-xl p-3 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Revenus Totaux Card -->
                        <div class="group relative bg-gradient-to-br from-purple-50 via-purple-50 to-violet-100 border border-purple-200 rounded-xl p-5 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1 overflow-hidden">
                            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-purple-200 rounded-full opacity-20 group-hover:opacity-30 transition-opacity"></div>
                            <div class="relative flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-purple-700 uppercase tracking-wider mb-2">Revenus Totaux</p>
                                    <p class="text-3xl font-bold text-purple-900 mb-1">{{ number_format($totalRevenue, 2, ',', ' ') }}</p>
                                    <p class="text-xs text-purple-600 font-medium">EUR</p>
                                </div>
                                <div class="flex-shrink-0 bg-purple-500 rounded-xl p-3 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Moyenne/Réservation Card -->
                        <div class="group relative bg-gradient-to-br from-orange-50 via-amber-50 to-orange-100 border border-orange-200 rounded-xl p-5 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1 overflow-hidden">
                            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-orange-200 rounded-full opacity-20 group-hover:opacity-30 transition-opacity"></div>
                            <div class="relative flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-orange-700 uppercase tracking-wider mb-2">Moyenne/Réservation</p>
                                    <p class="text-3xl font-bold text-orange-900 mb-1">{{ number_format($averageRevenue, 2, ',', ' ') }}</p>
                                    <p class="text-xs text-orange-600 font-medium">EUR</p>
                                </div>
                                <div class="flex-shrink-0 bg-orange-500 rounded-xl p-3 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
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
                    <div class="relative bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-12 border-2 border-dashed border-gray-300 overflow-hidden">
                        <div class="absolute inset-0 bg-white opacity-50"></div>
                        <div class="relative text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-200 rounded-full mb-4">
                                <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Aucune donnée disponible</h3>
                            <p class="text-sm text-gray-500 max-w-md mx-auto">
                                Les statistiques de réservation apparaîtront automatiquement une fois que des réservations auront été effectuées sur cette borne de recharge.
                            </p>
                        </div>
                    </div>
                    @endif
                </div>

                </div>
                <!-- END TAB: Overview -->
                
                <!-- TAB: Integrations -->
                <div x-show="activeTab === 'integrations'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                
                <!-- Integration Section -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Intégration') }}</h3>
                    
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
                                        <label for="charge-box-id-display" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                            Charge Box ID <span class="text-red-500">*</span>
                                        </label>
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-1">
                                                <input type="text" 
                                                       id="charge-box-id-display"
                                                       value="{{ $chargingPoint->serial_number ?? $chargingPoint->charge_box_id ?? 'BORNE_' . $chargingPoint->id }}"
                                                       readonly
                                                       class="hidden w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm bg-gray-50 text-sm">
                                            </div>
                                            <button onclick="connectChargingPoint()" 
                                                    id="connect-btn"
                                                    class="inline-flex items-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                </svg>
                                                <span id="connect-btn-text">Connecter la borne</span>
                                            </button>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500 flex items-center">
                                            <svg class="h-3 w-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Numéro de série: {{ $chargingPoint->serial_number ?? 'N/A' }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500 flex items-center">
                                            <svg class="h-3 w-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Connexion automatique via API Steve
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">URL du serveur SteVe</label>
                                        <input type="text" 
                                               id="steve-server-url-input"
                                               value="{{ $chargingPoint->steve_server_url ?? 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/' }}"
                                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm transition-colors">
                                        <p class="mt-2 text-xs text-gray-500 flex items-center">
                                            <svg class="h-3 w-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            URL WebSocket du serveur SteVe OCPP
                                        </p>
                                    </div>
                                </div>
                                
                                @if($chargingPoint->websocket_url)
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">URL WebSocket complète</label>
                                    <div class="flex">
                                        <input type="text" 
                                               value="{{ $chargingPoint->websocket_url }}" 
                                               readonly 
                                               class="flex-1 block w-full px-4 py-3 border border-gray-300 rounded-l-lg shadow-sm bg-white text-sm font-mono">
                                        <button onclick="copyWebSocketUrl()" 
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
                                
                                <!-- QR Code Section -->
                                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-6 border border-green-200 mt-6">
                                    <div class="mb-4">
                                        <h4 class="text-xl font-semibold text-gray-900">QR Code de réservation</h4>
                                        <p class="text-sm text-gray-600 mt-1">Scannez ce code pour réserver cette borne</p>
                                    </div>
                                    @if($qrCodeUrl)
                                    <div class="flex justify-center py-4 bg-white rounded-lg border border-green-100">
                                        <img src="{{ $qrCodeUrl }}" alt="QR Code pour {{ $chargingPoint->name }}" class="w-48 h-48">
                                    </div>
                                    <div class="mt-3 text-center">
                                        <p class="text-xs text-gray-500 break-all">{{ $publicReservationUrl }}</p>
                                    </div>
                                    @else
                                    <div class="flex justify-center py-4 bg-gray-100 rounded-lg">
                                        <p class="text-sm text-gray-500">QR code non disponible</p>
                                    </div>
                                    @endif
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



                    </div>
                </div>
                
                </div>
                <!-- END TAB: Integrations -->
                
                <!-- TAB: Sessions -->
                <div x-show="activeTab === 'sessions'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

                <!-- Recent transactions -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Sessions de recharge') }}</h3>
                        <span class="text-sm text-gray-500">{{ isset($recentTransactions) ? count($recentTransactions) : 0 }} {{ __('sessions') }}</span>
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
                        {{ __('Aucune session de recharge récente') }}
                    </div>
                    @endif
                </div>
                
                </div>
                <!-- END TAB: Sessions -->
                
                <!-- TAB: Payments -->
                <div x-show="activeTab === 'payments'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="bg-white shadow rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Paiements') }}</h3>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-credit-card text-4xl text-gray-300 mb-3"></i>
                            <p>{{ __('Informations de paiement à venir') }}</p>
                        </div>
                    </div>
                </div>
                <!-- END TAB: Payments -->
                
            </div>

            <!-- Right sidebar - Details and Remote actions -->
            <div class="w-full lg:w-1/4 xl:w-1/5">
                <!-- Remote actions panel -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-900">{{ __('messages.remote_actions') }}</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ __('messages.control_charging_point_remotely') }}</p>
                    </div>
                    <div class="p-4">
                        @if(empty($chargingPoint->steve_charging_point_id))
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                <div class="flex items-start">
                                    <svg class="h-5 w-5 text-yellow-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-yellow-800">Point de charge non connecté à Steve</p>
                                        <p class="text-xs text-yellow-700 mt-1">Les actions à distance nécessitent un identifiant Steve configuré.</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <ul class="space-y-2">
                            <li>
                                <button 
                                    id="start-charging-btn-{{ $chargingPoint->id }}" 
                                    onclick="startChargingAction({{ $chargingPoint->id }})" 
                                    class="w-full text-left block px-4 py-3 text-green-600 hover:bg-green-50 rounded-lg transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed remote-action-btn"
                                    data-charging-point-id="{{ $chargingPoint->id }}"
                                    data-action="start"
                                    @if(empty($chargingPoint->steve_charging_point_id)) disabled title="Point de charge non connecté à Steve" @endif>
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3 action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="action-text">{{ __('messages.start_charging_remote') }}</span>
                                        <svg class="hidden h-4 w-4 ml-auto animate-spin action-spinner" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button 
                                    id="stop-charging-btn-{{ $chargingPoint->id }}" 
                                    onclick="stopChargingAction({{ $chargingPoint->id }})" 
                                    class="w-full text-left block px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed remote-action-btn"
                                    data-charging-point-id="{{ $chargingPoint->id }}"
                                    data-action="stop"
                                    @if(empty($chargingPoint->steve_charging_point_id)) disabled title="Point de charge non connecté à Steve" @endif>
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3 action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span class="action-text">{{ __('messages.stop_charging') }}</span>
                                        <svg class="hidden h-4 w-4 ml-auto animate-spin action-spinner" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button 
                                    id="unlock-connector-btn-{{ $chargingPoint->id }}" 
                                    onclick="unlockConnectorAction({{ $chargingPoint->id }})" 
                                    class="w-full text-left block px-4 py-3 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed remote-action-btn"
                                    data-charging-point-id="{{ $chargingPoint->id }}"
                                    data-action="unlock"
                                    @if(empty($chargingPoint->steve_charging_point_id)) disabled title="Point de charge non connecté à Steve" @endif>
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3 action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        <span class="action-text">{{ __('messages.unlock_connector') }}</span>
                                        <svg class="hidden h-4 w-4 ml-auto animate-spin action-spinner" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button 
                                    id="reset-charging-point-btn-{{ $chargingPoint->id }}" 
                                    onclick="resetChargingPointAction({{ $chargingPoint->id }})" 
                                    class="w-full text-left block px-4 py-3 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed remote-action-btn"
                                    data-charging-point-id="{{ $chargingPoint->id }}"
                                    data-action="reset"
                                    @if(empty($chargingPoint->steve_charging_point_id)) disabled title="Point de charge non connecté à Steve" @endif>
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3 action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span class="action-text">{{ __('messages.reset') }}</span>
                                        <svg class="hidden h-4 w-4 ml-auto animate-spin action-spinner" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button id="update-config-btn" onclick="updateChargingPointConfigAction({{ $chargingPoint->id }})" class="w-full text-left block px-4 py-3 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors font-medium">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ __('messages.update_parameters') }}
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button id="diagnostic-btn" onclick="getDiagnosticAction({{ $chargingPoint->id }})" class="w-full text-left block px-4 py-3 text-white hover:bg-green-50 rounded-lg transition-colors font-medium">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        Diagnostic
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button id="get-logs-btn" onclick="getLogsAction({{ $chargingPoint->id }})" class="w-full text-left block px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors font-medium">
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
</div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for interactive elements -->
<script>
    // Charts data handling would go here in real implementation
    // This is just a placeholder for demonstration purposes
    document.getElementById('stats-period').addEventListener('change', function() {
        // In a real implementation, this would fetch data for the selected period
        // and update the charts and statistics
        console.log('Period changed to:', this.value);
    });


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
    
    return `• ${kwh} kWh = ${totalCost.toFixed(2)} EUR (${estimatedDuration} min)`;
}).join('\n')}

Détail du calcul pour 10 kWh :
• Coût énergie: 10 kWh × ${pricingPlan.price_per_kwh} EUR = ${(10 * pricingPlan.price_per_kwh).toFixed(2)} EUR
${pricingPlan.activation_fee > 0 ? `• Frais d'activation: ${pricingPlan.activation_fee.toFixed(2)} EUR` : ''}
• Coût total: ${(10 * pricingPlan.price_per_kwh + (pricingPlan.activation_fee || 0)).toFixed(2)} EUR

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
    
    return `• ${minutes} min = ${totalCost.toFixed(2)} EUR (~${estimatedEnergy.toFixed(1)} kWh)`;
}).join('\n')}

Détail du calcul pour 60 minutes :
• Coût temps: 60 min × ${pricingPlan.price_per_minute} EUR = ${(60 * pricingPlan.price_per_minute).toFixed(2)} EUR
${pricingPlan.activation_fee > 0 ? `• Frais d'activation: ${pricingPlan.activation_fee.toFixed(2)} EUR` : ''}
• Coût total: ${(60 * pricingPlan.price_per_minute + (pricingPlan.activation_fee || 0)).toFixed(2)} EUR

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

    function copyWebSocketUrl() {
        const websocketUrlInput = document.querySelector('input[value*="ws://"]');
        if (websocketUrlInput) {
            websocketUrlInput.select();
            document.execCommand('copy');
            showNotification('URL WebSocket copiée !', 'success');
        }
    }

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
        @php
            // Profils potentiels
            $bpAdminCandidate = optional(optional($chargingPoint->integrator)->businessProfile);
            $bpIntegratorCandidate = optional(optional($chargingPoint->partner)->businessProfile);
            $bpDirect = optional($chargingPoint->businessProfile);

            // Part Admin (ordre de priorité): profil admin-intégrateur probable sur l'intégrateur, sinon profil direct borne, sinon valeur par défaut 10%
            $adminPct_operatorCreated = null;
            $adminPct_operatorCreated = $adminPct_operatorCreated ?? ($bpAdminCandidate->admin_fee_percentage ?? null);
            $adminPct_operatorCreated = $adminPct_operatorCreated ?? ($bpDirect->admin_fee_percentage ?? null);
            $adminPct_operatorCreated = (float) ($adminPct_operatorCreated ?? 10.0);

            // Part Intégrateur (ordre): profil intégrateur-opérateur sur le partenaire, sinon profil direct borne, sinon 0
            $integratorPct_operatorCreated = null;
            $integratorPct_operatorCreated = $integratorPct_operatorCreated ?? ($bpIntegratorCandidate->integrator_fee_percentage ?? null);
            $integratorPct_operatorCreated = $integratorPct_operatorCreated ?? ($bpDirect->integrator_fee_percentage ?? null);
            $integratorPct_operatorCreated = (float) ($integratorPct_operatorCreated ?? 0.0);

            // Part Opérateur = reste
            $operatorPct_operatorCreated = max(0, 100 - $adminPct_operatorCreated - $integratorPct_operatorCreated);

            // Scénario opérateur créé par Admin (pas d'intégrateur)
            $adminPct_adminCreated = (float) (($bpDirect->admin_fee_percentage ?? null) ?? 15.0);
            $integratorPct_adminCreated = 0.0;
            $operatorPct_adminCreated = max(0, 100 - $adminPct_adminCreated - $integratorPct_adminCreated);

            // Exemple de calcul
            $exampleAmount = 200.0;
            $t1IntegratorAmount = round($exampleAmount * ($integratorPct_operatorCreated / 100), 2);
            // Les frais Admin s'appliquent sur la part de l'intégrateur, pas sur le total
            $t2AdminAmount = round($t1IntegratorAmount * ($adminPct_operatorCreated / 100), 2);
            $finalIntegratorAmount = round($t1IntegratorAmount - $t2AdminAmount, 2);
            // La part opérateur = total - part intégrateur (avant déduction Admin)
            $operatorAmount_operatorCreated = round($exampleAmount - $t1IntegratorAmount, 2);
            $adminAmount_adminCreated = round($exampleAmount * ($adminPct_adminCreated / 100), 2);
            $operatorAmount_adminCreated = round($exampleAmount - $adminAmount_adminCreated, 2);

            // Noms des profils
            $bpAdminName = $bpAdminCandidate->name ?? ($bpDirect->name ?? null);
            $bpIntegratorName = $bpIntegratorCandidate->name ?? ($bpDirect->name ?? null);

            // Surcharger par les Business Profiles réels si fournis par le contrôleur
            if (isset($businessProfiles) && is_array($businessProfiles)) {
                $bpAdminName = $businessProfiles['admin_integrator']->name ?? $bpAdminName;
                $bpIntegratorName = $businessProfiles['integrator_operator']->name ?? $bpIntegratorName;
            }

            // Surcharger les pourcentages si un calcul réel est fourni
            if (isset($calculation) && is_array($calculation)) {
                if (isset($calculation['admin_percentage'])) {
                    $adminPct_operatorCreated = (float) $calculation['admin_percentage'];
                }
                if (isset($calculation['integrator_percentage'])) {
                    $integratorPct_operatorCreated = (float) $calculation['integrator_percentage'];
                }
                $operatorPct_operatorCreated = max(0, 100 - $adminPct_operatorCreated - $integratorPct_operatorCreated);
            }

            $hasRealProfiles = isset($businessProfiles) && is_array($businessProfiles) && isset($calculation) && is_array($calculation);
        @endphp
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
                        <!-- Business Profiles Appliqués -->
                        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg p-6 border border-indigo-200">
                            <h3 class="text-lg font-semibold text-indigo-900 mb-4 flex items-center">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Business Profiles Appliqués
                            </h3>
                            
                            <div class="space-y-6">
                                @php
                                    // Vérifier si l'integrator a un business_profile_id même si la relation n'est pas chargée
                                    $hasAdminProfile = false;
                                    $adminBP = null;
                                    if ($chargingPoint->integrator) {
                                        // Vérifier d'abord si business_profile_id existe
                                        if (isset($chargingPoint->integrator->business_profile_id) && $chargingPoint->integrator->business_profile_id) {
                                            // Si la relation n'est pas chargée, la charger maintenant
                                            if (!$chargingPoint->integrator->relationLoaded('businessProfile')) {
                                                $chargingPoint->integrator->load('businessProfile');
                                            }
                                            $adminBP = $chargingPoint->integrator->businessProfile;
                                            $hasAdminProfile = $adminBP !== null;
                                        } elseif ($chargingPoint->integrator->businessProfile) {
                                            // La relation est déjà chargée et existe
                                            $adminBP = $chargingPoint->integrator->businessProfile;
                                            $hasAdminProfile = $adminBP !== null;
                                        }
                                    }
                                    
                                    // Même chose pour le partner - Chercher le business profile créé par l'intégrateur pour ce partenaire
                                    $hasIntegratorProfile = false;
                                    $integratorBP = null;
                                    if ($chargingPoint->partner && $chargingPoint->integrator) {
                                        // Chercher le profil créé par l'intégrateur pour ce partenaire
                                        // Utiliser l'ID de l'intégrateur (modèle), pas l'ID de l'utilisateur
                                        $integratorBP = \App\Models\BusinessProfile::where('created_by_id', $chargingPoint->integrator->id)
                                            ->where('created_by_type', 'integrator')
                                            ->where('partner_id', $chargingPoint->partner->id)
                                            ->where('is_active', true)
                                            ->orderBy('created_at', 'desc')
                                            ->first();
                                        
                                        // Si pas trouvé, chercher un profil par défaut de l'intégrateur (sans partner_id spécifique)
                                        if (!$integratorBP) {
                                            $integratorBP = \App\Models\BusinessProfile::where('created_by_id', $chargingPoint->integrator->id)
                                                ->where('created_by_type', 'integrator')
                                                ->where('is_active', true)
                                                ->orderBy('created_at', 'desc')
                                                ->first();
                                        }
                                        
                                        // Fallback: utiliser le business profile du partenaire si aucun profil intégrateur trouvé
                                        if (!$integratorBP && $chargingPoint->partner->businessProfile) {
                                            $integratorBP = $chargingPoint->partner->businessProfile;
                                        }
                                        
                                        $hasIntegratorProfile = $integratorBP !== null;
                                    }
                                    
                                    // Vérifier les données complètes pour Admin
                                    $adminProfileComplete = false;
                                    $adminProfileError = null;
                                    if ($hasAdminProfile && $adminBP) {
                                        if (!$adminBP->name) {
                                            $adminProfileError = 'Le nom du business profile est manquant';
                                        } elseif (!$adminBP->transaction_fee_config) {
                                            $adminProfileError = 'La configuration des frais de transaction est manquante';
                                        } elseif (!$adminBP->charge_fee_config) {
                                            $adminProfileError = 'La configuration des frais de charge est manquante';
                                        } else {
                                            $adminProfileComplete = true;
                                        }
                                    } else {
                                        $adminProfileError = $chargingPoint->integrator 
                                            ? 'Aucun business profile associé à l\'intégrateur "' . ($chargingPoint->integrator->name ?? 'inconnu') . '"' 
                                            : 'Aucun intégrateur associé à cette borne';
                                    }
                                    
                                    // Vérifier les données complètes pour Intégrateur
                                    $integratorProfileComplete = false;
                                    $integratorProfileError = null;
                                    if ($hasIntegratorProfile && $integratorBP) {
                                        if (!$integratorBP->name) {
                                            $integratorProfileError = 'Le nom du business profile est manquant';
                                        } elseif (!$integratorBP->transaction_fee_config) {
                                            $integratorProfileError = 'La configuration des frais de transaction est manquante';
                                        } elseif (!$integratorBP->charge_fee_config) {
                                            $integratorProfileError = 'La configuration des frais de charge est manquante';
                                        } else {
                                            $integratorProfileComplete = true;
                                        }
                                    } else {
                                        $integratorProfileError = $chargingPoint->partner 
                                            ? 'Aucun business profile associé au partenaire/opérateur "' . ($chargingPoint->partner->name ?? 'inconnu') . '"' 
                                            : 'Aucun partenaire/opérateur associé à cette borne';
                                    }
                                @endphp
                                
                                <!-- Appliqués par Admin aux Intégrateurs -->
                                <div class="bg-white rounded-lg p-5 border {{ $adminProfileComplete ? 'border-indigo-100' : 'border-red-200' }} shadow-sm">
                                    <h4 class="font-semibold {{ $adminProfileComplete ? 'text-indigo-800' : 'text-red-800' }} mb-3 text-base">Appliqués par Admin aux Intégrateurs</h4>
                                    
                                    @if($adminProfileComplete && $adminBP)
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between pb-2 border-b border-indigo-100">
                                            <span class="text-indigo-700 font-medium">{{ $adminBP->name }}</span>
                                        </div>
                                        <div class="text-sm text-gray-600 mb-2">
                                            <span class="font-medium text-indigo-800">Type:</span>
                                            <span class="ml-2">Admin → Intégrateur</span>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="text-sm">
                                                <span class="font-semibold text-indigo-800">Détails des Frais Appliqués</span>
                                            </div>
                                            <div class="bg-gray-50 rounded p-3 space-y-3">
                                                @php
                                                    // Décoder la configuration des frais de transaction
                                                    $transactionConfig = is_string($adminBP->transaction_fee_config) 
                                                        ? json_decode($adminBP->transaction_fee_config, true) 
                                                        : ($adminBP->transaction_fee_config ?? []);
                                                    
                                                    // Décoder la configuration des frais de charge
                                                    $chargeConfig = is_string($adminBP->charge_fee_config) 
                                                        ? json_decode($adminBP->charge_fee_config, true) 
                                                        : ($adminBP->charge_fee_config ?? []);
                                                    
                                                    // Déterminer les types de frais de transaction
                                                    $transactionTypes = $transactionConfig['types'] ?? [];
                                                    $hasFixedTransaction = in_array('fixed', $transactionTypes) || (isset($transactionConfig['fixed_amount']) && $transactionConfig['fixed_amount'] > 0);
                                                    $hasPercentageTransaction = in_array('percentage', $transactionTypes) || (isset($transactionConfig['percentage']) && $transactionConfig['percentage'] > 0);
                                                    
                                                    // Calculer les totaux
                                                    $totalFixedAmount = (isset($transactionConfig['fixed_amount']) ? (float)$transactionConfig['fixed_amount'] : 0) + 
                                                                       (isset($chargeConfig['fixed_amount']) ? (float)$chargeConfig['fixed_amount'] : 0);
                                                    $totalPercentage = (isset($transactionConfig['percentage']) ? (float)$transactionConfig['percentage'] : 0) + 
                                                                      (isset($chargeConfig['percentage']) ? (float)$chargeConfig['percentage'] : 0);
                                                @endphp
                                                
                                                <!-- Config Transaction -->
                                                <div class="border-b border-gray-200 pb-2">
                                                    <div class="text-xs font-semibold text-indigo-700 mb-2">Config Transaction:</div>
                                                    <div class="space-y-1.5 text-xs">
                                                        @if(!empty($transactionTypes))
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-gray-600">Types:</span>
                                                                <div class="flex gap-1">
                                                                    @foreach($transactionTypes as $type)
                                                                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs font-medium">
                                                                            {{ $type === 'fixed' ? 'Fixe' : ($type === 'percentage' ? 'Pourcentage' : ucfirst($type)) }}
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                        @if($hasFixedTransaction && isset($transactionConfig['fixed_amount']))
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-gray-600">Montant fixe:</span>
                                                                <span class="font-semibold text-gray-800">{{ number_format($transactionConfig['fixed_amount'], 2) }} €</span>
                                                            </div>
                                                        @endif
                                                        @if($hasPercentageTransaction && isset($transactionConfig['percentage']))
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-gray-600">Pourcentage:</span>
                                                                <span class="font-semibold text-gray-800">{{ number_format($transactionConfig['percentage'], 2) }}%</span>
                                                            </div>
                                                        @endif
                                                        @if(empty($transactionConfig) || (!$hasFixedTransaction && !$hasPercentageTransaction))
                                                            <div class="text-gray-500 italic">Aucun frais de transaction configuré</div>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <!-- Config Charge -->
                                                <div class="border-b border-gray-200 pb-2">
                                                    <div class="text-xs font-semibold text-indigo-700 mb-2">Config Charge:</div>
                                                    <div class="space-y-1.5 text-xs">
                                                        @if(isset($chargeConfig['fixed_amount']) && $chargeConfig['fixed_amount'] > 0)
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-gray-600">Montant fixe:</span>
                                                                <span class="font-semibold text-gray-800">{{ number_format($chargeConfig['fixed_amount'], 2) }} €</span>
                                                            </div>
                                                        @endif
                                                        @if(isset($chargeConfig['percentage']) && $chargeConfig['percentage'] > 0)
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-gray-600">Pourcentage:</span>
                                                                <span class="font-semibold text-gray-800">{{ number_format($chargeConfig['percentage'], 2) }}%</span>
                                                            </div>
                                                        @endif
                                                        @if((!isset($chargeConfig['fixed_amount']) || $chargeConfig['fixed_amount'] == 0) && (!isset($chargeConfig['percentage']) || $chargeConfig['percentage'] == 0))
                                                            <div class="text-gray-500 italic">Aucun frais de charge configuré</div>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <!-- Résumé Total -->
                                                @if($totalFixedAmount > 0 || $totalPercentage > 0)
                                                <div class="bg-indigo-50 border border-indigo-200 rounded p-2.5">
                                                    <div class="text-xs font-bold text-indigo-900 mb-2">Résumé Total:</div>
                                                    <div class="space-y-1.5 text-xs">
                                                        @if($totalFixedAmount > 0)
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-indigo-700 font-medium">Total montant fixe:</span>
                                                                <span class="font-bold text-indigo-900">{{ number_format($totalFixedAmount, 2) }} €</span>
                                                            </div>
                                                        @endif
                                                        @if($totalPercentage > 0)
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-indigo-700 font-medium">Total pourcentage:</span>
                                                                <span class="font-bold text-indigo-900">
                                                                    @php
                                                                        $transactionPercentage = isset($transactionConfig['percentage']) ? (float)$transactionConfig['percentage'] : 0;
                                                                        $chargePercentage = isset($chargeConfig['percentage']) ? (float)$chargeConfig['percentage'] : 0;
                                                                        $parts = [];
                                                                        if($transactionPercentage > 0) {
                                                                            $parts[] = number_format($transactionPercentage, 2) . '%';
                                                                        }
                                                                        if($chargePercentage > 0) {
                                                                            $parts[] = number_format($chargePercentage, 2) . '%';
                                                                        }
                                                                    @endphp
                                                                    @if(count($parts) > 1)
                                                                        {{ implode(' + ', $parts) }} = {{ number_format($totalPercentage, 2) }}%
                                                                    @else
                                                                        {{ number_format($totalPercentage, 2) }}%
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @else
                                    <!-- Erreur avec croix rouge -->
                                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <h5 class="text-sm font-semibold text-red-800 mb-1">Erreur de configuration</h5>
                                                <p class="text-sm text-red-700">{{ $adminProfileError }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                
                                <!-- Appliqués par Intégrateurs aux Opérateurs/Partenaires -->
                                <div class="bg-white rounded-lg p-5 border {{ $integratorProfileComplete ? 'border-indigo-100' : 'border-red-200' }} shadow-sm">
                                    <h4 class="font-semibold {{ $integratorProfileComplete ? 'text-indigo-800' : 'text-red-800' }} mb-3 text-base">Appliqués par Intégrateurs aux Opérateurs/Partenaires</h4>
                                    
                                    @if($integratorProfileComplete && $integratorBP)
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between pb-2 border-b border-indigo-100">
                                            <span class="text-indigo-700 font-medium">{{ $integratorBP->name }}</span>
                                        </div>
                                        <div class="text-sm text-gray-600 mb-2">
                                            <span class="font-medium text-indigo-800">Type:</span>
                                            <span class="ml-2">Intégrateur → Opérateur</span>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="text-sm">
                                                <span class="font-semibold text-indigo-800">Détails des Frais Appliqués</span>
                                            </div>
                                            <div class="bg-gray-50 rounded p-3 space-y-3">
                                                @php
                                                    // Décoder la configuration des frais de transaction
                                                    $transactionConfig = is_string($integratorBP->transaction_fee_config) 
                                                        ? json_decode($integratorBP->transaction_fee_config, true) 
                                                        : ($integratorBP->transaction_fee_config ?? []);
                                                    
                                                    // Décoder la configuration des frais de charge
                                                    $chargeConfig = is_string($integratorBP->charge_fee_config) 
                                                        ? json_decode($integratorBP->charge_fee_config, true) 
                                                        : ($integratorBP->charge_fee_config ?? []);
                                                    
                                                    // Déterminer les types de frais de transaction
                                                    $transactionTypes = $transactionConfig['types'] ?? [];
                                                    $hasFixedTransaction = in_array('fixed', $transactionTypes) || (isset($transactionConfig['fixed_amount']) && $transactionConfig['fixed_amount'] > 0);
                                                    $hasPercentageTransaction = in_array('percentage', $transactionTypes) || (isset($transactionConfig['percentage']) && $transactionConfig['percentage'] > 0);
                                                    
                                                    // Calculer les totaux
                                                    $totalFixedAmount = (isset($transactionConfig['fixed_amount']) ? (float)$transactionConfig['fixed_amount'] : 0) + 
                                                                       (isset($chargeConfig['fixed_amount']) ? (float)$chargeConfig['fixed_amount'] : 0);
                                                    $totalPercentage = (isset($transactionConfig['percentage']) ? (float)$transactionConfig['percentage'] : 0) + 
                                                                      (isset($chargeConfig['percentage']) ? (float)$chargeConfig['percentage'] : 0);
                                                @endphp
                                                
                                                <!-- Config Transaction -->
                                                <div class="border-b border-gray-200 pb-2">
                                                    <div class="text-xs font-semibold text-indigo-700 mb-2">Config Transaction:</div>
                                                    <div class="space-y-1.5 text-xs">
                                                        @if(!empty($transactionTypes))
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-gray-600">Types:</span>
                                                                <div class="flex gap-1">
                                                                    @foreach($transactionTypes as $type)
                                                                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs font-medium">
                                                                            {{ $type === 'fixed' ? 'Fixe' : ($type === 'percentage' ? 'Pourcentage' : ucfirst($type)) }}
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                        @if($hasFixedTransaction && isset($transactionConfig['fixed_amount']))
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-gray-600">Montant fixe:</span>
                                                                <span class="font-semibold text-gray-800">{{ number_format($transactionConfig['fixed_amount'], 2) }} €</span>
                                                            </div>
                                                        @endif
                                                        @if($hasPercentageTransaction && isset($transactionConfig['percentage']))
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-gray-600">Pourcentage:</span>
                                                                <span class="font-semibold text-gray-800">{{ number_format($transactionConfig['percentage'], 2) }}%</span>
                                                            </div>
                                                        @endif
                                                        @if(empty($transactionConfig) || (!$hasFixedTransaction && !$hasPercentageTransaction))
                                                            <div class="text-gray-500 italic">Aucun frais de transaction configuré</div>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <!-- Config Charge -->
                                                <div class="border-b border-gray-200 pb-2">
                                                    <div class="text-xs font-semibold text-indigo-700 mb-2">Config Charge:</div>
                                                    <div class="space-y-1.5 text-xs">
                                                        @if(isset($chargeConfig['fixed_amount']) && $chargeConfig['fixed_amount'] > 0)
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-gray-600">Montant fixe:</span>
                                                                <span class="font-semibold text-gray-800">{{ number_format($chargeConfig['fixed_amount'], 2) }} €</span>
                                                            </div>
                                                        @endif
                                                        @if(isset($chargeConfig['percentage']) && $chargeConfig['percentage'] > 0)
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-gray-600">Pourcentage:</span>
                                                                <span class="font-semibold text-gray-800">{{ number_format($chargeConfig['percentage'], 2) }}%</span>
                                                            </div>
                                                        @endif
                                                        @if((!isset($chargeConfig['fixed_amount']) || $chargeConfig['fixed_amount'] == 0) && (!isset($chargeConfig['percentage']) || $chargeConfig['percentage'] == 0))
                                                            <div class="text-gray-500 italic">Aucun frais de charge configuré</div>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <!-- Résumé Total -->
                                                @if($totalFixedAmount > 0 || $totalPercentage > 0)
                                                <div class="bg-indigo-50 border border-indigo-200 rounded p-2.5">
                                                    <div class="text-xs font-bold text-indigo-900 mb-2">Résumé Total:</div>
                                                    <div class="space-y-1.5 text-xs">
                                                        @if($totalFixedAmount > 0)
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-indigo-700 font-medium">Total montant fixe:</span>
                                                                <span class="font-bold text-indigo-900">{{ number_format($totalFixedAmount, 2) }} €</span>
                                                            </div>
                                                        @endif
                                                        @if($totalPercentage > 0)
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-indigo-700 font-medium">Total pourcentage:</span>
                                                                <span class="font-bold text-indigo-900">
                                                                    @php
                                                                        $transactionPercentage = isset($transactionConfig['percentage']) ? (float)$transactionConfig['percentage'] : 0;
                                                                        $chargePercentage = isset($chargeConfig['percentage']) ? (float)$chargeConfig['percentage'] : 0;
                                                                        $parts = [];
                                                                        if($transactionPercentage > 0) {
                                                                            $parts[] = number_format($transactionPercentage, 2) . '%';
                                                                        }
                                                                        if($chargePercentage > 0) {
                                                                            $parts[] = number_format($chargePercentage, 2) . '%';
                                                                        }
                                                                    @endphp
                                                                    @if(count($parts) > 1)
                                                                        {{ implode(' + ', $parts) }} = {{ number_format($totalPercentage, 2) }}%
                                                                    @else
                                                                        {{ number_format($totalPercentage, 2) }}%
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @else
                                    <!-- Erreur avec croix rouge -->
                                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <h5 class="text-sm font-semibold text-red-800 mb-1">Erreur de configuration</h5>
                                                <p class="text-sm text-red-700">{{ $integratorProfileError }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Calcul Concret -->
                        @php
                            // S'assurer que $pricingPlan est disponible
                            $pricingPlan = $chargingPoint->pricingPlan ?? $pricingPlan ?? null;
                            
                            // Charger les business profiles nécessaires pour le calcul
                            $hasAdminProfile = false;
                            $adminBP = null;
                            if ($chargingPoint->integrator) {
                                // Vérifier d'abord si business_profile_id existe
                                if (isset($chargingPoint->integrator->business_profile_id) && $chargingPoint->integrator->business_profile_id) {
                                    // Si la relation n'est pas chargée, la charger maintenant
                                    if (!$chargingPoint->integrator->relationLoaded('businessProfile')) {
                                        $chargingPoint->integrator->load('businessProfile');
                                    }
                                    $adminBP = $chargingPoint->integrator->businessProfile;
                                    $hasAdminProfile = $adminBP !== null;
                                } elseif ($chargingPoint->integrator->businessProfile) {
                                    // La relation est déjà chargée et existe
                                    $adminBP = $chargingPoint->integrator->businessProfile;
                                    $hasAdminProfile = $adminBP !== null;
                                }
                            }
                            
                            // Même chose pour le partner - Chercher le business profile créé par l'intégrateur pour ce partenaire
                            $hasIntegratorProfile = false;
                            $integratorBP = null;
                            if ($chargingPoint->partner && $chargingPoint->integrator) {
                                // Chercher le profil créé par l'intégrateur pour ce partenaire
                                // Utiliser l'ID de l'intégrateur (modèle), pas l'ID de l'utilisateur
                                $integratorBP = \App\Models\BusinessProfile::where('created_by_id', $chargingPoint->integrator->id)
                                    ->where('created_by_type', 'integrator')
                                    ->where('partner_id', $chargingPoint->partner->id)
                                    ->where('is_active', true)
                                    ->orderBy('created_at', 'desc')
                                    ->first();
                                
                                // Si pas trouvé, chercher un profil par défaut de l'intégrateur (sans partner_id spécifique)
                                if (!$integratorBP) {
                                    $integratorBP = \App\Models\BusinessProfile::where('created_by_id', $chargingPoint->integrator->id)
                                        ->where('created_by_type', 'integrator')
                                        ->where('is_active', true)
                                        ->orderBy('created_at', 'desc')
                                        ->first();
                                }
                                
                                // Fallback: utiliser le business profile du partenaire si aucun profil intégrateur trouvé
                                if (!$integratorBP && $chargingPoint->partner->businessProfile) {
                                    $integratorBP = $chargingPoint->partner->businessProfile;
                                }
                                
                                $hasIntegratorProfile = $integratorBP !== null;
                            }
                            
                            // Calculer un exemple concret basé sur les business profiles réels
                            // Fixer le montant à 120.00 EUR comme demandé
                            $exampleAmount = 120.0;
                            
                            // Calculer les parts selon la logique hiérarchique
                            $adminPercentage = 0;
                            $integratorPercentage = 0;
                            $operatorPercentage = 0;
                            $adminAmount = 0;
                            $integratorAmount = 0;
                            $operatorAmount = 0;
                            $hasCalculation = false;
                            $calculationError = null;
                            
                            // Variables pour l'affichage des détails
                            $integratorFixedAmountDisplay = 0;
                            $integratorPercentageDisplay = 0;
                            $adminFixedAmountDisplay = 0;
                            $adminPercentageDisplay = 0;
                            $integratorAmountBrut = 0;
                            
                            // Scénario: Borne créée par Opérateur (avec intégrateur et business profiles)
                            if ($hasAdminProfile && $hasIntegratorProfile && $adminBP && $integratorBP) {
                                $hasCalculation = true;
                                
                                try {
                                    // Calculer les frais Intégrateur depuis charge_fee_config (Intégrateur → Opérateur)
                                    $integratorFixedAmount = 0;
                                    $integratorPercentage = 0;
                                    
                                    if ($integratorBP->charge_fee_config) {
                                        $chargeConfig = is_string($integratorBP->charge_fee_config) 
                                            ? json_decode($integratorBP->charge_fee_config, true) 
                                            : $integratorBP->charge_fee_config;
                                        
                                        if (is_array($chargeConfig)) {
                                            $integratorFixedAmount = (float) ($chargeConfig['fixed_amount'] ?? 0);
                                            $integratorPercentage = (float) ($chargeConfig['percentage'] ?? 0);
                                        }
                                    }
                                    
                                    // Calculer les frais Admin depuis transaction_fee_config + charge_fee_config (Admin → Intégrateur)
                                    $adminFixedAmount = 0;
                                    $adminPercentage = 0;
                                    
                                    // Transaction fee config
                                    if ($adminBP->transaction_fee_config) {
                                        $transactionConfig = is_string($adminBP->transaction_fee_config) 
                                            ? json_decode($adminBP->transaction_fee_config, true) 
                                            : $adminBP->transaction_fee_config;
                                        
                                        if (is_array($transactionConfig)) {
                                            $adminFixedAmount += (float) ($transactionConfig['fixed_amount'] ?? 0);
                                            $adminPercentage += (float) ($transactionConfig['percentage'] ?? 0);
                                        }
                                    }
                                    
                                    // Charge fee config
                                    if ($adminBP->charge_fee_config) {
                                        $chargeConfig = is_string($adminBP->charge_fee_config) 
                                            ? json_decode($adminBP->charge_fee_config, true) 
                                            : $adminBP->charge_fee_config;
                                        
                                        if (is_array($chargeConfig)) {
                                            $adminFixedAmount += (float) ($chargeConfig['fixed_amount'] ?? 0);
                                            $adminPercentage += (float) ($chargeConfig['percentage'] ?? 0);
                                        }
                                    }
                                    
                                    // Vérifier que nous avons des configurations valides
                                    if ($integratorFixedAmount == 0 && $integratorPercentage == 0 && $adminFixedAmount == 0 && $adminPercentage == 0) {
                                        throw new \Exception('Les configurations de frais ne peuvent pas être extraites des business profiles. Vérifiez charge_fee_config et transaction_fee_config.');
                                    }
                                    
                                    // Calcul selon la logique hiérarchique:
                                    // 1. Frais Intégrateur = fixed_amount + (total * percentage / 100) depuis charge_fee_config
                                    $integratorAmountBrut = round($integratorFixedAmount + ($exampleAmount * $integratorPercentage / 100), 2);
                                    
                                    // 2. Frais Admin = fixed_amount + (total * percentage / 100) depuis transaction_fee_config + charge_fee_config
                                    // Les frais admin sont calculés sur le TOTAL (pas sur la part intégrateur)
                                    $adminAmount = round($adminFixedAmount + ($exampleAmount * $adminPercentage / 100), 2);
                                    
                                    // 3. Part intégrateur finale = frais intégrateur - frais admin (les frais admin sont déduits de la part intégrateur)
                                    $integratorAmount = round($integratorAmountBrut - $adminAmount, 2);
                                    
                                    // 4. Part opérateur = total - frais intégrateur brut
                                    $operatorAmount = round($exampleAmount - $integratorAmountBrut, 2);
                                    
                                    // Recalculer les pourcentages finaux basés sur le total
                                    $adminPercentageFinal = $adminAmount > 0 ? round(($adminAmount / $exampleAmount) * 100, 4) : 0;
                                    $integratorPercentageFinal = $integratorAmount > 0 ? round(($integratorAmount / $exampleAmount) * 100, 2) : 0;
                                    $operatorPercentage = $operatorAmount > 0 ? round(($operatorAmount / $exampleAmount) * 100, 2) : 0;
                                    
                                    // Utiliser les pourcentages finaux pour l'affichage
                                    $adminPercentage = $adminPercentageFinal;
                                    $integratorPercentage = $integratorPercentageFinal;
                                    
                                    // Stocker les détails pour l'affichage
                                    $integratorFixedAmountDisplay = $integratorFixedAmount;
                                    $integratorPercentageDisplay = $integratorPercentage;
                                    $adminFixedAmountDisplay = $adminFixedAmount;
                                    $adminPercentageDisplay = $adminPercentage;
                                    
                                } catch (\Exception $e) {
                                    $calculationError = 'Erreur lors du calcul: ' . $e->getMessage();
                                }
                            } elseif ($chargingPoint->integrator && $chargingPoint->partner) {
                                $calculationError = 'Les business profiles ne sont pas complètement configurés pour effectuer le calcul';
                            } else {
                                $calculationError = 'Cette borne n\'a pas d\'intégrateur et/ou de partenaire associé';
                            }
                        @endphp
                        
                        @if($hasCalculation && !$calculationError)
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-6 border border-purple-200">
                            <h3 class="text-lg font-semibold text-purple-900 mb-4 flex items-center">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Calcul Concret (Transaction {{ number_format($exampleAmount, 2) }} EUR)
                            </h3>
                            
                            <div class="bg-white rounded-lg p-5 border border-purple-100 shadow-sm">
                                <h4 class="font-semibold text-purple-800 mb-3 text-base">Borne créée par Opérateur</h4>
                                <p class="text-sm text-purple-600 mb-4">Avec intégrateur et business profiles appliqués</p>
                                
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                        <span class="text-gray-700 font-medium">Montant total de la transaction:</span>
                                        <span class="text-gray-900 font-bold">{{ number_format($exampleAmount, 2) }} EUR</span>
                                    </div>
                                    
                                    <!-- Détails Frais Intégrateur -->
                                    <div class="bg-blue-50 rounded p-3 border border-blue-200">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-blue-800 font-semibold text-sm">Frais Intégrateur (charge_fee_config):</span>
                                            <span class="text-blue-900 font-bold">{{ number_format($integratorAmountBrut, 2) }} EUR</span>
                                        </div>
                                        <div class="text-xs text-blue-700 space-y-1">
                                            @if($integratorFixedAmountDisplay > 0)
                                            <div class="flex justify-between">
                                                <span>Montant fixe:</span>
                                                <span>{{ number_format($integratorFixedAmountDisplay, 2) }} EUR</span>
                                            </div>
                                            @endif
                                            @if($integratorPercentageDisplay > 0)
                                            <div class="flex justify-between">
                                                <span>Pourcentage ({{ number_format($integratorPercentageDisplay, 2) }}%):</span>
                                                <span>{{ number_format($exampleAmount * $integratorPercentageDisplay / 100, 2) }} EUR</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Détails Frais Admin -->
                                    <div class="bg-red-50 rounded p-3 border border-red-200">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-red-800 font-semibold text-sm">Frais Admin (transaction_fee_config + charge_fee_config):</span>
                                            <span class="text-red-900 font-bold">{{ number_format($adminAmount, 2) }} EUR</span>
                                        </div>
                                        <div class="text-xs text-red-700 space-y-1">
                                            @if($adminFixedAmountDisplay > 0)
                                            <div class="flex justify-between">
                                                <span>Montant fixe total:</span>
                                                <span>{{ number_format($adminFixedAmountDisplay, 2) }} EUR</span>
                                            </div>
                                            @endif
                                            @if($adminPercentageDisplay > 0)
                                            <div class="flex justify-between">
                                                <span>Pourcentage total ({{ number_format($adminPercentageDisplay, 2) }}%):</span>
                                                <span>{{ number_format($exampleAmount * $adminPercentageDisplay / 100, 2) }} EUR</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Résumé des Parts -->
                                    <div class="pt-2 border-t border-gray-200 space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-700">Part Admin ({{ number_format($adminPercentage, 4) }}%):</span>
                                            <span class="text-gray-900 font-semibold">{{ number_format($adminAmount, 2) }} EUR</span>
                                        </div>
                                        
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-700">Part Intégrateur Net ({{ number_format($integratorPercentage, 2) }}%):</span>
                                            <span class="text-gray-900 font-semibold">{{ number_format($integratorAmount, 2) }} EUR</span>
                                        </div>
                                        
                                        <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                                            <span class="text-gray-700 font-medium">Part Opérateur/Partenaire:</span>
                                            <span class="text-gray-900 font-bold">{{ number_format($operatorAmount, 2) }} EUR</span>
                                        </div>
                                        
                                        <!-- Vérification -->
                                        <div class="flex justify-between items-center pt-2 border-t border-gray-300 mt-2">
                                            <span class="text-gray-600 text-xs italic">Total vérification:</span>
                                            <span class="text-gray-700 text-xs font-semibold">
                                                {{ number_format($adminAmount + $integratorAmount + $operatorAmount, 2) }} EUR
                                                @if(abs(($adminAmount + $integratorAmount + $operatorAmount) - $exampleAmount) < 0.01)
                                                    <span class="text-green-600">✓</span>
                                                @else
                                                    <span class="text-red-600">✗</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    
                                    @if($adminBP || $integratorBP)
                                    <div class="mt-4 pt-3 border-t border-purple-100 space-y-2">
                                        @if($adminBP)
                                        <div>
                                            <span class="text-sm text-purple-600">Business Profile Admin: </span>
                                            <span class="text-sm font-semibold text-purple-800">{{ $adminBP->name }}</span>
                                        </div>
                                        @endif
                                        @if($integratorBP)
                                        <div>
                                            <span class="text-sm text-purple-600">Business Profile Intégrateur: </span>
                                            <span class="text-sm font-semibold text-purple-800">{{ $integratorBP->name }}</span>
                                        </div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @elseif($calculationError)
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h5 class="text-sm font-semibold text-red-800 mb-1">Calcul impossible</h5>
                                    <p class="text-sm text-red-700">{{ $calculationError }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
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

    // Fonction pour connecter automatiquement la borne - ouvre la popup
    function connectChargingPoint() {
        // Ouvrir la popup de connexion SteVe
        openSteveConnectionModal();
    }
    
    // Fonction pour afficher les messages de connexion
    function showConnectionMessage(message, type = 'info') {
        // Créer ou mettre à jour le conteneur de messages
        let messageContainer = document.getElementById('connection-messages');
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.id = 'connection-messages';
            messageContainer.className = 'mt-4';
            
            // Insérer après le bouton de connexion
            const connectSection = document.querySelector('.space-y-6');
            if (connectSection) {
                connectSection.appendChild(messageContainer);
            }
        }
        
        // Créer le message
        const messageDiv = document.createElement('div');
        messageDiv.className = `p-3 rounded-lg text-sm ${
            type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
            type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
            'bg-blue-100 text-blue-800 border border-blue-200'
        }`;
        
        messageDiv.innerHTML = `
            <div class="flex items-center">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${
                        type === 'success' ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' :
                        type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' :
                        'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                    }" />
                </svg>
                <span>${message}</span>
            </div>
        `;
        
        // Vider le conteneur et ajouter le nouveau message
        messageContainer.innerHTML = '';
        messageContainer.appendChild(messageDiv);
        
        // Supprimer le message après 5 secondes
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }
    
    // Variable pour stocker l'interval de vérification du statut
    let connectionStatusInterval = null;
    let stopConnectionStatusCheck = false;
    
    // Fonction pour vérifier le statut de connexion
    async function checkConnectionStatus() {
        // Arrêter si désactivé
        if (stopConnectionStatusCheck) {
            return;
        }
        
        const chargingPointId = {{ $chargingPoint->id }};
        
        try {
            // Récupérer le token CSRF de manière sécurisée
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
            
            const response = await fetch(`/api/auto-connect/status/${chargingPointId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin' // Nécessaire pour envoyer les cookies de session Sanctum
            });
            
            // Vérifier si la réponse est une erreur 401 (Unauthorized)
            if (response.status === 401) {
                // Arrêter silencieusement les vérifications répétées en cas d'erreur 401
                // (l'utilisateur n'est probablement pas authentifié avec Sanctum)
                stopConnectionStatusCheck = true;
                if (connectionStatusInterval) {
                    clearInterval(connectionStatusInterval);
                    connectionStatusInterval = null;
                }
                return; // Sortir silencieusement sans logger d'erreur dans la console
            }
            
            // Vérifier si la réponse est ok
            if (!response.ok) {
                console.error(`Erreur HTTP ${response.status} lors de la vérification du statut`);
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                const connectBtn = document.getElementById('connect-btn');
                const connectBtnText = document.getElementById('connect-btn-text');
                
                // Vérifier que les éléments existent avant de les modifier
                if (connectBtn && connectBtnText) {
                    if (data.data.connected) {
                        connectBtn.classList.remove('bg-green-600', 'hover:bg-green-700', 'bg-red-600', 'hover:bg-red-700', 'bg-gray-600', 'hover:bg-gray-700');
                        connectBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                        connectBtnText.textContent = 'Connecté';
                    } else {
                        connectBtn.classList.remove('bg-green-600', 'hover:bg-green-700', 'bg-red-600', 'hover:bg-red-700');
                        connectBtn.classList.add('bg-gray-600', 'hover:bg-gray-700');
                        connectBtnText.textContent = 'Déconnecté';
                    }
                }
            }
        } catch (error) {
            console.error('Erreur lors de la vérification du statut:', error);
            // En cas d'erreur réseau ou autre, continuer à essayer
        }
    }
    
    // Fonction pour tester la connectivité
    async function testConnectivity() {
        try {
            showConnectionMessage('Test de connectivité en cours...', 'info');
            
            // Récupérer le token CSRF de manière sécurisée
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
            
            const response = await fetch('/api/auto-connect/test-connectivity', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin' // Nécessaire pour envoyer les cookies de session Sanctum
            });
            
            // Vérifier si la réponse est une erreur 401 (Unauthorized)
            if (response.status === 401) {
                showConnectionMessage('Authentification requise pour tester la connectivité', 'error');
                return;
            }
            
            if (!response.ok) {
                showConnectionMessage(`Erreur HTTP ${response.status} lors du test`, 'error');
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                showConnectionMessage('Service de connexion automatique opérationnel!', 'success');
            } else {
                showConnectionMessage(`Erreur de connectivité: ${data.message}`, 'error');
            }
            
        } catch (error) {
            showConnectionMessage(`Erreur de test: ${error.message}`, 'error');
        }
    }
    
    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier le statut de connexion au chargement
        checkConnectionStatus();
        
        // Vérifier le statut toutes les 30 secondes (seulement si pas d'erreur 401)
        if (!stopConnectionStatusCheck) {
            connectionStatusInterval = setInterval(function() {
                if (!stopConnectionStatusCheck) {
                    checkConnectionStatus();
                } else if (connectionStatusInterval) {
                    clearInterval(connectionStatusInterval);
                    connectionStatusInterval = null;
                }
            }, 30000);
        }
    });

    // Fonction pour ouvrir la popup de connexion SteVe
    function openSteveConnectionModal() {
        const modal = document.getElementById('steve-connection-modal');
        if (modal) {
            modal.classList.remove('hidden');
            // Récupérer le statut de l'API Steve
            fetchSteveApiStatus();
        }
    }

    // Fonction pour fermer la popup
    function closeSteveConnectionModal() {
        const modal = document.getElementById('steve-connection-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    // Fonction pour récupérer le statut de l'API Steve
    async function fetchSteveApiStatus() {
        const statusContainer = document.getElementById('steve-api-status');
        const loadingIndicator = document.getElementById('steve-status-loading');
        const wsUrlDisplay = document.getElementById('websocket-url-display');
        const chargingPointId = {{ $chargingPoint->id }};
        
        if (!statusContainer || !loadingIndicator) return;

        // Afficher l'indicateur de chargement et masquer le JSON
        loadingIndicator.classList.remove('hidden');
        statusContainer.classList.add('hidden');
        statusContainer.textContent = '';

        try {
            // Générer l'URL WebSocket avec le numéro de série (charge box ID)
            const chargeBoxId = document.getElementById('charge-box-id-display').value;
            const wsBaseUrl = 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/';
            const wsUrl = wsBaseUrl + chargeBoxId;
            
            // Afficher l'URL WebSocket immédiatement
            if (wsUrlDisplay) {
                wsUrlDisplay.textContent = wsUrl;
            }

            // Appeler la route web dédiée pour obtenir le statut complet
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            // Utiliser la route web qui combine tous les statuts
            const response = await fetch(`/steve-integration/charging-points/${chargingPointId}/complete-api-status`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            let apiStatus;
            if (response.ok) {
                apiStatus = await response.json();
                // S'assurer que l'URL WebSocket est correcte
                if (!apiStatus.websocket_url) {
                    apiStatus.websocket_url = wsUrl;
                }
            } else {
                // Si la route web échoue, construire un statut d'erreur
                const errorData = await response.json().catch(() => ({}));
                apiStatus = {
                    websocket_url: wsUrl,
                    charge_box_id: chargeBoxId,
                    charging_point_id: chargingPointId,
                    timestamp: new Date().toISOString(),
                    success: false,
                    error: errorData.message || `HTTP ${response.status}`,
                    test_connection: { success: false, error: 'Failed to fetch' },
                    charger_status: { success: false, error: 'Failed to fetch' },
                    charger_info: { success: false, error: 'Failed to fetch' }
                };
            }

            // Masquer l'indicateur de chargement et afficher le JSON
            loadingIndicator.classList.add('hidden');
            statusContainer.classList.remove('hidden');
            
            // Afficher le statut JSON formaté avec syntax highlighting
            statusContainer.textContent = JSON.stringify(apiStatus, null, 2);
            
        } catch (error) {
            // En cas d'erreur, masquer le loader et afficher l'erreur
            loadingIndicator.classList.add('hidden');
            statusContainer.classList.remove('hidden');
            
            const errorStatus = {
                success: false,
                error: error.message,
                message: 'Erreur lors de la récupération du statut de l\'API Steve',
                timestamp: new Date().toISOString(),
                websocket_url: document.getElementById('websocket-url-display')?.textContent || 'N/A',
                charge_box_id: document.getElementById('charge-box-id-display')?.value || 'N/A'
            };
            
            statusContainer.textContent = JSON.stringify(errorStatus, null, 2);
            console.error('Steve API Status Error:', error);
        }
    }
</script>

<!-- Popup de connexion SteVe avec petit écran noir -->
<div id="steve-connection-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 text-center sm:p-0">
        <!-- Backdrop noir -->
        <div class="fixed inset-0 bg-black bg-opacity-95 transition-opacity" aria-hidden="true" onclick="closeSteveConnectionModal()"></div>
        
        <!-- Modal Content - Small Black Screen -->
        <div class="relative inline-block bg-black border-2 border-green-500 rounded-lg text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full" style="box-shadow: 0 0 20px rgba(34, 197, 94, 0.3);">
            <!-- Header compact -->
            <div class="bg-black border-b border-green-500 px-4 py-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-green-400 uppercase tracking-wide" id="modal-title">
                        SteVe API Status
                    </h3>
                    <button onclick="closeSteveConnectionModal()" class="text-green-400 hover:text-green-300 transition-colors focus:outline-none">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body - Small Black Screen with Green Text -->
            <div class="bg-black px-4 py-4">
                <!-- WebSocket URL - Compact -->
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-green-400 mb-1.5 uppercase tracking-wide">WebSocket URL</label>
                    <div class="bg-black border border-green-500 rounded px-3 py-2">
                        <p id="websocket-url-display" class="text-xs text-green-400 break-all font-mono leading-relaxed">
                            Chargement...
                        </p>
                    </div>
                </div>

                <!-- API Status - Compact Scrollable Area -->
                <div>
                    <label class="block text-xs font-semibold text-green-400 mb-1.5 uppercase tracking-wide">Statut API Steve</label>
                    <div class="bg-black border border-green-500 rounded px-3 py-2 min-h-[250px] max-h-[400px] overflow-auto" style="scrollbar-width: thin; scrollbar-color: rgba(34, 197, 94, 0.3) transparent;">
                        <!-- Loading Indicator -->
                        <div id="steve-status-loading" class="flex items-center justify-center h-full min-h-[200px]">
                            <div class="text-center">
                                <svg class="w-6 h-6 text-green-400 animate-spin mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <p class="text-green-400 text-xs font-mono">Chargement du statut...</p>
                            </div>
                        </div>
                        <!-- JSON Status Display -->
                        <pre id="steve-api-status" class="text-green-400 text-[11px] font-mono whitespace-pre-wrap leading-relaxed hidden"></pre>
                    </div>
                </div>

                <!-- Action Buttons - Compact -->
                <div class="mt-4 flex justify-end space-x-2">
                    <button onclick="fetchSteveApiStatus()" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded border border-green-500 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500">
                        Actualiser
                    </button>
                    <button onclick="closeSteveConnectionModal()" class="px-3 py-1.5 bg-black hover:bg-gray-900 text-green-400 text-xs font-semibold rounded border border-green-500 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom scrollbar pour l'écran noir */
    #steve-connection-modal pre::-webkit-scrollbar,
    #steve-connection-modal div[style*="overflow-auto"]::-webkit-scrollbar {
        width: 6px;
    }
    #steve-connection-modal pre::-webkit-scrollbar-track,
    #steve-connection-modal div[style*="overflow-auto"]::-webkit-scrollbar-track {
        background: #000000;
    }
    #steve-connection-modal pre::-webkit-scrollbar-thumb,
    #steve-connection-modal div[style*="overflow-auto"]::-webkit-scrollbar-thumb {
        background: rgba(34, 197, 94, 0.4);
        border-radius: 3px;
    }
    #steve-connection-modal pre::-webkit-scrollbar-thumb:hover,
    #steve-connection-modal div[style*="overflow-auto"]::-webkit-scrollbar-thumb:hover {
        background: rgba(34, 197, 94, 0.6);
    }
</style>

<!-- Script pour le rafraîchissement automatique du statut -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const badge = document.getElementById('cp-status-badge-{{ $chargingPoint->id }}');
    if (!badge) return;

    // Ne rafraîchir que si la borne a un steve_charging_point_id configuré
    @if($chargingPoint->steve_charging_point_id)
    let isUpdating = false;
    
    function updateBadge(status) {
        const statusMap = {
            'online': {
                className: 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                html: '<span class="h-2 w-2 mr-1 rounded-full bg-green-500"></span>En ligne'
            },
            'offline': {
                className: 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800',
                html: '<span class="h-2 w-2 mr-1 rounded-full bg-red-500"></span>Hors ligne'
            },
            'maintenance': {
                className: 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800',
                html: '<span class="h-2 w-2 mr-1 rounded-full bg-yellow-500"></span>Maintenance'
            },
            'charging': {
                className: 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800',
                html: '<span class="h-2 w-2 mr-1 rounded-full bg-blue-500"></span>En charge'
            },
            'reserved': {
                className: 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800',
                html: '<span class="h-2 w-2 mr-1 rounded-full bg-orange-500"></span>RÃ©servÃ©'
            },
            'error': {
                className: 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800',
                html: '<span class="h-2 w-2 mr-1 rounded-full bg-red-500"></span>Erreur'
            },
            'unknown': {
                className: 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800',
                html: '<span class="h-2 w-2 mr-1 rounded-full bg-gray-500"></span>Inconnu'
            }
        };
        const statusConfig = statusMap[status] || statusMap['unknown'];
        badge.className = statusConfig.className;
        badge.innerHTML = statusConfig.html;
    }

    async function fetchStatus(forceRefresh = false) {
        // Éviter les requêtes simultanées
        if (isUpdating) {
            return;
        }
        isUpdating = true;

        try {
            const url = "{{ route('charging-points.status-ajax', $chargingPoint->id) }}" + (forceRefresh ? '?force=1' : '');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            const res = await fetch(url, {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                method: 'GET'
            });

            if (!res.ok) {
                console.warn('Status fetch failed with status:', res.status);
                return;
            }

            const json = await res.json();
            if (json.ok && json.status) {
                updateBadge(json.status);
            } else {
                console.warn('Invalid status response:', json);
            }
        } catch (e) {
            console.error('Error fetching charging point status from Steve API:', e);
        } finally {
            isUpdating = false;
        }
    }

    // Initial fetch with force refresh to get real-time status from Steve API
    fetchStatus(true);
    
    // Periodic refresh every 30 seconds
    const statusInterval = setInterval(() => {
        fetchStatus(false);
    }, 30000); // every 30 seconds
    
    // Expose function for manual refresh if needed
    window.refreshChargingPointStatus = function() {
        fetchStatus(true);
    };

    // Nettoyer l'intervalle quand la page est quittée
    // Utilise pagehide au lieu de beforeunload (API moderne, non dépréciée)
    window.addEventListener('pagehide', function() {
        if (statusInterval) {
            clearInterval(statusInterval);
        }
    }, { capture: true });
    @endif

    // Fonction pour déclencher une recharge et afficher la réponse
    // Fonction utilitaire pour gérer l'état des boutons d'action
    function setButtonLoading(chargingPointId, action, isLoading) {
        const button = document.querySelector(`[data-charging-point-id="${chargingPointId}"][data-action="${action}"]`);
        if (!button) return;
        
        const icon = button.querySelector('.action-icon');
        const text = button.querySelector('.action-text');
        const spinner = button.querySelector('.action-spinner');
        
        if (isLoading) {
            button.disabled = true;
            if (icon) icon.classList.add('hidden');
            if (spinner) spinner.classList.remove('hidden');
            if (text) {
                const originalText = text.textContent;
                text.textContent = 'Envoi...';
                text.dataset.originalText = originalText;
            }
        } else {
            button.disabled = false;
            if (icon) icon.classList.remove('hidden');
            if (spinner) spinner.classList.add('hidden');
            if (text && text.dataset.originalText) {
                text.textContent = text.dataset.originalText;
                delete text.dataset.originalText;
            }
        }
    }

    async function startChargingAction(chargingPointId) {
        // Vérifier si le point de charge a un ID Steve
        const button = document.querySelector(`[data-charging-point-id="${chargingPointId}"][data-action="start"]`);
        if (button && button.disabled) {
            if (typeof showNotification === 'function') {
                showNotification('Ce point de charge n\'est pas connecté à Steve. Veuillez configurer un identifiant Steve.', 'error');
            }
            return;
        }

        // Activer l'état de chargement
        setButtonLoading(chargingPointId, 'start', true);

        // Afficher le modal
        const modal = document.getElementById('steve-response-modal');
        const modalContent = document.getElementById('steve-response-content');
        const modalStatus = document.getElementById('steve-response-status');
        const downloadContainer = document.getElementById('download-buttons-container');
        
        // Nettoyer les boutons de téléchargement précédents
        if (downloadContainer) {
            downloadContainer.innerHTML = '';
        }
        
        // Demander le connectorId et idTag à l'utilisateur
        const connectorId = prompt('ID du connecteur (par défaut: 1):', '1') || '1';
        const idTag = prompt('ID Tag (RFID) pour démarrer la recharge:', 'USR12345') || 'USR12345';
        
        if (!connectorId || !idTag) {
            setButtonLoading(chargingPointId, 'start', false);
            return; // L'utilisateur a annulé
        }

        if (modal) {
            modal.classList.remove('hidden');
            modalContent.textContent = 'Envoi de la commande START à l\'API Steve...';
            modalStatus.textContent = 'En attente...';
        }

        try {
            const response = await fetch(`{{ url('/charging-points') }}/${chargingPointId}/remote/start`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    connectorId: parseInt(connectorId),
                    idTag: idTag
                })
            });

            const data = await response.json();
            
            // Formater la réponse JSON pour l'affichage
            const responseText = JSON.stringify(data, null, 2);
            
            // Mettre à jour le contenu du modal
            modalContent.textContent = responseText;
            
            // Mettre à jour le statut
            if (data.success === true) {
                if (modalStatus) {
                    modalStatus.textContent = '✓ Succès - Commande acceptée';
                    modalStatus.className = 'text-green-400 font-bold text-sm mb-2';
                }
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Commande START envoyée avec succès', 'success');
                }
            } else {
                if (modalStatus) {
                    modalStatus.textContent = '✗ Échec';
                    modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
                }
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Échec de la commande START', 'error');
                }
            }

        } catch (error) {
            if (modalContent) {
                modalContent.textContent = `Erreur: ${error.message}\n\n${error.stack || ''}`;
            }
            if (modalStatus) {
                modalStatus.textContent = '✗ Erreur';
                modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
            }
            if (typeof showNotification === 'function') {
                showNotification('Erreur lors de l\'envoi de la commande: ' + error.message, 'error');
            }
        } finally {
            // Désactiver l'état de chargement
            setButtonLoading(chargingPointId, 'start', false);
        }
    }

    // Fonction pour arrêter une recharge et afficher la réponse
    async function stopChargingAction(chargingPointId) {
        // Vérifier si le point de charge a un ID Steve
        const button = document.querySelector(`[data-charging-point-id="${chargingPointId}"][data-action="stop"]`);
        if (button && button.disabled) {
            if (typeof showNotification === 'function') {
                showNotification('Ce point de charge n\'est pas connecté à Steve. Veuillez configurer un identifiant Steve.', 'error');
            }
            return;
        }

        // Activer l'état de chargement
        setButtonLoading(chargingPointId, 'stop', true);

        // Demander le connectorId et transactionId à l'utilisateur
        const connectorId = prompt('ID du connecteur (par défaut: 1):', '1') || '1';
        const transactionId = prompt('ID de la transaction à arrêter (optionnel):', '');
        
        if (!connectorId) {
            setButtonLoading(chargingPointId, 'stop', false);
            return; // L'utilisateur a annulé
        }

        // Afficher le modal
        const modal = document.getElementById('steve-response-modal');
        const modalContent = document.getElementById('steve-response-content');
        const modalStatus = document.getElementById('steve-response-status');
        const downloadContainer = document.getElementById('download-buttons-container');
        
        // Nettoyer les boutons de téléchargement précédents
        if (downloadContainer) {
            downloadContainer.innerHTML = '';
        }
        
        if (modal) {
            modal.classList.remove('hidden');
            modalContent.textContent = 'Envoi de la commande STOP à l\'API Steve...';
            modalStatus.textContent = 'En attente...';
        }

        const payload = {
            connectorId: parseInt(connectorId)
        };
        if (transactionId) {
            payload.transactionId = transactionId;
        }

        try {
            const response = await fetch(`{{ url('/charging-points') }}/${chargingPointId}/remote/stop`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            
            // Formater la réponse JSON pour l'affichage
            const responseText = JSON.stringify(data, null, 2);
            
            // Mettre à jour le contenu du modal
            modalContent.textContent = responseText;
            
            // Mettre à jour le statut
            if (data.success === true) {
                if (modalStatus) {
                    modalStatus.textContent = '✓ Succès - Commande acceptée';
                    modalStatus.className = 'text-green-400 font-bold text-sm mb-2';
                }
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Commande STOP envoyée avec succès', 'success');
                }
            } else {
                if (modalStatus) {
                    modalStatus.textContent = '✗ Échec';
                    modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
                }
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Échec de la commande STOP', 'error');
                }
            }

        } catch (error) {
            if (modalContent) {
                modalContent.textContent = `Erreur: ${error.message}\n\n${error.stack || ''}`;
            }
            if (modalStatus) {
                modalStatus.textContent = '✗ Erreur';
                modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
            }
            if (typeof showNotification === 'function') {
                showNotification('Erreur lors de l\'envoi de la commande: ' + error.message, 'error');
            }
        } finally {
            // Désactiver l'état de chargement
            setButtonLoading(chargingPointId, 'stop', false);
        }
    }

    // Fonction pour débloquer un connecteur et afficher la réponse
    async function unlockConnectorAction(chargingPointId) {
        // Vérifier si le point de charge a un ID Steve
        const button = document.querySelector(`[data-charging-point-id="${chargingPointId}"][data-action="unlock"]`);
        if (button && button.disabled) {
            if (typeof showNotification === 'function') {
                showNotification('Ce point de charge n\'est pas connecté à Steve. Veuillez configurer un identifiant Steve.', 'error');
            }
            return;
        }

        // Activer l'état de chargement
        setButtonLoading(chargingPointId, 'unlock', true);

        // Demander le connectorId à l'utilisateur
        const connectorId = prompt('ID du connecteur à débloquer (par défaut: 1):', '1') || '1';
        
        if (!connectorId) {
            setButtonLoading(chargingPointId, 'unlock', false);
            return; // L'utilisateur a annulé
        }

        // Afficher le modal
        const modal = document.getElementById('steve-response-modal');
        const modalContent = document.getElementById('steve-response-content');
        const modalStatus = document.getElementById('steve-response-status');
        const downloadContainer = document.getElementById('download-buttons-container');
        
        // Nettoyer les boutons de téléchargement précédents
        if (downloadContainer) {
            downloadContainer.innerHTML = '';
        }
        
        if (modal) {
            modal.classList.remove('hidden');
            modalContent.textContent = 'Envoi de la commande UNLOCK à l\'API Steve...';
            modalStatus.textContent = 'En attente...';
        }

        try {
            const response = await fetch(`{{ url('/charging-points') }}/${chargingPointId}/remote/unlock`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    connectorId: parseInt(connectorId)
                })
            });

            const data = await response.json();
            
            // Formater la réponse JSON pour l'affichage
            const responseText = JSON.stringify(data, null, 2);
            
            // Mettre à jour le contenu du modal
            modalContent.textContent = responseText;
            
            // Mettre à jour le statut
            if (data.success === true) {
                if (modalStatus) {
                    modalStatus.textContent = '✓ Succès - Commande acceptée';
                    modalStatus.className = 'text-green-400 font-bold text-sm mb-2';
                }
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Commande UNLOCK envoyée avec succès', 'success');
                }
            } else {
                if (modalStatus) {
                    modalStatus.textContent = '✗ Échec';
                    modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
                }
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Échec de la commande UNLOCK', 'error');
                }
            }

        } catch (error) {
            if (modalContent) {
                modalContent.textContent = `Erreur: ${error.message}\n\n${error.stack || ''}`;
            }
            if (modalStatus) {
                modalStatus.textContent = '✗ Erreur';
                modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
            }
            if (typeof showNotification === 'function') {
                showNotification('Erreur lors de l\'envoi de la commande: ' + error.message, 'error');
            }
        } finally {
            // Désactiver l'état de chargement
            setButtonLoading(chargingPointId, 'unlock', false);
        }
    }

    // Fonction pour réinitialiser un point de charge et afficher la réponse
    async function resetChargingPointAction(chargingPointId) {
        // Vérifier si le point de charge a un ID Steve
        const button = document.querySelector(`[data-charging-point-id="${chargingPointId}"][data-action="reset"]`);
        if (button && button.disabled) {
            if (typeof showNotification === 'function') {
                showNotification('Ce point de charge n\'est pas connecté à Steve. Veuillez configurer un identifiant Steve.', 'error');
            }
            return;
        }

        // Activer l'état de chargement
        setButtonLoading(chargingPointId, 'reset', true);

        // Demander le type de réinitialisation (Hard ou Soft)
        let type = prompt('Type de réinitialisation (Hard/Soft):', 'Soft');
        
        if (!type) {
            setButtonLoading(chargingPointId, 'reset', false);
            return; // L'utilisateur a annulé
        }
        
        // Normaliser la casse
        type = type.charAt(0).toUpperCase() + type.slice(1).toLowerCase();
        
        if (type !== 'Hard' && type !== 'Soft') {
            alert('Type invalide. Utilisez "Hard" ou "Soft".');
            setButtonLoading(chargingPointId, 'reset', false);
            return;
        }

        // Afficher le modal
        const modal = document.getElementById('steve-response-modal');
        const modalContent = document.getElementById('steve-response-content');
        const modalStatus = document.getElementById('steve-response-status');
        const downloadContainer = document.getElementById('download-buttons-container');
        
        // Nettoyer les boutons de téléchargement précédents
        if (downloadContainer) {
            downloadContainer.innerHTML = '';
        }
        
        if (modal) {
            modal.classList.remove('hidden');
            modalContent.textContent = 'Envoi de la commande RESET à l\'API Steve...';
            modalStatus.textContent = 'En attente...';
        }

        try {
            const response = await fetch(`{{ url('/charging-points') }}/${chargingPointId}/remote/reset`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    type: type
                })
            });

            const data = await response.json();
            
            // Formater la réponse JSON pour l'affichage
            const responseText = JSON.stringify(data, null, 2);
            
            // Mettre à jour le contenu du modal
            modalContent.textContent = responseText;
            
            // Mettre à jour le statut
            if (data.success === true) {
                if (modalStatus) {
                    modalStatus.textContent = '✓ Succès - Commande acceptée';
                    modalStatus.className = 'text-green-400 font-bold text-sm mb-2';
                }
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Commande RESET envoyée avec succès', 'success');
                }
            } else {
                if (modalStatus) {
                    modalStatus.textContent = '✗ Échec';
                    modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
                }
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Échec de la commande RESET', 'error');
                }
            }

        } catch (error) {
            if (modalContent) {
                modalContent.textContent = `Erreur: ${error.message}\n\n${error.stack || ''}`;
            }
            if (modalStatus) {
                modalStatus.textContent = '✗ Erreur';
                modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
            }
            if (typeof showNotification === 'function') {
                showNotification('Erreur lors de l\'envoi de la commande: ' + error.message, 'error');
            }
        } finally {
            // Désactiver l'état de chargement
            setButtonLoading(chargingPointId, 'reset', false);
        }
    }

    // Fonction pour mettre à jour la configuration et afficher la réponse
    async function updateChargingPointConfigAction(chargingPointId) {
        // Demander la clé et la valeur
        const key = prompt('Clé de configuration (ex: MaxCurrent, HeartbeatInterval):');
        if (!key) {
            return; // L'utilisateur a annulé
        }

        const value = prompt(`Valeur pour "${key}":`);
        if (!value) {
            return; // L'utilisateur a annulé
        }

        // Afficher le modal
        const modal = document.getElementById('steve-response-modal');
        const modalContent = document.getElementById('steve-response-content');
        const modalStatus = document.getElementById('steve-response-status');
        const downloadContainer = document.getElementById('download-buttons-container');
        
        // Nettoyer les boutons de téléchargement précédents
        if (downloadContainer) {
            downloadContainer.innerHTML = '';
        }
        
        modal.classList.remove('hidden');
        modalContent.textContent = 'Envoi de la requête de mise à jour de configuration à l\'API Steve...';
        modalStatus.textContent = 'En attente...';

        try {
            const response = await fetch(`/charging-points/${chargingPointId}/action/config`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    key: key,
                    value: value
                })
            });

            const data = await response.json();
            
            // Formater la réponse JSON pour l'affichage
            const responseText = JSON.stringify(data, null, 2);
            
            // Mettre à jour le contenu du modal
            modalContent.textContent = responseText;
            
            // Mettre à jour le statut
            if (data.ok === true) {
                modalStatus.textContent = '✓ Succès';
                modalStatus.className = 'text-green-400 font-bold text-sm mb-2';
            } else {
                modalStatus.textContent = '✗ Échec';
                modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
            }

        } catch (error) {
            modalContent.textContent = `Erreur: ${error.message}\n\n${error.stack || ''}`;
            modalStatus.textContent = '✗ Erreur';
            modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
        }
    }

    // Fonction pour récupérer les diagnostics et afficher la réponse
    async function getDiagnosticAction(chargingPointId) {
        // Afficher le modal
        const modal = document.getElementById('steve-response-modal');
        const modalContent = document.getElementById('steve-response-content');
        const modalStatus = document.getElementById('steve-response-status');
        const downloadContainer = document.getElementById('download-buttons-container');
        
        // Nettoyer les boutons de téléchargement précédents
        if (downloadContainer) {
            downloadContainer.innerHTML = '';
        }
        
        modal.classList.remove('hidden');
        modalContent.textContent = 'Envoi de la requête de diagnostic à l\'API Steve...';
        modalStatus.textContent = 'En attente...';

        try {
            const response = await fetch(`/charging-points/${chargingPointId}/action/diagnostic`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({})
            });

            const data = await response.json();
            
            // Formater la réponse JSON pour l'affichage
            const responseText = JSON.stringify(data, null, 2);
            
            // Mettre à jour le contenu du modal
            modalContent.textContent = responseText;
            
            // Mettre à jour le statut
            if (data.ok === true) {
                modalStatus.textContent = '✓ Succès';
                modalStatus.className = 'text-green-400 font-bold text-sm mb-2';
            } else {
                modalStatus.textContent = '✗ Échec';
                modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
            }

        } catch (error) {
            modalContent.textContent = `Erreur: ${error.message}\n\n${error.stack || ''}`;
            modalStatus.textContent = '✗ Erreur';
            modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
        }
    }

    // Fonction pour récupérer les logs et afficher la réponse avec lien de téléchargement
    async function getLogsAction(chargingPointId) {
        // Demander le type de log (optionnel)
        const logType = prompt('Type de log (DiagnosticsLog par défaut):', 'DiagnosticsLog') || 'DiagnosticsLog';

        // Afficher le modal
        const modal = document.getElementById('steve-response-modal');
        const modalContent = document.getElementById('steve-response-content');
        const modalStatus = document.getElementById('steve-response-status');
        const downloadContainer = document.getElementById('download-buttons-container');
        
        // Nettoyer les boutons de téléchargement précédents
        if (downloadContainer) {
            downloadContainer.innerHTML = '';
        }
        
        modal.classList.remove('hidden');
        modalContent.textContent = 'Envoi de la requête de logs à l\'API Steve...';
        modalStatus.textContent = 'En attente...';

        try {
            const response = await fetch(`/charging-points/${chargingPointId}/action/logs`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    log_type: logType
                })
            });

            const data = await response.json();
            
            // Formater la réponse JSON pour l'affichage
            let responseText = JSON.stringify(data, null, 2);
            
            // Ajouter le lien de téléchargement si disponible
            if (data.ok === true && data.data && data.data.download_link) {
                responseText += '\n\n=== LIEN DE TÉLÉCHARGEMENT ===\n';
                responseText += data.data.download_link + '\n';
            }
            
            // Mettre à jour le contenu du modal
            modalContent.textContent = responseText;
            
            // Mettre à jour le statut
            if (data.ok === true) {
                modalStatus.textContent = '✓ Succès';
                modalStatus.className = 'text-green-400 font-bold text-sm mb-2';
                
                // Si un lien de téléchargement est disponible, le rendre cliquable
                if (data.data && data.data.download_link) {
                    // Ajouter un bouton de téléchargement au modal
                    const downloadContainer = document.getElementById('download-buttons-container');
                    if (downloadContainer) {
                        const downloadBtn = document.createElement('a');
                        downloadBtn.href = data.data.download_link;
                        downloadBtn.target = '_blank';
                        downloadBtn.className = 'bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1.5 px-3 rounded text-sm transition-colors download-link-btn';
                        downloadBtn.textContent = '📥 Télécharger';
                        downloadContainer.appendChild(downloadBtn);
                    }
                }
            } else {
                modalStatus.textContent = '✗ Échec';
                modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
            }

        } catch (error) {
            modalContent.textContent = `Erreur: ${error.message}\n\n${error.stack || ''}`;
            modalStatus.textContent = '✗ Erreur';
            modalStatus.className = 'text-red-400 font-bold text-sm mb-2';
        }
    }

    // Fermer le modal en cliquant dessus ou avec la touche Escape
    const modalElement = document.getElementById('steve-response-modal');
    if (modalElement) {
        modalElement.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                // Supprimer les boutons de téléchargement dynamiques lors de la fermeture
                const downloadContainer = document.getElementById('download-buttons-container');
                if (downloadContainer) {
                    downloadContainer.innerHTML = '';
                }
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                modalElement.classList.add('hidden');
                // Supprimer les boutons de téléchargement dynamiques lors de la fermeture
                const downloadContainer = document.getElementById('download-buttons-container');
                if (downloadContainer) {
                    downloadContainer.innerHTML = '';
                }
            }
        });
    }
});
</script>

<!-- Modal pour afficher la réponse de l'API Steve -->
<div id="steve-response-modal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-95 flex items-center justify-center p-2">
    <div class="bg-black border border-green-500 rounded-md max-w-lg w-full max-h-[85vh] overflow-hidden flex flex-col shadow-2xl">
        <div class="px-3 py-2 border-b border-green-500 flex justify-between items-center">
            <div class="flex-1">
                <h3 class="text-green-400 font-bold text-sm">Réponse API Steve</h3>
                <p id="steve-response-status" class="text-green-400 font-semibold text-xs mt-0.5"></p>
            </div>
            <button onclick="document.getElementById('steve-response-modal').classList.add('hidden')" class="text-green-400 hover:text-green-300 text-xl font-bold ml-2 px-1">&times;</button>
        </div>
        <div class="px-3 py-2 overflow-auto flex-1 bg-gray-900">
            <pre id="steve-response-content" class="text-green-400 font-mono text-xs whitespace-pre-wrap break-words leading-relaxed"></pre>
        </div>
        <div class="px-3 py-2 border-t border-green-500 flex gap-2">
            <div id="download-buttons-container" class="flex-1"></div>
            <button onclick="document.getElementById('steve-response-modal').classList.add('hidden'); document.getElementById('download-buttons-container').innerHTML = '';" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-1.5 px-3 rounded text-sm transition-colors">
                Fermer
            </button>
        </div>
    </div>
</div>

@endsection
