@extends('layouts.app')

@section('title', 'Gestion des Charging Points')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-emerald-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-emerald-900/10">
    <!-- Header with back button and title -->
    <div class="bg-white dark:bg-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.dashboard') }}" class="p-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                        <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Gestion des Charging Points</h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Administrez tous les points de charge du système</p>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.charging-points.export') }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors shadow-lg hover:shadow-xl">
                        <i class="fas fa-download mr-2"></i>Exporter
                    </a>
                    <a href="{{ route('admin.charging-points.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-xl hover:from-emerald-700 hover:to-emerald-800 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-plus mr-2"></i>Créer un Charging Point
                    </a>
                </div>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 py-8">

        @if(session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded-lg shadow-sm" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg shadow-sm" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5 mb-6">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-filter text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Filtres de recherche</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Filtrez les charging points selon vos critères</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <form method="GET" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recherche</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" 
                               placeholder="Nom ou localisation..."
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                        <select name="status" id="status" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">Tous</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                        </select>
                    </div>

                    <div>
                        <label for="visibility" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Visibilité</label>
                        <select name="visibility" id="visibility" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">Tous</option>
                            <option value="public" {{ request('visibility') === 'public' ? 'selected' : '' }}>Public</option>
                            <option value="private" {{ request('visibility') === 'private' ? 'selected' : '' }}>Privé</option>
                        </select>
                    </div>

                    <div>
                        <label for="creator" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Créateur</label>
                        <select name="creator" id="creator" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">Tous</option>
                            @foreach($creators as $creator)
                                <option value="{{ $creator->id }}" {{ request('creator') == $creator->id ? 'selected' : '' }}>
                                    {{ $creator->name }} ({{ $creator->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-4 flex justify-end space-x-4 pt-4">
                        <a href="{{ route('admin.charging-points.index') }}" 
                           class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors">
                            Réinitialiser
                        </a>
                        <button type="submit" 
                                class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-xl text-sm font-medium hover:from-emerald-700 hover:to-emerald-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                            <i class="fas fa-search mr-2"></i>Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($chargingPoints->count() > 0)
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5">
                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($chargingPoints as $chargingPoint)
                        <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="h-12 w-12 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                            <i class="fas fa-charging-station text-white text-lg"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $chargingPoint->name }}
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $chargingPoint->location }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2 mb-3">
                                        @if($chargingPoint->is_public)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                                                <i class="fas fa-globe mr-1"></i>Public
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">
                                                <i class="fas fa-lock mr-1"></i>Privé
                                            </span>
                                        @endif
                                        
                                        @if($chargingPoint->is_active)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                                <i class="fas fa-check-circle mr-1"></i>Actif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300">
                                                <i class="fas fa-times-circle mr-1"></i>Inactif
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-gray-600 dark:text-gray-400">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-bolt text-emerald-500"></i>
                                            <span>{{ $chargingPoint->max_power }} kW</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-plug text-blue-500"></i>
                                            <span>{{ $chargingPoint->connector_type }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-user text-purple-500"></i>
                                            <span>{{ $chargingPoint->user ? $chargingPoint->user->name : 'N/A' }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-calendar mr-1"></i>
                                        Créé le {{ $chargingPoint->created_at->format('d/m/Y à H:i') }}
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2 ml-4">
                                    <a href="{{ route('admin.charging-points.show', $chargingPoint) }}" 
                                       class="px-3 py-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg text-sm font-medium transition-colors">
                                        <i class="fas fa-eye mr-1"></i>Voir
                                    </a>
                                    <a href="{{ route('admin.charging-points.edit', $chargingPoint) }}" 
                                       class="px-3 py-2 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg text-sm font-medium transition-colors">
                                        <i class="fas fa-edit mr-1"></i>Modifier
                                    </a>
                                    
                                    <form action="{{ route('admin.charging-points.toggle-status', $chargingPoint) }}" 
                                          method="POST" 
                                          class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="px-3 py-2 text-{{ $chargingPoint->is_active ? 'red' : 'green' }}-600 hover:text-{{ $chargingPoint->is_active ? 'red' : 'green' }}-800 hover:bg-{{ $chargingPoint->is_active ? 'red' : 'green' }}-50 dark:hover:bg-{{ $chargingPoint->is_active ? 'red' : 'green' }}-900/20 rounded-lg text-sm font-medium transition-colors">
                                            <i class="fas fa-{{ $chargingPoint->is_active ? 'pause' : 'play' }} mr-1"></i>
                                            {{ $chargingPoint->is_active ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </form>
                                    
                                    <form action="{{ route('admin.charging-points.destroy', $chargingPoint) }}" 
                                          method="POST" 
                                          class="inline"
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce charging point ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm font-medium transition-colors">
                                            <i class="fas fa-trash mr-1"></i>Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6">
                {{ $chargingPoints->appends(request()->query())->links() }}
            </div>
        @else
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5">
                <div class="text-center py-16">
                    <div class="mx-auto h-16 w-16 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-charging-station text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Aucun charging point</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">
                        Aucun charging point ne correspond aux critères de recherche.
                    </p>
                    <div>
                        <a href="{{ route('admin.charging-points.create') }}" 
                           class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-xl text-sm font-medium hover:from-emerald-700 hover:to-emerald-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                            <i class="fas fa-plus mr-2"></i>
                            Créer un Charging Point
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </main>
</div>
@endsection
