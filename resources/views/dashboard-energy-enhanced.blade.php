@extends('layouts.app')

@section('title', 'Tableau de bord énergétique')

@push('styles')
<style>
    /* Variables CSS personnalisées */
    :root {
        --energy-primary: #10b981;
        --energy-secondary: #059669;
        --energy-accent: #34d399;
        --energy-warning: #f59e0b;
        --energy-danger: #ef4444;
        --energy-info: #3b82f6;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    /* Animations personnalisées */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Conteneur principal avec dégradé */
    .energy-dashboard {
        min-height: 100vh;
        background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #d1fae5 100%);
    }

    .dark .energy-dashboard {
        background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%);
    }

    /* En-tête hero amélioré */
    .hero-header {
        @apply bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600;
        @apply rounded-2xl p-8 mb-8 shadow-2xl;
        @apply relative overflow-hidden;
    }

    .hero-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.3;
    }

    .hero-content {
        @apply relative z-10;
    }

    /* Cartes KPI améliorées */
    .kpi-card {
        @apply bg-white dark:bg-gray-800 rounded-xl p-6;
        @apply border border-gray-200 dark:border-gray-700;
        @apply shadow-lg hover:shadow-xl;
        @apply transition-all duration-300 ease-in-out;
        @apply transform hover:-translate-y-1;
        animation: fadeInUp 0.6s ease-out;
    }

    .kpi-card:hover {
        @apply border-emerald-300 dark:border-emerald-600;
    }

    .kpi-icon {
        @apply w-14 h-14 rounded-xl flex items-center justify-center;
        @apply text-white text-2xl font-bold;
        @apply shadow-md;
        background: linear-gradient(135deg, var(--energy-primary) 0%, var(--energy-secondary) 100%);
    }

    .kpi-value {
        @apply text-4xl font-bold text-gray-900 dark:text-white;
        @apply mb-2;
        font-variant-numeric: tabular-nums;
    }

    .kpi-label {
        @apply text-sm font-medium text-gray-600 dark:text-gray-400;
        @apply uppercase tracking-wide;
    }

    .kpi-trend {
        @apply inline-flex items-center gap-1 text-sm font-semibold;
        @apply px-2 py-1 rounded-full;
    }

    .trend-up {
        @apply bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300;
    }

    .trend-down {
        @apply bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300;
    }

    .trend-neutral {
        @apply bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300;
    }

    /* Cartes de graphiques */
    .chart-card {
        @apply bg-white dark:bg-gray-800 rounded-xl p-6;
        @apply border border-gray-200 dark:border-gray-700;
        @apply shadow-lg;
        animation: fadeInUp 0.8s ease-out;
    }

    .chart-header {
        @apply flex items-center justify-between mb-6;
        @apply pb-4 border-b border-gray-200 dark:border-gray-700;
    }

    .chart-title {
        @apply text-lg font-bold text-gray-900 dark:text-white;
    }

    .chart-subtitle {
        @apply text-sm text-gray-600 dark:text-gray-400;
    }

    /* Badge de statut en temps réel */
    .live-badge {
        @apply inline-flex items-center gap-2;
        @apply px-3 py-1.5 rounded-full;
        @apply bg-emerald-100 dark:bg-emerald-900/30;
        @apply text-emerald-700 dark:text-emerald-300;
        @apply text-xs font-semibold uppercase tracking-wide;
    }

    .live-dot {
        @apply w-2 h-2 rounded-full bg-emerald-500;
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Carte d'énergie avec dégradé */
    .energy-gradient-card {
        @apply rounded-xl p-8 text-white shadow-2xl;
        @apply relative overflow-hidden;
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 50%, #ef4444 100%);
    }

    .energy-gradient-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse 4s ease-in-out infinite;
    }

    /* Tableau moderne */
    .modern-table {
        @apply w-full;
    }

    .modern-table thead {
        @apply bg-gray-50 dark:bg-gray-900/50;
    }

    .modern-table th {
        @apply px-6 py-4 text-left text-xs font-semibold;
        @apply text-gray-700 dark:text-gray-300;
        @apply uppercase tracking-wider;
    }

    .modern-table td {
        @apply px-6 py-4 text-sm;
        @apply text-gray-900 dark:text-gray-100;
    }

    .modern-table tbody tr {
        @apply border-b border-gray-200 dark:border-gray-700;
        @apply transition-colors duration-150;
    }

    .modern-table tbody tr:hover {
        @apply bg-gray-50 dark:bg-gray-700/50;
    }

    /* Badge de statut */
    .status-badge {
        @apply inline-flex items-center gap-1.5;
        @apply px-3 py-1 rounded-full text-xs font-semibold;
    }

    .status-online {
        @apply bg-emerald-100 dark:bg-emerald-900/30;
        @apply text-emerald-700 dark:text-emerald-300;
    }

    .status-offline {
        @apply bg-red-100 dark:bg-red-900/30;
        @apply text-red-700 dark:text-red-300;
    }

    .status-charging {
        @apply bg-blue-100 dark:bg-blue-900/30;
        @apply text-blue-700 dark:text-blue-300;
    }

    /* Barre de progression */
    .progress-bar {
        @apply w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden;
    }

    .progress-fill {
        @apply h-full rounded-full;
        @apply bg-gradient-to-r from-emerald-500 to-teal-500;
        transition: width 1s ease-out;
    }

    /* Sélecteur de période */
    .period-selector {
        @apply inline-flex items-center gap-2;
        @apply bg-white dark:bg-gray-800;
        @apply border border-gray-300 dark:border-gray-600;
        @apply rounded-lg px-4 py-2;
        @apply text-sm font-medium;
        @apply text-gray-700 dark:text-gray-300;
        @apply cursor-pointer;
        @apply transition-all duration-200;
    }

    .period-selector:hover {
        @apply border-emerald-500 dark:border-emerald-400;
        @apply shadow-md;
    }

    /* Carte de station */
    .station-card {
        @apply bg-gradient-to-br from-white to-gray-50;
        @apply dark:from-gray-800 dark:to-gray-900;
        @apply rounded-lg p-4 border border-gray-200 dark:border-gray-700;
        @apply transition-all duration-300;
    }

    .station-card:hover {
        @apply shadow-lg transform -translate-y-1;
        @apply border-emerald-300 dark:border-emerald-600;
    }

    /* Indicateur de puissance */
    .power-indicator {
        @apply flex items-center gap-2;
        @apply text-sm font-semibold;
    }

    .power-bar {
        @apply flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden;
    }

    .power-fill {
        @apply h-full bg-gradient-to-r from-emerald-400 to-emerald-600 rounded-full;
        transition: width 0.5s ease-out;
    }

    /* Conteneur de graphique responsive */
    .chart-container {
        @apply relative;
        height: 350px;
    }

    @media (max-width: 768px) {
        .chart-container {
            height: 250px;
        }
    }

    /* Grille responsive améliorée */
    .stats-grid {
        @apply grid gap-6;
        @apply grid-cols-1 sm:grid-cols-2 lg:grid-cols-4;
    }

    .charts-grid {
        @apply grid gap-6;
        @apply grid-cols-1 lg:grid-cols-2;
    }

    /* Bouton d'action */
    .action-btn {
        @apply inline-flex items-center gap-2;
        @apply px-4 py-2 rounded-lg;
        @apply font-semibold text-sm;
        @apply transition-all duration-200;
        @apply focus:outline-none focus:ring-2 focus:ring-offset-2;
    }

    .action-btn-primary {
        @apply bg-emerald-600 hover:bg-emerald-700;
        @apply text-white;
        @apply focus:ring-emerald-500;
    }

    .action-btn-secondary {
        @apply bg-white dark:bg-gray-800;
        @apply border border-gray-300 dark:border-gray-600;
        @apply text-gray-700 dark:text-gray-300;
        @apply hover:bg-gray-50 dark:hover:bg-gray-700;
        @apply focus:ring-gray-500;
    }

    /* Scrollbar personnalisée */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        @apply bg-gray-100 dark:bg-gray-800 rounded-full;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        @apply bg-gray-400 dark:bg-gray-600 rounded-full;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        @apply bg-gray-500 dark:bg-gray-500;
    }
