@extends('layouts.app')

@section('title', 'Gestion des Business Profiles - Intégrateur')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cogs"></i>
                        Gestion des Business Profiles de mes Opérateurs
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistiques -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Opérateurs</span>
                                    <span class="info-box-number" id="totalOperators">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Avec Business Profile</span>
                                    <span class="info-box-number" id="operatorsWithBP">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Sans Business Profile</span>
                                    <span class="info-box-number" id="operatorsWithoutBP">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-primary">
                                <span class="info-box-icon"><i class="fas fa-chart-pie"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Business Profiles Utilisés</span>
                                    <span class="info-box-number" id="businessProfilesUsed">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglets -->
                    <ul class="nav nav-tabs" id="managementTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="operators-tab" data-bs-toggle="tab" href="#operators" role="tab">
                                <i class="fas fa-users"></i> Mes Opérateurs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="business-profiles-tab" data-bs-toggle="tab" href="#business-profiles" role="tab">
                                <i class="fas fa-cogs"></i> Business Profiles Disponibles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#history" role="tab">
                                <i class="fas fa-history"></i> Historique des Applications
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="managementTabContent">
                        <!-- Onglet 1: Mes Opérateurs -->
                        <div class="tab-pane fade show active" id="operators" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="operatorsTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Opérateur</th>
                                            <th>Email</th>
                                            <th>Business Profile Actuel</th>
                                            <th>Créateur du BP</th>
                                            <th>Charging Points</th>
                                            <th>Date de Création</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Les données seront chargées via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Onglet 2: Business Profiles Disponibles -->
                        <div class="tab-pane fade" id="business-profiles" role="tabpanel">
                            <div class="row">
                                <!-- Mes Business Profiles -->
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">
                                                <i class="fas fa-user-cog"></i> Mes Business Profiles
                                            </h5>
                                        </div>
                                        <div class="card-body" id="myBusinessProfiles">
                                            <!-- Les données seront chargées via AJAX -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Business Profiles Publics -->
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">
                                                <i class="fas fa-globe"></i> Business Profiles Publics
                                            </h5>
                                        </div>
                                        <div class="card-body" id="publicBusinessProfiles">
                                            <!-- Les données seront chargées via AJAX -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Business Profiles de l'Admin -->
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">
                                                <i class="fas fa-crown"></i> Business Profiles Admin
                                            </h5>
                                        </div>
                                        <div class="card-body" id="adminBusinessProfiles">
                                            <!-- Les données seront chargées via AJAX -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet 3: Historique -->
                        <div class="tab-pane fade" id="history" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="historyTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Opérateur</th>
                                            <th>Business Profile</th>
                                            <th>Charging Points Affectés</th>
                                            <th>Statut</th>
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

<!-- Modal pour appliquer un business profile -->
<div class="modal fade" id="applyBusinessProfileModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Appliquer un Business Profile</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="applyBusinessProfileForm">
                    <input type="hidden" id="operatorId" name="operator_id">
                    
                    <div class="form-group">
                        <label>Opérateur</label>
                        <input type="text" class="form-control" id="operatorName" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Business Profile à appliquer</label>
                        <select class="form-control" id="businessProfileId" name="business_profile_id" required>
                            <option value="">Sélectionner un business profile</option>
                        </select>
                    </div>
                    
                    <div id="validationResults"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-info" onclick="validateApplication()">
                    <i class="fas fa-check"></i> Valider
                </button>
                <button type="button" class="btn btn-primary" onclick="applyBusinessProfile()" id="applyButton" disabled>
                    <i class="fas fa-save"></i> Appliquer
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentOperatorId = null;
let currentBusinessProfileId = null;

$(document).ready(function() {
    loadStats();
    loadOperators();
    
    // Charger les données des onglets
    $('#business-profiles-tab').on('click', function() {
        loadBusinessProfiles();
    });
    
    $('#history-tab').on('click', function() {
        loadHistory();
    });
});

function loadStats() {
    $.get('/api/v1/integrator/business-profiles/stats')
        .done(function(response) {
            if (response.success) {
                const data = response.data;
                $('#totalOperators').text(data.total_operators);
                $('#operatorsWithBP').text(data.operators_with_business_profile);
                $('#operatorsWithoutBP').text(data.operators_without_business_profile);
                $('#businessProfilesUsed').text(data.business_profiles_used.length);
            }
        });
}

