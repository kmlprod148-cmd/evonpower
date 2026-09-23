@extends('layouts.app')

@section('title', __('messages.dashboard'))

@section('content')
@php
    // Active le header premium avec statistiques
    $showHeaderStats = true;
    $pageTitle = __('messages.dashboard');
    $pageSubtitle = __('messages.welcome_back') ?? 'Bienvenue sur votre tableau de bord EVON';
    
    // Stats pour le header (passées au composant app-header via le layout)
    $stats = [
        'onlinePoints' => $bornesActives ?? 16,
        'totalPoints' => 20,
        'availabilityRate' => 80,
        'activeTransactions' => $rechargesActives ?? 4,
        'todayRevenue' => ($totalRecharges ?? 12) * 45.50,
        'todayTransactions' => $totalRecharges ?? 12,
        'todayEnergy' => ($totalRecharges ?? 12) * 35.2,
    ];
@endphp

{{-- Le header premium est maintenant géré par le composant app-header dans le layout --}}

{{-- Hero Graphic Section - Design Attractif --}}
<div class="evon-content -mt-8 relative z-10">
    <div class="bg-gradient-to-br from-eco-green-50 via-white to-blue-50 dark:from-gray-800 dark:via-gray-800 dark:to-gray-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-700 p-8 mb-6 overflow-hidden relative">
        {{-- Illustration SVG Animée --}}
        <div class="absolute top-0 right-0 w-96 h-96 opacity-10 dark:opacity-5 pointer-events-none">
            <svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" class="animate-float">
                {{-- Borne de recharge illustrée --}}
                <defs>
                    <linearGradient id="chargerGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#4acf7b;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#3ab66a;stop-opacity:1" />
                    </linearGradient>
                    <filter id="glow">
                        <feGaussianBlur stdDeviation="4" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>
                
                {{-- Corps de la borne --}}
                <rect x="150" y="100" width="100" height="200" rx="10" fill="url(#chargerGradient)" filter="url(#glow)"/>
                
                {{-- Écran --}}
                <rect x="165" y="130" width="70" height="50" rx="5" fill="#2d3748" opacity="0.8"/>
                <circle cx="200" cy="155" r="15" fill="#4acf7b" opacity="0.6">
                    <animate attributeName="opacity" values="0.6;1;0.6" dur="2s" repeatCount="indefinite"/>
                </circle>
                
                {{-- Prise --}}
                <rect x="180" y="220" width="40" height="60" rx="8" fill="#1a202c" opacity="0.7"/>
                <circle cx="200" cy="245" r="8" fill="#4acf7b"/>
                <circle cx="200" cy="260" r="8" fill="#4acf7b"/>
                
                {{-- Électricité animée --}}
                <g class="animate-pulse">
                    <path d="M 200 50 L 190 80 L 200 80 L 190 110" stroke="#fbbf24" stroke-width="3" fill="none" stroke-linecap="round"/>
                    <path d="M 220 60 L 210 90 L 220 90 L 210 120" stroke="#fbbf24" stroke-width="3" fill="none" stroke-linecap="round"/>
                </g>
                
                {{-- Voiture en charge (simplifiée) --}}
                <g transform="translate(60, 250)">
                    <rect x="0" y="20" width="80" height="40" rx="5" fill="#3b82f6" opacity="0.6"/>
                    <circle cx="20" cy="65" r="10" fill="#1f2937"/>
                    <circle cx="60" cy="65" r="10" fill="#1f2937"/>
                    <rect x="10" y="25" width="60" height="30" rx="3" fill="#60a5fa" opacity="0.4"/>
                </g>
                
                {{-- Particules d'énergie --}}
                <circle cx="130" cy="260" r="3" fill="#4acf7b">
                    <animate attributeName="cy" values="260;240;260" dur="1.5s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="1;0;1" dur="1.5s" repeatCount="indefinite"/>
                </circle>
                <circle cx="145" cy="270" r="2" fill="#4acf7b">
                    <animate attributeName="cy" values="270;250;270" dur="2s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="1;0;1" dur="2s" repeatCount="indefinite"/>
                </circle>
            </svg>
        </div>
        
        {{-- Contenu --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
                <div class="flex-1">
                    <div class="inline-flex items-center gap-2 bg-eco-green-100 dark:bg-eco-green-900/30 text-eco-green-700 dark:text-eco-green-300 px-4 py-2 rounded-full text-sm font-semibold mb-4">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-eco-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-eco-green-500"></span>
                        </span>
                        {{ __('dashboard.system_operational') }}
                    </div>
                    
                    <h2 class="text-3xl lg:text-4xl font-extrabold text-gray-900 dark:text-white mb-3 leading-tight">
                        {{ __('dashboard.evon_dashboard') }}
                    </h2>
                    
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-6 max-w-2xl">
                        {{ __('dashboard.dashboard_description') }}
                    </p>
                    
                    <div class="flex flex-wrap gap-3">
                        <button onclick="window.location.reload()" class="btn-primary inline-flex items-center gap-2 px-6 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span class="font-semibold">{{ __('messages.refresh') ?? 'Actualiser' }}</span>
                        </button>
                        
                        @can('manage', App\Models\ChargingPoint::class)
                        <button onclick="window.location.href='{{ route('charging-points.create') }}'" class="btn-secondary inline-flex items-center gap-2 px-6 py-3 rounded-xl">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="font-semibold">{{ __('dashboard.new_charging_point') }}</span>
                        </button>
                        @endcan
                    </div>
                </div>
                
                {{-- Mini Stats Cards --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white/60 dark:bg-gray-700/60 backdrop-blur-sm rounded-2xl p-4 border border-gray-200/50 dark:border-gray-600/50 min-w-[140px]">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('dashboard.charges') }}</div>
                        <div class="text-3xl font-black text-eco-green-600 dark:text-eco-green-400">{{ $totalRecharges ?? 12 }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('dashboard.percent_this_month', ['percent' => '+12%']) }}
                        </div>
                    </div>
                    
                    <div class="bg-white/60 dark:bg-gray-700/60 backdrop-blur-sm rounded-2xl p-4 border border-gray-200/50 dark:border-gray-600/50 min-w-[140px]">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('dashboard.active') }}</div>
                        <div class="text-3xl font-black text-blue-600 dark:text-blue-400">{{ $rechargesActives ?? 4 }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ __('dashboard.in_progress') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}
</style>

<!-- Cartes statistiques -->
<div class="evon-stats-grid">
    <!-- Nombre total de recharge -->
    <div class="evon-stat-card">
        <div class="evon-stat-card-header">
            <h3 class="evon-stat-card-label">{{ __('messages.total_charging_points') }}</h3>
            <div class="evon-stat-card-icon bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
        </div>
        <p class="evon-stat-card-value">{{ $totalRecharges ?? 12 }}</p>
        <div class="evon-stat-card-change evon-stat-card-change-positive">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            <span>+12%</span>
        </div>
    </div>

    <!-- Recharge active -->
    <div class="evon-stat-card">
        <div class="evon-stat-card-header">
            <h3 class="evon-stat-card-label">{{ __('messages.active_charging_points') }}</h3>
            <div class="evon-stat-card-icon bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                <div class="status-dot-live mr-0"></div>
            </div>
        </div>
        <p class="evon-stat-card-value">{{ $rechargesActives ?? 4 }}</p>
        <div class="evon-stat-card-change text-gray-500">
            <span>En cours</span>
        </div>
    </div>

    <!-- Abonnements actifs -->
    <div class="evon-stat-card">
        <div class="evon-stat-card-header">
            <h3 class="evon-stat-card-label">{{ __('messages.active') }} (Abo)</h3>
            <div class="evon-stat-card-icon bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
        </div>
        <p class="evon-stat-card-value">{{ $abonnementsActifs ?? 3 }}</p>
        <div class="evon-stat-card-change evon-stat-card-change-positive">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            <span>+2</span>
        </div>
    </div>

    <!-- Bornes actifs -->
    <div class="evon-stat-card">
        <div class="evon-stat-card-header">
            <h3 class="evon-stat-card-label">Bornes Actives</h3>
            <div class="evon-stat-card-icon bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
            </div>
        </div>
        <p class="evon-stat-card-value">{{ $bornesActives ?? 16 }}</p>
        <div class="evon-stat-card-change text-gray-500">
            <span>Sur 20 installées</span>
        </div>
    </div>
</div>

<!-- Graphiques et Visualisations -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Graphique Principal des Sessions -->
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        {{ __('messages.sessions_chart') }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Évolution des sessions de recharge</p>
                </div>
                <div class="flex gap-2">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors shadow-md">
                        {{ __('messages.today') }}
                    </button>
                    <button class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Semaine
                    </button>
                </div>
            </div>
        </div>
        <div class="p-6 bg-white dark:bg-gray-800">
            <div class="h-96 w-full relative">
                <canvas id="rechargesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Panneau Latéral avec Graphiques Supplémentaires -->
    <div class="space-y-6">
        <!-- Répartition des Sessions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                </svg>
                Répartition
            </h3>
            <div class="h-64">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>

        <!-- Sessions Actives Liste -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-gray-900 dark:to-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="inline-flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                    {{ __('messages.active_charging_points') }}
                </h3>
            </div>
            <div class="max-h-[400px] overflow-y-auto">
                @forelse($rechargesActivesList ?? [] as $recharge)
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $recharge['name'] }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">En cours de recharge</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-black text-green-600 dark:text-green-400">{{ $recharge['kwh'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">kWh</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400 font-semibold">{{ __('messages.no_data_available') }}</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Les sessions actives apparaîtront ici</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Graphiques Supplémentaires en Bas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
    <!-- Performance Horaire -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Performance Horaire
        </h3>
        <div class="h-48">
            <canvas id="hourlyChart"></canvas>
        </div>
    </div>

    <!-- Consommation Énergie -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Consommation Énergie
        </h3>
        <div class="h-48">
            <canvas id="energyChart"></canvas>
        </div>
    </div>

    <!-- Statistiques Rapides -->
    <div class="bg-gradient-to-br from-blue-500 to-purple-600 dark:from-blue-900 dark:to-purple-900 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
            Stats Rapides
        </h3>
        <div class="space-y-4">
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                <p class="text-sm text-white/70">Durée Moyenne</p>
                <p class="text-3xl font-black">45 min</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                <p class="text-sm text-white/70">Taux de Satisfaction</p>
                <p class="text-3xl font-black">96%</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                <p class="text-sm text-white/70">Bornes Disponibles</p>
                <p class="text-3xl font-black">{{ ($stats['totalPoints'] ?? 20) - ($stats['activeTransactions'] ?? 4) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Chart.js depuis CDN pour assurer le chargement -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuration commune pour tous les graphiques
        Chart.defaults.font.family = "'Inter', 'system-ui', 'sans-serif'";
        Chart.defaults.color = '#6B7280';
        
        // 1. GRAPHIQUE PRINCIPAL - Sessions de Recharge
        const ctx = document.getElementById('rechargesChart').getContext('2d');
        const labels = {!! isset($hourlyData['labels']) ? json_encode($hourlyData['labels']) : json_encode(['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h']) !!};
        const data = {!! isset($hourlyData['data']) ? json_encode($hourlyData['data']) : json_encode([15, 8, 12, 25, 45, 38, 22, 18]) !!};
        
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Sessions de recharge',
                    data: data,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: 'rgb(37, 99, 235)',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1500,
                    easing: 'easeInOutQuart'
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        titleColor: '#fff',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyColor: '#fff',
                        bodyFont: { size: 13 },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Sessions: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(200, 200, 200, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            font: { size: 12 },
                            padding: 10
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 12 },
                            padding: 10
                        }
                    }
                }
            }
        });
        
        // 2. GRAPHIQUE DE RÉPARTITION (Pie Chart)
        const distributionCtx = document.getElementById('distributionChart').getContext('2d');
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['En ligne', 'En maintenance', 'Hors ligne'],
                datasets: [{
                    data: [{{ $stats['onlinePoints'] ?? 16 }}, 3, 1],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        'rgb(34, 197, 94)',
                        'rgb(251, 191, 36)',
                        'rgb(239, 68, 68)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 2000
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12, weight: 'bold' },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        // 3. GRAPHIQUE PERFORMANCE HORAIRE (Bar Chart)
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                datasets: [{
                    label: 'Sessions',
                    data: [45, 52, 38, 65, 73, 58, 42],
                    backgroundColor: 'rgba(147, 51, 234, 0.7)',
                    borderColor: 'rgb(147, 51, 234)',
                    borderWidth: 2,
                    borderRadius: 8,
                    hoverBackgroundColor: 'rgba(147, 51, 234, 0.9)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1500
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(200, 200, 200, 0.1)'
                        },
                        ticks: {
                            font: { size: 11 }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
        
        // 4. GRAPHIQUE CONSOMMATION ÉNERGIE (Area Chart)
        const energyCtx = document.getElementById('energyChart').getContext('2d');
        const energyGradient = energyCtx.createLinearGradient(0, 0, 0, 200);
        energyGradient.addColorStop(0, 'rgba(251, 191, 36, 0.6)');
        energyGradient.addColorStop(1, 'rgba(251, 191, 36, 0.0)');
        
        new Chart(energyCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'kWh',
                    data: [320, 385, 420, 450, 480, 520],
                    borderColor: 'rgb(251, 191, 36)',
                    backgroundColor: energyGradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(251, 191, 36)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1500
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return 'Énergie: ' + context.parsed.y + ' kWh';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(200, 200, 200, 0.1)'
                        },
                        ticks: {
                            font: { size: 11 },
                            callback: function(value) {
                                return value + ' kWh';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
        
        // Fonction optimisée de mise à jour des données
        function fetchRealtimeData() {
            fetch('{{ route("dashboard.realtime-data") }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store' // Désactiver le cache pour les données en temps réel
            })
            .then(response => response.json())
            .then(data => {
                // Mettre à jour les compteurs importants
                updateCounters(data);
                
                // Mettre à jour le tableau des recharges actives
                updateRechargesTable(data);
            })
            .catch(error => console.error('Erreur lors de la récupération des données:', error));
        }
        
        // Fonction pour mettre à jour les compteurs
        function updateCounters(data) {
            // Mettre à jour uniquement si les données existent
            if (data.rechargesActives !== undefined) {
                document.querySelector('.bg-white:nth-child(2) .text-3xl').textContent = data.rechargesActives;
                document.querySelector('.bg-white:nth-child(2) h3').textContent = `Recharge active (${data.rechargesActives})`;
            }
            
            if (data.totalRecharges !== undefined) {
                document.querySelector('.bg-white:nth-child(1) .text-3xl').textContent = data.totalRecharges;
            }
            
            if (data.abonnementsActifs !== undefined) {
                document.querySelector('.bg-white:nth-child(3) .text-3xl').textContent = data.abonnementsActifs;
            }
            
            if (data.bornesActives !== undefined) {
                document.querySelector('.bg-white:nth-child(4) .text-3xl').textContent = data.bornesActives;
            }
        }
        
        // Fonction pour mettre à jour le tableau des recharges actives
        function updateRechargesTable(data) {
            if (data.rechargesActivesList && data.rechargesActivesList.length > 0) {
                const tableBody = document.querySelector('tbody');
                if (tableBody) {
                    // Utiliser DocumentFragment pour améliorer les performances
                    const fragment = document.createDocumentFragment();
                    
                    data.rechargesActivesList.forEach(recharge => {
                        const row = document.createElement('tr');
                        
                        const nameCell = document.createElement('td');
                        nameCell.className = 'px-3 py-3 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200';
                        nameCell.textContent = recharge.name || 'N/A';
                        
                        const kwhCell = document.createElement('td');
                        kwhCell.className = 'px-3 py-3 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200 text-right';
                        kwhCell.textContent = recharge.kwh || '0';
                        
                        row.appendChild(nameCell);
                        row.appendChild(kwhCell);
                        fragment.appendChild(row);
                    });
                    
                    // Effacer le tableau actuel et ajouter les nouvelles lignes
                    tableBody.innerHTML = '';
                    tableBody.appendChild(fragment);
                }
            }
        }
        
        // Mise à jour toutes les 30 secondes avec un meilleur contrôle des performances
        let updateInterval = setInterval(fetchRealtimeData, 30000);
        
        // Nettoyer l'intervalle quand l'utilisateur quitte la page
        // Utilise pagehide au lieu de beforeunload (plus moderne et fiable)
        window.addEventListener('pagehide', function() {
            clearInterval(updateInterval);
        }, { capture: true });
        
        // Suspendre les mises à jour quand l'onglet n'est pas visible pour économiser les ressources
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                clearInterval(updateInterval);
            } else {
                // Récupérer les données immédiatement lorsque l'utilisateur revient
                fetchRealtimeData();
                updateInterval = setInterval(fetchRealtimeData, 30000);
            }
        });
    });
</script>
@endpush