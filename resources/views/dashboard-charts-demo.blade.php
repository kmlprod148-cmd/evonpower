@extends('layouts.app')

@section('title', __('dashboard.analytics'))

@push('styles')
<link rel="stylesheet" href="{{ asset('css/charts-animations.css') }}">
@endpush

@section('content')
<div class="evon-content">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            {{ __('dashboard.analytics') }} & {{ __('dashboard.performance') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            {{ __('dashboard.overview_chart') }} avec animations SVG avancées
        </p>
    </div>

    <!-- Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        
        <!-- Energy Wave Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 stagger-item">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('dashboard.energy_today') }}
                </h3>
                <span class="flex items-center gap-2 text-sm text-eco-green-600 dark:text-eco-green-400 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    +12.5%
                </span>
            </div>
            
            <x-charts.energy-wave-chart 
                :data="[45, 52, 38, 65, 58, 70, 68, 75]"
                :height="250" />
        </div>

        <!-- Circular Progress -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 stagger-item">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                {{ __('dashboard.station_availability') }}
            </h3>
            
            <div class="grid grid-cols-3 gap-4">
                <div class="flex flex-col items-center">
                    <x-charts.circular-progress 
                        :percentage="98"
                        :size="100"
                        :strokeWidth="8"
                        label="{{ __('dashboard.online') }}"
                        color="#4acf7b"
                        :animated="true" />
                </div>
                
                <div class="flex flex-col items-center">
                    <x-charts.circular-progress 
                        :percentage="85"
                        :size="100"
                        :strokeWidth="8"
                        label="{{ __('dashboard.active') }}"
                        color="#3b82f6"
                        :animated="true" />
                </div>
                
                <div class="flex flex-col items-center">
                    <x-charts.circular-progress 
                        :percentage="92"
                        :size="100"
                        :strokeWidth="8"
                        label="{{ __('dashboard.performance') }}"
                        color="#8b5cf6"
                        :animated="true" />
                </div>
            </div>
        </div>

    </div>

    <!-- Second Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- Bar Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 stagger-item">
            <x-charts.bar-chart-animated 
                :data="[65, 82, 58, 95, 73, 88, 92]"
                :labels="['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']"
                :height="350"
                title="{{ __('dashboard.sessions_chart') }}"
                color="#4acf7b" />
        </div>

        <!-- Donut Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 stagger-item">
            <x-charts.donut-chart 
                :data="[35, 25, 20, 15, 5]"
                :labels="['Type 2', 'CCS', 'CHAdeMO', 'AC', 'Autre']"
                :colors="['#4acf7b', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6']"
                :size="250"
                :strokeWidth="45"
                title="{{ __('dashboard.charger_types') }}" />
        </div>

    </div>

    <!-- Stats Grid with Animations -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        
        <!-- Stat Card 1 -->
        <div class="bg-gradient-to-br from-eco-green-500 to-eco-green-600 rounded-2xl shadow-lg p-6 text-white stagger-item chart-card-3d">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="glow-animated w-2 h-2 bg-white rounded-full"></div>
            </div>
            <div class="number-animated text-3xl font-bold mb-1">1,247</div>
            <div class="text-eco-green-100 text-sm">{{ __('dashboard.active_sessions') }}</div>
            <div class="mt-4 flex items-center text-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                <span>+18.2%</span>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white stagger-item chart-card-3d">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="glow-animated w-2 h-2 bg-white rounded-full"></div>
            </div>
            <div class="number-animated text-3xl font-bold mb-1">856 kWh</div>
            <div class="text-blue-100 text-sm">{{ __('dashboard.energy') }}</div>
            <div class="mt-4 flex items-center text-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                <span>+24.5%</span>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white stagger-item chart-card-3d">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
                <div class="glow-animated w-2 h-2 bg-white rounded-full"></div>
            </div>
            <div class="number-animated text-3xl font-bold mb-1">45,678 MAD</div>
            <div class="text-purple-100 text-sm">{{ __('dashboard.revenue') }}</div>
            <div class="mt-4 flex items-center text-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                <span>+15.8%</span>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl shadow-lg p-6 text-white stagger-item chart-card-3d">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div class="glow-animated w-2 h-2 bg-white rounded-full"></div>
            </div>
            <div class="number-animated text-3xl font-bold mb-1">3,542</div>
            <div class="text-amber-100 text-sm">{{ __('dashboard.users') }}</div>
            <div class="mt-4 flex items-center text-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                <span>+8.3%</span>
            </div>
        </div>

    </div>

    <!-- Performance Indicators -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 stagger-item">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
            {{ __('dashboard.performance_by_station') }}
        </h3>
        
        <div class="space-y-4">
            @foreach([
                ['name' => 'Station Centre-Ville', 'progress' => 95, 'value' => '1,245 kWh', 'color' => '#4acf7b'],
                ['name' => 'Station Aéroport', 'progress' => 88, 'value' => '987 kWh', 'color' => '#3b82f6'],
                ['name' => 'Station Gare', 'progress' => 76, 'value' => '756 kWh', 'color' => '#f59e0b'],
                ['name' => 'Station Port', 'progress' => 92, 'value' => '1,120 kWh', 'color' => '#8b5cf6'],
            ] as $index => $station)
            <div class="flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $station['name'] }}
                        </span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $station['value'] }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div class="h-full rounded-full gradient-animated transition-all duration-1000 ease-out"
                             style="width: {{ $station['progress'] }}%; background: linear-gradient(90deg, {{ $station['color'] }}, {{ $station['color'] }}dd); animation-delay: {{ $index * 0.1 }}s;">
                        </div>
                    </div>
                </div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white w-12 text-right">
                    {{ $station['progress'] }}%
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection

