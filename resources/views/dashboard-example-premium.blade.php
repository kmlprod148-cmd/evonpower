@extends('layouts.app')

@section('title', __('dashboard.dashboard'))

@php
    // Exemple de données statistiques
    $headerStats = [
        'onlinePoints' => 24,
        'totalPoints' => 30,
        'availabilityRate' => 80,
        'activeTransactions' => 8,
        'todayRevenue' => 12560,
        'todayTransactions' => 45,
        'todayEnergy' => 324.5
    ];
    
    $showHeaderStats = true;
@endphp

@section('content')
<div class="evon-dashboard-container">
    {{-- Welcome Section avec animation --}}
    <div class="mb-8 evon-animate-fade-in-up">
        <h2 class="evon-heading-2 text-gray-900 dark:text-white mb-2">
            {{ __('dashboard.welcome_back') }}, {{ auth()->user()->name }} 👋
        </h2>
        <p class="evon-text-body text-gray-600 dark:text-gray-400">
            {{ __('dashboard.welcome_message') ?? 'Voici un aperçu de vos activités aujourd\'hui.' }}
        </p>
    </div>

    {{-- Stats Grid Premium --}}
    <x-dashboard-stats-grid :stats="$headerStats" />

    {{-- Graphiques et Tableaux --}}
    <div class="evon-grid evon-grid-2 mb-6">
        {{-- Carte Graphique 1 --}}
        <div class="evon-chart-card evon-animate-fade-in-up evon-animate-delay-1">
            <div class="evon-chart-header">
                <div>
                    <h3 class="evon-chart-title">{{ __('dashboard.charging_activity') ?? 'Activité de Charge' }}</h3>
                    <p class="evon-chart-subtitle">{{ __('dashboard.last_7_days') ?? 'Derniers 7 jours' }}</p>
                </div>
                <div class="evon-chart-filters">
                    <button class="evon-chart-filter-btn active">7j</button>
                    <button class="evon-chart-filter-btn">30j</button>
                    <button class="evon-chart-filter-btn">3m</button>
                </div>
            </div>
            <div class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="text-gray-500 dark:text-gray-400">{{ __('dashboard.chart_placeholder') ?? 'Graphique ici' }}</p>
            </div>
        </div>

        {{-- Carte Graphique 2 --}}
        <div class="evon-chart-card evon-animate-fade-in-up evon-animate-delay-2">
            <div class="evon-chart-header">
                <div>
                    <h3 class="evon-chart-title">{{ __('dashboard.revenue_trend') ?? 'Tendance des Revenus' }}</h3>
                    <p class="evon-chart-subtitle">{{ __('dashboard.this_month') ?? 'Ce mois-ci' }}</p>
                </div>
                <div class="evon-chart-filters">
                    <button class="evon-chart-filter-btn">Mois</button>
                    <button class="evon-chart-filter-btn active">Année</button>
                </div>
            </div>
            <div class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="text-gray-500 dark:text-gray-400">{{ __('dashboard.chart_placeholder') ?? 'Graphique ici' }}</p>
            </div>
        </div>
    </div>

    {{-- Tableau des Transactions Récentes --}}
    <div class="evon-data-table-wrapper evon-animate-fade-in-up evon-animate-delay-3">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="evon-chart-title">{{ __('dashboard.recent_transactions') ?? 'Transactions Récentes' }}</h3>
            <p class="evon-chart-subtitle">{{ __('dashboard.last_transactions') ?? 'Dernières activités' }}</p>
        </div>
        <table class="evon-data-table">
            <thead>
                <tr>
                    <th>{{ __('dashboard.date') ?? 'Date' }}</th>
                    <th>{{ __('dashboard.station') ?? 'Station' }}</th>
                    <th>{{ __('dashboard.user') ?? 'Utilisateur' }}</th>
                    <th>{{ __('dashboard.energy') ?? 'Énergie' }}</th>
                    <th>{{ __('dashboard.amount') ?? 'Montant' }}</th>
                    <th>{{ __('dashboard.status') ?? 'Statut' }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ now()->format('d/m/Y H:i') }}</td>
                    <td>Station Centre-Ville</td>
                    <td>John Doe</td>
                    <td>45.2 kWh</td>
                    <td>280 MAD</td>
                    <td>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            Complété
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>{{ now()->subHour()->format('d/m/Y H:i') }}</td>
                    <td>Station Nord</td>
                    <td>Jane Smith</td>
                    <td>32.8 kWh</td>
                    <td>195 MAD</td>
                    <td>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                            En cours
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>{{ now()->subHours(2)->format('d/m/Y H:i') }}</td>
                    <td>Station Sud</td>
                    <td>Ahmed Ali</td>
                    <td>58.5 kWh</td>
                    <td>350 MAD</td>
                    <td>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            Complété
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Cartes d'Actions Rapides --}}
    <div class="mt-8 evon-grid evon-grid-3">
        <a href="{{ route('charging-points.index') }}" 
           class="evon-card evon-hover-lift evon-animate-fade-in-up evon-animate-delay-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border border-green-200 dark:border-green-700">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ __('dashboard.manage_stations') }}</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.view_all_stations') }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('transactions.index') }}" 
           class="evon-card evon-hover-lift evon-animate-fade-in-up evon-animate-delay-5 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200 dark:border-blue-700">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ __('dashboard.transactions') }}</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.view_history') }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('reports.index') }}" 
           class="evon-card evon-hover-lift evon-animate-fade-in-up evon-animate-delay-6 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border border-purple-200 dark:border-purple-700">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ __('dashboard.reports') }}</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.view_analytics') }}</p>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection

