@extends('layouts.admin')

@section('title', 'Analyses Avancées - Paiements')

@push('styles')
<style>
    .analytics-container {
        padding: 2rem;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
    }

    .analytics-header {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        border-left: 6px solid #8b5cf6;
    }

    .filters-section {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .filter-input {
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: border-color 0.3s ease;
    }

    .filter-input:focus {
        outline: none;
        border-color: #8b5cf6;
    }

    .charts-section {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .chart-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }

    .chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
    }

    .chart-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
    }

    .chart-controls {
        display: flex;
        gap: 0.5rem;
    }

    .control-btn {
        padding: 0.5rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        background: white;
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .control-btn.active {
        background: #8b5cf6;
        color: white;
        border-color: #8b5cf6;
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .metric-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        border-left: 4px solid #8b5cf6;
    }

    .metric-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .metric-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }

    .metric-value {
        font-size: 2rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .metric-label {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .metric-change {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.8rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }

    .metric-change.positive {
        color: #10b981;
    }

    .metric-change.negative {
        color: #ef4444;
    }

    .comparison-section {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }

    .comparison-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .comparison-card {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .comparison-card:hover {
        border-color: #8b5cf6;
        transform: translateY(-2px);
    }

    .comparison-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1rem;
    }

    .comparison-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .comparison-item:last-child {
        border-bottom: none;
    }

    .comparison-label {
        color: #64748b;
        font-weight: 600;
    }

    .comparison-value {
        font-weight: 700;
        color: #1e293b;
    }

    .export-section {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }

    .export-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
    }

    .export-card {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .export-card:hover {
        border-color: #8b5cf6;
        transform: translateY(-2px);
    }

    .export-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        color: white;
        font-size: 1.5rem;
    }

    .export-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .export-description {
        color: #64748b;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .charts-section {
            grid-template-columns: 1fr;
        }
        
        .filters-grid {
            grid-template-columns: 1fr;
        }
        
        .metrics-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="analytics-container">
    <!-- Header -->
    <div class="analytics-header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Analyses Avancées</h1>
                <p class="text-gray-600 mt-2">Rapports détaillés et analyses des paiements</p>
            </div>
            <div class="flex gap-3">
                <button onclick="generateReport()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-chart-line mr-2"></i>
                    Générer Rapport
                </button>
                <button onclick="exportAllData()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>
                    Exporter Tout
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <h3 class="text-xl font-bold mb-4">Filtres d'Analyse</h3>
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Période</label>
                <select id="period" class="filter-input">
                    <option value="7d">7 derniers jours</option>
                    <option value="30d" selected>30 derniers jours</option>
                    <option value="90d">90 derniers jours</option>
                    <option value="1y">1 an</option>
                    <option value="custom">Période personnalisée</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Méthode de Paiement</label>
                <select id="paymentMethod" class="filter-input">
                    <option value="">Toutes les méthodes</option>
                    <option value="cmi">CMI</option>
                    <option value="stripe">Stripe</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Statut</label>
                <select id="status" class="filter-input">
                    <option value="">Tous les statuts</option>
                    <option value="completed">Terminé</option>
                    <option value="failed">Échec</option>
                    <option value="pending">En attente</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Montant Minimum</label>
                <input type="number" id="minAmount" class="filter-input" placeholder="0.00">
            </div>

            <div class="filter-group">
                <label class="filter-label">Montant Maximum</label>
                <input type="number" id="maxAmount" class="filter-input" placeholder="1000.00">
            </div>

            <div class="filter-group">
                <button onclick="applyFilters()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>
                    Appliquer
                </button>
            </div>
        </div>
    </div>

    <!-- Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="metric-value" id="conversion-rate">94.2%</div>
            <div class="metric-label">Taux de Conversion</div>
            <div class="metric-change positive">
                <i class="fas fa-arrow-up"></i>
                +2.3% vs période précédente
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="metric-value" id="avg-processing-time">2.3s</div>
            <div class="metric-label">Temps de Traitement Moyen</div>
            <div class="metric-change positive">
                <i class="fas fa-arrow-down"></i>
                -0.5s vs période précédente
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
            </div>
            <div class="metric-value" id="avg-transaction">€45.67</div>
            <div class="metric-label">Transaction Moyenne</div>
            <div class="metric-change positive">
                <i class="fas fa-arrow-up"></i>
                +€3.21 vs période précédente
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="metric-value" id="failure-rate">2.1%</div>
            <div class="metric-label">Taux d'Échec</div>
            <div class="metric-change negative">
                <i class="fas fa-arrow-up"></i>
                +0.3% vs période précédente
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-section">
        <!-- Revenue Trends -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">Évolution des Revenus</h3>
                <div class="chart-controls">
                    <button class="control-btn active" data-chart="revenue">Revenus</button>
                    <button class="control-btn" data-chart="volume">Volume</button>
                    <button class="control-btn" data-chart="transactions">Transactions</button>
                </div>
            </div>
            <canvas id="revenueChart" width="400" height="200"></canvas>
        </div>

        <!-- Payment Methods Performance -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">Performance par Méthode</h3>
            </div>
            <canvas id="methodsChart" width="300" height="200"></canvas>
        </div>
    </div>

    <!-- Comparison Section -->
    <div class="comparison-section">
        <h3 class="text-xl font-bold mb-4">Comparaisons</h3>
        <div class="comparison-grid">
            <div class="comparison-card">
                <h4 class="comparison-title">CMI vs Stripe</h4>
                <div class="comparison-item">
                    <span class="comparison-label">Volume CMI</span>
                    <span class="comparison-value">€12,450</span>
                </div>
                <div class="comparison-item">
                    <span class="comparison-label">Volume Stripe</span>
                    <span class="comparison-value">€8,230</span>
                </div>
                <div class="comparison-item">
                    <span class="comparison-label">Taux de Réussite CMI</span>
                    <span class="comparison-value">96.2%</span>
                </div>
                <div class="comparison-item">
                    <span class="comparison-label">Taux de Réussite Stripe</span>
                    <span class="comparison-value">92.8%</span>
                </div>
            </div>

            <div class="comparison-card">
                <h4 class="comparison-title">Période Actuelle vs Précédente</h4>
                <div class="comparison-item">
                    <span class="comparison-label">Revenus Actuels</span>
                    <span class="comparison-value">€20,680</span>
                </div>
                <div class="comparison-item">
                    <span class="comparison-label">Revenus Précédents</span>
                    <span class="comparison-value">€18,450</span>
                </div>
                <div class="comparison-item">
                    <span class="comparison-label">Croissance</span>
                    <span class="comparison-value" style="color: #10b981;">+12.1%</span>
                </div>
                <div class="comparison-item">
                    <span class="comparison-label">Transactions</span>
                    <span class="comparison-value">453 vs 398</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Section -->
    <div class="export-section">
        <h3 class="text-xl font-bold mb-4">Export des Données</h3>
        <div class="export-grid">
            <div class="export-card" onclick="exportData('revenue')">
                <div class="export-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="export-title">Rapport de Revenus</div>
                <div class="export-description">Export des revenus par période</div>
            </div>

            <div class="export-card" onclick="exportData('transactions')">
                <div class="export-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="export-title">Liste des Transactions</div>
                <div class="export-description">Export détaillé des transactions</div>
            </div>

            <div class="export-card" onclick="exportData('methods')">
                <div class="export-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="export-title">Performance des Méthodes</div>
                <div class="export-description">Analyse des méthodes de paiement</div>
            </div>

            <div class="export-card" onclick="exportData('failures')">
                <div class="export-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="export-title">Rapport d'Échecs</div>
                <div class="export-description">Analyse des échecs de paiement</div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
let revenueChart, methodsChart;

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    setupEventListeners();
});

