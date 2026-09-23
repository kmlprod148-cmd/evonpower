@extends('layouts.app')

@section('title', __('dashboard.dashboard'))

@section('content')
@php
    // Active le header avec statistiques pour le dashboard
    $showHeaderStats = true;
    $pageTitle = __('dashboard.dashboard');
    $pageSubtitle = __('dashboard.realtime_overview');
@endphp

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-slate-900 dark:to-indigo-950 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Key Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 animate-slide-up">
            <!-- Total Points -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('dashboard.total_charging_points') }}</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2" data-stat="total-points">{{ $stats['totalPoints'] }}</p>
                        <p class="text-sm text-green-600 dark:text-green-400 mt-2">
                            <span class="inline-flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                {{ $stats['onlinePoints'] }} {{ __('dashboard.online') }}
                            </span>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Sessions -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('dashboard.active_sessions') }}</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2" data-stat="active-transactions">{{ $stats['activeTransactions'] }}</p>
                        <div class="flex items-center mt-2">
                            <span class="relative flex h-2 w-2 mr-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.charging_now') }}</p>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Today Revenue -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('dashboard.today_revenue') }}</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['todayRevenue'], 2) }} MAD</p>
                        <p class="text-sm mt-2 {{ $revenueStats['todayTrend'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $revenueStats['todayTrend'] >= 0 ? '+' : '' }}{{ $revenueStats['todayTrend'] }}% {{ __('dashboard.vs_yesterday') }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Energy Delivered -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('dashboard.energy_today') }}</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['todayEnergy'] }} kWh</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            {{ $stats['todayTransactions'] }} {{ __('dashboard.sessions_completed') }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-slide-up" style="animation-delay: 0.1s">
            <!-- Sessions Chart -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('dashboard.sessions_chart') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('dashboard.last_7_days') }}</p>
                    </div>
                    <select id="chartPeriod" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm">
                        <option value="hourly">{{ __('dashboard.today') }}</option>
                        <option value="weekly" selected>{{ __('dashboard.this_week') }}</option>
                        <option value="monthly">{{ __('dashboard.this_month') }}</option>
                    </select>
                </div>
                <div class="h-80">
                    <canvas id="sessionsChart"></canvas>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">{{ __('dashboard.status_distribution') }}</h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="mt-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('dashboard.online') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $stats['onlinePoints'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('dashboard.offline') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $stats['totalPoints'] - $stats['onlinePoints'] - $stats['maintenancePoints'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('dashboard.maintenance') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $stats['maintenancePoints'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Charging Points & Top Stations -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-slide-up" style="animation-delay: 0.2s">
            <!-- Active Charging Points -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('dashboard.active_charging') }}</h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        <span class="relative flex h-2 w-2 mr-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        {{ __('dashboard.live') }}
                    </span>
                </div>
                <div id="active-charging-list" class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($chargingPoints as $point)
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $point['name'] }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $point['location'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ $point['energy'] }} kWh</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $point['duration'] }} min</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.no_active_charging') }}</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Top Performing Stations -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('dashboard.top_stations') }}</h3>
                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('dashboard.last_7_days') }}</span>
                </div>
                <div class="space-y-4">
                    @forelse($topStations as $index => $station)
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $station['name'] }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $station['city'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $station['sessions'] }} {{ __('dashboard.sessions') }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $station['energy'] }} kWh</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.no_data_available') }}</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 animate-slide-up" style="animation-delay: 0.3s">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">{{ __('dashboard.recent_transactions') }}</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('dashboard.charging_point') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('dashboard.user') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('dashboard.energy') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('dashboard.duration') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('dashboard.amount') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('dashboard.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recentTransactions as $transaction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $transaction['charging_point'] }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $transaction['user_name'] }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $transaction['energy'] }} kWh</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $transaction['duration'] }} min</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-green-600 dark:text-green-400">{{ number_format($transaction['amount'], 2) }} MAD</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $transaction['date'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.no_transactions') }}</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slide-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}

.animate-slide-up {
    animation: slide-up 0.6s ease-out;
}
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let sessionsChart, statusChart;
const chartData = @json($chartData);

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    setupEventListeners();
});

function initCharts() {
    // Sessions Chart
    const sessionsCtx = document.getElementById('sessionsChart').getContext('2d');
    sessionsChart = new Chart(sessionsCtx, {
        type: 'line',
        data: {
            labels: chartData.weekly.labels,
            datasets: [{
                label: '{{ __("dashboard.sessions") }}',
                data: chartData.weekly.data,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    borderRadius: 8,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: { font: { size: 12 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 12 } }
                }
            }
        }
    });

    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const stats = @json($stats);
    const offline = stats.totalPoints - stats.onlinePoints - stats.maintenancePoints;
    
    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['{{ __("dashboard.online") }}', '{{ __("dashboard.offline") }}', '{{ __("dashboard.maintenance") }}'],
            datasets: [{
                data: [stats.onlinePoints, offline, stats.maintenancePoints],
                backgroundColor: [
                    'rgb(34, 197, 94)',
                    'rgb(239, 68, 68)',
                    'rgb(251, 191, 36)'
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    borderRadius: 8,
                }
            },
            cutout: '70%'
        }
    });
}

function setupEventListeners() {
    const chartPeriod = document.getElementById('chartPeriod');
    if (chartPeriod) {
        chartPeriod.addEventListener('change', function() {
            updateSessionsChart(this.value);
        });
    }
}

function updateSessionsChart(period) {
    let data = chartData[period];
    if (!data) return;

    sessionsChart.data.labels = data.labels;
    sessionsChart.data.datasets[0].data = data.data;
    sessionsChart.update();
}

function refreshDashboard() {
    const btn = event.target.closest('button');
    const icon = btn.querySelector('svg');
    icon.classList.add('animate-spin');

    fetch('{{ route("dashboard.enhanced.realtime") }}', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Update stats
        const stats = data.stats;
        document.querySelector('[data-stat="total-points"]').textContent = stats.totalPoints;
        document.querySelector('[data-stat="active-transactions"]').textContent = stats.activeTransactions;

        // Update active charging points
        updateActiveChargingList(data.activeChargingPoints);

        // Show success message
        showNotification('{{ __("dashboard.data_updated") }}', 'success');
    })
    .catch(error => {
        console.error('Error refreshing dashboard:', error);
        showNotification('{{ __("dashboard.refresh_failed") }}', 'error');
    })
    .finally(() => {
        icon.classList.remove('animate-spin');
    });
}

function updateActiveChargingList(points) {
    const container = document.getElementById('active-charging-list');
    if (!container) return;

    if (points.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.no_active_charging') }}</p>
            </div>
        `;
        return;
    }

    container.innerHTML = points.map(point => `
        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <div class="flex-1">
                <p class="font-semibold text-gray-900 dark:text-white">${point.name}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">${point.location}</p>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold text-green-600 dark:text-green-400">${point.energy} kWh</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">${point.duration} min</p>
            </div>
        </div>
    `).join('');
}

function showNotification(message, type = 'success') {
    // Simple notification system
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } text-white z-50 animate-fade-in`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Auto-refresh every 30 seconds
setInterval(() => {
    fetch('{{ route("dashboard.enhanced.realtime") }}', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const stats = data.stats;
        document.querySelector('[data-stat="total-points"]').textContent = stats.totalPoints;
        document.querySelector('[data-stat="active-transactions"]').textContent = stats.activeTransactions;
        updateActiveChargingList(data.activeChargingPoints);
    })
    .catch(error => console.error('Error auto-refreshing:', error));
}, 30000);
</script>
@endpush

