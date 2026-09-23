@extends('layouts.app')

@section('title', 'Mon Profil - Solde et Transactions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Profil utilisateur -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user"></i>
                        Mon Profil
                    </h3>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-title rounded-circle bg-primary text-white display-4">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <h4 id="userName">Chargement...</h4>
                        <p class="text-muted" id="userEmail">Chargement...</p>
                        <span class="badge badge-lg" id="userRoleBadge">Chargement...</span>
                    </div>
                    
                    <hr>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <h5 class="text-muted mb-1">Solde Actuel</h5>
                            <h3 class="text-success mb-0" id="currentBalance">0 EUR</h3>
                        </div>
                        <div class="col-6">
                            <h5 class="text-muted mb-1">Transactions</h5>
                            <h3 class="text-info mb-0" id="totalTransactions">0</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hiérarchie -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sitemap"></i>
                        Hiérarchie
                    </h3>
                </div>
                <div class="card-body">
                    <div id="hierarchyInfo">
                        <!-- Le contenu sera chargé via AJAX -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Solde et historique -->
        <div class="col-md-8">
            <!-- Statistiques de solde -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-arrow-up"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Entrées</span>
                            <span class="info-box-number" id="totalIncoming">0 EUR</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="fas fa-arrow-down"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Sorties</span>
                            <span class="info-box-number" id="totalOutgoing">0 EUR</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-exchange-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Net</span>
                            <span class="info-box-number" id="netBalance">0 EUR</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-chart-line"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Tx</span>
                            <span class="info-box-number" id="totalTxCount">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historique des mouvements -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i>
                        Historique des Mouvements
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" onclick="refreshBalanceData()">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="balanceHistoryTable">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Contrepartie</th>
                                    <th>Montant</th>
                                    <th>Solde Avant</th>
                                    <th>Solde Après</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Les données seront chargées via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div id="balancePagination"></div>
                        <div>
                            <small class="text-muted">
                                Affichage de <span id="balanceCount">0</span> mouvements
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les détails d'un mouvement -->
<div class="modal fade" id="movementDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails du Mouvement</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="movementDetailModalBody">
                <!-- Le contenu sera chargé via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentBalancePage = 1;

$(document).ready(function() {
    loadUserProfile();
    loadBalanceHistory();
});

function loadUserProfile() {
    $.get('/api/v1/balance/profile')
        .done(function(response) {
            if (response.success) {
                const data = response.data;
                
                // Informations utilisateur
                $('#userName').text(data.user.name);
                $('#userEmail').text(data.user.email);
                $('#userRoleBadge').text(data.user.role).removeClass().addClass(`badge badge-lg badge-${data.user.role_badge_color}`);
                
                // Solde
                $('#currentBalance').text(data.balance.formatted_balance);
                $('#totalTransactions').text(data.balance.stats.total_transactions);
                
                // Statistiques
                $('#totalIncoming').text(data.balance.stats.total_incoming + ' EUR');
                $('#totalOutgoing').text(data.balance.stats.total_outgoing + ' EUR');
                $('#netBalance').text(data.balance.stats.net_balance_change + ' EUR');
                $('#totalTxCount').text(data.balance.stats.total_transactions);
                
                // Hiérarchie
                displayHierarchy(data.hierarchy);
            }
        });
}

function displayHierarchy(hierarchy) {
    let html = '';
    
    if (hierarchy.root_admin) {
        html += `
            <div class="d-flex align-items-center mb-2">
                <div class="avatar-sm bg-danger rounded-circle d-flex align-items-center justify-content-center me-2">
                    <i class="fas fa-crown text-white"></i>
                </div>
                <div>
                    <div class="fw-medium">${hierarchy.root_admin.name}</div>
                    <small class="text-muted">Admin Racine</small>
                </div>
            </div>
        `;
    }
    
    if (hierarchy.direct_integrator) {
        html += `
            <div class="d-flex align-items-center mb-2">
                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                    <i class="fas fa-user-tie text-white"></i>
                </div>
                <div>
                    <div class="fw-medium">${hierarchy.direct_integrator.name}</div>
                    <small class="text-muted">Intégrateur Direct</small>
                </div>
            </div>
        `;
    }
    
    if (hierarchy.stats) {
        html += '<hr>';
        if (hierarchy.stats.created_integrators) {
            html += `<div class="d-flex justify-content-between"><span>Intégrateurs créés:</span><strong>${hierarchy.stats.created_integrators}</strong></div>`;
        }
        if (hierarchy.stats.created_operators) {
            html += `<div class="d-flex justify-content-between"><span>Opérateurs créés:</span><strong>${hierarchy.stats.created_operators}</strong></div>`;
        }
        if (hierarchy.stats.total_created_users) {
            html += `<div class="d-flex justify-content-between"><span>Total utilisateurs:</span><strong>${hierarchy.stats.total_created_users}</strong></div>`;
        }
    }
    
    $('#hierarchyInfo').html(html || '<p class="text-muted">Aucune hiérarchie disponible</p>');
}

function loadBalanceHistory(page = 1) {
    $.get('/api/v1/balance/history', { limit: 20, page: page })
        .done(function(response) {
            if (response.success) {
                const tbody = $('#balanceHistoryTable tbody');
                tbody.empty();
                
                response.data.forEach(function(movement) {
                    const typeClass = movement.type === 'incoming' ? 'success' : 'danger';
                    const typeIcon = movement.type === 'incoming' ? 'arrow-up' : 'arrow-down';
                    const typeText = movement.type === 'incoming' ? 'Entrée' : 'Sortie';
                    
                    const row = `
                        <tr>
                            <td>
                                <span class="badge badge-${typeClass}">
                                    <i class="fas fa-${typeIcon}"></i> ${typeText}
                                </span>
                            </td>
                            <td>${movement.counterparty}</td>
                            <td class="text-end ${movement.type === 'incoming' ? 'text-success' : 'text-danger'}">
                                ${movement.type === 'incoming' ? '+' : '-'}${movement.amount} EUR
                            </td>
                            <td class="text-end">${movement.balance_before} EUR</td>
                            <td class="text-end fw-bold">${movement.balance_after} EUR</td>
                            <td>${new Date(movement.created_at).toLocaleDateString()}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
                
                $('#balanceCount').text(response.data.length);
            }
        });
}

function refreshBalanceData() {
    loadUserProfile();
    loadBalanceHistory(currentBalancePage);
    toastr.success('Données actualisées');
}

// Actualisation automatique toutes les 30 secondes
setInterval(function() {
    loadUserProfile();
}, 30000);
</script>
@endsection
