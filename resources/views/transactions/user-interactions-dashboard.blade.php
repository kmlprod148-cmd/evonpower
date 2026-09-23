@extends('layouts.app')

@section('title', 'Tableau de Bord des Interactions Utilisateurs')

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">
                                <i class="fas fa-users-cog me-2"></i>
                                Interactions Détaillées entre Utilisateurs
                            </h4>
                            <small>Analyse approfondie des relations et flux financiers entre tous les acteurs du système</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="refreshData()">
                                <i class="fas fa-sync-alt"></i> Actualiser
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="exportReport()">
                                <i class="fas fa-download"></i> Exporter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vue d'ensemble des interactions -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="interaction-card bg-primary text-white">
                <div class="card-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="card-content">
                    <h6 class="card-title">Total Interactions</h6>
                    <h3 class="card-value">{{ number_format($interactionData['total_interactions'] ?? 0) }}</h3>
                    <small class="card-subtitle">Transactions hiérarchiques</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="interaction-card bg-success text-white">
                <div class="card-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="card-content">
                    <h6 class="card-title">Montant Total</h6>
                    <h3 class="card-value">{{ number_format($interactionData['total_amount'] ?? 0, 2) }} €</h3>
                    <small class="card-subtitle">Flux financiers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="interaction-card bg-info text-white">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <h6 class="card-title">Utilisateurs Actifs</h6>
                    <h3 class="card-value">{{ number_format($interactionData['active_users'] ?? 0) }}</h3>
                    <small class="card-subtitle">Dans les interactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="interaction-card bg-warning text-white">
                <div class="card-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="card-content">
                    <h6 class="card-title">Taux de Commission</h6>
                    <h3 class="card-value">{{ number_format($interactionData['average_commission_rate'] ?? 0, 2) }}%</h3>
                    <small class="card-subtitle">Moyenne globale</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Flux hiérarchiques détaillés -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-sitemap me-2"></i>
                        Flux Hiérarchiques Détaillés
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Flux Admin → Intégrateur -->
                    <div class="mb-4">
                        <h6 class="text-primary">
                            <i class="fas fa-arrow-right me-2"></i>
                            Flux Admin → Intégrateur
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Admin (Payeur)</th>
                                        <th>Intégrateur (Bénéficiaire)</th>
                                        <th>Montant Total</th>
                                        <th>Transactions</th>
                                        <th>Moyenne</th>
                                        <th>Dernière Transaction</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($interactionData['admin_integrator_flows'] ?? [] as $flow)
                                        <tr>
                                            <td>
                                                <div class="user-info">
                                                    <strong>{{ $flow['payer']['name'] ?? 'N/A' }}</strong>
                                                    <small class="text-muted d-block">{{ $flow['payer']['role'] ?? 'N/A' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <strong>{{ $flow['payee']['name'] ?? 'N/A' }}</strong>
                                                    <small class="text-muted d-block">{{ $flow['payee']['role'] ?? 'N/A' }}</small>
                                                </div>
                                            </td>
                                            <td class="text-success">
                                                <strong>{{ number_format($flow['amount'], 2) }} €</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $flow['count'] }}</span>
                                            </td>
                                            <td>{{ number_format($flow['amount'] / $flow['count'], 2) }} €</td>
                                            <td>{{ $flow['last_transaction'] ? \Carbon\Carbon::parse($flow['last_transaction'])->format('d/m/Y H:i') : 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Flux Intégrateur → Opérateur -->
                    <div>
                        <h6 class="text-success">
                            <i class="fas fa-arrow-right me-2"></i>
                            Flux Intégrateur → Opérateur
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-success">
                                    <tr>
                                        <th>Intégrateur (Payeur)</th>
                                        <th>Opérateur (Bénéficiaire)</th>
                                        <th>Montant Total</th>
                                        <th>Transactions</th>
                                        <th>Moyenne</th>
                                        <th>Dernière Transaction</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($interactionData['integrator_operator_flows'] ?? [] as $flow)
                                        <tr>
                                            <td>
                                                <div class="user-info">
                                                    <strong>{{ $flow['payer']['name'] ?? 'N/A' }}</strong>
                                                    <small class="text-muted d-block">{{ $flow['payer']['role'] ?? 'N/A' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <strong>{{ $flow['payee']['name'] ?? 'N/A' }}</strong>
                                                    <small class="text-muted d-block">{{ $flow['payee']['role'] ?? 'N/A' }}</small>
                                                </div>
                                            </td>
                                            <td class="text-success">
                                                <strong>{{ number_format($flow['amount'], 2) }} €</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ $flow['count'] }}</span>
                                            </td>
                                            <td>{{ number_format($flow['amount'] / $flow['count'], 2) }} €</td>
                                            <td>{{ $flow['last_transaction'] ? \Carbon\Carbon::parse($flow['last_transaction'])->format('d/m/Y H:i') : 'N/A' }}</td>
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

    <!-- Relations entre utilisateurs -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-project-diagram me-2"></i>
                        Relations et Réseaux d'Utilisateurs
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($interactionData['user_relationships'] ?? [] as $userId => $relationship)
                            <div class="col-md-6 mb-4">
                                <div class="relationship-card">
                                    <div class="relationship-header">
                                        <h6 class="relationship-user">
                                            <i class="fas fa-user me-2"></i>
                                            {{ $relationship['user']['name'] ?? 'N/A' }}
                                            <span class="badge bg-secondary">{{ $relationship['user']['role'] ?? 'N/A' }}</span>
                                        </h6>
                                        <div class="relationship-stats">
                                            <span class="stat-item">
                                                <strong>{{ count($relationship['relationships']) }}</strong> partenaires
                                            </span>
                                            <span class="stat-item">
                                                <strong>{{ number_format($relationship['total_amount'], 2) }} €</strong> total
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="relationship-partners">
                                        @foreach($relationship['relationships'] as $partnerId => $partner)
                                            <div class="partner-item">
                                                <div class="partner-info">
                                                    <strong>{{ $partner['partner']['name'] ?? 'N/A' }}</strong>
                                                    <small class="text-muted d-block">{{ $partner['partner']['role'] ?? 'N/A' }}</small>
                                                </div>
                                                <div class="partner-stats">
                                                    <div class="stat">
                                                        <span class="stat-label">Montant:</span>
                                                        <span class="stat-value">{{ number_format($partner['total_amount'], 2) }} €</span>
                                                    </div>
                                                    <div class="stat">
                                                        <span class="stat-label">Transactions:</span>
                                                        <span class="stat-value">{{ $partner['transaction_count'] }}</span>
                                                    </div>
                                                    <div class="stat">
                                                        <span class="stat-label">Types:</span>
                                                        <span class="stat-value">
                                                            @foreach(array_unique($partner['transaction_types']) as $type)
                                                                <span class="badge bg-info">{{ $type }}</span>
                                                            @endforeach
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Distribution des commissions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Distribution Détaillée des Commissions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Répartition globale -->
                        <div class="col-md-4">
                            <h6>Répartition Globale</h6>
                            <canvas id="commissionDistributionChart" width="300" height="200"></canvas>
                            <div class="commission-breakdown mt-3">
                                <div class="breakdown-item">
                                    <span class="breakdown-label">Admin:</span>
                                    <span class="breakdown-value">{{ number_format($interactionData['commission_distribution']['admin_share']['amount'] ?? 0, 2) }} €</span>
                                    <span class="breakdown-percentage">({{ number_format($interactionData['commission_distribution']['admin_share']['percentage'] ?? 0, 1) }}%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="breakdown-label">Intégrateur:</span>
                                    <span class="breakdown-value">{{ number_format($interactionData['commission_distribution']['integrator_share']['amount'] ?? 0, 2) }} €</span>
                                    <span class="breakdown-percentage">({{ number_format($interactionData['commission_distribution']['integrator_share']['percentage'] ?? 0, 1) }}%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="breakdown-label">Partner:</span>
                                    <span class="breakdown-value">{{ number_format($interactionData['commission_distribution']['partner_share']['amount'] ?? 0, 2) }} €</span>
                                    <span class="breakdown-percentage">({{ number_format($interactionData['commission_distribution']['partner_share']['percentage'] ?? 0, 1) }}%)</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Commissions par type de transaction -->
                        <div class="col-md-8">
                            <h6>Commissions par Type de Transaction</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Type de Transaction</th>
                                            <th>Nombre</th>
                                            <th>Admin</th>
                                            <th>Intégrateur</th>
                                            <th>Partner</th>
                                            <th>Total</th>
                                            <th>Moyenne</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($interactionData['commission_distribution']['commission_by_transaction_type'] ?? [] as $type)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $type['transaction_type'] }}</span>
                                                </td>
                                                <td>{{ $type['transaction_count'] }}</td>
                                                <td class="text-primary">{{ number_format($type['admin_commission'], 2) }} €</td>
                                                <td class="text-success">{{ number_format($type['integrator_commission'], 2) }} €</td>
                                                <td class="text-warning">{{ number_format($type['partner_commission'], 2) }} €</td>
                                                <td class="text-info"><strong>{{ number_format($type['total_commission'], 2) }} €</strong></td>
                                                <td>{{ number_format($type['average_commission_per_transaction'], 2) }} €</td>
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

    <!-- Impact des business profiles -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i>
                        Impact des Business Profiles sur les Interactions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Statistiques générales -->
                        <div class="col-md-4">
                            <h6>Statistiques Générales</h6>
                            <div class="bp-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Transactions avec BP:</span>
                                    <span class="stat-value">{{ number_format($interactionData['business_profile_impact']['total_transactions_with_bp'] ?? 0) }}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Montant avec BP:</span>
                                    <span class="stat-value">{{ number_format($interactionData['business_profile_impact']['total_amount_with_bp'] ?? 0, 2) }} €</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Moyenne avec BP:</span>
                                    <span class="stat-value">{{ number_format($interactionData['business_profile_impact']['average_amount_with_bp'] ?? 0, 2) }} €</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top business profiles -->
                        <div class="col-md-8">
                            <h6>Top Business Profiles par Impact</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Business Profile</th>
                                            <th>Type</th>
                                            <th>Utilisations</th>
                                            <th>Montant Total</th>
                                            <th>Moyenne</th>
                                            <th>Commissions Admin</th>
                                            <th>Commissions Intégrateur</th>
                                            <th>Commissions Partner</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($interactionData['business_profile_impact']['business_profile_stats'] ?? [] as $bp)
                                            <tr>
                                                <td>
                                                    <strong>{{ $bp->businessProfile->name ?? 'N/A' }}</strong>
                                                    <small class="text-muted d-block">{{ $bp->businessProfile->description ?? 'N/A' }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">{{ $bp->businessProfile->type ?? 'N/A' }}</span>
                                                </td>
                                                <td>{{ $bp->usage_count }}</td>
                                                <td class="text-success">{{ number_format($bp->total_amount, 2) }} €</td>
                                                <td>{{ number_format($bp->average_amount, 2) }} €</td>
                                                <td class="text-primary">{{ number_format($bp->total_admin_commission, 2) }} €</td>
                                                <td class="text-success">{{ number_format($bp->total_integrator_commission, 2) }} €</td>
                                                <td class="text-warning">{{ number_format($bp->total_partner_commission, 2) }} €</td>
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

    <!-- Patterns géographiques -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Patterns Géographiques des Interactions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Top stations -->
                        <div class="col-md-4">
                            <h6>Top Stations par Interactions</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Station</th>
                                            <th>Ville</th>
                                            <th>Transactions</th>
                                            <th>Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($interactionData['geographic_patterns']['station_patterns'] ?? [] as $station)
                                            <tr>
                                                <td>
                                                    <strong>{{ $station->station_name }}</strong>
                                                </td>
                                                <td>{{ $station->city }}, {{ $station->country }}</td>
                                                <td>{{ $station->transaction_count }}</td>
                                                <td class="text-success">{{ number_format($station->total_amount, 2) }} €</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Top villes -->
                        <div class="col-md-4">
                            <h6>Top Villes par Interactions</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Ville</th>
                                            <th>Pays</th>
                                            <th>Transactions</th>
                                            <th>Montant</th>
                                            <th>Stations</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($interactionData['geographic_patterns']['city_patterns'] ?? [] as $city)
                                            <tr>
                                                <td><strong>{{ $city->city }}</strong></td>
                                                <td>{{ $city->country }}</td>
                                                <td>{{ $city->transaction_count }}</td>
                                                <td class="text-success">{{ number_format($city->total_amount, 2) }} €</td>
                                                <td>{{ $city->station_count }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Top pays -->
                        <div class="col-md-4">
                            <h6>Top Pays par Interactions</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Pays</th>
                                            <th>Transactions</th>
                                            <th>Montant</th>
                                            <th>Villes</th>
                                            <th>Stations</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($interactionData['geographic_patterns']['country_patterns'] ?? [] as $country)
                                            <tr>
                                                <td><strong>{{ $country->country }}</strong></td>
                                                <td>{{ $country->transaction_count }}</td>
                                                <td class="text-success">{{ number_format($country->total_amount, 2) }} €</td>
                                                <td>{{ $country->city_count }}</td>
                                                <td>{{ $country->station_count }}</td>
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

    <!-- Patterns temporels -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Patterns Temporels des Interactions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Distribution par heure -->
                        <div class="col-md-6">
                            <h6>Distribution par Heure de la Journée</h6>
                            <canvas id="hourlyPatternsChart" width="400" height="200"></canvas>
                        </div>
                        
                        <!-- Distribution par jour de la semaine -->
                        <div class="col-md-6">
                            <h6>Distribution par Jour de la Semaine</h6>
                            <canvas id="dayOfWeekPatternsChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <!-- Patterns saisonniers -->
                        <div class="col-md-6">
                            <h6>Patterns Saisonniers</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Saison</th>
                                            <th>Transactions</th>
                                            <th>Montant Total</th>
                                            <th>Moyenne</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($interactionData['temporal_patterns']['seasonal_patterns'] ?? [] as $season)
                                            <tr>
                                                <td><strong>{{ $season->season }}</strong></td>
                                                <td>{{ $season->transaction_count }}</td>
                                                <td class="text-success">{{ number_format($season->total_amount, 2) }} €</td>
                                                <td>{{ number_format($season->average_amount, 2) }} €</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
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
                                    <span class="stat-label">Interactions par jour:</span>
                                    <span class="stat-value">{{ $dates['from']->diffInDays($dates['to']) > 0 ? number_format(($interactionData['total_interactions'] ?? 0) / ($dates['from']->diffInDays($dates['to']) + 1), 1) : 0 }}</span>
                                </div>
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
.interaction-card {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
    height: 120px;
}

.card-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 2rem;
    background: rgba(255,255,255,0.2);
}

.card-content {
    flex: 1;
}

.card-title {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-bottom: 0.25rem;
}

.card-value {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.card-subtitle {
    font-size: 0.75rem;
    opacity: 0.8;
}

.relationship-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid #dee2e6;
}

.relationship-header {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 0.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.relationship-user {
    margin: 0;
    font-size: 1rem;
}

.relationship-stats {
    display: flex;
    gap: 1rem;
}

.stat-item {
    font-size: 0.875rem;
    color: #6c757d;
}

.partner-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.partner-info {
    flex: 1;
}

.partner-stats {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.partner-stats .stat {
    text-align: center;
}

.stat-label {
    font-size: 0.75rem;
    color: #6c757d;
    display: block;
}

.stat-value {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
}

.commission-breakdown {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
    border-bottom: 1px solid #e9ecef;
}

.breakdown-item:last-child {
    border-bottom: none;
}

.breakdown-label {
    font-weight: 500;
    color: #495057;
}

.breakdown-value {
    font-weight: 600;
    color: #212529;
}

.breakdown-percentage {
    font-size: 0.875rem;
    color: #6c757d;
}

.bp-stats .stat-item {
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

.user-info {
    line-height: 1.2;
}

.table-responsive {
    max-height: 400px;
    overflow-y: auto;
}

.badge {
    font-size: 0.75rem;
}

.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: none;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>

<!-- Scripts JavaScript -->
<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
let charts = {};

// Initialiser les graphiques
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
});

function initCharts() {
    // Détruire les graphiques existants
    Object.values(charts).forEach(chart => {
        if (chart) chart.destroy();
    });
    charts = {};

    // Graphique de distribution des commissions
    const commissionCtx = document.getElementById('commissionDistributionChart');
    if (commissionCtx) {
        const commissionData = @json($interactionData['commission_distribution'] ?? []);
        charts.commissionDistribution = new Chart(commissionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Admin', 'Intégrateur', 'Partner'],
                datasets: [{
                    data: [
                        commissionData.admin_share?.amount || 0,
                        commissionData.integrator_share?.amount || 0,
                        commissionData.partner_share?.amount || 0
                    ],
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

    // Graphique des patterns par heure
    const hourlyCtx = document.getElementById('hourlyPatternsChart');
    if (hourlyCtx) {
        const hourlyData = @json($interactionData['temporal_patterns']['hourly_patterns'] ?? []);
        charts.hourlyPatterns = new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: hourlyData.map(item => item.hour + 'h'),
                datasets: [{
                    label: 'Transactions',
                    data: hourlyData.map(item => item.transaction_count),
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

    // Graphique des patterns par jour de la semaine
    const dayOfWeekCtx = document.getElementById('dayOfWeekPatternsChart');
    if (dayOfWeekCtx) {
        const dayOfWeekData = @json($interactionData['temporal_patterns']['day_of_week_patterns'] ?? []);
        const dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        charts.dayOfWeekPatterns = new Chart(dayOfWeekCtx, {
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
function refreshData() {
    window.location.reload();
}

function exportReport() {
    const url = `/transactions/analytics/export?format=csv`;
    window.open(url, '_blank');
}
</script>
@endsection
