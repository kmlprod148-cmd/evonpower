<div class="transaction-dashboard">
    <!-- En-tête avec sélecteur de période -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Dashboard des Transactions
                    </h5>
                    <div class="btn-group">
                        <button wire:click="updatePeriod('today')" class="btn btn-sm {{ $period === 'today' ? 'btn-primary' : 'btn-outline-primary' }}">
                            Aujourd'hui
                        </button>
                        <button wire:click="updatePeriod('week')" class="btn btn-sm {{ $period === 'week' ? 'btn-primary' : 'btn-outline-primary' }}">
                            Cette semaine
                        </button>
                        <button wire:click="updatePeriod('month')" class="btn btn-sm {{ $period === 'month' ? 'btn-primary' : 'btn-outline-primary' }}">
                            Ce mois
                        </button>
                        <button wire:click="updatePeriod('quarter')" class="btn btn-sm {{ $period === 'quarter' ? 'btn-primary' : 'btn-outline-primary' }}">
                            Ce trimestre
                        </button>
                        <button wire:click="updatePeriod('year')" class="btn btn-sm {{ $period === 'year' ? 'btn-primary' : 'btn-outline-primary' }}">
                            Cette année
                        </button>
                        <button wire:click="updatePeriod('all')" class="btn btn-sm {{ $period === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                            Tout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques générales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-primary text-white">
                <div class="stat-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Total Transactions</h6>
                    <h4 class="stat-value">{{ number_format($dashboardData['general_stats']['total_transactions'] ?? 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-success text-white">
                <div class="stat-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Montant Total</h6>
                    <h4 class="stat-value">{{ number_format($dashboardData['general_stats']['total_amount'] ?? 0, 2) }} €</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-warning text-white">
                <div class="stat-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Frais Totaux</h6>
                    <h4 class="stat-value">{{ number_format($dashboardData['general_stats']['total_fees'] ?? 0, 2) }} €</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-info text-white">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Moyenne</h6>
                    <h4 class="stat-value">{{ number_format($dashboardData['general_stats']['average_transaction_amount'] ?? 0, 2) }} €</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques par statut -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-success text-white">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Terminées</h6>
                    <h4 class="stat-value">{{ number_format($dashboardData['general_stats']['completed_transactions'] ?? 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-warning text-white">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">En Attente</h6>
                    <h4 class="stat-value">{{ number_format($dashboardData['general_stats']['pending_transactions'] ?? 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-danger text-white">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Annulées</h6>
                    <h4 class="stat-value">{{ number_format($dashboardData['general_stats']['canceled_transactions'] ?? 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-secondary text-white">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Échouées</h6>
                    <h4 class="stat-value">{{ number_format($dashboardData['general_stats']['failed_transactions'] ?? 0) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et analyses -->
    <div class="row mb-4">
        <!-- Répartition par type -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Répartition par Type
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="typeChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Répartition par statut -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-doughnut me-2"></i>
                        Répartition par Statut
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tendances quotidiennes -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Tendances Quotidiennes
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="trendsChart" width="800" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques spécifiques au rôle -->
    @if(!empty($dashboardData['role_specific_stats']))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-user-tag me-2"></i>
                        Statistiques Spécifiques au Rôle
                    </h6>
                </div>
                <div class="card-body">
                    @if($user->role === 'admin')
                        <div class="row">
                            <div class="col-md-4">
                                <div class="stat-card bg-primary text-white">
                                    <div class="stat-icon">
                                        <i class="fas fa-euro-sign"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h6 class="stat-title">Revenus Totaux</h6>
                                        <h4 class="stat-value">{{ number_format($dashboardData['role_specific_stats']['total_revenue'] ?? 0, 2) }} €</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card bg-warning text-white">
                                    <div class="stat-icon">
                                        <i class="fas fa-percentage"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h6 class="stat-title">Frais Collectés</h6>
                                        <h4 class="stat-value">{{ number_format($dashboardData['role_specific_stats']['total_fees_collected'] ?? 0, 2) }} €</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card bg-danger text-white">
                                    <div class="stat-icon">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h6 class="stat-title">Frais Admin</h6>
                                        <h4 class="stat-value">{{ number_format($dashboardData['role_specific_stats']['admin_fees'] ?? 0, 2) }} €</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top intégrateurs -->
                        @if(isset($dashboardData['role_specific_stats']['top_integrators']) && $dashboardData['role_specific_stats']['top_integrators']->count() > 0)
                        <div class="mt-4">
                            <h6>Top Intégrateurs</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Intégrateur</th>
                                            <th>Transactions</th>
                                            <th>Montant Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($dashboardData['role_specific_stats']['top_integrators'] as $integrator)
                                            <tr>
                                                <td>{{ $integrator->targetUser->name ?? 'N/A' }}</td>
                                                <td>{{ $integrator->transaction_count }}</td>
                                                <td>{{ number_format($integrator->total_amount, 2) }} €</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                    @elseif($user->role === 'integrator')
                        <div class="row">
                            <div class="col-md-4">
                                <div class="stat-card bg-success text-white">
                                    <div class="stat-icon">
                                        <i class="fas fa-euro-sign"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h6 class="stat-title">Revenus Totaux</h6>
                                        <h4 class="stat-value">{{ number_format($dashboardData['role_specific_stats']['total_revenue'] ?? 0, 2) }} €</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card bg-warning text-white">
                                    <div class="stat-icon">
                                        <i class="fas fa-percentage"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h6 class="stat-title">Frais Collectés</h6>
                                        <h4 class="stat-value">{{ number_format($dashboardData['role_specific_stats']['total_fees_collected'] ?? 0, 2) }} €</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card bg-info text-white">
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h6 class="stat-title">Frais Operator</h6>
                                        <h4 class="stat-value">{{ number_format($dashboardData['role_specific_stats']['operator_fees'] ?? 0, 2) }} €</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top opérateurs -->
                        @if(isset($dashboardData['role_specific_stats']['top_operators']) && $dashboardData['role_specific_stats']['top_operators']->count() > 0)
                        <div class="mt-4">
                            <h6>Top Opérateurs</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Opérateur</th>
                                            <th>Transactions</th>
                                            <th>Montant Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($dashboardData['role_specific_stats']['top_operators'] as $operator)
                                            <tr>
                                                <td>{{ $operator->targetUser->name ?? 'N/A' }}</td>
                                                <td>{{ $operator->transaction_count }}</td>
                                                <td>{{ number_format($operator->total_amount, 2) }} €</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                    @elseif($user->role === 'operator')
                        <div class="row">
                            <div class="col-md-4">
                                <div class="stat-card bg-success text-white">
                                    <div class="stat-icon">
                                        <i class="fas fa-euro-sign"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h6 class="stat-title">Revenus Totaux</h6>
                                        <h4 class="stat-value">{{ number_format($dashboardData['role_specific_stats']['total_revenue'] ?? 0, 2) }} €</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card bg-danger text-white">
                                    <div class="stat-icon">
                                        <i class="fas fa-percentage"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h6 class="stat-title">Frais Payés</h6>
                                        <h4 class="stat-value">{{ number_format($dashboardData['role_specific_stats']['total_fees_paid'] ?? 0, 2) }} €</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card bg-info text-white">
                                    <div class="stat-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h6 class="stat-title">Moyenne Transaction</h6>
                                        <h4 class="stat-value">{{ number_format($dashboardData['role_specific_stats']['average_transaction_amount'] ?? 0, 2) }} €</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Type de transaction le plus courant -->
                        @if(isset($dashboardData['role_specific_stats']['most_common_transaction_type']))
                        <div class="mt-4">
                            <h6>Type de Transaction le Plus Courant</h6>
                            <div class="alert alert-info">
                                <strong>{{ $this->getTransactionTypeLabel($dashboardData['role_specific_stats']['most_common_transaction_type']->transaction_type) }}</strong>
                                avec {{ $dashboardData['role_specific_stats']['most_common_transaction_type']->count }} transactions
                            </div>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
.stat-card {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.5rem;
}

.stat-content {
    flex: 1;
}

.stat-title {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 600;
    margin: 0;
}

.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: none;
}

.btn-group .btn {
    border-radius: 0;
}

.btn-group .btn:first-child {
    border-top-left-radius: 0.375rem;
    border-bottom-left-radius: 0.375rem;
}

.btn-group .btn:last-child {
    border-top-right-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
}
</style>

<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
document.addEventListener('livewire:init', () => {
    // Initialiser les graphiques
    initCharts();
    
    // Réinitialiser les graphiques quand les données changent
    Livewire.on('periodChanged', () => {
        setTimeout(() => {
            initCharts();
        }, 100);
    });
});

function initCharts() {
    // Données pour les graphiques
    const typeData = @json($chartData['type_distribution'] ?? []);
    const statusData = @json($chartData['status_distribution'] ?? []);
    const trendsData = @json($chartData['daily_trends'] ?? []);

    // Graphique en secteurs - Types
    const typeCtx = document.getElementById('typeChart');
    if (typeCtx) {
        new Chart(typeCtx, {
            type: 'pie',
            data: {
                labels: typeData.map(item => item.transaction_type),
                datasets: [{
                    data: typeData.map(item => item.count),
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ]
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

    // Graphique en secteurs - Statuts
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(item => item.status),
                datasets: [{
                    data: statusData.map(item => item.count),
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#6c757d'
                    ]
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

    // Graphique linéaire - Tendances
    const trendsCtx = document.getElementById('trendsChart');
    if (trendsCtx) {
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: trendsData.map(item => item.date),
                datasets: [{
                    label: 'Nombre de Transactions',
                    data: trendsData.map(item => item.count),
                    borderColor: '#36A2EB',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Montant Total (€)',
                    data: trendsData.map(item => item.total_amount),
                    borderColor: '#FF6384',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.1,
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
}
</script>
