<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard énergétique moderne pour la gestion de bornes de recharge électrique">
    <title>Dashboard Énergétique - Démo</title>
    
    <!-- Tailwind CSS CDN pour la démo -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Styles personnalisés -->
    <link rel="stylesheet" href="{{ asset('css/dashboard-energy.css') }}">
    
    <style>
        /* Configuration Tailwind pour les couleurs personnalisées */
        @layer utilities {
            .text-eco-green-600 { color: #10b981; }
            .text-eco-green-300 { color: #6ee7b7; }
            .bg-eco-green-600 { background-color: #10b981; }
            .bg-eco-green-100 { background-color: #d1fae5; }
            .bg-eco-green-900 { background-color: rgba(16, 185, 129, 0.2); }
            .border-eco-green-500 { border-color: #10b981; }
        }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    <!-- Navigation simple pour la démo -->
    <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-900 dark:text-white">EVON Dashboard</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="toggleDarkMode()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                        DÉMO
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main>
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
                            <button onclick="refreshData()" class="action-btn action-btn-secondary bg-white/20 backdrop-blur-sm text-white border-white/30 hover:bg-white/30">
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
                            <p class="text-3xl font-bold text-white" id="demo-active-sessions">23</p>
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
                    <p class="kpi-value" id="demo-total-sessions">1,247</p>
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
                    <p class="kpi-value">23</p>
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
                        <canvas id="demoSessionsChart"></canvas>
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
                        <canvas id="demoEnergyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Message de démo -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Mode démo :</strong> Ceci est une version de démonstration du dashboard énergétique. 
                            Les données affichées sont fictives et à titre d'illustration uniquement.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-8">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                © 2025 EVON Dashboard - Tableau de bord énergétique moderne
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Toggle dark mode
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        }

        // Charger la préférence du mode sombre
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }

        // Refresh data (simulation)
        function refreshData() {
            const btn = event.currentTarget;
            const icon = btn.querySelector('svg');
            icon.style.animation = 'spin 0.6s linear';
            
            setTimeout(() => {
                icon.style.animation = '';
                // Simuler une mise à jour
                const totalSessions = document.getElementById('demo-total-sessions');
                const activeSessions = document.getElementById('demo-active-sessions');
                
                if (totalSessions) {
                    const newValue = Math.floor(Math.random() * 100) + 1200;
                    totalSessions.textContent = new Intl.NumberFormat('fr-FR').format(newValue);
                }
                
                if (activeSessions) {
                    const newValue = Math.floor(Math.random() * 20) + 15;
                    activeSessions.textContent = newValue;
                }
            }, 600);
        }

        // Initialiser les graphiques
        document.addEventListener('DOMContentLoaded', function() {
            const colors = {
                primary: '#10b981',
                blue: '#3b82f6',
                orange: '#f59e0b'
            };

            // Graphique des sessions
            const sessionsCtx = document.getElementById('demoSessionsChart');
            if (sessionsCtx) {
                new Chart(sessionsCtx.getContext('2d'), {
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
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0, 0, 0, 0.05)' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // Graphique de l'énergie
            const energyCtx = document.getElementById('demoEnergyChart');
            if (energyCtx) {
                new Chart(energyCtx.getContext('2d'), {
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
                                '#ef4444' + '90',
                                colors.orange + '80',
                                colors.orange + '60'
                            ],
                            borderRadius: 8
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
                                grid: { color: 'rgba(0, 0, 0, 0.05)' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>





