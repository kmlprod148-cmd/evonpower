@extends("layouts.admin")

@section("title", "Dashboard - Administration")
@section("page-title", "Dashboard")
@section("add-button", "Nouveau")

@section("content")
<div class="row">
    <!-- Statistics Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Charging Points
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $chargingPointsCount ?? 0 }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-charging-station fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Utilisateurs
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $usersCount ?? 0 }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Transactions
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $transactionsCount ?? 0 }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Réservations
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $reservationsCount ?? 0 }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-line"></i>
                    Activité Récente
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="fas fa-charging-station text-primary"></i> Charging Point</td>
                                <td>Nouveau point de charge ajouté</td>
                                <td>{{ now()->format("d/m/Y H:i") }}</td>
                                <td><span class="badge bg-success">Actif</span></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-user text-info"></i> Utilisateur</td>
                                <td>Nouvel utilisateur enregistré</td>
                                <td>{{ now()->subHours(2)->format("d/m/Y H:i") }}</td>
                                <td><span class="badge bg-info">En attente</span></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-money-bill-wave text-success"></i> Transaction</td>
                                <td>Nouvelle transaction enregistrée</td>
                                <td>{{ now()->subHours(4)->format("d/m/Y H:i") }}</td>
                                <td><span class="badge bg-success">Complétée</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-tasks"></i>
                    Actions Rapides
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route("charging-points.create.step1") }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Nouveau Charging Point
                    </a>
                    <a href="{{ route("admin.users.create") }}" class="btn btn-success">
                        <i class="fas fa-user-plus"></i>
                        Nouvel Utilisateur
                    </a>
                    <a href="{{ route("admin.business-profiles.create") }}" class="btn btn-info">
                        <i class="fas fa-chart-line"></i>
                        Nouveau Business Profile
                    </a>
                    <a href="{{ route("admin.reports.index") }}" class="btn btn-warning">
                        <i class="fas fa-chart-bar"></i>
                        Voir les Rapports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection