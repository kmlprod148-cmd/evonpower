@extends('layouts.app')

@section('title', 'Paramètres Généraux - Administration')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-cogs text-primary"></i>
                        Paramètres Généraux
                    </h1>
                    <p class="text-muted mb-0">Configuration globale du système</p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetSettings()">
                        <i class="fas fa-undo"></i>
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Alertes -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Erreurs détectées :</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Formulaire -->
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sliders-h"></i>
                        Configuration du Système
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.general-settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Section Devise -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-coins"></i>
                                    Configuration de la Devise
                                </h6>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">
                                        <i class="fas fa-globe"></i>
                                        Devise Principale
                                    </label>
                                    <select class="form-select" id="currency" name="currency" required>
                                        <option value="EUR" {{ $settings['currency'] == 'EUR' ? 'selected' : '' }}>
                                            Euro (€)
                                        </option>
                                        <option value="USD" {{ $settings['currency'] == 'USD' ? 'selected' : '' }}>
                                            Dollar américain ($)
                                        </option>
                                        <option value="EUR" {{ $settings['currency'] == 'EUR' ? 'selected' : '' }}>
                                            Dirham marocain (EUR)
                                        </option>
                                    </select>
                                    <div class="form-text">La devise utilisée dans toute l'application</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency_symbol" class="form-label">
                                        <i class="fas fa-symbol"></i>
                                        Symbole de la Devise
                                    </label>
                                    <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" 
                                           value="{{ $settings['currency_symbol'] }}" maxlength="5" required>
                                    <div class="form-text">Symbole affiché (ex: €, $, EUR)</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="decimal_places" class="form-label">
                                        <i class="fas fa-calculator"></i>
                                        Décimales
                                    </label>
                                    <input type="number" class="form-control" id="decimal_places" name="decimal_places" 
                                           value="{{ $settings['decimal_places'] }}" min="0" max="4" required>
                                    <div class="form-text">Nombre de décimales (0-4)</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="thousands_separator" class="form-label">
                                        <i class="fas fa-separator"></i>
                                        Séparateur de Milliers
                                    </label>
                                    <input type="text" class="form-control" id="thousands_separator" name="thousands_separator" 
                                           value="{{ $settings['thousands_separator'] }}" maxlength="1" required>
                                    <div class="form-text">Ex: espace, virgule</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="decimal_separator" class="form-label">
                                        <i class="fas fa-separator"></i>
                                        Séparateur Décimal
                                    </label>
                                    <input type="text" class="form-control" id="decimal_separator" name="decimal_separator" 
                                           value="{{ $settings['decimal_separator'] }}" maxlength="1" required>
                                    <div class="form-text">Ex: point, virgule</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Section Application -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-application"></i>
                                    Configuration de l'Application
                                </h6>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="app_name" class="form-label">
                                        <i class="fas fa-tag"></i>
                                        Nom de l'Application
                                    </label>
                                    <input type="text" class="form-control" id="app_name" name="app_name" 
                                           value="{{ $settings['app_name'] }}" maxlength="100" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="timezone" class="form-label">
                                        <i class="fas fa-clock"></i>
                                        Fuseau Horaire
                                    </label>
                                    <select class="form-select" id="timezone" name="timezone" required>
                                        <option value="Europe/Paris" {{ $settings['timezone'] == 'Europe/Paris' ? 'selected' : '' }}>
                                            Europe/Paris (France)
                                        </option>
                                        <option value="Europe/London" {{ $settings['timezone'] == 'Europe/London' ? 'selected' : '' }}>
                                            Europe/London (Royaume-Uni)
                                        </option>
                                        <option value="America/New_York" {{ $settings['timezone'] == 'America/New_York' ? 'selected' : '' }}>
                                            America/New_York (États-Unis)
                                        </option>
                                        <option value="Africa/Casablanca" {{ $settings['timezone'] == 'Africa/Casablanca' ? 'selected' : '' }}>
                                            Africa/Casablanca (Maroc)
                                        </option>
                                        <option value="UTC" {{ $settings['timezone'] == 'UTC' ? 'selected' : '' }}>
                                            UTC (Temps universel)
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="app_description" class="form-label">
                                        <i class="fas fa-align-left"></i>
                                        Description de l'Application
                                    </label>
                                    <textarea class="form-control" id="app_description" name="app_description" 
                                              rows="3" maxlength="500">{{ $settings['app_description'] }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_format" class="form-label">
                                        <i class="fas fa-calendar"></i>
                                        Format de Date
                                    </label>
                                    <select class="form-select" id="date_format" name="date_format" required>
                                        <option value="d/m/Y" {{ $settings['date_format'] == 'd/m/Y' ? 'selected' : '' }}>
                                            dd/mm/yyyy (25/12/2024)
                                        </option>
                                        <option value="Y-m-d" {{ $settings['date_format'] == 'Y-m-d' ? 'selected' : '' }}>
                                            yyyy-mm-dd (2024-12-25)
                                        </option>
                                        <option value="m/d/Y" {{ $settings['date_format'] == 'm/d/Y' ? 'selected' : '' }}>
                                            mm/dd/yyyy (12/25/2024)
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="time_format" class="form-label">
                                        <i class="fas fa-clock"></i>
                                        Format d'Heure
                                    </label>
                                    <select class="form-select" id="time_format" name="time_format" required>
                                        <option value="H:i" {{ $settings['time_format'] == 'H:i' ? 'selected' : '' }}>
                                            24h (14:30)
                                        </option>
                                        <option value="h:i A" {{ $settings['time_format'] == 'h:i A' ? 'selected' : '' }}>
                                            12h (2:30 PM)
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Aperçu de la devise -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-eye"></i>
                                        Aperçu de la Devise
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Montant simple:</strong><br>
                                            <span id="preview-simple">1 234,56 €</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Montant décimal:</strong><br>
                                            <span id="preview-decimal">0,50 €</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Montant entier:</strong><br>
                                            <span id="preview-integer">1 000 €</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Montant zéro:</strong><br>
                                            <span id="preview-zero">0,00 €</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i>
                                        Retour au Dashboard
                                    </a>
                                    <div>
                                        <button type="button" class="btn btn-outline-warning me-2" onclick="resetSettings()">
                                            <i class="fas fa-undo"></i>
                                            Réinitialiser
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i>
                                            Sauvegarder les Paramètres
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informations système -->
            <div class="card shadow mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i>
                        Informations Système
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Dernière modification:</strong> {{ $settings['updated_at'] ?? 'Jamais' }}</p>
                            <p><strong>Modifié par:</strong> {{ $settings['updated_by'] ?? 'Système' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Version:</strong> 1.0.0</p>
                            <p><strong>Accès restreint:</strong> admin@evon.com uniquement</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour l'aperçu en temps réel -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currencySelect = document.getElementById('currency');
    const symbolInput = document.getElementById('currency_symbol');
    const decimalPlacesInput = document.getElementById('decimal_places');
    const thousandsSeparatorInput = document.getElementById('thousands_separator');
    const decimalSeparatorInput = document.getElementById('decimal_separator');

    function updatePreview() {
        const currency = currencySelect.value;
        const symbol = symbolInput.value;
        const decimalPlaces = parseInt(decimalPlacesInput.value);
        const thousandsSep = thousandsSeparatorInput.value;
        const decimalSep = decimalSeparatorInput.value;

        // Mise à jour automatique du symbole selon la devise
        const currencySymbols = {
            'EUR': '€',
            'USD': '$',
            'EUR': 'EUR'
        };
        
        if (currencySymbols[currency]) {
            symbolInput.value = currencySymbols[currency];
        }

        // Formatage des exemples
        function formatNumber(number, decimals, thousandsSep, decimalSep) {
            return number.toFixed(decimals)
                .replace('.', decimalSep)
                .replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSep);
        }

        document.getElementById('preview-simple').textContent = 
            formatNumber(1234.56, decimalPlaces, thousandsSep, decimalSep) + ' ' + symbol;
        
        document.getElementById('preview-decimal').textContent = 
            formatNumber(0.50, decimalPlaces, thousandsSep, decimalSep) + ' ' + symbol;
        
        document.getElementById('preview-integer').textContent = 
            formatNumber(1000, decimalPlaces, thousandsSep, decimalSep) + ' ' + symbol;
        
        document.getElementById('preview-zero').textContent = 
            formatNumber(0, decimalPlaces, thousandsSep, decimalSep) + ' ' + symbol;
    }

    // Écouteurs d'événements
    currencySelect.addEventListener('change', updatePreview);
    symbolInput.addEventListener('input', updatePreview);
    decimalPlacesInput.addEventListener('input', updatePreview);
    thousandsSeparatorInput.addEventListener('input', updatePreview);
    decimalSeparatorInput.addEventListener('input', updatePreview);

    // Initialisation
    updatePreview();
});

function resetSettings() {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser tous les paramètres aux valeurs par défaut ?')) {
        window.location.href = '{{ route("admin.general-settings.reset") }}';
    }
}
</script>
@endsection
