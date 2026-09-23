@extends('layouts.app')

@section('title', 'Tableau de Bord Steve - Monitoring')
@section('page-title', 'Tableau de Bord Steve')

@section('content')
<div class="space-y-6">
    <!-- En-tête du tableau de bord -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tableau de Bord Steve API</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Monitoring en temps réel des stations de recharge</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-3">
                <!-- Statut de connexion -->
                <div class="flex items-center space-x-2 px-3 py-1.5 rounded-full 
                    {{ ($health['api_reachable'] ?? false) ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                    <div class="w-2 h-2 rounded-full {{ ($health['api_reachable'] ?? false) ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></div>
                    <span class="text-sm font-medium">
                        {{ ($health['api_reachable'] ?? false) ? 'API Connectée' : 'API Hors ligne' }}
                    </span>
                </div>
                <!-- Bouton Actualiser -->
                <button onclick="refreshDashboard()" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Actualiser
                </button>
            </div>
        </div>
        <!-- Dernière mise à jour -->
        <div class="mt-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div>
                Dernière mise à jour: <span id="lastUpdate">{{ $summary['last_updated'] ?? now()->toISOString() }}</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded text-xs">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Données authentiques
                </span>
            </div>
        </div>
    </div>

    <!-- Cartes de statistiques principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Sessions totales -->
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-indigo-100 text-sm font-medium mb-1">Sessions totales</h3>
                <p class="text-3xl font-bold mb-2">{{ number_format($summary['total_sessions'] ?? 0) }}</p>
                <p class="text-indigo-100 text-xs">Toutes les sessions</p>
            </div>
        </div>

        <!-- Sessions actives -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <span class="animate-pulse text-orange-100 text-xs font-medium">EN DIRECT</span>
                </div>
                <h3 class="text-orange-100 text-sm font-medium mb-1">Sessions actives</h3>
                <p class="text-3xl font-bold mb-2">{{ $summary['active_sessions'] ?? 0 }}</p>
                <p class="text-orange-100 text-xs">En cours de charge</p>
            </div>
        </div>

        <!-- Énergie distribuée -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-emerald-100 text-sm font-medium mb-1">Énergie distribuée</h3>
                <p class="text-3xl font-bold mb-2">{{ number_format($summary['total_energy_kwh'] ?? 0, 1) }} <span class="text-lg">kWh</span></p>
                <p class="text-emerald-100 text-xs">Total cumulé</p>
            </div>
        </div>

        <!-- Stations opérationnelles -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-blue-100 text-sm font-medium mb-1">Stations en ligne</h3>
                <p class="text-3xl font-bold mb-2">{{ $summary['operational_stations'] ?? 0 }} <span class="text-lg">/ {{ $summary['total_stations'] ?? 0 }}</span></p>
                <p class="text-blue-100 text-xs">Stations opérationnelles</p>
            </div>
        </div>
    </div>

    <!-- Deuxième rangée de statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Disponibles -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Disponibles</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $availability['available']['count'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $availability['available']['percentage'] ?? 0 }}% du total</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- En charge -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">En charge</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $availability['charging']['count'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $availability['charging']['percentage'] ?? 0 }}% du total</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Hors ligne -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Hors ligne</p>
                    <p class="text-2xl font-bold text-gray-600 dark:text-gray-400 mt-1">{{ $availability['offline']['count'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $availability['offline']['percentage'] ?? 0 }}% du total</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- En défaut -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">En défaut</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $availability['faulted']['count'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $availability['faulted']['percentage'] ?? 0 }}% du total</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Métriques d'énergie et Types de chargeurs -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Métriques d'énergie -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Métriques d'Énergie</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Aujourd'hui</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ number_format($energy['today_kwh'] ?? 0, 2) }} kWh</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Cette semaine</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ number_format($energy['week_kwh'] ?? 0, 2) }} kWh</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Ce mois</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ number_format($energy['month_kwh'] ?? 0, 2) }} kWh</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Total cumulé</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($energy['total_kwh'] ?? 0, 2) }} kWh</span>
                </div>
            </div>
        </div>

        <!-- Types de chargeurs -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Types de Chargeurs</h3>
            <div class="space-y-3">
                @if(!empty($chargerTypes['types']))
                    @foreach($chargerTypes['types'] as $type => $data)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full 
                                @switch($loop->index % 5)
                                    @case(0) bg-blue-500
                                    @case(1) bg-green-500
                                    @case(2) bg-purple-500
                                    @case(3) bg-orange-500
                                    @default bg-pink-500
                                @endswitch
                            "></div>
                            <span class="text-gray-700 dark:text-gray-300">{{ $type }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $data['count'] }}</span>
                            <span class="text-sm text-gray-500">({{ $data['percentage'] }}%)</span>
                        </div>
                    </div>
                    @endforeach
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">Aucune donnée disponible</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Performance et Santé du réseau -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Performance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Performance du Réseau</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Temps de réponse API</span>
                    <span class="font-bold 
                        @if(($performance['api_response_time_ms'] ?? 0) < 500)
                            text-green-600 dark:text-green-400
                        @elseif(($performance['api_response_time_ms'] ?? 0) < 1000)
                            text-yellow-600 dark:text-yellow-400
                        @else
                            text-red-600 dark:text-red-400
                        @endif
                    ">
                        {{ $performance['api_response_time_ms'] ?? 'N/A' }} ms
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Sessions complétées</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ number_format($performance['completed_sessions'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Taux de succès</span>
                    <span class="font-bold text-green-600 dark:text-green-400">{{ $performance['success_rate'] ?? 0 }}%</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Durée moyenne session</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $performance['avg_session_duration_minutes'] ?? 0 }} min</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Énergie moyenne/session</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $performance['avg_energy_kwh'] ?? 0 }} kWh</span>
                </div>
            </div>
        </div>

        <!-- Santé du réseau -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Santé du Réseau</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-lg 
                    @if(($health['status'] ?? '') === 'healthy')
                        bg-green-50 dark:bg-green-900/20
                    @else
                        bg-red-50 dark:bg-red-900/20
                    @endif
                ">
                    <span class="text-gray-600 dark:text-gray-400">Statut global</span>
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        @if(($health['status'] ?? '') === 'healthy')
                            bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-300
                        @else
                            bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-300
                        @endif
                    ">
                        {{ ucfirst($health['status'] ?? 'inconnu') }}
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">API accessible</span>
                    <span class="flex items-center text-sm">
                        @if($health['api_reachable'] ?? false)
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-green-600 dark:text-green-400">Oui</span>
                        @else
                            <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-red-600 dark:text-red-400">Non</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Base de données</span>
                    <span class="flex items-center text-sm">
                        @if($health['database_connected'] ?? false)
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-green-600 dark:text-green-400">Connectée</span>
                        @else
                            <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-red-600 dark:text-red-400">Déconnectée</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Temps de réponse</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $health['response_time_ms'] ?? 'N/A' }} ms</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Sessions actives en temps réel -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Sessions Actives en Temps Réel</h3>
            <span class="px-3 py-1 bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 rounded-full text-sm font-medium animate-pulse">
                {{ $activeSessions['count'] ?? 0 }} en cours
            </span>
        </div>
        
        @if(!empty($activeSessions['sessions']))
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Station</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Statut</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Démarré</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Durée</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Énergie</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Tag OCPP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($activeSessions['sessions'] as $session)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="py-3 px-4 text-sm text-gray-900 dark:text-white font-medium">
                                {{ $session['charging_point_name'] ?? 'N/A' }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded text-xs font-medium">
                                    {{ ucfirst($session['status'] ?? 'inconnu') }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $session['started_at'] ? \Carbon\Carbon::parse($session['started_at'])->format('H:i:s') : 'N/A' }}
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400 font-mono">
                                {{ gmdate('H:i:s', $session['duration_seconds'] ?? 0) }}
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ number_format($session['estimated_energy'] ?? 0, 2) }} kWh
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400 font-mono">
                                {{ $session['ocpp_tag'] ?? 'N/A' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p>Aucune session active en cours</p>
            </div>
        @endif
    </div>

    <!-- Liste des stations -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">État des Stations</h3>
        
        @if(!empty($stations['stations']))
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Nom</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Charge Box ID</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Fabricant</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Statut</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Énergie (kWh)</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Sessions</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Dernière connexion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($stations['stations'] as $station)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="py-3 px-4 text-sm text-gray-900 dark:text-white font-medium">
                                {{ $station['name'] }}
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400 font-mono text-xs">
                                {{ $station['charge_box_id'] ?? 'N/A' }}
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $station['manufacturer'] ?? 'N/A' }} {{ $station['model'] ?? '' }}
                            </td>
                            <td class="py-3 px-4">
                                @switch(strtolower($station['status'] ?? 'unknown'))
                                    @case('online')
                                    @case('available')
                                    @case('ready')
                                        <span class="px-2 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded text-xs font-medium">Disponible</span>
                                        @break
                                    @case('charging')
                                    @case('in_use')
                                    @case('occupied')
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded text-xs font-medium">En charge</span>
                                        @break
                                    @case('offline')
                                    @case('disconnected')
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded text-xs font-medium">Hors ligne</span>
                                        @break
                                    @case('fault')
                                    @case('error')
                                    @case('faulted')
                                        <span class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded text-xs font-medium">Défaut</span>
                                        @break
                                    @default
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded text-xs font-medium">{{ ucfirst($station['status'] ?? 'Inconnu') }}</span>
                                @endswitch
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ number_format($station['total_energy_delivered'] ?? 0, 2) }}
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $station['total_charging_sessions'] ?? 0 }}
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $station['last_connection'] ? \Carbon\Carbon::parse($station['last_connection'])->format('d/m/Y H:i') : 'Jamais' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <p>Aucune station trouvée</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Rafraîchissement automatique toutes les 30 secondes
    let refreshInterval = 30000; // 30 secondes
    
    function refreshDashboard() {
        // Show loading indicator
        const btn = event.target;
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<svg class="w-4 h-4 inline mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Actualisation...';
        btn.disabled = true;
        
        // Reload the page
        window.location.reload();
    }
    
    // Auto-refresh
    setInterval(() => {
        refreshDashboard();
    }, refreshInterval);
    
    // Mettre à jour l'heure de dernière mise à jour
    function updateLastUpdateTime() {
        const now = new Date();
        document.getElementById('lastUpdate').textContent = now.toISOString();
    }
</script>
@endpush
@endsection
