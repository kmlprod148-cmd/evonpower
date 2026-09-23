@extends('layouts.app')

@section('title', 'Démonstration des Switches Traduits')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-toggle-on"></i> Démonstration des Switches Traduits
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Langues -->
                        <div class="col-md-6">
                            <h4><i class="fas fa-language"></i> Sélecteur de Langue</h4>
                            <div class="form-group">
                                <label>Langue actuelle</label>
                                <select class="form-control" id="language-selector">
                                    <option value="fr" data-flag="🇫🇷">Français</option>
                                    <option value="en" data-flag="🇺🇸">English</option>
                                    <option value="ar" data-flag="🇲🇦">العربية</option>
                                    <option value="es" data-flag="🇪🇸">Español</option>
                                </select>
                            </div>
                        </div>

                        <!-- Devises -->
                        <div class="col-md-6">
                            <h4><i class="fas fa-coins"></i> Sélecteur de Devise</h4>
                            <div class="form-group">
                                <label>Devise actuelle</label>
                                <select class="form-control" id="currency-selector">
                                    <option value="EUR" data-symbol="د.م.">Dirham marocain (د.م.)</option>
                                    <option value="EUR" data-symbol="€">Euro (€)</option>
                                    <option value="USD" data-symbol="$">Dollar américain ($)</option>
                                    <option value="GBP" data-symbol="£">Livre sterling (£)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Switches de Configuration -->
                    <div class="row">
                        <div class="col-md-4">
                            <h5><i class="fas fa-bell"></i> Notifications</h5>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="email-notifications" checked>
                                    <label class="custom-control-label" for="email-notifications">
                                        Notifications par email
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="sms-notifications">
                                    <label class="custom-control-label" for="sms-notifications">
                                        Notifications par SMS
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="push-notifications" checked>
                                    <label class="custom-control-label" for="push-notifications">
                                        Notifications push
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <h5><i class="fas fa-shield-alt"></i> Sécurité</h5>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="two-factor-auth">
                                    <label class="custom-control-label" for="two-factor-auth">
                                        Authentification à deux facteurs
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="maintenance-mode">
                                    <label class="custom-control-label" for="maintenance-mode">
                                        Mode maintenance
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="auto-backup" checked>
                                    <label class="custom-control-label" for="auto-backup">
                                        Sauvegarde automatique
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <h5><i class="fas fa-charging-station"></i> Bornes de Charge</h5>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="auto-start-charging" checked>
                                    <label class="custom-control-label" for="auto-start-charging">
                                        Démarrage automatique
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="session-timeout">
                                    <label class="custom-control-label" for="session-timeout">
                                        Timeout de session
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Affichage des valeurs -->
                    <div class="row">
                        <div class="col-12">
                            <h5><i class="fas fa-info-circle"></i> Valeurs Actuelles</h5>
                            <div class="alert alert-info">
                                <strong>Langue sélectionnée:</strong> <span id="current-language">Français</span><br>
                                <strong>Devise sélectionnée:</strong> <span id="current-currency">EUR (د.م.)</span><br>
                                <strong>Notifications activées:</strong> <span id="active-notifications">Email, Push</span><br>
                                <strong>Paramètres de sécurité:</strong> <span id="security-settings">Sauvegarde automatique</span>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="row">
                        <div class="col-12">
                            <button type="button" class="btn btn-primary" onclick="saveSettings()">
                                <i class="fas fa-save"></i> Sauvegarder les paramètres
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetSettings()">
                                <i class="fas fa-undo"></i> Réinitialiser
                            </button>
                            <button type="button" class="btn btn-info" onclick="exportSettings()">
                                <i class="fas fa-download"></i> Exporter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialiser les sélecteurs
    initializeSelectors();
    
    // Gestion des changements
    $('#language-selector, #currency-selector').on('change', updateDisplay);
    $('input[type="checkbox"]').on('change', updateDisplay);
    
    // Mettre à jour l'affichage initial
    updateDisplay();
});

function initializeSelectors() {
    // Simuler la langue actuelle (en réalité, récupérer depuis la session)
    $('#language-selector').val('fr');
    $('#currency-selector').val('EUR');
}

function updateDisplay() {
    const language = $('#language-selector option:selected').text();
    const currency = $('#currency-selector option:selected').text();
    
    $('#current-language').text(language);
    $('#current-currency').text(currency);
    
    // Mettre à jour les notifications actives
    const activeNotifications = [];
    if ($('#email-notifications').is(':checked')) activeNotifications.push('Email');
    if ($('#sms-notifications').is(':checked')) activeNotifications.push('SMS');
    if ($('#push-notifications').is(':checked')) activeNotifications.push('Push');
    
    $('#active-notifications').text(activeNotifications.join(', ') || 'Aucune');
    
    // Mettre à jour les paramètres de sécurité
    const securitySettings = [];
    if ($('#two-factor-auth').is(':checked')) securitySettings.push('2FA');
    if ($('#maintenance-mode').is(':checked')) securitySettings.push('Maintenance');
    if ($('#auto-backup').is(':checked')) securitySettings.push('Sauvegarde auto');
    
    $('#security-settings').text(securitySettings.join(', ') || 'Aucun');
}

function saveSettings() {
    const settings = {
        language: $('#language-selector').val(),
        currency: $('#currency-selector').val(),
        notifications: {
            email: $('#email-notifications').is(':checked'),
            sms: $('#sms-notifications').is(':checked'),
            push: $('#push-notifications').is(':checked')
        },
        security: {
            twoFactor: $('#two-factor-auth').is(':checked'),
            maintenance: $('#maintenance-mode').is(':checked'),
            autoBackup: $('#auto-backup').is(':checked')
        },
        charging: {
            autoStart: $('#auto-start-charging').is(':checked'),
            timeout: $('#session-timeout').is(':checked')
        }
    };
    
    // Ici, vous pouvez envoyer les paramètres au serveur
    console.log('Paramètres à sauvegarder:', settings);
    
    // Afficher une notification de succès
    toastr.success('Paramètres sauvegardés avec succès!');
}

function resetSettings() {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser tous les paramètres ?')) {
        // Réinitialiser les valeurs par défaut
        $('#language-selector').val('fr');
        $('#currency-selector').val('EUR');
        $('#email-notifications').prop('checked', true);
        $('#sms-notifications').prop('checked', false);
        $('#push-notifications').prop('checked', true);
        $('#two-factor-auth').prop('checked', false);
        $('#maintenance-mode').prop('checked', false);
        $('#auto-backup').prop('checked', true);
        $('#auto-start-charging').prop('checked', true);
        $('#session-timeout').prop('checked', false);
        
        updateDisplay();
        toastr.info('Paramètres réinitialisés');
    }
}

function exportSettings() {
    const settings = {
        language: $('#language-selector').val(),
        currency: $('#currency-selector').val(),
        notifications: {
            email: $('#email-notifications').is(':checked'),
            sms: $('#sms-notifications').is(':checked'),
            push: $('#push-notifications').is(':checked')
        },
        security: {
            twoFactor: $('#two-factor-auth').is(':checked'),
            maintenance: $('#maintenance-mode').is(':checked'),
            autoBackup: $('#auto-backup').is(':checked')
        },
        charging: {
            autoStart: $('#auto-start-charging').is(':checked'),
            timeout: $('#session-timeout').is(':checked')
        },
        exported_at: new Date().toISOString()
    };
    
    const dataStr = JSON.stringify(settings, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'settings-demo-' + new Date().toISOString().split('T')[0] + '.json';
    link.click();
    
    URL.revokeObjectURL(url);
    toastr.success('Paramètres exportés!');
}
</script>
@endsection