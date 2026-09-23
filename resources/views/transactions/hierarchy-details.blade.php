@extends('layouts.app')

@section('title', 'Détails des Transactions Hiérarchiques')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sitemap"></i>
                        Détails des Transactions Hiérarchiques
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" onclick="loadExampleCalculation()">
                            <i class="fas fa-calculator"></i> Exemple de Calcul
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-control" id="filterRole">
                                <option value="">Tous les rôles</option>
                                <option value="admin">Admin</option>
                                <option value="integrator">Intégrateur</option>
                                <option value="partner">Opérateur</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" id="filterDateFrom" placeholder="Date de début">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" id="filterDateTo" placeholder="Date de fin">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-info" onclick="applyFilters()">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-coins"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Transactions</span>
                                    <span class="info-box-number" id="totalTransactions">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-money-bill-wave"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Montant Total</span>
                                    <span class="info-box-number" id="totalAmount">0 EUR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-percentage"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Frais Totaux</span>
                                    <span class="info-box-number" id="totalFees">0 EUR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-share-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Parts Admin</span>
                                    <span class="info-box-number" id="totalAdminShares">0 EUR</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglets -->
                    <ul class="nav nav-tabs" id="transactionTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="admin-shares-tab" data-bs-toggle="tab" href="#admin-shares" role="tab">
                                <i class="fas fa-crown"></i> Parts Admin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="hierarchical-tab" data-bs-toggle="tab" href="#hierarchical" role="tab">
                                <i class="fas fa-sitemap"></i> Transactions Hiérarchiques
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="balances-tab" data-bs-toggle="tab" href="#balances" role="tab">
                                <i class="fas fa-wallet"></i> Soldes Utilisateurs
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="transactionTabContent">
                        <!-- Parts Admin -->
                        <div class="tab-pane fade show active" id="admin-shares" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped" id="adminSharesTable">
                                    <thead>
                                        <tr>
                                            <th>ID Transaction</th>
                                            <th>Business Profile</th>
                                            <th>Admin</th>
                                            <th>Intégrateur</th>
                                            <th>Opérateur</th>
                                            <th>Montant Original</th>
                                            <th>Frais</th>
                                            <th>Part Admin</th>
                                            <th>Part Intégrateur</th>
                                            <th>Part Opérateur</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Les données seront chargées via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Transactions Hiérarchiques -->
                        <div class="tab-pane fade" id="hierarchical" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped" id="hierarchicalTable">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Payeur</th>
                                            <th>Bénéficiaire</th>
                                            <th>Montant</th>
                                            <th>Frais</th>
                                            <th>Montant Net</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Les données seront chargées via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Soldes Utilisateurs -->
                        <div class="tab-pane fade" id="balances" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped" id="balancesTable">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Rôle</th>
                                            <th>Solde</th>
                                            <th>Créé par</th>
                                            <th>Date de création</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Les données seront chargées via AJAX -->
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

<!-- Modal pour l'exemple de calcul -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exemple de Calcul - Transaction de 200 EUR</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="exampleModalBody">
                <!-- Le contenu sera chargé via AJAX -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    loadStats();
    loadAdminShares();
    
    // Charger les données des onglets
    $('#hierarchical-tab').on('click', function() {
        loadHierarchicalTransactions();
    });
    
    $('#balances-tab').on('click', function() {
        loadUserBalances();
    });
});

function loadStats() {
    $.get('/api/v1/transaction-hierarchy/stats')
        .done(function(response) {
            if (response.success) {
                const data = response.data;
                $('#totalTransactions').text(data.total_transactions);
                $('#totalAmount').text(data.total_amount + ' EUR');
                $('#totalFees').text(data.total_fees + ' EUR');
                $('#totalAdminShares').text(data.total_admin_shares + ' EUR');
            }
        });
}

