@extends('layouts.app')

@section('title', 'Tableau de Bord Analytique des Transactions')

@section('content')
<div class="container-fluid">
    <!-- En-tête avec contrôles -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">
                                <i class="fas fa-chart-line me-2"></i>
                                Tableau de Bord Analytique des Transactions
                            </h4>
                            <small>Analyse détaillée des interactions entre utilisateurs du système</small>
                        </div>
                        <div class="d-flex gap-2">
                            <!-- Sélecteur de période -->
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm {{ $period === 'today' ? 'btn-light' : 'btn-outline-light' }}" 
                                        onclick="updatePeriod('today')">Aujourd'hui</button>
                                <button type="button" class="btn btn-sm {{ $period === 'week' ? 'btn-light' : 'btn-outline-light' }}" 
                                        onclick="updatePeriod('week')">Cette semaine</button>
                                <button type="button" class="btn btn-sm {{ $period === 'month' ? 'btn-light' : 'btn-outline-light' }}" 
                                        onclick="updatePeriod('month')">Ce mois</button>
                                <button type="button" class="btn btn-sm {{ $period === 'quarter' ? 'btn-light' : 'btn-outline-light' }}" 
                                        onclick="updatePeriod('quarter')">Ce trimestre</button>
                                <button type="button" class="btn btn-sm {{ $period === 'year' ? 'btn-light' : 'btn-outline-light' }}" 
                                        onclick="updatePeriod('year')">Cette année</button>
                                <button type="button" class="btn btn-sm {{ $period === 'all' ? 'btn-light' : 'btn-outline-light' }}" 
                                        onclick="updatePeriod('all')">Tout</button>
                            </div>
                            
                            <!-- Boutons d'action -->
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-light" onclick="refreshData()">
                                    <i class="fas fa-sync-alt"></i> Actualiser
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-light" onclick="exportData()">
                                    <i class="fas fa-download"></i> Exporter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicateurs de connexion et dernière mise à jour -->
    <div class="row mb-2">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div id="connection-status" class="connection-status connected">
                    <i class="fas fa-circle text-success"></i> Connecté
                </div>
                <div id="last-update" class="last-update-indicator">
                    Dernière mise à jour: {{ now()->format('H:i:s') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques générales -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="stat-card bg-primary text-white">
                <div class="stat-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Total Transactions</h6>
                    <h4 class="stat-value">{{ number_format($generalStats['total_transactions']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card bg-success text-white">
                <div class="stat-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Montant Total</h6>
                    <h4 class="stat-value">{{ number_format($generalStats['total_amount'], 2) }} €</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card bg-info text-white">
                <div class="stat-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Énergie Totale</h6>
                    <h4 class="stat-value">{{ number_format($generalStats['total_energy_delivered'], 2) }} kWh</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card bg-warning text-white">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Moyenne Transaction</h6>
                    <h4 class="stat-value">{{ number_format($generalStats['average_transaction_amount'], 2) }} €</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card bg-secondary text-white">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Durée Moyenne</h6>
                    <h4 class="stat-value">{{ number_format($generalStats['average_duration'] / 60, 1) }} min</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card bg-dark text-white">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-title">Taux de Réussite</h6>
                    <h4 class="stat-value">{{ $generalStats['total_transactions'] > 0 ? number_format(($generalStats['completed_transactions'] / $generalStats['total_transactions']) * 100, 1) : 0 }}%</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyses hiérarchiques -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-sitemap me-2"></i>
                        Analyses Hiérarchiques - Interactions entre Utilisateurs
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Statistiques hiérarchiques -->
                        <div class="col-md-6">
                            <h6>Statistiques Hiérarchiques</h6>
                            <div class="row">
                                <div class="col-6">
                                    <div class="metric-card">
                                        <div class="metric-value">{{ number_format($hierarchyAnalytics['stats']['total_hierarchy_transactions']) }}</div>
                                        <div class="metric-label">Transactions Hiérarchiques</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-card">
                                        <div class="metric-value">{{ number_format($hierarchyAnalytics['stats']['total_hierarchy_amount'], 2) }} €</div>
                                        <div class="metric-label">Montant Total</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <div class="metric-card">
                                        <div class="metric-value">{{ number_format($hierarchyAnalytics['stats']['admin_integrator_transactions']) }}</div>
                                        <div class="metric-label">Admin ↔ Intégrateur</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-card">
                                        <div class="metric-value">{{ number_format($hierarchyAnalytics['stats']['integrator_operator_transactions']) }}</div>
                                        <div class="metric-label">Intégrateur ↔ Opérateur</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Flux entre rôles -->
                        <div class="col-md-6">
                            <h6>Flux entre Rôles</h6>
                            <canvas id="roleFlowChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <!-- Top payeurs et bénéficiaires -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h6>Top Payeurs</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Utilisateur</th>
                                            <th>Rôle</th>
                                            <th>Transactions</th>
                                            <th>Montant Payé</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($hierarchyAnalytics['top_payers'] as $payer)
                                            <tr>
                                                <td>{{ $payer->payer->name ?? 'N/A' }}</td>
                                                <td><span class="badge bg-secondary">{{ $payer->payer->role ?? 'N/A' }}</span></td>
                                                <td>{{ $payer->transaction_count }}</td>
                                                <td>{{ number_format($payer->total_paid, 2) }} €</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Top Bénéficiaires</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Utilisateur</th>
                                            <th>Rôle</th>
                                            <th>Transactions</th>
                                            <th>Montant Reçu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($hierarchyAnalytics['top_payees'] as $payee)
                                            <tr>
                                                <td>{{ $payee->payee->name ?? 'N/A' }}</td>
                                                <td><span class="badge bg-secondary">{{ $payee->payee->role ?? 'N/A' }}</span></td>
                                                <td>{{ $payee->transaction_count }}</td>
                                                <td>{{ number_format($payee->total_received, 2) }} €</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyses par utilisateur -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Analyses par Utilisateur et Rôle
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Statistiques par rôle -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>Statistiques par Rôle</h6>
                            <div class="row">
                                @foreach($userAnalytics['role_stats'] as $role => $stats)
                                    <div class="col-md-3">
                                        <div class="role-stat-card">
                                            <div class="role-header">
                                                <h6 class="role-name">{{ ucfirst($role) }}</h6>
                                                <span class="role-count">{{ $stats['user_count'] }} utilisateurs</span>
                                            </div>
                                            <div class="role-metrics">
                                                <div class="metric">
                                                    <span class="metric-label">Transactions:</span>
                                                    <span class="metric-value">{{ number_format($stats['total_transactions']) }}</span>
                                                </div>
                                                <div class="metric">
                                                    <span class="metric-label">Montant Total:</span>
                                                    <span class="metric-value">{{ number_format($stats['total_amount'], 2) }} €</span>
                                                </div>
                                                <div class="metric">
                                                    <span class="metric-label">Moyenne par Utilisateur:</span>
                                                    <span class="metric-value">{{ number_format($stats['average_per_user'], 2) }} €</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top utilisateurs -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Top Utilisateurs par Activité</h6>
                            <div class="table-responsive">
                                <table class="table table-sm" id="top-active-users">
                                    <thead>
                                        <tr>
                                            <th>Utilisateur</th>
                                            <th>Rôle</th>
                                            <th>Transactions</th>
                                            <th>Montant Total</th>
                                            <th>Moyenne</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($userAnalytics['top_active_users'] as $user)
                                            <tr>
                                                <td>{{ $user->user->name ?? 'N/A' }}</td>
                                                <td><span class="badge bg-primary">{{ $user->user->role ?? 'N/A' }}</span></td>
                                                <td>{{ $user->transaction_count }}</td>
                                                <td>{{ number_format($user->total_amount, 2) }} €</td>
                                                <td>{{ number_format($user->avg_amount, 2) }} €</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Top Utilisateurs par Revenus</h6>
                            <div class="table-responsive">
                                <table class="table table-sm" id="top-revenue-users">
                                    <thead>
                                        <tr>
                                            <th>Utilisateur</th>
                                            <th>Rôle</th>
                                            <th>Transactions</th>
                                            <th>Montant Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($userAnalytics['top_revenue_users'] as $user)
                                            <tr>
                                                <td>{{ $user->user->name ?? 'N/A' }}</td>
                                                <td><span class="badge bg-success">{{ $user->user->role ?? 'N/A' }}</span></td>
                                                <td>{{ $user->transaction_count }}</td>
                                                <td>{{ number_format($user->total_amount, 2) }} €</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyses financières -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Analyses Financières Détaillées
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Statistiques financières -->
                        <div class="col-md-4">
                            <h6>Revenus et Commissions</h6>
                            <div class="financial-metrics">
                                <div class="metric-row">
                                    <span class="metric-label">Revenus Totaux:</span>
                                    <span class="metric-value text-success">{{ number_format($financialAnalytics['stats']['total_revenue'], 2) }} €</span>
                                </div>
                                <div class="metric-row">
                                    <span class="metric-label">Commissions Totales:</span>
                                    <span class="metric-value text-warning">{{ number_format($financialAnalytics['stats']['total_commissions'], 2) }} €</span>
                                </div>
                                <div class="metric-row">
                                    <span class="metric-label">Revenus Nets:</span>
                                    <span class="metric-value text-info">{{ number_format($financialAnalytics['stats']['net_revenue'], 2) }} €</span>
                                </div>
                                <div class="metric-row">
                                    <span class="metric-label">Taux de Commission:</span>
                                    <span class="metric-value">{{ number_format($financialAnalytics['stats']['average_commission_rate'], 2) }}%</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Répartition des commissions -->
                        <div class="col-md-4">
                            <h6>Répartition des Commissions</h6>
                            <canvas id="commissionChart" width="300" height="200"></canvas>
                        </div>
                        
                        <!-- Répartition des revenus par type -->
                        <div class="col-md-4">
                            <h6>Revenus par Type de Transaction</h6>
                            <canvas id="revenueTypeChart" width="300" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyses temporelles -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Analyses Temporelles et Tendances
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Tendances quotidiennes -->
                        <div class="col-md-8">
                            <h6>Tendances Quotidiennes</h6>
                            <canvas id="dailyTrendsChart" width="600" height="300"></canvas>
                        </div>
                        
                        <!-- Distribution par heure -->
                        <div class="col-md-4">
                            <h6>Distribution par Heure</h6>
                            <canvas id="hourlyChart" width="300" height="200"></canvas>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <!-- Distribution par jour de la semaine -->
                        <div class="col-md-6">
                            <h6>Distribution par Jour de la Semaine</h6>
                            <canvas id="dayOfWeekChart" width="400" height="200"></canvas>
                        </div>
                        
                        <!-- Statistiques temporelles -->
                        <div class="col-md-6">
                            <h6>Statistiques Temporelles</h6>
                            <div class="temporal-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Période analysée:</span>
                                    <span class="stat-value">{{ $dates['from']->format('d/m/Y') }} - {{ $dates['to']->format('d/m/Y') }}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Jours analysés:</span>
                                    <span class="stat-value">{{ $dates['from']->diffInDays($dates['to']) + 1 }} jours</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Transactions par jour:</span>
                                    <span class="stat-value">{{ $dates['from']->diffInDays($dates['to']) > 0 ? number_format($generalStats['total_transactions'] / ($dates['from']->diffInDays($dates['to']) + 1), 1) : 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyses géographiques -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Analyses Géographiques
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Top stations -->
                        <div class="col-md-4">
                            <h6>Top Stations</h6>
                            <div class="table-responsive">
                                <table class="table table-sm" id="station-stats-table">
                                    <thead>
                                        <tr>
                                            <th>Station</th>
                                            <th>Ville</th>
                                            <th>Transactions</th>
                                            <th>Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($geographicAnalytics['station_stats']->take(10) as $station)
                                            <tr>
                                                <td>{{ $station->station_name }}</td>
                                                <td>{{ $station->city }}</td>
                                                <td>{{ $station->transaction_count }}</td>
                                                <td>{{ number_format($station->total_amount, 2) }} €</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Top villes -->
                        <div class="col-md-4">
                            <h6>Top Villes</h6>
                            <div class="table-responsive">
                                <table class="table table-sm" id="city-stats-table">
                                    <thead>
                                        <tr>
                                            <th>Ville</th>
                                            <th>Pays</th>
                                            <th>Transactions</th>
                                            <th>Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($geographicAnalytics['city_stats']->take(10) as $city)
                                            <tr>
                                                <td>{{ $city->city }}</td>
                                                <td>{{ $city->country }}</td>
                                                <td>{{ $city->transaction_count }}</td>
                                                <td>{{ number_format($city->total_amount, 2) }} €</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Top pays -->
                        <div class="col-md-4">
                            <h6>Top Pays</h6>
                            <div class="table-responsive">
                                <table class="table table-sm" id="country-stats-table">
                                    <thead>
                                        <tr>
                                            <th>Pays</th>
                                            <th>Transactions</th>
                                            <th>Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($geographicAnalytics['country_stats']->take(10) as $country)
                                            <tr>
                                                <td>{{ $country->country }}</td>
                                                <td>{{ $country->transaction_count }}</td>
                                                <td>{{ number_format($country->total_amount, 2) }} €</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyses par business profile -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i>
                        Analyses par Business Profile
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Statistiques business profile -->
                        <div class="col-md-4">
                            <h6>Statistiques Business Profile</h6>
                            <div class="business-profile-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Transactions avec BP:</span>
                                    <span class="stat-value">{{ number_format($businessProfileAnalytics['stats']['transactions_with_business_profile']) }}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Montant avec BP:</span>
                                    <span class="stat-value">{{ number_format($businessProfileAnalytics['stats']['total_amount_with_business_profile'], 2) }} €</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Moyenne avec BP:</span>
                                    <span class="stat-value">{{ number_format($businessProfileAnalytics['stats']['average_amount_with_business_profile'], 2) }} €</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top business profiles -->
                        <div class="col-md-8">
                            <h6>Top Business Profiles par Utilisation</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Business Profile</th>
                                            <th>Utilisations</th>
                                            <th>Montant Total</th>
                                            <th>Moyenne</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($businessProfileAnalytics['top_business_profiles'] as $bp)
                                            <tr>
                                                <td>{{ $bp->businessProfile->name ?? 'N/A' }}</td>
                                                <td>{{ $bp->usage_count }}</td>
                                                <td>{{ number_format($bp->total_amount, 2) }} €</td>
                                                <td>{{ number_format($bp->total_amount / $bp->usage_count, 2) }} €</td>
                                            </tr>
                                        @endforeach
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

<!-- Styles CSS -->
<style>
.stat-card {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
    height: 100px;
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
    background: rgba(255,255,255,0.2);
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
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.metric-card {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    text-align: center;
    margin-bottom: 1rem;
}

.metric-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #495057;
}

.metric-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.role-stat-card {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.role-header {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 0.5rem;
    margin-bottom: 0.5rem;
}

.role-name {
    font-weight: 600;
    margin: 0;
    color: #495057;
}

.role-count {
    font-size: 0.875rem;
    color: #6c757d;
}

.role-metrics .metric {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}

.metric-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.metric-value {
    font-weight: 600;
    color: #495057;
}

.financial-metrics .metric-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.temporal-stats .stat-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.stat-label {
    font-weight: 500;
    color: #495057;
}

.stat-value {
    font-weight: 600;
    color: #212529;
}

.business-profile-stats .stat-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f3f4;
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

.table-responsive {
    max-height: 300px;
    overflow-y: auto;
}

.badge {
    font-size: 0.75rem;
}

.connection-status {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 0.875rem;
    z-index: 1000;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.connection-status.connected {
    border-left: 4px solid #28a745;
}

.connection-status.disconnected {
    border-left: 4px solid #dc3545;
}

.last-update-indicator {
    position: fixed;
    bottom: 20px;
    left: 20px;
    padding: 8px 12px;
    background: rgba(0,0,0,0.7);
    color: white;
    border-radius: 4px;
    font-size: 0.75rem;
    z-index: 1000;
}
</style>

<!-- Scripts JavaScript -->
<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script src="{{ asset('js/transaction-analytics-realtime.js') }}"></script>
<script>
let charts = {};

// Initialiser les graphiques
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    
    // Enregistrer les graphiques pour les mises à jour en temps réel
    if (window.transactionAnalyticsRealtime) {
        window.transactionAnalyticsRealtime.registerChart('roleFlow', charts.roleFlow);
        window.transactionAnalyticsRealtime.registerChart('commission', charts.commission);
        window.transactionAnalyticsRealtime.registerChart('revenueType', charts.revenueType);
        window.transactionAnalyticsRealtime.registerChart('dailyTrends', charts.dailyTrends);
        window.transactionAnalyticsRealtime.registerChart('hourly', charts.hourly);
        window.transactionAnalyticsRealtime.registerChart('dayOfWeek', charts.dayOfWeek);
    }
});

function initCharts() {
    // Détruire les graphiques existants
    Object.values(charts).forEach(chart => {
        if (chart) chart.destroy();
    });
    charts = {};

    // Graphique des flux entre rôles
    const roleFlowCtx = document.getElementById('roleFlowChart');
    if (roleFlowCtx) {
        const roleFlowData = @json($hierarchyAnalytics['role_flows']);
        charts.roleFlow = new Chart(roleFlowCtx, {
            type: 'doughnut',
            data: {
                labels: roleFlowData.map(item => {
                    return item.transaction_type === 'admin_integrator' ? 'Admin ↔ Intégrateur' : 'Intégrateur ↔ Opérateur';
                }),
                datasets: [{
                    data: roleFlowData.map(item => item.count),
                    backgroundColor: ['#FF6384', '#36A2EB'],
                    borderWidth: 2
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

    // Graphique des commissions
    const commissionCtx = document.getElementById('commissionChart');
    if (commissionCtx) {
        const commissionData = @json($financialAnalytics['commission_by_role']);
        charts.commission = new Chart(commissionCtx, {
            type: 'pie',
            data: {
                labels: ['Admin', 'Intégrateur', 'Partner'],
                datasets: [{
                    data: [commissionData.admin, commissionData.integrator, commissionData.partner],
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56'],
                    borderWidth: 2
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

    // Graphique des revenus par type
    const revenueTypeCtx = document.getElementById('revenueTypeChart');
    if (revenueTypeCtx) {
        const revenueTypeData = @json($financialAnalytics['revenue_by_type']);
        charts.revenueType = new Chart(revenueTypeCtx, {
            type: 'bar',
            data: {
                labels: revenueTypeData.map(item => item.transaction_type),
                datasets: [{
                    label: 'Montant (€)',
                    data: revenueTypeData.map(item => item.total_amount),
                    backgroundColor: '#4BC0C0',
                    borderWidth: 1
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
    }

    // Graphique des tendances quotidiennes
    const dailyTrendsCtx = document.getElementById('dailyTrendsChart');
    if (dailyTrendsCtx) {
        const dailyTrendsData = @json($temporalAnalytics['daily_trends']);
        charts.dailyTrends = new Chart(dailyTrendsCtx, {
            type: 'line',
            data: {
                labels: dailyTrendsData.map(item => item.date),
                datasets: [{
                    label: 'Nombre de Transactions',
                    data: dailyTrendsData.map(item => item.transaction_count),
                    borderColor: '#36A2EB',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.1,
                    yAxisID: 'y'
                }, {
                    label: 'Montant Total (€)',
                    data: dailyTrendsData.map(item => item.total_amount),
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

    // Graphique de distribution par heure
    const hourlyCtx = document.getElementById('hourlyChart');
    if (hourlyCtx) {
        const hourlyData = @json($temporalAnalytics['hourly_distribution']);
        charts.hourly = new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: hourlyData.map(item => item.hour + 'h'),
                datasets: [{
                    label: 'Transactions',
                    data: hourlyData.map(item => item.transaction_count),
                    backgroundColor: '#FFCE56',
                    borderWidth: 1
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
    }

    // Graphique de distribution par jour de la semaine
    const dayOfWeekCtx = document.getElementById('dayOfWeekChart');
    if (dayOfWeekCtx) {
        const dayOfWeekData = @json($temporalAnalytics['day_of_week_distribution']);
        const dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        charts.dayOfWeek = new Chart(dayOfWeekCtx, {
            type: 'bar',
            data: {
                labels: dayOfWeekData.map(item => dayNames[item.day_of_week - 1]),
                datasets: [{
                    label: 'Transactions',
                    data: dayOfWeekData.map(item => item.transaction_count),
                    backgroundColor: '#9966FF',
                    borderWidth: 1
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
    }
}

// Fonctions utilitaires
function updatePeriod(period) {
    const url = new URL(window.location);
    url.searchParams.set('period', period);
    window.location.href = url.toString();
}

function refreshData() {
    if (window.transactionAnalyticsRealtime) {
        window.transactionAnalyticsRealtime.forceUpdate();
    } else {
        window.location.reload();
    }
}

function exportData() {
    const period = new URLSearchParams(window.location.search).get('period') || 'month';
    const url = `/transactions/analytics/export?period=${period}&format=csv`;
    window.open(url, '_blank');
}
</script>
@endsection