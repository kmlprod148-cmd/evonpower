@extends('layouts.app')

@section('title', 'Configuration Stripe & Devise')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- En-tête -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Configuration Stripe & Devise
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Gérez les paramètres Stripe et la devise de l'application
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.stripe-api-settings.reset') }}" 
                   class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors"
                   onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser tous les paramètres Stripe ?')">
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Configuration Stripe -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Configuration Stripe
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Paramètres pour l'intégration avec Stripe (Test et Production)
                </p>
            </div>

            <form action="{{ route('admin.stripe-api-settings.update') }}" method="POST" id="stripeSettingsForm">
                @csrf
                @method('PUT')
                
                <!-- Onglets Test/Prod -->
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px">
                        <button type="button" onclick="showStripeTab('test')" id="stripe-test-tab" class="stripe-tab-button active py-4 px-6 text-sm font-medium border-b-2 border-blue-500 text-blue-600">
                            🔧 Environnement Test
                        </button>
                        <button type="button" onclick="showStripeTab('prod')" id="stripe-prod-tab" class="stripe-tab-button py-4 px-6 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            🚀 Production
                        </button>
                    </nav>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Section Test -->
                    <div id="stripe-test-content" class="stripe-tab-content">
                        <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                ⚠️ Configuration pour l'environnement de test. Utilisez les clés commençant par <code class="bg-yellow-100 dark:bg-yellow-900 px-1 rounded">pk_test_</code> et <code class="bg-yellow-100 dark:bg-yellow-900 px-1 rounded">sk_test_</code>
                            </p>
                        </div>

                        <!-- Clé publique Test -->
                        <div>
                            <label for="stripe_test_publishable_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Clé publique Test <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="stripe_test_publishable_key" 
                                   name="stripe_test_publishable_key" 
                                   value="{{ old('stripe_test_publishable_key', $stripeSettings['stripe_test_publishable_key']['value'] ?? '') }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('stripe_test_publishable_key') border-red-500 @enderror"
                                   placeholder="pk_test_...">
                            @error('stripe_test_publishable_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Clé secrète Test -->
                        <div>
                            <label for="stripe_test_secret_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Clé secrète Test <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="stripe_test_secret_key" 
                                       name="stripe_test_secret_key" 
                                       value="{{ old('stripe_test_secret_key', $stripeSettings['stripe_test_secret_key']['value'] ?? '') }}"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('stripe_test_secret_key') border-red-500 @enderror"
                                       placeholder="sk_test_...">
                                <button type="button" 
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                        onclick="togglePassword('stripe_test_secret_key')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                            @error('stripe_test_secret_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Secret webhook Test -->
                        <div>
                            <label for="stripe_test_webhook_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Secret webhook Test
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="stripe_test_webhook_secret" 
                                       name="stripe_test_webhook_secret" 
                                       value="{{ old('stripe_test_webhook_secret', $stripeSettings['stripe_test_webhook_secret']['value'] ?? '') }}"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('stripe_test_webhook_secret') border-red-500 @enderror"
                                       placeholder="whsec_...">
                                <button type="button" 
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                        onclick="togglePassword('stripe_test_webhook_secret')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                            @error('stripe_test_webhook_secret')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- URL webhook Test -->
                        <div>
                            <label for="stripe_test_webhook_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                URL webhook Test
                            </label>
                            <input type="url" 
                                   id="stripe_test_webhook_url" 
                                   name="stripe_test_webhook_url" 
                                   value="{{ old('stripe_test_webhook_url', $stripeSettings['stripe_test_webhook_url']['value'] ?? '') }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('stripe_test_webhook_url') border-red-500 @enderror"
                                   placeholder="https://yourdomain.com/webhooks/stripe/test">
                            @error('stripe_test_webhook_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="button" onclick="testStripeConnection('test')" class="w-full px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                            🧪 Tester la connexion Test
                        </button>
                    </div>

                    <!-- Section Production -->
                    <div id="stripe-prod-content" class="stripe-tab-content hidden">
                        <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <p class="text-sm text-green-800 dark:text-green-200">
                                ✅ Configuration pour l'environnement de production. Utilisez les clés commençant par <code class="bg-green-100 dark:bg-green-900 px-1 rounded">pk_live_</code> et <code class="bg-green-100 dark:bg-green-900 px-1 rounded">sk_live_</code>
                            </p>
                        </div>

                        <!-- Clé publique Prod -->
                        <div>
                            <label for="stripe_prod_publishable_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Clé publique Production <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="stripe_prod_publishable_key" 
                                   name="stripe_prod_publishable_key" 
                                   value="{{ old('stripe_prod_publishable_key', $stripeSettings['stripe_prod_publishable_key']['value'] ?? '') }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('stripe_prod_publishable_key') border-red-500 @enderror"
                                   placeholder="pk_live_...">
                            @error('stripe_prod_publishable_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Clé secrète Prod -->
                        <div>
                            <label for="stripe_prod_secret_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Clé secrète Production <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="stripe_prod_secret_key" 
                                       name="stripe_prod_secret_key" 
                                       value="{{ old('stripe_prod_secret_key', $stripeSettings['stripe_prod_secret_key']['value'] ?? '') }}"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('stripe_prod_secret_key') border-red-500 @enderror"
                                       placeholder="sk_live_...">
                                <button type="button" 
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                        onclick="togglePassword('stripe_prod_secret_key')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                            @error('stripe_prod_secret_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Secret webhook Prod -->
                        <div>
                            <label for="stripe_prod_webhook_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Secret webhook Production
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="stripe_prod_webhook_secret" 
                                       name="stripe_prod_webhook_secret" 
                                       value="{{ old('stripe_prod_webhook_secret', $stripeSettings['stripe_prod_webhook_secret']['value'] ?? '') }}"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('stripe_prod_webhook_secret') border-red-500 @enderror"
                                       placeholder="whsec_...">
                                <button type="button" 
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                        onclick="togglePassword('stripe_prod_webhook_secret')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                            @error('stripe_prod_webhook_secret')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- URL webhook Prod -->
                        <div>
                            <label for="stripe_prod_webhook_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                URL webhook Production
                            </label>
                            <input type="url" 
                                   id="stripe_prod_webhook_url" 
                                   name="stripe_prod_webhook_url" 
                                   value="{{ old('stripe_prod_webhook_url', $stripeSettings['stripe_prod_webhook_url']['value'] ?? '') }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('stripe_prod_webhook_url') border-red-500 @enderror"
                                   placeholder="https://yourdomain.com/webhooks/stripe/prod">
                            @error('stripe_prod_webhook_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="button" onclick="testStripeConnection('prod')" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            🧪 Tester la connexion Production
                        </button>
                    </div>

                    <!-- Paramètres communs -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Paramètres généraux</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Environnement actif -->
                            <div>
                                <label for="stripe_environment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Environnement actif <span class="text-red-500">*</span>
                                </label>
                                <select id="stripe_environment" 
                                        name="stripe_environment" 
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('stripe_environment') border-red-500 @enderror"
                                        required>
                                    <option value="test" {{ old('stripe_environment', $stripeSettings['stripe_environment']['value'] ?? 'test') == 'test' ? 'selected' : '' }}>
                                        Test
                                    </option>
                                    <option value="prod" {{ old('stripe_environment', $stripeSettings['stripe_environment']['value'] ?? '') == 'prod' ? 'selected' : '' }}>
                                        Production
                                    </option>
                                </select>
                                @error('stripe_environment')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Environnement utilisé par défaut pour les paiements
                                </p>
                            </div>

                            <!-- Devise Stripe -->
                            <div>
                                <label for="stripe_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Devise Stripe <span class="text-red-500">*</span>
                                </label>
                                <select id="stripe_currency" 
                                        name="stripe_currency" 
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('stripe_currency') border-red-500 @enderror"
                                        required>
                                    <option value="EUR" {{ old('stripe_currency', $stripeSettings['stripe_currency']['value'] ?? 'EUR') == 'EUR' ? 'selected' : '' }}>
                                        Euro (EUR)
                                    </option>
                                    <option value="USD" {{ old('stripe_currency', $stripeSettings['stripe_currency']['value'] ?? '') == 'USD' ? 'selected' : '' }}>
                                        Dollar américain (USD)
                                    </option>
                                </select>
                                @error('stripe_currency')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end">
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Sauvegarder Stripe
                    </button>
                </div>
            </form>
        </div>

        <!-- Configuration de la devise de l'application -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Devise de l'application
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Configurez la devise par défaut de l'application
                </p>
            </div>

            <form action="{{ route('admin.stripe-api-settings.update-currency') }}" method="POST" id="currencyForm">
                @csrf
                @method('PUT')
                
                <div class="p-6 space-y-6">
                    <!-- Devise par défaut -->
                    <div>
                        <label for="app_default_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Devise par défaut <span class="text-red-500">*</span>
                        </label>
                        <select id="app_default_currency" 
                                name="app_default_currency" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white @error('app_default_currency') border-red-500 @enderror"
                                required>
                            <option value="EUR" {{ old('app_default_currency', $currencySettings['app_default_currency']['value'] ?? '') == 'EUR' ? 'selected' : '' }}>
                                🇪🇺 Euro (EUR)
                            </option>
                            <option value="USD" {{ old('app_default_currency', $currencySettings['app_default_currency']['value'] ?? '') == 'USD' ? 'selected' : '' }}>
                                🇺🇸 Dollar américain (USD)
                            </option>
                        </select>
                        @error('app_default_currency')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $currencySettings['app_default_currency']['description'] ?? 'Devise utilisée par défaut dans l\'application' }}
                        </p>
                    </div>

                    <!-- Symbole de la devise -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Symbole de la devise
                        </label>
                        <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <span id="currency_symbol_display" class="text-lg font-semibold">
                                {{ $currencySettings['app_currency_symbol']['value'] ?? '€' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Symbole automatiquement mis à jour selon la devise sélectionnée
                        </p>
                    </div>

                    <!-- Format des décimales -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Format des décimales
                        </label>
                        <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm">
                                {{ $currencySettings['app_currency_format']['value'] ?? '2' }} décimales
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Nombre de décimales affichées pour les montants
                        </p>
                    </div>

                    <!-- Aperçu -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Aperçu du formatage
                        </label>
                        <div class="px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Exemple:</span>
                                <span id="currency_preview" class="text-lg font-semibold text-green-700 dark:text-green-400">
                                    1,234.56 {{ $currencySettings['app_currency_symbol']['value'] ?? '€' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end">
                    <button type="submit" 
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Sauvegarder la devise
                    </button>
                </div>
            </form>
        </div>
    </div>
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
                    Test de connexion Stripe
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

// Mise à jour du symbole de devise et aperçu
document.getElementById('app_default_currency').addEventListener('change', function() {
    const currency = this.value;
    const symbols = {
        'EUR': '€',
        'USD': '$'
    };
    
    document.getElementById('currency_symbol_display').textContent = symbols[currency];
    document.getElementById('currency_preview').textContent = `1,234.56 ${symbols[currency]}`;
});

// Gestion des onglets Stripe
function showStripeTab(tabName) {
    // Masquer tous les contenus
    document.querySelectorAll('.stripe-tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Désactiver tous les onglets
    document.querySelectorAll('.stripe-tab-button').forEach(button => {
        button.classList.remove('active', 'border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Afficher le contenu sélectionné
    document.getElementById('stripe-' + tabName + '-content').classList.remove('hidden');
    
    // Activer l'onglet sélectionné
    const activeTab = document.getElementById('stripe-' + tabName + '-tab');
    activeTab.classList.add('active', 'border-blue-500', 'text-blue-600');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
}

// Test de connexion Stripe
function testStripeConnection(environment) {
    const form = document.getElementById('stripeSettingsForm');
    const formData = new FormData(form);
    formData.append('test_environment', environment);
    
    // Afficher le modal
    document.getElementById('testConnectionModal').classList.remove('hidden');
    document.getElementById('testSpinner').classList.remove('hidden');
    document.getElementById('testSuccess').classList.add('hidden');
    document.getElementById('testError').classList.add('hidden');
    document.getElementById('testDetails').classList.add('hidden');
    
    // Effectuer le test
    fetch('{{ route("admin.stripe-api-settings.test") }}', {
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
}

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
