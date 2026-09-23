@extends('layouts.app')

@section('title', 'Dashboard Mobile')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-energy.css') }}">
<style>
    /* Optimisations spécifiques mobile */
    .mobile-dashboard {
        padding: 0.75rem;
        background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
        min-height: 100vh;
    }

    .dark .mobile-dashboard {
        background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
    }

    /* En-tête compact */
    .mobile-hero {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 1rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    /* Cartes KPI compactes */
    .mobile-kpi {
        background: white;
        border-radius: 0.75rem;
        padding: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .dark .mobile-kpi {
        background: #1f2937;
    }

    .mobile-kpi-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .mobile-kpi-content {
        flex: 1;
        min-width: 0;
    }

    .mobile-kpi-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #111827;
        line-height: 1;
    }

    .dark .mobile-kpi-value {
        color: white;
    }

    .mobile-kpi-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }

    .dark .mobile-kpi-label {
        color: #9ca3af;
    }

    /* Graphiques mobile */
    .mobile-chart-card {
        background: white;
        border-radius: 0.75rem;
        padding: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
    }

    .dark .mobile-chart-card {
        background: #1f2937;
    }

    .mobile-chart-container {
        height: 200px;
        margin-top: 0.75rem;
    }

    /* Liste des sessions mobile */
    .mobile-session-item {
        background: white;
        border-radius: 0.75rem;
        padding: 0.875rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .dark .mobile-session-item {
        background: #1f2937;
    }

    /* Onglets mobile */
    .mobile-tabs {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
        -webkit-overflow-scrolling: touch;
    }

    .mobile-tab {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        white-space: nowrap;
        background: white;
        color: #6b7280;
        border: 1px solid #e5e7eb;
        transition: all 0.2s;
    }

    .dark .mobile-tab {
        background: #1f2937;
        border-color: #374151;
        color: #9ca3af;
    }

    .mobile-tab.active {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }

    /* Bouton flottant */
    .fab {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        z-index: 50;
        transition: transform 0.2s;
    }

    .fab:active {
        transform: scale(0.95);
    }

    /* Pull to refresh */
    .ptr-element {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        transform: translateY(-60px);
        transition: transform 0.3s;
    }

    .ptr-element.ptr-refresh {
        transform: translateY(0);
    }
</style>
@endpush

@section('content')
<div class="mobile-dashboard">
    <!-- En-tête compact -->
    <div class="mobile-hero">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-white/80 text-xs font-semibold uppercase">Dashboard</p>
                    <h1 class="text-lg font-bold text-white">Énergie</h1>
                </div>
            </div>
            <button class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>

        <!-- Stats rapides -->
        <div class="grid grid-cols-3 gap-2">
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-2 text-center">
                <p class="text-2xl font-bold text-white">98%</p>
                <p class="text-white/80 text-xs">Dispo.</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-2 text-center">
                <p class="text-2xl font-bold text-white">2.4</p>
                <p class="text-white/80 text-xs">MW</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-2 text-center">
                <p class="text-2xl font-bold text-white">{{ $stats['activeRecharges'] ?? 23 }}</p>
                <p class="text-white/80 text-xs">Actives</p>
            </div>
        </div>
    </div>

    <!-- Onglets -->
    <div class="mobile-tabs">
        <button class="mobile-tab active" data-tab="overview">Vue d'ensemble</button>
        <button class="mobile-tab" data-tab="sessions">Sessions</button>
        <button class="mobile-tab" data-tab="energy">Énergie</button>
        <button class="mobile-tab" data-tab="stations">Stations</button>
    </div>

    <!-- Contenu Vue d'ensemble -->
    <div id="tab-overview" class="tab-content">
        <!-- Cartes KPI -->
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="mobile-kpi">
                <div class="mobile-kpi-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="mobile-kpi-content">
                    <p class="mobile-kpi-value">{{ number_format($stats['totalRecharges'] ?? 1247) }}</p>
                    <p class="mobile-kpi-label">Sessions</p>
                </div>
            </div>

            <div class="mobile-kpi">
                <div class="mobile-kpi-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="mobile-kpi-content">
                    <p class="mobile-kpi-value">{{ $stats['activeRecharges'] ?? 23 }}</p>
                    <p class="mobile-kpi-label">Actives</p>
                </div>
            </div>

            <div class="mobile-kpi">
                <div class="mobile-kpi-icon" style="background: linear-gradient(135deg, #f59e0b, #f97316);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="mobile-kpi-content">
                    <p class="mobile-kpi-value">2.4M</p>
                    <p class="mobile-kpi-label">kWh</p>
                </div>
            </div>

            <div class="mobile-kpi">
                <div class="mobile-kpi-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
                <div class="mobile-kpi-content">
                    <p class="mobile-kpi-value">125K</p>
                    <p class="mobile-kpi-label">Revenus €</p>
                </div>
            </div>
        </div>

        <!-- Graphique principal -->
        <div class="mobile-chart-card">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Sessions cette semaine</h3>
            <div class="mobile-chart-container">
                <canvas id="mobileSessionsChart"></canvas>
            </div>
        </div>

        <!-- Statut du réseau -->
        <div class="mobile-chart-card">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">Statut du réseau</h3>
            <div class="space-y-3">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-gray-600 dark:text-gray-400">Stations en ligne</span>
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">156/160</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 97.5%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-gray-600 dark:text-gray-400">Puissance utilisée</span>
                        <span class="text-xs font-bold text-orange-600 dark:text-orange-400">1.87/2.4 MW</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 78%; background: linear-gradient(to right, #f59e0b, #f97316)"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-gray-600 dark:text-gray-400">Sessions simultanées</span>
                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400">23/64</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 36%; background: linear-gradient(to right, #3b82f6, #2563eb)"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu Sessions -->
    <div id="tab-sessions" class="tab-content hidden">
        <div class="space-y-2">
            <div class="mobile-session-item">
                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">Morocco Mall</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">DC 150kW • 45.2 kWh</p>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                        En charge
                    </span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">18 min</p>
                </div>
            </div>

            <div class="mobile-session-item">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">Rabat Center</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">AC 22kW • 12.8 kWh</p>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                        En charge
                    </span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">35 min</p>
                </div>
            </div>

            <div class="mobile-session-item">
                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">Marrakech Plaza</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">DC 50kW • 28.5 kWh</p>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                        En charge
                    </span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">25 min</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu Énergie -->
    <div id="tab-energy" class="tab-content hidden">
        <div class="mobile-chart-card">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Distribution d'énergie</h3>
            <div class="mobile-chart-container">
                <canvas id="mobileEnergyChart"></canvas>
            </div>
        </div>

        <div class="mobile-chart-card">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">Répartition par type</h3>
            <div class="space-y-2">
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600 dark:text-gray-400">Rapide (DC)</span>
                        <span class="font-semibold text-gray-900 dark:text-white">62%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 62%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600 dark:text-gray-400">Standard (AC)</span>
                        <span class="font-semibold text-gray-900 dark:text-white">31%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 31%; background: linear-gradient(to right, #3b82f6, #2563eb)"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600 dark:text-gray-400">Indisponible</span>
                        <span class="font-semibold text-gray-900 dark:text-white">7%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 7%; background: linear-gradient(to right, #6b7280, #9ca3af)"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu Stations -->
    <div id="tab-stations" class="tab-content hidden">
        <div class="mobile-chart-card">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Top stations</h3>
            <div class="mobile-chart-container">
                <canvas id="mobileStationsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bouton flottant d'action -->
    <button class="fab">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
    </button>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/vendor/chart.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des onglets
    const tabs = document.querySelectorAll('.mobile-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.dataset.tab;
            
            // Désactiver tous les onglets
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.add('hidden'));
            
            // Activer l'onglet sélectionné
            tab.classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.remove('hidden');
        });
    });

    // Configuration des graphiques
    const chartColors = {
        primary: '#10b981',
        blue: '#3b82f6',
        orange: '#f59e0b'
    };

    // Graphique des sessions mobile
    const sessionsCtx = document.getElementById('mobileSessionsChart');
    if (sessionsCtx) {
        new Chart(sessionsCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['L', 'M', 'M', 'J', 'V', 'S', 'D'],
                datasets: [{
                    label: 'Sessions',
                    data: [45, 52, 38, 67, 89, 76, 82],
                    borderColor: chartColors.primary,
                    backgroundColor: chartColors.primary + '20',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // Graphique de l'énergie mobile
    const energyCtx = document.getElementById('mobileEnergyChart');
    if (energyCtx) {
        new Chart(energyCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['00h', '04h', '08h', '12h', '16h', '20h'],
                datasets: [{
                    label: 'kWh',
                    data: [120, 80, 200, 350, 280, 180],
                    backgroundColor: chartColors.orange + '90',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // Graphique des stations mobile
    const stationsCtx = document.getElementById('mobileStationsChart');
    if (stationsCtx) {
        new Chart(stationsCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Casa', 'Rabat', 'Marr', 'Fès', 'Agadir'],
                datasets: [{
                    label: 'Sessions',
                    data: [120, 95, 80, 65, 45],
                    backgroundColor: chartColors.primary,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }
});
</script>
@endpush

