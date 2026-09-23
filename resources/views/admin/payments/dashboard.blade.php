@extends('layouts.admin')

@section('title', 'Dashboard Paiements')

@push('styles')
<style>
    .admin-dashboard {
        padding: 2rem;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
    }

    .dashboard-header {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        border-left: 6px solid #3b82f6;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    }

    .stat-card.success::before {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .stat-card.warning::before {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .stat-card.danger::before {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .stat-card.info::before {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    }

    .stat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }

    .stat-icon.primary {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    }

    .stat-icon.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .stat-icon.warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .stat-icon.danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .stat-icon.info {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 0.5rem;
        line-height: 1;
    }

    .stat-label {
        color: #64748b;
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .stat-change {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .stat-change.positive {
        color: #10b981;
    }

    .stat-change.negative {
        color: #ef4444;
    }

    .stat-change.neutral {
        color: #64748b;
    }

    .charts-grid {
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

    .chart-period {
        display: flex;
        gap: 0.5rem;
    }

    .period-btn {
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

    .period-btn.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .recent-activity {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }

    .activity-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
    }

    .activity-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
    }

    .activity-list {
        space-y: 1rem;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border-radius: 12px;
        background: #f8fafc;
        transition: all 0.3s ease;
    }

    .activity-item:hover {
        background: #f1f5f9;
        transform: translateX(4px);
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
    }

    .activity-content {
        flex: 1;
    }

    .activity-message {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .activity-time {
        font-size: 0.9rem;
        color: #64748b;
    }

    .activity-amount {
        font-weight: 700;
        color: #1e293b;
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .action-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid transparent;
    }

    .action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        border-color: #3b82f6;
    }

    .action-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.5rem;
        color: white;
    }

    .action-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .action-description {
        color: #64748b;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .quick-actions {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="admin-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Dashboard Paiements</h1>
                <p class="text-gray-600 mt-2">Vue d'ensemble des paiements et statistiques en temps réel</p>
            </div>
            <div class="flex gap-3">
                <button onclick="refreshDashboard()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Actualiser
                </button>
                <button onclick="exportReport()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>
                    Exporter
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon primary">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +12.5%
                </div>
            </div>
            <div class="stat-value" id="total-payments">{{ $stats['total_payments'] ?? 0 }}</div>
            <div class="stat-label">Total Paiements</div>
        </div>

        <div class="stat-card success">
            <div class="stat-header">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +8.3%
                </div>
            </div>
            <div class="stat-value" id="completed-payments">{{ $stats['completed_payments'] ?? 0 }}</div>
            <div class="stat-label">Paiements Réussis</div>
        </div>

        <div class="stat-card warning">
            <div class="stat-header">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-change neutral">
                    <i class="fas fa-minus"></i>
                    0%
                </div>
            </div>
            <div class="stat-value" id="pending-payments">{{ $stats['pending_payments'] ?? 0 }}</div>
            <div class="stat-label">En Attente</div>
        </div>

        <div class="stat-card danger">
            <div class="stat-header">
                <div class="stat-icon danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-change negative">
                    <i class="fas fa-arrow-down"></i>
                    -2.1%
                </div>
            </div>
            <div class="stat-value" id="failed-payments">{{ $stats['failed_payments'] ?? 0 }}</div>
            <div class="stat-label">Échecs</div>
        </div>

        <div class="stat-card info">
            <div class="stat-header">
                <div class="stat-icon info">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +15.7%
                </div>
            </div>
            <div class="stat-value" id="total-amount">{{ number_format($stats['total_amount'] ?? 0, 2) }} €</div>
            <div class="stat-label">Montant Total</div>
        </div>

        <div class="stat-card success">
            <div class="stat-header">
                <div class="stat-icon success">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +22.4%
                </div>
            </div>
            <div class="stat-value" id="today-amount">{{ number_format($stats['today_amount'] ?? 0, 2) }} €</div>
            <div class="stat-label">Aujourd'hui</div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
        <!-- Payment Trends Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">Tendances des Paiements</h3>
                <div class="chart-period">
                    <button class="period-btn active" data-period="7d">7j</button>
                    <button class="period-btn" data-period="30d">30j</button>
                    <button class="period-btn" data-period="90d">90j</button>
                </div>
            </div>
            <canvas id="paymentTrendsChart" width="400" height="200"></canvas>
        </div>

        <!-- Payment Methods Distribution -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">Méthodes de Paiement</h3>
            </div>
            <canvas id="paymentMethodsChart" width="300" height="200"></canvas>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <div class="activity-header">
            <h3 class="activity-title">Activité Récente</h3>
            <a href="{{ route('admin.payments.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                Voir tout <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="activity-list" id="recent-activity-list">
            <!-- Recent activities will be loaded here -->
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="action-card" onclick="window.location.href='{{ route('admin.payments.index') }}'">
            <div class="action-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="action-title">Gérer les Paiements</div>
            <div class="action-description">Voir et gérer tous les paiements</div>
        </div>

        <div class="action-card" onclick="window.location.href='{{ route('admin.payments.export') }}'">
            <div class="action-icon">
                <i class="fas fa-download"></i>
            </div>
            <div class="action-title">Exporter les Données</div>
            <div class="action-description">Exporter les rapports de paiement</div>
        </div>

        <div class="action-card" onclick="showRefundModal()">
            <div class="action-icon">
                <i class="fas fa-undo"></i>
            </div>
            <div class="action-title">Remboursements</div>
            <div class="action-description">Gérer les remboursements</div>
        </div>

        <div class="action-card" onclick="showAnalyticsModal()">
            <div class="action-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="action-title">Analyses Avancées</div>
            <div class="action-description">Rapports détaillés et analyses</div>
        </div>
    </div>
</div>

<!-- Refund Modal -->
<div id="refundModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4">Remboursement de Paiement</h3>
            <form id="refundForm">
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">ID de Transaction</label>
                    <input type="text" id="refundTransactionId" class="w-full p-3 border rounded-lg" placeholder="Entrez l'ID de transaction">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Raison du Remboursement</label>
                    <textarea id="refundReason" class="w-full p-3 border rounded-lg" rows="3" placeholder="Raison du remboursement"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 text-white py-3 rounded-lg hover:bg-red-700">
                        Rembourser
                    </button>
                    <button type="button" onclick="closeRefundModal()" class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
// Dashboard functionality
let paymentTrendsChart, paymentMethodsChart;

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    loadRecentActivity();
    startAutoRefresh();
});

function initializeCharts() {
    // Payment Trends Chart
    const trendsCtx = document.getElementById('paymentTrendsChart').getContext('2d');
    paymentTrendsChart = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            datasets: [{
                label: 'Paiements',
                data: [12, 19, 3, 5, 2, 3, 8],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
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
                    beginAtZero: true
                }
            }
        }
    });

    // Payment Methods Chart
    const methodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
    paymentMethodsChart = new Chart(methodsCtx, {
        type: 'doughnut',
        data: {
            labels: ['CMI', 'Stripe', 'PayPal'],
            datasets: [{
                data: [45, 35, 20],
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b']
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

function loadRecentActivity() {
    // Simulate loading recent activity
    const activities = [
        {
            icon: 'fas fa-check-circle',
            iconColor: 'bg-green-500',
            message: 'Paiement CMI réussi',
            time: 'Il y a 2 minutes',
            amount: '45.50 €'
        },
        {
            icon: 'fas fa-times-circle',
            iconColor: 'bg-red-500',
            message: 'Paiement Stripe échoué',
            time: 'Il y a 5 minutes',
            amount: '23.00 €'
        },
        {
            icon: 'fas fa-clock',
            iconColor: 'bg-yellow-500',
            message: 'Paiement en attente',
            time: 'Il y a 8 minutes',
            amount: '67.25 €'
        }
    ];

    const container = document.getElementById('recent-activity-list');
    container.innerHTML = activities.map(activity => `
        <div class="activity-item">
            <div class="activity-icon ${activity.iconColor}">
                <i class="${activity.icon}"></i>
            </div>
            <div class="activity-content">
                <div class="activity-message">${activity.message}</div>
                <div class="activity-time">${activity.time}</div>
            </div>
            <div class="activity-amount">${activity.amount}</div>
        </div>
    `).join('');
}

function startAutoRefresh() {
    setInterval(() => {
        refreshStats();
    }, 30000); // Refresh every 30 seconds
}

function refreshStats() {
    fetch('/admin/payments/statistics')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStats(data.statistics);
            }
        })
        .catch(error => console.error('Error refreshing stats:', error));
}

function updateStats(stats) {
    document.getElementById('total-payments').textContent = stats.total_payments;
    document.getElementById('completed-payments').textContent = stats.completed_payments;
    document.getElementById('pending-payments').textContent = stats.pending_payments;
    document.getElementById('failed-payments').textContent = stats.failed_payments;
    document.getElementById('total-amount').textContent = stats.total_amount.toFixed(2) + ' €';
    document.getElementById('today-amount').textContent = stats.today_amount.toFixed(2) + ' €';
}

function refreshDashboard() {
    location.reload();
}

function exportReport() {
    window.open('/admin/payments/export', '_blank');
}

function showRefundModal() {
    document.getElementById('refundModal').classList.remove('hidden');
}

function closeRefundModal() {
    document.getElementById('refundModal').classList.add('hidden');
}

function showAnalyticsModal() {
    alert('Fonctionnalité d\'analyses avancées à venir !');
}

// Period buttons
document.querySelectorAll('.period-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        // Update chart based on period
        updateChartPeriod(this.dataset.period);
    });
});

function updateChartPeriod(period) {
    // Update chart data based on selected period
    console.log('Updating chart for period:', period);
}
</script>
@endsection
