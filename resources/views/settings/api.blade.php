@extends('layouts.app')

@section('title', 'Paramètres API')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- En-tête -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Paramètres API
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Gérez les identifiants API pour Stripe et CMI
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

    @if($errors->any())
    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Formulaire principal -->
    <form action="{{ route('settings.api.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        
        <!-- Stripe API Settings -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Configuration Stripe
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Configurez les clés API Stripe pour les paiements. Les clés secrètes sont cryptées.
                </p>
            </div>

            <div class="p-6 space-y-6">
                <!-- Stripe Environment -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="stripe_environment" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Environnement actif
                        </label>
                        <select id="stripe_environment" name="stripe_environment" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="test" {{ (old('stripe_environment', $stripe['stripe_environment']['value'] ?? 'test') == 'test') ? 'selected' : '' }}>
                                Test
                            </option>
                            <option value="prod" {{ (old('stripe_environment', $stripe['stripe_environment']['value'] ?? '') == 'prod') ? 'selected' : '' }}>
                                Production
                            </option>
                        </select>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Sélectionnez l'environnement Stripe à utiliser
                        </p>
                    </div>
                </div>

                <!-- Stripe Test Environment -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Environnement Test</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="stripe_test_publishable_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Clé publique Test
                            </label>
                            <input type="text" id="stripe_test_publishable_key" name="stripe_test_publishable_key" 
                                   value="{{ old('stripe_test_publishable_key', $stripe['stripe_test_publishable_key']['value'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="pk_test_...">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Clé publique Stripe pour l'environnement de test
                            </p>
                        </div>
                        <div>
                            <label for="stripe_test_secret_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Clé secrète Test
                            </label>
                            <input type="password" id="stripe_test_secret_key" name="stripe_test_secret_key" 
                                   value="{{ old('stripe_test_secret_key', $stripe['stripe_test_secret_key']['has_value'] ? '' : '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="{{ $stripe['stripe_test_secret_key']['has_value'] ?? false ? '•••••••••••••••• (laisser vide pour conserver)' : 'sk_test_...' }}">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Clé secrète Stripe pour l'environnement de test
                            </p>
                        </div>
                        <div>
                            <label for="stripe_test_webhook_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Secret webhook Test
                            </label>
                            <input type="password" id="stripe_test_webhook_secret" name="stripe_test_webhook_secret" 
                                   value="{{ old('stripe_test_webhook_secret', $stripe['stripe_test_webhook_secret']['has_value'] ? '' : '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="{{ $stripe['stripe_test_webhook_secret']['has_value'] ?? false ? '•••••••••••••••• (laisser vide pour conserver)' : 'whsec_...' }}">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Secret webhook Stripe pour l'environnement de test
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stripe Production Environment -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Environnement Production</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="stripe_prod_publishable_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Clé publique Production
                            </label>
                            <input type="text" id="stripe_prod_publishable_key" name="stripe_prod_publishable_key" 
                                   value="{{ old('stripe_prod_publishable_key', $stripe['stripe_prod_publishable_key']['value'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="pk_live_...">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Clé publique Stripe pour l'environnement de production
                            </p>
                        </div>
                        <div>
                            <label for="stripe_prod_secret_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Clé secrète Production
                            </label>
                            <input type="password" id="stripe_prod_secret_key" name="stripe_prod_secret_key" 
                                   value="{{ old('stripe_prod_secret_key', $stripe['stripe_prod_secret_key']['has_value'] ? '' : '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="{{ $stripe['stripe_prod_secret_key']['has_value'] ?? false ? '•••••••••••••••• (laisser vide pour conserver)' : 'sk_live_...' }}">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Clé secrète Stripe pour l'environnement de production
                            </p>
                        </div>
                        <div>
                            <label for="stripe_prod_webhook_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Secret webhook Production
                            </label>
                            <input type="password" id="stripe_prod_webhook_secret" name="stripe_prod_webhook_secret" 
                                   value="{{ old('stripe_prod_webhook_secret', $stripe['stripe_prod_webhook_secret']['has_value'] ? '' : '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="{{ $stripe['stripe_prod_webhook_secret']['has_value'] ?? false ? '•••••••••••••••• (laisser vide pour conserver)' : 'whsec_...' }}">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Secret webhook Stripe pour l'environnement de production
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CMI API Settings -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Configuration CMI
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Configurez les clés API CMI (Credit Mutuel International) pour les paiements. Les clés API sont cryptées.
                </p>
            </div>

            <div class="p-6 space-y-6">
                <!-- CMI Environment -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="cmi_environment" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Environnement actif
                        </label>
                        <select id="cmi_environment" name="cmi_environment" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="test" {{ (old('cmi_environment', $cmi['cmi_environment']['value'] ?? 'test') == 'test') ? 'selected' : '' }}>
                                Test
                            </option>
                            <option value="prod" {{ (old('cmi_environment', $cmi['cmi_environment']['value'] ?? '') == 'prod') ? 'selected' : '' }}>
                                Production
                            </option>
                        </select>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Sélectionnez l'environnement CMI à utiliser
                        </p>
                    </div>
                </div>

                <!-- CMI Test Environment -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Environnement Test</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="cmi_test_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Clé API Test (Store Key)
                            </label>
                            <input type="password" id="cmi_test_api_key" name="cmi_test_api_key" 
                                   value="{{ old('cmi_test_api_key', $cmi['cmi_test_api_key']['has_value'] ? '' : '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="{{ $cmi['cmi_test_api_key']['has_value'] ?? false ? '•••••••••••••••• (laisser vide pour conserver)' : 'Votre clé API CMI de test' }}">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Clé API CMI (Store Key) pour l'environnement de test
                            </p>
                        </div>
                        <div>
                            <label for="cmi_test_merchant_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                ID marchand Test (Client ID)
                            </label>
                            <input type="text" id="cmi_test_merchant_id" name="cmi_test_merchant_id" 
                                   value="{{ old('cmi_test_merchant_id', $cmi['cmi_test_merchant_id']['value'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="Votre ID marchand CMI de test">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                ID marchand CMI (Client ID) pour l'environnement de test
                            </p>
                        </div>
                        <div>
                            <label for="cmi_test_api_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                URL API Test
                            </label>
                            <input type="url" id="cmi_test_api_url" name="cmi_test_api_url" 
                                   value="{{ old('cmi_test_api_url', $cmi['cmi_test_api_url']['value'] ?? 'https://testpayment.cmi.co.ma/fim/est3Dgate') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="https://testpayment.cmi.co.ma/fim/est3Dgate">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                URL de l'API CMI pour l'environnement de test
                            </p>
                        </div>
                        <div>
                            <label for="cmi_test_callback_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                URL Callback Test
                            </label>
                            <input type="url" id="cmi_test_callback_url" name="cmi_test_callback_url" 
                                   value="{{ old('cmi_test_callback_url', $cmi['cmi_test_callback_url']['value'] ?? config('app.url')) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="{{ config('app.url') }}">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                URL où CMI redirige après le paiement (environnement de test)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- CMI Production Environment -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Environnement Production</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="cmi_prod_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Clé API Production (Store Key)
                            </label>
                            <input type="password" id="cmi_prod_api_key" name="cmi_prod_api_key" 
                                   value="{{ old('cmi_prod_api_key', $cmi['cmi_prod_api_key']['has_value'] ? '' : '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="{{ $cmi['cmi_prod_api_key']['has_value'] ?? false ? '•••••••••••••••• (laisser vide pour conserver)' : 'Votre clé API CMI de production' }}">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Clé API CMI (Store Key) pour l'environnement de production
                            </p>
                        </div>
                        <div>
                            <label for="cmi_prod_merchant_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                ID marchand Production (Client ID)
                            </label>
                            <input type="text" id="cmi_prod_merchant_id" name="cmi_prod_merchant_id" 
                                   value="{{ old('cmi_prod_merchant_id', $cmi['cmi_prod_merchant_id']['value'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="Votre ID marchand CMI de production">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                ID marchand CMI (Client ID) pour l'environnement de production
                            </p>
                        </div>
                        <div>
                            <label for="cmi_prod_api_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                URL API Production
                            </label>
                            <input type="url" id="cmi_prod_api_url" name="cmi_prod_api_url" 
                                   value="{{ old('cmi_prod_api_url', $cmi['cmi_prod_api_url']['value'] ?? 'https://payment.cmi.co.ma/fim/est3Dgate') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="https://payment.cmi.co.ma/fim/est3Dgate">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                URL de l'API CMI pour l'environnement de production
                            </p>
                        </div>
                        <div>
                            <label for="cmi_prod_callback_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                URL Callback Production
                            </label>
                            <input type="url" id="cmi_prod_callback_url" name="cmi_prod_callback_url" 
                                   value="{{ old('cmi_prod_callback_url', $cmi['cmi_prod_callback_url']['value'] ?? config('app.url')) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="{{ config('app.url') }}">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                URL où CMI redirige après le paiement (environnement de production)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SteVe API Settings -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Configuration SteVe API
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Configurez les identifiants API SteVe pour l'intégration OCPP. Les identifiants sont cryptés.
                </p>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="steve_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Clé API SteVe
                        </label>
                        <input type="password" id="steve_api_key" name="steve_api_key" 
                               value="{{ old('steve_api_key', $steve['steve_api_key']['has_value'] ? '' : '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                               placeholder="{{ $steve['steve_api_key']['has_value'] ?? false ? '•••••••••••••••• (laisser vide pour conserver)' : 'Votre clé API SteVe' }}">
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Clé API pour l'authentification SteVe (optionnel)
                        </p>
                    </div>
                    <div>
                        <label for="steve_api_user" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nom d'utilisateur
                        </label>
                        @php
                            $steveUserValue = old('steve_api_user');
                            if (!$steveUserValue) {
                                if (isset($steve['steve_api_user']['has_value']) && $steve['steve_api_user']['has_value']) {
                                    $steveUserValue = ($steve['steve_api_user']['value'] === '••••••••••••••••') ? 'admin' : $steve['steve_api_user']['raw_value'];
                                } else {
                                    $steveUserValue = 'admin';
                                }
                            }
                        @endphp
                        <input type="text" id="steve_api_user" name="steve_api_user" 
                               value="{{ $steveUserValue }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                               placeholder="admin">
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Nom d'utilisateur pour l'API SteVe (par défaut: admin)
                        </p>
                    </div>
                    <div>
                        <label for="steve_api_pass" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Mot de passe
                        </label>
                        @php
                            $stevePassValue = old('steve_api_pass', '');
                            $stevePassPlaceholder = '1234 (par défaut)';
                            if (isset($steve['steve_api_pass']['has_value']) && $steve['steve_api_pass']['has_value'] && ($steve['steve_api_pass']['value'] ?? '') === '••••••••••••••••') {
                                $stevePassPlaceholder = '•••••••••••••••• (laisser vide pour conserver)';
                            }
                        @endphp
                        <input type="password" id="steve_api_pass" name="steve_api_pass" 
                               value="{{ $stevePassValue }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                               placeholder="{{ $stevePassPlaceholder }}">
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Mot de passe pour l'API SteVe (par défaut: 1234)
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- WebSocket Settings -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Configuration WebSocket
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Configurez les URLs WebSocket pour la communication en temps réel avec SteVe.
                </p>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="steve_websocket_base_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            URL WebSocket SteVe
                        </label>
                        <input type="text" id="steve_websocket_base_url" name="steve_websocket_base_url" 
                               value="{{ old('steve_websocket_base_url', $websocket['steve_websocket_base_url']['value'] ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                               placeholder="ws://158.69.27.239:8080/steve/websocket/CentralSystemService/">
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            URL de base pour les connexions WebSocket SteVe
                        </p>
                    </div>
                    <div>
                        <label for="steve_api_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            URL API SteVe
                        </label>
                        <input type="url" id="steve_api_url" name="steve_api_url" 
                               value="{{ old('steve_api_url', $websocket['steve_api_url']['value'] ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                               placeholder="http://158.69.27.239:8080">
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            URL de base de l'API SteVe
                        </p>
                    </div>
                    <div>
                        <label for="websocket_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Timeout WebSocket (secondes)
                        </label>
                        <input type="number" id="websocket_timeout" name="websocket_timeout" 
                               value="{{ old('websocket_timeout', $websocket['websocket_timeout']['value'] ?? '30') }}"
                               min="1" max="300"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Timeout pour les connexions WebSocket (1-300 secondes)
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bouton de soumission -->
        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                Sauvegarder les identifiants API
            </button>
        </div>
    </form>
</div>
@endsection
