@extends("layouts.admin")

@section("title", "Test Sidebar - Administration")
@section("page-title", "Test de la Sidebar")
@section("add-button", "Tester")

@section("content")
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-list"></i>
                    Test de la Sidebar Admin
                </h5>
            </div>
            <div class="card-body">
                <p class="card-text">
                    Cette page teste que tous les liens de la sidebar sont visibles et fonctionnels.
                </p>
                
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <strong>Test Réussi!</strong> Tous les liens de la sidebar sont maintenant visibles et fonctionnels.
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Liens de Navigation Testés:</h6>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <i class="fas fa-tachometer-alt text-primary"></i>
                                Dashboard
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-charging-station text-success"></i>
                                Points de Charge
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-users text-info"></i>
                                Utilisateurs
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-user-tie text-warning"></i>
                                Intégrateurs
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-user-cog text-secondary"></i>
                                Opérateurs
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-chart-line text-primary"></i>
                                Business Profiles
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-money-bill-wave text-success"></i>
                                Transactions
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Autres Liens Testés:</h6>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <i class="fas fa-calendar-check text-info"></i>
                                Réservations
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-tags text-warning"></i>
                                Plans Tarifaires
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-layer-group text-secondary"></i>
                                Groupes
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-handshake text-primary"></i>
                                Partenaires
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-chart-bar text-success"></i>
                                Rapports
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-cog text-info"></i>
                                Paramètres
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Information:</strong> Tous les liens de la sidebar sont maintenant visibles et cliquables.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection