@extends('layouts.app')

@section('title', 'Paramètres Système')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- En-tête -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Paramètres Système
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Gérez les API keys, URLs WebSocket et la devise de l'application
                </p>
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

    <!-- Onglets -->
    <div class="mb-8">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showTab('api-keys')" id="api-keys-tab" class="tab-button active py-2 px-1 border-b-2 font-medium text-sm">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    API Keys
                </button>
                <button onclick="showTab('websocket')" id="websocket-tab" class="tab-button py-2 px-1 border-b-2 font-medium text-sm">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                    </svg>
                    WebSocket URLs
                </button>
                <button onclick="showTab('currency')" id="currency-tab" class="tab-button py-2 px-1 border-b-2 font-medium text-sm">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                    Devise
                </button>
            </nav>
        </div>
    </div>

    <!-- Contenu des onglets -->
    <div class="space-y-8">
        <!-- Onglet API Keys -->
        <div id="api-keys-content" class="tab-content">
            <form action="{{ route('admin.system-settings.update-api-keys') }}" method="POST" class="space-y-6">
                @csrf

                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Configuration des API Keys
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Gérez les clés API pour les services externes
                        </p>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- SteVe API Settings -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">SteVe API</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- SteVe API Key -->
                                <div>
                                    <label for="steve_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Clé API SteVe
                                    </label>
                                    <input type="password" id="steve_api_key" name="steve_api_key" 
                                           value="{{ old('steve_api_key', $apiKeys['steve_api_key']['value'] ?? '') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                           placeholder="Votre clé API SteVe">
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Clé API pour l'intégration SteVe
                                    </p>
                                </div>

                                <!-- SteVe API User -->
                                <div>
                                    <label for="steve_api_user" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Nom d'utilisateur
                                    </label>
                                    <input type="text" id="steve_api_user" name="steve_api_user" 
                                           value="{{ old('steve_api_user', $apiKeys['steve_api_user']['value'] ?? '') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                           placeholder="admin">
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Nom d'utilisateur pour l'API SteVe
                                    </p>
                                </div>

                                <!-- SteVe API Password -->
                                <div>
                                    <label for="steve_api_pass" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Mot de passe
                                    </label>
                                    <input type="password" id="steve_api_pass" name="steve_api_pass" 
                                           value="{{ old('steve_api_pass', $apiKeys['steve_api_pass']['value'] ?? '') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                           placeholder="Votre mot de passe">
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Mot de passe pour l'API SteVe
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Currency API Settings -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">API Taux de Change</h3>
                            
                            <div>
                                <label for="currency_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Clé API Taux de Change
                                </label>
                                <input type="password" id="currency_api_key" name="currency_api_key" 
                                       value="{{ old('currency_api_key', $apiKeys['currency_api_key']['value'] ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                       placeholder="Votre clé API pour les taux de change">
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Clé API pour récupérer les taux de change (ex: Fixer.io, ExchangeRate-API)
                                </p>
                            </div>
                        </div>

                        <!-- External API Settings -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">API Externe</h3>
                            
                            <div>
                                <label for="external_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Clé API Externe
                                </label>
                                <input type="password" id="external_api_key" name="external_api_key" 
                                       value="{{ old('external_api_key', $apiKeys['external_api_key']['value'] ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                       placeholder="Votre clé API externe">
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Clé API pour les services externes
                                </p>
                            </div>
                        </div>

                        <!-- Stripe API Settings -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Stripe API</h3>
                            
                            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    Configurez les clés API Stripe pour les paiements. Les clés secrètes sont cryptées.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Stripe Environment -->
                                <div>
                                    <label for="stripe_environment" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Environnement actif
                                    </label>
                                    <select id="stripe_environment" name="stripe_environment" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="test" {{ (old('stripe_environment', $apiKeys['stripe_environment']['value'] ?? 'test') == 'test') ? 'selected' : '' }}>
                                            Test
                                        </option>
                                        <option value="prod" {{ (old('stripe_environment', $apiKeys['stripe_environment']['value'] ?? '') == 'prod') ? 'selected' : '' }}>
                                            Production
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h4 class="text-md font-medium text-gray-800 dark:text-gray-200">Environnement Test</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="stripe_test_publishable_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Clé publique Test
                                        </label>
                                        <input type="text" id="stripe_test_publishable_key" name="stripe_test_publishable_key" 
                                               value="{{ old('stripe_test_publishable_key', $apiKeys['stripe_test_publishable_key']['value'] ?? '') }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                               placeholder="pk_test_...">
                                    </div>
                                    <div>
                                        <label for="stripe_test_secret_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Clé secrète Test
                                        </label>
                                        <input type="password" id="stripe_test_secret_key" name="stripe_test_secret_key" 
                                               value="{{ old('stripe_test_secret_key', $apiKeys['stripe_test_secret_key']['value'] ?? '') }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                               placeholder="sk_test_...">
                                    </div>
                                    <div>
                                        <label for="stripe_test_webhook_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Secret webhook Test
                                        </label>
                                        <input type="password" id="stripe_test_webhook_secret" name="stripe_test_webhook_secret" 
                                               value="{{ old('stripe_test_webhook_secret', $apiKeys['stripe_test_webhook_secret']['value'] ?? '') }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                               placeholder="whsec_...">
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 mt-6">
                                <h4 class="text-md font-medium text-gray-800 dark:text-gray-200">Environnement Production</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="stripe_prod_publishable_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Clé publique Production
                                        </label>
                                        <input type="text" id="stripe_prod_publishable_key" name="stripe_prod_publishable_key" 
                                               value="{{ old('stripe_prod_publishable_key', $apiKeys['stripe_prod_publishable_key']['value'] ?? '') }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                               placeholder="pk_live_...">
                                    </div>
                                    <div>
                                        <label for="stripe_prod_secret_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Clé secrète Production
                                        </label>
                                        <input type="password" id="stripe_prod_secret_key" name="stripe_prod_secret_key" 
                                               value="{{ old('stripe_prod_secret_key', $apiKeys['stripe_prod_secret_key']['value'] ?? '') }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                               placeholder="sk_live_...">
                                    </div>
                                    <div>
                                        <label for="stripe_prod_webhook_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Secret webhook Production
                                        </label>
                                        <input type="password" id="stripe_prod_webhook_secret" name="stripe_prod_webhook_secret" 
                                               value="{{ old('stripe_prod_webhook_secret', $apiKeys['stripe_prod_webhook_secret']['value'] ?? '') }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                               placeholder="whsec_...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CMI API Settings -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">CMI API</h3>
                            
                            <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <p class="text-sm text-green-800 dark:text-green-200">
                                    Configurez les clés API CMI (Credit Mutuel International) pour les paiements. Les clés API sont cryptées.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- CMI Environment -->
                                <div>
                                    <label for="cmi_environment" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Environnement actif
                                    </label>
                                    <select id="cmi_environment" name="cmi_environment" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="test" {{ (old('cmi_environment', $apiKeys['cmi_environment']['value'] ?? 'test') == 'test') ? 'selected' : '' }}>
                                            Test
                                        </option>
                                        <option value="prod" {{ (old('cmi_environment', $apiKeys['cmi_environment']['value'] ?? '') == 'prod') ? 'selected' : '' }}>
                                            Production
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h4 class="text-md font-medium text-gray-800 dark:text-gray-200">Environnement Test</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="cmi_test_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Clé API Test
                                        </label>
                                        <input type="password" id="cmi_test_api_key" name="cmi_test_api_key" 
                                               value="{{ old('cmi_test_api_key', $apiKeys['cmi_test_api_key']['value'] ?? '') }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                               placeholder="Votre clé API CMI de test">
                                    </div>
                                    <div>
                                        <label for="cmi_test_merchant_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            ID marchand Test
                                        </label>
                                        <input type="text" id="cmi_test_merchant_id" name="cmi_test_merchant_id" 
                                               value="{{ old('cmi_test_merchant_id', $apiKeys['cmi_test_merchant_id']['value'] ?? '') }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                               placeholder="Votre ID marchand CMI de test">
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 mt-6">
                                <h4 class="text-md font-medium text-gray-800 dark:text-gray-200">Environnement Production</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="cmi_prod_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Clé API Production
                                        </label>
                                        <input type="password" id="cmi_prod_api_key" name="cmi_prod_api_key" 
                                               value="{{ old('cmi_prod_api_key', $apiKeys['cmi_prod_api_key']['value'] ?? '') }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                               placeholder="Votre clé API CMI de production">
                                    </div>
                                    <div>
                                        <label for="cmi_prod_merchant_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            ID marchand Production
                                        </label>
                                        <input type="text" id="cmi_prod_merchant_id" name="cmi_prod_merchant_id" 
                                               value="{{ old('cmi_prod_merchant_id', $apiKeys['cmi_prod_merchant_id']['value'] ?? '') }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                               placeholder="Votre ID marchand CMI de production">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Sauvegarder API Keys
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Onglet WebSocket URLs -->
        <div id="websocket-content" class="tab-content hidden">
            <form action="{{ route('admin.system-settings.update-websocket-urls') }}" method="POST" class="space-y-6">
                @csrf

                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Configuration WebSocket
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Gérez les URLs WebSocket pour la communication en temps réel
                        </p>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- SteVe WebSocket Base URL -->
                        <div>
                            <label for="steve_websocket_base_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                URL de base WebSocket SteVe <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="steve_websocket_base_url" name="steve_websocket_base_url" 
                                   value="{{ old('steve_websocket_base_url', $websocketUrls['steve_websocket_base_url']['value'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   required
                                   placeholder="ws://158.69.27.239:8080/steve/websocket/CentralSystemService/">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                URL de base pour les connexions WebSocket SteVe (format: ws://host:port/path/)
                            </p>
                        </div>

                        <!-- SteVe API URL -->
                        <div>
                            <label for="steve_api_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                URL de l'API SteVe <span class="text-red-500">*</span>
                            </label>
                            <input type="url" id="steve_api_url" name="steve_api_url" 
                                   value="{{ old('steve_api_url', $websocketUrls['steve_api_url']['value'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   required
                                   placeholder="http://158.69.27.239:8080">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                URL de base de l'API SteVe (format: http://host:port)
                            </p>
                        </div>

                        <!-- WebSocket Timeout -->
                        <div>
                            <label for="websocket_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Timeout WebSocket (secondes)
                            </label>
                            <input type="number" id="websocket_timeout" name="websocket_timeout" 
                                   value="{{ old('websocket_timeout', $websocketUrls['websocket_timeout']['value'] ?? '30') }}"
                                   min="1" max="300"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Timeout pour les connexions WebSocket (1-300 secondes)
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-500 active:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Sauvegarder WebSocket URLs
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Onglet Devise -->
        <div id="currency-content" class="tab-content hidden">
            <form action="{{ route('admin.system-settings.update-currency') }}" method="POST" class="space-y-6">
                @csrf

                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Paramètres de Devise
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Configuration de la devise par défaut de l'application
                        </p>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- App Default Currency -->
                        <div>
                            <label for="app_default_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Devise par défaut de l'application <span class="text-red-500">*</span>
                            </label>
                            <select id="app_default_currency" name="app_default_currency" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    required>
                                <option value="EUR" {{ (old('app_default_currency', $currencySettings['app_default_currency']['value'] ?? 'EUR') == 'EUR') ? 'selected' : '' }}>
                                    Euro (EUR)
                                </option>
                                <option value="USD" {{ (old('app_default_currency', $currencySettings['app_default_currency']['value'] ?? '') == 'USD') ? 'selected' : '' }}>
                                    Dollar américain (USD)
                                </option>
                            </select>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Devise par défaut pour toutes les transactions et affichages
                            </p>
                        </div>

                        <!-- App Currency Symbol -->
                        <div>
                            <label for="app_currency_symbol" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Symbole de devise <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="app_currency_symbol" name="app_currency_symbol" 
                                   value="{{ old('app_currency_symbol', $currencySettings['app_currency_symbol']['value'] ?? '€') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   required
                                   maxlength="5"
                                   placeholder="€">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Symbole affiché pour la devise par défaut (ex: €, $)
                            </p>
                        </div>

                        <!-- App Currency Format -->
                        <div>
                            <label for="app_currency_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Format de devise <span class="text-red-500">*</span>
                            </label>
                            <select id="app_currency_format" name="app_currency_format" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    required>
                                <option value="0" {{ (old('app_currency_format', $currencySettings['app_currency_format']['value'] ?? '2') == '0') ? 'selected' : '' }}>
                                    Pas de décimales (ex: 100)
                                </option>
                                <option value="1" {{ (old('app_currency_format', $currencySettings['app_currency_format']['value'] ?? '2') == '1') ? 'selected' : '' }}>
                                    1 décimale (ex: 100.0)
                                </option>
                                <option value="2" {{ (old('app_currency_format', $currencySettings['app_currency_format']['value'] ?? '2') == '2') ? 'selected' : '' }}>
                                    2 décimales (ex: 100.00)
                                </option>
                            </select>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Nombre de décimales à afficher pour les montants
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Sauvegarder Devise
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Gestion des onglets
function showTab(tabName) {
    // Masquer tous les contenus
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Désactiver tous les onglets
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Afficher le contenu sélectionné
    document.getElementById(tabName + '-content').classList.remove('hidden');
    
    // Activer l'onglet sélectionné
    const activeTab = document.getElementById(tabName + '-tab');
    activeTab.classList.add('active', 'border-blue-500', 'text-blue-600');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
}
</script>

<style>
.tab-button.active {
    border-color: #3b82f6;
    color: #2563eb;
}
</style>
@endsection

