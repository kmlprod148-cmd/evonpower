@extends('layouts.app')

@section('title', 'Transactions Détaillées - Système Hiérarchique')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exchange-alt"></i>
                        Transactions Détaillées - Système Hiérarchique
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                        <button type="button" class="btn btn-info" onclick="exportData()">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtres avancés -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <select class="form-control" id="filterUserRole">
                                <option value="">Tous les rôles</option>
                                <option value="admin">Admin</option>
                                <option value="integrator">Intégrateur</option>
                                <option value="partner">Opérateur</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="filterTransactionType">
                                <option value="">Tous les types</option>
                                <option value="admin_integrator">Admin ↔ Intégrateur</option>
                                <option value="integrator_operator">Intégrateur ↔ Opérateur</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="filterStatus">
                                <option value="">Tous les statuts</option>
                                <option value="pending">En attente</option>
                                <option value="completed">Terminé</option>
                                <option value="failed">Échoué</option>
                                <option value="cancelled">Annulé</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" id="filterDateFrom" placeholder="Date de début">
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" id="filterDateTo" placeholder="Date de fin">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary btn-block" onclick="applyFilters()">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                        </div>
                    </div>

                    <!-- Statistiques globales -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-exchange-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Transactions</span>
                                    <span class="info-box-number" id="totalTransactions">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Montant Total</span>
                                    <span class="info-box-number" id="totalAmount">0 EUR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Frais Totaux</span>
                                    <span class="info-box-number" id="totalFees">0 EUR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Utilisateurs Actifs</span>
                                    <span class="info-box-number" id="activeUsers">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglets pour les deux tableaux -->
                    <ul class="nav nav-tabs" id="transactionTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="hierarchical-tab" data-bs-toggle="tab" href="#hierarchical" role="tab">
                                <i class="fas fa-sitemap"></i> Transactions Hiérarchiques
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="detailed-tab" data-bs-toggle="tab" href="#detailed" role="tab">
                                <i class="fas fa-list-alt"></i> Détails Complets
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="transactionTabContent">
                        <!-- Tableau 1: Transactions Hiérarchiques -->
                        <div class="tab-pane fade show active" id="hierarchical" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="hierarchicalTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Payeur</th>
                                            <th>Bénéficiaire</th>
                                            <th>Montant</th>
                                            <th>Frais</th>
                                            <th>Montant Net</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Les données seront chargées via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div id="hierarchicalPagination"></div>
                                <div>
                                    <small class="text-muted">
                                        Affichage de <span id="hierarchicalCount">0</span> transactions
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Tableau 2: Détails Complets -->
                        <div class="tab-pane fade" id="detailed" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="detailedTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>Business Profile</th>
                                            <th>Admin</th>
                                            <th>Intégrateur</th>
                                            <th>Opérateur</th>
                                            <th>Montant Original</th>
                                            <th>Frais Admin</th>
                                            <th>Frais Intégrateur</th>
                                            <th>Total Frais</th>
                                            <th>Part Admin</th>
                                            <th>Part Intégrateur</th>
                                            <th>Part Opérateur</th>
                                            <th>Statut Paiement</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Les données seront chargées via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div id="detailedPagination"></div>
                                <div>
                                    <small class="text-muted">
                                        Affichage de <span id="detailedCount">0</span> détails
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les détails d'une transaction -->
<div class="modal fade" id="transactionDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la Transaction</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="transactionDetailModalBody">
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
let currentPage = 1;
let currentTab = 'hierarchical';

$(document).ready(function() {
    loadStats();
    loadHierarchicalTransactions();
    
    // Charger les données des onglets
    $('#detailed-tab').on('click', function() {
        currentTab = 'detailed';
        loadDetailedTransactions();
    });
    
    $('#hierarchical-tab').on('click', function() {
        currentTab = 'hierarchical';
        loadHierarchicalTransactions();
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
                $('#activeUsers').text(data.hierarchical_transactions.admin_integrator + data.hierarchical_transactions.integrator_operator);
            }
        });
}

