@extends('layouts.app')

@section('page-title', 'Points de charge')

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
    <div>
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Gérez vos bornes de recharge</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Surveillez et administrez vos points de charge</p>
    </div>
    <div class="mt-3 sm:mt-0">
        <a href="{{ route('charging-points.create.step1') }}" 
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Ajouter une borne
        </a>
    </div>
</div>

        <!-- Messages de notification -->
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

        <!-- Enhanced Mobile-Friendly Search and Filters -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 p-4 sm:p-6 mb-6">
            <form action="{{ route('charging-points.index') }}" method="GET" class="space-y-4">
                <!-- Search Input -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-green-500 text-sm" 
                           placeholder="Rechercher par nom, modèle, numéro de série, ville...">
                </div>
                
                <!-- Filters Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                        <select name="status" id="status" class="w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-green-500 text-sm">
                            <option value="">Tous les statuts</option>
                            <option value="online" {{ request('status') == 'online' ? 'selected' : '' }}>En ligne</option>
                            <option value="offline" {{ request('status') == 'offline' ? 'selected' : '' }}>Hors ligne</option>
                            <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <svg class="h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filtrer
                        </button>
                    </div>
                </div>
                
                <!-- Clear Filters (if any filters are active) -->
                @if(request('search') || request('status'))
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('charging-points.index') }}" 
                           class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Effacer les filtres
                        </a>
                    </div>
                @endif
            </form>
        </div>

        <!-- Enhanced Responsive Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6">
            <!-- Total Charging Points Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total des points de charge</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $chargingPoints->total() }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Active Charging Points Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Points actifs</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $stats['activeChargingPoints'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Maintenance Charging Points Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow duration-200 sm:col-span-2 lg:col-span-1">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">En maintenance</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $stats['maintenanceChargingPoints'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Charging Points List -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Header with Filters -->
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Points de charge</h3>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('charging-points.index') }}" 
                           class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors min-h-[36px] {{ !request()->has('filter') ? 'bg-gray-100 dark:bg-gray-600 border-gray-400 dark:border-gray-500' : '' }}">
                            Tous
                        </a>
                        <a href="{{ route('charging-points.index', ['filter' => 'online']) }}" 
                           class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors min-h-[36px] {{ request('filter') == 'online' ? 'bg-green-100 dark:bg-green-900 border-green-400 dark:border-green-700 text-green-700 dark:text-green-300' : '' }}">
                            Actifs
                        </a>
                        <a href="{{ route('charging-points.index', ['filter' => 'maintenance']) }}" 
                           class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors min-h-[36px] {{ request('filter') == 'maintenance' ? 'bg-yellow-100 dark:bg-yellow-900 border-yellow-400 dark:border-yellow-700 text-yellow-700 dark:text-yellow-300' : '' }}">
                            Maintenance
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Card View (hidden on lg+) -->
            <div class="block lg:hidden">
                @forelse($chargingPoints as $chargingPoint)
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-start space-x-3 mb-3">
                            <!-- 3D Model Preview -->
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                                    <canvas id="model-preview-{{ $chargingPoint->id }}" class="w-full h-full"></canvas>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate pr-2">{{ $chargingPoint->name }}</h4>
                                    <!-- Status Badge -->
                                    @if($chargingPoint->status == 'online')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1"></span>
                                            En ligne
                                        </span>
                                    @elseif($chargingPoint->status == 'offline')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 bg-red-400 rounded-full mr-1"></span>
                                            Hors ligne
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 bg-yellow-400 rounded-full mr-1"></span>
                                            Maintenance
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                                    <div class="truncate">{{ $chargingPoint->serial_number }}</div>
                                    <div class="truncate">{{ $chargingPoint->city }}, {{ $chargingPoint->address }}</div>
                                    <div class="truncate">Groupe: {{ $chargingPoint->group->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="{{ route('charging-points.show', $chargingPoint) }}" 
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Voir
                            </a>
                            <a href="{{ route('charging-points.edit', $chargingPoint) }}" 
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Modifier
                            </a>
                            <form action="{{ route('charging-points.destroy', $chargingPoint) }}" method="POST" class="flex-1 delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-full inline-flex items-center justify-center px-3 py-2 border border-red-300 dark:border-red-600 rounded-lg text-xs font-medium text-red-700 dark:text-red-300 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <p class="mt-2 text-sm">Aucun point de charge trouvé</p>
                    </div>
                @endforelse
            </div>
            
            <!-- Desktop Table View (hidden on mobile) -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Localisation</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Groupe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($chargingPoints as $chargingPoint)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                                                <canvas id="model-preview-table-{{ $chargingPoint->id }}" class="w-full h-full"></canvas>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $chargingPoint->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $chargingPoint->serial_number }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($chargingPoint->status == 'online')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                            <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5"></span>
                                            En ligne
                                        </span>
                                    @elseif($chargingPoint->status == 'offline')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                            <span class="w-1.5 h-1.5 bg-red-400 rounded-full mr-1.5"></span>
                                            Hors ligne
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                            <span class="w-1.5 h-1.5 bg-yellow-400 rounded-full mr-1.5"></span>
                                            Maintenance
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $chargingPoint->city }}, {{ $chargingPoint->address }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $chargingPoint->group->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-3">
                                        <a href="{{ route('charging-points.show', $chargingPoint) }}" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 transition-colors">Voir</a>
                                        <a href="{{ route('charging-points.edit', $chargingPoint) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors">Modifier</a>
                                        <form action="{{ route('charging-points.destroy', $chargingPoint) }}" method="POST" class="inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Aucun point de charge trouvé
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($chargingPoints->hasPages())
                <div class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $chargingPoints->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

<script src="{{ asset('js/vendor/three.min.js') }}"></script>
<script src="{{ asset('js/vendor/GLTFLoader.js') }}"></script>
<script src="{{ asset('js/charging-point-3d-viewer.js') }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // S'assurer que le body n'a pas overflow:hidden par défaut
        document.body.style.overflow = '';
        
        // Simple delete confirmation
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer ce point de charge ?')) {
                    e.preventDefault();
                }
            });
        });

        // Initialize 3D model previews
        initializeModelPreviews();
    });

    // Cache pour éviter de créer trop de contextes WebGL
    const webglContexts = new Map();
    let contextCounter = 0;
    const MAX_CONTEXTS = 5;

    function initializeModelPreviews() {
        const mobilePreviews = document.querySelectorAll('[id^="model-preview-"]:not([id*="table"])');
        const tablePreviews = document.querySelectorAll('[id^="model-preview-table-"]');
        const allPreviews = [...mobilePreviews, ...tablePreviews];
        const limitedPreviews = allPreviews.slice(0, MAX_CONTEXTS);
        
        limitedPreviews.forEach((canvas, index) => {
            const isTablePreview = canvas.id.includes('table');
            createSmallModelPreview(canvas, {
                modelPath: '/models/charging-point.glb',
                autoRotate: true,
                rotationSpeed: isTablePreview ? 0.008 : 0.01,
                enableShadows: false,
                enableControls: false,
                showLoading: false,
                backgroundColor: 0x000000,
                backgroundAlpha: 0,
                contextId: `context_${index}`
            });
        });

        allPreviews.slice(MAX_CONTEXTS).forEach(canvas => {
            showStaticFallback(canvas);
        });
    }

    function showStaticFallback(canvas) {
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        ctx.fillStyle = '#10b981';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#ffffff';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('⚡', canvas.width/2, canvas.height/2 + 4);
    }

    function createSmallModelPreview(canvas, options = {}) {
        if (!canvas) return;
        if (contextCounter >= MAX_CONTEXTS) {
            showStaticFallback(canvas);
            return;
        }

        try {
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, 1, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ 
                canvas: canvas, 
                alpha: true, 
                antialias: false,
                preserveDrawingBuffer: false,
                powerPreference: "low-power"
            });
            
            renderer.setSize(32, 32);
            renderer.setClearColor(0x000000, 0);
            renderer.shadowMap.enabled = options.enableShadows || false;
            renderer.outputEncoding = THREE.sRGBEncoding;

            const contextId = options.contextId || `context_${contextCounter++}`;
            webglContexts.set(contextId, { renderer, scene, camera });

            canvas.addEventListener('webglcontextlost', (event) => {
                event.preventDefault();
                webglContexts.delete(contextId);
                contextCounter--;
            });

            const ambientLight = new THREE.AmbientLight(0x404040, 0.8);
            scene.add(ambientLight);

            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.6);
            directionalLight.position.set(5, 5, 5);
            scene.add(directionalLight);

            camera.position.set(0, 1, 3);
            camera.lookAt(0, 0, 0);

            const loader = new THREE.GLTFLoader();
            loader.load(
                options.modelPath || '/models/charging-point.glb',
                function (gltf) {
                    const model = gltf.scene;
                    model.traverse((child) => {
                        if (child.isMesh) {
                            child.castShadow = false;
                            child.receiveShadow = false;
                        }
                    });
                    
                    const box = new THREE.Box3().setFromObject(model);
                    const center = box.getCenter(new THREE.Vector3());
                    const size = box.getSize(new THREE.Vector3());
                    
                    model.position.sub(center);
                    const maxDim = Math.max(size.x, size.y, size.z);
                    const scale = 2 / maxDim;
                    model.scale.setScalar(scale);
                    model.position.y = -box.min.y * scale;
                    
                    scene.add(model);
                },
                undefined,
                function (error) {
                    createFallbackPreview(scene);
                }
            );

            function animate() {
                requestAnimationFrame(animate);
                if (options.autoRotate !== false) {
                    scene.rotation.y += options.rotationSpeed || 0.01;
                }
                renderer.render(scene, camera);
            }
            animate();

        } catch (error) {
            showStaticFallback(canvas);
        }
    }

    function createFallbackPreview(scene) {
        const geometry = new THREE.CylinderGeometry(0.3, 0.4, 1.5, 8);
        const material = new THREE.MeshLambertMaterial({ color: 0x10b981 });
        const station = new THREE.Mesh(geometry, material);
        station.position.y = 0.75;
        scene.add(station);

        const headGeometry = new THREE.BoxGeometry(0.6, 0.4, 0.3);
        const headMaterial = new THREE.MeshLambertMaterial({ color: 0x1f2937 });
        const head = new THREE.Mesh(headGeometry, headMaterial);
        head.position.set(0.3, 1.4, 0);
        scene.add(head);
    }
</script>
@endsection