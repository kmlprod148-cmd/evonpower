@extends('layouts.app')

@section('title', 'Configurations Admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cogs"></i> Configurations Admin
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="fas fa-upload"></i> Importer
                        </button>
                        <button type="button" class="btn btn-warning" onclick="resetConfigurations()">
                            <i class="fas fa-undo"></i> Réinitialiser
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistiques -->
                    <div class="row mb-4" id="statsContainer">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-language"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Langues</span>
                                    <span class="info-box-number" id="languagesCount">4</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-coins"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Devises</span>
                                    <span class="info-box-number" id="currenciesCount">4</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-cogs"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Paramètres</span>
                                    <span class="info-box-number" id="settingsCount">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-percentage"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Complétion</span>
                                    <span class="info-box-number" id="completionPercentage">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglets des catégories -->
                    <ul class="nav nav-tabs" id="configTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="system-tab" data-bs-toggle="tab" href="#system" role="tab">
                                <i class="fas fa-server"></i> Système
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="business-tab" data-bs-toggle="tab" href="#business" role="tab">
                                <i class="fas fa-building"></i> Business
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="financial-tab" data-bs-toggle="tab" href="#financial" role="tab">
                                <i class="fas fa-money-bill-wave"></i> Financier
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="charging-points-tab" data-bs-toggle="tab" href="#charging-points" role="tab">
                                <i class="fas fa-charging-station"></i> Bornes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="notifications-tab" data-bs-toggle="tab" href="#notifications" role="tab">
                                <i class="fas fa-bell"></i> Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="security-tab" data-bs-toggle="tab" href="#security" role="tab">
                                <i class="fas fa-shield-alt"></i> Sécurité
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="api-tab" data-bs-toggle="tab" href="#api" role="tab">
                                <i class="fas fa-code"></i> API
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="backup-tab" data-bs-toggle="tab" href="#backup" role="tab">
                                <i class="fas fa-database"></i> Sauvegarde
                            </a>
                        </li>
                    </ul>

                    <!-- Contenu des onglets -->
                    <div class="tab-content" id="configTabsContent">
                        <!-- Onglet Système -->
                        <div class="tab-pane fade show active" id="system" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-12">
                                    <form id="systemForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="app_name">Nom de l'application</label>
                                                    <input type="text" class="form-control" id="app_name" name="app_name" value="EVON">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="app_url">URL de l'application</label>
                                                    <input type="url" class="form-control" id="app_url" name="app_url" value="http://localhost">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="timezone">Fuseau horaire</label>
                                                    <select class="form-control" id="timezone" name="timezone">
                                                        <option value="Africa/Casablanca">Casablanca (UTC+1)</option>
                                                        <option value="Europe/Paris">Paris (UTC+1)</option>
                                                        <option value="UTC">UTC (UTC+0)</option>
                                                        <option value="America/New_York">New York (UTC-5)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode">
                                                        <label class="form-check-label" for="maintenance_mode">
                                                            Mode maintenance
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Sauvegarder
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet Business -->
                        <div class="tab-pane fade" id="business" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-12">
                                    <form id="businessForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="company_name">Nom de l'entreprise</label>
                                                    <input type="text" class="form-control" id="company_name" name="company_name" value="EVON">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="company_email">Email de l'entreprise</label>
                                                    <input type="email" class="form-control" id="company_email" name="company_email" value="contact@evon.ma">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="company_phone">Téléphone</label>
                                                    <input type="tel" class="form-control" id="company_phone" name="company_phone" value="+212 5XX XXX XXX">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="vat_number">Numéro de TVA</label>
                                                    <input type="text" class="form-control" id="vat_number" name="vat_number">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="company_address">Adresse</label>
                                            <textarea class="form-control" id="company_address" name="company_address" rows="3"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Sauvegarder
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet Financier -->
                        <div class="tab-pane fade" id="financial" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-12">
                                    <form id="financialForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="default_currency">Devise par défaut</label>
                                                    <select class="form-control" id="default_currency" name="default_currency">
                                                        <option value="EUR">Euro (€)</option>
                                                        <option value="USD">Dollar américain ($)</option>
                                                        <option value="GBP">Livre sterling (£)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="vat_rate">Taux de TVA (%)</label>
                                                    <input type="number" class="form-control" id="vat_rate" name="vat_rate" value="20" min="0" max="100" step="0.1">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="commission_rate">Taux de commission (%)</label>
                                                    <input type="number" class="form-control" id="commission_rate" name="commission_rate" value="5" min="0" max="50" step="0.1">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="minimum_transaction_amount">Montant minimum</label>
                                                    <input type="number" class="form-control" id="minimum_transaction_amount" name="minimum_transaction_amount" value="1" min="0" step="0.01">
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Sauvegarder
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Autres onglets... -->
                        <div class="tab-pane fade" id="charging-points" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-12">
                                    <p class="text-muted">Configuration des bornes de charge...</p>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="notifications" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-12">
                                    <p class="text-muted">Configuration des notifications...</p>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-12">
                                    <p class="text-muted">Configuration de la sécurité...</p>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="api" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-12">
                                    <p class="text-muted">Configuration de l'API...</p>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="backup" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-12">
                                    <p class="text-muted">Configuration des sauvegardes...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Export -->
<div class="modal fade" id="exportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exporter les configurations</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Cette action va télécharger un fichier JSON contenant toutes les configurations actuelles.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="exportConfigurations()">Exporter</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importer les configurations</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="importForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="configFile">Fichier de configuration (JSON)</label>
                        <input type="file" class="form-control-file" id="configFile" name="file" accept=".json">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="overwrite" name="overwrite">
                        <label class="form-check-label" for="overwrite">
                            Écraser les paramètres existants
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="importConfigurations()">Importer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Charger les configurations au démarrage
    loadConfigurations();
    
    // Gestion des formulaires
    $('#systemForm, #businessForm, #financialForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const category = this.id.replace('Form', '');
        
        saveConfiguration(category, formData);
    });
});

