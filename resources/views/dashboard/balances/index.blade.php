@extends('layouts.dashboard')

@section('title', 'Dashboard des Balances Hiérarchiques')

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Dashboard des Balances Hiérarchiques</h1>
                    <p class="text-muted">Vue d'ensemble des balances {{ ucfirst($userRole) }}</p>
                </div>
                <div class="btn-group">
                    @if($userRole === 'admin')
                        <a href="{{ route('dashboard.balances.statistics') }}" class="btn btn-outline-primary">
                            <i class="fas fa-chart-bar"></i> Statistiques Globales
                        </a>
                        <a href="{{ route('dashboard.balances.recalculate') }}" class="btn btn-outline-warning">
                            <i class="fas fa-sync-alt"></i> Recalculer
                        </a>
                    @endif
                    <button class="btn btn-outline-secondary" onclick="location.reload()">
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

    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($dashboardData)
        <!-- Balance de l'utilisateur -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-wallet"></i> Ma Balance
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3 class="text-primary mb-1">{{ number_format($dashboardData['user_balance']['current_balance'], 2) }} €</h3>
                                    <p class="text-muted mb-0">Balance Actuelle</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3 class="text-success mb-1">{{ number_format($dashboardData['user_balance']['total_earnings'], 2) }} €</h3>
                                    <p class="text-muted mb-0">Total des Gains</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3 class="text-info mb-1">{{ $dashboardData['user_balance']['transaction_count'] }}</h3>
                                    <p class="text-muted mb-0">Transactions</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3 class="text-warning mb-1">
                                        @if($dashboardData['user_balance']['last_transaction_date'])
                                            {{ \Carbon\Carbon::parse($dashboardData['user_balance']['last_transaction_date'])->diffForHumans() }}
                                        @else
                                            Jamais
                                        @endif
                                    </h3>
                                    <p class="text-muted mb-0">Dernière Transaction</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Métriques de performance -->
        @if(isset($dashboardData['performance_metrics']['general']))
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
                                    <h4 class="text-primary">{{ number_format($dashboardData['performance_metrics']['general']['total_transactions']) }}</h4>
                                    <small class="text-muted">Total Transactions</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h4 class="text-success">{{ number_format($dashboardData['performance_metrics']['general']['total_amount'], 2) }} €</h4>
                                    <small class="text-muted">Montant Total</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h4 class="text-info">{{ number_format($dashboardData['performance_metrics']['general']['average_amount'], 2) }} €</h4>
                                    <small class="text-muted">Moyenne</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h4 class="text-warning">{{ number_format($dashboardData['performance_metrics']['general']['min_amount'], 2) }} €</h4>
                                    <small class="text-muted">Minimum</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h4 class="text-danger">{{ number_format($dashboardData['performance_metrics']['general']['max_amount'], 2) }} €</h4>
                                    <small class="text-muted">Maximum</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h4 class="text-secondary">{{ $dashboardData['performance_metrics']['general']['active_days'] }}</h4>
                                    <small class="text-muted">Jours Actifs</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Données hiérarchiques -->
        @if(isset($dashboardData['hierarchy_data']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-sitemap"></i> 
                            @if($dashboardData['hierarchy_data']['type'] === 'admin')
                                Intégrateurs
                            @elseif($dashboardData['hierarchy_data']['type'] === 'integrator')
                                Opérateurs
                            @else
                                Points de Charge
                            @endif
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($dashboardData['hierarchy_data']['type'] === 'admin')
                            @if(count($dashboardData['hierarchy_data']['integrators']) > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Email</th>
                                                <th>Transactions</th>
                                                <th>Commissions</th>
                                                <th>Dernière Activité</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dashboardData['hierarchy_data']['integrators'] as $integrator)
                                            <tr>
                                                <td>{{ $integrator['name'] }}</td>
                                                <td>{{ $integrator['email'] }}</td>
                                                <td><span class="badge badge-primary">{{ $integrator['transaction_count'] }}</span></td>
                                                <td>{{ number_format($integrator['total_commissions'], 2) }} €</td>
                                                <td>
                                                    @if($integrator['last_activity'])
                                                        {{ \Carbon\Carbon::parse($integrator['last_activity'])->diffForHumans() }}
                                                    @else
                                                        Jamais
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center text-muted">
                                    <i class="fas fa-users fa-3x mb-3"></i>
                                    <p>Aucun intégrateur trouvé</p>
                                </div>
                            @endif
                        @elseif($dashboardData['hierarchy_data']['type'] === 'integrator')
                            @if(count($dashboardData['hierarchy_data']['operators']) > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Email</th>
                                                <th>Transactions</th>
                                                <th>Revenus</th>
                                                <th>Dernière Activité</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dashboardData['hierarchy_data']['operators'] as $operator)
                                            <tr>
                                                <td>{{ $operator['name'] }}</td>
                                                <td>{{ $operator['email'] }}</td>
                                                <td><span class="badge badge-primary">{{ $operator['transaction_count'] }}</span></td>
                                                <td>{{ number_format($operator['total_earnings'], 2) }} €</td>
                                                <td>
                                                    @if($operator['last_activity'])
                                                        {{ \Carbon\Carbon::parse($operator['last_activity'])->diffForHumans() }}
                                                    @else
                                                        Jamais
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center text-muted">
                                    <i class="fas fa-users fa-3x mb-3"></i>
                                    <p>Aucun opérateur trouvé</p>
                                </div>
                            @endif
                        @elseif($dashboardData['hierarchy_data']['type'] === 'operator')
                            @if(isset($dashboardData['hierarchy_data']['integrator']))
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <strong>Intégrateur:</strong> {{ $dashboardData['hierarchy_data']['integrator']['name'] }} 
                                        ({{ $dashboardData['hierarchy_data']['integrator']['email'] }})
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            @if(count($dashboardData['hierarchy_data']['charging_points']) > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Statut</th>
                                                <th>Transactions</th>
                                                <th>Revenus</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dashboardData['hierarchy_data']['charging_points'] as $point)
                                            <tr>
                                                <td>{{ $point['name'] }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $point['status'] === 'active' ? 'success' : 'secondary' }}">
                                                        {{ ucfirst($point['status']) }}
                                                    </span>
                                                </td>
                                                <td><span class="badge badge-primary">{{ $point['transaction_count'] }}</span></td>
                                                <td>{{ number_format($point['total_revenue'], 2) }} €</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center text-muted">
                                    <i class="fas fa-charging-station fa-3x mb-3"></i>
                                    <p>Aucun point de charge trouvé</p>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Activité récente -->
        @if(isset($dashboardData['recent_activity']) && count($dashboardData['recent_activity']) > 0)
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
                            @foreach($dashboardData['recent_activity'] as $activity)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Transaction #{{ $activity['id'] }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $activity['user_name'] }} ({{ $activity['user_email'] }})</small>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-success">{{ number_format($activity['amount'], 2) }} €</span>
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

        <!-- Statistiques globales (admin seulement) -->
        @if($userRole === 'admin' && isset($dashboardData['global_statistics']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-globe"></i> Statistiques Globales
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3 class="text-primary">{{ $dashboardData['global_statistics']['total_users'] }}</h3>
                                    <p class="text-muted">Total Utilisateurs</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3 class="text-success">{{ $dashboardData['global_statistics']['total_transactions'] }}</h3>
                                    <p class="text-muted">Total Transactions</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3 class="text-info">{{ number_format($dashboardData['global_statistics']['total_revenue'], 2) }} €</h3>
                                    <p class="text-muted">Total Revenus</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3 class="text-warning">{{ $dashboardData['global_statistics']['performance_metrics']['active_users'] ?? 0 }}</h3>
                                    <p class="text-muted">Utilisateurs Actifs</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    @else
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h4>Erreur de chargement</h4>
                        <p class="text-muted">Impossible de charger les données du dashboard.</p>
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
<script>
    // Auto-refresh toutes les 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000);

    // Animation des compteurs
    document.addEventListener('DOMContentLoaded', function() {
        const counters = document.querySelectorAll('h3, h4');
        counters.forEach(counter => {
            if (counter.textContent.includes('€') || !isNaN(counter.textContent)) {
                counter.style.opacity = '0';
                counter.style.transition = 'opacity 0.5s ease-in-out';
                setTimeout(() => {
                    counter.style.opacity = '1';
                }, 100);
            }
        });
    });
</script>
@endpush
@endsection
