@extends('layouts.app')

@section('title', 'Analytics des Paiements')

@push('styles')
    {{-- Chart.js CSS pas necessaire --}}
    <style>
        .analytics-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3b82f6;
        }
        
        .stat-card.success {
            border-left-color: #10b981;
        }
        
        .stat-card.warning {
            border-left-color: #f59e0b;
        }
        
        .stat-card.danger {
            border-left-color: #ef4444;
        }
        
        .stat-title {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .stat-change {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .stat-change.positive {
            color: #10b981;
        }
        
        .stat-change.negative {
            color: #ef4444;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
        }
        
        .refresh-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .refresh-btn:hover {
            background: #2563eb;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .error {
            background: #fef2f2;
            color: #dc2626;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        
        .method-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .method-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .method-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .method-count {
            font-size: 24px;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 4px;
        }
        
        .method-revenue {
            font-size: 14px;
            color: #6b7280;
        }
    </style>
@endpush

@section('content')
<div class="analytics-container">
    <h1 style="margin-bottom: 30px; color: #1f2937;">
        <i class="fas fa-chart-line" style="margin-right: 10px; color: #3b82f6;"></i>
        Analytics des Paiements
    </h1>
    
    <!-- Filtres -->
    <div class="filters">
        <label for="period">Période :</label>
        <select id="period" class="filter-select">
            <option value="7d">7 derniers jours</option>
            <option value="30d" selected>30 derniers jours</option>
            <option value="90d">90 derniers jours</option>
            <option value="1y">1 an</option>
        </select>
        
        <button id="refresh-btn" class="refresh-btn">
            <i class="fas fa-sync-alt"></i> Actualiser
        </button>
    </div>
    
    <!-- Statistiques principales -->
    <div class="stats-grid" id="stats-grid">
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i> Chargement des statistiques...
        </div>
    </div>
    
    <!-- Graphiques -->
    <div class="chart-container">
        <h3 class="chart-title">Évolution des Paiements</h3>
        <canvas id="daily-chart" width="400" height="200"></canvas>
    </div>
    
    <div class="chart-container">
        <h3 class="chart-title">Répartition par Méthode de Paiement</h3>
        <canvas id="method-chart" width="400" height="200"></canvas>
    </div>
    
    <!-- Statistiques par méthode -->
    <div class="chart-container">
        <h3 class="chart-title">Détails par Méthode de Paiement</h3>
        <div id="method-stats" class="method-stats">
            <div class="loading">Chargement...</div>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/chart.min.js') }}"></script>
<script>
class PaymentAnalytics {
    constructor() {
        this.period = '30d';
        this.dailyChart = null;
        this.methodChart = null;
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadData();
    }
    
    bindEvents() {
        document.getElementById('period').addEventListener('change', (e) => {
            this.period = e.target.value;
            this.loadData();
        });
        
        document.getElementById('refresh-btn').addEventListener('click', () => {
            this.loadData();
        });
    }
    
    async loadData() {
        try {
            this.showLoading();
            
            const [statsResponse, methodResponse, conversionResponse] = await Promise.all([
                fetch(`/api/payment-analytics/stats?period=${this.period}`),
                fetch(`/api/payment-analytics/methods?period=${this.period}`),
                fetch(`/api/payment-analytics/conversion?period=${this.period}`)
            ]);
            
            const stats = await statsResponse.json();
            const methods = await methodResponse.json();
            const conversion = await conversionResponse.json();
            
            if (stats.success) {
                this.renderStats(stats.data);
                this.renderDailyChart(stats.data.daily_stats);
            }
            
            if (methods.success) {
                this.renderMethodChart(methods.data);
                this.renderMethodStats(methods.data);
            }
            
            if (conversion.success) {
                this.renderConversionStats(conversion.data);
            }
            
        } catch (error) {
            console.error('Error loading analytics:', error);
            this.showError('Erreur lors du chargement des données');
        }
    }
    
    showLoading() {
        document.getElementById('stats-grid').innerHTML = `
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i> Chargement des statistiques...
            </div>
        `;
    }
    
    showError(message) {
        document.getElementById('stats-grid').innerHTML = `
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> ${message}
            </div>
        `;
    }
    
    renderStats(data) {
        const statsHtml = `
            <div class="stat-card">
                <div class="stat-title">Total Transactions</div>
                <div class="stat-value">${data.total_transactions.toLocaleString()}</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> +12%
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-title">Paiements Réussis</div>
                <div class="stat-value">${data.successful_payments.toLocaleString()}</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> +8%
                </div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-title">Paiements Échoués</div>
                <div class="stat-value">${data.failed_payments.toLocaleString()}</div>
                <div class="stat-change negative">
                    <i class="fas fa-arrow-down"></i> -3%
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-title">Revenus Totaux</div>
                <div class="stat-value">${data.total_revenue.toLocaleString()} EUR</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> +15%
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">Taux de Conversion</div>
                <div class="stat-value">${data.conversion_rate.toFixed(1)}%</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> +2%
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">Valeur Moyenne</div>
                <div class="stat-value">${data.average_transaction_value.toFixed(2)} EUR</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> +5%
                </div>
            </div>
        `;
        
        document.getElementById('stats-grid').innerHTML = statsHtml;
    }
    
    renderDailyChart(dailyStats) {
        const ctx = document.getElementById('daily-chart').getContext('2d');
        
        if (this.dailyChart) {
            this.dailyChart.destroy();
        }
        
        const labels = dailyStats.map(stat => stat.date);
        const transactions = dailyStats.map(stat => stat.transactions);
        const revenue = dailyStats.map(stat => stat.revenue);
        
        this.dailyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Transactions',
                    data: transactions,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Revenus (EUR)',
                    data: revenue,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    }
    
    renderMethodChart(methodStats) {
        const ctx = document.getElementById('method-chart').getContext('2d');
        
        if (this.methodChart) {
            this.methodChart.destroy();
        }
        
        const labels = methodStats.map(stat => stat.payment_method.toUpperCase());
        const data = methodStats.map(stat => stat.count);
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'];
        
        this.methodChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    renderMethodStats(methodStats) {
        const statsHtml = methodStats.map(stat => `
            <div class="method-card">
                <div class="method-name">${stat.payment_method.toUpperCase()}</div>
                <div class="method-count">${stat.count}</div>
                <div class="method-revenue">${stat.total_amount.toLocaleString()} EUR</div>
            </div>
        `).join('');
        
        document.getElementById('method-stats').innerHTML = statsHtml;
    }
    
    renderConversionStats(conversionData) {
        // Ajouter les statistiques de conversion si nécessaire
        console.log('Conversion stats:', conversionData);
    }
}

// Initialiser l'analytics au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    new PaymentAnalytics();
});
</script>
@endsection