function loadConfigurations() {
    $.ajax({
        url: '{{ route("admin.configurations.index") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateStats(response.data.stats);
                populateForms(response.data.configurations);
            }
        },
        error: function(xhr) {
            console.error('Erreur lors du chargement des configurations:', xhr);
        }
    });
}

function updateStats(stats) {
    let totalSettings = 0;
    let configuredSettings = 0;
    
    Object.values(stats).forEach(category => {
        totalSettings += category.total;
        configuredSettings += category.configured;
    });
    
    $('#settingsCount').text(configuredSettings);
    $('#completionPercentage').text(Math.round((configuredSettings / totalSettings) * 100) + '%');
}

function populateForms(configurations) {
    // Remplir les formulaires avec les données
    Object.keys(configurations).forEach(category => {
        const form = document.getElementById(category + 'Form');
        if (form) {
            Object.keys(configurations[category]).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === 'checkbox') {
                        input.checked = configurations[category][key].value;
                    } else {
                        input.value = configurations[category][key].value;
                    }
                }
            });
        }
    });
}

function saveConfiguration(category, formData) {
    const settings = {};
    for (let [key, value] of formData.entries()) {
        settings[key] = value;
    }
    
    $.ajax({
        url: '{{ route("admin.configurations.update-multiple") }}',
        method: 'PUT',
        data: {
            settings: { [category]: settings }
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Configuration sauvegardée avec succès');
                loadConfigurations(); // Recharger les données
            } else {
                toastr.error('Erreur lors de la sauvegarde');
            }
        },
        error: function(xhr) {
            console.error('Erreur lors de la sauvegarde:', xhr);
            toastr.error('Erreur lors de la sauvegarde');
        }
    });
}

function exportConfigurations() {
    window.location.href = '{{ route("admin.configurations.export") }}';
    const exportModalEl = document.getElementById('exportModal');
    const exportModal = bootstrap.Modal.getInstance(exportModalEl);
    if (exportModal) exportModal.hide();
}

function importConfigurations() {
    const formData = new FormData(document.getElementById('importForm'));
    
    $.ajax({
        url: '{{ route("admin.configurations.import") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Configurations importées avec succès');
                const importModalEl = document.getElementById('importModal');
                const importModal = bootstrap.Modal.getInstance(importModalEl);
                if (importModal) importModal.hide();
                loadConfigurations();
            } else {
                toastr.error('Erreur lors de l\'import');
            }
        },
        error: function(xhr) {
            console.error('Erreur lors de l\'import:', xhr);
            toastr.error('Erreur lors de l\'import');
        }
    });
}

function resetConfigurations() {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser toutes les configurations ?')) {
        $.ajax({
            url: '{{ route("admin.configurations.reset") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Configurations réinitialisées');
                    loadConfigurations();
                } else {
                    toastr.error('Erreur lors de la réinitialisation');
                }
            },
            error: function(xhr) {
                console.error('Erreur lors de la réinitialisation:', xhr);
                toastr.error('Erreur lors de la réinitialisation');
            }
        });
    }
}
</script>
@endsection
