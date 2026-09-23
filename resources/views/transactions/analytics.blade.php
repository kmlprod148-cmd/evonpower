@extends('layouts.app')

@section('title', 'Analytiques des Transactions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line"></i> Analytiques des Transactions
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" onclick="refreshAnalytics()">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtres de période -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-filter"></i> Filtres de Période
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form id="analyticsFilters">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="period">Période</label>
                                                    <select class="form-control" id="period" name="period" onchange="updateDateInputs()">
                                                        <option value="today">Aujourd'hui</option>
                                                        <option value="yesterday">Hier</option>
                                                        <option value="this_week">Cette semaine</option>
                                                        <option value="last_week">Semaine dernière</option>
                                                        <option value="this_month" selected>Ce mois</option>
                                                        <option value="last_month">Mois dernier</option>
                                                        <option value="this_year">Cette année</option>
                                                        <option value="custom">Personnalisé</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="start_date">Date de début</label>
                                                    <input type="date" class="form-control" id="start_date" name="start_date">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="end_date">Date de fin</label>
                                                    <input type="date" class="form-control" id="end_date" name="end_date">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <button type="button" class="btn btn-primary btn-block" onclick="applyFilters()">
                                                        <i class="fas fa-search"></i> Appliquer
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques principales -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-exchange-alt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Transactions</span>
                                    <span class="info-box-number" id="totalTransactions">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-euro-sign"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Chiffre d'Affaires</span>
                                    <span class="info-box-number" id="totalRevenue">0 EUR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-percentage"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Commissions</span>
                                    <span class="info-box-number" id="totalCommissions">0 EUR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-chart-line"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Taux de Réussite</span>
                                    <span class="info-box-number" id="successRate">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graphiques -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-area"></i> Évolution des Transactions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="transactionsChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-pie"></i> Répartition par Type
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="transactionTypesChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-bar"></i> Top Points de Charge
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="chargingPointsChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-line"></i> Évolution des Revenus
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tableau des statistiques détaillées -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-table"></i> Statistiques Détaillées
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Métrique</th>
                                                    <th>Valeur</th>
                                                    <th>Évolution</th>
                                                    <th>Tendance</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detailedStats">
                                                <!-- Les données seront chargées via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts pour les graphiques -->
<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
let transactionsChart, transactionTypesChart, chargingPointsChart, revenueChart;

document.addEventListener('DOMContentLoaded', function() {
    initializeDateInputs();
    loadAnalytics();
});

function initializeDateInputs() {
    const today = new Date();
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.getElementById('start_date').value = startOfMonth.toISOString().split('T')[0];
    document.getElementById('end_date').value = today.toISOString().split('T')[0];
}

function updateDateInputs() {
    const period = document.getElementById('period').value;
    const today = new Date();
    let startDate, endDate;

    switch(period) {
        case 'today':
            startDate = endDate = today;
            break;
        case 'yesterday':
            startDate = endDate = new Date(today.getTime() - 24 * 60 * 60 * 1000);
            break;
        case 'this_week':
            startDate = new Date(today.getTime() - (today.getDay() * 24 * 60 * 60 * 1000));
            endDate = today;
            break;
        case 'last_week':
            startDate = new Date(today.getTime() - ((today.getDay() + 7) * 24 * 60 * 60 * 1000));
            endDate = new Date(today.getTime() - (today.getDay() * 24 * 60 * 60 * 1000));
            break;
        case 'this_month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = today;
            break;
        case 'last_month':
            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'this_year':
            startDate = new Date(today.getFullYear(), 0, 1);
            endDate = today;
            break;
        default:
            return;
    }

    document.getElementById('start_date').value = startDate.toISOString().split('T')[0];
    document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
}

function applyFilters() {
    loadAnalytics();
}

function refreshAnalytics() {
    loadAnalytics();
}

function loadAnalytics() {
    const formData = new FormData(document.getElementById('analyticsFilters'));
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        params.append(key, value);
    }

    fetch(`{{ route('transactions.analytics.data') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            updateStatistics(data.statistics);
            updateCharts(data.charts);
            updateDetailedStats(data.detailedStats);
        })
        .catch(error => {
            console.error('Erreur lors du chargement des analytiques:', error);
        });
}

function updateStatistics(stats) {
    document.getElementById('totalTransactions').textContent = stats.totalTransactions || 0;
    document.getElementById('totalRevenue').textContent = (stats.totalRevenue || 0).toFixed(2) + ' EUR';
    document.getElementById('totalCommissions').textContent = (stats.totalCommissions || 0).toFixed(2) + ' EUR';
    document.getElementById('successRate').textContent = (stats.successRate || 0).toFixed(1) + '%';
}

function updateCharts(chartData) {
    // Graphique des transactions dans le temps
    if (transactionsChart) transactionsChart.destroy();
    const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
    transactionsChart = new Chart(transactionsCtx, {
        type: 'line',
        data: {
            labels: chartData.transactionsOverTime.labels,
            datasets: [{
                label: 'Transactions',
                data: chartData.transactionsOverTime.data,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Graphique des types de transactions
    if (transactionTypesChart) transactionTypesChart.destroy();
    const typesCtx = document.getElementById('transactionTypesChart').getContext('2d');
    transactionTypesChart = new Chart(typesCtx, {
        type: 'doughnut',
        data: {
            labels: chartData.transactionTypes.labels,
            datasets: [{
                data: chartData.transactionTypes.data,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Graphique des points de charge
    if (chargingPointsChart) chargingPointsChart.destroy();
    const chargingPointsCtx = document.getElementById('chargingPointsChart').getContext('2d');
    chargingPointsChart = new Chart(chargingPointsCtx, {
        type: 'bar',
        data: {
            labels: chartData.topChargingPoints.labels,
            datasets: [{
                label: 'Transactions',
                data: chartData.topChargingPoints.data,
                backgroundColor: 'rgba(54, 162, 235, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Graphique des revenus
    if (revenueChart) revenueChart.destroy();
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: chartData.revenueOverTime.labels,
            datasets: [{
                label: 'Revenus (EUR)',
                data: chartData.revenueOverTime.data,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function updateDetailedStats(detailedStats) {
    const tbody = document.getElementById('detailedStats');
    tbody.innerHTML = '';

    detailedStats.forEach(stat => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${stat.metric}</td>
            <td>${stat.value}</td>
            <td>${stat.evolution}</td>
            <td>
                <span class="badge badge-${stat.trend === 'up' ? 'success' : stat.trend === 'down' ? 'danger' : 'secondary'}">
                    <i class="fas fa-arrow-${stat.trend === 'up' ? 'up' : stat.trend === 'down' ? 'down' : 'right'}"></i>
                    ${stat.trend === 'up' ? 'Hausse' : stat.trend === 'down' ? 'Baisse' : 'Stable'}
                </span>
            </td>
        `;
        tbody.appendChild(row);
    });
}
</script>
@endsection