</style>
@endpush

@section('content')
<div class="energy-dashboard p-4 sm:p-6 lg:p-8">
    <!-- En-tête Hero -->
    <div class="hero-header">
        <div class="hero-content">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white/80 text-sm font-semibold uppercase tracking-wide">Vue d'ensemble</p>
                            <h1 class="text-3xl lg:text-4xl font-bold text-white">Tableau de bord énergétique</h1>
                        </div>
                    </div>
                    <p class="text-white/90 text-base lg:text-lg max-w-2xl">
                        Surveillance en temps réel de votre infrastructure de recharge électrique
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="live-badge bg-white/20 backdrop-blur-sm text-white">
                        <span class="live-dot bg-white"></span>
                        Données en direct
                    </div>
                    <button class="action-btn action-btn-secondary bg-white/20 backdrop-blur-sm text-white border-white/30 hover:bg-white/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Actualiser
                    </button>
                    <select class="period-selector bg-white/20 backdrop-blur-sm text-white border-white/30">
                        <option value="today">Aujourd'hui</option>
                        <option value="week">Cette semaine</option>
                        <option value="month" selected>Ce mois</option>
                        <option value="year">Cette année</option>
                    </select>
                </div>
            </div>

            <!-- Statistiques rapides dans le hero -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8">
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <p class="text-white/80 text-sm font-medium mb-1">Taux de disponibilité</p>
                    <p class="text-3xl font-bold text-white">98.4%</p>
                    <p class="text-white/70 text-xs mt-1">+2.1% vs hier</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <p class="text-white/80 text-sm font-medium mb-1">Puissance totale</p>
                    <p class="text-3xl font-bold text-white">2.4 MW</p>
                    <p class="text-white/70 text-xs mt-1">Capacité réseau</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <p class="text-white/80 text-sm font-medium mb-1">Sessions actives</p>
                    <p class="text-3xl font-bold text-white">{{ $stats['activeRecharges'] ?? 23 }}</p>
                    <p class="text-white/70 text-xs mt-1">En cours maintenant</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cartes KPI principales -->
    <div class="stats-grid mb-8">
        <!-- Total des recharges -->
        <div class="kpi-card" style="animation-delay: 0.1s">
            <div class="flex items-start justify-between mb-4">
                <div class="kpi-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="kpi-trend trend-up">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    +12%
                </span>
            </div>
            <p class="kpi-label mb-2">Total des sessions</p>
            <p class="kpi-value" id="total-sessions">{{ number_format($stats['totalRecharges'] ?? 1247) }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">Ce mois-ci</p>
        </div>

        <!-- Sessions actives -->
        <div class="kpi-card" style="animation-delay: 0.2s">
            <div class="flex items-start justify-between mb-4">
                <div class="kpi-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="live-badge">
                    <span class="live-dot"></span>
                    Live
                </span>
            </div>
            <p class="kpi-label mb-2">Sessions actives</p>
            <p class="kpi-value" id="active-sessions">{{ $stats['activeRecharges'] ?? 23 }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">En cours maintenant</p>
        </div>

        <!-- Énergie distribuée -->
        <div class="kpi-card" style="animation-delay: 0.3s">
            <div class="flex items-start justify-between mb-4">
                <div class="kpi-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="kpi-trend trend-up">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    +18%
                </span>
            </div>
            <p class="kpi-label mb-2">Énergie distribuée</p>
            <p class="kpi-value">2.4M</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">kWh ce mois</p>
        </div>

        <!-- Revenus -->
        <div class="kpi-card" style="animation-delay: 0.4s">
            <div class="flex items-start justify-between mb-4">
                <div class="kpi-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
                <span class="kpi-trend trend-up">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    +15%
                </span>
            </div>
            <p class="kpi-label mb-2">Revenus totaux</p>
            <p class="kpi-value">125K €</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">Ce mois-ci</p>
        </div>
    </div>

    <!-- Graphiques principaux -->
    <div class="charts-grid mb-8">
        <!-- Graphique des sessions -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Sessions de recharge</h3>
                    <p class="chart-subtitle">Évolution sur les 7 derniers jours</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">+23%</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">vs semaine dernière</span>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="sessionsChart"></canvas>
            </div>
        </div>

        <!-- Graphique de l'énergie -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Distribution d'énergie</h3>
                    <p class="chart-subtitle">kWh par tranche horaire</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-bold text-orange-600 dark:text-orange-400">2.4M</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">kWh total</span>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="energyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Graphiques de performance -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Types de chargeurs -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Types de chargeurs</h3>
                    <p class="chart-subtitle">Répartition par puissance</p>
                </div>
            </div>
            <div class="chart-container" style="height: 280px;">
                <canvas id="chargerTypesChart"></canvas>
            </div>
        </div>

        <!-- Performance par station -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Top stations</h3>
                    <p class="chart-subtitle">Sessions par jour</p>
                </div>
            </div>
            <div class="chart-container" style="height: 280px;">
                <canvas id="stationPerformanceChart"></canvas>
            </div>
        </div>

        <!-- Disponibilité -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Disponibilité</h3>
                    <p class="chart-subtitle">Taux sur 24h</p>
                </div>
            </div>
            <div class="chart-container" style="height: 280px;">
                <canvas id="availabilityChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Section inférieure : Tableau et widgets -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tableau des sessions actives -->
        <div class="lg:col-span-2 chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Sessions en temps réel</h3>
                    <p class="chart-subtitle">Activité en cours sur le réseau</p>
                </div>
                <span class="live-badge">
                    <span class="live-dot"></span>
                    {{ $stats['activeRecharges'] ?? 23 }} actives
                </span>
            </div>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Station</th>
                            <th>Type</th>
                            <th>Puissance</th>
                            <th class="text-right">Énergie</th>
                            <th class="text-right">Durée</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody id="active-sessions-table">
                        <tr>
                            <td>
                                <div class="font-semibold text-gray-900 dark:text-white">Morocco Mall</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Casablanca</div>
                            </td>
                            <td>DC Fast</td>
                            <td>
                                <div class="power-indicator">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">150 kW</span>
                                </div>
                            </td>
                            <td class="text-right font-semibold">45.2 kWh</td>
                            <td class="text-right">18 min</td>
                            <td>
                                <span class="status-badge status-charging">
                                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                    En charge
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="font-semibold text-gray-900 dark:text-white">Rabat Center</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Rabat</div>
                            </td>
                            <td>AC Normal</td>
                            <td>
                                <div class="power-indicator">
                                    <span class="text-blue-600 dark:text-blue-400 font-semibold">22 kW</span>
                                </div>
                            </td>
                            <td class="text-right font-semibold">12.8 kWh</td>
                            <td class="text-right">35 min</td>
                            <td>
                                <span class="status-badge status-charging">
                                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                    En charge
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="font-semibold text-gray-900 dark:text-white">Marrakech Plaza</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Marrakech</div>
                            </td>
                            <td>DC Fast</td>
                            <td>
                                <div class="power-indicator">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">50 kW</span>
                                </div>
                            </td>
                            <td class="text-right font-semibold">28.5 kWh</td>
                            <td class="text-right">25 min</td>
                            <td>
                                <span class="status-badge status-charging">
                                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                    En charge
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-right">
                <a href="#" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors">
                    Voir toutes les sessions →
                </a>
            </div>
        </div>

        <!-- Widget de statut du réseau -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Statut du réseau</h3>
                    <p class="chart-subtitle">Vue d'ensemble</p>
                </div>
            </div>
            <div class="space-y-6">
                <!-- Stations en ligne -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Stations en ligne</span>
                        <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">156/160</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 97.5%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">97.5% de disponibilité</p>
                </div>

                <!-- Puissance utilisée -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Puissance utilisée</span>
                        <span class="text-sm font-bold text-orange-600 dark:text-orange-400">1.87/2.4 MW</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 78%; background: linear-gradient(to right, #f59e0b, #f97316)"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">78% de capacité</p>
                </div>

                <!-- Sessions simultanées -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sessions simultanées</span>
                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">23/64</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 36%; background: linear-gradient(to right, #3b82f6, #2563eb)"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">36% de capacité</p>
                </div>

                <!-- Statistiques rapides -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Temps moyen/session</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">43 min</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Énergie moyenne</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">32.4 kWh</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Clients actifs</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">312</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Taux de satisfaction</span>
                        <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">96.8%</span>
                    </div>
                </div>

                <!-- Alertes -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Alertes récentes</h4>
                    <div class="space-y-2">
                        <div class="flex items-start gap-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-xs font-medium text-yellow-800 dark:text-yellow-200">Maintenance prévue</p>
                                <p class="text-xs text-yellow-600 dark:text-yellow-400">3 bornes - Demain 14h</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2 p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-xs font-medium text-emerald-800 dark:text-emerald-200">Mise à jour réussie</p>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400">Firmware v2.4.1</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/vendor/chart.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuration des couleurs
    const colors = {
        primary: '#10b981',
        secondary: '#059669',
        accent: '#34d399',
        blue: '#3b82f6',
        orange: '#f59e0b',
        red: '#ef4444',
        purple: '#8b5cf6'
    };

    // Configuration commune pour les graphiques
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                borderRadius: 8,
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 13
                }
            }
        }
    };

    // Graphique des sessions
    const sessionsCtx = document.getElementById('sessionsChart').getContext('2d');
    new Chart(sessionsCtx, {
        type: 'line',
        data: {
            labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            datasets: [{
                label: 'Sessions',
                data: [45, 52, 38, 67, 89, 76, 82],
                borderColor: colors.primary,
                backgroundColor: colors.primary + '20',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: colors.primary,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });

    // Graphique de l'énergie
    const energyCtx = document.getElementById('energyChart').getContext('2d');
    new Chart(energyCtx, {
        type: 'bar',
        data: {
            labels: ['00h', '04h', '08h', '12h', '16h', '20h'],
            datasets: [{
                label: 'kWh',
                data: [120, 80, 200, 350, 280, 180],
                backgroundColor: [
                    colors.orange + '90',
                    colors.orange + '70',
                    colors.orange + '90',
                    colors.red + '90',
                    colors.orange + '80',
                    colors.orange + '60'
                ],
                borderColor: colors.orange,
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
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
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });

    // Graphique en donut - Types de chargeurs
    const chargerTypesCtx = document.getElementById('chargerTypesChart').getContext('2d');
    new Chart(chargerTypesCtx, {
        type: 'doughnut',
        data: {
            labels: ['AC 22kW', 'DC 50kW', 'DC 150kW', 'DC 350kW'],
            datasets: [{
                data: [35, 25, 30, 10],
                backgroundColor: [
                    colors.primary,
                    colors.blue,
                    colors.orange,
                    colors.purple
                ],
                borderWidth: 0,
                cutout: '65%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    ...commonOptions.plugins.tooltip,
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            }
        }
    });

    // Graphique en barres - Performance par station
    const stationPerformanceCtx = document.getElementById('stationPerformanceChart').getContext('2d');
    new Chart(stationPerformanceCtx, {
        type: 'bar',
        data: {
            labels: ['Casa', 'Rabat', 'Marr', 'Fès', 'Agadir'],
            datasets: [{
                label: 'Sessions/jour',
                data: [120, 95, 80, 65, 45],
                backgroundColor: colors.primary,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });

    // Graphique de disponibilité
    const availabilityCtx = document.getElementById('availabilityChart').getContext('2d');
    new Chart(availabilityCtx, {
        type: 'line',
        data: {
            labels: ['00h', '06h', '12h', '18h', '24h'],
            datasets: [{
                label: 'Disponibilité (%)',
                data: [98, 95, 92, 96, 99],
                borderColor: colors.primary,
                backgroundColor: colors.primary + '30',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: colors.primary,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });

    // Mise à jour en temps réel (toutes les 30 secondes)
    setInterval(function() {
        fetch('{{ route("dashboard.realtime-data") }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Mettre à jour les valeurs
            const totalSessions = document.getElementById('total-sessions');
            const activeSessions = document.getElementById('active-sessions');
            
            if (totalSessions && data.totalRecharges !== undefined) {
                totalSessions.textContent = new Intl.NumberFormat('fr-FR').format(data.totalRecharges);
            }
            
            if (activeSessions && data.rechargesActives !== undefined) {
                activeSessions.textContent = data.rechargesActives;
            }
        })
        .catch(error => console.error('Erreur lors de la mise à jour:', error));
    }, 30000);

    // Animation des cartes au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.kpi-card, .chart-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        observer.observe(card);
    });
});
</script>
@endpush

