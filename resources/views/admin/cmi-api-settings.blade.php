@extends('layouts.app')

@section('title', 'Configuration API CMI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- En-tête -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Configuration API CMI
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Gérez les paramètres de connexion à l'API CMI pour les paiements
                </p>
            </div>
            <div class="flex space-x-3">
                <button type="button" id="testConnectionBtn" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tester la connexion
                </button>
                <a href="{{ route('admin.cmi-api-settings.reset') }}" 
                   class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors"
                   onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser tous les paramètres CMI ?')">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Réinitialiser
                </a>
            </div>
        </div>
    </div>

    <!-- Messages d'alerte -->
    @if(session('success'))
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    <!-- Formulaire de configuration -->
    <form action="{{ route('admin.cmi-api-settings.update') }}" method="POST" id="cmiSettingsForm">
        @csrf
        @method('PUT')
        
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <!-- Section Informations de base -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Informations de base
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Configurez les paramètres essentiels pour la connexion à l'API CMI
                </p>
            </div>

            <div class="p-6 space-y-6">
                <!-- Clé API CMI -->
                <div>
                    <label for="cmi_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Clé API CMI <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="cmi_api_key" 
                               name="cmi_api_key" 
                               value="{{ old('cmi_api_key', $cmiSettings['cmi_api_key']['value'] ?? '') }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('cmi_api_key') border-red-500 @enderror"
                               placeholder="Entrez votre clé API CMI"
                               required>
                        <button type="button" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                onclick="togglePassword('cmi_api_key')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    @error('cmi_api_key')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $cmiSettings['cmi_api_key']['description'] ?? 'Clé API fournie par CMI pour l\'authentification' }}
                    </p>
                </div>

                <!-- ID du marchand -->
                <div>
                    <label for="cmi_merchant_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ID du marchand CMI <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="cmi_merchant_id" 
                           name="cmi_merchant_id" 
                           value="{{ old('cmi_merchant_id', $cmiSettings['cmi_merchant_id']['value'] ?? '') }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('cmi_merchant_id') border-red-500 @enderror"
                           placeholder="Entrez votre ID marchand CMI"
                           required>
                    @error('cmi_merchant_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $cmiSettings['cmi_merchant_id']['description'] ?? 'Identifiant unique de votre compte marchand CMI' }}
                    </p>
                </div>

                <!-- URL de l'API -->
                <div>
                    <label for="cmi_api_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        URL de l'API CMI <span class="text-red-500">*</span>
                    </label>
                    <select id="cmi_api_url" 
                            name="cmi_api_url" 
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('cmi_api_url') border-red-500 @enderror"
                            required>
                        <option value="">Sélectionnez une URL</option>
                        <option value="https://testpayment.cmi.co.ma/fim/est3Dgate" 
                                {{ old('cmi_api_url', $cmiSettings['cmi_api_url']['value'] ?? '') == 'https://testpayment.cmi.co.ma/fim/est3Dgate' ? 'selected' : '' }}>
                            Test - https://testpayment.cmi.co.ma/fim/est3Dgate
                        </option>
                        <option value="https://payment.cmi.co.ma/fim/est3Dgate" 
                                {{ old('cmi_api_url', $cmiSettings['cmi_api_url']['value'] ?? '') == 'https://payment.cmi.co.ma/fim/est3Dgate' ? 'selected' : '' }}>
                            Production - https://payment.cmi.co.ma/fim/est3Dgate
                        </option>
                    </select>
                    @error('cmi_api_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $cmiSettings['cmi_api_url']['description'] ?? 'URL du serveur API CMI selon l\'environnement' }}
                    </p>
                </div>
            </div>

            <!-- Section Configuration avancée -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Configuration avancée
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Paramètres optionnels pour personnaliser l'expérience de paiement
                </p>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Environnement -->
                    <div>
                        <label for="cmi_environment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Environnement <span class="text-red-500">*</span>
                        </label>
                        <select id="cmi_environment" 
                                name="cmi_environment" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('cmi_environment') border-red-500 @enderror"
                                required>
                            <option value="test" {{ old('cmi_environment', $cmiSettings['cmi_environment']['value'] ?? '') == 'test' ? 'selected' : '' }}>
                                Test
                            </option>
                            <option value="production" {{ old('cmi_environment', $cmiSettings['cmi_environment']['value'] ?? '') == 'production' ? 'selected' : '' }}>
                                Production
                            </option>
                        </select>
                        @error('cmi_environment')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Devise -->
                    <div>
                        <label for="cmi_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Devise <span class="text-red-500">*</span>
                        </label>
                        <select id="cmi_currency" 
                                name="cmi_currency" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('cmi_currency') border-red-500 @enderror"
                                required>
                            <option value="EUR" {{ old('cmi_currency', $cmiSettings['cmi_currency']['value'] ?? '') == 'EUR' ? 'selected' : '' }}>
                                Euro (EUR)
                            </option>
                            <option value="USD" {{ old('cmi_currency', $cmiSettings['cmi_currency']['value'] ?? '') == 'USD' ? 'selected' : '' }}>
                                Dollar américain (USD)
                            </option>
                        </select>
                        @error('cmi_currency')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Langue -->
                    <div>
                        <label for="cmi_language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Langue <span class="text-red-500">*</span>
                        </label>
                        <select id="cmi_language" 
                                name="cmi_language" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('cmi_language') border-red-500 @enderror"
                                required>
                            <option value="fr" {{ old('cmi_language', $cmiSettings['cmi_language']['value'] ?? '') == 'fr' ? 'selected' : '' }}>
                                Français
                            </option>
                            <option value="en" {{ old('cmi_language', $cmiSettings['cmi_language']['value'] ?? '') == 'en' ? 'selected' : '' }}>
                                English
                            </option>
                            <option value="ar" {{ old('cmi_language', $cmiSettings['cmi_language']['value'] ?? '') == 'ar' ? 'selected' : '' }}>
                                العربية
                            </option>
                        </select>
                        @error('cmi_language')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3">
                <a href="{{ route('settings.api.index') }}" 
                   class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    Annuler
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Sauvegarder les paramètres
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal de test de connexion -->
<div id="testConnectionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4">
                    <div id="testSpinner" class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <div id="testSuccess" class="hidden text-green-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div id="testError" class="hidden text-red-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white text-center mb-2">
                    Test de connexion CMI
                </h3>
                <p id="testMessage" class="text-sm text-gray-600 dark:text-gray-400 text-center">
                    Vérification de la connexion en cours...
                </p>
                <div id="testDetails" class="mt-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg hidden">
                    <p class="text-sm text-gray-600 dark:text-gray-400"></p>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="button" 
                            onclick="closeTestModal()" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour basculer la visibilité du mot de passe
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('svg');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
        `;
    } else {
        field.type = 'password';
        icon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        `;
    }
}

