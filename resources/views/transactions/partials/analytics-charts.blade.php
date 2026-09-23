<!-- Graphiques et Analyses Visuelles -->
<div class="row mb-4">
    <!-- Graphique des transactions par jour -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Transactions par Jour
                </h6>
            </div>
            <div class="card-body">
                <canvas id="transactionsByDayChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Graphique des montants par type -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Répartition par Type
                </h6>
            </div>
            <div class="card-body">
                <canvas id="transactionsByTypeChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Graphique des montants par catégorie -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Montants par Catégorie
                </h6>
            </div>
            <div class="card-body">
                <canvas id="amountsByCategoryChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Graphique des statuts -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-doughnut me-2"></i>
                    Répartition par Statut
                </h6>
            </div>
            <div class="card-body">
                <canvas id="transactionsByStatusChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Graphique des tendances mensuelles -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-area me-2"></i>
                    Tendance Mensuelle des Revenus
                </h6>
            </div>
            <div class="card-body">
                <canvas id="monthlyTrendsChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tableau de bord des performances -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-success">
                    <i class="fas fa-arrow-up fa-2x mb-2"></i>
                </div>
                <h4 class="text-success" id="avgTransactionAmount">{{ number_format($statistics['total_amount'] / max($statistics['total_transactions'], 1), 2) }} EUR</h4>
                <p class="text-muted mb-0">Montant Moyen</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-info">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                </div>
                <h4 class="text-info" id="avgTransactionsPerDay">{{ number_format($statistics['total_transactions'] / max(30, 1), 1) }}</h4>
                <p class="text-muted mb-0">Transactions/Jour</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-warning">
                    <i class="fas fa-percentage fa-2x mb-2"></i>
                </div>
                <h4 class="text-warning" id="completionRate">{{ number_format(($statistics['completed_transactions'] / max($statistics['total_transactions'], 1)) * 100, 1) }}%</h4>
                <p class="text-muted mb-0">Taux de Réussite</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-primary">
                    <i class="fas fa-calendar fa-2x mb-2"></i>
                </div>
                <h4 class="text-primary" id="activeDays">{{ $statistics['total_transactions'] > 0 ? '30' : '0' }}</h4>
                <p class="text-muted mb-0">Jours Actifs</p>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données pour les graphiques (à remplacer par des données réelles)
    const chartData = {
        transactionsByDay: {
            labels: @json($chartData['transactionsByDay']['labels'] ?? []),
            datasets: [{
                label: 'Transactions',
                data: @json($chartData['transactionsByDay']['data'] ?? []),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        transactionsByType: {
            labels: @json($chartData['transactionsByType']['labels'] ?? []),
            datasets: [{
                data: @json($chartData['transactionsByType']['data'] ?? []),
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        amountsByCategory: {
            labels: @json($chartData['amountsByCategory']['labels'] ?? []),
            datasets: [{
                label: 'Montants (EUR)',
                data: @json($chartData['amountsByCategory']['data'] ?? []),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        transactionsByStatus: {
            labels: @json($chartData['transactionsByStatus']['labels'] ?? []),
            datasets: [{
                data: @json($chartData['transactionsByStatus']['data'] ?? []),
                backgroundColor: [
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#6c757d'
                ]
            }]
        },
        monthlyTrends: {
            labels: @json($chartData['monthlyTrends']['labels'] ?? []),
            datasets: [{
                label: 'Crédits',
                data: @json($chartData['monthlyTrends']['credits'] ?? []),
                borderColor: 'rgb(40, 167, 69)',
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                fill: true
            }, {
                label: 'Débits',
                data: @json($chartData['monthlyTrends']['debits'] ?? []),
                borderColor: 'rgb(220, 53, 69)',
                backgroundColor: 'rgba(220, 53, 69, 0.2)',
                fill: true
            }]
        }
    };

    // Configuration des graphiques
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    };

    // Créer les graphiques
    new Chart(document.getElementById('transactionsByDayChart'), {
        type: 'line',
        data: chartData.transactionsByDay,
        options: {
            ...chartOptions,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    new Chart(document.getElementById('transactionsByTypeChart'), {
        type: 'doughnut',
        data: chartData.transactionsByType,
        options: chartOptions
    });

    new Chart(document.getElementById('amountsByCategoryChart'), {
        type: 'bar',
        data: chartData.amountsByCategory,
        options: {
            ...chartOptions,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    new Chart(document.getElementById('transactionsByStatusChart'), {
        type: 'pie',
        data: chartData.transactionsByStatus,
        options: chartOptions
    });

    new Chart(document.getElementById('monthlyTrendsChart'), {
        type: 'line',
        data: chartData.monthlyTrends,
        options: {
            ...chartOptions,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
