@extends('layouts.app')

@section('title', __('messages.dashboard'))

@section('content')
@php
    // Variables locales pour le contenu de la page
    $topStations = collect($activeRecharges ?? [
        ['name' => 'Station Centre', 'kwh' => 25.5, 'trend' => '+8%'],
        ['name' => 'Station Nord', 'kwh' => 18.2, 'trend' => '+5%'],
        ['name' => 'Station Sud', 'kwh' => 15.8, 'trend' => '+2%'],
        ['name' => 'Station Est', 'kwh' => 12.3, 'trend' => '+1%'],
    ])->take(4);

    $timeline = $activiteRecente ?? [
        ['message' => 'Station Alpha a terminé une session rapide', 'time' => 'Il y a 12 min'],
        ['message' => 'Nouveau plan tarifaire publié', 'time' => 'Il y a 2 h'],
        ['message' => 'Maintenance préventive confirmée', 'time' => 'Hier • 18:40'],
    ];
@endphp
<div class="dashboard-gradient dashboard-surface space-y-6 pb-10">
    <!-- Hero -->
    <div class="dashboard-panel dashboard-hero">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-eco-green-600 dark:text-eco-green-300">{{ __('messages.dashboard') }}</p>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                    Tableau de bord énergétique
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    Vue consolidée des performances de vos groupes, bornes et revenus en temps réel.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-flex items-center gap-2 rounded-full border border-eco-green-200 dark:border-eco-green-500/40 px-3 py-1.5 text-xs font-semibold text-eco-green-700 dark:text-eco-green-200">
                    <span class="status-dot status-dot-live"></span>
                    Système opérationnel
                </div>
                <button class="dashboard-chip dashboard-chip-active">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Actualiser
                </button>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
            <button class="dashboard-chip dashboard-chip-active">Vue globale</button>
            <button class="dashboard-chip">Infrastructure</button>
            <button class="dashboard-chip">Finances</button>
            <button class="dashboard-chip">Clients</button>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="hero-stat">
                <p class="hero-stat-label">Taux de disponibilité</p>
                <p class="hero-stat-value">{{ data_get($stats ?? [], 'availabilityRate', '98,4%') }}</p>
                <p class="hero-stat-trend trend-up">+2,1% vs hier</p>
            </div>
            <div class="hero-stat">
                <p class="hero-stat-label">Consommation moyenne</p>
                <p class="hero-stat-value">{{ data_get($stats ?? [], 'avgConsumption', '32 kWh') }}</p>
                <p class="hero-stat-trend text-gray-500 dark:text-gray-300">Stable</p>
            </div>
            <div class="hero-stat">
                <p class="hero-stat-label">Alertes critiques</p>
                <p class="hero-stat-value">{{ data_get($stats ?? [], 'criticalAlerts', 2) }}</p>
                <p class="hero-stat-trend trend-live">2 à traiter</p>
            </div>
        </div>
    </div>

    <!-- System health -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="system-health-card">
            <div class="system-health-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c1.657 0 3 .895 3 2s-1.343 2-3 2-3 .895-3 2 1.343 2 3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Charge moyenne</p>
                <p class="text-xl font-semibold text-gray-900 dark:text-white">32,4 kWh</p>
                <p class="text-xs text-eco-green-600 dark:text-eco-green-300">+4% vs semaine dernière</p>
            </div>
        </div>
        <div class="system-health-card">
            <div class="system-health-icon text-blue-600 dark:text-blue-200 bg-blue-100 dark:bg-blue-900/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 8L2 12l4 4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Maintenance programmée</p>
                <p class="text-xl font-semibold text-gray-900 dark:text-white">3 bornes</p>
                <p class="text-xs text-yellow-600 dark:text-yellow-300">Intervention planifiée</p>
            </div>
        </div>
        <div class="system-health-card">
            <div class="system-health-icon text-purple-600 dark:text-purple-200 bg-purple-100 dark:bg-purple-900/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3a1 1 0 011 0l8 4.5a1 1 0 010 1.732L12 14.5 3 9.232a1 1 0 010-1.732L11 3z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10l9 5 9-5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 14l9 5 9-5"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Capacité réseau</p>
                <p class="text-xl font-semibold text-gray-900 dark:text-white">78%</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Répartition optimale</p>
            </div>
        </div>
    </div>

    <!-- Key metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="stats-card stats-card-primary">
            <div>
                <p class="stats-card-label">{{ __('messages.total_recharges') }}</p>
                <p class="stats-card-value" data-stat="total-recharges">{{ number_format(data_get($stats ?? [], 'totalRecharges', 1250)) }}</p>
                <p class="stats-card-trend trend-up">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l4 4L19 6"/>
                    </svg>
                    +12% ce mois
                </p>
            </div>
            <div class="stats-card-icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
        </div>

        <div class="stats-card stats-card-success">
            <div>
                <p class="stats-card-label">{{ __('messages.active_recharges') }}</p>
                <p class="stats-card-value" data-stat="active-recharges">{{ data_get($stats ?? [], 'activeRecharges', 8) }}</p>
                <p class="stats-card-trend trend-live">
                    <span class="status-dot status-dot-live"></span>
                    Sessions en cours
                </p>
            </div>
            <div class="stats-card-icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <div class="stats-card stats-card-warning">
            <div>
                <p class="stats-card-label">{{ __('messages.active_subscriptions') }}</p>
                <p class="stats-card-value">{{ data_get($stats ?? [], 'activeSubscriptions', 45) }}</p>
                <p class="stats-card-trend text-white/80">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01"/>
                    </svg>
                    8 demandes en attente
                </p>
            </div>
            <div class="stats-card-icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>

        <div class="stats-card stats-card-info">
            <div>
                <p class="stats-card-label">Revenus totaux</p>
                <p class="stats-card-value">{{ number_format(data_get($stats ?? [], 'revenusTotaux', 125000), 0, ',', ' ') }} €</p>
                <p class="stats-card-trend">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    +15% ce mois
                </p>
            </div>
            <div class="stats-card-icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="energy-card lg:col-span-2">
            <div class="energy-card-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div>
                    <p class="text-sm uppercase tracking-wide text-white/70">Énergie distribuée</p>
                    <p class="text-4xl font-bold mt-2">{{ data_get($stats ?? [], 'energyDistributed', '2,4 MWh') }}</p>
                    <p class="mt-3 text-white/80 text-sm">
                        Croissance soutenue sur les sessions rapides aux heures de pointe avec une stabilité réseau optimale.
                    </p>
                    <div class="flex flex-wrap gap-3 mt-4">
                        <span class="inline-flex items-center gap-2 text-xs font-semibold bg-white/20 px-3 py-1.5 rounded-full">
                            <span class="status-dot status-dot-live"></span> Pics midi
                        </span>
                        <span class="inline-flex items-center gap-2 text-xs font-semibold bg-white/20 px-3 py-1.5 rounded-full">
                            🔌 64 sessions simultanées
                        </span>
                    </div>
                </div>
                <div class="mini-chart w-full sm:w-64 text-gray-900">
                    <p class="text-sm font-semibold text-gray-600">Répartition</p>
                    <div class="mt-3 space-y-3 text-sm">
                        @php
                            $mix = [
                                ['label' => 'Rapide (DC)', 'value' => 62, 'color' => 'bg-eco-green-500'],
                                ['label' => 'Standard (AC)', 'value' => 31, 'color' => 'bg-eco-green-300'],
                                ['label' => 'Indisponible', 'value' => 7, 'color' => 'bg-white/50'],
                            ];
                        @endphp
                        @foreach($mix as $item)
                            <div>
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-300">
                                    <span>{{ $item['label'] }}</span>
                                    <span>{{ $item['value'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-200/60 dark:bg-gray-800 rounded-full h-2 mt-1">
                                    <div class="{{ $item['color'] }} h-2 rounded-full" style="width: {{ $item['value'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4">
            <div class="insight-card">
                <p class="insight-label">Temps moyen par session</p>
                <p class="insight-value">43 min</p>
                <p class="insight-trend trend-up">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l4 4L19 6"/>
                    </svg>
                    -5 min vs semaine dernière
                </p>
            </div>
            <div class="insight-card">
                <p class="insight-label">Clients actifs aujourd'hui</p>
                <p class="insight-value">312</p>
                <p class="insight-trend text-gray-500 dark:text-gray-300">+24 nouveaux inscrits</p>
            </div>
        </div>
    </div>

    <!-- Charts row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="dashboard-panel lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sessions de recharge</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Comparaison des sessions terminées sur la période</p>
                </div>
                <select class="dashboard-select">
                    <option>Aujourd'hui</option>
                    <option>Cette semaine</option>
                    <option>Ce mois</option>
                    <option>Cette année</option>
                </select>
            </div>
            <div class="h-80">
                <canvas id="sessionsChart"></canvas>
            </div>
        </div>

        <div class="dashboard-panel">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Points de charge actifs</h3>
                <span class="text-xs font-semibold text-eco-green-600 dark:text-eco-green-300">Live</span>
            </div>
            <div id="active-recharges-list" class="space-y-3">
                @forelse($activeRecharges ?? [] as $recharge)
                    <div class="table-row-item">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $recharge['name'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $recharge['kwh'] }} kWh</p>
                        </div>
                        <span class="text-xs font-semibold text-eco-green-600 dark:text-eco-green-300">Actif</span>
                    </div>
                @empty
                    <div class="empty-state">
                        <p class="font-semibold">Aucune donnée disponible</p>
                        <p class="text-sm mt-2">Les prochaines sessions actives apparaîtront ici.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Activity & performance -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="dashboard-panel xl:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Activité récente</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">Flux live</span>
            </div>
            <div class="space-y-4">
                @forelse($timeline as $event)
                    <div class="timeline-item">
                        <span class="timeline-dot"></span>
                        <p class="timeline-time">{{ $event['time'] }}</p>
                        <p class="timeline-label">{{ $event['message'] }}</p>
                    </div>
                @empty
                    <div class="empty-state">
                        <p class="font-semibold">Aucune activité récente</p>
                        <p class="text-sm mt-2">Les actions récentes s'affichent automatiquement ici.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="dashboard-panel space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top stations</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">24h</span>
            </div>
            @forelse($topStations as $station)
                <div class="top-station-card">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $station['name'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($station['kwh'], 1, ',', ' ') }} kWh</p>
                    </div>
                    <span class="top-station-performance performance-positive">{{ $station['trend'] ?? '+3%' }}</span>
                </div>
            @empty
                <div class="empty-state">
                    <p class="font-semibold">Aucune station disponible</p>
                    <p class="text-sm mt-2">Les meilleures performances s'afficheront ici.</p>
                </div>
            @endforelse

            <div class="mini-chart">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Performance des bornes</p>
                <div class="h-48">
                    <canvas id="performanceChart"></canvas>
                </div>
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
    // Vérifier que Chart.js est chargé
    if (typeof Chart === 'undefined') {
        console.error('Chart.js n\'est pas chargé !');
        return;
    }
    
    const sessionsCtx = document.getElementById('sessionsChart');
    if (!sessionsCtx) {
        console.error('Canvas sessionsChart introuvable !');
        return;
    }
    
    new Chart(sessionsCtx.getContext('2d'), {
        type: 'line',
        data: {
            labels: {!! isset($chartData['labels']) ? json_encode($chartData['labels']) : json_encode(['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h']) !!},
            datasets: [{
                label: 'Sessions de recharge',
                data: {!! isset($chartData['data']) ? json_encode($chartData['data']) : json_encode([15, 8, 12, 25, 45, 38, 22, 18]) !!},
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
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
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(200, 200, 200, 0.2)' }
                },
                x: { grid: { display: false } }
            }
        }
    });

    const performanceCtx = document.getElementById('performanceChart');
    if (!performanceCtx) {
        console.error('Canvas performanceChart introuvable !');
        return;
    }
    new Chart(performanceCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Opérationnelles', 'En maintenance', 'Hors service'],
            datasets: [{
                data: [85, 10, 5],
                backgroundColor: ['rgb(34, 197, 94)', 'rgb(251, 191, 36)', 'rgb(239, 68, 68)'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 20 }
                }
            }
        }
    });

    const statElements = {
        total: document.querySelector('[data-stat="total-recharges"]'),
        active: document.querySelector('[data-stat="active-recharges"]')
    };
    const activeList = document.getElementById('active-recharges-list');
    const numberFormatter = new Intl.NumberFormat('fr-FR');

    function renderActiveList(items = []) {
        if (!activeList) return;
        if (!items.length) {
            activeList.innerHTML = `
                <div class="empty-state">
                    <p class="font-semibold">Aucune donnée disponible</p>
                    <p class="text-sm mt-2">Les prochaines sessions actives apparaîtront ici.</p>
                </div>
            `;
            return;
        }

        activeList.innerHTML = items.slice(0, 4).map(item => `
            <div class="table-row-item">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">${item.name ?? 'Station inconnue'}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">${item.kwh ?? 0} kWh</p>
                </div>
                <span class="text-xs font-semibold text-eco-green-600 dark:text-eco-green-300">Actif</span>
            </div>
        `).join('');
    }

    function updateDashboard() {
        fetch('{{ route("dashboard.realtime-data") }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (statElements.total && data.totalRecharges !== undefined) {
                statElements.total.textContent = numberFormatter.format(data.totalRecharges);
            }
            if (statElements.active && data.rechargesActives !== undefined) {
                statElements.active.textContent = numberFormatter.format(data.rechargesActives);
            }
            if (Array.isArray(data.rechargesActivesList)) {
                renderActiveList(data.rechargesActivesList);
            }
        })
        .catch(error => console.error('Erreur lors de la mise à jour:', error));
    }

    setInterval(updateDashboard, 30000);
});
</script>
@endpush