function loadAdminShares() {
    $.get('/api/v1/transaction-hierarchy/admin-shares')
        .done(function(response) {
            if (response.success) {
                const tbody = $('#adminSharesTable tbody');
                tbody.empty();
                
                response.data.data.forEach(function(detail) {
                    const businessProfileName = detail.calculation_details && detail.calculation_details.business_profile_name 
                        ? detail.calculation_details.business_profile_name 
                        : 'N/A';
                    
                    const row = `
                        <tr>
                            <td>${detail.transaction_id}</td>
                            <td>
                                <span class="badge badge-info" title="Business Profile utilisé pour le calcul des frais">
                                    ${businessProfileName}
                                </span>
                            </td>
                            <td>${detail.admin_creator ? detail.admin_creator.name : 'N/A'}</td>
                            <td>${detail.integrator_creator ? detail.integrator_creator.name : 'N/A'}</td>
                            <td>${detail.operator ? detail.operator.name : 'N/A'}</td>
                            <td>${detail.transaction ? detail.transaction.amount : 0} EUR</td>
                            <td>
                                <span class="text-muted" title="Frais calculés basés sur le business profile">
                                    ${detail.transaction_fee_total} EUR
                                </span>
                            </td>
                            <td>${detail.admin_share_amount} EUR</td>
                            <td>${detail.integrator_share_amount} EUR</td>
                            <td>${detail.operator_share_amount} EUR</td>
                            <td>
                                <span class="badge badge-${detail.admin_paid ? 'success' : 'warning'}">
                                    ${detail.admin_paid ? 'Payé' : 'En attente'}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="markAsPaid(${detail.id}, 'admin')">
                                    Marquer payé
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }
        });
}

function loadHierarchicalTransactions() {
    $.get('/api/v1/transaction-hierarchy/hierarchical-transactions')
        .done(function(response) {
            if (response.success) {
                const tbody = $('#hierarchicalTable tbody');
                tbody.empty();
                
                response.data.data.forEach(function(transaction) {
                    const row = `
                        <tr>
                            <td>
                                <span class="badge badge-info">
                                    ${transaction.transaction_type === 'admin_integrator' ? 'Admin ↔ Intégrateur' : 'Intégrateur ↔ Opérateur'}
                                </span>
                            </td>
                            <td>${transaction.payer ? transaction.payer.name : 'N/A'}</td>
                            <td>${transaction.payee ? transaction.payee.name : 'N/A'}</td>
                            <td>${transaction.amount} EUR</td>
                            <td>${transaction.fees_amount} EUR</td>
                            <td>${transaction.net_amount} EUR</td>
                            <td>
                                <span class="badge badge-${transaction.status === 'completed' ? 'success' : 'warning'}">
                                    ${transaction.status === 'completed' ? 'Terminé' : 'En attente'}
                                </span>
                            </td>
                            <td>${new Date(transaction.created_at).toLocaleDateString()}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }
        });
}

function loadUserBalances() {
    $.get('/api/v1/transaction-hierarchy/user-balances')
        .done(function(response) {
            if (response.success) {
                const tbody = $('#balancesTable tbody');
                tbody.empty();
                
                response.data.data.forEach(function(user) {
                    const role = user.roles && user.roles.length > 0 ? user.roles[0].name : 'N/A';
                    const row = `
                        <tr>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td>
                                <span class="badge badge-${role === 'admin' ? 'danger' : role === 'integrator' ? 'primary' : 'success'}">
                                    ${role}
                                </span>
                            </td>
                            <td>${user.balance} ${user.currency}</td>
                            <td>${user.creator ? user.creator.name : 'Système'}</td>
                            <td>${new Date(user.created_at).toLocaleDateString()}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }
        });
}

function loadExampleCalculation() {
    $.get('/api/v1/transaction-hierarchy/example-calculation')
        .done(function(response) {
            if (response.success) {
                const data = response.data;
                const html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Exemple de Transaction</h6>
                            <ul>
                                <li>Opérateur: ${data.example.operator_name}</li>
                                <li>Intégrateur: ${data.example.integrator_name}</li>
                                <li>Admin: ${data.example.admin_name}</li>
                                <li>Montant: ${data.example.amount} EUR</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Calculs</h6>
                            <ul>
                                <li>Frais de transaction: ${data.calculations.transaction_fees} EUR</li>
                                <li>Montant net: ${data.calculations.net_amount} EUR</li>
                                <li>Part Admin (10%): ${data.calculations.admin_share} EUR</li>
                                <li>Part Intégrateur (5%): ${data.calculations.integrator_share} EUR</li>
                                <li>Part Opérateur: ${data.calculations.operator_share} EUR</li>
                            </ul>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Transactions Hiérarchiques</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Payeur</th>
                                            <th>Bénéficiaire</th>
                                            <th>Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Admin ↔ Intégrateur</td>
                                            <td>${data.hierarchical_transactions.admin_integrator.payer}</td>
                                            <td>${data.hierarchical_transactions.admin_integrator.payee}</td>
                                            <td>${data.hierarchical_transactions.admin_integrator.amount} EUR</td>
                                        </tr>
                                        <tr>
                                            <td>Intégrateur ↔ Opérateur</td>
                                            <td>${data.hierarchical_transactions.integrator_operator.payer}</td>
                                            <td>${data.hierarchical_transactions.integrator_operator.payee}</td>
                                            <td>${data.hierarchical_transactions.integrator_operator.amount} EUR</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
                $('#exampleModalBody').html(html);
                const exampleModalEl = document.getElementById('exampleModal');
                const exampleModal = bootstrap.Modal.getOrCreateInstance(exampleModalEl);
                exampleModal.show();
            }
        });
}

function markAsPaid(transactionDetailId, shareType) {
    if (confirm('Êtes-vous sûr de vouloir marquer cette part comme payée ?')) {
        $.post('/api/v1/transaction-hierarchy/mark-share-paid', {
            transaction_detail_id: transactionDetailId,
            share_type: shareType,
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            if (response.success) {
                toastr.success('Part marquée comme payée avec succès');
                loadAdminShares();
            } else {
                toastr.error('Erreur: ' + response.message);
            }
        });
    }
}

function applyFilters() {
    const role = $('#filterRole').val();
    const dateFrom = $('#filterDateFrom').val();
    const dateTo = $('#filterDateTo').val();
    
    let url = '/api/v1/transaction-hierarchy/admin-shares?';
    const params = [];
    
    if (role) params.push('role=' + role);
    if (dateFrom) params.push('date_from=' + dateFrom);
    if (dateTo) params.push('date_to=' + dateTo);
    
    url += params.join('&');
    
    $.get(url)
        .done(function(response) {
            if (response.success) {
                loadAdminShares();
            }
        });
}
</script>
@endsection