function loadHierarchicalTransactions(page = 1) {
    const params = getFilterParams();
    params.page = page;
    
    $.get('/api/v1/transaction-hierarchy/hierarchical-transactions', params)
        .done(function(response) {
            if (response.success) {
                const tbody = $('#hierarchicalTable tbody');
                tbody.empty();
                
                response.data.data.forEach(function(transaction) {
                    const row = `
                        <tr>
                            <td>${transaction.id}</td>
                            <td>
                                <span class="badge badge-${transaction.transaction_type === 'admin_integrator' ? 'danger' : 'primary'}">
                                    ${transaction.transaction_type === 'admin_integrator' ? 'Admin ↔ Intégrateur' : 'Intégrateur ↔ Opérateur'}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2">
                                        <i class="fas fa-user text-muted"></i>
                                    </div>
                                    <div>
                                        <div class="fw-medium">${transaction.payer ? transaction.payer.name : 'N/A'}</div>
                                        <small class="text-muted">${transaction.payer ? transaction.payer.email : ''}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2">
                                        <i class="fas fa-user text-muted"></i>
                                    </div>
                                    <div>
                                        <div class="fw-medium">${transaction.payee ? transaction.payee.name : 'N/A'}</div>
                                        <small class="text-muted">${transaction.payee ? transaction.payee.email : ''}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">${transaction.amount} EUR</td>
                            <td class="text-end">${transaction.fees_amount} EUR</td>
                            <td class="text-end fw-bold">${transaction.net_amount} EUR</td>
                            <td>
                                <span class="badge badge-${getStatusBadgeClass(transaction.status)}">
                                    ${getStatusLabel(transaction.status)}
                                </span>
                            </td>
                            <td>${new Date(transaction.created_at).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="showTransactionDetails(${transaction.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
                
                $('#hierarchicalCount').text(response.data.total);
                updatePagination('hierarchicalPagination', response.data, page);
            }
        });
}

function loadDetailedTransactions(page = 1) {
    const params = getFilterParams();
    params.page = page;
    
    $.get('/api/v1/transaction-hierarchy/admin-shares', params)
        .done(function(response) {
            if (response.success) {
                const tbody = $('#detailedTable tbody');
                tbody.empty();
                
                response.data.data.forEach(function(detail) {
                    const businessProfileName = detail.calculation_details && detail.calculation_details.business_profile_name 
                        ? detail.calculation_details.business_profile_name 
                        : 'N/A';
                    
                    const adminFees = detail.calculation_details && detail.calculation_details.transaction_fees 
                        ? detail.calculation_details.transaction_fees.admin_fees || { total: 0 }
                        : { total: 0 };
                    
                    const integratorFees = detail.calculation_details && detail.calculation_details.transaction_fees 
                        ? detail.calculation_details.transaction_fees.integrator_fees || { total: 0 }
                        : { total: 0 };
                    
                    const row = `
                        <tr>
                            <td>${detail.transaction_id}</td>
                            <td>
                                <span class="badge badge-info" title="Business Profile utilisé">
                                    ${businessProfileName}
                                </span>
                            </td>
                            <td>${detail.admin_creator ? detail.admin_creator.name : 'N/A'}</td>
                            <td>${detail.integrator_creator ? detail.integrator_creator.name : 'N/A'}</td>
                            <td>${detail.operator ? detail.operator.name : 'N/A'}</td>
                            <td class="text-end">${detail.transaction ? detail.transaction.amount : 0} EUR</td>
                            <td class="text-end text-danger">${adminFees.total} EUR</td>
                            <td class="text-end text-warning">${integratorFees.total} EUR</td>
                            <td class="text-end fw-bold">${detail.transaction_fee_total} EUR</td>
                            <td class="text-end text-danger">${detail.admin_share_amount} EUR</td>
                            <td class="text-end text-warning">${detail.integrator_share_amount} EUR</td>
                            <td class="text-end text-success">${detail.operator_share_amount} EUR</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="badge badge-${detail.admin_paid ? 'success' : 'warning'} mb-1">
                                        Admin: ${detail.admin_paid ? 'Payé' : 'En attente'}
                                    </span>
                                    <span class="badge badge-${detail.integrator_paid ? 'success' : 'warning'} mb-1">
                                        Intégrateur: ${detail.integrator_paid ? 'Payé' : 'En attente'}
                                    </span>
                                    <span class="badge badge-${detail.operator_paid ? 'success' : 'warning'}">
                                        Opérateur: ${detail.operator_paid ? 'Payé' : 'En attente'}
                                    </span>
                                </div>
                            </td>
                            <td>${new Date(detail.created_at).toLocaleDateString()}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
                
                $('#detailedCount').text(response.data.total);
                updatePagination('detailedPagination', response.data, page);
            }
        });
}

function getFilterParams() {
    return {
        role: $('#filterUserRole').val(),
        type: $('#filterTransactionType').val(),
        status: $('#filterStatus').val(),
        date_from: $('#filterDateFrom').val(),
        date_to: $('#filterDateTo').val(),
    };
}

function applyFilters() {
    if (currentTab === 'hierarchical') {
        loadHierarchicalTransactions(1);
    } else {
        loadDetailedTransactions(1);
    }
    loadStats();
}

function updatePagination(containerId, paginationData, currentPage) {
    const container = $('#' + containerId);
    container.empty();
    
    if (paginationData.last_page > 1) {
        let pagination = '<nav><ul class="pagination pagination-sm">';
        
        // Page précédente
        if (paginationData.current_page > 1) {
            pagination += `<li class="page-item"><a class="page-link" href="#" onclick="loadPage(${paginationData.current_page - 1})">Précédent</a></li>`;
        }
        
        // Pages
        for (let i = 1; i <= paginationData.last_page; i++) {
            const activeClass = i === paginationData.current_page ? 'active' : '';
            pagination += `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="loadPage(${i})">${i}</a></li>`;
        }
        
        // Page suivante
        if (paginationData.current_page < paginationData.last_page) {
            pagination += `<li class="page-item"><a class="page-link" href="#" onclick="loadPage(${paginationData.current_page + 1})">Suivant</a></li>`;
        }
        
        pagination += '</ul></nav>';
        container.html(pagination);
    }
}

function loadPage(page) {
    if (currentTab === 'hierarchical') {
        loadHierarchicalTransactions(page);
    } else {
        loadDetailedTransactions(page);
    }
}

function showTransactionDetails(transactionId) {
    $.get(`/api/v1/transaction-hierarchy/summary/${transactionId}`)
        .done(function(response) {
            if (response.success) {
                const data = response.data;
                const html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informations Générales</h6>
                            <table class="table table-sm">
                                <tr><td>ID Transaction:</td><td>${data.transaction_id}</td></tr>
                                <tr><td>Montant Original:</td><td>${data.original_amount} EUR</td></tr>
                                <tr><td>Frais Totaux:</td><td>${data.transaction_fees.total} EUR</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Répartition des Parts</h6>
                            <table class="table table-sm">
                                <tr><td>Part Admin:</td><td>${data.shares.admin.amount} EUR (${data.shares.admin.percentage}%)</td></tr>
                                <tr><td>Part Intégrateur:</td><td>${data.shares.integrator.amount} EUR (${data.shares.integrator.percentage}%)</td></tr>
                                <tr><td>Part Opérateur:</td><td>${data.shares.operator.amount} EUR</td></tr>
                            </table>
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
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.hierarchical_transactions.map(ht => `
                                            <tr>
                                                <td>${ht.type}</td>
                                                <td>${ht.payer}</td>
                                                <td>${ht.payee}</td>
                                                <td>${ht.amount} EUR</td>
                                                <td>${ht.status}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
                $('#transactionDetailModalBody').html(html);
                const detailModalEl = document.getElementById('transactionDetailModal');
                const detailModal = bootstrap.Modal.getOrCreateInstance(detailModalEl);
                detailModal.show();
            }
        });
}

function getStatusBadgeClass(status) {
    const classes = {
        'pending': 'warning',
        'completed': 'success',
        'failed': 'danger',
        'cancelled': 'secondary'
    };
    return classes[status] || 'secondary';
}

function getStatusLabel(status) {
    const labels = {
        'pending': 'En attente',
        'completed': 'Terminé',
        'failed': 'Échoué',
        'cancelled': 'Annulé'
    };
    return labels[status] || status;
}

function refreshData() {
    loadStats();
    if (currentTab === 'hierarchical') {
        loadHierarchicalTransactions(currentPage);
    } else {
        loadDetailedTransactions(currentPage);
    }
    toastr.success('Données actualisées');
}

function exportData() {
    const params = getFilterParams();
    const url = currentTab === 'hierarchical' 
        ? '/api/v1/transaction-hierarchy/hierarchical-transactions'
        : '/api/v1/transaction-hierarchy/admin-shares';
    
    const queryString = new URLSearchParams(params).toString();
    window.open(`${url}?${queryString}&export=true`, '_blank');
}
</script>
@endsection
