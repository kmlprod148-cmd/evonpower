@extends('layouts.admin')

@section('title', 'Gestion des Clés API')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-key"></i>
                        Gestion des Clés API
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" onclick="saveAllApiKeys()">
                            <i class="fas fa-save"></i> Sauvegarder tout
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="testAllApiKeys()">
                            <i class="fas fa-vial"></i> Tester toutes les clés
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Alertes -->
                    <div id="alert-container"></div>

                    <!-- Navigation par catégories -->
                    <ul class="nav nav-tabs" id="api-categories-tabs" role="tablist">
                        @foreach($apiCategories as $categoryKey => $category)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $loop->first ? 'active' : '' }}" 
                               id="{{ $categoryKey }}-tab" 
                               data-bs-toggle="tab" 
                               href="#{{ $categoryKey }}" 
                               role="tab">
                                <i class="{{ $category['icon'] }}"></i>
                                {{ $category['name'] }}
                            </a>
                        </li>
                        @endforeach
                    </ul>

                    <!-- Contenu des catégories -->
                    <div class="tab-content" id="api-categories-content">
                        @foreach($apiCategories as $categoryKey => $category)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                             id="{{ $categoryKey }}" 
                             role="tabpanel">
                            <div class="row mt-4">
                                @foreach($category['keys'] as $key)
                                    @if(isset($apiKeys[$key]))
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card api-key-card" data-key="{{ $key }}">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">
                                                    {{ $apiKeys[$key]['label'] }}
                                                    @if($apiKeys[$key]['required'])
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group">
                                                    @if($apiKeys[$key]['type'] === 'password')
                                                        <div class="input-group">
                                                            <input type="password" 
                                                                   class="form-control api-key-input" 
                                                                   id="{{ $key }}" 
                                                                   name="{{ $key }}"
                                                                   value="{{ $apiKeys[$key]['value'] ?? '' }}"
                                                                   placeholder="{{ $apiKeys[$key]['placeholder'] ?? '' }}"
                                                                   data-masked="true">
                                                            <div class="input-group-append">
                                                                <button class="btn btn-outline-secondary toggle-password" 
                                                                        type="button" 
                                                                        data-target="{{ $key }}">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @elseif($apiKeys[$key]['type'] === 'select')
                                                        <select class="form-control api-key-input" 
                                                                id="{{ $key }}" 
                                                                name="{{ $key }}">
                                                            @foreach($apiKeys[$key]['options'] as $optionValue => $optionLabel)
                                                                <option value="{{ $optionValue }}" 
                                                                        {{ ($apiKeys[$key]['value'] ?? '') == $optionValue ? 'selected' : '' }}>
                                                                    {{ $optionLabel }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <input type="{{ $apiKeys[$key]['type'] }}" 
                                                               class="form-control api-key-input" 
                                                               id="{{ $key }}" 
                                                               name="{{ $key }}"
                                                               value="{{ $apiKeys[$key]['value'] ?? '' }}"
                                                               placeholder="{{ $apiKeys[$key]['placeholder'] ?? '' }}">
                                                    @endif
                                                    
                                                    @if(!empty($apiKeys[$key]['description']))
                                                        <small class="form-text text-muted">
                                                            {{ $apiKeys[$key]['description'] }}
                                                        </small>
                                                    @endif
                                                </div>
                                                
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" 
                                                            class="btn btn-outline-primary test-api-key" 
                                                            data-key="{{ $key }}"
                                                            title="Tester la clé API">
                                                        <i class="fas fa-vial"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-outline-info mask-api-key" 
                                                            data-key="{{ $key }}"
                                                            title="Masquer/Afficher">
                                                        <i class="fas fa-eye-slash"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-outline-warning reset-api-key" 
                                                            data-key="{{ $key }}"
                                                            title="Réinitialiser">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </div>
                                                
                                                <div class="api-key-status mt-2" id="status-{{ $key }}" style="display: none;">
                                                    <span class="badge badge-secondary">Non testé</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation pour les actions sensibles -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmation requise</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirm-message"></p>
                <div class="form-group">
                    <label for="confirm-password">Mot de passe de confirmation :</label>
                    <input type="password" class="form-control" id="confirm-password" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirm-action">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de test de clé API -->
<div class="modal fade" id="testModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test de clé API</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="test-result"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.api-key-card {
    transition: all 0.3s ease;
}

.api-key-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.api-key-status .badge {
    font-size: 0.75em;
}

.api-key-status .badge-success {
    background-color: #28a745;
}

.api-key-status .badge-danger {
    background-color: #dc3545;
}

.api-key-status .badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.toggle-password {
    cursor: pointer;
}

.nav-tabs .nav-link {
    border: 1px solid transparent;
    border-top-left-radius: 0.25rem;
    border-top-right-radius: 0.25rem;
}

