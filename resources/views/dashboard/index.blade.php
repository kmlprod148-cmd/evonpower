@extends('layouts.app')

@section('title', __('messages.dashboard'))
@section('page-title', __('messages.dashboard'))

@push('styles')
<style>
    .chart-container {
        position: relative;
        height: 400px;
        margin: 20px 0;
    }
    .chart-card {
        @apply rounded-xl p-6 text-white shadow-lg;
        background: linear-gradient(135deg, #4acf7b 0%, #3bb86b 50%, #2d8f56 100%);
    }
    .chart-card-energy {
        @apply rounded-xl p-6 text-white shadow-lg;
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 50%, #ef4444 100%);
    }
    .stats-card {
        @apply card card-hover border-l-4 border-eco-green-500;
    }
    .metric-icon {
        @apply w-12 h-12 rounded-xl flex items-center justify-center text-2xl text-white;
        background: linear-gradient(135deg, #4acf7b 0%, #3bb86b 100%);
    }
</style>
@endpush

@section('content')
<!-- Page Header -->
<div class="mb-6">
    <p class="text-label mb-1">{{ __('messages.groups') }}</p>
    <h1 class="heading-3 text-gray-900 dark:text-white">
        {{ __('messages.dashboard') }}
    </h1>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Sessions de recharge -->
    <div class="stat-card border-l-4 border-eco-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-label mb-2">{{ __('messages.charging_sessions') }}</p>
                <p class="text-stat">{{ number_format($totalRecharges ?? 1247) }}</p>
                <p class="text-sm text-eco-green-600 dark:text-eco-green-400 mt-1">↗ {{ __('messages.percent_this_month') }}</p>
            </div>
            <div class="metric-icon">
                ⚡
            </div>
        </div>
    </div>

    <!-- Sessions actives -->
    <div class="stat-card border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-label mb-2">{{ __('messages.active_sessions') }}</p>
                <p class="text-stat">{{ $rechargesActives ?? 23 }}</p>
                <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">{{ __('messages.in_progress_now') }}</p>
            </div>
            <div class="metric-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                🔌
            </div>
        </div>
    </div>

    <!-- Énergie distribuée -->
    <div class="stat-card border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-label mb-2">{{ __('messages.energy_distributed') }}</p>
                <p class="text-stat">2.4M kWh</p>
                <p class="text-sm text-orange-600 dark:text-orange-400 mt-1">{{ __('messages.percent_vs_last_month') }}</p>
            </div>
            <div class="metric-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);">
                🔋
            </div>
        </div>
    </div>

    <!-- Bornes opérationnelles -->
    <div class="stat-card border-l-4 border-eco-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-label mb-2">{{ __('messages.operational_stations') }}</p>
                <p class="text-stat">{{ $bornesActives ?? 156 }}</p>
                <p class="text-sm text-eco-green-600 dark:text-eco-green-400 mt-1">{{ __('messages.availability_percent') }}</p>
            </div>
            <div class="metric-icon">
                🏭
            </div>
        </div>
    </div>
</div>

<!-- Graphiques modernes -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Graphique des sessions de recharge -->
    <div class="chart-card">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-xl font-bold text-white">{{ __('messages.charging_sessions') }}</h3>
                <p class="text-white/80">{{ __('messages.evolution_last_7_days') }}</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-white">+23%</p>
                <p class="text-white/80 text-sm">{{ __('messages.vs_last_week') }}</p>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="sessionsChart"></canvas>
        </div>
    </div>

    <!-- Graphique de l'énergie distribuée -->
    <div class="chart-card-energy">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-xl font-bold text-white">{{ __('messages.energy_distributed') }}</h3>
                <p class="text-white/80">{{ __('messages.kwh_per_hour') }}</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-white">2.4M kWh</p>
                <p class="text-white/80 text-sm">{{ __('messages.this_month') }}</p>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="energyChart"></canvas>
        </div>
    </div>
</div>

<!-- Graphiques de performance -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Graphique en donut - Types de chargeurs -->
    <div class="card">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.charger_types') }}</h3>
        <div class="chart-container" style="height: 300px;">
            <canvas id="chargerTypesChart"></canvas>
        </div>
    </div>

    <!-- Graphique en barres - Performance par station -->
    <div class="card">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.performance_by_station') }}</h3>
        <div class="chart-container" style="height: 300px;">
            <canvas id="stationPerformanceChart"></canvas>
        </div>
    </div>

    <!-- Graphique de disponibilité -->
    <div class="card">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.station_availability') }}</h3>
        <div class="chart-container" style="height: 300px;">
            <canvas id="availabilityChart"></canvas>
        </div>
    </div>
</div>

<!-- Tableau des sessions actives -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('messages.realtime_sessions') }}</h3>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-eco-green-100 dark:bg-eco-green-900/30 text-eco-green-800 dark:text-eco-green-300">
                {{ $rechargesActives ?? 23 }} {{ __('messages.active_label') }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="table-header">{{ __('messages.station') }}</th>
                        <th class="table-header">{{ __('messages.type') }}</th>
                        <th class="table-header text-right">kWh</th>
                        <th class="table-header text-right">{{ __('messages.duration') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="table-row">
                        <td class="table-cell">Morocco Mall</td>
                        <td class="table-cell">DC 150kW</td>
                        <td class="table-cell text-right">45.2</td>
                        <td class="table-cell text-right">18 min</td>
                    </tr>
                    <tr class="table-row">
                        <td class="table-cell">Rabat Center</td>
                        <td class="table-cell">AC 22kW</td>
                        <td class="table-cell text-right">12.8</td>
                        <td class="table-cell text-right">35 min</td>
                    </tr>
                    <tr class="table-row">
                        <td class="table-cell">Marrakech Plaza</td>
                        <td class="table-cell">DC 50kW</td>
                        <td class="table-cell text-right">28.5</td>
                        <td class="table-cell text-right">25 min</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-right">
            <a href="#" class="text-sm text-eco-green-600 dark:text-eco-green-400 hover:text-eco-green-700 dark:hover:text-eco-green-300 hover:underline transition-colors">{{ __('messages.view_all_sessions') }}</a>
        </div>
    </div>

    <!-- Widget de statut en temps réel -->
    <div class="card">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.network_status') }}</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.stations_online') }}</span>
                <span class="text-sm font-semibold text-eco-green-600 dark:text-eco-green-400">156/160</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.sessions_in_progress') }}</span>
                <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">23</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.total_power') }}</span>
                <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">2.4 MW</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.availability') }}</span>
                <span class="text-sm font-semibold text-eco-green-600 dark:text-eco-green-400">98.2%</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuration des couleurs EVON (eco-green)
    const electricColors = {
        primary: '#4acf7b',
        secondary: '#3bb86b',
        accent: '#2d8f56',
        success: '#4acf7b',
        warning: '#f59e0b',
        danger: '#ef4444'
    };

    // Graphique des sessions de recharge
    const sessionsCtx = document.getElementById('sessionsChart').getContext('2d');
    new Chart(sessionsCtx, {
        type: 'line',
        data: {
            labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            datasets: [{
                label: 'Sessions',
                data: [45, 52, 38, 67, 89, 76, 82],
                borderColor: '#ffffff',
                backgroundColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#4acf7b',
                pointBorderWidth: 3,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)'
                    }
                }
            }
        }
    });

    // Graphique de l'énergie distribuée
    const energyCtx = document.getElementById('energyChart').getContext('2d');
    new Chart(energyCtx, {
        type: 'bar',
        data: {
            labels: ['00h', '04h', '08h', '12h', '16h', '20h'],
            datasets: [{
                label: 'kWh',
                data: [120, 80, 200, 350, 280, 180],
                backgroundColor: [
                    'rgba(255, 255, 255, 0.8)',
                    'rgba(255, 255, 255, 0.6)',
                    'rgba(255, 255, 255, 0.8)',
                    'rgba(255, 255, 255, 0.9)',
                    'rgba(255, 255, 255, 0.7)',
                    'rgba(255, 255, 255, 0.5)'
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)'
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
                    electricColors.primary,
                    electricColors.secondary,
                    electricColors.accent,
                    electricColors.success
                ],
                borderWidth: 0,
                cutout: '60%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
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
            labels: ['Casablanca', 'Rabat', 'Marrakech', 'Fès', 'Agadir'],
            datasets: [{
                label: 'Sessions/jour',
                data: [120, 95, 80, 65, 45],
                backgroundColor: electricColors.primary,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
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
                borderColor: electricColors.success,
                backgroundColor: electricColors.success + '20',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: electricColors.success,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Animation des cartes statistiques
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease-out';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.stats-card').forEach(card => {
        observer.observe(card);
    });
});
</script>
@endpush