function loadOperators() {
    $.get('/api/v1/integrator/business-profiles/my-operators')
        .done(function(response) {
            if (response.success) {
                const tbody = $('#operatorsTable tbody');
                tbody.empty();
                
                response.data.forEach(function(operator) {
                    const businessProfileInfo = operator.business_profile ? 
                        `<span class="badge badge-success">${operator.business_profile.name}</span><br><small class="text-muted">Créé par: ${operator.business_profile.creator}</small>` :
                        '<span class="badge badge-warning">Aucun</span>';
                    
                    const row = `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2">
                                        <i class="fas fa-user text-muted"></i>
                                    </div>
                                    <div>
                                        <div class="fw-medium">${operator.name}</div>
                                        <small class="text-muted">ID: ${operator.id}</small>
                                    </div>
                                </div>
                            </td>
                            <td>${operator.email}</td>
                            <td>${businessProfileInfo}</td>
                            <td>${operator.business_profile ? operator.business_profile.creator : '-'}</td>
                            <td>
                                <span class="badge badge-info">0</span>
                                <small class="text-muted">charging points</small>
                            </td>
                            <td>${new Date(operator.created_at).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="openApplyModal(${operator.id}, '${operator.name}')">
                                    <i class="fas fa-cogs"></i> Appliquer BP
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }
        });
}

function loadBusinessProfiles() {
    $.get('/api/v1/integrator/business-profiles/available')
        .done(function(response) {
            if (response.success) {
                const data = response.data;
                
                // Mes business profiles
                const myBPHtml = data.my_business_profiles.map(bp => `
                    <div class="business-profile-card mb-3 p-3 border rounded">
                        <h6 class="mb-2">${bp.name}</h6>
                        <p class="text-muted small mb-2">${bp.description || 'Aucune description'}</p>
                        <div class="row text-center">
                            <div class="col-6">
                                <small class="text-muted">Frais Transaction</small>
                                <div class="fw-bold">${bp.transaction_fee_amount}${bp.transaction_fee_type === 'percentage' ? '%' : ' EUR'}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Part Admin</small>
                                <div class="fw-bold">${bp.admin_fee_percentage}%</div>
                            </div>
                        </div>
                    </div>
                `).join('');
                $('#myBusinessProfiles').html(myBPHtml || '<p class="text-muted">Aucun business profile créé</p>');
                
                // Business profiles publics
                const publicBPHtml = data.public_business_profiles.map(bp => `
                    <div class="business-profile-card mb-3 p-3 border rounded">
                        <h6 class="mb-2">${bp.name}</h6>
                        <p class="text-muted small mb-2">${bp.description || 'Aucune description'}</p>
                        <div class="row text-center">
                            <div class="col-6">
                                <small class="text-muted">Frais Transaction</small>
                                <div class="fw-bold">${bp.transaction_fee_amount}${bp.transaction_fee_type === 'percentage' ? '%' : ' EUR'}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Part Admin</small>
                                <div class="fw-bold">${bp.admin_fee_percentage}%</div>
                            </div>
                        </div>
                    </div>
                `).join('');
                $('#publicBusinessProfiles').html(publicBPHtml || '<p class="text-muted">Aucun business profile public</p>');
                
                // Business profiles admin
                const adminBPHtml = data.admin_business_profiles.map(bp => `
                    <div class="business-profile-card mb-3 p-3 border rounded">
                        <h6 class="mb-2">${bp.name}</h6>
                        <p class="text-muted small mb-2">${bp.description || 'Aucune description'}</p>
                        <div class="row text-center">
                            <div class="col-6">
                                <small class="text-muted">Frais Transaction</small>
                                <div class="fw-bold">${bp.transaction_fee_amount}${bp.transaction_fee_type === 'percentage' ? '%' : ' EUR'}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Part Admin</small>
                                <div class="fw-bold">${bp.admin_fee_percentage}%</div>
                            </div>
                        </div>
                    </div>
                `).join('');
                $('#adminBusinessProfiles').html(adminBPHtml || '<p class="text-muted">Aucun business profile admin</p>');
            }
        });
}

function loadHistory() {
    $.get('/api/v1/integrator/business-profiles/application-history')
        .done(function(response) {
            if (response.success) {
                const tbody = $('#historyTable tbody');
                tbody.empty();
                
                response.data.forEach(function(application) {
                    const row = `
                        <tr>
                            <td>${new Date(application.applied_at).toLocaleDateString()}</td>
                            <td>${application.operator.name}</td>
                            <td>
                                <span class="badge badge-info">${application.business_profile.name}</span>
                            </td>
                            <td>${application.application_data.charging_points_updated ? application.application_data.charging_points_updated.length : 0}</td>
                            <td>
                                <span class="badge badge-success">Appliqué</span>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }
        });
}

function openApplyModal(operatorId, operatorName) {
    currentOperatorId = operatorId;
    $('#operatorId').val(operatorId);
    $('#operatorName').val(operatorName);
    
    // Charger les business profiles disponibles
    $.get('/api/v1/integrator/business-profiles/available')
        .done(function(response) {
            if (response.success) {
                const select = $('#businessProfileId');
                select.empty().append('<option value="">Sélectionner un business profile</option>');
                
                const data = response.data;
                const allProfiles = [
                    ...data.my_business_profiles.map(bp => ({...bp, category: 'Mes BP'})),
                    ...data.public_business_profiles.map(bp => ({...bp, category: 'Public'})),
                    ...data.admin_business_profiles.map(bp => ({...bp, category: 'Admin'}))
                ];
                
                allProfiles.forEach(bp => {
                    select.append(`<option value="${bp.id}" data-category="${bp.category}">${bp.name} (${bp.category})</option>`);
                });
            }
        });
    
    const applyModalEl = document.getElementById('applyBusinessProfileModal');
    const applyModal = bootstrap.Modal.getOrCreateInstance(applyModalEl);
    applyModal.show();
}

function validateApplication() {
    const operatorId = $('#operatorId').val();
    const businessProfileId = $('#businessProfileId').val();
    
    if (!operatorId || !businessProfileId) {
        toastr.error('Veuillez sélectionner un opérateur et un business profile');
        return;
    }
    
    $.post('/api/v1/integrator/business-profiles/validate', {
        operator_id: operatorId,
        business_profile_id: businessProfileId
    })
    .done(function(response) {
        if (response.success) {
            const validation = response.validation;
            let html = '<div class="alert alert-info"><h6>Résultat de la validation :</h6>';
            
            if (validation.valid) {
                html += '<div class="text-success"><i class="fas fa-check"></i> Application autorisée</div>';
                $('#applyButton').prop('disabled', false);
            } else {
                html += '<div class="text-danger"><i class="fas fa-times"></i> Application refusée</div>';
                $('#applyButton').prop('disabled', true);
            }
            
            if (validation.errors.length > 0) {
                html += '<div class="mt-2"><strong>Erreurs :</strong><ul>';
                validation.errors.forEach(error => {
                    html += `<li class="text-danger">${error}</li>`;
                });
                html += '</ul></div>';
            }
            
            if (validation.warnings.length > 0) {
                html += '<div class="mt-2"><strong>Avertissements :</strong><ul>';
                validation.warnings.forEach(warning => {
                    html += `<li class="text-warning">${warning}</li>`;
                });
                html += '</ul></div>';
            }
            
            if (validation.info.length > 0) {
                html += '<div class="mt-2"><strong>Informations :</strong><ul>';
                validation.info.forEach(info => {
                    html += `<li class="text-info">${info}</li>`;
                });
                html += '</ul></div>';
            }
            
            html += '</div>';
            $('#validationResults').html(html);
        }
    });
}

function applyBusinessProfile() {
    const operatorId = $('#operatorId').val();
    const businessProfileId = $('#businessProfileId').val();
    
    $.post('/api/v1/integrator/business-profiles/apply', {
        operator_id: operatorId,
        business_profile_id: businessProfileId
    })
    .done(function(response) {
        if (response.success) {
            toastr.success(response.message);
            const applyModalEl = document.getElementById('applyBusinessProfileModal');
            const applyModal = bootstrap.Modal.getInstance(applyModalEl);
            if (applyModal) applyModal.hide();
            loadOperators();
            loadStats();
        } else {
            toastr.error('Erreur: ' + response.error);
        }
    });
}

function refreshData() {
    loadStats();
    loadOperators();
    toastr.success('Données actualisées');
}
</script>
@endsection
