@extends('layouts.dashboard')

@section('title', 'Statistiques Globales des Balances')

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Statistiques Globales des Balances</h1>
                    <p class="text-muted">Vue d'ensemble complète du système hiérarchique</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('dashboard.balances.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour au Dashboard
                    </a>
                    <button class="btn btn-outline-primary" onclick="exportStatistics()">
                        <i class="fas fa-download"></i> Exporter
                    </button>
                    <button class="btn btn-outline-success" onclick="location.reload()">
                        <i class="fas fa-refresh"></i> Actualiser
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(isset($error))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> {{ $error }}
        </div>
    @endif

    @if($statistics)
        <!-- Vue d'ensemble -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0">{{ $statistics['total_users'] }}</h4>
                                <p class="mb-0">Total Utilisateurs</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0">{{ $statistics['total_transactions'] }}</h4>
                                <p class="mb-0">Total Transactions</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-exchange-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0">{{ number_format($statistics['total_revenue'], 2) }} EUR</h4>
                                <p class="mb-0">Total Revenus</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-coins fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0">{{ $statistics['performance_metrics']['active_users'] ?? 0 }}</h4>
                                <p class="mb-0">Utilisateurs Actifs</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-user-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Répartition par rôle -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-sitemap"></i> Répartition par Rôle
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Rôle</th>
                                        <th>Utilisateurs</th>
                                        <th>Transactions</th>
                                        <th>Montant Total</th>
                                        <th>Moyenne par Utilisateur</th>
                                        <th>Moyenne par Transaction</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statistics['hierarchy_breakdown'] as $role)
                                    <tr>
                                        <td>
                                            <span class="badge badge-{{ $role['role'] === 'admin' ? 'danger' : ($role['role'] === 'integrator' ? 'warning' : 'info') }}">
                                                {{ ucfirst($role['role']) }}
                                            </span>
                                        </td>
                                        <td><strong>{{ $role['user_count'] }}</strong></td>
                                        <td>{{ number_format($role['transaction_count']) }}</td>
                                        <td>{{ number_format($role['total_amount'], 2) }} EUR</td>
                                        <td>{{ number_format($role['user_count'] > 0 ? $role['total_amount'] / $role['user_count'] : 0, 2) }} EUR</td>
                                        <td>{{ number_format($role['transaction_count'] > 0 ? $role['total_amount'] / $role['transaction_count'] : 0, 2) }} EUR</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Métriques de performance -->
        @if(isset($statistics['performance_metrics']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line"></i> Métriques de Performance
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h4 class="text-primary">{{ number_format($statistics['performance_metrics']['total_transactions']) }}</h4>
                                    <small class="text-muted">Total Transactions</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h4 class="text-success">{{ number_format($statistics['performance_metrics']['total_amount'], 2) }} EUR</h4>
                                    <small class="text-muted">Montant Total</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h4 class="text-info">{{ number_format($statistics['performance_metrics']['average_amount'], 2) }} EUR</h4>
                                    <small class="text-muted">Moyenne</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h4 class="text-warning">{{ number_format($statistics['performance_metrics']['min_amount'], 2) }} EUR</h4>
                                    <small class="text-muted">Minimum</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h4 class="text-danger">{{ number_format($statistics['performance_metrics']['max_amount'], 2) }} EUR</h4>
                                    <small class="text-muted">Maximum</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h4 class="text-secondary">{{ $statistics['performance_metrics']['active_days'] }}</h4>
                                    <small class="text-muted">Jours Actifs</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Graphiques -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie"></i> Répartition des Utilisateurs
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="usersChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar"></i> Répartition des Revenus
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activité récente -->
        @if(isset($statistics['recent_activity']) && count($statistics['recent_activity']) > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock"></i> Activité Récente (7 derniers jours)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach($statistics['recent_activity'] as $activity)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Transaction #{{ $activity['id'] }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $activity['user_name'] }} ({{ $activity['user_email'] }})</small>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-success">{{ number_format($activity['amount'], 2) }} EUR</span>
                                    <br>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Informations de calcul -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle"></i> Informations de Calcul
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Calculé le:</strong> {{ \Carbon\Carbon::parse($statistics['calculated_at'])->format('d/m/Y H:i:s') }}</p>
                                <p><strong>Période:</strong> Toutes les données disponibles</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Statut:</strong> <span class="badge badge-success">À jour</span></p>
                                <p><strong>Prochaine mise à jour:</strong> {{ \Carbon\Carbon::now()->addMinutes(15)->format('H:i:s') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @else
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h4>Erreur de chargement</h4>
                        <p class="text-muted">Impossible de charger les statistiques globales.</p>
                        <button class="btn btn-primary" onclick="location.reload()">
                            <i class="fas fa-refresh"></i> Réessayer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
    // Graphique des utilisateurs
    const usersCtx = document.getElementById('usersChart').getContext('2d');
    const usersData = @json($statistics['hierarchy_breakdown'] ?? []);
    
    new Chart(usersCtx, {
        type: 'pie',
        data: {
            labels: usersData.map(item => item.role.charAt(0).toUpperCase() + item.role.slice(1)),
            datasets: [{
                data: usersData.map(item => item.user_count),
                backgroundColor: [
                    '#dc3545', // Admin - Rouge
                    '#ffc107', // Integrator - Jaune
                    '#17a2b8'  // Operator - Bleu
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Graphique des revenus
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueData = @json($statistics['hierarchy_breakdown'] ?? []);
    
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: revenueData.map(item => item.role.charAt(0).toUpperCase() + item.role.slice(1)),
            datasets: [{
                label: 'Montant (EUR)',
                data: revenueData.map(item => item.total_amount),
                backgroundColor: [
                    '#28a745', // Vert
                    '#17a2b8', // Bleu
                    '#ffc107'  // Jaune
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' EUR';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Fonction d'export
    function exportStatistics() {
        const data = @json($statistics);
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'statistics_' + new Date().toISOString().slice(0, 10) + '.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    // Auto-refresh toutes les 15 minutes
    setInterval(function() {
        location.reload();
    }, 900000);
</script>
@endpush
@endsection
