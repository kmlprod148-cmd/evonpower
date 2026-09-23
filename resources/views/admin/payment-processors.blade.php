@extends('layouts.app')

@section('title', 'Processeurs de Paiement & Devise')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- En-tête -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Processeurs de Paiement & Devise
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Gérez les paramètres des processeurs de paiement (CMI, Stripe) et la devise de l'application
                </p>
            </div>
            <div class="flex space-x-3">
                <button type="button" id="testCmiBtn" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tester CMI
                </button>
                <button type="button" id="testStripeBtn" 
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tester Stripe
                </button>
                <a href="{{ route('admin.payment-processors.reset') }}" 
                   class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors"
                   onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser tous les paramètres ?')">
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

    <!-- Onglets -->
    <div class="mb-8">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showTab('cmi')" id="cmi-tab" class="tab-button active py-2 px-1 border-b-2 font-medium text-sm">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Configuration CMI
                </button>
                <button onclick="showTab('stripe')" id="stripe-tab" class="tab-button py-2 px-1 border-b-2 font-medium text-sm">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Configuration Stripe
                </button>
                <button onclick="showTab('currency')" id="currency-tab" class="tab-button py-2 px-1 border-b-2 font-medium text-sm">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                    Paramètres de Devise
                </button>
            </nav>
        </div>
    </div>

    <!-- Contenu des onglets -->
    <div class="space-y-8">
        <!-- Onglet CMI -->
        <div id="cmi-content" class="tab-content">
            <form action="{{ route('admin.payment-processors.update-payment-processors') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Configuration API CMI
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Paramètres de connexion à l'API CMI pour les paiements
                        </p>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- CMI Base URL -->
                        <div>
                            <label for="cmi_base_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                URL de base CMI <span class="text-red-500">*</span>
                            </label>
                            <input type="url" id="cmi_base_url" name="cmi_base_url" 
                                   value="{{ old('cmi_base_url', $paymentProcessors['cmi_base_url']['value'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   required>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                URL de base de l'API CMI (ex: https://testpayment.cmi.co.ma)
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- CMI Store Key -->
                            <div>
                                <label for="cmi_store_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Clé de magasin CMI
                                </label>
                                <input type="password" id="cmi_store_key" name="cmi_store_key" 
                                       value="{{ old('cmi_store_key', $paymentProcessors['cmi_store_key']['value'] ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                       placeholder="Votre clé de magasin CMI">
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Clé de magasin fournie par CMI
                                </p>
                            </div>

                            <!-- CMI Store Password -->
                            <div>
                                <label for="cmi_store_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Mot de passe de magasin CMI
                                </label>
                                <input type="password" id="cmi_store_password" name="cmi_store_password" 
                                       value="{{ old('cmi_store_password', $paymentProcessors['cmi_store_password']['value'] ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                       placeholder="Votre mot de passe de magasin">
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Mot de passe associé à la clé de magasin
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- CMI Client ID -->
                            <div>
                                <label for="cmi_client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    ID client CMI
                                </label>
                                <input type="password" id="cmi_client_id" name="cmi_client_id" 
                                       value="{{ old('cmi_client_id', $paymentProcessors['cmi_client_id']['value'] ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                       placeholder="Votre ID client CMI">
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Identifiant client CMI
                                </p>
                            </div>

                            <!-- CMI Username -->
                            <div>
                                <label for="cmi_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Nom d'utilisateur CMI
                                </label>
                                <input type="text" id="cmi_username" name="cmi_username" 
                                       value="{{ old('cmi_username', $paymentProcessors['cmi_username']['value'] ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                       placeholder="Votre nom d'utilisateur CMI">
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Nom d'utilisateur pour l'API CMI
                                </p>
                            </div>
                        </div>

                        <!-- CMI Password -->
                        <div>
                            <label for="cmi_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Mot de passe CMI
                            </label>
                            <input type="password" id="cmi_password" name="cmi_password" 
                                   value="{{ old('cmi_password', $paymentProcessors['cmi_password']['value'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="Votre mot de passe CMI">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Mot de passe pour l'API CMI
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- CMI Currency -->
                            <div>
                                <label for="cmi_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Devise CMI <span class="text-red-500">*</span>
                                </label>
                                <select id="cmi_currency" name="cmi_currency" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        required>
                                    <option value="EUR" {{ (old('cmi_currency', $paymentProcessors['cmi_currency']['value'] ?? 'EUR') == 'EUR') ? 'selected' : '' }}>
                                        Euro (EUR)
                                    </option>
                                    <option value="USD" {{ (old('cmi_currency', $paymentProcessors['cmi_currency']['value'] ?? '') == 'USD') ? 'selected' : '' }}>
                                        Dollar américain (USD)
                                    </option>
                                    <option value="MAD" {{ (old('cmi_currency', $paymentProcessors['cmi_currency']['value'] ?? '') == 'MAD') ? 'selected' : '' }}>
                                        Dirham marocain (MAD)
                                    </option>
                                </select>
                            </div>

                            <!-- CMI Test Mode -->
                            <div>
                                <label for="cmi_test_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Mode test CMI
                                </label>
                                <select id="cmi_test_mode" name="cmi_test_mode" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="1" {{ (old('cmi_test_mode', $paymentProcessors['cmi_test_mode']['value'] ?? '1') == '1' || old('cmi_test_mode', $paymentProcessors['cmi_test_mode']['value'] ?? true) === true) ? 'selected' : '' }}>
                                        Activé
                                    </option>
                                    <option value="0" {{ (old('cmi_test_mode', $paymentProcessors['cmi_test_mode']['value'] ?? '') == '0' || old('cmi_test_mode', $paymentProcessors['cmi_test_mode']['value'] ?? '') === false) ? 'selected' : '' }}>
                                        Désactivé
                                    </option>
                                </select>
                            </div>

                            <!-- CMI Timeout -->
                            <div>
                                <label for="cmi_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Timeout (secondes)
                                </label>
                                <input type="number" id="cmi_timeout" name="cmi_timeout" 
                                       value="{{ old('cmi_timeout', $paymentProcessors['cmi_timeout']['value'] ?? '30') }}"
                                       min="1" max="300"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Timeout pour les requêtes CMI (1-300 secondes)
                                </p>
                            </div>
                        </div>

                        <!-- Webhook URLs Section -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">URLs de Webhooks CMI</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- CMI Webhook Success URL -->
                                <div>
                                    <label for="cmi_webhook_success_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        URL de callback succès
                                    </label>
                                    <input type="url" id="cmi_webhook_success_url" name="cmi_webhook_success_url" 
                                           value="{{ old('cmi_webhook_success_url', $paymentProcessors['cmi_webhook_success_url']['value'] ?? route('payment.cmi.success')) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        URL appelée par CMI en cas de paiement réussi
                                    </p>
                                </div>

                                <!-- CMI Webhook Failure URL -->
                                <div>
                                    <label for="cmi_webhook_failure_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        URL de callback échec
                                    </label>
                                    <input type="url" id="cmi_webhook_failure_url" name="cmi_webhook_failure_url" 
                                           value="{{ old('cmi_webhook_failure_url', $paymentProcessors['cmi_webhook_failure_url']['value'] ?? route('payment.cmi.failure')) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        URL appelée par CMI en cas d'échec de paiement
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings Section -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Paramètres de Sécurité</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Verify Signatures -->
                                <div>
                                    <label for="verify_signatures" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Vérifier les signatures
                                    </label>
                                    <select id="verify_signatures" name="verify_signatures" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="1" {{ (old('verify_signatures', $paymentProcessors['verify_signatures']['value'] ?? '1') == '1' || old('verify_signatures', $paymentProcessors['verify_signatures']['value'] ?? true) === true) ? 'selected' : '' }}>
                                            Activé
                                        </option>
                                        <option value="0" {{ (old('verify_signatures', $paymentProcessors['verify_signatures']['value'] ?? '') == '0' || old('verify_signatures', $paymentProcessors['verify_signatures']['value'] ?? '') === false) ? 'selected' : '' }}>
                                            Désactivé
                                        </option>
                                    </select>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Vérifier les signatures des webhooks
                                    </p>
                                </div>

                                <!-- Allowed IPs -->
                                <div>
                                    <label for="allowed_ips" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        IPs autorisées
                                    </label>
                                    <input type="text" id="allowed_ips" name="allowed_ips" 
                                           value="{{ old('allowed_ips', $paymentProcessors['allowed_ips']['value'] ?? '') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                           placeholder="192.168.1.1, 10.0.0.1">
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        IPs autorisées pour les webhooks (séparées par des virgules)
                                    </p>
                                </div>

                                <!-- Payment Timeout -->
                                <div>
                                    <label for="payment_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Timeout général (secondes)
                                    </label>
                                    <input type="number" id="payment_timeout" name="payment_timeout" 
                                           value="{{ old('payment_timeout', $paymentProcessors['payment_timeout']['value'] ?? '30') }}"
                                           min="1" max="300"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Timeout général pour les paiements (1-300 secondes)
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Sauvegarder CMI
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Onglet Stripe -->
        <div id="stripe-content" class="tab-content hidden">
            <form action="{{ route('admin.payment-processors.update-payment-processors') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Configuration API Stripe
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Paramètres de connexion à l'API Stripe pour les paiements
                        </p>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Stripe Secret Key -->
                        <div>
                            <label for="stripe_secret_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Clé Secrète Stripe
                            </label>
                            <input type="password" id="stripe_secret_key" name="stripe_secret_key" 
                                   value="{{ old('stripe_secret_key', $paymentProcessors['stripe_secret_key']['value'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   required>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Votre clé secrète Stripe (commence par sk_live_ ou sk_test_)
                            </p>
                        </div>

                        <!-- Stripe Public Key -->
                        <div>
                            <label for="stripe_public_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Clé Publique Stripe
                            </label>
                            <input type="text" id="stripe_public_key" name="stripe_public_key" 
                                   value="{{ old('stripe_public_key', $paymentProcessors['stripe_public_key']['value'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   required>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Votre clé publique Stripe (commence par pk_live_ ou pk_test_)
                            </p>
                        </div>

                        <!-- Stripe Webhook Secret -->
                        <div>
                            <label for="stripe_webhook_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Clé Secrète Webhook Stripe
                            </label>
                            <input type="password" id="stripe_webhook_secret" name="stripe_webhook_secret" 
                                   value="{{ old('stripe_webhook_secret', $paymentProcessors['stripe_webhook_secret']['value'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                La clé secrète de signature de votre webhook Stripe (facultatif)
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Stripe Currency -->
                            <div>
                                <label for="stripe_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Devise Stripe <span class="text-red-500">*</span>
                                </label>
                                <select id="stripe_currency" name="stripe_currency" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        required>
                                    <option value="eur" {{ (old('stripe_currency', $paymentProcessors['stripe_currency']['value'] ?? 'eur') == 'eur') ? 'selected' : '' }}>
                                        Euro (eur)
                                    </option>
                                    <option value="usd" {{ (old('stripe_currency', $paymentProcessors['stripe_currency']['value'] ?? '') == 'usd') ? 'selected' : '' }}>
                                        Dollar américain (usd)
                                    </option>
                                    <option value="mad" {{ (old('stripe_currency', $paymentProcessors['stripe_currency']['value'] ?? '') == 'mad') ? 'selected' : '' }}>
                                        Dirham marocain (mad)
                                    </option>
                                </select>
                            </div>

                            <!-- Stripe Test Mode -->
                            <div>
                                <label for="stripe_test_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Mode test Stripe
                                </label>
                                <select id="stripe_test_mode" name="stripe_test_mode" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="1" {{ (old('stripe_test_mode', $paymentProcessors['stripe_test_mode']['value'] ?? '1') == '1' || old('stripe_test_mode', $paymentProcessors['stripe_test_mode']['value'] ?? true) === true) ? 'selected' : '' }}>
                                        Activé
                                    </option>
                                    <option value="0" {{ (old('stripe_test_mode', $paymentProcessors['stripe_test_mode']['value'] ?? '') == '0' || old('stripe_test_mode', $paymentProcessors['stripe_test_mode']['value'] ?? '') === false) ? 'selected' : '' }}>
                                        Désactivé
                                    </option>
                                </select>
                            </div>

                            <!-- Stripe Timeout -->
                            <div>
                                <label for="stripe_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Timeout (secondes)
                                </label>
                                <input type="number" id="stripe_timeout" name="stripe_timeout" 
                                       value="{{ old('stripe_timeout', $paymentProcessors['stripe_timeout']['value'] ?? '30') }}"
                                       min="1" max="300"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Timeout pour les requêtes Stripe (1-300 secondes)
                                </p>
                            </div>
                        </div>

                        <!-- Stripe Webhook URL -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Webhook Stripe</h3>
                            
                            <div>
                                <label for="stripe_webhook_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    URL de webhook Stripe
                                </label>
                                <input type="url" id="stripe_webhook_url" name="stripe_webhook_url" 
                                       value="{{ old('stripe_webhook_url', $paymentProcessors['stripe_webhook_url']['value'] ?? route('payment.stripe.webhook')) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    URL à configurer dans le tableau de bord Stripe pour recevoir les événements de paiement
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-500 active:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Sauvegarder Stripe
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Onglet Devise -->
        <div id="currency-content" class="tab-content hidden">
            <form action="{{ route('admin.payment-processors.update-currency') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
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
                                Devise par défaut de l'application
                            </label>
                            <select id="app_default_currency" name="app_default_currency" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    required>
                                <option value="EUR" {{ (old('app_default_currency', $currencySettings['app_default_currency']['value'] ?? '') == 'EUR') ? 'selected' : '' }}>
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
                                Symbole de devise
                            </label>
                            <input type="text" id="app_currency_symbol" name="app_currency_symbol" 
                                   value="{{ old('app_currency_symbol', $currencySettings['app_currency_symbol']['value'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   required>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Symbole affiché pour la devise par défaut
                            </p>
                        </div>

                        <!-- App Currency Format -->
                        <div>
                            <label for="app_currency_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Format de devise
                            </label>
                            <select id="app_currency_format" name="app_currency_format" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    required>
                                <option value="0" {{ (old('app_currency_format', $currencySettings['app_currency_format']['value'] ?? '') == '0') ? 'selected' : '' }}>
                                    Pas de décimales (ex: 100)
                                </option>
                                <option value="1" {{ (old('app_currency_format', $currencySettings['app_currency_format']['value'] ?? '') == '1') ? 'selected' : '' }}>
                                    1 décimale (ex: 100.0)
                                </option>
                                <option value="2" {{ (old('app_currency_format', $currencySettings['app_currency_format']['value'] ?? '') == '2') ? 'selected' : '' }}>
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

// Test de connexion CMI
document.getElementById('testCmiBtn').addEventListener('click', function() {
    const formData = new FormData();
    formData.append('cmi_base_url', document.getElementById('cmi_base_url')?.value || '');
    formData.append('cmi_store_key', document.getElementById('cmi_store_key')?.value || '');
    formData.append('cmi_store_password', document.getElementById('cmi_store_password')?.value || '');
    
    fetch('{{ route("admin.payment-processors.test-cmi") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message + (data.details ? '\n\nDétails: ' + JSON.stringify(data.details, null, 2) : ''));
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Erreur lors du test: ' + error.message);
    });
});

// Test de connexion Stripe
document.getElementById('testStripeBtn').addEventListener('click', function() {
    const formData = new FormData();
    formData.append('stripe_secret_key', document.getElementById('stripe_secret_key').value);
    
    fetch('{{ route("admin.payment-processors.test-stripe") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Erreur lors du test: ' + error.message);
    });
});
</script>

<style>
.tab-button.active {
    border-color: #3b82f6;
    color: #2563eb;
}
</style>
@endsection
