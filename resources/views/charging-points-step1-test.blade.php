{{-- Page de test pour la route charging-points/create/step1 --}}
@extends('layouts.app-fixed')

@section('title', 'Test Route Charging Points Step1 - EVON')
@section('description', 'Test de la route charging-points/create/step1')

@section('content')
<div class="w-full">
    <!-- Test de la route charging-points/create/step1 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Test Route Charging Points Step1</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Cette page teste l'accès à la route charging-points/create/step1.
        </p>
        
        <!-- Indicateur d'état -->
        <div class="bg-gradient-to-r from-green-500 to-blue-600 text-white p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-semibold">
                    ✅ Test Route Step1 | 
                    Utilisateur: <span id="user-name">{{ Auth::user()->name ?? 'Non connecté' }}</span> | 
                    Rôles: <span id="user-roles">{{ Auth::user()->getRoleNames()->implode(', ') ?? 'Aucun' }}</span>
                </span>
            </div>
        </div>

        <!-- Test des routes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Route Step1</h3>
                <div class="space-y-2">
                    <a href="{{ route('charging-points.create.step1') }}" 
                       class="block w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-600 transition-colors text-center">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Accéder à Step1
                    </a>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Route: {{ route('charging-points.create.step1') }}
                    </p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Route Create</h3>
                <div class="space-y-2">
                    <a href="{{ route('charging-points.create') }}" 
                       class="block w-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-center">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Accéder à Create
                    </a>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Route: {{ route('charging-points.create') }}
                    </p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Route Index</h3>
                <div class="space-y-2">
                    <a href="{{ route('charging-points.index') }}" 
                       class="block w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors text-center">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        Accéder à Index
                    </a>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Route: {{ route('charging-points.index') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Test des permissions -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Test des Permissions</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">create_charging_points</span>
                        <span class="px-2 py-1 rounded text-xs {{ Auth::user()->can('create_charging_points') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ Auth::user()->can('create_charging_points') ? 'Oui' : 'Non' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">view_charging_points</span>
                        <span class="px-2 py-1 rounded text-xs {{ Auth::user()->can('view_charging_points') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ Auth::user()->can('view_charging_points') ? 'Oui' : 'Non' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">edit_charging_points</span>
                        <span class="px-2 py-1 rounded text-xs {{ Auth::user()->can('edit_charging_points') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ Auth::user()->can('edit_charging_points') ? 'Oui' : 'Non' }}
                        </span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">delete_charging_points</span>
                        <span class="px-2 py-1 rounded text-xs {{ Auth::user()->can('delete_charging_points') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ Auth::user()->can('delete_charging_points') ? 'Oui' : 'Non' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">manage_charging_points</span>
                        <span class="px-2 py-1 rounded text-xs {{ Auth::user()->can('manage_charging_points') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ Auth::user()->can('manage_charging_points') ? 'Oui' : 'Non' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test des rôles -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Test des Rôles</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Admin</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ Auth::user()->hasRole('admin') ? 'Oui' : 'Non' }}
                    </p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Integrator</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ Auth::user()->hasRole('integrator') ? 'Oui' : 'Non' }}
                    </p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Operator</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ Auth::user()->hasRole('operator') ? 'Oui' : 'Non' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Instructions de test -->
        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
            <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Instructions de Test</h3>
            <ul class="text-gray-700 dark:text-gray-300 text-sm space-y-1">
                <li>• <strong>Route Step1</strong> : Cliquez sur "Accéder à Step1" pour tester la route</li>
                <li>• <strong>Permissions</strong> : Vérifiez que vous avez les bonnes permissions</li>
                <li>• <strong>Rôles</strong> : Vérifiez que vous avez le bon rôle</li>
                <li>• <strong>Erreurs</strong> : Si vous obtenez une erreur 403, vérifiez les permissions</li>
                <li>• <strong>Redirection</strong> : Si vous êtes redirigé, vérifiez les middlewares</li>
            </ul>
        </div>
    </div>
</div>

<script>
function updateStatus() {
    // Update user info
    const userName = document.getElementById('user-name');
    const userRoles = document.getElementById('user-roles');
    
    if (userName) {
        userName.textContent = '{{ Auth::user()->name ?? "Non connecté" }}';
    }
    
    if (userRoles) {
        userRoles.textContent = '{{ Auth::user()->getRoleNames()->implode(", ") ?? "Aucun" }}';
    }
}

// Update status on load
document.addEventListener('DOMContentLoaded', updateStatus);
</script>
@endsection
