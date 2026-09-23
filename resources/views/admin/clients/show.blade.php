@extends('layouts.app')

@section('title', 'Détails du Client')
@section('page-title', 'Détails du Client')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center">
                <a href="{{ route('admin.clients.index') }}" class="mr-4 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $client->full_name }}</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Client depuis le {{ $client->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.clients.edit', $client) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                    Modifier
                </a>
            </div>
        </div>

        <!-- Status Badges -->
        <div class="flex items-center space-x-2 mb-6">
            @if($client->is_active)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                    Actif
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                    Inactif
                </span>
            @endif
            
            @if($client->email_verified_at)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                    Email vérifié
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                    Email non vérifié
                </span>
            @endif

            <form action="{{ route('admin.clients.toggle-active', $client) }}" method="POST" class="inline">
                @csrf
                @method('PUT')
                <button type="submit" 
                        class="px-3 py-1 rounded-full text-sm font-medium {{ $client->is_active ? 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' : 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400' }}">
                    {{ $client->is_active ? 'Désactiver' : 'Activer' }}
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Personal Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Personal Information -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Informations Personnelles</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Nom</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $client->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Prénom</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $client->first_name ?? 'Non défini' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $client->email }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Téléphone</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $client->phone ?? 'Non défini' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Adresse</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $client->address ?? 'Non définie' }}
                                @if($client->city)
                                    , {{ $client->city }}
                                @endif
                                @if($client->postal_code)
                                    , {{ $client->postal_code }}
                                @endif
                                @if($client->country)
                                    , {{ $client->country }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Vehicles -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Véhicules</h2>
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            Ajouter un véhicule
                        </a>
                    </div>
                    @if($client->vehicles->count() > 0)
                        <div class="space-y-4">
                            @foreach($client->vehicles as $vehicle)
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="h-12 w-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $vehicle->make }} {{ $vehicle->model }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $vehicle->registration }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($vehicle->is_primary)
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded">
                                                Principal
                                            </span>
                                        @endif
                                        @if($vehicle->connector_type)
                                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 rounded">
                                                {{ $vehicle->connector_type }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Aucun véhicule enregistré</p>
                    @endif
                </div>

                <!-- OCPP Tags -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Tags OCPP</h2>
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            Ajouter un tag
                        </a>
                    </div>
                    @if($client->ocppTags->count() > 0)
                        <div class="space-y-4">
                            @foreach($client->ocppTags as $tag)
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $tag->ocpp_tag }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $tag->total_sessions }} sessions • Dernière utilisation: {{ $tag->last_used_at ? $tag->last_used_at->format('d/m/Y H:i') : 'Jamais' }}
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($tag->blocked)
                                            <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded">
                                                Bloqué
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded">
                                                Actif
                                            </span>
                                        @endif
                                        @if($tag->is_default)
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded">
                                                Par défaut
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Aucun tag OCPP associé</p>
                    @endif
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Quick Stats -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Statistiques</h2>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Réservations</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $client->reservations->count() }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Transactions</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $client->transactions->count() }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Dernière connexion</p>
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                {{ $client->last_login_at ? $client->last_login_at->format('d/m/Y H:i') : 'Jamais' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Informations du Compte</h2>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Créé par</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $client->user->name ?? 'Système' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Date de création</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $client->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @if($client->activated_at)
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Activé le</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $client->activated_at->format('d/m/Y H:i') }}</p>
                            </div>
                        @endif
                        @if(!$client->email_verified_at)
                            <form action="{{ route('admin.clients.verify-email', $client) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full mt-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                    Vérifier l'email
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-red-200 dark:border-red-900/50 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">Zone Dangereuse</h2>
                    <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce client? Cette action est irréversible.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
                                {{ $client->transactions->count() > 0 ? 'disabled' : '' }}>
                            Supprimer le client
                        </button>
                        @if($client->transactions->count() > 0)
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Impossible de supprimer ce client car il a des transactions associées.
                            </p>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