// Test de connexion
document.getElementById('testConnectionBtn').addEventListener('click', function() {
    const form = document.getElementById('cmiSettingsForm');
    const formData = new FormData(form);
    
    // Afficher le modal
    document.getElementById('testConnectionModal').classList.remove('hidden');
    document.getElementById('testSpinner').classList.remove('hidden');
    document.getElementById('testSuccess').classList.add('hidden');
    document.getElementById('testError').classList.add('hidden');
    document.getElementById('testDetails').classList.add('hidden');
    
    // Effectuer le test
    fetch('{{ route("admin.cmi-api-settings.test") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('testSpinner').classList.add('hidden');
        
        if (data.success) {
            document.getElementById('testSuccess').classList.remove('hidden');
            document.getElementById('testMessage').textContent = data.message;
            if (data.details) {
                document.getElementById('testDetails').classList.remove('hidden');
                document.getElementById('testDetails').querySelector('p').textContent = data.details;
            }
        } else {
            document.getElementById('testError').classList.remove('hidden');
            document.getElementById('testMessage').textContent = data.message;
            if (data.details) {
                document.getElementById('testDetails').classList.remove('hidden');
                document.getElementById('testDetails').querySelector('p').textContent = data.details;
            }
        }
    })
    .catch(error => {
        document.getElementById('testSpinner').classList.add('hidden');
        document.getElementById('testError').classList.remove('hidden');
        document.getElementById('testMessage').textContent = 'Erreur lors du test de connexion';
        console.error('Error:', error);
    });
});

// Fermer le modal de test
function closeTestModal() {
    document.getElementById('testConnectionModal').classList.add('hidden');
}

// Fermer le modal en cliquant à l'extérieur
document.getElementById('testConnectionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTestModal();
    }
});
</script>
@endsection