function initializeCharts() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
            datasets: [{
                label: 'Revenus (€)',
                data: [12000, 15000, 18000, 16000, 20000, 22000],
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '€' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Methods Chart
    const methodsCtx = document.getElementById('methodsChart').getContext('2d');
    methodsChart = new Chart(methodsCtx, {
        type: 'bar',
        data: {
            labels: ['CMI', 'Stripe', 'PayPal'],
            datasets: [{
                label: 'Volume (€)',
                data: [12450, 8230, 2100],
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '€' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function setupEventListeners() {
    // Chart control buttons
    document.querySelectorAll('.control-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.control-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            updateChart(this.dataset.chart);
        });
    });
}

function updateChart(chartType) {
    // Update chart based on selected type
    console.log('Updating chart for type:', chartType);
}

function applyFilters() {
    const filters = {
        period: document.getElementById('period').value,
        paymentMethod: document.getElementById('paymentMethod').value,
        status: document.getElementById('status').value,
        minAmount: document.getElementById('minAmount').value,
        maxAmount: document.getElementById('maxAmount').value
    };
    
    console.log('Applying filters:', filters);
    // Implement filter logic
}

function generateReport() {
    alert('Génération du rapport en cours...');
    // Implement report generation
}

function exportAllData() {
    alert('Export de toutes les données en cours...');
    // Implement full data export
}

function exportData(type) {
    alert(`Export des données ${type} en cours...`);
    // Implement specific data export
}
</script>
@endsection