.nav-tabs .nav-link:hover {
    border-color: #e9ecef #e9ecef #dee2e6;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Gestion des clés API
    let currentAction = null;
    let currentKey = null;

    // Toggle password visibility
    $('.toggle-password').click(function() {
        const target = $(this).data('target');
        const input = $('#' + target);
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Test d'une clé API
    $('.test-api-key').click(function() {
        const key = $(this).data('key');
        const value = $('#' + key).val();
        
        if (!value) {
            showAlert('Veuillez saisir une valeur pour tester la clé API', 'warning');
            return;
        }

        testApiKey(key, value);
    });

    // Masquer/Afficher une clé API
    $('.mask-api-key').click(function() {
        const key = $(this).data('key');
        const input = $('#' + key);
        const icon = $(this).find('i');
        
        if (input.data('masked')) {
            // Afficher la vraie valeur
            maskApiKey(key, false);
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            // Masquer la valeur
            maskApiKey(key, true);
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Réinitialiser une clé API
    $('.reset-api-key').click(function() {
        const key = $(this).data('key');
        currentAction = 'reset';
        currentKey = key;
        
        $('#confirm-message').text('Êtes-vous sûr de vouloir réinitialiser cette clé API ? Cette action est irréversible.');
        const confirmModalEl = document.getElementById('confirmModal');
        const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
        confirmModal.show();
    });

    // Sauvegarder toutes les clés API
    window.saveAllApiKeys = function() {
        const apiKeys = {};
        $('.api-key-input').each(function() {
            const key = $(this).attr('name');
            const value = $(this).val();
            if (value) {
                apiKeys[key] = value;
            }
        });

        if (Object.keys(apiKeys).length === 0) {
            showAlert('Aucune clé API à sauvegarder', 'warning');
            return;
        }

        currentAction = 'save-all';
        $('#confirm-message').text('Êtes-vous sûr de vouloir sauvegarder toutes les clés API modifiées ?');
        const confirmModalEl = document.getElementById('confirmModal');
        const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
        confirmModal.show();
    };

    // Tester toutes les clés API
    window.testAllApiKeys = function() {
        const keys = [];
        $('.api-key-input').each(function() {
            const key = $(this).attr('name');
            const value = $(this).val();
            if (value) {
                keys.push({key: key, value: value});
            }
        });

        if (keys.length === 0) {
            showAlert('Aucune clé API à tester', 'warning');
            return;
        }

        showAlert('Test de toutes les clés API en cours...', 'info');
        
        let completed = 0;
        keys.forEach(function(item) {
            testApiKey(item.key, item.value, function() {
                completed++;
                if (completed === keys.length) {
                    showAlert('Test de toutes les clés API terminé', 'success');
                }
            });
        });
    };

    // Confirmation d'action
    $('#confirm-action').click(function() {
        const password = $('#confirm-password').val();
        
        if (!password) {
            showAlert('Veuillez saisir votre mot de passe', 'warning');
            return;
        }

        if (currentAction === 'reset') {
            resetApiKey(currentKey, password);
        } else if (currentAction === 'save-all') {
            saveAllApiKeysWithPassword(password);
        }

        const confirmModalEl = document.getElementById('confirmModal');
        const confirmModal = bootstrap.Modal.getInstance(confirmModalEl);
        if (confirmModal) confirmModal.hide();
        $('#confirm-password').val('');
    });

    // Fonctions utilitaires
    function testApiKey(key, value, callback) {
        $.ajax({
            url: '{{ route("admin.api-keys.test", ":key") }}'.replace(':key', key),
            method: 'POST',
            data: {
                value: value,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    updateApiKeyStatus(key, response.data.valid ? 'success' : 'danger', response.data.message);
                    if (callback) callback();
                } else {
                    showAlert(response.message, 'danger');
                    if (callback) callback();
                }
            },
            error: function() {
                showAlert('Erreur lors du test de la clé API', 'danger');
                if (callback) callback();
            }
        });
    }

    function maskApiKey(key, mask) {
        $.ajax({
            url: '{{ route("admin.api-keys.mask", ":key") }}'.replace(':key', key),
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const input = $('#' + key);
                    if (mask) {
                        input.val(response.data.masked_value);
                        input.data('masked', true);
                    } else {
                        input.data('masked', false);
                    }
                }
            }
        });
    }

    function resetApiKey(key, password) {
        $.ajax({
            url: '{{ route("admin.api-keys.reset", ":key") }}'.replace(':key', key),
            method: 'DELETE',
            data: {
                confirm_password: password,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#' + key).val('');
                    updateApiKeyStatus(key, 'secondary', 'Clé réinitialisée');
                    showAlert(response.message, 'success');
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function() {
                showAlert('Erreur lors de la réinitialisation de la clé API', 'danger');
            }
        });
    }

    function saveAllApiKeysWithPassword(password) {
        const apiKeys = {};
        $('.api-key-input').each(function() {
            const key = $(this).attr('name');
            const value = $(this).val();
            if (value) {
                apiKeys[key] = value;
            }
        });

        $.ajax({
            url: '{{ route("admin.api-keys.update-multiple") }}',
            method: 'PUT',
            data: {
                api_keys: apiKeys,
                confirm_password: password,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function() {
                showAlert('Erreur lors de la sauvegarde des clés API', 'danger');
            }
        });
    }

    function updateApiKeyStatus(key, status, message) {
        const statusElement = $('#status-' + key);
        const badgeClass = 'badge-' + status;
        
        statusElement.html('<span class="badge ' + badgeClass + '">' + message + '</span>');
        statusElement.show();
    }

    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-bs-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        $('#alert-container').html(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endpush
