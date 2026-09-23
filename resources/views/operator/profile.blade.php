@extends('layouts.app')

@section('title', 'Mon Profil Système')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Mon Profil Système</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Gérez vos informations personnelles</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informations principales -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Informations personnelles</h3>
                    <a href="{{ route('operator.profile.edit') }}" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                        Modifier
                    </a>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom complet</label>
                            <p class="text-gray-900 dark:text-white">{{ $user->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <p class="text-gray-900 dark:text-white">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Téléphone</label>
                            <p class="text-gray-900 dark:text-white">{{ $user->phone ?? 'Non renseigné' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de compte</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Opérateur Système
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Intégrateur</label>
                            <p class="text-gray-900 dark:text-white">{{ $user->integrator->name ?? 'Non assigné' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Partenaire</label>
                            <p class="text-gray-900 dark:text-white">{{ $user->partner->name ?? 'Non assigné' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Compte créé le</label>
                            <p class="text-gray-900 dark:text-white">{{ $user->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dernière connexion</label>
                            <p class="text-gray-900 dark:text-white">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y à H:i') : 'Jamais' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques du compte -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Statistiques du compte</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->chargingPoints()->count() }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Points de charge</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->transactions()->count() }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Transactions</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->transactions()->sum('amount') }} €</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Chiffre d'affaires</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Actions rapides -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Actions rapides</h3>
                <div class="space-y-3">
                    <a href="{{ route('operator.dashboard') }}" 
                       class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded-lg transition-colors">
                        Dashboard
                    </a>
                    <a href="{{ route('operator.profile.edit') }}" 
                       class="block w-full bg-green-600 hover:bg-green-700 text-white text-center py-2 px-4 rounded-lg transition-colors">
                        Modifier le profil
                    </a>
                    <a href="{{ route('charging-points.index') }}" 
                       class="block w-full bg-purple-600 hover:bg-purple-700 text-white text-center py-2 px-4 rounded-lg transition-colors">
                        Mes points de charge
                    </a>
                </div>
            </div>

            <!-- Informations de sécurité -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sécurité</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Mot de passe</span>
                        <span class="text-sm text-green-600 dark:text-green-400">✓ Défini</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Email vérifié</span>
                        <span class="text-sm text-green-600 dark:text-green-400">✓ Vérifié</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Compte actif</span>
                        <span class="text-sm text-green-600 dark:text-green-400">✓ Actif</span>
                    </div>
                </div>
            </div>

            <!-- Rôles et permissions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Rôles et permissions</h3>
                <div class="space-y-2">
                    @foreach($user->roles as $role)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $role->name }}
                    </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
