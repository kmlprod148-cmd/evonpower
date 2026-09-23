@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('page-title', 'Tableau de Bord')

@section('content')
<div class="space-y-6">
    <!-- En-tête du tableau de bord -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tableau de Bord</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Vue d'ensemble de votre système de recharge électrique</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-3">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Système opérationnel</span>
                </div>
                <button class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Actualiser
                </button>
            </div>
        </div>
    </div>

    <!-- Cartes de statistiques principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Points de charge totaux -->
        <div class="stats-card stats-card-primary">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-primary-100 text-sm font-medium">Points de charge totaux</p>
                    <p class="text-3xl font-bold mt-2">{{ $totalRecharges ?? 1250 }}</p>
                    <div class="mt-2 flex items-center">
                        <svg class="w-4 h-4 text-green-300 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-green-300">+12% ce mois</span>
                    </div>
                </div>
                <div class="h-12 w-12 rounded-full bg-primary-400 bg-opacity-30 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Points de charge actifs -->
        <div class="stats-card stats-card-success">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Points de charge actifs</p>
                    <p class="text-3xl font-bold mt-2">{{ $rechargesActives ?? 8 }}</p>
                    <div class="mt-2 flex items-center">
                        <div class="w-2 h-2 bg-green-300 rounded-full mr-2 animate-pulse"></div>
                        <span class="text-sm text-green-300">En cours</span>
                    </div>
                </div>
                <div class="h-12 w-12 rounded-full bg-green-400 bg-opacity-30 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Abonnements actifs -->
        <div class="stats-card stats-card-warning">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm font-medium">Abonnements actifs</p>
                    <p class="text-3xl font-bold mt-2">{{ $abonnementsActifs ?? 45 }}</p>
                    <div class="mt-2 flex items-center">
                        <svg class="w-4 h-4 text-yellow-300 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-yellow-300">+8% ce mois</span>
                    </div>
                </div>
                <div class="h-12 w-12 rounded-full bg-yellow-400 bg-opacity-30 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Revenus totaux -->
        <div class="stats-card stats-card-info">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-cyan-100 text-sm font-medium">Revenus totaux</p>
                    <p class="text-3xl font-bold mt-2">{{ number_format($revenusTotaux ?? 125000, 0, ',', ' ') }} EUR</p>
                    <div class="mt-2 flex items-center">
                        <svg class="w-4 h-4 text-cyan-300 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-cyan-300">+15% ce mois</span>
                    </div>
                </div>
                <div class="h-12 w-12 rounded-full bg-cyan-400 bg-opacity-30 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et tableaux -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Graphique des sessions -->
        <div class="lg:col-span-2 chart-container">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sessions de recharge</h3>
                <div class="relative">
                    <select class="text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded px-3 py-1 bg-white dark:bg-gray-800">
                        <option>Aujourd'hui</option>
                        <option>Cette semaine</option>
                        <option>Ce mois</option>
                        <option>Cette année</option>
                    </select>
                </div>
            </div>
            <div class="h-80">
                <canvas id="sessionsChart"></canvas>
            </div>
        </div>

        <!-- Tableau des points de charge actifs -->
        <div class="table-container">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Points de charge actifs</h3>
            <div class="space-y-3">
                @forelse($rechargesActivesList ?? [] as $recharge)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $recharge['name'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $recharge['kwh'] }} kWh</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="h-2 w-2 bg-green-400 rounded-full animate-pulse"></div>
                            <span class="text-xs text-green-600 dark:text-green-400">Actif</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Aucune donnée disponible</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Cartes d'activité récente -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Activité récente -->
        <div class="chart-container">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Activité récente</h3>
            <div class="space-y-4">
                @forelse($activiteRecente ?? [] as $activite)
                    <div class="flex items-center space-x-3">
                        <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                            <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activite['message'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $activite['time'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Aucune activité récente</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Performance des bornes -->
        <div class="chart-container">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performance des bornes</h3>
            <div class="h-64">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique des sessions
    const sessionsCtx = document.getElementById('sessionsChart').getContext('2d');
    const sessionsChart = new Chart(sessionsCtx, {
        type: 'line',
        data: {
            labels: {!! isset($hourlyData['labels']) ? json_encode($hourlyData['labels']) : json_encode(['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h']) !!},
            datasets: [{
                label: 'Sessions de recharge',
                data: {!! isset($hourlyData['data']) ? json_encode($hourlyData['data']) : json_encode([15, 8, 12, 25, 45, 38, 22, 18]) !!},
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointRadius: 4,
                pointHoverRadius: 6
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
                        color: 'rgba(200, 200, 200, 0.1)'
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

    // Graphique de performance
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    const performanceChart = new Chart(performanceCtx, {
        type: 'doughnut',
        data: {
            labels: ['Opérationnelles', 'En maintenance', 'Hors service'],
            datasets: [{
                data: [85, 10, 5],
                backgroundColor: [
                    'rgb(34, 197, 94)',
                    'rgb(251, 191, 36)',
                    'rgb(239, 68, 68)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });

    // Mise à jour en temps réel
    function updateDashboard() {
        fetch('{{ route("dashboard.realtime-data") }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Mettre à jour les statistiques
            if (data.rechargesActives !== undefined) {
                document.querySelector('.stats-card-success .text-3xl').textContent = data.rechargesActives;
            }
            if (data.totalRecharges !== undefined) {
                document.querySelector('.stats-card-primary .text-3xl').textContent = data.totalRecharges;
            }
        })
        .catch(error => console.error('Erreur lors de la mise à jour:', error));
    }

    // Mise à jour toutes les 30 secondes
    setInterval(updateDashboard, 30000);
});
</script>
@endpush
