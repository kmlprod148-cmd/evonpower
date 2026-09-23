@extends('layouts.app')

@section('title', 'Détails de la Borne')
@section('page-title', 'Détails de la Borne')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('integrator.charging-points.index') }}" class="text-gray-500 hover:text-gray-700 mb-2 inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Retour à la liste
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $chargingPoint->name }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $chargingPoint->address }}, {{ $chargingPoint->city }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('integrator.charging-points.edit', $chargingPoint) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                    Modifier
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm" role="alert">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Informations générales -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Informations générales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Nom</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Numéro de série</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->serial_number ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Statut</label>
                        <p class="text-sm mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $chargingPoint->status === 'online' ? 'bg-green-100 text-green-800' : 
                                   ($chargingPoint->status === 'offline' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($chargingPoint->status) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Puissance</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->power_output ?? 'N/A' }} kW</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Adresse</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->address }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Ville</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->city }}</p>
                    </div>
                    @if($chargingPoint->description)
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->description }}</p>
                    </div>
                    @endif
                </div>
            </div>

            @php
                $hasRealProfiles = isset($businessProfiles) && is_array($businessProfiles) && isset($calculation) && is_array($calculation);
            @endphp

            @if($hasRealProfiles)
            <!-- Calculs Appliqués (réels uniquement) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Calculs Appliqués</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                        <div class="space-y-2 text-sm text-green-900">
                            <div class="flex justify-between"><span>Transaction</span><span>Admin → Intégrateur</span></div>
                            <div class="flex justify-between"><span>Part Admin</span><span>{{ number_format($calculation['admin_percentage'] ?? 0, 0) }}%</span></div>
                            <div class="flex justify-between"><span>Profil</span><span>{{ $businessProfiles['admin_integrator']->name ?? '—' }}</span></div>
                        </div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                        <div class="space-y-2 text-sm text-green-900">
                            <div class="flex justify-between"><span>Transaction</span><span>Intégrateur → Opérateur</span></div>
                            <div class="flex justify-between"><span>Part Intégrateur</span><span>{{ number_format($calculation['integrator_percentage'] ?? 0, 0) }}%</span></div>
                            <div class="flex justify-between"><span>Profil</span><span>{{ $businessProfiles['integrator_operator']->name ?? '—' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Connecteurs -->
            @if($chargingPoint->connectors && $chargingPoint->connectors->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Connecteurs</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Puissance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($chargingPoint->connectors as $connector)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $connector->type }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $connector->power }} kW</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $connector->status === 'available' ? 'bg-green-100 text-green-800' : 
                                           ($connector->status === 'occupied' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                        {{ ucfirst($connector->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Statut -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Statut</h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Statut actuel</label>
                        <p class="text-sm mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $chargingPoint->status === 'online' ? 'bg-green-100 text-green-800' : 
                                   ($chargingPoint->status === 'offline' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($chargingPoint->status) }}
                            </span>
                        </p>
                    </div>
                    @if($chargingPoint->last_connection_attempt)
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Dernière tentative de connexion</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->last_connection_attempt->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Informations liées -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Informations liées</h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Opérateur</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->user->name ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $chargingPoint->user->email ?? '' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Business Profile</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->businessProfile->name ?? 'Aucun' }}</p>
                    </div>
                    @if($chargingPoint->group)
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Groupe</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->group->name }}</p>
                    </div>
                    @endif
                    @if($chargingPoint->pricingPlan)
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Plan tarifaire</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->pricingPlan->name }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Coordonnées GPS -->
            @if($chargingPoint->latitude && $chargingPoint->longitude)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Coordonnées GPS</h2>
                <div class="space-y-2">
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Latitude</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->latitude }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Longitude</label>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $chargingPoint->longitude }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

