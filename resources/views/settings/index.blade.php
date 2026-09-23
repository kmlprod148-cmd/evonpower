@extends('layouts.app')

@section('content')
<div class="px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-medium">Paramètres</h1>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p>{{ session('error') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-medium mb-4">Navigation</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="{{ route('settings.general') }}" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <div>
                        <h3 class="font-medium">Paramètres généraux</h3>
                        <p class="text-sm text-gray-500">Configuration générale du système</p>
                    </div>
                </a>

                <a href="{{ route('settings.profile') }}" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <div>
                        <h3 class="font-medium">Profil utilisateur</h3>
                        <p class="text-sm text-gray-500">Gérez vos informations personnelles</p>
                    </div>
                </a>

                <a href="{{ route('settings.security') }}" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <div>
                        <h3 class="font-medium">Sécurité</h3>
                        <p class="text-sm text-gray-500">Mot de passe et paramètres de sécurité</p>
                    </div>
                </a>

                <a href="{{ route('settings.notifications') }}" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                    <div class="mr-3">
                        @include('components.icons.lucide-bell', ['size' => 24, 'class' => 'text-gray-500'])
                    </div>
                    <div>
                        <h3 class="font-medium">Notifications</h3>
                        <p class="text-sm text-gray-500">Gérez vos préférences de notification</p>
                    </div>
                </a>

                <a href="{{ route('settings.billing') }}" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <div>
                        <h3 class="font-medium">Facturation</h3>
                        <p class="text-sm text-gray-500">Gérez vos méthodes de paiement</p>
                    </div>
                </a>

                <a href="{{ route('settings.api.index') }}" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                    <div>
                        <h3 class="font-medium">API</h3>
                        <p class="text-sm text-gray-500">Clés API et configuration</p>
                    </div>
                </a>

                @can('view_commission_settings')
                <a href="{{ route('settings.commission-plans') }}" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <div>
                        <h3 class="font-medium">Plans de Commission</h3>
                        <p class="text-sm text-gray-500">Gérer les plans de commission</p>
                    </div>
                </a>

                <a href="{{ route('settings.commission-dashboard') }}" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <div>
                        <h3 class="font-medium">Tableau de Bord des Commissions</h3>
                        <p class="text-sm text-gray-500">Visualiser les statistiques de commission</p>
                    </div>
                </a>
                @endcan
            </div>
        </div>

        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-medium mb-4">Informations système</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Version du système</h3>
                    <p>{{ config('app.version', '1.0.0') }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Dernière mise à jour</h3>
                    <p>{{ now()->subDays(random_int(1, 30))->format('d/m/Y') }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Environnement</h3>
                    <p>{{ ucfirst(config('app.env')) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Version de PHP</h3>
                    <p>{{ phpversion() }}</p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <h2 class="text-lg font-medium mb-4">Support</h2>
            <div class="flex flex-col md:flex-row items-start gap-6">
                <div class="bg-blue-50 p-4 rounded-lg flex-1">
                    <h3 class="font-medium mb-2">Documentation</h3>
                    <p class="text-sm text-gray-600 mb-3">Consultez notre documentation complète pour obtenir des guides détaillés sur l'utilisation du système.</p>
                    <a href="#" class="text-blue-600 hover:text-blue-800 font-medium text-sm">Accéder à la documentation →</a>
                </div>

                <div class="bg-purple-50 p-4 rounded-lg flex-1">
                    <h3 class="font-medium mb-2">Centre d'aide</h3>
                    <p class="text-sm text-gray-600 mb-3">Des questions? Consultez notre base de connaissances ou contactez notre équipe de support.</p>
                    <a href="#" class="text-purple-600 hover:text-purple-800 font-medium text-sm">Visiter le centre d'aide →</a>
                </div>

                <div class="bg-green-50 p-4 rounded-lg flex-1">
                    <h3 class="font-medium mb-2">Contactez-nous</h3>
                    <p class="text-sm text-gray-600 mb-3">Vous avez besoin d'une assistance personnalisée? Notre équipe est là pour vous aider.</p>
                    <a href="#" class="text-green-600 hover:text-green-800 font-medium text-sm">Envoyer un message →</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection