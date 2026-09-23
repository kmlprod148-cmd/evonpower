@extends('layouts.app')

@section('title', 'Test de paiement CMI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- En-tête -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    <svg class="w-8 h-8 inline-block mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Test de paiement CMI
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Testez l'intégration CMI avec des cartes de test
                </p>
            </div>
            <a href="{{ route('admin.cmi-api-settings.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Configuration CMI
            </a>
        </div>
    </div>

    <!-- Messages d'alerte -->
    @if(session('success'))
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex items-center">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg flex items-center">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        {{ session('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Configuration actuelle -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Configuration actuelle
                </h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">Identifiant marchand</span>
                        <span class="font-mono text-gray-900 dark:text-white">
                            {{ $cmiConfig['client_id'] ?: 'Non configuré' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">Clé de hachage</span>
                        <span class="font-mono {{ $cmiConfig['store_key'] ? 'text-green-600' : 'text-red-600' }}">
                            {{ $cmiConfig['store_key'] ?: 'Non configurée' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">Environnement</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cmiConfig['environment'] === 'prod' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $cmiConfig['environment'] === 'prod' ? 'Production' : 'Test' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">URL API</span>
                        <span class="font-mono text-xs text-gray-900 dark:text-white truncate max-w-xs">
                            {{ $cmiConfig['api_url'] }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-3">
                        <span class="text-gray-600 dark:text-gray-400">Statut</span>
                        @if($cmiConfig['is_configured'])
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Prêt
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Configuration incomplète
                            </span>
                        @endif
                    </div>
                </div>

                @if(!$cmiConfig['is_configured'])
                <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Configuration requise
                    </h4>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p>Pour configurer la clé de hachage CMI :</p>
                        <ol class="list-decimal list-inside mt-2 space-y-1">
                            <li>Connectez-vous au <a href="https://testpayment.cmi.co.ma/cmi/report" target="_blank" class="underline font-medium">back office CMI</a></li>
                            <li>Utilisateur: <code class="bg-yellow-200 dark:bg-yellow-800 px-1 rounded">bd_a</code> / Mot de passe: <code class="bg-yellow-200 dark:bg-yellow-800 px-1 rounded">Bd_a2021</code></li>
                            <li>Allez dans <strong>Administration → Changer les clés du magasin</strong></li>
                            <li>Configurez la clé dans les paramètres admin ou le fichier .env</li>
                        </ol>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Formulaire de test -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Effectuer un paiement de test
                </h2>
            </div>
            <form action="{{ route('admin.cmi-test.initiate') }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-6">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Montant
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   id="amount" 
                                   name="amount" 
                                   value="10"
                                   min="1"
                                   max="1000"
                                   step="0.01"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="10.00"
                                   required
                                   {{ !$cmiConfig['is_configured'] ? 'disabled' : '' }}>
                        </div>
                    </div>

                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Devise
                        </label>
                        <select id="currency" 
                                name="currency" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                {{ !$cmiConfig['is_configured'] ? 'disabled' : '' }}>
                            <option value="MAD" selected>MAD - Dirham marocain</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="USD">USD - Dollar américain</option>
                        </select>
                    </div>

                    <button type="submit" 
                            class="w-full inline-flex justify-center items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium {{ !$cmiConfig['is_configured'] ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ !$cmiConfig['is_configured'] ? 'disabled' : '' }}>
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Initier le paiement de test
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cartes de test -->
    <div class="mt-8 bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Cartes de test CMI
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Utilisez ces cartes pour tester les différents scénarios de paiement
            </p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($testCards as $card)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $card['color'] === 'blue' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $card['color'] === 'orange' ? 'bg-orange-100 text-orange-800' : '' }}
                            {{ $card['color'] === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                            {{ $card['brand'] }}
                        </span>
                        <span class="text-xs text-gray-500">{{ $card['comment'] }}</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Numéro</span>
                            <code class="text-sm font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded select-all">{{ $card['number'] }}</code>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Expiration</span>
                            <code class="text-sm font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $card['expiry_month'] }}/{{ substr($card['expiry_year'], -2) }}</code>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">CVS</span>
                            <code class="text-sm font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $card['cvs'] }}</code>
                        </div>
                        @if($card['auth_code'] !== 'N/A')
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Code 3DS</span>
                            <code class="text-sm font-mono bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200 px-2 py-0.5 rounded">{{ $card['auth_code'] }}</code>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Informations back office -->
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Accès au back office CMI
        </h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-blue-700 dark:text-blue-300"><strong>URL:</strong> 
                    <a href="https://testpayment.cmi.co.ma/cmi/report" target="_blank" class="underline">
                        https://testpayment.cmi.co.ma/cmi/report
                    </a>
                </p>
                <p class="text-blue-700 dark:text-blue-300"><strong>Identifiant marchand:</strong> 600002823</p>
            </div>
            <div>
                <p class="text-blue-700 dark:text-blue-300"><strong>Utilisateur:</strong> bd_a</p>
                <p class="text-blue-700 dark:text-blue-300"><strong>Mot de passe:</strong> Bd_a2021 <span class="text-blue-500">(à changer à la 1ère connexion)</span></p>
            </div>
        </div>
    </div>
</div>
@endsection
